# Intégration Gutenberg — Formulaire d'inscription

---

## ✅ Méthode recommandée — Coller dans l'éditeur de code

Utilisez les fichiers **`*-blocs-gutenberg.txt`** : ouvrez le lien raw, sélectionnez tout (`Ctrl+A`), copiez, collez dans Gutenberg.

### 🇫🇷 Formulaire français

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/copilot/create-internship-registration-form/inscriptions/formulaire-inscription-blocs-gutenberg.txt**

### 🇬🇧 English form

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/copilot/create-internship-registration-form/inscriptions/formulaire-inscription-en-blocs-gutenberg.txt**

### Étapes dans WordPress

1. Ouvrez la page WordPress où vous voulez placer le formulaire.
2. En haut à droite, cliquez sur **⋮ → Éditeur de code** (raccourci `Ctrl + Shift + Alt + M`).
3. Sélectionnez tout (`Ctrl+A`) et collez.
4. Cliquez **⋮ → Éditeur visuel** pour vérifier le rendu, puis **Mettre à jour**.

> Le fichier commence par un commentaire d'instructions, suivi du bloc `<!-- wp:html -->` contenant le formulaire complet.

---

## Méthode alternative — Importer le JSON comme bloc réutilisable

Les fichiers `*-gutenberg.json` sont au format `wp_block` (Synced Pattern).

**WordPress 6.3+ (Motifs synchronisés) :**

1. WordPress Admin → **Apparence → Éditeur → Motifs**.
2. Cliquez sur **⋮ → Importer depuis JSON**.
3. Sélectionnez `formulaire-inscription-gutenberg.json` (FR) ou `formulaire-inscription-en-gutenberg.json` (EN).
4. Le motif apparaît dans la bibliothèque — insérez-le dans n'importe quelle page.

---

## Fichiers

| Fichier | Usage |
|---|---|
| `formulaire-inscription-blocs-gutenberg.txt` | ⭐ **À coller dans Éditeur de code** (FR) |
| `formulaire-inscription-en-blocs-gutenberg.txt` | ⭐ **À coller dans Éditeur de code** (EN) |
| `formulaire-inscription-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (FR) |
| `formulaire-inscription-en-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (EN) |
| `formulaire-inscription.html` | Source HTML brut (FR) |
| `formulaire-inscription-en.html` | Source HTML brut (EN) |
