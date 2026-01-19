<?php
/*
	farkleBotTurn.php
	Desc: Interactive turn state machine for bot players. Supports both interactive
	      (step-by-step visible) and instant (complete all rounds) play modes.

	State Progression:
	  rolling → choosing_keepers → deciding_roll → banking/farkled

	Changelog:
	18-Jan-2026		mas		Initial implementation with interactive state machine
*/

require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleBotAI.php');
require_once('farkleBotMessages.php');
require_once('farkleGameFuncs.php');
require_once('farkleDiceScoring.php');

// ============================================================================
// STATE INITIALIZATION AND MANAGEMENT
// ============================================================================

/**
 * Initialize a new bot turn state
 *
 * Creates entry in farkle_bot_game_state table to track turn progression
 *
 * @param int $gameId Game ID
 * @param int $playerId Bot player ID
 * @return array|null State record or null on failure
 */
function Bot_InitializeTurnState($gameId, $playerId) {
	BaseUtil_Debug(__FUNCTION__ . ": Initializing turn state for game $gameId, player $playerId", 14);

	$dbh = db_connect();

	// Delete any existing state for this game/player combo
	$sql = "DELETE FROM farkle_bot_game_state WHERE gameid = :gameid AND playerid = :playerid";
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameId, ':playerid' => $playerId]);

	// Create new state
	$sql = "INSERT INTO farkle_bot_game_state
	        (gameid, playerid, current_step, dice_remaining, turn_score, created_at, updated_at)
	        VALUES (:gameid, :playerid, 'rolling', 6, 0, NOW(), NOW())
	        RETURNING stateid, gameid, playerid, current_step, dice_kept, turn_score,
	                  dice_remaining, last_roll, last_message, created_at, updated_at";

	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameId, ':playerid' => $playerId]);

	$state = $stmt->fetch(PDO::FETCH_ASSOC);

	BaseUtil_Debug(__FUNCTION__ . ": Created state with ID " . ($state['stateid'] ?? 'FAILED'), 14);

	return $state;
}

/**
 * Get current turn state for a bot
 *
 * @param int $gameId Game ID
 * @param int $playerId Bot player ID
 * @return array|null State record or null if not found
 */
function Bot_GetTurnState($gameId, $playerId) {
	$dbh = db_connect();

	$sql = "SELECT stateid, gameid, playerid, current_step, dice_kept, turn_score,
	               dice_remaining, last_roll, last_message, created_at, updated_at
	        FROM farkle_bot_game_state
	        WHERE gameid = :gameid AND playerid = :playerid
	        ORDER BY created_at DESC LIMIT 1";

	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameId, ':playerid' => $playerId]);

	$state = $stmt->fetch(PDO::FETCH_ASSOC);

	return $state ?: null;
}

/**
 * Update turn state with new values
 *
 * @param int $gameId Game ID
 * @param int $playerId Bot player ID
 * @param array $updates Associative array of field => value pairs to update
 * @return array|null Updated state record or null on failure
 */
function Bot_UpdateTurnState($gameId, $playerId, $updates) {
	$dbh = db_connect();

	// Build SET clause dynamically
	$setClauses = ['updated_at = NOW()'];
	$params = [':gameid' => $gameId, ':playerid' => $playerId];

	$allowedFields = ['current_step', 'dice_kept', 'turn_score', 'dice_remaining', 'last_roll', 'last_message'];

	foreach ($updates as $field => $value) {
		if (in_array($field, $allowedFields)) {
			$setClauses[] = "$field = :$field";
			$params[":$field"] = $value;
		}
	}

	$setClause = implode(', ', $setClauses);

	$sql = "UPDATE farkle_bot_game_state
	        SET $setClause
	        WHERE gameid = :gameid AND playerid = :playerid";

	$stmt = $dbh->prepare($sql);
	$stmt->execute($params);

	// Return updated state
	return Bot_GetTurnState($gameId, $playerId);
}

// ============================================================================
// MAIN STEP EXECUTOR
// ============================================================================

/**
 * Execute the next step in the bot's turn
 *
 * Routes to appropriate step handler based on current_step field
 *
 * @param int $gameId Game ID
 * @param int $playerId Bot player ID
 * @return array Step execution result with new state
 */
function Bot_ExecuteStep($gameId, $playerId) {
	BaseUtil_Debug(__FUNCTION__ . ": Executing step for game $gameId, player $playerId", 14);
	error_log("Bot_ExecuteStep: Called for game $gameId, player $playerId");

	// Get current state
	$state = Bot_GetTurnState($gameId, $playerId);

	// If no state exists, initialize it (first turn)
	if (!$state) {
		error_log("Bot_ExecuteStep: No state found, initializing turn state");
		BaseUtil_Debug(__FUNCTION__ . ": No state found, initializing turn for game $gameId, player $playerId", 14);
		$state = Bot_InitializeTurnState($gameId, $playerId);

		if (!$state) {
			BaseUtil_Error(__FUNCTION__ . ": Failed to initialize turn state for game $gameId, player $playerId");
			error_log("Bot_ExecuteStep: FAILED to initialize turn state");
			return ['error' => 'Failed to initialize turn state'];
		}
		error_log("Bot_ExecuteStep: Turn state initialized successfully");
	}

	// Get game data and bot player record
	$gameData = Bot_GetGameData($gameId);
	$botPlayer = Bot_GetBotPlayer($playerId);

	if (!$gameData || !$botPlayer) {
		BaseUtil_Error(__FUNCTION__ . ": Invalid game or bot player");
		return ['error' => 'Invalid game or player'];
	}

	// Route to appropriate step handler
	$currentStep = $state['current_step'];

	BaseUtil_Debug(__FUNCTION__ . ": Current step is $currentStep", 14);

	switch ($currentStep) {
		case 'rolling':
			return Bot_Step_Rolling($state, $gameData, $botPlayer);

		case 'choosing_keepers':
			return Bot_Step_ChoosingKeepers($state, $gameData, $botPlayer);

		case 'deciding_roll':
			return Bot_Step_DecidingRoll($state, $gameData, $botPlayer);

		case 'banking':
			return Bot_Step_Banking($state, $gameData, $botPlayer);

		case 'farkled':
			return Bot_Step_Farkled($state, $gameData, $botPlayer);

		default:
			BaseUtil_Error(__FUNCTION__ . ": Unknown step: $currentStep");
			return ['error' => "Unknown step: $currentStep"];
	}
}

// ============================================================================
// STEP IMPLEMENTATIONS
// ============================================================================

/**
 * Step 1: Roll dice
 *
 * Rolls N dice (where N = dice_remaining) and transitions to choosing_keepers
 *
 * @param array $state Current state
 * @param array $gameData Game data
 * @param array $botPlayer Bot player record
 * @return array Updated state with roll result
 */
function Bot_Step_Rolling($state, $gameData, $botPlayer) {
	BaseUtil_Debug(__FUNCTION__ . ": Rolling " . $state['dice_remaining'] . " dice", 14);

	$numDice = $state['dice_remaining'];
	$roll = [];

	// Roll the dice
	for ($i = 0; $i < $numDice; $i++) {
		$roll[] = rand(1, 6);
	}

	BaseUtil_Debug(__FUNCTION__ . ": Rolled: " . implode(',', $roll), 14);

	// Update state: store roll and transition to choosing_keepers
	$updates = [
		'last_roll' => json_encode($roll),
		'current_step' => 'choosing_keepers'
	];

	$newState = Bot_UpdateTurnState($state['gameid'], $state['playerid'], $updates);

	return [
		'step' => 'rolled',
		'dice' => $roll,
		'state' => $newState
	];
}

/**
 * Step 2: Choose which dice to keep
 *
 * Uses Bot_MakeDecision() to select scoring dice
 * If no scoring dice → transition to farkled
 * If has scoring dice → update score and transition to deciding_roll
 *
 * @param array $state Current state
 * @param array $gameData Game data
 * @param array $botPlayer Bot player record
 * @return array Updated state with keeper choice
 */
function Bot_Step_ChoosingKeepers($state, $gameData, $botPlayer) {
	BaseUtil_Debug(__FUNCTION__ . ": Choosing keepers from roll", 14);

	// Get the last roll
	$roll = json_decode($state['last_roll'], true);

	if (!$roll || !is_array($roll)) {
		BaseUtil_Error(__FUNCTION__ . ": Invalid roll data");
		return ['error' => 'Invalid roll data'];
	}

	// Get bot decision
	$decision = Bot_MakeDecision(
		$botPlayer,
		$gameData,
		$roll,
		$state['turn_score'],
		$state['dice_remaining']
	);

	BaseUtil_Debug(__FUNCTION__ . ": Decision: " . print_r($decision, true), 14);

	// Check for farkle
	if ($decision['farkled']) {
		// Use AI chat message if available, otherwise generate algorithmic message
		if (!empty($decision['chat_message'])) {
			$message = $decision['chat_message'];
		} else {
			$context = Bot_BuildMessageContext($state['gameid'], $state['playerid'], $gameData);
			$message = Bot_SelectMessage(4, $botPlayer, $context); // Category 4: Farkle
		}

		// Transition to farkled state
		$updates = [
			'current_step' => 'farkled',
			'last_message' => $message
		];

		$newState = Bot_UpdateTurnState($state['gameid'], $state['playerid'], $updates);

		return [
			'step' => 'farkled',
			'message' => $message,
			'state' => $newState
		];
	}

	// Bot chose keepers - update turn score
	$keeperChoice = $decision['keeper_choice'];
	$newTurnScore = $decision['new_turn_score'];
	$newDiceRemaining = $decision['new_dice_remaining'];

	// Use AI chat message if available, otherwise generate algorithmic message
	if (!empty($decision['chat_message'])) {
		$message = $decision['chat_message'];
	} else {
		// Build message context with keeper info
		$context = Bot_BuildMessageContext($state['gameid'], $state['playerid'], $gameData);
		$context['bot_last_keep_score'] = $keeperChoice['points'];
		$context['dice_description'] = $keeperChoice['description'];
		$context['num_dice_left'] = $newDiceRemaining;

		// Generate keeper selection message (Category 1)
		$message = Bot_SelectMessage(1, $botPlayer, $context);
	}

	// Update state
	$updates = [
		'dice_kept' => json_encode($keeperChoice['dice']),
		'turn_score' => $newTurnScore,
		'dice_remaining' => $newDiceRemaining,
		'current_step' => 'deciding_roll',
		'last_message' => $message
	];

	$newState = Bot_UpdateTurnState($state['gameid'], $state['playerid'], $updates);

	return [
		'step' => 'chose_keepers',
		'kept' => $keeperChoice,
		'turn_score' => $newTurnScore,
		'dice_remaining' => $newDiceRemaining,
		'message' => $message,
		'state' => $newState
	];
}

/**
 * Step 3: Decide whether to roll again or bank
 *
 * Uses Bot_MakeDecision() to make roll/bank decision
 * If bank → transition to banking
 * If roll → check for hot dice, transition to rolling
 *
 * @param array $state Current state
 * @param array $gameData Game data
 * @param array $botPlayer Bot player record
 * @return array Updated state with decision
 */
function Bot_Step_DecidingRoll($state, $gameData, $botPlayer) {
	BaseUtil_Debug(__FUNCTION__ . ": Deciding whether to roll again or bank", 14);

	// The decision was already made in the choosing_keepers step
	// We need to call Bot_MakeDecision again to get the should_roll decision
	// (In a real implementation, we'd cache this, but for clarity we'll recalculate)

	$roll = json_decode($state['last_roll'], true);

	// Re-calculate decision (we're at the "after choosing keepers" state now)
	// The Bot_MakeDecision already calculated should_roll for us
	$decision = Bot_MakeDecision(
		$botPlayer,
		$gameData,
		$roll,
		0, // We use 0 here because we want the decision based on the dice we just rolled
		$state['dice_remaining']
	);

	// Get the actual decision using the updated turn score
	// Since Bot_MakeDecision returns new_turn_score and new_dice_remaining,
	// we need to use those values to determine if we should roll
	$shouldRoll = $decision['should_roll'];

	BaseUtil_Debug(__FUNCTION__ . ": Should roll again? " . ($shouldRoll ? 'YES' : 'NO'), 14);

	// Build message context
	$context = Bot_BuildMessageContext($state['gameid'], $state['playerid'], $gameData);
	$context['turn_score'] = $state['turn_score'];
	$context['num_dice'] = $state['dice_remaining'];
	$context['farkle_prob'] = round(Bot_CalculateFarkleProbability($state['dice_remaining']) * 100, 1);

	if ($shouldRoll) {
		// Use AI chat message if available, otherwise generate algorithmic message
		if (!empty($decision['chat_message'])) {
			$message = $decision['chat_message'];
		} else {
			// Check for hot dice (all dice used)
			if ($state['dice_remaining'] == 6) {
				// Hot dice! Generate hot dice message (Category 5)
				$message = Bot_SelectMessage(5, $botPlayer, $context);
			} else {
				// Regular roll again decision (Category 2)
				$message = Bot_SelectMessage(2, $botPlayer, $context);
			}
		}

		// Transition back to rolling
		$updates = [
			'current_step' => 'rolling',
			'last_message' => $message
		];

		$newState = Bot_UpdateTurnState($state['gameid'], $state['playerid'], $updates);

		return [
			'step' => 'roll_again',
			'message' => $message,
			'state' => $newState
		];
	} else {
		// Use AI chat message if available, otherwise generate algorithmic message
		if (!empty($decision['chat_message'])) {
			$message = $decision['chat_message'];
		} else {
			// Banking decision (Category 3)
			$message = Bot_SelectMessage(3, $botPlayer, $context);
		}

		// Transition to banking
		$updates = [
			'current_step' => 'banking',
			'last_message' => $message
		];

		$newState = Bot_UpdateTurnState($state['gameid'], $state['playerid'], $updates);

		return [
			'step' => 'banking',
			'message' => $message,
			'state' => $newState
		];
	}
}

/**
 * Step 4: Bank the turn score
 *
 * Completes the turn successfully, adds score to total, advances game
 *
 * @param array $state Current state
 * @param array $gameData Game data
 * @param array $botPlayer Bot player record
 * @return array Final state with banked score
 */
function Bot_Step_Banking($state, $gameData, $botPlayer) {
	BaseUtil_Debug(__FUNCTION__ . ": Banking " . $state['turn_score'] . " points", 14);
	error_log("Bot_Step_Banking: Banking turn score of " . $state['turn_score'] . " for player " . $state['playerid']);

	$finalScore = $state['turn_score'];
	$dbh = db_connect();

	try {
		$dbh->beginTransaction();

		// Get current player game state
		$sql = "SELECT playerscore, playerround FROM farkle_games_players
		        WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $state['gameid'], ':playerid' => $state['playerid']]);
		$playerGame = $stmt->fetch(PDO::FETCH_ASSOC);

		$newScore = $playerGame['playerscore'] + $finalScore;
		$newRound = $playerGame['playerround'] + 1;

		// Update player score and round
		$sql = "UPDATE farkle_games_players
		        SET playerscore = :playerscore,
		            playerround = :playerround,
		            lastroundscore = :lastroundscore,
		            lastplayed = NOW()
		        WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':playerscore' => $newScore,
			':playerround' => $newRound,
			':lastroundscore' => $finalScore,
			':gameid' => $state['gameid'],
			':playerid' => $state['playerid']
		]);

		// Insert round record
		$sql = "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
		        VALUES (:playerid, :gameid, :roundnum, :roundscore, NOW())";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':playerid' => $state['playerid'],
			':gameid' => $state['gameid'],
			':roundnum' => $playerGame['playerround'],
			':roundscore' => $finalScore
		]);

		// Advance turn for bot games in interactive mode
		if ($gameData['gamemode'] == GAME_MODE_10ROUND) {
			$sql = "SELECT bot_play_mode FROM farkle_games WHERE gameid = :gameid";
			$stmt = $dbh->prepare($sql);
			$stmt->execute([':gameid' => $state['gameid']]);
			$botMode = $stmt->fetchColumn();

			if ($botMode == 'interactive') {
				// Get current turn and number of players
				$sql = "SELECT currentturn,
				        (SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid) as num_players
				        FROM farkle_games WHERE gameid = :gameid";
				$stmt = $dbh->prepare($sql);
				$stmt->execute([':gameid' => $state['gameid']]);
				$turnData = $stmt->fetch(PDO::FETCH_ASSOC);

				$nextTurn = ($turnData['currentturn'] % $turnData['num_players']) + 1;

				$sql = "UPDATE farkle_games SET currentturn = :currentturn WHERE gameid = :gameid";
				$stmt = $dbh->prepare($sql);
				$stmt->execute([':currentturn' => $nextTurn, ':gameid' => $state['gameid']]);

				error_log("Bot_Step_Banking: Advanced turn from {$turnData['currentturn']} to $nextTurn");
			}
		}

		// Clean up bot turn state
		$sql = "DELETE FROM farkle_bot_game_state WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $state['gameid'], ':playerid' => $state['playerid']]);

		$dbh->commit();
		error_log("Bot_Step_Banking: Successfully banked $finalScore points. New score: $newScore");

	} catch (Exception $e) {
		$dbh->rollBack();
		error_log("Bot_Step_Banking: ERROR - " . $e->getMessage());
		return ['error' => 'Failed to bank score: ' . $e->getMessage()];
	}

	// Check if game is complete (all players finished 10 rounds)
	// This must be done AFTER commit since GameIsCompleted does its own DB operations
	if ($gameData['gamemode'] == GAME_MODE_10ROUND) {
		require_once('farkleGameFuncs.php');

		// Get number of players
		$dbh = db_connect();
		$sql = "SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $state['gameid']]);
		$numPlayers = $stmt->fetchColumn();

		$gameFinished = GameIsCompleted($state['gameid'], $numPlayers);
		if ($gameFinished) {
			error_log("Bot_Step_Banking: Game {$state['gameid']} is now complete!");
		}
	}

	return [
		'step' => 'completed',
		'final_score' => $finalScore,
		'banked' => true
	];
}

/**
 * Step 5: Handle farkle (lost all points)
 *
 * Completes the turn with 0 points, advances game
 *
 * @param array $state Current state
 * @param array $gameData Game data
 * @param array $botPlayer Bot player record
 * @return array Final state with farkle result
 */
function Bot_Step_Farkled($state, $gameData, $botPlayer) {
	BaseUtil_Debug(__FUNCTION__ . ": Farkled - lost all points", 14);
	error_log("Bot_Step_Farkled: Bot farkled, ending turn with 0 points for player " . $state['playerid']);

	$dbh = db_connect();

	try {
		$dbh->beginTransaction();

		// Get current player game state
		$sql = "SELECT playerscore, playerround FROM farkle_games_players
		        WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $state['gameid'], ':playerid' => $state['playerid']]);
		$playerGame = $stmt->fetch(PDO::FETCH_ASSOC);

		$newRound = $playerGame['playerround'] + 1;

		// Update player round (score stays same, just advance round)
		$sql = "UPDATE farkle_games_players
		        SET playerround = :playerround,
		            lastroundscore = 0,
		            lastplayed = NOW()
		        WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':playerround' => $newRound,
			':gameid' => $state['gameid'],
			':playerid' => $state['playerid']
		]);

		// Insert round record with 0 score (farkle)
		$sql = "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
		        VALUES (:playerid, :gameid, :roundnum, 0, NOW())";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':playerid' => $state['playerid'],
			':gameid' => $state['gameid'],
			':roundnum' => $playerGame['playerround']
		]);

		// Update player farkle count
		$sql = "UPDATE farkle_players SET farkles = farkles + 1 WHERE playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':playerid' => $state['playerid']]);

		// Advance turn for bot games in interactive mode
		if ($gameData['gamemode'] == GAME_MODE_10ROUND) {
			$sql = "SELECT bot_play_mode FROM farkle_games WHERE gameid = :gameid";
			$stmt = $dbh->prepare($sql);
			$stmt->execute([':gameid' => $state['gameid']]);
			$botMode = $stmt->fetchColumn();

			if ($botMode == 'interactive') {
				// Get current turn and number of players
				$sql = "SELECT currentturn,
				        (SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid) as num_players
				        FROM farkle_games WHERE gameid = :gameid";
				$stmt = $dbh->prepare($sql);
				$stmt->execute([':gameid' => $state['gameid']]);
				$turnData = $stmt->fetch(PDO::FETCH_ASSOC);

				$nextTurn = ($turnData['currentturn'] % $turnData['num_players']) + 1;

				$sql = "UPDATE farkle_games SET currentturn = :currentturn WHERE gameid = :gameid";
				$stmt = $dbh->prepare($sql);
				$stmt->execute([':currentturn' => $nextTurn, ':gameid' => $state['gameid']]);

				error_log("Bot_Step_Farkled: Advanced turn from {$turnData['currentturn']} to $nextTurn");
			}
		}

		// Clean up bot turn state
		$sql = "DELETE FROM farkle_bot_game_state WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $state['gameid'], ':playerid' => $state['playerid']]);

		$dbh->commit();
		error_log("Bot_Step_Farkled: Successfully recorded farkle for round {$playerGame['playerround']}");

	} catch (Exception $e) {
		$dbh->rollBack();
		error_log("Bot_Step_Farkled: ERROR - " . $e->getMessage());
		return ['error' => 'Failed to record farkle: ' . $e->getMessage()];
	}

	// Check if game is complete (all players finished 10 rounds)
	// This must be done AFTER commit since GameIsCompleted does its own DB operations
	if ($gameData['gamemode'] == GAME_MODE_10ROUND) {
		require_once('farkleGameFuncs.php');

		// Get number of players
		$dbh = db_connect();
		$sql = "SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $state['gameid']]);
		$numPlayers = $stmt->fetchColumn();

		$gameFinished = GameIsCompleted($state['gameid'], $numPlayers);
		if ($gameFinished) {
			error_log("Bot_Step_Farkled: Game {$state['gameid']} is now complete!");
		}
	}

	return [
		'step' => 'completed',
		'final_score' => 0,
		'farkled' => true
	];
}

// ============================================================================
// INSTANT MODE: COMPLETE ENTIRE TURN
// ============================================================================

/**
 * Play an entire bot turn instantly (non-interactive mode)
 *
 * Loops through all steps until banking or farkled
 * Returns final state with complete turn history
 *
 * @param int $gameId Game ID
 * @param int $playerId Bot player ID
 * @return array Final turn result
 */
function Bot_PlayEntireTurn($gameId, $playerId) {
	BaseUtil_Debug(__FUNCTION__ . ": Playing entire turn instantly for game $gameId, player $playerId", 14);

	// Initialize turn state
	$state = Bot_InitializeTurnState($gameId, $playerId);

	if (!$state) {
		BaseUtil_Error(__FUNCTION__ . ": Failed to initialize turn state");
		return ['error' => 'Failed to initialize turn state'];
	}

	$maxIterations = 100; // Safety limit to prevent infinite loops
	$iteration = 0;
	$turnHistory = [];

	// Loop until turn is complete
	while ($iteration < $maxIterations) {
		$iteration++;

		// Execute next step
		$result = Bot_ExecuteStep($gameId, $playerId);

		// Add to history
		$turnHistory[] = $result;

		// Check if turn is complete
		if (isset($result['step']) && $result['step'] === 'completed') {
			BaseUtil_Debug(__FUNCTION__ . ": Turn completed after $iteration steps", 14);

			return [
				'success' => true,
				'final_score' => $result['final_score'] ?? 0,
				'farkled' => $result['farkled'] ?? false,
				'banked' => $result['banked'] ?? false,
				'iterations' => $iteration,
				'history' => $turnHistory
			];
		}

		// Check for errors
		if (isset($result['error'])) {
			BaseUtil_Error(__FUNCTION__ . ": Error during turn: " . $result['error']);
			return [
				'success' => false,
				'error' => $result['error'],
				'history' => $turnHistory
			];
		}
	}

	// Safety limit reached
	BaseUtil_Error(__FUNCTION__ . ": Turn exceeded maximum iterations ($maxIterations)");
	return [
		'success' => false,
		'error' => 'Turn exceeded maximum iterations',
		'history' => $turnHistory
	];
}

// ============================================================================
// TURN COMPLETION
// ============================================================================

/**
 * Complete a bot turn and update game database
 *
 * Adds finalScore to bot's total score, advances to next player's turn,
 * and cleans up the turn state
 *
 * @param int $gameId Game ID
 * @param int $playerId Bot player ID
 * @param int $finalScore Points scored this turn (0 if farkled)
 * @return bool Success
 */
function Bot_CompleteTurn($gameId, $playerId, $finalScore) {
	BaseUtil_Debug(__FUNCTION__ . ": Completing turn for game $gameId, player $playerId with score $finalScore", 14);

	$dbh = db_connect();

	try {
		// Begin transaction
		$dbh->beginTransaction();

		// Get current game state
		$sql = "SELECT currentround, currentturn FROM farkle_games WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $gameId]);
		$game = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$game) {
			throw new Exception("Game $gameId not found");
		}

		// Get player's game record
		$sql = "SELECT playerid, playerturn, playerround, playerscore
		        FROM farkle_games_players
		        WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $gameId, ':playerid' => $playerId]);
		$playerGame = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$playerGame) {
			throw new Exception("Player $playerId not in game $gameId");
		}

		// Update player's score and round
		$newTotalScore = $playerGame['playerscore'] + $finalScore;
		$newRound = $playerGame['playerround'] + 1;

		$sql = "UPDATE farkle_games_players
		        SET playerscore = :playerscore,
		            playerround = :playerround,
		            lastroundscore = :lastroundscore
		        WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':playerscore' => $newTotalScore,
			':playerround' => $newRound,
			':lastroundscore' => $finalScore,
			':gameid' => $gameId,
			':playerid' => $playerId
		]);

		// Advance to next player
		// Get total number of players
		$sql = "SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $gameId]);
		$numPlayers = $stmt->fetchColumn();

		$nextPlayer = ($game['currentturn'] % $numPlayers) + 1;

		// Update game's current player
		$sql = "UPDATE farkle_games SET currentturn = :currentturn WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':currentturn' => $nextPlayer, ':gameid' => $gameId]);

		// Clean up bot turn state
		$sql = "DELETE FROM farkle_bot_game_state WHERE gameid = :gameid AND playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $gameId, ':playerid' => $playerId]);

		// Commit transaction
		$dbh->commit();

		BaseUtil_Debug(__FUNCTION__ . ": Turn completed successfully. New score: $newTotalScore", 14);

		return true;

	} catch (Exception $e) {
		// Rollback on error
		$dbh->rollBack();
		BaseUtil_Error(__FUNCTION__ . ": Failed to complete turn: " . $e->getMessage());
		return false;
	}
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get game data for bot decision-making
 *
 * @param int $gameId Game ID
 * @return array|null Game data or null if not found
 */
function Bot_GetGameData($gameId) {
	$sql = "SELECT g.gameid, g.currentround, g.currentturn, g.gamemode, g.maxturns,
	               g.bot_play_mode
	        FROM farkle_games g
	        WHERE g.gameid = :gameid";

	$dbh = db_connect();
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameId]);

	$game = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$game) {
		return null;
	}

	// Get all players and scores
	$sql = "SELECT playerid, playerturn, playerround, playerscore
	        FROM farkle_games_players
	        WHERE gameid = :gameid
	        ORDER BY playerturn";

	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameId]);
	$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$game['players'] = $players;

	// Calculate scores for bot decision context
	if (count($players) >= 2) {
		// Assume first player is bot, second is opponent (simplified)
		$game['bot_score'] = $players[0]['playerscore'] ?? 0;
		$game['opponent_score'] = $players[1]['playerscore'] ?? 0;
	}

	$game['total_rounds'] = 10; // Standard 10-round game

	return $game;
}

/**
 * Get bot player record
 *
 * @param int $playerId Player ID
 * @return array|null Bot player record or null if not found
 */
function Bot_GetBotPlayer($playerId) {
	$sql = "SELECT playerid, username, bot_algorithm, playerlevel, personality_id
	        FROM farkle_players
	        WHERE playerid = :playerid AND is_bot = TRUE";

	$dbh = db_connect();
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':playerid' => $playerId]);

	return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Build message context for Bot_SelectMessage
 *
 * Gathers game state information for message variable substitution
 *
 * @param int $gameId Game ID
 * @param int $botPlayerId Bot player ID
 * @param array $gameData Game data (optional, will fetch if not provided)
 * @return array Context variables for message formatting
 */
function Bot_BuildMessageContext($gameId, $botPlayerId, $gameData = null) {
	// Get game data if not provided
	if (!$gameData) {
		$gameData = Bot_GetGameData($gameId);
	}

	// Get bot player info
	$botPlayer = Bot_GetBotPlayer($botPlayerId);

	// Find bot and opponent scores
	$botScore = 0;
	$opponentScore = 0;
	$opponentUsername = 'Player';
	$opponentLevel = 0;

	if (isset($gameData['players']) && is_array($gameData['players'])) {
		foreach ($gameData['players'] as $player) {
			if ($player['playerid'] == $botPlayerId) {
				$botScore = $player['playerscore'];
			} else {
				$opponentScore = $player['playerscore'];

				// Get opponent details
				$sql = "SELECT username, playerlevel FROM farkle_players WHERE playerid = :playerid";
				$dbh = db_connect();
				$stmt = $dbh->prepare($sql);
				$stmt->execute([':playerid' => $player['playerid']]);
				$opponent = $stmt->fetch(PDO::FETCH_ASSOC);

				if ($opponent) {
					$opponentUsername = $opponent['username'];
					$opponentLevel = $opponent['playerlevel'] ?? 0;
				}
			}
		}
	}

	// Build context
	$context = [
		'player_username' => $opponentUsername,
		'player_level' => $opponentLevel,
		'player_score' => $opponentScore,
		'bot_username' => $botPlayer['username'] ?? 'Bot',
		'bot_level' => $botPlayer['playerlevel'] ?? 0,
		'bot_score' => $botScore,
		'round' => $gameData['currentround'] ?? 1,
		'lead' => $botScore - $opponentScore
	];

	return $context;
}

?>
