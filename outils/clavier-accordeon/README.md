# Outil Plan de Claviers – Guide

## Fichiers disponibles

| Fichier | Usage |
|---|---|
| `index.html` | **Outil complet offline** — à ouvrir en local dans le navigateur |
| `gutenberg-widget.html` | **Widget WordPress (source)** — référence HTML pour les versions Gutenberg stagiaires |
| `clavier-accordeon-admin-gutenberg.txt` | **Import Gutenberg — PAGE ADMIN** — version complète basée sur `index.html` |
| `clavier-accordeon-stagiaires-gutenberg.txt` | **Import Gutenberg — PAGE STAGIAIRES** — widget avec présets préconfigurés par l'admin |
| `clavier-accordeon-gutenberg.txt` | **Import Gutenberg (version originale)** — conservé pour compatibilité |

---

## Deux pages WordPress

| Page | Fichier à coller | Qui y accède ? | Basé sur |
|---|---|---|---|
| Page admin (privée/protégée) | `clavier-accordeon-admin-gutenberg.txt` | Admin uniquement | `index.html` (outil complet) |
| Page stagiaires (publique) | `clavier-accordeon-stagiaires-gutenberg.txt` | Tous les stagiaires | `gutenberg-widget.html` (widget) |

### Fonctionnalités de la page admin

La page admin est la **version complète** de l'outil (`index.html`) :
- 📚 **Bibliothèque d'instruments** — sauvegarde multiple via `localStorage`
- ✏️ **Éditeur complet** — toutes les fonctionnalités d'édition
- 📋 **Génération de présets** — bouton "Copier comme préset" pour configurer la page stagiaires
- ⬇️ **Export/Import JSON** — compatible avec l'outil offline

> **⚠️ Configuration des présets dans la page admin**
> La version admin charge les présets intégrés depuis `PRESETS_BASE_URL`.
> Éditez la ligne `const PRESETS_BASE_URL = '';` dans le fichier et renseignez l'URL
> du dossier `presets/` hébergé sur votre serveur WordPress
> (ex: `https://votre-site.com/wp-content/uploads/presets/`).
> Laissez vide (`''`) pour utiliser les présets locaux (ne fonctionnera qu'en offline).

### Workflow de configuration des présets

1. **Configurer** un clavier sur la **page admin** (bibliothèque + éditeur complet)
2. Cliquer **⬇️ Enregistrer sous .json** pour exporter le plan configuré
3. **Éditer** `clavier-accordeon-stagiaires-gutenberg.txt` → ajouter le JSON dans le bloc `<script id="cw-custom-presets">`
4. Recoller le fichier stagiaires dans la page WordPress des stagiaires et sauvegarder

---

## Utilisation sur WordPress

### Coller le widget dans une page

1. Dans WordPress, aller dans **Pages → Ajouter une page**
2. Cliquer **⋮ → Éditeur de code** (`Ctrl + Shift + Alt + M`)
3. Ouvrir le fichier Gutenberg approprié, sélectionner tout (`Ctrl+A`), copier, coller
4. Revenir en éditeur visuel et publier

### Présets de la page stagiaires

Les présets de la page stagiaires sont configurés dans `clavier-accordeon-stagiaires-gutenberg.txt` dans le bloc :
```html
<script id="cw-custom-presets" type="application/json">
[ /* coller ici les présets JSON */ ]
</script>
```
Les présets par défaut (GC21, AD33, etc.) sont **intégrés directement** dans le widget (`PRESETS_DATA`).

### ⚠️ Note WordPress : opérateur `&&`

WordPress encode automatiquement `&&` en `&#038;&#038;` dans les blocs HTML personnalisés, ce qui casse le JavaScript. Tous les fichiers Gutenberg (`.txt`) utilisent des ternaires et des `if` imbriqués à la place de `&&` pour éviter ce problème.

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
