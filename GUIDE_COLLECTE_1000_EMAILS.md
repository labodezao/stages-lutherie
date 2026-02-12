# Guide pour Collecter 1000+ Emails d'Associations

## Objectif
Collecter plus de 1000 adresses email de contacts intéressés par l'accordéon diatonique, la musique traditionnelle, et le bal folk.

## Stratégie en 3 Axes

### 1. ÉLARGIR LA CIBLE (pas seulement accordéon)

Pour atteindre 1000+ emails, il faut cibler:

#### A. Associations de musique traditionnelle
- Bal folk
- Fest-noz
- Musique celtique
- Musique cajun/zydeco
- Musique folklorique européenne

#### B. Structures éducatives et culturelles
- Conservatoires
- Écoles de musique
- Centres culturels
- MJC (Maisons des Jeunes et de la Culture)
- Médiathèques avec sections musique

#### C. Professionnels et commerces
- Luthiers d'instruments folk
- Magasins de musique spécialisés
- Réparateurs d'accordéons
- Professeurs de musique indépendants

#### D. Organisateurs d'événements
- Festivals de musique folk/trad
- Organisateurs de bals
- Associations culturelles bretonnes
- Groupes de danseurs folk

### 2. SOURCES DE DONNÉES PRINCIPALES

#### HelloAsso (100+ associations par catégorie)
**URL à explorer:**
```
https://www.helloasso.com/e/cat/accordeon
https://www.helloasso.com/e/cat/musique
https://www.helloasso.com/e/cat/bal-folk
https://www.helloasso.com/e/cat/danse
https://www.helloasso.com/e/cat/culture
```

**Par région (12 régions = 12 × 100 = 1200 associations potentielles):**
- Bretagne: `/e/reg/bretagne/cat/accordeon`
- Pays de la Loire: `/e/reg/pays--de--la--loire/cat/accordeon`
- Nouvelle-Aquitaine: `/e/reg/nouvelle-aquitaine/cat/accordeon`
- Occitanie: `/e/reg/occitanie/cat/accordeon`
- Auvergne-Rhône-Alpes: `/e/reg/auvergne-rhone-alpes/cat/accordeon`
- PACA: `/e/reg/provence-alpes-cote-d-azur/cat/accordeon`
- Île-de-France: `/e/reg/ile-de-france/cat/accordeon`
- Grand Est: `/e/reg/grand-est/cat/accordeon`
- Hauts-de-France: `/e/reg/hauts-de-france/cat/accordeon`
- Normandie: `/e/reg/normandie/cat/accordeon`
- Bourgogne-Franche-Comté: `/e/reg/bourgogne-franche-comte/cat/accordeon`
- Centre-Val de Loire: `/e/reg/centre-val-de-loire/cat/accordeon`

**Méthode:**
1. Ouvrir chaque page de région/catégorie
2. Cliquer sur chaque association listée
3. Noter l'email de contact (souvent dans "À propos" ou "Contact")
4. Compiler dans un tableur

#### AgendaTrad (500+ organisateurs)
**URL:** https://agendatrad.org/orgas/Francia

**Méthode:**
- Parcourir la liste des organisateurs
- Cliquer sur chaque fiche
- Noter nom, ville, email, site web
- Beaucoup d'organisateurs listent leur email directement

#### Chorotempo (300+ organisateurs)
**URL:** https://chorotempo.org/organisateurs/France

**Méthode similaire à AgendaTrad**

#### Net1901 (Annuaire complet des associations)
**URL:** https://www.net1901.org/

**Recherche par mots-clés:**
- "accordéon"
- "musique traditionnelle"
- "bal folk"
- "fest noz"
- "folk"
- "musette"

**Avantage:** Contient souvent l'email direct dans les fiches

#### Répertoire National des Associations
**URL:** https://repertoiredesassociations.fr/

**Recherche officielle gouvernementale**
- Recherche par activité
- Par département
- Données officielles

### 3. SOURCES COMPLÉMENTAIRES

#### A. Groupes et Forums Facebook
**Groupes ciblés (50 000+ membres au total):**
1. BALS FOLK TRAD PARISIENS (~5000 membres)
2. Folk Info / Folk Weekender (~3000 membres)
3. Agendas Trad par régions (multiples groupes)
4. Accordéon Diatonique – Passionnés
5. Musique Traditionnelle en France

**Méthode:**
- Rejoindre les groupes
- Poster une annonce demandant les contacts intéressés
- Contacter les administrateurs pour partenariat

#### B. Forums Spécialisés
1. **Melodeon.net** (forum anglophone très actif)
   - Section "Events" et "Makers"
   - Membres du monde entier

2. **AccordionistsInfo** (forum international)

3. **Reddit r/Accordion** (~25k membres)

#### C. Fédérations et Réseaux
1. **UNAF** (Union Nationale des Accordéonistes de France)
   - Contact: Via leur site pour accès à leur réseau

2. **CIA** (Confédération Internationale des Accordéonistes)
   - Réseau mondial avec 40+ pays

3. **Réseaux régionaux de culture bretonne:**
   - Kendalc'h
   - War'l Leur
   - Bodadeg ar Sonerion

#### D. Festivals et Événements
**Rechercher les contacts des festivals:**
- Festival Interceltique de Lorient
- Fest-Noz dans toute la Bretagne
- Rencontres de Luthiers et Maîtres Sonneurs
- Bal'O'Phonies
- Festival de Cornouaille

**Méthode:** Chercher "festival folk France" + année courante

### 4. OUTILS D'AUTOMATISATION

#### Script Python fourni: `scrape_mass_contacts.py`

**Usage:**
```bash
# Installer les dépendances
pip install requests beautifulsoup4

# Lancer la collecte sur toutes les sources
python3 scrape_mass_contacts.py

# Collecter seulement HelloAsso
python3 scrape_mass_contacts.py --sources helloasso

# Collecter avec catégories spécifiques
python3 scrape_mass_contacts.py --categories accordeon musique bal-folk danse culture

# Ajuster le délai entre requêtes (respecter les sites)
python3 scrape_mass_contacts.py --delay 3
```

**IMPORTANT:** 
- Respecter les limites de taux de requêtes
- Délai minimum de 2 secondes entre requêtes
- Certains sites peuvent bloquer le scraping automatique
- Alternative: collecte manuelle plus fiable

### 5. MÉTHODE MANUELLE EFFICACE

#### Étape 1: Créer un tableur Google Sheets
Colonnes:
- Nom association/structure
- Email
- Téléphone
- Site web
- Ville
- Département
- Type (association/école/festival/commerce)
- Source (HelloAsso/AgendaTrad/etc.)
- Date de collecte
- Notes

#### Étape 2: Répartir le travail par source
**Session 1 (2-3 heures):** HelloAsso région par région
**Session 2 (2-3 heures):** AgendaTrad + Chorotempo
**Session 3 (1-2 heures):** Net1901
**Session 4 (1-2 heures):** Fédérations + Festivals
**Session 5 (1-2 heures):** Écoles de musique + Conservatoires

#### Étape 3: Recherche Google ciblée
```
site:*.fr "contact" "accordéon diatonique"
site:*.fr "association" "musique traditionnelle" email
site:*.fr "bal folk" contact
site:*.fr "école musique" accordéon email
```

### 6. EXPANSION GÉOGRAPHIQUE

#### Pays francophones voisins
- **Belgique** (Wallonie): Associations folk, bals
- **Suisse** (Romandie): Musique trad suisse
- **Luxembourg**: Scène folk
- **Québec/Canada**: Musique trad québécoise

#### Autres pays européens
- **UK/Irlande**: Melodeon societies (anglophone mais pertinent)
- **Allemagne**: Akkordeon Vereine
- **Italie**: Associazioni fisarmonica
- **Espagne**: Asociaciones acordeón

### 7. ESTIMATION RÉALISTE

**Sources directement accessibles:**
- HelloAsso: 200-400 emails
- AgendaTrad: 150-300 emails
- Chorotempo: 100-200 emails
- Net1901: 200-400 emails
- Écoles/Conservatoires: 100-200 emails
- Festivals: 50-100 emails
- Luthiers/Commerces: 50-100 emails
- **TOTAL: 850-1700 emails**

### 8. CONSIDÉRATIONS LÉGALES (RGPD)

✅ **Autorisé:**
- Collecter des emails publics d'associations
- Utiliser pour prospection B2B légitime
- Proposer des services professionnels (stages de lutherie)

⚠️ **Obligations:**
- Indiquer la source de collecte
- Offrir un moyen de désinscription
- Ne pas revendre la liste
- Utiliser seulement pour l'usage déclaré

❌ **Interdit:**
- Spam massif non ciblé
- Vente de liste d'emails
- Emails personnels non professionnels
- Emails sans lien avec l'activité

### 9. NEXT STEPS

1. ✅ Décider: Automatique (script) ou Manuel (plus fiable)
2. ⏳ Allouer 10-15 heures pour collecte manuelle complète
3. ⏳ Créer le tableur de collecte
4. ⏳ Commencer par HelloAsso + AgendaTrad (600+ emails garantis)
5. ⏳ Compléter avec autres sources
6. ⏳ Nettoyer et dédupliquer
7. ⏳ Segmenter par type/région pour emails ciblés

### 10. ALTERNATIVE: CAMPAGNE D'INSCRIPTION VOLONTAIRE

Au lieu de collecter des emails, **créer une page d'inscription:**

1. Landing page "Newsletter Stages d'Accordéon"
2. Promouvoir dans les groupes Facebook
3. Poster sur les forums
4. Collaborer avec les associations pour relayer
5. Laisser les gens s'inscrire volontairement

**Avantages:**
- 100% conforme RGPD
- Audience qualifiée et intéressée
- Meilleur taux d'ouverture
- Pas de risque de blocage/spam

**Promotion de la landing page dans:**
- Tous les groupes Facebook folk/trad
- Forums (Melodeon.net, etc.)
- Via les associations partenaires
- Publicité Facebook ciblée (petit budget)

---

## CONCLUSION

**Pour 1000+ emails:** Combinaison de méthode manuelle (HelloAsso + AgendaTrad + Chorotempo) + expansion géographique (Belgique, Suisse) + élargissement de cible (musique trad en général, pas que accordéon).

**Temps estimé:** 12-20 heures de travail manuel
**OU:** Développement approfondi d'outils de scraping (mais risques techniques et légaux)

**Recommandation:** Commencer par collecte manuelle de 500-700 emails de haute qualité + landing page pour inscriptions volontaires.
