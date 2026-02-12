#!/usr/bin/env python3
"""
Interactive Collection Assistant - Guides user through email collection process.
This script provides an interactive interface for managing the email collection workflow.
"""

import csv
import sys
from datetime import datetime


def show_menu():
    """Display main menu."""
    print("\n" + "="*80)
    print("EMAIL COLLECTION ASSISTANT")
    print("="*80)
    print("\n📋 MAIN MENU:\n")
    print("  1. Show Current Status")
    print("  2. Show Next Tasks (High Priority)")
    print("  3. Mark Task as Complete")
    print("  4. Add Collected Emails")
    print("  5. Merge & Deduplicate All Databases")
    print("  6. Update Documentation")
    print("  7. Generate Progress Report")
    print("  8. Exit")
    print("\n" + "="*80)


def show_current_status():
    """Show current collection status."""
    import subprocess
    subprocess.run(['python3', 'track_collection_progress.py'])


def show_next_tasks():
    """Show next high priority tasks."""
    try:
        with open('collection_checklist_1000.csv', 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            rows = list(reader)
        
        high_priority_todo = [
            row for row in rows 
            if row.get('priority', '').upper() == 'HIGH' 
            and row.get('status', '').upper() == 'TODO'
        ]
        
        if not high_priority_todo:
            print("\n✅ No high priority tasks remaining!")
            return
        
        print("\n" + "="*80)
        print("NEXT HIGH PRIORITY TASKS")
        print("="*80)
        
        for i, row in enumerate(high_priority_todo[:5], 1):
            print(f"\n{i}. {row.get('category', 'Unknown')}")
            print(f"   Source: {row.get('source', 'Unknown')}")
            print(f"   Estimated emails: {row.get('estimated_emails', '?')}")
            print(f"   URL: {row.get('url', 'N/A')}")
            print(f"   Notes: {row.get('notes', 'N/A')}")
        
        print("\n" + "="*80)
    except Exception as e:
        print(f"❌ Error: {e}")


def mark_task_complete():
    """Mark a task as complete and update collected count."""
    try:
        # Read checklist
        with open('collection_checklist_1000.csv', 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            rows = list(reader)
        
        # Show tasks
        print("\n" + "="*80)
        print("SELECT TASK TO MARK AS COMPLETE")
        print("="*80)
        
        todo_tasks = [
            (i, row) for i, row in enumerate(rows)
            if row.get('status', '').upper() == 'TODO'
        ]
        
        if not todo_tasks:
            print("\n✅ No TODO tasks remaining!")
            return
        
        for display_idx, (actual_idx, row) in enumerate(todo_tasks[:10], 1):
            print(f"{display_idx}. {row.get('category', 'Unknown')} - Est. {row.get('estimated_emails', '?')} emails")
        
        print("\n0. Cancel")
        
        # Get user input
        choice = input("\nSelect task number (0 to cancel): ").strip()
        if choice == '0':
            return
        
        try:
            task_num = int(choice) - 1
            if task_num < 0 or task_num >= len(todo_tasks):
                print("❌ Invalid selection")
                return
            
            actual_idx, selected_task = todo_tasks[task_num]
            
            # Get collected count
            collected = input(f"\nHow many emails were collected from this source? ").strip()
            collected = int(collected) if collected else 0
            
            # Update task
            rows[actual_idx]['status'] = 'DONE'
            rows[actual_idx]['collected'] = str(collected)
            
            # Write back
            with open('collection_checklist_1000.csv', 'w', newline='', encoding='utf-8') as f:
                writer = csv.DictWriter(f, fieldnames=reader.fieldnames)
                writer.writeheader()
                writer.writerows(rows)
            
            print(f"\n✅ Task marked as DONE with {collected} emails collected!")
            
        except ValueError:
            print("❌ Invalid input")
            
    except Exception as e:
        print(f"❌ Error: {e}")


def add_collected_emails():
    """Guide user to add collected emails."""
    print("\n" + "="*80)
    print("ADD COLLECTED EMAILS")
    print("="*80)
    print("\n📝 To add emails to the database:\n")
    print("1. Open trad_music_emails_expanded.csv in a spreadsheet editor")
    print("2. Add new rows with the following columns:")
    print("   - email (required)")
    print("   - association_name (required)")
    print("   - location (city)")
    print("   - department (e.g., 44, 75)")
    print("   - region (e.g., Bretagne, Île-de-France)")
    print("   - phone")
    print("   - website")
    print("   - type (Association, Teacher, Luthier, etc.)")
    print("   - notes")
    print("\n3. Save the file")
    print("4. Run option 5 (Merge & Deduplicate) to update the database")
    print("\n💡 TIP: You can also add to international_emails_collected.csv")
    print("   for international contacts")
    print("\n" + "="*80)
    input("\nPress Enter to continue...")


def merge_and_deduplicate():
    """Run merge and deduplication."""
    import subprocess
    subprocess.run(['python3', 'merge_deduplicate_emails.py'])


def update_documentation():
    """Update all documentation."""
    import subprocess
    subprocess.run(['python3', 'update_collection_status.py'])


def generate_report():
    """Generate comprehensive progress report."""
    print("\n" + "="*80)
    print("COMPREHENSIVE PROGRESS REPORT")
    print("="*80)
    print(f"\nGenerated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
    
    import subprocess
    subprocess.run(['python3', 'track_collection_progress.py'])
    
    print("\n" + "="*80)
    input("\nPress Enter to continue...")


def main():
    """Main interactive loop."""
    while True:
        show_menu()
        choice = input("\nSelect option (1-8): ").strip()
        
        if choice == '1':
            show_current_status()
        elif choice == '2':
            show_next_tasks()
        elif choice == '3':
            mark_task_complete()
        elif choice == '4':
            add_collected_emails()
        elif choice == '5':
            merge_and_deduplicate()
        elif choice == '6':
            update_documentation()
        elif choice == '7':
            generate_report()
        elif choice == '8':
            print("\n👋 Goodbye! Keep collecting emails!")
            return 0
        else:
            print("\n❌ Invalid option. Please select 1-8.")
        
        input("\nPress Enter to continue...")


if __name__ == '__main__':
    try:
        sys.exit(main())
    except KeyboardInterrupt:
        print("\n\n👋 Interrupted. Goodbye!")
        sys.exit(0)
