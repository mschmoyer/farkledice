# Plan: Move Application to `/app` Subfolder

## Goal
Clean up the GitHub repo by moving application code into an `/app` subfolder, separating it from infrastructure/config files.

## Current vs Target Structure

```
CURRENT                          TARGET
/                                /
├── index.php (delete)           ├── app/
├── wwwroot/    ──────────────>  │   ├── wwwroot/
├── includes/   ──────────────>  │   ├── includes/
├── templates/  ──────────────>  │   └── templates/
├── vendor/                      ├── vendor/
├── docker/                      ├── docker/
├── scripts/                     ├── scripts/
├── docs/                        ├── docs/
├── planning/                    ├── planning/
├── logs/                        ├── logs/
├── Procfile                     ├── Procfile (edit)
├── Dockerfile                   ├── Dockerfile (edit)
├── docker-compose.yml           ├── docker-compose.yml (edit)
├── composer.json                ├── composer.json (edit)
└── *.md files                   └── *.md files (edit)
```

## Key Insight: Minimal Code Changes Required

The `baseutil.php` bootstrap (lines 117-126) walks up directories looking for `wwwroot`:
```php
while( basename($dir) != $gFolder && $x <= 5 ) {
    $dir = dirname($dir);
}
$dir = dirname($dir);  // $dir becomes the parent of wwwroot (i.e., /app)
```

**This means:**
- All 75 PHP files with `require_once('../includes/...')` continue working
- Template paths, config paths relative to `$dir` continue working
- Only ONE bootstrap change needed: vendor autoload path

## Files to Modify

### 1. Move Directories (git mv)
- `wwwroot/` → `app/wwwroot/`
- `includes/` → `app/includes/`
- `templates/` → `app/templates/`

### 2. Delete Root index.php
- `/index.php` - obsolete, Apache serves from `app/wwwroot/` directly

### 3. Configuration Files

**Procfile** (line 1):
```diff
-web: vendor/bin/heroku-php-apache2 wwwroot/
+web: vendor/bin/heroku-php-apache2 app/wwwroot/
```

**Dockerfile** (line 27):
```diff
-ENV APACHE_DOCUMENT_ROOT /var/www/html/wwwroot
+ENV APACHE_DOCUMENT_ROOT /var/www/html/app/wwwroot
```

**docker-compose.yml** (line 15):
```diff
-      - APACHE_DOCUMENT_ROOT=/var/www/html/wwwroot
+      - APACHE_DOCUMENT_ROOT=/var/www/html/app/wwwroot
```

**composer.json** (line 16):
```diff
-    "classmap": ["includes/"]
+    "classmap": ["app/includes/"]
```

### 4. Bootstrap Fix

**app/includes/baseutil.php** (line 139):
```diff
-require_once($dir . '/vendor/autoload.php');
+require_once($dir . '/../vendor/autoload.php');
```

After move, `$dir` = `/app`, but vendor is at root, so need `/../vendor/`.

### 5. Scripts (4 files)

**scripts/sync-leaderboards.php** (line 23):
```diff
-chdir(dirname(__DIR__) . '/wwwroot');
+chdir(dirname(__DIR__) . '/app/wwwroot');
```

Same change for:
- `scripts/seed_bot_personalities.php` (line 23)
- `scripts/link_bot_players.php` (line 23)

**scripts/migrate-db.php** - NO CHANGE (doesn't use baseutil.php)

### 6. Documentation

**CLAUDE.md** - Update path references:
- `includes/baseutil.php` → `app/includes/baseutil.php`
- `wwwroot/` references → `app/wwwroot/`
- Directory structure diagram

## Implementation Steps

1. **Create directory and move files**
   ```bash
   mkdir -p app
   git mv wwwroot app/
   git mv includes app/
   git mv templates app/
   ```

2. **Delete obsolete root index.php**
   ```bash
   git rm index.php
   ```

3. **Edit configuration files**
   - Procfile
   - Dockerfile
   - docker-compose.yml
   - composer.json

4. **Fix bootstrap vendor path**
   - app/includes/baseutil.php line 139

5. **Update scripts**
   - 3 scripts with wwwroot path

6. **Update documentation**
   - CLAUDE.md

7. **Regenerate autoloader**
   ```bash
   composer dump-autoload
   ```

8. **Update version** in `app/includes/baseutil.php`

## Verification

### Local Testing
```bash
docker-compose down
docker-compose up --build
```

Then verify:
- [ ] http://localhost:8080 loads the game
- [ ] Login with testuser/test123 works
- [ ] Static assets (CSS, JS, images) load correctly
- [ ] Create a game, play a turn
- [ ] Admin panel at /admin/ works
- [ ] Check `docker logs farkle_web` for errors

### Post-Heroku Deploy
- [ ] https://www.farkledice.com loads
- [ ] All functionality works

## Rollback
If issues occur:
```bash
git revert HEAD
git push origin main
```
Heroku auto-deploys, restoring previous structure.

## Risk Assessment
- **LOW RISK**: Config file changes are straightforward
- **LOW RISK**: Bootstrap vendor path is simple fix
- **NO RISK**: Static assets use root-relative paths (`/css/`, `/js/`)
- All changes in single atomic commit
