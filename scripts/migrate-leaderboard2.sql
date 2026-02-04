-- Leaderboard 2.0 Migration
-- Run: heroku pg:psql -a farkledice --file scripts/migrate-leaderboard2.sql
-- Safe to run multiple times (all statements are idempotent)

-- Table 1: Per-game tracking within daily 20-game cap
CREATE TABLE IF NOT EXISTS farkle_lb_daily_games (
    id SERIAL PRIMARY KEY,
    playerid INT NOT NULL,
    gameid INT NOT NULL,
    lb_date DATE NOT NULL,
    game_seq INT NOT NULL,
    game_score INT NOT NULL,
    counted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(playerid, gameid),
    UNIQUE(playerid, lb_date, game_seq)
);
CREATE INDEX IF NOT EXISTS idx_lb_daily_games_player_date ON farkle_lb_daily_games(playerid, lb_date);
CREATE INDEX IF NOT EXISTS idx_lb_daily_games_date ON farkle_lb_daily_games(lb_date);

-- Table 2: Aggregated daily leaderboard scores
CREATE TABLE IF NOT EXISTS farkle_lb_daily_scores (
    playerid INT NOT NULL,
    lb_date DATE NOT NULL,
    games_played INT NOT NULL DEFAULT 0,
    top10_score INT NOT NULL DEFAULT 0,
    qualifies BOOLEAN DEFAULT FALSE,
    rank INT,
    prev_rank INT,
    PRIMARY KEY (playerid, lb_date)
);
CREATE INDEX IF NOT EXISTS idx_lb_daily_scores_date_score ON farkle_lb_daily_scores(lb_date, top10_score DESC);

-- Table 3: Aggregated weekly leaderboard scores
CREATE TABLE IF NOT EXISTS farkle_lb_weekly_scores (
    playerid INT NOT NULL,
    week_start DATE NOT NULL,
    daily_scores_used INT NOT NULL DEFAULT 0,
    top5_score INT NOT NULL DEFAULT 0,
    qualifies BOOLEAN DEFAULT FALSE,
    rank INT,
    prev_rank INT,
    PRIMARY KEY (playerid, week_start)
);
CREATE INDEX IF NOT EXISTS idx_lb_weekly_scores_week ON farkle_lb_weekly_scores(week_start, top5_score DESC);

-- Table 4: Career/all-time leaderboard
CREATE TABLE IF NOT EXISTS farkle_lb_alltime (
    playerid INT NOT NULL UNIQUE,
    qualifying_days INT NOT NULL DEFAULT 0,
    total_daily_score BIGINT NOT NULL DEFAULT 0,
    avg_daily_score NUMERIC(10,2) NOT NULL DEFAULT 0,
    best_day_score INT DEFAULT 0,
    qualifies BOOLEAN DEFAULT FALSE,
    rank INT,
    prev_rank INT,
    last_updated TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_lb_alltime_avg ON farkle_lb_alltime(avg_daily_score DESC) WHERE qualifies = TRUE;

-- Add per-game metrics to all-time table
ALTER TABLE farkle_lb_alltime ADD COLUMN IF NOT EXISTS avg_game_score NUMERIC(10,2) NOT NULL DEFAULT 0;
ALTER TABLE farkle_lb_alltime ADD COLUMN IF NOT EXISTS best_game_score INT DEFAULT 0;
ALTER TABLE farkle_lb_alltime ADD COLUMN IF NOT EXISTS total_games INT NOT NULL DEFAULT 0;
CREATE INDEX IF NOT EXISTS idx_lb_alltime_avg_game ON farkle_lb_alltime(avg_game_score DESC) WHERE qualifies = TRUE;

-- Table 5: Rotating stat highlights
CREATE TABLE IF NOT EXISTS farkle_lb_stats (
    id SERIAL PRIMARY KEY,
    playerid INT NOT NULL,
    lb_date DATE NOT NULL,
    stat_type VARCHAR(30) NOT NULL,
    stat_value NUMERIC(12,4) NOT NULL,
    stat_detail TEXT,
    UNIQUE(playerid, lb_date, stat_type)
);
CREATE INDEX IF NOT EXISTS idx_lb_stats_type_date ON farkle_lb_stats(stat_type, lb_date, stat_value DESC);

-- ALTER existing table: add win streak columns
ALTER TABLE farkle_players ADD COLUMN IF NOT EXISTS current_win_streak INT DEFAULT 0;
ALTER TABLE farkle_players ADD COLUMN IF NOT EXISTS best_win_streak INT DEFAULT 0;
