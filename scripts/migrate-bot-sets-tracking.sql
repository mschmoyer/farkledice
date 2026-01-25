-- Migration: Add setnum, handnum, roundnum to farkle_bot_game_state
-- Purpose: Track dice sets for bot turns to enable activity log display
-- Date: 2026-01-25

-- Add columns for tracking farkle_sets data during bot turns
ALTER TABLE farkle_bot_game_state
ADD COLUMN IF NOT EXISTS setnum INTEGER DEFAULT 0,
ADD COLUMN IF NOT EXISTS handnum INTEGER DEFAULT 1,
ADD COLUMN IF NOT EXISTS roundnum INTEGER DEFAULT 1;
