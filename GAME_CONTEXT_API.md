# Game Context Builder API Reference

## Overview
The game context builder creates structured data payloads for AI bot decision-making via Claude API.

## Function Reference

### `buildGameContext($gameState, $botPlayerData, $opponentData = [])`

Builds a comprehensive game context payload containing all information needed for Claude to make strategic Farkle decisions.

**Location**: `/wwwroot/farkleBotAI_Claude.php`

**Parameters**:

```php
$gameState = [
    'game_mode' => 'standard' | '10round',  // Game type
    'current_round' => int,                  // Current round number (1-10)
    'points_to_win' => int,                  // Target score (10000 for standard)
    'dice_available' => int,                 // Number of dice that can be rolled (1-6)
    'current_roll' => array,                 // Array of dice values [1-6]
    'turn_score' => int,                     // Points accumulated this turn (not banked)
    'round_score' => int                     // Points accumulated this round
];

$botPlayerData = [
    'playerid' => int,                       // Bot's player ID
    'username' => string,                    // Bot's display name
    'total_score' => int,                    // Bot's total game score
    'round_score' => int,                    // Bot's current round score
    'level' => int                           // Bot's level (optional)
];

$opponentData = [
    [
        'username' => string,                // Opponent's username
        'total_score' => int,                // Opponent's total score
        'round_score' => int                 // Opponent's round score
    ],
    // ... additional opponents
];
```

**Returns**:

```php
[
    'game_mode' => string,                   // 'standard' or '10round'
    'current_round' => int,
    'points_to_win' => int,
    'bot_status' => [
        'total_score' => int,
        'round_score' => int,
        'turn_score_so_far' => int,
        'position' => string                 // 'leading', 'tied', or 'trailing'
    ],
    'opponents' => [
        ['username' => string, 'total_score' => int, 'round_score' => int],
        ...
    ],
    'dice_state' => [
        'dice_available' => int,
        'current_roll' => array,
        'scoring_combinations_available' => [
            [
                'dice' => array,             // Die values to keep
                'points' => int,             // Point value
                'description' => string      // Human-readable description
            ],
            ...
        ]
    ],
    'farkle_probability' => float            // 0.0 to 1.0
]
```

**Security**: All user-controlled data is automatically sanitized via `sanitizeGameContext()` to prevent prompt injection attacks.

## Usage Examples

### Example 1: Basic Usage

```php
<?php
require_once('farkleBotAI_Claude.php');

// Bot just rolled [1, 5, 3, 4] with 4 dice
$gameState = [
    'game_mode' => 'standard',
    'current_round' => 1,
    'points_to_win' => 10000,
    'dice_available' => 4,
    'current_roll' => [1, 5, 3, 4],
    'turn_score' => 150,
    'round_score' => 150
];

$botPlayerData = [
    'playerid' => 123,
    'username' => 'Byte',
    'total_score' => 1500,
    'round_score' => 150
];

$opponentData = [
    ['username' => 'testuser', 'total_score' => 2000, 'round_score' => 100]
];

$context = buildGameContext($gameState, $botPlayerData, $opponentData);

// Result: Bot is trailing, has 150 points scored, 15.43% farkle risk with 4 dice
echo "Position: " . $context['bot_status']['position']; // "trailing"
echo "Farkle Risk: " . ($context['farkle_probability'] * 100) . "%"; // "15.43%"
?>
```

### Example 2: Multiple Opponents (10-Round Mode)

```php
<?php
// 10-round tournament with 3 players
$gameState = [
    'game_mode' => '10round',
    'current_round' => 7,
    'points_to_win' => 0,  // Not applicable for 10-round
    'dice_available' => 6,
    'current_roll' => [1, 1, 1, 5, 5, 6],
    'turn_score' => 0,
    'round_score' => 0
];

$botPlayerData = [
    'playerid' => 456,
    'username' => 'Chaos',
    'total_score' => 8500,
    'round_score' => 0
];

$opponentData = [
    ['username' => 'Player1', 'total_score' => 9000, 'round_score' => 500],
    ['username' => 'Player2', 'total_score' => 7500, 'round_score' => 300]
];

$context = buildGameContext($gameState, $botPlayerData, $opponentData);

// Result: Bot is trailing (8500 vs 9000), has three 1s + two 5s available
echo "Position: " . $context['bot_status']['position']; // "trailing"
echo "Best combo: " . $context['dice_state']['scoring_combinations_available'][0]['description'];
// "three 1s + 2 fives [1][1][1][5][5]" for 1100 points
?>
```

### Example 3: Integration with Claude API

```php
<?php
require_once('farkleBotAI_Claude.php');

// Build game context
$gameContext = buildGameContext($gameState, $botPlayerData, $opponentData);

// Get bot personality
$personalityData = [
    'name' => 'Byte',
    'personality_prompt' => 'You are Byte, a calculated bot...',
    'play_style_tendencies' => 'Analytical and strategic...',
    'conversation_style' => 'Tech-savvy and precise...',
    'risk_tolerance' => 7,
    'trash_talk_level' => 5
];

// Build system prompt with personality
$systemPrompt = buildBotSystemPrompt($personalityData);

// Create user message with game context
$userMessage = "It's your turn. Analyze the current game state and make your decision.\n\n" .
               "Game Context: " . json_encode($gameContext);

// Prepare messages for Claude
$messages = [
    ['role' => 'user', 'content' => $userMessage]
];

// Get decision tools schema
$tools = getBotDecisionTools();

// Call Claude API
$response = callClaudeAPI($systemPrompt, $messages, $tools);

// Parse the decision
$decision = parseBotDecision($response);

// Use the decision
if ($decision) {
    $selectedDice = $decision['selected_combination']['dice'];
    $action = $decision['action']; // 'roll_again' or 'bank'
    $chatMessage = $decision['chat_message'];

    echo "Bot selects: " . json_encode($selectedDice) . "\n";
    echo "Bot action: " . $action . "\n";
    echo "Bot says: " . $chatMessage . "\n";
}
?>
```

## Scoring Combinations

The function automatically detects all possible scoring combinations from the current roll:

### Single Die Scoring
- Single 1 = 100 points
- Single 5 = 50 points

### Three of a Kind
- Three 1s = 1,000 points
- Three 2s = 200 points
- Three 3s = 300 points
- Three 4s = 400 points
- Three 5s = 500 points
- Three 6s = 600 points

### Four/Five/Six of a Kind
- Four of a kind = 2x the three of a kind value
- Five of a kind = 3x the three of a kind value
- Six of a kind = 4x the three of a kind value

### Special Combinations (6 dice)
- Straight (1,2,3,4,5,6) = 1,000 points
- Three Pairs = 750 points
- Two Triplets = 2,500 points

### Combined Combinations
The function also detects combinations like:
- Three 2s + one 5 = 250 points
- Three 4s + two 1s = 600 points
- etc.

## Farkle Probability Table

| Dice Count | Farkle Probability |
|------------|-------------------|
| 1 die      | 66.67%           |
| 2 dice     | 44.44%           |
| 3 dice     | 27.78%           |
| 4 dice     | 15.43%           |
| 5 dice     | 7.72%            |
| 6 dice     | 2.31%            |

These probabilities are calculated based on standard Farkle rules where only 1s, 5s, and three-of-a-kind score.

## Position Calculation

The bot's position is determined by comparing its total score to all opponents:

- **Leading**: Bot has the highest score
- **Tied**: Bot's score equals the highest opponent's score
- **Trailing**: Bot's score is less than the highest opponent's score

This information helps Claude make strategic decisions:
- When leading: Play more conservatively to protect the lead
- When trailing: Take more risks to catch up
- When tied: Balance risk and reward

## Dependencies

The `buildGameContext()` function requires:

1. **`farkleBotAI.php`**
   - `Bot_GetAllScoringCombinations($diceRoll)` - Analyzes dice and returns all scoring options
   - `Bot_CalculateFarkleProbability($numDice)` - Calculates farkle probability

2. **Security functions** (in `farkleBotAI_Claude.php`):
   - `sanitizeGameContext($context)` - Sanitizes user-controlled data

## Testing

Test files are available:

- **`/wwwroot/test_game_context.php`**: Comprehensive test suite (7 scenarios)
- **`/wwwroot/example_game_context_usage.php`**: Practical usage example

Run tests:
```bash
docker exec farkle_web php /var/www/html/wwwroot/test_game_context.php
docker exec farkle_web php /var/www/html/wwwroot/example_game_context_usage.php
```

## Error Handling

The function handles invalid/missing data gracefully:

- Missing parameters default to safe values
- Invalid dice values are filtered out
- Empty opponent arrays are handled
- Non-array inputs are converted to empty arrays

## Performance

- **Fast**: Reuses existing scoring logic, no redundant calculations
- **Efficient**: O(n) complexity where n = number of dice (max 6)
- **Scalable**: Handles multiple opponents without performance impact

## Best Practices

1. **Always provide complete game state**: Missing data reduces decision quality
2. **Update turn_score accurately**: Include all points accumulated in current turn
3. **Include all active opponents**: Helps with position calculation
4. **Use consistent game_mode**: 'standard' or '10round', not mixed values
5. **Validate dice_available**: Must be 1-6, affects farkle probability accuracy

## Related Functions

- `buildBotSystemPrompt()` - Generates personality-driven system prompts
- `callClaudeAPI()` - Makes API calls to Claude
- `parseBotDecision()` - Extracts decisions from Claude responses
- `getBotDecisionTools()` - Returns tool schema for function calling
