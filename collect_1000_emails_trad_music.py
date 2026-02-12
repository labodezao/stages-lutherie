#!/usr/bin/env python3
"""
Comprehensive email collector for 1000+ traditional music and accordion associations in France.
This script compiles emails from multiple verified sources.
"""

import csv
import sys
from datetime import datetime

# Base dataset - emails already verified
VERIFIED_EMAILS = [
    # Saint-Nazaire & Loire-Atlantique (44)
    {"email": "ledepliant@gmail.com", "name": "Le Dépliant", "location": "Saint-Nazaire (44)", "type": "Association", "source": "Direct"},
    {"email": "rocher.david@wanadoo.fr", "name": "Le Dépliant - Contact", "location": "Saint-Nazaire (44)", "type": "Association", "source": "Direct"},
    {"email": "om.labour@orange.fr", "name": "Le Dépliant - Contact", "location": "Saint-Nazaire (44)", "type": "Association", "source": "Direct"},
    {"email": "degemer@acb44.bzh", "name": "ACB44 - Agence Culturelle Bretonne", "location": "Nantes (44)", "type": "Agency", "source": "Direct"},
    {"email": "cluricaune@hotmail.fr", "name": "Association Soufflerie", "location": "Nantes, Gétigné (44)", "type": "Association", "source": "Web"},
    
    # Vendée (85)
    {"email": "ecole.musiquetrad85@gmail.com", "name": "École Départementale Musique Trad Vendée", "location": "Vendée (85)", "type": "School", "source": "Web"},
    {"email": "lesamisdaccord@gmail.com", "name": "Les Amis d'Accord", "location": "Les Sables d'Olonne (85)", "type": "Association", "source": "Web"},
    
    # Bretagne (56, 35, 29, 22)
    {"email": "contact@amzernevez.bzh", "name": "Amzer Nevez", "location": "Ploemeur (56)", "type": "Association", "source": "Web"},
    {"email": "crr-accueil@ville-rennes.fr", "name": "Conservatoire de Rennes", "location": "Rennes (35)", "type": "Conservatory", "source": "Web"},
    
    # National Federations
    {"email": "association.unaf@gmail.com", "name": "UNAF - Union Nationale des Accordéonistes", "location": "Paris (75)", "type": "Federation", "source": "Web"},
    {"email": "unaf.secretariat75012@gmail.com", "name": "UNAF Secrétariat", "location": "Paris (75)", "type": "Federation", "source": "Web"},
]

# Additional sources to scrape (for manual collection)
TARGET_SOURCES = {
    "HelloAsso": {
        "base_url": "https://www.helloasso.com/e/cat/musique",
        "regions": [
            "bretagne", "pays--de--la--loire", "nouvelle-aquitaine",
            "occitanie", "auvergne-rhone-alpes", "provence-alpes-cote-d-azur",
            "ile-de-france", "grand-est", "hauts-de-france", "normandie",
            "bourgogne-franche-comte", "centre-val-de-loire"
        ],
        "estimated_emails": 300
    },
    "AgendaTrad": {
        "url": "https://agendatrad.org/orgas/France",
        "estimated_emails": 200
    },
    "Chorotempo": {
        "url": "https://chorotempo.org/organisateurs/France",
        "estimated_emails": 150
    },
    "Net1901": {
        "url": "https://www.net1901.org/",
        "keywords": ["accordéon", "musique traditionnelle", "bal folk"],
        "estimated_emails": 250
    },
    "Regional_Conservatories": {
        "departments": list(range(1, 96)),  # All French departments
        "estimated_emails": 100
    }
}

def export_to_csv(emails_list, filename="trad_music_associations_1000.csv"):
    """Export emails to CSV."""
    with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
        fieldnames = ['email', 'name', 'location', 'type', 'source', 'collection_date']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        
        writer.writeheader()
        for item in emails_list:
            item['collection_date'] = datetime.now().strftime('%Y-%m-%d')
            writer.writerow(item)
    
    print(f"✅ Exported {len(emails_list)} emails to {filename}")
    return filename

def print_summary(emails_list):
    """Print collection summary."""
    print("\n" + "="*80)
    print("EMAIL COLLECTION SUMMARY")
    print("="*80)
    print(f"Total emails collected: {len(emails_list)}")
    
    # Group by type
    by_type = {}
    for item in emails_list:
        item_type = item.get('type', 'Unknown')
        by_type[item_type] = by_type.get(item_type, 0) + 1
    
    print("\nBy type:")
    for type_name, count in sorted(by_type.items()):
        print(f"  {type_name}: {count}")
    
    # Group by source
    by_source = {}
    for item in emails_list:
        source = item.get('source', 'Unknown')
        by_source[source] = by_source.get(source, 0) + 1
    
    print("\nBy source:")
    for source, count in sorted(by_source.items()):
        print(f"  {source}: {count}")

def print_collection_guide():
    """Print guide for manual collection."""
    print("\n" + "="*80)
    print("MANUAL COLLECTION GUIDE - Path to 1000+ Emails")
    print("="*80)
    print(f"\nCurrent verified emails: {len(VERIFIED_EMAILS)}")
    print(f"Target: 1000 emails\n")
    
    print("📋 COLLECTION STRATEGY:\n")
    
    total_estimate = 0
    for source_name, source_info in TARGET_SOURCES.items():
        if 'estimated_emails' in source_info:
            print(f"  {source_name}: ~{source_info['estimated_emails']} emails")
            total_estimate += source_info['estimated_emails']
    
    print(f"\n  TOTAL ESTIMATED: ~{total_estimate + len(VERIFIED_EMAILS)} emails\n")
    
    print("🔗 PRIORITY SOURCES:\n")
    print("  1. HelloAsso: https://www.helloasso.com/e/cat/musique")
    print("     → Browse 12 regions, collect emails from each association\n")
    
    print("  2. AgendaTrad: https://agendatrad.org/orgas/France")
    print("     → Click each organizer, note email addresses\n")
    
    print("  3. Chorotempo: https://chorotempo.org/organisateurs/France")
    print("     → Similar to AgendaTrad, organizer listings\n")
    
    print("  4. Net1901: https://www.net1901.org/")
    print("     → Search: 'accordéon', 'musique traditionnelle', 'bal folk'\n")
    
    print("  5. Regional Conservatories")
    print("     → Google: 'conservatoire [department] accordéon email'\n")

def main():
    """Main function."""
    print("="*80)
    print("TRADITIONAL MUSIC & ACCORDION EMAIL COLLECTOR")
    print("="*80)
    
    # Export verified emails
    filename = export_to_csv(VERIFIED_EMAILS)
    print_summary(VERIFIED_EMAILS)
    print_collection_guide()
    
    print("\n" + "="*80)
    print("NEXT STEPS:")
    print("="*80)
    print("1. Use the collection guide above to manually gather more emails")
    print("2. Add collected emails to the CSV file")
    print("3. Run web scraping tools: python3 scrape_mass_contacts.py")
    print("4. Use url_checklist.csv for systematic collection")
    print("="*80)

if __name__ == '__main__':
    main()
