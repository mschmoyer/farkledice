/**
 * farkleShop.js
 * Challenge Mode shop interface controller
 *
 * Handles displaying shop dice, purchasing, and slot selection
 */

// Shop state
var gShopRunId = 0;
var gShopDice = [];
var gShopMoney = 0;
var gShopInventory = [];
var gSelectedDiceTypeId = 0;
var gSelectedDicePrice = 0;
var gSelectedDiceName = '';
var gSelectedSlot = -1;
var gShopBotNum = 0;
var gShopBotName = '';

// Category colors from special-dice.json
var gDiceColors = {
	'farkle_lovers': '#cc0000',      // red
	'farkle_protection': '#1d8711',  // green
	'face_changers': '#4169E1',      // blue
	'score_boosters': '#FFA500'      // orange
};

/**
 * Show the challenge shop after defeating a bot or at start of challenge
 * @param {number} runId - The current challenge run ID
 * @param {number} money - Current player money
 * @param {number} botNum - Bot number (next opponent at start, or just defeated if after victory)
 * @param {string} botName - Bot name
 * @param {array} inventory - Current dice inventory (6 slots)
 * @param {boolean} isStart - True if showing shop at start of challenge (before first game)
 */
function ShowChallengeShop(runId, money, botNum, botName, inventory, isStart) {
	ConsoleDebug('ShowChallengeShop: runId=' + runId + ', money=' + money + ', isStart=' + isStart);

	gShopRunId = runId;
	gShopMoney = money;
	gShopBotNum = botNum;
	gShopBotName = botName;
	gShopInventory = inventory || [];

	// Show the shop div
	HideAllWindows();
	$('#divChallengeShop').show();

	// Show appropriate header based on context
	if (isStart) {
		// Start of challenge - show "CHALLENGE BEGUN!" header
		$('#divShopVictoryHeader').hide();
		$('#divShopStartHeader').show();
		$('#shopStartBotNum').text(botNum);
		$('#shopStartBotName').text(botName);
		$('#shopStartMoney').text('$' + money);
	} else {
		// After victory - show "VICTORY!" header
		$('#divShopStartHeader').hide();
		$('#divShopVictoryHeader').show();
		$('#shopBotNum').text(botNum);
		$('#shopBotName').text(botName);
		$('#shopMoney').text('$' + money);
	}

	// Reset state
	gSelectedDiceTypeId = 0;
	gSelectedSlot = -1;
	$('#btnContinueAfterPurchase').hide();

	// Render current inventory
	RenderShopInventory();

	// Load shop dice from server
	LoadShopDice();
}

/**
 * Load available dice from server
 */
function LoadShopDice() {
	ConsoleDebug('LoadShopDice: Loading dice for run ' + gShopRunId);

	$('#divShopDiceCards').html('<p style="color: white;">Loading...</p>');

	var params = 'action=get_shop&run_id=' + gShopRunId;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					ConsoleDebug('LoadShopDice: Error - ' + response.Error);
					$('#divShopDiceCards').html('<p style="color: #cc0000;">Error loading shop</p>');
					return;
				}

				gShopDice = response.dice || [];
				ConsoleDebug('LoadShopDice: Loaded ' + gShopDice.length + ' dice');
				RenderShopDice();

			} catch (e) {
				ConsoleDebug('LoadShopDice: Parse error - ' + e);
				$('#divShopDiceCards').html('<p style="color: #cc0000;">Error loading shop</p>');
			}
		}
	}, params);
}

/**
 * Render the dice cards in the shop
 */
function RenderShopDice() {
	var html = '';

	for (var i = 0; i < gShopDice.length; i++) {
		var die = gShopDice[i];
		var canAfford = gShopMoney >= die.price;
		var borderColor = gDiceColors[die.category] || '#666';
		var opacity = canAfford ? '1' : '0.6';
		var textColor = canAfford ? borderColor : '#666';

		html += '<div class="loginBox" style="margin: 5px; border: 2px solid ' + borderColor + '; opacity: ' + opacity + ';">';
		html += '  <div style="font-size: 16px; font-weight: bold; color: ' + textColor + ';">' + die.name.toUpperCase() + '</div>';

		// Dice image placeholder (using standard dice for now)
		html += '  <div style="margin: 5px;">';
		html += '    <img src="/images/diceFront1.png" width="50" height="50">';
		html += '  </div>';

		html += '  <p style="font-size: 14px; margin: 5px; color: white;">' + (die.effect || die.description || '') + '</p>';

		html += '  <div style="font-size: 16px; font-family: \'Courier New\', monospace; margin: 5px; color: white;">$' + die.price + '</div>';

		if (canAfford) {
			html += '  <input type="button" class="mobileButton" buttoncolor="green" value="BUY" ';
			html += '    onClick="BuyDie(' + die.dice_type_id + ', ' + die.price + ', \'' + die.name.replace(/'/g, "\\'") + '\')" style="width: 150px;">';
		} else {
			html += '  <input type="button" class="mobileButton" disabled value="BUY" style="width: 150px;">';
		}

		html += '</div>';
	}

	if (gShopDice.length === 0) {
		html = '<p style="color: white; margin: 10px;">No dice available in shop.</p>';
	}

	$('#divShopDiceCards').html(html);
}

/**
 * Render the player's current dice inventory
 */
function RenderShopInventory() {
	var html = '';

	for (var i = 0; i < 6; i++) {
		var slot = null;

		// Find die for this slot (inventory might be array with dice_slot or slot_number property)
		for (var j = 0; j < gShopInventory.length; j++) {
			var slotNum = gShopInventory[j].dice_slot || gShopInventory[j].slot_number;
			if (slotNum == (i + 1)) {
				slot = gShopInventory[j];
				break;
			}
		}

		var dieName = slot && slot.name ? slot.name : 'Standard';
		var shortWord = 'STD';
		var isSpecial = false;
		var categoryColor = '#666';

		// Extract short_word - check direct property first, then effect_value JSON
		if (slot) {
			if (slot.short_word) {
				shortWord = slot.short_word;
			} else if (slot.effect_value) {
				try {
					var effectVal = typeof slot.effect_value === 'string'
						? JSON.parse(slot.effect_value)
						: slot.effect_value;
					shortWord = effectVal.short_word || 'STD';
				} catch (e) {}
			}

			// Check if it's a special die (not standard)
			isSpecial = (shortWord !== 'STD' && dieName !== 'Standard');

			// Get category color for special dice
			if (isSpecial && slot.category) {
				categoryColor = gDiceColors[slot.category] || '#4169E1';
			}
		}

		// Style differently for special dice
		var borderStyle = isSpecial ? '2px solid ' + categoryColor : '1px solid #666';
		var bgColor = isSpecial ? 'rgba(255,255,255,0.1)' : 'transparent';
		var textColor = isSpecial ? categoryColor : '#999';

		html += '<span style="display: inline-block; margin: 3px; padding: 3px; text-align: center; ';
		html += 'border: ' + borderStyle + '; border-radius: 4px; background: ' + bgColor + ';">';
		html += '  <img src="/images/diceFront1.png" width="28" height="28" title="' + dieName + '"><br/>';
		html += '  <span style="font-size: 10px; font-weight: ' + (isSpecial ? 'bold' : 'normal') + '; color: ' + textColor + ';">' + shortWord + '</span>';
		html += '</span>';
	}

	$('#divShopDiceInventory').html(html);
}

/**
 * Start purchase flow - show slot selection modal
 */
function BuyDie(diceTypeId, price, name) {
	ConsoleDebug('BuyDie: diceTypeId=' + diceTypeId + ', price=' + price + ', name=' + name);

	gSelectedDiceTypeId = diceTypeId;
	gSelectedDicePrice = price;
	gSelectedDiceName = name;
	gSelectedSlot = -1;

	// Update modal content
	$('#purchaseDiceName').text(name);
	$('#purchasePrice').text(price);
	$('#slotSelectedLabel').hide();
	$('#btnConfirmPurchase').prop('disabled', true);

	// Render slot selection
	RenderSlotSelection();

	// Show modal
	$('#slotSelectionOverlay').show();
}

/**
 * Render the 6 dice slots for selection
 */
function RenderSlotSelection() {
	var html = '';

	for (var i = 0; i < 6; i++) {
		var slot = null;

		// Find die for this slot
		for (var j = 0; j < gShopInventory.length; j++) {
			var slotNum = gShopInventory[j].dice_slot || gShopInventory[j].slot_number;
			if (slotNum == (i + 1)) {
				slot = gShopInventory[j];
				break;
			}
		}

		var dieName = slot && slot.name ? slot.name : 'Standard';
		var shortWord = 'STD';
		var isSpecial = false;
		var categoryColor = '#666';

		// Extract short_word
		if (slot) {
			if (slot.short_word) {
				shortWord = slot.short_word;
			} else if (slot.effect_value) {
				try {
					var effectVal = typeof slot.effect_value === 'string'
						? JSON.parse(slot.effect_value)
						: slot.effect_value;
					shortWord = effectVal.short_word || 'STD';
				} catch (e) {}
			}

			isSpecial = (shortWord !== 'STD' && dieName !== 'Standard');
			if (isSpecial && slot.category) {
				categoryColor = gDiceColors[slot.category] || '#4169E1';
			}
		}

		var isSelected = (i === gSelectedSlot);
		var borderColor = isSelected ? '#f7ef00' : (isSpecial ? categoryColor : '#ccc');
		var borderWidth = isSelected ? '3px' : '2px';
		var bgColor = isSpecial ? 'rgba(100,100,100,0.2)' : '#f0f0f0';
		var textColor = isSpecial ? categoryColor : '#333';

		html += '<div class="diceSlot" onclick="SelectSlot(' + i + ')" ontouchstart="" ';
		html += '  style="display: inline-block; margin: 5px; padding: 5px; background: ' + bgColor + '; ';
		html += '  border: ' + borderWidth + ' solid ' + borderColor + '; border-radius: 4px; cursor: pointer;">';
		html += '  <img src="/images/diceFront1.png" width="50" height="50" title="' + dieName + '"><br/>';
		html += '  <span style="font-size: 12px; font-weight: ' + (isSpecial ? 'bold' : 'normal') + '; color: ' + textColor + ';">' + shortWord + '</span>';
		html += '</div>';
	}

	$('#divSlotSelection').html(html);
}

/**
 * Handle slot selection
 */
function SelectSlot(slotIndex) {
	ConsoleDebug('SelectSlot: ' + slotIndex);

	gSelectedSlot = slotIndex;

	// Update UI
	RenderSlotSelection();
	$('#slotSelectedLabel').show();
	$('#btnConfirmPurchase').prop('disabled', false);
}

/**
 * Confirm the purchase
 */
function ConfirmPurchase() {
	if (gSelectedSlot < 0 || gSelectedDiceTypeId <= 0) {
		ConsoleDebug('ConfirmPurchase: Invalid selection');
		return;
	}

	ConsoleDebug('ConfirmPurchase: Purchasing dice ' + gSelectedDiceTypeId + ' into slot ' + (gSelectedSlot + 1));

	// Disable button during request
	$('#btnConfirmPurchase').prop('disabled', true);

	var params = 'action=purchase_dice&run_id=' + gShopRunId +
		'&dice_type_id=' + gSelectedDiceTypeId +
		'&slot_number=' + (gSelectedSlot + 1);  // 1-indexed for backend

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					ConsoleDebug('ConfirmPurchase: Error - ' + response.Error);
					alert('Purchase failed: ' + response.Error);
					$('#btnConfirmPurchase').prop('disabled', false);
					return;
				}

				// Update local state
				gShopMoney = response.new_money;
				gShopInventory = response.inventory || gShopInventory;

				ConsoleDebug('ConfirmPurchase: Success! New money: ' + gShopMoney);

				// Close modal and update UI
				CloseSlotModal();
				// Update both money spans (whichever is visible)
				$('#shopMoney').text('$' + gShopMoney);
				$('#shopStartMoney').text('$' + gShopMoney);
				RenderShopInventory();
				RenderShopDice();  // Re-render to update affordability

				// Show continue button, hide skip
				$('#btnContinueAfterPurchase').show();

			} catch (e) {
				ConsoleDebug('ConfirmPurchase: Parse error - ' + e);
				alert('Purchase failed. Please try again.');
				$('#btnConfirmPurchase').prop('disabled', false);
			}
		}
	}, params);
}

/**
 * Close the slot selection modal
 */
function CloseSlotModal() {
	$('#slotSelectionOverlay').hide();
	gSelectedSlot = -1;
}

/**
 * Skip the shop and continue to next bot
 */
function SkipShop() {
	ConsoleDebug('SkipShop: Skipping shop');
	ContinueToNextBot();
}

/**
 * Continue to the next bot battle
 */
function ContinueToNextBot() {
	ConsoleDebug('ContinueToNextBot: Moving to next bot');

	// Hide shop
	$('#divChallengeShop').hide();

	// Start next bot game using the current challenge run
	if (gShopRunId > 0) {
		gChallengeRunId = gShopRunId;
		ChallengeStartBotGame();
	} else {
		ConsoleDebug('ContinueToNextBot: No run ID, returning to lobby');
		ShowChallengeLobby();
	}
}
