#!/usr/bin/env python3
"""
Advanced web scraper to collect 1000+ email addresses from:
- HelloAsso (associations)
- AgendaTrad (folk organizers)
- Net1901 (association directory)
- Chorotempo (traditional music organizers)

This script can scrape multiple sources to build a comprehensive contact database.
"""

import re
import csv
import sys
import time
import argparse
from urllib.parse import urljoin, urlparse
import json

try:
    import requests
    from bs4 import BeautifulSoup
    HAS_LIBS = True
except ImportError:
    print("Error: Required libraries not installed.", file=sys.stderr)
    print("Install with: pip install requests beautifulsoup4", file=sys.stderr)
    sys.exit(1)


# Shared email regex pattern
EMAIL_REGEX = r'\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b'


class ContactScraper:
    """Multi-source contact scraper for associations."""
    
    def __init__(self, delay=2):
        self.delay = delay
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
        self.emails = set()
        self.contacts = []
        
    def extract_emails_from_text(self, text):
        """Extract email addresses from text."""
        return re.findall(EMAIL_REGEX, text)
    
    def fetch_page(self, url):
        """Fetch a web page with error handling."""
        try:
            print(f"  Fetching: {url}")
            response = self.session.get(url, timeout=30)
            response.raise_for_status()
            time.sleep(self.delay)
            return response.text
        except Exception as e:
            print(f"  Error fetching {url}: {e}", file=sys.stderr)
            return None
    
    def scrape_helloasso_category(self, category="accordeon", regions=None):
        """
        Scrape HelloAsso directory for associations.
        Categories: accordeon, musique, bal-folk, danse, culture
        Note: This version scrapes the main listing page only.
        Individual association pages could be scraped for more emails but would be much slower.
        """
        print(f"\n=== Scraping HelloAsso: {category} ===")
        
        if regions is None:
            # All French regions
            regions = [
                "bretagne", "pays--de--la--loire", "nouvelle-aquitaine",
                "occitanie", "auvergne-rhone-alpes", "provence-alpes-cote-d-azur",
                "ile-de-france", "grand-est", "hauts-de-france", "normandie",
                "bourgogne-franche-comte", "centre-val-de-loire"
            ]
        
        for region in regions:
            url = f"https://www.helloasso.com/e/reg/{region}/cat/{category}"
            html = self.fetch_page(url)
            if html:
                self._parse_helloasso_page(html, region, category)
                
        print(f"  Found {len(self.emails)} unique emails so far")
    
    def _parse_helloasso_page(self, html, region, category):
        """Parse a HelloAsso page for contact information."""
        soup = BeautifulSoup(html, 'html.parser')
        
        # Look for emails in the page
        text_content = soup.get_text()
        emails = self.extract_emails_from_text(text_content)
        
        for email in emails:
            if email not in self.emails and not self._is_excluded_email(email):
                self.emails.add(email)
                self.contacts.append({
                    'email': email.lower(),
                    'source': 'HelloAsso',
                    'category': category,
                    'region': region,
                    'context': ''
                })
        
        # Look for association links to visit
        for link in soup.find_all('a', href=True):
            href = link['href']
            if '/associations/' in href and href.startswith('http'):
                # Could visit individual association pages (but would be very slow)
                pass
    
    def scrape_agendatrad_organizers(self, countries=["France"]):
        """
        Scrape AgendaTrad for event organizers.
        AgendaTrad is a major folk music directory.
        Note: Scrapes the main organizer listing page.
        """
        print(f"\n=== Scraping AgendaTrad organizers ===")
        
        for country in countries:
            url = f"https://agendatrad.org/orgas/{country}"
            html = self.fetch_page(url)
            if html:
                self._parse_agendatrad_page(html, country)
        
        print(f"  Found {len(self.emails)} unique emails so far")
    
    def _parse_agendatrad_page(self, html, country):
        """Parse AgendaTrad organizer listing."""
        soup = BeautifulSoup(html, 'html.parser')
        
        # Extract all text and find emails
        text_content = soup.get_text()
        emails = self.extract_emails_from_text(text_content)
        
        for email in emails:
            if email not in self.emails and not self._is_excluded_email(email):
                self.emails.add(email)
                self.contacts.append({
                    'email': email.lower(),
                    'source': 'AgendaTrad',
                    'category': 'folk-organizer',
                    'region': country,
                    'context': ''
                })
    
    def scrape_chorotempo_organizers(self, countries=["France"]):
        """Scrape Chorotempo for traditional music organizers."""
        print(f"\n=== Scraping Chorotempo organizers ===")
        
        for country in countries:
            url = f"https://chorotempo.org/organisateurs/{country}"
            html = self.fetch_page(url)
            if html:
                self._parse_chorotempo_page(html, country)
        
        print(f"  Found {len(self.emails)} unique emails so far")
    
    def _parse_chorotempo_page(self, html, country):
        """Parse Chorotempo organizer listing."""
        soup = BeautifulSoup(html, 'html.parser')
        
        text_content = soup.get_text()
        emails = self.extract_emails_from_text(text_content)
        
        for email in emails:
            if email not in self.emails and not self._is_excluded_email(email):
                self.emails.add(email)
                self.contacts.append({
                    'email': email.lower(),
                    'source': 'Chorotempo',
                    'category': 'trad-organizer',
                    'region': country,
                    'context': ''
                })
    
    def scrape_net1901_search(self, keywords=["accordéon", "folk", "musique traditionnelle"]):
        """
        Scrape Net1901 association directory.
        Note: This is a placeholder. Net1901 requires specific implementation
        as their site structure may use JavaScript or require form submissions.
        Consider manual collection from Net1901 instead.
        """
        print(f"\n=== Scraping Net1901 directory ===")
        print(f"  ⚠️  WARNING: Net1901 scraping not fully implemented")
        print(f"  Recommend manual collection from: https://www.net1901.org/")
        
        for keyword in keywords:
            print(f"  Would search for: {keyword}")
        
        print(f"  Found {len(self.emails)} unique emails so far")
    
    def _is_excluded_email(self, email):
        """Filter out generic/admin emails and platform addresses."""
        excluded_domains = [
            'example.com', 'test.com', 'localhost',
            'helloasso.org', 'helloasso.com'
        ]
        excluded_keywords = [
            'noreply', 'no-reply', 'donotreply'
        ]
        excluded_prefixes = [
            'admin@', 'webmaster@', 'postmaster@',
            'support@', 'hello@'
        ]
        # Allow 'info@' and 'contact@' as these are often real association contacts
        
        email_lower = email.lower()
        
        # Check domains
        for domain in excluded_domains:
            if domain in email_lower:
                return True
        
        # Check keywords
        for keyword in excluded_keywords:
            if keyword in email_lower:
                return True
        
        # Check prefixes
        for prefix in excluded_prefixes:
            if email_lower.startswith(prefix):
                return True
        
        return False
    
    def export_to_csv(self, filename='contacts_mass.csv'):
        """Export all collected contacts to CSV."""
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            fieldnames = ['email', 'source', 'category', 'region', 'context']
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            writer.writerows(self.contacts)
        
        print(f"\n✓ Exported {len(self.contacts)} contacts to {filename}")
        return filename
    
    def print_summary(self):
        """Print collection summary."""
        print("\n" + "="*80)
        print("COLLECTION SUMMARY")
        print("="*80)
        print(f"Total unique emails: {len(self.emails)}")
        
        # Count by source
        sources = {}
        for contact in self.contacts:
            source = contact['source']
            sources[source] = sources.get(source, 0) + 1
        
        print("\nBy source:")
        for source, count in sorted(sources.items()):
            print(f"  {source}: {count}")


def main():
    parser = argparse.ArgumentParser(
        description='Mass email collector for folk/traditional music associations'
    )
    parser.add_argument(
        '--delay',
        type=float,
        default=2,
        help='Delay between requests in seconds (default: 2)'
    )
    parser.add_argument(
        '--output',
        default='contacts_mass.csv',
        help='Output CSV file (default: contacts_mass.csv)'
    )
    parser.add_argument(
        '--sources',
        nargs='+',
        default=['helloasso', 'agendatrad', 'chorotempo'],
        help='Sources to scrape: helloasso agendatrad chorotempo net1901'
    )
    parser.add_argument(
        '--categories',
        nargs='+',
        default=['accordeon', 'musique', 'bal-folk', 'danse'],
        help='HelloAsso categories to search'
    )
    
    args = parser.parse_args()
    
    scraper = ContactScraper(delay=args.delay)
    
    print("="*80)
    print("MASS CONTACT COLLECTION TOOL")
    print("="*80)
    print(f"Target: 1000+ email addresses")
    print(f"Sources: {', '.join(args.sources)}")
    print(f"Delay: {args.delay}s between requests")
    print("="*80)
    
    # Scrape each source
    if 'helloasso' in args.sources:
        for category in args.categories:
            scraper.scrape_helloasso_category(category=category)
    
    if 'agendatrad' in args.sources:
        scraper.scrape_agendatrad_organizers()
    
    if 'chorotempo' in args.sources:
        scraper.scrape_chorotempo_organizers()
    
    if 'net1901' in args.sources:
        scraper.scrape_net1901_search()
    
    # Export results
    scraper.print_summary()
    scraper.export_to_csv(args.output)
    
    if len(scraper.emails) < 1000:
        print("\n⚠️  Warning: Collected fewer than 1000 emails")
        print("Tips to collect more:")
        print("  - Add more categories: --categories accordeon musique bal-folk danse culture")
        print("  - Scrape individual association pages (slower)")
        print("  - Add more sources")
        print("  - Expand to neighboring countries")
    
    return 0


if __name__ == '__main__':
    sys.exit(main())
