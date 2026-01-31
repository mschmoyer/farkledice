# Release Notes Page Implementation Plan

## Overview
Add a Release Notes page that displays version history with bullet lists of changes, accessible via a link in the footer.

## Files to Create

### 1. `data/release-notes.json`
New directory and JSON file for release notes data:
```json
{
  "releases": [
    {
      "version": "2.7.0",
      "date": "2025-01-31",
      "notes": [
        "Added Release Notes page accessible from footer",
        "Updated footer version styling for better visibility"
      ]
    }
  ]
}
```

### 2. `templates/farkle_div_releasenotes.tpl`
New template following existing page patterns:
- Outer div with `id="divReleaseNotes"`, `class="pagelayout"`, `display: none`
- Content in `loginBox` container
- Loop through releases showing version header + bullet list
- Back button: `<input type="button" class="mobileButton" buttoncolor="red" value="Back" onClick="PageGoBack()">`

## Files to Modify

### 3. `wwwroot/farkle.php`
Load JSON and pass to template:
```php
$releaseNotesPath = $dir . '/data/release-notes.json';
$releaseNotes = [];
if (file_exists($releaseNotesPath)) {
    $releaseNotes = json_decode(file_get_contents($releaseNotesPath), true);
}
$smarty->assign('release_notes', $releaseNotes);
```

### 4. `templates/farkle.tpl`
Add include after other page templates:
```smarty
{include file="farkle_div_releasenotes.tpl"}
```

### 5. `wwwroot/js/farklePage.js`
- Add `divReleaseNotesObj` variable
- Initialize in `pageInit()`
- Add to `HideAllWindows()`
- Add to `PageGoBack()` routing
- Add `ShowReleaseNotes()` function

### 6. `templates/footer.tpl`
Update styling and add link:
- Change `font-size: 11px` to `font-size: 13px`
- Change `color: #999` to `color: #fff`
- Add `[Release Notes]` link calling `ShowReleaseNotes()`

### 7. `CLAUDE.md`
Add to Versioning section:
```markdown
**Release Notes:**
When updating the version, also update `data/release-notes.json`:
- Add new entry at TOP of `releases` array (newest first)
- Include version, date (YYYY-MM-DD), and array of change notes
```

### 8. `includes/baseutil.php`
Bump `APP_VERSION` from `2.6.0` to `2.7.0`

## Implementation Order
1. Create `data/release-notes.json`
2. Create `templates/farkle_div_releasenotes.tpl`
3. Modify `templates/farkle.tpl` (include new template)
4. Modify `wwwroot/farkle.php` (load JSON)
5. Modify `wwwroot/js/farklePage.js` (navigation)
6. Modify `templates/footer.tpl` (styling + link)
7. Update `CLAUDE.md`
8. Update version in `includes/baseutil.php`

## Verification
1. Run `docker-compose up -d`
2. Navigate to http://localhost:8080
3. Verify footer shows white version text at larger size
4. Click "[Release Notes]" link
5. Verify release notes page displays with version headers and bullet lists
6. Click "Back" button - should return to lobby
7. Navigate to release notes from different pages - verify back button works correctly
