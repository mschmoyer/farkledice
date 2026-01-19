# Challenge Mode - Implementation Plan

## Overview

This document outlines the technical implementation for Challenge Mode, a roguelike dice-enhancement system for Farkle Ten. Players face 20 sequential AI bots, earning money to purchase enhanced dice from a shop after each victory.

---

## Database Schema Changes

### New Tables

#### 1. `farkle_challenge_runs`
Tracks active and completed challenge mode runs.

```sql
CREATE TABLE farkle_challenge_runs (
    run_id SERIAL PRIMARY KEY,
    player_id INTEGER NOT NULL REFERENCES farkle_players(playerid),
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'completed', 'abandoned'
    current_bot_number INTEGER NOT NULL DEFAULT 1, -- 1-20
    current_money INTEGER NOT NULL DEFAULT 0,
    furthest_bot_reached INTEGER NOT NULL DEFAULT 0,
    total_dice_saved INTEGER NOT NULL DEFAULT 0,
    total_games_played INTEGER NOT NULL DEFAULT 0,
    created_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_played TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_date TIMESTAMP,

    -- Ensure only one active run per player
    CONSTRAINT one_active_run_per_player UNIQUE (player_id, status)
        WHERE status = 'active'
);

CREATE INDEX idx_challenge_runs_player ON farkle_challenge_runs(player_id);
CREATE INDEX idx_challenge_runs_status ON farkle_challenge_runs(status);
```

#### 2. `farkle_challenge_dice_inventory`
Tracks the 6 dice in player's current challenge run.

```sql
CREATE TABLE farkle_challenge_dice_inventory (
    inventory_id SERIAL PRIMARY KEY,
    run_id INTEGER NOT NULL REFERENCES farkle_challenge_runs(run_id) ON DELETE CASCADE,
    dice_slot INTEGER NOT NULL CHECK (dice_slot >= 1 AND dice_slot <= 6),
    dice_type_id INTEGER NOT NULL REFERENCES farkle_challenge_dice_types(dice_type_id),

    CONSTRAINT unique_slot_per_run UNIQUE (run_id, dice_slot)
);

CREATE INDEX idx_challenge_inventory_run ON farkle_challenge_dice_inventory(run_id);
```

#### 3. `farkle_challenge_dice_types`
Master list of all special dice types.

```sql
CREATE TABLE farkle_challenge_dice_types (
    dice_type_id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    price INTEGER NOT NULL,
    tier VARCHAR(20) NOT NULL, -- 'simple', 'better', 'amazing'
    effect_type VARCHAR(50) NOT NULL, -- 'multiplier', 'reroll', 'wild', 'protection', etc.
    effect_value TEXT, -- JSON for complex effects: {"multiplier": 2, "applies_to": [1,5]}
    rarity VARCHAR(20) DEFAULT 'common', -- 'common', 'rare', 'legendary' (future use)
    enabled BOOLEAN DEFAULT true,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_dice_types_tier ON farkle_challenge_dice_types(tier);
CREATE INDEX idx_dice_types_enabled ON farkle_challenge_dice_types(enabled);
```

#### 4. `farkle_challenge_bot_lineup`
The fixed lineup of 20 bots for challenge mode.

```sql
CREATE TABLE farkle_challenge_bot_lineup (
    bot_number INTEGER PRIMARY KEY CHECK (bot_number >= 1 AND bot_number <= 20),
    personality_id INTEGER NOT NULL REFERENCES farkle_bot_personalities(personality_id),
    display_name VARCHAR(100) NOT NULL,
    point_target INTEGER DEFAULT 3000,
    special_rules TEXT, -- JSON: {"farkle_penalty": 500, "bonus_rolls": true, etc.}
    bot_dice_types TEXT, -- JSON array of dice_type_ids the bot has
    description TEXT -- Brief description of this bot's challenge
);
```

#### 5. `farkle_challenge_stats`
Player statistics specific to challenge mode.

```sql
CREATE TABLE farkle_challenge_stats (
    player_id INTEGER PRIMARY KEY REFERENCES farkle_players(playerid),
    total_runs INTEGER NOT NULL DEFAULT 0,
    completed_runs INTEGER NOT NULL DEFAULT 0,
    furthest_bot_reached INTEGER NOT NULL DEFAULT 0,
    total_dice_purchased INTEGER NOT NULL DEFAULT 0,
    total_money_earned INTEGER NOT NULL DEFAULT 0,
    total_money_spent INTEGER NOT NULL DEFAULT 0,
    favorite_dice_type_id INTEGER REFERENCES farkle_challenge_dice_types(dice_type_id),
    fastest_completion_time INTEGER, -- seconds
    last_run_date TIMESTAMP,

    -- Achievement tracking
    reached_bot_5 BOOLEAN DEFAULT false,
    reached_bot_10 BOOLEAN DEFAULT false,
    reached_bot_15 BOOLEAN DEFAULT false,
    reached_bot_20 BOOLEAN DEFAULT false,

    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_challenge_stats_furthest ON farkle_challenge_stats(furthest_bot_reached DESC);
```

### Schema Modifications to Existing Tables

#### `farkle_games` - Add challenge mode fields
```sql
ALTER TABLE farkle_games
ADD COLUMN is_challenge_game BOOLEAN DEFAULT false,
ADD COLUMN challenge_run_id INTEGER REFERENCES farkle_challenge_runs(run_id),
ADD COLUMN challenge_bot_number INTEGER;

CREATE INDEX idx_games_challenge ON farkle_games(is_challenge_game, challenge_run_id);
```

#### `farkle_games_players` - Track dice used in challenge games
```sql
ALTER TABLE farkle_games_players
ADD COLUMN dice_inventory TEXT; -- JSON array of dice_type_ids for this player

-- Example: [1, 1, 1, 7, 12, 15] where 1 = standard die
```

---

## Backend PHP Implementation

### New Files

#### 1. `wwwroot/farkleChallenge.php`
Main challenge mode page handler.

**Responsibilities:**
- Display challenge lobby (run selection, leaderboard, stats)
- Show current run status (money, dice inventory, current bot)
- Handle navigation to shop and game

**Key Functions:**
- `Challenge_GetActiveRun($playerId)` - Get current run or null
- `Challenge_StartNewRun($playerId)` - Create new run with 6 standard dice
- `Challenge_GetBotLineup()` - Fetch all 20 bots
- `Challenge_GetPlayerStats($playerId)` - Get challenge-specific stats
- `Challenge_GetLeaderboard($limit)` - Top players by furthest bot

#### 2. `wwwroot/farkleChallengeShop.php`
Shop interface and purchase handling.

**Responsibilities:**
- Display 3 random unique dice options
- Handle dice purchases
- Manage dice replacement selection
- Update money and inventory

**Key Functions:**
- `Shop_GetAvailableDice($runId, $count = 3)` - Get random unique dice
- `Shop_PurchaseDice($runId, $diceTypeId, $slotNumber)` - Buy and equip
- `Shop_GetPlayerMoney($runId)` - Current money balance
- `Shop_ValidatePurchase($runId, $diceTypeId)` - Check funds & uniqueness

**AJAX Endpoints:**
- `/farkle_challenge_shop.php?action=get_shop` - Get shop offerings
- `/farkle_challenge_shop.php?action=purchase` - Buy dice
- `/farkle_challenge_shop.php?action=refresh` - Skip shop (future: reroll)

#### 3. `wwwroot/farkleChallengeGame.php`
Game logic specific to challenge mode.

**Responsibilities:**
- Create challenge games with special dice rules
- Track money earned per die saved
- Handle victory/defeat outcomes
- Progress to next bot or end run

**Key Functions:**
- `ChallengeGame_Create($runId, $botNumber)` - Start game vs bot
- `ChallengeGame_OnDiceSaved($gameId, $playerId, $numDice)` - Award money
- `ChallengeGame_OnVictory($runId)` - Increment bot number, redirect to shop
- `ChallengeGame_OnDefeat($runId)` - End run, update stats
- `ChallengeGame_ApplyDiceEffects($diceInventory, $rollResult)` - Modify roll based on special dice

#### 4. `wwwroot/farkleChallengeScoring.php`
Dice effect calculation engine.

**Responsibilities:**
- Apply special dice effects to rolls and scoring
- Handle complex dice interactions and combos
- Calculate effective roll results

**Key Functions:**
- `DiceEffect_Apply($diceInventory, $rollValues)` - Return modified roll
- `DiceEffect_CalculateMultiplier($diceInventory, $scoringDice)` - Point multipliers
- `DiceEffect_CheckReroll($diceInventory)` - Available rerolls
- `DiceEffect_CheckProtection($diceInventory)` - Farkle protection
- `DiceEffect_GetDescription($diceTypeId)` - Human-readable effect

**Effect Processing Order:**
1. Pre-roll effects (rerolls, guarantees)
2. Roll result modification (wild dice, value changes)
3. Scoring calculation (multipliers, bonuses)
4. Post-roll effects (protection, insurance)

#### 5. `includes/challengeUtil.php`
Shared utility functions for challenge mode.

**Key Functions:**
- `ChallengeUtil_GetStandardDiceType()` - Get dice_type_id for standard die
- `ChallengeUtil_InitializeInventory($runId)` - Create 6 standard dice
- `ChallengeUtil_ValidateInventory($runId)` - Ensure 6 dice exist
- `ChallengeUtil_FormatMoney($amount)` - Display as "$X" with monospace font
- `ChallengeUtil_GetDiceImage($diceTypeId)` - Get icon/image for dice type

### Modified Files

#### `wwwroot/farkleGameFuncs.php`
**Changes:**
- Modify `createNewGame()` to accept `$isChallengeGame` parameter
- Add challenge-specific game initialization
- Track `is_challenge_game` flag

#### `wwwroot/farkleDiceScoring.php`
**Changes:**
- Check if game is challenge mode
- If yes, call `DiceEffect_Apply()` before scoring
- Apply multipliers from special dice

#### `wwwroot/farkle_fetch.php`
**Changes:**
- Return dice inventory with game state
- Include current money balance for challenge games
- Return dice effect descriptions for UI tooltips

#### `wwwroot/farklePage.js`
**Changes:**
- Add navigation to challenge mode from lobby
- Handle challenge-specific UI updates

---

## Frontend JavaScript Implementation

### New Files

#### 1. `js/farkleChallenge.js`
Main challenge mode UI controller.

**Responsibilities:**
- Challenge lobby interface
- Run management (start, resume, abandon)
- Bot lineup display
- Stats and leaderboard display

**Key Functions:**
- `Challenge_ShowLobby()` - Render challenge lobby
- `Challenge_StartRun()` - Create new run via AJAX
- `Challenge_ResumeRun(runId)` - Load active run
- `Challenge_ShowBotLineup()` - Display all 20 bots
- `Challenge_ShowStats()` - Player's challenge stats

#### 2. `js/farkleChallengeShop.js`
Shop interface and dice selection.

**Responsibilities:**
- Display shop with 3 dice offerings
- Handle purchase flow
- Dice replacement selection modal
- Money display updates

**Key Functions:**
- `Shop_Load(runId)` - Fetch and display shop
- `Shop_ShowDiceDetails(diceTypeId)` - Tooltip/modal with full info
- `Shop_InitiatePurchase(diceTypeId)` - Show slot selection modal
- `Shop_ConfirmPurchase(diceTypeId, slotNumber)` - Complete purchase
- `Shop_Skip()` - Exit shop without buying
- `Shop_UpdateMoney(newBalance)` - Update money display

**UI Components:**
- Shop card grid (3 cards, similar to bot selection)
- Dice detail modal (name, description, effect, price)
- Slot selection modal (show 6 current dice, click to replace)
- Current money display (💰 with monospace font)

#### 3. `js/farkleChallengeGame.js`
Challenge-specific game UI enhancements.

**Responsibilities:**
- Display special dice in game
- Show money earned during play
- Track dice saved for money calculation
- Show dice effect indicators

**Key Functions:**
- `ChallengeGame_Initialize(runId, botNumber)` - Setup challenge game
- `ChallengeGame_ShowDiceInventory()` - Display player's 6 special dice
- `ChallengeGame_OnDiceSaved(numDice)` - Animate +$X popup
- `ChallengeGame_ShowEffects()` - Highlight active dice effects
- `ChallengeGame_OnVictory()` - Victory animation, redirect to shop
- `ChallengeGame_OnDefeat()` - Defeat screen, show stats

### Modified Files

#### `js/farkleGame.js`
**Changes:**
- Add `gChallengeRunId` global variable
- Add `gChallengeDiceInventory` array
- Modify `FarkleGameDisplayDice()` to show special dice icons
- Call `ChallengeGame_OnDiceSaved()` when dice are kept
- Check for challenge mode in game state

#### `js/farklePage.js`
**Changes:**
- Add "Challenge Mode" button to main lobby
- Add navigation handler `Page_ShowChallenge()`
- Modify page routing to support challenge screens

#### `js/farkleLobby.js`
**Changes:**
- Add challenge mode button next to "Play vs Bot"
- Style to match existing lobby buttons

---

## Template Files (Smarty)

### New Templates

#### 1. `templates/farkle_div_challenge_lobby.tpl`
Challenge mode main lobby.

**Sections:**
- Header with title and description
- Current run status (if active)
  - Current bot number
  - Money balance (💰 $X)
  - Dice inventory preview
  - "Continue Run" button
- New run section
  - "Start New Challenge" button (if no active run)
  - Warning if abandoning active run
- Bot lineup preview (show bot 1-5 with "Show All" button)
- Player stats summary
  - Total runs
  - Furthest bot reached
  - Completion rate
- Leaderboard top 10

#### 2. `templates/farkle_div_challenge_shop.tpl`
Shop interface after winning a game.

**Layout:**
```
+----------------------------------------------------------+
|              VICTORY! You defeated Bot #X                |
|                                                          |
|  Current Money: 💰 $XX                                   |
|                                                          |
|  +----------------+  +----------------+  +----------------+
|  | Dice Name      |  | Dice Name      |  | Dice Name      |
|  | [Dice Image]   |  | [Dice Image]   |  | [Dice Image]   |
|  | Effect desc    |  | Effect desc    |  | Effect desc    |
|  |                |  |                |  |                |
|  |   $X  [BUY]    |  |   $X  [BUY]    |  |   $X  [BUY]    |
|  +----------------+  +----------------+  +----------------+
|                                                          |
|  Your Current Dice:                                      |
|  [D1] [D2] [D3] [D4] [D5] [D6]                          |
|                                                          |
|             [Skip] [Continue to Next Bot]                |
+----------------------------------------------------------+
```

**Features:**
- Highlight affordable dice (green border if money >= price)
- Disable expensive dice (grey out if can't afford)
- Tooltips on hover for full dice descriptions
- Current inventory display at bottom

#### 3. `templates/farkle_div_challenge_game.tpl`
Challenge-specific game UI additions.

**Additions to existing game template:**
- Challenge header bar:
  - "Challenge Mode - Bot #X/20"
  - Current money: 💰 $X
  - Mini dice inventory (icons only)
- Money earned notifications (+$1 popups when dice saved)
- Special dice effect indicators (icons next to dice)
- Victory/defeat modals with challenge-specific messaging

#### 4. `templates/farkle_div_challenge_bot_lineup.tpl`
Full bot lineup display (modal or dedicated page).

**Layout:**
```
Bot #1 - Byte          [Easy]     3000 points     [Your Record: WIN]
Bot #2 - Chip          [Easy]     3000 points     [Your Record: --]
Bot #3 - Beep          [Easy]     3000 points     [Your Record: --]
...
Bot #18 - Sigma        [Hard]     3500 points     [Your Record: --]
Bot #19 - Prime        [Hard]     3500 points     [Your Record: --]
Bot #20 - [BOSS NAME]  [BOSS]     4000 points     [Your Record: --]
```

**Features:**
- Color-coded difficulty (green=easy, yellow=medium, red=hard, purple=boss)
- Show special rules on hover/expand
- Show player's furthest reached (lock icon on unreached bots)

#### 5. `templates/farkle_div_challenge_stats.tpl`
Detailed player stats page.

**Sections:**
- Run history (last 10 runs)
- Personal bests (furthest bot, fastest completion)
- Dice statistics (most purchased, most effective)
- Achievement progress (bot milestones)

### Modified Templates

#### `templates/farkle_div_lobby.tpl`
**Changes:**
- Add "Challenge Mode" button
- Position near "Play a Bot" button
- Use orange/gold color scheme to stand out

#### `templates/farkle_div_game.tpl`
**Changes:**
- Include challenge header if `$isChallengeGame`
- Add money display area
- Add dice inventory preview

---

## CSS Styling

### New Classes

```css
/* Challenge Lobby */
.challenge-lobby {
  background: rgba(0, 100, 0, 0.3);
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #000;
}

.challenge-header {
  color: #FFD700;
  font-size: 24px;
  text-shadow: 2px 2px 4px #000;
  font-weight: bold;
}

.challenge-run-status {
  background: rgba(255, 215, 0, 0.2);
  border: 2px solid #FFD700;
  border-radius: 6px;
  padding: 15px;
  margin: 10px 0;
}

/* Shop */
.shop-container {
  display: flex;
  justify-content: space-around;
  gap: 15px;
  margin: 20px 0;
}

.shop-dice-card {
  background: rgba(255, 255, 255, 0.9);
  border: 2px solid #000;
  border-radius: 8px;
  padding: 15px;
  width: 200px;
  text-align: center;
  transition: transform 0.2s, box-shadow 0.2s;
}

.shop-dice-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
}

.shop-dice-card.affordable {
  border-color: #1d8711;
  box-shadow: 0 0 10px rgba(29, 135, 17, 0.5);
}

.shop-dice-card.expensive {
  opacity: 0.6;
  border-color: #888;
}

.shop-dice-name {
  font-size: 18px;
  font-weight: bold;
  color: #333;
  margin-bottom: 10px;
}

.shop-dice-image {
  width: 80px;
  height: 80px;
  margin: 10px auto;
}

.shop-dice-description {
  font-size: 13px;
  color: #555;
  min-height: 60px;
  margin: 10px 0;
}

.shop-dice-price {
  font-family: 'Courier New', monospace;
  font-size: 20px;
  font-weight: bold;
  color: #1d8711;
  margin: 10px 0;
}

.shop-buy-button {
  background: linear-gradient(to bottom, #FFD700, #FFA500);
  border: 1px solid #000;
  border-radius: 6px;
  padding: 10px 20px;
  font-size: 16px;
  color: #000;
  font-weight: bold;
  cursor: pointer;
}

.shop-buy-button:hover {
  background: linear-gradient(to bottom, #FFED4E, #FFB52E);
}

.shop-buy-button:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.5;
}

/* Money Display */
.challenge-money {
  font-family: 'Courier New', monospace;
  font-size: 20px;
  font-weight: bold;
  color: #FFD700;
  text-shadow: 2px 2px 4px #000;
  padding: 5px 10px;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 4px;
  display: inline-block;
}

.challenge-money::before {
  content: '💰 ';
}

/* Money Earned Popup */
.money-earned-popup {
  position: absolute;
  font-size: 24px;
  font-weight: bold;
  color: #FFD700;
  text-shadow: 2px 2px 4px #000;
  animation: money-float 1.5s ease-out;
  pointer-events: none;
}

@keyframes money-float {
  0% {
    opacity: 1;
    transform: translateY(0);
  }
  100% {
    opacity: 0;
    transform: translateY(-50px);
  }
}

/* Dice Inventory */
.challenge-dice-inventory {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin: 15px 0;
}

.challenge-dice-slot {
  width: 50px;
  height: 50px;
  background: white;
  border: 2px solid #000;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 2px 2px 4px #000;
  cursor: pointer;
  transition: transform 0.2s;
}

.challenge-dice-slot:hover {
  transform: scale(1.1);
  border-color: #FFD700;
}

.challenge-dice-slot.selected {
  border: 3px solid #FFD700;
  box-shadow: 0 0 10px #FFD700;
}

.challenge-dice-icon {
  width: 40px;
  height: 40px;
}

/* Bot Lineup */
.bot-lineup-row {
  display: flex;
  align-items: center;
  padding: 10px;
  margin: 5px 0;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  border-left: 4px solid transparent;
}

.bot-lineup-row.easy {
  border-left-color: #1d8711;
}

.bot-lineup-row.medium {
  border-left-color: #FFA500;
}

.bot-lineup-row.hard {
  border-left-color: #cc0000;
}

.bot-lineup-row.boss {
  border-left-color: #9400D3;
  background: rgba(148, 0, 211, 0.2);
}

.bot-lineup-row.locked {
  opacity: 0.5;
}

.bot-lineup-number {
  font-weight: bold;
  width: 60px;
}

.bot-lineup-name {
  flex: 1;
  font-weight: bold;
}

.bot-lineup-difficulty {
  width: 80px;
  text-align: center;
}

.bot-lineup-target {
  width: 100px;
  text-align: center;
  font-family: 'Courier New', monospace;
}

.bot-lineup-record {
  width: 100px;
  text-align: right;
}

/* Tier Badges */
.dice-tier-badge {
  display: inline-block;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 11px;
  font-weight: bold;
  text-transform: uppercase;
}

.dice-tier-badge.simple {
  background: #90EE90;
  color: #000;
}

.dice-tier-badge.better {
  background: #4169E1;
  color: #fff;
}

.dice-tier-badge.amazing {
  background: #9400D3;
  color: #fff;
}

/* Slot Selection Modal */
.slot-selection-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  border: 3px solid #000;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 0 50px rgba(0, 0, 0, 0.7);
  z-index: 1000;
  max-width: 500px;
}

.slot-selection-header {
  font-size: 20px;
  font-weight: bold;
  color: #333;
  margin-bottom: 20px;
  text-align: center;
}

.slot-selection-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  margin: 20px 0;
}
```

---

## Integration with Existing Systems

### Game Creation Flow

**Current Flow:**
1. Player selects game type in lobby
2. `createNewGame()` in `farkleGameFuncs.php`
3. Insert into `farkle_games` table
4. Redirect to game page

**Challenge Flow:**
1. Player starts challenge run
2. `Challenge_StartNewRun()` creates run record
3. For each bot:
   - `ChallengeGame_Create()` creates game with special flags
   - Game links to `challenge_run_id`
   - Load player's dice inventory
4. On victory → redirect to shop
5. On defeat → end run, update stats

### Scoring Integration

**Current Flow:**
1. Player selects dice to keep
2. `farkleDiceScoring.php` calculates points
3. Update `roundscore` and `turnscore`

**Challenge Flow:**
1. Load player's dice inventory
2. **Before scoring:** `DiceEffect_Apply()` modifies roll
3. Calculate base points
4. **After scoring:** `DiceEffect_CalculateMultiplier()` applies bonuses
5. Award money for dice saved
6. Show money earned popup

### Bot Game Integration

**Current Flow:**
1. Bot turn starts via `Bot_CheckAndStartTurn()`
2. Bot executes steps via AJAX
3. Frontend displays bot actions

**Challenge Flow:**
- Bots in challenge mode have special dice too
- Store bot's dice inventory in game record
- Apply bot dice effects to their rolls
- Show bot's special dice in UI
- Bot trash talk references challenge context

---

## Implementation Phases

### Phase 1: Database & Core Backend (Week 1)
**Tasks:**
- [ ] Create all new database tables
- [ ] Write seed data for `farkle_challenge_dice_types` (20-30 starter dice)
- [ ] Write seed data for `farkle_challenge_bot_lineup` (20 bots)
- [ ] Implement `challengeUtil.php` helper functions
- [ ] Write database migration script

**Testing:**
- Verify tables created correctly
- Test foreign key constraints
- Verify seed data loads

### Phase 2: Shop System (Week 2)
**Tasks:**
- [ ] Implement `farkleChallengeShop.php`
- [ ] Create shop AJAX endpoints
- [ ] Implement dice purchase logic
- [ ] Build shop template (`farkle_div_challenge_shop.tpl`)
- [ ] Implement `farkleChallengeShop.js` frontend
- [ ] Add shop CSS styling

**Testing:**
- Test shop displays 3 random unique dice
- Test purchase flow (with/without funds)
- Test dice replacement
- Test money deduction
- Test edge cases (duplicate purchases, invalid slots)

### Phase 3: Dice Effect Engine (Week 2-3)
**Tasks:**
- [ ] Implement `farkleChallengeScoring.php`
- [ ] Build effect processing pipeline
- [ ] Implement 10-15 core dice effects
- [ ] Integrate with existing scoring system
- [ ] Add effect descriptions and tooltips

**Testing:**
- Test each dice type individually
- Test dice combos and interactions
- Test edge cases (all wild dice, multiple multipliers)
- Verify scoring accuracy

### Phase 4: Challenge Lobby & Run Management (Week 3)
**Tasks:**
- [ ] Implement `farkleChallenge.php`
- [ ] Build challenge lobby template
- [ ] Implement `farkleChallenge.js` frontend
- [ ] Add run creation/resumption logic
- [ ] Build bot lineup display
- [ ] Add challenge CSS styling

**Testing:**
- Test new run creation
- Test run resumption
- Test "one active run" constraint
- Test bot lineup display

### Phase 5: Challenge Game Mode (Week 4)
**Tasks:**
- [ ] Implement `farkleChallengeGame.php`
- [ ] Modify `farkleGameFuncs.php` for challenge games
- [ ] Build challenge game template additions
- [ ] Implement `farkleChallengeGame.js` frontend
- [ ] Add money tracking during gameplay
- [ ] Implement victory/defeat flow

**Testing:**
- Test full challenge game flow
- Test money earned calculation
- Test victory → shop redirect
- Test defeat → run end
- Test bot progression

### Phase 6: Stats & Leaderboard (Week 5)
**Tasks:**
- [ ] Implement stats tracking on game completion
- [ ] Build leaderboard query
- [ ] Create stats template
- [ ] Add achievement integration
- [ ] Build stats display UI

**Testing:**
- Test stat updates on run completion
- Test leaderboard ranking
- Test achievement unlocks
- Verify stat accuracy

### Phase 7: Polish & Balancing (Week 6)
**Tasks:**
- [ ] Tune dice prices based on playtesting
- [ ] Balance bot difficulty curve
- [ ] Add more dice types (expand to 50+)
- [ ] Improve animations and visual feedback
- [ ] Add sound effects (optional)
- [ ] Mobile responsive design
- [ ] Add tutorial/help text

**Testing:**
- Full playthrough testing
- Balance testing (can players reasonably beat 20 bots?)
- Mobile device testing
- Cross-browser testing

### Phase 8: Deployment (Week 7)
**Tasks:**
- [ ] Database migration on staging
- [ ] Staging environment testing
- [ ] Performance testing (shop queries, effect calculations)
- [ ] Deploy to production
- [ ] Monitor for bugs
- [ ] Gather player feedback

---

## Technical Considerations

### Performance Optimization
- **Shop dice selection:** Index on `tier` and `enabled` for fast random queries
- **Effect calculations:** Cache dice effects in memory during game
- **Leaderboard:** Index on `furthest_bot_reached` for fast ranking
- **Inventory lookups:** Index on `run_id` for quick dice fetching

### Security
- **Validate all purchases:** Server-side checks for money, uniqueness, valid dice types
- **Prevent cheating:** All dice effects calculated server-side
- **Run ownership:** Verify player owns run_id before any operations
- **SQL injection:** Use PDO prepared statements (already in place)

### Edge Cases
- **Player quits mid-game:** Mark run as abandoned, don't count as loss
- **Simultaneous purchases:** Database constraints prevent double-purchasing
- **Invalid dice inventory:** Auto-repair to 6 standard dice if corrupted
- **Bot lineup changes:** Version the lineup, or only affect new runs

### Mobile Considerations
- **Shop cards:** Stack vertically on small screens
- **Dice inventory:** Smaller icons, scrollable if needed
- **Money display:** Always visible, sticky header
- **Touch targets:** Minimum 44px tap areas

---

## Appendix

### Standard Die Definition
```sql
INSERT INTO farkle_challenge_dice_types (name, description, price, tier, effect_type, effect_value)
VALUES ('Standard Die', 'A regular six-sided die with no special effects.', 0, 'simple', 'none', '{}');
```

### Example Dice Definitions
```sql
-- Lucky Die
INSERT INTO farkle_challenge_dice_types (name, description, price, tier, effect_type, effect_value)
VALUES (
  'Lucky Die',
  'Higher chance to roll 1s and 5s.',
  5,
  'better',
  'probability',
  '{"boost_faces": [1, 5], "boost_amount": 0.15}'
);

-- Double Die
INSERT INTO farkle_challenge_dice_types (name, description, price, tier, effect_type, effect_value)
VALUES (
  'Double Die',
  'Doubles the points from this die.',
  8,
  'amazing',
  'multiplier',
  '{"multiplier": 2, "applies_to": "self"}'
);
```

### Example Bot Lineup Entry
```sql
INSERT INTO farkle_challenge_bot_lineup (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
VALUES (
  1,
  1, -- Byte
  'Byte the Beginner',
  2500,
  '{"none": true}',
  '[1, 1, 1, 1, 1, 1]', -- 6 standard dice
  'A friendly bot to get you started. Easy target of 2500 points.'
);

INSERT INTO farkle_challenge_bot_lineup (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
VALUES (
  20,
  15, -- Prime
  'Prime the Champion',
  4000,
  '{"bot_has_lucky_dice": true, "player_farkle_penalty": 200}',
  '[1, 1, 7, 7, 12, 15]', -- 2 standard, 4 special dice
  'The final boss. 4000 point target, has special dice, and you lose 200 points when you farkle!'
);
```

---

## File Structure Summary

```
includes/
  challengeUtil.php (NEW)

wwwroot/
  farkleChallenge.php (NEW)
  farkleChallengeShop.php (NEW)
  farkleChallengeGame.php (NEW)
  farkleChallengeScoring.php (NEW)
  farkleGameFuncs.php (MODIFIED)
  farkleDiceScoring.php (MODIFIED)
  farkle_fetch.php (MODIFIED)

js/
  farkleChallenge.js (NEW)
  farkleChallengeShop.js (NEW)
  farkleChallengeGame.js (NEW)
  farkleGame.js (MODIFIED)
  farklePage.js (MODIFIED)
  farkleLobby.js (MODIFIED)

templates/
  farkle_div_challenge_lobby.tpl (NEW)
  farkle_div_challenge_shop.tpl (NEW)
  farkle_div_challenge_game.tpl (NEW)
  farkle_div_challenge_bot_lineup.tpl (NEW)
  farkle_div_challenge_stats.tpl (NEW)
  farkle_div_lobby.tpl (MODIFIED)
  farkle_div_game.tpl (MODIFIED)

css/
  farkle.css (MODIFIED - add challenge classes)

scripts/
  seed_challenge_dice.php (NEW)
  seed_challenge_bots.php (NEW)
  migrate_challenge_schema.php (NEW)
```

---

**End of Implementation Plan**
