# Collecte des Emails d'Associations d'Accordéon Diatonique

Ce dossier contient les outils et données pour collecter les emails de contact des associations d'accordéon diatonique autour de Saint-Nazaire (44600) et dans un rayon de 200 km.

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

## Ressources Complémentaires

- **ACB44 Accordéon Diatonique:** https://www.acb44.bzh/index.php/cours/musique/97-accordeon-diatonique
- **HelloAsso Pays de la Loire:** https://www.helloasso.com/e/reg/pays--de--la--loire/cat/accordeon
- **HelloAsso Bretagne:** https://www.helloasso.com/e/reg/bretagne/cat/accordeon
- **Document de prospection existant:** `/comm/prospection-reseaux-sociaux.md`

## Contact

Pour toute question sur ces outils:
- Site: https://stages.ewendaviau.com
- Email: contact@ewendaviau.com
