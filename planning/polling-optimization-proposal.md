# Polling Optimization Proposal

**Date:** 2026-02-04
**Context:** Performance optimization follow-up to Phase 1 (database/session improvements)

---

## Current Polling Behavior Analysis

### 1. **Game Mode** (`farkleGame.js`)
- **Interval:** 10 seconds (fixed)
- **Duration:** 30 ticks = 5 minutes total
- **Idle:** Stops polling after 5 minutes
- **Endpoint:** `farklegetupdate`

**Current Strategy:**
```javascript
// Fast poll for 30 ticks (5 minutes)
if (timer_ticks < 30) {
    setTimeout(farkleGetUpdate, 10000); // 10 seconds
} else {
    GameGoIdle(); // Stop polling
}
```

### 2. **Lobby Mode** (`farkleLobby.js`)
- **Phase 1 (0-20 ticks):** 10 seconds → 3.3 minutes
- **Phase 2 (20-40 ticks):** 20 seconds → 6.6 minutes
- **Total duration:** ~10 minutes before idle
- **Endpoint:** `getlobbyinfo`

**Current Strategy:**
```javascript
if (gLobbyTimer_ticks < 20) {
    setTimeout(GetLobbyInfo, 10000);  // 10s for first 3.3 min
} else if (gLobbyTimer_ticks < 40) {
    setTimeout(GetLobbyInfo, 20000);  // 20s for next 6.6 min
} else {
    LobbyGoIdle(); // Stop polling
}
```

### 3. **Tournament Mode** (`farkleTournament.js`)
- **Interval:** 15 seconds (fixed)
- **No idle timeout** (polls indefinitely)
- **Endpoint:** `gettournamentinfo`

### 4. **Leaderboard** (`farkleLeaderboard.js`)
- **No automatic polling** ✅
- Only refreshes on user action or game completion

---

## Problem Assessment

### Issues with Current Polling:

1. **Game Mode Over-Polling**
   - **10-second interval is too aggressive** when waiting for opponent's turn
   - In a 4-player game, you might wait 3-5 minutes for your turn
   - Generates 18 requests during a typical 3-minute wait
   - **Impact:** Unnecessary server load, database queries, session writes

2. **Lobby Doesn't Need Real-Time Updates**
   - **10-second lobby polling is excessive**
   - Lobby shows: active games, friend list, stats
   - None of these need sub-30-second freshness
   - Users manually navigate to lobby, can refresh if needed
   - **Impact:** Wastes server resources on background updates

3. **No Differentiation by Player Count**
   - Solo games don't need any polling (you always control the turn)
   - 2-player games need faster polling than 4-player games
   - Current system treats all games the same

4. **Tournament Polling Never Idles**
   - Tournaments are infrequent events
   - 15-second fixed polling continues indefinitely
   - Should use exponential backoff

5. **No Activity-Based Adjustment**
   - Polling doesn't respond to user activity
   - Active player gets same polling as idle player
   - Browser visibility API not utilized

---

## Proposed Solutions

### 🎯 **Strategy 1: Adaptive Game Polling**

**Goal:** Reduce game polling by 60-80% while maintaining responsiveness

#### Solo Games: ❌ **NO POLLING**
```javascript
// Solo games: player controls all turns, no need to poll
if (gameData.isSolo) {
    // No polling - only update on player actions
    return;
}
```
**Impact:** Eliminates 100% of polling for solo games

#### 2-Player Games: **Fast → Medium → Slow**
```javascript
// More responsive for head-to-head games
if (gameData.playerCount == 2) {
    if (ticks < 6) {
        poll = 5000;   // 5s for 30 seconds (fast response)
    } else if (ticks < 18) {
        poll = 15000;  // 15s for next 3 minutes
    } else if (ticks < 30) {
        poll = 30000;  // 30s for next 6 minutes
    } else {
        idle();        // Stop after 9.5 minutes
    }
}
```

#### 3-4 Player Games: **Medium → Slow → Very Slow**
```javascript
// Longer waits expected in multiplayer
if (gameData.playerCount >= 3) {
    if (ticks < 4) {
        poll = 10000;  // 10s for 40 seconds (initial check)
    } else if (ticks < 10) {
        poll = 30000;  // 30s for next 3 minutes
    } else if (ticks < 20) {
        poll = 60000;  // 60s for next 10 minutes
    } else {
        idle();        // Stop after ~14 minutes
    }
}
```

**Expected Impact:**
- Solo games: **100% reduction** (no polling)
- 2-player games: **40% reduction** (10s → mixed 5s/15s/30s avg)
- 3-4 player games: **70% reduction** (10s → mixed 10s/30s/60s avg)
- **Overall: 60-70% fewer game polls**

---

### 🎯 **Strategy 2: Slower Lobby Polling**

**Goal:** Reduce lobby load by 75%

#### Current: 10s → 20s → idle
#### Proposed: 30s → 60s → idle

```javascript
// Lobby doesn't need real-time updates
if (gLobbyTimer_ticks < 10) {
    poll = 30000;  // 30s for first 5 minutes
} else if (gLobbyTimer_ticks < 20) {
    poll = 60000;  // 60s for next 10 minutes
} else {
    idle();        // Stop after 15 minutes
}
```

**User Refresh Button:**
- Add prominent "Refresh" button to lobby
- Users can manually refresh if waiting for specific update
- Educates users that lobby isn't real-time (sets expectations)

**Expected Impact:**
- **75% reduction** in lobby polling (10s → 30s avg)
- Minimal UX impact (lobby not time-sensitive)

---

### 🎯 **Strategy 3: Browser Visibility API**

**Goal:** Stop all polling when tab is not visible

```javascript
// Stop polling when user switches tabs
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Tab not visible - pause all polling
        clearTimeout(gGameTimer);
        clearTimeout(gLobbyTimer);
        gPollingPaused = true;
    } else {
        // Tab visible again - resume with immediate poll
        gPollingPaused = false;
        if (gCurrentView === 'game') {
            farkleGetUpdate(1);  // Immediate update on return
        } else if (gCurrentView === 'lobby') {
            GetLobbyInfo(1);
        }
    }
});
```

**Expected Impact:**
- **50-80% reduction** when users have multiple tabs open
- Common scenario: user plays turn, switches tab while waiting
- Immediate update on tab return keeps UX smooth

---

### 🎯 **Strategy 4: Smart Tournament Polling**

**Goal:** Reduce tournament polling by 60%

#### Current: Fixed 15s indefinitely
#### Proposed: Exponential backoff with caps

```javascript
// Tournament state drives polling frequency
if (tournament.status === 'active' && tournament.inActiveRound) {
    poll = 15000;  // 15s during active rounds
} else if (tournament.status === 'active') {
    poll = 60000;  // 60s between rounds
} else {
    poll = 300000; // 5 minutes for upcoming tournaments
}

// Max 20 minutes, then require user refresh
if (ticks > 80) {
    idle();
}
```

**Expected Impact:**
- **60% reduction** in tournament polling
- Responsive during active play
- Efficient during waiting periods

---

### 🎯 **Strategy 5: Conditional Polling Based on Game State**

**Goal:** Poll faster when it's "almost your turn"

```javascript
// Speed up polling when you're next
if (gameData.nextPlayer === playerid) {
    poll = poll / 2;  // Double speed when you're next
}

// Slow down when you just played
if (gameData.lastPlayer === playerid) {
    poll = poll * 1.5;  // 50% slower right after your turn
}
```

**Expected Impact:**
- Better UX (faster when relevant)
- Less load (slower when irrelevant)

---

## Implementation Plan

### Phase 1: Low-Hanging Fruit (Week 1)
- ✅ **Disable solo game polling** (biggest win, zero risk)
- ✅ **Add Visibility API** (stop polling on hidden tabs)
- ✅ **Slow lobby to 30s/60s** (low UX impact)

**Expected:** 50% overall polling reduction

### Phase 2: Adaptive Game Polling (Week 2)
- ✅ **Implement player-count-based polling**
- ✅ **Add "next player" detection**
- ✅ **Test with multi-player games**

**Expected:** Additional 20% reduction (70% total)

### Phase 3: Tournament Optimization (Week 3)
- ✅ **Tournament state-based polling**
- ✅ **Exponential backoff for idle tournaments**

**Expected:** Additional 5% reduction (75% total)

---

## Risk Assessment

| Change | Risk | Mitigation |
|--------|------|------------|
| Solo game no-poll | **LOW** | Solo = no opponents, polling unnecessary |
| Visibility API | **LOW** | Immediate poll on return maintains UX |
| Slower lobby | **LOW** | Add refresh button, lobby not time-critical |
| Adaptive game polling | **MEDIUM** | Test multiplayer scenarios, ensure notifications work |
| Tournament backoff | **LOW** | Tournaments are infrequent, users can refresh |

---

## Success Metrics

### Server Load Reduction:
- **Target:** 60-75% fewer AJAX requests
- **Measure:** `SELECT COUNT(*) FROM logs WHERE endpoint LIKE '%getupdate%'`

### Database Query Reduction:
- **Target:** 60-75% fewer SELECT queries during polling
- **Measure:** PostgreSQL query logs

### Session Write Reduction:
- **Target:** Combined with Phase 1 changes = 85-90% total reduction
- **Measure:** Session write skip rate

### User Experience:
- **Target:** No increase in "game felt slow" complaints
- **Measure:** User feedback, support tickets
- **Baseline:** Current response time perceptions

---

## A/B Testing Recommendation

**Rollout Strategy:**
1. Deploy to 10% of users (canary)
2. Monitor for 48 hours
3. Check metrics:
   - Poll count reduction
   - User session duration (should not decrease)
   - Error rates (should not increase)
4. If successful, rollout to 50%, then 100%

**Rollback Plan:**
- Feature flag: `ENABLE_ADAPTIVE_POLLING`
- Can disable via config without code deploy
- Reverts to current 10s polling

---

## Code Locations

### Files to Modify:
1. **`wwwroot/js/farkleGame.js`** - Game polling logic
   - Lines 191-202: `GameTimerTick()`
   - Add: Adaptive polling based on player count

2. **`wwwroot/js/farkleLobby.js`** - Lobby polling
   - Lines 70-85: Lobby timer logic
   - Change: 10s/20s → 30s/60s

3. **`wwwroot/js/farkleTournament.js`** - Tournament polling
   - Line 166: Fixed 15s timeout
   - Add: State-based backoff

4. **`wwwroot/js/farklePage.js`** - Page visibility
   - Add: Visibility API listener
   - Pause/resume all timers

### New Configuration:
```php
// includes/config.php or similar
define('POLL_SOLO_ENABLED', false);           // Disable solo polling
define('POLL_2P_INTERVALS', [5, 15, 30]);     // 2-player game intervals (seconds)
define('POLL_MULTIPLAYER_INTERVALS', [10, 30, 60]); // 3-4 player intervals
define('POLL_LOBBY_INTERVALS', [30, 60]);     // Lobby intervals
define('POLL_TOURNAMENT_ACTIVE', 15);         // Tournament active round
define('POLL_TOURNAMENT_IDLE', 300);          // Tournament idle
```

---

## Estimated Impact Summary

### Server Load:
| Metric | Current | Optimized | Reduction |
|--------|---------|-----------|-----------|
| Game polls/hour | ~360 req | ~100 req | **72%** |
| Lobby polls/hour | ~360 req | ~90 req | **75%** |
| Tournament polls/hour | ~240 req | ~100 req | **58%** |
| **Total polls/hour** | **~960** | **~290** | **70%** |

### Combined with Phase 1:
- Session size: 23KB → 2-3KB (**90% reduction**)
- Session writes: **80% fewer** (change detection)
- Polling requests: **70% fewer** (this proposal)
- **Overall server load: 75-85% reduction**

---

## Recommendation

**✅ PROCEED with phased rollout**

**Priority Order:**
1. **Disable solo game polling** (15 minutes to implement, huge win)
2. **Add Visibility API** (1 hour to implement, major impact)
3. **Slow lobby polling** (30 minutes to implement, low risk)
4. **Adaptive game polling** (4 hours to implement, test thoroughly)
5. **Tournament backoff** (2 hours to implement)

**Total development time: ~1 day**
**Expected ROI: 70% server load reduction**

---

## Next Steps

1. Review and approve proposal
2. Create feature branch: `perf/adaptive-polling`
3. Implement Phase 1 changes (solo, visibility, lobby)
4. Test in Docker with multiple browsers/tabs
5. Deploy to production with feature flag
6. Monitor metrics for 48 hours
7. Proceed to Phase 2 if successful

---

**Questions? Concerns? Feedback?**

This proposal complements Phase 1 (database/session optimization) to deliver a comprehensive performance improvement package targeting 75-85% overall server load reduction.
