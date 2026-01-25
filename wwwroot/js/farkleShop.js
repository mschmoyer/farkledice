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
		// Start of challenge - show "GAUNTLET BEGUN!" header
		$('#divShopVictoryHeader').hide();
		$('#divShopStartHeader').show();
		$('#shopStartBotNum').text(botNum);
		$('#shopStartBotName').text(botName);
		$('#shopStartMoney').text(money);
	} else {
		// After victory - show "VICTORY!" header
		$('#divShopStartHeader').hide();
		$('#divShopVictoryHeader').show();
		$('#shopBotNum').text(botNum);
		$('#shopBotName').text(botName);
		$('#shopMoney').text(money);
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

		// Extract short_word for dice display
		var info = ExtractDiceInfo(die);

		html += '<div class="loginBox" style="margin: 5px; border: 2px solid ' + borderColor + '; opacity: ' + opacity + ';">';
		html += '  <div class="shadowed" style="font-size: 20px; font-weight: bold; color: white;">' + die.name.toUpperCase() + '</div>';

		// Use dice square component
		html += '  <div style="margin: 5px;">';
		html += CreateDiceSquare({
			label: info.shortWord,
			category: die.category,
			isSpecial: true,
			size: 'normal',
			title: die.name,
			pips: 6
		});
		html += '  </div>';

		html += '  <p style="font-size: 16px; margin: 5px; color: white;">' + (die.effect || die.description || '') + '</p>';

		html += '  <div class="shadowed" style="font-size: 24px; font-weight: 900; font-family: \'Courier New\', monospace; margin: 5px; color: #feca57;">$' + die.price + '</div>';

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

		var info = ExtractDiceInfo(slot);

		html += CreateDiceSquare({
			label: info.shortWord,
			category: info.category,
			isSpecial: info.isSpecial,
			size: 'small',
			title: info.name,
			pips: i + 1
		});
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

		var info = ExtractDiceInfo(slot);
		var isSelected = (i === gSelectedSlot);

		// Selection styling
		var selectionBorder = isSelected ? '3px solid #f7ef00' : '2px solid transparent';
		var selectionBg = isSelected ? 'rgba(247, 239, 0, 0.1)' : 'transparent';

		html += '<div class="diceSlot" onclick="SelectSlot(' + i + ')" ontouchstart="" ';
		html += '  style="padding: 8px; border: ' + selectionBorder + '; border-radius: 8px; cursor: pointer; background: ' + selectionBg + ';">';
		html += CreateDiceSquare({
			label: info.shortWord,
			category: info.category,
			isSpecial: info.isSpecial,
			size: 'normal',
			title: info.name,
			pips: i + 1
		});
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
				$('#shopMoney').text(gShopMoney);
				$('#shopStartMoney').text(gShopMoney);
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
