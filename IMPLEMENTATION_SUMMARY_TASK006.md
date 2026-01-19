# Implementation Summary: Task-006

## System Prompt Generator for AI Bot Personalities

**Completed**: 2026-01-18
**Status**: ✓ READY FOR INTEGRATION

### What Was Implemented

Built a comprehensive system prompt generator in `wwwroot/farkleBotAI_Claude.php` that creates personality-driven prompts for Claude API to control Farkle bot behavior.

### Core Function

```php
buildBotSystemPrompt($personalityData, $difficulty = null)
```

**Input**: Bot personality data from `farkle_bot_personalities` table
**Output**: Comprehensive system prompt string for Claude API

### Key Features

1. **Personality Integration**
   - Incorporates `personality_prompt` field for character traits
   - Includes `play_style_tendencies` for strategy guidance
   - Integrates `conversation_style` for chat message tone

2. **Risk Tolerance (1-10 scale)**
   - Very Cautious (1-2): Bank at 300+ points, need 4+ dice
   - Cautious (3-4): Bank at 500+ points, need 3+ dice
   - Balanced (5-6): Bank at 750+ points, risk with 3+ dice
   - Aggressive (7-8): Bank at 1000+ points, risk with 2 dice
   - Very Aggressive (9-10): Bank at 1500+ points, almost never stop

3. **Trash Talk Level (1-10 scale)**
   - Very Polite (1-2): Friendly and encouraging
   - Friendly (3-4): Positive with gentle teasing
   - Playful (5-6): Mix of banter and jabs
   - Competitive (7-8): Confident taunting
   - Trash Talker (9-10): Theatrical and bold

4. **Complete Farkle Rules**
   - Basic gameplay mechanics
   - Banking vs rolling decisions
   - Farkle conditions
   - Strategy considerations

5. **Comprehensive Scoring Reference**
   - Single dice (1s = 100, 5s = 50)
   - Three/four/five/six of a kind
   - Special combinations (straight, three pairs, two triplets)
   - Combination selection strategy

6. **Decision-Making Instructions**
   - How to use the `make_farkle_decision` tool
   - Required response format
   - Guidelines for staying in character

### Supporting Functions

- `buildRiskToleranceGuidance($riskTolerance)` - Converts 1-10 scale to banking thresholds
- `buildTrashTalkGuidance($trashTalkLevel)` - Converts 1-10 scale to message tone
- `getFarkleRulesReference()` - Returns complete game rules
- `getFarkleScoringReference()` - Returns all scoring combinations

### Security

- All personality data sanitized using existing `sanitizeForPrompt()` function
- Required field validation with fallback handling
- Length limits on all text fields (max 500 chars)
- Prevents prompt injection attacks

### Requirements Satisfied

✓ **REQ-009**: System prompt generator incorporates personality, play style, conversation style
✓ **REQ-013**: Each bot has distinct play tendencies based on risk tolerance

### Example Usage

```php
// Fetch personality from database
$query = "SELECT * FROM farkle_bot_personalities WHERE personality_id = ?";
$result = db_query($db, $query, [$botId]);
$personality = db_fetch_assoc($result);

// Generate system prompt
$systemPrompt = buildBotSystemPrompt($personality);

// Use with Claude API
$response = callClaudeAPI(
    $systemPrompt,
    $messages,
    getBotDecisionTools()
);
```

### Files Modified

1. `wwwroot/farkleBotAI_Claude.php`
   - Added 5 new functions (300+ lines)
   - All functions fully documented with PHPDoc

### Files Created

1. `wwwroot/test_prompt_generator.php` - Comprehensive test with database
2. `wwwroot/test_prompt_simple.php` - Simple inline test
3. `SAMPLE_PROMPTS.md` - Example outputs for different personalities
4. `TEST_REPORT_TASK006.md` - Detailed test documentation
5. `IMPLEMENTATION_SUMMARY_TASK006.md` - This file

### Sample Prompt Differences

**Byte (Risk=3, Trash=2)**:
```
RISK TOLERANCE (Cautious - 3/10):
You prefer safety over big scores. Bank when you have 500+ points...

CHAT MESSAGE TONE (Very Polite - 2/10):
Be friendly and encouraging. Examples: 'Nice roll!', 'Great move!'
```

**Prime (Risk=8, Trash=9)**:
```
RISK TOLERANCE (Aggressive - 8/10):
You love taking risks! Bank only when you have 1000+ points...

CHAT MESSAGE TONE (Trash Talker - 9/10):
Be boldly competitive and theatrical. Examples: 'BOOM! That's how it's done!'
```

### Integration Ready

The system prompt generator is ready to be integrated with:
- Bot turn processing logic
- Claude API decision-making flow
- Bot personality selection system

### Next Steps

1. Integrate with bot turn handler (next task)
2. Test with actual Claude API calls
3. Monitor and validate personality differences in gameplay
4. Adjust guidance based on observed behavior if needed

---

**Code Location**: `/Users/mikeschmoyer/Documents/GitHub/mschmoyer/farkledice-ai-bot/wwwroot/farkleBotAI_Claude.php`

**Branch**: `feature/ai-bot-players`

**Ready for**: Code review and integration testing
