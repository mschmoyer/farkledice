# Farkle Dice Modernization Plan

## Executive Summary

This document outlines the modernization strategy for Farkle Dice (www.farkledice.com) to:
- Run natively on Apple Silicon (no more slow emulation)
- Deploy seamlessly to Heroku
- Upgrade from deprecated/insecure PHP 5.6 to modern PHP 8.3
- Migrate from MySQL to PostgreSQL
- Establish a foundation for future enhancements

## Current State Analysis

### Technology Stack (Legacy)

| Component | Current | Issues |
|-----------|---------|--------|
| **PHP** | 5.6 | EOL since Jan 2019 (7+ years without security updates) |
| **Database** | MySQL 5.7 | Requires `platform: linux/amd64` on Apple Silicon (slow emulation) |
| **DB Driver** | mysql_* functions | Removed in PHP 7.0, completely unsupported |
| **Templating** | Smarty 2.x | Ancient version, no PHP 8 support |
| **Dependencies** | Manual downloads | No dependency management, hard to update |
| **Docker** | Emulated on M1/M2 | MySQL 5.7 requires x86 emulation layer |

### Security Concerns

- PHP 5.6 has known vulnerabilities with no patches available
- mysql_* functions are injection-prone without prepared statements
- No modern authentication mechanisms
- No dependency security scanning

### Performance Issues

- Docker on Apple Silicon runs 2-3x slower due to emulation
- No query optimization or caching layer
- Inefficient session handling
- No CDN or static asset optimization

---

## Modernization Strategy

We recommend a **phased approach** to minimize risk and allow incremental deployment.

---

## Phase 1: Foundation Modernization (RECOMMENDED START)

**Timeline:** 3-5 days
**Risk Level:** Low
**Deployment:** Can deploy incrementally

### Goals

- Native Apple Silicon support (no emulation)
- Heroku deployment ready
- Security updates and modern PHP features
- PostgreSQL migration for better Heroku integration

### Changes Required

#### 1. PHP Upgrade (5.6 → 8.3)

**Benefits:**
- Active security support (maintained until Nov 2026)
- 2-3x performance improvement
- Modern language features (typed properties, enums, null-safe operator)
- Native Apple Silicon support

**Changes:**
- Update Dockerfile to use `php:8.3-apache`
- Replace all `mysql_*` functions with PDO (PostgreSQL compatible)
- Upgrade Smarty 2.x → 4.x
- Add Composer for dependency management

#### 2. Database Migration (MySQL 5.7 → PostgreSQL 16)

**Benefits:**
- Native ARM64 support (fast on Apple Silicon)
- Better Heroku support (free tier available, managed backups)
- Advanced features (JSONB, full-text search, CTEs)
- Better concurrency handling for multiplayer game
- No licensing concerns (truly open source)

**Changes:**
- Update `dbutil.php` to use PDO with PostgreSQL driver
- Migrate SQL schema from MySQL to PostgreSQL syntax
- Update queries for PostgreSQL compatibility:
  - `AUTO_INCREMENT` → `SERIAL` or `GENERATED ALWAYS AS IDENTITY`
  - `LIMIT x,y` → `LIMIT y OFFSET x`
  - `NOW()` → `CURRENT_TIMESTAMP` (or keep NOW(), both work)
  - Backticks `` `column` `` → double quotes `"column"` (if needed)
- Create PostgreSQL initialization script

#### 3. Add Composer Dependency Management

**composer.json:**
```json
{
  "name": "farkledice/farkle-ten",
  "description": "Farkle Ten multiplayer dice game",
  "type": "project",
  "require": {
    "php": "^8.3",
    "smarty/smarty": "^4.5",
    "ext-pdo": "*",
    "ext-pdo_pgsql": "*"
  },
  "autoload": {
    "classmap": ["includes/"]
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  }
}
```

#### 4. Update Docker Configuration

**New Dockerfile:**

```dockerfile
FROM php:8.3-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY ../.. /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set up Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/wwwroot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/backbone/templates_c \
    /var/www/backbone/cache \
    /var/www/configs \
    /var/www/html/logs

# Set permissions
RUN chown -R www-data:www-data /var/www/backbone /var/www/configs /var/www/html/logs
RUN chmod -R 755 /var/www/backbone /var/www/configs
RUN chmod -R 777 /var/www/backbone/templates_c /var/www/backbone/cache /var/www/html/logs

# Install Smarty (handled by Composer, but ensure directory structure)
RUN cp -r vendor/smarty/smarty/libs/* /var/www/backbone/libs/ || true

EXPOSE 80

CMD ["apache2-foreground"]
```

**Updated docker-compose.yml:**
```yaml
version: '3.8'

services:
  web:
    build: .
    container_name: farkle_web
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/backbone:/var/www/backbone
      - ./docker/configs:/var/www/configs
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/wwwroot
      - DB_HOST=db
      - DB_NAME=farkle_db
      - DB_USER=farkle_user
      - DB_PASS=farkle_pass
    depends_on:
      - db
    networks:
      - farkle_network

  db:
    image: postgres:16-alpine
    # No platform override needed - native ARM64 support!
    container_name: farkle_db
    environment:
      POSTGRES_DB: farkle_db
      POSTGRES_USER: farkle_user
      POSTGRES_PASSWORD: farkle_pass
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - farkle_network

  adminer:
    image: adminer:latest
    container_name: farkle_adminer
    environment:
      ADMINER_DEFAULT_SERVER: db
    ports:
      - "8081:8080"
    depends_on:
      - db
    networks:
      - farkle_network

networks:
  farkle_network:
    driver: bridge

volumes:
  postgres_data:
```

#### 5. Database Migration Script

Create `migrations/mysql_to_postgres.md` with mapping guide:

**Common Conversions:**

| MySQL | PostgreSQL |
|-------|------------|
| `AUTO_INCREMENT` | `SERIAL` or `BIGSERIAL` |
| `INT` | `INTEGER` |
| `TINYINT(1)` | `BOOLEAN` |
| `DATETIME` | `TIMESTAMP` |
| `MEDIUMTEXT`, `LONGTEXT` | `TEXT` |
| `UNSIGNED` | Remove (use larger type or CHECK constraint) |
| Backticks `` `table` `` | Double quotes `"table"` or none |
| `LIMIT 10, 20` | `LIMIT 20 OFFSET 10` |
| `INSERT ... ON DUPLICATE KEY UPDATE` | `INSERT ... ON CONFLICT ... DO UPDATE` |

#### 6. Update dbutil.php for PDO + PostgreSQL

**New db_connect() function:**
```php
function db_connect()
{
    $config = new FarkleConfig();

    $dbname = $config->data['dbname'] ?? getenv('DB_NAME') ?? 'farkle_db';
    $username = $config->data['dbuser'] ?? getenv('DB_USER') ?? 'farkle_user';
    $password = $config->data['dbpass'] ?? getenv('DB_PASS') ?? 'farkle_pass';
    $host = $config->data['dbhost'] ?? getenv('DB_HOST') ?? 'db';
    $port = $config->data['dbport'] ?? getenv('DB_PORT') ?? '5432';

    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $dbh = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $dbh;
    } catch (PDOException $e) {
        die('Error connecting to database: ' . $e->getMessage());
    }
}
```

**Updated query functions:**
```php
function db_select_query($sql, $return_type = SQL_MULTI_ROW)
{
    global $g_debug;
    BaseUtil_Debug('Executing query: ' . $sql, 7, "gray");

    if ($g_debug >= 14) $theStartTime = microtime(true);

    $dbh = db_connect();
    $stmt = $dbh->query($sql);

    if (!$stmt) {
        $error = $dbh->errorInfo();
        BaseUtil_Error(__FUNCTION__ . ": SQL Error [{$error[1]}]: {$error[2]}   SQL = $sql");
    }

    if ($return_type == SQL_MULTI_ROW) {
        $retval = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($return_type == SQL_SINGLE_ROW) {
        $retval = $stmt->fetch(PDO::FETCH_ASSOC);
    } else { // SQL_SINGLE_VALUE
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $retval = $row ? $row[0] : null;
    }

    if ($g_debug >= 14) {
        $duration = microtime(true) - $theStartTime;
        BaseUtil_Debug("Query took: " . round($duration * 1000, 2) . "ms", 14);
    }

    return $retval;
}

function db_insert_update_query($sql)
{
    global $g_debug;
    BaseUtil_Debug('Executing query: ' . $sql, 7, "gray");

    $dbh = db_connect();
    $result = $dbh->exec($sql);

    if ($result === false) {
        $error = $dbh->errorInfo();
        BaseUtil_Error(__FUNCTION__ . ": SQL Error [{$error[1]}]: {$error[2]}   SQL = $sql");
    }

    return $result;
}

function db_insert_id()
{
    $dbh = db_connect();
    // PostgreSQL uses sequences for auto-increment
    // This gets the last value from the most recently used sequence
    $result = $dbh->query("SELECT lastval()");
    $row = $result->fetch(PDO::FETCH_NUM);
    return $row ? $row[0] : 0;
}

function db_escape_string($str)
{
    $dbh = db_connect();
    // Remove quotes added by PDO::quote()
    return trim($dbh->quote($str), "'");
}
```

#### 7. Heroku Deployment Files

**Procfile:**
```
web: vendor/bin/heroku-php-apache2 wwwroot/
```

**app.json:**
```json
{
  "name": "Farkle Ten",
  "description": "Online multiplayer Farkle dice game",
  "repository": "https://github.com/yourusername/farkledice",
  "keywords": ["php", "game", "multiplayer", "dice"],
  "addons": [
    {
      "plan": "heroku-postgresql:mini",
      "as": "DATABASE"
    },
    {
      "plan": "heroku-redis:mini",
      "as": "REDIS"
    }
  ],
  "env": {
    "APP_ENV": {
      "description": "Application environment",
      "value": "production"
    },
    "SESSION_SECRET": {
      "description": "Secret key for session encryption",
      "generator": "secret"
    }
  },
  "buildpacks": [
    {
      "url": "heroku/php"
    }
  ]
}
```

**composer.json (Heroku-ready):**
```json
{
  "name": "farkledice/farkle-ten",
  "description": "Farkle Ten multiplayer dice game",
  "type": "project",
  "require": {
    "php": "^8.3",
    "ext-pdo": "*",
    "ext-pdo_pgsql": "*",
    "ext-mbstring": "*",
    "smarty/smarty": "^4.5"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  },
  "autoload": {
    "classmap": ["includes/"]
  }
}
```

**.htaccess (ensure mod_rewrite works on Heroku):**
```apache
RewriteEngine On

# Redirect to HTTPS on Heroku
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Your existing rules...
```

**Update includes/farkleconfig.class.php:**
```php
// Add environment variable support for Heroku
public function __construct()
{
    // Try environment variables first (Heroku)
    if (getenv('DATABASE_URL')) {
        $db = parse_url(getenv('DATABASE_URL'));
        $this->data['dbhost'] = $db['host'];
        $this->data['dbport'] = $db['port'];
        $this->data['dbuser'] = $db['user'];
        $this->data['dbpass'] = $db['pass'];
        $this->data['dbname'] = ltrim($db['path'], '/');
    } else {
        // Fall back to config file (local development)
        $config_file = dirname(__FILE__) . '/../../configs/siteconfig.ini';
        if (file_exists($config_file)) {
            $this->data = parse_ini_file($config_file);
        }
    }
}
```

### Phase 1 Migration Checklist

- [ ] Create `composer.json` with dependencies
- [ ] Update `Dockerfile` for PHP 8.3
- [ ] Update `docker-compose.yml` for PostgreSQL 16
- [ ] Rewrite `dbutil.php` to use PDO
- [ ] Export MySQL schema and data
- [ ] Convert MySQL schema to PostgreSQL
- [ ] Create `docker/init.sql` for PostgreSQL
- [ ] Test all database queries
- [ ] Update `farkleconfig.class.php` for environment variables
- [ ] Create Heroku deployment files (`Procfile`, `app.json`)
- [ ] Test locally with Docker
- [ ] Deploy to Heroku staging environment
- [ ] Run full integration tests
- [ ] Deploy to production

### Expected Performance Improvements

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Docker startup (M1/M2) | 45-60s | 8-12s | **5x faster** |
| Local dev performance | Slow (emulated) | Native | **2-3x faster** |
| Page load time | Baseline | 15-25% faster | PHP 8 JIT |
| Security patches | None (EOL) | Regular updates | ✅ |
| Heroku deployment | Not possible | Native support | ✅ |

---

## Phase 2: Application Modernization

**Timeline:** 3-4 weeks
**Risk Level:** Medium
**Dependencies:** Phase 1 complete

### Goals

- Modern PHP framework for maintainability
- Improved code organization and testability
- Better separation of concerns
- API-first architecture (optional)

### Options

#### Option A: Slim Framework (Lightweight)

**Pros:**
- Minimal learning curve
- Easy to migrate existing code incrementally
- Keeps most of your current structure
- Good for APIs

**Cons:**
- Less built-in functionality
- Need to add your own ORM, validation, etc.

#### Option B: Laravel (Full-Featured)

**Pros:**
- Complete ecosystem (ORM, auth, queues, testing)
- Excellent documentation
- Large community
- Built-in WebSocket support (for real-time game updates)
- Admin panel options (Nova, Filament)

**Cons:**
- Steeper learning curve
- More opinionated structure
- Heavier framework

### Recommended: Laravel Migration Strategy

**Step 1: Run Laravel alongside existing code**
- Install Laravel in `/src/laravel`
- Create API routes that wrap existing PHP functions
- Gradually move business logic to Laravel controllers/models

**Step 2: Create Models**
```php
// app/Models/Game.php
class Game extends Model
{
    protected $table = 'farkle_games';

    public function players()
    {
        return $this->belongsToMany(Player::class, 'farkle_games_players');
    }
}
```

**Step 3: Migrate templates**
- Convert Smarty templates to Blade (Laravel's templating)
- Or keep Smarty (Laravel supports multiple template engines)

**Step 4: Add modern authentication**
```php
// Laravel Sanctum for API tokens
// Or Laravel Breeze for traditional auth
```

**Step 5: WebSockets for real-time gameplay**
```php
// Use Laravel Broadcasting + Pusher or Soketi
// Replace polling with WebSocket events
broadcast(new GameUpdated($game));
```

### Phase 2 Changes

1. **Install Laravel:** `composer create-project laravel/laravel src/laravel`
2. **Create database migrations** from existing schema
3. **Build Eloquent models** for game entities
4. **Create API routes** that wrap existing functions
5. **Add authentication** (Sanctum or Breeze)
6. **Write tests** for game logic
7. **Gradually migrate pages** to Laravel controllers
8. **Add Redis caching** for leaderboards/stats
9. **Implement queue system** for background jobs (tournaments, cleanup)

---

## Phase 3: Frontend Modernization

**Timeline:** 4-6 weeks
**Risk Level:** High
**Dependencies:** Phase 2 complete

### Goals

- Modern JavaScript framework (React/Vue/Svelte)
- Real-time game updates via WebSockets
- Mobile-first responsive design
- Progressive Web App (PWA) capabilities

### Stack Options

#### Option A: Vue.js + Inertia
- Integrates perfectly with Laravel
- Can migrate page-by-page
- SSR support
- Keeps some backend rendering

#### Option B: React SPA
- Popular, large ecosystem
- Fully decoupled frontend
- Better for mobile apps later
- More complex setup

#### Option C: Keep jQuery, Modernize Build
- Bundle with Vite
- TypeScript for game logic
- Keep existing structure
- Lowest risk

### Recommended: Vue.js + Inertia (Incremental)

**Benefits:**
- Migrate one page at a time
- Keep Laravel backend
- Modern reactive UI
- TypeScript support
- Can still use some Blade templates

**Example Migration:**
```vue
<!-- resources/js/Pages/Game.vue -->
<template>
  <div class="game-board">
    <DiceRoller
      :dice="game.current_dice"
      @roll="handleRoll"
    />
    <ScoreBoard :players="game.players" />
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'

const props = defineProps<{
  game: Game
}>()

const handleRoll = () => {
  router.post('/api/game/roll', {
    game_id: props.game.id
  })
}
</script>
```

---

## Phase 4: DevOps & Monitoring

**Timeline:** 1-2 weeks
**Risk Level:** Low

### CI/CD Pipeline

**GitHub Actions workflow:**
```yaml
name: Deploy to Heroku

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - run: ./vendor/bin/phpunit

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: akhileshns/heroku-deploy@v3.12.12
        with:
          heroku_api_key: ${{secrets.HEROKU_API_KEY}}
          heroku_app_name: "farkle-ten"
          heroku_email: "your-email@example.com"
```

### Monitoring & Logging

1. **Heroku Add-ons:**
   - Papertrail (logging)
   - New Relic (APM)
   - Sentry (error tracking)

2. **Application logging:**
```php
// Use Monolog (included in Laravel)
Log::info('Game started', ['game_id' => $gameId]);
Log::error('Game error', ['error' => $exception->getMessage()]);
```

3. **Performance monitoring:**
   - Track game creation time
   - Monitor dice roll performance
   - Alert on slow database queries

---

## Migration Timeline

### Week 1-2: Phase 1 Foundation
- Day 1-2: Set up Composer, update Docker config
- Day 3-4: Migrate dbutil.php to PDO
- Day 5-7: MySQL → PostgreSQL schema conversion
- Day 8-10: Test all game functions, fix compatibility issues

### Week 3: Heroku Deployment
- Day 1-2: Create Heroku app, configure DATABASE_URL
- Day 3-4: Deploy and test on staging
- Day 5: Performance testing and optimization

### Week 4: Phase 1 Production Deploy
- Code freeze
- Final testing
- Database backup and migration
- Production deployment
- Monitoring and rollback plan

### Week 5-8: Phase 2 (Optional)
- Install Laravel
- Create models and migrations
- Build API endpoints
- Migrate authentication

---

## Rollback Plan

### Pre-migration
1. **Full database backup:**
```bash
pg_dump -h localhost -U farkle_user farkle_db > backup_$(date +%Y%m%d).sql
```

2. **Code snapshot:**
```bash
git tag pre-modernization-$(date +%Y%m%d)
git push --tags
```

3. **Docker image backup:**
```bash
docker save farkle_web:latest > farkle_web_backup.tar
```

### Rollback Procedure
1. Revert git to previous tag
2. Restore database from backup
3. Restart Docker containers
4. Verify functionality

**Estimated rollback time:** 15-30 minutes

---

## Testing Strategy

### Phase 1 Testing

**Unit Tests:**
```php
// tests/Unit/DatabaseTest.php
class DatabaseTest extends TestCase
{
    public function test_database_connection()
    {
        $dbh = db_connect();
        $this->assertInstanceOf(PDO::class, $dbh);
    }

    public function test_query_execution()
    {
        $result = db_select_query("SELECT 1 as test", SQL_SINGLE_VALUE);
        $this->assertEquals(1, $result);
    }
}
```

**Integration Tests:**
```php
// tests/Integration/GameTest.php
class GameTest extends TestCase
{
    public function test_create_game()
    {
        $gameId = createGame($playerId, GAME_MODE_STANDARD);
        $this->assertGreaterThan(0, $gameId);

        $game = getGameById($gameId);
        $this->assertEquals(GAME_MODE_STANDARD, $game['mode']);
    }

    public function test_roll_dice()
    {
        $gameId = createGame($playerId, GAME_MODE_STANDARD);
        $result = rollDice($gameId);
        $this->assertCount(6, $result['dice']);
    }
}
```

**Manual Testing Checklist:**
- [ ] User registration and login
- [ ] Game creation (all modes)
- [ ] Dice rolling and scoring
- [ ] Turn management
- [ ] Friend system
- [ ] Leaderboards
- [ ] Tournaments
- [ ] Achievements
- [ ] Mobile/tablet views

---

## Cost Estimate

### Development Time

| Phase | Time | Notes |
|-------|------|-------|
| Phase 1 | 3-5 days | Can be done over a weekend |
| Phase 2 | 3-4 weeks | Part-time (evenings) |
| Phase 3 | 4-6 weeks | Part-time (evenings) |
| Phase 4 | 1-2 weeks | Ongoing optimization |

### Heroku Costs (Monthly)

| Service | Plan | Cost |
|---------|------|------|
| Web Dyno | Eco | $5/month |
| PostgreSQL | Mini | $5/month |
| Redis | Mini | $3/month |
| **Total** | | **$13/month** |

For production with more traffic:
- Standard-1X dyno: $25/month
- PostgreSQL Standard-0: $50/month
- Redis Premium-0: $15/month
- **Total:** ~$90/month

### Comparison to Current

| | Current (Shared hosting?) | Heroku (Modern) |
|-|---------------------------|-----------------|
| Security | Vulnerable (PHP 5.6) | Up-to-date |
| Performance | Unknown | Auto-scaling |
| Deployment | Manual FTP? | Git push |
| Monitoring | None | Built-in |
| Backups | Manual | Automated |
| SSL | ? | Included |

---

## Success Metrics

### Phase 1 Goals

- [ ] Docker starts in <15s on Apple Silicon
- [ ] All game functions work with PostgreSQL
- [ ] Can deploy to Heroku with `git push heroku main`
- [ ] Page load time improved by >15%
- [ ] Zero PHP deprecation warnings
- [ ] 100% test coverage for database layer

### Phase 2 Goals

- [ ] API response time <100ms (p95)
- [ ] Code coverage >70%
- [ ] All business logic in testable classes
- [ ] Zero direct SQL queries in templates

### Phase 3 Goals

- [ ] Lighthouse score >90
- [ ] Real-time updates <500ms latency
- [ ] Mobile-friendly (100% responsive)
- [ ] PWA installable

---

## Next Steps

### Immediate (This Week)

1. **Backup everything:**
   ```bash
   git tag pre-modernization
   mysqldump mikeschm_db > backup.sql
   ```

2. **Create feature branch:**
   ```bash
   git checkout -b modernization/phase-1
   ```

3. **Set up Composer:**
   ```bash
   composer init
   composer require smarty/smarty:^4.5
   ```

4. **Update Docker files** (see Phase 1 details above)

5. **Test locally:**
   ```bash
   docker-compose down -v
   docker-compose up --build
   ```

### This Month

- Complete Phase 1 migration
- Deploy to Heroku staging
- Run integration tests
- Deploy to production (with rollback plan ready)

### This Quarter

- Evaluate Phase 2 (Laravel migration)
- Set up CI/CD pipeline
- Implement monitoring and logging

---

## Questions & Decisions Needed

1. **Do you have the MySQL schema?**
   - Need to export existing schema for PostgreSQL conversion
   - Or we can reverse-engineer from SHOW CREATE TABLE

2. **Current hosting setup?**
   - Where is it currently deployed?
   - How is deployment currently done?
   - Any existing monitoring/logging?

3. **Traffic expectations?**
   - Current daily active users?
   - Peak concurrent games?
   - Helps size Heroku dynos

4. **Timeline preference?**
   - Weekend sprint for Phase 1?
   - Or gradual migration over 2-3 weeks?

5. **Budget approval?**
   - Heroku costs ~$13/month (dev) to $90/month (production)
   - Any constraints?

---

## Resources

### Documentation
- [PHP 8.3 Migration Guide](https://www.php.net/manual/en/migration83.php)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/16/)
- [PDO Tutorial](https://www.php.net/manual/en/book.pdo.php)
- [Heroku PHP Support](https://devcenter.heroku.com/articles/getting-started-with-php)
- [Laravel Documentation](https://laravel.com/docs)

### Tools
- [MySQL to PostgreSQL converter](https://github.com/lanyrd/mysql-postgresql-converter)
- [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) - Code style
- [PHPStan](https://phpstan.org/) - Static analysis
- [Postgres.app](https://postgresapp.com/) - Local PostgreSQL for Mac

### Community
- [Laravel Discord](https://discord.gg/laravel)
- [PostgreSQL Slack](https://postgres-slack.herokuapp.com/)
- [PHP Reddit](https://reddit.com/r/php)

---

## Conclusion

**Recommended Path:**

1. **Start with Phase 1** (this weekend if possible)
   - Gets you modern, secure, fast development environment
   - Low risk, high reward
   - Unblocks Heroku deployment

2. **Evaluate Phase 2 after Phase 1 success**
   - See how much the PHP 8.3 improvements help
   - Decide if Laravel migration is worth the effort
   - May not be needed if just maintaining (vs. adding features)

3. **Phase 3 only if you want to grow**
   - Modern frontend is optional
   - Current jQuery approach works fine
   - Only needed for mobile app or major UX overhaul

**You can stop after Phase 1 and have a perfectly modern, secure, maintainable application.**

The key is getting off PHP 5.6 and onto a supported stack that runs natively on Apple Silicon and deploys to Heroku. Everything else is optimization.
