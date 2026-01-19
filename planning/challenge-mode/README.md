# Challenge Mode - Planning Documents

## Overview

Challenge Mode is a roguelike game mode for Farkle Ten where players face 20 sequential AI bots, earning money to purchase enhanced dice that modify gameplay. This adds strategic depth and skill expression to the traditionally RNG-heavy game of Farkle.

**Status:** Planning Phase
**Target Implementation:** ~7 weeks (phased approach)
**Priority:** High - Major feature addition

---

## Core Concept

**The Gauntlet:**
- Face 20 AI bots in sequence (same lineup for all players)
- Each match is 10 rounds to 3000 points (modified from standard Farkle)
- Progressive difficulty: Bot #1 (easiest) → Bot #20 (final boss)
- **Permadeath:** Lose once and restart from Bot #1

**The Economy:**
- Earn **$1 for every die you save** during gameplay (roll or score)
- Money persists through run, resets when run ends
- After each victory, visit the shop to upgrade your arsenal

**The Shop:**
- Displays 3 random unique dice options
- Three price tiers: **$2-3 (Simple), $4-6 (Better), $6-15 (Amazing)**
- Purchase dice to replace one of your 6 dice
- Can skip to save money for later rounds

**Progression:**
- Pure skill-based: No meta-progression or permanent unlocks
- Leaderboard: Furthest bot reached
- Achievements: Reach Bot #5, #10, #15, #20
- Save/resume: One active run at a time, resume anytime

---

## Planning Documents

### 1. [Executive Pitch](executive-pitch.md)
**Purpose:** Sell the vision to stakeholders
**Contents:**
- The problem: Current Farkle is too RNG-heavy
- The solution: Roguelike challenge mode with strategic dice upgrades
- Target audience and success metrics
- Competitive positioning

**Key Takeaway:** Challenge Mode transforms Farkle from a luck-based casual game into a skill-based roguelike with infinite replayability.

---

### 2. [Special Dice Catalog](special-dice.json)
**Purpose:** Curated list of special dice for MVP
**Format:** JSON with categories and dice definitions
**Contents:**
- 4 color-coded strategy categories
- 22 balanced dice across all categories (3-6 per category)
- Short descriptors for UI display (LUCKY, WILD, DOUBLE, etc.)
- Pricing tiers: Simple ($2-3), Better ($4-6), Amazing ($10-15)

**Categories:**
1. 🔴 **Farkle Lovers (Red)** - Make farkles beneficial (Phoenix, Vampire, Gamble, Fark$, Dare)
2. 🟢 **Farkle Protection (Green)** - Prevent or reduce farkle damage (Safe, Cushion, Guard, Second, Chute)
3. 🔵 **Face Changers (Blue)** - Modify dice face values (Lucky, Odds, Wild, Heavy, Fives, Evens)
4. 🟠 **Score Boosters (Orange)** - Multiply points and money (Double, Midas, Hot, Jackpot, Triple, Greedy)

**Key Takeaway:** Color-coded strategies make dice selection intuitive. Each category offers distinct playstyles.

---

### 3. [Implementation Plan](implementation-plan.md)
**Purpose:** Technical roadmap for development
**Contents:**
- Database schema (5 new tables, 2 modified)
- Backend PHP files (4 new, 3 modified)
- Frontend JavaScript (3 new files, 3 modified)
- Smarty templates (5 new, 2 modified)
- CSS styling and design system
- 8-phase implementation timeline (~7 weeks)
- Testing strategies and edge cases

**Key Sections:**
- **Database:** Challenge runs, dice inventory, bot lineup, stats tracking
- **Backend:** Shop logic, dice effects engine, challenge game flow
- **Frontend:** Shop UI, dice selection, in-game money tracking
- **Integration:** How challenge mode plugs into existing systems

**Key Takeaway:** Well-scoped project with clear phases, minimal disruption to existing codebase.

---

### 4. [UI Mockups](ui-mockups.md)
**Purpose:** Visual design matching existing Farkle aesthetic
**Contents:**
- 10 detailed screen mockups with ASCII art
- Design system (colors, typography, spacing)
- Mobile-responsive layouts
- Animation specifications
- Accessibility considerations

**Key Screens:**
1. **Challenge Lobby** - Start new run, view bot lineup, stats
2. **Shop Interface** - Browse and purchase dice
3. **Slot Selection Modal** - Choose which die to replace
4. **In-Game Challenge UI** - Money display, dice inventory
5. **Victory Screen** - Post-game summary, shop CTA
6. **Defeat Screen** - Run summary, achievements, try again
7. **Bot Lineup** - Full 20-bot overview
8. **Mobile Layouts** - Responsive designs

**Art Direction:**
- Green felt casino aesthetic (matches existing app)
- **Gold gradient** for challenge mode elements
- **Monospace font** (Courier New) for money values
- **💰 Money bag emoji** next to all dollar amounts
- Tier badges: Green (Simple), Blue (Better), Purple (Amazing)

**Key Takeaway:** Cohesive visual design that feels like a natural extension of Farkle Ten.

---

## Design Decisions Log

### Confirmed Decisions (from user Q&A):

**Game Flow:**
- ✅ Lose once = restart from Bot #1 (classic roguelike)
- ✅ 10-round games, ~3000 points to win (variable per bot)
- ✅ Shop appears after every bot victory
- ✅ Save/resume anytime, one active run only

**Economy:**
- ✅ Earn $1 per die saved (regardless of farkle)
- ✅ Money resets each run (no meta-currency)
- ✅ Pricing: $2-3 / $4-6 / $6-15 tiers
- ✅ Can skip shop to save money

**Dice System:**
- ✅ Permanent dice (not consumable)
- ✅ Random shop selection, always unique (no duplicates)
- ✅ Future: Rarity tiers, but start with random pool

**Difficulty:**
- ✅ Linear progression (Bot #1 easiest → #20 hardest)
- ✅ Bots use: Modified rules, special abilities, AI strategies, trash talk
- ✅ Fixed 20-bot lineup (same for everyone, no randomness)

**Progression:**
- ✅ No permanent unlocks (pure skill-based)
- ✅ Leaderboard: Furthest bot reached
- ✅ Achievements: Milestones (Bot #5, #10, #15, #20)
- ✅ Challenge-specific stats tracking

**Starting:**
- ✅ Start with 6 standard dice, $0
- ✅ No tutorial (just description text)

---

## Open Questions for Implementation

### Balancing:
1. **Money tuning:** Confirm $1/die is balanced after playtesting
2. **Bot difficulty curve:** May need adjustment based on player success rate
3. **Dice pricing:** Iterate based on power level vs cost
4. **Target points:** 3000 may be too high/low for 10 rounds

### UX:
1. **Shop skip frequency:** Is every bot too grindy? Allow skipping bots?
2. **Dice visibility:** Should bot's special dice be visible before match?
3. **Effect tooltips:** How detailed should dice effect descriptions be during gameplay?
4. **Tutorial:** Is text description enough, or add practice round?

### Technical:
1. **Run abandonment:** Auto-delete old runs or keep for stats?
2. **Dice effect visibility:** Icons only, or full effect text in game?
3. **Mobile optimization:** Touch vs click interactions for dice selection
4. **Performance:** Cache dice effects, optimize shop queries

---

## Success Metrics

### Engagement Goals (Month 1):
- **30%** of active players try Challenge Mode
- **3+** average runs per player
- **10%** of players reach Bot #10
- **1%** of players beat all 20 bots

### Technical Goals:
- Shop load time **< 200ms**
- Effect calculation **< 50ms per roll**
- **< 10** database queries per game action
- **Zero critical bugs** in first week

### Long-term Goals:
- Challenge Mode becomes most-played game type
- Player retention increases due to replayability
- Foundation for future expansions (seasons, daily challenges)

---

## Next Steps

### Immediate (This Week):
1. ✅ Complete planning documents (DONE)
2. Review and approve design with stakeholders
3. Finalize first 20-30 dice types for MVP
4. Design bot lineup with difficulty curve

### Short-term (Next 2 Weeks):
1. **Phase 1:** Database schema + migration script
2. **Phase 2:** Shop system (backend + frontend)
3. Create seed data for dice types and bot lineup
4. Set up local testing environment

### Mid-term (Weeks 3-5):
1. **Phase 3:** Dice effect engine
2. **Phase 4:** Challenge lobby + run management
3. **Phase 5:** Challenge game mode integration
4. Internal playtesting and balancing

### Long-term (Weeks 6-7):
1. **Phase 6:** Stats and leaderboard
2. **Phase 7:** Polish, mobile optimization, balancing
3. **Phase 8:** Staging deployment, QA, production launch

---

## Future Enhancements (Post-MVP)

**Potential expansions after launch:**
- **Rarity System:** Common/Rare/Legendary dice unlock at higher bots
- **Daily Challenges:** Special bot lineups with unique rules
- **Dice Crafting:** Combine dice to create new ones
- **Seasons:** Rotate bot lineups and special dice monthly
- **Multiplayer Challenge:** Race friends through same bot lineup
- **Prestige Mode:** After beating all 20, unlock harder difficulty
- **Premium Dice Skins:** Cosmetic customization (monetization)
- **Challenge Expansions:** New bot packs, special events

---

## File Structure

```
planning/challenge-mode/
├── README.md (this file)
├── executive-pitch.md
├── special-dice.json
├── dice-visual-guide.md
├── implementation-plan.md
└── ui-mockups.md
```

---

## Contributors

**Game Design:** Michael Schmoyer
**Technical Planning:** Claude Code (AI Assistant)
**Codebase Analysis:** Automated exploration of Farkle Ten architecture

---

## Glossary

- **Run:** A single attempt to beat all 20 bots (ends on loss or completion)
- **Slot:** One of the 6 dice positions in player's inventory
- **Tier:** Price category for dice (Simple $2-3, Better $4-6, Amazing $6-15)
- **Bot Number:** Position in the gauntlet (1-20)
- **Permadeath:** Losing once resets progress to Bot #1
- **Unique Dice:** Shop never offers duplicates of dice already owned
- **Standard Die:** Regular six-sided die with no special effects
- **Effect Engine:** Backend system that applies dice modifications to rolls
- **Monospace Font:** Fixed-width font (Courier New) for money display

---

**Last Updated:** 2026-01-18
**Status:** ✅ Planning Complete - Ready for Development
