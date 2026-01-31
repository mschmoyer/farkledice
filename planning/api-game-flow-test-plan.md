# API Testing Plan: Farkle 10-Round Game Flow

**Status: COMPLETED**

## Overview

Create a PHP test script that exercises the core game flow via HTTP API calls (no browser), logging in two users, playing a complete 10-round game, and verifying database consistency throughout.

## File Location

```
/test/api_game_flow_test.php
```

Per CLAUDE.md: "When creating test PHP files, place them in a `/test` subfolder."

## Implementation Approach

### Why PHP + cURL (not Bash/Python)
- Native to project with existing patterns in `/wwwroot/admin/farkle_test.php`
- Can use cURL for HTTP and PDO for database verification in same script
- Runs naturally: `docker exec farkle_web php /var/www/html/test/api_game_flow_test.php`

### Key Discovery: 10-Round Mode Turn Logic
In 10-round mode, players do NOT alternate turns. Each player plays their rounds independently:
- Player 1 can play rounds 1-10
- Then Player 2 plays rounds 1-10
- Game ends when both complete round 10

This is confirmed in `farkleGameFuncs.php:823`:
```php
$isMyTurn = (($gData['currentturn'] == $gData['playerturn']) || $gData['gamemode'] == GAME_MODE_10ROUND);
```

## Script Structure

### 1. FarkleAPIClient Class
Handles HTTP requests with session cookies:
```php
class FarkleAPIClient {
    private $baseUrl = 'http://localhost:8080/wwwroot/farkle_fetch.php';
    private $cookieFile;  // Unique per player for session isolation

    public function login($username, $password);     // MD5 hashes credentials
    public function startGame($players, $gamemode);  // Returns gameid
    public function roll($gameid, $saveddice);       // Roll/select dice
    public function pass($gameid, $saveddice);       // Bank and end turn
    public function getUpdate($gameid);              // Get current state
}
```

### 2. Assertion Functions
Following existing pattern from `farkle_test.php`:
```php
function assertSuccess($response, $testName);        // Check no Error key
function assertDBValue($sql, $expected, $testName);  // Verify DB state
```

### 3. Simple Dice Selection Strategy
```php
function selectScoringDice($diceArray) {
    // Detect 1s (100 pts) and 5s (50 pts)
    // Detect three-of-a-kind
    // Return saveddice array [0,0,0,0,0,0] format
}
```

### 4. Round Player Function
```php
function playOneRound($client, $gameid) {
    // 1. Roll all dice
    // 2. Select scoring dice (1s, 5s, triples)
    // 3. Either bank if score >= 300 or continue rolling
    // 4. Pass to complete round
}
```

## Test Flow

```
1. SETUP
   - Create second test user (testuser2) if needed

2. LOGIN BOTH PLAYERS
   - POST action=login, user=MD5(testuser), pass=MD5(test123)
   - POST action=login, user=MD5(testuser2), pass=MD5(test123)
   - Verify: JSON response has playerid, no Error

3. CREATE 10-ROUND GAME
   - POST action=startgame, players=[p1id,p2id], gamemode=2, gamewith=1
   - Verify DB: farkle_games row exists, gamemode=2, winningplayer=0

4. PLAYER 1 PLAYS ROUNDS 1-10
   For each round:
   - POST action=farkleroll, gameid=X, saveddice=[0,0,0,0,0,0]
   - Select 1s and 5s from response
   - POST action=farklepass, gameid=X, saveddice=[selected]
   - Verify DB: playerround incremented, farkle_rounds entry created

5. PLAYER 2 PLAYS ROUNDS 1-10
   Same as above with player 2's session

6. VERIFY GAME COMPLETION
   - DB: winningplayer > 0, gamefinish IS NOT NULL
   - DB: Both players have playerround = 10
   - DB: Sum of farkle_rounds matches playerscore
   - Output: Winner and final scores
```

## API Endpoints Reference

| Action | Parameters | Response |
|--------|-----------|----------|
| `login` | user (MD5), pass (MD5), remember | `{username, playerid}` |
| `startgame` | players (JSON), gamemode=2, gamewith=1, breakin=0 | Array with gameid at index 5 |
| `farkleroll` | gameid, saveddice (JSON array) | Updated game state |
| `farklepass` | gameid, saveddice (JSON array) | Updated game state |

## Database Verification Queries

```sql
-- Game created correctly
SELECT gameid, gamemode, maxturns FROM farkle_games WHERE gameid = ?;

-- Round completed
SELECT playerround, playerscore FROM farkle_games_players
WHERE gameid = ? AND playerid = ?;

-- Score recorded
SELECT roundscore FROM farkle_rounds
WHERE gameid = ? AND playerid = ? AND roundnum = ?;

-- Game finished
SELECT winningplayer, gamefinish FROM farkle_games WHERE gameid = ?;

-- Score integrity
SELECT SUM(roundscore) = playerscore FROM farkle_rounds r
JOIN farkle_games_players gp ON r.gameid = gp.gameid AND r.playerid = gp.playerid
WHERE r.gameid = ?;
```

## Execution

```bash
# Run test
docker exec farkle_web php /var/www/html/test/api_game_flow_test.php

# Expected output format (following existing pattern):
# 1 - Player 1 login: Test successful. Testing for: [1], RC=[testuser]
# 2 - Player 2 login: Test successful. Testing for: [1], RC=[testuser2]
# 3 - Create 10-round game: Test successful. Testing for: [1], RC=[gameid]
# ...
# 24 - Game completion verified: Test successful.
```

## Files to Create/Modify

| File | Action | Purpose |
|------|--------|---------|
| `/test/api_game_flow_test.php` | Create | Main test script |
| `/docker/init.sql` | Modify (if needed) | Add testuser2 |

## Test User Setup

The script will check for and create `testuser2` if it doesn't exist:
```sql
INSERT INTO farkle_players (username, password, salt, email, active)
VALUES ('testuser2', CONCAT(MD5('test123'), MD5('35td2c')), '35td2c',
        'testuser2@test.com', 1)
ON CONFLICT (username) DO NOTHING;
```

## Success Criteria

1. Both players authenticate via HTTP API
2. 10-round game created and verified in database
3. All 20 rounds played (10 per player) via API calls
4. Each round score recorded in `farkle_rounds`
5. Game completes with winner declared
6. Final `playerscore` = sum of `roundscore` entries
7. Script exits with success message
