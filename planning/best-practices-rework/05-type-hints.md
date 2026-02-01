# PHP Type Hints Implementation Plan

**Priority:** Medium
**Estimated Effort:** 5-6 weeks
**Risk Level:** Low-Medium

---

## Executive Summary

The Farkle Ten codebase contains ~173 functions across 25+ PHP files with minimal type hinting (~5%). This plan outlines adding PHP 8.3 type hints to improve code reliability, IDE support, and runtime error detection.

---

## PHP 8.3 Type Features Available

### Scalar Types
```php
function example(string $name, int $id, float $rate, bool $active): void
```

### Union Types (PHP 8.0+)
```php
function findPlayer(int $id): array|false
function getScore(int $gameId): int|null
```

### Nullable Types
```php
function getUsername(?int $playerId): ?string
```

### Return Types
```php
function updateScore(int $playerId, int $score): void
function die_with_error(string $message): never
```

---

## Priority Files to Update

### Tier 1: Critical Game Logic (Weeks 1-2)

| File | Functions | Priority |
|------|-----------|----------|
| `farkleGameFuncs.php` | 35 | **HIGHEST** |
| `includes/dbutil.php` | 7 | **HIGHEST** |
| `farkleDiceScoring.php` | 1 | HIGH |

### Tier 2: Authentication (Weeks 2-3)

| File | Functions | Priority |
|------|-----------|----------|
| `farkleLogin.php` | 12 | HIGH |
| `session-handler.php` | 7 | HIGH (already typed) |

### Tier 3: Player & Achievement (Weeks 3-4)

| File | Functions | Priority |
|------|-----------|----------|
| `farkleAchievements.php` | 18 | MEDIUM |
| `farkleLevel.php` | 10 | MEDIUM |
| `farkleFriends.php` | ~8 | MEDIUM |

### Tier 4: Bot & Utility (Weeks 4-5)

| File | Functions | Priority |
|------|-----------|----------|
| `farkleBotAI.php` | 15+ | LOWER |
| `farkleUtil.php` | 6 | LOWER |

---

## Common Patterns to Apply

### Pattern 1: Player ID Parameters

**Before:**
```php
function Ach_CheckLevel($playerid, $playerlevel)
```

**After:**
```php
function Ach_CheckLevel(int $playerid, int $playerlevel): int
```

### Pattern 2: Database Query Functions

**Before:**
```php
function db_select_query($sql, $return_type = SQL_MULTI_ROW)
```

**After:**
```php
function db_select_query(string $sql, int $return_type = SQL_MULTI_ROW): array|string|int|float|null
```

### Pattern 3: Boolean Check Functions

**Before:**
```php
function Ach_CheckForAchievement($playerid, $achievementid)
{
    return 0; // or 1
}
```

**After:**
```php
function Ach_CheckForAchievement(int $playerid, int $achievementid): bool
{
    return false; // or true
}
```

---

## Example Transformations

### Game Creation Function

**Before:**
```php
function FarkleNewGame($thePlayers, $mBreakIn = 500, $pointsToWin = 5000,
    $gameWith = GAME_WITH_FRIENDS, $gameMode = GAME_MODE_10ROUND, $tournamentGame=false)
```

**After:**
```php
/**
 * @param string $thePlayers JSON array of player IDs
 * @return array Game update data or error array
 */
function FarkleNewGame(
    string $thePlayers,
    int $mBreakIn = 500,
    int $pointsToWin = 5000,
    int $gameWith = GAME_WITH_FRIENDS,
    int $gameMode = GAME_MODE_10ROUND,
    bool $tournamentGame = false
): array
```

### Dice Scoring Function

**Before:**
```php
function farkleScoreDice($savedDice, $playerid)
```

**After:**
```php
/**
 * @param array<int, int> $savedDice Array of 6 dice values (0-6)
 */
function farkleScoreDice(array $savedDice, int $playerid): int
```

---

## Strict Types Strategy

### Gradual Adoption Approach

**Phase 1:** Add type hints without `declare(strict_types=1)`

**Phase 2:** Add strict types to low-risk files first:
- `includes/session-handler.php` (already mostly typed)
- `wwwroot/farkleDiceScoring.php` (single function, pure logic)

**Phase 3:** Add strict types after tests exist:
- `wwwroot/farkleGameFuncs.php`
- `wwwroot/farkleLogin.php`

---

## Backward Compatibility

### Issue 1: Callers Passing Wrong Types

**Problem:** JavaScript sends string "123", not int 123

**Solution:** Cast at entry points
```php
// In farkle_fetch.php
$playerid = (int)($_POST['playerid'] ?? 0);
$gameid = (int)($_POST['gameid'] ?? 0);
FarkleSendUpdate($playerid, $gameid);
```

### Issue 2: Return Type Changes

**Phase the change:**
1. First add type hints without changing return values
2. Update callers to use strict comparison
3. Then change `0/1` returns to `bool`

---

## Testing After Adding Types

### Pre-Typing Checklist

1. Run existing tests
2. Check function call sites: `grep -r "FunctionName(" wwwroot/`
3. Identify edge cases (NULL, empty strings, zero values)

### Post-Typing Tests

```bash
# Run API test after each file
docker exec farkle_web php /var/www/html/test/api_game_flow_test.php
```

### Unit Test Example

```php
class DiceScoringTest extends TestCase
{
    public function testSingleOne(): void
    {
        $dice = [1, 0, 0, 0, 0, 0];
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(100, $score);
    }

    public function testInvalidArrayThrowsError(): void
    {
        $dice = [1, 2, 3]; // Only 3 elements
        $score = farkleScoreDice($dice, 0);
        $this->assertEquals(0, $score);
    }
}
```

---

## Implementation Timeline

| Week | Focus | Files |
|------|-------|-------|
| 1 | Foundation | dbutil.php, session-handler.php |
| 2 | Core Game | farkleDiceScoring.php, farkleLevel.php |
| 3 | Game Functions | farkleGameFuncs.php (helper functions) |
| 3 | Auth | farkleLogin.php |
| 4 | Achievements | farkleAchievements.php, farkleFriends.php |
| 5 | Utilities | farkleUtil.php, remaining files |
| 6 | Strict Types | Add declare(strict_types=1) to typed files |

---

## Success Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Functions with type hints | ~5% | 90% |
| Files with strict_types | 0% | 60% |
| PHPStan level passing | N/A | Level 5 |
