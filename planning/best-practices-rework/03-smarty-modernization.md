# Smarty Template Modernization Plan

**Priority:** Medium
**Estimated Effort:** 6-8 weeks
**Risk Level:** Low

---

## Executive Summary

This plan modernizes Smarty template usage in the Farkle Ten codebase. Current implementation uses Smarty 4.5 but doesn't utilize modern features like template inheritance, caching, or custom modifiers.

---

## Current State Analysis

### Configuration (includes/baseutil.php)
- **Version:** Smarty 4.5 via Composer
- **Caching:** DISABLED (`$smarty->caching = 0`)
- **Config Dir:** `/backbone/configs/` (directory exists but empty)

### Template Structure
- 25+ template files in `templates/`
- Flat include pattern via `{include file="..."}`
- No template inheritance (`{extends}`/`{block}`)
- No custom modifiers/plugins
- Inline CSS in `{literal}` blocks

### Key Issues
1. Large monolithic template (`farkle.tpl` includes 13 modules)
2. Inline CSS/JS scattered throughout templates
3. Device-specific conditionals repeated 6+ times
4. Hidden input pattern for JS data passing

---

## Phase 1: Foundation Setup (Week 1)

### 1.1 Create Plugin Directory

```bash
mkdir -p plugins/
```

Update `baseutil.php`:
```php
$smarty->addPluginsDir($dir . '/plugins/');
```

### 1.2 Create Config Directory

```bash
mkdir -p backbone/configs/
```

Create `backbone/configs/game.conf`:
```ini
POINTS_TO_WIN = 10000
MAX_PLAYERS = 4
ROUND_LIMIT = 10
```

### 1.3 Enable Selective Caching

Update `baseutil.php`:
```php
$smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
$smarty->cache_lifetime = 3600; // 1 hour default
```

---

## Phase 2: Custom Modifiers (Week 2)

### formatScore Modifier

**File:** `/plugins/modifier.formatScore.php`
```php
<?php
function smarty_modifier_formatScore($score)
{
    if (!is_numeric($score)) return $score;
    return number_format((int)$score);
}
```

**Usage:**
```smarty
<span>{$player.score|formatScore}</span>
```

### formatTime Modifier

**File:** `/plugins/modifier.formatTime.php`
```php
<?php
function smarty_modifier_formatTime($timestamp, $format = 'relative')
{
    if (empty($timestamp)) return '';
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = time() - $time;

    if ($format === 'relative') {
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        return date('M j', $time);
    }
    return date($format, $time);
}
```

### formatPercent Modifier

**File:** `/plugins/modifier.formatPercent.php`
```php
<?php
function smarty_modifier_formatPercent($value, $decimals = 1)
{
    if (!is_numeric($value)) return $value;
    return number_format((float)$value * 100, $decimals) . '%';
}
```

---

## Phase 3: Template Inheritance (Weeks 3-4)

### Create Base Template

**File:** `/templates/base.tpl`
```smarty
<!DOCTYPE html>
<html lang="en">
<head>
    {block name="head"}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{block name="title"}Farkle Ten{/block}</title>

    <link rel="stylesheet" href="/css/mobile.css?v={$app_version}" />
    <link rel="stylesheet" href="/css/farkle.css?v={$app_version}" />
    {block name="css"}{/block}

    <script src="/js/bubble_util.js?v={$app_version}"></script>
    {block name="head_scripts"}{/block}
    {/block}
</head>

<body>
{block name="body_start"}{/block}

<div align="center">
<div id="outerGlobalDiv">
    {block name="header"}
    <img src="/images/farkle_ten_logo.png" class="titleImage">
    {/block}

    {block name="content"}{/block}
</div>
</div>

{block name="footer"}{/block}
{block name="scripts"}{/block}
</body>
</html>
```

### Refactor Main Game Template

**File:** `/templates/farkle.tpl` (refactored)
```smarty
{extends file="base.tpl"}

{block name="title"}{$title|default:"Farkle Ten"}{/block}

{block name="content"}
{include file="farkle_div_login.tpl"}
{include file="farkle_div_lobby.tpl"}
{include file="farkle_div_game.tpl"}
{* ... other includes *}
{/block}

{block name="scripts"}
<script src="/js/farkleGame.js?v={$app_version}"></script>
{* ... other scripts *}

{* Replace {literal} block with JSON data approach *}
<script type="application/json" id="pageInitData">
{"lobbyInfo": {$lobbyinfo|default:'""'}, "friendInfo": {$friendinfo|default:'""'}}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var data = JSON.parse(document.getElementById('pageInitData').textContent);
    pageInit(data.lobbyInfo, data.friendInfo);
});
</script>
{/block}
```

---

## Phase 4: Extract Inline CSS (Weeks 4-5)

### High-Priority Extractions

| Current Inline Style | New CSS Class |
|---------------------|---------------|
| `style="color: #9DF497;"` | `.stat-level` |
| `style="color: yellow;"` | `.stat-xp-target` |
| `style="color: #FF6B6B;"` | `.stat-farkle` |

### Add to farkle.css

```css
/* Player Stats Colors */
.stat-level { color: #9DF497; }
.stat-xp-target { color: yellow; }
.stat-farkle { color: #FF6B6B; }

/* Release Notes Styles */
.release-version { color: #FFCC66; font-size: 18px; font-weight: bold; }
.release-headline { color: #64B5F6; }
.release-date { font-size: 12px; color: #ccc; }
```

### Before/After Example

**Before:**
```smarty
<div style="color: #FFCC66; font-size: 18px; font-weight: bold;">
    v{$release.version}
</div>
```

**After:**
```smarty
<div class="release-version">v{$release.version}</div>
```

---

## Phase 5: Config Files for Constants (Week 6)

### Game Config

**File:** `/backbone/configs/game.conf`
```ini
GAME_MODE_STANDARD = 1
GAME_MODE_10ROUND = 2
POINTS_TO_WIN = 10000
ROUND_LIMIT = 10
```

### Usage in Templates

```smarty
{config_load file="game.conf"}
<p>Play to {#POINTS_TO_WIN#} points!</p>
```

---

## Phase 6: Reusable Components (Week 7)

### Player Card Component

**File:** `/templates/partials/player_card.tpl`
```smarty
{* Player Card Component *}
<div class="playerCard {$cardcolor}">
    <span class="playerName">{$username|escape}</span>
    <span class="playerScore">{$score|formatScore}</span>
    <span class="playerTitle">{$title|escape}</span>
</div>
```

**Usage:**
```smarty
{include 'partials/player_card.tpl' username=$player.username score=$player.score}
```

### Button Component

**File:** `/templates/partials/button.tpl`
```smarty
<input type="button" class="mobileButton"
    value="{$text}" onClick="{$onclick}"
    style="width: {$width|default:'110px'};">
```

---

## Migration Strategy

| Step | Change | Risk | Rollback |
|------|--------|------|----------|
| 1 | Add plugins directory + modifiers | Low | Remove directory |
| 2 | Add config directory + files | Low | Remove directory |
| 3 | Create base.tpl (unused) | None | Delete file |
| 4 | Migrate one template to inheritance | Medium | Revert single file |
| 5 | Extract inline CSS | Low | Revert CSS + template |
| 6 | Enable caching for header/footer | Low | Disable caching |

---

## Testing Strategy

1. **Visual Regression:** Screenshots before/after each change
2. **Functional:** Run `api_game_flow_test.php` after each phase
3. **Mobile:** Test on iOS Safari, Android Chrome after CSS changes
4. **Cache:** Clear compiled templates after inheritance changes

```bash
# Clear compiled templates
rm -rf backbone/templates_c/*
```

---

## Estimated Impact

- **Performance:** 30-50% faster initial renders with caching
- **Maintainability:** Cleaner templates, reusable components
- **Developer Experience:** Easier to add new pages/features
