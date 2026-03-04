# Intégration Gutenberg — Page d'inscription

---

## ✅ Méthode recommandée — Blocs natifs Gutenberg

Utilisez les fichiers **`page-inscription-*-blocs-gutenberg.txt`** : ce sont de vraies pages Gutenberg composées de blocs natifs (`wp:cover`, `wp:heading`, `wp:columns`, `wp:list`, `wp:buttons`…).  
Zéro CSS personnalisé — tout est éditable visuellement dans WordPress.

### 🇫🇷 Page d'inscription (français)

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/main/inscriptions/page-inscription-blocs-gutenberg.txt**

### 🇬🇧 Registration page (English)

> **https://raw.githubusercontent.com/labodezao/stages-lutherie/main/inscriptions/page-inscription-en-blocs-gutenberg.txt**

### Étapes dans WordPress

1. WordPress → **Pages → Ajouter une page**, donnez-lui un titre (ex. : _Inscription_).
2. En haut à droite, cliquez sur **⋮ → Éditeur de code** (raccourci `Ctrl + Shift + Alt + M`).
3. Ouvrez le lien _raw_ ci-dessus, sélectionnez tout (`Ctrl+A`), copiez, collez.
4. Cliquez **⋮ → Éditeur visuel** pour vérifier le rendu.
5. Mettez à jour les liens `VOTRE-SITE.COM` dans les boutons CTA, puis **Publier**.

> Les blocs sont entièrement éditables : couleurs, textes, liens, images — tout se modifie directement dans l'éditeur visuel.

---

## Formulaire interactif (avec calcul de prix et PDF)

Le formulaire d'inscription avec calcul automatique du prix, génération de PDF et envoi par email REST WordPress se trouve dans :

| Fichier | Usage |
|---|---|
| `formulaire-inscription.html` | Formulaire interactif complet (FR) — à coller dans un bloc HTML personnalisé |
| `formulaire-inscription-en.html` | Interactive form (EN) — paste into a Custom HTML block |
| `formulaire-inscription-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (FR) |
| `formulaire-inscription-en-gutenberg.json` | Import JSON Gutenberg — Synced Pattern (EN) |

---

## Fichiers de blocs natifs

| Fichier | Usage |
|---|---|
| `page-inscription-blocs-gutenberg.txt` | ⭐ **Page d'inscription en blocs natifs** (FR) |
| `page-inscription-en-blocs-gutenberg.txt` | ⭐ **Registration page in native blocks** (EN) |
