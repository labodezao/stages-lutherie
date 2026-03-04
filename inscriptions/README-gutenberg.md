# Intégration Gutenberg — Formulaire d'inscription

Deux façons d'intégrer le formulaire dans WordPress :

---

## Méthode 1 — Coller dans l'éditeur de code (la plus simple)

### 📋 Code à coller (FR)

Ouvrez ce lien → sélectionnez tout (`Ctrl+A`) → copiez :

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/copilot/create-internship-registration-form/inscriptions/formulaire-inscription.html**

Version anglaise :

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/copilot/create-internship-registration-form/inscriptions/formulaire-inscription-en.html**

### Étapes dans WordPress

1. Ouvrez la page WordPress où vous voulez placer le formulaire.
2. En haut à droite, cliquez sur **⋮ → Éditeur de code** (raccourci `Ctrl + Shift + Alt + M`).
3. Collez le code copié — il commence et finit par `<!-- wp:html -->` / `<!-- /wp:html -->`.
4. Cliquez **Quitter l'éditeur de code**, puis **Mettre à jour**.

> Le fichier `.html` est un bloc `wp:html` prêt à coller tel quel.

---

## Méthode 2 — Importer le JSON comme bloc réutilisable

Les fichiers `*-gutenberg.json` sont au format `wp_block` (Synced Pattern).

**WordPress 6.3+ (Motifs synchronisés) :**

1. WordPress Admin → **Apparence → Éditeur → Motifs**.
2. Cliquez sur **⋮ → Importer depuis JSON**.
3. Sélectionnez `formulaire-inscription-gutenberg.json` (FR) ou `formulaire-inscription-en-gutenberg.json` (EN).
4. Le motif apparaît dans la bibliothèque — insérez-le dans n'importe quelle page.

**WordPress ≤ 6.2 (Blocs réutilisables) :**

1. Dans l'éditeur, ouvrez le panneau **Blocs** → onglet **Réutilisables**.
2. Cliquez sur **⋮ → Importer depuis JSON** et sélectionnez le fichier.

---

## Fichiers

| Fichier | Description |
|---|---|
| `formulaire-inscription.html` | Formulaire FR (bloc `wp:html`, coller directement) |
| `formulaire-inscription-en.html` | Formulaire EN |
| `formulaire-inscription-gutenberg.json` | Import JSON Gutenberg (FR) |
| `formulaire-inscription-en-gutenberg.json` | Import JSON Gutenberg (EN) |
