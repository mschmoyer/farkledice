# Overtime Rounds for 10-Round Tie Breaking

## Problem

When players tie at the end of a 10-round game, the winner is determined by an obscure tiebreaker: highest single round score. Players don't know this rule exists and it can feel arbitrary.

## Solution

Add sudden-death overtime rounds. When players tie after 10 rounds, they continue playing additional rounds until someone pulls ahead.

---

## Complexity Assessment: **Moderate**

### What makes it manageable:
- The 10-round limit is centralized via `LAST_ROUND` constant (PHP line 29, JS line 59)
- `playerround` field already supports values > 10 (11 = "done")
- Game completion logic is centralized in one function: `GameIsCompleted()`
- The `farkle_rounds` table already stores per-round scores

### Challenges:
- ~12 locations check `playerround >= 11` meaning "done" - these need dynamic checks
- Complex SQL in `farklePageFuncs.php` for game sorting
- Bot logic also has round limit checks
- UI needs to display "Overtime Round X" clearly

---

## Database Changes

Add 2 columns to `farkle_games`:
```sql
ALTER TABLE farkle_games ADD COLUMN max_round INTEGER DEFAULT 10;
ALTER TABLE farkle_games ADD COLUMN is_overtime BOOLEAN DEFAULT FALSE;
```

- `max_round`: Current last round (10 normally, 11-15 during overtime)
- `is_overtime`: Flag for UI display and queries

---

## Files to Modify

| File | Changes |
|------|---------|
| `wwwroot/farkleGameFuncs.php` | Add constants, helper function, modify `GameIsCompleted()` for tie detection, update boundary checks |
| `wwwroot/js/farkleGame.js` | Use dynamic `max_round`, display "Overtime Round X" |
| `wwwroot/farklePageFuncs.php` | Update game listing queries to handle `max_round > 10` |
| `wwwroot/farkleBotTurn.php` | Update round limit checks for bots |
| `docker/init.sql` | Add new columns to schema |

---

## Implementation Steps

### 1. Database Migration
- Create migration script: `scripts/add_overtime_columns.sql`
- Add `max_round` and `is_overtime` to `farkle_games`
- Update `docker/init.sql` for fresh installs

### 2. PHP Constants & Helper (farkleGameFuncs.php)
```php
define('MAX_OVERTIME_ROUNDS', 5);    // Max 5 overtime rounds
define('ABSOLUTE_MAX_ROUND', 15);   // Hard cap: 10 + 5

function GetGameMaxRound($gameid) {
    $sql = "SELECT COALESCE(max_round, 10) FROM farkle_games WHERE gameid=$gameid";
    return db_select_query($sql, SQL_SINGLE_VALUE);
}
```

### 3. Core Logic - Modify GameIsCompleted() (lines 1065-1122)
Current behavior: Immediately picks winner by score (with tiebreaker)

New logic:
1. Check if top scores are tied
2. If tied AND `max_round < 15`: Set `is_overtime = true`, increment `max_round`, return (game continues)
3. If not tied OR max overtime reached: Determine winner as before

```php
// Pseudocode for new GameIsCompleted()
if ($playersDone >= $maxTurns) {
    $topScore = $wp[0]['playerscore'];
    $secondScore = $wp[1]['playerscore'];

    if ($topScore == $secondScore && $currentMaxRound < ABSOLUTE_MAX_ROUND) {
        // TIE - Initiate overtime
        $newMaxRound = $currentMaxRound + 1;
        UPDATE farkle_games SET max_round = $newMaxRound, is_overtime = true;
        return 0; // Game continues
    }

    // No tie (or max overtime) - determine winner
    FarkleWinGame(...);
}
```

### 4. Update Boundary Checks
Replace hardcoded `LAST_ROUND` with dynamic `GetGameMaxRound($gameid)` in:
- `FarklePass()` line 913 - prevents rolling after max round
- `FarklePass()` line 1022 - round completion check
- `FarkleSendUpdate()` line 579 - boundary condition

### 5. Update Bot Logic (farkleBotTurn.php)
- `Bot_Step_Banking()` and `Bot_Step_Farkled()` use dynamic max round

### 6. JavaScript UI (farkleGame.js)
- Read `gGameData.max_round` and `gGameData.is_overtime` from server
- Display "Overtime Round X" when `is_overtime` is true
- Update all `LAST_ROUND` checks to use `gGameData.max_round || LAST_ROUND`

Example UI change:
```javascript
if (isOvertime) {
    strInfo += ' <span style="color: orange;">Overtime Round ' + (currentRound - LAST_ROUND) + '!</span>';
} else if (currentRound == maxRound) {
    strInfo += ' <span style="color: yellow;">Last round!</span>';
} else {
    strInfo += ' Round: ' + currentRound + ' of ' + maxRound;
}
```

### 7. Update Game Queries (farklePageFuncs.php)
- Modify `GetGames()` ORDER BY to use `COALESCE(b.max_round, 10)` instead of hardcoded 11

---

## Verification

1. **Basic overtime test:**
   - Create 2-player 10-round game
   - Manipulate DB to set equal scores after round 10
   - Verify overtime triggers and UI shows "Overtime Round 1"

2. **Overtime resolution test:**
   - Play overtime round with different scores
   - Verify winner determined correctly

3. **Max overtime test:**
   - Tie through 5 overtime rounds
   - Verify fallback tiebreaker (highest single round) is used

4. **Bot test:**
   - Play vs bot to overtime
   - Verify bot plays overtime rounds correctly

5. **Backward compatibility:**
   - Verify existing games (before migration) still work
   - `COALESCE(max_round, 10)` handles NULL for old games

---

## Key Code References

- **LAST_ROUND constant (PHP):** `wwwroot/farkleGameFuncs.php:29`
- **LAST_ROUND constant (JS):** `wwwroot/js/farkleGame.js:59`
- **GameIsCompleted():** `wwwroot/farkleGameFuncs.php:1065-1122`
- **FarklePass() boundary:** `wwwroot/farkleGameFuncs.php:913`
- **Game listing queries:** `wwwroot/farklePageFuncs.php:185-199`
- **Bot turn logic:** `wwwroot/farkleBotTurn.php:540-570`

---

## Version Update
Bump minor version in `includes/baseutil.php` when implementing (new feature)
