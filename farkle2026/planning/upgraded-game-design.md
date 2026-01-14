# Farkle Ten: Lucky Dice - Enhanced Game Design

> *A Balatro-inspired roguelike reimagining of classic Farkle*

## Executive Summary

This document outlines an enhanced solo game mode for Farkle Ten that transforms the classic push-your-luck dice game into a deep, replayable roguelike experience. Inspired by Balatro's addictive loop of meaningful choices, satisfying progression, and emergent strategy, "Lucky Dice" mode adds:

- **Special Dice** with unique abilities (like Balatro's Jokers)
- **Roguelike Runs** with escalating difficulty across 8 Antes
- **Boss Challenges** that modify the rules
- **Shop Economy** with packs, consumables, and upgrades
- **Meta-Progression** that persists between runs

Players can still chase achievements and leaderboards while experiencing a fundamentally deeper gameplay loop.

---

## Table of Contents

1. [Core Design Philosophy](#1-core-design-philosophy)
2. [The Scoring System](#2-the-scoring-system)
3. [Run Structure](#3-run-structure)
4. [Special Dice System](#4-special-dice-system)
5. [Economy & Shop](#5-economy--shop)
6. [Boss Blinds](#6-boss-blinds)
7. [Consumables & Upgrades](#7-consumables--upgrades)
8. [Challenge Modes](#8-challenge-modes)
9. [Daily & Weekly Events](#9-daily--weekly-events)
10. [Endless Mode](#10-endless-mode)
11. [Meta-Progression](#11-meta-progression)
12. [Visual & Audio Design](#12-visual--audio-design)
13. [Implementation Phases](#13-implementation-phases)

---

## 1. Core Design Philosophy

### What Makes Balatro Addictive

1. **Meaningful Choices**: Every decision matters - skip a blind for a tag, or play for money?
2. **Emergent Strategy**: Simple pieces combine into complex, satisfying synergies
3. **Variable Rewards**: The dopamine hit of discovering powerful combos
4. **Short Sessions**: 30-45 minute runs encourage "one more game"
5. **Visible Progression**: Watch numbers go up in satisfying ways

### Applying to Farkle

Farkle already has the core "push your luck" tension. We enhance it by adding:

| Balatro Element | Farkle Translation |
|-----------------|-------------------|
| Joker cards | Special Dice with passive abilities |
| Chips × Mult scoring | Base Score × Multiplier formula |
| Ante/Blind structure | Floors with escalating point targets |
| Boss Blinds | Boss Rounds with rule modifications |
| Planet cards | Combo Training upgrades |
| Tarot cards | Consumable Fate Dice |
| Vouchers | Permanent run upgrades |
| Economy/Interest | Money system with interest cap |

### Key Design Principles

1. **Respect Farkle's DNA**: The core dice-rolling remains untouched
2. **Depth Through Systems**: Layers of strategy without complexity overload
3. **Every Run Feels Different**: Procedural shops, bosses, and dice selection
4. **Fail Forward**: Even failed runs provide progression and learning
5. **Skill Expression**: Good players should win more often, but luck still matters

---

## 2. The Scoring System

### Enhanced Formula

**Classic Farkle**: Additive scoring (100 + 50 + 500 = 650)

**Lucky Dice Mode**: **Base Score × Multiplier**

```
Final Score = (Base Points from Dice) × (1.0 + Multiplier Bonuses)
```

### Why This Matters

The multiplier system creates a "race to balance" - you need both base points AND multipliers. This opens design space for:

- Dice that boost base points
- Dice that boost multipliers
- Synergies between the two

### Scoring Reference (Base Points)

| Combination | Base Points |
|-------------|-------------|
| Single 1 | 100 |
| Single 5 | 50 |
| Three 1s | 1,000 |
| Three 2s | 200 |
| Three 3s | 300 |
| Three 4s | 400 |
| Three 5s | 500 |
| Three 6s | 600 |
| Four of a kind | 2× three of a kind |
| Five of a kind | 4× three of a kind |
| Six of a kind | 8× three of a kind |
| Straight (1-6) | 1,500 |
| Three Pairs | 1,000 |
| Two Triplets | 2,500 |

### Multiplier Display

When scoring, show the calculation dramatically:

```
┌─────────────────────────────────────┐
│  Three 5s (500)  ×  1.5x Mult      │
│                                     │
│         = 750 POINTS!               │
└─────────────────────────────────────┘
```

---

## 3. Run Structure

### Overview

A complete run consists of **8 Antes** (stages), each containing **3 Blinds** (rounds):

```
ANTE 1: "The Warm-Up"
├── Small Blind: 300 points (skippable)
├── Big Blind: 450 points (skippable)
└── Boss Blind: 600 points (required)
    └── SHOP ACCESS

ANTE 2: "Getting Serious" (1.3× difficulty)
├── Small Blind: 400 points
├── Big Blind: 600 points
└── Boss Blind: 800 points
    └── SHOP ACCESS

... continues through ANTE 8 ...

ANTE 8: "Final Showdown" (5× difficulty)
├── Small Blind: 3,000 points
├── Big Blind: 4,500 points
└── Boss Blind: 6,000 points (THE HOUSE)
    └── VICTORY or ENDLESS MODE
```

### Difficulty Scaling

| Ante | Multiplier | Small | Big | Boss |
|------|------------|-------|-----|------|
| 1 | 1.0× | 300 | 450 | 600 |
| 2 | 1.3× | 400 | 600 | 800 |
| 3 | 1.6× | 550 | 825 | 1,100 |
| 4 | 2.0× | 750 | 1,125 | 1,500 |
| 5 | 2.5× | 1,000 | 1,500 | 2,000 |
| 6 | 3.2× | 1,400 | 2,100 | 2,800 |
| 7 | 4.0× | 2,000 | 3,000 | 4,000 |
| 8 | 5.0× | 3,000 | 4,500 | 6,000 |

### Rolls Per Blind

| Blind Type | Rolls Allowed |
|------------|---------------|
| Small Blind | 4 rolls |
| Big Blind | 4 rolls |
| Boss Blind | 3 rolls |

### Skip Mechanics

- **Skip Small/Big Blind**: Gain a random Tag (modifier for next shop)
- **Cannot Skip Boss Blinds**: Must defeat to progress
- **Trade-off**: Skipping = no money reward, but valuable Tags

### Run Duration Targets

| Player Type | Expected Time | Typical Result |
|-------------|---------------|----------------|
| New Player | 20-25 min | Reach Ante 3-4 |
| Intermediate | 30-40 min | Reach Ante 5-7 |
| Experienced | 35-45 min | Complete run |

---

## 4. Special Dice System

### Overview

Special Dice are collectible dice with unique passive abilities that modify scoring. They're the core of build variety.

### Dice Slots

- **Start**: 5 Active Dice Slots
- **Maximum**: 8 slots (purchasable upgrades)
- Only dice in active slots provide effects

### Rarity Tiers

| Rarity | Border | Shop Rate | Power Level |
|--------|--------|-----------|-------------|
| Common | Silver | 60% | Basic effects |
| Uncommon | Green | 25% | Medium power |
| Rare | Blue | 12% | Build-defining |
| Legendary | Gold | 3% | Game-changing |

### Special Dice Catalog

#### Category A: Scoring Dice
*Modify base point values*

| Die | Rarity | Effect |
|-----|--------|--------|
| **Golden Ace** | Uncommon | 1s score 150 instead of 100 |
| **Platinum Five** | Uncommon | 5s score 75 instead of 50 |
| **Triple Crown** | Rare | Three-of-a-kind +200 bonus |
| **Straight Shooter** | Rare | Straights score 2,000 instead of 1,500 |

#### Category B: Multiplier Dice
*Add mult bonuses*

| Die | Rarity | Effect |
|-----|--------|--------|
| **Fire Die** | Common | +0.5× mult per 5 rolled |
| **Ice Die** | Common | +0.5× mult per 1 rolled |
| **Storm Die** | Uncommon | +1× mult if you roll 4+ of same number |
| **Perfectionist** | Rare | +2× mult if ALL dice score (no duds) |
| **Chaos Orb** | Legendary | +0.25× mult per die rolled |

#### Category C: Trigger Dice
*Activate on conditions*

| Die | Rarity | Effect |
|-----|--------|--------|
| **Lucky Seven** | Uncommon | Roll exactly 7 dice total: +500 bonus |
| **The Collector** | Rare | Score all 6 dice: +$25 and +1,000 points |
| **Farkle's Revenge** | Rare | After Farkle, next turn starts +3× mult |
| **Hot Streak** | Uncommon | 3+ scores without banking: +1× each after 2nd |

#### Category D: Combo Dice
*Synergize with other dice*

| Die | Rarity | Effect |
|-----|--------|--------|
| **Mirror Die** | Rare | Copy effect of highest-rarity die (non-Legendary) |
| **Amplifier Die** | Uncommon | All Scoring Dice effects +50% |
| **Chain Die** | Rare | 3+ Mult Dice active: all mult +0.5× |
| **The Twins** | Legendary | All effects trigger twice (uses 2 slots) |

#### Category E: Risk/Reward Dice
*High stakes*

| Die | Rarity | Effect |
|-----|--------|--------|
| **Cursed Die** | Rare | +3× mult, but Farkle loses 50% game score |
| **Gambler's Ruin** | Uncommon | Each roll: 50% +2× mult OR -1× mult |
| **All or Nothing** | Legendary | Final bank worth 2×, but Farkle loses turn +1,000 |
| **Glass Cannon** | Rare | +5× mult, but destroyed if you score <1,000 |

#### Category F: Economy Dice
*Generate money*

| Die | Rarity | Effect |
|-----|--------|--------|
| **Coin Die** | Common | $1 per 5 banked |
| **Banker's Die** | Uncommon | $5 when banking 1,000+ points |
| **Investment Die** | Rare | End of game: earn 10% of score as $ (max $100) |
| **Thief's Die** | Uncommon | Steal $10 when opponent Farkles (PvP) |

### Powerful Synergy Combos

**"The Inferno"**: Fire Die + Ice Die + Chaos Orb + Perfectionist
- Roll all 6, score all = 5-8× mult potential

**"The Economist"**: Coin Die + Banker's Die + Investment Die + Golden Ace
- Generate $50-150 per game

**"Double Trouble"**: The Twins + Mirror Die + Chain Die
- Exponential effect doubling

**"Phoenix Rising"**: Farkle's Revenge + Cursed Die + Glass Cannon
- High risk, massive comeback potential

---

## 5. Economy & Shop

### Currency: Chips

Chips are earned during runs and spent in the shop.

#### Earning Chips

| Source | Amount |
|--------|--------|
| Complete any round | $3 |
| Score bonus | $1 per 500 points |
| Farkle-free round | $2 bonus |
| Hot dice (all 6 score) | $1 bonus |
| Beat target by 50%+ | $3-5 bonus |
| Defeat Boss | $8 |

#### Interest System

```
Interest: $1 per $5 held (max $5 per round)
Optimal: Hold $25 = earn $5 interest

Example: End round with $17 → earn $3 interest
```

### Shop Structure

```
┌─────────────────────────────────────────────────┐
│  LUCKY'S DICE SHOP                 Chips: $47  │
├─────────────────────────────────────────────────┤
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐│
│  │ Fire   │  │ Coin   │  │ Re-Roll│  │ Shield ││
│  │ Die    │  │ Die    │  │ Token  │  │        ││
│  │  $6    │  │  $4    │  │  $3    │  │  $6    ││
│  └────────┘  └────────┘  └────────┘  └────────┘│
│                                                 │
│  [REROLL $2]                                    │
│                                                 │
│  ┌──────────────┐    ┌──────────────┐          │
│  │  DICE PACK   │    │   VOUCHER    │          │
│  │     $6       │    │    $10       │          │
│  └──────────────┘    └──────────────┘          │
└─────────────────────────────────────────────────┘
```

### Pricing by Rarity

| Rarity | Price Range |
|--------|-------------|
| Common | $3-5 |
| Uncommon | $6-8 |
| Rare | $10-14 |
| Legendary | $16-22 |

### Reroll Mechanics

- Base cost: $2
- +$1 per reroll in same shop
- Resets at next shop

### Pack Types

| Pack | Cost | Contents |
|------|------|----------|
| **Dice Pack** | $6 | Choose 1 of 3 dice (70% Common, 25% Uncommon, 5% Rare) |
| **Weighted Pack** | $10 | Choose 1 of 3 (40% Uncommon, 45% Rare, 15% Legendary) |
| **Modifier Pack** | $5 | Choose 1 of 4 passive modifiers |
| **Consumable Pack** | $4 | Choose 2 of 5 one-time items |
| **Gambler's Pack** | $8 | 1 random item, any type, any rarity |
| **Chaos Pack** | $7 | 3 items, but 1 is cursed (with upside) |

---

## 6. Boss Blinds

Boss Blinds add special rules that force adaptation.

### Tier 1 Bosses (Antes 1-3)

| Boss | Effect | Difficulty |
|------|--------|------------|
| **The Miser** | Must score 500+ before banking | Easy |
| **The Curse** | 1s don't score, count toward Farkle | Easy-Medium |
| **The Fog** | Current score hidden until banking | Easy |
| **The Glutton** | Must keep ALL scoring dice (no choice) | Medium |

### Tier 2 Bosses (Antes 4-5)

| Boss | Effect | Difficulty |
|------|--------|------------|
| **The Mirror** | Target = your highest banked score this run | Medium |
| **The Thief** | Lose $3 every time you bank | Medium |
| **The Gambler** | Must bet $1-5 per roll (lose on Farkle, 2× on score) | Medium-Hard |
| **The Taxman** | 20% of all points deducted | Medium |

### Tier 3 Bosses (Antes 6-7)

| Boss | Effect | Difficulty |
|------|--------|------------|
| **The Chaos Lord** | Dice values randomize AFTER you select keeps | Hard |
| **The Executioner** | 3 Farkles total = instant loss | Hard |
| **The Vampire** | Each score increases requirement by 10% of points | Hard |
| **The Twins** | Combines TWO Tier 1 boss effects | Hard |

### Final Boss (Ante 8)

**THE HOUSE** - *"The game was rigged from the start"*

| Phase | Trigger | Effect |
|-------|---------|--------|
| 1 | Start | All Tier 2 effects active |
| 2 | 50% score | Adds one Tier 3 effect |
| 3 | 80% score | All dice abilities disabled |

---

## 7. Consumables & Upgrades

### Consumables (One-Time Use)

#### Common ($2-4)

| Item | Effect |
|------|--------|
| **Re-Roll Token** | Re-roll any held dice once free |
| **Lucky Penny** | Next roll: all 1s become 5s |
| **Score Chip** | Add +100 to banked score |
| **Second Wind** | After Farkle, recover 2 random dice |

#### Uncommon ($5-8)

| Item | Effect |
|------|--------|
| **Farkle Shield** | Prevent next Farkle (dice stay) |
| **Score Doubler** | Double next scoring combination |
| **Cash Out** | Convert banked score to $ (100 pts = $1) |
| **Time Warp** | Undo last roll |

#### Rare ($9-12)

| Item | Effect |
|------|--------|
| **Golden Roll** | Next roll: all scoring dice give 2× |
| **Loaded Dice** | Choose one die's result |
| **Resurrection** | On Farkle out, keep half banked score |

### Vouchers (Permanent Run Upgrades)

Expensive, powerful upgrades that change gameplay.

| Voucher | Effect | Cost |
|---------|--------|------|
| **Clearance Sale** | Rerolls cost $1 | $10 |
| **Investor** | Interest: $1 per $4 (max $6) | $12 |
| **Seed Money** | +$2 at start of each round | $10 |
| **Seventh Heaven** | +1 die slot (7 total) | $16 |
| **Safe Keeper** | Banking doesn't end turn (1×/round) | $14 |
| **Lucky Streak** | Consecutive scores give +25 each | $12 |

### Combo Training (Permanent Upgrades)

Like Balatro's Planet cards - permanently upgrade specific combinations.

| Training | Effect (Stacks) |
|----------|-----------------|
| **Triple Training** | Three-of-a-kind +50 base |
| **Straight Practice** | Straights +200 base |
| **Pair Mastery** | Three-pairs +100 base |
| **Ace Affinity** | 1s score 120 instead of 100 |

---

## 8. Challenge Modes

Unlocked after first complete run.

### The Minimalist
*Max 3 dice slots*

- All dice guaranteed Rare+
- Dice cost 50% less
- **Unlock**: "Minimalist" card back

### Broke and Proud
*Spend $0 in shops*

- Free booster pack each ante
- Double boss money
- **Unlock**: "Vagrant" special die

### Speed Demon
*Complete run in 20 minutes*

- 10 seconds per roll decision
- 30 seconds per shop visit
- Requirements reduced 20%
- **Unlock**: "Speedster" die, leaderboard entry

### One Die Wonder
*Start each roll with 1 die*

- Each score adds +1 die for next roll
- Farkle resets to 1 die
- Requirements reduced 40%
- **Unlock**: "Lucky Penny" die

### No Safety Net
*One Farkle ends run*

- Start with 2 anti-farkle dice
- +1 roll per blind
- Requirements reduced 25%
- **Unlock**: "Perfectionist" achievement

### The Gauntlet
*Face every boss in sequence*

- 12 rounds, each is a Boss
- Shops after every 3 bosses
- **Unlock**: "Champion" title, golden dice skin

---

## 9. Daily & Weekly Events

### Daily Challenge

- New seeded run every 24 hours
- Same dice rolls for all players
- Modified rules each day
- Global leaderboard

| Day | Modifier |
|-----|----------|
| Monday | "Manic Monday" - Scores and requirements 2× |
| Tuesday | "Two-Face" - Only 2s and 5s score |
| Wednesday | "Wild" - Random die added each round |
| Thursday | "Thrifty" - Shop prices 2× |
| Friday | "Farkle Friday" - Farkle on first roll = $10 bonus |
| Saturday | "Stacked" - Start with 5 random dice |
| Sunday | "Funday" - All boss effects at 50% |

### Daily Rewards

| Placement | Reward |
|-----------|--------|
| Participation | 5 Coins + 1 Ticket |
| Top 50% | 15 Coins + 2 Tickets |
| Top 10% | 30 Coins + Rare Pack |
| Top 1% | 50 Coins + Legendary Pack |
| #1 | 100 Coins + Daily Crown |

### Weekly Challenge

- Friday 00:00 - Sunday 23:59 UTC
- Longer, complex challenges
- Best score of multiple attempts counts

Types:
- **Marathon**: 3 runs back-to-back, cumulative score
- **Specialist**: Pre-set dice loadout
- **Boss Rush**: Continuous bosses, minimal shops
- **Collector**: Bonus for unique dice collected

---

## 10. Endless Mode

After defeating The House (Ante 8), choose to continue into Endless Mode.

### Scaling

```
Ante 9+:  Base × 1.15^(Ante - 8)

Ante 9:  3,600 / 5,400 / 7,200
Ante 10: 4,300 / 6,450 / 8,600
Ante 12: 6,200 / 9,300 / 12,400
Ante 15: 10,000 / 15,000 / 20,000
Ante 20: 22,000 / 33,000 / 44,000
```

### Endless Modifiers

| Ante Range | New Mechanic |
|------------|--------------|
| 9-10 | Nightmare Bosses (enhanced effects) |
| 11-12 | Double boss effects |
| 13-15 | Random die disabled each ante |
| 16-20 | Lose 1 die slot per ante |
| 21+ | All modifiers active |

### Abyssal Bosses (Endless Only)

| Boss | Effect |
|------|--------|
| **The Void** | 50% of dice disappear each roll |
| **The Paradox** | Scoring dice SUBTRACT points |
| **The Entropy** | Die effects randomly swap |
| **The Absolute** | Requirement increases in real-time |

### Endless Leaderboard

```
Score = (Highest Ante × 1000) + (Total Points / 100) + (Bosses × 50)
```

---

## 11. Meta-Progression

### Between-Run Currency: Stars

Stars persist forever and unlock content.

#### Earning Stars

| Achievement | Stars |
|-------------|-------|
| Complete Ante 1 | 1 |
| Complete Ante 2 | 2 |
| Complete Ante 3 | 3 |
| Beat the game | 5 |
| Farkle-free run | 4 |
| High score milestones | 1-5 |

#### Star Spending

```
┌─────────────────────────────────────────────┐
│  UNLOCK SHOP                   Stars: 47   │
├─────────────────────────────────────────────┤
│  DICE UNLOCKS                               │
│  ├─ Lucky Seven Die (5 Stars)              │
│  ├─ Chaos Die (10 Stars)                   │
│  └─ Golden Die (25 Stars)                  │
│                                             │
│  STARTING BONUSES                           │
│  ├─ Start with +$2 (3 Stars)               │
│  ├─ Start with +$5 (10 Stars)              │
│  └─ Start with consumable (12 Stars)       │
│                                             │
│  CHALLENGES                                 │
│  ├─ No Interest Mode (5 Stars)             │
│  └─ Boss Rush Mode (20 Stars)              │
└─────────────────────────────────────────────┘
```

### Unlockable Content

| Category | Unlock Method |
|----------|---------------|
| New Dice | Star purchases |
| Modifiers | Star purchases |
| Vouchers | Beat higher difficulties |
| Cosmetics | Achievements |
| Challenge Modes | Star purchases |
| Starting Decks | Beat with specific conditions |

### Achievements → Cosmetics

| Achievement | Condition | Reward |
|-------------|-----------|--------|
| **Hot Dice** | 5,000+ in single round | Flame dice skin |
| **Close Call** | Win with 0 pts to spare | Sweaty table theme |
| **Minimalist** | Beat with 4 dice | Minimalist starting deck |
| **Collector** | Discover 50 items | Museum background |
| **Whale** | Hold $50+ | Golden table theme |

---

## 12. Visual & Audio Design

### Rarity Visual Language

| Rarity | Border | Background | Animation |
|--------|--------|------------|-----------|
| Common | Thin silver | Matte white | Subtle pulse |
| Uncommon | Double green | Green tint | Gentle glow |
| Rare | Thick blue + inner glow | Blue gradient | Sparkle particles |
| Legendary | Animated gold flames | Dynamic gradient | Constant particles + shake |

### Category Themes

| Category | Colors | Effects | Sound |
|----------|--------|---------|-------|
| Scoring | Gold/silver | Shimmer, coin-flip | "Cha-ching" |
| Multiplier | Fire/ice/storm | Element particles | Whoosh |
| Trigger | Green/teal | Connection lines | Slot machine ding |
| Combo | Rainbow/chrome | Lines between synergies | Harmonic chord |
| Risk | Black/red | Ominous aura, cracks | Heartbeat |
| Economy | Green/gold | Floating coins | Register sound |

### Scoring Animation (Critical!)

Balatro's magic is the **satisfying score reveal**. Each element appears sequentially:

```
Step 1: Base dice score appears     "500"
Step 2: Multiplier builds           "× 1.5"
Step 3: Final score with fanfare    "= 750!"
Step 4: Combo bonuses pop up        "+200 (Triple Crown)"
Step 5: Running total updates       "Bank: 2,450"
```

Each step has:
- Distinct sound effect
- Screen shake for big numbers
- Particle effects for multipliers
- Color pulses matching dice involved

### Audio Design Principles

1. **Escalating tension**: Roll sounds get more intense with consecutive scores
2. **Satisfying clicks**: Each dice kept makes a distinct "lock" sound
3. **Victory fanfares**: Scale with point value
4. **Danger tones**: Warn when approaching Farkle risk
5. **Shop ambiance**: Relaxed, rewarding atmosphere

---

## 13. Implementation Phases

### Phase 1: Core Roguelike (Foundation)
**Estimated Scope: Large**

- [ ] Ante/Blind structure (8 antes, 3 blinds each)
- [ ] Basic point targets and scaling
- [ ] Run-based progression (restart on failure)
- [ ] 4 Tier 1 Boss Blinds
- [ ] Simple shop (buy dice directly)
- [ ] Basic money + interest system
- [ ] Win/loss conditions

### Phase 2: Special Dice (Core Feature)
**Estimated Scope: Large**

- [ ] Dice slot system (5 slots)
- [ ] 12 Common/Uncommon dice
- [ ] Rarity visual system
- [ ] Dice effects engine
- [ ] Shop dice display
- [ ] Selling mechanic

### Phase 3: Economy Expansion
**Estimated Scope: Medium**

- [ ] Pack system (4 pack types)
- [ ] Shop reroll mechanics
- [ ] 6 Rare dice
- [ ] 4 Legendary dice
- [ ] Consumable items (10+)

### Phase 4: Boss Variety
**Estimated Scope: Medium**

- [ ] 4 Tier 2 Bosses
- [ ] 4 Tier 3 Bosses
- [ ] Final Boss (The House)
- [ ] Boss effect engine
- [ ] Boss intro animations

### Phase 5: Depth Systems
**Estimated Scope: Medium**

- [ ] Voucher system
- [ ] Combo Training upgrades
- [ ] Tags (skip rewards)
- [ ] Synergy bonus indicators
- [ ] 5+ more dice

### Phase 6: Challenge & Events
**Estimated Scope: Medium**

- [ ] 6 Challenge Modes
- [ ] Daily Challenge system
- [ ] Seeded runs
- [ ] Weekly Challenge system
- [ ] Event leaderboards

### Phase 7: Endless & Meta
**Estimated Scope: Medium**

- [ ] Endless Mode
- [ ] Star currency system
- [ ] Unlock shop
- [ ] Achievement system
- [ ] Cosmetic unlocks

### Phase 8: Polish & Addiction
**Estimated Scope: Large**

- [ ] Dramatic scoring animations
- [ ] Full sound design
- [ ] Particle effects
- [ ] Screen shake/juice
- [ ] Tutorial/onboarding
- [ ] Balance tuning

---

## Appendix A: Balance Parameters

```javascript
const BALANCE_CONFIG = {
  // Run Structure
  BASE_ANTE_COUNT: 8,
  ANTE_DIFFICULTY_SCALING: 1.3,

  // Blinds
  SMALL_BLIND_BASE: 300,
  BIG_BLIND_BASE: 450,
  BOSS_BLIND_BASE: 600,
  ROLLS_PER_BLIND: 4,
  BOSS_ROLLS: 3,

  // Economy
  SMALL_BLIND_REWARD: 3,
  BIG_BLIND_REWARD: 4,
  BOSS_BLIND_REWARD: 5,
  INTEREST_RATE: 0.2,  // $1 per $5
  INTEREST_CAP: 5,

  // Shop
  REROLL_BASE_COST: 2,
  REROLL_ESCALATION: 1,

  // Dice Slots
  STARTING_SLOTS: 5,
  MAX_SLOTS: 8,
  SLOT_COST_FORMULA: "500 * 2^(slot - 5)",

  // Endless
  ENDLESS_SCALING: 1.15,  // per ante after 8

  // Session Targets
  TARGET_RUN_MINUTES: 40,
  TARGET_ANTE_MINUTES: 5,
};
```

---

## Appendix B: Dice Quick Reference

### Scoring Dice
| Die | Rarity | Effect |
|-----|--------|--------|
| Golden Ace | U | 1s = 150 pts |
| Platinum Five | U | 5s = 75 pts |
| Triple Crown | R | 3-of-kind +200 |
| Straight Shooter | R | Straights = 2,000 |

### Multiplier Dice
| Die | Rarity | Effect |
|-----|--------|--------|
| Fire Die | C | +0.5× per 5 |
| Ice Die | C | +0.5× per 1 |
| Storm Die | U | +1× on 4+ same |
| Perfectionist | R | +2× all score |
| Chaos Orb | L | +0.25× per die |

### Trigger Dice
| Die | Rarity | Effect |
|-----|--------|--------|
| Lucky Seven | U | 7 dice = +500 |
| The Collector | R | All 6 = +$25, +1000 |
| Farkle's Revenge | R | Post-Farkle +3× |
| Hot Streak | U | 3+ scores +1× each |

### Economy Dice
| Die | Rarity | Effect |
|-----|--------|--------|
| Coin Die | C | $1 per 5 banked |
| Banker's Die | U | $5 on 1000+ bank |
| Investment Die | R | 10% score → $ |

---

## Appendix C: Boss Quick Reference

### Tier 1 (Antes 1-3)
- **The Miser**: Bank minimum 500
- **The Curse**: 1s don't score
- **The Fog**: Score hidden
- **The Glutton**: Must keep all scoring

### Tier 2 (Antes 4-5)
- **The Mirror**: Target = your best score
- **The Thief**: -$3 per bank
- **The Gambler**: Bet on each roll
- **The Taxman**: -20% all points

### Tier 3 (Antes 6-7)
- **The Chaos Lord**: Dice randomize after keep
- **The Executioner**: 3 Farkles = loss
- **The Vampire**: Requirement grows
- **The Twins**: Two Tier 1 effects

### Final Boss
- **The House**: All Tier 2 → add Tier 3 → disable dice

---

*Document Version: 1.0*
*Inspired by: Balatro, Farkle, Yahtzee, Dicey Dungeons*
*Target Platform: Web (Farkle Ten 2026)*
