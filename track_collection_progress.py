#!/usr/bin/env python3
"""
Track email collection progress and update status files.
This script helps manage the manual email collection process by:
- Counting current emails in the database
- Tracking progress against the target
- Updating checklist status
- Generating progress reports
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
            return len(emails), len(rows)
    except FileNotFoundError:
        return 0, 0
    except Exception as e:
        print(f"Error reading {csv_file}: {e}", file=sys.stderr)
        return 0, 0


def analyze_collection_progress():
    """Analyze current email collection progress."""
    print("="*80)
    print("EMAIL COLLECTION PROGRESS TRACKER")
    print("="*80)
    print(f"Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    # Count emails in each file
    main_unique, main_total = count_emails_in_csv('trad_music_emails_expanded.csv')
    intl_unique, intl_total = count_emails_in_csv('international_emails_collected.csv')
    assoc_unique, assoc_total = count_emails_in_csv('associations_accordeon_emails.csv')
    
    print(f"📊 EMAIL DATABASES:")
    print(f"  trad_music_emails_expanded.csv:    {main_unique:4d} unique emails ({main_total:4d} total rows)")
    print(f"  international_emails_collected.csv: {intl_unique:4d} unique emails ({intl_total:4d} total rows)")
    print(f"  associations_accordeon_emails.csv:  {assoc_unique:4d} unique emails ({assoc_total:4d} total rows)")
    print()
    
    # Count total unique across all files (approximation)
    total_collected = main_unique + intl_unique + assoc_unique
    print(f"📈 TOTAL COLLECTED (approximate): {total_collected} emails")
    print()
    
    # Calculate progress
    TARGET = 1000
    progress_pct = (total_collected / TARGET) * 100
    remaining = TARGET - total_collected
    
    print(f"🎯 TARGET: {TARGET} emails")
    print(f"✅ COLLECTED: {total_collected} emails ({progress_pct:.1f}%)")
    print(f"⏳ REMAINING: {remaining} emails ({100-progress_pct:.1f}%)")
    print()
    
    # Progress bar
    bar_width = 50
    filled = int(bar_width * total_collected / TARGET)
    bar = '█' * filled + '░' * (bar_width - filled)
    print(f"Progress: [{bar}] {progress_pct:.1f}%")
    print()
    
    # Estimate time to completion
    if remaining > 0:
        print(f"📋 NEXT STEPS:")
        print(f"  - Continue collection from checklists")
        print(f"  - Focus on HIGH priority sources first")
        print(f"  - Estimated time: {remaining * 2 / 60:.1f}-{remaining * 4 / 60:.1f} hours")
        print(f"    (assuming 2-4 minutes per email)")
    else:
        print("🎉 TARGET REACHED!")
    
    print()
    print("="*80)
    
    return total_collected, TARGET, remaining


def analyze_checklist(checklist_file):
    """Analyze checklist completion status."""
    try:
        with open(checklist_file, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            rows = list(reader)
            
        total = len(rows)
        completed = sum(1 for row in rows if row.get('status', '').upper() == 'DONE')
        todo = sum(1 for row in rows if row.get('status', '').upper() == 'TODO')
        in_progress = sum(1 for row in rows if row.get('status', '').upper() == 'IN_PROGRESS')
        
        high_priority = sum(1 for row in rows if row.get('priority', '').upper() == 'HIGH')
        high_todo = sum(1 for row in rows if row.get('priority', '').upper() == 'HIGH' 
                       and row.get('status', '').upper() == 'TODO')
        
        estimated_total = sum(int(row.get('estimated_emails', 0) or 0) for row in rows)
        collected_total = sum(int(row.get('collected', 0) or 0) for row in rows)
        
        print(f"📋 {checklist_file}:")
        print(f"  Total sources: {total}")
        print(f"  Completed: {completed} | In Progress: {in_progress} | TODO: {todo}")
        print(f"  High Priority TODO: {high_todo}/{high_priority}")
        print(f"  Estimated emails: {estimated_total} | Collected from checklist: {collected_total}")
        print()
        
        return {
            'total': total,
            'completed': completed,
            'todo': todo,
            'in_progress': in_progress,
            'high_priority': high_priority,
            'high_todo': high_todo,
            'estimated_total': estimated_total,
            'collected_total': collected_total
        }
    except FileNotFoundError:
        print(f"  ⚠️  {checklist_file} not found")
        return None
    except Exception as e:
        print(f"  ⚠️  Error reading {checklist_file}: {e}")
        return None


def get_next_high_priority_tasks(checklist_file, limit=5):
    """Get next high priority tasks to work on."""
    try:
        with open(checklist_file, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            rows = list(reader)
        
        # Filter high priority TODO items
        high_priority_todo = [
            row for row in rows 
            if row.get('priority', '').upper() == 'HIGH' 
            and row.get('status', '').upper() == 'TODO'
        ]
        
        if high_priority_todo:
            print(f"🔥 NEXT {min(limit, len(high_priority_todo))} HIGH PRIORITY TASKS from {checklist_file}:")
            for i, row in enumerate(high_priority_todo[:limit], 1):
                print(f"  {i}. {row.get('category', 'Unknown')} - Est. {row.get('estimated_emails', '?')} emails")
                print(f"     URL: {row.get('url', 'N/A')}")
                print(f"     Notes: {row.get('notes', 'N/A')}")
                print()
        else:
            print(f"  ✅ No high priority TODO items in {checklist_file}")
        
        return high_priority_todo
    except Exception as e:
        print(f"  ⚠️  Error reading {checklist_file}: {e}")
        return []


def main():
    """Main function."""
    # Analyze overall progress
    total_collected, target, remaining = analyze_collection_progress()
    
    # Analyze checklists
    print("\n📋 CHECKLIST ANALYSIS:")
    print("-" * 80)
    checklist1 = analyze_checklist('collection_checklist_1000.csv')
    checklist2 = analyze_checklist('international_checklist.csv')
    
    # Show next tasks
    print("\n🎯 RECOMMENDED NEXT ACTIONS:")
    print("-" * 80)
    get_next_high_priority_tasks('collection_checklist_1000.csv', limit=3)
    
    # Summary
    print("\n💡 TIPS FOR EFFICIENT COLLECTION:")
    print("-" * 80)
    print("  1. Start with HIGH priority sources (HelloAsso, AgendaTrad)")
    print("  2. Visit each URL in the checklist")
    print("  3. Browse association listings and click on individual profiles")
    print("  4. Extract email, name, location from each profile")
    print("  5. Add to trad_music_emails_expanded.csv")
    print("  6. Update checklist status and collected count")
    print("  7. Run this script regularly to track progress")
    print()
    
    return 0


if __name__ == '__main__':
    sys.exit(main())
