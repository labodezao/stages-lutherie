# Intégration Gutenberg — Formulaire d'inscription

---

## ✅ Structure des fichiers Gutenberg

Chaque fichier HTML du formulaire est désormais **structuré en blocs Gutenberg natifs** :

| Bloc | Type | Contenu |
|---|---|---|
| `<!-- wp:cover -->` | **Bloc natif** ✅ | Héro avec photo de fond, titre H1, sous-titre |
| `<!-- wp:html -->` | Bloc HTML | CSS scopé + JavaScript (calcul de prix, PDF, REST) + formulaire HTML |

Le formulaire interactif (calcul de prix automatique, génération PDF, envoi email via API WordPress) nécessite du JavaScript — il reste dans un bloc `wp:html` ciblé. Tout le reste est natif.

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
| `formulaire-inscription.html` | ⭐ Formulaire complet (FR) — `wp:cover` natif + `wp:html` formulaire |
| `formulaire-inscription-en.html` | ⭐ Complete form (EN) — native `wp:cover` + `wp:html` form |
| `formulaire-inscription-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (FR) |
| `formulaire-inscription-en-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (EN) |
| `page-inscription-blocs-gutenberg.txt` | Page d'accueil inscription 100 % blocs natifs (FR) |
| `page-inscription-en-blocs-gutenberg.txt` | Registration landing page, 100% native blocks (EN) |
