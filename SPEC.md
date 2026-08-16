# Camagru — Spécifications (sujet 42 v4.1)

> Objectif : petite application web type Instagram : capture photo via webcam (ou upload), superposition d'images prédéfinies à canal alpha (PNG), partage public, likes et commentaires.
> Ces specs sont une transcription fidèle du PDF officiel (`en.subject.pdf` v4.1), enrichies de recommandations d'implémentation pour l'agent de coding. Ce qui est **obligatoire** vient du PDF ; ce qui est marqué **[RECO]** est un conseil.

---

## 1. Règles strictes du sujet (ne pas enfreindre)

| Règle | Détail |
|---|---|
| **Correction** | Uniquement par des humains (pas de moulinette auto). |
| **Zéro erreur console** | Aucune erreur, warning ou log ligne dans les consoles serveur ET client. Tolérance : erreurs liées à `getUserMedia()` (pas de HTTPS). |
| **Langage serveur** | Libre, MAIS chaque fonction utilisée doit avoir un équivalent dans la **bibliothèque standard PHP**. → En pratique : **PHP pur, zéro dépendance Composer**. |
| **Langage client** | HTML, CSS, JavaScript — **API natives du navigateur uniquement** (pas de librairie JS). |
| **Frameworks** | Interdits (framework, micro-framework ou librairie) sauf **frameworks CSS sans JavaScript interdit** (ex. Bootstrap via CSS, sans son JS). |
| **Conteneurisation** | **Obligatoire** : un (ou plusieurs) conteneur pour déployer le site en **une seule commande** (docker-compose ou équivalent). |
| **Compatibilité** | Firefox >= 41 et Chrome >= 46. |
| **Credentials** | Toutes les variables d'env / clés API / credentials dans un fichier **`.env` local, ignoré par git**. Des credentials publics = échec direct du projet. |
| **Sécurité** | Aucune faille de sécurité ; au minimum les cas listés section 5. |

⚠️ **Note pour l'agent** : le sujet interdit les frameworks serveur. **Symfony/Laravel/etc. sont exclus** pour ce projet (malgré la compétence PHP-Symfony de la développeuse). PHP vanilla + PDO + GD, c'est le cœur attendu.

---

## 2. Stack recommandée [RECO]

- **Serveur** : PHP 8.x (extension **GD** pour le traitement d'images — incluse avec PHP, considérée standard), avec le serveur intégré PHP ou Apache/Nginx.
- **Base de données** : MySQL / MariaDB.
- **Déploiement** : `docker-compose.yml` avec services `php` (ou apache+php), `mysql`, `mailhog` (pour les emails en dev).
- **Mail** : fonction `mail()` de PHP (stdlib). En dev, MailHog dans le compose.
- **.env** : parser écrit à la main (interdiction de composer donc pas de `phpdotenv`) — ~15 lignes : lecture du fichier, `parse_ini_file` ou split sur `=`.

---

## 3. Partie obligatoire

### 3.1. Fonctionnalités communes
- Layout décent : **header, section principale, footer**.
- **Responsive** : affichage correct sur mobile, layout adapté aux petites résolutions.
- **Validation correcte de tous les formulaires**.
- Site **sécurisé** dans son ensemble (voir section 5).

### 3.2. Fonctionnalités utilisateur
- [ ] **Inscription** : email valide + username + mot de passe avec **niveau de complexité minimal**.
- [ ] **Confirmation de compte** : à la fin de l'inscription, l'utilisateur doit confirmer son compte via un **lien unique** envoyé par email.
- [ ] **Connexion** : username + mot de passe.
- [ ] **Mot de passe oublié** : l'app peut envoyer un email de **réinitialisation de mot de passe**.
- [ ] **Déconnexion en un clic**, depuis n'importe quelle page, à tout moment.
- [ ] **Modification du profil** (connecté) : username, email, ou mot de passe.

### 3.3. Fonctionnalités galerie
- [ ] Partie **publique** : affiche **toutes** les images éditées de **tous** les utilisateurs, **triées par date de création**.
- [ ] **Like** et **commentaire** : réservés aux utilisateurs **connectés**.
- [ ] **Notification email** : quand une image reçoit un nouveau commentaire, l'**auteur de l'image** est notifié par email. Préférence **activée par défaut**, désactivable dans les préférences utilisateur.
- [ ] **Pagination** : au moins **5 éléments par page**.

### 3.4. Fonctionnalités d'édition (page éditeur)
- Accessible **uniquement aux utilisateurs connectés** ; refuser poliment les autres (redirection vers login avec message).
- La page contient **2 sections** :
  1. **Section principale** : aperçu de la webcam de l'utilisateur + liste des images superposables + bouton de capture.
  2. **Section latérale** : vignettes de toutes les photos précédemment prises.
- [ ] Les **images superposables sont sélectionnables** ; le bouton de capture est **inactif (non cliquable) tant qu'aucune image n'est sélectionnée**.
- [ ] La **création de l'image finale (superposition des 2 images) doit être faite côté serveur**.
- [ ] **Upload d'image** en alternative à la webcam (tout le monde n'a pas de webcam).
- [ ] **Suppression** : l'utilisateur peut supprimer ses images éditées, **uniquement les siennes** (jamais celles des autres).

---

## 4. Sécurité — points MANDATOIRES

Ce qui est explicitement listé comme NON sécurisé dans le sujet (à tout prix éviter) :

1. **Mots de passe en clair ou non chiffrés** en base → utiliser `password_hash()` / `password_verify()`.
2. **Injection HTML ou JavaScript utilisateur** dans des variables mal protégées (XSS) → échapper toute sortie avec `htmlspecialchars()`.
3. **Upload de contenu indésirable** sur le serveur → valider type MIME + extension + `getimagesize()`, renommer les fichiers, restreindre le dossier d'upload.
4. **Altération d'une requête SQL** (SQL injection) → **PDO avec requêtes préparées uniquement**.
5. **Formulaire externe manipulant des données privées** (CSRF) → **jeton CSRF dans la session**, vérifié sur chaque formulaire POST.

Recommandations supplémentaires [RECO] : cookies de session `HttpOnly`, `session_regenerate_id()` après login, limite de taille d'upload, taux de hachage par défaut.

---

## 5. Déploiement

- Un `docker-compose.yml` (ou équivalent) qui lance tout le projet en **une seule commande** (ex. `docker compose up --build`).
- Doit inclure : serveur web + PHP, MySQL, service de mail (dev).
- Fichiers `.env` (credentials DB, secrets) hors git.

---

## 6. Partie bonus

> Le bonus n'est évalué que si la partie obligatoire est **PARFAITE** (intégrale et sans dysfonctionnement). Grille : granularité équivalente à **5 bonus de taille moyenne**. Un bonus substantiel peut valoir plusieurs points ; plusieurs petits bonus valent moins.
> Critère de validité d'un bonus : **utile et pertinent**, **fonctionnel à 100%**, **techniquement solide**, **cohérent avec l'app**. Un bonus incomplet/cassé/superficiel n'est pas compté.

Idées du sujet (le bonus est libre, mais ces pistes sont celles attendues) :

1. [ ] **AJAXifier** les échanges avec le serveur (like, commentaires, pagination sans rechargement).
2. [ ] **Aperçu en direct** du résultat édité, directement sur l'aperçu webcam (plus facile qu'il n'y paraît : superposition CSS/JS avant capture).
3. [ ] **Pagination infinie** de la galerie (scroll infini).
4. [ ] **Partage des images sur les réseaux sociaux**.
5. [ ] **Rendu d'un GIF animé**.

---

## 7. Ordre d'implémentation conseillé [RECO]

1. **Squelette + déploiement** : structure MVC, docker-compose (php + mysql + mailhog), `.env`, connexion PDO, layout header/main/footer responsive.
2. **Auth** : inscription + validation + hash password, confirmation par email (lien unique), connexion, déconnexion, reset password, édition du profil, session sécurisée (CSRF, HttpOnly).
3. **Galerie** : affichage public paginé (>= 5/page), likes, commentaires (connecté uniquement), notification email à l'auteur (préférence par défaut ON).
4. **Éditeur** : page protégée, webcam `getUserMedia()` + canvas → envoi au serveur, liste d'images superposables, bouton capture désactivé tant que rien n'est sélectionné, **superposition serveur (GD)**, upload de fichier, vignettes des photos précédentes, suppression de ses propres images.
5. **Nettoyage** : vérifier zéro erreur/warning dans les logs (PHP, serveur, console navigateur), tests de sécurité.
6. **Bonus** (dans l'ordre de rentabilité : aperçu en direct, AJAX, pagination infinie, partage, GIF).

---

## 8. Pièges connus (à vérifier en fin de projet)

- ⚠️ Erreur console navigateur du moindre détail (même un 404 de favicon peut être signalé).
- ⚠️ Le bouton capture doit être **vraiment désactivé** (attribut `disabled`) tant que rien n'est sélectionné.
- ⚠️ La superposition **doit** être côté serveur — ne pas se contenter d'un rendu client.
- ⚠️ Email de confirmation : le lien doit être **unique** et fonctionner une seule fois (jeton en base, pas juste un paramètre devinable).
- ⚠️ Pagination : bien 5+ éléments par page, navigation page précédente/suivante qui marche.
- ⚠️ Un utilisateur ne doit **jamais** pouvoir supprimer/liker en son nom propre sur les images des autres sans être connecté.
- ⚠️ Images superposables : prévoir des PNG **avec canal alpha**, sinon la superposition ne rend rien.
