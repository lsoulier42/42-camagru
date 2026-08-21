# DESIGN.md — Camagru Design System

> **Positionnement : « Le studio créatif »**
>
> Camagru n'est pas une app d'archivage photo, c'est un outil de création visuelle.
> L'ambiance est celle d'un studio pro (type Picsart/Lensa) : fond sombre,
> accent vif, canvas au centre de l'attention. Tout doit donner envie de créer.

---

## 1. Design tokens (CSS custom properties, déclarés dans `:root`)

### Couleurs

| Token         | Valeur     | Usage                                          |
|---------------|------------|-------------------------------------------------|
| `--bg-0`      | `#0E0F13`  | Fond de l'application (body)                    |
| `--bg-1`      | `#16181D`  | Surfaces, cartes, header, footer                |
| `--bg-2`      | `#1E2128`  | Hover, éléments interactifs au survol           |
| `--text-0`    | `#F5F6F7`  | Texte principal                                 |
| `--text-1`    | `#A6ADB8`  | Texte secondaire, jamais en pleine intensité    |
| `--accent`    | `#FF5C7A`  | Accent unique (rose vif)                        |
| `--border`    | `rgba(255,255,255,.06)` | Bordures subtiles                  |
| `--success`   | `#2ECC71`  | Succès, validation                              |
| `--danger`    | `#E74C3C`  | Erreur, danger                                  |

**Règle d'or** : l'accent `#FF5C7A` est le SEUL point de couleur vive dans l'app.
Le dégradé `linear-gradient(#FF5C7A, #FF8A3D)` est réservé à l'éditeur et au logo —
pas de rainbow partout.

### Typographie

| Token               | Valeur                                                       | Usage                      |
|----------------------|-------------------------------------------------------------|----------------------------|
| `--font-display`    | `'Space Grotesk', 'Sora', system-ui, sans-serif`            | Logo, titres, hero         |
| `--font-body`       | `'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif` | Corps de texte             |
| `--font-mono`       | `'JetBrains Mono', 'Fira Code', monospace`                  | _(optionnel, non utilisé)_ |

Google Fonts à charger dans le `<head>` (une seule ligne) :
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
```

**Règles typographiques** :
- Display (Space Grotesk) pour le logo et les titres de section (`h1`, `.hero h1`)
- Corps (Inter) partout ailleurs
- Libellés courts en majuscules espacées : `text-transform: uppercase; letter-spacing: 0.08em`

### Rayons & ombres

| Token         | Valeur                                    |
|---------------|-------------------------------------------|
| `--radius-lg` | `20px` (cartes, landing, surfaces larges) |
| `--radius-md` | `16px` (éléments moyens, inputs)          |
| `--radius-sm` | `12px` (petits éléments, boutons)         |
| `--shadow`    | `0 2px 12px rgba(0,0,0,.25)`             |

### Espacements

Inspirés d'une échelle 4px : `4, 8, 12, 16, 20, 24, 32, 40, 48, 64`.

---

## 2. Composants

### Header
- Fond `--bg-1`, bordure inférieure `--border`
- Logo en Space Grotesk bold, couleur accent (dégradé discret)
- Navigation : liens en `--text-1`, actif en `--text-0` avec underline accent
- Sticky, z-index élevé

### Footer
- Fond `--bg-1`, bordure supérieure `--border`
- Texte `--text-1`, discret

### Boutons
- `.btn` : fond transparent, bordure `--border`, texte `--text-0`
- `.btn--primary` : fond `--accent`, texte blanc, bordure `--accent`
- `.btn--ghost` : fond transparent, hover `--bg-2`
- `border-radius: 999px` (pill)
- Transition 200ms ease sur background, color, transform
- Feedback au clic : `transform: scale(0.97)`
- `:focus-visible` : outline 2px `--accent`, offset 2px

### Inputs / formulaires
- Fond `--bg-1`, bordure `--border`, texte `--text-0`
- Focus : bordure `--accent`, box-shadow `0 0 0 3px rgba(255,92,122,.15)`
- Placeholder en `--text-1`

### Cartes (galerie)
- Fond `--bg-1`, border `--border`, border-radius `--radius-lg`
- Hover subtil : `transform: translateY(-2px)`, shadow plus prononcée, transition 250ms
- Image en `object-fit: cover`, aspect-ratio 4:3

---

## 3. Pages

### Landing page
- Hero centré avec mock du concept :
  - Un visage stylisé (CSS-only ou SVG inline) avec stickers superposés
  - Titre en Space Grotesk, large, caractère fort
  - Sous-titre en `--text-1`
  - CTA principal en `.btn--primary`
- Section features en cartes arrondies, 3 colonnes responsive

### Galerie
- Grille responsive `auto-fill, minmax(280px, 1fr)`
- Cartes arrondies, hover subtil, image couvrante
- Like/commentaire intégrés à la carte
- Défilement infini avec sentinelle
- Skeletons pendant le chargement AJAX (pulsations)

### Éditeur (« l'atelier »)
- **C'est LE cœur de l'app**
- Mode plein écran « atelier » :
  - Canvas sombre (`--bg-0`) occupant tout l'espace
  - Preview webcam centrée, aspect-ratio 4:3, miroir horizontal
  - Panneau latéral glissant pour stickers/filtres (icônes ligne monochrome)
    - Icônes : SVG inline ou PNG, rendu en `--text-1`, accent au survol/sélection
  - Barre d'actions en bas : capture, GIF, upload
- Overlay preview en superposition temps réel
- Transitions douces sur les stickers

### Pages auth (login, register, forgot, reset)
- Carte centrée, fond `--bg-1`, border-radius `--radius-lg`
- Formulaire épuré
- Liens en `--accent`

### Profil
- Même carte que les pages auth
- Section « changer de mot de passe » séparée par un divider

### Pages d'erreur
- Centré, illustration CSS simple, CTA retour à l'accueil

---

## 4. États

### Empty state
- Icône CSS (emoji ou dessin simple)
- Texte : « Aucune photo — capture ta première ! »
- Lien vers l'éditeur

### Loading (skeleton)
- Animation de pulsation (`@keyframes pulse`)
- Forme des cartes, gris `--bg-2` qui pulse vers `--bg-1`

### État désactivé
- `opacity: 0.4`, `cursor: not-allowed`

### Feedback clic
- `transform: scale(0.97)` sur les boutons
- Transition 150ms

### Focus visible (accessibilité clavier)
- `:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }`
- Jamais de `outline: none` sans alternative visible

---

## 5. Responsive

- Mobile-first : tout est conçu pour ≤ 375px d'abord
- Breakpoints :
  - `≥ 640px` : tablette
  - `≥ 900px` : desktop, éditeur en 2 colonnes
  - `≥ 1080px` : conteneur max-width
- Pas de scrollbar parasite : `overflow-x: hidden` au besoin, mais jamais sur body
- Images : `max-width: 100%`, jamais de débordement

---

## 6. Micro-interactions

| Élément            | Effet                                          |
|--------------------|------------------------------------------------|
| Stickers (éditeur) | Apparition en `scale(0.8)` → `scale(1)`        |
| Cartes (galerie)   | Hover `translateY(-2px)` + shadow               |
| Boutons            | `scale(0.97)` au clic + transition             |
| Like               | Animation cœur qui pulse                       |
| Loader             | Skeleton pulsation                             |
| Flash messages     | Slide-in depuis le haut + auto-dismiss (optionnel) |

---

## 7. Implémentation

- **Un seul fichier CSS** : `public/assets/css/style.css`
- **Zéro build step** : tout est en CSS custom properties natif
- **Zéro framework** : pas de Bootstrap, Tailwind, etc.
- **Google Fonts** : chargées en `<link>` dans le `<head>`
- **Icônes** : SVG inline ou PNG dans `public/assets/overlays/`
- **JS natif** : déjà en place (`editor.js`, `gallery.js`)