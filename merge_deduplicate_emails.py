#!/usr/bin/env python3
"""
Merge and deduplicate email collections from multiple CSV files.
This ensures all collected emails are properly consolidated.
"""

import csv
import sys
from collections import OrderedDict


def read_emails_from_csv(csv_file):
    """Read emails from CSV file into a list of dictionaries."""
    try:
        with open(csv_file, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            rows = list(reader)
            print(f"  Read {len(rows)} rows from {csv_file}")
            return rows
    except FileNotFoundError:
        print(f"  ⚠️  {csv_file} not found")
        return []
    except Exception as e:
        print(f"  ⚠️  Error reading {csv_file}: {e}", file=sys.stderr)
        return []


def normalize_email(email):
    """Normalize email address for comparison."""
    if not email:
        return None
    return email.strip().lower()


def merge_contact_info(existing, new):
    """Merge contact information, preferring more complete data."""
    merged = existing.copy()
    
    # For each field, if new has data and existing doesn't, use new
    for key, value in new.items():
        if value and (not merged.get(key) or len(str(value)) > len(str(merged.get(key, '')))):
            merged[key] = value
    
    return merged


def deduplicate_emails(all_rows):
    """Deduplicate emails, keeping the most complete record for each."""
    # Use OrderedDict to preserve insertion order while deduplicating
    unique_emails = OrderedDict()
    duplicates = []
    
    for row in all_rows:
        email = normalize_email(row.get('email', ''))
        if not email:
            continue
        
        if email in unique_emails:
            # Merge information from duplicate
            duplicates.append(email)
            unique_emails[email] = merge_contact_info(unique_emails[email], row)
        else:
            unique_emails[email] = row
    
    return list(unique_emails.values()), len(duplicates)


def get_all_fieldnames(rows_list):
    """Get all unique fieldnames from multiple lists of rows."""
    all_fields = set()
    for rows in rows_list:
        for row in rows:
            all_fields.update(row.keys())
    
    # Define preferred order for common fields
    preferred_order = [
        'email', 'association_name', 'name', 'location', 'city', 
        'department', 'region', 'country', 'phone', 'website', 
        'type', 'language', 'specialization', 'notes', 'source'
    ]
    
    # Sort: preferred fields first, then alphabetically
    ordered_fields = []
    for field in preferred_order:
        if field in all_fields:
            ordered_fields.append(field)
            all_fields.remove(field)
    
    ordered_fields.extend(sorted(all_fields))
    return ordered_fields


def merge_and_deduplicate():
    """Main merge and deduplication function."""
    print("="*80)
    print("EMAIL MERGE & DEDUPLICATION TOOL")
    print("="*80)
    print()
    
    # Read all CSV files
    print("📖 Reading CSV files...")
    main_rows = read_emails_from_csv('trad_music_emails_expanded.csv')
    intl_rows = read_emails_from_csv('international_emails_collected.csv')
    assoc_rows = read_emails_from_csv('associations_accordeon_emails.csv')
    
    # Combine all rows
    all_rows = main_rows + intl_rows + assoc_rows
    print(f"\n  Total rows read: {len(all_rows)}")
    print()
    
    # Deduplicate
    print("🔍 Deduplicating emails...")
    unique_rows, num_duplicates = deduplicate_emails(all_rows)
    print(f"  Unique emails: {len(unique_rows)}")
    print(f"  Duplicates removed: {num_duplicates}")
    print()
    
    # Get all fieldnames
    fieldnames = get_all_fieldnames([main_rows, intl_rows, assoc_rows])
    
    # Write merged file
    output_file = 'all_emails_merged.csv'
    print(f"💾 Writing merged file to {output_file}...")
    
    with open(output_file, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
        writer.writeheader()
        writer.writerows(unique_rows)
    
    print(f"  ✅ Wrote {len(unique_rows)} unique emails")
    print()
    
    # Statistics
    print("📊 STATISTICS:")
    print(f"  Input files: 3")
    print(f"  Total rows: {len(all_rows)}")
    print(f"  Unique emails: {len(unique_rows)}")
    print(f"  Deduplication rate: {(num_duplicates/len(all_rows)*100):.1f}%")
    print()
    
    print("="*80)
    print(f"✅ Merged file created: {output_file}")
    print("="*80)
    
    return len(unique_rows)


def main():
    """Main function."""
    try:
        unique_count = merge_and_deduplicate()
        return 0
    except Exception as e:
        print(f"❌ Error: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return 1


if __name__ == '__main__':
    sys.exit(main())
