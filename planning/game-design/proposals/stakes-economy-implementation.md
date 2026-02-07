# Stakes Economy System: Implementation Spec

**Date:** 2026-02-04
**Status:** Ready to Build (pending legal review)

---

## Overview

Replace XP/leveling with a money-based stakes system. Players wager coins on games, track lifetime earnings, and spend coins on cosmetics. The economy is balanced through participation rewards (faucets) and cosmetic purchases (sinks).

---

## Economic Model

### Money Flow

| Type | Mechanism | Rate |
|------|-----------|------|
| **Faucet** | Per-game completion | +75 coins (win or lose) |
| **Faucet** | Win bonus | +50 coins |
| **Faucet** | Daily login | +150 coins |
| **Faucet** | Win streak bonuses | +100/300/1,000 (3/5/10 wins) |
| **Faucet** | Daily challenges | +150-450 coins/day potential |
| **Faucet** | Leaderboard bonuses | +150-5,000 coins (daily/weekly/monthly) |
| **Sink** | Cosmetic purchases | Variable (100-1,000 coins) |
| **Redistribution** | Game stakes (betting) | Zero-sum (no house fee) |

**Net daily income**: ~600+ coins/day for active player (5 games, 50% win rate)
- (5 games × 75 base) + (2.5 wins × 50 bonus) + 150 login = 650 coins
- Plus daily challenges: ~200 coins average
- Minus ~100 coins/day cosmetic spending = **+750 net**

### Starting Balance & Negatives

- All players start with **1,000 coins**
- Can go **infinitely negative** (no lockouts, ever)
- Negative players still earn rewards (faucets work normally)
- Negative players **cannot buy cosmetics** until positive (soft incentive to recover)

### Momentum System (Anti-Spiral Protection)

Players below -3,000 coins automatically receive escalating "Comeback Boost" bonuses:

| Balance Range | Per-Game | Win Bonus | Daily Login | Leaderboard Bonus |
|---------------|----------|-----------|-------------|-------------------|
| +3,000 to ∞ | 75 | 50 | 150 | 1x |
| 0 to -3,000 | 100 (+33%) | 75 (+50%) | 200 (+33%) | 1.5x |
| -3,000 to -8,000 | 125 (+67%) | 100 (+100%) | 250 (+67%) | 2x |
| Below -8,000 | 150 (+100%) | 125 (+150%) | 300 (+100%) | 2.5x |

**Recovery time from -1,000 coins** (worst case):
- Player at -10K earning 1,362 coins/day (5 games at 50% win rate with max boost)
- Returns to positive in **8 days** of normal play

**UI messaging**: "Comeback Boost Active: 2x rewards until you break even!"

### Betting is Zero-Sum

- Entry fees go entirely into pot
- Winner takes all
- **No house fee** (keeps betting feel skill-based, not extractive)
- Stakes are redistributive only; faucets/sinks control inflation

---

## Feature Details

### 1. Deprecate XP/Leveling

**Action**: Full reset - everyone starts fresh
- XP and level columns preserved in database (historical data only)
- Stop displaying XP/level in UI entirely
- All players start with 1,000 coins regardless of previous level
- Remove all XP gain mechanics going forward
- **Keep existing achievements** - players retain their earned achievements and titles

**Rationale**: Clean break allows everyone to start on equal footing with new economy; simpler than conversion formulas. Achievements preserved because they represent real accomplishments (not grindable XP).

### 2. New Status System: Lifetime Stats

Replace "Player Level" with visible stat card:
- **Primary**: Total games played (always increasing)
- **Secondary**: Net lifetime winnings, win rate, best streak
- **Visual**: Star rating (1-5 stars) based on games played milestones:
  - ★☆☆☆☆ 0-99 games
  - ★★☆☆☆ 100-499 games
  - ★★★☆☆ 500-999 games
  - ★★★★☆ 1,000-4,999 games
  - ★★★★★ 5,000+ games

**Key**: Players with negative bankrolls still progress via games played

### 3. Betting Flow (UX)

**Balance Display**: Persistent chip icon + count in top-right corner (all screens)

**Setting Stakes (Friend Invite)**:
1. "Create Game" → Shows inline stake selector
2. Three preset buttons: Low / Medium / High (relative to your bankroll)
3. Or drag slider for custom amount
4. Default: Last-used stake, clamped to current tier
5. Both players see stakes on game card in lobby before starting

**Setting Stakes (Random Opponent)**:
1. "Quick Match" auto-matches by stake bracket
2. Shows "Stakes: 50 coins" in lobby before accepting
3. One-tap accept or decline

**Bankroll Tiers** (auto-suggest stakes):
- 0-1,000 coins → 10-50 stakes
- 1,000-5,000 coins → 50-200 stakes
- 5,000-1,000 coins → 200-500 stakes
- 10,000+ coins → 500-2,000 stakes

### 4. Post-Game Money Display

**Win Screen**:
1. Animated chip stack with "+200" floating above
2. Coins animate into top-right balance counter
3. Green color-coded, subtle (not slot machine style)
4. Message: "You won 200 coins!"

**Loss Screen**:
1. Animated chips with "-100" floating away
2. Red color-coded but neutral tone
3. Message: "Better luck next time" (not "You lost X")

### 5. Post-Game Accolade (Emoji Replacement)

**Flow**:
1. Winner sees post-game screen with **3 auto-suggested contextual accolades**
   - Based on game events (clutch win, hot streak, farkle streak)
   - Examples: "Clutch God", "Hot Streak", "Farkle Magnet", "Comeback Kid"
2. One-tap to send accolade to opponent
3. Optional: Swipe right to see full picker for custom choice
4. Tap "Done" to skip (no pressure)

**Display on Profile**:
- Show top 3 most-received accolades with counts
- Example: "🔥 Clutch God ×47 | 💀 Farkle Magnet ×23 | ⚡ Hot Streak ×19"

**Contextual Logic**:
- **Clutch God**: Won by <500 points in final round
- **Comeback Kid**: Won after being down 3,000+ points mid-game
- **Hot Streak**: Scored 2,500+ in a single turn
- **Farkle Magnet**: Farkled 3+ times in the game
- **Unstoppable**: Won every round (10-round mode)
- **Lucky Devil**: Won despite lower average score per turn

### 6. Cosmetic Shop (Spending Money)

**Location**: New tab in profile screen (Profile → Shop)

**Categories**:
- **Dice Skins**: 200-2,000 coins
- **Profile Backgrounds**: 500-5,000 coins
- **Emojis/Stickers**: 50-200 coins
- **Table Themes**: 1,000-1,000 coins

**Two Unlock Paths**:

**Path A: Achievement Unlocks** (prestigious status symbols)
- Earned through gameplay milestones (e.g., "100 wins: Golden Dice backdrop")
- Still cost coins to purchase after unlock (creates sink)
- Displayed with "Earned" badge on profile

**Path B: Direct Purchase** (variety and personalization)
- Available immediately for coins
- Lower-tier items (100-500 coins) for casual spenders
- Higher-tier items (5,000-1,000 coins) for whales

**Browse UX**:
- Horizontal scrollable categories
- Grid view with preview thumbnails
- Tap item → full preview + "Unlock for X coins" button
- Confirmation for 500+ coin items: "New balance will be: X"
- Instant purchase for <100 coin items

**Cosmetic Lock While Negative**:
- Shop shows items grayed out with "Requires positive balance"
- Soft incentive to recover from negative

### 7. Profile Backgrounds

**Full reset**: Everyone starts with default background
- Add new DB column `active_background` (new field, separate from legacy data)
- Read from `active_background` instead of old background field
- Legacy background data preserved in old column (rollback-safe)
- New backgrounds earned through achievements or purchased with coins
- Achievement-based backgrounds (100 wins, 1K games, etc.)
- Purchasable backgrounds (500-5,000 coins)

**Migration**: All players reset to default. Old column untouched for rollback.

### 8. Practice Mode (Safe Recovery Path)

**Bot and solo games with capped rewards for safe farming**

**Mechanics**:
- Practice games have **no stakes** (0 coins bet)
- Participation reward: **10 coins** (not 75)
- Win bonus: **5 coins** (not 50)
- Daily cap: **Max 100 coins/day** from practice games

**UI**:
- Labeled "Practice Mode" with badge showing "⚠️ Limited Rewards"
- Auto-suggested when balance drops below -5,000
- Clear messaging: "Earn coins safely while you recover"

**Restrictions**:
- Practice games don't count toward leaderboard stats
- Achievement progress disabled in practice mode
- Accolades can still be granted (for fun)

**Purpose**: Provides guaranteed income for deeply negative players without exploitation risk (10x less efficient than human games)

### 9. Leaderboard Bonuses

**Three-tier bonus system rewarding top performers**

| Leaderboard | Rank | Bonus | Frequency |
|-------------|------|-------|-----------|
| **Daily Top 10** | 1st | 500 coins | Daily reset |
| | 2nd-3rd | 300 coins | |
| | 4th-10th | 150 coins | |
| **Weekly Top 10** | 1st | 2,000 coins | Weekly reset |
| | 2nd-3rd | 1,200 coins | |
| | 4th-10th | 600 coins | |
| **Monthly Top 3** | 1st-3rd | 5,000 coins | Monthly |
| | 4th-10th | 2,500 coins | |

**Comeback Multipliers** (applied when balance is negative):
- 0 to -3,000: **1.5x** bonus
- -3,000 to -8,000: **2x** bonus
- Below -8,000: **2.5x** bonus

**Example**: Player at -5,000 finishes 5th on daily leaderboard
- Base bonus: 150 coins
- With 2x multiplier: **300 coins**

**Additional Leaderboard**: "Most Improved This Week"
- Top 3 players with biggest weekly net gain: 500 / 300 / 150 coins
- Accessible to anyone on a hot streak, not just the wealthy

### 10. Win Streak Bonuses

**Immediate dopamine rewards for momentum**

- **3 wins in a row**: +100 coins + notification
- **5 wins in a row**: +300 coins + "Hot Streak" accolade
- **10 wins in a row**: +1,000 coins + "Unstoppable" title unlock

**Comeback Streak Bonuses** (while balance is negative):
- First win after 5-game losing streak: **+200 coins** + "Resilience" accolade
- Won 5 games in a row while negative: **+500 coins** + "Phoenix Rising" badge

### 11. Daily Challenges

**Optional objectives with coin rewards**

| Challenge | Reward |
|-----------|--------|
| Win 3 games today | +150 coins |
| Score 3,000+ in a single turn | +200 coins |
| Play 5 games (win or lose) | +100 coins |
| Win without farkles | +250 coins |
| Beat a friend by 5,000+ points | +300 coins |

**Structure**:
- 3 challenges appear daily (random rotation)
- Total potential: **+450 coins/day** from challenges
- UI: Small banner in lobby showing active challenges
- Progress tracked automatically

### 12. Milestone Celebrations

**Three-tier rewards**:

| Tier | Milestones | Reward |
|------|------------|--------|
| Common | First win, 50th game, 100th game | 50 coins + notification |
| Rare | 100 wins, 500 games, 10-game streak | 200 coins + cosmetic unlock |
| Epic | 1,000 games, 100-game win streak | 500 coins + exclusive cosmetic + title |

**Delivery**: In-game modal with animation, logged in achievements section

---

## Implementation Phases

### Phase 1: Core Economy (MVP)
- Add coins to player table (starting balance: 10,000)
- Implement stake selection UI (friend invites only)
- Per-game rewards (**75 base + 50 win**) + daily login (**150**)
- **Momentum System**: Escalating bonuses for negative balances
- Post-game money display (win/loss animations)
- Balance display in header (persistent)
- Zero-sum betting (winner takes pot, no house fee)

### Phase 2: Safety Net Features
- **Practice Mode**: Bot/solo games (10 coins/game, 100/day cap)
- **Win Streak Bonuses**: 100/300/1,000 for 3/5/10 wins
- **Daily Challenges**: 3 random challenges, +450 coins/day potential
- Comeback bonuses (resilience accolades, recovery rewards)

### Phase 3: Leaderboard Bonuses
- Daily/weekly/monthly leaderboard coin bonuses (150-5,000 coins)
- Comeback multipliers (1.5x-2.5x for negative players)
- "Most Improved This Week" leaderboard
- Automated payout system

### Phase 4: Cosmetics & Sinks
- Build cosmetic shop (dice, backgrounds, emojis, tables)
- Migrate existing backgrounds to shop
- Add 20-30 initial purchasable items (100-1,000 coins)
- Achievement-based unlocks (prestigious items)
- Cosmetic purchase flow
- Lock shop when balance < 0

### Phase 5: Progression Overhaul
- Hide XP/level from UI (keep data in database)
- Build new stats card (games played, star rating, net winnings)
- Milestone celebration system (50/200/500 coin bonuses)
- Player announcement: "New economy system! Everyone starts with 1,000 coins"

### Phase 6: Contextual Accolades
- Auto-detect game events (clutch, hot streak, farkles, comeback)
- Post-game accolade picker (3 auto-suggested options)
- Display top 3 accolades on profile with counts
- Replace old emoji system

### Phase 7: Advanced Features
- Stake tiers & auto-matching by bracket (10/50/200/500/1,000 stakes)
- Tournament prize pools (entry fees → winner payouts)
- Seasonal "Fresh Start" events (quarterly debt forgiveness)
- Premium cosmetics (20,000-50,000 coin luxury items)

---

## Database Schema Changes

### New Columns: `farkle_players`
```sql
ALTER TABLE farkle_players
ADD COLUMN coins INTEGER DEFAULT 1000 NOT NULL,
ADD COLUMN lifetime_coins_earned INTEGER DEFAULT 0 NOT NULL,
ADD COLUMN lifetime_coins_spent INTEGER DEFAULT 0 NOT NULL,
ADD COLUMN last_daily_reward DATE,
ADD COLUMN current_win_streak INTEGER DEFAULT 0 NOT NULL,
ADD COLUMN best_win_streak INTEGER DEFAULT 0 NOT NULL,
ADD COLUMN current_loss_streak INTEGER DEFAULT 0 NOT NULL;

-- Note: Keep existing xp/level columns for historical data, but stop using them
-- games_played column already exists
```

### New Columns: `farkle_games`
```sql
ALTER TABLE farkle_games
ADD COLUMN stake_amount INTEGER DEFAULT 0 NOT NULL;
```

### New Table: `farkle_cosmetics`
```sql
CREATE TABLE farkle_cosmetics (
    cosmetic_id SERIAL PRIMARY KEY,
    category VARCHAR(50) NOT NULL, -- 'dice', 'background', 'emoji'
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price INTEGER NOT NULL,
    unlock_achievement_id INTEGER, -- NULL if direct purchase
    image_file VARCHAR(255),
    active BOOLEAN DEFAULT TRUE
);
```

### New Table: `farkle_player_cosmetics`
```sql
CREATE TABLE farkle_player_cosmetics (
    playerid INTEGER NOT NULL,
    cosmetic_id INTEGER NOT NULL,
    unlocked_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (playerid, cosmetic_id)
);
```

### New Table: `farkle_accolades`
```sql
CREATE TABLE farkle_accolades (
    accolade_id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- 'Clutch God', 'Hot Streak', etc.
    emoji VARCHAR(10),
    description TEXT
);
```

### New Table: `farkle_player_accolades`
```sql
CREATE TABLE farkle_player_accolades (
    playerid INTEGER NOT NULL,
    accolade_id INTEGER NOT NULL,
    count INTEGER DEFAULT 1,
    last_received TIMESTAMP,
    PRIMARY KEY (playerid, accolade_id)
);
```

### New Table: `farkle_daily_challenges`
```sql
CREATE TABLE farkle_daily_challenges (
    challenge_id SERIAL PRIMARY KEY,
    challenge_date DATE NOT NULL,
    challenge_type VARCHAR(50) NOT NULL, -- 'win_3', 'score_3000', 'play_5', etc.
    description TEXT NOT NULL,
    reward INTEGER NOT NULL,
    active BOOLEAN DEFAULT TRUE
);
```

### New Table: `farkle_player_challenges`
```sql
CREATE TABLE farkle_player_challenges (
    playerid INTEGER NOT NULL,
    challenge_id INTEGER NOT NULL,
    progress INTEGER DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    completed_date TIMESTAMP,
    PRIMARY KEY (playerid, challenge_id)
);
```

### New Table: `farkle_coin_transactions`
```sql
CREATE TABLE farkle_coin_transactions (
    transaction_id SERIAL PRIMARY KEY,
    playerid INTEGER NOT NULL,
    amount INTEGER NOT NULL, -- positive for earned, negative for spent
    transaction_type VARCHAR(50) NOT NULL, -- 'game_reward', 'win_bonus', 'daily_login', 'challenge', 'leaderboard', 'cosmetic_purchase', 'win_streak', etc.
    reference_id INTEGER, -- gameid, challenge_id, cosmetic_id, etc.
    balance_after INTEGER NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_coin_transactions_player ON farkle_coin_transactions(playerid, created_date);
```

---

## Key Decisions Summary

| Question | Decision |
|----------|----------|
| Deprecate XP/leveling? | **Yes** - total reset, everyone starts with 10K coins |
| Profile backgrounds? | **Migrate to shop** - dual-path (achievements + purchases) |
| Bet at game start? | **Inline stake selector** with presets, auto-suggest by tier |
| Grant emojis by performance? | **Yes** - auto-suggest 3 contextual accolades, one-tap send |
| Display money changes? | **Animated chips** floating into top-right balance counter |
| Spend money on cosmetics? | **Yes** - dice, backgrounds, emojis, tables (100-10K coins) |
| Money generation? | **75/game + 50 win + 150 daily + streaks + challenges + leaderboard** = ~850/day |
| House fee on bets? | **No** - betting is zero-sum, inflation controlled via faucets |
| Negative balances? | **Momentum System** - 2x-3x rewards when negative, recover in 8 days |
| Bot/solo games? | **Practice Mode** - 10 coins/game, 100/day cap (safe farming) |
| Leaderboard bonuses? | **Yes** - 150-5K coins daily/weekly/monthly with comeback multipliers |

---

## Open Questions / Risks

1. **Legal review required**: Even play-money wagering may trigger gambling regulations
2. **Inflation tuning**: Monitor player wealth distribution, adjust faucet rates seasonally
3. **Cosmetic catalog size**: Need 20-30 items at launch to make shop feel substantial
4. **Social pressure**: Will negative-bankroll players feel stigmatized? Monitor sentiment
5. **Announcement strategy**: How to communicate the reset to existing players (everyone starts fresh at 10K coins)?

---

## Success Metrics

- **Engagement**: Games per player per day (target: +20% from baseline)
- **Retention**: D7/D30 retention (target: +15%)
- **Economy health**: Median player balance over time (should stay 5K-15K range)
- **Social activity**: Accolades granted per game (target: >70%)
- **Monetization readiness**: Cosmetic purchase rate (coins spent / coins earned ratio)