# Farkle Fortune - Enhanced Solo Mode

> *A deep, replayable single-player experience that expands on classic Farkle*

## Executive Summary

Farkle Fortune transforms the classic push-your-luck dice game into a rich solo experience with lasting progression. Rather than copying other roguelikes, this design builds naturally from what makes Farkle exciting: the tension of each roll, the thrill of hot dice, and the agony of the Farkle.

**Core Additions:**
- **Special Dice** with unique faces and behaviors
- **The Gauntlet** - 20 escalating challenges with rule modifiers
- **Fortune Economy** - earn and spend currency with strategic depth
- **Meta-Progression** - unlock content across runs

**Design Philosophy:** Enhance Farkle, don't replace it. Every new system amplifies the core push-your-luck tension.

---

## Table of Contents

1. [The Gauntlet: Challenge Structure](#1-the-gauntlet-challenge-structure)
2. [Challenge Modifiers](#2-challenge-modifiers)
3. [Fortune Economy](#3-fortune-economy)
4. [Special Dice](#4-special-dice)
5. [Consumables](#5-consumables)
6. [Permanent Upgrades](#6-permanent-upgrades)
7. [The Shop](#7-the-shop)
8. [Meta-Progression](#8-meta-progression)
9. [Daily & Weekly Challenges](#9-daily--weekly-challenges)
10. [Endless Mode](#10-endless-mode)
11. [Achievements & Leaderboards](#11-achievements--leaderboards)
12. [Session Flow Example](#12-session-flow-example)
13. [Implementation Phases](#13-implementation-phases)

---

## 1. The Gauntlet: Challenge Structure

### Overview

A complete run consists of **20 Challenges**, each with:
- **Target Score** - Points needed to advance
- **Turn Limit** - How many turns to reach the target
- **Modifier** - Optional rule that changes gameplay (introduced after Challenge 5)

### Challenge Progression

| Challenge | Target | Turns | Modifier | Reward |
|-----------|--------|-------|----------|--------|
| 1 | 1,000 | 4 | None | 5 |
| 2 | 1,200 | 4 | None | 5 |
| 3 | 1,500 | 4 | None | 6 |
| 4 | 1,800 | 4 | None | 6 |
| 5 | 2,000 | 4 | None | 7 |
| 6 | 2,300 | 4 | Cold Fives | 8 |
| 7 | 2,600 | 4 | Minimum Bank | 8 |
| 8 | 3,000 | 3 | No Singles | 9 |
| 9 | 3,500 | 3 | Random | 10 |
| 10 | 4,000 | 3 | **Double Modifier** | 12 |
| 11 | 4,500 | 3 | Decay | 10 |
| 12 | 5,000 | 3 | Farkle Limit | 11 |
| 13 | 5,500 | 3 | Random | 12 |
| 14 | 6,000 | 3 | The Squeeze | 13 |
| 15 | 7,000 | 3 | **Double Modifier** | 15 |
| 16 | 8,000 | 2 | All or Nothing | 14 |
| 17 | 9,000 | 2 | Random | 15 |
| 18 | 10,000 | 2 | Countdown | 16 |
| 19 | 12,000 | 2 | Random | 18 |
| 20 | 15,000 | 2 | **Triple Modifier** | 25 |

### Difficulty Zones

```
Challenges 1-5:   "Learning" - No modifiers, generous turns
Challenges 6-10:  "Building" - Single modifiers introduced
Challenges 11-15: "Testing" - Tighter margins, harder modifiers
Challenges 16-20: "Mastery" - Only 2 turns, combined modifiers
```

### Win & Loss Conditions

**Win:** Complete Challenge 20

**Lose:**
- Fail to reach target score within turn limit
- Some modifiers add additional loss conditions (e.g., Farkle Limit)

### Session Length

- **Target:** 30-45 minutes for a full run
- **Per Challenge:** ~2 minutes average
- **Shop Visits:** ~1 minute each

---

## 2. Challenge Modifiers

Modifiers change the rules for a single challenge, creating variety and testing different skills.

### Scoring Modifiers

| Modifier | Effect |
|----------|--------|
| **Cold Ones** | 1s score 0 points (but still count for combos) |
| **Cold Fives** | 5s score 0 points (but still count for combos) |
| **No Singles** | Loose 1s and 5s don't score; only combos count |
| **Combo Only** | Three-of-a-kind minimum to score anything |
| **Reversed** | 6s score like 1s (100), 1s score like 6s (in combos) |

### Banking Modifiers

| Modifier | Effect |
|----------|--------|
| **Minimum Bank** | Cannot bank less than 500 points |
| **The Squeeze** | Must bank at least 300 every turn |
| **All or Nothing** | Must hit full target in a single turn |
| **Greedy** | Banking ends your entire round (not just turn) |
| **Taxed** | 20% of banked points are lost |

### Risk Modifiers

| Modifier | Effect |
|----------|--------|
| **Farkle Limit** | 2 Farkles = instant challenge failure |
| **One Chance** | First Farkle ends the challenge |
| **Decay** | Target increases by 200 for each turn you don't bank |
| **Countdown** | Target decreases by 100 per successful roll (minimum 50%) |
| **Pressure** | 30-second timer per roll decision |

### Dice Modifiers

| Modifier | Effect |
|----------|--------|
| **Five Dice** | Play with only 5 dice instead of 6 |
| **Sticky** | Once you keep a die, you cannot reroll it |
| **Blind Roll** | Dice results hidden until you commit to keep/reroll |
| **Chaos** | After each roll, one random die changes value |
| **Cursed Die** | One die shows skulls on 2 faces (instant turn Farkle) |

### Combined Modifiers (Challenges 10, 15, 20)

| Challenge | Combination |
|-----------|-------------|
| 10 | Cold Ones + Minimum Bank |
| 15 | No Singles + Farkle Limit |
| 20 | Decay + The Squeeze + Five Dice |

---

## 3. Fortune Economy

### Currency: Fortune

**Fortune** (₣) is the currency of luck. Represented by a golden die pip.

### Earning Fortune

Fortune is earned through skillful and risky play:

| Action | Fortune Earned |
|--------|----------------|
| Bank 1,000+ points | +1₣ |
| Bank 2,000+ points | +2₣ |
| Bank 3,000+ points | +4₣ |
| Hot Dice (all 6 score, roll again) | +5₣ |
| Risk Roll (continue with 1-2 dice) | +3₣ |
| Streak (3+ rolls without banking) | +1₣ per roll after 2nd |
| Three-of-a-kind or better | +1₣ |
| Four/Five/Six-of-a-kind | +2₣/+3₣/+5₣ |
| Full Straight (1-2-3-4-5-6) | +4₣ |

### Farkle Penalty

**Farkle does NOT cost Fortune.** Losing your turn's points is punishment enough. This prevents death spirals and keeps the game feeling fair.

### Fortune's Favor (Saving Bonus)

Your saved Fortune provides **passive gameplay bonuses**. This creates tension between spending and saving.

| Fortune Held | Tier | Bonus |
|--------------|------|-------|
| 10-19₣ | Lucky | 5% of Farkles become "near misses" (reroll 1 die free) |
| 20-29₣ | Fortunate | All scoring combinations worth +10% |
| 30-39₣ | Blessed | First roll each turn scores +100 guaranteed minimum |
| 40+₣ | Charmed | All above bonuses + Hot Dice awards +8₣ instead of +5₣ |

### The Spending Decision

```
Example: You have 25₣ (Fortunate tier, +10% scoring)

Shop offers:
  - Echo Die (18₣) - powerful, would synergize with your build
  - Reroll Token (3₣) - useful consumable

Options:
  A) Buy both (4₣ left) → Lose ALL tier bonuses
  B) Buy only die (7₣ left) → Drop to no bonus
  C) Buy only token (22₣ left) → Keep Fortunate +10%
  D) Buy nothing → Maximize scoring bonus

There's no obviously correct answer.
```

### Starting Fortune

- **New Run:** 10₣
- **With Upgrades:** Up to 20₣ (via meta-progression unlocks)

---

## 4. Special Dice

### Design Philosophy

Special Dice aren't passive buffs—they're **actual dice with modified faces and behaviors**. They change how you roll and keep, creating new decisions each turn.

### Dice Slots

- **Starting Slots:** 3
- **Maximum Slots:** 6 (expandable via upgrades)
- **Normal Dice:** Always have 6 total dice (special + normal = 6)

### Rarity & Pricing

| Rarity | Shop Price | Border Color |
|--------|------------|--------------|
| Common | 5-10₣ | Silver |
| Uncommon | 12-18₣ | Green |
| Rare | 20-30₣ | Blue |

### Special Dice Catalog

#### Common Dice (5-10₣)

| Die | Faces | Behavior |
|-----|-------|----------|
| **Lucky Die** | 1,1,5,5,5,5 | Harder to Farkle, but no combo potential |
| **Loaded Die** | Normal | After rolling, nudge result ±1 (6↔1 wraps) |
| **Streak Die** | Normal | +50₣ bonus points for each consecutive roll you keep it |
| **Banker's Die** | Normal | +1₣ every time you bank while this die scored |

#### Uncommon Dice (12-18₣)

| Die | Faces | Behavior |
|-----|-------|----------|
| **Echo Die** | 1,2,3,4,5,⟲ | Mirror face (⟲) copies any other die you rolled |
| **Phantom Die** | Normal | Once per turn, "unroll" it to remove a bad result |
| **Magnetic Die** | Normal | Must keep with any die showing same number (pairs stick) |
| **Ember Die** | Normal | Builds +50 points each roll you keep it; resets if rerolled |
| **Insurance Die** | Normal | If you Farkle, this die's last value still scores |

#### Rare Dice (20-30₣)

| Die | Faces | Behavior |
|-----|-------|----------|
| **Splitting Die** | 1\|2, 2\|3, 3\|4, 4\|5, 5\|6, 6\|1 | Each face counts as BOTH numbers for combos |
| **Volatile Die** | ☠,☠,1,5,5,★ | Skull = instant turn Farkle. Star = wild (any number) |
| **Last Stand Die** | Normal | When rolled alone (1 die left), scores triple value |
| **Phoenix Die** | Normal | One-time: sacrifice permanently to cancel a Farkle |
| **Golden Die** | 1,1,1,5,5,5 | All faces score; nearly impossible to contribute to Farkle |
| **Chaos Die** | ?,?,?,?,?,? | Faces randomize each roll (shown after rolling) |

### Dice Synergies

Good builds combine dice that work together:

**"The Safe Bet"**: Lucky Die + Golden Die + Insurance Die
- Extremely hard to Farkle, consistent small scores

**"Risk Taker"**: Volatile Die + Last Stand Die + Phantom Die
- High variance, huge potential, Phantom saves bad skulls

**"Combo Hunter"**: Splitting Die + Echo Die + Magnetic Die
- Easier to form three/four-of-a-kinds

**"The Snowball"**: Ember Die + Streak Die + Banker's Die
- Rewards long turns without banking

---

## 5. Consumables

Single-use items purchased in the shop and used during play.

### Consumable Slots

- **Starting:** 3 slots
- **Maximum:** 5 slots (via upgrade)

### Consumable List

| Item | Cost | Effect |
|------|------|--------|
| **Reroll Token** | 3₣ | Reroll up to 2 kept dice once this turn |
| **Wild Pip** | 4₣ | Declare one die as any number (1-6) for scoring |
| **Safety Net** | 5₣ | If you Farkle this turn, keep 50% of turn points |
| **Lucky Seven** | 6₣ | Add a 7th die for one complete turn |
| **Second Chance** | 8₣ | After a Farkle, reroll all 6 dice once |
| **Score Shield** | 4₣ | Next Farkle only loses turn points, not round points |
| **Quick Bank** | 3₣ | Bank current points without ending your turn |
| **Double Down** | 6₣ | Next scoring combination worth 2× |
| **Modifier Skip** | 10₣ | Ignore the current challenge's modifier |

### Using Consumables

- Use **before rolling** (Lucky Seven, Modifier Skip)
- Use **after rolling** (Wild Pip, Reroll Token)
- Use **on Farkle** (Safety Net, Second Chance)
- Use **when banking** (Quick Bank, Double Down)

---

## 6. Permanent Upgrades

Lasting improvements purchased in Full Shops that persist for the entire run.

### Scoring Upgrades

| Upgrade | Cost | Effect |
|---------|------|--------|
| **Ace Mastery** | 15₣ | 1s score 150 instead of 100 |
| **Five Mastery** | 15₣ | 5s score 75 instead of 50 |
| **Triple Bonus** | 18₣ | Three-of-a-kind scores +100 base |
| **Straight Bonus** | 20₣ | Straights score 2,000 instead of 1,500 |
| **Hot Dice Bonus** | 15₣ | Hot Dice awards +200 bonus points |

### Economy Upgrades

| Upgrade | Cost | Effect |
|---------|------|--------|
| **Deep Pockets** | 12₣ | Hold 5 consumables instead of 3 |
| **Fortune Finder** | 20₣ | +1₣ on all Fortune earnings |
| **Bargain Hunter** | 15₣ | Shop prices reduced 15% |
| **Lucky Start** | 18₣ | Begin each challenge with 100 free points |

### Dice Upgrades

| Upgrade | Cost | Effect |
|---------|------|--------|
| **Extra Slot** | 25₣ | +1 special die slot (max 6) |
| **Die Synergy** | 20₣ | Special dice effects are 25% stronger |
| **Quick Hands** | 15₣ | May swap one special die between challenges |

### Safety Upgrades

| Upgrade | Cost | Effect |
|---------|------|--------|
| **Farkle Insurance** | 22₣ | First Farkle each challenge loses only 50% |
| **Lucky Break** | 18₣ | 10% chance to "save" a Farkle (auto-reroll) |
| **Steady Hand** | 20₣ | +1 turn limit on all challenges |

---

## 7. The Shop

### Shop Types

| Type | Appears | Contents |
|------|---------|----------|
| **Quick Shop** | After challenges 1,2,4,5,7,8,etc. | 2 consumables, 1 die |
| **Full Shop** | After challenges 3,6,9,12,15,18 | 3 dice, 4 consumables, 2 upgrades |

### Shop Layout

```
┌─────────────────────────────────────────────────────────┐
│  FORTUNE'S SHOP                          Fortune: 32₣  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  SPECIAL DICE                                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │ Lucky    │  │ Echo     │  │ Phantom  │              │
│  │ Die      │  │ Die      │  │ Die      │              │
│  │   8₣     │  │  15₣     │  │  14₣     │              │
│  │ [Common] │  │[Uncommon]│  │[Uncommon]│              │
│  └──────────┘  └──────────┘  └──────────┘              │
│                                                         │
│  CONSUMABLES                                            │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐          │
│  │ Reroll │ │ Safety │ │ Wild   │ │ Lucky  │          │
│  │ Token  │ │ Net    │ │ Pip    │ │ Seven  │          │
│  │  3₣    │ │  5₣    │ │  4₣    │ │  6₣    │          │
│  └────────┘ └────────┘ └────────┘ └────────┘          │
│                                                         │
│  UPGRADES                                               │
│  ┌───────────────┐  ┌───────────────┐                  │
│  │ Ace Mastery   │  │ Extra Slot    │                  │
│  │ 1s → 150 pts  │  │ +1 die slot   │                  │
│  │     15₣       │  │     25₣       │                  │
│  └───────────────┘  └───────────────┘                  │
│                                                         │
│  [REROLL 2₣]                     [CONTINUE →]          │
└─────────────────────────────────────────────────────────┘
```

### Shop Actions

| Action | Cost | Effect |
|--------|------|--------|
| **Reroll** | 2₣ (+1₣ each time) | Randomize all unpurchased items |
| **Sell Die** | — | Sell owned die for 40% of purchase price |
| **Skip** | Free | Continue without buying |

### Shop Pacing

- **Quick Shops:** Fast decisions, 30 seconds average
- **Full Shops:** Strategic planning, 1-2 minutes
- **No shop mid-challenge:** Clean gameplay flow

---

## 8. Meta-Progression

### Stars (Permanent Currency)

**Stars** (★) are earned across all runs and unlock permanent content.

### Earning Stars

| Achievement | Stars |
|-------------|-------|
| Complete Challenge 5 | 1★ |
| Complete Challenge 10 | 2★ |
| Complete Challenge 15 | 3★ |
| Complete Challenge 20 (win) | 5★ |
| First win ever | 10★ bonus |
| No-Farkle run (any length) | 2★ |
| Earn 50₣ in single run | 1★ |
| Earn 100₣ in single run | 2★ |

### Unlockable Content

#### New Special Dice (unlocked for all future runs)

| Die | Star Cost | Effect |
|-----|-----------|--------|
| **Starter's Luck Die** | 5★ | Common die available from Challenge 1 shop |
| **Mirror Die** | 15★ | Rare die that copies another special die's effect |
| **Loaded Golden** | 25★ | Rare die: 1,1,5,5,★,★ (two wilds) |
| **Quantum Die** | 40★ | Legendary: exists in superposition until observed |

#### Starting Bonuses

| Bonus | Star Cost | Effect |
|-------|-----------|--------|
| **Head Start I** | 10★ | Start runs with 15₣ instead of 10₣ |
| **Head Start II** | 25★ | Start runs with 20₣ |
| **Packed Bag** | 15★ | Start with 1 random consumable |
| **Apprentice Slot** | 30★ | Start with 4 die slots instead of 3 |

#### Challenge Modifiers (new modifiers added to pool)

| Modifier | Star Cost | Effect |
|----------|-----------|--------|
| **Lucky Sevens** | 20★ | Adds "rolling exactly 7 total dice = +500" modifier |
| **Jackpot** | 35★ | Adds "triple or nothing" high-risk modifier |

### Prestige System

After winning 5 runs, unlock **Prestige Mode**:
- Start from Challenge 1 with harder scaling
- All targets increased 25%
- Earn 2× Stars
- Exclusive Prestige dice unlocks

---

## 9. Daily & Weekly Challenges

### Daily Challenge

- **Resets:** Every 24 hours at midnight UTC
- **Format:** Fixed seed—everyone gets same dice rolls
- **Length:** 10 challenges (shortened run)
- **Modifier:** Random daily theme

| Day | Theme |
|-----|-------|
| Monday | "Manic Monday" - All targets and scores 2× |
| Tuesday | "Two-sday" - Only 2s and pairs score |
| Wednesday | "Wild Card" - Random modifier every challenge |
| Thursday | "Thrifty" - Shop prices doubled |
| Friday | "Farkle Friday" - Farkle gives +5₣ consolation |
| Saturday | "Stacked" - Start with 3 random special dice |
| Sunday | "Sunday Funday" - All modifiers at 50% strength |

### Daily Leaderboard

| Placement | Reward |
|-----------|--------|
| Participation | 1★ |
| Top 50% | 2★ |
| Top 10% | 3★ + Consumable Pack |
| Top 1% | 5★ + Rare Die |
| #1 | 10★ + Daily Crown badge |

### Weekly Challenge

- **Runs:** Friday 00:00 - Sunday 23:59 UTC
- **Format:** Best score across unlimited attempts
- **Special Rules:** Unique weekly modifiers

Weekly Types:
- **Marathon:** Complete 30 challenges, cumulative score
- **Speedrun:** Finish 20 challenges fastest
- **Efficiency:** Highest score with lowest Fortune spent
- **Minimalist:** Win with max 2 special dice

---

## 10. Endless Mode

### Unlocking Endless

Complete Challenge 20 to unlock Endless Mode for that run.

### Endless Scaling

| Challenge | Target | Turns | Notes |
|-----------|--------|-------|-------|
| 21 | 18,000 | 2 | Double modifier |
| 22 | 21,000 | 2 | |
| 23 | 25,000 | 2 | |
| 24 | 30,000 | 2 | |
| 25 | 35,000 | 2 | Triple modifier |
| 26+ | +5,000 each | 2 | Random extreme modifiers |

### Endless-Only Modifiers

| Modifier | Effect |
|----------|--------|
| **Entropy** | Each roll, one random die is removed from play |
| **Reversal** | Higher numbers score less than lower numbers |
| **Sudden Death** | Any Farkle ends the run |
| **The Squeeze 2.0** | Must bank exactly 500-600 per turn |
| **Chaos Storm** | All dice become Chaos Dice |

### Endless Leaderboard

Separate leaderboard tracking:
- Highest challenge reached
- Total points scored
- Longest streak without Farkle

---

## 11. Achievements & Leaderboards

### Achievement Categories

#### Progress Achievements

| Achievement | Condition | Reward |
|-------------|-----------|--------|
| **First Steps** | Complete Challenge 5 | 1★ |
| **Halfway There** | Complete Challenge 10 | 2★ |
| **The Final Push** | Complete Challenge 15 | 3★ |
| **Fortune's Champion** | Complete Challenge 20 | 5★, Golden Dice Skin |
| **Endless Walker** | Reach Challenge 25 | 5★ |
| **Endless Master** | Reach Challenge 30 | 10★, Platinum Dice Skin |

#### Skill Achievements

| Achievement | Condition | Reward |
|-------------|-----------|--------|
| **Hot Streak** | 5 Hot Dice in one run | 2★ |
| **Untouchable** | Win without any Farkles | 5★, "Lucky" Title |
| **Risk Taker** | Win 10 Risk Rolls in one run | 3★ |
| **High Roller** | Bank 5,000+ in single turn | 2★ |
| **Perfect 10** | Score exactly the target (no excess) | 2★ |

#### Collection Achievements

| Achievement | Condition | Reward |
|-------------|-----------|--------|
| **Dice Collector** | Own 10 different special dice | 3★ |
| **Full Set** | Own all Common dice | 2★ |
| **Rare Hunter** | Own all Rare dice | 5★ |
| **Big Spender** | Spend 200₣ total across runs | 3★ |
| **Fortune Favors** | Hold 50₣ at once | 2★ |

### Global Leaderboards

| Board | Metric |
|-------|--------|
| **High Score** | Single challenge highest score |
| **Speedrun** | Fastest full run completion |
| **Endless** | Highest challenge reached |
| **Fortune** | Most ₣ earned in single run |
| **Daily** | Today's seeded challenge |
| **Weekly** | Current week's challenge |

---

## 12. Session Flow Example

```
═══════════════════════════════════════════════════════════
                    FARKLE FORTUNE - SAMPLE RUN
═══════════════════════════════════════════════════════════

STARTING STATE
├── Fortune: 10₣
├── Special Dice: None (3 slots available)
├── Consumables: None (3 slots available)
└── Fortune's Favor: None (need 10₣ minimum)

───────────────────────────────────────────────────────────
CHALLENGE 1: "First Roll"
Target: 1,000 | Turns: 4 | Modifier: None
───────────────────────────────────────────────────────────

Turn 1: Roll [1,3,3,3,5,6]
  → Keep: 1 (100) + 3-3-3 (300) + 5 (50) = 450
  → Bank: 450 points

Turn 2: Roll [2,2,4,5,5,6]
  → Keep: 5-5 (100)
  → Roll again: [1,2,4,6]
  → Keep: 1 (100)
  → Bank: 200 points (Total: 650)

Turn 3: Roll [1,1,2,3,5,6]
  → Keep: 1-1 (200) + 5 (50) = 250
  → Roll: [4,4,6]
  → Keep: nothing... FARKLE!
  → Lost 250, still have 650

Turn 4: Roll [1,3,4,5,5,5]
  → Keep: 1 (100) + 5-5-5 (500) = 600
  → Bank: 600 (Total: 1,250)

✓ CHALLENGE COMPLETE! Target: 1,000 | Scored: 1,250

Fortune Earned:
  +1₣ (banked 1,000+)
  +1₣ (three-of-a-kind)
  = 12₣ total

───────────────────────────────────────────────────────────
QUICK SHOP (after Challenge 1)
Fortune: 12₣ | Fortune's Favor: LUCKY (10-19₣)
───────────────────────────────────────────────────────────

Available:
  [Lucky Die - 8₣] [Reroll Token - 3₣] [Safety Net - 5₣]

Decision: Buy Lucky Die (8₣)
  → Now have 4₣, lost LUCKY bonus
  → But have powerful die for future challenges

───────────────────────────────────────────────────────────
CHALLENGES 2-5: Building momentum...
───────────────────────────────────────────────────────────

Challenge 2: Score 1,380/1,200 ✓ (+6₣)
Challenge 3: Score 1,620/1,500 ✓ (+6₣)
  → FULL SHOP: Buy Echo Die (15₣), Ace Mastery (15₣)
Challenge 4: Score 2,100/1,800 ✓ (+7₣)
Challenge 5: Score 2,450/2,000 ✓ (+7₣)

Current State:
├── Fortune: 24₣
├── Special Dice: Lucky Die, Echo Die
├── Upgrades: Ace Mastery (1s = 150)
├── Fortune's Favor: FORTUNATE (+10% scoring)
└── Consumables: Reroll Token

───────────────────────────────────────────────────────────
CHALLENGE 8: "Cold Snap"
Target: 3,000 | Turns: 3 | Modifier: 5s score 0
───────────────────────────────────────────────────────────

This modifier hurts! But Lucky Die still prevents Farkles,
Echo Die can copy 1s for big points, and Ace Mastery helps.

Turn 1: Roll [1,1,5,5,5,5] (Lucky Die + Echo copying 1)
  → All 5s are worthless, but 1-1 + Echo(1) = 450
  → Risk roll with 2 dice: [3,4] FARKLE!

Turn 2: Roll [1,2,3,4,4,6]
  → Keep 1 (150 with mastery)
  → Roll: [1,2,4,5,6]
  → Keep 1 (150)
  → Roll: [2,3,6] FARKLE!
  → Lost 300

Turn 3: MUST score 3,000 in one turn...
  → Use Reroll Token strategically
  → Final bank: 3,200

✓ CHALLENGE COMPLETE (barely!)

───────────────────────────────────────────────────────────
CHALLENGE 20: "The Gauntlet"
Target: 15,000 | Turns: 2 | Modifier: Decay + Squeeze + 5 Dice
───────────────────────────────────────────────────────────

Final challenge. Triple modifier:
  - Decay: Target +200 per turn without banking
  - Squeeze: Must bank 300+ per turn
  - Five Dice: Only 5 dice instead of 6

Current Build:
├── Dice: Lucky, Echo, Splitting, Golden, Phantom
├── Upgrades: Ace Mastery, Triple Bonus, Hot Dice Bonus
├── Consumables: Second Chance, Double Down, Lucky Seven
└── Fortune: 45₣ (CHARMED tier: all bonuses active)

Turn 1: Use Lucky Seven (7 dice!)
  → Massive combo potential with Splitting Die
  → Bank 8,500 (satisfies Squeeze)
  → Target now 15,200 (Decay)

Turn 2: Use Double Down
  → Need 6,700+ points
  → Hot Dice! Roll again with all 6
  → Echo copies Splitting for quad
  → Use Phantom to remove one bad die
  → Final score: 17,800

═══════════════════════════════════════════════════════════
                      ★ VICTORY! ★
═══════════════════════════════════════════════════════════

Final Stats:
├── Challenges Completed: 20/20
├── Total Points: 87,450
├── Fortune Earned: 156₣
├── Fortune Spent: 142₣
├── Hot Dice: 7
├── Farkles: 12
├── Time: 38 minutes
└── Stars Earned: 5★ (win) + 2★ (no-Farkle challenge) = 7★

UNLOCKED: "Fortune's Champion" Achievement
UNLOCKED: Golden Dice Skin

[CONTINUE TO ENDLESS] or [END RUN]
```

---

## 13. Implementation Phases

### Phase 1: Core Gauntlet
- [ ] 20-challenge structure with targets and turn limits
- [ ] Basic win/loss conditions
- [ ] 6 starter modifiers (Cold, Minimum Bank, etc.)
- [ ] Fortune earning (basic actions only)
- [ ] Simple shop (dice purchase only)

### Phase 2: Special Dice
- [ ] 6 Common dice
- [ ] 4 Uncommon dice
- [ ] Dice slot system (3 slots)
- [ ] Dice selling mechanic

### Phase 3: Economy Depth
- [ ] Fortune's Favor tier system
- [ ] Consumables (8 types)
- [ ] Full shop / Quick shop rhythm
- [ ] Shop reroll mechanic

### Phase 4: Upgrades & Variety
- [ ] Permanent upgrades (12 types)
- [ ] 3 Rare dice
- [ ] All 15 modifiers
- [ ] Combined modifiers for late challenges

### Phase 5: Meta-Progression
- [ ] Star currency system
- [ ] Unlockable dice
- [ ] Starting bonuses
- [ ] Achievement system

### Phase 6: Competitive Features
- [ ] Daily challenges (seeded runs)
- [ ] Weekly challenges
- [ ] Global leaderboards
- [ ] Endless mode

### Phase 7: Polish
- [ ] Scoring animations
- [ ] Sound design
- [ ] Dice visual effects
- [ ] Tutorial / onboarding
- [ ] Balance tuning

---

## Appendix A: Quick Reference

### Fortune Earnings
| Action | ₣ |
|--------|---|
| Bank 1,000+ | +1 |
| Bank 2,000+ | +2 |
| Bank 3,000+ | +4 |
| Hot Dice | +5 |
| Risk Roll (1-2 dice) | +3 |
| Streak (per roll after 2nd) | +1 |
| Three-of-a-kind+ | +1 |
| Four-of-a-kind | +2 |
| Five-of-a-kind | +3 |
| Six-of-a-kind | +5 |
| Straight | +4 |

### Fortune's Favor
| ₣ Held | Tier | Bonus |
|--------|------|-------|
| 10-19 | Lucky | 5% Farkle saves |
| 20-29 | Fortunate | +10% scoring |
| 30-39 | Blessed | +100 first roll |
| 40+ | Charmed | All bonuses |

### Pricing
| Type | Range |
|------|-------|
| Common Dice | 5-10₣ |
| Uncommon Dice | 12-18₣ |
| Rare Dice | 20-30₣ |
| Consumables | 3-10₣ |
| Upgrades | 12-25₣ |

---

*Document Version: 2.0*
*Design: Farkle Fortune - Enhanced Solo Mode*
*Platform: Farkle Ten 2026*
