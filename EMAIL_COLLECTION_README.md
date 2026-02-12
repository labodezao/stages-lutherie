# Email Collection Progress Tracker

## Current Status
- **Total Collected**: 477 unique emails (after deduplication)
- **Target**: 1000 emails
- **Progress**: 47.7%
- **Remaining**: 523 emails

## Quick Start

### 1. Interactive Collection Assistant (Recommended)
```bash
python3 collection_assistant.py
```
This provides an interactive menu with all collection tools in one place:
- Check current status
- View next tasks
- Mark tasks as complete
- Add emails to database
- Merge & deduplicate
- Update documentation

### 2. Check Current Progress
```bash
python3 track_collection_progress.py
```
This shows:
- Current email counts from all databases
- Progress toward 1000 email target
- Next high-priority tasks to work on

### 3. Update Status Documents
```bash
python3 update_collection_status.py
```
This:
- Analyzes email distribution by region, type, and country
- Updates PATH_TO_1000_EMAILS.md with current progress
- Shows breakdown of collected emails

### 4. Merge & Deduplicate Emails
```bash
python3 merge_deduplicate_emails.py
```
This:
- Combines emails from all CSV files
- Removes duplicates
- Creates all_emails_merged.csv with unique emails
- Shows deduplication statistics

### 5. Automated Batch Collection (if internet available)
```bash
python3 continue_collection.py
```
This attempts to:
- Automatically collect from high-priority sources
- Update checklists as sources are completed
- Continue until target is reached
- Note: Requires internet access to external domains

## Email Collection Workflow

### Step 1: Check What's Next
Run the progress tracker to see high-priority tasks:
```bash
python3 track_collection_progress.py
```

### Step 2: Visit Source URLs
The checklists contain URLs to visit:
- `collection_checklist_1000.csv` - French sources (27 URLs, est. 1024 emails)
- `international_checklist.csv` - International sources (78 URLs, est. 5170 emails)

### Step 3: Collect Emails Manually
For each URL in the checklist:
1. Visit the URL
2. Browse association/organization listings
3. Click on individual profiles
4. Extract:
   - Email address
   - Organization name
   - Location/region
   - Phone (if available)
   - Website (if available)
   - Type (Association, Teacher, Luthier, etc.)

### Step 4: Add to Database
Add collected emails to `trad_music_emails_expanded.csv`:
```csv
email,association_name,location,department,region,phone,website,type,notes
example@domain.com,Example Association,Paris,75,Île-de-France,01 23 45 67 89,https://example.com,Association,
```

### Step 5: Update Checklist
In the checklist CSV, update:
- `status`: Change from `TODO` to `IN_PROGRESS` or `DONE`
- `collected`: Update with number of emails collected

### Step 6: Track Progress
Run the merge script to get accurate count:
```bash
python3 merge_deduplicate_emails.py
python3 update_collection_status.py
```

## Priority Order

### High Priority (focus here first):
1. **HelloAsso regions** (est. 284 emails total)
   - Browse accordion associations by region
   - 12 regions to cover
   
2. **Teacher Directories** (est. 200 emails)
   - Superprof (80 emails)
   - ProfesseurParticulier (50 emails)
   - Others (70 emails)

3. **AgendaTrad Organizers** (est. 180 emails)
   - Folk music event organizers across France

### Medium Priority:
4. **Net1901 Directory** (est. 230 emails)
   - Search for accordion and folk music associations

5. **Luthiers & Shops** (est. 70 emails)
   - Diatonic accordion specialists

## Tools Reference

### collection_assistant.py (Interactive)
- Interactive menu-driven interface
- All collection tools in one place
- Mark tasks complete
- Update checklists
- Guide through collection process

### continue_collection.py (Automated)
- Automated batch collection from checklists
- Processes high-priority sources in sequence
- Updates checklists automatically
- Continues until target reached
- Usage: `python3 continue_collection.py [--target 1000] [--max-sources N]`
- Note: Requires internet access to external domains

### track_collection_progress.py
- Shows current status across all databases
- Lists next high-priority tasks
- Provides recommendations

### update_collection_status.py
- Generates comprehensive statistics
- Updates PATH_TO_1000_EMAILS.md
- Shows distribution by region/type/country

### merge_deduplicate_emails.py
- Combines all CSV files
- Removes duplicate emails
- Creates all_emails_merged.csv

### scrape_mass_contacts.py
- Automated web scraper (requires internet access)
- Can scrape HelloAsso, AgendaTrad, Chorotempo
- Usage: `python3 scrape_mass_contacts.py --help`
- Note: May not work in restricted environments

## Data Quality Guidelines

### Valid Emails:
✅ Professional/organization emails
✅ Association contact emails
✅ Public business emails
✅ info@, contact@, association@

### Avoid:
❌ Personal emails (unless public professional contact)
❌ Generic platform emails (admin@, webmaster@)
❌ No-reply addresses

### GDPR Compliance:
- Only collect publicly available contact information
- Focus on professional/organizational contacts
- No scraping of private data

## Files Overview

### Email Databases:
- `trad_music_emails_expanded.csv` - Main French database (296 unique)
- `international_emails_collected.csv` - International contacts (183 unique)
- `associations_accordeon_emails.csv` - Additional associations (9 unique after dedup)
- `all_emails_merged.csv` - Combined deduplicated database (477 unique)

### Checklists:
- `collection_checklist_1000.csv` - French sources checklist
- `international_checklist.csv` - International sources checklist

### Documentation:
- `PATH_TO_1000_EMAILS.md` - Strategy and milestones
- `COLLECTION_STRATEGY_1000.md` - Detailed collection strategy
- `GUIDE_COLLECTE_1000_EMAILS.md` - French collection guide
- `INTERNATIONAL_EXPANSION.md` - International expansion plan

## Progress Milestones

- ✅ 100 emails - DONE
- ✅ 250 emails - DONE
- ✅ 400 emails - DONE
- 🔄 500 emails - IN PROGRESS (477/500 - 95.4%)
- ⏳ 750 emails - PENDING
- ⏳ 1000 emails - PENDING (TARGET)

## Estimated Time to Completion

Based on current progress:
- **Remaining**: 523 emails
- **Estimated time**: 17-35 hours of collection work
- **Rate**: 2-4 minutes per email (browsing + extraction)

### Weekly Schedule:
- **Week 1** (10-12 hours): Complete HelloAsso + Teacher directories → ~700 emails total
- **Week 2** (8-10 hours): Complete AgendaTrad + Net1901 → ~950 emails total
- **Week 3** (4-6 hours): Complete remaining sources → 1000+ emails total

## Tips for Efficient Collection

1. **Batch Processing**: Process one source completely before moving to next
2. **Use Browser Tabs**: Open multiple profiles in tabs, extract in batches
3. **Copy-Paste Template**: Keep CSV format ready for quick data entry
4. **Regular Saves**: Save CSV after every 10-20 emails
5. **Track Progress**: Run progress scripts every 50-100 emails
6. **Focus on High Priority**: Complete high-priority sources first
7. **Verify Emails**: Quick format check before adding to database

## Troubleshooting

### Issue: Duplicate emails after adding new ones
**Solution**: Run `python3 merge_deduplicate_emails.py`

### Issue: Progress not updating in PATH_TO_1000_EMAILS.md
**Solution**: Run `python3 update_collection_status.py`

### Issue: Don't know what to work on next
**Solution**: Run `python3 track_collection_progress.py`

### Issue: CSV format errors
**Solution**: Ensure proper escaping of commas and quotes in CSV fields

## Contact & Support

For questions about the collection process, refer to:
- `GUIDE_COLLECTE_1000_EMAILS.md` - Detailed French guide
- `PATH_TO_1000_EMAILS.md` - Strategy document
- `COLLECTION_STRATEGY_1000.md` - Phase-by-phase plan

---

**Last Updated**: 2026-02-12  
**Status**: 477/1000 emails (47.7%)  
**Next Milestone**: 500 emails (23 emails away)
