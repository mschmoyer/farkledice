# Bot Code Consolidation Implementation Plan

**Priority:** Medium
**Estimated Effort:** 4-5 weeks
**Risk Level:** Medium

---

## Executive Summary

The current bot implementation consists of 5 files totaling ~5,416 lines with significant duplication between the traditional algorithmic bot and Claude API bot. This plan consolidates using the Strategy pattern, reducing complexity while maintaining backward compatibility.

---

## Current Architecture

### File Overview

| File | Lines | Purpose |
|------|-------|---------|
| `farkleBotAI.php` | 972 | Traditional algorithmic bot (Easy/Medium/Hard) |
| `farkleBotAI_Claude.php` | 953 | Claude API client, prompt building |
| `farkleBotTurn.php` | 1,061 | Turn state machine orchestration |
| `farkleBotPersonalities.php` | 544 | 15 bot personality configurations |
| `farkleBotMessages.php` | 1,886 | Hardcoded messages (UNUSED) |

**Total: 5,416 lines**

### Code Duplication Identified

1. **Risk Tolerance Guidance** - 100% identical in two files
2. **Trash Talk Guidance** - 100% identical in two files
3. **Farkle Rules Reference** - 100% identical content
4. **Turn State Management** - ~80% overlap between Banking/Farkled handlers
5. **Entire farkleBotMessages.php** - 1,886 lines of dead code

---

## Proposed Architecture: Strategy Pattern

```
┌─────────────────────────────────────────┐
│              BotTurn.php                │
│  (Orchestration - state machine)        │
│                  │                      │
│                  ▼                      │
│        BotStrategyInterface             │
│         │                │              │
│    ┌────┴────┐    ┌─────┴─────┐        │
│    ▼          ▼    ▼           ▼        │
│ Algorithmic   Claude                    │
│ Strategy      Strategy                  │
└─────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│           BotCommon.php                 │
│  (Shared utilities: scoring, context)  │
└─────────────────────────────────────────┘
```

### New File Structure

```
wwwroot/bot/
├── BotStrategyInterface.php    # Interface definition
├── AlgorithmicStrategy.php     # Traditional algorithms
├── ClaudeStrategy.php          # Claude API integration
├── BotCommon.php               # Shared utilities
├── BotTurn.php                 # Orchestration
└── BotPersonalities.php        # Personalities
```

---

## Interface Definition

```php
<?php
interface BotStrategyInterface {
    /**
     * Make a decision for the current game state
     */
    public function makeDecision(
        array $botPlayer,
        array $gameData,
        array $diceRoll,
        int $turnScore,
        int $diceRemaining
    ): BotDecision;

    public function getStrategyName(): string;
}
```

### BotDecision Value Object

```php
<?php
class BotDecision {
    public ?array $keeperChoice;
    public bool $shouldRoll;
    public string $strategy;
    public bool $farkled;
    public int $newTurnScore;
    public int $newDiceRemaining;
    public string $chatMessage;
    public string $reasoning;

    public static function farkle(string $strategy, string $message = ''): self;
    public static function fromArray(array $data): self;
}
```

---

## Strategy Implementations

### AlgorithmicStrategy

```php
<?php
class AlgorithmicStrategy implements BotStrategyInterface {
    private string $difficulty;  // 'easy', 'medium', 'hard'

    public function __construct(string $difficulty = 'medium') {
        $this->difficulty = $difficulty;
    }

    public function makeDecision(...): BotDecision {
        // Consolidated logic from Bot_Easy_*, Bot_Medium_*, Bot_Hard_*
        // Uses BotCommon:: utilities
    }

    private function chooseKeepersEasy(...): ?array;
    private function chooseKeepersMedium(...): ?array;
    private function chooseKeepersHard(...): ?array;
}
```

### ClaudeStrategy

```php
<?php
class ClaudeStrategy implements BotStrategyInterface {
    private ?AlgorithmicStrategy $fallback;

    public function __construct(?AlgorithmicStrategy $fallback = null) {
        $this->fallback = $fallback ?? new AlgorithmicStrategy('medium');
    }

    public function makeDecision(...): BotDecision {
        // 1. Build context using BotCommon::buildGameContext()
        // 2. Call Claude API
        // 3. Parse response
        // 4. If error, use fallback strategy
    }
}
```

---

## BotCommon Shared Utilities

```php
<?php
class BotCommon {
    // Scoring analysis (from farkleBotAI.php)
    public static function getAllScoringCombinations(array $diceRoll): array;
    public static function formatDiceArray(array $dice): string;

    // Probability calculations
    public static function calculateFarkleProbability(int $numDice): float;
    public static function estimateExpectedPoints(int $numDice): int;

    // Game context (consolidated)
    public static function buildGameContext(array $gameState, array $botPlayer): array;
    public static function calculatePosition(int $botScore, array $opponents): string;

    // Input sanitization (from Claude file)
    public static function sanitizeForPrompt(string $text, int $maxLength = 100): string;

    // Prompt helpers (deduplicated)
    public static function buildRiskToleranceGuidance(int $riskTolerance): string;
    public static function buildTrashTalkGuidance(int $trashTalkLevel): string;
    public static function getFarkleRulesReference(): string;
}
```

---

## Migration Strategy

### Phase 1: Create New Structure (Week 1)
1. Create `wwwroot/bot/` directory
2. Implement interfaces and value objects
3. Implement `BotCommon.php` with shared utilities
4. **No changes to existing files**

### Phase 2: Implement Strategies (Week 2)
1. Implement `AlgorithmicStrategy.php`
2. Implement `ClaudeStrategy.php`
3. Add unit tests for both
4. Verify behavior matches current

### Phase 3: Refactor BotTurn (Week 3)
1. Create new `BotTurn.php` class
2. Add feature flag routing:
   ```php
   if (USE_NEW_BOT_SYSTEM) {
       return BotTurn::create($gameId, $playerId)->executeStep();
   }
   ```
3. Test extensively

### Phase 4: Switch Over (Week 4)
1. Enable new system by default
2. Keep old code as fallback
3. Monitor for issues

### Phase 5: Cleanup (Week 5)
1. Delete `farkleBotMessages.php` (1,886 lines - completely unused)
2. Remove old procedural functions
3. Update documentation

---

## Expected Reduction

### Current: 5,416 lines

### Projected: ~1,800 lines

| File | Estimated Lines |
|------|-----------------|
| BotStrategyInterface.php | 30 |
| BotDecision.php | 80 |
| BotCommon.php | 350 |
| AlgorithmicStrategy.php | 300 |
| ClaudeStrategy.php | 250 |
| BotTurn.php | 400 |
| BotPersonalities.php | 400 |

### Reduction Summary

- **Lines removed:** 3,616 (67% reduction)
- **Duplicate code eliminated:** ~400 lines
- **Dead code removed:** 1,886 lines

---

## Testing Strategy

### Unit Tests

```php
class AlgorithmicStrategyTest extends TestCase {
    public function testEasy_prefersHighScoring(): void;
    public function testEasy_banksWith300Points(): void;
    public function testMedium_considersPointsPerDieRatio(): void;
    public function testHard_calculatesExpectedValue(): void;
}

class ClaudeStrategyTest extends TestCase {
    public function testMakeDecision_parsesValidResponse(): void;
    public function testMakeDecision_fallsBackOnApiError(): void;
    public function testMakeDecision_fallsBackOnTimeout(): void;
}
```

### Integration Tests

Test scenarios:
1. Play full game with Easy/Medium/Hard bot
2. Play full game with AI-powered bot
3. Simulate API failure, verify fallback
4. Test hot dice (6-dice reroll) handling
5. Verify game completion

---

## Rollback Plan

1. Keep old files during transition
2. Feature flag allows instant rollback
3. Old code remains functional until deleted
