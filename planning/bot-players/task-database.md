# Task: Database Schema for Bot Players

**Assignee:** Database/Backend Developer
**Estimated Time:** 1-2 days
**Dependencies:** None
**Status:** Ready to start

## Objective

Set up all database schema changes needed for bot player functionality, including player accounts, turn state tracking, and ENUM types.

## Database Changes Required

### 1. Create Bot Algorithm ENUM Type

```sql
-- Create ENUM type for bot algorithms (extensible for future algorithms)
CREATE TYPE bot_algorithm_type AS ENUM ('easy', 'medium', 'hard');
```

**Why ENUM:**
- Type safety at database level
- Easy to add new algorithms: `ALTER TYPE bot_algorithm_type ADD VALUE 'aggressive';`
- Self-documenting schema

---

### 2. Extend farkle_players Table

```sql
ALTER TABLE farkle_players
ADD COLUMN is_bot BOOLEAN DEFAULT FALSE,
ADD COLUMN bot_algorithm bot_algorithm_type DEFAULT NULL;

-- Indexes for performance
CREATE INDEX idx_bot_players ON farkle_players(is_bot) WHERE is_bot = TRUE;
CREATE INDEX idx_bot_algorithm ON farkle_players(bot_algorithm) WHERE bot_algorithm IS NOT NULL;
```

**Columns Added:**
- `is_bot` - Flag to identify bot accounts
- `bot_algorithm` - Which AI algorithm this bot uses

**Why:** Bots are real player accounts, reusing all existing infrastructure (XP, levels, stats, game history)

---

### 3. Extend farkle_games Table

```sql
ALTER TABLE farkle_games
ADD COLUMN bot_play_mode VARCHAR(20) DEFAULT NULL;

-- Values: 'interactive' (real-time visible turns) or 'instant' (complete all rounds immediately)
```

**Purpose:** Track whether bot should play interactively or instantly complete the game

---

### 4. Create Bot Turn State Table

```sql
CREATE TABLE farkle_bot_game_state (
    stateid SERIAL PRIMARY KEY,
    gameid INTEGER NOT NULL,
    playerid INTEGER NOT NULL,           -- Reference to farkle_players (the bot)
    current_step VARCHAR(20),            -- 'rolling', 'choosing_keepers', 'deciding_roll', 'banking', 'farkled'
    dice_kept TEXT,                      -- JSON array of kept dice
    turn_score INTEGER DEFAULT 0,
    dice_remaining INTEGER DEFAULT 6,
    last_roll TEXT,                      -- JSON array of last dice roll
    last_message TEXT,                   -- Last bot message displayed
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (gameid) REFERENCES farkle_games(gameid) ON DELETE CASCADE,
    FOREIGN KEY (playerid) REFERENCES farkle_players(playerid) ON DELETE CASCADE
);

CREATE INDEX idx_bot_game_state ON farkle_bot_game_state(gameid, playerid);
```

**Purpose:** Track bot turn progression for interactive play mode

**States:**
- `rolling` - Bot is rolling dice
- `choosing_keepers` - Bot is selecting which dice to keep
- `deciding_roll` - Bot is deciding to roll again or bank
- `banking` - Bot is saving score
- `farkled` - Bot rolled no scoring dice

---

### 5. Seed Bot Player Accounts

Create 9 bot accounts (3 per algorithm) with unique personalities:

```sql
-- Easy algorithm bots (fun, approachable names)
INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm,
                            title, level, xp, random_selectable, sendhourlyemails, active)
VALUES
    ('Byte', 'LOCKED', '', NULL, TRUE, 'easy', 'the Rookie Bot', 1, 0, 0, 0, TRUE),
    ('Chip', 'LOCKED', '', NULL, TRUE, 'easy', 'the Friendly Bot', 1, 0, 0, 0, TRUE),
    ('Beep', 'LOCKED', '', NULL, TRUE, 'easy', 'the Learning Bot', 1, 0, 0, 0, TRUE);

-- Medium algorithm bots (tech-themed names)
INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm,
                            title, level, xp, random_selectable, sendhourlyemails, active)
VALUES
    ('Cyber', 'LOCKED', '', NULL, TRUE, 'medium', 'the Tactical Bot', 5, 1000, 0, 0, TRUE),
    ('Logic', 'LOCKED', '', NULL, TRUE, 'medium', 'the Strategic Bot', 5, 1000, 0, 0, TRUE),
    ('Binary', 'LOCKED', '', NULL, TRUE, 'medium', 'the Calculated Bot', 5, 1000, 0, 0, TRUE);

-- Hard algorithm bots (advanced AI names)
INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm,
                            title, level, xp, random_selectable, sendhourlyemails, active)
VALUES
    ('Neural', 'LOCKED', '', NULL, TRUE, 'hard', 'the Master Bot', 10, 5000, 0, 0, TRUE),
    ('Quantum', 'LOCKED', '', NULL, TRUE, 'hard', 'the Perfect Bot', 10, 5000, 0, 0, TRUE),
    ('Apex', 'LOCKED', '', NULL, TRUE, 'hard', 'the Supreme Bot', 10, 5000, 0, 0, TRUE);
```

**Important Details:**
- `password = 'LOCKED'` - Prevents login attempts
- `random_selectable = 0` - Bots don't appear in random matchmaking pool
- `sendhourlyemails = 0` - No emails sent to bots
- `active = TRUE` - Accounts are active
- Starting levels: Easy (1), Medium (5), Hard (10) - creates illusion of experience

---

### 6. Update siteinfo Table for Timed Tasks

```sql
-- Add throttle entry for bot fill task
INSERT INTO siteinfo (paramid, paramname, paramvalue)
VALUES (5, 'last_bot_fill_check', '0')
ON CONFLICT (paramid) DO NOTHING;
```

**Purpose:** Throttle bot fill checks to run every 5 minutes

---

## Migration Script

Create: `docker/migrations/002_add_bot_players.sql`

```sql
-- Bot Players Migration
-- Run this against both local and production databases

-- 1. Create ENUM type
CREATE TYPE bot_algorithm_type AS ENUM ('easy', 'medium', 'hard');

-- 2. Extend farkle_players
ALTER TABLE farkle_players
ADD COLUMN IF NOT EXISTS is_bot BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS bot_algorithm bot_algorithm_type DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_bot_players ON farkle_players(is_bot) WHERE is_bot = TRUE;
CREATE INDEX IF NOT EXISTS idx_bot_algorithm ON farkle_players(bot_algorithm) WHERE bot_algorithm IS NOT NULL;

-- 3. Extend farkle_games
ALTER TABLE farkle_games
ADD COLUMN IF NOT EXISTS bot_play_mode VARCHAR(20) DEFAULT NULL;

-- 4. Create bot turn state table
CREATE TABLE IF NOT EXISTS farkle_bot_game_state (
    stateid SERIAL PRIMARY KEY,
    gameid INTEGER NOT NULL,
    playerid INTEGER NOT NULL,
    current_step VARCHAR(20),
    dice_kept TEXT,
    turn_score INTEGER DEFAULT 0,
    dice_remaining INTEGER DEFAULT 6,
    last_roll TEXT,
    last_message TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (gameid) REFERENCES farkle_games(gameid) ON DELETE CASCADE,
    FOREIGN KEY (playerid) REFERENCES farkle_players(playerid) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_bot_game_state ON farkle_bot_game_state(gameid, playerid);

-- 5. Seed bot accounts (with conflict handling for re-runs)
INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm, title, level, xp, random_selectable, sendhourlyemails, active)
VALUES
    ('Byte', 'LOCKED', '', NULL, TRUE, 'easy', 'the Rookie Bot', 1, 0, 0, 0, TRUE),
    ('Chip', 'LOCKED', '', NULL, TRUE, 'easy', 'the Friendly Bot', 1, 0, 0, 0, TRUE),
    ('Beep', 'LOCKED', '', NULL, TRUE, 'easy', 'the Learning Bot', 1, 0, 0, 0, TRUE),
    ('Cyber', 'LOCKED', '', NULL, TRUE, 'medium', 'the Tactical Bot', 5, 1000, 0, 0, TRUE),
    ('Logic', 'LOCKED', '', NULL, TRUE, 'medium', 'the Strategic Bot', 5, 1000, 0, 0, TRUE),
    ('Binary', 'LOCKED', '', NULL, TRUE, 'medium', 'the Calculated Bot', 5, 1000, 0, 0, TRUE),
    ('Neural', 'LOCKED', '', NULL, TRUE, 'hard', 'the Master Bot', 10, 5000, 0, 0, TRUE),
    ('Quantum', 'LOCKED', '', NULL, TRUE, 'hard', 'the Perfect Bot', 10, 5000, 0, 0, TRUE),
    ('Apex', 'LOCKED', '', NULL, TRUE, 'hard', 'the Supreme Bot', 10, 5000, 0, 0, TRUE)
ON CONFLICT (username) DO UPDATE SET
    is_bot = EXCLUDED.is_bot,
    bot_algorithm = EXCLUDED.bot_algorithm,
    title = EXCLUDED.title,
    level = EXCLUDED.level,
    xp = EXCLUDED.xp;

-- 6. Add siteinfo entry for bot fill throttling
INSERT INTO siteinfo (paramid, paramname, paramvalue)
VALUES (5, 'last_bot_fill_check', '0')
ON CONFLICT (paramid) DO NOTHING;
```

---

## Migration Execution Steps

### Step 1: Create Migration File

```bash
# Create migration file in docker/migrations/
cat > docker/migrations/002_add_bot_players.sql << 'EOF'
[... copy full migration script from above ...]
EOF
```

### Step 2: Test Locally in Docker

```bash
# 1. Copy migration into Docker container
docker cp docker/migrations/002_add_bot_players.sql farkle_db:/tmp/002_add_bot_players.sql

# 2. Run migration
docker exec farkle_db psql -U farkle_user -d farkle_db -f /tmp/002_add_bot_players.sql

# 3. Check for errors
echo $?  # Should be 0 for success
```

### Step 3: Verify Local Migration

```bash
# Run full verification checklist (see below)
```

### Step 4: Deploy to Heroku

```bash
# 1. Copy migration to Heroku using heroku run
cat docker/migrations/002_add_bot_players.sql | heroku pg:psql -a farkledice

# OR via file upload (if available)
heroku pg:psql -a farkledice < docker/migrations/002_add_bot_players.sql
```

### Step 5: Verify Heroku Migration

```bash
# Run verification queries against Heroku (see checklist below)
```

---

## Testing Checklist

### ✅ Local Docker Testing

```bash
# Run migration
docker exec farkle_db psql -U farkle_user -d farkle_db -f /tmp/002_add_bot_players.sql

# Verify ENUM type created
docker exec farkle_db psql -U farkle_user -d farkle_db -c "\dT+ bot_algorithm_type"

# Verify columns added
docker exec farkle_db psql -U farkle_user -d farkle_db -c "\d farkle_players"

# Verify bot accounts created
docker exec farkle_db psql -U farkle_user -d farkle_db -c "SELECT username, bot_algorithm, title, level FROM farkle_players WHERE is_bot = TRUE ORDER BY level, username;"

# Expected output: 9 bots (3 easy, 3 medium, 3 hard)

# Verify bot turn state table created
docker exec farkle_db psql -U farkle_user -d farkle_db -c "\d farkle_bot_game_state"

# Verify siteinfo entry
docker exec farkle_db psql -U farkle_user -d farkle_db -c "SELECT * FROM siteinfo WHERE paramid = 5;"
```

### ✅ Heroku Production Deployment

```bash
# Run migration on Heroku
cat docker/migrations/002_add_bot_players.sql | heroku pg:psql -a farkledice

# Verify deployment
heroku pg:psql -a farkledice -c "SELECT username, bot_algorithm, title FROM farkle_players WHERE is_bot = TRUE;"

# Expected: 9 rows (Byte, Chip, Beep, Cyber, Logic, Binary, Neural, Quantum, Apex)
```

---

## Pre-Deployment Checklist

Before running migration, verify:

- [ ] Migration file created: `docker/migrations/002_add_bot_players.sql`
- [ ] Migration script reviewed for syntax errors
- [ ] Migration uses `IF NOT EXISTS` and `ON CONFLICT` for idempotency
- [ ] Rollback script prepared and tested
- [ ] Local Docker database backed up (if needed)
- [ ] Heroku database backed up:
  ```bash
  heroku pg:backups:capture -a farkledice
  ```

## Post-Deployment Verification

### Local Verification Checklist

- [ ] ENUM type exists: `\dT+ bot_algorithm_type`
- [ ] Columns added to `farkle_players`:
  ```sql
  SELECT column_name, data_type
  FROM information_schema.columns
  WHERE table_name = 'farkle_players'
    AND column_name IN ('is_bot', 'bot_algorithm');
  ```
- [ ] Column added to `farkle_games`:
  ```sql
  SELECT column_name
  FROM information_schema.columns
  WHERE table_name = 'farkle_games'
    AND column_name = 'bot_play_mode';
  ```
- [ ] Table `farkle_bot_game_state` exists with all columns
- [ ] 9 bot accounts created with correct attributes:
  ```sql
  SELECT username, bot_algorithm, title, level, password, random_selectable
  FROM farkle_players
  WHERE is_bot = TRUE
  ORDER BY level, username;
  ```
  Expected rows:
  - Beep, Byte, Chip (easy, level 1)
  - Binary, Cyber, Logic (medium, level 5)
  - Apex, Neural, Quantum (hard, level 10)
  - All have password='LOCKED', random_selectable=0
- [ ] Indexes created:
  ```sql
  SELECT indexname FROM pg_indexes
  WHERE tablename = 'farkle_players'
    AND indexname IN ('idx_bot_players', 'idx_bot_algorithm');
  ```
- [ ] Siteinfo entry for bot fill (paramid=5) exists:
  ```sql
  SELECT * FROM siteinfo WHERE paramid = 5;
  ```

### Heroku Verification Checklist

Run same queries as above, prefixed with:
```bash
heroku pg:psql -a farkledice -c "QUERY_HERE"
```

- [ ] All local checks pass on Heroku
- [ ] No migration errors in Heroku logs:
  ```bash
  heroku logs --tail -a farkledice | grep ERROR
  ```
- [ ] Can query bot players:
  ```bash
  heroku pg:psql -a farkledice -c "SELECT COUNT(*) FROM farkle_players WHERE is_bot = TRUE;"
  # Expected: 9
  ```

---

## Troubleshooting

### If Migration Fails Locally

1. Check Docker logs:
   ```bash
   docker logs farkle_db
   ```

2. Check PostgreSQL error messages in migration output

3. Test migration in parts (run CREATE TYPE, then ALTER TABLE, etc.)

4. Verify database connection:
   ```bash
   docker exec farkle_db psql -U farkle_user -d farkle_db -c "SELECT version();"
   ```

### If Migration Fails on Heroku

1. Check Heroku logs:
   ```bash
   heroku logs --tail -a farkledice
   ```

2. Restore from backup:
   ```bash
   heroku pg:backups:restore -a farkledice
   ```

3. Run migration in psql session (for better error visibility):
   ```bash
   heroku pg:psql -a farkledice
   # Then paste migration SQL manually
   ```

### If Bot Accounts Not Created

Check for username conflicts:
```sql
SELECT username FROM farkle_players
WHERE username IN ('Byte', 'Chip', 'Beep', 'Cyber', 'Logic', 'Binary', 'Neural', 'Quantum', 'Apex');
```

If conflicts exist, use the `ON CONFLICT` clause in the migration (already included).

---

## Rollback Script

In case we need to revert:

```sql
-- Rollback: Remove bot players feature

-- 1. Delete bot accounts
DELETE FROM farkle_players WHERE is_bot = TRUE;

-- 2. Drop bot turn state table
DROP TABLE IF EXISTS farkle_bot_game_state;

-- 3. Remove columns from farkle_games
ALTER TABLE farkle_games DROP COLUMN IF EXISTS bot_play_mode;

-- 4. Remove columns from farkle_players
ALTER TABLE farkle_players DROP COLUMN IF EXISTS bot_algorithm;
ALTER TABLE farkle_players DROP COLUMN IF EXISTS is_bot;

-- 5. Drop ENUM type (must not be in use)
DROP TYPE IF EXISTS bot_algorithm_type;

-- 6. Remove siteinfo entry
DELETE FROM siteinfo WHERE paramid = 5;
```

---

## Acceptance Criteria

### Migration File
- [ ] File exists: `docker/migrations/002_add_bot_players.sql`
- [ ] Migration is idempotent (can run multiple times safely)
- [ ] Rollback script prepared

### Local Environment
- [ ] Migration executed successfully on local Docker
- [ ] ENUM type `bot_algorithm_type` exists with values: easy, medium, hard
- [ ] `farkle_players` has `is_bot` and `bot_algorithm` columns
- [ ] `farkle_games` has `bot_play_mode` column
- [ ] `farkle_bot_game_state` table exists with all required columns
- [ ] 9 bot player accounts exist with correct names, titles, and levels
- [ ] All bot accounts have `password='LOCKED'` and `random_selectable=0`
- [ ] Indexes created for performance
- [ ] `siteinfo` has paramid=5 for bot fill throttling
- [ ] All local verification queries pass

### Heroku Production
- [ ] Heroku database backed up before migration
- [ ] Migration executed successfully on Heroku
- [ ] All Heroku verification queries pass
- [ ] No errors in Heroku logs
- [ ] Can SELECT bot players from Heroku database
- [ ] Rollback script tested (optional, in dev environment)

---

## Notes

- **No breaking changes:** All columns use `DEFAULT` or `NULL`, so existing code continues to work
- **Idempotent:** Migration can be run multiple times safely (uses `IF NOT EXISTS`, `ON CONFLICT`)
- **Bot accounts are first-class players:** They level up, accumulate stats, and appear in game history
- **Future extensibility:** Easy to add new bot algorithms by adding ENUM values
