# Best Practice Updates Plan

**Analysis Date:** 2026-02-01
**Current Version:** 2.7.3
**Tech Stack:** PHP 8.3 + Smarty 4.5 + PostgreSQL + JavaScript

---

## Executive Summary

This document analyzes the Farkle Ten codebase for modern development practices and identifies opportunities for improvement. The application is functional and actively maintained but has grown organically without architectural planning, resulting in:

- **95% procedural code** with only 3 classes
- **Smarty features underutilized** (no template inheritance, caching disabled)
- **No parameterized queries** at application level (SQL injection risk)
- **<1% test coverage** (only one integration test)
- **10+ global variables** creating tight coupling

---

## Part 1: Smarty Template Opportunities

### Current State

| Feature | Status | Notes |
|---------|--------|-------|
| Template Inheritance | Not Used | Flat include structure |
| Blocks | Not Used | No reusable content sections |
| Custom Plugins/Modifiers | Not Used | No custom formatters |
| Caching | Disabled | Could cache static sections |
| Config Files | Configured but empty | Could centralize constants |

### Key Problems

1. **Large Monolithic Template** - `farkle.tpl` includes 13 modules with no hierarchy
2. **Inline CSS/JS** - `{literal}` blocks scattered in templates
3. **Hidden Input Anti-Pattern** - JSON data embedded in hidden inputs for JS
4. **Repetitive Device Logic** - Same mobile/tablet conditionals repeated 6+ times

### Recommendations

#### Quick Wins (Low Risk)

**1. Enable Caching for Static Sections**
```php
// baseutil.php - Cache header/footer (estimated 30-50% faster initial render)
$smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
$smarty->cache_lifetime = 3600; // 1 hour for static content
```

**2. Extract Inline CSS to External Files**
- Move `{literal}` CSS blocks from templates to `/wwwroot/css/`
- Already have infrastructure: `mobile.css`, `farkle.css` loaded in header.tpl

**3. Create Custom Modifiers**
```php
// Create plugins/modifier.formatScore.php
function smarty_modifier_formatScore($score) {
    return number_format($score);
}
// Usage: {$player.score|formatScore}
```

#### Medium-Term (Maintainability)

**4. Implement Template Inheritance**
```smarty
{* templates/base.tpl *}
<!DOCTYPE html>
<html>
<head>{block name="head"}{include 'partials/head.tpl'}{/block}</head>
<body>
    {block name="header"}{include 'partials/header.tpl'}{/block}
    {block name="content"}{/block}
    {block name="footer"}{include 'partials/footer.tpl'}{/block}
</body>
</html>

{* templates/farkle.tpl *}
{extends 'base.tpl'}
{block name="content"}
    {include 'farkle_div_lobby.tpl'}
    {include 'farkle_div_game.tpl'}
{/block}
```

**5. Replace Hidden Input Pattern with JSON Data Blocks**
```smarty
{* Before (anti-pattern) *}
<input type="hidden" value="{$lobbyinfo}" id="m_lobbyInfo">

{* After *}
<script type="application/json" id="gameData">
{$gameData|json_encode}
</script>
```

**6. Create Reusable Component Templates**
```smarty
{* templates/components/player_card.tpl *}
<div class="player-card {$cardcolor}">
    <span class="username">{$username}</span>
    <span class="score">{$score|formatScore}</span>
</div>

{* Usage *}
{include 'components/player_card.tpl' username=$player.username score=$player.score}
```

#### Long-Term (Architecture)

**7. Use Config Files for Constants**
```ini
; /backbone/configs/game.conf
POINTS_TO_WIN = 10000
MAX_PLAYERS = 4
ROUND_LIMIT = 10
```
```smarty
{config_load file="game.conf"}
{#POINTS_TO_WIN#}
```

---

## Part 2: PHP Architecture Opportunities

### Current State

| Metric | Current | Target | Notes |
|--------|---------|--------|-------|
| Architecture | Procedural | Service-oriented | 95%+ procedural functions |
| Classes | 3 | 20+ | FarkleConfig, DatabaseSessionHandler, Player |
| Functions | 173 | Consolidated into services | Spread across 25 files |
| Global Variables | 10+ | 0-2 | Major coupling issue |
| Type Hints | ~5% | 90%+ | PHP 8.3 supports strict |
| Parameterized Queries | ~20% | 100% | Security critical |
| Test Coverage | <1% | 70%+ | Only 1 integration test |

### Key Problems

1. **Monolithic Action Dispatch** - `farkle_fetch.php` has 40+ action handlers in if/elseif chain
2. **SQL Injection Risk** - String interpolation in queries throughout codebase
3. **No Service Layer** - Business logic mixed with HTTP routing
4. **Code Duplication** - Bot implementations duplicated (~1,500 lines)
5. **No CSRF Protection** - Forms lack token validation

### Recommendations

#### High Priority (Security + Maintainability)

**1. Implement Prepared Statement Wrapper**

Current risk: SQL injection if input validation fails anywhere.

```php
// Add to dbutil.php
function db_prepared_query(string $sql, array $params = []): PDOStatement {
    $dbh = db_connect();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_prepared_select(string $sql, array $params, int $mode = SQL_MULTI_ROW) {
    $stmt = db_prepared_query($sql, $params);
    // ... return based on mode
}

// Usage (replace string interpolation):
// Before:
$sql = "SELECT * FROM farkle_players WHERE playerid = $playerid";

// After:
$sql = "SELECT * FROM farkle_players WHERE playerid = :playerid";
$result = db_prepared_select($sql, [':playerid' => $playerid], SQL_SINGLE_ROW);
```

**Timeline:** 2-3 weeks (affects 25+ files)

**2. Add CSRF Protection**

```php
// Add to baseutil.php
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// In templates:
<input type="hidden" name="csrf_token" value="{$csrf_token}">

// In farkle_fetch.php:
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    die(json_encode(['Error' => 'Invalid request']));
}
```

**3. Add Type Hints to Public APIs**

```php
// Before:
function CreateGameWithPlayers($players, $whostarted=0, $expireDays = 2)

// After:
function CreateGameWithPlayers(
    array $players,
    int $whostarted = 0,
    int $expireDays = 2
): int
```

#### Medium Priority (Scalability)

**4. Create Service Layer**

```
/includes/services/
├── GameService.php
├── PlayerService.php
├── AchievementService.php
└── LeaderboardService.php
```

```php
// includes/services/GameService.php
class GameService {
    public function createGame(array $players, int $whoStarted, int $gameMode): int
    public function rollDice(int $gameId, int $playerId): array
    public function bankScore(int $gameId, int $playerId): array
    public function endGame(int $gameId): void
}

// farkle_fetch.php becomes thin dispatcher:
$gameService = new GameService();
if ($action === 'startgame') {
    $result = $gameService->createGame($players, $whoStarted, $gameMode);
}
```

**5. Implement Repository Pattern**

```php
// includes/repositories/GameRepository.php
class GameRepository {
    public function create(array $data): int
    public function findById(int $id): ?array
    public function updateScore(int $gameId, int $playerId, int $score): bool
    public function findActiveByPlayer(int $playerId): array
}
```

**6. Consolidate Bot Implementations**

Current duplication: `farkleBotAI.php` (972 lines) + `farkleBotAI_Claude.php` (953 lines)

```php
// Create strategy pattern
interface BotStrategy {
    public function decideDice(array $gameState): array;
    public function shouldBank(array $gameState): bool;
}

class RandomBotStrategy implements BotStrategy { ... }
class ClaudeAIBotStrategy implements BotStrategy { ... }

// Single BotTurn class uses strategy
class BotTurn {
    public function __construct(private BotStrategy $strategy) {}
    public function play(int $gameId): void { ... }
}
```

**Expected reduction:** ~1,500 lines of duplicate code

#### Lower Priority (Quality of Life)

**7. Implement Structured Logging**

```php
// Replace ad-hoc error_log() calls
class Logger {
    public static function info(string $message, array $context = []): void
    public static function error(string $message, array $context = []): void
    public static function debug(string $message, array $context = []): void
}

// Usage:
Logger::info('Game created', ['gameid' => $id, 'players' => count($players)]);
```

**8. Add Input Validation Framework**

```php
class Validator {
    public static function validateGameCreate(array $input): ValidationResult {
        $errors = [];
        if (empty($input['players'])) {
            $errors[] = 'Players required';
        }
        if (count($input['players']) > 4) {
            $errors[] = 'Maximum 4 players';
        }
        return new ValidationResult($errors);
    }
}
```

**9. Eliminate Global Variables**

```php
// Create AppContainer singleton
class App {
    private static ?App $instance = null;
    private PDO $db;
    private array $config;
    private bool $debug;

    public static function getInstance(): App { ... }
    public function getDb(): PDO { ... }
    public function getConfig(): array { ... }
}

// Usage (instead of global $g_dbh):
$db = App::getInstance()->getDb();
```

---

## Part 3: Testing Improvements

### Current State

- 1 integration test (`test/api_game_flow_test.php`)
- PHPUnit 10.0 in composer (unused)
- No unit tests for core logic
- No database test strategy

### Recommendations

**1. Set Up PHPUnit Properly**

```php
// phpunit.xml
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

**2. Add Unit Tests for Core Logic**

```php
// tests/Unit/DiceScoringTest.php
class DiceScoringTest extends TestCase {
    public function testThreeOfAKind(): void {
        $dice = [1, 1, 1, 2, 3, 4];
        $score = ScoreDice($dice);
        $this->assertEquals(1000, $score);
    }

    public function testStraight(): void {
        $dice = [1, 2, 3, 4, 5, 6];
        $score = ScoreDice($dice);
        $this->assertEquals(1500, $score);
    }
}
```

**3. Create Test Database Strategy**

```php
// tests/TestCase.php
abstract class TestCase extends \PHPUnit\Framework\TestCase {
    protected function setUp(): void {
        // Use test database
        putenv('DATABASE_URL=postgresql://test:test@localhost/farkle_test');

        // Run migrations
        $this->runMigrations();
    }

    protected function tearDown(): void {
        // Rollback or truncate tables
        $this->truncateTables();
    }
}
```

---

## Implementation Roadmap

### Phase 1: Foundation (Weeks 1-2)
- [ ] Add prepared statement wrapper to dbutil.php
- [ ] Add CSRF protection
- [ ] Enable Smarty caching for header/footer
- [ ] Extract inline CSS to external files

### Phase 2: Type Safety (Weeks 3-4)
- [ ] Add type hints to farkleGameFuncs.php (most critical)
- [ ] Add type hints to farkleLogin.php
- [ ] Create custom Smarty modifiers (formatScore, formatTime)
- [ ] Set up PHPUnit properly

### Phase 3: Service Layer (Weeks 5-8)
- [ ] Create GameService class
- [ ] Create PlayerService class
- [ ] Refactor farkle_fetch.php to use services
- [ ] Add unit tests for services

### Phase 4: Repository Pattern (Weeks 9-12)
- [ ] Create GameRepository class
- [ ] Create PlayerRepository class
- [ ] Migrate direct SQL to repositories
- [ ] Implement template inheritance in Smarty

### Phase 5: Advanced (Weeks 13+)
- [ ] Consolidate bot implementations
- [ ] Implement event system for achievements
- [ ] Add comprehensive test suite
- [ ] Consider framework migration for new features

---

## Quick Reference: What's Available But Not Used

### Smarty 4.5 Features

| Feature | Benefit | Effort |
|---------|---------|--------|
| `{extends}` / `{block}` | Better template organization | Medium |
| `{cache}` | Faster page loads | Low |
| Custom modifiers | DRY formatting | Low |
| Config files | Centralized constants | Low |
| Output filters | Minify HTML/CSS | Medium |

### PHP 8.3 Features

| Feature | Benefit | Effort |
|---------|---------|--------|
| Type hints | Catch bugs early | Medium |
| Attributes | Replace doc annotations | Low |
| Named arguments | Clearer function calls | Low |
| Enums | Replace magic constants | Medium |
| Readonly classes | Immutable value objects | Medium |

### PDO Features

| Feature | Benefit | Effort |
|---------|---------|--------|
| Prepared statements | SQL injection prevention | High (many files) |
| Named parameters | Clearer queries | Low (with prepared) |
| Transactions | Data integrity | Medium |
| Error modes | Better debugging | Low |

---

## Summary

The codebase has a solid foundation but significant technical debt. The highest-impact improvements are:

1. **Security:** Prepared statements (prevents SQL injection)
2. **Maintainability:** Service layer (separates concerns)
3. **Performance:** Smarty caching (faster renders)
4. **Quality:** Type hints + tests (catch bugs early)

Start with security fixes, then gradually refactor toward better architecture.
