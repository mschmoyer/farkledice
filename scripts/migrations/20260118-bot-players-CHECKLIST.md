# Bot Players Schema Migration - Deployment Checklist

**Migration File:** `scripts/migrations/20260118-bot-players-schema.sql`
**Date:** 2026-01-18
**Purpose:** Add database support for bot players feature including ENUM types, new columns, bot state tracking table, and seeded bot accounts.

---

## Pre-Deployment Verification

### 1. Local Testing (COMPLETED)
- [x] Migration tested successfully against local Docker database
- [x] All schema changes verified:
  - ENUM type `bot_algorithm_type` created with values: easy, medium, hard
  - `farkle_players` table extended with `is_bot` (boolean) and `bot_algorithm` (ENUM)
  - `farkle_games` table extended with `bot_play_mode` (VARCHAR)
  - `farkle_bot_game_state` table created with all required columns
  - 9 bot accounts seeded (3 easy, 3 medium, 3 hard)
  - `siteinfo` entry added for bot fill throttling

### 2. Migration File Corrections
The following issues were identified and corrected during local testing:
- **Foreign key constraint:** Removed FK on `playerid` in `farkle_bot_game_state` due to composite PK in `farkle_players`
- **Column name mapping:** Updated to use `playertitle` and `playerlevel` instead of `title` and `level`
- **Data type compatibility:** Changed `active` column value from boolean to smallint (1 instead of TRUE)
- **Email requirement:** Added unique bot email addresses (bot1@farkledice.local through bot9@farkledice.local)
- **Idempotency implementation:** Replaced ON CONFLICT clause with conditional DO block checking email existence
- **Siteinfo paramid:** Changed from 5 to 7 to avoid conflict with existing parameter
- **Bot seeding:** Uses DO block with conditional INSERT/UPDATE logic for true idempotency (creates on first run, updates on subsequent runs)

### 3. Backup Recommendation
Before deploying to production, consider creating a database backup:
```bash
# Heroku backup
heroku pg:backups:capture -a farkledice
heroku pg:backups:download -a farkledice
```

---

## Deployment Instructions

### Local Docker Deployment

**Execute migration:**
```bash
docker exec -i farkle_db psql -U farkle_user -d farkle_db < scripts/migrations/20260118-bot-players-schema.sql
```

**Alternative method (copy then execute):**
```bash
docker cp scripts/migrations/20260118-bot-players-schema.sql farkle_db:/tmp/migration.sql
docker exec farkle_db psql -U farkle_user -d farkle_db -f /tmp/migration.sql
```

### Heroku Production Deployment

**Execute migration:**
```bash
cat scripts/migrations/20260118-bot-players-schema.sql | heroku pg:psql -a farkledice
```

**Expected output (first run):**
```
BEGIN
NOTICE:  Created ENUM type: bot_algorithm_type
NOTICE:  Added column: farkle_players.is_bot
NOTICE:  Added column: farkle_players.bot_algorithm
NOTICE:  Added column: farkle_games.bot_play_mode
CREATE INDEX
CREATE INDEX
CREATE TABLE
CREATE INDEX
NOTICE:  Created bot account: Byte
NOTICE:  Created bot account: Chip
NOTICE:  Created bot account: Beep
NOTICE:  Created bot account: Cyber
NOTICE:  Created bot account: Logic
NOTICE:  Created bot account: Binary
NOTICE:  Created bot account: Neural
NOTICE:  Created bot account: Quantum
NOTICE:  Created bot account: Apex
INSERT 0 1
COMMIT
```

**Expected output (subsequent runs - idempotent):**
```
BEGIN
NOTICE:  ENUM type bot_algorithm_type already exists, skipping
NOTICE:  Column farkle_players.is_bot already exists, skipping
NOTICE:  Column farkle_players.bot_algorithm already exists, skipping
NOTICE:  Column farkle_games.bot_play_mode already exists, skipping
NOTICE:  Updated bot account: Byte
NOTICE:  Updated bot account: Chip
NOTICE:  Updated bot account: Beep
NOTICE:  Updated bot account: Cyber
NOTICE:  Updated bot account: Logic
NOTICE:  Updated bot account: Binary
NOTICE:  Updated bot account: Neural
NOTICE:  Updated bot account: Quantum
NOTICE:  Updated bot account: Apex
COMMIT
```

---

## Post-Deployment Verification

### 1. Verify ENUM Type
```sql
SELECT enumlabel FROM pg_enum WHERE enumtypid = 'bot_algorithm_type'::regtype;
```
**Expected:** 3 rows (easy, medium, hard)

### 2. Verify farkle_players Columns
```sql
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'farkle_players'
  AND column_name IN ('is_bot', 'bot_algorithm');
```
**Expected:** 2 rows showing is_bot (boolean) and bot_algorithm (USER-DEFINED)

### 3. Verify farkle_games Column
```sql
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'farkle_games'
  AND column_name = 'bot_play_mode';
```
**Expected:** 1 row showing bot_play_mode (character varying)

### 4. Verify farkle_bot_game_state Table
```sql
SELECT table_name
FROM information_schema.tables
WHERE table_name = 'farkle_bot_game_state';
```
**Expected:** 1 row

**Detailed structure check:**
```sql
\d farkle_bot_game_state
```

### 5. Verify Bot Accounts
```sql
SELECT username, bot_algorithm, playertitle, playerlevel, password, random_selectable
FROM farkle_players
WHERE is_bot = TRUE
ORDER BY playerlevel, username;
```
**Expected:** 9 rows with:
- 3 easy bots (level 1): Beep, Byte, Chip
- 3 medium bots (level 5): Binary, Cyber, Logic
- 3 hard bots (level 10): Apex, Neural, Quantum
- All with password='LOCKED' and random_selectable=FALSE

### 6. Verify Siteinfo Entry
```sql
SELECT paramid, paramname, paramvalue
FROM siteinfo
WHERE paramname = 'last_bot_fill_check';
```
**Expected:** 1 row with paramid=7, paramvalue='0'

### 7. Verify Indexes
```sql
SELECT indexname, tablename
FROM pg_indexes
WHERE tablename IN ('farkle_players', 'farkle_bot_game_state')
  AND indexname LIKE '%bot%';
```
**Expected:** At least 3 indexes (idx_bot_players, idx_bot_algorithm, idx_bot_game_state)

---

## Rollback Instructions

If issues are encountered, the migration can be rolled back using the following SQL:

```sql
BEGIN;

-- Drop siteinfo entry
DELETE FROM siteinfo WHERE paramid = 7 AND paramname = 'last_bot_fill_check';

-- Drop bot accounts
DELETE FROM farkle_players WHERE is_bot = TRUE;

-- Drop bot game state table
DROP TABLE IF EXISTS farkle_bot_game_state;

-- Remove bot_play_mode column from farkle_games
ALTER TABLE farkle_games DROP COLUMN IF EXISTS bot_play_mode;

-- Remove columns from farkle_players
ALTER TABLE farkle_players DROP COLUMN IF EXISTS bot_algorithm;
ALTER TABLE farkle_players DROP COLUMN IF EXISTS is_bot;

-- Drop indexes
DROP INDEX IF EXISTS idx_bot_game_state;
DROP INDEX IF EXISTS idx_bot_algorithm;
DROP INDEX IF EXISTS idx_bot_players;

-- Drop ENUM type (only if no other dependencies exist)
DROP TYPE IF EXISTS bot_algorithm_type;

COMMIT;
```

**WARNING:** This rollback script will permanently delete all bot-related data. Use with caution.

---

## Migration Properties

- **Idempotent:** YES - Can be run multiple times safely
- **Backwards Compatible:** YES - Does not modify existing data
- **Requires Downtime:** NO
- **Data Loss Risk:** LOW (only adds new structures and data)
- **Estimated Duration:** < 5 seconds

---

## Notes

1. The migration is fully idempotent - it uses `IF NOT EXISTS` checks and conditional DO blocks
2. Bot accounts are identified by unique email addresses (bot1@farkledice.local through bot9@farkledice.local)
3. On first run, bot accounts are created; on subsequent runs, they are updated to ensure consistency
4. Bot accounts have LOCKED passwords and cannot be logged into
5. Bot accounts are excluded from random matchmaking (random_selectable = FALSE)
6. The foreign key constraint on playerid was intentionally removed due to the composite primary key structure of farkle_players
7. The siteinfo parameter uses paramid=7 (paramid 5 was already in use by last_tournament_round_check)
8. The migration was tested multiple times locally to ensure true idempotency

---

## Contact & Support

If issues arise during deployment:
1. Check the migration output for error messages
2. Verify pre-deployment requirements are met
3. Review rollback instructions if necessary
4. Test the rollback on local Docker environment first before applying to production
