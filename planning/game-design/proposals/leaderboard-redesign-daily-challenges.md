# Game Design Proposal: Interactive Leaderboard Redesign with Daily Challenges

**Date:** 2026-02-03
**Status:** Draft

---

## Topic

Making the leaderboard as fun and interactive as possible. Limiting daily leaderboards to a fixed number of games to prevent grinding. Adding time-based views, interesting stats, daily challenges, and other reasons to make the leaderboard a destination players visit every day.

---

## Committee Perspectives

### The Player Advocate
- **Best-20 game cap is the single highest-impact idea.** It levels the playing field so a parent squeezing in games at lunch can compete with someone who plays all day. Creates a natural daily ritual without grind.
- **Strongly prefers "best 20" over "first 20."** First-20 punishes bad starts and creates anxiety. Best-20 lets players warm up and put their best foot forward — feels generous, not punitive.
- **Wants a "Rival Board"** showing only your friends with movement arrows (who moved up/down since yesterday). That's where the kitchen-table energy lives.
- **Cautious on daily challenges.** If they feel like homework, they become a chore. Must feel like a fun twist, not an obligation.
- **Curate stats, don't overwhelm.** 3-4 rotating "featured stats" per week keeps it fresh without cluttering the UI for casual players.

### The Systems Designer
- **Advocates for "first 20" over "best 20."** If you allow unlimited games and take the best 20, the optimal strategy is to play hundreds and cherry-pick. First-20 means every game counts and extends the push-your-luck tension to the meta-level ("do I play risky when I only have 3 daily games left?").
- **Weekly leaderboards are the primary social driver.** Daily resets too fast for async players; all-time calcifies and discourages newcomers. Weekly creates short rivalry arcs.
- **Skip hourly leaderboards.** Too synchronous for an async-first game.
- **Daily challenges are high risk.** They create obligation, which conflicts with async-first. If implemented, make them weekly challenges with no streak penalties.
- **Rotating featured stats can feed the achievement system** — hidden achievements triggered by weekly stat categories give XP/levels a fresh injection without new grind.

### The Social Designer
- **Game cap is essential for social fairness.** Without it, different life schedules (working parent vs. teenager on summer break) create asymmetric competition that kills rivalry. Leans toward best-20 for the same "no anxiety" reasons as the Player Advocate.
- **Weekly is the sweet spot for social groups.** "Sunday night leaderboard check" becomes a family ritual. Hourly is synchronous by nature — skip it.
- **Stats create stories.** "I once scored 4,000 in a single turn" is exactly the kitchen-table conversation the game should generate. Suggests a "highlight of the week" per player.
- **Shared daily challenges are powerful** — if everyone in a friend group gets the *same* challenge, it creates shared context even in async play. Avoid individual random challenges that fragment the social experience.
- **Social risk flag:** Never publicly show "games played per day." It could shame casual players or spotlight addictive patterns. Keep volume metrics private.

---

## Consensus & Tension

**Where the committee agrees:**
- A daily game cap on leaderboard scoring is the most important change — it prevents grinding and equalizes players with different amounts of free time
- Weekly leaderboards should be the primary competitive timeframe for friend groups
- Skip hourly leaderboards — too synchronous for an async game
- Stats should be curated and rotating, not an overwhelming wall of numbers
- Everything should prioritize friend-group rivalry over global rankings
- Never publicly expose "games played" volume metrics

**Where the committee disagrees:**
- **"Best 20" vs "First 20":** The Player Advocate and Social Designer want best-20 (less anxiety, more forgiving). The Systems Designer wants first-20 (prevents cherry-picking from unlimited play, adds meta-level push-your-luck). This is the key design tension — fairness-feeling vs. exploit-resistance. A compromise: **"best 20 of your first 30 games"** gives a small buffer for warm-up while still capping total volume.
- **Daily challenges:** The Player Advocate and Systems Designer are cautious (risk of obligation/chore). The Social Designer loves them if they're shared across friend groups. Resolution: implement as low-stakes shared challenges with no streak tracking or punishment for missing.

---

## Recommendation

### Feature Name
**Leaderboard 2.0: The Daily Board, Weekly Rivals, and Stat Highlights**

### Description
A redesigned leaderboard experience with three layers: a daily competition capped at your best games, a weekly friend rivalry board, and rotating stat highlights that give every player something to chase. Optional shared challenges add a twist without creating obligation.

### How It Works

**1. Daily Leaderboard (Capped)**
- Each player's daily score is calculated from their **best 20 games** completed that calendar day (UTC or player-local timezone)
- Games beyond 20 still count for XP, achievements, and all-time stats — they just don't improve your daily leaderboard position
- Daily board resets at midnight
- Show rank, daily score (sum of best-20 game scores), and movement arrows vs. yesterday
- Default view when opening leaderboard

**2. Weekly Leaderboard**
- Aggregates daily scores across the week (Monday–Sunday)
- Separate tab on the leaderboard page
- Highlights top 3 with visual distinction
- Resets every Monday
- This becomes the primary "rivalry timeframe" for friend groups

**3. Friend Rival Board**
- A dedicated view showing only your friends
- Each friend shows: rank, score, movement arrow (up/down/same since last check), and their "highlight stat" for the period
- Available for both daily and weekly views
- This is the default leaderboard view if the player has friends

**4. Rotating Stat Highlights**
- Each week, 3 featured stats are spotlighted on the leaderboard (examples below)
- Every player gets their personal best for each featured stat shown on their leaderboard row
- Example stats to rotate through:
  - Highest single-turn score
  - Longest hot streak (consecutive non-Farkle rolls)
  - Most points banked in one game
  - Most Farkles survived to still win
  - Highest 10-round game score
  - Most comebacks (won after trailing by 2,000+)
  - Fewest rolls to reach 10,000
- Featured stats can trigger hidden achievements when milestones are hit

**5. Shared Weekly Challenge (Optional / Phase 2)**
- One challenge per week, same for all players (e.g., "Score 3,000+ in a single turn," "Win a game after Farkling 3 times," "Bank exactly on 10,000")
- Displayed on the leaderboard page as a banner
- Completing it earns a small XP bonus and a checkmark visible to friends
- No streak tracking, no punishment for skipping — purely opt-in
- Friends can see who completed the challenge, creating conversation

### Why It Fits

This proposal directly serves all four design pillars:
- **Fun First:** The game cap preserves the push-your-luck tension (every game matters) without adding complexity to the core dice loop
- **Replayability:** Daily resets, weekly cycles, and rotating stats give players a reason to come back every day and every week
- **Social Connection:** The Friend Rival Board and shared challenges create conversation and rivalry between people who know each other — the game's strongest retention driver
- **Async-First:** Everything works without both players being online. Check the board when you want, play your games when you can, see results later

---

## Risks & Trade-offs

- **"Best 20" still allows some cherry-picking** if players play 50+ games — monitor whether this becomes a problem. If it does, consider tightening to "best 20 of first 30" as a compromise
- **Timezone handling for daily resets** needs careful implementation — UTC is simplest but may feel wrong for players ("my game at 11pm didn't count for today's board")
- **Rotating stats add UI complexity** — keep the display clean; don't show all stats at once. 3 per week maximum
- **Weekly challenges need careful curation** — challenges that are too easy feel pointless, too hard feel exclusionary. Target ~60-70% completion rate among active players
- **Database load** from daily/weekly aggregation queries — may need summary tables or caching for the leaderboard calculations
- **Risk of the daily cap feeling like a limit rather than a feature** — messaging matters. Frame it as "your best 20" not "only 20 count"

---

## Priority

**Suggested priority:** Should-have

**Rationale:** The leaderboard is already identified as the strongest retention driver in the design bible, but right now it's passive. Adding daily caps, weekly cycles, and friend rivalry transforms it from a scoreboard into a daily destination — high impact on retention with manageable implementation scope.

---

## Implementation Phases

**Phase 1 (Core):** Daily leaderboard with best-20 cap + weekly leaderboard + friend rival board
**Phase 2 (Enrichment):** Rotating stat highlights + hidden achievements from stats
**Phase 3 (Social Layer):** Shared weekly challenges

---

## Open Questions

1. **Timezone for daily reset:** UTC (simplest) or player-local timezone (better UX but more complex)?
2. **What score metric for daily leaderboard?** Sum of best-20 scores? Average of best-20? Win count among best-20? Each creates different incentives.
3. **Should the game cap be visible?** ("You've played 14 of 20 daily games") or invisible (just silently stop counting after 20)?
4. **How does this interact with 10-round vs Standard mode?** Do both modes count toward the daily board, or separate boards per mode?
5. **Should bot games count toward daily leaderboard?** Including them lowers the bar; excluding them encourages multiplayer.
6. **What's the minimum friend count before showing the Friend Rival Board as default?** (2? 3? 5?)
