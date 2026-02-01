# Eliminate Global Variables Implementation Plan

**Priority:** Medium
**Estimated Effort:** 4-5 weeks
**Risk Level:** Medium

---

## Executive Summary

This plan details migrating from PHP global variables to a modern Application Container pattern. The codebase currently uses 10+ global variables creating tight coupling and making unit testing impossible.

---

## Current Global Variable Inventory

### Core Infrastructure (includes/baseutil.php)

| Variable | Type | Purpose |
|----------|------|---------|
| `$g_debug` | int | Debug level (0-31) |
| `$g_json` | int | JSON output mode flag |
| `$g_flushcache` | int | Cache flush flag |
| `$gMobileMode` | int | Mobile device flag |
| `$gTabletMode` | int | Tablet device flag |
| `$gFolder` | string | Working directory name |
| `$smarty` | Smarty | Template engine instance |

### Database (includes/dbutil.php)

| Variable | Type | Purpose |
|----------|------|---------|
| `$g_dbh` | PDO | Database connection |

### Feature-Specific

| Variable | File | Purpose |
|----------|------|---------|
| `$gEmailEnabled` | farklePageFuncs.php | Email toggle |
| `$gTitles` | farklePageFuncs.php | Player titles |
| `$g_leaderboardDirty` | farkleLeaderboard.php | Cache invalidation |

---

## Solution: Application Container Pattern

### App Container Class

```php
<?php
// File: includes/App.php

class App
{
    private static ?App $instance = null;

    // Services (lazy-loaded)
    private ?Config $config = null;
    private ?Database $db = null;
    private ?Debug $debug = null;
    private ?DeviceDetector $device = null;
    private ?Template $template = null;

    // Runtime state
    private bool $leaderboardDirty = false;
    private bool $emailEnabled = true;

    private function __construct() {}

    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function config(): Config
    {
        return $this->config ??= new Config();
    }

    public function db(): Database
    {
        return $this->db ??= new Database($this->config());
    }

    public function debug(): Debug
    {
        return $this->debug ??= new Debug();
    }

    public function device(): DeviceDetector
    {
        return $this->device ??= new DeviceDetector();
    }

    public function template(): Template
    {
        return $this->template ??= new Template($this->config());
    }

    // Runtime state
    public function isLeaderboardDirty(): bool { return $this->leaderboardDirty; }
    public function setLeaderboardDirty(bool $dirty): void { $this->leaderboardDirty = $dirty; }
    public function isEmailEnabled(): bool { return $this->emailEnabled; }

    // Testing support
    public static function resetForTesting(): void { self::$instance = null; }
    public function setDatabase(Database $db): void { $this->db = $db; }
}

// Global convenience function
function app(): App
{
    return App::getInstance();
}
```

---

## Service Classes

### Config Class

```php
<?php
class Config
{
    private array $data = [];
    private bool $isHeroku;

    public function __construct()
    {
        $this->isHeroku = (getenv('DATABASE_URL') !== false);
        $this->loadConfiguration();
    }

    private function loadConfiguration(): void
    {
        // Priority: DATABASE_URL → env vars → config file
        if ($databaseUrl = getenv('DATABASE_URL')) {
            $db = parse_url($databaseUrl);
            $this->data['db'] = [
                'host' => $db['host'],
                'port' => $db['port'] ?? 5432,
                'user' => $db['user'],
                'pass' => $db['pass'],
                'name' => ltrim($db['path'], '/'),
            ];
        }
        // ... other sources
    }

    public function get(string $key, $default = null)
    {
        // Supports dot notation: 'db.host'
    }
}
```

### Database Class

```php
<?php
class Database
{
    private ?PDO $pdo = null;
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function connection(): PDO
    {
        return $this->pdo ??= $this->createConnection();
    }

    public function selectSingle(string $sql) { ... }
    public function selectRow(string $sql): ?array { ... }
    public function selectAll(string $sql): array { ... }
    public function execute(string $sql): int { ... }
}
```

### Debug Class

```php
<?php
class Debug
{
    private int $level = 0;
    private bool $jsonMode = false;

    public function __construct()
    {
        $this->level = (int)($_REQUEST['debug'] ?? 0);
    }

    public function getLevel(): int { return $this->level; }
    public function setLevel(int $level): void { $this->level = $level; }

    public function log(string $msg, int $debugLevel = 1, string $color = '#ff22ff'): void
    {
        if ($this->level >= $debugLevel && !$this->jsonMode) {
            echo "<pre style='color: $color;'>$msg</pre>";
        }
    }

    public function error(string $msg): void
    {
        error_log($msg);
        $this->log($msg, 1, 'red');
    }
}
```

### DeviceDetector Class

```php
<?php
class DeviceDetector
{
    private bool $isMobile = false;
    private bool $isTablet = false;

    public function __construct()
    {
        $this->detect();
    }

    private function detect(): void
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($userAgent, 'iPhone') !== false ||
            stripos($userAgent, 'Mobile') !== false) {

            if (stripos($userAgent, 'iPad') !== false) {
                $this->isTablet = true;
            } else {
                $this->isMobile = true;
            }
        }

        // Allow override via request params
        if (!empty($_REQUEST['mobilemode'])) $this->isMobile = true;
        if (!empty($_REQUEST['tabletmode'])) $this->isTablet = true;
    }

    public function isMobile(): bool { return $this->isMobile; }
    public function isTablet(): bool { return $this->isTablet; }
}
```

---

## Migration Strategy

### Phase 1: Create Compatibility Layer (Week 1)

Modify `baseutil.php` to use App while still exposing globals:

```php
<?php
require_once(__DIR__ . '/App.php');

$app = App::getInstance();

// Backward compatibility during migration
$g_debug = $app->debug()->getLevel();
$gMobileMode = $app->device()->isMobile() ? 1 : 0;
$gTabletMode = $app->device()->isTablet() ? 1 : 0;
$smarty = $app->template()->getSmarty();

// Legacy function wrappers
function BaseUtil_Debug($msg, $debuglevel = 1, $color = '#ff22ff')
{
    app()->debug()->log($msg, $debuglevel, $color);
}

function BaseUtil_Error($msg)
{
    app()->debug()->error($msg);
}
```

### Phase 2: Migrate Feature Files (Weeks 2-3)

**farkleLeaderboard.php:**

Before:
```php
global $g_leaderboardDirty;
if (!$g_leaderboardDirty) {
    return $_SESSION['farkle']['lb'];
}
```

After:
```php
if (!app()->isLeaderboardDirty()) {
    return $_SESSION['farkle']['lb'];
}
```

**farkleGameFuncs.php:**

Before:
```php
function FarkleWinGame($gameid, $winnerid)
{
    global $g_leaderboardDirty;
    $g_leaderboardDirty = 1;
}
```

After:
```php
function FarkleWinGame($gameid, $winnerid)
{
    app()->setLeaderboardDirty(true);
}
```

### Phase 3: Remove Global References (Week 4)

Search and replace all `global $g_*` statements:
```bash
grep -rn "global \$g_" --include="*.php" wwwroot/ includes/
```

### Phase 4: Cleanup (Week 5)

1. Remove global variable declarations
2. Update CLAUDE.md documentation
3. Add PHPDoc to service classes

---

## Before/After Examples

### Debug Output

**Before:**
```php
function FarkleNewGame($thePlayers, ...)
{
    global $g_debug;
    BaseUtil_Debug(__FUNCTION__ . ": New game...", 14);

    if ($g_debug >= 14) {
        var_dump($players);
    }
}
```

**After:**
```php
function FarkleNewGame($thePlayers, ...)
{
    app()->debug()->log(__FUNCTION__ . ": New game...", 14);

    if (app()->debug()->getLevel() >= 14) {
        var_dump($players);
    }
}
```

### Database Query

**Before:**
```php
function GetStats($playerid)
{
    $sql = "SELECT * FROM farkle_players WHERE playerid='$playerid'";
    $stats = db_select_query($sql, SQL_SINGLE_ROW);
    return $stats;
}
```

**After:**
```php
function GetStats($playerid)
{
    $sql = "SELECT * FROM farkle_players WHERE playerid='$playerid'";
    return app()->db()->selectRow($sql);
}
```

---

## Testability Improvements

### Current: Untestable

```php
// Requires actual database, cannot mock
function GetPlayerInfo($playerid) {
    global $g_dbh;
    $sql = "SELECT * FROM farkle_players WHERE playerid=$playerid";
    // ...
}
```

### With App Container: Fully Testable

```php
class GameFuncsTest extends TestCase
{
    protected function setUp(): void
    {
        App::resetForTesting();

        $mockDb = $this->createMock(Database::class);
        $mockDb->method('selectRow')
            ->willReturn(['playerid' => 1, 'username' => 'testuser']);

        app()->setDatabase($mockDb);
    }

    public function testGetPlayerInfo(): void
    {
        $result = GetPlayerInfo(1);
        $this->assertEquals('testuser', $result['username']);
    }
}
```

---

## Success Criteria

1. Zero `global $` statements in production code
2. All functionality preserved (verified by regression tests)
3. Unit tests can mock database and services
4. Code more readable with explicit dependencies
