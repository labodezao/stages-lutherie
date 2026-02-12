#!/usr/bin/env python3
"""
International Email Collection - Generate country-specific checklists
Target: 1000 emails per country for diatonic accordion associations
"""

import csv
from urllib.parse import quote_plus

# Country configurations
COUNTRIES = {
    'DE': {
        'name': 'Germany',
        'language': 'de',
        'target_emails': 1000,
        'instrument_terms': ['Akkordeon', 'Steirische Harmonika', 'Harmonika'],
        'music_terms': ['Volksmusik', 'Volkstanz', 'Musikschule'],
        'national_federations': [
            {
                'name': 'Deutscher Harmonikaverband (DHV)',
                'url': 'https://www.harmonika-verband.de/',
                'estimated_emails': 200
            }
        ],
        'search_keywords': [
            'Akkordeon Verein Deutschland',
            'Steirische Harmonika Lehrer',
            'Volksmusik Schule Deutschland',
            'Harmonika Verband'
        ]
    },
    'IT': {
        'name': 'Italy',
        'language': 'it',
        'target_emails': 1000,
        'instrument_terms': ['organetto', 'fisarmonica diatonica'],
        'music_terms': ['musica popolare', 'musica tradizionale', 'scuola di musica'],
        'national_federations': [
            {
                'name': 'Associazione Italiana Fisarmonicisti',
                'url': 'http://www.fisarmonicisti.it/',
                'estimated_emails': 150
            }
        ],
        'search_keywords': [
            'organetto associazione Italia',
            'fisarmonica diatonica scuola',
            'musica popolare italiana',
            'scuola organetto'
        ]
    },
    'IE': {
        'name': 'Ireland',
        'language': 'en',
        'target_emails': 1000,
        'instrument_terms': ['button accordion', 'melodeon', 'concertina'],
        'music_terms': ['traditional Irish music', 'céilí', 'fleadh'],
        'national_federations': [
            {
                'name': 'Comhaltas Ceoltóirí Éireann (CCE)',
                'url': 'https://comhaltas.ie/',
                'estimated_emails': 250
            }
        ],
        'search_keywords': [
            'button accordion teacher Ireland',
            'CCE branch Ireland',
            'traditional Irish music association',
            'fleadh cheoil'
        ]
    },
    'GB': {
        'name': 'United Kingdom',
        'language': 'en',
        'target_emails': 1000,
        'instrument_terms': ['melodeon', 'button accordion', 'concertina'],
        'music_terms': ['folk music', 'morris dancing', 'ceilidh', 'folk club'],
        'national_federations': [
            {
                'name': 'English Folk Dance and Song Society (EFDSS)',
                'url': 'https://www.efdss.org/',
                'estimated_emails': 200
            }
        ],
        'search_keywords': [
            'melodeon teacher UK',
            'morris dancing group',
            'folk club England',
            'ceilidh band Scotland',
            'EFDSS member'
        ]
    },
    'BE': {
        'name': 'Belgium',
        'language': 'fr/nl',
        'target_emails': 1000,
        'instrument_terms': ['accordéon diatonique', 'trekharmonica'],
        'music_terms': ['musique folk', 'bal folk', 'volksmuziek'],
        'national_federations': [],
        'search_keywords': [
            'accordéon diatonique Belgique',
            'volksmuziek vereniging België',
            'bal folk Belgium',
            'école musique traditionnelle'
        ]
    },
    'CH': {
        'name': 'Switzerland',
        'language': 'de/fr/it',
        'target_emails': 1000,
        'instrument_terms': ['Schwyzerörgeli', 'accordéon diatonique'],
        'music_terms': ['Volksmusik', 'musique folklorique'],
        'national_federations': [
            {
                'name': 'Eidgenössischer Jodlerverband',
                'url': 'https://www.jodlerverband.ch/',
                'estimated_emails': 100
            }
        ],
        'search_keywords': [
            'Schwyzerörgeli Lehrer Schweiz',
            'accordéon diatonique Suisse',
            'Volksmusik Verein Schweiz'
        ]
    },
    'ES': {
        'name': 'Spain',
        'language': 'es',
        'target_emails': 800,
        'instrument_terms': ['trikitixa', 'acordeón diatónico'],
        'music_terms': ['música tradicional', 'música popular'],
        'national_federations': [],
        'search_keywords': [
            'trikitixa asociación País Vasco',
            'acordeón diatónico escuela España',
            'música tradicional asociación'
        ]
    },
    'AT': {
        'name': 'Austria',
        'language': 'de',
        'target_emails': 800,
        'instrument_terms': ['Steirische Harmonika'],
        'music_terms': ['Volksmusik', 'Alpenmusik'],
        'national_federations': [
            {
                'name': 'Österreichischer Volksmusikbund',
                'url': 'http://www.volksmusikbund.at/',
                'estimated_emails': 100
            }
        ],
        'search_keywords': [
            'Steirische Harmonika Lehrer Österreich',
            'Volksmusik Verein Österreich'
        ]
    },
    'NL': {
        'name': 'Netherlands',
        'language': 'nl',
        'target_emails': 800,
        'instrument_terms': ['accordeon', 'trekharmonica'],
        'music_terms': ['volksmuziek', 'balfolk'],
        'national_federations': [],
        'search_keywords': [
            'accordeon les Nederland',
            'volksmuziek vereniging',
            'balfolk Nederland'
        ]
    },
    'PT': {
        'name': 'Portugal',
        'language': 'pt',
        'target_emails': 500,
        'instrument_terms': ['concertina', 'acordeão'],
        'music_terms': ['música tradicional'],
        'national_federations': [],
        'search_keywords': [
            'concertina Portugal associação',
            'música tradicional portuguesa'
        ]
    }
}

def generate_country_checklist(country_code):
    """Generate collection checklist for a specific country."""
    if country_code not in COUNTRIES:
        print(f"Country {country_code} not configured")
        return []
    
    config = COUNTRIES[country_code]
    urls = []
    
    # National federations
    for fed in config['national_federations']:
        urls.append({
            'country': country_code,
            'priority': 'HIGH',
            'source': 'National Federation',
            'category': fed['name'],
            'url': fed['url'],
            'estimated_emails': fed['estimated_emails'],
            'status': 'TODO'
        })
    
    # Google searches for associations
    for keyword in config['search_keywords']:
        urls.append({
            'country': country_code,
            'priority': 'HIGH',
            'source': 'Google Search',
            'category': keyword,
            'url': f'https://www.google.com/search?q={quote_plus(keyword)}',
            'estimated_emails': 50,
            'status': 'TODO'
        })
    
    # Facebook groups
    for term in config['instrument_terms'][:2]:  # Top 2 instrument terms
        urls.append({
            'country': country_code,
            'priority': 'MEDIUM',
            'source': 'Facebook',
            'category': f'{term} groups',
            'url': f'https://www.facebook.com/search/groups/?q={quote_plus(f"{term} {config["name"]}")}',
            'estimated_emails': 100,
            'status': 'TODO'
        })
    
    # Music school searches
    for term in config['music_terms'][:2]:  # Top 2 music terms
        urls.append({
            'country': country_code,
            'priority': 'MEDIUM',
            'source': 'Google Search',
            'category': f'{term} schools',
            'url': f'https://www.google.com/search?q={quote_plus(f"{term} {config["name"]} contact")}',
            'estimated_emails': 30,
            'status': 'TODO'
        })
    
    return urls

def generate_all_countries_checklist():
    """Generate master checklist for all countries."""
    all_urls = []
    
    # Add header info
    summary = []
    
    for country_code in sorted(COUNTRIES.keys()):
        config = COUNTRIES[country_code]
        country_urls = generate_country_checklist(country_code)
        all_urls.extend(country_urls)
        
        summary.append({
            'country': country_code,
            'name': config['name'],
            'target': config['target_emails'],
            'urls': len(country_urls),
            'total_estimated': sum(url['estimated_emails'] for url in country_urls if isinstance(url['estimated_emails'], int))
        })
    
    return all_urls, summary

def export_to_csv(urls, filename='international_checklist.csv'):
    """Export checklist to CSV."""
    with open(filename, 'w', newline='', encoding='utf-8') as f:
        fieldnames = ['country', 'priority', 'source', 'category', 'url', 'estimated_emails', 'status', 'collected']
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        
        writer.writeheader()
        for url in urls:
            url['collected'] = 0  # Initialize collected count
            writer.writerow(url)
    
    return filename

def print_summary(summary):
    """Print collection summary."""
    print("\n" + "="*80)
    print("INTERNATIONAL EMAIL COLLECTION - SUMMARY")
    print("="*80)
    print(f"\n{'Country':<20} {'Code':<6} {'Target':<8} {'URLs':<6} {'Est. Emails'}")
    print("-" * 80)
    
    total_target = 0
    total_urls = 0
    total_estimated = 0
    
    for item in summary:
        print(f"{item['name']:<20} {item['country']:<6} {item['target']:<8} {item['urls']:<6} {item['total_estimated']}")
        total_target += item['target']
        total_urls += item['urls']
        total_estimated += item['total_estimated']
    
    print("-" * 80)
    print(f"{'TOTAL':<20} {len(summary)} {'countries':<8} {total_target:<8} {total_urls:<6} {total_estimated}")
    print("="*80)

def main():
    """Main function."""
    print("="*80)
    print("INTERNATIONAL EMAIL COLLECTION CHECKLIST GENERATOR")
    print("="*80)
    
    urls, summary = generate_all_countries_checklist()
    
    filename = export_to_csv(urls)
    print(f"\n✅ Checklist exported to: {filename}")
    print(f"   Total URLs: {len(urls)}")
    
    print_summary(summary)
    
    print("\n" + "="*80)
    print("NEXT STEPS:")
    print("="*80)
    print("1. Complete France collection first (955 emails remaining)")
    print("2. Start with Germany (HIGH priority federation + searches)")
    print("3. Use systematic approach per country:")
    print("   - Visit national federations first")
    print("   - Execute Google searches")
    print("   - Join & scrape Facebook groups")
    print("4. Track progress in international_checklist.csv")
    print("5. Deduplicate across countries when merging")
    print("="*80)

if __name__ == '__main__':
    main()
