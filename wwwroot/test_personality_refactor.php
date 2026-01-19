<?php
/**
 * Test script for personality refactoring
 *
 * Verifies that:
 * 1. Personalities can be loaded from code configuration
 * 2. Database personality_id mapping works correctly
 * 3. System prompts are properly generated
 * 4. Bot decision-making still functions
 */

require_once('farkleBotAI.php');
require_once('farkleBotPersonalities.php');

echo "=== Bot Personality Refactoring Test ===\n\n";

// Test 1: Load personality by key
echo "Test 1: Load personality by key\n";
$personality = fetchBotPersonality('byte');
if ($personality) {
    echo "✓ Successfully loaded 'byte' personality\n";
    echo "  Name: {$personality['name']}\n";
    echo "  Difficulty: {$personality['difficulty']}\n";
    echo "  Risk Tolerance: {$personality['risk_tolerance']}\n";
    echo "  Has system_prompt: " . (isset($personality['system_prompt']) ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ Failed to load 'byte' personality\n";
}
echo "\n";

// Test 2: Load personality by database ID (backward compatibility)
echo "Test 2: Load personality by database ID (backward compatibility)\n";
$personalityById = fetchBotPersonality(9); // ID 9 = Glitch
if ($personalityById) {
    echo "✓ Successfully loaded personality ID 9 (mapped to key)\n";
    echo "  Name: {$personalityById['name']}\n";
    echo "  Expected: Glitch\n";
    if ($personalityById['name'] === 'Glitch') {
        echo "✓ Mapping is correct\n";
    } else {
        echo "✗ Mapping is incorrect\n";
    }
} else {
    echo "✗ Failed to load personality by ID\n";
}
echo "\n";

// Test 3: Load personality by name
echo "Test 3: Load personality by name\n";
$personalityByName = fetchBotPersonalityByName('Prime');
if ($personalityByName) {
    echo "✓ Successfully loaded personality by name 'Prime'\n";
    echo "  Key: prime\n";
    echo "  Difficulty: {$personalityByName['difficulty']}\n";
} else {
    echo "✗ Failed to load personality by name\n";
}
echo "\n";

// Test 4: Verify system prompt generation
echo "Test 4: Verify system prompt generation\n";
require_once('farkleBotAI_Claude.php');

$testPersonality = fetchBotPersonality('neural');
if ($testPersonality) {
    $systemPrompt = buildBotSystemPrompt($testPersonality);
    if (!empty($systemPrompt)) {
        echo "✓ System prompt generated successfully\n";
        echo "  Length: " . strlen($systemPrompt) . " characters\n";
        echo "  Contains bot name: " . (strpos($systemPrompt, 'Neural') !== false ? 'Yes' : 'No') . "\n";
        echo "  Contains rules: " . (strpos($systemPrompt, 'FARKLE') !== false ? 'Yes' : 'No') . "\n";

        // Show first 200 chars
        echo "\n  First 200 chars of prompt:\n";
        echo "  " . substr($systemPrompt, 0, 200) . "...\n";
    } else {
        echo "✗ System prompt is empty\n";
    }
} else {
    echo "✗ Failed to load personality for system prompt test\n";
}
echo "\n";

// Test 5: Get all personalities by difficulty
echo "Test 5: Get all personalities by difficulty\n";
$easyBots = getBotPersonalitiesByDifficulty('easy');
$mediumBots = getBotPersonalitiesByDifficulty('medium');
$hardBots = getBotPersonalitiesByDifficulty('hard');

echo "  Easy bots: " . count($easyBots) . " (expected: 5)\n";
echo "  Medium bots: " . count($mediumBots) . " (expected: 5)\n";
echo "  Hard bots: " . count($hardBots) . " (expected: 5)\n";

if (count($easyBots) === 5 && count($mediumBots) === 5 && count($hardBots) === 5) {
    echo "✓ Difficulty filtering works correctly\n";
} else {
    echo "✗ Difficulty filtering has issues\n";
}
echo "\n";

// Test 6: List all personalities
echo "Test 6: List all personalities\n";
$allPersonalities = getBotPersonalities();
echo "  Total personalities: " . count($allPersonalities) . " (expected: 15)\n";

foreach ($allPersonalities as $key => $p) {
    echo "  - {$key}: {$p['name']} ({$p['difficulty']})\n";
}

if (count($allPersonalities) === 15) {
    echo "✓ All personalities loaded correctly\n";
} else {
    echo "✗ Expected 15 personalities, found " . count($allPersonalities) . "\n";
}
echo "\n";

// Test 7: Verify personality_id to key mapping
echo "Test 7: Verify personality_id to key mapping\n";
$mappingTests = [
    1 => 'byte',
    5 => 'dot',
    9 => 'glitch',
    13 => 'apex',
    15 => 'prime',
];

$allMappingsCorrect = true;
foreach ($mappingTests as $id => $expectedKey) {
    $key = mapPersonalityIdToKey($id);
    if ($key === $expectedKey) {
        echo "  ✓ ID {$id} → {$key}\n";
    } else {
        echo "  ✗ ID {$id} → {$key} (expected: {$expectedKey})\n";
        $allMappingsCorrect = false;
    }
}

if ($allMappingsCorrect) {
    echo "✓ All ID mappings are correct\n";
} else {
    echo "✗ Some ID mappings are incorrect\n";
}
echo "\n";

echo "=== Test Complete ===\n";
?>
