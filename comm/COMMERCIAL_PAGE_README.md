# Commercial WordPress Page - Stages de Lutherie

## Description
Page commerciale complète pour les stages de fabrication d'accordéons diatoniques, optimisée pour WordPress et incluant toutes les informations détaillées.

## Fichier
`comm/commercial-page-wordpress.html`

## Contenu inclus

### Sections principales
1. **Hero Section** - Bannière d'accueil avec titre accrocheur
2. **Dates** - Sessions printemps et automne 2026
3. **Présentation** - Esprit du stage, public cible, objectifs
4. **Tarifs & Modèles** - 3 modèles (21/8, 33/12, 33/18) avec prix et acomptes
5. **Programme détaillé** - 10 jours décrits étape par étape
6. **Options** - Plus de 100 combinaisons possibles (bois, tonalités, grilles, etc.)
7. **Informations pratiques** - Lieu, horaires, inclus/non inclus, conditions
8. **Call-to-Action** - Boutons d'inscription et contact
9. **Contact** - Liens vers site web, email, vidéo YouTube

### Informations détaillées
✅ **Modèles**: 21/8 (2820€), 33/12 (4500€), 33/18 (4880€)
✅ **Acomptes**: 900€ / 1500€ / 1900€ (40%)
✅ **Dates 2026**: 8-17 avril & 14-23 octobre
✅ **Lieu**: 9 rue Fernand de Magellan, Saint-Nazaire (44)
✅ **Options complètes**: essences bois, tonalités, layouts, grilles, soufflets, anches
✅ **Programme jour par jour**: 10 jours détaillés
✅ **Horaires**: 9h30-12h30 / 14h00-17h30

## Sources
Contenu compilé depuis:
- `01-fiche-stage-detaillee-fr.md` - Informations détaillées du stage
- `02-programme-jour-par-jour-fr.md` - Programme quotidien
- `00-communication-annonce-fr.md` - Annonce commerciale
- `guide-stagiaires-fr.md` - Guide des stagiaires
- `comm/visuel-stage-email.html` - Template email existant
- `README.md` - Informations générales

## Compatibilité WordPress

### Méthode 1: Bloc HTML personnalisé (Recommandée)
```
1. Dans WordPress, créez une nouvelle page
2. Ajoutez un bloc "HTML personnalisé" (Gutenberg)
3. Copiez le contenu de <style> jusqu'à </style>
4. Ajoutez-le dans Apparence > Personnaliser > CSS additionnel
5. Copiez le contenu de <div class="workshop-page"> jusqu'à </div>
6. Collez-le dans le bloc HTML
7. Publiez la page
```

### Méthode 2: Template de page
```
1. Créez un nouveau fichier dans votre thème: page-stages.php
2. Copiez tout le code HTML
3. Dans WordPress, créez une page et assignez le template "Stages"
```

### Méthode 3: Plugin page builder
- Compatible avec Elementor, Divi, WPBakery
- Utilisez un widget HTML/Code personnalisé

## Formulaires de contact

### Option 1: Contact Form 7
```html
<!-- Remplacez le lien du bouton par: -->
<a href="#contact-form" class="btn btn-primary">S'inscrire</a>

<!-- Ajoutez votre shortcode CF7 en bas de page: -->
<div id="contact-form">
  [contact-form-7 id="123" title="Inscription Stage"]
</div>
```

### Option 2: WPForms
```html
<!-- Remplacez le lien du bouton par: -->
<a href="#wpforms-form" class="btn btn-primary">S'inscrire</a>

<!-- Ajoutez votre shortcode WPForms: -->
<div id="wpforms-form">
  [wpforms id="456"]
</div>
```

### Option 3: Lien direct
Les liens actuels pointent vers:
- Inscription: `https://stages.ewendaviau.com`
- Email: `contact@ewendaviau.com`
- Vidéo: YouTube

## Design

### Palette de couleurs
- Bois foncé: `#3E2723`
- Bois moyen: `#5D4037`
- Bois clair: `#8D6E63`
- Crème: `#FFF8E1`
- Or: `#D4A017`
- Or clair: `#F5D061`

### Typographie
- Titres: Georgia (serif)
- Texte: System fonts (Apple/Windows)
- Boutons: Arial, sans-serif

### Responsive
✅ Mobile: optimisé pour smartphones
✅ Tablette: grilles adaptatives
✅ Desktop: mise en page complète

## SEO

### Meta tags inclus
- Title: "Fabriquez votre accordéon diatonique - Stage de lutherie 10 jours"
- Description: optimisée avec mots-clés
- Viewport: responsive
- Charset: UTF-8

### Recommandations
1. Installez Yoast SEO ou Rank Math
2. Optimisez le slug de page: `/stages-accordeon-diatonique/`
3. Ajoutez des images avec attributs `alt`
4. Créez des liens internes vers cette page
5. Soumettez le sitemap à Google

## Performance

### Optimisations
✅ CSS inline (pas de fichiers externes)
✅ Pas de JavaScript (chargement rapide)
✅ Design léger (29 KB)
✅ Pas d'images lourdes dans le HTML
✅ Polices système (pas de web fonts)

### Tests recommandés
- PageSpeed Insights
- GTmetrix
- Test mobile Google

## Accessibilité

✅ Structure sémantique (H1, H2, H3)
✅ Contraste de couleurs WCAG AA
✅ Navigation au clavier
✅ Liens descriptifs
✅ Responsive design

## Maintenance

### Mises à jour à faire
- Dates des sessions: modifier les dates 2026/2027
- Tarifs: mettre à jour si changement de prix
- Lieu: si déménagement d'atelier
- Liens: vérifier que les URLs sont valides

### Version
- Date de création: 2026-02-12
- Version: 1.0
- Compatibilité: WordPress 5.0+

## Support

### Problèmes courants

**Le CSS ne s'applique pas**
→ Vérifiez que le CSS est bien dans "CSS additionnel"

**La mise en page est cassée**
→ Votre thème peut avoir des styles conflictuels
→ Ajoutez `!important` aux propriétés CSS si nécessaire

**Les boutons ne fonctionnent pas**
→ Vérifiez les liens des boutons
→ Ajoutez vos shortcodes de formulaires

**Page trop lente**
→ Activez la mise en cache (WP Rocket, W3 Total Cache)
→ Optimisez les images si vous en ajoutez
→ Utilisez un CDN

## Contact
Pour questions ou modifications:
- Email: contact@ewendaviau.com
- Site: https://stages.ewendaviau.com

---

**Note**: Cette page est conçue pour être autonome et facile à intégrer dans n'importe quel site WordPress sans dépendances externes.
