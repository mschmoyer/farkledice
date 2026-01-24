/**
 * farkleChallenge.js
 * Challenge Mode lobby and run management
 */

// Challenge state
var gChallengeStatus = null;
var gChallengeRunId = 0;

/**
 * Show the challenge lobby
 */
function ShowChallengeLobby() {
	ConsoleDebug('ShowChallengeLobby: Showing challenge lobby');

	HideAllWindows();
	$('#divChallengeLobby').show();

	// Load challenge status
	LoadChallengeStatus();
}

/**
 * Load challenge status from server
 */
function LoadChallengeStatus() {
	ConsoleDebug('LoadChallengeStatus: Loading status');

	var params = 'action=get_challenge_status&playerid=' + playerid;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					ConsoleDebug('LoadChallengeStatus: Error - ' + response.Error);
					return;
				}

				gChallengeStatus = response;
				UpdateChallengeLobbyUI();

			} catch (e) {
				ConsoleDebug('LoadChallengeStatus: Parse error - ' + e);
			}
		}
	}, params);
}

/**
 * Update the challenge lobby UI based on current status
 */
function UpdateChallengeLobbyUI() {
	if (!gChallengeStatus) return;

	// Show/hide active run vs no run sections
	if (gChallengeStatus.has_active_run) {
		$('#divChallengeActiveRun').show();
		$('#divChallengeNoRun').hide();

		var run = gChallengeStatus.active_run;
		gChallengeRunId = run.run_id;

		$('#challengeCurrentBotNum').text(run.current_bot_num);
		$('#challengeRunMoney').text(run.money);

		if (run.current_bot) {
			$('#challengeCurrentBotName').text(run.current_bot.bot_name);
			$('#challengeCurrentBotDiff').text(run.current_bot.difficulty);
		}

		// Render dice inventory
		RenderChallengeDice(run.dice_inventory);
	} else {
		$('#divChallengeActiveRun').hide();
		$('#divChallengeNoRun').show();
		gChallengeRunId = 0;
	}

	// Update stats
	var stats = gChallengeStatus.stats;
	$('#challengeStatRuns').text(stats.total_runs);
	$('#challengeStatWins').text(stats.total_wins);
	$('#challengeStatFurthest').text(stats.furthest_bot);
	$('#challengeStatDiceSaved').text(stats.total_dice_saved);

	// Render bot lineup
	RenderBotLineup(gChallengeStatus.bot_lineup);
}

// Dice category colors (same as shop)
var gChallengeDiceColors = {
	'farkle_lovers': '#cc0000',      // red
	'farkle_protection': '#1d8711',  // green
	'face_changers': '#4169E1',      // blue
	'score_boosters': '#FFA500'      // orange
};

/**
 * Render player's dice inventory in the lobby
 */
function RenderChallengeDice(inventory) {
	var html = '';

	for (var i = 0; i < 6; i++) {
		var die = null;
		// Find die for this slot
		for (var j = 0; j < inventory.length; j++) {
			var slotNum = inventory[j].slot_number || inventory[j].dice_slot;
			if (slotNum == (i + 1)) {
				die = inventory[j];
				break;
			}
		}

		var dieName = die ? die.name : 'Standard';
		var shortWord = 'STD';
		var isSpecial = false;
		var categoryColor = '#666';

		if (die) {
			// Extract short_word from effect_value or direct property
			if (die.short_word) {
				shortWord = die.short_word;
			} else if (die.effect_value) {
				try {
					var effectVal = typeof die.effect_value === 'string'
						? JSON.parse(die.effect_value)
						: die.effect_value;
					shortWord = effectVal.short_word || 'STD';
				} catch (e) {}
			}

			isSpecial = (shortWord !== 'STD' && dieName !== 'Standard');
			if (isSpecial && die.category) {
				categoryColor = gChallengeDiceColors[die.category] || '#4169E1';
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

	$('#divChallengeRunDice').html(html);
}

/**
 * Render the bot lineup preview
 */
function RenderBotLineup(bots) {
	var html = '<table width="100%" style="color: white;">';

	for (var i = 0; i < bots.length; i++) {
		var bot = bots[i];
		var diffColor = '#666';

		if (bot.difficulty === 'Easy') diffColor = '#1d8711';
		else if (bot.difficulty === 'Medium') diffColor = '#FFA500';
		else if (bot.difficulty === 'Hard') diffColor = '#cc0000';
		else if (bot.difficulty === 'Very Hard') diffColor = '#8B0000';
		else if (bot.difficulty === 'Boss') diffColor = '#4B0082';

		var isHidden = (bot.bot_name === '???');
		var opacity = isHidden ? '0.5' : '1';

		html += '<tr style="opacity: ' + opacity + ';">';
		html += '  <td style="width: 30px;">#' + bot.bot_number + '</td>';
		html += '  <td>' + bot.bot_name + '</td>';
		html += '  <td style="color: ' + diffColor + ';">' + bot.difficulty + '</td>';
		html += '  <td style="text-align: right;">' + (isHidden ? '???' : bot.target_score) + '</td>';
		html += '</tr>';
	}

	html += '</table>';
	$('#divChallengeBotLineup').html(html);
}

/**
 * Start a new challenge run
 */
function ChallengeStartRun() {
	ConsoleDebug('ChallengeStartRun: Starting new run');

	var params = 'action=start_challenge&playerid=' + playerid;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					alert('Error: ' + response.Error);
					return;
				}

				// Update status and UI
				gChallengeStatus = response;
				gChallengeRunId = response.active_run ? response.active_run.run_id : 0;
				UpdateChallengeLobbyUI();

				// Show the shop before the first bot game
				if (gChallengeRunId > 0 && response.active_run) {
					var run = response.active_run;
					var botNum = run.current_bot_num;
					var botName = run.current_bot ? run.current_bot.bot_name : 'Bot #' + botNum;
					var inventory = run.dice_inventory || [];
					var money = run.money;

					// Pass true for isStart since this is the beginning of the challenge
					ShowChallengeShop(gChallengeRunId, money, botNum, botName, inventory, true);
				}

			} catch (e) {
				ConsoleDebug('ChallengeStartRun: Parse error - ' + e);
				alert('Error starting challenge. Please try again.');
			}
		}
	}, params);
}

/**
 * Resume an existing challenge run
 */
function ChallengeResumeRun() {
	ConsoleDebug('ChallengeResumeRun: Resuming run ' + gChallengeRunId);

	if (gChallengeRunId <= 0) {
		alert('No active run to resume.');
		return;
	}

	// Start game against current bot
	ChallengeStartBotGame();
}

/**
 * Start a game against the current bot
 */
function ChallengeStartBotGame() {
	ConsoleDebug('ChallengeStartBotGame: Starting bot game for run ' + gChallengeRunId);

	var params = 'action=start_challenge_game&run_id=' + gChallengeRunId;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					alert('Error: ' + response.Error);
					return;
				}

				ConsoleDebug('ChallengeStartBotGame: Game created - ' + response.game_id);

				// Game created - show the game window and load the game
				if (response.game_id) {
					ShowFarkleGame(response.game_id);
				}

			} catch (e) {
				ConsoleDebug('ChallengeStartBotGame: Parse error - ' + e);
				alert('Error starting game. Please try again.');
			}
		}
	}, params);
}

/**
 * Abandon the current challenge run
 */
function ChallengeAbandonRun() {
	if (!confirm('Are you sure you want to abandon this run? You will lose all progress.')) {
		return;
	}

	ConsoleDebug('ChallengeAbandonRun: Abandoning run');

	var params = 'action=abandon_challenge&playerid=' + playerid;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					alert('Error: ' + response.Error);
					return;
				}

				// Reload status
				LoadChallengeStatus();

			} catch (e) {
				ConsoleDebug('ChallengeAbandonRun: Parse error - ' + e);
			}
		}
	}, params);
}

/**
 * Start over - abandon current run and start a new one
 */
function ChallengeStartOver() {
	if (!confirm('Are you sure? This will abandon your current run.')) {
		return;
	}

	ConsoleDebug('ChallengeStartOver: Abandoning and starting new run');

	var params = 'action=abandon_challenge&playerid=' + playerid;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					alert('Error: ' + response.Error);
					return;
				}

				// Now start a new run
				ChallengeStartRun();

			} catch (e) {
				ConsoleDebug('ChallengeStartOver: Parse error - ' + e);
			}
		}
	}, params);
}

/**
 * Called when a challenge game ends (win or loss)
 * @param {boolean} won Whether the player won
 * @param {number} diceSaved Number of dice saved during the game
 */
function ChallengeGameEnded(won, diceSaved) {
	ConsoleDebug('ChallengeGameEnded: won=' + won + ', diceSaved=' + diceSaved);

	if (gChallengeRunId <= 0) {
		ConsoleDebug('ChallengeGameEnded: No active run ID');
		return;
	}

	var params = 'action=challenge_game_result&run_id=' + gChallengeRunId +
		'&won=' + (won ? '1' : '0') + '&dice_saved=' + diceSaved;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					alert('Error: ' + response.Error);
					ShowChallengeLobby();
					return;
				}

				if (response.challenge_complete) {
					// Player completed the challenge!
					alert('Congratulations! You completed the Challenge Mode!');
					ShowChallengeLobby();
				} else if (response.run_ended) {
					// Player lost
					alert(response.message);
					ShowChallengeLobby();
				} else if (response.show_shop) {
					// Player won - show shop
					// Get the current bot number (the one just defeated) and dice inventory
					var botNum = gGameData.challenge_bot_number || 1;
					var botName = '';

					// Find the bot name from the opponent data
					for (var i = 0; i < gGamePlayerData.length; i++) {
						if (gGamePlayerData[i].playerid != playerid && gGamePlayerData[i].is_bot) {
							botName = gGamePlayerData[i].username;
							break;
						}
					}

					// Refresh challenge status to get updated inventory, then show shop
					LoadChallengeStatusThenShop(response.money, botNum, botName);
				}

			} catch (e) {
				ConsoleDebug('ChallengeGameEnded: Parse error - ' + e);
				ShowChallengeLobby();
			}
		}
	}, params);
}

/**
 * Load challenge status then show shop with updated inventory
 */
function LoadChallengeStatusThenShop(money, botNum, botName) {
	var params = 'action=get_challenge_status&playerid=' + playerid;

	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");

				if (response.Error) {
					ConsoleDebug('LoadChallengeStatusThenShop: Error - ' + response.Error);
					ShowChallengeLobby();
					return;
				}

				gChallengeStatus = response;

				// Get inventory from updated status
				var inventory = [];
				if (gChallengeStatus.active_run && gChallengeStatus.active_run.dice_inventory) {
					inventory = gChallengeStatus.active_run.dice_inventory;
				}

				// Pass false for isStart since this is after a victory
				ShowChallengeShop(gChallengeRunId, money, botNum, botName, inventory, false);

			} catch (e) {
				ConsoleDebug('LoadChallengeStatusThenShop: Parse error - ' + e);
				ShowChallengeLobby();
			}
		}
	}, params);
}
