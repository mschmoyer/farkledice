# CLAUDE.md - AI Assistant Guide for Farkle Ten

This document provides essential information for AI assistants working with the Farkle Ten codebase.

## Project Overview

**Farkle Ten** is an online multiplayer dice game built with PHP and vanilla JavaScript. It's a web-based implementation of the classic Farkle dice game featuring achievements, levels, tournaments, leaderboards, and social features.

**Live Site:** www.farkledice.com

## Repository Structure

```
farkledice/
├── css/                    # Stylesheets (main, mobile, responsive)
├── includes/               # Shared PHP utilities and classes
├── js/                     # Client-side JavaScript
│   └── compressed/         # Minified versions
├── templates/              # Smarty template files
├── wwwroot/                # Web root (main PHP application)
├── index.php               # Redirect to wwwroot/farkle.php
└── README.md
```

## Technology Stack

### Backend
- **PHP** with MySQL database
- **Smarty Template Engine** for server-side templating
- **Facebook SDK** for OAuth authentication

### Frontend
- **Vanilla JavaScript** (ES5, no frameworks)
- **jQuery** for DOM manipulation and AJAX
- **HTML5 Canvas** for dice rendering
- **CSS3** with responsive design via media queries

## Key Files

### Backend Entry Points (wwwroot/)
| File | Purpose |
|------|---------|
| `farkle.php` | Main entry point, renders primary template |
| `farkle_fetch.php` | AJAX request router for all POST requests |
| `farkleGameFuncs.php` | Core game logic (creating games, scoring, turns) |
| `farkleLogin.php` | Authentication, registration, Facebook login |
| `farkleDiceScoring.php` | Dice scoring algorithm |
| `farkleTournament.php` | Tournament system |
| `farkleAchievements.php` | Achievement tracking |
| `farkleLevel.php` | Player progression/leveling |
| `farkleLeaderboard.php` | Leaderboard calculations |
| `farkleFriends.php` | Friend system |
| `farkleUtil.php` | General utilities |

### Shared Includes (includes/)
| File | Purpose |
|------|---------|
| `baseutil.php` | Session management, debug output, Smarty init |
| `dbutil.php` | Database connection and query helpers |
| `farkleconfig.class.php` | Configuration loader (from `../configs/siteconfig.ini`) |
| `class.player.php` | Player class |
| `facebook.php` | Facebook integration wrapper |

### Frontend JavaScript (js/)
| File | Purpose |
|------|---------|
| `farklePage.js` | Page initialization and screen navigation |
| `farkleGame.js` | Game play UI and dice interactions |
| `farkleGameLogic.js` | Game logic (minified) |
| `farkleGameLogic_raw.js` | Game logic (readable source) |
| `ajax.js` | AJAX request wrapper |
| `farkleLobby.js` | Lobby screen |
| `farkleTournament.js` | Tournament UI |
| `farkleLeaderboard.js` | Leaderboard UI |
| `farkleFriends.js` | Friend system UI |

## Code Conventions

### PHP
- **Function naming:** camelCase, prefixed with context (e.g., `Farkle_SessSet()`, `db_select_query()`)
- **Debug output:** Use `BaseUtil_Debug($msg, $level)` with global `$g_debug`
- **Database queries:** Three types: `SQL_SINGLE_VALUE`, `SQL_SINGLE_ROW`, `SQL_MULTI_ROW`
- **Change logs:** File headers contain change history with date and editor

### JavaScript
- **Global variables:** Prefixed with `g` (e.g., `gGameData`, `gPlayerid`, `gDiceOnTable`)
- **Constants:** UPPERCASE (e.g., `MAX_DICE`, `GAME_STATE_ROLLING`, `GAME_MODE_STANDARD`)
- **Functions:** camelCase
- **AJAX:** POST requests to `wwwroot/farkle_fetch.php` with `action` parameter
- **Alerts:** Use `farkleAlert()` for user notifications

### CSS
- **Class naming:** camelCase descriptive names
- **Responsive:** Mobile-first approach with attribute-based selectors
- **Effects:** CSS3 transitions for game animations

### Smarty Templates
- **Variable assignment:** `$smarty->assign('name', $value)` in PHP
- **Script blocks:** Use `{literal}` blocks for embedded JavaScript

## Development Workflow

### No Build Process
This is a traditional PHP application with no bundler or build system. Files are edited directly.

### Configuration
Configuration is loaded from `../configs/siteconfig.ini` (not in repo). Required keys:
- `dbuser` - Database username
- `dbpass` - Database password
- `dbhost` - Database host

### Database
- Database name: `mikeschm_db` (hardcoded)
- Uses legacy `mysql_*` functions (deprecated)

### Debugging
- Enable debug output with `?debug=` query parameter
- Debug levels control verbosity

## Architecture Patterns

### Client-Server Communication
All AJAX requests go through `wwwroot/farkle_fetch.php` which routes based on the `action` parameter:
```javascript
farkleAjax('farkle_fetch.php', { action: 'ACTION_NAME', ...params }, callback);
```

### Game State
- Stored in MySQL database
- Client-side caching for current turn data
- AJAX polling for real-time updates

### Screen Navigation
Single-page app pattern using `showPage('pagename')` to swap visible divs.

## Game Mechanics

### Game Modes
- **Standard:** Race to target points (e.g., 10,000)
- **10-Round:** Best score after 10 rounds wins

### Game Types
- **Random:** Matched with random opponents
- **Friends:** Invite friends to play
- **Solo:** Practice mode

### Dice System
- HTML5 Canvas rendering with sprite sheets
- Touch and mouse event support

## Important Notes for AI Assistants

### Legacy Code Considerations
- Uses deprecated `mysql_*` functions (PHP 5.x era)
- Direct SQL string concatenation exists in some places
- Facebook credentials are hardcoded in source files

### When Making Changes
1. Follow existing naming conventions (camelCase, `g` prefix for globals)
2. Use the existing debug system (`BaseUtil_Debug()`)
3. Route new AJAX actions through `farkle_fetch.php`
4. Add change log comments to file headers when modifying files
5. Test on both desktop and mobile (responsive design)

### Files to Avoid Modifying Without Care
- `includes/dbutil.php` - Database core functionality
- `includes/baseutil.php` - Session/Smarty core
- `wwwroot/farkle_fetch.php` - Request router

### Common Tasks

**Adding a new AJAX endpoint:**
1. Add handler in `wwwroot/farkle_fetch.php`
2. Create function in appropriate `farkle*.php` file
3. Call from JavaScript using `farkleAjax()`

**Adding a new page/screen:**
1. Create template in `templates/farkle_div_*.tpl`
2. Include in `templates/farkle.tpl`
3. Add navigation in `js/farklePage.js`

**Adding styles:**
1. Main styles go in `css/farkle.css`
2. Mobile-specific styles in `css/mobile.css`

## Testing

No automated test suite exists. Manual testing required:
- Test game flows (create game, roll dice, scoring)
- Test on multiple screen sizes
- Admin pages available for tournament simulation
