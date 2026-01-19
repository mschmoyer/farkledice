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

/**
 * Render player's dice inventory in the lobby
 */
function RenderChallengeDice(inventory) {
	var html = '';

	for (var i = 0; i < 6; i++) {
		var die = null;
		// Find die for this slot
		for (var j = 0; j < inventory.length; j++) {
			if (inventory[j].slot_number == (i + 1)) {
				die = inventory[j];
				break;
			}
		}

		var dieName = die ? die.name : 'Standard';
		var shortWord = 'STD';
		if (die && die.effect_value) {
			try {
				var effectVal = JSON.parse(die.effect_value);
				shortWord = effectVal.short_word || 'STD';
			} catch (e) {}
		}

		html += '<span style="display: inline-block; margin: 2px; text-align: center;">';
		html += '  <img src="/images/die1.gif" width="24" height="24" title="' + dieName + '"><br/>';
		html += '  <span style="font-size: 10px; color: white;">' + shortWord + '</span>';
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

				// Start the first bot game
				if (gChallengeRunId > 0) {
					ChallengeStartBotGame();
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

				// Game created - show the game
				if (response.game_data && response.game_data[0]) {
					FarkleGameStarted(response.game_data);
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
					ShowChallengeShop(gChallengeRunId, response.money,
						gChallengeStatus.active_run.current_bot_num,
						gChallengeStatus.active_run.current_bot.bot_name,
						gChallengeStatus.active_run.dice_inventory);
				}

			} catch (e) {
				ConsoleDebug('ChallengeGameEnded: Parse error - ' + e);
				ShowChallengeLobby();
			}
		}
	}, params);
}
