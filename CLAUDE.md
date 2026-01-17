# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Farkle Ten is an online multiplayer dice game written in PHP and JavaScript. The game features:
- Multiple game modes: Standard (to 10,000 points) and 10-round tournaments
- Multiplayer gameplay: against friends, random opponents, or solo play
- Social features: friend system, leaderboards, achievements, and tournaments
- Mobile and tablet support with responsive design

Live site: www.farkledice.com

## Local Development with Docker

To run this application locally:

```bash
docker-compose up -d
```

Access at http://localhost:8080 (see DOCKER.md for full instructions)

Test credentials: `testuser` / `test123`

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
- `includes/dbutil.php` - Database abstraction layer using deprecated mysql_* functions
- `includes/farkleconfig.class.php` - Configuration management via `../configs/siteconfig.ini`

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

Database connection configured via:
- Database: `mikeschm_db`
- Config file: `../configs/siteconfig.ini`
- Connection: `db_connect()` in `dbutil.php`

Key tables (based on code):
- `farkle_games` - Game instances
- `farkle_games_players` - Player participation in games

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

- This codebase uses deprecated `mysql_*` functions (replaced by mysqli/PDO in modern PHP)
- Working directory changes to `wwwroot/` on initialization via `baseutil.php`
- Templates use Smarty engine with compiled templates in `../backbone/templates_c/`
- Entry point `index.php` redirects to `wwwroot/farkle.php`
