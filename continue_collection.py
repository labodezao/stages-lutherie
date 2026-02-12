#!/usr/bin/env python3
"""
Automated Batch Collection Runner
Attempts to collect emails from all high-priority sources in sequence.
This script is designed to run autonomously to reach the 1000 email target.
"""

import csv
import sys
import time
from datetime import datetime


def load_checklist(filename):
    """Load checklist from CSV."""
    try:
        with open(filename, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            return list(reader), reader.fieldnames
    except Exception as e:
        print(f"❌ Error loading {filename}: {e}")
        return [], []


def save_checklist(filename, rows, fieldnames):
    """Save checklist to CSV."""
    try:
        with open(filename, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames)
            writer.writeheader()
            writer.writerows(rows)
        return True
    except Exception as e:
        print(f"❌ Error saving {filename}: {e}")
        return False


def collect_from_source(source_row):
    """
    Attempt to collect emails from a single source.
    Returns number of emails collected.
    
    In a real implementation with internet access, this would:
    1. Visit the URL
    2. Parse the page
    3. Extract contact information
    4. Validate emails
    5. Save to database
    """
    source = source_row.get('source', 'Unknown')
    category = source_row.get('category', 'Unknown')
    url = source_row.get('url', '')
    estimated = int(source_row.get('estimated_emails', 0) or 0)
    
    print(f"\n{'='*80}")
    print(f"📍 Source: {source} - {category}")
    print(f"🔗 URL: {url}")
    print(f"📊 Estimated emails: {estimated}")
    print(f"{'='*80}")
    
    # Attempt to collect (this would be actual web scraping in real implementation)
    print(f"\n⏳ Attempting to collect from {source}...")
    
    # Note: scrape_mass_contacts is an optional dependency used only for
    # automated collection features. Manual collection is always an option.
    try:
        # Try to import and use the scraper
        import scrape_mass_contacts
        
        # For HelloAsso sources
        if 'helloasso' in url.lower():
            print(f"  → Using HelloAsso scraper...")
            # Extract region from URL
            if '/reg/' in url:
                region = url.split('/reg/')[1].split('/')[0]
                category_name = url.split('/cat/')[1].split('/')[0] if '/cat/' in url else 'accordeon'
                
                scraper = scrape_mass_contacts.ContactScraper(delay=3)
                scraper.scrape_helloasso_category(category=category_name, regions=[region])
                
                collected = len(scraper.emails)
                if collected > 0:
                    # Save to temporary file
                    temp_file = f'temp_collected_{int(time.time())}.csv'
                    scraper.export_to_csv(temp_file)
                    print(f"  ✅ Collected {collected} emails")
                    return collected
        
        # For AgendaTrad
        elif 'agendatrad' in url.lower():
            print(f"  → Using AgendaTrad scraper...")
            scraper = scrape_mass_contacts.ContactScraper(delay=3)
            scraper.scrape_agendatrad_organizers()
            
            collected = len(scraper.emails)
            if collected > 0:
                temp_file = f'temp_collected_{int(time.time())}.csv'
                scraper.export_to_csv(temp_file)
                print(f"  ✅ Collected {collected} emails")
                return collected
        
        # For Chorotempo
        elif 'chorotempo' in url.lower():
            print(f"  → Using Chorotempo scraper...")
            scraper = scrape_mass_contacts.ContactScraper(delay=3)
            scraper.scrape_chorotempo_organizers()
            
            collected = len(scraper.emails)
            if collected > 0:
                temp_file = f'temp_collected_{int(time.time())}.csv'
                scraper.export_to_csv(temp_file)
                print(f"  ✅ Collected {collected} emails")
                return collected
        
        print(f"  ⚠️  Automated collection not available for this source")
        print(f"  💡 Manual collection required")
        return 0
        
    except ImportError:
        print(f"  ⚠️  Scraper module not available")
        return 0
    except Exception as e:
        print(f"  ❌ Collection failed: {e}")
        return 0


def run_batch_collection(target=1000, max_sources=None):
    """
    Run batch collection from checklist until target is reached.
    
    Args:
        target: Target number of total emails (default: 1000)
        max_sources: Maximum number of sources to process (None = all)
    """
    print("="*80)
    print("AUTOMATED BATCH COLLECTION")
    print("="*80)
    print(f"\n🎯 Target: {target} emails")
    print(f"🚀 Starting batch collection...")
    print(f"⏰ Started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    # Load checklist
    rows, fieldnames = load_checklist('collection_checklist_1000.csv')
    if not rows:
        print("❌ Could not load checklist")
        return 1
    
    # Count current emails
    from merge_deduplicate_emails import read_emails_from_csv, deduplicate_emails
    
    main_rows = read_emails_from_csv('trad_music_emails_expanded.csv')
    intl_rows = read_emails_from_csv('international_emails_collected.csv')
    assoc_rows = read_emails_from_csv('associations_accordeon_emails.csv')
    
    all_rows = main_rows + intl_rows + assoc_rows
    unique_rows, _ = deduplicate_emails(all_rows)
    
    current_count = len(unique_rows)
    print(f"📊 Current email count: {current_count}")
    print(f"📈 Progress: {(current_count/target*100):.1f}%")
    print(f"⏳ Remaining: {target - current_count}")
    print()
    
    if current_count >= target:
        print(f"🎉 Target already reached! ({current_count}/{target})")
        return 0
    
    # Filter high priority TODO tasks
    high_priority_todo = [
        (i, row) for i, row in enumerate(rows)
        if row.get('priority', '').upper() == 'HIGH'
        and row.get('status', '').upper() == 'TODO'
    ]
    
    print(f"📋 Found {len(high_priority_todo)} high-priority TODO sources")
    print()
    
    if not high_priority_todo:
        print("⚠️  No high-priority tasks remaining")
        print("💡 Consider:")
        print("   - Adding more sources to the checklist")
        print("   - Processing medium-priority sources")
        print("   - Expanding to international checklist")
        return 0
    
    # Process sources
    sources_to_process = high_priority_todo[:max_sources] if max_sources else high_priority_todo
    total_collected_this_run = 0
    sources_completed = 0
    
    for idx, (actual_idx, source_row) in enumerate(sources_to_process, 1):
        print(f"\n\n{'#'*80}")
        print(f"Processing source {idx}/{len(sources_to_process)}")
        print(f"{'#'*80}")
        
        # Attempt collection
        collected = collect_from_source(source_row)
        
        if collected > 0:
            # Update checklist
            rows[actual_idx]['status'] = 'DONE'
            rows[actual_idx]['collected'] = str(collected)
            sources_completed += 1
            total_collected_this_run += collected
            
            # Save checklist
            save_checklist('collection_checklist_1000.csv', rows, fieldnames)
            
            print(f"\n✅ Source completed: {collected} emails collected")
        else:
            # Mark as needing manual collection
            rows[actual_idx]['status'] = 'TODO'
            rows[actual_idx]['notes'] = rows[actual_idx].get('notes', '') + ' [Needs manual collection]'
            save_checklist('collection_checklist_1000.csv', rows, fieldnames)
            
            print(f"\n⏭️  Skipping (requires manual collection)")
        
        # Check if target reached
        current_count += collected
        print(f"\n📊 Current total: {current_count}/{target} ({(current_count/target*100):.1f}%)")
        
        if current_count >= target:
            print(f"\n🎉 TARGET REACHED! {current_count}/{target} emails")
            break
        
        # Small delay between sources
        time.sleep(1)
    
    # Final summary
    print("\n\n" + "="*80)
    print("BATCH COLLECTION SUMMARY")
    print("="*80)
    print(f"⏰ Completed at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"✅ Sources processed: {sources_completed}/{len(sources_to_process)}")
    print(f"📧 Emails collected this run: {total_collected_this_run}")
    print(f"📊 Total emails: {current_count}/{target}")
    print(f"📈 Progress: {(current_count/target*100):.1f}%")
    
    if current_count < target:
        remaining = target - current_count
        print(f"⏳ Still remaining: {remaining}")
        print(f"\n💡 Next steps:")
        print(f"   - Run this script again to process more sources")
        print(f"   - Or use manual collection for remaining sources")
        print(f"   - Run: python3 track_collection_progress.py for details")
    else:
        print(f"\n🎉 CONGRATULATIONS! Target of {target} emails reached!")
    
    print("="*80)
    
    return 0


def main():
    """Main function."""
    import argparse
    
    parser = argparse.ArgumentParser(
        description='Automated batch email collection'
    )
    parser.add_argument(
        '--target',
        type=int,
        default=1000,
        help='Target number of emails (default: 1000)'
    )
    parser.add_argument(
        '--max-sources',
        type=int,
        default=None,
        help='Maximum number of sources to process (default: all)'
    )
    
    args = parser.parse_args()
    
    try:
        return run_batch_collection(target=args.target, max_sources=args.max_sources)
    except KeyboardInterrupt:
        print("\n\n⚠️  Interrupted by user")
        print("Progress has been saved to checklist")
        return 1
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
        return 1


if __name__ == '__main__':
    sys.exit(main())
