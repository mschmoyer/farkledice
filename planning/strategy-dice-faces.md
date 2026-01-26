# Dice Face Customization System

**Status:** Ideation
**Created:** 2026-01-25

## Overview

Transform Farkle from a pure luck game into a strategic experience by allowing players to customize their dice faces. Players can purchase enhanced dice faces from a store and apply them to specific dice, creating a personalized "deck" of 36 faces (6 dice × 6 sides).

## Core Concept

Each die face can have modifiers printed on it that activate when that face is rolled and scores. These modifiers are visually displayed on the dice during gameplay.

### Modifier Types

| Modifier | Visual | Description | Example Effect |
|----------|--------|-------------|----------------|
| **Multiplier** | `×2` (red) | Multiplies the score value of this die | A 5 with ×2 scores 100 instead of 50 |
| **Bonus Points** | `+100` (gold) | Adds flat bonus when this die scores | A 1 with +100 scores 200 instead of 100 |
| **Value Swap** | `→1` (blue) | Changes this face to act as a different value | A 3 face that counts as a 1 |

### Future Modifier Ideas

| Modifier | Visual | Description |
|----------|--------|-------------|
| **Re-roll** | `↻` (green) | If this die doesn't score, automatically re-roll once |
| **Sticky** | `📌` | Once scored, this die stays selected for next roll |
| **Wild** | `★` | Counts as any value needed to complete a combo |
| **Protected** | `🛡️` | If this die scores, it cannot cause a farkle |
| **Chain** | `⚡` | If scored, adds +50 per other scoring die |

## Store System

### Currency

- **Farkle Coins** - Earned through gameplay (wins, achievements, daily login)
- Alternative: Use existing XP system or introduce premium currency

### Store Interface

```
┌─────────────────────────────────────────────┐
│  DICE FACE STORE                            │
├─────────────────────────────────────────────┤
│  Your Balance: 🪙 2,450                      │
├─────────────────────────────────────────────┤
│                                             │
│  MULTIPLIERS                                │
│  ┌─────┐  ┌─────┐  ┌─────┐                 │
│  │ ×2  │  │ ×3  │  │ ×5  │                 │
│  │ 500 │  │1500 │  │5000 │                 │
│  └─────┘  └─────┘  └─────┘                 │
│                                             │
│  BONUS POINTS                               │
│  ┌─────┐  ┌─────┐  ┌─────┐                 │
│  │+50  │  │+100 │  │+250 │                 │
│  │ 200 │  │ 600 │  │2000 │                 │
│  └─────┘  └─────┘  └─────┘                 │
│                                             │
│  VALUE SWAPS                                │
│  ┌─────┐  ┌─────┐                          │
│  │ →1  │  │ →5  │  (replace any face)      │
│  │1000 │  │ 400 │                          │
│  └─────┘  └─────┘                          │
│                                             │
└─────────────────────────────────────────────┘
```

### Dice Customization UI

After purchasing a modifier, player applies it:

```
┌─────────────────────────────────────────────┐
│  APPLY MODIFIER: ×2 Multiplier              │
├─────────────────────────────────────────────┤
│                                             │
│  Step 1: Select a Die                       │
│                                             │
│  ┌───┐ ┌───┐ ┌───┐ ┌───┐ ┌───┐ ┌───┐      │
│  │ 1 │ │ 2 │ │ 3 │ │ 4 │ │ 5 │ │ 6 │      │
│  └───┘ └───┘ └───┘ └───┘ └───┘ └───┘      │
│    ▲                                        │
│  Selected                                   │
│                                             │
│  Step 2: Select a Face                      │
│                                             │
│  Die 1 Faces:                               │
│  ┌───┐ ┌───┐ ┌───┐ ┌───┐ ┌───┐ ┌───┐      │
│  │ ⚀ │ │ ⚁ │ │ ⚂ │ │ ⚃ │ │ ⚄ │ │ ⚅ │      │
│  │   │ │   │ │+50│ │   │ │×2 │ │   │      │
│  └───┘ └───┘ └───┘ └───┘ └───┘ └───┘      │
│    ▲                                        │
│  Apply ×2 here? [CONFIRM]                   │
│                                             │
│  ⚠️ This will replace existing modifier     │
│                                             │
└─────────────────────────────────────────────┘
```

## Database Schema

### New Tables

```sql
-- Available modifiers that can be purchased
CREATE TABLE farkle_dice_modifiers (
    modifier_id SERIAL PRIMARY KEY,
    modifier_type VARCHAR(20) NOT NULL,  -- 'multiplier', 'bonus', 'value_swap'
    modifier_value VARCHAR(10) NOT NULL, -- 'x2', '+100', '->1'
    display_text VARCHAR(10) NOT NULL,   -- What shows on the die face
    display_color VARCHAR(20) NOT NULL,  -- CSS color for the modifier
    cost INTEGER NOT NULL,               -- Price in coins
    description TEXT,
    active BOOLEAN DEFAULT TRUE
);

-- Player's inventory of purchased modifiers
CREATE TABLE farkle_player_modifiers (
    id SERIAL PRIMARY KEY,
    playerid INTEGER REFERENCES farkle_players(playerid),
    modifier_id INTEGER REFERENCES farkle_dice_modifiers(modifier_id),
    quantity INTEGER DEFAULT 0,
    purchased_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Player's dice face configuration (what's applied to each die)
CREATE TABLE farkle_player_dice_config (
    id SERIAL PRIMARY KEY,
    playerid INTEGER REFERENCES farkle_players(playerid),
    die_number INTEGER NOT NULL CHECK (die_number BETWEEN 1 AND 6),
    face_value INTEGER NOT NULL CHECK (face_value BETWEEN 1 AND 6),
    modifier_id INTEGER REFERENCES farkle_dice_modifiers(modifier_id),
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(playerid, die_number, face_value)
);

-- Player's currency balance
ALTER TABLE farkle_players ADD COLUMN coins INTEGER DEFAULT 0;
```

## Gameplay Integration

### Visual Display

When dice are rolled, the modifier is visually displayed:

```
Standard Die 5:      Modified Die 5:
   ┌───────┐            ┌───────┐
   │ ● ● ● │            │ ● ● ● │
   │   ●   │            │ ×2 ●  │  ← Red "×2" overlay
   │ ● ● ● │            │ ● ● ● │
   └───────┘            └───────┘
```

### Score Calculation

```php
// In farkleDiceScoring.php
function calculateModifiedScore($die_value, $base_score, $player_dice_config) {
    $modifier = getModifierForDie($die_value, $player_dice_config);

    if ($modifier) {
        switch ($modifier['type']) {
            case 'multiplier':
                return $base_score * $modifier['value'];
            case 'bonus':
                return $base_score + $modifier['value'];
            case 'value_swap':
                // Already handled in dice evaluation
                return $base_score;
        }
    }
    return $base_score;
}
```

## Coin Economy

### Earning Coins

| Action | Coins Earned |
|--------|-------------|
| Win a game | 100-500 (based on opponent level) |
| Complete a game | 25 |
| Daily login | 50 |
| Win streak (3+) | Bonus 50 per game |
| Achievement unlock | Varies (50-500) |
| Level up | 100 × new level |

### Spending Coins

Modifiers are consumed when applied (one-time use per face). To change a face, you need a new modifier.

## Balancing Considerations

1. **Fair Matchmaking** - Games could match players with similar modifier loadouts
2. **Modifier Limits** - Cap total modifiers per player (e.g., max 6 active)
3. **Game Mode Option** - "Classic" mode with no modifiers for purists
4. **Bot Balance** - Bots could have preset modifier configurations by difficulty

## Implementation Phases

### Phase 1: Foundation
- Database schema
- Coin system (earning/balance)
- Basic store UI

### Phase 2: Modifiers
- Implement multiplier and bonus modifiers
- Dice customization UI
- Visual display on dice

### Phase 3: Scoring Integration
- Modify scoring engine
- Apply modifiers during gameplay
- Show modifier effects in score breakdown

### Phase 4: Polish
- Value swap modifiers
- Balancing and testing
- Bot modifier configurations

## Open Questions

1. Should modifiers be permanent or consumable?
2. Should there be a "reset to default" option?
3. How do modifiers interact with 3-of-a-kind and other combinations?
4. Should multiplayer games have modifier restrictions?
5. Can players see opponents' modifier configurations?

## See Also

- [Player Superpowers](./strategy-superpowers.md) - Alternative/complementary strategic system
