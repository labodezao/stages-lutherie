# Outil Plan de Claviers – Guide

## Fichiers disponibles

| Fichier | Usage |
|---|---|
| `index.html` | **Outil complet** — à ouvrir en local dans le navigateur |
| `gutenberg-widget.html` | **Widget WordPress (source)** — référence HTML pour les deux versions Gutenberg |
| `clavier-accordeon-admin-gutenberg.txt` | **Import Gutenberg — PAGE ADMIN** — bloc admin toujours visible |
| `clavier-accordeon-stagiaires-gutenberg.txt` | **Import Gutenberg — PAGE STAGIAIRES** — bloc admin caché, présets préconfigurés |
| `clavier-accordeon-gutenberg.txt` | **Import Gutenberg (version originale)** — conservé pour compatibilité |

---

## Deux pages WordPress

| Page | Fichier à coller | Qui y accède ? |
|---|---|---|
| Page admin (privée/protégée) | `clavier-accordeon-admin-gutenberg.txt` | Admin uniquement |
| Page stagiaires (publique) | `clavier-accordeon-stagiaires-gutenberg.txt` | Tous les stagiaires |

### Workflow de configuration des présets

1. **Configurer** un clavier sur la **page admin** (le bloc "📋 Copier comme préset" est toujours visible)
2. Cliquer **📋 Copier comme préset** → copier le JSON généré
3. **Éditer** `clavier-accordeon-stagiaires-gutenberg.txt` → coller le JSON dans le bloc `<script id="cw-custom-presets">`
4. Recoller le fichier stagiaires dans la page WordPress des stagiaires et sauvegarder

---

## Utilisation sur WordPress

### Coller le widget dans une page

1. Dans WordPress, aller dans **Pages → Ajouter une page**
2. Cliquer **⋮ → Éditeur de code** (`Ctrl + Shift + Alt + M`)
3. Ouvrir le fichier Gutenberg approprié, sélectionner tout (`Ctrl+A`), copier, coller
4. Revenir en éditeur visuel et publier

### Aucun fichier à uploader

Les presets (GC21, AD33, etc.) sont **intégrés directement dans le widget** (`PRESETS_DATA`). Il n'y a rien à héberger ni à uploader sur le serveur WordPress.

### ⚠️ Note WordPress : opérateur `&&`

WordPress encode automatiquement `&&` en `&#038;&#038;` dans les blocs HTML personnalisés, ce qui casse le JavaScript. Les fichiers `gutenberg-widget.html` et `clavier-accordeon-gutenberg.txt` utilisent des `if` imbriqués et des ternaires à la place de `&&` pour éviter ce problème.

---

## Format JSON des plans de claviers

Les fichiers JSON exportés par le widget et par l'outil offline `index.html` sont **100 % compatibles**. Format complet :

```json
{
  "id": "1700000000000",
  "nom": "Sol/Do 21 boutons",
  "joueur": "Prénom Nom",
  "luthier": "Ewen d'Aviau",
  "dateCreation": "2026-01-15",
  "lastModified": "2026-01-15T10:30:00.000Z",
  "nbRangees": 2,
  "rangees": [
    { "nom": "Sol",  "nb": 10, "offset": 0,  "register": "N" },
    { "nom": "Do",   "nb": 10, "offset": -1, "register": "N" }
  ],
  "nbVoix": 2,
  "lhDisplayMode": "separated",
  "lhRows": [{ "nb": 6, "offset": 0 }],
  "notes": {
    "droite": [
      [ {"p":"Sol3","t":"La3"}, {"p":"Si3","t":"Do4"}, ... ],
      [ {"p":"Do3","t":"Si2"}, ... ]
    ],
    "basses":  [ {"p":"Sol2","t":"Fa#2"}, ... ],
    "accords": [ {"p":"Sol Maj","t":"Ré Min"}, ... ]
  }
}
```

**Champs clés :**
- `rangees[i].offset` — décalage vertical de la rangée (−30 … +30 demi-pas)
- `lhRows[i].offset` — décalage vertical de la rangée main gauche
- `notes.droite[rangée][bouton].p` — note poussée (↓)
- `notes.droite[rangée][bouton].t` — note tirée (↑)

### Importer un JSON dans l'outil offline

Glisser-déposer le fichier `.json` sur l'outil ou utiliser le bouton **Importer fichier**.

### Importer un JSON dans le widget WordPress

Utiliser le bouton **📂 Charger fichier JSON** dans la section *Sauvegarde & Export* du widget.

---

## Modifier les presets intégrés

### Via l'interface du widget (recommandé)

1. **Sélectionner** le modèle à modifier dans le menu déroulant
2. Modifier les notes via l'éditeur (tables ou clic sur le SVG)
3. Cliquer **⬇ Exporter JSON** pour télécharger le plan en fichier `.json`
4. Ouvrir le fichier dans un éditeur de texte pour modifier directement le JSON
5. Réimporter avec **📂 Charger fichier JSON**

Ce workflow est disponible dans les deux versions : widget WordPress et outil offline.

### Modifier les presets de l'outil offline

Pour l'outil offline `index.html`, les presets sont des fichiers JSON dans le dossier `presets/` :

```
presets/
  GC21.json
  AD33.json
  ...
```

Pour **ajouter un preset** à l'outil offline :
1. Créer `presets/MONPRESET.json` au format ci-dessus
2. Ajouter une entrée dans `PRESET_CATALOG` dans `index.html`

Pour le **widget WordPress**, les présets personnalisés admin se configurent via la **page admin** (voir workflow ci-dessus). Les présets intégrés sont dans `PRESETS_DATA` dans `gutenberg-widget.html`.

---

## Fonctionnalités du widget

- 🎹 **Sélecteur de notes** — cliquer sur un champ de note pour ouvrir le sélecteur (touches naturelles, dièses, bémols, octaves)
- 🎯 **Édition directe sur le SVG** — cliquer sur un bouton du plan pour modifier poussé/tiré
- 📐 **Décalage des rangées** — contrôle ±30 pour chaque rangée MD et MG
- 💾 **Sauvegarde locale** — via `localStorage` du navigateur
- 📄 **Copier (texte)** — exporte un résumé lisible dans le presse-papiers
- ⬇ **Exporter JSON** — télécharge le plan courant en fichier `.json` (compatible outil offline)
- 📂 **Charger fichier JSON** — importe un fichier `.json` (édité manuellement ou exporté par l'outil offline)
- 🌙 **Mode sombre/clair**
