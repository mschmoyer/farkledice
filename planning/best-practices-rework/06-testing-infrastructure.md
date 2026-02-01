# Testing Infrastructure Implementation Plan

**Priority:** Medium-High
**Estimated Effort:** 4-6 weeks
**Risk Level:** Low

---

## Executive Summary

The Farkle Ten codebase has minimal test coverage (<1%) with only 1 custom integration test. This plan establishes a proper PHPUnit-based testing infrastructure with unit tests, integration tests, test database strategy, and CI/CD integration.

---

## Current State

- 1 custom PHP integration test (`test/api_game_flow_test.php`)
- 3 Playwright E2E tests (TypeScript)
- PHPUnit 10.0 in composer.json but not configured
- No unit tests for core game logic

---

## PHPUnit Configuration

**File:** `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         bootstrap="tests/bootstrap.php"
         colors="true">

    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">wwwroot</directory>
            <directory suffix=".php">includes</directory>
        </include>
        <report>
            <html outputDirectory="coverage-report"/>
        </report>
    </coverage>

    <php>
        <env name="DB_NAME" value="farkle_test"/>
        <env name="APP_ENV" value="testing"/>
    </php>
</phpunit>
```

---

## Directory Structure

```
tests/
├── bootstrap.php              # Test autoloader and setup
├── TestCase.php               # Base test class
├── DatabaseTestCase.php       # Base class for DB tests
│
├── Unit/                      # Pure unit tests (no database)
│   ├── DiceScoringTest.php    # farkleDiceScoring.php tests
│   ├── GameConstantsTest.php  # Game mode/type constants
│   └── ValidationTest.php     # Input validation
│
├── Integration/               # Tests requiring database
│   ├── GameFlowTest.php       # Game creation, turns
│   ├── PlayerTest.php         # Player operations
│   └── AchievementTest.php    # Achievement awarding
│
└── Fixtures/                  # Test data
    ├── players.php            # Sample player data
    └── dice_scenarios.php     # Dice combinations
```

---

## Priority Test Cases

### 1. Dice Scoring Tests (Highest Priority)

**File:** `tests/Unit/DiceScoringTest.php`

```php
<?php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../wwwroot/farkleDiceScoring.php';

class DiceScoringTest extends TestCase
{
    /**
     * @dataProvider singleDiceProvider
     */
    public function testSingleScoringDice(array $dice, int $expected): void
    {
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals($expected, $score);
    }

    public static function singleDiceProvider(): array
    {
        return [
            'single 1' => [[1, 0, 0, 0, 0, 0], 100],
            'two 1s' => [[1, 1, 0, 0, 0, 0], 200],
            'single 5' => [[5, 0, 0, 0, 0, 0], 50],
            'one 1 and one 5' => [[1, 5, 0, 0, 0, 0], 150],
        ];
    }

    /**
     * @dataProvider threeOfAKindProvider
     */
    public function testThreeOfAKind(array $dice, int $expected): void
    {
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals($expected, $score);
    }

    public static function threeOfAKindProvider(): array
    {
        return [
            'three 1s' => [[1, 1, 1, 0, 0, 0], 1000],
            'three 2s' => [[2, 2, 2, 0, 0, 0], 200],
            'three 5s' => [[5, 5, 5, 0, 0, 0], 500],
            'three 6s' => [[6, 6, 6, 0, 0, 0], 600],
        ];
    }

    public function testStraight(): void
    {
        $dice = [1, 2, 3, 4, 5, 6];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(1000, $score);
    }

    public function testFarkle(): void
    {
        $dice = [2, 3, 4, 6, 2, 4];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(0, $score);
    }
}
```

### 2. Game Flow Integration Tests

**File:** `tests/Integration/GameFlowTest.php`

```php
<?php
namespace Tests\Integration;

use Tests\DatabaseTestCase;

class GameFlowTest extends DatabaseTestCase
{
    private int $player1Id;
    private int $player2Id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->player1Id = $this->createTestPlayer('gameflow_p1');
        $this->player2Id = $this->createTestPlayer('gameflow_p2');
        $_SESSION['playerid'] = $this->player1Id;
    }

    public function testCreateTenRoundGame(): void
    {
        $players = json_encode([$this->player1Id, $this->player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('Error', $result);
    }

    public function testGameConstantsAreDefined(): void
    {
        $this->assertTrue(defined('GAME_MODE_10ROUND'));
        $this->assertEquals(2, GAME_MODE_10ROUND);
        $this->assertEquals(10, LAST_ROUND);
    }
}
```

---

## Test Database Strategy

### Docker Test Database

Add to `docker-compose.yml`:
```yaml
db-test:
  image: postgres:16
  environment:
    POSTGRES_DB: farkle_test
    POSTGRES_USER: farkle_user
    POSTGRES_PASSWORD: farkle_pass
  ports:
    - "5433:5432"
```

### DatabaseTestCase Base Class

```php
<?php
namespace Tests;

abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $db = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::$db->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$db->rollBack();
        parent::tearDown();
    }

    protected function createTestPlayer(string $username): int
    {
        $sql = "INSERT INTO farkle_players (username, password, salt, email, active)
                VALUES (:username, :password, '', :email, true)
                RETURNING playerid";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([
            ':username' => $username . '_' . uniqid(),
            ':password' => md5('testpass'),
            ':email' => $username . '@test.com'
        ]);
        return (int)$stmt->fetch()['playerid'];
    }
}
```

---

## CI/CD Integration

**File:** `.github/workflows/tests.yml`

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: farkle_test
          POSTGRES_USER: farkle_user
          POSTGRES_PASSWORD: farkle_pass
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo, pdo_pgsql
          coverage: xdebug

      - name: Install dependencies
        run: composer install

      - name: Initialize test database
        run: psql -h localhost -U farkle_user -d farkle_test -f docker/init.sql

      - name: Run unit tests
        run: ./vendor/bin/phpunit --testsuite Unit

      - name: Run integration tests
        run: ./vendor/bin/phpunit --testsuite Integration
```

---

## Code Coverage Goals

### Phase 1 (Weeks 1-2): 30% Coverage
- 100% coverage of `farkleDiceScoring.php`
- Basic tests for game creation

### Phase 2 (Weeks 3-4): 50% Coverage
- `farkleGameFuncs.php` game flow
- `farkleLogin.php` authentication
- `farkleAchievements.php` awarding

### Phase 3 (Weeks 5-6): 70% Coverage
- All remaining game functions
- Leaderboard calculations
- Tournament logic

---

## Running Tests

### Makefile Commands

```makefile
test:
    ./vendor/bin/phpunit

test-unit:
    ./vendor/bin/phpunit --testsuite Unit

test-integration:
    docker-compose up -d db-test
    ./vendor/bin/phpunit --testsuite Integration

test-coverage:
    ./vendor/bin/phpunit --coverage-html coverage-report
```

### Docker Commands

```bash
# Run all tests
docker exec farkle_web vendor/bin/phpunit

# Run with coverage
docker exec farkle_web vendor/bin/phpunit --coverage-html coverage-report
```

---

## Composer Updates

```json
{
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  },
  "scripts": {
    "test": "./vendor/bin/phpunit",
    "test-unit": "./vendor/bin/phpunit --testsuite Unit"
  }
}
```
