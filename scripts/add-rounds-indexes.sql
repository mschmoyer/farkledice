-- Add indexes to farkle_rounds for stats query performance
-- These indexes dramatically improve performance of LeaderboardStats_ComputeAll()
-- which scans farkle_rounds filtering by date

-- Index for date-based queries (hot_dice, farkle_rate, comeback_king)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_rounds_datetime ON farkle_rounds(rounddatetime);

-- Composite index for date + playerid queries (common pattern in stats)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_rounds_datetime_playerid ON farkle_rounds(rounddatetime, playerid);

-- Verify indexes were created
SELECT indexname, indexdef FROM pg_indexes
WHERE schemaname = 'public' AND tablename = 'farkle_rounds'
ORDER BY indexname;
