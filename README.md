<div align="center">

# 📸 Camagru

**Une petite application web type Instagram** — capture photo (webcam ou upload), superposition d'images, publication dans une galerie publique, likes et commentaires.

Projet de fin d'année de l'école **42** — développé en **PHP vanilla** (zéro dépendance), **JavaScript natif** et **Docker**.

</div>

---

## ✨ Fonctionnalités

### Obligatoires
- **Comptes** : inscription, confirmation par email à **jeton unique**, connexion, mot de passe oublié, déconnexion en un clic, édition du profil (username, email, mot de passe).
- **Galerie publique** : toutes les images de tous les utilisateurs, triées par date, **pagination** (6 par page), **likes** et **commentaires** réservés aux connectés.
- **Notification email** à l'auteur d'une image à chaque nouveau commentaire (préférence activée par défaut, désactivable dans le profil).
- **Éditeur** : page protégée, aperçu **webcam** (`getUserMedia`) avec images superposables **PNG à canal alpha**, **superposition composée côté serveur** (GD), upload d'image en alternative, vignettes des photos précédentes, suppression de ses propres images uniquement.

### Bonus
- 🎬 **Aperçu en direct** de la superposition sur le flux webcam avant capture (WYSIWYG).
- ⚡ **Galerie AJAX** : likes, commentaires et pagination sans rechargement (`fetch`, avec repli propre sans JavaScript).
- ♾️ **Pagination infinie** (IntersectionObserver), bouton « Suivant » conservé comme repli sans JS.
- 🔗 **Partage social** X / Facebook par image (dernier commentaire en citation) + **page de détail** `/image/{id}`.
- 🎞️ **Export GIF animé** : l'overlay pulse et flotte sur la capture, encodé **côté serveur en PHP pur** (GIF89a + NETSCAPE2.0).

---

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Backend | **PHP 8.3** — MVC maison, `PDO` (requêtes préparées), `GD` (images), `mail()` → MailHog |
| Frontend | HTML, CSS pur, **JavaScript natif** (aucune librairie) |
| Base de données | **MySQL 8** (utf8mb4) |
| Email (dev) | **MailHog** (UI sur `http://localhost:8025`) |
| Déploiement | **Docker Compose** — une seule commande |

Aucun framework, aucune dépendance Composer : uniquement la bibliothèque standard PHP et les API natives du navigateur.

---

## 🚀 Démarrage rapide

### Prérequis
- Docker + Docker Compose

### Installation

```bash
# 1. Configurer l'environnement (le .env réel est ignoré par git)
cp .env.example .env

# 2. Lancer toute la stack en une commande
docker compose up --build
```

Le site est alors disponible sur **http://localhost:8080** et MailHog sur **http://localhost:8025**.

### Données de démonstration (optionnel)

```bash
# Crée 3 comptes (alice, bob, carol — mot de passe : Seedpass123),
# 13 images et quelques likes/commentaires.
docker compose exec web php scripts/seed.php
```

---

## 📁 Structure du projet

```
├── docker/                 # Config Apache, PHP, ssmtp
├── database/schema.sql     # Schéma MySQL (monté au premier démarrage)
├── docker-compose.yml      # web (8080) + db + mailhog (8025)
├── public/                 # Front controller, assets, uploads
│   └── assets/overlays/    # Images superposables (PNG alpha)
├── scripts/                # seed.php, generate_overlays.php
└── src/
    ├── Controllers/        # Auth, Gallery, Editor, Home
    ├── Core/               # Router, Database, Csrf, Session, Mailer,
    │                       # Compositor, GifEncoder…
    ├── Models/             # User, Token, Image, Like, Comment
    └── Views/              # Vues + layout (header/main/footer)
```

---

## 🔒 Sécurité

- **Mots de passe** hachés (`password_hash` / `password_verify`), jamais en clair.
- **XSS** : toute sortie échappée (`htmlspecialchars`), y compris les commentaires.
- **SQLi** : 100 % de requêtes préparées PDO.
- **CSRF** : jeton par session vérifié sur chaque formulaire POST (réponses JSON 403 en AJAX).
- **Uploads** : triple validation (MIME réel `finfo` + extension + décodage GD) puis **ré-encodage** complet — aucun contenu exécutable n'est stocké.
- **Superposition côté serveur** : le client n'envoie qu'une image ; le rendu final est composé par GD, jamais côté client.
- **Sessions** : cookie `HttpOnly` + `SameSite=Lax`, `session_regenerate_id()` après connexion.
- **Accès** : pages/actions sensibles protégées côté serveur ; suppression limitée à ses propres images ; `return_path` validé (anti open-redirect).
- `.env`, uploads et données de la base **jamais commités** (`.gitignore`).

---

## 🧪 Tests

Le projet a été vérifié de bout en bout (curl + navigateur réel) :

- Flux complets : inscription → email → confirmation → connexion → profil → déconnexion ; reset de mot de passe.
- Galerie : pagination, tri, likes/commentaires, notification email (et désactivation), défilement infini.
- Éditeur : capture webcam, superposition serveur vérifiée **au pixel près**, uploads refusés (faux PNG, `.php`, `.exe`), suppression.
- Sécurité : SQLi, XSS, CSRF, anti-traversal, open-redirect — tous refusés.
- Consoles : zéro erreur PHP / navigateur / réseau ; responsive validé en mobile et desktop.

---

## 📄 Licence

Projet pédagogique réalisé dans le cadre de l'école **42**. Aucune licence particulière.
