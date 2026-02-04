# Game Design Proposal: All-Time Leaderboard Metric

**Date:** 2026-02-03
**Status:** Draft

---

## Topic

For the all-time leaderboard, we currently show "Avg Daily Score" (average of a player's daily top-10 sums, producing numbers like 62,797) and "Best Day Score" (highest single daily sum, like 72,550). These numbers are confusing because they're accumulated totals from 10 games summed together. Should we use "Average Game Score" instead, or some other metric?

---

## Committee Perspectives

### The Player Advocate
- Numbers like 62,797 are meaningless to a player who just scored 7,200 in a game — they can't map it to their own experience
- "Average Game Score" (6,000-8,000) is instantly relatable because it matches what you actually see when you play
- Also recommends replacing "Best Day Score" with "Best Single Game" — a moment of glory everyone understands

### The Systems Designer
- Current metric is "systemically broken" because it forces players to reverse-engineer what success looks like
- Career Average Score incentivizes consistency over daily optimization (can't game it by cherry-picking your best day)
- Produces numbers that feel like Farkle scores because they *are* Farkle scores

### The Social Designer
- The leaderboard metric needs to answer: "What story can I tell about my performance?"
- "I average 7,200 per game" is trash-talk ready; "I averaged 62,797 on my daily top-10 sum" is not
- Parents, kids, coworkers can all understand average game score without explanation

---

## Consensus & Tension

**Where the committee agrees:**
- Unanimous: "Avg Daily Score" is confusing and should be replaced with "Average Game Score"
- The primary metric should use numbers that match the actual gameplay experience (2,000-13,000 range)
- This better serves the Social Connection pillar by enabling natural trash talk and comparison
- Passes the "no tutorial needed" test from the design bible

**Where the committee disagrees:**
- Minor tension on "Best Day Score" — Player Advocate wants "Best Single Game" instead, Systems Designer thinks Best Day is fine as a secondary trophy stat. Social Designer didn't take a strong position.
- Systems Designer considered median vs average (median is more robust to outliers, but average is simpler and matches player intuition)

---

## Recommendation

### Feature Name
Career Average Score

### Description
Replace the all-time leaderboard's primary metric from "Avg Daily Score" (sum of top-10 daily games averaged across days) to "Average Game Score" (simple average of all eligible game scores). This produces numbers in the 5,000-9,000 range that players instantly recognize as Farkle scores.

### How It Works
1. **Primary sort metric:** Average score per eligible game (non-solo, non-bot, score > 0)
2. **Display as:** "Avg Game" or "Career Avg" in the score column
3. **Minimum qualifying games:** Keep at a reasonable threshold (e.g., 50 games) rather than qualifying days, since the metric is now per-game
4. **Secondary stat (optional):** "Best Game" (highest single game score ever) — replaces "Best Day Score"
5. **Computation:** Simple `AVG(playerscore)` across all qualifying games for each player
6. **Column in `farkle_lb_alltime`:** Replace `avg_daily_score` with `avg_game_score`, add `best_game_score`, add `total_games`

### Why It Fits
- **Fun First:** Players see a number they recognize from their own games — instant connection to the core loop
- **Social Connection:** "I'm a 7,200 average player" is a natural, brag-worthy statement that fuels rivalry
- **Simple to discover:** No explanation needed — everyone understands "average score per game"
- **Replayability:** Clear feedback on improvement over time without complex aggregation schemes

---

## Risks & Trade-offs

- Average game score could be gamed by only playing against weak opponents (but this is true of any metric)
- Players with fewer games may have inflated averages from lucky streaks — the minimum games threshold mitigates this
- Loses the "daily engagement" signal that avg daily score provided — but the daily and weekly boards already serve that purpose
- Migration requires recomputing from historical game data (straightforward with existing `farkle_games_players.playerscore`)

---

## Priority

**Suggested priority:** Must-have (before launch of Leaderboard 2.0)

**Rationale:** The all-time board is a core feature and showing confusing numbers on day one undermines trust in the entire leaderboard system.

---

## Open Questions

- Should the minimum be based on games played (e.g., 50 games) or qualifying days (e.g., 10 days)? Games played is more intuitive for a per-game metric.
- Should "Best Game" replace "Best Day" as the secondary stat, or show both?
- Should we weight recent games more heavily (e.g., last 90 days) to keep the metric feeling current, or use all-time unweighted?
