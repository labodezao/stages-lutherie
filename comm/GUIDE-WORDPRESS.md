# Guide : Utiliser les fichiers HTML dans WordPress

> **Ewen d'Aviau — Stages de lutherie · stages.ewendaviau.com**

---

## Vue d'ensemble des fichiers

| Fichier | Type | Usage |
| --------- | ------ | ------- |
| **`page-blocs-gutenberg.txt`** | ⭐ **Blocs Gutenberg natifs** | Coller dans l'éditeur de code → tous les blocs sont éditables visuellement |
| `commercial-page-wordpress.html` | Page HTML monolithique | Intégrer via un bloc HTML personnalisé (moins flexible) |
| `visuels-bilingues.html` | **Outil de captures d'écran** | Ouvrir dans le navigateur → faire une capture → uploader l'image |
| `visuels-urgence-avril.html` | **Outil de captures d'écran** | Idem — version française uniquement |
| `visuals-urgency-april.html` | **Outil de captures d'écran** | Idem — version anglaise uniquement |

---

## ⭐ PARTIE 0 — Page en Blocs Gutenberg natifs (recommandée)

Le fichier **`page-blocs-gutenberg.txt`** contient la page complète en **blocs WordPress natifs**.  
Chaque section devient un bloc éditable visuellement : vous pouvez modifier les textes, couleurs, boutons directement dans l'éditeur Gutenberg, sans toucher au code.

### Contenu de la page (311 blocs)

| Section | Blocs utilisés |
| --------- | --------------- |
| 🎨 Héro (fond brun) | `wp:cover` + `wp:heading` + `wp:paragraph` |
| 📅 Dates sessions | `wp:group` + `wp:columns` (2 colonnes) |
| 👥 À propos | `wp:group` + `wp:columns` (3 colonnes) |
| 💰 Tarifs (3 modèles) | `wp:group` + `wp:columns` (3 colonnes) |
| 📋 Programme 10 jours | `wp:group` × 10 jours en colonnes |
| 🎛 Options & personnalisation | `wp:columns` (4 + 4) |
| ℹ️ Infos pratiques | `wp:columns` (3 + 3) |
| ✍️ CTA Inscription | `wp:cover` + `wp:buttons` |
| 📞 Contact | `wp:columns` (3 colonnes) |

### Comment l'importer — 3 étapes

**Étape 1 — Créer une nouvelle page**

Dans WordPress : **Pages → Ajouter une page**

Donnez-lui un titre, par exemple : `Stage accordéon diatonique 2026`

**Étape 2 — Ouvrir l'éditeur de code Gutenberg**

1. Dans l'éditeur Gutenberg, cliquez sur les **3 points ⋮** (coin supérieur droit)
2. Choisissez **"Éditeur de code"** (ou appuyez sur `Ctrl+Shift+Alt+M`)
3. Vous voyez maintenant le code brut de la page

**Étape 3 — Coller les blocs**

1. Sélectionnez tout le contenu existant (**Ctrl+A** ou **Cmd+A**) et supprimez-le
2. Ouvrez le fichier `page-blocs-gutenberg.txt` et copiez tout son contenu (**Ctrl+A** puis **Ctrl+C**)
3. Collez dans l'éditeur de code WordPress (**Ctrl+V**)
4. Cliquez à nouveau sur **⋮ → "Éditeur visuel"** pour revenir en mode visuel
5. Tous vos blocs apparaissent — vous pouvez maintenant cliquer sur n'importe quel texte pour le modifier !
6. Cliquez **Publier**

> 💡 **Astuce** : Si vous voyez un message "Ce bloc contient du contenu inattendu", cliquez sur **"Convertir en blocs HTML"** puis **"Récupérer"** pour forcer la conversion.

### Modifier les blocs après import

Une fois importé, chaque élément est modifiable directement dans Gutenberg :

- **Changer une date** → cliquez sur "8 – 17 avril" → modifiez le texte
- **Changer un tarif** → cliquez sur "4 500 €" → modifiez
- **Changer la couleur de fond** → cliquez sur la section → panneau latéral → Couleur
- **Ajouter un formulaire** → cliquez entre deux blocs → **+** → cherchez "Contact Form 7" ou "WPForms"
- **Ajouter une image** → cliquez sur n'importe quel bloc → **+** au-dessus → "Image"

---

## PARTIE 1 — Page commerciale HTML dans WordPress (méthode alternative)

Le fichier `commercial-page-wordpress.html` est une version HTML autonome (tout-en-un).  
Moins flexible que les blocs, mais utile si vous avez des problèmes de compatibilité de thème.

### Méthode — Bloc HTML personnalisé (Gutenberg)

**Étape 1 — Copier le CSS**

1. Ouvrez `commercial-page-wordpress.html` dans un éditeur de texte (VS Code, Notepad++…)
2. Copiez tout le contenu entre `<style type="text/css">` et `</style>`
3. Dans WordPress, allez dans **Apparence → Personnaliser → CSS additionnel**
4. Collez le CSS et cliquez **Publier**

**Étape 2 — Créer la page**

1. Dans WordPress : **Pages → Ajouter**
2. Donnez un titre : par exemple `Stage accordéon diatonique 2026`
3. Cliquez le bouton **+** pour ajouter un bloc
4. Cherchez **HTML personnalisé** et sélectionnez-le
5. Dans `commercial-page-wordpress.html`, copiez tout le contenu entre `<body>` et `</body>`  
   *(c'est-à-dire le bloc `<div class="workshop-page">…</div>`)*
6. Collez-le dans le bloc HTML
7. Cliquez **Publier** ou **Aperçu**

**Étape 3 — Personnaliser le slug (URL)**

Dans les réglages de la page (panneau droit), changez le **Permalien** en :

```text
/stage-accordeon-diatonique/
```text
---

### Méthode alternative — Plugin "WPCode" ou "Insert Headers and Footers"

Si vous préférez ne pas toucher au CSS additionnel :

1. Installez le plugin gratuit **WPCode** (anciennement Insert Headers and Footers)
2. Allez dans **Code Snippets → Ajouter un snippet → HTML Snippet**
3. Collez tout le contenu du fichier `commercial-page-wordpress.html`
4. Insérez le snippet dans votre page via le shortcode généré : `[wpcode id="XX"]`

---

### Méthode alternative — Elementor / Divi / WPBakery

Si vous utilisez un page builder :

1. Ajoutez un widget de type **HTML** ou **Code HTML**
2. Collez le contenu de `<div class="workshop-page">…</div>`
3. Ajoutez le CSS dans le panneau **CSS personnalisé** du widget ou dans le CSS global du thème

---

## PARTIE 2 — Visuels réseaux sociaux dans WordPress

> ⚠️ **Important** : les fichiers `visuels-bilingues.html`, `visuels-urgence-avril.html` et `visuals-urgency-april.html` sont des **outils de création d'images**, pas des pages web. Ils sont conçus pour être affichés dans un navigateur à taille fixe, puis capturés en image.

### Workflow en 4 étapes

```text
1. Ouvrir le fichier HTML dans Chrome/Firefox
        ↓
2. Capturer le visuel à la bonne taille
        ↓
3. Uploader l'image dans la Médiathèque WordPress
        ↓
4. Insérer l'image dans votre page/article ou partager sur les réseaux
```text
---

### Étape 1 — Ouvrir le fichier

Double-cliquez sur `visuels-bilingues.html` (ou glissez-le dans Chrome/Firefox).

Le fichier s'ouvre et vous voyez tous les visuels organisés par concept, avec :

- 📘 🇫🇷 Facebook 1200×630
- 📘 🇬🇧 Facebook 1200×630
- 📸 🇫🇷 Instagram 1080×1080
- 📸 🇬🇧 Instagram 1080×1080

---

### Étape 2 — Capturer un visuel (3 méthodes)

#### Méthode A — Extension Chrome "GoFullPage" ou "FireShot" ⭐ Recommandée

1. Installez l'extension Chrome **GoFullPage** (gratuite) ou **FireShot**
2. Dans le menu de l'extension, choisissez **Capture Selected Area** ou **Capture Element**
3. Cliquez sur le visuel à capturer
4. L'image est téléchargée automatiquement en PNG

#### Méthode B — Outil de développeur Chrome (précis à la taille exacte)

1. Ouvrez le fichier dans Chrome
2. Appuyez sur **F12** (Outils de développeur)
3. Cliquez sur l'icône **"Toggle device toolbar"** (📱) ou **Ctrl+Shift+M**
4. Dans la barre du haut, réglez la taille selon le visuel :
   - Facebook : `1200 × 630`
   - Instagram : `1080 × 1080`
   - Story : `1080 × 1920`
5. Clic droit sur le visuel → **Capture screenshot** (ou dans le menu ⋮ → Capture node screenshot)

#### Méthode C — Logiciel de capture (macOS/Windows)

- **macOS** : `Cmd + Shift + 4` → sélectionnez le visuel à la souris
- **Windows** : `Win + Shift + S` → sélectionnez la zone
- **Snagit** ou **Greenshot** (logiciels gratuits) : encore plus précis

> 💡 **Conseil** : réglez le zoom de votre navigateur à **100%** avant de capturer pour obtenir la taille exacte (1200px, 1080px…).

---

### Étape 3 — Uploader dans WordPress

1. Dans WordPress : **Médiathèque → Ajouter**
2. Glissez vos images PNG capturées
3. Ajoutez un **Texte alternatif** (important pour le SEO) :  
   Exemple : `Visuel Facebook stage lutherie accordéon diatonique avril 2026`

---

### Étape 4 — Utiliser les images

**Pour une page/article WordPress :**

- Insérez le bloc **Image** et sélectionnez votre visuel depuis la Médiathèque

**Pour Facebook :**

- Lors de la création d'un post, cliquez **Photo/Vidéo** et uploadez l'image
- Format recommandé : `1200 × 630 px` (les fichiers v1, v2fb, etc.)

**Pour Instagram :**

- Uploadez depuis mobile ou avec l'outil de création de contenu Meta
- Format carré : `1080 × 1080 px` (les fichiers v1ig, v2, etc.)
- Format Story : `1080 × 1920 px` (les fichiers v4, v9)

---

## PARTIE 3 — Structure des visuels disponibles

Le fichier `visuels-bilingues.html` contient **44 visuels** organisés en 10 thèmes :

| # | Thème FR | Thème EN | Formats disponibles |
| --- | ---------- | ---------- | --------------------- |
| 01 | Urgence & Rareté | Urgency & Scarcity | FB + IG |
| 02 | Transformation | Life Transformation | FB + IG |
| 03 | Authenticité | Authenticity | FB + IG |
| 04 | Expérience Unique | Unique Experience | FB + IG + Story |
| 05 | Accessibilité | Accessibility | FB + IG |
| 06 | Parcours d'Ewen d'Aviau | Ewen d'Aviau's Background | FB + IG |
| 07 | Philosophie de transmission | Teaching Philosophy | FB + IG |
| 08 | Démarche Handicap | Disability Approach | FB + IG |
| 09 | Atelier Inclusif | Inclusive Workshop | FB + IG + Story |
| 10 | Créer ensemble, sans limites | Create Together, Without Limits | FB + IG |

---

## PARTIE 4 — Ajouter un lien de partage dans WordPress

Pour créer un **bouton "S'inscrire"** sur votre site WordPress qui pointe vers le formulaire d'inscription :

```html
<a href="https://stages.ewendaviau.com" 
   class="wp-block-button__link" 
   style="background:#D4A017; color:#3E2723; font-weight:bold; padding:14px 32px; border-radius:30px; text-decoration:none;">
  🌸 S'inscrire au stage d'avril →
</a>
```text
Copiez ce code dans un bloc **HTML personnalisé** dans Gutenberg.

---

## PARTIE 5 — Conseils pour les réseaux sociaux

### Fréquence de publication suggérée (7 jours avant le stage)

| Jour | Plateforme | Visuel recommandé |
| ------ | ----------- | ------------------- |
| J-7 | Facebook | Concept 1 — Urgence (FB 1200×630) |
| J-7 | Instagram | Concept 1 — Urgence (IG 1080×1080) |
| J-6 | Facebook | Concept 2 — Transformation |
| J-5 | Instagram | Concept 5 — Accessibilité |
| J-4 | Facebook | Concept 3 — Authenticité |
| J-3 | Instagram Story | Concept 4 — Expérience (Story) |
| J-2 | Facebook + Instagram | Concept 1 — Urgence (relance) |
| J-1 | Tous | Concept 1 — Urgence finale |

### Texte d'accompagnement suggéré (FR)

```text
🎵 Il ne reste plus que quelques places pour le stage d'avril !
🔨 10 jours pour fabriquer votre accordéon diatonique de A à Z
🌿 Guidé par Ewen d'Aviau, luthier professionnel à Saint-Nazaire
✅ Aucun prérequis · Tout inclus · Max 6 stagiaires

👉 Plus d'infos et inscription : stages.ewendaviau.com
#lutherie #accordeon #diatonique #stage #fabrication #saintnazaire
```text
```text
🎵 Only a few spots left for the April workshop!
🔨 10 days to build your own diatonic accordion from scratch
🌿 Led by Ewen d'Aviau, professional luthier in Saint-Nazaire, France
✅ No experience needed · Everything included · Max 6 participants

👉 Info & registration: stages.ewendaviau.com
#lutherie #accordion #diatonic #workshop #handmade #saintnazaire
```text
---

## Résumé rapide

```text
PAGE DE VENTE WORDPRESS
  → Ouvrir commercial-page-wordpress.html
  → Copier le CSS dans Apparence > CSS additionnel
  → Copier le <div class="workshop-page">…</div> dans un bloc HTML
  → Publier

IMAGES POUR LES RÉSEAUX SOCIAUX / ARTICLES
  → Ouvrir visuels-bilingues.html dans Chrome
  → Capturer le visuel avec F12 > Capture screenshot
  → Uploader dans Médiathèque WordPress ou directement sur Facebook/Instagram
```text
---

*Guide créé pour le projet stages.ewendaviau.com · Saint-Nazaire (44)*
