# Performance Optimization Plan: Heroku Response Time Improvement

**Status:** Ready for Implementation
**Priority:** Critical
**Estimated Impact:** 50-80% response time reduction
**Target:** Reduce 6-12 second requests to 1-3 seconds

## Problem Summary

Heroku production environment experiencing severe performance issues:
- **Database connection overhead**: 3+ seconds per request creating new PDO connections
- **Session storage bloat**: 23KB sessions written on every request (no change detection)
- **Response times**: 6-12 seconds average (should be < 2 seconds)

### Root Causes Identified

1. **No persistent PDO connections** - Each PHP request creates new database connection (3s overhead)
2. **Leaderboard data in sessions** - 15-18KB cached in database-backed sessions
3. **No session write optimization** - Writes 23KB on every request even when unchanged
4. **Friend list in sessions** - 2-3KB stored unnecessarily

## Implementation Strategy

Three-phase approach with incremental, testable improvements:

### Phase 1: Quick Wins (CRITICAL)
**Goal:** 50-70% response time reduction
**Risk:** Low
**Timeline:** 1-2 days

#### 1.1 Enable PDO Persistent Connections
**File:** `includes/dbutil.php:59-63`

Add two connection options:
```php
PDO::ATTR_PERSISTENT => true,  // Enable connection pooling across requests
PDO::ATTR_TIMEOUT => 5,         // 5 second connection timeout
```

**Impact:** Eliminates 3+ second connection overhead by reusing connections across PHP-FPM worker requests.

**Testing:**
- All 177 PHPUnit tests must pass
- Load test with ApacheBench to verify improvement
- Monitor Heroku logs for connection issues

#### 1.2 Add Session Change Detection
**File:** `includes/session-handler.php:86-111`

Implement change detection in custom session handler:
- Add `private $previousData = []` property
- Compare session data before writing to database
- Skip database write if data unchanged
- Store previous data hash in `read()` method

**Impact:** Reduce 23KB database writes by 60-80% (most requests don't modify sessions).

**Testing:**
- Verify login, game creation, leaderboard flows work correctly
- Monitor session table write frequency in production

#### 1.3 Remove Leaderboard from Sessions
**File:** `wwwroot/farkleLeaderboard.php:142-231`

Replace session-based caching with static variable caching:
- Remove `$_SESSION['farkle']['lb']` storage (15-18KB)
- Use `static $cacheData` and `static $cacheTime` in `GetLeaderBoard()`
- Keep 3-minute TTL behavior
- Maintain dirty flag functionality

**Impact:** Reduce session size from 23KB to ~5KB.

**Testing:**
- Verify leaderboard still refreshes every 3 minutes
- Test dirty flag triggers immediate refresh
- Confirm cache works per PHP-FPM worker

#### 1.4 Optimize Friend List Storage
**File:** `wwwroot/farkleFriends.php:126-159`

Replace session storage with process-level caching:
- Remove `$_SESSION['farkle']['friends']` storage (2-3KB)
- Use `static $cache` with 5-minute TTL
- Query database only when cache expired or forced

**Impact:** Further reduce session size to ~2-3KB (90% total reduction).

**Testing:**
- Test add/remove friend operations invalidate cache
- Verify friend list displays correctly

### Phase 2: Caching Layer (MEDIUM PRIORITY)
**Goal:** Additional 20-30% improvement
**Risk:** Medium
**Timeline:** 3-5 days

#### 2.1 File-Based Cache Implementation
**New File:** `includes/cacheutil.php`

Create `FileCache` class for cross-process data sharing:
- Use `/tmp/farkle_cache` on Heroku (ephemeral, acceptable)
- Use `backbone/cache/app_data` locally (persistent)
- Implement TTL-based expiration
- Graceful fallback if cache directory not writable

**Integration Points:**
- `farkleLeaderboard.php` - Share leaderboard across workers
- `farkleFriends.php` - Share friend lists
- Expensive query results (optional)

**Impact:** Leaderboard queries reduce from "per worker" to "globally shared" (significant DB load reduction).

#### 2.2 Docker Configuration
**File:** `docker-compose.yml`

Add cache volume and environment variables:
```yaml
volumes:
  - cache_data:/var/www/html/backbone/cache/app_data

volumes:
  cache_data:
```

### Phase 3: Monitoring & Long-term (OPTIONAL)
**Goal:** Observability and future optimization
**Risk:** Low
**Timeline:** 5-7 days

- Add performance timing headers for debugging
- Consider pgBouncer addon if Phase 1 insufficient ($50/month)
- Implement query result caching for expensive operations

## Critical Files

1. **includes/dbutil.php** - Database connection with persistent connections
2. **includes/session-handler.php** - Session write optimization
3. **wwwroot/farkleLeaderboard.php** - Remove from sessions, add caching
4. **wwwroot/farkleFriends.php** - Remove from sessions
5. **includes/cacheutil.php** - New file for Phase 2 caching

## Testing Strategy

### Pre-Deployment (Local Docker)
```bash
# Run full test suite
docker exec farkle_web vendor/bin/phpunit

# Load testing baseline
ab -n 1000 -c 10 http://localhost:8080/farkle.php

# Apply changes, measure improvement
# Target: 50-70% response time reduction
```

### Verification Queries
```sql
-- Check session sizes (should be ~2-3KB after Phase 1)
SELECT session_id, length(session_data)/1024.0 as kb
FROM farkle_sessions ORDER BY kb DESC LIMIT 10;

-- Monitor connection reuse (should see same PIDs)
SELECT pid, usename, application_name, state, query_start
FROM pg_stat_activity WHERE datname = 'dao82fforoqscb';
```

### Production Monitoring
```bash
# Monitor response times
heroku logs --tail -a farkledice | grep "service="

# Monitor errors
heroku logs --tail -a farkledice | grep -i "error"

# Check connection count (should stay under 20)
heroku pg:psql -a farkledice -c "SELECT count(*) FROM pg_stat_activity;"
```

## Deployment Plan

### Step 1: Create Feature Branch
```bash
git checkout -b perf/database-session-optimization
```

### Step 2: Implement Phase 1 Changes (One at a Time)
Each change deployed and tested individually:
1. Persistent connections → test → commit
2. Session change detection → test → commit
3. Leaderboard optimization → test → commit
4. Friend list optimization → test → commit

### Step 3: Deploy to Production
```bash
# Backup database
heroku pg:backups:capture -a farkledice

# Deploy during low-traffic hours (2-5am CST)
git push heroku perf/database-session-optimization:main

# Monitor for 30 minutes
heroku logs --tail -a farkledice
```

### Step 4: Verify Success Metrics
- Response time: 50-70% reduction (6-12s → 2-4s)
- Session size: 90% reduction (23KB → 2-3KB)
- Connection overhead: < 50ms (was 3+ seconds)

## Rollback Procedures

### Emergency Rollback (One Command)
```bash
heroku rollback -a farkledice
```

### Surgical Rollback (Individual Changes)
- **Persistent connections**: Remove `PDO::ATTR_PERSISTENT` line from dbutil.php
- **Session detection**: Restore original `write()` method in session-handler.php
- **Leaderboard**: Restore `$_SESSION['farkle']['lb']` caching
- **Friend list**: Restore session-based storage

Each rollback requires commit and deploy.

## Success Metrics

| Metric | Baseline | Target | Measurement |
|--------|----------|--------|-------------|
| Response Time | 6-12s | 2-4s | Heroku logs `service=` |
| Session Size | 23KB | 2-3KB | Query `farkle_sessions` |
| DB Connection Time | 3+ seconds | < 50ms | PDO connection reuse |
| Session Writes | Every request | 60-80% fewer | Monitor UPDATE frequency |

## Risk Assessment

| Change | Risk | Mitigation |
|--------|------|-----------|
| Persistent Connections | **LOW** | Standard PDO feature, easy rollback |
| Session Change Detection | **LOW** | Transparent to application |
| Leaderboard Caching | **MEDIUM** | Test cache invalidation thoroughly |
| Friend List Caching | **LOW** | Static variable well-understood |
| File-Based Cache | **MEDIUM** | Graceful fallback, loss acceptable on restart |

## Notes

- **PostgreSQL Essential-0 plan**: 20 connection limit sufficient for persistent connections
- **pgBouncer addon**: Defer until Phase 1 results measured ($50/month cost)
- **Redis/Memcache**: File-based cache sufficient for current scale ($15/month saved)
- **Heroku /tmp cache**: Acceptable to lose cache on dyno restart (3-minute TTL means quick rebuild)

## Verification Checklist

After deployment:
- [ ] Login works correctly
- [ ] Game creation and gameplay functional
- [ ] Leaderboard displays and refreshes
- [ ] Friend list shows correctly
- [ ] All 177 tests pass
- [ ] Response times improved 50-70%
- [ ] No error rate increase
- [ ] Session sizes reduced to 2-3KB
- [ ] Database connections reused (check PIDs)
