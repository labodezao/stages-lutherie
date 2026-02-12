# Collecte des Emails d'Associations d'Accordéon Diatonique

Ce dossier contient les outils et données pour collecter les emails de contact des associations d'accordéon diatonique, de musique traditionnelle, et de bal folk.

## 🎯 Objectif: Collecter 1000+ Emails

Pour atteindre 1000+ emails de contacts qualifiés, ce projet propose:
1. **Outils automatisés** de web scraping (Python)
2. **Checklists manuelles** structurées pour collecte systématique
3. **Guides complets** des sources et méthodes
4. **Templates CSV** pour organiser les données

## Fichiers

### 1. `associations_accordeon_emails.csv`
Tableau CSV contenant les informations de contact des associations d'accordéon diatonique identifiées dans la région.

**Colonnes:**
- `email`: Adresse email de contact (si disponible)
- `association_name`: Nom de l'association
- `location`: Localisation (ville et département)
- `phone`: Numéro(s) de téléphone
- `website`: Site web de l'association
- `notes`: Informations complémentaires

**Statistiques:**
- **Emails directs collectés:** 3 adresses principales
- **Associations répertoriées:** 24 structures
- **Rayon de recherche:** 200 km autour de Saint-Nazaire (44600)
- **Départements couverts:** Loire-Atlantique (44), Vendée (85), Maine-et-Loire (49), Morbihan (56), Finistère (29), Côtes d'Armor (22), Ille-et-Vilaine (35)

### 2. `collect_associations_emails.py`
Script Python pour extraire automatiquement les emails depuis des pages web.

**Installation des dépendances:**
```bash
pip install requests beautifulsoup4
```

**Utilisation:**
```bash
# Scraper une URL
python3 collect_associations_emails.py https://www.acb44.bzh/index.php/cours/musique/97-accordeon-diatonique

# Ou analyser un fichier HTML local
python3 collect_associations_emails.py --file page.html

# Spécifier un fichier de sortie personnalisé
python3 collect_associations_emails.py --output mes_contacts.csv https://example.com
```

### 3. `scrape_mass_contacts.py` ⭐
Script Python avancé pour collecter en masse depuis plusieurs sources:
- HelloAsso (toutes régions et catégories)
- AgendaTrad (organisateurs d'événements folk)
- Chorotempo (musique traditionnelle)
- Net1901 (annuaire des associations)

**Utilisation:**
```bash
# Collecter depuis toutes les sources
python3 scrape_mass_contacts.py

# Collecter seulement depuis HelloAsso
python3 scrape_mass_contacts.py --sources helloasso

# Avec catégories spécifiques
python3 scrape_mass_contacts.py --categories accordeon musique bal-folk danse culture

# Ajuster le délai (respecter les sites)
python3 scrape_mass_contacts.py --delay 3 --output resultats.csv
```

### 4. `generate_url_checklist.py` 🎯
Génère une liste complète de 82 URLs à visiter pour collecte manuelle.

**Utilisation:**
```bash
python3 generate_url_checklist.py
```

**Génère:**
- `url_checklist.csv` - Liste de toutes les URLs à visiter avec priorités
- `contacts_template.csv` - Template vide pour saisir les contacts

**Workflow recommandé:**
1. Ouvrir `url_checklist.csv` dans Excel/Google Sheets
2. Commencer par les URLs "HIGH" priority (14 URLs)
3. Visiter chaque URL, collecter les emails
4. Entrer les données dans `contacts_template.csv`
5. Marquer "done=YES" dans `url_checklist.csv`
6. Continuer avec "MEDIUM" puis "LOW" priority

### 5. `GUIDE_COLLECTE_1000_EMAILS.md` 📚
Guide complet avec:
- Stratégie pour atteindre 1000+ emails
- Liste de toutes les sources (HelloAsso, AgendaTrad, Net1901, etc.)
- Méthodes manuelles et automatisées
- Considérations RGPD
- Estimation réaliste: 800-1700 emails possibles

## Contacts Principaux Identifiés

### Dans Saint-Nazaire même (44600)
1. **Le Dépliant**
   - Email: ledepliant@gmail.com
   - Contacts: rocher.david@wanadoo.fr, om.labour@orange.fr
   - Tél: 02 40 66 80 25, 06 27 25 19 53, 07 89 30 24 27
   - Adresse: Maison de quartier Kerlédé, 70 rue Ferdinand Buisson, 44600 Saint-Nazaire

### Dans la région (Loire-Atlantique 44)
2. **ACB44 - Agence Culturelle Bretonne**
   - Email: degemer@acb44.bzh
   - Tél: 02 51 84 16 07
   - Adresse: 24 quai de la Fosse, 44000 Nantes
   - Site: https://www.acb44.bzh
   - Note: Réseau d'écoles partenaires dans tout le département

3. **Association Soufflerie**
   - Contact web: https://accordeondiatonique.jimdofree.com/contacts/
   - Tél: 02 40 80 47 73, 06 26 91 09 29
   - Localisation: Nantes, Gétigné, Clisson
   - Contact: Vincent Lelièvre

### Écoles partenaires ACB44 en Loire-Atlantique
- Artissimo (Clisson)
- Boest an Diaoul (Mesquer-Quimiac)
- Centre Culturel Breton d'Orvault (Orvault)
- Conservatoire de Nantes (Nantes)

## Méthodologie de Collecte

Les données ont été collectées via:
1. **Recherche web ciblée** sur les associations d'accordéon diatonique dans les Pays de la Loire et Bretagne
2. **Sources officielles:**
   - Site ACB44 (Agence Culturelle Bretonne 44)
   - Annuaires HelloAsso
   - Sites web des associations
   - Annuaires municipaux
3. **Rayon géographique:** 200 km autour de Saint-Nazaire (44600)

## Départements dans le rayon de 200 km

### Moins de 100 km
- **Loire-Atlantique (44)** - Distance: 0-50 km
- **Vendée (85)** - Distance: 50-150 km
- **Maine-et-Loire (49)** - Distance: 100-150 km

### 100-200 km
- **Morbihan (56)** - Distance: 100-180 km
- **Finistère (29)** - Distance: 150-200 km (parties est)
- **Ille-et-Vilaine (35)** - Distance: 100-180 km
- **Côtes d'Armor (22)** - Distance: 150-200 km (parties sud)

## Utilisation pour la Prospection

Ce fichier CSV peut être utilisé pour:
- Créer une campagne d'emailing pour promouvoir les stages de lutherie
- Établir des partenariats avec les associations locales
- Proposer des ateliers et démonstrations
- Diffuser les annonces de stages

## Notes Importantes

### Limites du scraping automatique
Le site ACB44 (https://www.acb44.bzh/index.php/cours/musique/97-accordeon-diatonique) n'était pas accessible depuis l'environnement de développement lors de la création initiale de ce script. Pour obtenir les emails supplémentaires:

1. **Option manuelle:** Visitez le site et sauvegardez la page HTML
2. **Utilisez le script:** `python3 collect_associations_emails.py --file page.html`

### Conformité RGPD
- Les emails collectés sont des adresses publiques d'associations
- Utilisation recommandée: prospection professionnelle légitime
- Respecter le droit d'opposition et de désinscription

## Améliorations Futures

- [ ] Ajouter une fonctionnalité de calcul de distance géographique précise
- [ ] Intégrer une API de géolocalisation pour vérifier le rayon de 200 km
- [ ] Automatiser la collecte depuis HelloAsso et autres annuaires
- [ ] Ajouter un système de vérification d'emails actifs
- [ ] Créer un template d'email de prospection

## 💡 Comment Atteindre 1000+ Emails

### Approche Recommandée: Élargir la Cible

Pour collecter 1000+ emails, il faut **élargir au-delà des associations d'accordéon** pour inclure:

1. **Musique traditionnelle en général:**
   - Bal folk
   - Fest-noz (Bretagne)
   - Musique celtique
   - Cajun/Zydeco

2. **Structures éducatives:**
   - Conservatoires (tous départements)
   - Écoles de musique
   - Centres culturels
   - MJC

3. **Professionnels:**
   - Luthiers d'instruments folk
   - Magasins de musique spécialisés
   - Professeurs indépendants

4. **Organisateurs d'événements:**
   - Festivals folk/trad
   - Organisateurs de bals
   - Compagnies de danse folk

### Sources Principales (Potentiel 800-1700 emails)

| Source | Emails Estimés | Difficulté |
|--------|----------------|------------|
| HelloAsso (12 régions × 5 catégories) | 200-400 | Facile |
| AgendaTrad (organisateurs) | 150-300 | Facile |
| Chorotempo (organisateurs) | 100-200 | Moyen |
| Net1901 (annuaire) | 200-400 | Moyen |
| Écoles/Conservatoires | 100-200 | Moyen |
| Festivals | 50-100 | Facile |
| Luthiers/Commerces | 50-100 | Facile |
| **TOTAL** | **850-1700** | - |

### Deux Stratégies Possibles

#### Stratégie A: Collecte Manuelle (Recommandée ✅)
- **Temps:** 12-20 heures
- **Fiabilité:** Élevée
- **Qualité:** Excellente
- **Outils:** `url_checklist.csv` + `contacts_template.csv`
- **Résultat:** 800-1200 emails de haute qualité

#### Stratégie B: Scraping Automatique
- **Temps:** Développement + débogage
- **Fiabilité:** Variable (dépend des sites)
- **Qualité:** Moyenne (besoin de nettoyage)
- **Outils:** `scrape_mass_contacts.py`
- **Résultat:** 500-1000 emails (avec doublons et faux positifs)
- **Risques:** Blocage IP, captchas, données incomplètes

### Alternative: Campagne d'Inscription Volontaire

Au lieu de collecter des emails existants, **créer une landing page** "Newsletter Stages d'Accordéon":

**Avantages:**
- ✅ 100% conforme RGPD
- ✅ Audience qualifiée et intéressée
- ✅ Meilleur taux d'ouverture (30-40% vs 5-10%)
- ✅ Pas de risque de spam

**Promotion via:**
- Groupes Facebook folk/trad (50 000+ membres)
- Forums (Melodeon.net, Reddit)
- Partenariats avec associations
- Publicité Facebook ciblée (50-100€)

**Résultat attendu:** 200-500 inscriptions volontaires en 2-3 mois

## Ressources Complémentaires

- **ACB44 Accordéon Diatonique:** https://www.acb44.bzh/index.php/cours/musique/97-accordeon-diatonique
- **HelloAsso Pays de la Loire:** https://www.helloasso.com/e/reg/pays--de--la--loire/cat/accordeon
- **HelloAsso Bretagne:** https://www.helloasso.com/e/reg/bretagne/cat/accordeon
- **Document de prospection existant:** `/comm/prospection-reseaux-sociaux.md`

## Contact

Pour toute question sur ces outils:
- Site: https://stages.ewendaviau.com
- Email: contact@ewendaviau.com
