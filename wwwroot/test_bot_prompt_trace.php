<?php
/**
 * Test script to trace bot personality loading and system prompt generation
 *
 * Shows the complete flow of:
 * 1. Loading personality from code configuration
 * 2. Building system prompt
 * 3. Verifying prompt structure
 */

require_once('farkleBotAI.php');
require_once('farkleBotAI_Claude.php');

echo "=== Bot Personality System Prompt Trace ===\n\n";

// Test with different personality types
$testBots = ['byte', 'glitch', 'prime'];

foreach ($testBots as $botKey) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Testing Bot: " . strtoupper($botKey) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Step 1: Load personality
    echo "Step 1: Load personality from configuration\n";
    $personality = fetchBotPersonality($botKey);

    if (!$personality) {
        echo "✗ Failed to load personality: {$botKey}\n\n";
        continue;
    }

    echo "✓ Personality loaded successfully\n";
    echo "  Name: {$personality['name']}\n";
    echo "  Difficulty: {$personality['difficulty']}\n";
    echo "  Personality Type: {$personality['personality_type']}\n";
    echo "  Risk Tolerance: {$personality['risk_tolerance']}/10\n";
    echo "  Trash Talk Level: {$personality['trash_talk_level']}/10\n";
    echo "\n";

    // Step 2: Build system prompt
    echo "Step 2: Build system prompt\n";
    $systemPrompt = buildBotSystemPrompt($personality);

    if (empty($systemPrompt)) {
        echo "✗ System prompt is empty\n\n";
        continue;
    }

    echo "✓ System prompt built successfully\n";
    echo "  Total length: " . strlen($systemPrompt) . " characters\n";
    echo "\n";

    // Step 3: Verify prompt structure
    echo "Step 3: Verify prompt structure\n";

    $requiredSections = [
        'YOUR PERSONALITY' => false,
        'YOUR PLAY STYLE' => false,
        'YOUR CONVERSATION STYLE' => false,
        'RISK TOLERANCE' => false,
        'CHAT MESSAGE TONE' => false,
        'FARKLE GAME RULES' => false,
        'FARKLE SCORING REFERENCE' => false,
        'HOW TO MAKE DECISIONS' => false,
        'make_farkle_decision' => false,
    ];

    foreach ($requiredSections as $section => &$found) {
        $found = (strpos($systemPrompt, $section) !== false);
    }

    $allFound = true;
    foreach ($requiredSections as $section => $found) {
        $status = $found ? '✓' : '✗';
        echo "  {$status} Contains '{$section}'\n";
        if (!$found) {
            $allFound = false;
        }
    }

    if ($allFound) {
        echo "\n✓ All required sections present\n";
    } else {
        echo "\n✗ Some sections missing\n";
    }

    echo "\n";

    // Step 4: Show excerpt from system prompt
    echo "Step 4: System prompt excerpt\n";
    echo "─────────────────────────────────────────────────────────────\n";

    // Extract the personality section
    $personalityStart = strpos($systemPrompt, '=== YOUR PERSONALITY ===');
    $personalityEnd = strpos($systemPrompt, '=== YOUR PLAY STYLE ===', $personalityStart);

    if ($personalityStart !== false && $personalityEnd !== false) {
        $personalitySection = substr($systemPrompt, $personalityStart, $personalityEnd - $personalityStart);
        echo trim($personalitySection) . "\n";
    } else {
        echo "(Could not extract personality section)\n";
    }

    echo "─────────────────────────────────────────────────────────────\n";
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Trace Complete\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Summary of how to use the system
echo "\n";
echo "=== USAGE SUMMARY ===\n\n";
echo "Adding a new bot personality:\n";
echo "1. Edit wwwroot/farkleBotPersonalities.php\n";
echo "2. Add a new entry to the array returned by getBotPersonalities()\n";
echo "3. Use buildSystemPrompt() to generate the full system prompt\n";
echo "4. The system_prompt field contains everything Claude needs\n";
echo "\n";
echo "Loading a personality:\n";
echo "- By key: fetchBotPersonality('byte')\n";
echo "- By ID (backward compat): fetchBotPersonality(1)\n";
echo "- By name: fetchBotPersonalityByName('Byte')\n";
echo "\n";
echo "All personalities are now in code, not the database!\n";
echo "This makes it easy to version control, deploy, and modify bot behavior.\n";
?>
