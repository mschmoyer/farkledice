# Bot Player Implementation Plan

**Created:** 2026-01-18
**Status:** Planning
**Complexity:** High

## Overview

Add AI bot opponents to Farkle with three difficulty levels. Bots are actual player accounts that level up, earn XP, and have special bot-themed names and titles. Bots can play interactively in real-time for "Play a Bot" mode, or instantly complete games to fill unfilled random matches.

### Why Bots Are Real Player Accounts

Using actual `farkle_players` records for bots provides significant benefits:

**Simplicity:**
- No separate bot table - reuse existing player infrastructure
- All game logic works unchanged (bots are just playerids)
- Existing XP/leveling/stats system works automatically
- Friend system, leaderboards, game history all compatible

**Player Experience:**
- Bots have persistent stats that grow over time
- "Neural the Master Bot - Level 47" feels like a real opponent
- Bots can appear in leaderboards (with bot indicator)
- Players can view bot profiles and game history

**Implementation:**
- Just add 2 columns: `is_bot`, `bot_algorithm` (ENUM type)
- ENUM allows easy expansion: future algorithms like 'aggressive', 'conservative', 'chaos'
- Mark accounts with `random_selectable = 0` to prevent random matchmaking
- Lock password field to prevent login

## User-Facing Features

### 1. Play a Bot Mode (Interactive)
- New game creation option: "Play a Bot"
- User selects difficulty: Easy, Medium, or Hard
- Bot plays its turn in real-time with visible dice rolls, keeps, and banking
- User can watch bot decision-making process unfold
- Game progresses turn-by-turn like multiplayer

### 2. Bot Fills Random Games (Background)
- Random games that haven't filled after timeout get a bot player
- **Timeout:** 1 minute (dev/local), 24 hours (production)
- Bot plays all 10 rounds instantly (non-interactive)
- No visible turn-by-turn for background fills

## Technical Requirements

### A. Database Schema Changes

#### Column Additions to Existing Tables

**`farkle_players`** - Mark bot accounts and track algorithm
```sql
-- Create ENUM type for bot algorithms
CREATE TYPE bot_algorithm_type AS ENUM ('easy', 'medium', 'hard');

ALTER TABLE farkle_players
ADD COLUMN is_bot BOOLEAN DEFAULT FALSE,
ADD COLUMN bot_algorithm bot_algorithm_type DEFAULT NULL;

CREATE INDEX idx_bot_players ON farkle_players(is_bot) WHERE is_bot = TRUE;
CREATE INDEX idx_bot_algorithm ON farkle_players(bot_algorithm) WHERE bot_algorithm IS NOT NULL;
```

**`farkle_games`** - Track bot play mode
```sql
-- Note: gamewith column already exists (0=RANDOM, 1=FRIENDS, 2=SOLO, 3=BOT)
ALTER TABLE farkle_games
ADD COLUMN bot_play_mode VARCHAR(20) DEFAULT NULL;  -- 'interactive' or 'instant'
```

#### New Table for Bot Turn State

**`farkle_bot_game_state`** - State for interactive bot turns
```sql
CREATE TABLE farkle_bot_game_state (
    stateid SERIAL PRIMARY KEY,
    gameid INTEGER NOT NULL,
    playerid INTEGER NOT NULL,           -- Reference to farkle_players (the bot)
    current_step VARCHAR(20),            -- 'rolling', 'deciding', 'keeping', 'banking'
    dice_kept TEXT,                      -- JSON array of kept dice
    turn_score INTEGER DEFAULT 0,
    dice_remaining INTEGER DEFAULT 6,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (gameid) REFERENCES farkle_games(gameid) ON DELETE CASCADE,
    FOREIGN KEY (playerid) REFERENCES farkle_players(playerid) ON DELETE CASCADE
);

CREATE INDEX idx_bot_game_state ON farkle_bot_game_state(gameid, playerid);
```

#### Bot Player Accounts

**Seed bot player accounts** - Create actual player accounts for bots
```sql
-- Easy algorithm bots (fun, approachable names)
INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm,
                            title, level, xp, random_selectable, sendhourlyemails)
VALUES
    ('Byte', 'LOCKED', '', NULL, TRUE, 'easy', 'the Rookie Bot', 1, 0, 0, 0),
    ('Chip', 'LOCKED', '', NULL, TRUE, 'easy', 'the Friendly Bot', 1, 0, 0, 0),
    ('Beep', 'LOCKED', '', NULL, TRUE, 'easy', 'the Learning Bot', 1, 0, 0, 0);

-- Medium algorithm bots (tech-themed names)
INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm,
                            title, level, xp, random_selectable, sendhourlyemails)
VALUES
    ('Cyber', 'LOCKED', '', NULL, TRUE, 'medium', 'the Tactical Bot', 5, 1000, 0, 0),
    ('Logic', 'LOCKED', '', NULL, TRUE, 'medium', 'the Strategic Bot', 5, 1000, 0, 0),
    ('Binary', 'LOCKED', '', NULL, TRUE, 'medium', 'the Calculated Bot', 5, 1000, 0, 0);

-- Hard algorithm bots (advanced AI names)
INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm,
                            title, level, xp, random_selectable, sendhourlyemails)
VALUES
    ('Neural', 'LOCKED', '', NULL, TRUE, 'hard', 'the Master Bot', 10, 5000, 0, 0),
    ('Quantum', 'LOCKED', '', NULL, TRUE, 'hard', 'the Perfect Bot', 10, 5000, 0, 0),
    ('Apex', 'LOCKED', '', NULL, TRUE, 'hard', 'the Supreme Bot', 10, 5000, 0, 0);

-- Prevent bots from appearing in random matchmaking
-- (random_selectable = 0, sendhourlyemails = 0)
```

#### Bot Titles

Bot-exclusive titles that make it clear they're AI opponents:
- **Easy Bots:** "the Rookie Bot", "the Friendly Bot", "the Learning Bot"
- **Medium Bots:** "the Tactical Bot", "the Strategic Bot", "the Calculated Bot"
- **Hard Bots:** "the Master Bot", "the Perfect Bot", "the Supreme Bot"

These titles will display as: **Byte the Rookie Bot**, **Neural the Master Bot**, etc.

### B. PHP Constants and Configuration

**`wwwroot/farkleGameFuncs.php`** - Add new constants
```php
// Game types (add to existing)
define('GAME_WITH_BOT', 3);  // Game against AI bot

// Bot algorithms (match ENUM in database)
define('BOT_ALGORITHM_EASY', 'easy');
define('BOT_ALGORITHM_MEDIUM', 'medium');
define('BOT_ALGORITHM_HARD', 'hard');

// Bot play modes
define('BOT_PLAY_INTERACTIVE', 'interactive');  // Real-time visible turns
define('BOT_PLAY_INSTANT', 'instant');          // Complete all rounds immediately

// Bot configuration
define('BOT_FILL_TIMEOUT_DEV', 60);          // 1 minute for local/dev
define('BOT_FILL_TIMEOUT_PROD', 86400);      // 24 hours for production
```

### C. Core Bot AI Logic

**New File: `wwwroot/farkleBotAI.php`**

Functions needed:
- `Bot_SelectBotPlayer($algorithm)` - Choose random bot player account with specified algorithm
- `Bot_GetBotAlgorithm($playerid)` - Get algorithm type for a bot player
- `Bot_CalculateFarkleProbability($diceCount)` - Return farkle % for N dice
- `Bot_GetAllScoringCombinations($diceRoll)` - Find all valid scoring combinations from a roll
- `Bot_ChooseKeepers($diceRoll, $turnScore, $diceLeft, $algorithm)` - Which dice to keep from current roll
- `Bot_ShouldRollAgain($keptDice, $turnScore, $diceLeft, $gameState, $algorithm)` - Roll again or bank?
- `Bot_UpdateStats($playerid, $gameResult)` - Update bot XP/level after game (bots level up!)

#### Bot Decision Algorithms

Each bot needs to make TWO decisions per roll:
1. **Which dice to keep?** (keeper selection)
2. **Roll again or bank?** (risk assessment)

---

### EASY Algorithm: Simple & Makes Mistakes

**Keeper Selection:**
```php
function Bot_Easy_ChooseKeepers($diceRoll, $turnScore, $diceLeft) {
    // Get all possible scoring combinations
    $combos = Bot_GetAllScoringCombinations($diceRoll);

    if (empty($combos)) {
        return null; // Farkle
    }

    // 30% chance of making a mistake
    if (rand(1, 100) <= 30) {
        // Pick a random valid combination (might not be optimal)
        return $combos[array_rand($combos)];
    }

    // Usually: Keep highest-scoring combination
    usort($combos, function($a, $b) {
        return $b['points'] - $a['points'];
    });

    return $combos[0];
}
```

**Roll Again Decision:**
```php
function Bot_Easy_ShouldRollAgain($keptDice, $turnScore, $diceLeft) {
    // Very simple threshold: bank at 300-450 points
    $threshold = rand(300, 450);

    // If we only have 1 die left, usually don't risk it (66% farkle chance)
    if ($diceLeft == 1) {
        return rand(1, 100) <= 20; // Only 20% chance to roll single die
    }

    // If we only have 2 dice left, be cautious
    if ($diceLeft == 2) {
        return $turnScore < 300 && rand(1, 100) <= 40;
    }

    // Otherwise: roll again if under threshold
    return $turnScore < $threshold;
}
```

---

### MEDIUM Algorithm: Tactical & Strategic

**Keeper Selection:**
```php
function Bot_Medium_ChooseKeepers($diceRoll, $turnScore, $diceLeft) {
    $combos = Bot_GetAllScoringCombinations($diceRoll);

    if (empty($combos)) {
        return null; // Farkle
    }

    // Evaluate each combination by points-per-die-kept ratio
    // Goal: Maximize points while keeping dice available for re-rolls
    $bestCombo = null;
    $bestRatio = 0;

    foreach ($combos as $combo) {
        $ratio = $combo['points'] / count($combo['dice']);

        // Bonus for keeping all scoring dice (hot dice = re-roll all 6)
        if (count($combo['dice']) == $diceLeft) {
            $ratio *= 1.5; // Prefer hot dice situations
        }

        if ($ratio > $bestRatio) {
            $bestRatio = $ratio;
            $bestCombo = $combo;
        }
    }

    return $bestCombo;
}
```

**Roll Again Decision:**
```php
function Bot_Medium_ShouldRollAgain($keptDice, $turnScore, $diceLeft, $gameState) {
    $farkleProb = Bot_CalculateFarkleProbability($diceLeft);

    // Don't roll if farkle chance is too high
    if ($farkleProb > 0.50) {
        return false;
    }

    // Position-aware: adjust threshold based on score deficit
    $deficit = $gameState['oppScore'] - $gameState['myScore'];
    $threshold = 350 + ($deficit / 10000) * 200; // Higher threshold when behind

    // Expected value calculation
    $avgPointsPerRoll = Bot_EstimateExpectedPoints($diceLeft);
    $evRoll = (1 - $farkleProb) * $avgPointsPerRoll - ($farkleProb * $turnScore);

    // Roll if EV is positive and under threshold
    return $evRoll > 30 && $turnScore < $threshold;
}
```

---

### HARD Algorithm: Optimal Mathematical Play

**Keeper Selection:**
```php
function Bot_Hard_ChooseKeepers($diceRoll, $turnScore, $diceLeft) {
    $combos = Bot_GetAllScoringCombinations($diceRoll);

    if (empty($combos)) {
        return null; // Farkle
    }

    // For each combination, calculate expected value of the resulting position
    $bestCombo = null;
    $bestEV = -999999;

    foreach ($combos as $combo) {
        $pointsFromCombo = $combo['points'];
        $diceRemaining = $diceLeft - count($combo['dice']);

        if ($diceRemaining == 0) {
            // Hot dice - we get to re-roll all 6
            $diceRemaining = 6;
        }

        // Calculate EV of continuing from this position
        $newTurnScore = $turnScore + $pointsFromCombo;
        $farkleProb = Bot_CalculateFarkleProbability($diceRemaining);
        $expectedContinuation = Bot_GetExpectedScoreDistribution($diceRemaining);

        // EV = (chance of success * expected additional points) - (chance of farkle * current score)
        $ev = (1 - $farkleProb) * $expectedContinuation - ($farkleProb * $newTurnScore);

        // Add immediate points to EV
        $totalEV = $pointsFromCombo + max(0, $ev);

        if ($totalEV > $bestEV) {
            $bestEV = $totalEV;
            $bestCombo = $combo;
        }
    }

    return $bestCombo;
}
```

**Roll Again Decision:**
```php
function Bot_Hard_ShouldRollAgain($keptDice, $turnScore, $diceLeft, $gameState) {
    $farkleProb = Bot_CalculateFarkleProbability($diceLeft);

    // Calculate exact expected value of rolling vs. banking
    $expectedScoreIfRoll = Bot_GetExpectedScoreDistribution($diceLeft);
    $evRoll = (1 - $farkleProb) * $expectedScoreIfRoll - ($farkleProb * $turnScore);
    $evBank = $turnScore; // Guaranteed

    // Game-theoretic adjustments based on position
    $leadMargin = $gameState['myScore'] - $gameState['oppScore'];
    $roundsLeft = 11 - $gameState['currentRound'];

    if ($leadMargin > 2000) {
        // Winning comfortably: play conservatively
        // Only roll if EV is significantly positive
        return $evRoll > $evBank * 1.2 && $farkleProb < 0.25;
    } elseif ($leadMargin < -2000) {
        // Losing badly: take calculated risks
        // Roll if EV is positive at all
        return $evRoll > 0 && $turnScore < 800;
    } else {
        // Close game: pure EV optimization
        return $evRoll > $evBank;
    }
}
```

---

### Probability Tables & Helper Functions

```php
// Farkle probability by dice count (pre-calculated)
$FARKLE_PROBABILITY = [
    1 => 0.6667,  // 66.67% (only 1 and 5 score)
    2 => 0.4444,  // 44.44%
    3 => 0.2778,  // 27.78%
    4 => 0.1543,  // 15.43%
    5 => 0.0772,  // 7.72%
    6 => 0.0231   // 2.31%
];

function Bot_CalculateFarkleProbability($diceCount) {
    global $FARKLE_PROBABILITY;
    return $FARKLE_PROBABILITY[$diceCount] ?? 0;
}

// Expected score per roll by dice count (approximate)
$EXPECTED_SCORE_PER_ROLL = [
    1 => 83,   // Avg of (0 * 0.667) + (50 * 0.167) + (100 * 0.167)
    2 => 100,
    3 => 150,
    4 => 200,
    5 => 250,
    6 => 350
];

function Bot_EstimateExpectedPoints($diceCount) {
    global $EXPECTED_SCORE_PER_ROLL;
    return $EXPECTED_SCORE_PER_ROLL[$diceCount] ?? 0;
}

/**
 * Get all valid scoring combinations from a dice roll
 * Returns array of combinations, each with 'dice' and 'points'
 *
 * Example: [1, 1, 3, 4, 5, 6] returns:
 * - ['dice' => [1, 1], 'points' => 200]
 * - ['dice' => [5], 'points' => 50]
 * - ['dice' => [1, 1, 5], 'points' => 250]
 * - ['dice' => [1], 'points' => 100]
 * - ['dice' => [1, 5], 'points' => 150]
 */
function Bot_GetAllScoringCombinations($diceRoll) {
    // Use existing farkle scoring logic from farkleDiceScoring.php
    // This function already exists and returns all valid combinations
    require_once('farkleDiceScoring.php');

    // Convert dice roll to format expected by scoring engine
    $diceArray = is_array($diceRoll) ? $diceRoll : str_split($diceRoll);

    // Get all scoring options
    // Note: We'll need to implement or use existing GetAllScoringOptions() function
    // For now, pseudocode:
    $combinations = GetAllScoringOptions($diceArray);

    return $combinations;
}
```

**Note:** The actual implementation will leverage the existing `farkleDiceScoring.php` logic. We need to extend it to return ALL possible combinations, not just the optimal one.

---

### Bot Personality & Messages

**New File: `wwwroot/farkleBotMessages.php`**

Large message bank (50-100 messages per situation) organized by:
- Bot personality (easy/medium/hard)
- Game situation (keeper choice, roll decision, farkle, win, etc.)
- Emotional tone (confident, cautious, excited, frustrated)

#### Message Categories

Each category has 50-100 messages split across personalities:

**1. Keeper Selection Messages**
```php
// Easy bots - Uncertain, learning
"I'll keep these dice... fingers crossed! 🤞"
"Hmm, these look good to me!"
"Taking the {points} points - hope that's the right choice!"
"Is this a good combo? I think so!"

// Medium bots - Tactical, analytical
"Keeping {dice_description} for {points} points - best ratio here."
"This gives me {num_dice_left} dice for the next roll. Good position."
"Optimizing for point-per-die efficiency."

// Hard bots - Mathematical, confident
"Expected value: {ev}. Keeping {dice_description}."
"This is the statistically optimal combination."
"Probability analysis says keep the {dice_description}."
```

**2. Roll Again Messages**
```php
// Easy bots - Enthusiastic, risky
"Let's roll again! What could go wrong? 😅"
"Only {num_dice} dice left but I'm feeling lucky!"
"I'm going for it! You're going down!"
"My sensors say... ROLL! 🎲"

// Medium bots - Strategic
"Rolling again - {farkle_prob}% farkle risk is acceptable."
"Positive expected value. Let's continue."
"I need more points to catch up. Taking the risk."

// Hard bots - Calculated
"EV of rolling: {ev_value}. Proceeding."
"Farkle probability {farkle_prob}% < threshold. Roll."
"Game theory dictates another roll here."
```

**3. Banking Messages**
```php
// Easy bots - Relieved, cautious
"Banking {points} points - playing it safe!"
"That's enough for now! 😊"
"Better safe than sorry! Saving these points."

// Medium bots - Rational
"Banking {points} - risk/reward doesn't favor another roll."
"Negative expected value. Time to bank."
"Securing {points} points. Smart move."

// Hard bots - Clinical
"Banking {points}. EV of rolling is negative."
"Farkle probability too high. Banking is optimal."
"Maximum utility achieved. Saving score."
```

**4. Farkle Messages**
```php
// Easy bots - Disappointed, learning
"Oops! I farkled! 😢"
"Oh no! Better luck next time!"
"I'll learn from this mistake!"
"Beep boop... error! Lost my points! 🤖"

// Medium bots - Analytical
"Farkled. That was within the {farkle_prob}% probability."
"Statistical variance. It happens."
"Risk didn't pay off this time."

// Hard bots - Unemotional
"Farkle. Expected outcome within probability range."
"Data point recorded. Adjusting future calculations."
"Probability manifested. Moving on."
```

**5. Hot Dice Messages**
```php
// Easy bots - Excited
"Whoa! All my dice scored! Rolling all 6 again! 🎉"
"Hot dice! This is amazing!"

// Medium bots - Strategic
"Hot dice situation. Rolling all 6 - excellent position."
"All dice scored. Statistical advantage here."

// Hard bots - Matter-of-fact
"Hot dice. Re-rolling full set maximizes EV."
"Optimal outcome. Continuing with 6 dice."
```

**6. High Score Messages**
```php
// Easy bots - Celebratory
"Woohoo! {points} points! That's my best turn yet! 🎊"
"Did you see that?! {points} points!"

// Medium bots - Satisfied
"Solid turn. {points} points banked."
"Excellent result. {points} secured."

// Hard bots - Smug
"{points} points. As calculated."
"Predictably optimal outcome."
```

**7. Game State Commentary**
```php
// When ahead
Easy: "I'm winning! This is so fun! 😄"
Medium: "Maintaining my lead. Playing conservatively."
Hard: "Lead margin: {lead}. Probability of victory: {win_prob}%."

// When behind
Easy: "I need to catch up! Time to take risks!"
Medium: "Behind by {deficit}. Increasing aggression."
Hard: "Deficit detected. Adjusting risk parameters."

// Close game
Easy: "This is so close! My circuits are tingling! ⚡"
Medium: "Tight game. Every decision matters now."
Hard: "Win probability: 50%. Optimal play critical."
```

**8. Trash Talk (Rare, 5% chance)**
```php
// Easy bots - Friendly competition
"You're pretty good... but I'm learning fast! 🤖"
"Hope you're ready for my comeback!"

// Medium bots - Competitive
"My algorithms are superior today."
"You'll need more than luck to beat me."

// Hard bots - Condescending
"Your play is suboptimal. Disappointing."
"I've calculated 347 better moves you could have made."
"Human error is so predictable."
```

#### Message Selection Logic

```php
function Bot_GetMessage($botPlayer, $situation, $context = []) {
    $personality = $botPlayer['bot_algorithm']; // 'easy', 'medium', 'hard'
    $messages = Bot_GetMessagesForSituation($personality, $situation);

    // 5% chance of trash talk in competitive situations
    if (in_array($situation, ['keeper', 'roll_again', 'high_score']) && rand(1, 100) <= 5) {
        $trashTalk = Bot_GetMessagesForSituation($personality, 'trash_talk');
        if (!empty($trashTalk)) {
            $messages = $trashTalk;
        }
    }

    // Select random message from pool
    $message = $messages[array_rand($messages)];

    // Replace variables with context values
    $message = Bot_FormatMessage($message, $context);

    return $message;
}

function Bot_FormatMessage($template, $context) {
    // Replace {points}, {num_dice}, {farkle_prob}, etc.
    foreach ($context as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    return $template;
}
```

#### Message Frequency

To keep it engaging but not overwhelming:
- Show message on **every decision** (keeper choice, roll/bank decision)
- Show message on **game events** (farkle, hot dice, high score)
- Maximum 1 message per state transition
- Store last 5 messages in UI
- Auto-scroll to newest message

#### Database Schema for Messages

**Option 1: Store in PHP file (simpler)**
```php
// farkleBotMessages.php
return [
    'easy' => [
        'keeper' => [
            "I'll keep these dice... fingers crossed! 🤞",
            "Hmm, these look good to me!",
            // ... 50+ more messages
        ],
        'roll_again' => [ /* ... */ ],
        // ... more categories
    ],
    'medium' => [ /* ... */ ],
    'hard' => [ /* ... */ ]
];
```

**Option 2: Store in database table (more flexible)**
```sql
CREATE TABLE farkle_bot_messages (
    messageid SERIAL PRIMARY KEY,
    bot_algorithm bot_algorithm_type NOT NULL,
    situation VARCHAR(50) NOT NULL,  -- 'keeper', 'roll_again', 'bank', 'farkle', etc.
    message_text TEXT NOT NULL,
    is_trash_talk BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_bot_messages_lookup ON farkle_bot_messages(bot_algorithm, situation);
```

**Recommendation:** Start with **Option 1 (PHP file)** for simplicity, migrate to database later if we want user-submitted messages or A/B testing.

### D. Interactive Bot Play System

**New File: `wwwroot/farkleBotPlay.php`**

Functions:
- `Bot_StartTurn($gameId, $playerid)` - Initialize bot turn state
- `Bot_ExecuteTurnStep($gameId, $playerid)` - Execute next step of turn
- `Bot_GetTurnProgress($gameId, $playerid)` - Return current state for AJAX polling
- `Bot_CompleteTurn($gameId, $playerid)` - Finish turn and bank/farkle

#### Turn State Machine

States tracked in `farkle_bot_game_state` table:

1. **`rolling`**: Bot is rolling the dice
   - Server generates random dice roll
   - Store roll result in state

2. **`choosing_keepers`**: Bot is analyzing which dice to keep
   - Call `Bot_ChooseKeepers()` with current roll
   - Display "thinking" animation to user
   - Store chosen dice in state

3. **`deciding_roll`**: Bot is deciding whether to roll again or bank
   - Call `Bot_ShouldRollAgain()` with current state
   - Decision: continue to `rolling` state or go to `banking`

4. **`banking`**: Bot is saving the score
   - Call `FarklePass()` to bank turn score
   - Update player score and end turn

5. **`farkled`**: Bot rolled a farkle
   - No scoring dice in roll
   - Turn ends with 0 points

**Flow Example:**

```
Start Turn
  ↓
rolling: Roll 6 dice → [1, 2, 3, 4, 5, 6]
  ↓
choosing_keepers: Keep [1, 5] (150 pts)
  ↓
deciding_roll: "Should I roll 4 dice?" → YES
  ↓
rolling: Roll 4 dice → [2, 3, 4, 6]
  ↓
choosing_keepers: No scoring dice!
  ↓
farkled: Lost all 150 points
```

Client-side AJAX polls every 500ms to get current state and update display.

---

### Complete Bot Turn Example (Medium Algorithm)

**Starting Position:**
- Game score: Bot 2400, Opponent 2800 (bot is behind by 400)
- Bot's turn begins

**Roll 1:**
```
State: rolling
Dice rolled: [1, 1, 3, 5, 5, 6]

State: choosing_keepers
Bot_GetAllScoringCombinations() finds:
  - Keep [1, 1]: 200 pts, 2 dice (ratio: 100 pts/die)
  - Keep [5, 5]: 100 pts, 2 dice (ratio: 50 pts/die)
  - Keep [1, 1, 5, 5]: 300 pts, 4 dice (ratio: 75 pts/die)
  - Keep [1]: 100 pts, 1 die (ratio: 100 pts/die)
  - Keep [5]: 50 pts, 1 die (ratio: 50 pts/die)
  - Keep [1, 5]: 150 pts, 2 dice (ratio: 75 pts/die)
  - Keep [1, 1, 5]: 250 pts, 3 dice (ratio: 83 pts/die)
  - Keep [1, 5, 5]: 200 pts, 3 dice (ratio: 67 pts/die)

Medium bot chooses: [1, 1] - Best ratio at 100 pts/die
Turn score: 200
Dice remaining: 4

State: deciding_roll
Farkle probability (4 dice): 15.43%
Expected points (4 dice): ~200
EV of rolling: (1 - 0.1543) * 200 - (0.1543 * 200) = 138.28
Threshold: 350 + (400/10000)*200 = 358
Decision: EV > 30 AND turnScore (200) < threshold (358) → ROLL AGAIN
```

**Roll 2:**
```
State: rolling
Dice rolled: [2, 3, 4, 6]

State: choosing_keepers
Bot_GetAllScoringCombinations() finds: []
No scoring dice!

State: farkled
Turn ends with 0 points (lost the 200)
```

**Alternative Scenario - Bot Banks:**
```
Roll 1: Keeps [1, 1, 5, 5] = 300 pts, 2 dice left

State: deciding_roll
Farkle probability (2 dice): 44.44%
EV of rolling: (1 - 0.4444) * 100 - (0.4444 * 300) = -77.76
Decision: Negative EV → BANK IT

State: banking
Bot banks 300 points
New score: 2700 (still behind by 100)
Turn ends successfully
```

**With Bot Messages:**
```
[Cyber the Tactical Bot]: "Starting my turn. Let's see what we've got! 🎲"

Roll 1: [1, 1, 3, 5, 5, 6]
[Cyber]: "Keeping the 1s - 200 points. Best ratio at 100 pts/die."

Decision: Roll again
[Cyber]: "Rolling again - 15.4% farkle risk is acceptable. I need more points."

Roll 2: [2, 3, 4, 6]
[Cyber]: "Farkled. That was within the 15.4% probability. 😐"
```

### E. Background Bot Fill System

**New File: `wwwroot/farkleTimedTasks.php`**

Consolidate all cron-style tasks here:

```php
<?php
/**
 * Timed background tasks that run via farkle_fetch polling
 * Easy migration to cron jobs when available
 */

function TimedTasks_RunAll() {
    // Existing tasks (moved from farkleBackgroundTasks.php)
    TimedTask_RefreshLeaderboards();        // Every 5 minutes
    TimedTask_RefreshDailyLeaderboards();   // Every 1 hour
    TimedTask_CleanupStaleGames();          // Every 30 minutes

    // New bot task
    TimedTask_FillRandomGamesWithBots();    // Every 5 minutes
}

/**
 * Find random games waiting for players and add bots if timed out
 */
function TimedTask_FillRandomGamesWithBots() {
    // Throttle: run every 5 minutes
    $sql = "SELECT (paramvalue::numeric <= EXTRACT(EPOCH FROM NOW()))
            FROM siteinfo WHERE paramid=5";
    $shouldRun = db_select_query($sql, SQL_SINGLE_VALUE);

    if (!$shouldRun) return;

    BaseUtil_Debug("TimedTask: Checking random games for bot fills", 1);

    // Determine timeout based on environment
    $timeout = (defined('IS_PRODUCTION') && IS_PRODUCTION)
        ? BOT_FILL_TIMEOUT_PROD
        : BOT_FILL_TIMEOUT_DEV;

    // Find games waiting for players past timeout
    $sql = "SELECT gameid, maxturns
            FROM farkle_games
            WHERE gamewith = " . GAME_WITH_RANDOM . "
              AND winningplayer = 0
              AND actualplayers < maxturns
              AND starttime < NOW() - INTERVAL '$timeout seconds'";

    $games = db_select_query($sql, SQL_ALL_ROWS);

    foreach ($games as $game) {
        Bot_FillRandomGame($game['gameid'], $game['maxturns']);
    }
}

/**
 * Fill a random game with bot player(s)
 */
function Bot_FillRandomGame($gameid, $maxturns) {
    require_once('farkleBotAI.php');
    require_once('farkleGameFuncs.php');

    // Determine how many bot slots needed
    $sql = "SELECT actualplayers FROM farkle_games WHERE gameid = $gameid";
    $currentPlayers = db_select_query($sql, SQL_SINGLE_VALUE);
    $botsNeeded = $maxturns - $currentPlayers;

    BaseUtil_Debug("Bot fill: Game $gameid needs $botsNeeded bots", 1);

    for ($i = 0; $i < $botsNeeded; $i++) {
        // Select a medium bot for random fills (balanced difficulty)
        $botPlayer = Bot_SelectBotPlayer('medium');

        // Add bot to game
        $sql = "INSERT INTO farkle_games_players (gameid, playerid, playerturn)
                VALUES ($gameid, {$botPlayer['playerid']},
                        (SELECT COALESCE(MAX(playerturn), 0) + 1 FROM farkle_games_players WHERE gameid = $gameid))";
        db_command($sql);

        // Update actualplayers count
        $sql = "UPDATE farkle_games SET actualplayers = actualplayers + 1 WHERE gameid = $gameid";
        db_command($sql);

        BaseUtil_Debug("Bot fill: Added bot {$botPlayer['username']} to game $gameid", 1);
    }

    // Set bot play mode to instant (background fills complete all rounds immediately)
    $sql = "UPDATE farkle_games SET bot_play_mode = 'instant' WHERE gameid = $gameid";
    db_command($sql);

    // Have bot(s) play all 10 rounds instantly
    Bot_PlayGameInstantly($gameid)

    // Update throttle: next run in 5 minutes
    $sql = "UPDATE siteinfo
            SET paramvalue = EXTRACT(EPOCH FROM (NOW() + interval '5' minute))
            WHERE paramid = 5 AND paramname = 'last_bot_fill_check'";
    db_command($sql);
}
```

**Migration Note:** When cron is available:
1. Create cron job that calls `farkleTimedTasks.php` directly
2. Remove `TimedTasks_RunAll()` call from `farkle_fetch.php`
3. No logic changes needed

### F. Frontend Changes

#### JavaScript Constants (`js/farkleGame.js`)
```javascript
var GAME_WITH_BOT = 3;
var BOT_ALGORITHM_EASY = 'easy';
var BOT_ALGORITHM_MEDIUM = 'medium';
var BOT_ALGORITHM_HARD = 'hard';
```

#### New UI Components

**Lobby - Bot Game Selection**
- Add "Play a Bot" button in lobby
- Modal for algorithm selection (Easy/Medium/Hard)
- Bot algorithm descriptions:
  - **Easy:** "Friendly and makes mistakes - great for learning! (e.g., Byte, Chip, Beep)"
  - **Medium:** "Solid tactical play with strategic thinking (e.g., Cyber, Logic, Binary)"
  - **Hard:** "Advanced AI with optimal decision-making (e.g., Neural, Quantum, Apex)"
- Show bot names and titles in preview
- Future: Could add other algorithms like "Aggressive", "Conservative", "Chaos"

**Game Board - Bot Turn Display**
- Show "[BotName] the [Title] is thinking..." message (e.g., "Neural the Master Bot is thinking...")
- Animate dice rolls with delays
- Show kept dice accumulating
- Display decision reasoning: "[BotName] banks 450 points" or "[BotName] rolls again!"
- Bot players show in player list with their titles and levels (they level up over time!)

**Bot Chat Messages Area**
- New UI container (similar to `divWaitingForGamePlayer`) for bot commentary
- Blue-colored message area showing last 5 bot messages
- Bots announce decisions with personality-driven messages
- Messages explain strategy and add character to gameplay
- Example messages:
  - **Byte (Easy):** "I think I'll keep these dice... hope that's right! 🤖"
  - **Cyber (Medium):** "Keeping the 1s - maximizing my reroll potential."
  - **Neural (Hard):** "Expected value of 247.3. This is the optimal play."

#### Bot Progression System

**Bots Level Up Like Players!**

Bots earn XP and level up through gameplay:
- Win a game: Earn XP based on score and opponents
- Lose a game: Still earn participation XP
- Level thresholds same as human players
- Stats tracked: wins, losses, total points, highest round, etc.
- Bots never unlock achievements (those are for humans only)
- Starting levels vary by algorithm:
  - Easy algorithm: Start at level 1
  - Medium algorithm: Start at level 5
  - Hard algorithm: Start at level 10

This creates the illusion that harder bots have "played more games" and are more experienced. Over time, as bots play many games, easy bots might reach level 20+ making them appear as seasoned veterans!

**Implementation Note:**
- Use existing XP/leveling system from `farkleLevel.php`
- After each game, call normal game-end logic which updates bot stats
- Bots automatically benefit from existing progression mechanics

**Achievement Exclusion:**
- Modify `farkleAchievements.php` to check `is_bot` before granting achievements
- Bots can trigger achievement logic but never actually receive them
- This keeps achievements special for human players only

```php
// Example: In achievement granting function
function GrantAchievement($playerid, $achievementId) {
    // Check if player is a bot
    $isBot = db_select_query("SELECT is_bot FROM farkle_players WHERE playerid = $playerid", SQL_SINGLE_VALUE);
    if ($isBot) {
        return; // Bots don't earn achievements
    }

    // ... rest of achievement logic
}
```

#### AJAX Endpoints

**`farkle_fetch.php`** - Add new actions:
```php
else if ($p['action'] == 'startbotgame')
    $rc = Bot_StartNewGame($p['algorithm']);  // 'easy', 'medium', or 'hard'

else if ($p['action'] == 'getbotstatus')
    $rc = Bot_GetTurnProgress($p['gameid'], $p['playerid']);
```

### G. Testing Requirements

#### Unit Tests
- Probability calculations (verify against known values)
- Expected value computations
- Decision logic for each difficulty level
- Database operations (bot creation, state tracking)

#### Integration Tests
- Complete bot game from start to finish
- Bot fills random game after timeout
- Interactive turn progression
- Multiple bots in same game

#### Docker Test Plan
1. Start game against Easy bot (Byte, Chip, or Beep)
2. Verify bot plays turn with visible steps
3. Verify bot name shows as "[Name] the [Title]" (e.g., "Byte the Rookie Bot")
4. Complete full game and verify bot earns XP/levels up
5. Check bot stats page shows wins/losses/level
6. Verify bot does NOT earn achievements
7. Create random 2-player game
8. Wait 1 minute (dev timeout)
9. Verify medium-difficulty bot fills second slot
10. Check bot completes all 10 rounds instantly
11. Verify bot stats updated after instant game

#### Bot-Specific Tests
- Verify `is_bot = TRUE` players cannot login
- Verify bots don't appear in random matchmaking pool
- Verify bots level up correctly through multiple games
- Verify each algorithm behaves distinctly (Easy makes mistakes, Hard plays optimally)
- Check bot game history shows all games played

## File Structure

```
wwwroot/
├── farkleBotAI.php           (NEW) - Core bot decision algorithms
├── farkleBotPlay.php         (NEW) - Interactive bot turn execution
├── farkleBotMessages.php     (NEW) - Bot personality message bank
├── farkleTimedTasks.php      (NEW) - Consolidated cron-style tasks
├── farkleGameFuncs.php       (MODIFY) - Add bot game creation
├── farkle_fetch.php          (MODIFY) - Add bot action handlers
└── farkleBackgroundTasks.php (REMOVE/REFACTOR) - Move to TimedTasks

js/
├── farkleBot.js              (NEW) - Bot turn polling and display
├── farkleBotChat.js          (NEW) - Bot message display and formatting
├── farkleLobby.js            (MODIFY) - Add bot game option
└── farkleGame.js             (MODIFY) - Handle bot player rendering

templates/
├── farkle_div_lobby.tpl      (MODIFY) - Add "Play a Bot" UI
├── farkle_div_game.tpl       (MODIFY) - Bot turn display
└── farkle_div_bot_chat.tpl   (NEW) - Bot message area (or add to farkle_div_game.tpl)

docker/
└── init.sql                  (MODIFY) - Add bot tables and columns
```

## Implementation Phases

### Phase 1: Database & Core Infrastructure
**Tasks:**
1. Add database tables and columns (migration script)
2. Add constants to `farkleGameFuncs.php`
3. Create `farkleBotAI.php` with probability tables
4. Implement basic bot decision functions
5. Test in Docker with direct PHP calls

**Verification:**
- Database schema correct
- Can create bot player records
- Probability calculations return expected values

### Phase 2: Bot AI Logic
**Tasks:**
1. Extend `farkleDiceScoring.php` to return all possible scoring combinations (not just optimal)
2. Implement `Bot_GetAllScoringCombinations()` wrapper
3. Implement Easy algorithm (keeper selection + roll decision)
4. Implement Medium algorithm (keeper selection + roll decision)
5. Implement Hard algorithm (keeper selection + roll decision)
6. Create unit tests for both keeper selection and roll decisions

**Verification:**
- Each algorithm makes two distinct decisions per roll (keep what + roll again?)
- Easy bot: 30% mistake rate on keeper selection, simple threshold on rolling
- Medium bot: Points-per-die optimization, EV-based roll decisions
- Hard bot: Full EV on keepers, game-theoretic roll decisions
- No infinite loops or crashes
- Test with known dice rolls (e.g., [1,1,3,4,5,6]) produces expected keeper choices

### Phase 3: Bot Personality & Messages
**Tasks:**
1. Create `farkleBotMessages.php` with message banks
2. Write 50-100 messages per situation for each personality
3. Implement `Bot_GetMessage()` and message selection logic
4. Add message formatting with variable replacement
5. Test message variety and appropriateness

**Verification:**
- Each bot has distinct personality in messages
- Messages correctly reference game context ({points}, {dice}, etc.)
- Trash talk appears ~5% of the time
- Messages are appropriate for situation
- No duplicate messages in a single game

### Phase 4: Interactive Bot Play
**Tasks:**
1. Create `farkleBotPlay.php` with turn state machine
2. Implement step-by-step turn execution
3. Add bot turn state tracking
4. Create AJAX endpoints for bot status
5. Build frontend polling and display
6. Integrate bot messages with turn progression
7. Create bot chat UI container

**Verification:**
- Can start bot game from lobby
- Bot turn progresses visibly step-by-step
- Client sees dice rolls, keeps, banking
- Bot messages appear with each decision
- Message area shows last 5 messages
- Messages scroll automatically
- Turn completes correctly

### Phase 5: Background Bot Fill
**Tasks:**
1. Create `farkleTimedTasks.php`
2. Move existing background tasks from `farkleBackgroundTasks.php`
3. Implement `TimedTask_FillRandomGamesWithBots()`
4. Add siteinfo paramid=5 for throttling
5. Update `farkle_fetch.php` to call TimedTasks

**Verification:**
- Random game gets bot after timeout
- Bot completes game instantly
- Throttling works (doesn't run every request)
- Existing background tasks still work

### Phase 6: Frontend & Polish
**Tasks:**
1. Add "Play a Bot" button to lobby
2. Create difficulty selection modal
3. Implement bot turn display animations
4. Add bot player names/avatars
5. Show bot reasoning messages (optional)

**Verification:**
- UI flows smoothly
- Bot turns are visually clear
- No UI bugs or glitches
- Mobile/tablet compatible

### Phase 7: Testing & Deployment
**Tasks:**
1. Full integration testing in Docker
2. Test all difficulty levels
3. Test random game filling
4. Performance testing (many bot games)
5. Production deployment
6. Monitor for issues

**Verification:**
- All test scenarios pass
- No performance degradation
- Bot fills working in production
- User feedback positive

## Configuration

**Environment Detection:**
```php
// In farkleconfig or baseutil.php
define('IS_PRODUCTION',
    strpos($_SERVER['HTTP_HOST'] ?? '', 'farkledice.com') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'herokuapp.com') !== false
);

// Use in bot fill timeout
$botTimeout = IS_PRODUCTION ? BOT_FILL_TIMEOUT_PROD : BOT_FILL_TIMEOUT_DEV;
```

## Bot Personality & Character

### Bot Names by Algorithm

Note: Using `bot_algorithm` ENUM instead of "difficulty" allows future expansion to other play styles like "aggressive", "conservative", or "chaos" modes.

**Easy Bots (Learning, Friendly):**
- **Byte the Rookie Bot** - Enthusiastic learner, uses emojis, sometimes uncertain
- **Chip the Friendly Bot** - Encouraging and supportive, plays for fun
- **Beep the Learning Bot** - Analytical but makes mistakes, references being a robot

**Medium Bots (Tactical, Strategic):**
- **Cyber the Tactical Bot** - Military-themed language, talks about strategy
- **Logic the Strategic Bot** - Rational and methodical, explains reasoning
- **Binary the Calculated Bot** - Numbers-focused, talks in stats

**Hard Bots (Advanced, Masterful):**
- **Neural the Master Bot** - References neural networks and AI concepts
- **Quantum the Perfect Bot** - Physics references, talks about probability fields
- **Apex the Supreme Bot** - Superior and condescending, uses advanced vocabulary

### Display in Game

When viewing bot in player list or game board:
```
Neural the Master Bot - Level 23
Wins: 487 | Losses: 213 | Highest: 14,250
```

Bots accumulate real stats over time, making them feel like persistent opponents!

## Future Enhancements (Not in Scope)

### New Bot Algorithms
With the ENUM structure, easily add new algorithms:
- **'aggressive'**: Always pushes for high scores, takes risks
- **'conservative'**: Banks early, plays safe
- **'chaos'**: Completely random decisions for fun
- **'adaptive'**: Adjusts strategy based on opponent behavior
- **'speedrun'**: Tries to finish games quickly

### Other Enhancements
- Bot tournaments (all-bot brackets)
- Multiple bots in one game (practice mode)
- Bot chat messages/reactions
- Bot avatars/images
- "Famous bot" leaderboard showing top-performing bots
- Player can "train" custom bots with specific thresholds

## Risks & Mitigations

**Risk:** Bot decision making too slow
**Mitigation:** Pre-calculate probabilities, optimize queries, cache game state

**Risk:** Bot fills games too aggressively
**Mitigation:** Conservative timeouts, monitoring, killswitch via siteinfo flag

**Risk:** Interactive mode feels sluggish
**Mitigation:** Tune delays, parallel AJAX calls, optimize rendering

**Risk:** Bots break existing game logic
**Mitigation:** Extensive testing, feature flag to disable, backward compatibility

## Success Metrics

- Bots successfully complete games without errors
- Interactive mode feels responsive (<500ms per step)
- Random games filled within 5 minutes of timeout
- User engagement increases with bot option
- No performance degradation on main game flow

---

## Notes for Claude Code

When implementing:
- Follow existing code patterns in `farkleGameFuncs.php`
- Use PDO for all database operations
- Add debug logging at level 7 for bot decisions
- Ensure mobile compatibility for all UI changes
- Test in Docker after each phase
- Keep bot logic separate from core game logic for maintainability
