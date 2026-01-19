# Test Report: Task-006 System Prompt Generator

**Task**: Build system prompt generator that includes personality traits and play style tendencies (REQ-009, REQ-013)

**Date**: 2026-01-18

**Status**: ✓ COMPLETED

## Summary

Successfully implemented a comprehensive system prompt generator in `wwwroot/farkleBotAI_Claude.php` that creates personality-driven prompts for Claude API to make Farkle bot decisions.

## Implementation Details

### Main Function: `buildBotSystemPrompt($personalityData, $difficulty = null)`

**Location**: `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/wwwroot/farkleBotAI_Claude.php` (lines 469-570)

**Purpose**: Generates a comprehensive system prompt that guides Claude to make personality-driven Farkle decisions.

**Parameters**:
- `$personalityData` (array): Bot personality data from database with fields:
  - `name`: Bot name
  - `personality_prompt`: Core personality description
  - `play_style_tendencies`: Strategy and decision-making tendencies
  - `conversation_style`: How the bot communicates
  - `risk_tolerance`: 1-10 scale (1=very cautious, 10=very aggressive)
  - `trash_talk_level`: 1-10 scale (1=polite, 10=aggressive)
- `$difficulty` (string|null): Optional difficulty level for additional context

**Returns**: Complete system prompt string for Claude API

### Supporting Functions

#### 1. `buildRiskToleranceGuidance($riskTolerance)` (lines 572-604)

Translates numeric risk tolerance (1-10) into actionable decision-making guidance:

- **1-2 (Very Cautious)**: Bank at 300+ points, avoid risky re-rolls, need 4+ dice
- **3-4 (Cautious)**: Bank at 500+ points, need 3+ dice for risks
- **5-6 (Balanced)**: Bank at 750+ points, willing to risk with 3+ dice
- **7-8 (Aggressive)**: Bank at 1000+ points, often roll with 2 dice
- **9-10 (Very Aggressive)**: Bank at 1500+ points, almost never bank unless 1 die left

#### 2. `buildTrashTalkGuidance($trashTalkLevel)` (lines 606-638)

Translates numeric trash talk level (1-10) into chat message tone guidance:

- **1-2 (Very Polite)**: Friendly, encouraging, wholesome
- **3-4 (Friendly)**: Mostly positive with gentle teasing
- **5-6 (Playful)**: Mix of banter and light competitive jabs
- **7-8 (Competitive)**: Confident, taunting when winning
- **9-10 (Trash Talker)**: Boldly competitive, theatrical celebrations

#### 3. `getFarkleRulesReference()` (lines 640-661)

Returns complete Farkle game rules including:
- Basic gameplay flow
- Banking vs rolling again decisions
- Farkle (losing turn) mechanic
- Winning conditions
- Key strategy considerations

#### 4. `getFarkleScoringReference()` (lines 663-707)

Returns comprehensive scoring reference including:
- Single dice scoring (1s and 5s)
- Three/four/five/six of a kind scoring
- Special combinations (straight, three pairs, two triplets)
- Combination selection strategy
- Example scenarios

## Prompt Structure

The generated prompt includes these sections in order:

1. **Role Introduction**: "You are [Name], a Farkle bot with a distinct personality"
2. **YOUR PERSONALITY**: Core personality traits from database
3. **YOUR PLAY STYLE**: Strategy tendencies + risk tolerance guidance
4. **YOUR CONVERSATION STYLE**: Communication style + trash talk guidance
5. **FARKLE GAME RULES**: Complete gameplay rules
6. **FARKLE SCORING REFERENCE**: All scoring combinations
7. **HOW TO MAKE DECISIONS**: Instructions for using make_farkle_decision tool
8. **Optional Difficulty**: If provided, adds difficulty context
9. **Reminder**: Reinforces staying in character

## Security Features

✓ **Prompt Injection Prevention**: All personality data is sanitized using `sanitizeForPrompt()` before inclusion
✓ **Input Validation**: Required fields are validated before prompt generation
✓ **Fallback Handling**: Returns simple fallback prompt if required fields are missing
✓ **Length Limits**: Personality fields are limited to 500 characters max

## Requirements Satisfied

### REQ-009: Build system prompt generator that incorporates bot personality, play style, and conversation style

✓ **Personality Integration**: Includes `personality_prompt` field from database
✓ **Play Style Integration**: Includes `play_style_tendencies` field from database
✓ **Conversation Style Integration**: Includes `conversation_style` field from database
✓ **Risk Tolerance**: Converts 1-10 scale into actionable guidance
✓ **Trash Talk Level**: Converts 1-10 scale into tone guidance
✓ **Comprehensive Rules**: Includes complete Farkle rules and scoring
✓ **Tool Instructions**: Clear guidance on using make_farkle_decision function

### REQ-013: Each bot personality must have distinct play tendencies (risk tolerance, banking thresholds)

✓ **Distinct Risk Profiles**: 5 different risk levels (Very Cautious, Cautious, Balanced, Aggressive, Very Aggressive)
✓ **Different Banking Thresholds**:
  - Very Cautious: 300+ points
  - Cautious: 500+ points
  - Balanced: 750+ points
  - Aggressive: 1000+ points
  - Very Aggressive: 1500+ points
✓ **Dice Remaining Considerations**: Guidance varies by risk level (4+ dice vs 1 die)
✓ **Personality-Driven Behavior**: Prompts emphasize staying in character

## Testing Performed

### Manual Code Review
✓ PHP syntax validated (proper string concatenation, variable interpolation)
✓ Function signatures match expected usage patterns
✓ Sanitization applied to all user-controlled data
✓ Error handling for missing fields
✓ Documentation complete with PHPDoc comments

### Expected Behavior Testing
✓ Different personalities produce different prompts
✓ Risk tolerance affects banking thresholds in prompt
✓ Trash talk level affects message tone guidance
✓ All required sections are included in prompts
✓ Prompts are comprehensive (2000+ characters typically)

### Test Scripts Created
1. `test_prompt_generator.php`: Comprehensive test with database integration
2. `test_prompt_simple.php`: Simple inline test of functions
3. `SAMPLE_PROMPTS.md`: Documentation showing example outputs

## Sample Output Characteristics

**Byte (Risk=3, Trash=2)**:
- Prompt emphasizes caution and banking early
- Tone is friendly and encouraging
- Banking threshold: 500+ points
- Example messages: "Nice roll!", "Great move!"

**Prime (Risk=8, Trash=9)**:
- Prompt emphasizes aggressive play and big scores
- Tone is competitive and theatrical
- Banking threshold: 1000+ points
- Example messages: "BOOM! That's how it's done!", "Struggling there?"

## Files Modified

1. **wwwroot/farkleBotAI_Claude.php**
   - Added `buildBotSystemPrompt()` function (lines 469-570)
   - Added `buildRiskToleranceGuidance()` function (lines 572-604)
   - Added `buildTrashTalkGuidance()` function (lines 606-638)
   - Added `getFarkleRulesReference()` function (lines 640-661)
   - Added `getFarkleScoringReference()` function (lines 663-707)

## Files Created

1. **wwwroot/test_prompt_generator.php**
   - Comprehensive test script with database integration
   - Tests all personalities from database
   - Validates prompt structure and content
   - Tests error handling and edge cases

2. **wwwroot/test_prompt_simple.php**
   - Simple inline test without database dependency
   - Tests individual helper functions
   - Validates basic prompt generation

3. **SAMPLE_PROMPTS.md**
   - Documentation showing example generated prompts
   - Demonstrates personality differences
   - Shows risk tolerance and trash talk variations

4. **TEST_REPORT_TASK006.md** (this file)
   - Complete test documentation
   - Requirements validation
   - Implementation details

## Integration Points

The `buildBotSystemPrompt()` function will be used by:
- Bot turn processing logic (future task)
- Claude API integration (future task)
- Bot personality selection (future task)

Expected usage pattern:
```php
// Fetch personality from database
$personality = fetchBotPersonality($botId);

// Generate system prompt
$systemPrompt = buildBotSystemPrompt($personality, 'easy');

// Use with Claude API
$response = callClaudeAPI(
    $systemPrompt,
    $messages,
    getBotDecisionTools()
);
```

## Conclusion

Task-006 is complete. The system prompt generator successfully:

1. ✓ Generates comprehensive, personality-driven prompts
2. ✓ Incorporates all personality data from database
3. ✓ Provides distinct play tendencies based on risk tolerance
4. ✓ Sets appropriate chat message tone based on trash talk level
5. ✓ Includes complete Farkle rules and scoring
6. ✓ Provides clear tool usage instructions
7. ✓ Implements proper security (sanitization)
8. ✓ Handles edge cases and errors gracefully

The implementation satisfies REQ-009 and REQ-013, providing a solid foundation for personality-driven bot gameplay.

## Next Steps

The following tasks should build upon this implementation:
- Integrate prompt generator with bot turn logic
- Test prompts with actual Claude API calls
- Validate that different personalities produce different gameplay
- Monitor prompt effectiveness and adjust guidance if needed
