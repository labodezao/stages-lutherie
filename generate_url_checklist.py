#!/usr/bin/env python3
"""
URL List Generator - Creates a list of URLs to manually visit for data collection.
This generates a checklist of all the URLs you need to visit to collect 1000+ emails.
"""

import csv

def generate_url_checklist():
    """Generate a comprehensive list of URLs to visit."""
    
    urls = []
    
    # HelloAsso by region and category
    regions = [
        ("Bretagne", "bretagne"),
        ("Pays de la Loire", "pays--de--la--loire"),
        ("Nouvelle-Aquitaine", "nouvelle-aquitaine"),
        ("Occitanie", "occitanie"),
        ("Auvergne-Rhône-Alpes", "auvergne-rhone-alpes"),
        ("PACA", "provence-alpes-cote-d-azur"),
        ("Île-de-France", "ile-de-france"),
        ("Grand Est", "grand-est"),
        ("Hauts-de-France", "hauts-de-france"),
        ("Normandie", "normandie"),
        ("Bourgogne-Franche-Comté", "bourgogne-franche-comte"),
        ("Centre-Val de Loire", "centre-val-de-loire"),
    ]
    
    categories = [
        ("Accordéon", "accordeon"),
        ("Musique", "musique"),
        ("Bal Folk", "bal-folk"),
        ("Danse", "danse"),
        ("Culture", "culture"),
    ]
    
    # Generate HelloAsso URLs
    for cat_name, cat_slug in categories:
        for region_name, region_slug in regions:
            urls.append({
                'source': 'HelloAsso',
                'category': f'{cat_name} - {region_name}',
                'url': f'https://www.helloasso.com/e/reg/{region_slug}/cat/{cat_slug}',
                'estimated_contacts': '10-50',
                'priority': 'HIGH' if cat_slug == 'accordeon' else 'MEDIUM',
                'done': 'NO'
            })
    
    # AgendaTrad
    urls.append({
        'source': 'AgendaTrad',
        'category': 'Organisateurs France',
        'url': 'https://agendatrad.org/orgas/Francia',
        'estimated_contacts': '200-400',
        'priority': 'HIGH',
        'done': 'NO'
    })
    
    # Chorotempo
    urls.append({
        'source': 'Chorotempo',
        'category': 'Organisateurs France',
        'url': 'https://chorotempo.org/organisateurs/France',
        'estimated_contacts': '100-200',
        'priority': 'HIGH',
        'done': 'NO'
    })
    
    # Net1901 searches
    net1901_keywords = [
        "accordéon", "accordeon diatonique", "musique traditionnelle",
        "bal folk", "fest noz", "folk", "musette"
    ]
    for keyword in net1901_keywords:
        urls.append({
            'source': 'Net1901',
            'category': f'Recherche: {keyword}',
            'url': f'https://www.net1901.org/recherche/{keyword.replace(" ", "+")}',
            'estimated_contacts': '20-100',
            'priority': 'MEDIUM',
            'done': 'NO'
        })
    
    # Conservatoires by department
    departments_44_vicinity = [
        ("Loire-Atlantique", "44"),
        ("Vendée", "85"),
        ("Maine-et-Loire", "49"),
        ("Morbihan", "56"),
        ("Finistère", "29"),
        ("Ille-et-Vilaine", "35"),
        ("Côtes-d'Armor", "22"),
    ]
    
    for dept_name, dept_num in departments_44_vicinity:
        urls.append({
            'source': 'Google Search',
            'category': f'Conservatoires {dept_name}',
            'url': f'https://www.google.com/search?q=conservatoire+école+musique+{dept_name.replace(" ", "+")}+contact+email',
            'estimated_contacts': '10-30',
            'priority': 'MEDIUM',
            'done': 'NO'
        })
    
    # Festivals
    urls.append({
        'source': 'Google Search',
        'category': 'Festivals folk/trad France',
        'url': 'https://www.google.com/search?q=festival+folk+musique+traditionnelle+France+2025+2026+contact',
        'estimated_contacts': '50-100',
        'priority': 'MEDIUM',
        'done': 'NO'
    })
    
    # Luthiers
    urls.append({
        'source': 'Google Search',
        'category': 'Luthiers accordéon France',
        'url': 'https://www.google.com/search?q=luthier+accordéon+diatonique+France+contact+email',
        'estimated_contacts': '20-50',
        'priority': 'LOW',
        'done': 'NO'
    })
    
    # Facebook groups (manual)
    facebook_groups = [
        "BALS FOLK TRAD PARISIENS",
        "Folk Info / Folk Weekender",
        "Accordéon Diatonique – Passionnés",
        "Musique Traditionnelle en France",
    ]
    
    for group in facebook_groups:
        urls.append({
            'source': 'Facebook',
            'category': f'Groupe: {group}',
            'url': f'https://www.facebook.com/search/groups/?q={group.replace(" ", "%20")}',
            'estimated_contacts': 'Variable (membres du groupe)',
            'priority': 'LOW',
            'done': 'NO'
        })
    
    return urls


def export_checklist_to_csv(urls, filename='url_checklist.csv'):
    """Export URL checklist to CSV."""
    with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
        fieldnames = ['done', 'priority', 'source', 'category', 'url', 'estimated_contacts', 'emails_found', 'notes']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()
        
        for url_item in urls:
            writer.writerow({
                'done': url_item.get('done', 'NO'),
                'priority': url_item.get('priority', 'MEDIUM'),
                'source': url_item['source'],
                'category': url_item['category'],
                'url': url_item['url'],
                'estimated_contacts': url_item['estimated_contacts'],
                'emails_found': '',  # To be filled manually
                'notes': ''  # To be filled manually
            })
    
    print(f"✓ URL checklist exported to {filename}")
    print(f"  Total URLs to visit: {len(urls)}")
    
    # Calculate estimates
    high_priority = sum(1 for u in urls if u.get('priority') == 'HIGH')
    medium_priority = sum(1 for u in urls if u.get('priority') == 'MEDIUM')
    low_priority = sum(1 for u in urls if u.get('priority') == 'LOW')
    
    print(f"\n  Priority breakdown:")
    print(f"    HIGH: {high_priority} URLs")
    print(f"    MEDIUM: {medium_priority} URLs")
    print(f"    LOW: {low_priority} URLs")


def export_empty_contacts_template(filename='contacts_template.csv'):
    """Export an empty template for manually entering contacts."""
    with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
        fieldnames = [
            'email', 'association_name', 'contact_name', 'phone', 'website',
            'address', 'city', 'postal_code', 'department', 'region',
            'type', 'source', 'date_collected', 'notes', 'validated'
        ]
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()
        
        # Add a sample row as example
        writer.writerow({
            'email': 'example@association.fr',
            'association_name': 'Association Example',
            'contact_name': 'Jean Dupont',
            'phone': '02 40 00 00 00',
            'website': 'https://www.example.fr',
            'address': '1 rue de la Musique',
            'city': 'Nantes',
            'postal_code': '44000',
            'department': 'Loire-Atlantique',
            'region': 'Pays de la Loire',
            'type': 'Association',
            'source': 'HelloAsso',
            'date_collected': '2026-02-12',
            'notes': 'Cours accordéon diatonique',
            'validated': 'NO'
        })
    
    print(f"✓ Empty contacts template exported to {filename}")


def main():
    """Main function."""
    print("="*80)
    print("URL CHECKLIST GENERATOR FOR 1000+ EMAILS COLLECTION")
    print("="*80)
    print()
    
    # Generate URL checklist
    urls = generate_url_checklist()
    export_checklist_to_csv(urls)
    
    print()
    
    # Generate empty contacts template
    export_empty_contacts_template()
    
    print()
    print("="*80)
    print("NEXT STEPS:")
    print("="*80)
    print("1. Open url_checklist.csv in Excel/Google Sheets")
    print("2. Start with HIGH priority URLs")
    print("3. Visit each URL and collect contact information")
    print("4. Enter collected data in contacts_template.csv")
    print("5. Mark 'done' = YES and add 'emails_found' count in url_checklist.csv")
    print("6. Track your progress!")
    print()
    print("Estimated time: 10-15 hours of manual work")
    print("Expected result: 800-1200 unique email contacts")
    print("="*80)


if __name__ == '__main__':
    main()
