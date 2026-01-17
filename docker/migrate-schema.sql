-- Migration to add missing tables and columns for Farkle Dice
-- Run this against the existing database to add missing schema elements

-- Add missing columns to farkle_players
ALTER TABLE farkle_players
  ADD COLUMN IF NOT EXISTS lastplayed TIMESTAMP DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS createdate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS remoteaddr VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS facebookid VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS random_selectable BOOLEAN DEFAULT true,
  ADD COLUMN IF NOT EXISTS playerlevel INTEGER DEFAULT 1,
  ADD COLUMN IF NOT EXISTS xp_to_level INTEGER DEFAULT 100,
  ADD COLUMN IF NOT EXISTS level_acked BOOLEAN DEFAULT false,
  ADD COLUMN IF NOT EXISTS farkles INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS rolls INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS roundsplayed INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS totalpoints INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS highestround INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS highest10round INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS avgscorepoints INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS cardcolor VARCHAR(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS playertitle VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS titlelevel INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS prestige INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS resetpasscode VARCHAR(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS active BOOLEAN DEFAULT true;

-- Add missing columns to farkle_games
ALTER TABLE farkle_games
  ADD COLUMN IF NOT EXISTS currentturn INTEGER DEFAULT 1,
  ADD COLUMN IF NOT EXISTS maxturns INTEGER DEFAULT 2,
  ADD COLUMN IF NOT EXISTS mintostart INTEGER DEFAULT 500,
  ADD COLUMN IF NOT EXISTS gamestart TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS gamefinish TIMESTAMP DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS gameexpire TIMESTAMP DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS lastturn INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS winningreason VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS titleredeemed BOOLEAN DEFAULT false,
  ADD COLUMN IF NOT EXISTS playerarray TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS playerstring VARCHAR(255) DEFAULT NULL;

-- Rename breakin to mintostart if needed (they're the same thing)
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name='farkle_games' AND column_name='breakin') THEN
    ALTER TABLE farkle_games RENAME COLUMN breakin TO mintostart;
  END IF;
END $$;

-- Add missing columns to farkle_games_players
ALTER TABLE farkle_games_players
  ADD COLUMN IF NOT EXISTS playerturn INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS lastroundscore INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS lastxpgain INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS lastplayed TIMESTAMP DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS winacknowledged BOOLEAN DEFAULT false,
  ADD COLUMN IF NOT EXISTS inactivepasses INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS playerscore INTEGER DEFAULT 0;

-- Rename score to playerscore if needed
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name='farkle_games_players' AND column_name='score'
             AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                            WHERE table_name='farkle_games_players' AND column_name='playerscore')) THEN
    ALTER TABLE farkle_games_players RENAME COLUMN score TO playerscore;
  END IF;
END $$;

-- Create farkle_players_devices table
CREATE TABLE IF NOT EXISTS farkle_players_devices (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  sessionid VARCHAR(64) NOT NULL,
  device VARCHAR(50) DEFAULT 'web',
  token VARCHAR(255) DEFAULT NULL,
  agentString TEXT DEFAULT NULL,
  lastused TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_players_devices_playerid ON farkle_players_devices(playerid);
CREATE INDEX IF NOT EXISTS idx_players_devices_sessionid ON farkle_players_devices(sessionid);
CREATE UNIQUE INDEX IF NOT EXISTS idx_players_devices_unique ON farkle_players_devices(playerid, device);

-- Create farkle_sets table (dice roll tracking)
CREATE TABLE IF NOT EXISTS farkle_sets (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  gameid INTEGER NOT NULL,
  roundnum INTEGER NOT NULL,
  setnum INTEGER NOT NULL,
  handnum INTEGER DEFAULT 0,
  d1 INTEGER DEFAULT 0,
  d2 INTEGER DEFAULT 0,
  d3 INTEGER DEFAULT 0,
  d4 INTEGER DEFAULT 0,
  d5 INTEGER DEFAULT 0,
  d6 INTEGER DEFAULT 0,
  d1save INTEGER DEFAULT 0,
  d2save INTEGER DEFAULT 0,
  d3save INTEGER DEFAULT 0,
  d4save INTEGER DEFAULT 0,
  d5save INTEGER DEFAULT 0,
  d6save INTEGER DEFAULT 0,
  setscore INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_sets_playerid_gameid ON farkle_sets(playerid, gameid);

-- Create farkle_rounds table (round score tracking)
CREATE TABLE IF NOT EXISTS farkle_rounds (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  gameid INTEGER NOT NULL,
  roundnum INTEGER NOT NULL,
  roundscore INTEGER DEFAULT 0,
  rounddatetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_rounds_playerid_gameid ON farkle_rounds(playerid, gameid);

-- Update farkle_friends to add removed column
ALTER TABLE farkle_friends
  ADD COLUMN IF NOT EXISTS removed BOOLEAN DEFAULT false;

-- Rename playerid to sourceid in farkle_friends if needed
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name='farkle_friends' AND column_name='playerid'
             AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                            WHERE table_name='farkle_friends' AND column_name='sourceid')) THEN
    ALTER TABLE farkle_friends RENAME COLUMN playerid TO sourceid;
  END IF;
END $$;

-- Add missing columns to farkle_achievements
ALTER TABLE farkle_achievements
  ADD COLUMN IF NOT EXISTS title VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS worth INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS imagefile VARCHAR(255) DEFAULT NULL;

-- Rename name to title if needed
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name='farkle_achievements' AND column_name='name'
             AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                            WHERE table_name='farkle_achievements' AND column_name='title')) THEN
    ALTER TABLE farkle_achievements RENAME COLUMN name TO title;
  END IF;
END $$;

-- Rename xp_reward to worth if needed
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name='farkle_achievements' AND column_name='xp_reward'
             AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                            WHERE table_name='farkle_achievements' AND column_name='worth')) THEN
    ALTER TABLE farkle_achievements RENAME COLUMN xp_reward TO worth;
  END IF;
END $$;

-- Rename farkle_player_achievements to farkle_achievements_players
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_name='farkle_player_achievements'
             AND NOT EXISTS (SELECT 1 FROM information_schema.tables
                            WHERE table_name='farkle_achievements_players')) THEN
    ALTER TABLE farkle_player_achievements RENAME TO farkle_achievements_players;
  END IF;
END $$;

-- Add missing columns to farkle_achievements_players
ALTER TABLE farkle_achievements_players
  ADD COLUMN IF NOT EXISTS awarded BOOLEAN DEFAULT false,
  ADD COLUMN IF NOT EXISTS achievedate TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Rename earned_date to achievedate if needed
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name='farkle_achievements_players' AND column_name='earned_date'
             AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                            WHERE table_name='farkle_achievements_players' AND column_name='achievedate')) THEN
    ALTER TABLE farkle_achievements_players RENAME COLUMN earned_date TO achievedate;
  END IF;
END $$;

-- Create farkle_lbdata table (leaderboard cache)
CREATE TABLE IF NOT EXISTS farkle_lbdata (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  lbindex INTEGER NOT NULL,
  lbrank INTEGER DEFAULT 0,
  username VARCHAR(100) DEFAULT NULL,
  playerlevel INTEGER DEFAULT 1,
  first_int INTEGER DEFAULT 0,
  second_int INTEGER DEFAULT 0,
  first_string VARCHAR(255) DEFAULT NULL,
  second_string VARCHAR(255) DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_lbdata_lbindex ON farkle_lbdata(lbindex);

-- Add missing columns to farkle_tournaments
ALTER TABLE farkle_tournaments
  ADD COLUMN IF NOT EXISTS playercap INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS launchdate TIMESTAMP DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS tformat INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS tname VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS pointstowin INTEGER DEFAULT 10000,
  ADD COLUMN IF NOT EXISTS mintostart INTEGER DEFAULT 500,
  ADD COLUMN IF NOT EXISTS startcondition VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS lobbyimage VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS roundhours INTEGER DEFAULT 24,
  ADD COLUMN IF NOT EXISTS roundnum INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS roundstartdate TIMESTAMP DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS winningplayer INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS achievementid INTEGER DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS finishdate TIMESTAMP DEFAULT NULL;

-- Rename name to tname if needed
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
             WHERE table_name='farkle_tournaments' AND column_name='name'
             AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                            WHERE table_name='farkle_tournaments' AND column_name='tname')) THEN
    ALTER TABLE farkle_tournaments RENAME COLUMN name TO tname;
  END IF;
END $$;

-- Rename farkle_tournament_participants to farkle_tournaments_players
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables
             WHERE table_name='farkle_tournament_participants'
             AND NOT EXISTS (SELECT 1 FROM information_schema.tables
                            WHERE table_name='farkle_tournaments_players')) THEN
    ALTER TABLE farkle_tournament_participants RENAME TO farkle_tournaments_players;
  END IF;
END $$;

-- Add missing columns to farkle_tournaments_players
ALTER TABLE farkle_tournaments_players
  ADD COLUMN IF NOT EXISTS seednum INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS wins INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS losses INTEGER DEFAULT 0;

-- Rename score to wins if that's what it represents (assuming based on context)
-- Note: You may need to adjust this if score means something different

-- Create farkle_tournaments_games table
CREATE TABLE IF NOT EXISTS farkle_tournaments_games (
  id SERIAL PRIMARY KEY,
  tournamentid INTEGER NOT NULL,
  gameid INTEGER NOT NULL,
  roundnum INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_tournaments_games_tournamentid ON farkle_tournaments_games(tournamentid);
CREATE INDEX IF NOT EXISTS idx_tournaments_games_gameid ON farkle_tournaments_games(gameid);

-- Create siteinfo table (system configuration)
CREATE TABLE IF NOT EXISTS siteinfo (
  paramid INTEGER PRIMARY KEY,
  paramname VARCHAR(100) NOT NULL,
  paramvalue TEXT DEFAULT NULL
);

-- Insert default siteinfo values
INSERT INTO siteinfo (paramid, paramname, paramvalue) VALUES
  (1, 'last_leaderboard_refresh', '0'),
  (3, 'day_of_week', 'Monday'),
  (4, 'double_xp_flag', '0')
ON CONFLICT (paramid) DO NOTHING;

-- Create backup tables for prestige feature
CREATE TABLE IF NOT EXISTS farkle_players_backup (LIKE farkle_players INCLUDING ALL);
CREATE TABLE IF NOT EXISTS farkle_achievements_players_backup (LIKE farkle_achievements_players INCLUDING ALL);

-- Grant permissions (if needed)
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO your_db_user;
-- GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO your_db_user;
