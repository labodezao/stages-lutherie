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

### Méthode 2 — Coller le fichier texte dans l'éditeur de code

1. Ouvrez `formulaire-inscription-gutenberg.txt` (FR) ou `formulaire-inscription-en-gutenberg.txt` (EN)
2. Sélectionnez tout (`Ctrl+A`) et copiez
3. WordPress → **Pages → Ajouter une page**
4. Cliquez **⋮ → Éditeur de code** (`Ctrl + Shift + Alt + M`)
5. Collez le contenu
6. **⋮ → Éditeur visuel** pour vérifier le résultat
7. Publiez

---

## Fichiers

| Fichier | Usage |
|---|---|
| `formulaire-inscription.html` | ⭐ Formulaire complet (FR) — 45 blocs Gutenberg (29 natifs) |
| `formulaire-inscription-en.html` | ⭐ Complete form (EN) — 45 Gutenberg blocks (29 native) |
| `formulaire-inscription-gutenberg.txt` | Import texte Gutenberg (FR) — coller dans l'éditeur de code |
| `formulaire-inscription-en-gutenberg.txt` | Gutenberg plain text import (EN) — paste into code editor |
| `inscription-api.php` | ⭐ Mu-plugin WordPress — **déposer via FTP dans `wp-content/mu-plugins/`** |
| `email-confirmation-stagiaire.html` | Template de référence (optionnel) — le contenu est géré depuis l'admin WP |

---

## 🔌 Déploiement du mu-plugin (backend REST)

Le formulaire envoie les inscriptions via un endpoint REST WordPress fourni par `inscription-api.php`.
Ce fichier est un **mu-plugin** (must-use plugin) : il est chargé automatiquement par WordPress sans activation manuelle.

### Fichier à déposer via FTP

**Un seul fichier** suffit :

```
wp-content/
  mu-plugins/
    inscription-api.php           ← mu-plugin REST endpoint (seul fichier nécessaire)
```

### Étapes

1. Connectez-vous au FTP de votre hébergement WordPress
2. Naviguez vers `wp-content/mu-plugins/` (créez le dossier s'il n'existe pas)
3. Déposez `inscription-api.php`
4. Vérifiez dans WordPress → **Outils → Plugins obligatoires** que le plugin apparaît bien
5. Configurez tout dans WordPress → **Réglages → Inscription stage** :
   - **Email du luthier** (destinataire des nouvelles inscriptions)
   - **Coordonnées bancaires** (injectées automatiquement via `{bank_details}`)
   - **Objet** de l'email de confirmation
   - **Corps HTML** de l'email de confirmation — éditable directement, bouton "Réinitialiser au modèle par défaut" disponible

### Fonctionnement de l'email de confirmation

Quand un stagiaire soumet le formulaire :
1. `inscription-api.php` lit l'option `stluth_confirmation_body` depuis la base WordPress
2. Si l'option est vide → utilise le modèle HTML intégré (beau design aux couleurs du site)
3. Les variables `{nom}`, `{modele}`, `{session}`, `{acompte}`, `{bank_details}` sont remplacées
4. Un email **HTML** est envoyé au stagiaire avec le PDF en pièce jointe
5. Un email plain-text avec tous les champs est envoyé au luthier

> 💡 Le fichier `email-confirmation-stagiaire.html` n'est **plus nécessaire** sur le serveur.
> Il peut servir de référence locale mais le contenu est entièrement géré depuis l'admin WordPress.
