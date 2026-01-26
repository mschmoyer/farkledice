# Player Loadout System (Superpowers + Items)

**Status:** Ideation
**Created:** 2026-01-25

## Overview

Allow players to build a strategic "loadout" before each game. This provides meaningful choices and lets players lean into their preferred approach - whether that's high-risk/high-reward, aggressive offense, or farkle mitigation.

## Core Concept: The Loadout

Each player's loadout consists of:

| Slot | Type | Usage | Description |
|------|------|-------|-------------|
| **Superpower** | Passive ability | Automatic | Triggers based on game conditions |
| **Item 1** | Active ability | 1x per game | Player chooses when to activate |
| **Item 2** | Active ability | 1x per game | Player chooses when to activate |

This creates a "1 power + 2 items" system where the superpower defines your playstyle and items provide tactical flexibility.

## Superpower Categories

### Risk/Reward Powers (For Aggressive Players)

| Power | Name | Description | Visual |
|-------|------|-------------|--------|
| **Hot Streak** | "Fortune Favors the Bold" | After banking 500+ points in a single turn, your next roll cannot farkle | 🔥 |
| **Double Down** | "All or Nothing" | Once per game, bank your current turn score twice (but if you farkle before banking, lose double) | ⚡ |
| **Risk Master** | "Nothing Ventured" | +25% bonus on any turn where you rolled all 6 dice at least twice | 🎲 |
| **Comeback King** | "Never Say Die" | When 2000+ points behind, all 5s score 75 instead of 50 | 👑 |

### Farkle Mitigation Powers (For Cautious Players)

| Power | Name | Description | Visual |
|-------|------|-------------|--------|
| **Safety Net** | "Soft Landing" | Once per game, if you farkle, keep half your turn score (rounded down) | 🪢 |
| **Second Chance** | "Lucky Break" | Once per game, re-roll a farkle (must use immediately) | 🍀 |
| **Farkle Shield** | "Protected Roll" | First roll of each turn cannot farkle (if no scoring dice, re-roll automatically) | 🛡️ |
| **Pain Reducer** | "Thick Skin" | Farkles only count as -50 to your turn score instead of losing everything | 💪 |

### Scoring Enhancement Powers

| Power | Name | Description | Visual |
|-------|------|-------------|--------|
| **Triple Threat** | "Lucky Threes" | 3s count as 30 points (normally 0 unless three-of-a-kind) | 3️⃣ |
| **Combo Master** | "Pattern Recognition" | Three-of-a-kind bonuses are +50% | 🎯 |
| **Hot Dice Bonus** | "On a Roll" | When you score with all 6 dice (hot dice), bonus +200 points | 🌟 |
| **Steady Scorer** | "Consistency Pays" | Every turn you bank 200-400 points, gain +25 bonus | 📊 |

### Strategic Powers

| Power | Name | Description | Visual |
|-------|------|-------------|--------|
| **Mind Reader** | "Sixth Sense" | See a hint showing which dice to keep (based on optimal strategy) | 🔮 |
| **Time Warp** | "Patience Pays" | Your turn timer is extended by 50% | ⏱️ |
| **Pressure Player** | "Clutch Performer" | In the final round, all your scores are +20% | 🏆 |
| **Early Bird** | "Quick Start" | First 3 turns of the game, 1s score 150 instead of 100 | 🐦 |

---

## Inventory Items (Active Abilities)

Items are single-use abilities that players **manually activate** at the moment of their choosing. Each player brings 2 items per game.

### Offensive Items (Attack Opponent)

| Item | Name | Effect | When to Use |
|------|------|--------|-------------|
| **Score Wipe** | "Eraser" | Wipe opponent's last banked score (they keep total minus last turn) | 💥 After opponent banks a big turn |
| **Freeze** | "Ice Block" | Opponent must bank immediately on their next turn (no rolling) | ❄️ When opponent is on a hot streak |
| **Pressure** | "Heat Wave" | Opponent's next turn timer is halved | 🌡️ Force rushed decisions |
| **Jinx** | "Bad Luck Charm" | Opponent's next roll has +20% farkle chance | 🪬 When they're pushing luck |

### Defensive Items (Protect Yourself)

| Item | Name | Effect | When to Use |
|------|------|--------|-------------|
| **Farkle Shield** | "Safety Bubble" | Your next farkle is prevented (re-roll instead) | 🛡️ Before a risky roll |
| **Score Save** | "Piggy Bank" | If you farkle this turn, keep 50% of turn score | 🐷 When sitting on big points |
| **Mulligan** | "Do-Over" | Re-roll your last roll (must use immediately after rolling) | 🔄 After a bad roll |
| **Insurance** | "Backup Plan" | If you farkle, steal 200 points from opponent instead | 📋 High-risk situations |

### Scoring Items (Boost Your Points)

| Item | Name | Effect | When to Use |
|------|------|--------|-------------|
| **Double Down** | "2x Multiplier" | Double your current turn score when you bank | ×2 When sitting on 400+ points |
| **Bonus Roll** | "Extra Dice" | Add a 7th die to your next roll | 🎲 Early in a turn |
| **Hot Dice Instant** | "Fresh Start" | Immediately get hot dice (roll all 6 again) | 🔥 When stuck with few dice |
| **Point Surge** | "Power Boost" | Your next scoring combination is worth +50% | ⚡ Before banking a combo |

### Strategic Items

| Item | Name | Effect | When to Use |
|------|------|--------|-------------|
| **Peek** | "Crystal Ball" | See what your next roll would be (then choose to roll or bank) | 🔮 Uncertain moments |
| **Swap** | "Trade Places" | Swap your current turn score with opponent's last banked score | 🔀 When behind |
| **Time Out** | "Pause" | Skip opponent's next turn entirely | ⏸️ Protect a lead |
| **Copycat** | "Mirror" | Copy opponent's superpower for the rest of this game | 🪞 If their power is better |

---

## Loadout Examples

### "The Gambler" (High Risk)
- **Power:** 🔥 Hot Streak (protection after big scores)
- **Item 1:** ×2 Double Down (maximize big turns)
- **Item 2:** 🛡️ Farkle Shield (one safety net)

### "The Saboteur" (Aggressive)
- **Power:** 🏆 Pressure Player (strong finish)
- **Item 1:** 💥 Score Wipe (erase opponent's lead)
- **Item 2:** ❄️ Freeze (stop their momentum)

### "The Turtle" (Conservative)
- **Power:** 🪢 Safety Net (auto farkle protection)
- **Item 1:** 🐷 Score Save (extra farkle insurance)
- **Item 2:** 🔄 Mulligan (fix bad rolls)

### "The Comeback Kid"
- **Power:** 👑 Comeback King (bonus when behind)
- **Item 1:** 🔀 Swap (steal opponent's score)
- **Item 2:** ⏸️ Time Out (skip their turn)

---

## Selection UI

### Loadout Builder (Profile Page)

```
┌─────────────────────────────────────────────────────────┐
│  BUILD YOUR LOADOUT                                     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  SUPERPOWER (Passive)                           │   │
│  │  ┌───────────────────────────────────────────┐ │   │
│  │  │ 🔥 HOT STREAK                             │ │   │
│  │  │ After banking 500+ points, your next      │ │   │
│  │  │ roll cannot farkle                        │ │   │
│  │  └───────────────────────────────────────────┘ │   │
│  │  [CHANGE POWER]                                │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  ITEM SLOT 1                                    │   │
│  │  ┌───────────────────────────────────────────┐ │   │
│  │  │ ×2 DOUBLE DOWN                            │ │   │
│  │  │ Double your turn score when banking       │ │   │
│  │  │ (1 use per game)                          │ │   │
│  │  └───────────────────────────────────────────┘ │   │
│  │  [CHANGE ITEM]                                 │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  ITEM SLOT 2                                    │   │
│  │  ┌───────────────────────────────────────────┐ │   │
│  │  │ 🛡️ FARKLE SHIELD                          │ │   │
│  │  │ Prevent your next farkle (re-roll)        │ │   │
│  │  │ (1 use per game)                          │ │   │
│  │  └───────────────────────────────────────────┘ │   │
│  │  [CHANGE ITEM]                                 │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  [SAVE LOADOUT]                                         │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Item Selection Modal

```
┌─────────────────────────────────────────────────────────┐
│  SELECT ITEM FOR SLOT 1                    [X]          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  OFFENSIVE                                              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│  │ 💥       │ │ ❄️       │ │ 🌡️       │ │ 🪬       │   │
│  │ Score    │ │ Freeze   │ │ Pressure │ │ Jinx     │   │
│  │ Wipe     │ │          │ │          │ │          │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
│                                                         │
│  DEFENSIVE                                              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│  │ 🛡️       │ │ 🐷       │ │ 🔄       │ │ 📋       │   │
│  │ Farkle   │ │ Score    │ │ Mulligan │ │ Insurance│   │
│  │ Shield   │ │ Save     │ │          │ │          │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
│                                                         │
│  SCORING                                                │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│  │ ×2       │ │ 🎲       │ │ 🔥       │ │ ⚡       │   │
│  │ Double   │ │ Bonus    │ │ Hot Dice │ │ Point    │   │
│  │ Down     │ │ Roll     │ │ Instant  │ │ Surge    │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ ×2 DOUBLE DOWN                                  │   │
│  │                                                 │   │
│  │ Double your current turn score when you bank.  │   │
│  │ Best used when sitting on 400+ points.         │   │
│  │                                                 │   │
│  │ Category: Scoring | Uses: 1 per game           │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  [SELECT THIS ITEM]                                     │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### In-Game Item Bar

During gameplay, items appear as buttons:

```
┌─────────────────────────────────────────────────────────┐
│  Your Turn                          🔥 Hot Streak       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Turn Score: 450                                        │
│                                                         │
│  ⚀ ⚀ ⚂ ⚃ ⚄ ⚅                                          │
│                                                         │
│  [ROLL AGAIN]  [BANK 450]                               │
│                                                         │
├─────────────────────────────────────────────────────────┤
│  ITEMS:                                                 │
│  ┌────────────────┐  ┌────────────────┐                │
│  │ ×2 DOUBLE DOWN │  │ 🛡️ FARKLE     │                │
│  │   [USE NOW]    │  │    SHIELD     │                │
│  │                │  │   [USE NOW]   │                │
│  └────────────────┘  └────────────────┘                │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

After using an item:

```
│  ITEMS:                                                 │
│  ┌────────────────┐  ┌────────────────┐                │
│  │ ×2 DOUBLE DOWN │  │ 🛡️ FARKLE     │                │
│  │    ✓ USED     │  │    SHIELD     │                │
│  │   (Round 3)   │  │   [USE NOW]   │                │
│  └────────────────┘  └────────────────┘                │
```

## Database Schema

```sql
-- Available superpowers (passive abilities)
CREATE TABLE farkle_superpowers (
    power_id SERIAL PRIMARY KEY,
    power_key VARCHAR(30) NOT NULL UNIQUE,  -- 'hot_streak', 'safety_net', etc.
    name VARCHAR(50) NOT NULL,
    tagline VARCHAR(50),                     -- "Fortune Favors the Bold"
    description TEXT NOT NULL,
    category VARCHAR(20) NOT NULL,           -- 'risk', 'mitigation', 'scoring', 'strategic'
    icon VARCHAR(10),                        -- Emoji or icon class
    unlock_level INTEGER DEFAULT 1,          -- Level required to unlock
    active BOOLEAN DEFAULT TRUE
);

-- Available items (active abilities, 1 use per game)
CREATE TABLE farkle_items (
    item_id SERIAL PRIMARY KEY,
    item_key VARCHAR(30) NOT NULL UNIQUE,   -- 'score_wipe', 'farkle_shield', etc.
    name VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(20) NOT NULL,           -- 'offensive', 'defensive', 'scoring', 'strategic'
    icon VARCHAR(10),
    effect_type VARCHAR(30) NOT NULL,        -- 'instant', 'next_roll', 'next_turn', 'on_farkle'
    effect_target VARCHAR(20) NOT NULL,      -- 'self', 'opponent', 'both'
    unlock_level INTEGER DEFAULT 1,
    active BOOLEAN DEFAULT TRUE
);

-- Player's loadout configuration (1 power + 2 items)
CREATE TABLE farkle_player_loadout (
    playerid INTEGER PRIMARY KEY REFERENCES farkle_players(playerid),
    superpower_id INTEGER REFERENCES farkle_superpowers(power_id),
    item_slot_1 INTEGER REFERENCES farkle_items(item_id),
    item_slot_2 INTEGER REFERENCES farkle_items(item_id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Per-game item usage tracking
CREATE TABLE farkle_game_item_usage (
    id SERIAL PRIMARY KEY,
    gameid INTEGER REFERENCES farkle_games(gameid),
    playerid INTEGER REFERENCES farkle_players(playerid),
    item_id INTEGER REFERENCES farkle_items(item_id),
    slot_number INTEGER NOT NULL CHECK (slot_number IN (1, 2)),
    used_round INTEGER,
    effect_details JSONB,                    -- Store specifics (e.g., points wiped, dice rerolled)
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(gameid, playerid, slot_number)    -- Can only use each slot once per game
);

-- Per-game power trigger tracking (for passive powers that activate)
CREATE TABLE farkle_game_power_triggers (
    id SERIAL PRIMARY KEY,
    gameid INTEGER REFERENCES farkle_games(gameid),
    playerid INTEGER REFERENCES farkle_players(playerid),
    power_id INTEGER REFERENCES farkle_superpowers(power_id),
    triggered_round INTEGER,
    effect_details JSONB,
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Track power/item effectiveness for balancing
CREATE TABLE farkle_loadout_stats (
    id SERIAL PRIMARY KEY,
    entity_type VARCHAR(10) NOT NULL,        -- 'power' or 'item'
    entity_id INTEGER NOT NULL,              -- power_id or item_id
    games_used INTEGER DEFAULT 0,
    games_won INTEGER DEFAULT 0,
    times_triggered INTEGER DEFAULT 0,
    total_effect_value BIGINT DEFAULT 0,     -- Points gained/saved/stolen
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(entity_type, entity_id)
);
```

### Seed Data Example

```sql
-- Superpowers
INSERT INTO farkle_superpowers (power_key, name, tagline, description, category, icon, unlock_level) VALUES
('hot_streak', 'Hot Streak', 'Fortune Favors the Bold', 'After banking 500+ points, your next roll cannot farkle', 'risk', '🔥', 1),
('safety_net', 'Safety Net', 'Soft Landing', 'Once per game, if you farkle, keep half your turn score', 'mitigation', '🪢', 1),
('triple_threat', 'Triple Threat', 'Lucky Threes', '3s count as 30 points (normally 0)', 'scoring', '3️⃣', 1),
('pressure_player', 'Pressure Player', 'Clutch Performer', 'In the final round, all scores are +20%', 'strategic', '🏆', 5);

-- Items
INSERT INTO farkle_items (item_key, name, description, category, icon, effect_type, effect_target, unlock_level) VALUES
('score_wipe', 'Score Wipe', 'Wipe opponent''s last banked score', 'offensive', '💥', 'instant', 'opponent', 1),
('farkle_shield', 'Farkle Shield', 'Your next farkle is prevented (re-roll instead)', 'defensive', '🛡️', 'next_roll', 'self', 1),
('double_down', 'Double Down', 'Double your current turn score when banking', 'scoring', '×2', 'instant', 'self', 1),
('freeze', 'Freeze', 'Opponent must bank immediately on their next turn', 'offensive', '❄️', 'next_turn', 'opponent', 5);
```

## Implementation Details

### Power Trigger System (Passive)

```php
// In farkleGameFuncs.php
class SuperpowerManager {

    public static function checkTrigger($power_key, $context) {
        switch ($power_key) {
            case 'hot_streak':
                // Trigger after banking 500+
                if ($context['action'] == 'bank' && $context['turn_score'] >= 500) {
                    return ['next_roll_protected' => true];
                }
                break;

            case 'safety_net':
                // Trigger on farkle, if not used this game
                if ($context['action'] == 'farkle' && !$context['power_used']) {
                    $saved_score = floor($context['turn_score'] / 2);
                    return ['save_score' => $saved_score, 'mark_used' => true];
                }
                break;

            case 'triple_threat':
                // Passive: 3s always score 30
                if ($context['action'] == 'score_dice') {
                    // Modify scoring in farkleDiceScoring.php
                }
                break;
        }
        return null;
    }
}
```

### Item Activation System (Active)

```php
// In farkleGameFuncs.php
class ItemManager {

    public static function canUseItem($gameid, $playerid, $slot_number) {
        // Check if item already used this game
        $sql = "SELECT id FROM farkle_game_item_usage
                WHERE gameid = ? AND playerid = ? AND slot_number = ?";
        return !db_get_row($sql, [$gameid, $playerid, $slot_number]);
    }

    public static function useItem($gameid, $playerid, $item_key, $slot_number, $context) {
        if (!self::canUseItem($gameid, $playerid, $slot_number)) {
            return ['error' => 'Item already used this game'];
        }

        $effect = null;

        switch ($item_key) {
            case 'score_wipe':
                // Wipe opponent's last banked score
                $opponent_id = $context['opponent_id'];
                $last_score = getLastBankedScore($gameid, $opponent_id);
                subtractFromScore($gameid, $opponent_id, $last_score);
                $effect = ['points_wiped' => $last_score, 'target' => $opponent_id];
                break;

            case 'double_down':
                // Double current turn score on bank
                $doubled = $context['turn_score'] * 2;
                $effect = ['original' => $context['turn_score'], 'doubled' => $doubled];
                return ['modify_bank' => $doubled, 'effect' => $effect];

            case 'farkle_shield':
                // Set flag for next roll protection
                setPlayerFlag($gameid, $playerid, 'farkle_protected', true);
                $effect = ['protection_active' => true];
                break;

            case 'freeze':
                // Force opponent to bank next turn
                $opponent_id = $context['opponent_id'];
                setPlayerFlag($gameid, $opponent_id, 'must_bank', true);
                $effect = ['target' => $opponent_id, 'frozen' => true];
                break;
        }

        // Record usage
        self::recordUsage($gameid, $playerid, $item_key, $slot_number, $context['round'], $effect);

        return ['success' => true, 'effect' => $effect];
    }

    private static function recordUsage($gameid, $playerid, $item_key, $slot, $round, $effect) {
        $item_id = getItemIdByKey($item_key);
        $sql = "INSERT INTO farkle_game_item_usage
                (gameid, playerid, item_id, slot_number, used_round, effect_details)
                VALUES (?, ?, ?, ?, ?, ?)";
        db_query($sql, [$gameid, $playerid, $item_id, $slot, $round, json_encode($effect)]);
    }
}
```

### JavaScript Item Activation

```javascript
// In farkleGame.js
function useItem(slotNumber) {
    const item = playerLoadout.items[slotNumber - 1];

    if (item.used) {
        showMessage("Already used this game!");
        return;
    }

    // Confirm usage for impactful items
    if (item.category === 'offensive') {
        if (!confirm(`Use ${item.name} against your opponent?`)) {
            return;
        }
    }

    ajaxCall('use_item', {
        gameid: currentGame.id,
        slot: slotNumber,
        context: {
            turn_score: currentTurnScore,
            round: currentRound
        }
    }, function(response) {
        if (response.success) {
            markItemUsed(slotNumber, currentRound);
            showItemEffect(item, response.effect);
            refreshGameState();
        }
    });
}

function showItemEffect(item, effect) {
    // Animate the item effect
    switch (item.key) {
        case 'score_wipe':
            animateScoreWipe(effect.target, effect.points_wiped);
            break;
        case 'double_down':
            animateScoreDouble(effect.original, effect.doubled);
            break;
        case 'farkle_shield':
            showShieldActive();
            break;
    }
}
```

### Visual Indicators

During gameplay, show active power status:

```
┌─────────────────────────────────────────────┐
│  Your Turn                    🔥 Hot Streak │
│                                             │
│  Turn Score: 650              ✓ CHARGED     │
│  [Next roll is farkle-proof!]               │
│                                             │
│  ⚀ ⚀ ⚂ ⚃ ⚄ ⚅                              │
│                                             │
│  [ROLL AGAIN - PROTECTED!]  [BANK 650]      │
│                                             │
└─────────────────────────────────────────────┘
```

For one-time powers:

```
│  🪢 Safety Net: AVAILABLE                   │
│  or                                         │
│  🪢 Safety Net: USED (Round 4)              │
```

## Balancing Considerations

### Win Rate Tracking

Track each power's win rate to ensure balance:

```sql
SELECT
    sp.name,
    ps.games_used,
    ps.games_won,
    ROUND(ps.games_won::numeric / ps.games_used * 100, 1) as win_rate
FROM farkle_superpowers sp
JOIN farkle_power_stats ps ON sp.power_id = ps.power_id
WHERE ps.games_used > 100
ORDER BY win_rate DESC;
```

Target: All powers should have win rates between 45-55% in balanced matchups.

### Unlock Progression

Powers could be unlocked as players level up:

| Level | Powers Unlocked |
|-------|-----------------|
| 1 | Safety Net, Hot Streak, Triple Threat |
| 5 | Second Chance, Risk Master, Combo Master |
| 10 | Farkle Shield, Double Down, Hot Dice Bonus |
| 15 | Pain Reducer, Comeback King, Steady Scorer |
| 20 | Mind Reader, Pressure Player, Early Bird |
| 25 | Time Warp (premium) |

## Multiplayer Fairness

### Options

1. **Mirror Match** - Both players must use the same power
2. **Power Draft** - Players take turns picking from available powers
3. **Hidden Selection** - Players choose without seeing opponent's choice
4. **Handicap System** - Stronger power = lower starting score

### Visibility

- **Show opponent's power?** Adds strategy if visible, surprise if hidden
- **Show when triggered?** Animation/notification when a power activates

## Integration with Dice Face System

Superpowers and dice face customization could work together:

- **Complementary** - Power enhances a playstyle, dice faces enhance specific outcomes
- **Exclusive** - Choose one system or the other per game
- **Synergy** - Some powers boost modified dice (e.g., "Modifier Master" - your dice modifiers are 50% stronger)

## Implementation Phases

### Phase 1: Foundation
- Database schema (powers + items tables)
- Seed initial powers (4-6) and items (8-10)
- Loadout configuration table
- Basic loadout builder UI on profile page

### Phase 2: Superpower Integration
- Power trigger system for passive abilities
- Modify game logic to check power conditions
- Visual indicator for active power during gameplay
- Track power triggers for stats

### Phase 3: Item System
- Item activation endpoints
- In-game item bar UI with [USE] buttons
- Item effect animations
- Usage tracking (1 per slot per game)
- Confirmation dialogs for offensive items

### Phase 4: Opponent Interactions
- Score Wipe implementation (subtract from opponent)
- Freeze implementation (force bank)
- Notifications when opponent uses item on you
- "You were hit by Score Wipe!" alerts

### Phase 5: Polish & Balance
- Add remaining powers and items
- Unlock progression by level
- Balance testing with win rate tracking
- Bot loadout configurations
- Mobile-friendly item bar

## Balancing Considerations

### Item Power Levels

Items should be impactful but not game-breaking:

| Item | Impact | Fairness Check |
|------|--------|----------------|
| Score Wipe | High | Only affects last turn, not total game progress |
| Double Down | High | Requires building up a good turn first |
| Farkle Shield | Medium | Only prevents one farkle |
| Freeze | Medium | Opponent still banks whatever they have |

### Counter-Play

Some items naturally counter others:
- **Score Wipe** → Countered by banking frequently (smaller amounts)
- **Freeze** → Less impactful if opponent was planning to bank anyway
- **Farkle Shield** → Wasted if you don't farkle

### Duplicate Prevention

Players cannot select the same item for both slots (enforced in UI and backend).

## Open Questions

1. Should opponent see your loadout before the game starts?
2. Should there be a "loadout reveal" at game start?
3. Can items be used on opponent's turn or only your own?
4. Should Score Wipe work on any past turn or only the most recent?
5. How do bots decide when to use items?
6. Should items be unlockable or available from level 1?
7. Premium items that cost coins vs. free items?

## Future Ideas

### Item Shop
- Players earn items through gameplay
- Limited inventory (own 5, bring 2 to each game)
- Rare/powerful items drop from achievements

### Loadout Presets
- Save multiple loadouts ("Aggressive", "Defensive", "Anti-Bot")
- Quick-switch before starting a game

### Seasonal Items
- Limited-time items during events
- Holiday-themed effects

## See Also

- [Dice Face Customization](./strategy-dice-faces.md) - Complementary strategic system
