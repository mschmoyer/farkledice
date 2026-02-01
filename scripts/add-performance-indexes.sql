-- Performance Indexes Migration
-- Run this on existing databases to add performance indexes
--
-- Local: docker exec farkle_db psql -U farkle_user -d farkle_db -f /var/www/html/scripts/add-performance-indexes.sql
-- Heroku: heroku pg:psql -a farkledice --file scripts/add-performance-indexes.sql

-- ============================================
-- farkle_players indexes
-- ============================================
-- Used for: friend list filtering, active player queries
CREATE INDEX IF NOT EXISTS idx_players_active ON farkle_players(active);
CREATE INDEX IF NOT EXISTS idx_players_lastplayed ON farkle_players(lastplayed);

-- Add is_bot column if it doesn't exist (for bot player filtering)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'farkle_players' AND column_name = 'is_bot') THEN
        ALTER TABLE farkle_players ADD COLUMN is_bot BOOLEAN DEFAULT false;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_players_is_bot ON farkle_players(is_bot);

-- ============================================
-- farkle_friends indexes
-- ============================================
-- Used for: GetActiveFriends queries that filter by sourceid
CREATE INDEX IF NOT EXISTS idx_friends_sourceid ON farkle_friends(sourceid);
CREATE INDEX IF NOT EXISTS idx_friends_sourceid_removed ON farkle_friends(sourceid, removed);

-- ============================================
-- farkle_games indexes
-- ============================================
-- Used for: leaderboard queries, game listing, activity filtering
CREATE INDEX IF NOT EXISTS idx_games_gamefinish ON farkle_games(gamefinish);
CREATE INDEX IF NOT EXISTS idx_games_gamewith ON farkle_games(gamewith);

-- Add last_activity index only if column exists (may not exist in older schemas)
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_name = 'farkle_games' AND column_name = 'last_activity') THEN
        CREATE INDEX IF NOT EXISTS idx_games_last_activity ON farkle_games(last_activity);
    END IF;
END $$;

-- ============================================
-- farkle_rounds indexes
-- ============================================
-- Used for: daily leaderboard queries, activity log
CREATE INDEX IF NOT EXISTS idx_rounds_rounddatetime ON farkle_rounds(rounddatetime);
CREATE INDEX IF NOT EXISTS idx_rounds_gameid_roundnum ON farkle_rounds(gameid, roundnum);

-- ============================================
-- farkle_sets indexes
-- ============================================
-- Used for: activity log JOIN optimization (dice data per round)
CREATE INDEX IF NOT EXISTS idx_sets_gameid_playerid_roundnum ON farkle_sets(gameid, playerid, roundnum);

-- ============================================
-- Verify indexes were created
-- ============================================
SELECT
    schemaname,
    tablename,
    indexname
FROM pg_indexes
WHERE schemaname = 'public'
AND indexname LIKE 'idx_%'
ORDER BY tablename, indexname;
