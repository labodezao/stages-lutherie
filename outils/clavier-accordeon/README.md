# Outil Plan de Claviers – Guide

## Deux versions disponibles

| Fichier | Usage |
|---|---|
| `index.html` | **Outil complet** — à ouvrir en local dans le navigateur |
| `gutenberg-widget.html` | **Widget WordPress** — à coller dans un bloc HTML personnalisé |
| `clavier-accordeon-gutenberg.json` | **Import Gutenberg** — importer via WordPress Outils → Importer |

---

## Utilisation sur WordPress

### Import du widget

1. Dans WordPress, aller dans **Outils → Importer → WordPress** (ou utiliser le bouton d'import de blocs dans l'éditeur)
2. Importer `clavier-accordeon-gutenberg.json`
3. Le bloc apparaît dans la bibliothèque de blocs réutilisables

### Aucun fichier à uploader

Les presets (GC21, AD33, etc.) sont **intégrés directement dans le widget** (`PRESETS_DATA`). Il n'y a rien à héberger ni à uploader sur le serveur WordPress.

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

Utiliser le bouton **📂 Charger fichier** dans l'interface du widget.

---

## Modifier les presets intégrés (outil offline uniquement)

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

Pour le **widget WordPress**, les presets sont inline dans `PRESETS_DATA` dans `gutenberg-widget.html`. Pour en ajouter un, modifier ce bloc JS et réimporter `clavier-accordeon-gutenberg.json`.

---

## Fonctionnalités du widget

- 🎹 **Sélecteur de notes** — cliquer sur un champ de note pour ouvrir le sélecteur (touches naturelles, dièses, bémols, octaves)
- 🎯 **Édition directe sur le SVG** — cliquer sur un bouton du plan pour modifier poussé/tiré
- 📐 **Décalage des rangées** — contrôle ±30 pour chaque rangée MD et MG
- 💾 **Sauvegarde locale** — via `localStorage` du navigateur
- 📄 **Copier (texte)** — exporte un résumé lisible dans le presse-papiers
- 🌙 **Mode sombre/clair**
