#!/usr/bin/env python3
"""
Comprehensive URL List Generator for 1000 Diatonic Accordion Email Collection
Generates a structured list of ALL URLs to visit for systematic email collection.
"""

import csv
import json

# HelloAsso - Primary Source (Expected: 300-400 emails)
HELLOASSO_REGIONS = {
    "Bretagne": {
        "url": "https://www.helloasso.com/e/reg/bretagne/cat/accordeon",
        "estimated_associations": 35,
        "estimated_emails": 30
    },
    "Pays de la Loire": {
        "url": "https://www.helloasso.com/e/reg/pays--de--la--loire/cat/accordeon",
        "estimated_associations": 30,
        "estimated_emails": 25
    },
    "Nouvelle-Aquitaine": {
        "url": "https://www.helloasso.com/e/reg/nouvelle-aquitaine/cat/accordeon",
        "estimated_associations": 35,
        "estimated_emails": 30
    },
    "Occitanie": {
        "url": "https://www.helloasso.com/e/reg/occitanie/cat/accordeon",
        "estimated_associations": 30,
        "estimated_emails": 25
    },
    "Auvergne-Rhône-Alpes": {
        "url": "https://www.helloasso.com/e/reg/auvergne-rhone-alpes/cat/accordeon",
        "estimated_associations": 30,
        "estimated_emails": 25
    },
    "PACA": {
        "url": "https://www.helloasso.com/e/reg/provence-alpes-cote-d-azur/cat/accordeon",
        "estimated_associations": 25,
        "estimated_emails": 20
    },
    "Île-de-France": {
        "url": "https://www.helloasso.com/e/reg/ile-de-france/cat/accordeon",
        "estimated_associations": 40,
        "estimated_emails": 35
    },
    "Grand Est": {
        "url": "https://www.helloasso.com/e/reg/grand-est/cat/accordeon",
        "estimated_associations": 25,
        "estimated_emails": 20
    },
    "Hauts-de-France": {
        "url": "https://www.helloasso.com/e/reg/hauts-de-france/cat/accordeon",
        "estimated_associations": 25,
        "estimated_emails": 20
    },
    "Normandie": {
        "url": "https://www.helloasso.com/e/reg/normandie/cat/accordeon",
        "estimated_associations": 20,
        "estimated_emails": 18
    },
    "Bourgogne-Franche-Comté": {
        "url": "https://www.helloasso.com/e/reg/bourgogne-franche-comte/cat/accordeon",
        "estimated_associations": 20,
        "estimated_emails": 18
    },
    "Centre-Val de Loire": {
        "url": "https://www.helloasso.com/e/reg/centre-val-de-loire/cat/accordeon",
        "estimated_associations": 20,
        "estimated_emails": 18
    }
}

# AgendaTrad - Folk Organizers (Expected: 180-220 emails)
AGENDATRAD_URLS = [
    {
        "name": "Organisateurs France",
        "url": "https://agendatrad.org/orgas/France",
        "estimated_emails": 180,
        "notes": "Click each organizer profile, extract email"
    }
]

# Regional Diatonic Accordion Networks
REGIONAL_NETWORKS = [
    {
        "name": "ACB44 - Loire-Atlantique",
        "url": "https://www.acb44.bzh/index.php/cours/musique/97-accordeon-diatonique",
        "estimated_emails": 15
    },
    {
        "name": "Fédération Trad 33 - Gironde",
        "url": "https://trad33.com/annuaire/associations/",
        "estimated_emails": 20
    },
    {
        "name": "CADB - Collectif Accordéon Diatonique Bretagne",
        "url": "http://diato.orlulas.fr/cadb/",
        "estimated_emails": 10
    },
    {
        "name": "Musique et Danse 44",
        "url": "http://musiqueetdanse44.asso.fr/",
        "estimated_emails": 15
    }
]

# Teacher/School Directories (Expected: 150-200 emails)
TEACHER_DIRECTORIES = [
    {
        "name": "Superprof - Accordéon diatonique France",
        "url": "https://www.superprof.fr/cours/accordeon-diatonique/france/",
        "estimated_emails": 80,
        "notes": "Browse all teacher profiles, extract emails"
    },
    {
        "name": "ProfesseurParticulier.com",
        "url": "https://www.professeurparticulier.com/cours-particuliers/55-accordeon/",
        "estimated_emails": 50,
        "notes": "Teacher contacts by region"
    },
    {
        "name": "Prof-contact.com",
        "url": "https://prof-contact.com/prof-accordeon-france-74-100.html",
        "estimated_emails": 40,
        "notes": "Direct email listings"
    },
    {
        "name": "Accordéonistes de France",
        "url": "https://accordeonistes-de-france.tiuls.fr/3-cours",
        "estimated_emails": 30,
        "notes": "Professional directory with contacts"
    }
]

# Luthiers & Shops (Expected: 60-80 emails)
LUTHIERS_RESOURCES = [
    {
        "name": "CCTheater Luthiers Directory",
        "url": "http://cctheater.free.fr/adresses_luthiers.htm",
        "estimated_emails": 40,
        "notes": "Complete list with emails"
    },
    {
        "name": "dia.to Professionnels",
        "url": "https://dia.to/ressources/professionnels",
        "estimated_emails": 30,
        "notes": "Repair shops and sellers"
    }
]

# Festivals & Events (Expected: 80-100 emails)
FESTIVALS = [
    {
        "name": "Fête de l'Accordéon Luzy",
        "url": "https://fetedelaccordeon.com/contact",
        "email": "contact@fetedelaccordeon.com",
        "estimated_contacts": 5
    },
    {
        "name": "Festival Fréquence Accordéon",
        "url": "https://www.haut2gammes.com/festival",
        "estimated_contacts": 3
    },
    {
        "name": "Festival Chamberet",
        "email": "chamberet@festivalaccordeon.com",
        "estimated_contacts": 2
    }
]

# Net1901 Searches (Expected: 150-200 emails)
NET1901_SEARCHES = [
    {"keyword": "accordéon diatonique", "estimated_results": 80},
    {"keyword": "accordéon traditionnel", "estimated_results": 40},
    {"keyword": "bal folk", "estimated_results": 60},
    {"keyword": "musique traditionnelle", "estimated_results": 50}
]

def generate_collection_checklist():
    """Generate complete collection checklist CSV."""
    
    rows = []
    
    # HelloAsso
    for region, data in HELLOASSO_REGIONS.items():
        rows.append({
            'priority': 'HIGH',
            'source': 'HelloAsso',
            'category': f'Accordéon - {region}',
            'url': data['url'],
            'estimated_emails': data['estimated_emails'],
            'status': 'TODO',
            'collected': 0,
            'notes': f"Browse {data['estimated_associations']} associations"
        })
    
    # AgendaTrad
    for item in AGENDATRAD_URLS:
        rows.append({
            'priority': 'HIGH',
            'source': 'AgendaTrad',
            'category': item['name'],
            'url': item['url'],
            'estimated_emails': item['estimated_emails'],
            'status': 'TODO',
            'collected': 0,
            'notes': item['notes']
        })
    
    # Regional networks
    for item in REGIONAL_NETWORKS:
        rows.append({
            'priority': 'MEDIUM',
            'source': 'Regional Network',
            'category': item['name'],
            'url': item['url'],
            'estimated_emails': item['estimated_emails'],
            'status': 'TODO',
            'collected': 0,
            'notes': ''
        })
    
    # Teacher directories
    for item in TEACHER_DIRECTORIES:
        rows.append({
            'priority': 'HIGH',
            'source': 'Teacher Directory',
            'category': item['name'],
            'url': item['url'],
            'estimated_emails': item['estimated_emails'],
            'status': 'TODO',
            'collected': 0,
            'notes': item.get('notes', '')
        })
    
    # Luthiers
    for item in LUTHIERS_RESOURCES:
        rows.append({
            'priority': 'MEDIUM',
            'source': 'Luthiers',
            'category': item['name'],
            'url': item['url'],
            'estimated_emails': item['estimated_emails'],
            'status': 'TODO',
            'collected': 0,
            'notes': item.get('notes', '')
        })
    
    # Net1901
    for item in NET1901_SEARCHES:
        rows.append({
            'priority': 'MEDIUM',
            'source': 'Net1901',
            'category': f'Search: {item["keyword"]}',
            'url': f'https://www.net1901.org/recherche/{item["keyword"].replace(" ", "+")}',
            'estimated_emails': item['estimated_results'],
            'status': 'TODO',
            'collected': 0,
            'notes': 'Search and extract from profiles'
        })
    
    return rows

def export_checklist(rows, filename='collection_checklist_1000.csv'):
    """Export to CSV."""
    with open(filename, 'w', newline='', encoding='utf-8') as f:
        fieldnames = ['priority', 'source', 'category', 'url', 'estimated_emails', 'status', 'collected', 'notes']
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)
    
    return filename

def print_summary(rows):
    """Print summary statistics."""
    total_estimated = sum(row['estimated_emails'] for row in rows)
    
    by_priority = {}
    by_source = {}
    
    for row in rows:
        priority = row['priority']
        source = row['source']
        emails = row['estimated_emails']
        
        by_priority[priority] = by_priority.get(priority, 0) + emails
        by_source[source] = by_source.get(source, 0) + emails
    
    print("="*80)
    print("COLLECTION CHECKLIST FOR 1000 DIATONIC ACCORDION EMAILS")
    print("="*80)
    print(f"\nTotal URLs to visit: {len(rows)}")
    print(f"Total estimated emails: {total_estimated}")
    print(f"\nBy Priority:")
    for priority in sorted(by_priority.keys()):
        print(f"  {priority}: {by_priority[priority]} emails")
    
    print(f"\nBy Source:")
    for source in sorted(by_source.keys()):
        count = sum(1 for r in rows if r['source'] == source)
        print(f"  {source}: {count} URLs → {by_source[source]} emails")
    
    print("\n" + "="*80)
    print(f"CURRENT STATUS:")
    print(f"  Collected: 45 emails")
    print(f"  Remaining: {total_estimated - 45} emails")
    print(f"  Progress: 4.5%")
    print("="*80)

def main():
    """Main function."""
    rows = generate_collection_checklist()
    filename = export_checklist(rows)
    print_summary(rows)
    
    print(f"\n✅ Checklist exported to: {filename}")
    print("\nNEXT STEPS:")
    print("1. Open collection_checklist_1000.csv in Excel/Google Sheets")
    print("2. Start with HIGH priority sources (HelloAsso, AgendaTrad, Teachers)")
    print("3. Visit each URL, collect emails, mark 'collected' count")
    print("4. Change 'status' to DONE when completed")
    print("5. Track progress toward 1000 emails!")

if __name__ == '__main__':
    main()
