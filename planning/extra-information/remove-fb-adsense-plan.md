# Plan: Remove Facebook and Google AdSense Integration

**Status:** Draft
**Created:** 2026-01-17
**Estimated Impact:** Medium - affects login flow, database schema, templates, and multiple PHP/JS files

---

## Executive Summary

This plan outlines the complete removal of Facebook login integration and Google AdSense from the Farkle Ten codebase. Facebook integration is deeply embedded across authentication, social features, and UI. AdSense integration is minimal.

### Scope

**Will Remove:**
- Facebook SDK (PHP and JavaScript)
- Facebook login/authentication flow
- Facebook social features (friend invites, publishing scores)
- Facebook metadata tags
- Google AdSense integration
- Database `facebookid` column (migration required)

**Will Keep (Optional):**
- Google Analytics tracking (separate from AdSense)

---

## Current Integration Analysis

### Facebook Integration Points

#### 1. Backend PHP Files

| File | Integration Type | Lines of Code |
|------|-----------------|---------------|
| `includes/base_facebook.php` | Facebook PHP SDK | 1,269 lines |
| `includes/facebook.php` | PHP session wrapper | 94 lines |
| `wwwroot/farkleLogin.php` | Login handler | `UserFacebookLogin()` function (~100 lines) |
| `wwwroot/farkle_fetch.php` | AJAX handler | Facebook login endpoint |
| `wwwroot/farkleGameFuncs.php` | Achievement award | `ACH_FACEBOOK` references |
| `wwwroot/farkleFriends.php` | Friend integration | Facebook friend lookups |
| `wwwroot/farklePageFuncs.php` | Page functions | Facebook references |
| `wwwroot/farkleTournament.php` | Tournament functions | Facebook references |

#### 2. Frontend JavaScript Files

| File | Integration Type | Purpose |
|------|-----------------|---------|
| `js/facebook.js` | Facebook JS SDK | Login, friend invites, score publishing (202 lines) |
| `js/farkleLogin.js` | Login UI | `FacebookLogin()` calls |
| `js/farklePage.js` | Page management | Facebook login references |
| `js/farkleGame.js` | Game logic | Facebook score publishing |
| `js/farkleFriends.js` | Friend system | Facebook friend integration |

#### 3. Template Files (Smarty)

| File | Integration Type | Elements |
|------|-----------------|----------|
| `templates/header.tpl` | Meta tags | Facebook Open Graph tags, app ID |
| `templates/farkle_div_login.tpl` | Login UI | Facebook login button image |
| `templates/farkle_div_lobby.tpl` | Lobby UI | Facebook login prompts |
| `templates/farkle_div_addfriend.tpl` | Friend UI | Facebook friend integration |
| `templates/farkle_div_game.tpl` | Game UI | Facebook sharing |
| `templates/farkle_app_support.tpl` | App support | Facebook references |
| `templates/farkle.tpl` | Main template | Includes Facebook templates |

#### 4. Database Schema

**Table:** `farkle_players`

Column affected:
```sql
facebookid VARCHAR -- Stores Facebook user ID for linked accounts
```

**Migration needed:**
- Column can be safely removed (nullable field)
- Accounts using only Facebook login will need password reset flow
- Check for orphaned accounts (Facebook-only, no password)

#### 5. Configuration Files

| File | Content |
|------|---------|
| `docker/siteconfig.ini` | `fb_app_id`, `fb_app_secret` configuration |

#### 6. Assets

| File | Purpose |
|------|---------|
| `/images/fbloginbutton.png` | Facebook login button image |
| `/images/fb-icon.png` | Facebook Open Graph image |

### Google AdSense Integration Points

#### 1. Backend Files

| File | Purpose |
|------|---------|
| `wwwroot/google_adsense_script.html` | AdSense script loader (12 lines) |

**Note:** No other AdSense integration found. Google Analytics is separate and can be retained.

---

## Removal Strategy

### Phase 1: Preparation & Analysis

**Tasks:**

1. **Audit Facebook-only accounts**
   ```sql
   -- Find accounts with Facebook ID but no password
   SELECT playerid, username, email, facebookid
   FROM farkle_players
   WHERE facebookid IS NOT NULL
     AND (password IS NULL OR password = '');
   ```

2. **Document Facebook features to preserve**
   - Friend system (convert to internal-only)
   - Achievement system (remove Facebook-specific achievement)
   - Score publishing (remove or convert to internal leaderboard)

3. **Create database migration plan**
   - Backup existing database
   - Plan for orphaned accounts
   - Email notification to affected users (optional)

### Phase 2: Database Migration

**Tasks:**

1. **Identify orphaned accounts**
   - Accounts with Facebook ID but no email
   - Accounts with Facebook ID but no password

2. **Handle orphaned accounts** (choose one approach):
   - **Option A:** Set temporary random password + send reset email
   - **Option B:** Mark accounts as disabled, require password reset
   - **Option C:** Delete accounts with no activity in 90+ days

3. **Remove Facebook column**
   ```sql
   -- PostgreSQL migration
   ALTER TABLE farkle_players DROP COLUMN IF EXISTS facebookid;
   ```

4. **Update init.sql**
   - Remove `facebookid` from table schema

### Phase 3: Backend Code Removal

**Tasks:**

1. **Remove Facebook SDK files**
   - Delete `includes/base_facebook.php`
   - Delete `includes/facebook.php`

2. **Update farkleLogin.php**
   - Remove `require_once("facebook.php")`
   - Remove Facebook SDK initialization
   - Remove `UserFacebookLogin()` function
   - Remove Facebook login AJAX handler (`action=fblogin`)

3. **Update other PHP files**
   - `farkle_fetch.php`: Remove `fblogin` action handler
   - `farkleGameFuncs.php`: Remove `ACH_FACEBOOK` achievement references
   - `farkleFriends.php`: Remove Facebook friend lookup code
   - `farklePageFuncs.php`: Remove Facebook-related functions
   - `farkleTournament.php`: Remove Facebook references

4. **Remove configuration**
   - Remove `fb_app_id` and `fb_app_secret` from `docker/siteconfig.ini`

### Phase 4: Frontend Code Removal

**Tasks:**

1. **Delete Facebook JavaScript**
   - Delete `js/facebook.js`

2. **Update JavaScript files**
   - `js/farkleLogin.js`: Remove `FacebookLogin()` calls
   - `js/farklePage.js`: Remove Facebook initialization
   - `js/farkleGame.js`: Remove Facebook score publishing
   - `js/farkleFriends.js`: Remove Facebook friend integration

3. **Update templates**
   - `header.tpl`: Remove Facebook Open Graph meta tags, remove `fb:app_id`
   - `farkle_div_login.tpl`: Remove Facebook login button
   - `farkle_div_lobby.tpl`: Remove Facebook login prompts
   - `farkle_div_addfriend.tpl`: Remove Facebook friend integration
   - `farkle_div_game.tpl`: Remove Facebook sharing buttons

4. **Remove assets**
   - Delete `/images/fbloginbutton.png`
   - Optionally keep `/images/fb-icon.png` if used elsewhere

### Phase 5: Google AdSense Removal

**Tasks:**

1. **Remove AdSense files**
   - Delete `wwwroot/google_adsense_script.html`

2. **Search for AdSense references**
   ```bash
   grep -r "google_adsense\|adsense" --include="*.php" --include="*.tpl" --include="*.js"
   ```

3. **Remove any AdSense script includes**

**Google Analytics Decision:**
- Keep or remove? (current tracking code in `header.tpl`)
- If keeping: no changes needed
- If removing: remove analytics script block from `header.tpl:70-80`

### Phase 6: Testing & Verification

**Tasks:**

1. **Functional testing**
   - ✅ Regular login/registration works
   - ✅ Friend system works (without Facebook)
   - ✅ Game creation/joining works
   - ✅ No console errors related to Facebook SDK
   - ✅ No broken links or missing images

2. **Database testing**
   - ✅ New accounts created successfully
   - ✅ Existing accounts can log in
   - ✅ No references to `facebookid` in queries

3. **Code cleanup verification**
   ```bash
   # Should return no results
   grep -r "facebook\|Facebook\|FB\." --include="*.php" --include="*.js" --include="*.tpl"
   grep -r "facebookid" --include="*.php"
   grep -r "adsense" --include="*.php" --include="*.html" --include="*.tpl"
   ```

4. **Performance testing**
   - Check page load times (should improve without external SDK)
   - Verify Docker logs for errors

---

## File Changes Summary

### Files to DELETE (12 files)

```
includes/base_facebook.php          (1,269 lines - Facebook SDK)
includes/facebook.php               (94 lines - Facebook wrapper)
js/facebook.js                      (202 lines - Facebook JS SDK)
wwwroot/google_adsense_script.html  (12 lines - AdSense)
images/fbloginbutton.png            (image asset)
```

### Files to MODIFY (20+ files)

**PHP Backend:**
- `wwwroot/farkleLogin.php` - Remove Facebook login function
- `wwwroot/farkle_fetch.php` - Remove Facebook AJAX handler
- `wwwroot/farkleGameFuncs.php` - Remove Facebook achievement
- `wwwroot/farkleFriends.php` - Remove Facebook friend integration
- `wwwroot/farklePageFuncs.php` - Remove Facebook functions
- `wwwroot/farkleTournament.php` - Remove Facebook references

**JavaScript Frontend:**
- `js/farkleLogin.js` - Remove Facebook login calls
- `js/farklePage.js` - Remove Facebook initialization
- `js/farkleGame.js` - Remove Facebook publishing
- `js/farkleFriends.js` - Remove Facebook friend features

**Smarty Templates:**
- `templates/header.tpl` - Remove Facebook meta tags
- `templates/farkle_div_login.tpl` - Remove Facebook login UI
- `templates/farkle_div_lobby.tpl` - Remove Facebook prompts
- `templates/farkle_div_addfriend.tpl` - Remove Facebook integration
- `templates/farkle_div_game.tpl` - Remove Facebook sharing

**Configuration:**
- `docker/siteconfig.ini` - Remove Facebook config
- `docker/init.sql` - Remove `facebookid` column

---

## Migration SQL Scripts

### 1. Analyze Current Facebook Usage

```sql
-- Count Facebook-linked accounts
SELECT COUNT(*) as facebook_accounts
FROM farkle_players
WHERE facebookid IS NOT NULL;

-- Find Facebook-only accounts (no password)
SELECT playerid, username, email, facebookid, created_date, last_login
FROM farkle_players
WHERE facebookid IS NOT NULL
  AND (password IS NULL OR password = '');

-- Find hybrid accounts (Facebook + password)
SELECT COUNT(*) as hybrid_accounts
FROM farkle_players
WHERE facebookid IS NOT NULL
  AND password IS NOT NULL
  AND password != '';
```

### 2. Migration Script (Option A: Set Random Passwords)

```sql
-- Backup first!
CREATE TABLE farkle_players_backup_20260117 AS
SELECT * FROM farkle_players;

-- Set random passwords for Facebook-only accounts
UPDATE farkle_players
SET password = MD5(RANDOM()::TEXT || playerid::TEXT)
WHERE facebookid IS NOT NULL
  AND (password IS NULL OR password = '');

-- Remove facebookid column
ALTER TABLE farkle_players DROP COLUMN IF EXISTS facebookid;

-- Verify
SELECT COUNT(*) FROM farkle_players WHERE password IS NULL OR password = '';
```

### 3. Migration Script (Option B: Mark for Reset)

```sql
-- Add password_reset_required flag
ALTER TABLE farkle_players ADD COLUMN IF NOT EXISTS password_reset_required BOOLEAN DEFAULT false;

-- Mark Facebook-only accounts
UPDATE farkle_players
SET password_reset_required = true
WHERE facebookid IS NOT NULL
  AND (password IS NULL OR password = '');

-- Remove facebookid column
ALTER TABLE farkle_players DROP COLUMN IF EXISTS facebookid;
```

---

## Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Users lose access to accounts | HIGH | Pre-migration notification email, password reset flow |
| Friend system breaks | MEDIUM | Convert to internal-only friend system |
| Achievement loss | LOW | Remove Facebook achievement, keep other achievements |
| External Facebook posts | LOW | Already posted content remains on Facebook |
| SEO impact from Open Graph removal | LOW | Minimal - game is already indexed |
| Orphaned accounts in database | LOW | Clean up during migration |

---

## Rollback Plan

If issues occur:

1. **Database rollback**
   ```sql
   DROP TABLE farkle_players;
   ALTER TABLE farkle_players_backup_20260117 RENAME TO farkle_players;
   ```

2. **Code rollback**
   - Restore from git: `git checkout HEAD~1 -- <file>`
   - Redeploy previous Docker image

3. **Restore Facebook SDK files**
   - Keep backup of deleted files for 30 days

---

## Success Criteria

- ✅ Zero Facebook SDK references in codebase
- ✅ Zero Google AdSense references in codebase
- ✅ All users can log in with username/password
- ✅ Friend system works without Facebook
- ✅ No console errors or broken functionality
- ✅ Database schema updated and clean
- ✅ Page load time improved
- ✅ Docker build succeeds
- ✅ All existing games and data preserved

---

## Timeline Estimate

| Phase | Estimated Time |
|-------|----------------|
| Phase 1: Preparation | 1-2 hours |
| Phase 2: Database Migration | 1 hour |
| Phase 3: Backend Removal | 2-3 hours |
| Phase 4: Frontend Removal | 2-3 hours |
| Phase 5: AdSense Removal | 30 minutes |
| Phase 6: Testing | 2-3 hours |
| **Total** | **8-12 hours** |

---

## Next Steps

1. **Review this plan** - Confirm approach and migration strategy
2. **Choose orphaned account strategy** - Option A, B, or C for Facebook-only accounts
3. **Decide on Google Analytics** - Keep or remove?
4. **Schedule migration** - Choose low-traffic time for database changes
5. **Create feature branch** - `git checkout -b feature/remove-facebook-adsense`
6. **Execute removal** - Follow phases sequentially or use orchestrator

---

## Questions to Resolve

- [ ] What should happen to Facebook-only accounts? (set password vs mark for reset vs delete)
- [ ] Keep Google Analytics or remove it too?
- [ ] Send notification emails to affected users?
- [ ] Timeline/deadline for this work?
- [ ] Test on staging environment first?

---

## Related Documentation

- Facebook PHP SDK v3 (archived): https://github.com/facebookarchive/facebook-php-sdk
- PostgreSQL ALTER TABLE: https://www.postgresql.org/docs/current/sql-altertable.html
- Migration best practices: [internal wiki]
