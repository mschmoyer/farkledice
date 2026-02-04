# Game Design Proposal: Leaderboard 2.0

**Date:** 2026-02-03
**Status:** Draft (Round 2 — Refined)

---

## Topic

Making the leaderboard as fun, interactive, and socially engaging as possible. Three board tiers (daily, weekly, all-time), a "best 10 of first 20" daily game cap, rotating stats, friend rivalry, and shared challenges.

---

## Committee Perspectives (Round 2)

### The Player Advocate
- **Explain "best 10 of 20" simply:** "Play up to 20 games today — your 10 highest scores count." The UI needs real-time feedback after every game: "This score ranked #6 in your daily top 10!" or "This score didn't make your top 10 — 8 games left to improve."
- **Default the leaderboard to Weekly + Friends.** That's where the rivalry lives. Global all-time is a trophy case, not the daily draw.
- **Keep stats to 3-4 rotating highlights, not a stats page.** Each one should be a conversation starter: Hot Dice Moment, Farkle Rate, Comeback King, Greediest Roll, Ice Cold (most consecutive farkles).
- **Rank daily board by sum of best 10 scores.** Average punishes players who play fewer games, violating "respect the player's time."
- **After 20 games, let players keep playing** with a "just for fun" label so they don't feel locked out.

### The Systems Designer
- **Score = final game score, regardless of win/loss.** A loss at 9,200 was a better performance than a win at 10,050 against a weak opponent. This rewards quality, not matchup luck.
- **Both Standard and 10-round feed one combined board.** Score ranges are comparable. Separate boards fragment a small player base.
- **Weekly = sum of your best 5 daily scores out of 7 days.** Skipping 2 days guilt-free prevents obligation. ~350K-425K for a strong week.
- **All-time = career average daily score** (minimum 30 qualifying days to appear). Prevents "who played longest" and becomes a player identity metric/rating.
- **Exploit guardrails:** Only human-opponent games count. Games under 1,000 points or fewer than 3 rounds don't consume a daily slot. Games count toward the day they were started (uses `created_date`).
- **Weekly challenge examples:** "No Farkle Five" (5 games with zero farkles), "The 8K Club" (3 games scoring 8,000+), "Social Butterfly" (play 5 different opponents).

### The Social Designer
- **Friend Rival View is the killer feature.** Show friends ranked by daily score, movement arrows vs. yesterday's final position, games remaining today, and warm playful labels ("Right behind you," "On a heater," "Catching up...").
- **End-of-day summary is the screenshot moment:** "Today's results: You finished 2nd. Mike edged you by 120 points. You had the day's highest single game." End-of-week: mini awards ceremony with champion, most improved, most farkles.
- **Design for the 3-person friend group.** That's the real audience. For 2-3 friends, show a head-to-head rivalry card instead of a ranked list.
- **Solo players get a skill-bracket board** with percentile ranking and a gentle prompt to add friends.
- **Hard cap notifications:** One rival-passed-you nudge per friend per day, max 3 total. Easy mute.

---

## Consensus & Tension

**Where the committee agrees:**
- "Best 10 of first 20" is the right mechanic — needs strong UI feedback to make it intuitive
- Sum of best 10 scores for daily ranking
- Weekly board is the primary social driver; best 5 of 7 days prevents obligation
- Friend Rival View is the emotional core — build it first
- Both game modes on one combined board
- Only human-opponent games count for leaderboard
- Stats should be curated and rotating (3-4 at a time), not exhaustive
- End-of-day/week summaries are high-value social moments
- Let players keep playing after 20 games ("just for fun" mode)

**Where the committee disagrees:**
- **All-time board metric:** Systems Designer wants career average daily score (skill-focused). Player Advocate leans toward something more accessible. Resolution: career average daily score with a 30-day minimum, but also show total games played and career high day as secondary stats.
- **Notification cadence:** Player Advocate wants minimal nudges. Social Designer wants richer end-of-day stories. Resolution: opt-in daily summaries, very conservative real-time nudges.

---

## Recommendation

### Feature Name
**Leaderboard 2.0: Daily Board, Weekly Rivals, and All-Time Ratings**

### Description
A three-tier leaderboard system with a daily game cap that prevents grinding, a weekly friend rivalry board that creates conversation, and an all-time rating that rewards consistency over volume. Rotating stats and shared challenges give players new things to chase every day.

---

### How It Works

#### The Three Boards

**Daily Board**
- Each day, a player's first 20 completed games (vs human opponents) are eligible
- Their daily score = sum of the 10 highest game scores from those 20
- Games under 1,000 points or fewer than 3 rounds don't count as one of the 20 (prevents forfeit spam)
- After 20 eligible games, players can keep playing freely — games count for XP, achievements, and all-time stats, just not the daily board
- A progress tracker shows: "Games: 14/20 | Your top 10 score: 72,400"
- After each game, feedback: "This score ranked #4 in your daily top 10!" or "Didn't crack your top 10 — 6 games left"
- Minimum 3 completed games to appear on the daily board (prevents 1-game flukes)
- Resets at midnight US Central Time (CT)
- Games count toward the day they were **started**, not finished

**Weekly Board**
- Weekly score = sum of a player's best 5 daily scores out of the 7 days (Mon–Sun)
- Missing 2 days has zero penalty — no obligation to play every day
- Primary rivalry timeframe for friend groups
- Resets every Monday

**All-Time Board**
- Career rating = average daily score across all qualifying days
- Minimum 30 qualifying days to appear on the board (prevents one-day wonders)
- Secondary stats shown: total days played, career-high daily score, total career games
- This becomes the player's permanent "skill rating"

#### Friend Rival View

This is the **default view** when opening the leaderboard (if the player has friends).

**What you see:**
- Your friends ranked by the current board's score (daily, weekly, or all-time)
- Your own row is always highlighted and visible
- Each friend row shows:
  - Rank and score
  - Movement arrow vs. yesterday's final position (daily) or last week's final (weekly)
  - Games remaining today (e.g., "16/20")
  - A playful label when applicable:
    - "Right behind you" — within 500 points
    - "On a heater" — 3+ games above their average
    - "Catching up..." — moved up 2+ spots since yesterday
    - "Comfortable lead" — 1,500+ point gap to next friend

**For 2-3 friend groups:** Instead of a list, show a head-to-head rivalry card with direct matchup stats.

**For solo players (no friends):** Show a skill-bracket leaderboard with percentile ("Top 18% of daily players") and a prompt: "Invite someone to start a rivalry."

#### Rotating Stat Highlights

3-4 stats spotlighted per week, built into the daily and weekly board views. A "stat of the day" banner at the top of the leaderboard. Each player's value for that stat shown on their row.

**Stat Pool (rotate through):**

| Stat | What It Measures | Why It's Fun |
|------|-----------------|--------------|
| **Hot Dice** | Highest single-turn score | The "did you see THAT?" moment |
| **Farkle Rate** | % of turns that farkled | Low = skilled, high = entertaining |
| **Comeback King** | Largest deficit overcome to win | Creates hero stories |
| **Greediest Roll** | Most dice re-rolled when banking was safe | Celebrates push-your-luck spirit |
| **Ice Cold** | Most consecutive farkles in one game | Hilarious misfortune people screenshot |
| **Consistency Score** | Lowest variance across counting games | "Steady Eddie" vs. "Wild Card" |
| **Speed Demon** | Fastest average game completion | For the efficient players |
| **Head-to-Head** | Direct matchup record vs. each friend this week | Personal rivalry fuel |

#### Shared Weekly Challenge

One challenge per week, same for all players. Displayed as a banner on the leaderboard page.

**Design:**
- Completing it earns a small XP bonus and a checkmark badge visible to friends for the rest of the week
- Friends can see each other's progress: "Sarah (done!), You (best so far: 4,200), Mike (not attempted)"
- No streak tracking, no punishment for skipping — purely opt-in
- Target ~60-70% completion rate among active players

**Example Challenges:**

| Challenge | Criteria | Type |
|-----------|----------|------|
| "No Farkle Five" | Complete 5 games with 0 farkles | Skill |
| "The 8K Club" | Score 8,000+ in 3 separate games | Skill |
| "Hot Dice Hunter" | Use all 6 dice in a single turn, 3 times this week | Luck + Skill |
| "Marathon Runner" | Play 15 games vs human opponents | Engagement |
| "Social Butterfly" | Play games against 5 different opponents | Social |

#### End-of-Day / End-of-Week Summaries

**End-of-day (opt-in notification/in-app message):**
> "Today's results: You finished 2nd among friends. Mike's 74,200 edged you by 1,800. You had the day's highest single game (8,350). You used 18 of 20 games."

**End-of-week (in-app banner on Monday):**
> "This week's champion: Dad (380,200). Most improved: You (+12,000 over last week). Most farkles: Uncle Steve (47). Challenge completed by: Sarah, Dad."

These are designed to be screenshot-worthy — extending the social energy beyond the game.

---

### Why It Fits

| Pillar | How This Serves It |
|--------|-------------------|
| **Fun First** | Every game matters (best 10 of 20 creates tension), rotating stats celebrate fun moments, challenges add variety |
| **Replayability** | Daily resets, weekly cycles, rotating stats — there's always a reason to play today |
| **Social Connection** | Friend Rival View is the default, playful labels create kitchen-table banter, end-of-day summaries are shareable |
| **Async-First** | Everything works without both players online. Check the board when you want. Best 5 of 7 days means no daily obligation |

---

## Risks & Trade-offs

- **"Best 10 of first 20" needs excellent UI feedback** to feel intuitive. Without the progress tracker and post-game messaging, it will confuse casual players.
- **Combined board for Standard + 10-round** assumes score ranges are comparable. Need to validate with real game data. If 10-round scores skew dramatically different, may need normalization or separate boards.
- **Timezone:** US Central Time was chosen. Players in other US timezones will see reset at 11pm PT / 1am ET, which is close enough for most.
- **Career average for all-time board** means a new player who has one amazing month and then declines will watch their rating slowly drop. This is correct behavior (it reflects current skill) but may feel bad.
- **Database load:** Daily/weekly aggregation queries against game tables will need summary/cache tables. Don't compute on every leaderboard page load.
- **Small friend groups (2 people):** The leaderboard could feel sparse. The head-to-head rivalry card design addresses this, but it's a separate UI component to build.
- **Challenge curation is ongoing work.** Bad challenges (too easy, too hard, or promoting anti-fun play) can undermine the feature. Need a bank of ~50 challenges to rotate through.
- **Games-played counter ("16/20") could create anxiety** for some players. Consider making it collapsible or only showing it on hover/tap.

---

## Priority

**Suggested priority:** Should-have

**Rationale:** The leaderboard is identified in the design bible as the strongest retention driver, but it's currently passive. This transforms it into a daily destination with social energy. The daily cap solves a fairness problem that likely already exists.

---

## Implementation Phases

| Phase | What | Why First |
|-------|------|-----------|
| **Phase 1** | Daily board (best 10 of 20) + Weekly board (best 5 of 7) + All-time board (career average) + Friend Rival View as default | Core value. The three boards and friend view are the foundation everything else builds on. |
| **Phase 2** | Rotating stat highlights + post-game feedback ("This ranked #4 in your top 10!") + playful labels on friend rows | Enrichment. Makes the boards feel alive and personal. |
| **Phase 3 (Deferred)** | Shared weekly challenges + end-of-day/week summaries | Social layer. Ship after core boards are proven. |

---

## Resolved Decisions

| # | Question | Decision |
|---|----------|----------|
| 1 | Combined vs. separate boards for Standard and 10-round? | **Combined board.** Both modes feed one leaderboard. |
| 2 | Timezone for daily reset? | **US Central Time (CT).** Midnight CT resets the daily board. |
| 3 | Minimum games to appear on daily board? | **Minimum 3 games.** Must complete at least 3 games to qualify for a daily score. |
| 4 | Bot/solo games and daily slots? | **Completely separate.** Bot games don't count for leaderboard and don't consume any of the 20 daily slots. |
| 5 | 20-game counter visibility? | **Always visible in lobby.** Show "Daily Games: 14/20" in the lobby at all times. |
| 6 | Where does career rating appear? | **Leaderboard + player profile.** Shown on the all-time board and on each player's profile page. |
| 7 | Weekly challenges? | **Deferred.** Ship Phase 1 and 2 first. Add challenges later once core boards are proven. |

## Remaining Open Questions

1. **Score distribution validation:** Confirm with real game data that Standard and 10-round scores are comparable before shipping the combined board.
2. **Post-game feedback wording:** Exact copy for "This score ranked #4 in your daily top 10!" messages — needs to feel celebratory without being noisy.
