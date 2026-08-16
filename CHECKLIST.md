# Camagru — Checklist de tests (préparation soutenance)

> À parcourir intégralement avant de dire « c'est fini ». Chaque case doit être vérifiée **sur le site déployé via docker-compose**, en vrai navigateur (Firefox ET Chrome).

## Déploiement & environnement
- [ ] `docker compose up` (ou équivalent) démarre tout en **une commande**.
- [ ] `.env` présent localement, **absent de git** (`git status` propre).
- [ ] Aucune erreur/warning dans : logs PHP, logs serveur web, console navigateur (F12), réseau.
- [ ] Layout : header + section principale + footer visibles sur toutes les pages.
- [ ] Affichage correct en mobile (DevTools, vue responsive) et en desktop.

## Inscription & compte
- [ ] Inscription avec email invalide → refusé avec message.
- [ ] Inscription avec mot de passe trop faible → refusé (complexité minimale).
- [ ] Email de confirmation reçu (MailHog en dev) avec **lien unique**.
- [ ] Compte inactif : connexion impossible avant confirmation.
- [ ] Lien de confirmation utilisé deux fois → rejeté (jeton unique, une seule utilisation).
- [ ] Connexion username + mot de passe OK ; mauvais mot de passe → refusé.
- [ ] Mot de passe oublié → email de reset reçu, nouveau mot de passe pris en compte.
- [ ] Déconnexion en un clic, **depuis n'importe quelle page**.
- [ ] Modification du profil (username, email, mot de passe) fonctionnelle et reflétée immédiatement.
- [ ] En base : mot de passe **jamais en clair** (`password_hash` visible).

## Galerie
- [ ] Page publique accessible sans connexion.
- [ ] Affiche les images de **tous** les utilisateurs, triées par date de création (récentes d'abord).
- [ ] Pagination : ≥ 5 éléments/page, navigation précédente/suivante correcte.
- [ ] Like : refusé si non connecté (redirection ou message, pas d'erreur).
- [ ] Like/délike fonctionnel quand connecté ; compteur mis à jour.
- [ ] Commentaire : refusé si non connecté.
- [ ] Commentaire posté par un utilisateur → l'**auteur de l'image** reçoit un email de notification.
- [ ] Notification désactivée dans les préférences → plus d'email.
- [ ] Injection HTML/JS dans un commentaire → affiché comme texte, **jamais exécuté** (XSS).

## Éditeur (page protégée)
- [ ] Non connecté → redirection polie vers la connexion (pas d'accès direct par URL).
- [ ] Aperçu webcam s'affiche (autoriser la caméra) ; refus caméra → pas d'erreur console bloquante.
- [ ] Bouton de capture **désactivé** (grisé, non cliquable) tant qu'aucune image superposable n'est sélectionnée.
- [ ] Sélection d'une image superposable → bouton activé.
- [ ] Capture → image finale créée avec la superposition, **le rendu serveur doit correspondre** (vérifier dans le stockage que l'image finale contient bien la superposition — pas un simple collage client).
- [ ] Upload d'une image locale en alternative à la webcam → fonctionne.
- [ ] Upload d'un fichier non-image (ex. `.php`, `.exe`) → refusé, pas stocké.
- [ ] Upload d'un faux PNG (extension .png mais pas une image) → refusé (`getimagesize`).
- [ ] Section latérale : vignettes des photos précédentes visibles.
- [ ] Suppression : un utilisateur supprime **ses** images uniquement.
- [ ] Tentative de suppression/like/commentaire d'une image d'autrui → refusé côté serveur (tester en manipulant les requêtes : pas seulement le bouton caché).

## Sécurité (tests manuels)
- [ ] **SQLi** : `' OR 1=1--` dans username/email/recherche → aucune fuite de données, aucune erreur.
- [ ] **XSS** : `<script>alert(1)</script>` dans username/commentaire/email → stocké comme texte brut, jamais interprété.
- [ ] **CSRF** : requête POST forgée sans jeton (ou depuis un autre onglet/domaine) → refusée.
- [ ] **Upload** : aucune possibilité de déposer du contenu exécutable.
- [ ] **Données privées** : les pages/actions sensibles exigent une session valide côté serveur.
- [ ] Cookies de session `HttpOnly` (DevTools → Application → Cookies).
- [ ] `.env` jamais exposé (test direct `/`, `.env`, `.git` → 404 ou refus).

## Bonus (si implémentés)
- [ ] Aperçu en direct de la superposition sur la webcam avant capture.
- [ ] Échanges AJAX (like/commentaire/pagination sans rechargement de page).
- [ ] Pagination infinie sur la galerie.
- [ ] Partage réseaux sociaux (boutons + liens valides).
- [ ] GIF animé rendu.
- [ ] Chaque bonus : **fonctionnel à 100 %**, sans bug visible (sinon il ne compte pas).
