# Challenge Mode Special Dice - Technical Implementation Guide

**Version:** 1.0
**Date:** 2026-01-18
**Purpose:** Comprehensive technical analysis for implementing special dice effects

---

## Table of Contents

1. [Overview](#overview)
2. [Implementation Patterns](#implementation-patterns)
3. [Database Schema Requirements](#database-schema-requirements)
4. [Dice-by-Dice Analysis](#dice-by-dice-analysis)
5. [Complex Interactions](#complex-interactions)
6. [Order of Operations](#order-of-operations)
7. [Testing Strategy](#testing-strategy)
8. [Recommendations](#recommendations)

---

## Overview

Special dice introduce game-altering effects that require careful state management, proper trigger detection, and complex interaction handling. Effects are grouped into four categories:

- **Farkle Lovers** (Red): Reward farkles with points, money, or score preservation
- **Farkle Protection** (Green): Prevent or mitigate farkle damage
- **Face Changers** (Blue): Modify die face values
- **Score Boosters** (Orange): Multiply points and add bonuses

---

## Implementation Patterns

### Pattern 1: On-Farkle Triggers
**Dice:** Phoenix, Badluck, Fark$, Gamble, Dare, Cushion, Chute

**Trigger Point:** Detected during farkle validation (after roll, no valid dice to save)

**Processing Location:** Backend PHP (`farkleDiceScoring.php` or `farkleGameFuncs.php`)

**State Requirements:**
- Must know: current turn score, round score, dice saved count, active effects
- Must track: farkle count this turn (for Dare stacking), Safe usage per round

**Implementation Flow:**
```
1. Roll dice
2. Check for scoring dice → NONE found
3. TRIGGER: Farkle detected
4. Evaluate on-farkle effects (in priority order):
   a. Gamble/Chute → bank round score, lose turn score
   b. Badluck → check if ≤1 dice saved, award 1000 pts
   c. Phoenix/Cushion → preserve half of turn score
   d. Dare → accumulate +200 bonus for next bank
   e. Fark$ → award $3
5. Apply effect, update game state
6. End turn
```

---

### Pattern 2: Face Value Modifiers
**Dice:** Lucky, Heavy, Fives

**Trigger Point:** During dice roll generation (replace faces before roll)

**Processing Location:** Backend PHP (dice roll function)

**State Requirements:**
- Dice metadata (which dice have which effects)
- No runtime state needed (effects are applied at roll time)

**Implementation Flow:**
```
1. Determine which dice to roll
2. For each die, check if it has face modifier effect
3. Replace random roll with modified value:
   - Lucky: Map {6 → 5}, roll from [1,2,3,4,5,5]
   - Heavy: Map {2 → 1}, roll from [1,1,3,4,5,6]
   - Fives: Always return 5
4. Return modified roll results
```

---

### Pattern 3: Scoring Multipliers
**Dice:** Double, Jackpot, Triple, Hot

**Trigger Point:** During score calculation (when dice are saved/banked)

**Processing Location:** Backend PHP (`farkleDiceScoring.php`)

**State Requirements:**
- Which dice have multiplier effects
- Hot die counter (tracks consecutive saves of Hot die this turn)

**Implementation Flow:**
```
1. Player selects dice to save
2. Calculate base score for selected dice
3. Apply per-die effects:
   - Triple: If this specific die was scored, multiply its contribution by 3
   - Jackpot: If this specific die was scored, double its contribution
4. Apply turn-wide multipliers:
   - Double: Multiply final turn score by 2 (stacks: 2^n for n DOUBLE dice)
5. Apply bonus effects:
   - Hot: If this die was saved, add +50 to turn score, increment Hot counter
6. Return modified score
```

---

### Pattern 4: Money Modifiers
**Dice:** Midas, Fark$

**Trigger Point:**
- Midas: When any die is saved
- Fark$: When farkle occurs

**Processing Location:** Backend PHP (save function for Midas, farkle handler for Fark$)

**State Requirements:**
- Player's current money balance
- Count of dice saved this action (for Midas)

**Implementation Flow:**
```
Midas:
1. Player saves dice
2. Count number of dice saved in this action
3. Award $1 per die saved (if player has Midas die in play)
4. Update player money balance

Fark$:
1. Farkle detected
2. Award $3 if Fark$ die is in play
3. Update player money balance
```

---

### Pattern 5: Special Scoring Rules
**Dice:** Three (Thrice Die)

**Trigger Point:** During score validation (checking if dice are scoreable)

**Processing Location:** Backend PHP (`farkleDiceScoring.php`)

**State Requirements:**
- Which dice have special scoring rules

**Implementation Flow:**
```
1. Player attempts to save dice
2. Check if any saved dice are 3s
3. If Three die is in play:
   - Single 3 = 30 points (configurable)
   - Multiple 3s follow normal Farkle rules (three 3s = 300, etc.)
4. Validate as scoreable if Three die active
5. Calculate score normally
```

---

## Database Schema Requirements

### New Tables

#### `farkle_special_dice`
Defines available special dice types.

```sql
CREATE TABLE farkle_special_dice (
  dice_id SERIAL PRIMARY KEY,
  name VARCHAR(50) NOT NULL,           -- "Phoenix Die"
  short_word VARCHAR(10) NOT NULL,     -- "PHOENIX"
  category VARCHAR(30) NOT NULL,       -- "farkle_lovers"
  tier VARCHAR(20) NOT NULL,           -- "simple", "better", "amazing"
  price INTEGER NOT NULL,              -- Cost in virtual currency
  effect TEXT NOT NULL,                -- Description of effect
  color VARCHAR(7),                    -- Hex color code
  color_name VARCHAR(20),              -- "red", "green", etc.
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `farkle_players_special_dice`
Tracks which special dice each player owns (inventory).

```sql
CREATE TABLE farkle_players_special_dice (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL REFERENCES farkle_players(playerid),
  dice_id INTEGER NOT NULL REFERENCES farkle_special_dice(dice_id),
  quantity INTEGER NOT NULL DEFAULT 1,  -- For future: consumable dice
  acquired_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(playerid, dice_id)
);
```

#### `farkle_games_special_dice`
Tracks which special dice are active in a specific game for a specific player.

```sql
CREATE TABLE farkle_games_special_dice (
  id SERIAL PRIMARY KEY,
  gameid INTEGER NOT NULL REFERENCES farkle_games(gameid),
  playerid INTEGER NOT NULL REFERENCES farkle_players(playerid),
  dice_id INTEGER NOT NULL REFERENCES farkle_special_dice(dice_id),
  dice_position INTEGER NOT NULL,       -- Which die slot (1-6)
  UNIQUE(gameid, playerid, dice_position)
);
```

### Modified Tables

#### `farkle_games` - Add turn state tracking

```sql
ALTER TABLE farkle_games ADD COLUMN turn_state JSONB;
```

**Example `turn_state` structure:**
```json
{
  "playerid": 123,
  "turn_score": 500,
  "round_score": 2300,
  "dice_saved": ["1", "5", "5"],
  "dice_remaining": 3,
  "hot_counter": 2,
  "dare_stacks": 1,
  "safe_used": false,
  "double_multiplier": 2,
  "farkle_count_this_turn": 0
}
```

#### `farkle_games_players` - Add effect counters

```sql
ALTER TABLE farkle_games_players ADD COLUMN effect_state JSONB;
```

**Example `effect_state` structure:**
```json
{
  "safe_used_this_round": false,
  "dare_bonus_accumulated": 400,
  "hot_streak_count": 3,
  "money_earned_this_game": 15
}
```

---

## Dice-by-Dice Analysis

### 1. Phoenix Die (Farkle Lover)

**Category:** Farkle Lovers (Red)
**Tier:** Better
**Price:** $6

**Effect:** When you farkle, score half of your current turn score instead of losing it all

**Trigger:** On farkle detection

**State Tracking:**
- `current_turn_score` (already tracked)

**Database Needs:**
- No new columns required
- Check if Phoenix die in play: Query `farkle_games_special_dice`

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**
1. Detect farkle in PHP
2. Check if player has Phoenix die active
3. If yes: `round_score += (turn_score / 2)`, `turn_score = 0`
4. If no: Normal farkle (lose turn score)

**Edge Cases:**
- **Phoenix + Cushion:** Both have same effect, DO NOT stack (50% + 50% ≠ 100%). Use highest single effect only.
- **Phoenix + Gamble/Chute:** Gamble/Chute takes priority (banks full round score, Phoenix doesn't apply)
- **Rounding:** Use `floor()` for half score calculation

**Processing Location:** Backend PHP (`farkle_handler()` function)

**Testing:**
- Farkle with Phoenix: verify 50% of turn score saved
- Farkle without Phoenix: verify full turn score lost
- Phoenix + other farkle effects: verify priority rules

---

### 2. Back Luck Die (Farkle Lover)

**Category:** Farkle Lovers (Red)
**Tier:** Amazing
**Price:** $7

**Effect:** If you Farkle and have 1 or less dice saved, score 1,000 points

**Trigger:** On farkle detection

**State Tracking:**
- `dice_saved_count` (count of dice scored/banked before farkle)

**Database Needs:**
- No new columns required
- Track `dice_saved` array in `turn_state` JSON

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**
1. Detect farkle in PHP
2. Check if player has Badluck die active
3. Count total dice saved this turn (not just this roll, but entire turn)
4. If `dice_saved_count <= 1`: Award 1000 points to round score
5. Else: Normal farkle processing

**Edge Cases:**
- **"Saved" definition:** Does this count dice banked earlier in turn, or only dice from the final farkle roll?
  - **Recommendation:** Count all dice saved/banked during entire turn (more intuitive)
- **Badluck + Phoenix:** Badluck awards 1000 pts, Phoenix doesn't apply (no turn score to preserve)
- **Badluck + Gamble:** Badluck awards 1000 to round score, Gamble banks it (both apply)
- **Multiple farkles:** If player farkles, Safe rerolls, then farkles again with ≤1 saved, Badluck still applies

**Processing Location:** Backend PHP (`farkle_handler()` function)

**Testing:**
- Farkle with 0 saved dice + Badluck: verify 1000 pts awarded
- Farkle with 1 saved die + Badluck: verify 1000 pts awarded
- Farkle with 2+ saved dice + Badluck: verify no bonus
- Badluck + other farkle effects: verify both apply correctly

---

### 3. Farkle Payday Die (Farkle Lover)

**Category:** Farkle Lovers (Red)
**Tier:** Simple
**Price:** $3

**Effect:** Earn $3 when you farkle

**Trigger:** On farkle detection

**State Tracking:**
- Player's money balance

**Database Needs:**
- Existing `farkle_players` table (add `money` column if not exists)

```sql
ALTER TABLE farkle_players ADD COLUMN money INTEGER DEFAULT 0;
```

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**
1. Detect farkle in PHP
2. Check if player has Fark$ die active
3. If yes: `player_money += 3`
4. Update `farkle_players.money` in database

**Edge Cases:**
- **Multiple Fark$ dice:** If player has 2 Fark$ dice equipped, earn $6 per farkle
- **Fark$ + Safe reroll:** Only award money if farkle is final (after Safe reroll fails too)
- **Fark$ + Gamble:** Both apply (earn $3 AND bank round score)

**Processing Location:** Backend PHP (`farkle_handler()` function)

**Testing:**
- Farkle with Fark$: verify $3 added to player balance
- Farkle without Fark$: verify no money change
- Multiple Fark$ dice: verify money stacks

---

### 4. Gambler's Die (Farkle Lover)

**Category:** Farkle Lovers (Red)
**Tier:** Amazing
**Price:** $12

**Effect:** If you farkle, automatically bank your current round score (risky but can save big rounds)

**Trigger:** On farkle detection

**State Tracking:**
- `round_score` (cumulative score this round)
- `turn_score` (lost on farkle)

**Database Needs:**
- No new columns required
- Standard `farkle_games_players.score` update

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**
1. Detect farkle in PHP
2. Check if player has Gamble die active
3. If yes:
   a. Add `round_score` to player's banked game score
   b. Set `round_score = 0`, `turn_score = 0`
   c. End turn (pass to next player)
4. If no: Normal farkle (lose turn score, keep round score, continue round)

**Edge Cases:**
- **Gamble vs Normal Farkle:** Normal farkle loses turn score but keeps round score; Gamble banks round score (MORE beneficial)
- **Gamble + Phoenix/Cushion:** Gamble takes priority (banks full round score, Phoenix/Cushion don't apply)
- **Gamble + Badluck:** If Badluck triggers (≤1 die saved), add 1000 to round score BEFORE Gamble banks it
- **Gamble + Dare:** Dare bonus accumulates on farkle, applied on NEXT successful bank (not this Gamble bank)
- **Gamble + Safe:** Safe triggers first; if Safe reroll also farkles, THEN Gamble applies

**Processing Location:** Backend PHP (`farkle_handler()` function)

**Testing:**
- Farkle with round_score=2000, Gamble: verify 2000 banked, score increased
- Farkle with round_score=0, Gamble: verify 0 banked (no effect)
- Gamble + other farkle effects: verify priority rules

---

### 5. Daredevil Die (Farkle Lover)

**Category:** Farkle Lovers (Red)
**Tier:** Better
**Price:** $5

**Effect:** Each farkle this turn adds +200 to your next successful bank (stacks)

**Trigger:** On farkle detection (accumulate bonus), on bank (apply bonus)

**State Tracking:**
- `dare_bonus_accumulated` (counter, persists across rerolls)
- Reset to 0 after successful bank or turn ends

**Database Needs:**
- Store in `turn_state` JSON: `"dare_stacks": 0`

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**

**On Farkle:**
1. Detect farkle in PHP
2. Check if player has Dare die active
3. If yes: `dare_bonus_accumulated += 200`
4. Store in `turn_state.dare_stacks`

**On Bank:**
1. Player chooses to bank turn score
2. Calculate final turn score
3. Add `dare_bonus_accumulated` to turn score
4. Add to round score: `round_score += (turn_score + dare_bonus_accumulated)`
5. Reset `dare_bonus_accumulated = 0`

**Edge Cases:**
- **Multiple farkles:** Player farkles, Safe rerolls, farkles again → 400 bonus
- **Dare + Safe:** Safe reroll doesn't reset Dare counter (stacks across rerolls)
- **Dare + Gamble:** If Gamble auto-banks round score, Dare bonus is NOT applied (bonus only on voluntary bank)
  - **Alternative:** Apply Dare bonus to Gamble bank (more generous interpretation)
  - **Recommendation:** DO NOT apply Dare to Gamble (Gamble is already powerful)
- **Dare bonus display:** Show accumulated bonus in UI so player knows how much is at stake
- **Turn end without bank:** If turn ends (timeout, farkle with no Safe), Dare bonus is lost

**Processing Location:**
- Backend PHP (`farkle_handler()` for accumulation, `bank_score()` for application)

**Testing:**
- Farkle with Dare: verify +200 accumulated
- Farkle twice with Dare: verify +400 accumulated
- Bank after Dare farkle: verify bonus applied and reset
- Farkle with Dare + Gamble: verify bonus NOT applied to auto-bank

---

### 6. Cushion Die (Farkle Protection)

**Category:** Farkle Protection (Green)
**Tier:** Simple
**Price:** $3

**Effect:** When you farkle, keep half of your current turn score

**Trigger:** On farkle detection

**State Tracking:**
- `current_turn_score` (already tracked)

**Database Needs:**
- No new columns required

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**
1. Detect farkle in PHP
2. Check if player has Cushion die active
3. If yes: `round_score += (turn_score / 2)`, `turn_score = 0`
4. If no: Normal farkle (lose all turn score)

**Edge Cases:**
- **Cushion vs Phoenix:** IDENTICAL EFFECT. If player has both, only apply once (50% not 100%)
- **Cushion + Gamble/Chute:** Gamble/Chute takes priority (banks full round score, Cushion doesn't apply)
- **Rounding:** Use `floor()` for half score calculation

**Processing Location:** Backend PHP (`farkle_handler()` function)

**Testing:**
- Farkle with Cushion: verify 50% of turn score saved to round score
- Cushion + Phoenix: verify only 50% saved (not stacked)

---

### 7. Parachute Die (Farkle Protection)

**Category:** Farkle Protection (Green)
**Tier:** Amazing
**Price:** $12

**Effect:** When you farkle, automatically bank your current round score (loses turn score)

**Trigger:** On farkle detection

**State Tracking:**
- `round_score` (cumulative score this round)
- `turn_score` (lost on farkle)

**Database Needs:**
- No new columns required

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**
1. Detect farkle in PHP
2. Check if player has Chute die active
3. If yes:
   a. Add `round_score` to player's banked game score
   b. Set `round_score = 0`, `turn_score = 0`
   c. End turn (pass to next player)
4. If no: Normal farkle

**Edge Cases:**
- **Chute vs Gamble:** IDENTICAL EFFECT. Both bank round score on farkle.
- **Chute + Phoenix/Cushion:** Chute takes priority (banks full round score, Phoenix/Cushion don't apply)
- **Chute + Badluck:** Badluck adds 1000 to round score first, then Chute banks it
- **Chute + Dare:** Dare bonus NOT applied to Chute auto-bank (same as Gamble)

**Processing Location:** Backend PHP (`farkle_handler()` function)

**Testing:**
- Same as Gambler's Die tests

---

### 8. Lucky Die (Face Changer)

**Category:** Face Changers (Blue)
**Tier:** Simple
**Price:** $2

**Effect:** One face (6) is replaced with a 5, making this die more likely to score

**Trigger:** During dice roll generation

**State Tracking:**
- Which dice are Lucky dice (from game setup)

**Database Needs:**
- `farkle_games_special_dice` table (already defined)

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**
1. Generate random dice roll (1-6)
2. Check if this die is a Lucky die
3. If yes AND roll == 6: Change roll to 5
4. Return modified roll

**Alternate Implementation:**
- Roll from weighted array: `[1, 2, 3, 4, 5, 5]` (two 5s, no 6)

**Edge Cases:**
- **Lucky + other face changers:** Cannot stack on same die (die can only have one effect)
- **Lucky + scoring:** Lucky 5 counts as normal 5 (50 points, or 500 for three 5s, etc.)

**Processing Location:** Backend PHP (dice roll function in `farkleGameFuncs.php`)

**Testing:**
- Roll Lucky die 100 times: verify no 6s appear, extra 5s appear
- Lucky 5 + normal 5: verify both score as 5s (100 pts)
- Lucky die in triplets: verify scores as three 5s (500 pts)

---

### 9. Heavy Die (Face Changer)

**Category:** Face Changers (Blue)
**Tier:** Simple
**Price:** $2

**Effect:** 2s are replaced with 1s - more scoring opportunities

**Trigger:** During dice roll generation

**State Tracking:**
- Which dice are Heavy dice

**Database Needs:**
- `farkle_games_special_dice` table

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**
1. Generate random dice roll (1-6)
2. Check if this die is a Heavy die
3. If yes AND roll == 2: Change roll to 1
4. Return modified roll

**Alternate Implementation:**
- Roll from array: `[1, 1, 3, 4, 5, 6]` (two 1s, no 2)

**Edge Cases:**
- **Heavy + other face changers:** Cannot stack
- **Heavy 1 scoring:** Counts as normal 1 (100 pts, or 1000 for three 1s, etc.)

**Processing Location:** Backend PHP (dice roll function)

**Testing:**
- Roll Heavy die 100 times: verify no 2s appear, extra 1s appear
- Heavy in scoring: verify counts as normal 1

---

### 10. Fives Die (Face Changer)

**Category:** Face Changers (Blue)
**Tier:** Better
**Price:** $4

**Effect:** All faces are 5's

**Trigger:** During dice roll generation

**State Tracking:**
- Which dice are Fives dice

**Database Needs:**
- `farkle_games_special_dice` table

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**
1. Check if die is Fives die
2. If yes: Always return 5
3. If no: Normal roll

**Edge Cases:**
- **Fives + multipliers:** Fives 5 scores 50 pts normally, Jackpot/Triple can multiply
- **Fives in triplets:** Three Fives dice = three 5s = 500 pts
- **Fives vs Wild:** Fives is weaker than Wild (5 = 50 pts, 1 = 100 pts), but still very strong

**Processing Location:** Backend PHP (dice roll function)

**Testing:**
- Roll Fives die 100 times: verify always returns 5
- Multiple Fives dice: verify all return 5, score as multiple 5s

---

### 11. Double Die (Score Booster)

**Category:** Score Boosters (Orange)
**Tier:** Amazing
**Price:** $10

**Effect:** 2x your score (can stack).

**Trigger:** During score calculation (when turn score is finalized)

**State Tracking:**
- Count of Double dice in play
- `double_multiplier = 2^n` where n = number of Double dice

**Database Needs:**
- Query `farkle_games_special_dice` to count Double dice
- Store multiplier in `turn_state` JSON for UI display

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**
1. Player saves dice and scores points
2. Calculate base turn score
3. Check how many Double dice player has active
4. Apply multiplier: `final_turn_score = base_turn_score * (2^double_count)`
5. Display modified score to player

**Edge Cases:**
- **Double stacking:** 1 Double = 2x, 2 Doubles = 4x, 3 Doubles = 8x (exponential growth!)
- **Double + Jackpot/Triple:** Apply per-die multipliers FIRST, then apply Double to total
  - Example: Roll three 1s (1000 base), have Jackpot on one die (2000), then Double = 4000
- **Double + Hot:** Hot bonus (+50 per save) is added BEFORE Double multiplier
  - Example: Base score 300, Hot +50 = 350, then Double = 700
- **Double + farkle effects:** Double doesn't apply to Phoenix/Cushion half-score (turn score lost before Double applies)
- **Double on bank:** Only applies to turn score, not round score
- **UI clarity:** MUST show base score and multiplied score separately (e.g., "300 x 2 = 600")

**Processing Location:** Backend PHP (`calculate_turn_score()` function)

**Testing:**
- Score with 1 Double: verify 2x multiplier
- Score with 2 Doubles: verify 4x multiplier
- Double + Jackpot: verify both apply correctly
- Double + farkle: verify multiplier doesn't apply to lost score

---

### 12. Midas Die (Score Booster)

**Category:** Score Boosters (Orange)
**Tier:** Better
**Price:** $6

**Effect:** +1$ when you save a die.

**Trigger:** When player saves dice (not on roll, on save action)

**State Tracking:**
- Player's money balance
- Count of dice saved in each save action

**Database Needs:**
- `farkle_players.money` column

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**
1. Player selects dice to save (e.g., saves 2 dice)
2. Calculate score for saved dice
3. Check if player has Midas die active
4. If yes: `player_money += dice_saved_count`
5. Update `farkle_players.money` in database
6. Display money earned in UI

**Edge Cases:**
- **Multiple Midas dice:** If player has 2 Midas dice, earn $2 per die saved (stacks)
- **Midas + bank:** Banking counts as saving remaining dice - earn $1 per banked die
  - **Alternative:** Only apply Midas to explicit "save" actions, not banks
  - **Recommendation:** Apply to both saves and banks (more generous, clearer to player)
- **Midas + farkle:** No money earned (no dice saved)
- **Midas accumulation:** Money persists across games (stored in player account)

**Processing Location:** Backend PHP (`save_dice()` function)

**Testing:**
- Save 1 die with Midas: verify $1 earned
- Save 3 dice with Midas: verify $3 earned
- Bank with Midas: verify money earned for banked dice
- Multiple Midas dice: verify money stacks

---

### 13. Hot Die (Score Booster)

**Category:** Score Boosters (Orange)
**Tier:** Better
**Price:** $5

**Effect:** Each time you score this die, then roll, gain +50 points

**Trigger:** After saving Hot die, before next roll

**State Tracking:**
- `hot_counter` (increments each time Hot die is scored)
- Reset to 0 when turn ends

**Database Needs:**
- Store in `turn_state` JSON: `"hot_counter": 0`

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**

**On Save:**
1. Player saves dice
2. Check if any saved dice are Hot dice
3. If yes: `hot_counter++`
4. Store in turn_state

**On Roll:**
1. Player rolls remaining dice
2. Calculate score from this roll
3. Add Hot bonus: `turn_score += (hot_counter * 50)`
4. Display bonus in UI

**Alternative Interpretation:**
- "Each time you score this die" could mean:
  - **A)** Each time you save the Hot die specifically (must save the Hot die each roll)
  - **B)** Each time you score any dice while Hot die is active (cumulative)
- **Recommendation:** Interpretation A (must save Hot die to increment counter)

**Edge Cases:**
- **Hot + Double:** Hot bonus added BEFORE Double multiplier applies
  - Example: Score 300, Hot +50 = 350, then Double = 700
- **Hot + farkle:** If farkle occurs, Hot bonus is lost (turn score resets)
- **Hot die not saved:** If player doesn't save Hot die on a roll, counter doesn't increment
- **Multiple Hot dice:** Each Hot die tracks separately (or share counter)
  - **Recommendation:** Share counter (simpler implementation)
- **Hot streak display:** Show accumulated bonus in UI (e.g., "Hot Streak: +150")

**Processing Location:** Backend PHP (`save_dice()` and `calculate_turn_score()` functions)

**Testing:**
- Save Hot die once, roll: verify +50 bonus
- Save Hot die twice, roll: verify +100 bonus
- Hot + Double: verify bonus added before multiplier
- Farkle with Hot: verify bonus lost

---

### 14. Jackpot Die (Score Booster)

**Category:** Score Boosters (Orange)
**Tier:** Amazing
**Price:** $12

**Effect:** 2x your score earned when scoring this die.

**Trigger:** During score calculation (when Jackpot die is part of scored dice)

**State Tracking:**
- Which dice are Jackpot dice
- Track which dice were scored in current save action

**Database Needs:**
- `farkle_games_special_dice` table

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**
1. Player selects dice to save (e.g., two 1s and one 5)
2. Calculate base score for each die:
   - If die is normal: use standard Farkle scoring
   - If die is Jackpot AND part of scored dice: double its contribution
3. Sum all dice contributions
4. Apply turn-wide multipliers (Double, etc.)

**Scoring Breakdown Example:**
- Roll: Jackpot 1, normal 1, normal 5
- Save all three:
  - Jackpot 1: 100 * 2 = 200
  - Normal 1: 100
  - Normal 5: 50
  - Total: 350 pts

**Edge Cases:**
- **Jackpot in triplets:**
  - Example: Three 1s (Jackpot + two normals) = 1000 base score
  - Does Jackpot double the triplet? OR double only its contribution?
  - **Recommendation:** Jackpot doubles only its contribution to triplet
  - Formula: `(1000 / 3) * 2 + (1000 / 3) * 2 = 1333` (awkward)
  - **Alternative:** Jackpot doubles entire triplet if Jackpot die is part of it: 1000 * 2 = 2000
  - **Best approach:** Jackpot doubles entire combination if part of it (simpler, more powerful)
- **Jackpot + Double:** Jackpot applies first (per-die), then Double (turn-wide)
  - Example: Jackpot 1 (200) + Double = 400
- **Jackpot + Triple:** Both apply to same die (see interaction analysis below)
- **Multiple Jackpots:** Can have multiple Jackpot dice in play, each doubles its own scoring

**Processing Location:** Backend PHP (`farkleDiceScoring.php`)

**Testing:**
- Score Jackpot 1: verify 200 pts (doubled)
- Score Jackpot 5: verify 100 pts (doubled)
- Score three 1s with Jackpot: verify correct triplet scoring
- Jackpot + Double: verify both multipliers apply

---

### 15. Triple Die (Score Booster)

**Category:** Score Boosters (Orange)
**Tier:** Amazing
**Price:** $15

**Effect:** Triples the points earned from this specific die (1 = 300, 5 = 150)

**Trigger:** During score calculation (when Triple die is scored)

**State Tracking:**
- Which dice are Triple dice

**Database Needs:**
- `farkle_games_special_dice` table

**Implementation Complexity:** **MEDIUM**

**Implementation Steps:**
1. Player saves dice including Triple die
2. Calculate base score contribution for Triple die:
   - Triple 1: 100 * 3 = 300
   - Triple 5: 50 * 3 = 150
   - Triple in triplet: (triplet_score / 3) * 3 = same as triplet (no bonus)
     - **Alternative:** Triple die triples entire triplet score
3. Sum all dice contributions

**Edge Cases:**
- **Triple in triplets:**
  - If Triple die is part of three 1s, does it triple the 1000 pts?
  - **Recommendation:** Triple only affects single die scoring, not triplets
  - Reason: Triplets already have exponential scoring (three 1s = 1000, not 300)
- **Triple + Jackpot:** Both apply to same die (triple then double = 6x)
  - Example: Triple 1 (300) + Jackpot = 600
- **Triple + Double:** Triple applies first (per-die), then Double (turn-wide)
  - Example: Triple 1 (300) + Double = 600
- **Triple + Jackpot + Double:**
  - Triple 1: 100 * 3 = 300
  - Jackpot: 300 * 2 = 600
  - Double: 600 * 2 = 1200
- **Multiple Triples:** Each Triple die triples independently

**Processing Location:** Backend PHP (`farkleDiceScoring.php`)

**Testing:**
- Score Triple 1: verify 300 pts
- Score Triple 5: verify 150 pts
- Score three 1s with Triple: verify no triple bonus on triplet
- Triple + Jackpot: verify 6x multiplier (300 * 2 = 600)

---

### 16. Thrice Die (Score Booster)

**Category:** Score Boosters (Orange)
**Tier:** Simple
**Price:** $5

**Effect:** 3s can score alone (like 1s or 5s)

**Trigger:** During score validation and calculation

**State Tracking:**
- Whether player has Three/Thrice die active

**Database Needs:**
- `farkle_games_special_dice` table

**Implementation Complexity:** **SIMPLE**

**Implementation Steps:**

**On Save Validation:**
1. Player attempts to save dice
2. Check if any saved dice are 3s
3. If yes AND player has Thrice die active:
   - Mark 3s as scoreable (like 1s and 5s)
4. If no: 3s only score as triplets (normal rules)

**On Score Calculation:**
1. Count saved 3s
2. If Thrice die active:
   - Single 3: 30 points (configurable, similar to 5 = 50)
   - Two 3s: 60 points
   - Three 3s: 300 points (normal triplet rules)
   - Four 3s: 600 points (double triplet)
   - etc.
3. If Thrice not active:
   - 3s only score as triplets/quads/etc.

**Edge Cases:**
- **Thrice 3 value:** Should single 3 score 30 or 50 or 100?
  - **Recommendation:** 30 points (1 = 100, 5 = 50, 3 = 30 follows pattern)
- **Thrice + Odds die:** Odds die rolls only 1, 3, 5. With Thrice, all rolls are scoreable!
- **Thrice + Triple die:** If Triple die rolls a 3, score is 30 * 3 = 90
- **Thrice + Jackpot die:** If Jackpot die rolls a 3, score is 30 * 2 = 60
- **Multiple Thrice dice:** Effect doesn't stack (3s already scoreable with one Thrice)

**Processing Location:** Backend PHP (`farkleDiceScoring.php`)

**Testing:**
- Save single 3 with Thrice: verify 30 pts
- Save two 3s with Thrice: verify 60 pts
- Save three 3s with Thrice: verify 300 pts (normal triplet)
- Save 3 without Thrice: verify not scoreable (unless triplet)

---

## Complex Interactions

### Interaction Matrix: On-Farkle Effects

**Priority Order (Top to Bottom):**

1. **Badluck** - Award 1000 pts if ≤1 die saved
2. **Gamble/Chute** - Bank round score (highest priority farkle mitigation)
3. **Phoenix/Cushion** - Save 50% of turn score (secondary mitigation)
4. **Dare** - Accumulate +200 bonus (doesn't prevent farkle, just adds bonus)
5. **Fark$** - Award $3 (always applies)

**Stacking Rules:**

| Effect 1 | Effect 2 | Result |
|----------|----------|--------|
| Gamble | Phoenix/Cushion | Gamble only (banks round score, Phoenix/Cushion ignored) |
| Gamble | Badluck | Both apply (Badluck adds 1000 to round score, Gamble banks it) |
| Gamble | Dare | Dare accumulates but NOT applied to Gamble bank |
| Gamble | Fark$ | Both apply (bank round score AND earn $3) |
| Phoenix | Cushion | Only one applies (50% not 100%) - treat as same effect |
| Phoenix | Dare | Both apply (save 50% of turn score, accumulate Dare +200) |
| Phoenix | Fark$ | Both apply (save 50%, earn $3) |
| Badluck | Phoenix/Cushion | Badluck only (awards 1000 pts, no turn score to preserve) |
| Badluck | Dare | Both apply (award 1000 pts, accumulate Dare +200) |
| Dare | Any | Always accumulates (independent of other effects) |
| Fark$ | Any | Always applies (independent of other effects) |

**Implementation:**

```php
function handle_farkle($playerid, $gameid, $turn_score, $round_score, $dice_saved_count) {
    // Get active special dice for player
    $special_dice = get_player_special_dice($playerid, $gameid);

    // Process farkle effects
    $bonus_points = 0;
    $money_earned = 0;
    $turn_score_preserved = 0;

    // 1. Check Badluck (adds to round score before banking)
    if (has_die($special_dice, 'BADLUCK') && $dice_saved_count <= 1) {
        $bonus_points += 1000;
    }

    // 2. Check Gamble/Chute (banks round score)
    if (has_die($special_dice, 'GAMBLE') || has_die($special_dice, 'CHUTE')) {
        bank_round_score($playerid, $gameid, $round_score + $bonus_points);
        // Turn score and Dare bonus are lost
    }
    // 3. Check Phoenix/Cushion (saves half of turn score)
    else if (has_die($special_dice, 'PHOENIX') || has_die($special_dice, 'CUSHION')) {
        $turn_score_preserved = floor($turn_score / 2);
        add_to_round_score($playerid, $gameid, $turn_score_preserved);
    }

    // 4. Dare always accumulates
    if (has_die($special_dice, 'DARE')) {
        increment_dare_bonus($playerid, $gameid, 200);
    }

    // 5. Fark$ always earns money
    if (has_die($special_dice, 'FARK$')) {
        $money_earned = 3 * count_dice($special_dice, 'FARK$'); // Stacks if multiple
        award_money($playerid, $money_earned);
    }

    // End turn
    end_turn($gameid);
}
```

---

### Interaction Matrix: Score Multipliers

**Order of Operations:**

1. **Calculate base dice values** (apply face modifiers: Lucky, Heavy, Odds, Wild, Fives)
2. **Apply per-die multipliers** (Triple, Jackpot on specific dice)
3. **Sum dice contributions** (add up all scored dice)
4. **Apply bonus additions** (Hot streak bonus)
5. **Apply turn-wide multipliers** (Double die(s))
6. **Add special bonuses** (Dare bonus on bank)

**Example Calculation:**

Player has: 1x Triple die, 1x Double die, 1x Hot die (scored twice this turn)

Roll results: Triple 1, normal 1, normal 5

**Step-by-step:**
1. Base values: 1, 1, 5
2. Apply Triple:
   - Triple 1: 100 * 3 = 300
   - Normal 1: 100
   - Normal 5: 50
3. Sum: 300 + 100 + 50 = 450
4. Hot bonus: +50 (scored Hot die once this turn) = 500
5. Double: 500 * 2 = 1000
6. (No Dare bonus unless farkled previously)

**Final score:** 1000 points

**Stacking Rules:**

| Effect 1 | Effect 2 | Stacking Behavior |
|----------|----------|-------------------|
| Triple | Jackpot | Both apply: (base * 3) * 2 = 6x multiplier |
| Triple | Double | Both apply: (base * 3) * 2 = 6x (Triple first, then Double) |
| Jackpot | Double | Both apply: (base * 2) * 2 = 4x (Jackpot first, then Double) |
| Triple | Jackpot | Double | All apply: (base * 3) * 2 * 2 = 12x multiplier |
| Hot | Double | Both apply: (base + hot_bonus) * 2 |
| Midas | Any | Independent (earns money, doesn't affect points) |
| Double | Double | Exponential: 2^n where n = number of Double dice |

**Implementation:**

```php
function calculate_turn_score($playerid, $gameid, $saved_dice) {
    $special_dice = get_player_special_dice($playerid, $gameid);
    $turn_state = get_turn_state($playerid, $gameid);

    // Step 1: Calculate base score with per-die multipliers
    $base_score = 0;
    foreach ($saved_dice as $die) {
        $die_value = calculate_die_score($die); // Normal Farkle scoring

        // Apply per-die multipliers
        if (is_special_die($die, $special_dice, 'TRIPLE')) {
            $die_value *= 3;
        }
        if (is_special_die($die, $special_dice, 'JACKPOT')) {
            $die_value *= 2;
        }

        $base_score += $die_value;
    }

    // Step 2: Apply Hot bonus
    $hot_bonus = $turn_state['hot_counter'] * 50;
    $base_score += $hot_bonus;

    // Step 3: Apply Double multiplier (exponential stacking)
    $double_count = count_dice($special_dice, 'DOUBLE');
    $double_multiplier = pow(2, $double_count);
    $final_score = $base_score * $double_multiplier;

    return $final_score;
}
```

---

### Interaction Matrix: Face Modifiers + Score Boosters

Face modifiers change die values BEFORE scoring calculation.

**Examples:**

| Die Type | Roll | Effective Value | With Triple | With Jackpot | With Double |
|----------|------|-----------------|-------------|--------------|-------------|
| Lucky | 6 | 5 (changed) | 150 (50*3) | 100 (50*2) | 100 (50*2) |
| Heavy | 2 | 1 (changed) | 300 (100*3) | 200 (100*2) | 200 (100*2) |
| Fives | Any | 5 (always) | 150 | 100 | 100 |

**Key Insight:** Face modifiers don't interact with score boosters - they just change the base value that boosters multiply.

---

### Interaction Matrix: Special Cases

#### Multiple Double Dice (Exponential Growth)

| Double Dice Count | Multiplier | Example (base 100) |
|-------------------|------------|---------------------|
| 0 | 1x | 100 |
| 1 | 2x | 200 |
| 2 | 4x | 400 |
| 3 | 8x | 800 |
| 4 | 16x | 1600 |
| 5 | 32x | 3200 |
| 6 | 64x | 6400 |

**Balance Concern:** Players can equip up to 6 special dice. If all 6 are Double dice, multiplier is 64x. This is EXTREMELY powerful and may break game balance.

**Recommendation:**
- Limit Double dice to 1 per player, OR
- Cap multiplier at 4x (2 Double dice max effective), OR
- Make Double dice non-stackable (only one applies), OR
- Increase price exponentially for additional Double dice

---

## Order of Operations

### Complete Turn Flow with Special Dice

```
1. SETUP PHASE
   - Player equips special dice (up to 6)
   - Game loads special dice metadata
   - Initialize turn state (counters, flags, etc.)

2. ROLL PHASE
   a. Generate roll for each die:
      - Check for face modifiers (Lucky, Heavy, Fives)
      - Apply modifiers BEFORE returning roll value
   b. Display roll to player
   c. Check for scoring dice:
      - If Thrice active: 3s are scoreable
      - Validate dice can be saved

3. SAVE PHASE
   a. Player selects dice to save
   b. Validate selection is legal
   c. Calculate score:
      i. Calculate base score per die
      ii. Apply per-die multipliers (Triple, Jackpot)
      iii. Sum all dice contributions
      iv. Add Hot bonus (+50 * hot_counter)
      v. Apply turn-wide multipliers (Double: 2^n)
   d. Update turn score
   e. Apply Midas effect (award $1 per die saved)
   f. Update Hot counter (if Hot die was saved)

4. DECISION PHASE
   a. Player chooses: roll again OR bank
   b. If bank:
      i. Add Dare bonus (if accumulated)
      ii. Add turn score to round score
      iii. Check if round complete (all players finished)
      iv. Reset turn state (hot_counter, etc.)
      v. Reset round state if round complete (safe_used_this_round, dare_bonus)
   c. If roll again: return to ROLL PHASE

5. FARKLE PHASE (if no scoring dice in roll)
   a. Detect farkle (no valid dice to save)
   b. Apply farkle effects in priority order:
      i. Badluck: Award 1000 pts if ≤1 die saved
      ii. Gamble/Chute: Bank round score, end turn
      iii. Phoenix/Cushion: Save 50% of turn score to round score
      iv. Dare: Accumulate +200 bonus
      v. Fark$: Award $3
   c. Reset turn state
   d. End turn, pass to next player

6. GAME END
   - Check win condition (Standard: ≥10,000 pts, 10-Round: highest score after 10 rounds)
   - Award achievements, XP, money
   - Save game results to database
```

---

## Database Schema Requirements

### Summary of Required Changes

#### New Tables (3)

1. **`farkle_special_dice`** - Dice type definitions (master list)
2. **`farkle_players_special_dice`** - Player inventory (ownership)
3. **`farkle_games_special_dice`** - Active dice in games (equipped)

#### Modified Tables (3)

1. **`farkle_players`** - Add `money INTEGER DEFAULT 0` column
2. **`farkle_games`** - Add `turn_state JSONB` column
3. **`farkle_games_players`** - Add `effect_state JSONB` column

### Detailed Schema

```sql
-- ============================================================
-- NEW TABLES
-- ============================================================

-- Master list of all special dice types
CREATE TABLE farkle_special_dice (
  dice_id SERIAL PRIMARY KEY,
  name VARCHAR(50) NOT NULL,              -- "Phoenix Die"
  short_word VARCHAR(10) NOT NULL UNIQUE, -- "PHOENIX"
  category VARCHAR(30) NOT NULL,          -- "farkle_lovers", "face_changers", etc.
  tier VARCHAR(20) NOT NULL,              -- "simple", "better", "amazing"
  price INTEGER NOT NULL,                 -- Cost in virtual currency
  effect TEXT NOT NULL,                   -- Human-readable description
  color VARCHAR(7),                       -- Hex color code (#cc0000)
  color_name VARCHAR(20),                 -- "red", "green", "blue", "orange"
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for fast lookups by short_word
CREATE INDEX idx_special_dice_short_word ON farkle_special_dice(short_word);

-- Player inventory: which dice each player owns
CREATE TABLE farkle_players_special_dice (
  id SERIAL PRIMARY KEY,
  playerid INTEGER NOT NULL REFERENCES farkle_players(playerid) ON DELETE CASCADE,
  dice_id INTEGER NOT NULL REFERENCES farkle_special_dice(dice_id) ON DELETE CASCADE,
  quantity INTEGER NOT NULL DEFAULT 1,    -- For future: consumable dice
  acquired_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(playerid, dice_id)
);

-- Indexes for fast queries
CREATE INDEX idx_players_special_dice_playerid ON farkle_players_special_dice(playerid);
CREATE INDEX idx_players_special_dice_dice_id ON farkle_players_special_dice(dice_id);

-- Game-specific: which dice are active for each player in a game
CREATE TABLE farkle_games_special_dice (
  id SERIAL PRIMARY KEY,
  gameid INTEGER NOT NULL REFERENCES farkle_games(gameid) ON DELETE CASCADE,
  playerid INTEGER NOT NULL REFERENCES farkle_players(playerid) ON DELETE CASCADE,
  dice_id INTEGER NOT NULL REFERENCES farkle_special_dice(dice_id) ON DELETE CASCADE,
  dice_position INTEGER NOT NULL CHECK (dice_position BETWEEN 1 AND 6),
  UNIQUE(gameid, playerid, dice_position)
);

-- Indexes for fast queries
CREATE INDEX idx_games_special_dice_game_player ON farkle_games_special_dice(gameid, playerid);

-- ============================================================
-- TABLE MODIFICATIONS
-- ============================================================

-- Add money column to players (for Midas, Fark$ rewards)
ALTER TABLE farkle_players ADD COLUMN IF NOT EXISTS money INTEGER DEFAULT 0;

-- Add turn state tracking to games (JSON for flexibility)
ALTER TABLE farkle_games ADD COLUMN IF NOT EXISTS turn_state JSONB;

-- Add effect state tracking to game players (JSON for per-player state)
ALTER TABLE farkle_games_players ADD COLUMN IF NOT EXISTS effect_state JSONB;

-- ============================================================
-- SAMPLE DATA (for testing)
-- ============================================================

-- Insert all special dice from JSON
INSERT INTO farkle_special_dice (name, short_word, category, tier, price, effect, color, color_name) VALUES
  ('Phoenix Die', 'PHOENIX', 'farkle_lovers', 'better', 6, 'When you farkle, score half of your current turn score instead of losing it all', '#cc0000', 'red'),
  ('Back Luck Die', 'BADLUCK', 'farkle_lovers', 'amazing', 7, 'If you Farkle and have 1 or less dice saved, score 1,000 points', '#cc0000', 'red'),
  ('Farkle Payday Die', 'FARK$', 'farkle_lovers', 'simple', 3, 'Earn $3 when you farkle', '#cc0000', 'red'),
  ('Gambler''s Die', 'GAMBLE', 'farkle_lovers', 'amazing', 12, 'If you farkle, automatically bank your current round score (risky but can save big rounds)', '#cc0000', 'red'),
  ('Daredevil Die', 'DARE', 'farkle_lovers', 'better', 5, 'Each farkle this turn adds +200 to your next successful bank (stacks)', '#cc0000', 'red'),
  ('Cushion Die', 'CUSHION', 'farkle_protection', 'simple', 3, 'When you farkle, keep half of your current turn score', '#1d8711', 'green'),
  ('Parachute Die', 'CHUTE', 'farkle_protection', 'amazing', 12, 'When you farkle, automatically bank your current round score (loses turn score)', '#1d8711', 'green'),
  ('Lucky Die', 'LUCKY', 'face_changers', 'simple', 2, 'One face (6) is replaced with a 5, making this die more likely to score', '#4169E1', 'blue'),
  ('Heavy Die', 'HEAVY', 'face_changers', 'simple', 2, '2s are replaced with 1s - more scoring opportunities', '#4169E1', 'blue'),
  ('Fives Die', 'FIVES', 'face_changers', 'better', 4, 'All faces are 5''s', '#4169E1', 'blue'),
  ('Double Die', 'DOUBLE', 'score_boosters', 'amazing', 10, '2x your score (can stack).', '#FFA500', 'orange'),
  ('Midas Die', 'MIDAS', 'score_boosters', 'better', 6, '+1$ when you save a die.', '#FFA500', 'orange'),
  ('Hot Die', 'HOT', 'score_boosters', 'better', 5, 'Each time you score this die, then roll, gain +50 points', '#FFA500', 'orange'),
  ('Jackpot Die', 'JACKPOT', 'score_boosters', 'amazing', 12, '2x your score earned when scoring this die.', '#FFA500', 'orange'),
  ('Triple Die', 'TRIPLE', 'score_boosters', 'amazing', 15, 'Triples the points earned from this specific die (1 = 300, 5 = 150)', '#FFA500', 'orange'),
  ('Thrice Die', 'THREE', 'score_boosters', 'simple', 5, '3s can score alone (like 1s or 5s)', '#FFA500', 'orange');
```

---

## Testing Strategy

### Unit Tests (PHP)

#### Dice Roll Generation Tests
```php
test_lucky_die_never_rolls_6()
test_heavy_die_never_rolls_2()
test_fives_die_always_rolls_5()
```

#### Score Calculation Tests
```php
test_triple_die_triples_score()
test_jackpot_die_doubles_score()
test_double_die_doubles_turn_score()
test_double_die_stacking() // 2^n growth
test_hot_die_adds_50_per_streak()
test_thrice_die_makes_3s_scoreable()
```

#### Farkle Effect Tests
```php
test_phoenix_saves_half_turn_score()
test_badluck_awards_1000_with_1_die_saved()
test_badluck_no_bonus_with_2_dice_saved()
test_gamble_banks_round_score()
test_dare_accumulates_200_per_farkle()
test_fark_dollar_awards_3_dollars()
```

#### Interaction Tests
```php
test_phoenix_plus_cushion_no_stacking()
test_gamble_plus_phoenix_gamble_priority()
test_double_plus_jackpot_both_apply()
test_triple_plus_jackpot_plus_double() // 12x multiplier
test_multiple_double_dice_exponential()
```

### Integration Tests (PHP + Database)

#### Player Inventory Tests
```php
test_purchase_special_die()
test_equip_special_die_to_game()
test_unequip_special_die()
test_cannot_equip_more_than_6_dice()
test_cannot_equip_dice_not_owned()
```

#### Game State Tests
```php
test_turn_state_persists_across_rolls()
test_effect_state_resets_on_round_end()
test_hot_counter_increments_correctly()
test_dare_bonus_accumulates_correctly()
test_safe_flag_resets_on_round_end()
```

### Frontend Tests (JavaScript)

#### UI Display Tests
```js
test_special_dice_displayed_on_game_board()
test_score_multiplier_shown_correctly() // "300 x 2 = 600"
test_hot_streak_bonus_displayed()
test_dare_bonus_accumulated_shown()
test_safe_used_indicator_shown()
```

#### User Interaction Tests
```js
test_clicking_special_die_shows_effect_tooltip()
test_equip_special_die_in_lobby()
test_drag_drop_special_dice_positions()
```

### End-to-End Tests

#### Complete Game Scenarios
```
Scenario 1: Phoenix Die Farkle Recovery
  1. Player equips Phoenix die
  2. Player scores 800 pts in turn
  3. Player farkles
  4. Verify: 400 pts added to round score
  5. Verify: turn score reset to 0

Scenario 2: Double Die Exponential Stacking
  1. Player equips 3 Double dice
  2. Player scores 100 pts base
  3. Verify: final score = 800 (100 * 2^3)

Scenario 3: Fives Die Reliability
  1. Player equips Fives die
  2. Roll Fives die 20 times
  3. Verify: all rolls are 5
  4. Verify: die scores 50 pts each roll
```

### Load Testing

- Test with multiple special dice active simultaneously
- Test database performance with many players owning many dice
- Test JSON query performance for turn_state and effect_state
- Test race conditions (concurrent updates to turn state)

---

## Recommendations

### 1. Implementation Phases

**Phase 1: Foundation** (Week 1-2)
- Create database schema (tables, columns, indexes)
- Seed special dice data from JSON
- Build admin UI for managing special dice
- Build player inventory system (buy, own, equip)

**Phase 2: Face Modifiers** (Week 3)
- Implement Lucky, Heavy, Fives dice
- Modify dice roll generation logic
- Test face modifier combinations
- Update frontend to display special dice visually

**Phase 3: Simple Score Boosters** (Week 4)
- Implement Midas (money per save)
- Implement Thrice (3s scoreable)
- Implement Hot (streak bonus)
- Test scoring calculations

**Phase 4: Complex Score Multipliers** (Week 5)
- Implement Triple, Jackpot, Double dice
- Build order-of-operations scoring engine
- Test multiplier stacking (2x, 3x, 4x, etc.)
- Test exponential growth with multiple Doubles

**Phase 5: Farkle Effects** (Week 6-7)
- Implement Phoenix, Cushion, Fark$
- Implement Gamble, Chute
- Implement Badluck
- Implement Dare (accumulation + bonus)
- Test farkle effect priority system

**Phase 6: UI Polish** (Week 8)
- Dice shop/store UI
- Equip dice interface (drag-drop)
- Visual indicators for active effects
- Tooltips and help text
- Mobile optimization

**Phase 7: Balance & Testing** (Week 9)
- Playtest all dice combinations
- Adjust prices and effects for balance
- Set limits on stacking (e.g., max 2 Doubles)
- Load testing and performance optimization

### 2. Backend vs Frontend Processing

**Backend PHP (Authoritative):**
- Dice roll generation (face modifiers applied here)
- Score calculation (all multipliers applied here)
- Farkle detection and effects
- Turn state management
- Database updates

**Frontend JavaScript (Display Only):**
- Show special dice on game board
- Display score breakdowns ("300 x 2 = 600")
- Show accumulated bonuses (Hot streak, Dare bonus)
- Show effect indicators (Safe used, etc.)
- Animations for special dice effects

**Why Backend-Heavy:**
- Prevent cheating (all scoring is server-authoritative)
- Consistent state across clients (multiplayer)
- Easier to debug and test
- Frontend just mirrors backend state

### 3. Balance Considerations

**Overpowered Combinations:**

1. **Multiple Double Dice** = 2^6 = 64x multiplier (broken)
   - **Solution:** Cap at 2 Double dice max, OR make non-stackable, OR exponential pricing

2. **Fives + Jackpot** = 100 pts guaranteed every roll
   - **Solution:** Acceptable combo (costs 2 slots, high prices), powerful but not broken

**Pricing Review:**
- Simple tier ($2-5): Balanced, good entry-level
- Better tier ($4-7): Reasonable, mid-tier
- Amazing tier ($10-15): Need playtesting - may be underpriced for power level

**Recommendations:**
- Limit "amazing" tier dice to 1-2 per player
- Implement cooldowns or usage limits (e.g., Double only on first 3 turns per round)
- Create "banned combo" list for competitive play
- Offer dice rotation/seasons (certain dice only available at certain times)

### 4. Edge Case Handling

**Critical Edge Cases to Test:**

1. **Player disconnects mid-turn with effects active**
   - Store turn_state in database, restore on reconnect

2. **Multiple players with same special dice**
   - Each player's dice tracked separately in farkle_games_special_dice

3. **Turn timeout with accumulated Dare bonus**
   - Bonus is lost (not applied), reset to 0

4. **Hot counter overflows (player scores Hot die 100 times)**
   - Use INTEGER type (supports up to 2 billion), unlikely to overflow

5. **Money balance goes negative (future: consumable dice?)**
   - Add CHECK constraint: `money >= 0`

### 5. Performance Optimization

**Database Indexes:**
- Already included in schema (see above)
- Add composite index on (gameid, playerid) for fast lookups

**Caching:**
- Cache special dice definitions (rarely change)
- Cache player inventory (invalidate on purchase/equip)
- Use Redis for turn_state (faster than JSON queries)

**Query Optimization:**
```sql
-- Fast lookup: which dice are active for player in game?
SELECT d.short_word, d.effect, g.dice_position
FROM farkle_games_special_dice g
JOIN farkle_special_dice d ON g.dice_id = d.dice_id
WHERE g.gameid = ? AND g.playerid = ?;

-- Fast lookup: does player own this die?
SELECT 1 FROM farkle_players_special_dice
WHERE playerid = ? AND dice_id = ?
LIMIT 1;
```

**JSON vs Separate Tables:**
- `turn_state` and `effect_state` use JSONB for flexibility
- Alternative: Separate tables for each counter/flag (more normalized, but more complex)
- **Recommendation:** Keep JSONB for Phase 1, migrate to tables if performance issues arise

### 6. Future Enhancements

**Ideas for Phase 2 (Post-Launch):**

1. **Consumable Dice** - Use quantity field, dice "break" after X uses
2. **Dice Upgrades** - Upgrade Lucky to Super Lucky (two 6s replaced), etc.
3. **Dice Sets** - Equip full set for bonus effect (e.g., all red dice = extra farkle protection)
4. **Seasonal Dice** - Limited-time dice with unique effects
5. **Dice Crafting** - Combine two dice to create hybrid effect
6. **Dice Renting** - Rent expensive dice for single game
7. **Dice Trading** - Player-to-player dice marketplace
8. **Dice Quests** - Complete challenges to unlock rare dice
9. **Dice Skins** - Cosmetic variations (same effect, different appearance)
10. **Tournament-Specific Dice** - Only available in tournament mode

---

## Conclusion

This technical document provides a comprehensive guide for implementing special dice effects in Challenge Mode. Key takeaways:

1. **Complexity Tiers:**
   - Simple: Face modifiers, basic farkle effects (Phoenix, Cushion, Fark$)
   - Medium: Score multipliers (Double, Jackpot, Triple), Hot die, Dare die, Gamble/Chute

   (Complex tier removed: Safe die reroll mechanic was eliminated for simplicity)

2. **Implementation Priority:**
   - Start with face modifiers (easiest, immediate visual feedback)
   - Add simple score boosters and farkle effects
   - Build multiplier stacking system
   - Implement banking effects (Gamble/Chute)

3. **Critical Systems:**
   - Backend-authoritative scoring (prevent cheating)
   - JSON-based state tracking (flexible, extensible)
   - Clear order-of-operations (consistent behavior)
   - Comprehensive testing (unit + integration + E2E)

4. **Balance Considerations:**
   - Limit "amazing" tier dice (prevent overpowered combos)
   - Cap Double dice stacking (exponential growth is broken)
   - Playtest extensively before launch
   - Adjust prices based on win rate data

5. **Database Design:**
   - Three new tables (dice definitions, inventory, equipped)
   - Two modified tables (add money, turn_state, effect_state)
   - Proper indexing for fast queries
   - JSONB for flexible state tracking

**Next Steps:**
1. Review this document with team
2. Prioritize dice for Phase 1 implementation
3. Create detailed Jira tickets for each phase
4. Set up testing framework
5. Begin Phase 1 development (schema + inventory system)

**Estimated Total Development Time:** 8-9 weeks (assuming 1 developer full-time)

---

**Document Prepared By:** Claude Code
**Last Updated:** 2026-01-18
**Status:** Ready for Review
