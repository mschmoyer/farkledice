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
		BaseUtil_Debug("BackgroundMaintenance: Refreshing daily leaderboards", 1);
		Leaderboard_RefreshDaily();

		// Set next run to 5 minutes from now
		$sql = "UPDATE siteinfo SET paramvalue=EXTRACT(EPOCH FROM (NOW() + interval '5' minute))
				WHERE paramid=2 AND paramname='last_daily_leaderboard_refresh'";
		db_command($sql);
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
		BaseUtil_Debug("BackgroundMaintenance: Cleaning up stale games", 1);

		// Require the function if not already loaded
		if (!function_exists('FinishStaleGames')) {
			require_once('farklePageFuncs.php');
		}

		FinishStaleGames(0);

		// Set next run to 30 minutes from now
		$sql = "UPDATE siteinfo SET paramvalue=EXTRACT(EPOCH FROM (NOW() + interval '30' minute))
				WHERE paramid=4 AND paramname='last_cleanup'";
		db_command($sql);
	}
}

?>
