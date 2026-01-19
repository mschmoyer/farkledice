# Bot Player Frontend Implementation - Testing Guide

## Implementation Summary

The frontend JavaScript code for bot player interaction has been implemented in `wwwroot/js/farkleGame.js`. This enables real-time, step-by-step visualization of bot turns during interactive mode games.

### Files Modified

1. **wwwroot/farkleGameFuncs.php**
   - Added `is_bot` and `bot_algorithm` to player data query (line 651-660)
   - This ensures bot player information is sent to the frontend

2. **wwwroot/js/farkleGame.js**
   - Added bot-related constants and global variables (lines 43-55)
   - Added bot detection in `PopulatePlayerData()` (lines 363-376)
   - Added `Bot_CheckAndStartTurn()` call after player data population (line 416)
   - Implemented bot turn handling system (lines 421-640):
     - `Bot_CheckAndStartTurn()` - Detects when it's a bot's turn
     - `Bot_StartTurn()` - Initiates bot turn sequence
     - `Bot_PollAndExecuteStep()` - Polls backend for bot status
     - `Bot_ExecuteNextStep()` - Executes the next bot action
     - `Bot_ProcessStepResult()` - Handles step results and continues/ends turn
     - `Bot_AnimateDiceRoll()` - Displays bot's dice rolls
     - `Bot_DisplayMessage()` - Shows bot messages in UI
   - Added bot state cleanup in `FarkleResetGame()` (lines 79-85)

### How It Works

#### 1. Bot Detection
When game data is loaded, `PopulatePlayerData()` scans all players and builds a list of bot player IDs based on the `is_bot` flag from the database.

#### 2. Turn Detection
After player data is populated, `Bot_CheckAndStartTurn()` checks:
- Is the game still active? (not won)
- Is a bot already playing? (prevents overlapping turns)
- Whose turn is it? (based on `currentturn` in game data)
- Is the current player a bot who hasn't finished all rounds?

#### 3. Bot Turn Execution Flow
```
1. Bot_StartTurn()
   - Sets gBotIsPlaying = true
   - Displays "Bot is thinking..." message
   - Waits BOT_STEP_DELAY_MS (800ms)
   ↓
2. Bot_PollAndExecuteStep()
   - Calls backend: action=getbotstatus
   - Gets current state (rolling, choosing_keepers, etc.)
   ↓
3. Bot_ExecuteNextStep()
   - Calls backend: action=executebotstep
   - Backend processes one step and returns result
   ↓
4. Bot_ProcessStepResult()
   - Handles different step types:
     a. 'rolled' - Shows dice, continues after delay
     b. 'chose_keepers' - Shows what was kept, continues
     c. 'roll_again' - Shows message, continues
     d. 'banked' - Shows final score, ends turn, refreshes game
     e. 'farkled' - Shows farkle message, ends turn, refreshes game
   - If continuing: setTimeout() → Bot_PollAndExecuteStep()
   - If done: Sets gBotIsPlaying = false, calls farkleGetUpdate()
```

#### 4. Animation Timing
- `BOT_STEP_DELAY_MS = 800ms` between each step for visual clarity
- `SCORE_VIEW_DELAY_MS = 1500ms` to display final score before refreshing
- Each step is visible to the user before the next one executes

#### 5. Bot Messages
Bot personality messages from the backend are displayed in the main turn action area (`divTurnActionObj`) with colored text:
- Cyan (#96D3F2) for general messages
- Green (#7CFC00) for banking
- Red for farkle

### Backend Endpoints Used

1. **GET Bot Status**
   - Action: `getbotstatus`
   - Params: `gameid`, `botplayerid`
   - Returns: Current state (stateid, current_step, dice_kept, turn_score, etc.)

2. **Execute Bot Step**
   - Action: `executebotstep`
   - Params: `gameid`, `botplayerid`
   - Returns: Step result (step type, dice, message, etc.)

### Key Features

✅ **Non-blocking**: Bot turns don't prevent page interaction
✅ **Step-by-step visualization**: Each decision is shown sequentially
✅ **Bot messages**: Personality-driven commentary appears in real-time
✅ **Smooth animations**: Delays between steps for visual clarity
✅ **Error handling**: Gracefully falls back to game refresh on error
✅ **State management**: Prevents multiple bots from executing simultaneously

## Testing Instructions

### Test 1: Create and Play Bot Game

1. **Login to the game**
   ```
   URL: http://localhost:8080
   User: testuser
   Pass: test123
   ```

2. **Start a bot game**
   - Click "Play a Bot" button (if available in UI)
   - OR manually create via database:
   ```sql
   -- Create a game with bot
   INSERT INTO farkle_games (gamemode, gamewith, maxturns, mintostart, pointstowin, currentturn, currentround, gamestart, gameexpire, bot_play_mode)
   VALUES (2, 3, 2, 1000, 10000, 1, 1, NOW(), NOW() + INTERVAL '7 days', 'interactive');

   -- Get the gameid
   SELECT currval('farkle_games_gameid_seq');

   -- Add testuser (playerid=1) as player 1
   INSERT INTO farkle_games_players (gameid, playerid, playerturn, playerround, lastplayed)
   VALUES (<gameid>, 1, 1, 1, NOW());

   -- Add Byte bot (playerid=21) as player 2
   INSERT INTO farkle_games_players (gameid, playerid, playerturn, playerround, lastplayed)
   VALUES (<gameid>, 21, 2, 1, NOW());
   ```

3. **Navigate to the game**
   - The game should load normally
   - You should see "Byte the Rookie Bot" in the player list

4. **Play your turn**
   - Roll dice and complete your turn (bank or farkle)
   - Game should advance to round 2

5. **Watch bot's turn**
   - After your turn, bot should automatically start
   - You should see:
     - "Byte the Rookie Bot is thinking..." message
     - Dice rolling animation
     - Messages like "Keeping dice for X points"
     - "Rolling again..." or "Banked X points!"
   - Each step should have ~800ms delay for visibility

### Test 2: Verify Bot Detection

Open browser console (F12) and check for debug messages:
```
PopulatePlayerData: Found bot player: Byte the Rookie Bot (id=21)
Bot_CheckAndStartTurn: It's bot Byte the Rookie Bot's turn (round 2)
Bot_StartTurn: Starting turn for Byte the Rookie Bot
Bot_PollAndExecuteStep: Polling status for bot 21
Bot_ProcessStepResult: Processing step 'rolled'
...
```

### Test 3: Multiple Rounds

1. Continue playing against the bot for multiple rounds
2. Verify bot plays each of its turns automatically
3. Check that bot messages vary (different personality messages)
4. Confirm game ends properly when all rounds are complete

### Test 4: Error Handling

1. **Simulate backend error**
   - Stop the bot turn state (delete from farkle_bot_game_state during bot turn)
   - Bot should detect error and refresh game state gracefully

2. **Check state reset**
   - Navigate away from game during bot turn
   - Return to game
   - Verify `gBotIsPlaying` is properly reset

### Expected Console Output

```
PopulatePlayerData: populating. Number of players: 2
PopulatePlayerData: Found bot player: Byte (id=21)
Bot_CheckAndStartTurn: It's bot Byte's turn (round 2)
Bot_StartTurn: Starting turn for Byte
Bot_PollAndExecuteStep: Polling status for bot 21
Bot_PollAndExecuteStep: Got status, current_step=rolling
Bot_ExecuteNextStep: Executing step for bot 21
Bot_ExecuteNextStep: Step executed, result=rolled
Bot_ProcessStepResult: Processing step 'rolled'
Bot_AnimateDiceRoll: Showing dice: 3,4,5,1,2,6
Bot_PollAndExecuteStep: Polling status for bot 21
Bot_PollAndExecuteStep: Got status, current_step=choosing_keepers
Bot_ExecuteNextStep: Executing step for bot 21
Bot_ProcessStepResult: Processing step 'chose_keepers'
Bot_PollAndExecuteStep: Polling status for bot 21
Bot_PollAndExecuteStep: Got status, current_step=deciding_roll
Bot_ExecuteNextStep: Executing step for bot 21
Bot_ProcessStepResult: Processing step 'banked'
[Game refreshes after 1500ms]
```

## Known Limitations

1. **No chat area yet**: Bot messages appear in the main status area only. A dedicated chat/message history area could be added later.

2. **Single bot at a time**: Currently only handles one bot playing at a time. Multiple bots in same game would work sequentially.

3. **No visual keeper highlighting**: Dice that the bot keeps are shown but not visually highlighted. Could add CSS classes to show kept dice differently.

4. **Desktop-focused**: Mobile/tablet testing needed to ensure proper display on smaller screens.

## Future Enhancements

1. **Bot chat history panel**: Add a scrollable message area showing last 5-10 bot messages
2. **Keeper visualization**: Highlight which specific dice the bot kept
3. **Sound effects**: Add audio cues for bot actions (roll, bank, farkle)
4. **Animation improvements**: Smoother dice roll animations, fade effects
5. **Speed control**: Let user adjust bot turn speed (slow/normal/fast)
6. **Skip option**: Allow user to skip bot animation and see final result immediately

## Troubleshooting

### Bot turn doesn't start
- Check browser console for errors
- Verify `is_bot` flag is true in database for bot players
- Ensure `bot_play_mode = 'interactive'` in farkle_games table
- Check that bot has a turn state in farkle_bot_game_state table

### Bot turn freezes
- Check backend logs for PHP errors
- Verify bot turn endpoints are responding (check Network tab in dev tools)
- Confirm bot state machine hasn't entered invalid state

### Messages not showing
- Verify farkleBotMessages.php has messages for the bot's algorithm
- Check that backend is returning 'message' field in step results
- Look for JavaScript errors in console

### Multiple bots playing simultaneously
- Check `gBotIsPlaying` flag - it should block concurrent bot turns
- Verify `Bot_CheckAndStartTurn()` is checking this flag properly
- Ensure game state is refreshing correctly between turns

## Code Quality

- ✅ Follows existing code patterns in farkleGame.js
- ✅ Uses existing AJAX utilities (FarkleAjaxCall, FarkleParseAjaxResponse)
- ✅ Proper error handling with fallback to game refresh
- ✅ Debug logging at appropriate levels (ConsoleDebug, ConsoleError)
- ✅ Clear function documentation
- ✅ Proper state management (global flags, cleanup in reset)

## Integration Points

The bot frontend integrates seamlessly with:
- Existing game update cycle (`farkleGetUpdate()`)
- Player data loading (`PopulatePlayerData()`)
- Game state management (`FarkleGameUpdateState()`)
- Dice display system (`farkleUpdateDice()`)
- Turn action display (`divTurnActionObj`)

No breaking changes to existing functionality.
