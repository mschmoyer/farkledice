-- ============================================================================
-- Challenge Mode Database Schema Migration
-- ============================================================================
-- This script creates all new tables and modifies existing tables for
-- Challenge Mode, a roguelike game mode where players face 20 sequential
-- AI bots, earn money to purchase special dice upgrades, and compete for
-- leaderboard rankings.
--
-- Tables Created:
--   1. farkle_challenge_dice_types - Master dice definitions
--   2. farkle_challenge_bot_lineup - 20 bot configurations
--   3. farkle_challenge_runs - Player challenge attempts
--   4. farkle_challenge_dice_inventory - Player's 6 dice per run
--   5. farkle_challenge_stats - Player challenge statistics
--
-- Tables Modified:
--   1. farkle_games - Add challenge mode columns
--   2. farkle_games_players - Add dice_inventory column
--
-- Prerequisites:
--   1. farkle_players.playerid must be unique (foreign key requirement)
--
-- This script is idempotent and can be run multiple times safely.
-- ============================================================================

-- ============================================================================
-- 0. PREREQUISITE: Ensure playerid has a UNIQUE constraint
-- ============================================================================
-- PostgreSQL requires referenced columns in foreign keys to have a UNIQUE or
-- PRIMARY KEY constraint. The farkle_players table has a composite primary key
-- on (playerid, email), so we need to add a UNIQUE constraint on playerid alone.

DO $$
BEGIN
    -- Check if playerid already has a unique constraint
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'farkle_players_playerid_unique'
          AND conrelid = 'farkle_players'::regclass
    ) THEN
        -- Add UNIQUE constraint on playerid
        -- NOTE: This will FAIL if there are duplicate playerid values in the table.
        -- Run scripts/fix-duplicate-playerids.sql first if this fails.
        ALTER TABLE farkle_players ADD CONSTRAINT farkle_players_playerid_unique UNIQUE (playerid);
        RAISE NOTICE 'Added UNIQUE constraint on farkle_players(playerid)';
    ELSE
        RAISE NOTICE 'UNIQUE constraint on farkle_players(playerid) already exists';
    END IF;
END $$;

-- ============================================================================
-- 1. CREATE TABLE: farkle_challenge_dice_types
-- ============================================================================
-- Master list of all special dice types available in challenge mode.
-- Includes standard die (id 1) and all special dice variants.

CREATE TABLE IF NOT EXISTS farkle_challenge_dice_types (
    dice_type_id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    price INTEGER NOT NULL,
    tier VARCHAR(20) NOT NULL, -- 'simple', 'better', 'amazing'
    effect_type VARCHAR(50) NOT NULL, -- 'multiplier', 'reroll', 'wild', 'protection', etc.
    effect_value TEXT, -- JSON for complex effects: {"multiplier": 2, "applies_to": [1,5]}
    rarity VARCHAR(20) DEFAULT 'common', -- 'common', 'rare', 'legendary' (future use)
    enabled BOOLEAN DEFAULT true,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_dice_types_tier ON farkle_challenge_dice_types(tier);
CREATE INDEX IF NOT EXISTS idx_dice_types_enabled ON farkle_challenge_dice_types(enabled);

-- ============================================================================
-- 2. CREATE TABLE: farkle_challenge_bot_lineup
-- ============================================================================
-- The fixed lineup of 20 bots for challenge mode.
-- Each bot has a personality, difficulty, special rules, and special dice.

CREATE TABLE IF NOT EXISTS farkle_challenge_bot_lineup (
    bot_number INTEGER PRIMARY KEY CHECK (bot_number >= 1 AND bot_number <= 20),
    personality_id INTEGER NOT NULL REFERENCES farkle_bot_personalities(personality_id),
    display_name VARCHAR(100) NOT NULL,
    point_target INTEGER DEFAULT 3000,
    special_rules TEXT, -- JSON: {"farkle_penalty": 500, "bonus_rolls": true, etc.}
    bot_dice_types TEXT, -- JSON array of dice_type_ids the bot has
    description TEXT -- Brief description of this bot's challenge
);

-- ============================================================================
-- 3. CREATE TABLE: farkle_challenge_runs
-- ============================================================================
-- Tracks active and completed challenge mode runs.
-- Each player can have only one active run at a time.

CREATE TABLE IF NOT EXISTS farkle_challenge_runs (
    run_id SERIAL PRIMARY KEY,
    player_id INTEGER NOT NULL REFERENCES farkle_players(playerid),
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'completed', 'abandoned'
    current_bot_number INTEGER NOT NULL DEFAULT 1, -- 1-20
    current_money INTEGER NOT NULL DEFAULT 0,
    furthest_bot_reached INTEGER NOT NULL DEFAULT 0,
    total_dice_saved INTEGER NOT NULL DEFAULT 0,
    total_games_played INTEGER NOT NULL DEFAULT 0,
    created_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_played TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_date TIMESTAMP
);

-- Create partial unique index to ensure only one active run per player
-- Note: PostgreSQL supports partial indexes for WHERE conditions
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'one_active_run_per_player'
    ) THEN
        CREATE UNIQUE INDEX one_active_run_per_player
        ON farkle_challenge_runs(player_id)
        WHERE status = 'active';
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_challenge_runs_player ON farkle_challenge_runs(player_id);
CREATE INDEX IF NOT EXISTS idx_challenge_runs_status ON farkle_challenge_runs(status);

-- ============================================================================
-- 4. CREATE TABLE: farkle_challenge_dice_inventory
-- ============================================================================
-- Tracks the 6 dice in player's current challenge run.
-- Each run has exactly 6 dice (slots 1-6).

CREATE TABLE IF NOT EXISTS farkle_challenge_dice_inventory (
    inventory_id SERIAL PRIMARY KEY,
    run_id INTEGER NOT NULL REFERENCES farkle_challenge_runs(run_id) ON DELETE CASCADE,
    dice_slot INTEGER NOT NULL CHECK (dice_slot >= 1 AND dice_slot <= 6),
    dice_type_id INTEGER NOT NULL REFERENCES farkle_challenge_dice_types(dice_type_id),

    CONSTRAINT unique_slot_per_run UNIQUE (run_id, dice_slot)
);

CREATE INDEX IF NOT EXISTS idx_challenge_inventory_run ON farkle_challenge_dice_inventory(run_id);

-- ============================================================================
-- 5. CREATE TABLE: farkle_challenge_stats
-- ============================================================================
-- Player statistics specific to challenge mode.
-- Tracks overall performance, achievements, and records.

CREATE TABLE IF NOT EXISTS farkle_challenge_stats (
    player_id INTEGER PRIMARY KEY REFERENCES farkle_players(playerid),
    total_runs INTEGER NOT NULL DEFAULT 0,
    completed_runs INTEGER NOT NULL DEFAULT 0,
    furthest_bot_reached INTEGER NOT NULL DEFAULT 0,
    total_dice_purchased INTEGER NOT NULL DEFAULT 0,
    total_money_earned INTEGER NOT NULL DEFAULT 0,
    total_money_spent INTEGER NOT NULL DEFAULT 0,
    favorite_dice_type_id INTEGER REFERENCES farkle_challenge_dice_types(dice_type_id),
    fastest_completion_time INTEGER, -- seconds
    last_run_date TIMESTAMP,

    -- Achievement tracking
    reached_bot_5 BOOLEAN DEFAULT false,
    reached_bot_10 BOOLEAN DEFAULT false,
    reached_bot_15 BOOLEAN DEFAULT false,
    reached_bot_20 BOOLEAN DEFAULT false,

    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_challenge_stats_furthest ON farkle_challenge_stats(furthest_bot_reached DESC);

-- ============================================================================
-- 6. ALTER TABLE: farkle_games
-- ============================================================================
-- Add challenge mode fields to existing games table.

DO $$
BEGIN
    -- Add is_challenge_game column if it doesn't exist
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_games' AND column_name = 'is_challenge_game'
    ) THEN
        ALTER TABLE farkle_games ADD COLUMN is_challenge_game BOOLEAN DEFAULT false;
    END IF;

    -- Add challenge_run_id column if it doesn't exist
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_games' AND column_name = 'challenge_run_id'
    ) THEN
        ALTER TABLE farkle_games ADD COLUMN challenge_run_id INTEGER REFERENCES farkle_challenge_runs(run_id);
    END IF;

    -- Add challenge_bot_number column if it doesn't exist
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_games' AND column_name = 'challenge_bot_number'
    ) THEN
        ALTER TABLE farkle_games ADD COLUMN challenge_bot_number INTEGER;
    END IF;
END $$;

-- Create index on challenge game fields
CREATE INDEX IF NOT EXISTS idx_games_challenge ON farkle_games(is_challenge_game, challenge_run_id);

-- ============================================================================
-- 7. ALTER TABLE: farkle_games_players
-- ============================================================================
-- Track dice used in challenge games.

DO $$
BEGIN
    -- Add dice_inventory column if it doesn't exist
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_games_players' AND column_name = 'dice_inventory'
    ) THEN
        ALTER TABLE farkle_games_players ADD COLUMN dice_inventory TEXT;
    END IF;
END $$;

-- Add comment explaining the dice_inventory format
COMMENT ON COLUMN farkle_games_players.dice_inventory IS 'JSON array of dice_type_ids for this player in challenge mode. Example: [1, 1, 1, 7, 12, 15] where 1 = standard die';

-- ============================================================================
-- SEED DATA: Standard Die
-- ============================================================================
-- Insert the standard die (the default die all players start with).
-- This must exist before any other dice or runs can be created.

INSERT INTO farkle_challenge_dice_types (name, description, price, tier, effect_type, effect_value)
VALUES ('Standard Die', 'A regular six-sided die with no special effects.', 0, 'simple', 'none', '{}')
ON CONFLICT (name) DO NOTHING;

-- ============================================================================
-- SEED DATA: Special Dice
-- ============================================================================
-- Insert 15 special dice types to populate the challenge mode shop.
-- These cover a variety of effects and tiers.

-- SIMPLE TIER DICE (Low cost, basic effects)
INSERT INTO farkle_challenge_dice_types (name, description, price, tier, effect_type, effect_value)
VALUES
    ('Lucky Die', 'Higher chance to roll 1s and 5s.', 5, 'simple', 'probability', '{"boost_faces": [1, 5], "boost_amount": 0.15}'),
    ('Farkle Shield', 'Protects you from your first farkle.', 3, 'simple', 'protection', '{"max_uses": 1}'),
    ('Penny Pincher', 'Earn $2 per die instead of $1.', 4, 'simple', 'money_bonus', '{"multiplier": 2}')
ON CONFLICT (name) DO NOTHING;

-- BETTER TIER DICE (Medium cost, stronger effects)
INSERT INTO farkle_challenge_dice_types (name, description, price, tier, effect_type, effect_value)
VALUES
    ('Double Die', 'Doubles the points scored by this die.', 8, 'better', 'multiplier', '{"multiplier": 2, "applies_to": "self"}'),
    ('Wild Die', 'Can be used as any number you choose.', 10, 'better', 'wild', '{"choosable": true}'),
    ('Straight Booster', 'Counts as a 6 for straight purposes only.', 7, 'better', 'straight_helper', '{"counts_as": 6}'),
    ('Reroll Token', 'Allows you to reroll this die once per turn.', 6, 'better', 'reroll', '{"max_uses_per_turn": 1}'),
    ('Triple Ones', 'Triples the value of three-of-a-kind 1s (300 to 900).', 9, 'better', 'multiplier', '{"multiplier": 3, "applies_to": "triple_ones"}')
ON CONFLICT (name) DO NOTHING;

-- AMAZING TIER DICE (High cost, powerful effects)
INSERT INTO farkle_challenge_dice_types (name, description, price, tier, effect_type, effect_value)
VALUES
    ('Golden Die', 'All points scored by this die are tripled.', 15, 'amazing', 'multiplier', '{"multiplier": 3, "applies_to": "self"}'),
    ('Farkle Immunity', 'You cannot farkle while this die is in play.', 20, 'amazing', 'protection', '{"max_uses": 999}'),
    ('Six Magnet', 'This die always rolls a 6.', 12, 'amazing', 'fixed_value', '{"value": 6}'),
    ('Bonus Roller', 'Gain an extra die to roll each turn (7 dice instead of 6).', 18, 'amazing', 'extra_die', '{"extra_dice": 1}'),
    ('Point Doubler', 'All points you score this turn are doubled.', 25, 'amazing', 'multiplier', '{"multiplier": 2, "applies_to": "all"}'),
    ('Money Printer', 'Earn $5 per die instead of $1.', 14, 'amazing', 'money_bonus', '{"multiplier": 5}'),
    ('Streak Keeper', 'If you score, you can choose to end your turn and keep all points.', 16, 'amazing', 'safe_bank', '{"auto_bank": true}')
ON CONFLICT (name) DO NOTHING;

-- ============================================================================
-- SEED DATA: Bot Lineup
-- ============================================================================
-- Insert the 20 bot lineup for challenge mode.
-- Difficulty increases as bot_number increases.
-- NOTE: personality_id values must exist in farkle_bot_personalities table.
-- Adjust personality_id values based on your actual data.

-- BOTS 1-5: Easy (Standard dice, low targets)
INSERT INTO farkle_challenge_bot_lineup (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
VALUES
    (1, 1, 'Byte the Beginner', 2000, '{"none": true}', '[1, 1, 1, 1, 1, 1]', 'A friendly bot to get you started. Easy target of 2000 points.'),
    (2, 2, 'Chip the Cautious', 2500, '{"none": true}', '[1, 1, 1, 1, 1, 1]', 'Plays it safe. Reach 2500 points to win.'),
    (3, 3, 'Beep the Bold', 2500, '{"aggressive": true}', '[1, 1, 1, 1, 1, 1]', 'Takes risks! Beat 2500 points.'),
    (4, 4, 'Widget the Wise', 2750, '{"none": true}', '[1, 1, 1, 1, 1, 1]', 'Experienced player. 2750 point target.'),
    (5, 5, 'Gadget the Greedy', 3000, '{"none": true}', '[1, 1, 1, 1, 1, 1]', 'Always wants more points. Defeat at 3000 points.')
ON CONFLICT (bot_number) DO NOTHING;

-- BOTS 6-10: Medium (Start getting special dice)
INSERT INTO farkle_challenge_bot_lineup (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
VALUES
    (6, 6, 'Circuit the Clever', 3000, '{"none": true}', '[1, 1, 1, 1, 2, 2]', 'Has 2 Lucky Dice! Reach 3000 points.'),
    (7, 7, 'Volt the Volatile', 3000, '{"farkle_penalty": 100}', '[1, 1, 1, 1, 1, 3]', 'Has a Farkle Shield. You lose 100 points when you farkle!'),
    (8, 8, 'Spark the Steady', 3250, '{"none": true}', '[1, 1, 1, 2, 2, 5]', 'Has Lucky Dice and Double Die. 3250 point target.'),
    (9, 9, 'Diode the Daring', 3250, '{"aggressive": true}', '[1, 1, 1, 1, 6, 6]', 'Has 2 Wild Dice! Beat 3250 points.'),
    (10, 10, 'Resistor the Relentless', 3500, '{"none": true}', '[1, 1, 2, 2, 5, 5]', 'Mixed special dice. 3500 point target.')
ON CONFLICT (bot_number) DO NOTHING;

-- BOTS 11-15: Hard (More special dice, higher targets)
INSERT INTO farkle_challenge_bot_lineup (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
VALUES
    (11, 11, 'Transistor the Tenacious', 3500, '{"farkle_penalty": 150}', '[1, 1, 5, 5, 6, 8]', 'Has powerful dice. You lose 150 points on farkle!'),
    (12, 12, 'Capacitor the Charged', 3500, '{"bot_point_bonus": 100}', '[1, 2, 2, 5, 6, 9]', 'Bot gets +100 bonus points per turn!'),
    (13, 13, 'Amplifier the Aggressive', 3750, '{"aggressive": true}', '[1, 5, 5, 5, 6, 10]', 'Loaded with special dice. 3750 point target.'),
    (14, 14, 'Oscillator the Opportunist', 3750, '{"none": true}', '[2, 2, 5, 6, 9, 10]', 'No standard dice! All special. 3750 points to win.'),
    (15, 15, 'Inductor the Intense', 4000, '{"farkle_penalty": 200}', '[1, 5, 6, 9, 10, 11]', 'Powerful loadout. You lose 200 points on farkle!')
ON CONFLICT (bot_number) DO NOTHING;

-- BOTS 16-19: Very Hard (Elite bots with amazing dice)
INSERT INTO farkle_challenge_bot_lineup (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
VALUES
    (16, 16, 'Processor the Powerful', 4000, '{"none": true}', '[5, 6, 9, 10, 11, 12]', 'All amazing-tier dice! 4000 point target.'),
    (17, 17, 'Kernel the Cunning', 4000, '{"farkle_penalty": 250, "bot_point_bonus": 150}', '[2, 5, 9, 10, 11, 13]', 'Elite bot. Farkle costs 250 points, bot gets +150 per turn!'),
    (18, 18, 'Algorithm the Adaptive', 4250, '{"none": true}', '[6, 9, 10, 11, 12, 13]', 'Adapts to your strategy. 4250 point target.'),
    (19, 19, 'Compiler the Calculated', 4250, '{"farkle_penalty": 300}', '[5, 6, 10, 11, 12, 14]', 'Calculated and precise. You lose 300 points on farkle!')
ON CONFLICT (bot_number) DO NOTHING;

-- BOT 20: BOSS (The ultimate challenge)
INSERT INTO farkle_challenge_bot_lineup (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
VALUES
    (20, 20, 'Prime the Champion', 4500, '{"farkle_penalty": 500, "bot_point_bonus": 200, "aggressive": true}', '[9, 10, 11, 12, 13, 15]', 'THE FINAL BOSS! All amazing dice, 4500 points, farkle costs 500 points, bot gets +200 per turn!')
ON CONFLICT (bot_number) DO NOTHING;

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================
-- All tables created and seeded successfully.
-- Run this script again anytime to ensure schema is up to date.
