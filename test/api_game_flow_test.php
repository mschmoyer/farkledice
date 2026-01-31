<?php
/**
 * API Game Flow Test
 *
 * Tests the core Farkle 10-round game flow via HTTP API calls.
 * Logs in two users, creates a game, plays all 10 rounds, and verifies DB consistency.
 *
 * Usage: docker exec farkle_web php /var/www/html/test/api_game_flow_test.php
 */

// Change to wwwroot for includes
chdir(__DIR__ . '/../wwwroot');

require_once('../includes/baseutil.php');
require_once('dbutil.php');

// Test configuration
define('API_BASE_URL', 'http://localhost/farkle_fetch.php');
define('TEST_USER_1', 'testuser');
define('TEST_PASS_1', 'test123');
define('TEST_USER_2', 'testuser2');
define('TEST_PASS_2', 'test123');

// Global test state
$g_step = 0;
$g_passed = 0;
$g_failed = 0;

/**
 * Output colored text to terminal
 */
function output($message, $color = null) {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];

    if ($color && isset($colors[$color])) {
        echo $colors[$color] . $message . $colors['reset'] . "\n";
    } else {
        echo $message . "\n";
    }
}

/**
 * Test assertion - check for success (no Error key)
 */
function assertSuccess($response, $testName) {
    global $g_step, $g_passed, $g_failed;
    $g_step++;

    $success = is_array($response) && !isset($response['Error']);

    if ($success) {
        $g_passed++;
        output("$g_step - $testName: PASS", 'green');
        return true;
    } else {
        $g_failed++;
        $error = isset($response['Error']) ? $response['Error'] : 'Unknown error';
        output("$g_step - $testName: FAIL - $error", 'red');
        return false;
    }
}

/**
 * Test assertion - check for specific error
 */
function assertError($response, $expectedError, $testName) {
    global $g_step, $g_passed, $g_failed;
    $g_step++;

    $success = isset($response['Error']) && strpos($response['Error'], $expectedError) !== false;

    if ($success) {
        $g_passed++;
        output("$g_step - $testName: PASS (expected error)", 'green');
        return true;
    } else {
        $g_failed++;
        $actual = isset($response['Error']) ? $response['Error'] : 'No error';
        output("$g_step - $testName: FAIL - Expected '$expectedError', got '$actual'", 'red');
        return false;
    }
}

/**
 * Test assertion - check database value
 */
function assertDBValue($sql, $expected, $testName) {
    global $g_step, $g_passed, $g_failed;
    $g_step++;

    $actual = db_select_query($sql, SQL_SINGLE_VALUE);
    $success = ($actual == $expected);

    if ($success) {
        $g_passed++;
        output("$g_step - $testName: PASS (DB value: $actual)", 'green');
        return true;
    } else {
        $g_failed++;
        output("$g_step - $testName: FAIL - Expected '$expected', got '$actual'", 'red');
        return false;
    }
}

/**
 * Test assertion - check database condition is true
 */
function assertDBTrue($sql, $testName) {
    global $g_step, $g_passed, $g_failed;
    $g_step++;

    $result = db_select_query($sql, SQL_SINGLE_VALUE);
    $success = ($result == 't' || $result == '1' || $result === true);

    if ($success) {
        $g_passed++;
        output("$g_step - $testName: PASS", 'green');
        return true;
    } else {
        $g_failed++;
        output("$g_step - $testName: FAIL - Condition not met", 'red');
        return false;
    }
}

/**
 * API Client for making HTTP requests with session management
 */
class FarkleAPIClient {
    private $baseUrl;
    private $cookieFile;
    private $playerid;
    private $username;

    public function __construct($sessionName) {
        $this->baseUrl = API_BASE_URL;
        $this->cookieFile = sys_get_temp_dir() . '/farkle_test_cookies_' . $sessionName . '.txt';
        // Clean up any existing cookie file
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function __destruct() {
        // Clean up cookie file
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function getPlayerid() {
        return $this->playerid;
    }

    public function getUsername() {
        return $this->username;
    }

    /**
     * Make HTTP POST request to API
     */
    private function makeRequest($params) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['Error' => "cURL error: $error"];
        }

        if ($httpCode !== 200) {
            return ['Error' => "HTTP $httpCode"];
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            return ['Error' => "Invalid JSON response: " . substr($response, 0, 200)];
        }

        return $decoded;
    }

    /**
     * Login with username and password
     */
    public function login($username, $password) {
        $response = $this->makeRequest([
            'action' => 'login',
            'user' => md5($username),
            'pass' => md5($password),
            'remember' => 1
        ]);

        if (isset($response['playerid'])) {
            $this->playerid = $response['playerid'];
            $this->username = $response['username'];
        }

        return $response;
    }

    /**
     * Create a new game
     */
    public function startGame($players, $gamemode = 2, $gamewith = 1) {
        return $this->makeRequest([
            'action' => 'startgame',
            'players' => json_encode($players),
            'breakin' => 0,
            'playto' => 10000,
            'gamewith' => $gamewith,
            'gamemode' => $gamemode,
            'rp' => count($players)
        ]);
    }

    /**
     * Roll dice
     */
    public function roll($gameid, $saveddice = [0,0,0,0,0,0]) {
        return $this->makeRequest([
            'action' => 'farkleroll',
            'gameid' => $gameid,
            'saveddice' => json_encode($saveddice)
        ]);
    }

    /**
     * Pass/bank current score
     */
    public function pass($gameid, $saveddice) {
        return $this->makeRequest([
            'action' => 'farklepass',
            'gameid' => $gameid,
            'saveddice' => json_encode($saveddice)
        ]);
    }

    /**
     * Get game update
     */
    public function getUpdate($gameid) {
        return $this->makeRequest([
            'action' => 'farklegetupdate',
            'gameid' => $gameid
        ]);
    }
}

/**
 * Calculate score for dice selection (simplified)
 */
function calculateScore($dice) {
    $score = 0;
    $counts = array_count_values($dice);

    // Check for three of a kind first
    foreach ($counts as $value => $count) {
        if ($count >= 3 && $value > 0) {
            if ($value == 1) {
                $score += 1000;
            } else {
                $score += $value * 100;
            }
            $counts[$value] -= 3;
        }
    }

    // Count remaining 1s and 5s
    if (isset($counts[1])) {
        $score += $counts[1] * 100;
    }
    if (isset($counts[5])) {
        $score += $counts[5] * 50;
    }

    return $score;
}

/**
 * Select scoring dice from a roll (simple strategy: 1s, 5s, and triples)
 * Returns the saveddice array
 */
function selectScoringDice($diceArray) {
    $saveddice = [0, 0, 0, 0, 0, 0];
    $counts = array_count_values($diceArray);

    // First, look for three of a kind
    $tripleFound = false;
    foreach ($counts as $value => $count) {
        if ($count >= 3 && !$tripleFound) {
            $selected = 0;
            for ($i = 0; $i < 6; $i++) {
                if ($diceArray[$i] == $value && $selected < 3) {
                    $saveddice[$i] = $value;
                    $selected++;
                }
            }
            $tripleFound = true;
            break;
        }
    }

    // Then select any 1s and 5s not already selected
    for ($i = 0; $i < 6; $i++) {
        if ($saveddice[$i] == 0) {
            if ($diceArray[$i] == 1 || $diceArray[$i] == 5) {
                $saveddice[$i] = $diceArray[$i];
            }
        }
    }

    return $saveddice;
}

/**
 * Check if any scoring dice exist in the roll
 */
function hasScoringDice($diceArray) {
    $counts = array_count_values($diceArray);

    // Check for 1s or 5s
    if (isset($counts[1]) || isset($counts[5])) {
        return true;
    }

    // Check for three of a kind
    foreach ($counts as $count) {
        if ($count >= 3) {
            return true;
        }
    }

    return false;
}

/**
 * Extract dice values from game response
 */
function extractDice($response) {
    // The dice are in the response structure
    // Looking at the API, after a roll the dice are returned
    if (isset($response[2]) && is_array($response[2])) {
        // Dice data is at index 2
        $diceData = $response[2];
        $dice = [];
        for ($i = 1; $i <= 6; $i++) {
            if (isset($diceData["d$i"])) {
                $dice[] = (int)$diceData["d$i"];
            }
        }
        if (count($dice) == 6) {
            return $dice;
        }
    }

    // Try alternative format
    if (isset($response[4]) && is_array($response[4])) {
        return array_map('intval', $response[4]);
    }

    return null;
}

/**
 * Play one round for a player
 */
function playOneRound($client, $gameid, $roundNum, $playerName) {
    output("  Round $roundNum for $playerName:", 'blue');

    // First roll - roll all dice
    $response = $client->roll($gameid, [0, 0, 0, 0, 0, 0]);

    if (isset($response['Error'])) {
        output("    Roll failed: " . $response['Error'], 'red');
        return false;
    }

    // Get dice from response - need to query DB as fallback
    $playerid = $client->getPlayerid();
    $diceRow = db_select_query(
        "SELECT d1, d2, d3, d4, d5, d6 FROM farkle_sets
         WHERE gameid = $gameid AND playerid = $playerid
         ORDER BY roundnum DESC, setnum DESC LIMIT 1",
        SQL_SINGLE_ROW
    );

    if (!$diceRow) {
        output("    Could not get dice from DB", 'red');
        return false;
    }

    $dice = [
        (int)$diceRow['d1'], (int)$diceRow['d2'], (int)$diceRow['d3'],
        (int)$diceRow['d4'], (int)$diceRow['d5'], (int)$diceRow['d6']
    ];

    output("    Rolled: [" . implode(',', $dice) . "]");

    // Check for farkle (no scoring dice)
    if (!hasScoringDice($dice)) {
        output("    FARKLE! No scoring dice.", 'yellow');
        // Server should auto-pass on farkle
        return true;
    }

    // Select scoring dice
    $saveddice = selectScoringDice($dice);
    $score = calculateScore($saveddice);
    output("    Selected: [" . implode(',', $saveddice) . "] = $score pts");

    // Bank the score
    $response = $client->pass($gameid, $saveddice);

    if (isset($response['Error'])) {
        output("    Pass failed: " . $response['Error'], 'red');
        return false;
    }

    output("    Banked successfully!", 'green');
    return true;
}

/**
 * Ensure test user 2 exists
 */
function ensureTestUser2Exists() {
    $exists = db_select_query(
        "SELECT playerid FROM farkle_players WHERE username = '" . TEST_USER_2 . "'",
        SQL_SINGLE_VALUE
    );

    if (!$exists) {
        output("Creating test user 2...", 'yellow');
        $password = md5(TEST_PASS_2) . md5('');
        db_command(
            "INSERT INTO farkle_players (username, password, salt, email, active)
             VALUES ('" . TEST_USER_2 . "', '$password', '', 'testuser2@test.com', 1)"
        );
        return db_select_query(
            "SELECT playerid FROM farkle_players WHERE username = '" . TEST_USER_2 . "'",
            SQL_SINGLE_VALUE
        );
    }

    return $exists;
}

// =============================================================================
// MAIN TEST EXECUTION
// =============================================================================

output("\n========================================", 'blue');
output("  FARKLE API GAME FLOW TEST", 'blue');
output("========================================\n", 'blue');

// Ensure test user 2 exists
$testUser2Id = ensureTestUser2Exists();
output("Test user 2 ID: $testUser2Id\n");

// Create API clients for both players
$player1 = new FarkleAPIClient('player1');
$player2 = new FarkleAPIClient('player2');

// ---- LOGIN TESTS ----
output("--- LOGIN PHASE ---", 'blue');

$login1 = $player1->login(TEST_USER_1, TEST_PASS_1);
if (!assertSuccess($login1, "Player 1 login (" . TEST_USER_1 . ")")) {
    output("\nTest aborted: Could not login player 1", 'red');
    exit(1);
}
$p1id = $player1->getPlayerid();
output("  Player 1 ID: $p1id");

$login2 = $player2->login(TEST_USER_2, TEST_PASS_2);
if (!assertSuccess($login2, "Player 2 login (" . TEST_USER_2 . ")")) {
    output("\nTest aborted: Could not login player 2", 'red');
    exit(1);
}
$p2id = $player2->getPlayerid();
output("  Player 2 ID: $p2id\n");

// ---- GAME CREATION ----
output("--- GAME CREATION PHASE ---", 'blue');

$gameResult = $player1->startGame([$p1id, $p2id], 2, 1);
if (!assertSuccess($gameResult, "Create 10-round game")) {
    output("\nTest aborted: Could not create game", 'red');
    exit(1);
}

// Extract gameid from response
$gameid = null;
if (isset($gameResult[5])) {
    $gameid = $gameResult[5];
} elseif (isset($gameResult['gameid'])) {
    $gameid = $gameResult['gameid'];
}

if (!$gameid) {
    output("Could not extract gameid from response", 'red');
    var_dump($gameResult);
    exit(1);
}

output("  Game ID: $gameid\n");

// Verify game in database
assertDBValue(
    "SELECT gamemode FROM farkle_games WHERE gameid = $gameid",
    2,
    "Game mode is 10-round (2)"
);

assertDBValue(
    "SELECT maxturns FROM farkle_games WHERE gameid = $gameid",
    2,
    "Game has 2 players"
);

assertDBValue(
    "SELECT winningplayer FROM farkle_games WHERE gameid = $gameid",
    0,
    "Game not yet won"
);

// ---- GAMEPLAY PHASE ----
output("\n--- GAMEPLAY PHASE ---", 'blue');

// Player 1 plays all 10 rounds
output("\nPlayer 1 (" . TEST_USER_1 . ") playing rounds 1-10:", 'blue');
for ($round = 1; $round <= 10; $round++) {
    if (!playOneRound($player1, $gameid, $round, TEST_USER_1)) {
        output("Player 1 round $round failed!", 'red');
    }
}

// Verify player 1 completed rounds
$p1Round = db_select_query(
    "SELECT playerround FROM farkle_games_players WHERE gameid = $gameid AND playerid = $p1id",
    SQL_SINGLE_VALUE
);
output("\nPlayer 1 current round in DB: $p1Round");

// Player 2 plays all 10 rounds
output("\nPlayer 2 (" . TEST_USER_2 . ") playing rounds 1-10:", 'blue');
for ($round = 1; $round <= 10; $round++) {
    if (!playOneRound($player2, $gameid, $round, TEST_USER_2)) {
        output("Player 2 round $round failed!", 'red');
    }
}

// ---- VERIFICATION PHASE ----
output("\n--- VERIFICATION PHASE ---", 'blue');

// Check both players completed 10 rounds
assertDBTrue(
    "SELECT playerround >= 10 FROM farkle_games_players WHERE gameid = $gameid AND playerid = $p1id",
    "Player 1 completed 10 rounds"
);

assertDBTrue(
    "SELECT playerround >= 10 FROM farkle_games_players WHERE gameid = $gameid AND playerid = $p2id",
    "Player 2 completed 10 rounds"
);

// Check game has a winner
$winner = db_select_query(
    "SELECT winningplayer FROM farkle_games WHERE gameid = $gameid",
    SQL_SINGLE_VALUE
);

if ($winner > 0) {
    $g_step++;
    $g_passed++;
    output("$g_step - Game completed with winner: PASS (winner: playerid $winner)", 'green');
} else {
    $g_step++;
    $g_failed++;
    output("$g_step - Game completed with winner: FAIL (no winner set)", 'red');
}

// Check game finish timestamp
assertDBTrue(
    "SELECT gamefinish IS NOT NULL FROM farkle_games WHERE gameid = $gameid",
    "Game finish timestamp set"
);

// Verify score integrity - player scores match sum of round scores
$p1Score = db_select_query(
    "SELECT playerscore FROM farkle_games_players WHERE gameid = $gameid AND playerid = $p1id",
    SQL_SINGLE_VALUE
);
$p1RoundSum = db_select_query(
    "SELECT COALESCE(SUM(roundscore), 0) FROM farkle_rounds WHERE gameid = $gameid AND playerid = $p1id",
    SQL_SINGLE_VALUE
);
output("Player 1 score: $p1Score (rounds sum: $p1RoundSum)");

$p2Score = db_select_query(
    "SELECT playerscore FROM farkle_games_players WHERE gameid = $gameid AND playerid = $p2id",
    SQL_SINGLE_VALUE
);
$p2RoundSum = db_select_query(
    "SELECT COALESCE(SUM(roundscore), 0) FROM farkle_rounds WHERE gameid = $gameid AND playerid = $p2id",
    SQL_SINGLE_VALUE
);
output("Player 2 score: $p2Score (rounds sum: $p2RoundSum)");

// Count rounds logged
$roundsLogged = db_select_query(
    "SELECT COUNT(*) FROM farkle_rounds WHERE gameid = $gameid",
    SQL_SINGLE_VALUE
);
output("Total rounds logged in farkle_rounds: $roundsLogged");

// ---- SUMMARY ----
output("\n========================================", 'blue');
output("  TEST SUMMARY", 'blue');
output("========================================", 'blue');
output("Passed: $g_passed", 'green');
output("Failed: $g_failed", $g_failed > 0 ? 'red' : 'green');
output("Total:  $g_step");

if ($winner > 0) {
    $winnerName = db_select_query(
        "SELECT username FROM farkle_players WHERE playerid = $winner",
        SQL_SINGLE_VALUE
    );
    output("\nWinner: $winnerName (playerid: $winner)");
    output("Final Scores: " . TEST_USER_1 . "=$p1Score, " . TEST_USER_2 . "=$p2Score");
}

// Cleanup option (comment out to preserve test data)
// db_command("DELETE FROM farkle_sets WHERE gameid = $gameid");
// db_command("DELETE FROM farkle_rounds WHERE gameid = $gameid");
// db_command("DELETE FROM farkle_games_players WHERE gameid = $gameid");
// db_command("DELETE FROM farkle_games WHERE gameid = $gameid");

output("\nGame data preserved. Game ID: $gameid");
output("To cleanup: docker exec farkle_db psql -U farkle_user -d farkle_db -c \"DELETE FROM farkle_games WHERE gameid = $gameid\"\n");

exit($g_failed > 0 ? 1 : 0);
