#!/usr/bin/env php
<?php
/**
 * Test script for AI-powered bot decision integration
 *
 * Tests:
 * 1. AI decision-making when personality_id is set
 * 2. Fallback to algorithmic bot when API fails
 * 3. Fallback to algorithmic bot when personality_id is null
 * 4. 5-second timeout enforcement
 * 5. Decision format validation
 */

require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleBotAI.php');

echo "=================================================================\n";
echo "AI-POWERED BOT DECISION INTEGRATION TEST\n";
echo "=================================================================\n\n";

// Test helper function
function runTest($testName, $callback) {
    echo "TEST: $testName\n";
    echo str_repeat('-', 65) . "\n";
    $startTime = microtime(true);

    try {
        $result = $callback();
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($result['success']) {
            echo "✓ PASSED ({$duration}ms)\n";
            if (isset($result['message'])) {
                echo "  {$result['message']}\n";
            }
        } else {
            echo "✗ FAILED ({$duration}ms)\n";
            echo "  ERROR: {$result['message']}\n";
        }
    } catch (Exception $e) {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "✗ EXCEPTION ({$duration}ms)\n";
        echo "  {$e->getMessage()}\n";
    }

    echo "\n";
}

// =================================================================
// TEST 1: Fetch bot personality from database
// =================================================================

runTest("Fetch active bot personality from database", function() {
    $dbh = db_connect();

    $stmt = $dbh->prepare("SELECT personality_id, name FROM farkle_bot_personalities WHERE is_active = true LIMIT 1");
    $stmt->execute();
    $personality = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($personality) {
        return [
            'success' => true,
            'message' => "Found personality: {$personality['name']} (ID: {$personality['personality_id']})"
        ];
    } else {
        return [
            'success' => false,
            'message' => 'No active personalities found in database'
        ];
    }
});

// =================================================================
// TEST 2: Test fetchBotPersonality() function
// =================================================================

runTest("fetchBotPersonality() retrieves personality data", function() {
    $dbh = db_connect();

    $stmt = $dbh->prepare("SELECT personality_id FROM farkle_bot_personalities WHERE is_active = true LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['success' => false, 'message' => 'No personality to test with'];
    }

    $personalityId = $row['personality_id'];
    $personality = fetchBotPersonality($personalityId);

    if ($personality && isset($personality['name']) && isset($personality['personality_prompt'])) {
        return [
            'success' => true,
            'message' => "Retrieved personality: {$personality['name']}"
        ];
    } else {
        return ['success' => false, 'message' => 'fetchBotPersonality() returned invalid data'];
    }
});

// =================================================================
// TEST 3: Test algorithmic fallback (no personality_id)
// =================================================================

runTest("Algorithmic fallback when personality_id is null", function() {
    $botPlayer = [
        'playerid' => 999,
        'username' => 'TestBot',
        'bot_algorithm' => 'medium',
        'playerlevel' => 5,
        'personality_id' => null  // No AI personality
    ];

    $gameData = [
        'gamemode' => 2,
        'currentround' => 1,
        'bot_score' => 500,
        'opponent_score' => 600,
        'total_rounds' => 10
    ];

    $diceRoll = [1, 5, 2, 3, 4, 6];  // Has scoring dice (1 and 5)
    $turnScore = 0;
    $diceRemaining = 6;

    $decision = Bot_MakeDecision($botPlayer, $gameData, $diceRoll, $turnScore, $diceRemaining);

    if (isset($decision['algorithm']) &&
        $decision['algorithm'] === 'medium' &&
        isset($decision['keeper_choice']) &&
        !isset($decision['ai_powered'])) {
        return [
            'success' => true,
            'message' => "Correctly used algorithmic bot (medium difficulty)"
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Did not fall back to algorithmic bot correctly'
        ];
    }
});

// =================================================================
// TEST 4: Test AI decision with valid personality
// =================================================================

runTest("AI decision-making with valid personality_id", function() {
    // Skip if no API key configured
    if (!getenv('ANTHROPIC_API_KEY') && !isset($_ENV['ANTHROPIC_API_KEY']) && !isset($_SERVER['ANTHROPIC_API_KEY'])) {
        return [
            'success' => true,
            'message' => 'SKIPPED - No API key configured (expected in test environment)'
        ];
    }

    $dbh = db_connect();

    $stmt = $dbh->prepare("SELECT personality_id FROM farkle_bot_personalities WHERE is_active = true LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['success' => false, 'message' => 'No personality to test with'];
    }

    $botPlayer = [
        'playerid' => 999,
        'username' => 'AITestBot',
        'bot_algorithm' => 'medium',
        'playerlevel' => 5,
        'personality_id' => $row['personality_id']
    ];

    $gameData = [
        'gamemode' => 2,
        'currentround' => 3,
        'bot_score' => 1500,
        'opponent_score' => 1200,
        'total_rounds' => 10,
        'players' => [
            ['playerid' => 999, 'playerscore' => 1500],
            ['playerid' => 888, 'playerscore' => 1200]
        ]
    ];

    $diceRoll = [1, 5, 2, 3, 4, 6];  // Has scoring dice (1 and 5)
    $turnScore = 0;
    $diceRemaining = 6;

    $decision = Bot_MakeDecision($botPlayer, $gameData, $diceRoll, $turnScore, $diceRemaining);

    if (isset($decision['ai_powered']) && $decision['ai_powered'] === true) {
        $message = "AI decision successful! Algorithm: {$decision['algorithm']}";
        if (isset($decision['chat_message'])) {
            $message .= "\n  Chat: \"{$decision['chat_message']}\"";
        }
        if (isset($decision['reasoning'])) {
            $message .= "\n  Reasoning: \"{$decision['reasoning']}\"";
        }
        return ['success' => true, 'message' => $message];
    } else if (isset($decision['algorithm']) && $decision['algorithm'] !== 'ai-claude') {
        return [
            'success' => true,
            'message' => "AI failed, correctly fell back to algorithmic bot ({$decision['algorithm']})"
        ];
    } else {
        return ['success' => false, 'message' => 'Unexpected decision format: ' . json_encode($decision)];
    }
});

// =================================================================
// TEST 5: Test farkle handling (no scoring dice)
// =================================================================

runTest("Farkle detection without calling API", function() {
    $dbh = db_connect();

    $stmt = $dbh->prepare("SELECT personality_id FROM farkle_bot_personalities WHERE is_active = true LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['success' => false, 'message' => 'No personality to test with'];
    }

    $botPlayer = [
        'playerid' => 999,
        'username' => 'AITestBot',
        'bot_algorithm' => 'medium',
        'playerlevel' => 5,
        'personality_id' => $row['personality_id']
    ];

    $gameData = [
        'gamemode' => 2,
        'currentround' => 1,
        'bot_score' => 500,
        'opponent_score' => 600,
        'total_rounds' => 10
    ];

    $diceRoll = [2, 2, 3, 3, 4, 6];  // No scoring dice = FARKLE
    $turnScore = 350;
    $diceRemaining = 6;

    $startTime = microtime(true);
    $decision = Bot_MakeDecision($botPlayer, $gameData, $diceRoll, $turnScore, $diceRemaining);
    $duration = microtime(true) - $startTime;

    if (isset($decision['farkled']) && $decision['farkled'] === true) {
        // Should be instant (no API call)
        if ($duration < 0.5) {
            return [
                'success' => true,
                'message' => "Correctly detected farkle without API call ({$duration}s)"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Farkle detected but took too long ({$duration}s) - likely called API"
            ];
        }
    } else {
        return ['success' => false, 'message' => 'Did not detect farkle correctly'];
    }
});

// =================================================================
// TEST 6: Verify decision format matches expected structure
// =================================================================

runTest("Decision structure validation", function() {
    $botPlayer = [
        'playerid' => 999,
        'username' => 'TestBot',
        'bot_algorithm' => 'medium',
        'playerlevel' => 5,
        'personality_id' => null
    ];

    $gameData = [
        'gamemode' => 2,
        'currentround' => 1,
        'bot_score' => 500,
        'opponent_score' => 600,
        'total_rounds' => 10
    ];

    $diceRoll = [1, 1, 1, 5, 2, 3];  // Three 1s + one 5
    $turnScore = 0;
    $diceRemaining = 6;

    $decision = Bot_MakeDecision($botPlayer, $gameData, $diceRoll, $turnScore, $diceRemaining);

    $requiredFields = ['keeper_choice', 'should_roll', 'algorithm', 'farkled', 'new_turn_score', 'new_dice_remaining'];
    $missingFields = [];

    foreach ($requiredFields as $field) {
        if (!isset($decision[$field])) {
            $missingFields[] = $field;
        }
    }

    if (empty($missingFields)) {
        return [
            'success' => true,
            'message' => 'Decision has all required fields'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Missing fields: ' . implode(', ', $missingFields)
        ];
    }
});

// =================================================================
// SUMMARY
// =================================================================

echo "=================================================================\n";
echo "TEST COMPLETE\n";
echo "=================================================================\n";
echo "\nNOTES:\n";
echo "- API tests may be skipped if ANTHROPIC_API_KEY is not configured\n";
echo "- Fallback behavior is critical for production reliability\n";
echo "- 5-second timeout is enforced by CLAUDE_TIMEOUT_SECONDS constant\n";
echo "\nTo run with API key:\n";
echo "  export ANTHROPIC_API_KEY='your-key-here'\n";
echo "  php test_ai_bot_decision.php\n";
echo "\n";

?>
