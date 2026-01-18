# Task: Backend PHP Logic for Bot Players

**Assignee:** Backend PHP Developer
**Estimated Time:** 3-4 days
**Dependencies:** Database schema must be complete
**Status:** Waiting for database task

## Objective

Implement bot AI decision-making algorithms, personality message system, interactive turn execution, background bot fill logic, and timed task consolidation.

## Files to Create

### 1. `wwwroot/farkleBotAI.php` - Core Bot Intelligence

Bot decision algorithms for keeper selection and roll/bank decisions.

**Required Functions:**

```php
<?php
/**
 * Select a random bot player of the specified algorithm
 *
 * @param string $algorithm 'easy', 'medium', or 'hard'
 * @return array|null Bot player record or null if none available
 */
function Bot_SelectBotPlayer($algorithm) {
    $sql = "SELECT * FROM farkle_players
            WHERE is_bot = TRUE AND bot_algorithm = '$algorithm'
            ORDER BY RANDOM() LIMIT 1";
    return db_select_query($sql, SQL_SINGLE_ROW);
}

/**
 * Get bot algorithm for a player
 */
function Bot_GetBotAlgorithm($playerid) {
    $sql = "SELECT bot_algorithm FROM farkle_players WHERE playerid = $playerid";
    return db_select_query($sql, SQL_SINGLE_VALUE);
}

/**
 * Main decision wrapper: Choose which dice to keep
 */
function Bot_ChooseKeepers($diceRoll, $turnScore, $diceLeft, $algorithm) {
    // Get all possible scoring combinations
    $combos = Bot_GetAllScoringCombinations($diceRoll);

    if (empty($combos)) {
        return null; // Farkle
    }

    // Route to appropriate algorithm
    switch ($algorithm) {
        case 'easy':
            return Bot_Easy_ChooseKeepers($combos);
        case 'medium':
            return Bot_Medium_ChooseKeepers($combos, $diceLeft);
        case 'hard':
            return Bot_Hard_ChooseKeepers($combos, $turnScore, $diceLeft);
        default:
            return $combos[0]; // Fallback to highest scoring
    }
}

/**
 * Main decision wrapper: Roll again or bank?
 */
function Bot_ShouldRollAgain($keptDice, $turnScore, $diceLeft, $gameState, $algorithm) {
    switch ($algorithm) {
        case 'easy':
            return Bot_Easy_ShouldRollAgain($turnScore, $diceLeft);
        case 'medium':
            return Bot_Medium_ShouldRollAgain($turnScore, $diceLeft, $gameState);
        case 'hard':
            return Bot_Hard_ShouldRollAgain($turnScore, $diceLeft, $gameState);
        default:
            return $turnScore < 400; // Fallback
    }
}
```

#### Algorithm Implementations

See `planning/bot-player-implementation.md` lines 178-395 for complete algorithm pseudocode.

**Key Algorithms:**

**Easy:**
- Keeper: 30% chance to pick random combo, otherwise highest points
- Roll: Simple threshold (300-450), avoid 1-2 dice

**Medium:**
- Keeper: Maximize points-per-die ratio, bonus for hot dice
- Roll: EV calculation with position awareness

**Hard:**
- Keeper: Full EV calculation considering future rolls
- Roll: Game-theoretic optimization (conservative when ahead, aggressive when behind)

**Helper Functions:**

```php
function Bot_CalculateFarkleProbability($diceCount) {
    $probs = [1 => 0.6667, 2 => 0.4444, 3 => 0.2778, 4 => 0.1543, 5 => 0.0772, 6 => 0.0231];
    return $probs[$diceCount] ?? 0;
}

function Bot_EstimateExpectedPoints($diceCount) {
    $expected = [1 => 83, 2 => 100, 3 => 150, 4 => 200, 5 => 250, 6 => 350];
    return $expected[$diceCount] ?? 0;
}

/**
 * Get all possible scoring combinations from a dice roll
 * Extends farkleDiceScoring.php to return ALL combinations, not just optimal
 */
function Bot_GetAllScoringCombinations($diceRoll) {
    require_once('farkleDiceScoring.php');

    // TODO: Implement combination finder
    // Should return array of ['dice' => [1,1], 'points' => 200, 'description' => 'two 1s']

    return [];
}
```

---

### 2. `wwwroot/farkleBotMessages.php` - Personality System

Message bank with 400+ messages (50 per situation × 8 situations).

**File Structure:**

```php
<?php
/**
 * Bot personality messages
 * Returns array of messages organized by algorithm and situation
 */

return [
    'easy' => [
        'keeper' => [
            "I'll keep these dice... fingers crossed! 🤞",
            "Hmm, these look good to me!",
            "Taking the {points} points - hope that's the right choice!",
            "Is this a good combo? I think so!",
            "Keeping {dice_description}. Did I do that right? 🤖",
            "These dice are scoring! Beep boop! 🎲",
            // ... 44 more messages
        ],
        'roll_again' => [
            "Let's roll again! What could go wrong? 😅",
            "Only {num_dice} dice left but I'm feeling lucky!",
            "I'm going for it! You're going down!",
            "My sensors say... ROLL! 🎲",
            "Rolling again! This is so exciting!",
            // ... 45 more messages
        ],
        'bank' => [
            "Banking {points} points - playing it safe!",
            "That's enough for now! 😊",
            "Better safe than sorry! Saving these points.",
            // ... 47 more messages
        ],
        'farkle' => [
            "Oops! I farkled! 😢",
            "Oh no! Better luck next time!",
            "I'll learn from this mistake!",
            "Beep boop... error! Lost my points! 🤖",
            // ... 46 more messages
        ],
        'hot_dice' => [
            "Whoa! All my dice scored! Rolling all 6 again! 🎉",
            "Hot dice! This is amazing!",
            "All scoring! My circuits are overloading with joy! ⚡",
            // ... 47 more messages
        ],
        'high_score' => [
            "Woohoo! {points} points! That's my best turn yet! 🎊",
            "Did you see that?! {points} points!",
            // ... 48 more messages
        ],
        'game_state' => [
            "I'm winning! This is so fun! 😄",
            "I need to catch up! Time to take risks!",
            "This is so close! My circuits are tingling! ⚡",
            // ... 47 more messages
        ],
        'trash_talk' => [
            "You're pretty good... but I'm learning fast! 🤖",
            "Hope you're ready for my comeback!",
            "I may be new, but I'm catching on quick!",
            // ... 47 more messages
        ]
    ],

    'medium' => [
        'keeper' => [
            "Keeping {dice_description} for {points} points - best ratio here.",
            "This gives me {num_dice_left} dice for the next roll. Good position.",
            "Optimizing for point-per-die efficiency.",
            // ... 47 more messages
        ],
        'roll_again' => [
            "Rolling again - {farkle_prob}% farkle risk is acceptable.",
            "Positive expected value. Let's continue.",
            "I need more points to catch up. Taking the risk.",
            // ... 47 more messages
        ],
        // ... rest of medium categories
    ],

    'hard' => [
        // ... all hard categories with 50+ messages each
    ]
];
```

**Message Functions:**

```php
/**
 * Get a message for a specific situation
 */
function Bot_GetMessage($botPlayer, $situation, $context = []) {
    static $messages = null;
    if ($messages === null) {
        $messages = require('farkleBotMessages.php');
    }

    $personality = $botPlayer['bot_algorithm'];

    // 5% chance of trash talk in competitive situations
    if (in_array($situation, ['keeper', 'roll_again', 'high_score']) && rand(1, 100) <= 5) {
        $situation = 'trash_talk';
    }

    $messageList = $messages[$personality][$situation] ?? [];
    if (empty($messageList)) {
        return null;
    }

    $message = $messageList[array_rand($messageList)];
    return Bot_FormatMessage($message, $context, $botPlayer);
}

/**
 * Replace $variable placeholders with actual values
 *
 * @param string $template Message template with $variables
 * @param array $context Contextual data (scores, dice, etc.)
 * @param array $botPlayer Bot player record
 * @return string Formatted message
 */
function Bot_FormatMessage($template, $context, $botPlayer) {
    // Build complete variable map
    $variables = [
        // Bot info
        '$bot_username' => $botPlayer['username'],
        '$bot_level' => $botPlayer['level'],
        '$bot_score' => $context['bot_score'] ?? 0,
        '$bot_last_keep_score' => $context['bot_last_keep_score'] ?? 0,
        '$final_bot_score' => $context['final_bot_score'] ?? 0,

        // Player info (from context)
        '$player_username' => $context['player_username'] ?? 'Player',
        '$player_level' => $context['player_level'] ?? 0,
        '$player_score' => $context['player_score'] ?? 0,
        '$player_last_keep_score' => $context['player_last_keep_score'] ?? 0,
        '$final_player_score' => $context['final_player_score'] ?? 0,

        // Game state
        '$round' => $context['round'] ?? 1,
        '$lead' => abs($context['lead'] ?? 0),

        // Scoring
        '$points' => $context['points'] ?? 0,
        '$turn_score' => $context['turn_score'] ?? 0,

        // Dice info
        '$dice_description' => $context['dice_description'] ?? 'dice',
        '$num_dice' => $context['num_dice'] ?? 6,
        '$num_dice_left' => $context['num_dice_left'] ?? 6,
        '$farkle_prob' => $context['farkle_prob'] ?? 0,

        // Game conclusion
        '$winner' => $context['winner'] ?? '',
        '$score_difference' => $context['score_difference'] ?? 0,
        '$game_result' => $context['game_result'] ?? ''
    ];

    // Replace all variables
    foreach ($variables as $var => $value) {
        $template = str_replace($var, $value, $template);
    }

    return $template;
}

/**
 * Build context for message formatting from game state
 *
 * @param int $gameid Game ID
 * @param int $botPlayerid Bot player ID
 * @param int $humanPlayerid Human player ID
 * @param string $situation Message situation
 * @return array Context variables
 */
function Bot_BuildMessageContext($gameid, $botPlayerid, $humanPlayerid, $situation) {
    // Get game data
    $gameData = GetGameData($gameid);
    $botPlayer = db_select_query("SELECT * FROM farkle_players WHERE playerid = $botPlayerid", SQL_SINGLE_ROW);
    $humanPlayer = db_select_query("SELECT * FROM farkle_players WHERE playerid = $humanPlayerid", SQL_SINGLE_ROW);

    // Get scores
    $botScore = GetPlayerScore($gameid, $botPlayerid);
    $humanScore = GetPlayerScore($gameid, $humanPlayerid);

    // Build context
    $context = [
        'player_username' => $humanPlayer['username'],
        'player_level' => $humanPlayer['level'],
        'player_score' => $humanScore,

        'bot_score' => $botScore,
        'round' => $gameData['currentround'] ?? 1,
        'lead' => $botScore - $humanScore,
    ];

    // Add situation-specific context
    if ($situation == 'keeper') {
        // Last kept score from bot state
        $state = Bot_GetCurrentState($gameid, $botPlayerid);
        if ($state) {
            $context['bot_last_keep_score'] = $state['turn_score'] ?? 0;
        }
    }

    return $context;
}
```

**Message Categories (50+ messages each):**
0. `introduction` - Game start greeting (uses $player_username, $player_level, $bot_level)
1. `keeper` - Announcing which dice to keep (uses $bot_last_keep_score, $dice_description)
2. `roll_again` - Explaining decision to roll again (uses $farkle_prob, $num_dice)
3. `bank` - Announcing banking decision (uses $turn_score)
4. `farkle` - Reaction to farkling
5. `hot_dice` - Excitement about hot dice
6. `high_score` - Celebrating good turn 500+ points (uses $turn_score)
7. `game_state` - Commentary on being ahead/behind/close (uses $lead, $round, $player_username)
8. `trash_talk` - Rare competitive messages (uses $player_username, $player_level, $player_last_keep_score)
9. `player_achievement` - Reacting to player's moves (uses $player_last_keep_score, $player_username)
10. `conclusion` - Game end message (uses $winner, $final_bot_score, $final_player_score, $score_difference)

---

### 3. `wwwroot/farkleBotPlay.php` - Interactive Turn Execution

State machine for step-by-step bot turns.

**Core Functions:**

```php
/**
 * Start a new bot turn
 */
function Bot_StartTurn($gameid, $playerid) {
    // Create initial state
    $sql = "INSERT INTO farkle_bot_game_state
            (gameid, playerid, current_step, dice_remaining, turn_score, created_at, updated_at)
            VALUES ($gameid, $playerid, 'rolling', 6, 0, NOW(), NOW())
            RETURNING stateid";

    return db_select_query($sql, SQL_SINGLE_VALUE);
}

/**
 * Execute the next step of bot's turn
 * Returns current state for frontend display
 */
function Bot_ExecuteTurnStep($gameid, $playerid) {
    // Get current state
    $state = Bot_GetCurrentState($gameid, $playerid);

    switch ($state['current_step']) {
        case 'rolling':
            return Bot_Step_Roll($state);

        case 'choosing_keepers':
            return Bot_Step_ChooseKeepers($state);

        case 'deciding_roll':
            return Bot_Step_DecideRollOrBank($state);

        case 'banking':
            return Bot_Step_Bank($state);

        case 'farkled':
            return Bot_Step_Farkle($state);
    }
}

/**
 * Get current turn progress (for AJAX polling)
 */
function Bot_GetTurnProgress($gameid, $playerid) {
    $sql = "SELECT * FROM farkle_bot_game_state
            WHERE gameid = $gameid AND playerid = $playerid
            ORDER BY created_at DESC LIMIT 1";

    return db_select_query($sql, SQL_SINGLE_ROW);
}
```

**State Implementations:**

```php
function Bot_Step_Roll($state) {
    // Roll dice
    $numDice = $state['dice_remaining'];
    $roll = [];
    for ($i = 0; $i < $numDice; $i++) {
        $roll[] = rand(1, 6);
    }

    // Save roll to state
    $sql = "UPDATE farkle_bot_game_state
            SET last_roll = '" . json_encode($roll) . "',
                current_step = 'choosing_keepers',
                updated_at = NOW()
            WHERE stateid = {$state['stateid']}";
    db_command($sql);

    return ['step' => 'rolled', 'dice' => $roll];
}

function Bot_Step_ChooseKeepers($state) {
    $roll = json_decode($state['last_roll'], true);
    $algorithm = Bot_GetBotAlgorithm($state['playerid']);

    // Bot chooses keepers
    $choice = Bot_ChooseKeepers($roll, $state['turn_score'], $state['dice_remaining'], $algorithm);

    if ($choice === null) {
        // Farkle!
        $botPlayer = db_select_query("SELECT * FROM farkle_players WHERE playerid = {$state['playerid']}", SQL_SINGLE_ROW);

        // Get farkle message with context
        $context = Bot_BuildMessageContext($state['gameid'], $state['playerid'], $humanPlayerid, 'farkle');
        $message = Bot_GetMessage($botPlayer, 'farkle', $context);

        $sql = "UPDATE farkle_bot_game_state
                SET current_step = 'farkled',
                    last_message = '" . addslashes($message) . "',
                    updated_at = NOW()
                WHERE stateid = {$state['stateid']}";
        db_command($sql);

        return ['step' => 'farkled', 'message' => $message];
    }

    // Update state with keepers
    $newTurnScore = $state['turn_score'] + $choice['points'];
    $diceLeft = $state['dice_remaining'] - count($choice['dice']);
    if ($diceLeft == 0) $diceLeft = 6; // Hot dice

    // Get message with full context
    $botPlayer = db_select_query("SELECT * FROM farkle_players WHERE playerid = {$state['playerid']}", SQL_SINGLE_ROW);
    $context = Bot_BuildMessageContext($state['gameid'], $state['playerid'], $humanPlayerid, 'keeper');
    $context['bot_last_keep_score'] = $choice['points'];
    $context['dice_description'] = $choice['description'];
    $context['num_dice_left'] = $diceLeft;

    $message = Bot_GetMessage($botPlayer, 'keeper', $context);

    $sql = "UPDATE farkle_bot_game_state
            SET dice_kept = '" . json_encode($choice['dice']) . "',
                turn_score = $newTurnScore,
                dice_remaining = $diceLeft,
                current_step = 'deciding_roll',
                last_message = '" . addslashes($message) . "',
                updated_at = NOW()
            WHERE stateid = {$state['stateid']}";
    db_command($sql);

    return ['step' => 'chose_keepers', 'kept' => $choice, 'message' => $message];
}

/**
 * Send introduction message at game start
 */
function Bot_SendIntroduction($gameid, $botPlayerid, $humanPlayerid) {
    $botPlayer = db_select_query("SELECT * FROM farkle_players WHERE playerid = $botPlayerid", SQL_SINGLE_ROW);

    $context = Bot_BuildMessageContext($gameid, $botPlayerid, $humanPlayerid, 'introduction');
    $message = Bot_GetMessage($botPlayer, 'introduction', $context);

    // Store or return message for display
    return $message;
}

/**
 * Send conclusion message at game end
 */
function Bot_SendConclusion($gameid, $botPlayerid, $humanPlayerid, $winnerId) {
    $botPlayer = db_select_query("SELECT * FROM farkle_players WHERE playerid = $botPlayerid", SQL_SINGLE_ROW);

    $botScore = GetPlayerScore($gameid, $botPlayerid);
    $humanScore = GetPlayerScore($gameid, $humanPlayerid);
    $humanPlayer = db_select_query("SELECT username FROM farkle_players WHERE playerid = $humanPlayerid", SQL_SINGLE_ROW);

    $context = Bot_BuildMessageContext($gameid, $botPlayerid, $humanPlayerid, 'conclusion');
    $context['final_bot_score'] = $botScore;
    $context['final_player_score'] = $humanScore;
    $context['score_difference'] = abs($botScore - $humanScore);
    $context['winner'] = ($winnerId == $botPlayerid) ? $botPlayer['username'] : $humanPlayer['username'];
    $context['game_result'] = ($winnerId == $botPlayerid) ? 'won' : 'lost';

    $message = Bot_GetMessage($botPlayer, 'conclusion', $context);

    return $message;
}

// ... similar implementations for other steps
```

---

### 4. `wwwroot/farkleTimedTasks.php` - Background Task Consolidation

Consolidate all cron-style tasks (including new bot fill).

**Structure:**

```php
<?php
/**
 * Timed background tasks run via farkle_fetch polling
 * Easy migration to cron jobs when available
 */

require_once('farkleLeaderboard.php');
require_once('farklePageFuncs.php');
require_once('farkleBotAI.php');

/**
 * Main entry point - called from farkle_fetch.php
 */
function TimedTasks_RunAll() {
    // Only run for logged-in users to reduce load
    if (!isset($_SESSION['playerid'])) {
        return;
    }

    // Run all timed tasks (each has own throttle)
    TimedTask_RefreshLeaderboards();        // Every 5 minutes
    TimedTask_RefreshDailyLeaderboards();   // Every 1 hour
    TimedTask_CleanupStaleGames();          // Every 30 minutes
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

    // Find games waiting for players
    $sql = "SELECT gameid, maxturns, actualplayers
            FROM farkle_games
            WHERE gamewith = " . GAME_WITH_RANDOM . "
              AND winningplayer = 0
              AND actualplayers < maxturns
              AND starttime < NOW() - INTERVAL '$timeout seconds'";

    $games = db_select_query($sql, SQL_ALL_ROWS);

    foreach ($games as $game) {
        Bot_FillRandomGame($game['gameid'], $game['maxturns'], $game['actualplayers']);
    }

    // Update throttle: next run in 5 minutes
    $sql = "UPDATE siteinfo
            SET paramvalue = EXTRACT(EPOCH FROM (NOW() + interval '5' minute))
            WHERE paramid = 5";
    db_command($sql);
}

/**
 * Fill a specific game with bot player(s)
 */
function Bot_FillRandomGame($gameid, $maxturns, $currentPlayers) {
    $botsNeeded = $maxturns - $currentPlayers;

    BaseUtil_Debug("Bot fill: Game $gameid needs $botsNeeded bots", 1);

    for ($i = 0; $i < $botsNeeded; $i++) {
        // Select a medium bot for random fills (balanced difficulty)
        $botPlayer = Bot_SelectBotPlayer('medium');

        if (!$botPlayer) {
            BaseUtil_Error("No medium bots available for game $gameid");
            continue;
        }

        // Add bot to game
        $sql = "INSERT INTO farkle_games_players (gameid, playerid, playerturn)
                VALUES ($gameid, {$botPlayer['playerid']},
                        (SELECT COALESCE(MAX(playerturn), 0) + 1
                         FROM farkle_games_players WHERE gameid = $gameid))";
        db_command($sql);

        // Update actualplayers count
        $sql = "UPDATE farkle_games
                SET actualplayers = actualplayers + 1
                WHERE gameid = $gameid";
        db_command($sql);

        BaseUtil_Debug("Bot fill: Added {$botPlayer['username']} to game $gameid", 1);
    }

    // Set bot play mode to instant
    $sql = "UPDATE farkle_games SET bot_play_mode = 'instant' WHERE gameid = $gameid";
    db_command($sql);

    // Have bot(s) play all 10 rounds instantly
    Bot_PlayGameInstantly($gameid);
}

// ... other timed tasks (moved from farkleBackgroundTasks.php)
```

---

### 5. Modify `wwwroot/farkle_fetch.php`

Add AJAX endpoints:

```php
// In the main action switch
else if ($p['action'] == 'startbotgame')
    $rc = Bot_StartNewGame($_SESSION['playerid'], $p['algorithm']);

else if ($p['action'] == 'getbotstatus')
    $rc = Bot_GetTurnProgress($p['gameid'], $p['botplayerid']);

else if ($p['action'] == 'executebot step')
    $rc = Bot_ExecuteTurnStep($p['gameid'], $p['botplayerid']);
```

**New Functions:**

```php
function Bot_StartNewGame($humanPlayerid, $botAlgorithm) {
    require_once('farkleBotAI.php');
    require_once('farkleGameFuncs.php');

    // Select bot
    $botPlayer = Bot_SelectBotPlayer($botAlgorithm);
    if (!$botPlayer) {
        return ['Error' => 'No bot available'];
    }

    // Create game
    $players = json_encode([$humanPlayerid, $botPlayer['playerid']]);
    $gameData = FarkleNewGame($players, 0, 10000, GAME_WITH_BOT, GAME_MODE_10ROUND, false, 2);

    // Set bot play mode
    if (!empty($gameData['gameid'])) {
        $sql = "UPDATE farkle_games SET bot_play_mode = 'interactive' WHERE gameid = {$gameData['gameid']}";
        db_command($sql);
    }

    return $gameData;
}
```

---

### 6. Modify `wwwroot/farkleGameFuncs.php`

Add constants:

```php
// Game types (add after existing)
define('GAME_WITH_BOT', 3);

// Bot algorithms (match ENUM)
define('BOT_ALGORITHM_EASY', 'easy');
define('BOT_ALGORITHM_MEDIUM', 'medium');
define('BOT_ALGORITHM_HARD', 'hard');

// Bot play modes
define('BOT_PLAY_INTERACTIVE', 'interactive');
define('BOT_PLAY_INSTANT', 'instant');

// Bot configuration
define('BOT_FILL_TIMEOUT_DEV', 60);          // 1 minute
define('BOT_FILL_TIMEOUT_PROD', 86400);      // 24 hours
```

---

### 7. Modify `wwwroot/farkleBackgroundTasks.php`

Replace with call to TimedTasks:

```php
<?php
/**
 * DEPRECATED: Background tasks moved to farkleTimedTasks.php
 * This file kept for backward compatibility
 */

require_once('farkleTimedTasks.php');

function BackgroundMaintenance() {
    TimedTasks_RunAll();
}
```

---

## Testing Checklist

### Unit Tests

```php
// Test bot selection
$easy = Bot_SelectBotPlayer('easy');
assert($easy['bot_algorithm'] == 'easy');

// Test message system
$bot = ['bot_algorithm' => 'medium', 'username' => 'Cyber'];
$msg = Bot_GetMessage($bot, 'keeper', ['points' => 200]);
assert(strpos($msg, '200') !== false);

// Test farkle probability
assert(Bot_CalculateFarkleProbability(1) == 0.6667);
assert(Bot_CalculateFarkleProbability(6) == 0.0231);
```

### Integration Tests

1. Start bot game: `Bot_StartNewGame($playerid, 'easy')`
2. Verify game created with bot player
3. Execute full bot turn step-by-step
4. Verify messages appear correctly
5. Test bot fills random game after timeout

---

## Acceptance Criteria

- [ ] All bot AI algorithms implemented (Easy, Medium, Hard)
- [ ] Keeper selection and roll/bank decisions work for each algorithm
- [ ] 400+ personality messages written and organized
- [ ] Message system correctly formats variables
- [ ] Interactive turn state machine works step-by-step
- [ ] Background bot fill identifies and fills timed-out games
- [ ] Timed tasks consolidated in farkleTimedTasks.php
- [ ] AJAX endpoints functional
- [ ] All unit tests pass
- [ ] Integration test: Complete bot game from start to finish

---

## Performance Considerations

- Cache bot message arrays in memory (static variable)
- Minimize database queries in turn state machine
- Use prepared statements for repeated queries
- Index `is_bot` and `bot_algorithm` columns (done in DB task)

---

## Security Notes

- Bot accounts have `password='LOCKED'` - cannot be logged into
- Bot player selection uses `random_selectable=0` to exclude from matchmaking
- All bot decisions are server-side - no client manipulation possible
- Messages are pre-written - no user input in bot messages
