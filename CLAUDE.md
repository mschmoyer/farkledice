# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Farkle Ten is an online multiplayer dice game written in PHP and JavaScript. The game features:
- Multiple game modes: Standard (to 10,000 points) and 10-round tournaments
- Multiplayer gameplay: against friends, random opponents, or solo play
- Social features: friend system, leaderboards, achievements, and tournaments
- Mobile and tablet support with responsive design

Live site: https://www.farkledice.com

## Versioning

**IMPORTANT:** Always update the version when making changes to the codebase.

The version is defined in `includes/baseutil.php` as `APP_VERSION` and displayed in the footer of every page.

**Format:** `Major.Minor.Revision`

- **Major:** Breaking changes or major milestones (e.g., complete redesign, major architecture change)
- **Minor:** New features and significant changes (e.g., new game mode, new social feature)
- **Revision:** Bug fixes and small tweaks (e.g., fix remove friend bug, UI adjustments)

**Current Version:** Check `includes/baseutil.php` for the current `APP_VERSION` constant.

**When to Update:**
- Bump **revision** for bug fixes, minor UI changes, small improvements
- Bump **minor** (and reset revision to 0) for new features, significant enhancements
- Bump **major** (and reset minor/revision to 0) for breaking changes or major releases

**Release Notes:**
When updating the version, **always** update `data/release-notes.json`:
- Add a new entry at the **TOP** of the `releases` array (newest first)
- Include the version number, date (YYYY-MM-DD format), and an array of change notes
- Each note should be a brief, user-friendly description of what changed

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

**Claude API Logs (AI Bot Debugging):**

To debug AI bot decision-making, enable Claude API logging to see the full prompts and responses:

```bash
# 1. Enable logging in .env file
echo "CLAUDE_LOGGING=true" >> .env

# 2. Restart containers to pick up the change
docker-compose restart web

# 3. View Claude API logs in real-time
tail -f logs/claude.log

# 4. To disable logging later
# Edit .env and change CLAUDE_LOGGING=false, then restart
```

**What gets logged:**
- Full system prompts sent to Claude
- User messages with game state context
- Function calling schemas (dice selection tools)
- Complete Claude API responses
- Bot personality and decision reasoning

**Important:** Claude logs can grow quickly. Only enable when actively debugging AI bot behavior. Disable when done to save disk space.

**AI Bot Configuration:**

To modify Claude API prompts or model settings:

- **Model config** (model, tokens, timeout): `wwwroot/farkleBotAI_Claude.php` - constants at top of file (CLAUDE_MODEL, CLAUDE_MAX_TOKENS, etc.)
- **System prompt builder**: `wwwroot/farkleBotAI_Claude.php` - `buildBotSystemPrompt()` function
- **Bot personalities**: Database table `farkle_bot_personalities` - personality traits that shape prompts
- **Function calling schema**: `wwwroot/farkleBotAI_Claude.php` - `getBotDecisionTools()` function

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

### Local HTTPS (Optional)

For trusted HTTPS at `https://localhost:8443` without browser warnings:

```bash
# One-time setup
brew install mkcert
mkcert -install

# Generate certs for this project
mkdir -p docker/ssl
mkcert -cert-file docker/ssl/localhost.crt -key-file docker/ssl/localhost.key localhost 127.0.0.1

# Rebuild container
docker-compose down && docker-compose up -d --build
```

Without mkcert, the container auto-generates self-signed certs (browser will warn but still works).

## Heroku Production Environment

**App:** `farkledice` | **URL:** https://farkledice-03baf34d5c97.herokuapp.com/

**Automatic Deploys:** Enabled via Heroku GitHub integration. Merging to `main` automatically deploys to production.

**Quick Commands:**
```bash
# Manual deploy (if needed)
git push heroku main

# View logs (real-time)
heroku logs --tail -a farkledice

# Access database interactively
heroku pg:psql -a farkledice

# Run single SQL command
heroku pg:psql -a farkledice -c "SELECT COUNT(*) FROM farkle_players;"

# Run SQL migration file
heroku pg:psql -a farkledice --file scripts/your_migration.sql

# Run migration script
heroku run php scripts/migrate-db.php -a farkledice

# Restart app
heroku restart -a farkledice
```

**Database:**
- PostgreSQL Essential-0 (~$5/month)
- **Database name for migrations:** `DATABASE` (use this when tools require a database name)
- Sessions stored in database (`farkle_sessions` table)
- DATABASE_URL env var auto-configured
- Connection details available via: `heroku pg:credentials:url DATABASE -a farkledice`

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
- Create Claude Code Tasks for terminal visibility
- Spawn coder agents one at a time
- Verify each task in Docker
- Ensure all requirements are satisfied

See `planning/README.md` for details on planning artifacts and file structure.

### Task Persistence Across Sessions

For long-running features, use a named task list to persist progress:

```bash
# Start Claude Code with a named task list
CLAUDE_CODE_TASK_LIST_ID=farkledice claude

# Tasks are stored in ~/.claude/tasks/farkledice/
# Press Ctrl+T to toggle task visibility in terminal
```

This allows you to:
- Resume work in a new session without losing context
- See task progress in the terminal UI
- Coordinate between main sessions and subagents

**Two tracking systems work together:**
| System | Purpose | Location |
|--------|---------|----------|
| Claude Code Tasks | Real-time progress | `~/.claude/tasks/` |
| JSON Feature Files | Auditable history | `planning/features/` |

## Pull Requests

When creating a pull request using `gh pr create`, always open it in the browser immediately after creation:

```bash
# After creating PR, open it automatically
gh pr create --title "..." --body "..." && open $(gh pr view --json url -q .url)

# Or if you have the PR URL
open https://github.com/mschmoyer/farkledice/pull/4
```

**Best practice**: Use the `open` command (macOS) to launch the PR in the default browser so the user can review it immediately.

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
- `tests/` - PHPUnit test files (Unit and Integration tests)

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

## Testing

**IMPORTANT:** Run tests after implementing any feature or fix to ensure no regressions. Tests MUST pass before committing.

### Running Tests

```bash
# Run all PHPUnit tests (recommended)
docker exec farkle_web vendor/bin/phpunit

# Run only unit tests (fast, no database)
docker exec farkle_web vendor/bin/phpunit --testsuite Unit

# Run only integration tests (requires database)
docker exec farkle_web vendor/bin/phpunit --testsuite Integration

# Run a specific test file
docker exec farkle_web vendor/bin/phpunit tests/Unit/DiceScoringTest.php

# Run with verbose output
docker exec farkle_web vendor/bin/phpunit --testdox
```

### Test Suites

| Suite | Tests | Description |
|-------|-------|-------------|
| **Unit/DiceScoringTest** | 58 | All dice scoring combinations |
| **Integration/GameFlowTest** | 7 | Game creation, 10-round gameplay |
| **Integration/LobbyTest** | 15 | Lobby info, active games, player data |
| **Integration/ProfileTest** | 19 | Profile stats, titles, achievements |

**Total: 99 tests**

### Test Structure

```
tests/
├── bootstrap.php           # Test setup and autoloading
├── TestCase.php            # Base class for unit tests
├── DatabaseTestCase.php    # Base class for DB tests (with transaction rollback)
├── Unit/
│   └── DiceScoringTest.php # Pure unit tests, no database
├── Integration/
│   ├── GameFlowTest.php    # Game creation and play flow
│   ├── LobbyTest.php       # Lobby functionality
│   └── ProfileTest.php     # Player profile functionality
└── Fixtures/
    ├── TestPlayers.php     # Test player data
    └── DiceScenarios.php   # Dice roll scenarios
```

### Writing New Tests

- **Unit tests**: Extend `Tests\TestCase`, no database access
- **Integration tests**: Extend `Tests\DatabaseTestCase`, uses transactions that auto-rollback
- Use `$this->createTestPlayer('name')` to create isolated test players
- Use `$this->loginAs($playerId)` to simulate logged-in user
- Tests that need `mschmoyer` user should use `$this->markTestSkipped()` if user doesn't exist

### Expected Output

```
PHPUnit 10.5.60
OK (99 tests, 224 assertions)
```

All tests should pass. If any fail, investigate before committing.

## Important Notes

- **Modernized:** Uses PDO with PostgreSQL (migrated from deprecated mysql_* functions)
- **Composer:** Uses Smarty 4.5 via Composer (vendor/autoload.php)
- **Working directory:** Changes to `wwwroot/` on initialization via `baseutil.php`
- **Template compilation:** Local uses `../backbone/templates_c/`, Heroku uses `/tmp/smarty/`
- **Entry point:** `index.php` redirects to `wwwroot/farkle.php`
- **Sessions:** Database-backed (not file-based) for Heroku compatibility
