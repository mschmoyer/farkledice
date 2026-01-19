# Task-010: Update Bot Selection UI

## Status: Ready for Implementation

## Requirement
REQ-015: "Bot selection UI must show personality type and allow player to choose specific bots"

**Updated based on user feedback**: No individual bot selection needed. Just pick a random personality from the chosen difficulty category.

## Current Implementation

### UI (farkleLobby.js:200-236)
Currently shows a modal with difficulty levels:
- 🟢 Easy - "Friendly and makes mistakes - great for learning!"
- 🟡 Medium - "Solid tactical play with strategic thinking"
- 🔴 Hard - "Advanced AI with optimal decision-making"

Uses custom modal overlay with `.bot-select-modal` class.

### Backend (farkle_fetch.php:108-178)
`action=startbotgame` handler:
1. Receives `algorithm` parameter (easy/medium/hard)
2. Queries for random bot: `WHERE is_bot = TRUE AND bot_algorithm = :algorithm`
3. Creates 10-round game with selected bot
4. Sets `bot_play_mode = 'interactive'`

**Problem**: Query filters by `bot_algorithm` but AI bots use `personality_id` instead!

## Required Changes

### 1. Update Backend Query (farkle_fetch.php)

**Current**:
```php
$sql = "SELECT playerid, username FROM farkle_players
        WHERE is_bot = TRUE AND bot_algorithm = :algorithm
        ORDER BY RANDOM() LIMIT 1";
```

**New** (pick random personality from difficulty category):
```php
// Get random personality from difficulty category
$sql = "SELECT personality_id, name FROM farkle_bot_personalities
        WHERE difficulty = :difficulty AND is_active = true
        ORDER BY RANDOM() LIMIT 1";
$stmt->execute([':difficulty' => $algorithm]); // 'easy', 'medium', or 'hard'
$personality = $stmt->fetch(PDO::FETCH_ASSOC);

// Create or get bot player for this personality
$sql = "SELECT playerid, username FROM farkle_players
        WHERE personality_id = :personality_id
        LIMIT 1";
$stmt->execute([':personality_id' => $personality['personality_id']]);
$botPlayer = $stmt->fetch(PDO::FETCH_ASSOC);

// If no bot player exists for this personality, create one
if (!$botPlayer) {
    // Create bot player linked to personality
    // (implementation details TBD - might need helper function)
}
```

### 2. Simplify UI to Match "Play Random" Style

**User requirement**: "use simple UI like what you see when you select 'Play Random'"

The "Play Random" flow shows:
- Clean button-based interface (templates/farkle_div_newgame.tpl:18-25)
- `.loginBox` styled container
- Simple text description
- Standard `.mobileButton` buttons

**Update farkleLobby.js**:
- Remove custom modal overlay
- Use same container style as "Play Random" (`.loginBox`)
- Keep difficulty selection (Easy/Medium/Hard)
- Make it look consistent with the rest of the site

Example structure:
```html
<div id="divBotGame" class="loginBox" style="display: none; margin: 5px;">
    <p>Choose your opponent's skill level:</p>

    <input type="button" class="mobileButton" buttoncolor="green"
           value="🟢 Easy Bot" onClick="startBotGame('easy')" style="width: 250px;">

    <input type="button" class="mobileButton" buttoncolor="yellow"
           value="🟡 Medium Bot" onClick="startBotGame('medium')" style="width: 250px;">

    <input type="button" class="mobileButton" buttoncolor="red"
           value="🔴 Hard Bot" onClick="startBotGame('hard')" style="width: 250px;">

    <p style="font-size: 12px; color: #666;">
        A random AI personality will be selected from your chosen difficulty.
    </p>

    <input type="button" class="mobileButton" buttoncolor="red"
           value="Back" onClick="ShowNewGame()" style="width: 90px;">
</div>
```

### 3. Update Bot Player Creation Logic

AI bots need `personality_id` to be set. Options:

**Option A**: Create dedicated bot player accounts for each personality (15 total)
- Pre-seed during database migration
- Query just looks up by personality_id

**Option B**: Reuse existing bot players, update personality_id on selection
- More flexible but could cause issues if multiple games running

**Recommendation**: Option A - create dedicated bot players during seed

## Files to Modify

1. **wwwroot/js/farkleLobby.js**
   - Update `showBotGameModal()` to use simple UI
   - Remove custom modal styles
   - Match "Play Random" style

2. **wwwroot/farkle_fetch.php**
   - Update `action=startbotgame` handler
   - Query `farkle_bot_personalities` by difficulty
   - Pick random personality
   - Get/create bot player with that personality_id

3. **scripts/seed_bot_personalities.php** (maybe)
   - Create bot player accounts for each personality
   - Link via personality_id

## Testing Checklist

- [ ] UI displays correctly and matches site style
- [ ] Easy difficulty selects random easy personality (Byte, Chip, Beep, Spark, Dot)
- [ ] Medium difficulty selects random medium personality
- [ ] Hard difficulty selects random hard personality
- [ ] Game starts successfully with AI bot
- [ ] Bot uses AI decision-making (not algorithmic fallback)
- [ ] Bot displays personality-driven chat messages
- [ ] Back button returns to new game screen

## Success Criteria

✅ Bot selection UI looks consistent with rest of site
✅ Random personality selected from chosen difficulty
✅ AI bots use their personalities during gameplay
✅ No individual bot selection - just difficulty level
✅ Clean, simple user experience

---

**Implementation Date**: Ready for coder agent
**Estimated Complexity**: Medium (backend + frontend changes)
