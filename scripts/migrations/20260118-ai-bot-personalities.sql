-- ================================================================
-- AI Bot Personalities Migration
-- ================================================================
-- This migration adds AI personality support to the bot system
-- Run this against both local Docker and Heroku databases
--
-- Usage (Local):
--   docker cp scripts/migrations/20260118-ai-bot-personalities.sql farkle_db:/tmp/migration.sql
--   docker exec farkle_db psql -U farkle_user -d farkle_db -f /tmp/migration.sql
--
-- Usage (Heroku):
--   cat scripts/migrations/20260118-ai-bot-personalities.sql | heroku pg:psql -a farkledice
--
-- This migration is idempotent and can be run multiple times safely.
-- ================================================================

BEGIN;

-- ================================================================
-- 1. Create Bot Personalities Table
-- ================================================================
-- Stores AI personality configurations for bots
-- Each bot can be linked to a personality that defines their behavior
-- and conversation style when using Claude AI

CREATE TABLE IF NOT EXISTS farkle_bot_personalities (
    personality_id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    difficulty VARCHAR(20) NOT NULL,  -- 'easy', 'medium', 'hard'
    personality_type VARCHAR(50) NOT NULL,  -- 'cautious', 'aggressive', 'chaotic', etc.

    -- Personality description for system prompt
    personality_prompt TEXT NOT NULL,
    play_style_tendencies TEXT NOT NULL,
    conversation_style TEXT NOT NULL,

    -- Risk tolerance modifiers (1-10 scale)
    risk_tolerance INTEGER DEFAULT 5 CHECK (risk_tolerance BETWEEN 1 AND 10),
    trash_talk_level INTEGER DEFAULT 5 CHECK (trash_talk_level BETWEEN 1 AND 10),

    created_at TIMESTAMP DEFAULT NOW(),
    is_active BOOLEAN DEFAULT true
);

-- Create indexes for efficient lookups
CREATE INDEX IF NOT EXISTS idx_bot_personalities_difficulty
    ON farkle_bot_personalities(difficulty);

CREATE INDEX IF NOT EXISTS idx_bot_personalities_active
    ON farkle_bot_personalities(is_active);

-- ================================================================
-- 2. Extend farkle_players Table
-- ================================================================
-- Add personality_id column to link bot players to personalities

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'farkle_players' AND column_name = 'personality_id'
    ) THEN
        ALTER TABLE farkle_players
        ADD COLUMN personality_id INTEGER REFERENCES farkle_bot_personalities(personality_id);
        RAISE NOTICE 'Added column: farkle_players.personality_id';
    ELSE
        RAISE NOTICE 'Column farkle_players.personality_id already exists, skipping';
    END IF;
END $$;

-- Create index for efficient personality lookups
CREATE INDEX IF NOT EXISTS idx_players_personality
    ON farkle_players(personality_id);

COMMIT;

-- ================================================================
-- Migration Complete
-- ================================================================
-- Verification queries:
--
-- Check new table:
--   \d farkle_bot_personalities
--
-- Check new column:
--   \d farkle_players
--
-- Verify personality_id column exists:
--   SELECT column_name, data_type, is_nullable
--   FROM information_schema.columns
--   WHERE table_name = 'farkle_players' AND column_name = 'personality_id';
--
-- Expected: personality_id | integer | YES
-- ================================================================
