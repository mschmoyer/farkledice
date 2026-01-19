<?php
/**
 * Simple inline test of prompt generator functions
 */

require_once('farkleBotAI_Claude.php');

echo "Testing buildRiskToleranceGuidance():\n";
echo "=====================================\n\n";

for ($i = 1; $i <= 10; $i += 2) {
    echo "Risk Level {$i}:\n";
    echo buildRiskToleranceGuidance($i) . "\n\n";
}

echo "\nTesting buildTrashTalkGuidance():\n";
echo "=====================================\n\n";

for ($i = 1; $i <= 10; $i += 2) {
    echo "Trash Talk Level {$i}:\n";
    echo buildTrashTalkGuidance($i) . "\n\n";
}

echo "\nTesting buildBotSystemPrompt():\n";
echo "=====================================\n\n";

// Sample personality data
$samplePersonality = [
    'name' => 'TestBot',
    'personality_prompt' => 'You are a friendly AI learning to play Farkle. You are enthusiastic but cautious.',
    'play_style_tendencies' => 'You prefer to bank early rather than risk farkle. You value consistent small scores.',
    'conversation_style' => 'Upbeat and encouraging. You celebrate your own successes with innocent enthusiasm.',
    'risk_tolerance' => 3,
    'trash_talk_level' => 2
];

$prompt = buildBotSystemPrompt($samplePersonality, 'easy');

echo "Generated prompt length: " . strlen($prompt) . " characters\n\n";
echo "First 500 characters:\n";
echo substr($prompt, 0, 500) . "\n...\n\n";

// Check key sections are present
$checks = [
    'Contains bot name' => strpos($prompt, 'TestBot') !== false,
    'Contains PERSONALITY section' => strpos($prompt, '=== YOUR PERSONALITY ===') !== false,
    'Contains PLAY STYLE section' => strpos($prompt, '=== YOUR PLAY STYLE ===') !== false,
    'Contains CONVERSATION section' => strpos($prompt, '=== YOUR CONVERSATION STYLE ===') !== false,
    'Contains RULES section' => strpos($prompt, '=== FARKLE GAME RULES ===') !== false,
    'Contains SCORING section' => strpos($prompt, '=== FARKLE SCORING REFERENCE ===') !== false,
    'Contains DECISIONS section' => strpos($prompt, '=== HOW TO MAKE DECISIONS ===') !== false,
    'Contains risk guidance' => strpos($prompt, 'RISK TOLERANCE') !== false,
    'Contains trash talk guidance' => strpos($prompt, 'CHAT MESSAGE TONE') !== false,
];

echo "Validation Checks:\n";
foreach ($checks as $check => $passed) {
    echo "  " . ($passed ? '✓' : '✗') . " {$check}\n";
}

$allPassed = array_reduce($checks, function($carry, $item) { return $carry && $item; }, true);
echo "\nOverall: " . ($allPassed ? "✓ ALL CHECKS PASSED" : "✗ SOME CHECKS FAILED") . "\n";
?>
