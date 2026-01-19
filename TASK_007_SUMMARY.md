# Task 007: Game Context Payload Builder - Implementation Summary

## Overview
Implemented comprehensive game context builder function for AI bot decision-making. This function creates structured data payloads containing all relevant game state information for Claude API calls.

## Files Modified

### `/wwwroot/farkleBotAI_Claude.php`
Added two new functions:

1. **`buildGameContext($gameState, $botPlayerData, $opponentData = [])`**
   - Main function that constructs the game context payload
   - Integrates with existing `Bot_GetAllScoringCombinations()` and `Bot_CalculateFarkleProbability()` from `farkleBotAI.php`
   - Returns sanitized, structured data ready for Claude API
   - Handles multiple game modes (standard and 10-round)
   - Supports multiple opponents

2. **`calculatePosition($botScore, $opponentData)`**
   - Helper function that determines bot's position relative to opponents
   - Returns 'leading', 'tied', or 'trailing'
   - Handles edge cases (no opponents, invalid data)

## Game Context Structure

The function returns a comprehensive array with the following structure:

```php
[
    'game_mode' => 'standard' or '10round',
    'current_round' => int,
    'points_to_win' => int,
    'bot_status' => [
        'total_score' => int,
        'round_score' => int,
        'turn_score_so_far' => int,
        'position' => 'leading'|'tied'|'trailing'
    ],
    'opponents' => [
        ['username' => string, 'total_score' => int, 'round_score' => int],
        ...
    ],
    'dice_state' => [
        'dice_available' => int (1-6),
        'current_roll' => [int, ...],
        'scoring_combinations_available' => [
            [
                'dice' => [values],
                'points' => int,
                'description' => string
            ],
            ...
        ]
    ],
    'farkle_probability' => float (0.0 to 1.0)
]
```

## Key Features

### 1. Position Calculation
- Compares bot's total score to all opponents
- Determines if bot is leading (highest score), tied (equal highest), or trailing (behind)
- Handles edge cases (no opponents, invalid data)

### 2. Farkle Probability
- Uses existing `Bot_CalculateFarkleProbability()` function
- Provides accurate probabilities based on number of dice:
  - 1 die: 66.67%
  - 2 dice: 44.44%
  - 3 dice: 27.78%
  - 4 dice: 15.43%
  - 5 dice: 7.72%
  - 6 dice: 2.31%

### 3. Scoring Combinations
- Leverages `Bot_GetAllScoringCombinations()` from `farkleBotAI.php`
- Detects all possible scoring combinations from current roll
- Returns combinations sorted by point value (highest first)
- Includes:
  - Individual 1s and 5s
  - Three/four/five/six of a kind
  - Special combinations (straights, three pairs, two triplets)
  - Combined combinations (e.g., three 2s + two 5s)

### 4. Security
- All user-controlled data sanitized via `sanitizeGameContext()`
- Prevents prompt injection attacks
- HTML entity encoding for string fields
- Numeric and boolean values preserved unchanged

### 5. Multiple Opponent Support
- Handles games with multiple opponents
- Tracks each opponent's username and scores
- Position calculated against highest opponent score

## Integration Points

### Required Dependencies
- `farkleBotAI.php` - For `Bot_GetAllScoringCombinations()` and `Bot_CalculateFarkleProbability()`
- Existing sanitization functions in `farkleBotAI_Claude.php`

### Usage Pattern
```php
// 1. Prepare game state
$gameState = [
    'game_mode' => 'standard',
    'current_round' => 3,
    'points_to_win' => 10000,
    'dice_available' => 4,
    'current_roll' => [1, 5, 3, 4],
    'turn_score' => 150,
    'round_score' => 150
];

// 2. Prepare bot and opponent data
$botPlayerData = ['playerid' => 123, 'username' => 'Byte', 'total_score' => 2500];
$opponentData = [['username' => 'testuser', 'total_score' => 2000]];

// 3. Build context
$gameContext = buildGameContext($gameState, $botPlayerData, $opponentData);

// 4. Use in Claude API call
$systemPrompt = buildBotSystemPrompt($personalityData);
$userMessage = "Current game state: " . json_encode($gameContext);
$messages = [['role' => 'user', 'content' => $userMessage]];
$tools = getBotDecisionTools();
$response = callClaudeAPI($systemPrompt, $messages, $tools);
```

## Testing

### Test Files Created

1. **`/wwwroot/test_game_context.php`**
   - Comprehensive test suite with 7 test scenarios
   - Tests all features and edge cases
   - Color-coded terminal output
   - All tests passing

2. **`/wwwroot/example_game_context_usage.php`**
   - Practical example showing real-world usage
   - Demonstrates complete workflow
   - Shows expected output format

### Test Coverage

Test scenarios include:
- ✅ Bot leading in standard mode
- ✅ Bot trailing in 10-round mode
- ✅ Bot tied with multiple opponents
- ✅ Hot dice (straight) scenario
- ✅ Farkle scenario (no scoring dice)
- ✅ Edge case (single die remaining)
- ✅ Security/sanitization testing

### Test Results
```
All 7 test scenarios passed successfully:
- Position calculation (leading, tied, trailing) ✓
- Farkle probability calculation ✓
- Scoring combination detection ✓
- Multiple opponent handling ✓
- Game mode support (standard and 10-round) ✓
- Security sanitization ✓
- Edge cases (single die, farkle, hot dice) ✓
```

## Requirements Satisfied

✅ **REQ-010**: Build game context payload including:
- ✅ Current scores (bot and opponents)
- ✅ Round information (current round, game mode)
- ✅ Dice state (available dice, current roll)
- ✅ Farkle probability (based on dice count)
- ✅ Scoring combinations (all possible from current roll)
- ✅ Bot position (leading/tied/trailing)

## Performance Considerations

- **Efficient**: Reuses existing scoring logic, no redundant calculations
- **Scalable**: Handles multiple opponents without performance impact
- **Minimal overhead**: Direct integration with existing bot AI functions
- **No external dependencies**: Uses only existing project code

## Security Considerations

- **Prompt injection prevention**: All string fields sanitized
- **XSS protection**: HTML entity encoding applied
- **Input validation**: Numeric fields validated and cast to integers
- **Safe defaults**: Handles missing/invalid data gracefully

## Next Steps

This implementation completes the foundation for Claude API integration. The next tasks should:

1. **Task 008**: Integrate `buildGameContext()` into bot turn handler
2. **Task 009**: Build complete Claude API request with system prompt + game context
3. **Task 010**: Parse Claude's decision and translate to game actions

## Example Output

```json
{
    "game_mode": "standard",
    "current_round": 3,
    "points_to_win": 10000,
    "bot_status": {
        "total_score": 2500,
        "round_score": 200,
        "turn_score_so_far": 200,
        "position": "leading"
    },
    "opponents": [
        {
            "username": "testuser",
            "total_score": 2000,
            "round_score": 150
        }
    ],
    "dice_state": {
        "dice_available": 6,
        "current_roll": [1, 1, 1, 5, 3, 6],
        "scoring_combinations_available": [
            {
                "dice": [1, 1, 1, 5],
                "points": 1050,
                "description": "three 1s + 1 five [1][1][1][5]"
            },
            {
                "dice": [1, 1, 1],
                "points": 1000,
                "description": "three 1s [1][1][1]"
            }
        ]
    },
    "farkle_probability": 0.0231
}
```

## Conclusion

The game context payload builder is fully implemented, tested, and ready for integration. It provides all the information Claude needs to make informed, strategic decisions while playing Farkle, including game state, scoring options, risk assessment (farkle probability), and competitive position.
