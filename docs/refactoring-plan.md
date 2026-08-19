# Plan de refactoring — séparation Entity / Repository

> Objectif : rendre le code propre et lisible avant la mise en public du repo.
> Constat : la couche `src/Models/` mélange les concepts d'**entité** (données, état) et de
> **repository** (accès SQL, persistance). Aucun test n'existe aujourd'hui (CI = `php -l` + smoke tests Docker).

---

## État des lieux

| Fichier | Rôle actuel | Problème |
|---|---|---|
| `src/Models/User.php` | CRUD SQL statique (`findById`, `create`, …) | retourne des `array` bruts, aucun état |
| `src/Models/Image.php` | CRUD + requêtes aggrégées (`findPage`, `findForDetail`) | mélange entité, repository **et** read-model (counts, `liked`) |
| `src/Models/Comment.php` | CRUD SQL statique | idem |
| `src/Models/Like.php` | `toggle` / `countFor` | idem |
| `src/Models/Token.php` | CRUD + hash SHA-256 | idem |

**Problèmes concrets :**
1. **Conflation entité/repository** — les classes « modèles » sont des classes statiques sans propriétés ; l'entité n'existe que comme tableau associatif (`$user['username']`).
2. **Zéro typage** — `array<string, mixed>` partout ; les vues accèdent aux clés par convention (`$image['author']`), aucune vérification possible.
3. **Couplage au singleton** — `Database::pdo()` appelé en dur dans chaque méthode statique → intestable, non injectable.
4. **SQL éparpillé** — chaque classe embarque ses requêtes ; certaines sont des read-models (compteurs, drapeau `liked`) qui n'ont rien à voir avec l'entité.
5. **Logique métier dans les contrôleurs** — validation d'inscription (≈50 lignes), envoi d'email de notification, cycle de vie des jetons.
6. **Accès directs à `Database`** — `HomeController::databaseStatus()`.

---

## Cible

```
src/
├── Entities/        ← données pures, typées, immuables (User, Image, Comment, Like, Token)
│   └── GalleryImage.php   ← DTO de lecture (image + auteur + counts + liked + commentaires)
├── Repositories/    ← SQL uniquement, PDO injecté (UserRepository, ImageRepository, …)
├── Services/        ← logique métier (AuthService, NotificationService)
├── Controllers/     ← fins : requête → service → vue/redirection
└── Core/            ← inchangé + Container (injection des dépendances)
```

---

## Étapes

### Étape 0 — Filet de sécurité (préalable, non négociable)

- ✅ **FAIT (PR 0, branche `refactor/models-split`)** : `composer.json` **dev-only** (PHPUnit 12 + PHPStan 2), `phpstan.neon` (niveau 6, vert), `phpunit.xml`, tests d'intégration MySQL des 5 modèles actuels (29 tests / 85 assertions), job CI `test` (PHPStan + PHPUnit avec service MySQL), `Env::set()` pour pointer les tests vers `camagru_test`.
- ✅ **Décision actée** : option A — le runtime reste zéro-dépendance (le Dockerfile n'installe pas Composer ; `vendor/` est ignoré, `composer.lock` versionné).
- ⚠️ Ne rien casser : chaque PR doit passer la CI existante (lint + build + test).

### Étape 1 — Entités typées (`src/Entities/`)

- Classes `final`, propriétés typées, `readonly` quand possible, constructeur privé + fabrique `fromRow(array $row): self`.
- **Aucune** référence à `Database` ou au SQL dans ces classes — données pures.
- `User` : `id`, `username`, `email`, `passwordHash`, `isActive`, `notifyComments`, `createdAt`.
- `Image` : `id`, `userId`, `filename`, `createdAt`.
- `Comment`, `Like`, `Token` : idem sur le schéma.
- `GalleryImage` (DTO de lecture) : compose une `Image` + `author`, `likesCount`, `commentsCount`, `liked`, `comments` — c'est le read-model des pages galerie/détail.
- ✅ **Décision actée** : `created_at` en `DateTimeImmutable`. ~10 appels `strtotime`/`date` à migrer dans les vues (`format('d/m/Y H:i')`), comportement à préserver.

### Étape 2 — Repositories (`src/Repositories/`)

- Une classe par agrégat : `UserRepository`, `ImageRepository`, `CommentRepository`, `LikeRepository`, `TokenRepository`.
- Constructeur `__construct(private readonly PDO $pdo)` — le singleton `Database::pdo()` n'est appelé qu'à un seul endroit (le wiring).
- Déplacer les méthodes statiques actuelles en méthodes d'instance, renvoyant **entités/DTO** (plus `null` que `false`/`[]` quand absent) :
  - `UserRepository` : `findById`, `findByLogin`, `findByEmail`, `emailExists`, `usernameExists`, `create`, `activate`, `updateProfile`, `updatePassword`.
  - `ImageRepository` : `countAll`, `findById`, `findByUser`, `create`, `deleteOwned`, + `findPage`/`findForDetail` → `GalleryImage`.
  - `CommentRepository` : `create`, `findForImage`, `countFor`.
  - `LikeRepository` : `toggle`, `countFor`.
  - `TokenRepository` : `create`, `findUser`, `deleteFor`.
- Supprimer `src/Models/` une fois contrôleurs et vues migrés.

### Étape 3 — Injection & wiring (`App\Core\Container`) ✅

- ✅ **PR 3 faite** : `App\Core\Container` maison (fabriques explicites + autowiring par réflexion, instances en singleton) ; seule fabrique enregistrée : `PDO` → `Database::pdo()` (index.php).
- ✅ `Router` résout les contrôleurs via le conteneur (plus de `new $class()`).
- ✅ Constructeurs non-nullables `public function __construct(private readonly UserRepository $users, ...)` — les fallbacks `Database::pdo()` ont disparu.
- ✅ `App\Core\HealthCheck` (PDO injecté) : `HomeController` n'accède plus directement à `Database`.
- ✅ Test unitaire du conteneur (autowiring, singletons, fabriques, erreurs) — 43 tests / 139 assertions.

### Étape 4 — Services (contrôleurs fins) ✅

- ✅ **PR 4 faite** : `App\Services\AuthService` (validations inscription/profil/mot de passe — `validatePassword` pur et statique —, `register` + jeton + email, `confirm`, `sendResetLink` anti-énumération, `reset`, `updateProfile`), `App\Services\NotificationService` (`notifyComment` : auto-commentaire, auteur introuvable et préférence désactivée filtrés).
- ✅ `AuthController` passe de 385 à ~230 lignes (requête → service → rendu/redirection ; connexion/session restent au contrôleur), `GalleryController` n'accède plus à `Mailer`/`Env`/`UserRepository` (notification via le service).
- ✅ Tests services : 12 tests d'intégration `AuthService` (comptes, jetons, validations), 4 tests unitaires `validatePassword`, gardes de `NotificationService` — 60 tests / 175 assertions.

### Étape 5 — Tests + CI

- **Unitaires** : entités (`fromRow`, accesseurs typés), services purs (règles de validation) avec repositories factices.
- **Intégration** : repositories contre la base MySQL dockerisée (réutiliser le pattern du `scripts/seed.php`). ✅ **Décision actée** : base réelle (le SQL est MySQL-typé : `ENUM`, `UNSIGNED`, `LIMIT :x` — non portable vers SQLite sans adaptation).
- **CI** : ajouter `composer install`, `phpstan analyse --level=6`, `phpunit` dans `.github/workflows/ci.yml`, en gardant les smoke tests Docker.

### Étape 6 — Nettoyage pré-public ✅

- ✅ **PR 5 faite** : historique git audité (aucun `.env`, `docker-data/` ou `public/uploads/` jamais commité, aucun secret dans le contenu) ; `.gitignore` relu (déjà complet) ; README mis à jour (structure sans `Models/`, stack avec PHPStan/PHPUnit dev-only, section Tests automatisés, section Contribution, licence) ; licence **MIT** ajoutée (`LICENSE` + `composer.json`).

---

## Ordre des PRs (séquençage par agrégat, chaque PR garde la CI verte)

1. **PR 0** ✅ : filet de sécurité (Composer dev, PHPStan, PHPUnit, tests d'intégration sur l'existant).
2. **PR 1** ✅ : entités `User`/`Token` + `UserRepository`/`TokenRepository`, `AuthController` et `GalleryController` (notification auteur) migrés, vue profil sur accesseurs typés, `seed.php` migré, `Models/User.php` + `Models/Token.php` supprimés. Injection manuelle (fallback `Database::pdo()`) en attendant le conteneur de la PR 3. Tests adaptés (32 tests / 101 assertions) + tests unitaires entités.
3. **PR 2** ✅ : entités `Image`/`Comment`/`Like` + DTO `GalleryImage` (compose une `Image` + auteur + compteurs + `liked`, immuable via `withComments`), `ImageRepository`/`CommentRepository`/`LikeRepository`, `GalleryController`/`EditorController`/`seed.php` migrés, vues galerie/éditeur sur accesseurs typés (`createdAt()->format(...)`), couche `src/Models/` supprimée. Tests adaptés (38 tests / 132 assertions) + tests unitaires entités/DTO.
4. **PR 3** ✅ : `Container` + `Router`, constructeurs sans fallback, `HomeController` via `HealthCheck`.
5. **PR 4** ✅ : services (`AuthService`, `NotificationService`), contrôleurs fins.
6. **PR 5** ✅ : nettoyage pré-public (README, licence MIT, audit d'historique sain).

## Risques

- **Migration des vues** : ~53 accès `$image[...]`/`$user[...]` en `src/Views/` — PHPStan niveau 6 les détecte tous.
- **`created_at`** : le passage en `DateTimeImmutable` touche les vues (`strtotime`, `date`) — comportement à préserver.
- **Taille** : codebase de ~2 650 lignes, effort estimé modeste ; l'ordre par agrégat limite le risque de régression à chaque PR.
