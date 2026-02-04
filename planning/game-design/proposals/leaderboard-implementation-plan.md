# Leaderboard 2.0 — Implementation Plan

**Date:** 2026-02-03
**Status:** Draft
**Companion:** [Design Proposal](leaderboard-redesign-daily-challenges.md) | [HTML Mockup](leaderboard-mockup.html)

---

## Scope Summary

Three-tier leaderboard (Daily, Weekly, All-Time) with a "best 10 of first 20 games" daily cap, friend rival view with movement arrows, rotating stat highlights, post-game feedback toast, and an always-visible daily game counter in the lobby.

---

## 1. Database Changes

### New Tables (5)

#### `farkle_lb_daily_games` — Per-game tracking within the daily 20-game cap

```sql
CREATE TABLE farkle_lb_daily_games (
    id SERIAL PRIMARY KEY,
    playerid INT NOT NULL REFERENCES farkle_players(playerid),
    gameid INT NOT NULL REFERENCES farkle_games(gameid),
    lb_date DATE NOT NULL,                -- date in US Central Time
    game_seq INT NOT NULL,                -- 1..20, sequence within the day
    game_score INT NOT NULL,              -- player's final score in this game
    counted BOOLEAN DEFAULT FALSE,        -- TRUE if in player's top 10
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(playerid, gameid),
    UNIQUE(playerid, lb_date, game_seq)
);
CREATE INDEX idx_lb_daily_games_player_date ON farkle_lb_daily_games(playerid, lb_date);
CREATE INDEX idx_lb_daily_games_date ON farkle_lb_daily_games(lb_date);
```

**How it works:** When a qualifying game finishes, insert a row. `game_seq` = `COUNT(*) + 1` for that player+date. If `game_seq > 20`, the row is stored but `counted = FALSE`. After each insert, recompute which 10 games are `counted = TRUE` (top 10 by `game_score DESC`).

#### `farkle_lb_daily_scores` — Aggregated daily leaderboard

```sql
CREATE TABLE farkle_lb_daily_scores (
    playerid INT NOT NULL REFERENCES farkle_players(playerid),
    lb_date DATE NOT NULL,
    games_played INT NOT NULL DEFAULT 0,
    top10_score INT NOT NULL DEFAULT 0,   -- sum of best 10 game scores
    qualifies BOOLEAN DEFAULT FALSE,      -- TRUE if games_played >= 3
    rank INT,
    prev_rank INT,                        -- yesterday's rank (for movement arrows)
    PRIMARY KEY (playerid, lb_date)
);
CREATE INDEX idx_lb_daily_scores_date_score ON farkle_lb_daily_scores(lb_date, top10_score DESC);
```

#### `farkle_lb_weekly_scores` — Aggregated weekly leaderboard

```sql
CREATE TABLE farkle_lb_weekly_scores (
    playerid INT NOT NULL REFERENCES farkle_players(playerid),
    week_start DATE NOT NULL,             -- Monday of the week
    daily_scores_used INT NOT NULL DEFAULT 0,
    top5_score INT NOT NULL DEFAULT 0,    -- sum of best 5 daily scores
    qualifies BOOLEAN DEFAULT FALSE,
    rank INT,
    prev_rank INT,                        -- last week's rank
    PRIMARY KEY (playerid, week_start)
);
CREATE INDEX idx_lb_weekly_scores_week ON farkle_lb_weekly_scores(week_start, top5_score DESC);
```

#### `farkle_lb_alltime` — Career average leaderboard

```sql
CREATE TABLE farkle_lb_alltime (
    playerid INT NOT NULL UNIQUE REFERENCES farkle_players(playerid),
    qualifying_days INT NOT NULL DEFAULT 0,
    total_daily_score BIGINT NOT NULL DEFAULT 0,
    avg_daily_score NUMERIC(10,2) NOT NULL DEFAULT 0,
    best_day_score INT DEFAULT 0,
    qualifies BOOLEAN DEFAULT FALSE,      -- TRUE if qualifying_days >= 30
    rank INT,
    prev_rank INT,
    last_updated TIMESTAMP DEFAULT NOW()
);
CREATE INDEX idx_lb_alltime_avg ON farkle_lb_alltime(avg_daily_score DESC) WHERE qualifies = TRUE;
```

#### `farkle_lb_stats` — Rotating stat highlights

```sql
CREATE TABLE farkle_lb_stats (
    id SERIAL PRIMARY KEY,
    playerid INT NOT NULL REFERENCES farkle_players(playerid),
    lb_date DATE NOT NULL,
    stat_type VARCHAR(30) NOT NULL,       -- 'hot_dice', 'farkle_rate', 'comeback_king', etc.
    stat_value NUMERIC(12,4) NOT NULL,
    stat_detail TEXT,                     -- JSON context (gameid, round, etc.)
    UNIQUE(playerid, lb_date, stat_type)
);
CREATE INDEX idx_lb_stats_type_date ON farkle_lb_stats(stat_type, lb_date, stat_value DESC);
```

### Modifications to Existing Tables (1)

```sql
ALTER TABLE farkle_players ADD COLUMN current_win_streak INT DEFAULT 0;
ALTER TABLE farkle_players ADD COLUMN best_win_streak INT DEFAULT 0;
```

Updated in `FarkleWinGame()`: increment winner's `current_win_streak`, reset losers' to 0. Update `best_win_streak = GREATEST(best_win_streak, current_win_streak)`.

### Schema Change Summary

| # | Change | Type |
|---|--------|------|
| 1 | `farkle_lb_daily_games` | New table |
| 2 | `farkle_lb_daily_scores` | New table |
| 3 | `farkle_lb_weekly_scores` | New table |
| 4 | `farkle_lb_alltime` | New table |
| 5 | `farkle_lb_stats` | New table |
| 6 | `farkle_players` + `current_win_streak`, `best_win_streak` | ALTER TABLE |
| 7 | 7 new indexes across the new tables | Indexes |

**No changes to core game tables** (`farkle_games`, `farkle_games_players`, `farkle_rounds`, `farkle_sets`). All new tables are additive.

---

## 2. Backend Changes (PHP)

### Game Completion Hook

**Where:** `FarkleWinGame()` in `wwwroot/farkleGameFuncs.php`

After existing win/loss logic, add a call to:

```php
Leaderboard_RecordEligibleGame($playerId, $gameId, $score, $rounds, $gameWith);
```

**Eligibility check:**
- `$gameWith != 2` (not solo)
- Opponent is not a bot (`is_bot = FALSE`)
- `$score >= 1000`
- `$rounds >= 3`

If eligible:
1. Get today's Central Time date: `DATE(NOW() AT TIME ZONE 'America/Chicago')`
2. Count existing games for this player+date in `farkle_lb_daily_games`
3. If count < 20: insert with `game_seq = count + 1`
4. Recompute `counted` flags: top 10 by `game_score DESC` get `counted = TRUE`
5. Recompute `farkle_lb_daily_scores` for this player+date (sum of counted games)
6. Also update `current_win_streak` / `best_win_streak` on `farkle_players`

### New Functions in `farkleLeaderboard.php`

| Function | Purpose | When Called |
|----------|---------|-------------|
| `Leaderboard_RecordEligibleGame()` | Insert into daily_games, recompute daily score | On game completion |
| `Leaderboard_ComputeDailyScore($pid, $date)` | Sum top 10 scores for a player+date | On game completion |
| `Leaderboard_GetDailyProgress($pid)` | Return games_played, games_max, daily_score, top_scores | Lobby load, post-game |
| `Leaderboard_GetPostGameFeedback($pid, $score)` | Return rank_in_top10, games_remaining | Post-game response |
| `Leaderboard_GetBoard($tier, $scope, $pid)` | Fetch ranked board (daily/weekly/alltime, friends/everyone) | Leaderboard view |
| `Leaderboard_GetFriendBoard($pid, $tier)` | Friends-only view with movement arrows | Leaderboard view |
| `Leaderboard_ComputeWeeklyScore($pid, $weekStart)` | Sum best 5 daily scores for the week | Hourly cron |
| `Leaderboard_ComputeAllTimeScore($pid)` | Career average daily score | Nightly cron |

### New File: `wwwroot/farkleLeaderboardStats.php`

Rotating stat computation functions:

| Function | SQL Source | Already Tracked? |
|----------|-----------|-----------------|
| `LBStats_HotDice($date)` | `MAX(roundscore) FROM farkle_rounds` | Yes — `farkle_rounds.roundscore` |
| `LBStats_FarkleRate($date)` | `COUNT(roundscore=0) / COUNT(*)` from `farkle_rounds` | Yes |
| `LBStats_ComebackKing($date)` | Player trailing after round 5 but winning. Join `farkle_rounds` + `farkle_games.winningplayer` | Yes — complex query but data exists |
| `LBStats_HotStreak($date)` | `MAX(current_win_streak)` from `farkle_players` | **New** — needs `current_win_streak` column |
| `LBStats_Consistency($date)` | STDDEV of top-10 game scores from `farkle_lb_daily_games` | Yes — computed from new table |
| `LBStats_GreediestRoll($date)` | Most dice re-rolled (6 - saved dice count) from `farkle_sets` | Yes — `d1save..d6save` |

All materialized into `farkle_lb_stats` by cron and read from there for display.

### New API Actions in `farkle_fetch.php`

| Action | Returns | Cache |
|--------|---------|-------|
| `getleaderboard2` | Ranked board for tier+scope with scores, ranks, arrows, labels, stat values | 60s session |
| `getdailyprogress` | `{games_played, games_max:20, daily_score, daily_rank, top_scores[]}` | None (real-time) |
| `getrotatingstats` | Top 20 for current featured stat | 60s |

Post-game: Attach `leaderboard_feedback` to existing game-completion response payload (no new endpoint needed).

### Cron Job Changes

| Schedule | Task | File |
|----------|------|------|
| **Hourly** | Recompute `farkle_lb_weekly_scores` for current week. Refresh rotating stats. | `farkleCronHourly.php` |
| **Nightly** | Finalize previous day's daily ranks. Snapshot ranks → `prev_rank` for movement arrows. Recompute `farkle_lb_alltime` for all players. Clean up `farkle_lb_daily_games` older than 90 days. | `farkleCronNightly.php` |

### Migration Strategy

- Keep existing `farkle_lbdata` system running in parallel during rollout
- New tables are additive — no existing data modified
- **Historical backfill possible** from `farkle_games_players` + `farkle_games`:

```sql
INSERT INTO farkle_lb_daily_games (playerid, gameid, lb_date, game_seq, game_score)
SELECT gp.playerid, g.gameid,
       (g.gamefinish AT TIME ZONE 'America/Chicago')::DATE,
       ROW_NUMBER() OVER (
         PARTITION BY gp.playerid, (g.gamefinish AT TIME ZONE 'America/Chicago')::DATE
         ORDER BY g.gamefinish
       ),
       gp.playerscore
FROM farkle_games_players gp
JOIN farkle_games g ON g.gameid = gp.gameid
WHERE g.winningplayer IS NOT NULL
  AND g.gamewith IN (0, 1)
  AND g.gamefinish IS NOT NULL
  AND gp.playerscore >= 1000;

-- Remove rows beyond the 20-game cap
DELETE FROM farkle_lb_daily_games WHERE game_seq > 20;

-- Then run aggregation to populate daily_scores, weekly_scores, alltime
```

Win streaks cannot be perfectly reconstructed historically — start tracking from launch.

---

## 3. Frontend Changes (JS/Templates/CSS)

### Template: `farkle_div_leaderboard.tpl` — Full Rewrite

```
New structure:
├── #leaderboard2
│   ├── #lb-tier-tabs          (Daily | Weekly | All-Time)
│   ├── #lb-scope-toggle       (Friends | Everyone)
│   ├── #lb-weekly-badges      (Mon–Sun day badges, weekly tab only)
│   ├── #lb-stat-banner        (Rotating stat spotlight)
│   ├── #lb-toast              (Post-game feedback, auto-dismiss)
│   ├── #lb-table-container    (Dynamic leaderboard rows)
│   ├── #lb-h2h-card           (Head-to-head for 2-3 friends)
│   └── #lb-your-score-bar     (Your rank + gap to #1)
```

### Template: `farkle_div_lobby.tpl` — Add Game Counter

Add `#daily-game-counter` div above game buttons:
- Label: "Daily Games: X/20"
- Progress bar with green fill
- After 20 games: bar turns gold, text reads "All 20 games played — check your score!"

### JavaScript: `farkleLeaderboard.js` — Major Rewrite

**New data model:**
```javascript
g_lb2 = {
  daily:   { friends: [], everyone: [], myScore: {} },
  weekly:  { friends: [], everyone: [], dayScores: [] },
  alltime: { friends: [], everyone: [] },
  rotatingStat: { name: '', entries: [] },
  progress: { gamesPlayed: 0, gamesMax: 20, dailyScore: 0 }
};
```

**New functions:**

| Function | Purpose |
|----------|---------|
| `GetLeaderBoardData2(tier, scope)` | AJAX fetch for new leaderboard |
| `RenderLeaderboard(tier, scope)` | Main render dispatcher |
| `RenderFriendRows(entries, myScore)` | Render friend rows with arrows + labels |
| `RenderWeeklyBadges(dayScores)` | Render Mon–Sun day badges |
| `RenderH2HCard(friends)` | Head-to-head card for 2-3 friends |
| `switchLeaderboardTier(tier)` | Tab handler |
| `switchLeaderboardScope(scope)` | Friends/Everyone toggle |
| `getPlayfulLabel(entry, myEntry)` | Compute label text |
| `getArrowClass(rank, prevRank)` | Compute movement arrow |

**Playful label logic:**
- Within 500 pts of viewer → "Right behind you"
- 3+ games above their average → "On a heater"
- Moved up 2+ spots since yesterday → "Catching up..."
- 1,500+ point gap to next → "Comfortable lead"
- Rank 1 → "Pace setter"
- 20/20 games played → "All done"

**Movement arrow logic:**
- `rank < prevRank` → green up triangle
- `rank > prevRank` → red down triangle
- `rank == prevRank` → gray dash
- No previous rank → "NEW" badge

### JavaScript: `farkleGame.js` — Post-Game Toast

After game-end XP display, call `showLeaderboardToast()`. Reads `leaderboard_feedback` from game-completion response. Renders toast in `#lb-toast`, auto-dismissed after 5 seconds with CSS fade.

### JavaScript: `farkleLobby.js` — Game Counter

Add `updateDailyGameCounter(played, max)`. Called on lobby load from existing lobby data response (backend adds `daily_games_played` to payload).

### CSS: `farkle.css` — New Styles

New classes needed:
- `.lb-row`, `.lb-row.me` — Row styling with gold highlight for current player
- `.arrow-up`, `.arrow-down`, `.arrow-same`, `.arrow-new` — Movement indicators
- `.lb-label`, `.label-hot`, `.label-close`, `.label-lead`, `.label-catching` — Playful labels
- `#lb-toast` — Post-game feedback toast with fade animation
- `.day-badge`, `.badge-played`, `.badge-today`, `.badge-future` — Weekly badges
- `.rivalry-card` — Head-to-head card
- `#daily-game-counter`, `.counter-bar`, `.counter-fill` — Lobby counter
- `.rating-badge` — All-time rating display
- `#lb-your-score-bar` — Bottom summary bar

---

## 4. Complete File Change List

| File | Action | What Changes |
|------|--------|-------------|
| `docker/init.sql` | Modify | Add 5 new table definitions + indexes |
| `scripts/migrate-leaderboard2.sql` | **Create** | Migration: CREATE TABLEs + ALTER TABLE + backfill queries |
| `wwwroot/farkleGameFuncs.php` | Modify | Add `Leaderboard_RecordEligibleGame()` call in `FarkleWinGame()`. Update win streak columns. |
| `wwwroot/farkleLeaderboard.php` | Modify | Add 8 new functions for daily/weekly/alltime computation, friend board, post-game feedback. Keep existing functions. |
| `wwwroot/farkleLeaderboardStats.php` | **Create** | Rotating stat computation (Hot Dice, Farkle Rate, Comeback King, etc.) |
| `wwwroot/farkle_fetch.php` | Modify | Add `getleaderboard2`, `getdailyprogress` handlers. Attach `leaderboard_feedback` to game completion. |
| `wwwroot/farkleCronHourly.php` | Modify | Add weekly score recomputation + rotating stats refresh |
| `wwwroot/farkleCronNightly.php` | Modify | Add all-time recomputation, rank snapshots, daily finalization, cleanup |
| `js/farkleLeaderboard.js` | Modify | Major rewrite: new data model, rendering, tab/scope switching, labels, arrows |
| `js/farkleGame.js` | Modify | Add `showLeaderboardToast()` at game-end flow |
| `js/farkleLobby.js` | Modify | Add `updateDailyGameCounter()` on lobby load |
| `templates/farkle_div_leaderboard.tpl` | Modify | Full rewrite with new div structure |
| `templates/farkle_div_lobby.tpl` | Modify | Add `#daily-game-counter` div |
| `css/farkle.css` | Modify | Add ~15 new style classes |
| `includes/version.php` or `baseutil.php` | Modify | Bump version |
| `data/release-notes.json` | Modify | Add release notes entry |

**Totals: 2 new files, 14 modified files**

---

## 5. What Data Already Exists vs. What's New

| Data Point | Already Tracked? | Source |
|------------|-----------------|--------|
| Final game score per player | Yes | `farkle_games_players.playerscore` |
| Game completion timestamp | Yes | `farkle_games.gamefinish` |
| Game type (solo/friends/random) | Yes | `farkle_games.gamewith` |
| Bot flag | Yes | `farkle_players.is_bot` |
| Round scores | Yes | `farkle_rounds.roundscore` |
| Farkle detection (roundscore=0) | Yes | `farkle_rounds` |
| Per-roll dice values | Yes | `farkle_sets.d1-d6, d1save-d6save` |
| Winner of each game | Yes | `farkle_games.winningplayer` |
| Friend relationships | Yes | `farkle_friends` |
| Daily game count per player | **New** | `farkle_lb_daily_games` |
| "Best 10 of 20" selection | **New** | `farkle_lb_daily_games.counted` |
| Daily aggregated score | **New** | `farkle_lb_daily_scores` |
| Weekly aggregated score | **New** | `farkle_lb_weekly_scores` |
| Career average daily score | **New** | `farkle_lb_alltime` |
| Yesterday's rank (for arrows) | **New** | `prev_rank` columns |
| Win streaks | **New** | `farkle_players.current_win_streak` |
| Materialized rotating stats | **New** | `farkle_lb_stats` |

**Key insight:** All the raw data for computing stats (scores, rounds, farkles, dice, wins) already exists in the database. The new tables are aggregation/cache layers — we're not adding new game-time tracking, just new ways of slicing existing data.

---

## 6. Implementation Phases (Engineering)

### Phase 1: Foundation (Database + Backend Core)
1. Create migration script with all 5 new tables + ALTER TABLE
2. Run migration on local Docker + Heroku
3. Add `Leaderboard_RecordEligibleGame()` hook in `FarkleWinGame()`
4. Add daily score computation functions
5. Run historical backfill script
6. Add `getdailyprogress` API endpoint
7. Add daily game counter to lobby (template + JS)

### Phase 2: Leaderboard UI
8. Rewrite leaderboard template with new structure
9. Rewrite `farkleLeaderboard.js` with new data model
10. Implement `getleaderboard2` API endpoint
11. Build daily board with friend rival view
12. Build weekly board with day badges
13. Build all-time board
14. Add Friends/Everyone toggle
15. Add movement arrows + playful labels
16. Add CSS for all new components

### Phase 3: Enrichment
17. Add post-game feedback toast
18. Add rotating stat computation (new PHP file)
19. Add stat banner to leaderboard
20. Add head-to-head card for small friend groups
21. Add "your score" summary bar
22. Add cron job changes (hourly + nightly)
23. Bump version + release notes
