# Intégration Gutenberg — Formulaire d'inscription

---

## ✅ Structure des fichiers Gutenberg

Chaque fichier HTML du formulaire est désormais **entièrement décomposé en blocs Gutenberg natifs** — chaque section est un bloc indépendant éditable d'un clic :

| Bloc | Type | Contenu |
|---|---|---|
| `<!-- wp:cover -->` | **Natif** ✅ | Héro photo de fond · titre H1 · sous-titre |
| `<!-- wp:html -->` | HTML | CSS scopé (classes `.insc-*`) |
| `<!-- wp:html -->` | HTML | Script jsPDF |
| `<!-- wp:html -->` | HTML | Ouverture `<form>` |
| `<!-- wp:group -->` × 10 | **Natif** ✅ | Carte section (bordure + ombre natifs) |
| `↳ <!-- wp:heading level:2 -->` | **Natif** ✅ | Titre de section — **éditable d'un clic** 🖊️ |
| `↳ <!-- wp:html -->` | HTML | Champs de formulaire de la section |
| `<!-- wp:paragraph -->` × 5 | **Natif** ✅ | Texte des Conditions — **éditable d'un clic** 🖊️ |
| `<!-- wp:html -->` | HTML | Boutons d'actions + contact |
| `<!-- wp:html -->` | HTML | Fermeture `</form>` |
| `<!-- wp:html -->` | HTML | JavaScript (calcul prix, PDF, REST) |

**Total : 45 blocs — 29 natifs Gutenberg, 16 wp:html**

Les `wp:html` ne contiennent que ce qui est strictement interactif (champs `<input>`, `<select>`, `<textarea>`, JS) et ne peuvent pas être simplifié davantage sans perdre les fonctionnalités.

---

## 🎯 Utilisation dans WordPress

### Méthode 1 — Coller le fichier HTML (recommandé)

1. WordPress → **Pages → Ajouter une page**
2. Donnez un titre (ex. : _Inscription_)
3. Cliquez **⋮ → Éditeur de code** (`Ctrl + Shift + Alt + M`)
4. Ouvrez le lien _raw_ ci-dessous, sélectionnez tout (`Ctrl+A`), copiez, collez
5. **⋮ → Éditeur visuel** : le bloc héro est éditable visuellement !
6. Publiez

### 🇫🇷 Formulaire français

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/main/inscriptions/formulaire-inscription.html**

### 🇬🇧 English registration form

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/main/inscriptions/formulaire-inscription-en.html**

---

### Méthode 2 — Importer le JSON comme Synced Pattern

1. WordPress Admin → **Apparence → Éditeur → Motifs → ⋮ → Importer depuis JSON**
2. Sélectionnez `formulaire-inscription-gutenberg.json` (FR) ou `formulaire-inscription-en-gutenberg.json` (EN)
3. Le motif apparaît dans la bibliothèque — insérez-le dans n'importe quelle page

---

## Fichiers

| Fichier | Usage |
|---|---|
| `formulaire-inscription.html` | ⭐ Formulaire complet (FR) — 45 blocs Gutenberg (29 natifs) |
| `formulaire-inscription-en.html` | ⭐ Complete form (EN) — 45 Gutenberg blocks (29 native) |
| `formulaire-inscription-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (FR) |
| `formulaire-inscription-en-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (EN) |
