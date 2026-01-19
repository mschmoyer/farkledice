<?php
/**
 * Example usage of buildGameContext() function
 *
 * Demonstrates how to build a game context payload for Claude API calls
 * in a typical bot turn scenario.
 */

require_once('farkleBotAI_Claude.php');

echo "═══════════════════════════════════════════════════════════════\n";
echo "  Example: Building Game Context for Claude API\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// SCENARIO: Bot "Byte" is playing against a human player "testuser"
// - Standard game mode to 10,000 points
// - Currently Round 3
// - Bot is leading (2,500 vs 2,000)
// - Bot just rolled [1, 1, 1, 5, 3, 6] - three 1s and a 5!
// - Bot has 4 dice available and 200 points accumulated this turn

echo "GAME SCENARIO:\n";
echo "  Game Mode: Standard (to 10,000 points)\n";
echo "  Current Round: 3\n";
echo "  Bot 'Byte' Score: 2,500 points\n";
echo "  Opponent 'testuser' Score: 2,000 points\n";
echo "  Current Roll: [1, 1, 1, 5, 3, 6]\n";
echo "  Turn Score So Far: 200 points\n";
echo "  Dice Available: 6 (this is the full roll)\n\n";

// Step 1: Prepare game state
$gameState = [
    'game_mode' => 'standard',
    'current_round' => 3,
    'points_to_win' => 10000,
    'dice_available' => 6,  // Number of dice available for this roll
    'current_roll' => [1, 1, 1, 5, 3, 6],  // The dice values rolled
    'turn_score' => 200,  // Points accumulated this turn (not yet banked)
    'round_score' => 200  // Points accumulated this round (for 10-round mode)
];

// Step 2: Prepare bot player data
$botPlayerData = [
    'playerid' => 456,
    'username' => 'Byte',
    'total_score' => 2500,
    'round_score' => 200,
    'level' => 8
];

// Step 3: Prepare opponent data (can be multiple opponents)
$opponentData = [
    [
        'username' => 'testuser',
        'total_score' => 2000,
        'round_score' => 150
    ]
];

echo "BUILDING GAME CONTEXT...\n\n";

// Step 4: Build the game context
$gameContext = buildGameContext($gameState, $botPlayerData, $opponentData);

// Step 5: Display the result
echo "GAME CONTEXT BUILT SUCCESSFULLY!\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Context Structure:\n";
echo json_encode($gameContext, JSON_PRETTY_PRINT) . "\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  Analysis of the Context\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Bot Position: " . $gameContext['bot_status']['position'] . "\n";
echo "  (Bot has " . $gameContext['bot_status']['total_score'] . " points vs opponent's " .
     $gameContext['opponents'][0]['total_score'] . " points)\n\n";

echo "Available Scoring Combinations:\n";
foreach ($gameContext['dice_state']['scoring_combinations_available'] as $i => $combo) {
    echo "  " . ($i + 1) . ". [" . implode(', ', $combo['dice']) . "] = " .
         $combo['points'] . " points (" . $combo['description'] . ")\n";
}
echo "\n";

echo "Strategic Considerations:\n";
$farklePct = $gameContext['farkle_probability'] * 100;
echo "  - Farkle Probability: " . number_format($farklePct, 1) . "%\n";
echo "  - Best combination: " . $gameContext['dice_state']['scoring_combinations_available'][0]['description'] .
     " for " . $gameContext['dice_state']['scoring_combinations_available'][0]['points'] . " points\n";

$bestCombo = $gameContext['dice_state']['scoring_combinations_available'][0];
$diceUsed = count($bestCombo['dice']);
$diceRemaining = $gameContext['dice_state']['dice_available'] - $diceUsed;
echo "  - If bot takes the best combo, " . $diceRemaining . " dice will remain for next roll\n";

// Calculate new turn score if best combo is taken
$newTurnScore = $gameContext['bot_status']['turn_score_so_far'] + $bestCombo['points'];
echo "  - New turn score would be: " . $newTurnScore . " points\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  How to Use This Context with Claude API\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "The game context can now be sent to Claude API as part of the user message:\n\n";

echo "Example API call structure:\n";
echo "  \$systemPrompt = buildBotSystemPrompt(\$personalityData);\n";
echo "  \$userMessage = \"Current game state: \" . json_encode(\$gameContext);\n";
echo "  \$messages = [['role' => 'user', 'content' => \$userMessage]];\n";
echo "  \$tools = getBotDecisionTools();\n";
echo "  \$response = callClaudeAPI(\$systemPrompt, \$messages, \$tools);\n";
echo "  \$decision = parseBotDecision(\$response);\n\n";

echo "Claude will analyze the context and return a structured decision:\n";
echo "  - Which dice combination to select\n";
echo "  - Whether to roll again or bank\n";
echo "  - Reasoning for the decision\n";
echo "  - A personality-driven chat message\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  Example Complete!\n";
echo "═══════════════════════════════════════════════════════════════\n";

?>
