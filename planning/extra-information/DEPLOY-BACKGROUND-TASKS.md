# Deploying Background Task System

This document provides instructions for deploying the new background maintenance system to both local Docker and Heroku production.

## What Changed

### New Files
- `wwwroot/farkleBackgroundTasks.php` - Background maintenance task system
- `DEPLOY-BACKGROUND-TASKS.md` - This deployment guide

### Modified Files
- `wwwroot/farkle_fetch.php` - Added `BackgroundMaintenance()` call
- `wwwroot/farkleLeaderboard.php` - Fixed mysql_query() errors and MySQL→PostgreSQL compatibility
- `docker/migrate-schema.sql` - Added new siteinfo entries for task throttling
- `scripts/migrate-db.php` - Now executes both init.sql and migrate-schema.sql
- `planning/bugfixes.json` - Documented all fixes

### Database Changes
Added new `siteinfo` table entries for task throttling:
- `paramid=2`: `last_daily_leaderboard_refresh` (hourly leaderboard stats)
- `paramid=4`: `last_cleanup` (stale game cleanup)

## How It Works

The new system replaces cron jobs with **fetch-route-triggered background tasks**:

1. Every AJAX request to `farkle_fetch.php` calls `BackgroundMaintenance()`
2. Each maintenance task checks its timestamp in the `siteinfo` table
3. If enough time has passed, the task runs and updates its timestamp
4. Otherwise, the task is skipped (no performance impact)

### Three Background Tasks

| Task | Frequency | Purpose |
|------|-----------|---------|
| Main Leaderboard Refresh | Every 5 minutes | Updates wins/losses, highest rounds, achievements |
| Daily Leaderboard Stats | Every 1 hour | Updates yesterday's top scores, wins, farkles |
| Stale Game Cleanup | Every 30 minutes | Finishes abandoned games |

## Deployment Steps

### Local Docker Environment

1. **Stop Docker containers:**
   ```bash
   docker-compose down
   ```

2. **Pull/commit the latest code** (if from Git)

3. **Run the migration to add new siteinfo entries:**
   ```bash
   docker-compose up -d
   docker exec -i farkle_db psql -U farkle_user -d farkle_db < docker/migrate-schema.sql
   ```

4. **Restart containers to load new code:**
   ```bash
   docker-compose restart
   ```

5. **Verify it's working:**
   - Visit http://localhost:8080
   - Login and navigate around (triggers fetch requests)
   - Check logs: `docker-compose logs -f web`
   - Look for: `BackgroundMaintenance: Refreshing...` messages

### Heroku Production

1. **Commit all changes to Git:**
   ```bash
   git add .
   git commit -m "Add background task system to replace cron jobs"
   ```

2. **Deploy to Heroku:**
   ```bash
   git push heroku modernization/phase-1:main
   ```

3. **Run the migration script:**
   ```bash
   heroku run php scripts/migrate-db.php -a farkledice
   ```

   This will:
   - Execute `docker/init.sql` (base schema)
   - Execute `docker/migrate-schema.sql` (add new siteinfo entries)
   - Report success/skip/failure for each statement

4. **Verify deployment:**
   ```bash
   heroku logs --tail -a farkledice
   ```

   Look for:
   - No PHP errors
   - Background task messages in logs (when players are active)
   - Leaderboard working without errors

5. **Test the leaderboard:**
   - Visit https://farkledice-03baf34d5c97.herokuapp.com/
   - Login and click "Leaderboard"
   - Should load without errors
   - Stats should update as players play games

## Verification

### Check siteinfo Table

Connect to the database and verify new entries:

**Local Docker:**
```bash
docker exec -it farkle_db psql -U farkle_user -d farkle_db
```

**Heroku:**
```bash
heroku pg:psql -a farkledice
```

Then run:
```sql
SELECT * FROM siteinfo ORDER BY paramid;
```

Expected output:
```
 paramid |          paramname           | paramvalue
---------+------------------------------+------------
       1 | last_leaderboard_refresh     | 0
       2 | last_daily_leaderboard_refresh| 0
       3 | day_of_week                  | Monday
       4 | last_cleanup                 | 0
```

### Monitor Background Tasks

**Local:**
```bash
docker-compose logs -f web | grep -i "background\|leaderboard"
```

**Heroku:**
```bash
heroku logs --tail -a farkledice | grep -i "background\|leaderboard"
```

You should see messages like:
```
BackgroundMaintenance: Refreshing main leaderboards
BackgroundMaintenance: Refreshing daily leaderboards
BackgroundMaintenance: Cleaning up stale games
```

## Troubleshooting

### "Table siteinfo does not exist"

Run the migration:
```bash
# Local
docker exec -i farkle_db psql -U farkle_user -d farkle_db < docker/migrate-schema.sql

# Heroku
heroku run php scripts/migrate-db.php -a farkledice
```

### Background tasks not running

Check that:
1. Players are actively using the site (tasks only run on fetch requests)
2. Enough time has passed (check timestamps in siteinfo table)
3. No PHP errors in logs

### Leaderboard showing old data

1. Manually trigger refresh:
   ```
   http://localhost:8080/farkleLeaderboard.php?action=updateleaderboards
   ```

2. Check siteinfo timestamps are being updated:
   ```sql
   SELECT paramid, paramname,
          to_timestamp(paramvalue::numeric) AS last_run,
          NOW() AS current_time
   FROM siteinfo
   WHERE paramid IN (1,2,4);
   ```

## Benefits of New System

✅ **No cron configuration needed** - Works immediately on Heroku
✅ **Self-maintaining** - Runs as long as players are active
✅ **Performance-safe** - Throttling prevents database overload
✅ **Simple deployment** - Just push and migrate
✅ **Easy monitoring** - All in application logs
✅ **Docker-compatible** - No external dependencies

## Rollback Plan

If issues occur, you can disable background tasks temporarily:

1. **Comment out the BackgroundMaintenance() call** in `farkle_fetch.php`:
   ```php
   // BackgroundMaintenance();
   ```

2. **Redeploy:**
   ```bash
   git commit -am "Temporarily disable background tasks"
   git push heroku modernization/phase-1:main
   ```

3. **Manually refresh leaderboards** when needed:
   ```
   https://farkledice-03baf34d5c97.herokuapp.com/farkleLeaderboard.php?action=updateleaderboards
   ```

## Next Steps

After verifying everything works:

1. Monitor Heroku logs for 24 hours to ensure tasks run smoothly
2. Check leaderboard data updates correctly
3. Verify no performance degradation
4. Remove old cron job files if confirmed working:
   - `wwwroot/farkleCronHourly.php` (can be deleted)
   - `wwwroot/farkleCronNightly.php` (can be deleted)

## Questions?

Check logs first:
- Local: `docker-compose logs -f web`
- Heroku: `heroku logs --tail -a farkledice`

Look for error patterns and consult `planning/bugfixes.json` for known issues.
