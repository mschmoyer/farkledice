-- Migration script to add emoji reactions support
-- Run locally: docker exec farkle_db psql -U farkle_user -d farkle_db -f /dev/stdin < scripts/migrate-emoji-reactions.sql
-- Run on Heroku: heroku pg:psql -a farkledice --file scripts/migrate-emoji-reactions.sql

-- Add emoji_reactions column to farkle_players
-- Stores last ~40 emojis as a string (40 emojis x ~4 bytes each = ~160 bytes, 200 is safe)
ALTER TABLE farkle_players ADD COLUMN IF NOT EXISTS emoji_reactions VARCHAR(200) DEFAULT '';

-- Add emoji_given column to farkle_games_players
-- Tracks if player has already sent or skipped emoji for that game
ALTER TABLE farkle_games_players ADD COLUMN IF NOT EXISTS emoji_given BOOLEAN DEFAULT FALSE;

-- Add emoji_sent column to farkle_games_players
-- Stores the actual emoji that was sent (empty string if skipped)
ALTER TABLE farkle_games_players ADD COLUMN IF NOT EXISTS emoji_sent VARCHAR(10) DEFAULT '';

-- Update any NULL values in existing rows to defaults
UPDATE farkle_players SET emoji_reactions = '' WHERE emoji_reactions IS NULL;
UPDATE farkle_games_players SET emoji_given = FALSE WHERE emoji_given IS NULL;
UPDATE farkle_games_players SET emoji_sent = '' WHERE emoji_sent IS NULL;

-- Verify the columns were added
SELECT 'farkle_players.emoji_reactions' as added_column, column_name, data_type, column_default
FROM information_schema.columns
WHERE table_name = 'farkle_players'
AND column_name = 'emoji_reactions'
UNION ALL
SELECT 'farkle_games_players.emoji_given' as added_column, column_name, data_type, column_default
FROM information_schema.columns
WHERE table_name = 'farkle_games_players'
AND column_name = 'emoji_given'
UNION ALL
SELECT 'farkle_games_players.emoji_sent' as added_column, column_name, data_type, column_default
FROM information_schema.columns
WHERE table_name = 'farkle_games_players'
AND column_name = 'emoji_sent';
