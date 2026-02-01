# Service Layer Architecture Implementation Plan

**Priority:** Medium-High
**Estimated Effort:** 6-8 weeks
**Risk Level:** Medium

---

## Executive Summary

This plan introduces a service layer to the Farkle Ten codebase, extracting business logic from `farkle_fetch.php` and procedural files into organized service classes. This will separate HTTP routing from business logic, enable unit testing, and reduce the 40+ action if/elseif chain.

---

## Current Architecture Problems

- **95% procedural code** with only 3 classes
- **173 functions** spread across 25 files
- **40+ action handlers** in a single if/elseif chain
- No separation between routing and business logic
- Untestable without full HTTP stack

---

## Proposed Directory Structure

```
includes/
├── services/
│   ├── GameService.php          # Core game operations
│   ├── PlayerService.php        # Player management & stats
│   ├── AchievementService.php   # Achievement logic
│   ├── FriendService.php        # Friend management
│   ├── LeaderboardService.php   # Leaderboard operations
│   └── AuthService.php          # Login/session management
├── repositories/
│   ├── GameRepository.php       # Game database operations
│   ├── PlayerRepository.php     # Player database operations
│   └── AchievementRepository.php
├── exceptions/
│   ├── GameException.php
│   ├── ValidationException.php
│   └── AuthException.php
└── ServiceContainer.php         # Simple dependency injection
```

---

## GameService Class Design

```php
<?php
declare(strict_types=1);

class GameService
{
    public const MODE_STANDARD = 1;
    public const MODE_10ROUND = 2;
    public const WITH_RANDOM = 0;
    public const WITH_FRIENDS = 1;
    public const WITH_SOLO = 2;

    private GameRepository $gameRepo;
    private PlayerRepository $playerRepo;
    private AchievementService $achievementService;

    public function __construct(
        GameRepository $gameRepo,
        PlayerRepository $playerRepo,
        AchievementService $achievementService
    ) {
        $this->gameRepo = $gameRepo;
        $this->playerRepo = $playerRepo;
        $this->achievementService = $achievementService;
    }

    public function createGame(
        array $playerIds,
        int $whoStarted,
        int $gameMode = self::MODE_10ROUND,
        int $gameWith = self::WITH_FRIENDS
    ): array;

    public function rollDice(int $playerId, int $gameId, array $savedDice): array;
    public function bankScore(int $playerId, int $gameId, array $savedDice): array;
    public function getGameUpdate(int $playerId, int $gameId): array;
    public function quitGame(int $playerId, int $gameId): bool;
    public function checkGameCompletion(int $gameId): bool;
}
```

### Method Mapping

| Current Function | New Service Method |
|-----------------|-------------------|
| `FarkleNewGame()` | `GameService::createGame()` |
| `FarkleRoll()` | `GameService::rollDice()` |
| `FarklePass()` | `GameService::bankScore()` |
| `FarkleSendUpdate()` | `GameService::getGameUpdate()` |
| `FarkleQuitGame()` | `GameService::quitGame()` |

---

## PlayerService Class Design

```php
<?php
class PlayerService
{
    public function getPlayerStats(int $playerId): array;
    public function getPlayerInfo(int $playerId): array;
    public function getLobbyInfo(int $playerId): array;
    public function saveOptions(int $playerId, string $email, bool $sendEmails): bool;
    public function updateTitle(int $playerId, int $titleId): bool;
    public function awardXP(int $playerId, int $amount): ?array;
    public function recordWin(int $playerId): void;
    public function recordLoss(int $playerId): void;
}
```

---

## ServiceContainer (Simple DI)

```php
<?php
class ServiceContainer
{
    private static ?ServiceContainer $instance = null;
    private array $services = [];
    private PDO $db;

    private function __construct()
    {
        $this->db = db_connect();
        $this->registerServices();
    }

    public static function getInstance(): ServiceContainer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerServices(): void
    {
        // Repositories
        $this->services[GameRepository::class] = fn() => new GameRepository($this->db);
        $this->services[PlayerRepository::class] = fn() => new PlayerRepository($this->db);

        // Services
        $this->services[AchievementService::class] = fn() =>
            new AchievementService($this->get(AchievementRepository::class));

        $this->services[GameService::class] = fn() =>
            new GameService(
                $this->get(GameRepository::class),
                $this->get(PlayerRepository::class),
                $this->get(AchievementService::class)
            );
    }

    public function get(string $className): object
    {
        $factory = $this->services[$className];
        if ($factory instanceof Closure) {
            $this->services[$className] = $factory();
        }
        return $this->services[$className];
    }
}
```

---

## Refactored farkle_fetch.php

Transform 280-line if/elseif chain into thin dispatcher:

```php
<?php
declare(strict_types=1);

use Farkle\ServiceContainer;
use Farkle\Exceptions\ValidationException;
use Farkle\Exceptions\GameException;

$container = ServiceContainer::getInstance();
$game = $container->get(GameService::class);
$player = $container->get(PlayerService::class);

$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    $result = match($action) {
        // Auth (no session required)
        'login' => $auth->login($_POST['user'], $_POST['pass']),
        'register' => $auth->register($_POST['user'], $_POST['pass'], $_POST['email']),

        // Authenticated actions
        'getlobbyinfo' => $player->getLobbyInfo($playerId),
        'startgame' => $game->createGame(
            json_decode($_POST['players'], true),
            $playerId,
            (int)($_POST['gamemode'] ?? 2)
        ),
        'farkleroll' => $game->rollDice($playerId, (int)$_POST['gameid'],
            json_decode($_POST['saveddice'], true)),
        'farklepass' => $game->bankScore($playerId, (int)$_POST['gameid'],
            json_decode($_POST['saveddice'], true)),

        default => throw new ValidationException("Unknown action")
    };

    echo json_encode($result);

} catch (ValidationException $e) {
    echo json_encode(['Error' => $e->getMessage()]);
} catch (GameException $e) {
    echo json_encode(['Error' => $e->getMessage()]);
}
```

**Reduction:** ~280 lines → ~120 lines

---

## Migration Strategy

### Phase 1: Foundation (Week 1-2)
1. Create directory structure
2. Create ServiceContainer
3. Create empty service classes with interfaces
4. Add Composer PSR-4 autoloading

### Phase 2: AchievementService (Week 3)
- Smallest service with clear boundaries
- Minimal dependencies
- Easy to test independently

### Phase 3: PlayerService (Week 4)
1. `getPlayerInfo()` - simple read
2. `getPlayerStats()`
3. `getLobbyInfo()`
4. XP/leveling methods

### Phase 4: GameService (Weeks 5-7)
**Week 5:** Game creation
**Week 6:** Turn operations (roll, bank)
**Week 7:** Game lifecycle (completion, overtime)

### Phase 5: Remaining Services (Week 8)
- FriendService
- LeaderboardService
- AuthService

---

## Testing Benefits

### Current: Untestable
```php
// Requires HTTP server, session, database
function FarkleNewGame($players, ...) {
    global $g_debug;
    // ... 200 lines of mixed concerns
}
```

### With Services: Fully Testable
```php
class GameServiceTest extends TestCase
{
    public function testCreateGameRejectsEmptyPlayers(): void
    {
        $this->expectException(ValidationException::class);
        $this->gameService->createGame([], 1);
    }

    public function testCalculateDiceScoreWithTriple1s(): void
    {
        $score = $this->gameService->calculateDiceScore([1, 1, 1, 2, 3, 4]);
        $this->assertEquals(1000, $score);
    }
}
```

---

## Expected Outcomes

| Metric | Before | After |
|--------|--------|-------|
| farkle_fetch.php lines | ~280 | ~120 |
| Testable functions | 0 | 90%+ |
| Cyclomatic complexity | High | Low |
| Code coupling | Tight | Loose |
