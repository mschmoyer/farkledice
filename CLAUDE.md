# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Farkle Ten is an online multiplayer dice game written in PHP and JavaScript. The game features:
- Multiple game modes: Standard (to 10,000 points) and 10-round tournaments
- Multiplayer gameplay: against friends, random opponents, or solo play
- Social features: friend system, leaderboards, achievements, and tournaments
- Mobile and tablet support with responsive design

Live site: https://www.farkledice.com

## Local Development with Docker

To run this application locally:

```bash
docker-compose up -d
```

Access at http://localhost:8080 (see DOCKER.md for full instructions)

Test credentials: `testuser` / `test123`

### Viewing Logs

**Error logs are separated from access logs for easier troubleshooting.** When debugging issues, use error logs to see PHP errors, warnings, and Apache errors without access log noise.

**Error Logs (for troubleshooting):**
```bash
# View recent errors (last 50 lines)
tail -n 50 logs/error.log

# Follow error log in real-time
tail -f logs/error.log

# Docker logs (errors only, no access logs)
docker logs -f farkle_web

# View last 100 lines from Docker
docker logs --tail 100 farkle_web

# Search for specific error
grep "Fatal error" logs/error.log
```

**Access Logs (HTTP requests):**
```bash
# View recent access requests
tail -n 50 logs/access.log

# Follow access log in real-time
tail -f logs/access.log

# See requests from specific IP
grep "192.168." logs/access.log
```

**Note:** Log files are stored in `logs/` directory and mounted as a Docker volume. Access logs are NOT included in `docker logs` output to reduce noise.

### Local Database Access

Credentials are stored in `.env.local`. When running psql commands against the local Docker database, use:

```bash
# Quick reference (from .env.local):
# Container: farkle_db
# Database: farkle_db
# User: farkle_user
# Password: farkle_pass

# Run SQL queries locally:
docker exec farkle_db psql -U farkle_user -d farkle_db -c "YOUR SQL HERE"

# Interactive psql session:
docker exec -it farkle_db psql -U farkle_user -d farkle_db

# Examples:
docker exec farkle_db psql -U farkle_user -d farkle_db -c "SELECT username, adminlevel FROM farkle_players;"
docker exec farkle_db psql -U farkle_user -d farkle_db -c "UPDATE farkle_players SET adminlevel = 1 WHERE username = 'mschmoyer';"
```

## Heroku Production Environment

**App:** `farkledice` | **URL:** https://farkledice-03baf34d5c97.herokuapp.com/

**Quick Commands:**
```bash
# Deploy changes
git push heroku modernization/phase-1:main

# View logs (real-time)
heroku logs --tail -a farkledice

# Access database
heroku pg:psql -a farkledice

# Run migration script
heroku run php scripts/migrate-db.php -a farkledice

# Restart app
heroku restart -a farkledice
```

**Database:**
- PostgreSQL Essential-0 (~$5/month)
- Sessions stored in database (`farkle_sessions` table)
- DATABASE_URL env var auto-configured

**Key Differences from Local:**
- Smarty templates compile to `/tmp/smarty/` (ephemeral filesystem)
- Database credentials via `DATABASE_URL` environment variable
- HTTPS enforced via `.htaccess`

See `HEROKU.md` for complete deployment guide.

## Feature Development

Use the orchestrator for structured feature development:

```bash
/orchestrate-feature "your feature description"
```

The orchestrator will:
- Extract and confirm requirements
- Break down into granular tasks
- Spawn coder agents one at a time
- Verify each task in Docker
- Ensure all requirements are satisfied

See `planning/README.md` for details.

## Architecture

### Backend (PHP)

The backend follows a classic PHP architecture with Smarty templating:

**Core Infrastructure:**
- `includes/baseutil.php` - Core utilities, session management, Smarty initialization, mobile/tablet detection
- `includes/dbutil.php` - Database abstraction layer using PDO with PostgreSQL
- `includes/session-handler.php` - Database-backed session handler for Heroku
- `includes/farkleconfig.class.php` - Configuration management (supports DATABASE_URL, env vars, or config file)

**Game Logic:**
- `wwwroot/farkleGameFuncs.php` - Main game functions (create games, manage turns, validate moves)
- `wwwroot/farkleDiceScoring.php` - Dice scoring logic
- `wwwroot/farkleGameObject.php` - Game data structures

**Features:**
- `wwwroot/farkleAchievements.php` - Achievement system
- `wwwroot/farkleTournament.php` - Tournament management
- `wwwroot/farkleLevel.php` - Player leveling and XP
- `wwwroot/farkleFriends.php` - Friend system
- `wwwroot/farkleLeaderboard.php` - Leaderboard queries

**Page Handlers:**
- `wwwroot/farkle.php` - Main game page
- `wwwroot/farkle_fetch.php` - AJAX endpoint for game updates
- `wwwroot/farkleLogin.php` - Authentication

**Cron Jobs:**
- `wwwroot/farkleCronHourly.php` - Hourly maintenance tasks
- `wwwroot/farkleCronNightly.php` - Nightly maintenance tasks

### Frontend (JavaScript)

**Game Components:**
- `js/farkleGame.js` - Core game state management, turn handling, timer
- `js/farkleGameLogic.js` - Client-side game logic and validation
- `js/farklePage.js` - Page navigation and UI management
- `js/farkleLobby.js` - Game lobby interface
- `js/ajax.js` - AJAX utilities for server communication

**Social Features:**
- `js/farkleFriends.js` - Friend management UI
- `js/farkleLeaderboard.js` - Leaderboard display
- `js/farkleTournament.js` - Tournament UI
- `js/farklePlayerInfo.js` - Player profile and stats

**UI Utilities:**
- `js/bubble_util.js` - UI bubble/tooltip utilities
- `js/util.js` - General JavaScript utilities
- `js/farkle_bookmark_bubble.js` - Mobile bookmark prompts

### Templates (Smarty)

Templates are in `templates/` directory:
- `farkle.tpl` - Main game page
- `farkle_div_*.tpl` - Individual UI sections (game board, lobby, login, etc.)
- `header.tpl` / `footer.tpl` - Common page elements

**Subdirectory Template Paths:**
- The `template_dir` is set dynamically in `baseutil.php` based on the current folder
- For files in `wwwroot/admin/`, the template_dir becomes `templates/admin/`
- Admin pages should use `$smarty->display('admin_players.tpl')` NOT `$smarty->display('admin/admin_players.tpl')`
- The subfolder is already part of the template_dir path, so template names should not include it

### Directory Structure

- `includes/` - PHP utilities and base classes
- `wwwroot/` - Main application PHP files (entry point via `index.php` redirect)
- `js/` - JavaScript game logic and UI
- `css/` - Stylesheets
- `templates/` - Smarty templates
- `wwwroot/admin/` - Administrative tools
- `src/` - Additional source files (minimal usage)
- `tests/` - Test files

### Database

**PostgreSQL 16** (migrated from MySQL)
- Connection: `db_connect()` in `dbutil.php` using PDO
- Local: Config from `../configs/siteconfig.ini` or env vars (DB_HOST, DB_USER, etc.)
- Heroku: Auto-configured via `DATABASE_URL` environment variable

**Schema Overview:**

See `docker/init.sql` for complete schema. Migration: `scripts/migrate-db.php`

**NOTE:** Update this block if you add or edit the database schema.

**Core Tables:**
- `farkle_sessions` - PHP sessions (session_id, session_data, last_access)
- `farkle_players` - User accounts (playerid, username, password, email, adminlevel, level, xp, wins, losses, games_played, totalpoints, farkles, highest10round, active)
- `farkle_players_devices` - Device tracking (playerid, sessionid, device, token, lastused)

**Game Tables:**
- `farkle_games` - Game instances (gameid, whostarted, gamewith, gamemode, breakin, pointstowin, currentround, currentplayer, winningplayer, playerarray, created_date, last_activity)
- `farkle_games_players` - Player participation (gameid, playerid, score, playerround, roundscore, turnscore, playerorder, diceonhand, quit, lastplayed)

**Social Tables:**
- `farkle_friends` - Friend relationships (playerid, friendid, status ENUM: pending/accepted/blocked, sourceid, removed)
- `farkle_achievements` - Achievement definitions (achievementid, name, description, xp_reward, worth, title, imagefile)
- `farkle_achievements_players` - Player unlocks (playerid, achievementid, earned_date)
- `farkle_tournaments` - Tournament data (tournamentid, name, status ENUM: upcoming/active/completed, start_date, end_date)
- `farkle_tournament_participants` - Tournament players (tournamentid, playerid, score, rank)

### Game Constants

**Game Modes:**
- `GAME_MODE_STANDARD` (1) - Standard Farkle to 10,000 points
- `GAME_MODE_10ROUND` (2) - 10 rounds, highest score wins

**Game Types:**
- `GAME_WITH_RANDOM` (0) - Random opponents
- `GAME_WITH_FRIENDS` (1) - Selected players
- `GAME_WITH_SOLO` (2) - Single player practice

**Game States (JS):**
- `GAME_STATE_LOADING` (0)
- `GAME_STATE_ROLLING` (1)
- `GAME_STATE_ROLLED` (2)
- `GAME_STATE_PASSED` (3)
- `GAME_STATE_WATCHING` (4)

### Session Management

- Session name: "FarkleOnline"
- Session initialized via `BaseUtil_SessSet()` in `baseutil.php`
- Key session vars: `playerid`, `username`, `testserver`, `mobilemode`

### Debugging

Enable debug output via URL parameter:
- `?debug=7` - SQL queries and general debug info
- `?debug=14` - Verbose debugging with timing
- `?debug=31` - Path and configuration debugging

### Mobile/Tablet Detection

Automatic user agent detection in `baseutil.php`:
- Sets `$gMobileMode` for phones
- Sets `$gTabletMode` for tablets (iPad)
- Override with `?mobilemode=1` or `?tabletmode=1`

## Important Notes

- **Modernized:** Uses PDO with PostgreSQL (migrated from deprecated mysql_* functions)
- **Composer:** Uses Smarty 4.5 via Composer (vendor/autoload.php)
- **Working directory:** Changes to `wwwroot/` on initialization via `baseutil.php`
- **Template compilation:** Local uses `../backbone/templates_c/`, Heroku uses `/tmp/smarty/`
- **Entry point:** `index.php` redirects to `wwwroot/farkle.php`
- **Sessions:** Database-backed (not file-based) for Heroku compatibility
