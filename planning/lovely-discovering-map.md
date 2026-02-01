# Database Performance Optimization Plan

## Overview
This plan addresses three critical database performance issues identified in the polling/query analysis:
1. Missing database indexes on frequently-queried columns
2. N+1 query patterns in activity log and friends list
3. Unnecessary `lastplayed` UPDATE on every lobby poll

**Branch:** `feature/db-performance-optimization`

---

## Task 1: Add Missing Database Indexes

### Files to Modify
- `docker/init.sql` - Add indexes to base schema
- `scripts/add-performance-indexes.sql` (new) - Migration script for existing databases

### Indexes to Add

```sql
-- farkle_players: frequently filtered columns
CREATE INDEX IF NOT EXISTS idx_players_active ON farkle_players(active);
CREATE INDEX IF NOT EXISTS idx_players_lastplayed ON farkle_players(lastplayed);
CREATE INDEX IF NOT EXISTS idx_players_is_bot ON farkle_players(is_bot);

-- farkle_friends: core friend query pattern
CREATE INDEX IF NOT EXISTS idx_friends_sourceid ON farkle_friends(sourceid);
CREATE INDEX IF NOT EXISTS idx_friends_sourceid_removed ON farkle_friends(sourceid, removed);

-- farkle_games: leaderboard and game queries
CREATE INDEX IF NOT EXISTS idx_games_gamefinish ON farkle_games(gamefinish);
CREATE INDEX IF NOT EXISTS idx_games_gamewith ON farkle_games(gamewith);
CREATE INDEX IF NOT EXISTS idx_games_last_activity ON farkle_games(last_activity);

-- farkle_rounds: daily leaderboard queries
CREATE INDEX IF NOT EXISTS idx_rounds_rounddatetime ON farkle_rounds(rounddatetime);
CREATE INDEX IF NOT EXISTS idx_rounds_gameid_roundnum ON farkle_rounds(gameid, roundnum);

-- farkle_sets: activity log queries (JOIN optimization)
CREATE INDEX IF NOT EXISTS idx_sets_gameid_playerid_roundnum ON farkle_sets(gameid, playerid, roundnum);
```

### Implementation Steps
1. Add indexes to `docker/init.sql` after relevant table definitions
2. Create `scripts/add-performance-indexes.sql` migration with run instructions
3. Test locally with Docker rebuild

---

## Task 2: Fix N+1 Query in GetGameActivityLog

### File to Modify
- `wwwroot/farkleGameFuncs.php` - Lines 1512-1586

### Current Problem
```php
// Main query fetches rounds
$rounds = db_select_query($roundsSql);

// Then for EACH round, another query fetches dice
foreach ($rounds as $round) {
    $diceQuery = "SELECT ... FROM farkle_sets WHERE gameid=$gameid AND roundnum={$round['roundnum']}";
    $dice = db_select_query($diceQuery);  // N additional queries!
}
```

### Solution
Single query with LEFT JOIN, then group in PHP:

```sql
SELECT r.playerid, r.roundnum, r.roundscore, p.username,
       s.handnum, s.d1save, s.d2save, s.d3save, s.d4save, s.d5save, s.d6save
FROM farkle_rounds r
JOIN farkle_players p ON r.playerid = p.playerid
LEFT JOIN farkle_sets s ON s.gameid = r.gameid
    AND s.playerid = r.playerid
    AND s.roundnum = r.roundnum
    AND s.setscore > 0
WHERE r.gameid = $gameid
ORDER BY r.rounddatetime ASC, s.handnum ASC, s.setnum ASC
```

### PHP Changes
- Execute single query
- Loop through results, grouping by `playerid + roundnum`
- Build `dicehands` array for each round entry
- Preserve existing filtering logic (hide opponent future rounds)

**Impact:** 51 queries → 1 query for 50-round game (98% reduction)

---

## Task 3: Fix N+1 Query in GetActiveFriends

### File to Modify
- `wwwroot/farkleFriends.php` - Lines 183-228

### Current Problem
```php
// Main query fetches active friends
$friends = db_select_query($friendsSql);

// Then for EACH friend, query their current game
foreach ($friends as &$friend) {
    $gameSql = "SELECT g.gameid, opponent FROM ... WHERE playerid = {$friend['playerid']}";
    $game = db_select_query($gameSql);  // N additional queries!
}
```

### Solution
Single query with LEFT JOINs:

```sql
SELECT a.username, a.playerid, a.playertitle, a.cardcolor,
       g.gameid, p2.username as opponent
FROM farkle_players a
JOIN farkle_friends b ON a.playerid = b.friendid
LEFT JOIN farkle_games_players gp ON gp.playerid = a.playerid
LEFT JOIN farkle_games g ON g.gameid = gp.gameid AND g.winningplayer = 0
LEFT JOIN farkle_games_players gp2 ON gp2.gameid = g.gameid AND gp2.playerid != a.playerid
LEFT JOIN farkle_players p2 ON gp2.playerid = p2.playerid
WHERE b.sourceid = $playerid
  AND a.active = 1 AND b.removed = 0
  AND a.lastplayed > NOW() - interval '10 minutes'
ORDER BY a.lastplayed DESC
```

### PHP Changes
- Execute single query
- Set status based on `opponent` field: `'Playing: ' . $opponent` or `'In Lobby'`
- Handle potential duplicates if friend is in multiple games (use first/most recent)

**Impact:** 11 queries → 1 query for 10 friends (91% reduction)

---

## Task 4: Remove Unnecessary lastplayed UPDATE

### File to Modify
- `wwwroot/farklePageFuncs.php` - Line 157-158

### Current Problem
```php
// GetLobbyInfo() - called every 10-20 seconds during polling
db_command("update farkle_players set lastplayed=NOW() where playerid=$playerid");
```

This UPDATE runs on **every lobby poll**, creating constant write pressure.

### Solution
Remove this UPDATE entirely. `lastplayed` is already updated at appropriate times:
- On login (`farkleLogin.php:151`)
- On dice roll (`farkleGameFuncs.php:989`)
- On turn pass (`farkleGameFuncs.php:1116`)

### Impact Analysis
| Use Case | Impact |
|----------|--------|
| Friend list ordering | Minimal - still updated during gameplay |
| Random opponent matching | None - uses 2-week window |
| Daily leaderboards | None - based on game activity |
| Admin stats | Slightly more accurate (active = playing) |

**Impact:** Eliminates 1 WRITE per 10-20 seconds per lobby user

---

## Implementation Order

1. **Create branch** `feature/db-performance-optimization`
2. **Task 1:** Add indexes (schema + migration)
3. **Task 4:** Remove lastplayed UPDATE (simplest change)
4. **Task 2:** Fix activity log N+1 (more complex refactor)
5. **Task 3:** Fix friends N+1 (moderate refactor)
6. **Test all changes** with API game flow test
7. **Update version** in `includes/baseutil.php`
8. **Update release notes** in `data/release-notes.json`

---

## Verification

### Local Testing
```bash
# Rebuild Docker to apply schema changes
docker-compose down && docker-compose up -d --build

# Run migration on existing DB
docker exec farkle_web php -r "include 'scripts/add-performance-indexes.sql';"
# Or via psql:
docker exec farkle_db psql -U farkle_user -d farkle_db -f /path/to/migration.sql

# Run API test
docker exec farkle_web php /var/www/html/test/api_game_flow_test.php

# Manual testing
# 1. Open lobby - verify no errors
# 2. Play a game - verify activity log displays correctly
# 3. Check friends list - verify friend status shows correctly
```

### Performance Verification
```bash
# Check indexes were created
docker exec farkle_db psql -U farkle_user -d farkle_db -c "\di"

# Enable query logging (optional)
# Check docker/error.log for slow queries
```

---

## Rollback Plan

If issues arise:
1. Indexes: No rollback needed (indexes don't break functionality)
2. lastplayed: Revert single line in farklePageFuncs.php
3. N+1 fixes: Revert function changes in farkleGameFuncs.php / farkleFriends.php

---

## Expected Impact

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Queries per game poll | 12-54 | 8-12 | ~70% reduction |
| Queries per lobby poll | 10-16 | 8-14 | ~20% reduction |
| Writes per lobby user/min | 3-6 | 0 | 100% reduction |
| Friend list query time | O(n) queries | O(1) query | Significant |
| Activity log query time | O(n) queries | O(1) query | Significant |
