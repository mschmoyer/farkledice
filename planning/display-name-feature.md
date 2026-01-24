# Display Name Feature - Implementation Plan

## Overview

Allow users to set a custom display name from their profile/options page. This display name will be shown throughout the app instead of their login username.

## Research Findings

### Schema Status: ✅ Already Exists

The `fullname` column already exists in the `farkle_players` table:
- **Column:** `fullname VARCHAR(100)`
- **Location:** Defined in `docker/init.sql` line 18
- **Current State:** Optional (allows NULL), not exposed to users for editing

### Current Usage: ✅ Fully Integrated

The codebase already uses `COALESCE(fullname, username)` consistently across **19+ files**:
- Game names, leaderboards, friend lists, email notifications, tournaments
- If `fullname` is set → display it
- If `fullname` is NULL → fallback to `username`

### Profile/Options Page Structure

| Component | File Path |
|-----------|-----------|
| Template | `templates/farkle_div_playerinfo.tpl` |
| PHP Handler | `wwwroot/farklePageFuncs.php` |
| JavaScript | `wwwroot/js/farklePlayerInfo.js` |
| AJAX Router | `wwwroot/farkle_fetch.php` |

### Current Options Tab Contents
- Email game updates (checkbox)
- Email address (text input)
- "Play Me!" random selection (checkbox)
- Save Options button

---

## Implementation Plan

### 1. Update Template - Add Display Name Input

**File:** `templates/farkle_div_playerinfo.tpl`

Add a "Display Name" text input field to the Options tab (item 3), positioned at the top before email options:

```html
<div style="margin-bottom: 15px;">
    <label>Display Name:</label><br/>
    <input type="text" id="displayname" maxlength="30" style="width: 200px;"
           value="{$player.fullname|escape:'html'}"
           onchange="PlayerInfoOptionsDirty();" />
    <div class="hint-text">Shown to other players instead of your username. Letters, numbers, and emoji only.</div>
</div>
```

### 2. Update GetStats() - Return fullname Separately

**File:** `wwwroot/farklePageFuncs.php` - `GetStats()` function (line ~76)

The query already retrieves `COALESCE(fullname, username) as username`. We need to also return the raw `fullname` so the input field can be pre-populated:

- Add `fullname` to the SELECT query
- Include it in the returned player object

### 3. Update JavaScript - Send Display Name

**File:** `wwwroot/js/farklePlayerInfo.js` - `SaveOptions()` function

Add the display name to the AJAX POST parameters:

```javascript
var displayname = document.getElementById('displayname').value;
// Add to params: displayname=encodeURIComponent(displayname)
```

### 4. Update SaveOptions() - Save fullname to Database

**File:** `wwwroot/farklePageFuncs.php` - `SaveOptions()` function (line ~250)

- Accept new `$displayname` parameter
- Validate: max length 50, sanitize for XSS
- Add to UPDATE query: `fullname = :displayname`

### 5. Update AJAX Router (if needed)

**File:** `wwwroot/farkle_fetch.php`

Pass the new `displayname` POST parameter to `SaveOptions()`.

---

## UI Design

### Options Tab Mockup

```
┌─────────────────────────────────────────────────────┐
│  [Stats] [Achievements] [Games] [Options]           │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Display Name: [_________________________]          │
│  This name will be shown to other players           │
│  instead of your username.                          │
│                                                     │
│  ─────────────────────────────────────────          │
│                                                     │
│  ☑ Email game updates                               │
│  Email: [_________________________]                 │
│                                                     │
│  ☑ Play Me! (allow random game selection)           │
│                                                     │
│  [Save Options]                                     │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### User Flow

1. User opens their profile (clicks their avatar or "My Profile")
2. User clicks the "Options" tab
3. User sees their current display name (or empty if not set)
4. User enters/modifies their display name
5. User clicks "Save Options"
6. Success message confirms save
7. Display name is immediately used throughout the app

---

## Files to Modify

| # | File | Change |
|---|------|--------|
| 1 | `templates/farkle_div_playerinfo.tpl` | Add display name input field |
| 2 | `wwwroot/farklePageFuncs.php` | Update GetStats() to return fullname, update SaveOptions() to accept and save it |
| 3 | `wwwroot/js/farklePlayerInfo.js` | Send displayname in SaveOptions() AJAX call |
| 4 | `wwwroot/farkle_fetch.php` | Pass displayname parameter to SaveOptions() (if not already dynamic) |

---

## Validation Rules

| Rule | Implementation |
|------|----------------|
| Max length | 30 characters |
| Allowed chars | Alphanumeric (a-z, A-Z, 0-9), spaces, basic punctuation (. , ! ? - _ '), and emoji |
| Blocked chars | `< > & " ' \ / ; :` and other HTML/script-injection characters |
| Optional | Empty string clears display name, falls back to username |
| Sanitization | Server-side regex validation + parameterized query for storage |

### Character Validation Regex (PHP)
```php
// Allow: letters, numbers, spaces, basic punctuation, emoji
// Block: < > & " \ / ; : and other dangerous chars
$pattern = '/^[\p{L}\p{N}\p{Emoji}\s\.\,\!\?\-\_\']+$/u';
if (!preg_match($pattern, $displayname)) {
    return "Display name contains invalid characters.";
}
```

---

## Testing Plan

### Manual Testing Checklist

1. **View Options Tab**
   - [ ] Display name field appears for logged-in user viewing their own profile
   - [ ] Display name field does NOT appear when viewing another player's profile
   - [ ] Current display name (if set) is pre-populated

2. **Save Display Name**
   - [ ] Can save a new display name
   - [ ] Can update an existing display name
   - [ ] Can clear display name (revert to username)
   - [ ] Save button only enabled after changes
   - [ ] Success message appears after save

3. **Display Name Usage**
   - [ ] Leaderboards show new display name
   - [ ] Friend list shows new display name
   - [ ] Game lobby shows new display name
   - [ ] In-game player names show new display name

4. **Edge Cases**
   - [ ] Max length (30 chars) enforced
   - [ ] Invalid characters rejected (< > & etc.)
   - [ ] Emoji characters work correctly
   - [ ] Empty string saves as NULL (falls back to username)

### Test Credentials
- Username: `testuser` / Password: `test123`

---

## No Breaking Changes

This feature:
- Uses existing database column (no schema migration needed)
- Uses existing COALESCE pattern (no query changes needed)
- Adds new optional field (backwards compatible)
- Does not affect login/authentication (username unchanged)
