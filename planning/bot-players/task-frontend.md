# Task: Frontend JavaScript & UI for Bot Players

**Assignee:** Frontend JavaScript Developer
**Estimated Time:** 2-3 days
**Dependencies:** Database complete, Backend partially complete (at least message system)
**Status:** Waiting for backend task

## Objective

Build UI components for bot game creation, interactive turn display with animations, and bot chat message area.

## Files to Create

### 1. `js/farkleBot.js` - Bot Turn Polling & Display

Handles AJAX polling for bot turn progression and updates UI.

```javascript
/**
 * Bot game state tracking
 */
var gBotTurnState = null;
var gBotPollInterval = null;

/**
 * Start polling for bot turn updates
 */
function startBotTurnPolling(gameId, botPlayerId) {
    stopBotTurnPolling(); // Clear any existing

    gBotPollInterval = setInterval(function() {
        pollBotTurnStatus(gameId, botPlayerId);
    }, 500); // Poll every 500ms
}

/**
 * Stop polling
 */
function stopBotTurnPolling() {
    if (gBotPollInterval) {
        clearInterval(gBotPollInterval);
        gBotPollInterval = null;
    }
}

/**
 * Poll bot turn status via AJAX
 */
function pollBotTurnStatus(gameId, botPlayerId) {
    ajax('getbotstatus', {
        gameid: gameId,
        botplayerid: botPlayerId
    }, function(response) {
        if (response.Error) {
            console.error('Bot status error:', response.Error);
            stopBotTurnPolling();
            return;
        }

        updateBotTurnDisplay(response);

        // Check if turn is complete
        if (response.current_step === 'banking' || response.current_step === 'farkled') {
            stopBotTurnPolling();
            // Refresh game state
            setTimeout(function() {
                FarkleSendUpdate(gGameData.gameid);
            }, 1000);
        } else {
            // Execute next step
            executeBotStep(gameId, botPlayerId);
        }
    });
}

/**
 * Tell server to execute next bot step
 */
function executeBotStep(gameId, botPlayerId) {
    ajax('executebotstep', {
        gameid: gameId,
        botplayerid: botPlayerId
    }, function(response) {
        // Server will update state, we'll poll to get it
    });
}

/**
 * Update UI based on bot turn state
 */
function updateBotTurnDisplay(state) {
    switch (state.current_step) {
        case 'rolling':
            showBotRolling(state.dice_remaining);
            break;

        case 'choosing_keepers':
            showBotDiceRoll(JSON.parse(state.last_roll));
            break;

        case 'deciding_roll':
            showBotKeepers(JSON.parse(state.dice_kept), state.turn_score);
            if (state.last_message) {
                addBotMessage(state.last_message);
            }
            break;

        case 'banking':
            showBotBanking(state.turn_score);
            if (state.last_message) {
                addBotMessage(state.last_message);
            }
            break;

        case 'farkled':
            showBotFarkle();
            if (state.last_message) {
                addBotMessage(state.last_message);
            }
            break;
    }
}

/**
 * Animation functions
 */
function showBotRolling(numDice) {
    // Show "Bot is rolling {numDice} dice..." message
    $('#botStatusMessage').html('<i class="fa fa-dice"></i> Rolling ' + numDice + ' dice...');
}

function showBotDiceRoll(dice) {
    // Display dice results with animation
    var html = 'Rolled: ';
    dice.forEach(function(die) {
        html += '<span class="die die-' + die + '">' + die + '</span> ';
    });
    $('#botDiceDisplay').html(html);
    animateDiceRoll(); // Shake animation
}

function showBotKeepers(keptDice, turnScore) {
    // Highlight kept dice
    $('#botKeptDice').html('Kept: ' + keptDice.join(', ') + ' (' + turnScore + ' pts)');
}

function showBotBanking(points) {
    $('#botStatusMessage').html('<i class="fa fa-check-circle"></i> Banking ' + points + ' points!');
}

function showBotFarkle() {
    $('#botStatusMessage').html('<i class="fa fa-times-circle"></i> Farkle!');
}
```

---

### 2. `js/farkleBotChat.js` - Bot Message Display

Manages bot chat message area.

```javascript
/**
 * Bot chat messages array (last 5)
 */
var gBotMessages = [];

/**
 * Add a bot message to the chat area
 */
function addBotMessage(message) {
    // Add to array
    gBotMessages.push({
        text: message,
        timestamp: new Date().getTime()
    });

    // Keep only last 5 messages
    if (gBotMessages.length > 5) {
        gBotMessages.shift();
    }

    // Update UI
    renderBotMessages();
}

/**
 * Render bot message area
 */
function renderBotMessages() {
    var html = '';

    gBotMessages.forEach(function(msg) {
        html += '<div class="bot-message">';
        html += '  <span class="bot-message-icon">🤖</span>';
        html += '  <span class="bot-message-text">' + escapeHtml(msg.text) + '</span>';
        html += '</div>';
    });

    $('#divBotChat').html(html);

    // Auto-scroll to bottom
    var chatDiv = document.getElementById('divBotChat');
    if (chatDiv) {
        chatDiv.scrollTop = chatDiv.scrollHeight;
    }
}

/**
 * Clear bot messages (new game)
 */
function clearBotMessages() {
    gBotMessages = [];
    renderBotMessages();
}
```

---

### 3. Modify `js/farkleLobby.js` - Add "Play a Bot" Button

Add bot game creation UI:

```javascript
/**
 * Show bot game selection modal
 */
function showBotGameModal() {
    var html = '<div class="bot-select-modal">';
    html += '  <h2>Play Against a Bot</h2>';
    html += '  <p>Choose your opponent\'s difficulty:</p>';

    // Easy bots
    html += '  <div class="bot-option" onclick="startBotGame(\'easy\')">';
    html += '    <h3>🟢 Easy</h3>';
    html += '    <p>Friendly and makes mistakes - great for learning!</p>';
    html += '    <small>Bots: Byte, Chip, Beep</small>';
    html += '  </div>';

    // Medium bots
    html += '  <div class="bot-option" onclick="startBotGame(\'medium\')">';
    html += '    <h3>🟡 Medium</h3>';
    html += '    <p>Solid tactical play with strategic thinking</p>';
    html += '    <small>Bots: Cyber, Logic, Binary</small>';
    html += '  </div>';

    // Hard bots
    html += '  <div class="bot-option" onclick="startBotGame(\'hard\')">';
    html += '    <h3>🔴 Hard</h3>';
    html += '    <p>Advanced AI with optimal decision-making</p>';
    html += '    <small>Bots: Neural, Quantum, Apex</small>';
    html += '  </div>';

    html += '  <button onclick="closeBotGameModal()">Cancel</button>';
    html += '</div>';

    showModal(html);
}

/**
 * Start a game against a bot
 */
function startBotGame(algorithm) {
    closeBotGameModal();

    ajax('startbotgame', {
        algorithm: algorithm
    }, function(response) {
        if (response.Error) {
            showError(response.Error);
            return;
        }

        // Navigate to game
        if (response.gameid) {
            window.location.href = 'farkle.php?game=' + response.gameid;
        }
    });
}
```

---

### 4. Modify `js/farkleGame.js` - Handle Bot Players

Detect and handle bot turns:

```javascript
/**
 * Check if it's a bot's turn
 */
function isBotTurn() {
    if (!gGameData || !gGamePlayerData) return false;

    var currentPlayer = gGamePlayerData.find(function(p) {
        return p.playerturn === gGameData.currentturn;
    });

    return currentPlayer && currentPlayer.is_bot;
}

/**
 * Execute bot turn automatically
 */
function executeBotTurn() {
    if (!isBotTurn()) return;

    var currentPlayer = gGamePlayerData.find(function(p) {
        return p.playerturn === gGameData.currentturn;
    });

    // Start bot turn
    startBotTurnPolling(gGameData.gameid, currentPlayer.playerid);
}

// Modify existing game update function
var originalFarkleSendUpdate = FarkleSendUpdate;
FarkleSendUpdate = function(gameId) {
    originalFarkleSendUpdate(gameId);

    // After game state updates, check if bot's turn
    setTimeout(function() {
        if (isBotTurn()) {
            executeBotTurn();
        }
    }, 500);
};
```

---

### 5. Modify `js/farkleGame.js` - Add Constants

```javascript
// Add after existing game constants
var GAME_WITH_BOT = 3;
var BOT_ALGORITHM_EASY = 'easy';
var BOT_ALGORITHM_MEDIUM = 'medium';
var BOT_ALGORITHM_HARD = 'hard';
```

---

## Templates to Create/Modify

### 1. Create `templates/farkle_div_bot_chat.tpl`

Bot message area template:

```html
<!-- Bot Chat Messages Area -->
<div id="divBotChat" class="bot-chat-container" style="display: none;">
    <div class="bot-chat-header">
        <i class="fa fa-robot"></i> Bot Commentary
    </div>
    <div id="botChatMessages" class="bot-chat-messages">
        <!-- Messages will be inserted here by JavaScript -->
    </div>
</div>

<style>
.bot-chat-container {
    background-color: #e3f2fd;
    border: 2px solid #2196f3;
    border-radius: 8px;
    padding: 10px;
    margin: 10px 0;
    max-width: 500px;
}

.bot-chat-header {
    font-weight: bold;
    color: #1976d2;
    margin-bottom: 8px;
    font-size: 14px;
}

.bot-chat-messages {
    max-height: 150px;
    overflow-y: auto;
}

.bot-message {
    background-color: white;
    border-radius: 4px;
    padding: 6px 10px;
    margin: 4px 0;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bot-message-icon {
    font-size: 16px;
    flex-shrink: 0;
}

.bot-message-text {
    flex-grow: 1;
    color: #333;
}
</style>
```

---

### 2. Modify `templates/farkle_div_lobby.tpl`

Add "Play a Bot" button:

```html
<!-- Add after existing game mode buttons -->
<div class="lobby-section">
    <h3>Single Player</h3>

    <!-- Existing Solo Play button -->
    <button id="btnSoloPlay" onclick="startSoloGame()">
        Practice Mode (Solo)
    </button>

    <!-- NEW: Play a Bot button -->
    <button id="btnPlayBot" onclick="showBotGameModal()" class="btn-bot-game">
        🤖 Play Against a Bot
    </button>
</div>

<style>
.btn-bot-game {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: bold;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    margin: 8px 0;
}

.btn-bot-game:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.bot-option {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    margin: 12px 0;
    cursor: pointer;
    transition: all 0.2s;
}

.bot-option:hover {
    border-color: #2196f3;
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.2);
    transform: translateY(-2px);
}

.bot-option h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
}

.bot-option p {
    margin: 0 0 4px 0;
    color: #666;
}

.bot-option small {
    color: #999;
    font-size: 12px;
}
</style>
```

---

### 3. Modify `templates/farkle_div_game.tpl`

Add bot chat area to game board:

```html
<!-- Add after game board, before player list -->
{if $gameData.bot_play_mode == 'interactive'}
    {include file="farkle_div_bot_chat.tpl"}
{/if}

<!-- Bot status message area -->
<div id="botStatusMessage" class="bot-status" style="display: none;"></div>
<div id="botDiceDisplay" class="bot-dice-display" style="display: none;"></div>
<div id="botKeptDice" class="bot-kept-dice" style="display: none;"></div>
```

---

## CSS Additions

### Bot Turn Animations

```css
/* Dice roll animation */
@keyframes shakeDice {
    0%, 100% { transform: translateX(0) rotate(0deg); }
    25% { transform: translateX(-5px) rotate(-5deg); }
    75% { transform: translateX(5px) rotate(5deg); }
}

.die {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    background: white;
    border: 2px solid #333;
    border-radius: 6px;
    margin: 4px;
    font-size: 24px;
    font-weight: bold;
}

.dice-rolling .die {
    animation: shakeDice 0.5s ease-in-out;
}

/* Bot status messages */
.bot-status {
    background-color: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
    padding: 10px;
    margin: 10px 0;
    text-align: center;
    font-weight: bold;
}

/* Bot player indicator */
.player-bot {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.player-bot::after {
    content: " 🤖";
}
```

---

## Testing Checklist

### UI Tests

- [ ] "Play a Bot" button visible in lobby
- [ ] Bot selection modal displays all three difficulties
- [ ] Clicking difficulty creates game with bot
- [ ] Bot chat area appears in bot games
- [ ] Messages display in chat area
- [ ] Chat area scrolls automatically
- [ ] Last 5 messages shown (older ones removed)
- [ ] Bot player has robot emoji in player list

### Interactive Turn Tests

- [ ] Bot turn starts automatically when it's bot's turn
- [ ] Dice roll animation plays
- [ ] Kept dice highlighted correctly
- [ ] Bot messages appear at each decision
- [ ] Turn progresses through all states
- [ ] Turn ends correctly (bank or farkle)
- [ ] Game state refreshes after bot turn
- [ ] Polling stops when turn completes

### Mobile/Tablet Tests

- [ ] Bot chat area responsive on mobile
- [ ] Bot selection modal works on touch devices
- [ ] Animations don't cause performance issues
- [ ] All buttons touchable and sized correctly

---

## Acceptance Criteria

- [ ] "Play a Bot" button added to lobby
- [ ] Bot selection modal functional with all three difficulties
- [ ] Bot game created successfully via AJAX
- [ ] Bot turn polling system works
- [ ] Bot turn animations smooth and visible
- [ ] Bot chat message area displays and updates
- [ ] Last 5 messages shown, auto-scroll works
- [ ] Bot players marked with emoji in player list
- [ ] Mobile/tablet support confirmed
- [ ] No JavaScript errors in console
- [ ] All UI tests pass

---

## Performance Considerations

- Poll interval at 500ms (not too aggressive)
- Stop polling when turn completes
- Clear intervals on page unload
- Use CSS animations (GPU-accelerated)
- Limit message history to 5 (prevent memory leak)

---

## Accessibility Notes

- Bot chat messages have sufficient color contrast
- Buttons have clear labels
- Animations can be reduced via CSS `prefers-reduced-motion`
- Screen reader support for bot status updates

---

## Future Enhancements (Not in Scope)

- Bot avatar images
- Customizable bot chat color per bot
- Sound effects for bot actions
- Typing indicator ("Bot is thinking...")
- Bot reaction emojis
- Replay bot turn button
