#!/usr/bin/env python3
"""
Script to collect contact email addresses from diatonic accordion associations
in the Loire-Atlantique (44) region, specifically from the ACB44 website.

This script scrapes the webpage: https://www.acb44.bzh/index.php/cours/musique/97-accordeon-diatonique
and extracts all email addresses along with association information.

Usage:
    python3 collect_associations_emails.py [url]
    python3 collect_associations_emails.py --file <html_file>
"""

import re
import csv
import sys
import argparse
import os

try:
    import requests
    from bs4 import BeautifulSoup
    HAS_REQUESTS = True
except ImportError:
    HAS_REQUESTS = False
    from urllib.request import urlopen, Request
    from urllib.error import URLError, HTTPError


# Shared email regex pattern
EMAIL_REGEX = r'\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b'


def fetch_webpage_requests(url):
    """Fetch webpage content using requests library."""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        response = requests.get(url, headers=headers, timeout=30)
        response.raise_for_status()
        return response.text
    except Exception as e:
        print(f"Error fetching webpage with requests: {e}", file=sys.stderr)
        return None


def fetch_webpage_urllib(url):
    """Fetch webpage content using urllib."""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        req = Request(url, headers=headers)
        with urlopen(req, timeout=30) as response:
            content = response.read().decode('utf-8', errors='ignore')
            return content
    except HTTPError as e:
        print(f"HTTP Error {e.code}: {e.reason}", file=sys.stderr)
        return None
    except URLError as e:
        print(f"URL Error: {e.reason}", file=sys.stderr)
        return None
    except Exception as e:
        print(f"Error fetching webpage: {e}", file=sys.stderr)
        return None


def fetch_webpage(url):
    """Fetch webpage content, trying requests first, then urllib."""
    if HAS_REQUESTS:
        content = fetch_webpage_requests(url)
        if content:
            return content
    
    return fetch_webpage_urllib(url)


def extract_emails_from_html(html_content):
    """Extract emails from HTML content using BeautifulSoup or regex."""
    emails_data = []
    
    if HAS_REQUESTS:
        # Use BeautifulSoup for better parsing
        soup = BeautifulSoup(html_content, 'html.parser')
        text_content = soup.get_text()
        
        # Find all emails using shared regex
        emails_found = re.findall(EMAIL_REGEX, text_content)
        
        # Try to find context for each email
        for email in emails_found:
            # Search for the email in the original HTML to find nearby text
            email_lower = email.lower()
            
            # Find context around email (look for nearby text in paragraphs, divs, etc.)
            context = ""
            for element in soup.find_all(['p', 'div', 'td', 'li', 'span']):
                element_text = element.get_text()
                if email_lower in element_text.lower():
                    context = element_text.strip()
                    break
            
            emails_data.append({
                'email': email_lower,
                'context': context[:300]  # Limit context length
            })
    else:
        # Fallback to simple regex with shared pattern
        emails_found = re.findall(EMAIL_REGEX, html_content)
        for email in emails_found:
            emails_data.append({
                'email': email.lower(),
                'context': 'Context extraction not available'
            })
    
    # Remove duplicates
    seen = set()
    unique_emails = []
    for item in emails_data:
        if item['email'] not in seen:
            seen.add(item['email'])
            unique_emails.append(item)
    
    return unique_emails


def save_to_csv(emails_data, output_file='extracted_emails.csv'):
    """Save extracted emails to CSV file."""
    with open(output_file, 'w', newline='', encoding='utf-8') as csvfile:
        fieldnames = ['email', 'association_context']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        
        writer.writeheader()
        for item in emails_data:
            writer.writerow({
                'email': item['email'],
                'association_context': item['context']
            })
    
    return output_file


def main():
    """Main function to orchestrate the email collection."""
    parser = argparse.ArgumentParser(
        description='Extract email addresses from ACB44 accordion associations webpage'
    )
    parser.add_argument(
        'url',
        nargs='?',
        default='https://www.acb44.bzh/index.php/cours/musique/97-accordeon-diatonique',
        help='URL to scrape (default: ACB44 accordion page)'
    )
    parser.add_argument(
        '--file',
        help='Path to local HTML file instead of URL'
    )
    parser.add_argument(
        '--output',
        default='associations_accordeon_emails.csv',
        help='Output CSV file path (default: associations_accordeon_emails.csv)'
    )
    
    args = parser.parse_args()
    
    # Get HTML content
    if args.file:
        print(f"Reading HTML from file: {args.file}")
        if not os.path.exists(args.file):
            print(f"Error: File not found: {args.file}", file=sys.stderr)
            sys.exit(1)
        with open(args.file, 'r', encoding='utf-8') as f:
            html_content = f.read()
    else:
        url = args.url
        print(f"Fetching data from: {url}")
        html_content = fetch_webpage(url)
        
        if not html_content:
            print("\nFailed to fetch webpage.", file=sys.stderr)
            print("\nAlternative: You can save the webpage HTML manually and use:", file=sys.stderr)
            print(f"  python3 {sys.argv[0]} --file page.html", file=sys.stderr)
            sys.exit(1)
    
    print("Extracting email addresses...")
    emails_data = extract_emails_from_html(html_content)
    
    if not emails_data:
        print("\n⚠️  No email addresses found on the page.", file=sys.stderr)
        print("\nTip: The page might use JavaScript to load content, or emails might be obfuscated.", file=sys.stderr)
        print("Try saving the page HTML manually (View Source) and use --file option.", file=sys.stderr)
        sys.exit(1)
    
    print(f"✓ Found {len(emails_data)} unique email address(es)")
    
    # Save to CSV
    output_file = save_to_csv(emails_data, args.output)
    print(f"\n✓ Results saved to: {output_file}")
    
    # Display results
    print("\n" + "="*80)
    print("EXTRACTED EMAIL ADDRESSES")
    print("="*80)
    for i, item in enumerate(emails_data, 1):
        print(f"\n{i}. Email: {item['email']}")
        if item['context']:
            print(f"   Context: {item['context'][:150]}...")
    
    return 0


if __name__ == '__main__':
    sys.exit(main())
