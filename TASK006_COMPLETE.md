# ✓ TASK-006 COMPLETE

## System Prompt Generator Implementation

**Task**: Build system prompt generator that includes personality traits and play style tendencies

**Requirements**: REQ-009, REQ-013

**Status**: ✓ COMPLETE AND READY FOR INTEGRATION

---

## Implementation Summary

### Added to `wwwroot/farkleBotAI_Claude.php`

**5 New Functions** (240 lines of code added)

1. **`buildBotSystemPrompt($personalityData, $difficulty = null)`** [Lines 494-570]
   - Main function that generates comprehensive system prompts
   - Integrates personality, play style, conversation style
   - Includes risk tolerance and trash talk guidance
   - Adds complete Farkle rules and scoring
   - Returns 2000+ character prompts

2. **`buildRiskToleranceGuidance($riskTolerance)`** [Lines 580-604]
   - Converts 1-10 risk scale to banking thresholds
   - 5 distinct risk profiles (Very Cautious → Very Aggressive)
   - Banking thresholds: 300pts → 1500pts
   - Dice remaining considerations

3. **`buildTrashTalkGuidance($trashTalkLevel)`** [Lines 614-638]
   - Converts 1-10 trash talk scale to message tone
   - 5 distinct tone profiles (Very Polite → Trash Talker)
   - Example messages for each level
   - Guides chat message personality

4. **`getFarkleRulesReference()`** [Lines 645-661]
   - Returns complete Farkle gameplay rules
   - Banking vs rolling mechanics
   - Farkle conditions
   - Strategy considerations

5. **`getFarkleScoringReference()`** [Lines 668-707]
   - Returns all scoring combinations
   - Single dice, three/four/five/six of a kind
   - Special combinations (straight, three pairs, two triplets)
   - Combination selection strategy

---

## Key Features

### ✓ Personality-Driven Prompts
- Each bot gets unique personality from database
- Character traits influence decision-making
- Stay-in-character emphasis throughout

### ✓ Distinct Risk Profiles
| Risk Level | Banking Threshold | Dice Needed | Profile |
|------------|------------------|-------------|---------|
| 1-2 | 300+ points | 4+ dice | Very Cautious |
| 3-4 | 500+ points | 3+ dice | Cautious |
| 5-6 | 750+ points | 3+ dice | Balanced |
| 7-8 | 1000+ points | 2+ dice | Aggressive |
| 9-10 | 1500+ points | 1 die | Very Aggressive |

### ✓ Varied Trash Talk
| Trash Level | Tone | Example Messages |
|-------------|------|------------------|
| 1-2 | Very Polite | "Nice roll!", "Great move!" |
| 3-4 | Friendly | "Not bad!", "I might catch up yet!" |
| 5-6 | Playful | "Getting lucky, aren't you?", "Watch and learn!" |
| 7-8 | Competitive | "Is that all you got?", "Feeling the heat yet?" |
| 9-10 | Trash Talker | "BOOM! That's how it's done!", "Struggling there?" |

### ✓ Comprehensive Game Knowledge
- Complete Farkle rules included in every prompt
- All scoring combinations documented
- Strategy guidance for combination selection
- Tool usage instructions (make_farkle_decision)

### ✓ Security
- All personality data sanitized (`sanitizeForPrompt()`)
- Required field validation
- Fallback handling for missing data
- Length limits (500 chars max)

---

## Requirements Satisfied

### REQ-009: System Prompt Generator
✓ Incorporates bot personality from database
✓ Includes play style tendencies
✓ Integrates conversation style
✓ Provides comprehensive game rules
✓ Instructs on tool usage

### REQ-013: Distinct Play Tendencies
✓ Risk tolerance creates different banking thresholds
✓ 5 distinct risk profiles implemented
✓ Banking thresholds range from 300pts to 1500pts
✓ Dice-remaining considerations vary by risk level
✓ Personality traits influence decision-making

---

## Example Output

### Byte (Cautious Beginner - Risk 3, Trash 2)
```
You are Byte, a Farkle bot with a distinct personality...

RISK TOLERANCE (Cautious - 3/10):
You prefer safety over big scores. Bank when you have 500+ points
or fewer than 3 dice left. You take calculated risks but err on
the side of caution.

CHAT MESSAGE TONE (Very Polite - 2/10):
Be friendly and encouraging. Compliment the player's moves.
Examples: 'Nice roll!', 'Great move!', 'You're doing well!'
```

### Prime (Aggressive Expert - Risk 8, Trash 9)
```
You are Prime, a Farkle bot with a distinct personality...

RISK TOLERANCE (Aggressive - 8/10):
You love taking risks! You often roll again even with 2 dice left.
Bank only when you have 1000+ points or you're close to winning.
Fortune favors the bold!

CHAT MESSAGE TONE (Trash Talker - 9/10):
Be boldly competitive and theatrical. Celebrate your wins, mock
their mistakes (playfully). Examples: 'BOOM! That's how it's done!',
'Struggling there?', 'Better luck next time!'
```

---

## Test Files Created

1. **`test_prompt_generator.php`**
   - Comprehensive test with database integration
   - Tests all active personalities
   - Validates prompt structure
   - Tests error handling

2. **`test_prompt_simple.php`**
   - Simple inline test
   - No database dependency
   - Tests individual functions

3. **`SAMPLE_PROMPTS.md`**
   - Example prompts for Byte and Prime
   - Shows personality differences
   - Demonstrates risk/trash talk variations

4. **`TEST_REPORT_TASK006.md`**
   - Detailed test documentation
   - Requirements validation
   - Implementation details

5. **`IMPLEMENTATION_SUMMARY_TASK006.md`**
   - Quick reference guide
   - Usage examples
   - Integration instructions

---

## Integration Usage

```php
// Fetch bot personality from database
$query = "SELECT * FROM farkle_bot_personalities WHERE personality_id = ?";
$result = db_query($db, $query, [$botId]);
$personality = db_fetch_assoc($result);

// Generate system prompt
$systemPrompt = buildBotSystemPrompt($personality);

// Use with Claude API
$messages = [
    [
        'role' => 'user',
        'content' => 'You rolled [1, 2, 3, 4, 5, 6]. What do you do?'
    ]
];

$response = callClaudeAPI(
    $systemPrompt,
    $messages,
    getBotDecisionTools()
);

// Parse decision
$decision = parseBotDecision($response);
```

---

## Code Quality

✓ **Well-documented**: PHPDoc comments on all functions
✓ **Secure**: Input sanitization and validation
✓ **Maintainable**: Clear function separation
✓ **Testable**: Test scripts provided
✓ **Comprehensive**: 2000+ character prompts with all necessary information

---

## File Locations

**Modified:**
- `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/wwwroot/farkleBotAI_Claude.php`

**Created:**
- `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/wwwroot/test_prompt_generator.php`
- `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/wwwroot/test_prompt_simple.php`
- `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/SAMPLE_PROMPTS.md`
- `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/TEST_REPORT_TASK006.md`
- `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/IMPLEMENTATION_SUMMARY_TASK006.md`
- `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/TASK006_COMPLETE.md`

**Branch:** `feature/ai-bot-players`

**Worktree:** `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot`

---

## Next Steps

This implementation provides the foundation for personality-driven bot gameplay. The next tasks should:

1. Integrate the prompt generator with bot turn processing
2. Test with actual Claude API calls
3. Validate that different personalities produce different gameplay
4. Monitor effectiveness and adjust guidance if needed

**READY FOR CODE REVIEW AND INTEGRATION TESTING**
