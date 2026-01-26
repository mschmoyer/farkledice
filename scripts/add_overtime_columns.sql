-- Migration script to add overtime columns for 10-round tie breaking
-- Run: docker exec farkle_db psql -U farkle_user -d farkle_db -f /docker-entrypoint-initdb.d/add_overtime_columns.sql
-- Or: heroku pg:psql -a farkledice --file scripts/add_overtime_columns.sql

-- Add max_round column: tracks current last round (10 normally, 11-15 during overtime)
-- Default 10 for existing games, COALESCE in queries handles NULL
ALTER TABLE farkle_games ADD COLUMN IF NOT EXISTS max_round INTEGER DEFAULT 10;

-- Add is_overtime flag: indicates game is in overtime mode (for UI display)
ALTER TABLE farkle_games ADD COLUMN IF NOT EXISTS is_overtime BOOLEAN DEFAULT FALSE;

-- Update any NULL values in existing games to default
UPDATE farkle_games SET max_round = 10 WHERE max_round IS NULL;
UPDATE farkle_games SET is_overtime = FALSE WHERE is_overtime IS NULL;

-- Verify the columns were added
SELECT column_name, data_type, column_default
FROM information_schema.columns
WHERE table_name = 'farkle_games'
AND column_name IN ('max_round', 'is_overtime');
