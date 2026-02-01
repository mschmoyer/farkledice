# Prepared Statements Migration Implementation Plan

**Priority:** HIGH (Security Critical)
**Estimated Effort:** 6-8 weeks
**Risk Level:** Medium

---

## Executive Summary

This plan details migrating from string-interpolated SQL queries to prepared statements across the Farkle Ten codebase. Currently ~80% of database queries use string interpolation, creating SQL injection vulnerabilities.

---

## Current State Analysis

### Database Abstraction Layer (dbutil.php)

The codebase uses PDO for database connections but executes queries using direct string interpolation:

**Current Functions:**
- `db_select_query($sql, $return_type)` - Executes raw SQL string via `$dbh->query($sql)`
- `db_command($sql)` - Executes raw SQL string via `$dbh->exec($sql)`
- `db_insert_update_query($sql)` - Same as db_command, executes raw SQL

**Key Configuration (Already Correct):**
```php
$g_dbh = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,  // Native prepared statements enabled
]);
```

### SQL Injection Vulnerability Examples

**Example 1: farkleGameFuncs.php - CreateGameWithPlayers()**
```php
$sql = "insert into farkle_games
        (currentturn, maxturns, gamestart, mintostart,
        lastturn, whostarted, playerarray, titleredeemed, gamemode,
        gameexpire, playerstring, gamewith )
    values
        ($currentTurn, ".count($players).", NOW(), 0,
        0, $whostarted, '$thePlayers', $titleRedeemed, $gameMode,
        NOW() + ($expireDays || ' days')::INTERVAL, '$playerNames', $gameWith )";
```

**Example 2: farkleLogin.php - UserLogin()**
```php
$sql = "select username, playerid, adminlevel, sessionid
    from farkle_players
    where (MD5(username)='$user' OR MD5(LOWER(email))='$user') and password=CONCAT('$pass',MD5(salt))";
```

### Scope of Changes

| File | Estimated Queries | Priority | Risk Level |
|------|-------------------|----------|------------|
| farkleGameFuncs.php | 65+ | Critical | High |
| farkleLogin.php | 15+ | Critical | High (auth) |
| farkleFriends.php | 12+ | High | Medium |
| farkleAchievements.php | 12+ | Medium | Low |
| farkleLeaderboard.php | 20+ | Medium | Low |
| farkleTournament.php | 25+ | Medium | Low |

**Total: ~210+ queries across 25+ files**

---

## New Helper Functions for dbutil.php

### Proposed API

```php
/**
 * Execute a SELECT query with prepared statement
 *
 * @param string $sql SQL with named parameters (:param) or positional (?)
 * @param array $params Parameter values [':param' => value]
 * @param int $return_type SQL_SINGLE_VALUE, SQL_SINGLE_ROW, or SQL_MULTI_ROW
 * @return mixed Query result based on return_type
 */
function db_query(string $sql, array $params = [], int $return_type = SQL_MULTI_ROW)
{
    $dbh = db_connect();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);

    if ($return_type == SQL_MULTI_ROW) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($return_type == SQL_SINGLE_ROW) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : null;
    }
}

/**
 * Execute an INSERT/UPDATE/DELETE with prepared statement
 */
function db_execute(string $sql, array $params = []): int|false
{
    $dbh = db_connect();
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Execute an INSERT and return the last inserted ID
 */
function db_insert(string $sql, array $params = [], string $sequence = ''): int|false
{
    $result = db_execute($sql, $params);
    return $result !== false ? db_insert_id($sequence) : false;
}
```

---

## Example Before/After Transformations

### Simple SELECT with Single Parameter

**Before:**
```php
$sql = "select farkles from farkle_players where playerid=$playerid";
$numFarkles = db_select_query($sql, SQL_SINGLE_VALUE);
```

**After:**
```php
$sql = "SELECT farkles FROM farkle_players WHERE playerid = :playerid";
$numFarkles = db_query($sql, [':playerid' => $playerid], SQL_SINGLE_VALUE);
```

### INSERT with Multiple Parameters

**Before:**
```php
$sql = "insert into farkle_games_players (gameid, playerid, playerturn, lastplayed, playerround)
    values ($newGameId, $playerIdVal, $i, NOW(), 1)";
$result = db_command($sql);
```

**After:**
```php
$sql = "INSERT INTO farkle_games_players (gameid, playerid, playerturn, lastplayed, playerround)
        VALUES (:gameid, :playerid, :playerturn, NOW(), 1)";
$result = db_execute($sql, [
    ':gameid' => $newGameId,
    ':playerid' => $playerIdVal,
    ':playerturn' => $i
]);
```

---

## Migration Phases

### Phase 1: Foundation (Week 1)
1. Add new helper functions to dbutil.php
2. Add deprecation notices to old functions
3. Write unit tests for new functions

### Phase 2: Authentication & Security Critical (Week 2)
1. Migrate farkleLogin.php (all login/registration queries)
2. Migrate session-handler.php

### Phase 3: Core Game Logic (Weeks 3-4)
1. Migrate farkleGameFuncs.php (largest file, 65+ queries)
2. Migrate farkle_fetch.php inline queries

### Phase 4: Social Features (Week 5)
1. Migrate farkleFriends.php
2. Migrate farkleAchievements.php
3. Migrate farkleLevel.php

### Phase 5: Leaderboards & Tournaments (Week 6)
1. Migrate farkleLeaderboard.php
2. Migrate farkleTournament.php

### Phase 6: Cleanup (Weeks 7-8)
1. Migrate remaining utility files
2. Remove deprecated function usage
3. Remove db_escape_string() calls
4. Final testing and verification

---

## Testing Strategy

1. **Run existing API test after each file migration:**
   ```bash
   docker exec farkle_web php /var/www/html/test/api_game_flow_test.php
   ```

2. **Unit tests for SQL injection prevention:**
   ```php
   public function testDbQueryPreventsInjection(): void
   {
       $maliciousInput = "'; DROP TABLE farkle_players; --";
       $sql = "SELECT * FROM farkle_players WHERE username = :username";
       $result = db_query($sql, [':username' => $maliciousInput], SQL_SINGLE_ROW);
       $this->assertNull($result); // Query executes safely
   }
   ```

---

## Rollback Plan

- Keep backup of original files
- Old functions remain available during migration
- Git branch strategy allows per-file rollback:
  ```bash
  git checkout main -- wwwroot/farkleLogin.php
  ```

---

## Success Criteria

1. Zero SQL injection vulnerabilities (verified by static analysis)
2. All user input passed through prepared statement parameters
3. All existing API tests pass
4. No calls to deprecated functions with interpolated strings
