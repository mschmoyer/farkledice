-- ================================================================
-- Bot Players Migration
-- ================================================================
-- This migration adds database support for bot players feature
-- Run this against both local Docker and Heroku databases
--
-- Usage (Local):
--   docker cp scripts/migrations/20260118-bot-players-schema.sql farkle_db:/tmp/migration.sql
--   docker exec farkle_db psql -U farkle_user -d farkle_db -f /tmp/migration.sql
--
-- Usage (Heroku):
--   cat scripts/migrations/20260118-bot-players-schema.sql | heroku pg:psql -a farkledice
--
-- This migration is idempotent and can be run multiple times safely.
-- ================================================================

BEGIN;

-- ================================================================
-- 1. Create ENUM Type for Bot Algorithms
-- ================================================================
-- Supports easy, medium, hard difficulty levels
-- Future algorithms can be added with: ALTER TYPE bot_algorithm_type ADD VALUE 'new_algo';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'bot_algorithm_type') THEN
        CREATE TYPE bot_algorithm_type AS ENUM ('easy', 'medium', 'hard');
        RAISE NOTICE 'Created ENUM type: bot_algorithm_type';
    ELSE
        RAISE NOTICE 'ENUM type bot_algorithm_type already exists, skipping';
    END IF;
END $$;

-- ================================================================
-- 2. Extend farkle_players Table
-- ================================================================
-- Add bot identification and algorithm columns
-- Bots are real player accounts that reuse all existing infrastructure

DO $$
BEGIN
    -- Add is_bot column if it doesn't exist
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_players' AND column_name = 'is_bot'
    ) THEN
        ALTER TABLE farkle_players ADD COLUMN is_bot BOOLEAN DEFAULT FALSE;
        RAISE NOTICE 'Added column: farkle_players.is_bot';
    ELSE
        RAISE NOTICE 'Column farkle_players.is_bot already exists, skipping';
    END IF;

    -- Add bot_algorithm column if it doesn't exist
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_players' AND column_name = 'bot_algorithm'
    ) THEN
        ALTER TABLE farkle_players ADD COLUMN bot_algorithm bot_algorithm_type DEFAULT NULL;
        RAISE NOTICE 'Added column: farkle_players.bot_algorithm';
    ELSE
        RAISE NOTICE 'Column farkle_players.bot_algorithm already exists, skipping';
    END IF;
END $$;

-- Create indexes for performance (partial indexes for bot queries)
CREATE INDEX IF NOT EXISTS idx_bot_players
    ON farkle_players(is_bot) WHERE is_bot = TRUE;

CREATE INDEX IF NOT EXISTS idx_bot_algorithm
    ON farkle_players(bot_algorithm) WHERE bot_algorithm IS NOT NULL;

-- ================================================================
-- 3. Extend farkle_games Table
-- ================================================================
-- Add bot play mode to track interactive vs instant bot behavior

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_games' AND column_name = 'bot_play_mode'
    ) THEN
        ALTER TABLE farkle_games ADD COLUMN bot_play_mode VARCHAR(20) DEFAULT NULL;
        RAISE NOTICE 'Added column: farkle_games.bot_play_mode';
    ELSE
        RAISE NOTICE 'Column farkle_games.bot_play_mode already exists, skipping';
    END IF;
END $$;

-- Values: 'interactive' (real-time visible turns) or 'instant' (complete all rounds immediately)

-- ================================================================
-- 4. Create Bot Turn State Table
-- ================================================================
-- Tracks bot turn progression for interactive play mode
-- Allows bots to make decisions step-by-step with visible UI updates

CREATE TABLE IF NOT EXISTS farkle_bot_game_state (
    stateid SERIAL PRIMARY KEY,
    gameid INTEGER NOT NULL,
    playerid INTEGER NOT NULL,           -- Reference to farkle_players (the bot)
    current_step VARCHAR(20),            -- 'rolling', 'choosing_keepers', 'deciding_roll', 'banking', 'farkled'
    dice_kept TEXT,                      -- JSON array of kept dice
    turn_score INTEGER DEFAULT 0,        -- Points accumulated this turn
    dice_remaining INTEGER DEFAULT 6,    -- Number of dice left to roll
    last_roll TEXT,                      -- JSON array of last dice roll
    last_message TEXT,                   -- Last bot message displayed to user
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_bot_game FOREIGN KEY (gameid)
        REFERENCES farkle_games(gameid) ON DELETE CASCADE,
    -- No foreign key on playerid due to composite PK (playerid, email) in farkle_players
    -- Application-level enforcement used instead
    CONSTRAINT unique_bot_game_player UNIQUE(gameid, playerid)
);

-- Index for efficient lookups by game and player
CREATE INDEX IF NOT EXISTS idx_bot_game_state
    ON farkle_bot_game_state(gameid, playerid);

-- ================================================================
-- 5. Seed Bot Player Accounts
-- ================================================================
-- Create 9 bot accounts (3 per difficulty level)
-- Bots have LOCKED passwords and are excluded from random matchmaking
-- Uses conditional logic for idempotency (checks email since username can have duplicates)

DO $$
DECLARE
    bot_data RECORD;
    bot_list TEXT[][] := ARRAY[
        ['Byte', 'bot1@farkledice.local', 'easy', 'the Rookie Bot', '1', '0'],
        ['Chip', 'bot2@farkledice.local', 'easy', 'the Friendly Bot', '1', '0'],
        ['Beep', 'bot3@farkledice.local', 'easy', 'the Learning Bot', '1', '0'],
        ['Cyber', 'bot4@farkledice.local', 'medium', 'the Tactical Bot', '5', '1000'],
        ['Logic', 'bot5@farkledice.local', 'medium', 'the Strategic Bot', '5', '1000'],
        ['Binary', 'bot6@farkledice.local', 'medium', 'the Calculated Bot', '5', '1000'],
        ['Neural', 'bot7@farkledice.local', 'hard', 'the Master Bot', '10', '5000'],
        ['Quantum', 'bot8@farkledice.local', 'hard', 'the Perfect Bot', '10', '5000'],
        ['Apex', 'bot9@farkledice.local', 'hard', 'the Supreme Bot', '10', '5000']
    ];
    bot_row TEXT[];
BEGIN
    FOREACH bot_row SLICE 1 IN ARRAY bot_list
    LOOP
        IF NOT EXISTS (SELECT 1 FROM farkle_players WHERE email = bot_row[2]) THEN
            INSERT INTO farkle_players (
                username, password, salt, email, is_bot, bot_algorithm,
                playertitle, playerlevel, xp, random_selectable, sendhourlyemails, active
            ) VALUES (
                bot_row[1], 'LOCKED', '', bot_row[2], TRUE, bot_row[3]::bot_algorithm_type,
                bot_row[4], bot_row[5]::INTEGER, bot_row[6]::INTEGER, FALSE, 0, 1
            );
            RAISE NOTICE 'Created bot account: %', bot_row[1];
        ELSE
            -- Update existing bot to ensure consistency
            UPDATE farkle_players SET
                username = bot_row[1],
                is_bot = TRUE,
                bot_algorithm = bot_row[3]::bot_algorithm_type,
                playertitle = bot_row[4],
                playerlevel = bot_row[5]::INTEGER,
                xp = bot_row[6]::INTEGER,
                password = 'LOCKED',
                random_selectable = FALSE,
                sendhourlyemails = 0,
                active = 1
            WHERE email = bot_row[2];
            RAISE NOTICE 'Updated bot account: %', bot_row[1];
        END IF;
    END LOOP;
END $$;

-- ================================================================
-- 6. Add Siteinfo Entry for Bot Fill Throttling
-- ================================================================
-- Used by cron jobs to throttle bot fill checks to every 5 minutes

INSERT INTO siteinfo (paramid, paramname, paramvalue)
VALUES (7, 'last_bot_fill_check', '0')
ON CONFLICT (paramid) DO NOTHING;

COMMIT;

-- ================================================================
-- Migration Complete
-- ================================================================
-- Verification queries:
--
-- Check ENUM type:
--   \dT+ bot_algorithm_type
--
-- Check new columns:
--   \d farkle_players
--   \d farkle_games
--   \d farkle_bot_game_state
--
-- Check bot accounts:
--   SELECT username, bot_algorithm, playertitle, playerlevel, password, random_selectable
--   FROM farkle_players WHERE is_bot = TRUE
--   ORDER BY playerlevel, username;
--
-- Expected: 9 rows (3 easy, 3 medium, 3 hard)
-- ================================================================
