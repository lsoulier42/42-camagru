# Camagru — Kit pour agents de coding

Fichiers du kit :
- **`SPEC.md`** — les specs complètes (transcription fidèle du PDF v4.1 + recommandations). C'est le fichier à donner à l'agent.
- **`CHECKLIST.md`** — la checklist de tests pour vérifier le travail (et se préparer à la soutenance).

## Comment l'utiliser avec un agent de coding

### Option A — un seul agent, tout le projet (recommandé pour démarrer)
Copie ceci dans ton agent (Codex / Claude Code / OpenCode) :

```
Lis le fichier SPEC.md du projet Camagru et implémente-le intégralement :
- partie obligatoire (auth, galerie, éditeur, sécurité, docker-compose)
- puis les bonus, dans l'ordre de rentabilité indiqué dans la spec
Contraintes absolues : PHP vanilla (zéro dépendance Composer), JS natif,
zéro erreur/warning dans les consoles, .env hors git, déploiement en une commande.
Vérifie ton travail avec CHECKLIST.md et corrige tout ce qui ne passe pas.
```

### Option B — un agent par partie (projet par étapes)
1. Agent 1 : « Implémente le squelette MVC + docker-compose + layout responsive + connexion PDO + .env (section 2 et 3.1 de SPEC.md). »
2. Agent 2 : « Implémente l'auth complète (section 3.2 de SPEC.md) : inscription, confirmation email, login, reset, déconnexion, édition profil, CSRF. »
3. Agent 3 : « Implémente la galerie (section 3.3) : pagination ≥ 5, likes, commentaires, notification email. »
4. Agent 4 : « Implémente l'éditeur (section 3.4) : webcam, superposition serveur GD, upload, suppression. »
5. Agent 5 : « Passes les tests de sécurité de CHECKLIST.md et corrige tout. »
6. Agent bonus : « Ajoute les bonus dans cet ordre : aperçu en direct, AJAX, pagination infinie, partage, GIF. »

## Rappels
- **Pas de Symfony ici** : le sujet Camagru interdit les frameworks serveur (PHP stdlib uniquement). Les autres sujets web de 42 sont différents — vérifier chaque PDF.
- Les images superposables doivent être des **PNG avec canal alpha** (les fournir avec le projet).
- Tester la version finale avec CHECKLIST.md **dans Firefox et Chrome**, en mobile et desktop.
