#!/usr/bin/env python3
"""
Update collection status documents with current progress.
This script updates PATH_TO_1000_EMAILS.md with the latest progress.
"""

import csv
import sys
from datetime import datetime
from pathlib import Path


def count_emails_in_csv(csv_file):
    """Count unique emails in a CSV file."""
    try:
        with open(csv_file, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            emails = set()
            rows = list(reader)
            for row in rows:
                if 'email' in row and row['email']:
                    emails.add(row['email'].strip().lower())
            return len(emails), rows
    except FileNotFoundError:
        return 0, []
    except Exception as e:
        print(f"Error reading {csv_file}: {e}", file=sys.stderr)
        return 0, []


def analyze_by_region(rows):
    """Analyze email distribution by region."""
    regions = {}
    for row in rows:
        region = row.get('region', 'Unknown').strip()
        if region:
            regions[region] = regions.get(region, 0) + 1
    return regions


def analyze_by_type(rows):
    """Analyze email distribution by type."""
    types = {}
    for row in rows:
        type_val = row.get('type', 'Unknown').strip()
        if type_val:
            types[type_val] = types.get(type_val, 0) + 1
    return types


def analyze_by_country(rows):
    """Analyze email distribution by country."""
    countries = {}
    for row in rows:
        country = row.get('country', row.get('location', 'Unknown')).strip()
        if country:
            countries[country] = countries.get(country, 0) + 1
    return countries


def update_path_to_1000_emails(total_emails, remaining, progress_pct):
    """Update the PATH_TO_1000_EMAILS.md file with current progress."""
    try:
        with open('PATH_TO_1000_EMAILS.md', 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Update the status section
        today = datetime.now().strftime('%Y-%m-%d')
        
        # Replace the Current Status section
        import re
        
        # Pattern to match the Current Status section
        pattern = r'## Current Status\n\*\*Date:\*\* [^\n]+\n\*\*Collected:\*\* [^\n]+\n\*\*Target:\*\* [^\n]+\n\*\*Progress:\*\* [^\n]+\n\*\*Remaining:\*\* [^\n]+'
        
        replacement = f'''## Current Status
**Date:** {today}  
**Collected:** {total_emails} verified emails  
**Target:** 1000 emails  
**Progress:** {progress_pct:.1f}%  
**Remaining:** {remaining} emails'''
        
        new_content = re.sub(pattern, replacement, content)
        
        # If no match found, try a simpler pattern
        if new_content == content:
            pattern2 = r'## Current Status\n[^\n]+\n[^\n]+\n[^\n]+\n[^\n]+\n[^\n]+'
            new_content = re.sub(pattern2, replacement, content)
        
        # Write back
        with open('PATH_TO_1000_EMAILS.md', 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        print(f"✅ Updated PATH_TO_1000_EMAILS.md")
        return True
    except Exception as e:
        print(f"⚠️  Error updating PATH_TO_1000_EMAILS.md: {e}")
        return False


def generate_summary_report():
    """Generate a comprehensive summary report."""
    print("="*80)
    print("EMAIL COLLECTION STATUS UPDATE")
    print("="*80)
    print(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    # Count emails in each file
    main_unique, main_rows = count_emails_in_csv('trad_music_emails_expanded.csv')
    intl_unique, intl_rows = count_emails_in_csv('international_emails_collected.csv')
    assoc_unique, assoc_rows = count_emails_in_csv('associations_accordeon_emails.csv')
    
    total_collected = main_unique + intl_unique + assoc_unique
    TARGET = 1000
    progress_pct = (total_collected / TARGET) * 100
    remaining = TARGET - total_collected
    
    print(f"📊 COLLECTION STATUS:")
    print(f"  Main database (France):        {main_unique:4d} emails")
    print(f"  International database:        {intl_unique:4d} emails")
    print(f"  Associations database:         {assoc_unique:4d} emails")
    print(f"  ─────────────────────────────────────────")
    print(f"  TOTAL COLLECTED:               {total_collected:4d} emails")
    print(f"  TARGET:                        {TARGET:4d} emails")
    print(f"  PROGRESS:                      {progress_pct:5.1f}%")
    print(f"  REMAINING:                     {remaining:4d} emails")
    print()
    
    # Analyze distribution
    if main_rows:
        print("📍 FRENCH EMAILS BY REGION (top 10):")
        regions = analyze_by_region(main_rows)
        for region, count in sorted(regions.items(), key=lambda x: x[1], reverse=True)[:10]:
            print(f"  {region:30s} {count:3d} emails")
        print()
        
        print("📋 FRENCH EMAILS BY TYPE:")
        types = analyze_by_type(main_rows)
        for type_val, count in sorted(types.items(), key=lambda x: x[1], reverse=True):
            print(f"  {type_val:30s} {count:3d} emails")
        print()
    
    if intl_rows:
        print("🌍 INTERNATIONAL EMAILS BY COUNTRY:")
        countries = analyze_by_country(intl_rows)
        for country, count in sorted(countries.items(), key=lambda x: x[1], reverse=True):
            print(f"  {country:30s} {count:3d} emails")
        print()
    
    # Update the PATH_TO_1000_EMAILS.md file
    print("📝 UPDATING DOCUMENTATION:")
    update_path_to_1000_emails(total_collected, remaining, progress_pct)
    print()
    
    print("="*80)
    
    return total_collected, remaining, progress_pct


def main():
    """Main function."""
    total, remaining, progress = generate_summary_report()
    
    if remaining > 0:
        print("\n💡 NEXT STEPS:")
        print("  1. Run: python3 track_collection_progress.py")
        print("     → Shows next high-priority tasks to work on")
        print()
        print("  2. Continue manual collection from checklists")
        print("     → Focus on HelloAsso, AgendaTrad, teacher directories")
        print()
        print("  3. Update trad_music_emails_expanded.csv with new emails")
        print()
        print("  4. Run this script again to update progress")
        print()
    else:
        print("\n🎉 CONGRATULATIONS! Target of 1000 emails reached!")
        print()
    
    return 0


if __name__ == '__main__':
    sys.exit(main())
