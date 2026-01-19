# Bot Player Frontend - Flow Diagram

## Complete Turn Execution Flow

```
┌──────────────────────────────────────────────────────────────────────────┐
│                         GAME UPDATE CYCLE                                 │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  farkleGetUpdate()                                                        │
│  - AJAX call to backend: action=farklegetupdate                          │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  GameUpdateEx(gameData)                                                   │
│  - Parse game data                                                        │
│  - Extract player data                                                    │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  PopulatePlayerData(playerData)                                           │
│  - Loop through all players                                               │
│  - Check if player.is_bot == true                                         │
│  - Add bot playerid to gBotPlayerIds array                                │
│  - Track which player's turn it is (playerturn == currentturn)            │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Bot_CheckAndStartTurn()                                                  │
│  - Is game won? → NO                                                      │
│  - Is bot already playing? → NO                                           │
│  - Find current turn player                                               │
│  - Is current player a bot? → YES                                         │
│  - Has bot finished all rounds? → NO                                      │
│  - ✓ Start bot turn!                                                      │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Bot_StartTurn(botPlayer)                                                 │
│  - Set gBotIsPlaying = true                                               │
│  - Display: "Bot is thinking..."                                          │
│  - setTimeout(800ms) → Bot_PollAndExecuteStep()                           │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                         BOT TURN LOOP BEGINS                              │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Bot_PollAndExecuteStep(botPlayerId)                                      │
│  - AJAX: action=getbotstatus                                              │
│  - Returns: {current_step, dice_kept, turn_score, last_roll, ...}        │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Bot_ExecuteNextStep(botPlayerId, currentStatus)                          │
│  - AJAX: action=executebotstep                                            │
│  - Backend executes ONE step of bot's turn                                │
│  - Returns: {step, dice, kept, message, state, ...}                       │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Bot_ProcessStepResult(botPlayerId, stepResult)                           │
│  - Check stepResult.step                                                  │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
            ┌───────────────────────┼───────────────────────┐
            │                       │                       │
            ▼                       ▼                       ▼
┌─────────────────────┐ ┌─────────────────────┐ ┌─────────────────────┐
│   STEP: 'rolled'    │ │ STEP: 'chose_keepers'│ │ STEP: 'roll_again'  │
├─────────────────────┤ ├─────────────────────┤ ├─────────────────────┤
│ • Show dice array   │ │ • Display kept dice │ │ • Show "Rolling..."  │
│ • Animate roll      │ │ • Show points earned│ │ • Display message    │
│ • Display on board  │ │ • Update UI         │ │                     │
│                     │ │                     │ │                     │
│ setTimeout(800ms)   │ │ setTimeout(800ms)   │ │ setTimeout(800ms)   │
│        │            │ │        │            │ │        │            │
│        └────────────┴─┴────────┴────────────┴─┴────────┘            │
│                                  │                                   │
│                                  │                                   │
│                         CONTINUE LOOP                                │
│                  (back to Bot_PollAndExecuteStep)                    │
└──────────────────────────────────────────────────────────────────────┘
                                    │
                                    │
            ┌───────────────────────┼───────────────────────┐
            │                                               │
            ▼                                               ▼
┌─────────────────────────────────────┐     ┌─────────────────────────────┐
│      STEP: 'banked'                 │     │    STEP: 'farkled'          │
├─────────────────────────────────────┤     ├─────────────────────────────┤
│ • Display: "Banked X points!"       │     │ • Display: "Farkled!"       │
│ • Color: GREEN (#7CFC00)            │     │ • Color: RED                │
│ • Show final score                  │     │ • Show zero points          │
│                                     │     │                             │
│ setTimeout(1500ms)                  │     │ setTimeout(1500ms)          │
│        │                            │     │        │                    │
│        └────────────────────────────┴─────┴────────┘                    │
│                                  │                                      │
│                            BOT TURN ENDS                                │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Turn Cleanup                                                             │
│  - Set gBotIsPlaying = false                                              │
│  - Call farkleGetUpdate()                                                 │
│  - Refresh game state                                                     │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Game continues...                                                        │
│  - If it's human's turn → wait for input                                  │
│  - If it's another bot's turn → Bot_CheckAndStartTurn() again             │
│  - If game over → show results                                            │
└──────────────────────────────────────────────────────────────────────────┘
```

## State Machine Diagram

```
                        ┌─────────────────┐
                        │  GAME LOADING   │
                        └────────┬────────┘
                                 │
                                 ▼
                     ┌───────────────────────┐
                     │  Is current player    │
                     │  a bot?               │
                     └───────┬───────────────┘
                             │
                    YES ◄────┴────► NO
                     │               │
                     ▼               ▼
            ┌─────────────────┐  ┌──────────────────┐
            │ BOT TURN ACTIVE │  │ HUMAN TURN ACTIVE│
            │ gBotIsPlaying=T │  │ Wait for input   │
            └────────┬────────┘  └──────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
        ▼            ▼            ▼
   ┌────────┐  ┌─────────┐  ┌──────────┐
   │ROLLING │  │CHOOSING │  │DECIDING  │
   │        │  │KEEPERS  │  │TO ROLL   │
   └───┬────┘  └────┬────┘  └────┬─────┘
       │            │            │
       └────────────┼────────────┘
                    │
          ┌─────────┴─────────┐
          │                   │
          ▼                   ▼
    ┌──────────┐        ┌──────────┐
    │ BANKING  │        │ FARKLED  │
    └─────┬────┘        └─────┬────┘
          │                   │
          └─────────┬─────────┘
                    │
                    ▼
           ┌────────────────┐
           │  TURN COMPLETE │
           │ gBotIsPlaying=F│
           └────────┬───────┘
                    │
                    ▼
              [GAME UPDATE]
```

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          BACKEND (PHP)                                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  farkle_fetch.php                                                        │
│  ├─ action=farklegetupdate                                               │
│  │  └─ FarkleSendUpdate()                                                │
│  │     └─ Query: SELECT ... is_bot, bot_algorithm ...                    │
│  │        └─ Returns: [gameData, playerData[], ...]                      │
│  │                                                                        │
│  ├─ action=getbotstatus                                                  │
│  │  └─ Bot_GetTurnState()                                                │
│  │     └─ SELECT * FROM farkle_bot_game_state                            │
│  │        └─ Returns: {current_step, dice_kept, turn_score, ...}         │
│  │                                                                        │
│  └─ action=executebotstep                                                │
│     └─ Bot_ExecuteStep()                                                 │
│        └─ Bot_Step_Rolling() / Bot_Step_ChoosingKeepers() / etc.         │
│           └─ Bot_MakeDecision() (from farkleBotAI.php)                   │
│              └─ Returns: {step, dice, message, state, ...}               │
│                                                                          │
└──────────────────────────────┬──────────────────────────────────────────┘
                               │
                      JSON over HTTP POST
                               │
┌──────────────────────────────▼──────────────────────────────────────────┐
│                       FRONTEND (JavaScript)                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  farkleGame.js                                                           │
│  ├─ GameUpdateEx()                                                       │
│  │  └─ playerData.is_bot → gBotPlayerIds[]                              │
│  │                                                                        │
│  ├─ Bot_CheckAndStartTurn()                                              │
│  │  └─ if (currentPlayer.is_bot) → Bot_StartTurn()                       │
│  │                                                                        │
│  ├─ Bot_PollAndExecuteStep()                                             │
│  │  └─ AJAX: getbotstatus                                                │
│  │     └─ statusData.current_step                                        │
│  │                                                                        │
│  ├─ Bot_ExecuteNextStep()                                                │
│  │  └─ AJAX: executebotstep                                              │
│  │     └─ stepResult.step, stepResult.dice, stepResult.message           │
│  │                                                                        │
│  └─ Bot_ProcessStepResult()                                              │
│     └─ switch(stepResult.step)                                           │
│        ├─ 'rolled' → Bot_AnimateDiceRoll()                               │
│        ├─ 'chose_keepers' → Display kept dice                            │
│        ├─ 'roll_again' → Display message                                 │
│        ├─ 'banked' → Display score, end turn                             │
│        └─ 'farkled' → Display message, end turn                          │
│                                                                          │
└──────────────────────────────┬──────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                            UI (HTML/DOM)                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  divTurnActionObj.innerHTML                                              │
│  └─ "Byte the Rookie Bot is thinking..."                                │
│  └─ "Keeping dice for 250 points"                                       │
│  └─ "Rolling again..."                                                  │
│  └─ "Banked 450 points!" (green)                                        │
│  └─ "Farkled!" (red)                                                    │
│                                                                          │
│  farkleUpdateDice(index, value, clickable)                              │
│  └─ Shows dice on game board                                            │
│     [1] [5] [3] [2] [6] [4]                                             │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

## Timing Diagram

```
Time →
0ms         800ms       1600ms      2400ms      3200ms      4700ms

Human      [Wait...]   [Wait...]   [Wait...]   [Wait...]   [Resume]
  │                                                            │
  │ Completes                                                  │
  │ turn                                                       │
  └──────────────────────────────────────────────────────────┘
          │
          ▼
Bot      [Start]     [Poll+Exec]  [Poll+Exec]  [Poll+Exec]  [End]
          │             │            │            │            │
          │             │            │            │            │
          ├─Think──────►├─Rolled────►├─Chose─────►├─Banked───►│
          │  800ms      │  800ms     │  800ms     │  1500ms    │
          │             │            │            │            │
UI       "Thinking"   "Dice: 4,5"  "Keep 5s"   "Rolling"    "Banked 300!"
Display                  [Show]      [Show]      [Show]      [GREEN TEXT]
                                                              ▼
                                                         [Game Update]
```

## Error Handling Flow

```
┌────────────────────────────────────────────────────────────────┐
│  Any AJAX Call                                                  │
└──────────────────────┬─────────────────────────────────────────┘
                       │
                ┌──────┴───────┐
                │              │
            SUCCESS          ERROR
                │              │
                ▼              ▼
         ┌────────────┐  ┌──────────────────────────┐
         │ Parse JSON │  │ Error Response           │
         └─────┬──────┘  │ - Network timeout        │
               │         │ - Server error           │
               │         │ - Invalid JSON           │
               │         │ - Backend exception      │
               │         └───────────┬──────────────┘
               │                     │
        ┌──────┴──────┐              │
        │             │              │
    Has Data?      Has Error?        │
        │             │              │
       YES           YES             │
        │             │              │
        ▼             ▼              ▼
  ┌─────────┐   ┌────────────────────────────┐
  │Continue │   │ ERROR RECOVERY              │
  │Bot Turn │   │ 1. ConsoleError(message)    │
  └─────────┘   │ 2. Set gBotIsPlaying = false│
                │ 3. Call farkleGetUpdate()   │
                │ 4. Refresh game state       │
                └────────────────────────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │ User sees normal game│
                    │ Can continue playing │
                    └──────────────────────┘
```

## Key Timing Parameters

| Parameter | Value | Purpose |
|-----------|-------|---------|
| BOT_STEP_DELAY_MS | 800ms | Delay between bot steps for animation |
| SCORE_VIEW_DELAY_MS | 1500ms | Display final score before refresh |
| AJAX Timeout | Default | Backend request timeout |
| Game Update Interval | 10s | Normal game polling rate |

## State Flags

| Flag | Type | Purpose |
|------|------|---------|
| gBotIsPlaying | boolean | Prevents concurrent bot turns |
| gBotPlayerIds | array | List of bot player IDs in game |
| gBotTurnTimer | timeout | Reference to active timeout |
| gGameAjaxStatus | int | Prevents overlapping AJAX calls |

This ensures smooth, non-blocking bot turn execution with proper error handling and state management.
