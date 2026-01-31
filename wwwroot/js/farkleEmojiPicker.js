/**
 * Emoji Picker for Farkle Ten
 * Allows players to send emoji reactions after games end
 */

// Store the current game ID for the emoji picker
var emojiPickerGameId = null;
var emojiPickerLastEmoji = null;

/**
 * Shows the emoji picker popup
 * @param {number} gameid - The game ID for which to send a reaction
 */
function ShowEmojiPicker(gameid) {
    emojiPickerGameId = gameid;
    document.getElementById('emojiPickerGameId').value = gameid;
    document.getElementById('emojiPickerOverlay').style.display = 'flex';
}

/**
 * Hides the emoji picker popup
 */
function HideEmojiPicker() {
    document.getElementById('emojiPickerOverlay').style.display = 'none';
    emojiPickerGameId = null;
    document.getElementById('emojiPickerGameId').value = '';
}

/**
 * Sends an emoji reaction to the server
 * @param {string} emoji - The emoji character to send
 */
function SendEmojiReaction(emoji) {
    var gameid = emojiPickerGameId || document.getElementById('emojiPickerGameId').value;

    if (!gameid) {
        console.error('No game ID set for emoji reaction');
        HideEmojiPicker();
        return;
    }

    // Store emoji to update player card after response
    emojiPickerLastEmoji = emoji;

    var params = 'action=submitemoji&gameid=' + encodeURIComponent(gameid) + '&emoji=' + encodeURIComponent(emoji);

    AjaxCallPost('farkle_fetch.php', HandleEmojiResponse, params);

    // Hide the picker immediately for better UX
    HideEmojiPicker();
}

/**
 * Skips sending an emoji reaction (marks as skipped on server)
 */
function SkipEmojiReaction() {
    var gameid = emojiPickerGameId || document.getElementById('emojiPickerGameId').value;

    if (!gameid) {
        console.error('No game ID set for skipping emoji reaction');
        HideEmojiPicker();
        return;
    }

    var params = 'action=submitemoji&gameid=' + encodeURIComponent(gameid) + '&emoji=';

    AjaxCallPost('farkle_fetch.php', HandleEmojiResponse, params);

    // Hide the picker immediately
    HideEmojiPicker();
}

/**
 * Handles the server response after submitting an emoji
 */
function HandleEmojiResponse() {
    try {
        var response = JSON.parse(ajaxrequest.responseText);
        if (response.success) {
            // Emoji submitted successfully - update local data and re-render player cards only
            if (emojiPickerLastEmoji && typeof gGamePlayerData !== 'undefined' && typeof g_myPlayerIndex !== 'undefined' && g_myPlayerIndex >= 0) {
                gGamePlayerData[g_myPlayerIndex].emoji_sent = emojiPickerLastEmoji;
                // Re-render just the player cards (not the full game state)
                if (typeof FarkleGamePlayerTag === 'function') {
                    for (var i = 0; i < gGamePlayerData.length; i++) {
                        var p = gGamePlayerData[i];
                        var scoreStr = addCommas(parseInt(p.playerscore) + parseInt(p.lastroundscore || 0));
                        FarkleGamePlayerTag(p, scoreStr);
                    }
                }
            }
            emojiPickerLastEmoji = null;
        } else {
            console.error('Failed to submit emoji:', response.error);
        }
    } catch (e) {
        console.error('Error parsing emoji response:', e);
    }
}
