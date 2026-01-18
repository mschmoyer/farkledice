<?php
/**
 * Test script for bot AI algorithms
 * Run from project root
 */

// Mock the BaseUtil_Debug function since we're not loading the full framework
if (!function_exists('BaseUtil_Debug')) {
	function BaseUtil_Debug($msg, $level, $color = '') {
		// Suppress debug output for cleaner tests
	}
}

if (!function_exists('BaseUtil_Error')) {
	function BaseUtil_Error($msg) {
		error_log($msg);
	}
}

require_once(__DIR__ . '/wwwroot/farkleBotAI.php');

echo "Bot AI Algorithm Test Suite\n";
echo "============================\n\n";

// Test 1: Bot_GetAllScoringCombinations
echo "Test 1: Get All Scoring Combinations\n";
echo "======================================\n";
$roll = [1, 1, 2, 5, 5, 3];
$combos = Bot_GetAllScoringCombinations($roll);
echo "Roll: " . implode(', ', $roll) . "\n";
echo "Found " . count($combos) . " combinations:\n";
foreach ($combos as $i => $combo) {
	echo "  " . ($i+1) . ") " . $combo['description'] . " = " . $combo['points'] . " points\n";
}
echo "\n";

// Test 2: Farkle Probabilities
echo "Test 2: Farkle Probabilities\n";
echo "============================\n";
for ($i = 1; $i <= 6; $i++) {
	$prob = Bot_CalculateFarkleProbability($i);
	$pct = round($prob * 100, 1);
	echo "$i dice: $pct% chance of farkle\n";
}
echo "\n";

// Test 3: Easy Algorithm
echo "Test 3: Easy Algorithm Decision\n";
echo "================================\n";
$botPlayer = ['bot_algorithm' => 'easy', 'username' => 'TestBot'];
$gameData = [
	'bot_score' => 2000,
	'opponent_score' => 2500,
	'current_round' => 3,
	'total_rounds' => 10
];
$roll = [1, 5, 2, 3, 4, 6];
$decision = Bot_MakeDecision($botPlayer, $gameData, $roll, 0, 6);
echo "Roll: " . implode(', ', $roll) . "\n";
echo "Farkled: " . ($decision['farkled'] ? 'Yes' : 'No') . "\n";
if (!$decision['farkled']) {
	echo "Keeper choice: " . $decision['keeper_choice']['description'] . " = " . $decision['keeper_choice']['points'] . " points\n";
	echo "Should roll again: " . ($decision['should_roll'] ? 'Yes' : 'No') . "\n";
	echo "New turn score: " . $decision['new_turn_score'] . "\n";
	echo "Dice remaining: " . $decision['new_dice_remaining'] . "\n";
}
echo "\n";

// Test 4: Medium Algorithm
echo "Test 4: Medium Algorithm Decision\n";
echo "==================================\n";
$botPlayer['bot_algorithm'] = 'medium';
$decision = Bot_MakeDecision($botPlayer, $gameData, $roll, 0, 6);
echo "Roll: " . implode(', ', $roll) . "\n";
echo "Farkled: " . ($decision['farkled'] ? 'Yes' : 'No') . "\n";
if (!$decision['farkled']) {
	echo "Keeper choice: " . $decision['keeper_choice']['description'] . " = " . $decision['keeper_choice']['points'] . " points\n";
	echo "Should roll again: " . ($decision['should_roll'] ? 'Yes' : 'No') . "\n";
}
echo "\n";

// Test 5: Hard Algorithm
echo "Test 5: Hard Algorithm Decision\n";
echo "================================\n";
$botPlayer['bot_algorithm'] = 'hard';
$decision = Bot_MakeDecision($botPlayer, $gameData, $roll, 0, 6);
echo "Roll: " . implode(', ', $roll) . "\n";
echo "Farkled: " . ($decision['farkled'] ? 'Yes' : 'No') . "\n";
if (!$decision['farkled']) {
	echo "Keeper choice: " . $decision['keeper_choice']['description'] . " = " . $decision['keeper_choice']['points'] . " points\n";
	echo "Should roll again: " . ($decision['should_roll'] ? 'Yes' : 'No') . "\n";
}
echo "\n";

// Test 6: Farkle scenario
echo "Test 6: Farkle Scenario\n";
echo "========================\n";
$farkleRoll = [2, 3, 4, 6, 2, 3];
$decision = Bot_MakeDecision($botPlayer, $gameData, $farkleRoll, 0, 6);
echo "Roll: " . implode(', ', $farkleRoll) . "\n";
echo "Farkled: " . ($decision['farkled'] ? 'Yes' : 'No') . "\n";
echo "\n";

// Test 7: Three of a kind
echo "Test 7: Three of a Kind\n";
echo "========================\n";
$tripleRoll = [3, 3, 3, 2, 4, 6];
$combos = Bot_GetAllScoringCombinations($tripleRoll);
echo "Roll: " . implode(', ', $tripleRoll) . "\n";
echo "Found " . count($combos) . " combinations:\n";
foreach ($combos as $i => $combo) {
	echo "  " . ($i+1) . ") " . $combo['description'] . " = " . $combo['points'] . " points\n";
}
echo "\n";

// Test 8: Straight (1-2-3-4-5-6)
echo "Test 8: Straight\n";
echo "================\n";
$straightRoll = [1, 2, 3, 4, 5, 6];
$combos = Bot_GetAllScoringCombinations($straightRoll);
echo "Roll: " . implode(', ', $straightRoll) . "\n";
echo "Found " . count($combos) . " combinations:\n";
foreach (array_slice($combos, 0, 5) as $i => $combo) {
	echo "  " . ($i+1) . ") " . $combo['description'] . " = " . $combo['points'] . " points\n";
}
echo "\n";

// Test 9: Three pairs
echo "Test 9: Three Pairs\n";
echo "===================\n";
$threePairsRoll = [2, 2, 3, 3, 4, 4];
$combos = Bot_GetAllScoringCombinations($threePairsRoll);
echo "Roll: " . implode(', ', $threePairsRoll) . "\n";
echo "Found " . count($combos) . " combinations:\n";
foreach ($combos as $i => $combo) {
	echo "  " . ($i+1) . ") " . $combo['description'] . " = " . $combo['points'] . " points\n";
}
echo "\n";

// Test 10: Two triplets
echo "Test 10: Two Triplets\n";
echo "=====================\n";
$twoTripletsRoll = [2, 2, 2, 5, 5, 5];
$combos = Bot_GetAllScoringCombinations($twoTripletsRoll);
echo "Roll: " . implode(', ', $twoTripletsRoll) . "\n";
echo "Found " . count($combos) . " combinations:\n";
foreach ($combos as $i => $combo) {
	echo "  " . ($i+1) . ") " . $combo['description'] . " = " . $combo['points'] . " points\n";
}
echo "\n";

echo "✓ All tests completed successfully!\n";
?>
