# Bot Player Frontend - Code Changes

## File 1: wwwroot/farkleGameFuncs.php

### Change: Added is_bot and bot_algorithm to player data query

**Location**: Line 651-660 (FarkleSendUpdate function)

**Before**:
```php
// Get information about the players
$sql = "select COALESCE(fullname,username) as username, a.playerid, a.playerround,
    a.playerscore, b.cardcolor, b.playerlevel,
    a.lastxpgain, a.lastroundscore, $rollingScore
    COALESCE((select COALESCE(sum(setscore),0) from farkle_sets where playerid=a.playerid and gameid=$gameid and roundnum=$currentRound),0) as roundscore,
    a.playerturn, COALESCE(b.playertitle,'') as playertitle, b.titlelevel,
    NOW() - a.lastplayed as lastplayedseconds
    from farkle_games_players a, farkle_players b
    where a.gameid=$gameid and a.playerid=b.playerid
    ORDER BY (a.playerid=$playerid) desc, a.playerscore desc, b.lastplayed desc";
```

**After**:
```php
// Get information about the players
$sql = "select COALESCE(fullname,username) as username, a.playerid, a.playerround,
    a.playerscore, b.cardcolor, b.playerlevel,
    a.lastxpgain, a.lastroundscore, $rollingScore
    COALESCE((select COALESCE(sum(setscore),0) from farkle_sets where playerid=a.playerid and gameid=$gameid and roundnum=$currentRound),0) as roundscore,
    a.playerturn, COALESCE(b.playertitle,'') as playertitle, b.titlelevel,
    NOW() - a.lastplayed as lastplayedseconds,
    b.is_bot, b.bot_algorithm
    from farkle_games_players a, farkle_players b
    where a.gameid=$gameid and a.playerid=b.playerid
    ORDER BY (a.playerid=$playerid) desc, a.playerscore desc, b.lastplayed desc";
```

**Why**: This ensures that bot player information (is_bot flag and algorithm type) is included in every game update sent to the frontend.

---

## File 2: wwwroot/js/farkleGame.js

### Change 1: Added bot-related constants and global variables

**Location**: Lines 40-55

**Code Added**:
```javascript
var GAME_WITH_BOT = 3;

var GAME_STATE_LOADING = 0;
var GAME_STATE_ROLLING = 1;
var GAME_STATE_ROLLED = 2;
var GAME_STATE_PASSED = 3;
var GAME_STATE_WATCHING = 4;

// Bot-related globals
var gBotTurnTimer = null;
var gBotIsPlaying = false;
var gBotPlayerIds = [];  // Array of bot player IDs in current game
var BOT_STEP_DELAY_MS = 800;  // Delay between bot steps for animation
```

**Why**: Defines constants for bot games and global state variables to track bot turn execution.

---

### Change 2: Added bot state cleanup in FarkleResetGame()

**Location**: Lines 79-85

**Code Added**:
```javascript
// Reset bot state
gBotIsPlaying = false;
gBotPlayerIds = [];
if( gBotTurnTimer ) {
    clearTimeout( gBotTurnTimer );
    gBotTurnTimer = null;
}
```

**Why**: Ensures bot state is properly reset when starting a new game or refreshing the page.

---

### Change 3: Added bot detection in PopulatePlayerData()

**Location**: Lines 363-376, 416

**Code Added**:
```javascript
g_myPlayerIndex = -1;
gBotPlayerIds = [];  // Reset bot player list

for( i=0; i<gGamePlayerData.length; i++ )
{
    p = gGamePlayerData[i];

    // Find our player's index in the data and record our player's latest score
    if( p.playerid == playerid ) g_myPlayerIndex = i;

    // Track bot players
    if( p.is_bot ) {
        gBotPlayerIds.push( parseInt(p.playerid) );
        ConsoleDebug( "PopulatePlayerData: Found bot player: " + p.username + " (id=" + p.playerid + ")" );
    }

    // ... rest of function
}

// At end of function (line 416):
// Check if it's a bot's turn and start bot play if needed
Bot_CheckAndStartTurn();
```

**Why**: Detects which players are bots and triggers bot turn execution when appropriate.

---

### Change 4: Added complete bot turn handling system

**Location**: Lines 421-640

**Code Added**:

#### Function 1: Bot_CheckAndStartTurn()
```javascript
/**
 * Check if any bot needs to take their turn and start the process
 */
function Bot_CheckAndStartTurn() {
    // Only check if game is active and not in watch mode
    if( gGameData.winningplayer > 0 ) {
        ConsoleDebug( "Bot_CheckAndStartTurn: Game already won, skipping" );
        return;
    }

    // Don't interrupt if bot is already playing
    if( gBotIsPlaying ) {
        ConsoleDebug( "Bot_CheckAndStartTurn: Bot already playing, skipping" );
        return;
    }

    // Find which player's turn it is based on currentturn
    var currentTurnPlayer = null;
    for( var i = 0; i < gGamePlayerData.length; i++ ) {
        if( gGamePlayerData[i].playerturn == gGameData.currentturn ) {
            currentTurnPlayer = gGamePlayerData[i];
            break;
        }
    }

    if( !currentTurnPlayer ) {
        ConsoleDebug( "Bot_CheckAndStartTurn: Could not determine current turn player" );
        return;
    }

    // Check if current player is a bot and hasn't finished their rounds
    if( currentTurnPlayer.is_bot && currentTurnPlayer.playerround <= LAST_ROUND ) {
        ConsoleDebug( "Bot_CheckAndStartTurn: It's bot " + currentTurnPlayer.username + "'s turn (round " + currentTurnPlayer.playerround + ")" );
        Bot_StartTurn( currentTurnPlayer );
    }
}
```

#### Function 2: Bot_StartTurn()
```javascript
/**
 * Start a bot's turn
 */
function Bot_StartTurn( botPlayer ) {
    ConsoleDebug( "Bot_StartTurn: Starting turn for " + botPlayer.username );

    gBotIsPlaying = true;

    // Show bot thinking message
    var thinkingMsg = botPlayer.username + " " + (botPlayer.playertitle || '') + " is thinking...";
    divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">' + thinkingMsg + '</span>';

    // Start polling for bot turn state
    setTimeout( function() {
        Bot_PollAndExecuteStep( botPlayer.playerid );
    }, BOT_STEP_DELAY_MS );
}
```

#### Function 3: Bot_PollAndExecuteStep()
```javascript
/**
 * Poll bot status and execute next step
 */
function Bot_PollAndExecuteStep( botPlayerId ) {
    ConsoleDebug( "Bot_PollAndExecuteStep: Polling status for bot " + botPlayerId );

    // First check bot status
    FarkleAjaxCall(
        function() {
            var statusData = FarkleParseAjaxResponse( ajaxrequest.responseText );
            if( statusData && !statusData.Error ) {
                ConsoleDebug( "Bot_PollAndExecuteStep: Got status, current_step=" + statusData.current_step );

                // Execute the next step
                Bot_ExecuteNextStep( botPlayerId, statusData );
            } else {
                ConsoleError( "Bot_PollAndExecuteStep: Failed to get bot status: " + (statusData ? statusData.Error : "Unknown error") );
                gBotIsPlaying = false;
                // Refresh game state
                farkleGetUpdate();
            }
        },
        'action=getbotstatus&gameid=' + gGameData.gameid + '&botplayerid=' + botPlayerId
    );
}
```

#### Function 4: Bot_ExecuteNextStep()
```javascript
/**
 * Execute the next step in bot's turn
 */
function Bot_ExecuteNextStep( botPlayerId, currentStatus ) {
    ConsoleDebug( "Bot_ExecuteNextStep: Executing step for bot " + botPlayerId );

    FarkleAjaxCall(
        function() {
            var stepResult = FarkleParseAjaxResponse( ajaxrequest.responseText );
            if( stepResult && !stepResult.Error ) {
                ConsoleDebug( "Bot_ExecuteNextStep: Step executed, result=" + stepResult.step );

                // Process the step result
                Bot_ProcessStepResult( botPlayerId, stepResult );
            } else {
                ConsoleError( "Bot_ExecuteNextStep: Failed to execute step: " + (stepResult ? stepResult.Error : "Unknown error") );
                gBotIsPlaying = false;
                // Refresh game state
                farkleGetUpdate();
            }
        },
        'action=executebotstep&gameid=' + gGameData.gameid + '&botplayerid=' + botPlayerId
    );
}
```

#### Function 5: Bot_ProcessStepResult()
```javascript
/**
 * Process the result of a bot step and continue turn or end
 */
function Bot_ProcessStepResult( botPlayerId, stepResult ) {
    var step = stepResult.step;
    var message = stepResult.message || '';

    ConsoleDebug( "Bot_ProcessStepResult: Processing step '" + step + "'" );

    // Display bot message if present
    if( message ) {
        Bot_DisplayMessage( message );
    }

    // Handle different step types
    switch( step ) {
        case 'rolled':
            // Bot rolled dice - show the dice
            if( stepResult.dice && stepResult.dice.length > 0 ) {
                Bot_AnimateDiceRoll( stepResult.dice );
            }
            // Continue to next step after animation delay
            setTimeout( function() {
                Bot_PollAndExecuteStep( botPlayerId );
            }, BOT_STEP_DELAY_MS );
            break;

        case 'chose_keepers':
            // Bot chose which dice to keep
            var msg = "Keeping " + (stepResult.kept ? stepResult.kept.description : 'dice') +
                      " for " + (stepResult.kept ? stepResult.kept.points : 0) + " points";
            divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">' + msg + '</span>';

            // Continue to next step
            setTimeout( function() {
                Bot_PollAndExecuteStep( botPlayerId );
            }, BOT_STEP_DELAY_MS );
            break;

        case 'roll_again':
            // Bot decided to roll again
            divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">Rolling again...</span>';

            // Continue to next step
            setTimeout( function() {
                Bot_PollAndExecuteStep( botPlayerId );
            }, BOT_STEP_DELAY_MS );
            break;

        case 'banked':
            // Bot banked their score - turn is over
            var bankMsg = "Banked " + (stepResult.score || 0) + " points!";
            divTurnActionObj.innerHTML = '<span style="color: #7CFC00;">' + bankMsg + '</span>';

            // End bot turn and refresh game
            setTimeout( function() {
                gBotIsPlaying = false;
                farkleGetUpdate();
            }, SCORE_VIEW_DELAY_MS );
            break;

        case 'farkled':
            // Bot farkled - turn is over
            divTurnActionObj.innerHTML = '<span style="color: red;">Farkled!</span>';

            // End bot turn and refresh game
            setTimeout( function() {
                gBotIsPlaying = false;
                farkleGetUpdate();
            }, SCORE_VIEW_DELAY_MS );
            break;

        default:
            ConsoleError( "Bot_ProcessStepResult: Unknown step type: " + step );
            gBotIsPlaying = false;
            farkleGetUpdate();
            break;
    }
}
```

#### Function 6: Bot_AnimateDiceRoll()
```javascript
/**
 * Animate bot's dice roll
 */
function Bot_AnimateDiceRoll( diceArray ) {
    ConsoleDebug( "Bot_AnimateDiceRoll: Showing dice: " + diceArray.join(',') );

    // Show dice on the table
    for( var i = 0; i < diceArray.length && i <= MAX_DICE; i++ ) {
        if( diceArray[i] ) {
            farkleUpdateDice( i, diceArray[i], 1 );  // 1 = clickable/active
        }
    }
    // Clear remaining dice
    for( var i = diceArray.length; i <= MAX_DICE; i++ ) {
        farkleUpdateDice( i, 0, 0 );
    }
}
```

#### Function 7: Bot_DisplayMessage()
```javascript
/**
 * Display bot message in the UI
 */
function Bot_DisplayMessage( message ) {
    ConsoleDebug( "Bot_DisplayMessage: " + message );

    // Show message in turn action area
    divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">' + message + '</span>';

    // Could also add to a message history div if we create one
    // For now, just show in the main status area
}
```

**Why**: This complete system handles bot turn execution from start to finish, including:
- Detecting when it's a bot's turn
- Polling the backend for bot state
- Executing bot actions step-by-step
- Displaying animations and messages
- Handling turn completion (bank or farkle)
- Refreshing game state after bot turn

---

## Summary of Changes

| File | Lines Added | Lines Modified | Total Impact |
|------|-------------|----------------|--------------|
| farkleGameFuncs.php | 2 | 0 | Minimal |
| farkleGame.js | ~220 | 15 | Significant |
| **TOTAL** | **~222** | **15** | **~237 lines** |

### Change Categories

1. **Database Integration** (1 change)
   - Modified player data query to include bot fields

2. **State Management** (3 changes)
   - Added global variables
   - Added state cleanup
   - Added bot detection

3. **Turn Execution** (7 changes)
   - 7 new functions for complete bot turn handling
   - Integration with existing AJAX system
   - Smooth animations and delays

### No Breaking Changes
All changes are additive and backward-compatible. Existing game functionality remains unchanged.
