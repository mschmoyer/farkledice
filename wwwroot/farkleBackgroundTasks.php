<?php
/*
	farkleBackgroundTasks.php

	Background maintenance tasks that run periodically via the fetch route.
	This replaces the need for cron jobs by running tasks when players are active.

	Each task has its own throttle to prevent performance issues.
*/

require_once('farkleLeaderboard.php');

/**
 * Main background maintenance function
 * Called on every fetch request but throttled to only run when needed
 */
function BackgroundMaintenance()
{
	// Only run maintenance for logged-in users to reduce load
	if (!isset($_SESSION['playerid'])) {
		return;
	}

	// Task 1: Refresh leaderboard data (every 5 minutes)
	BackgroundTask_RefreshLeaderboards();

	// Task 2: Update daily leaderboard stats (every 5 minutes)
	BackgroundTask_RefreshDailyLeaderboards();

	// Task 3: Cleanup stale games (every 30 minutes)
	BackgroundTask_CleanupStaleGames();

	// Task 4: Auto-fill random games with bots (60 seconds dev / 24 hours prod)
	BackgroundTask_BotAutoFill();
}

/**
 * Refresh main leaderboard data (wins, highest round, achievements)
 * Throttled to run at most once every 5 minutes
 */
function BackgroundTask_RefreshLeaderboards()
{
	// Check timestamp in siteinfo table (paramid=1)
	$sql = "SELECT (paramvalue::numeric <= EXTRACT(EPOCH FROM NOW())) FROM siteinfo WHERE paramid=1";
	$shouldRun = db_select_query($sql, SQL_SINGLE_VALUE);

	if ($shouldRun) {
		BaseUtil_Debug("BackgroundMaintenance: Refreshing main leaderboards", 1);
		Leaderboard_RefreshData(true);
	}
}

/**
 * Refresh daily leaderboard stats (today's top scores, wins, farkles)
 * Throttled to run at most once every 5 minutes
 */
function BackgroundTask_RefreshDailyLeaderboards()
{
	// Check timestamp in siteinfo table (paramid=2)
	$sql = "SELECT (paramvalue::numeric <= EXTRACT(EPOCH FROM NOW())) FROM siteinfo WHERE paramid=2";
	$shouldRun = db_select_query($sql, SQL_SINGLE_VALUE);

	if ($shouldRun) {
		// Set next run to 5 minutes from now BEFORE doing work (prevents race condition)
		$sql = "UPDATE siteinfo SET paramvalue=EXTRACT(EPOCH FROM (NOW() + interval '5' minute))
				WHERE paramid=2 AND paramname='last_daily_leaderboard_refresh'";
		db_command($sql);

		BaseUtil_Debug("BackgroundMaintenance: Refreshing daily leaderboards", 1);
		Leaderboard_RefreshDaily();
	}
}

/**
 * Cleanup and finish stale games
 * Throttled to run at most once every 30 minutes
 */
function BackgroundTask_CleanupStaleGames()
{
	// Check timestamp in siteinfo table (paramid=4)
	$sql = "SELECT (paramvalue::numeric <= EXTRACT(EPOCH FROM NOW())) FROM siteinfo WHERE paramid=4";
	$shouldRun = db_select_query($sql, SQL_SINGLE_VALUE);

	if ($shouldRun) {
		// Set next run to 30 minutes from now BEFORE doing work (prevents race condition)
		$sql = "UPDATE siteinfo SET paramvalue=EXTRACT(EPOCH FROM (NOW() + interval '30' minute))
				WHERE paramid=4 AND paramname='last_cleanup'";
		db_command($sql);

		BaseUtil_Debug("BackgroundMaintenance: Cleaning up stale games", 1);

		// Require the function if not already loaded
		if (!function_exists('FinishStaleGames')) {
			require_once('farklePageFuncs.php');
		}

		FinishStaleGames(0);
	}
}

/**
 * Auto-fill random games with bots when they've been waiting too long
 * Throttled to run at most once every 60 seconds (dev) or 24 hours (production)
 */
function BackgroundTask_BotAutoFill()
{
	// Determine timeout based on environment
	$isTestServer = isset($_SESSION['testserver']) && $_SESSION['testserver'];
	$checkIntervalSeconds = $isTestServer ? 60 : 86400; // 1 min dev, 24 hrs prod

	// Check timestamp in siteinfo table (paramid=7)
	$sql = "SELECT (paramvalue::numeric <= EXTRACT(EPOCH FROM NOW())) FROM siteinfo WHERE paramid=7";
	$shouldRun = db_select_query($sql, SQL_SINGLE_VALUE);

	if ($shouldRun) {
		// Set next run based on environment BEFORE doing work (prevents race condition)
		$sql = "UPDATE siteinfo SET paramvalue=EXTRACT(EPOCH FROM (NOW() + interval '{$checkIntervalSeconds}' second))
				WHERE paramid=7 AND paramname='last_bot_fill_check'";
		db_command($sql);

		BaseUtil_Debug("BackgroundMaintenance: Checking for games to auto-fill with bots (interval: {$checkIntervalSeconds}s)", 1);

		// Find random games waiting for players
		Bot_FillWaitingRandomGames($checkIntervalSeconds);
	}
}

/**
 * Find random games waiting for players and fill them with bots
 *
 * @param int $waitTimeoutSeconds How long a game must wait before bot auto-fill
 */
function Bot_FillWaitingRandomGames($waitTimeoutSeconds)
{
	$dbh = db_connect();

	// Find random games that:
	// 1. Are missing players (player_count < maxturns)
	// 2. Have been waiting longer than timeout
	// 3. Don't already have a bot
	// 4. Are still active (winningplayer = 0)
	$sql = "SELECT g.gameid, g.maxturns, COUNT(gp.playerid) as actualplayers, g.gamestart
			FROM farkle_games g
			LEFT JOIN farkle_games_players gp ON g.gameid = gp.gameid
			WHERE g.gamewith = 0
			  AND g.winningplayer = 0
			  AND g.gamestart < NOW() - interval '{$waitTimeoutSeconds}' second
			  AND NOT EXISTS (
				  SELECT 1 FROM farkle_games_players gp2
				  INNER JOIN farkle_players p ON gp2.playerid = p.playerid
				  WHERE gp2.gameid = g.gameid AND p.is_bot = TRUE
			  )
			GROUP BY g.gameid, g.maxturns, g.gamestart
			HAVING COUNT(gp.playerid) < g.maxturns
			ORDER BY g.gamestart ASC
			LIMIT 10";

	$stmt = $dbh->prepare($sql);
	$stmt->execute();
	$waitingGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if (!$waitingGames || count($waitingGames) == 0) {
		BaseUtil_Debug("Bot_FillWaitingRandomGames: No games waiting for auto-fill", 14);
		return;
	}

	BaseUtil_Debug("Bot_FillWaitingRandomGames: Found " . count($waitingGames) . " games to fill", 14);

	// Fill each game with a bot
	foreach ($waitingGames as $game) {
		$result = Bot_FillRandomGame($game['gameid'], $game['maxturns'], $game['actualplayers']);

		if ($result['success']) {
			BaseUtil_Debug("Bot_FillWaitingRandomGames: Filled game {$game['gameid']} with bot {$result['bot_username']}", 1);
		} else {
			BaseUtil_Error("Bot_FillWaitingRandomGames: Failed to fill game {$game['gameid']}: " . ($result['error'] ?? 'unknown error'));
		}
	}
}

/**
 * Add a bot to a random game
 *
 * @param int $gameId Game ID to fill
 * @param int $maxPlayers Maximum players in the game
 * @param int $currentPlayers Current number of players
 * @return array Result with success status and bot info
 */
function Bot_FillRandomGame($gameId, $maxPlayers, $currentPlayers)
{
	$dbh = db_connect();

	// Select a random available bot (not already in game, not random_selectable to avoid real random matching)
	$sql = "SELECT p.playerid, p.username, p.bot_algorithm
			FROM farkle_players p
			WHERE p.is_bot = TRUE
			  AND p.active = 1
			  AND NOT EXISTS (
				  SELECT 1 FROM farkle_games_players gp
				  WHERE gp.gameid = :gameid AND gp.playerid = p.playerid
			  )
			ORDER BY RANDOM()
			LIMIT 1";

	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameId]);
	$bot = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$bot) {
		return ['success' => false, 'error' => 'No available bots found'];
	}

	BaseUtil_Debug("Bot_FillRandomGame: Selected bot {$bot['username']} (ID {$bot['playerid']}) for game $gameId", 14);

	try {
		// Begin transaction
		$dbh->beginTransaction();

		// Get highest player turn
		$sql = "SELECT COALESCE(MAX(playerturn), 0) FROM farkle_games_players WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $gameId]);
		$highestTurn = $stmt->fetchColumn();

		// Add bot to game
		$sql = "INSERT INTO farkle_games_players
				(gameid, playerid, playerturn, lastplayed, playerround, totalscore, lastscore)
				VALUES (:gameid, :playerid, :playerturn, NOW(), 1, 0, 0)";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':gameid' => $gameId,
			':playerid' => $bot['playerid'],
			':playerturn' => $highestTurn + 1
		]);

		// Update game's actualplayers count
		$newPlayerCount = $currentPlayers + 1;
		$sql = "UPDATE farkle_games SET actualplayers = :actualplayers WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':actualplayers' => $newPlayerCount,
			':gameid' => $gameId
		]);

		// Update playerstring for game display
		if (!function_exists('GetFarkleGameName')) {
			require_once('farkleGameFuncs.php');
		}
		if (!function_exists('GetGamePlayerids')) {
			require_once('farkleGameFuncs.php');
		}

		$playerIds = GetGamePlayerids($gameId);
		$playerString = GetFarkleGameName($gameId, 0, $playerIds, $maxPlayers);

		$sql = "UPDATE farkle_games SET playerstring = :playerstring WHERE gameid = :gameid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':playerstring' => $playerString,
			':gameid' => $gameId
		]);

		// If game is now full, set bot_play_mode to instant for this game
		if ($newPlayerCount >= $maxPlayers) {
			BaseUtil_Debug("Bot_FillRandomGame: Game $gameId is now full, setting bot_play_mode to instant", 14);

			$sql = "UPDATE farkle_games SET bot_play_mode = 'instant' WHERE gameid = :gameid";
			$stmt = $dbh->prepare($sql);
			$stmt->execute([':gameid' => $gameId]);

			// Play all bot turns instantly
			if (!function_exists('Bot_PlayEntireTurn')) {
				require_once('farkleBotTurn.php');
			}

			// Play all 10 rounds for the bot
			for ($round = 1; $round <= 10; $round++) {
				$turnResult = Bot_PlayEntireTurn($gameId, $bot['playerid']);

				if (!$turnResult['success']) {
					throw new Exception("Failed to play bot turn for round $round: " . ($turnResult['error'] ?? 'unknown error'));
				}

				BaseUtil_Debug("Bot_FillRandomGame: Bot {$bot['username']} completed round $round with score " . ($turnResult['final_score'] ?? 0), 14);
			}
		}

		// Commit transaction
		$dbh->commit();

		return [
			'success' => true,
			'bot_id' => $bot['playerid'],
			'bot_username' => $bot['username'],
			'game_filled' => ($newPlayerCount >= $maxPlayers)
		];

	} catch (Exception $e) {
		// Rollback on error
		$dbh->rollBack();
		BaseUtil_Error("Bot_FillRandomGame: Transaction failed: " . $e->getMessage());
		return ['success' => false, 'error' => $e->getMessage()];
	}
}

?>
