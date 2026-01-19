<?php
/**
 * Test script for system prompt generator
 *
 * Tests the buildBotSystemPrompt() function with different bot personalities
 * to verify that prompts are generated correctly and differ based on personality.
 */

// Include required files
require_once('../includes/baseutil.php');
require_once('../includes/dbutil.php');
require_once('farkleBotAI_Claude.php');

// Set up session (required by baseutil.php)
BaseUtil_SessSet();

// Connect to database
$db = db_connect();
if (!$db) {
    die("ERROR: Could not connect to database\n");
}

echo "=================================================================\n";
echo "FARKLE BOT SYSTEM PROMPT GENERATOR TEST\n";
echo "=================================================================\n\n";

// Fetch all bot personalities from database
$query = "SELECT * FROM farkle_bot_personalities WHERE is_active = true ORDER BY personality_id LIMIT 5";
$result = db_query($db, $query);

if (!$result) {
    die("ERROR: Could not fetch bot personalities from database\n");
}

$personalities = db_fetch_all($result);

if (empty($personalities)) {
    die("ERROR: No active bot personalities found in database\n");
}

echo "Found " . count($personalities) . " active bot personalities\n\n";

// Test prompt generation for each personality
foreach ($personalities as $personality) {
    echo "=================================================================\n";
    echo "TESTING BOT: {$personality['name']} (ID: {$personality['personality_id']})\n";
    echo "=================================================================\n";
    echo "Difficulty: {$personality['difficulty']}\n";
    echo "Personality Type: {$personality['personality_type']}\n";
    echo "Risk Tolerance: {$personality['risk_tolerance']}/10\n";
    echo "Trash Talk Level: {$personality['trash_talk_level']}/10\n";
    echo "\n";

    // Generate system prompt
    $systemPrompt = buildBotSystemPrompt($personality, $personality['difficulty']);

    echo "GENERATED SYSTEM PROMPT:\n";
    echo "-----------------------------------------------------------------\n";
    echo $systemPrompt;
    echo "\n-----------------------------------------------------------------\n";
    echo "Prompt length: " . strlen($systemPrompt) . " characters\n";
    echo "\n";

    // Verify prompt contains key elements
    $hasName = strpos($systemPrompt, $personality['name']) !== false;
    $hasPersonality = strpos($systemPrompt, 'PERSONALITY') !== false;
    $hasPlayStyle = strpos($systemPrompt, 'PLAY STYLE') !== false;
    $hasConversation = strpos($systemPrompt, 'CONVERSATION') !== false;
    $hasRules = strpos($systemPrompt, 'FARKLE GAME RULES') !== false;
    $hasScoring = strpos($systemPrompt, 'SCORING REFERENCE') !== false;
    $hasDecisions = strpos($systemPrompt, 'HOW TO MAKE DECISIONS') !== false;
    $hasRiskTolerance = strpos($systemPrompt, 'RISK TOLERANCE') !== false;
    $hasTrashTalk = strpos($systemPrompt, 'CHAT MESSAGE TONE') !== false;

    echo "VALIDATION CHECKS:\n";
    echo "- Contains bot name: " . ($hasName ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains personality section: " . ($hasPersonality ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains play style section: " . ($hasPlayStyle ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains conversation style: " . ($hasConversation ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains game rules: " . ($hasRules ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains scoring reference: " . ($hasScoring ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains decision instructions: " . ($hasDecisions ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains risk tolerance guidance: " . ($hasRiskTolerance ? "✓ YES" : "✗ NO") . "\n";
    echo "- Contains trash talk guidance: " . ($hasTrashTalk ? "✓ YES" : "✗ NO") . "\n";

    $allChecksPass = $hasName && $hasPersonality && $hasPlayStyle && $hasConversation &&
                     $hasRules && $hasScoring && $hasDecisions && $hasRiskTolerance && $hasTrashTalk;

    echo "\nOVERALL RESULT: " . ($allChecksPass ? "✓ PASS" : "✗ FAIL") . "\n";
    echo "\n\n";
}

// Test with missing fields to verify error handling
echo "=================================================================\n";
echo "TESTING ERROR HANDLING: Missing Required Fields\n";
echo "=================================================================\n";

$invalidPersonality = [
    'name' => 'TestBot',
    'personality_prompt' => 'Test personality',
    // Missing play_style_tendencies and conversation_style
];

$errorPrompt = buildBotSystemPrompt($invalidPersonality);
echo "Prompt with missing fields:\n";
echo $errorPrompt . "\n";
echo "Length: " . strlen($errorPrompt) . " characters\n";
echo "Expected fallback prompt: " . ($errorPrompt === "You are a Farkle bot. Play the game strategically." ? "✓ YES" : "✗ NO") . "\n";
echo "\n";

// Test risk tolerance edge cases
echo "=================================================================\n";
echo "TESTING RISK TOLERANCE EDGE CASES\n";
echo "=================================================================\n";

$riskLevels = [1, 3, 5, 7, 10];
foreach ($riskLevels as $risk) {
    $guidance = buildRiskToleranceGuidance($risk);
    echo "Risk Level {$risk}/10:\n";
    echo substr($guidance, 0, 100) . "...\n\n";
}

// Test trash talk edge cases
echo "=================================================================\n";
echo "TESTING TRASH TALK LEVEL EDGE CASES\n";
echo "=================================================================\n";

$trashLevels = [1, 3, 5, 7, 10];
foreach ($trashLevels as $level) {
    $guidance = buildTrashTalkGuidance($level);
    echo "Trash Talk Level {$level}/10:\n";
    echo substr($guidance, 0, 100) . "...\n\n";
}

// Compare two different personalities to verify they produce different prompts
echo "=================================================================\n";
echo "TESTING PROMPT UNIQUENESS\n";
echo "=================================================================\n";

if (count($personalities) >= 2) {
    $prompt1 = buildBotSystemPrompt($personalities[0]);
    $prompt2 = buildBotSystemPrompt($personalities[1]);

    $areDifferent = $prompt1 !== $prompt2;
    $hasDifferentRisk = strpos($prompt1, "RISK TOLERANCE") !== false &&
                        strpos($prompt2, "RISK TOLERANCE") !== false &&
                        (strpos($prompt1, $personalities[0]['risk_tolerance']) !== false ||
                         strpos($prompt2, $personalities[1]['risk_tolerance']) !== false);

    echo "Comparing {$personalities[0]['name']} vs {$personalities[1]['name']}:\n";
    echo "- Prompts are different: " . ($areDifferent ? "✓ YES" : "✗ NO") . "\n";
    echo "- Risk tolerance varies: " . ($hasDifferentRisk ? "✓ YES" : "✗ NO") . "\n";
    echo "- Bot 1 length: " . strlen($prompt1) . " chars\n";
    echo "- Bot 2 length: " . strlen($prompt2) . " chars\n";
    echo "\n";
}

echo "=================================================================\n";
echo "ALL TESTS COMPLETED\n";
echo "=================================================================\n";
?>
