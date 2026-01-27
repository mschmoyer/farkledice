# Symfony Migration Plan: Farkle Dice

Migrate legacy PHP application to Symfony using the strangler fig pattern with fallback routing.

## Overview

- **Approach**: Symfony handles all requests, falls back to legacy for unmatched routes
- **Templates**: Twig for new pages, Smarty adapter for existing
- **Database**: Keep existing PDO queries, introduce Doctrine entities alongside
- **Goal**: Incremental improvement while shipping features

---

## Phase 1: Symfony Skeleton Setup

### 1.1 Directory Structure

```
farkledice/
├── config/                  # NEW: Symfony config
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── framework.yaml
│   │   └── twig.yaml
│   ├── routes.yaml
│   └── services.yaml
├── src/                     # NEW: Symfony code
│   ├── Controller/
│   ├── Entity/
│   ├── EventListener/
│   └── Service/Legacy/      # Bridge services
├── public/                  # NEW: Entry point (replaces wwwroot as doc root)
│   └── index.php            # Symfony front controller with legacy fallback
├── templates/twig/          # NEW: Twig templates
├── var/                     # NEW: Symfony cache/logs
├── wwwroot/                 # EXISTING: Legacy code (shrinks over time)
├── includes/                # EXISTING: Legacy utilities
└── templates/               # EXISTING: Smarty templates
```

### 1.2 Files to Create/Modify

| File | Action | Purpose |
|------|--------|---------|
| `composer.json` | Modify | Add Symfony dependencies |
| `src/Kernel.php` | Create | Symfony kernel with Heroku support |
| `public/index.php` | Create | Front controller with legacy fallback |
| `config/services.yaml` | Create | Service definitions |
| `config/packages/*.yaml` | Create | Framework, Twig, Doctrine config |
| `docker-compose.yml` | Modify | Change document root to `public/` |
| `Dockerfile` | Modify | Add Symfony directories, opcache |

### 1.3 Composer Dependencies to Add

```json
{
  "symfony/framework-bundle": "^8.0",
  "symfony/twig-bundle": "^8.0",
  "symfony/yaml": "^8.0",
  "symfony/dotenv": "^8.0",
  "symfony/routing": "^8.0",
  "symfony/http-foundation": "^8.0",
  "doctrine/orm": "^3.0",
  "doctrine/doctrine-bundle": "^2.13"
}
```

### 1.4 Legacy Fallback Controller

The key to the fallback pattern - catches all unmatched routes:

```php
// src/Controller/LegacyFallbackController.php
#[Route('/{path}', requirements: ['path' => '.*'], priority: -1000)]
public function fallback(string $path): Response
{
    // Map URL to legacy PHP file and include it
    // Capture output and return as Symfony Response
}
```

---

## Phase 2: Service Layer Bridge

### 2.1 Bridge Services to Create

| Service | File | Purpose |
|---------|------|---------|
| `LegacyDatabaseBridge` | `src/Service/Legacy/LegacyDatabaseBridge.php` | Wraps `db_connect()`, shares PDO with Doctrine |
| `LegacySessionBridge` | `src/Service/Legacy/LegacySessionBridge.php` | Initializes legacy session handler |
| `BaseUtilBridge` | `src/Service/Legacy/BaseUtilBridge.php` | Wraps mobile detection, debug, version |
| `SmartyBridge` | `src/Service/Legacy/SmartyBridge.php` | Renders Smarty templates from Twig |
| `GameService` | `src/Service/Legacy/GameService.php` | Wraps game logic functions |

### 2.2 Session Compatibility

Configure Symfony to use the same session:
- Session name: `FarkleOnline`
- Handler: Existing `DatabaseSessionHandler`
- Lifetime: 7 days (604800 seconds)

### 2.3 Critical Legacy Files to Wrap

| File | Functions to Expose |
|------|---------------------|
| `includes/dbutil.php` | `db_connect()`, `db_select_query()`, `db_insert_update_query()` |
| `includes/baseutil.php` | `BaseUtil_SessSet()`, mobile/tablet detection |
| `wwwroot/farkleGameFuncs.php` | `FarkleNewGame()`, `FarkleRoll()`, `FarklePass()` |
| `wwwroot/farklePageFuncs.php` | `GetLobbyInfo()`, `GetStats()` |

---

## Phase 3: Dual Templating

### 3.1 Twig Configuration

```yaml
# config/packages/twig.yaml
twig:
    default_path: '%kernel.project_dir%/templates/twig'
    globals:
        app_version: '%env(APP_VERSION)%'
```

### 3.2 Smarty Integration

Create Twig extension for legacy template inclusion:

```twig
{# In a Twig template, include legacy Smarty section #}
{{ legacy_include('farkle_div_lobby.tpl', {lobbyinfo: lobbyData}) }}
```

### 3.3 Template Strategy

| Scenario | Use |
|----------|-----|
| New pages | Twig only |
| Migrating a page | Twig shell + `legacy_include()` for complex sections |
| Untouched pages | Legacy fallback (Smarty) |

---

## Phase 4: Migration Order

### 4.1 Priority Order (What to Migrate First)

**Tier 1: Foundation** (do first)
1. Health check endpoint (`/health`)
2. Static pages (privacy, support)
3. Admin dashboard

**Tier 2: API Endpoints** (high value)
4. AJAX actions from `farkle_fetch.php` → Symfony controllers
   - Start with: `getlobbyinfo`, `getplayerinfo`, `getfriends`
   - Then: `startgame`, `farkleroll`, `farklepass`

**Tier 3: Full Pages** (as needed)
5. Leaderboard page
6. Player profile
7. Friends management
8. Game lobby
9. Game play (last - most complex)

### 4.2 AJAX Migration Pattern

Current: `farkle_fetch.php?action=getlobbyinfo`
New: `GET /api/lobby/info`

JavaScript feature flag approach:
```javascript
const USE_NEW_API = { 'getlobbyinfo': true, 'startgame': false };
```

### 4.3 Doctrine Entity Introduction

Create entities alongside existing queries:
- `src/Entity/Player.php` - maps to `farkle_players`
- `src/Entity/Game.php` - maps to `farkle_games`

Use entities for new code; existing queries continue working.

---

## Phase 5: Environment & Deployment

### 5.1 Environment Variables

Add to `.env`:
```ini
APP_ENV=dev
APP_SECRET=generate-a-secret
APP_VERSION=2.5.3
```

### 5.2 Docker Changes

```yaml
# docker-compose.yml
environment:
  - APACHE_DOCUMENT_ROOT=/var/www/html/public  # Changed from wwwroot
```

### 5.3 Heroku Compatibility

- Cache/logs in `/tmp/symfony/`
- Session handler remains database-backed
- Update Procfile: `web: vendor/bin/heroku-php-apache2 public/`

---

## Implementation Steps (First Milestone)

**Goal**: Get Symfony running alongside legacy with one working endpoint.

1. [ ] Update `composer.json` with Symfony dependencies
2. [ ] Create `src/Kernel.php`
3. [ ] Create `config/` directory structure with basic config
4. [ ] Create `public/index.php` with legacy fallback
5. [ ] Create `LegacyFallbackController`
6. [ ] Create `LegacyDatabaseBridge` service
7. [ ] Create `LegacySessionBridge` service
8. [ ] Update Docker to use `public/` as document root
9. [ ] Create first Symfony route: `GET /health`
10. [ ] Verify legacy app still works through fallback
11. [ ] Create first API endpoint: `GET /api/lobby/info`

---

## Files to Modify

| Path | Changes |
|------|---------|
| `composer.json` | Add Symfony dependencies, PSR-4 autoload |
| `docker-compose.yml` | Change APACHE_DOCUMENT_ROOT |
| `Dockerfile` | Add var/ directories, opcache |
| `.env` | Add Symfony env vars |
| `Procfile` | Update document root for Heroku |
| `.gitignore` | Add var/, .env.local |

## Files to Create

| Path | Purpose |
|------|---------|
| `src/Kernel.php` | Symfony kernel |
| `public/index.php` | Front controller |
| `config/bundles.php` | Bundle registration |
| `config/services.yaml` | Service definitions |
| `config/routes.yaml` | Route imports |
| `config/packages/framework.yaml` | Framework config |
| `config/packages/twig.yaml` | Twig config |
| `config/packages/doctrine.yaml` | Doctrine config |
| `src/Controller/HealthController.php` | First endpoint |
| `src/Controller/LegacyFallbackController.php` | Legacy bridge |
| `src/Service/Legacy/LegacyDatabaseBridge.php` | DB wrapper |
| `src/Service/Legacy/LegacySessionBridge.php` | Session wrapper |
| `src/Service/Legacy/BaseUtilBridge.php` | Utility wrapper |
| `src/Service/Legacy/SmartyBridge.php` | Template wrapper |
| `src/Twig/LegacyExtension.php` | Twig extension |
