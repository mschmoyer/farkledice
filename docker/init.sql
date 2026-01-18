-- Initialize Farkle Database

-- Create sessions table for database-backed session storage
CREATE TABLE IF NOT EXISTS farkle_sessions (
  session_id VARCHAR(128) PRIMARY KEY,
  session_data TEXT,
  last_access TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_last_access ON farkle_sessions(last_access);

-- Create players table
CREATE TABLE IF NOT EXISTS farkle_players (
  playerid SERIAL PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  salt VARCHAR(32) DEFAULT '',
  fullname VARCHAR(100),
  email VARCHAR(255) DEFAULT NULL,
  adminlevel INTEGER DEFAULT 0,
  sessionid VARCHAR(64),
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP DEFAULT NULL,
  level INTEGER DEFAULT 1,
  xp INTEGER DEFAULT 0,
  wins INTEGER DEFAULT 0,
  losses INTEGER DEFAULT 0,
  games_played INTEGER DEFAULT 0
);

-- Create games table
CREATE TABLE IF NOT EXISTS farkle_games (
  gameid SERIAL PRIMARY KEY,
  whostarted INTEGER DEFAULT NULL,
  gamewith INTEGER DEFAULT 0, -- 0=random, 1=friends, 2=solo
  gamemode INTEGER DEFAULT 2, -- 1=standard, 2=10-round
  breakin INTEGER DEFAULT 500,
  pointstowin INTEGER DEFAULT 5000,
  winningplayer INTEGER DEFAULT 0,
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  currentround INTEGER DEFAULT 1,
  currentplayer INTEGER DEFAULT 0,
  randomPlayers INTEGER DEFAULT 2
);

CREATE INDEX IF NOT EXISTS idx_whostarted ON farkle_games(whostarted);
CREATE INDEX IF NOT EXISTS idx_winningplayer ON farkle_games(winningplayer);

-- Create game players junction table
CREATE TABLE IF NOT EXISTS farkle_games_players (
  id SERIAL PRIMARY KEY,
  gameid INTEGER NOT NULL,
  playerid INTEGER NOT NULL,
  score INTEGER DEFAULT 0,
  playerround INTEGER DEFAULT 1,
  roundscore INTEGER DEFAULT 0,
  turnscore INTEGER DEFAULT 0,
  playerorder INTEGER DEFAULT 0,
  lastroll VARCHAR(50) DEFAULT NULL,
  diceonhand VARCHAR(50) DEFAULT NULL,
  quit BOOLEAN DEFAULT false
);

CREATE INDEX IF NOT EXISTS idx_gameid ON farkle_games_players(gameid);
CREATE INDEX IF NOT EXISTS idx_playerid ON farkle_games_players(playerid);

-- Create achievements table
CREATE TABLE IF NOT EXISTS farkle_achievements (
  achievementid SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  xp_reward INTEGER DEFAULT 0
);

-- Create player achievements junction table
CREATE TABLE IF NOT EXISTS farkle_player_achievements (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  achievementid INTEGER NOT NULL,
  earned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (playerid, achievementid)
);

-- Create friends table
CREATE TYPE friend_status AS ENUM ('pending', 'accepted', 'blocked');

CREATE TABLE IF NOT EXISTS farkle_friends (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  friendid INTEGER NOT NULL,
  status friend_status DEFAULT 'pending',
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (playerid, friendid)
);

-- Create tournaments table
CREATE TYPE tournament_status AS ENUM ('upcoming', 'active', 'completed');

CREATE TABLE IF NOT EXISTS farkle_tournaments (
  tournamentid SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  status tournament_status DEFAULT 'upcoming',
  start_date TIMESTAMP DEFAULT NULL,
  end_date TIMESTAMP DEFAULT NULL,
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create tournament participants table
CREATE TABLE IF NOT EXISTS farkle_tournament_participants (
  id SERIAL PRIMARY KEY,
  tournamentid INTEGER NOT NULL,
  playerid INTEGER NOT NULL,
  score INTEGER DEFAULT 0,
  rank INTEGER DEFAULT NULL,
  UNIQUE (tournamentid, playerid)
);

-- Insert a test user (password is 'test123' - MD5 hashed with salt)
INSERT INTO farkle_players (username, password, salt, email, level, xp)
VALUES ('testuser', CONCAT(MD5('test123'), MD5('')), '', 'test@example.com', 1, 0)
ON CONFLICT (username) DO NOTHING;

-- Insert sample achievements
INSERT INTO farkle_achievements (name, description, xp_reward) VALUES
('First Win', 'Win your first game', 10),
('Perfect Roll', 'Score 1500+ points in a single roll', 25),
('Hot Streak', 'Win 5 games in a row', 50),
('Farkle Master', 'Reach level 10', 100)
ON CONFLICT DO NOTHING;
