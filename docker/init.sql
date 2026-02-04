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
  lastplayed TIMESTAMP DEFAULT NULL,
  createdate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  remoteaddr VARCHAR(50) DEFAULT NULL,
  level INTEGER DEFAULT 1,
  playerlevel INTEGER DEFAULT 1,
  playertitle VARCHAR(100) DEFAULT NULL,
  lobbyimage VARCHAR(100) DEFAULT NULL,
  playerstring VARCHAR(255) DEFAULT NULL,
  xp INTEGER DEFAULT 0,
  wins INTEGER DEFAULT 0,
  losses INTEGER DEFAULT 0,
  games_played INTEGER DEFAULT 0,
  cardcolor VARCHAR(100) DEFAULT 'green',
  cardbg VARCHAR(100) DEFAULT NULL,
  sendhourlyemails INTEGER DEFAULT 1,
  random_selectable INTEGER DEFAULT 1,
  totalpoints INTEGER DEFAULT 0,
  highestround INTEGER DEFAULT 0,
  farkles INTEGER DEFAULT 0,
  prestige INTEGER DEFAULT 0,
  titlelevel INTEGER DEFAULT 0,
  level_acked BOOLEAN DEFAULT false,
  title VARCHAR(100) DEFAULT NULL,
  avgscorepoints INTEGER DEFAULT 0,
  roundsplayed INTEGER DEFAULT 0,
  xp_to_level INTEGER DEFAULT 100,
  facebookid VARCHAR(100) DEFAULT NULL,
  rolls INTEGER DEFAULT 0,
  highest10round INTEGER DEFAULT 0,
  resetpasscode VARCHAR(64) DEFAULT NULL,
  reinvite_token VARCHAR(64) DEFAULT NULL,
  reinvite_expires TIMESTAMP DEFAULT NULL,
  active SMALLINT DEFAULT 1,
  stylepoints INTEGER DEFAULT 0,
  emoji_reactions VARCHAR(200) DEFAULT '',
  is_bot BOOLEAN DEFAULT false,
  bot_algorithm VARCHAR(50) DEFAULT NULL,
  personality_id INTEGER DEFAULT NULL
);

-- Create players devices table for session management
CREATE TABLE IF NOT EXISTS farkle_players_devices (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  sessionid VARCHAR(64),
  device VARCHAR(100),
  token VARCHAR(255),
  devicetoken VARCHAR(255),
  lastused TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  agentstring TEXT,
  UNIQUE (playerid, device)
);

CREATE INDEX IF NOT EXISTS idx_device_playerid ON farkle_players_devices(playerid);
CREATE INDEX IF NOT EXISTS idx_device_sessionid ON farkle_players_devices(sessionid);

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
  currentturn INTEGER DEFAULT 0,
  randomPlayers INTEGER DEFAULT 2,
  maxturns INTEGER DEFAULT 2,
  gamestart TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  mintostart INTEGER DEFAULT 0,
  lastturn INTEGER DEFAULT 0,
  playerarray TEXT DEFAULT NULL,
  titleredeemed INTEGER DEFAULT 0,
  gameexpire TIMESTAMP DEFAULT NULL,
  playerstring VARCHAR(255) DEFAULT NULL,
  gamefinish TIMESTAMP DEFAULT NULL,
  winningreason VARCHAR(255) DEFAULT NULL,
  max_round INTEGER DEFAULT 10,
  is_overtime BOOLEAN DEFAULT FALSE,
  bot_play_mode VARCHAR(20) DEFAULT NULL
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
  quit BOOLEAN DEFAULT false,
  playerturn INTEGER DEFAULT 1,
  lastplayed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  winacknowledged BOOLEAN DEFAULT false,
  lastroundscore INTEGER DEFAULT 0,
  lastxpgain INTEGER DEFAULT 0,
  inactivepasses INTEGER DEFAULT 0,
  playerscore INTEGER DEFAULT 0,
  emoji_given BOOLEAN DEFAULT FALSE,
  emoji_sent VARCHAR(10) DEFAULT ''
);

CREATE INDEX IF NOT EXISTS idx_gameid ON farkle_games_players(gameid);
CREATE INDEX IF NOT EXISTS idx_playerid ON farkle_games_players(playerid);

-- Create achievements table
CREATE TABLE IF NOT EXISTS farkle_achievements (
  achievementid SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  xp_reward INTEGER DEFAULT 0,
  worth INTEGER DEFAULT 0,
  title VARCHAR(100) DEFAULT NULL,
  imagefile VARCHAR(255) DEFAULT NULL
);

-- Create player achievements junction table
CREATE TABLE IF NOT EXISTS farkle_achievements_players (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  achievementid INTEGER NOT NULL,
  achievedate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  awarded BOOLEAN DEFAULT false,
  UNIQUE (playerid, achievementid)
);

-- Create friends table
-- sourceid = who initiated the friendship, friendid = who they friended
CREATE TYPE friend_status AS ENUM ('pending', 'accepted', 'blocked');

CREATE TABLE IF NOT EXISTS farkle_friends (
  sourceid INTEGER NOT NULL,
  friendid INTEGER NOT NULL,
  removed SMALLINT DEFAULT 0,
  status friend_status DEFAULT 'pending',
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (sourceid, friendid)
);

-- Create tournaments table
CREATE TABLE IF NOT EXISTS farkle_tournaments (
  tournamentid SERIAL PRIMARY KEY,
  tname VARCHAR(100) DEFAULT NULL,
  playercap INTEGER DEFAULT 0,
  tformat INTEGER DEFAULT 0,
  pointstowin INTEGER DEFAULT 10000,
  mintostart INTEGER DEFAULT 500,
  startcondition INTEGER DEFAULT 0,
  lobbyimage VARCHAR(255) DEFAULT NULL,
  roundhours INTEGER DEFAULT 24,
  roundnum INTEGER DEFAULT 0,
  roundstartdate TIMESTAMP DEFAULT NULL,
  winningplayer INTEGER DEFAULT 0,
  achievementid INTEGER DEFAULT NULL,
  finishdate TIMESTAMP DEFAULT NULL,
  launchdate TIMESTAMP DEFAULT NULL,
  startdate TIMESTAMP DEFAULT NULL,
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create tournament players table (tracks player participation)
CREATE TABLE IF NOT EXISTS farkle_tournaments_players (
  id SERIAL PRIMARY KEY,
  tournamentid INTEGER NOT NULL,
  playerid INTEGER NOT NULL,
  seednum INTEGER DEFAULT 0,
  wins INTEGER DEFAULT 0,
  losses INTEGER DEFAULT 0,
  UNIQUE (tournamentid, playerid)
);

-- Create tournament games table
CREATE TABLE IF NOT EXISTS farkle_tournaments_games (
  id SERIAL PRIMARY KEY,
  tournamentid INTEGER NOT NULL,
  gameid INTEGER NOT NULL,
  roundnum INTEGER NOT NULL,
  byeplayerid INTEGER DEFAULT 0
);

-- Create leaderboard cache table
-- Column order must match legacy INSERT queries in farkleLeaderboard.php
CREATE TABLE IF NOT EXISTS farkle_lbdata (
  lbindex INTEGER NOT NULL,
  playerid INTEGER NOT NULL,
  username VARCHAR(100) DEFAULT NULL,
  playerlevel INTEGER DEFAULT 1,
  first_int INTEGER DEFAULT 0,
  second_int INTEGER DEFAULT 0,
  first_string VARCHAR(255) DEFAULT NULL,
  second_string VARCHAR(255) DEFAULT NULL,
  lbrank INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_lbdata_lbindex ON farkle_lbdata(lbindex);

CREATE INDEX IF NOT EXISTS idx_tournaments_games_tournamentid ON farkle_tournaments_games(tournamentid);
CREATE INDEX IF NOT EXISTS idx_tournaments_games_gameid ON farkle_tournaments_games(gameid);

-- Create sets table (dice roll tracking)
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

-- Create rounds table (round score tracking for activity log)
CREATE TABLE IF NOT EXISTS farkle_rounds (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL,
  gameid INTEGER NOT NULL,
  roundnum INTEGER NOT NULL,
  roundscore INTEGER DEFAULT 0,
  rounddatetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_rounds_playerid_gameid ON farkle_rounds(playerid, gameid);

-- Create siteinfo table (system configuration)
CREATE TABLE IF NOT EXISTS siteinfo (
  paramid INTEGER PRIMARY KEY,
  paramname VARCHAR(100) NOT NULL,
  paramvalue TEXT DEFAULT NULL
);

-- Insert default siteinfo values
INSERT INTO siteinfo (paramid, paramname, paramvalue) VALUES
  (1, 'last_leaderboard_refresh', '0'),
  (2, 'last_daily_leaderboard_refresh', '0'),
  (3, 'day_of_week', 'Monday'),
  (4, 'last_cleanup', '0')
ON CONFLICT (paramid) DO NOTHING;

-- Insert a test user (password is 'test123' - MD5 hashed with salt)
INSERT INTO farkle_players (username, password, salt, email, level, xp)
VALUES ('testuser', CONCAT(MD5('test123'), MD5('')), '', 'test@example.com', 1, 0)
ON CONFLICT (username) DO NOTHING;

-- Insert sample achievements
INSERT INTO farkle_achievements (name, description, xp_reward, worth) VALUES
('First Win', 'Win your first game', 10, 5),
('Perfect Roll', 'Score 1500+ points in a single roll', 25, 10),
('Hot Streak', 'Win 5 games in a row', 50, 15),
('Farkle Master', 'Reach level 10', 100, 25)
ON CONFLICT DO NOTHING;
