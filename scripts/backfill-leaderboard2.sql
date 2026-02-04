-- Leaderboard 2.0 Backfill Script
-- Run: heroku pg:psql -a farkledice --file scripts/backfill-leaderboard2.sql
-- Populates leaderboard tables from ALL historical game data
-- Safe to run multiple times (clears and repopulates)
-- Expected runtime: a few seconds

BEGIN;

-- ============================================================
-- Step 1: Clear existing leaderboard data (fresh backfill)
-- ============================================================
TRUNCATE farkle_lb_daily_games, farkle_lb_daily_scores, farkle_lb_weekly_scores, farkle_lb_alltime, farkle_lb_stats;

-- ============================================================
-- Step 2: Insert daily games from historical game data
-- Only eligible games: finished, non-solo, non-bot, score > 0
-- Uses Central Time for date assignment
-- ============================================================
INSERT INTO farkle_lb_daily_games (playerid, gameid, lb_date, game_seq, game_score, counted)
SELECT
    sub.playerid,
    sub.gameid,
    sub.lb_date,
    sub.game_seq,
    sub.playerscore,
    FALSE
FROM (
    SELECT
        gp.playerid,
        g.gameid,
        (g.gamefinish AT TIME ZONE 'America/Chicago')::DATE as lb_date,
        gp.playerscore,
        ROW_NUMBER() OVER (
            PARTITION BY gp.playerid, (g.gamefinish AT TIME ZONE 'America/Chicago')::DATE
            ORDER BY g.gamefinish
        ) as game_seq
    FROM farkle_games g
    JOIN farkle_games_players gp ON g.gameid = gp.gameid
    JOIN farkle_players p ON gp.playerid = p.playerid
    WHERE g.winningplayer > 0
      AND g.gamefinish IS NOT NULL
      -- Include all historical games
      AND g.gamewith != 2          -- exclude solo
      AND p.is_bot = false         -- exclude bots
      AND gp.playerscore > 0      -- must have scored
      AND NOT EXISTS (             -- exclude games with bot opponents
          SELECT 1 FROM farkle_games_players gp2
          JOIN farkle_players p2 ON gp2.playerid = p2.playerid
          WHERE gp2.gameid = g.gameid AND p2.is_bot = true
      )
) sub
WHERE sub.game_seq <= 20  -- only first 20 games per day
ON CONFLICT DO NOTHING;

-- ============================================================
-- Step 3: Mark top 10 games per player-day as counted
-- ============================================================
UPDATE farkle_lb_daily_games g
SET counted = TRUE
FROM (
    SELECT id FROM (
        SELECT id,
               ROW_NUMBER() OVER (
                   PARTITION BY playerid, lb_date
                   ORDER BY game_score DESC
               ) as score_rank
        FROM farkle_lb_daily_games
        WHERE game_seq <= 20
    ) ranked
    WHERE score_rank <= 10
) top
WHERE g.id = top.id;

-- ============================================================
-- Step 4: Compute daily scores
-- ============================================================
INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
SELECT
    playerid,
    lb_date,
    COUNT(*) FILTER (WHERE game_seq <= 20) as games_played,
    COALESCE(SUM(game_score) FILTER (WHERE counted = TRUE), 0) as top10_score,
    COUNT(*) FILTER (WHERE game_seq <= 20) >= 3 as qualifies
FROM farkle_lb_daily_games
GROUP BY playerid, lb_date
ON CONFLICT (playerid, lb_date) DO UPDATE
SET games_played = EXCLUDED.games_played,
    top10_score = EXCLUDED.top10_score,
    qualifies = EXCLUDED.qualifies;

-- Rank daily scores per date
WITH ranked AS (
    SELECT playerid, lb_date,
           ROW_NUMBER() OVER (PARTITION BY lb_date ORDER BY top10_score DESC) as new_rank
    FROM farkle_lb_daily_scores
    WHERE qualifies = TRUE
)
UPDATE farkle_lb_daily_scores ds
SET rank = r.new_rank,
    prev_rank = NULL  -- no previous rank for backfill
FROM ranked r
WHERE ds.playerid = r.playerid AND ds.lb_date = r.lb_date;

-- ============================================================
-- Step 5: Compute weekly scores (best 5 of 7 daily scores)
-- ============================================================
WITH daily_with_week AS (
    SELECT playerid, lb_date, top10_score,
           date_trunc('week', lb_date)::DATE as week_start,
           ROW_NUMBER() OVER (
               PARTITION BY playerid, date_trunc('week', lb_date)
               ORDER BY top10_score DESC
           ) as day_rank
    FROM farkle_lb_daily_scores
    WHERE qualifies = TRUE
),
weekly_agg AS (
    SELECT playerid, week_start,
           COUNT(*) as daily_scores_used,
           SUM(top10_score) as top5_score
    FROM daily_with_week
    WHERE day_rank <= 5
    GROUP BY playerid, week_start
)
INSERT INTO farkle_lb_weekly_scores (playerid, week_start, daily_scores_used, top5_score, qualifies)
SELECT playerid, week_start, daily_scores_used, top5_score, daily_scores_used >= 3
FROM weekly_agg
ON CONFLICT (playerid, week_start) DO UPDATE
SET daily_scores_used = EXCLUDED.daily_scores_used,
    top5_score = EXCLUDED.top5_score,
    qualifies = EXCLUDED.qualifies;

-- Rank weekly scores
WITH ranked AS (
    SELECT playerid, week_start,
           ROW_NUMBER() OVER (PARTITION BY week_start ORDER BY top5_score DESC) as new_rank
    FROM farkle_lb_weekly_scores
    WHERE qualifies = TRUE
)
UPDATE farkle_lb_weekly_scores ws
SET rank = r.new_rank,
    prev_rank = NULL
FROM ranked r
WHERE ws.playerid = r.playerid AND ws.week_start = r.week_start;

-- ============================================================
-- Step 6: Compute all-time scores (career avg daily score)
-- ============================================================
WITH career AS (
    SELECT playerid,
           COUNT(*) as qualifying_days,
           SUM(top10_score) as total_daily_score,
           ROUND(AVG(top10_score), 2) as avg_daily_score,
           MAX(top10_score) as best_day_score
    FROM farkle_lb_daily_scores
    WHERE qualifies = TRUE
    GROUP BY playerid
)
INSERT INTO farkle_lb_alltime (playerid, qualifying_days, total_daily_score, avg_daily_score, best_day_score, qualifies, last_updated)
SELECT playerid, qualifying_days, total_daily_score, avg_daily_score, best_day_score,
       qualifying_days >= 10, NOW()
FROM career
ON CONFLICT (playerid) DO UPDATE
SET qualifying_days = EXCLUDED.qualifying_days,
    total_daily_score = EXCLUDED.total_daily_score,
    avg_daily_score = EXCLUDED.avg_daily_score,
    best_day_score = EXCLUDED.best_day_score,
    qualifies = EXCLUDED.qualifies,
    last_updated = NOW();

-- Rank all-time
WITH ranked AS (
    SELECT playerid,
           ROW_NUMBER() OVER (ORDER BY avg_daily_score DESC) as new_rank
    FROM farkle_lb_alltime
    WHERE qualifies = TRUE
)
UPDATE farkle_lb_alltime a
SET rank = r.new_rank,
    prev_rank = NULL
FROM ranked r
WHERE a.playerid = r.playerid;

-- ============================================================
-- Step 7: Compute win streaks from recent game history
-- ============================================================
-- Reset all streaks first
UPDATE farkle_players SET current_win_streak = 0, best_win_streak = 0
WHERE current_win_streak != 0 OR best_win_streak != 0;

-- Compute current win streaks by walking back from most recent game
-- (simplified: count consecutive wins from the latest game)
WITH player_results AS (
    SELECT
        gp.playerid,
        g.gameid,
        g.gamefinish,
        CASE WHEN g.winningplayer = gp.playerid THEN 1 ELSE 0 END as won,
        ROW_NUMBER() OVER (PARTITION BY gp.playerid ORDER BY g.gamefinish DESC) as game_num
    FROM farkle_games g
    JOIN farkle_games_players gp ON g.gameid = gp.gameid
    JOIN farkle_players p ON gp.playerid = p.playerid
    WHERE g.winningplayer > 0
      AND g.gamefinish IS NOT NULL
      AND g.gamewith != 2
      AND p.is_bot = false
      -- Include all historical games
),
-- Find first loss for each player (walking back from most recent)
first_loss AS (
    SELECT playerid, MIN(game_num) as first_loss_at
    FROM player_results
    WHERE won = 0
    GROUP BY playerid
),
current_streaks AS (
    SELECT
        pr.playerid,
        COUNT(*) as streak
    FROM player_results pr
    LEFT JOIN first_loss fl ON pr.playerid = fl.playerid
    WHERE pr.won = 1
      AND (fl.first_loss_at IS NULL OR pr.game_num < fl.first_loss_at)
    GROUP BY pr.playerid
)
UPDATE farkle_players p
SET current_win_streak = cs.streak,
    best_win_streak = cs.streak  -- best = current for backfill (no historical max)
FROM current_streaks cs
WHERE p.playerid = cs.playerid;

-- ============================================================
-- Step 8: Summary
-- ============================================================
SELECT 'Backfill complete' as status;
SELECT COUNT(*) as daily_games FROM farkle_lb_daily_games;
SELECT COUNT(*) as daily_scores, COUNT(*) FILTER (WHERE qualifies) as qualifying FROM farkle_lb_daily_scores;
SELECT COUNT(*) as weekly_scores, COUNT(*) FILTER (WHERE qualifies) as qualifying FROM farkle_lb_weekly_scores;
SELECT COUNT(*) as alltime_entries, COUNT(*) FILTER (WHERE qualifies) as qualifying FROM farkle_lb_alltime;
SELECT playerid, qualifying_days, avg_daily_score, best_day_score, rank
FROM farkle_lb_alltime WHERE qualifies = TRUE ORDER BY rank;
SELECT p.username, p.current_win_streak, p.best_win_streak
FROM farkle_players p WHERE p.current_win_streak > 0 ORDER BY p.current_win_streak DESC;

COMMIT;
