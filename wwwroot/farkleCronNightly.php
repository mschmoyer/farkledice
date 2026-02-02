#!/usr/bin/php
<?php

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farklePageFuncs.php');
	require_once('farkleLeaderboard.php');
	require_once('farkleLevel.php'); 
	
	// START CRON JOB CODE
	echo "Started - " . date('h:i:s A') . "\n\r";
	$alert = ""; 
	$_SESSION['playerid'] = 0;
	//$gEmailEnabled = false;
	
	// refresh the daily leaderboard charts. 
	Leaderboard_RefreshDaily();

	FinishStaleGames( 0 );
	
	CleanupTables( );
	
	$active = GetActiveTournaments();
	if( isset( $active['tournamentid'] ) && $active['tournamentid'] > 0 ) {
		echo "Nightly tournament check -- tournament already started. Not starting another one.\n\r"; 
	}
	else
	{
		// On the 14th day of the month, we will create a farkle tournament. 
		echo "Nightly tournament check -- No active tournament. Starting a new one.\n\r"; 
		CreateMonthlyTournament(); 
		$alert .= "Created monthly tournament."; 
	}
	
	/*$dayOfMonth = date('j'); 
	if( $dayOfMonth == 24 || $dayOfMonth == 25 || $dayOfMonth == 26 || $dayOfMonth == 12 || $dayOfMonth == 13 || $dayOfMonth == 14)
	{		
		// Random chance to be a "double-xp" day. 
		echo "Starting a double-XP day...\n\r"; 
		$sql = "update siteinfo set paramvalue='1' where paramid=".SITEINFO_DOUBLE_XP;
		$result = db_command($sql);
	}
	else
	{
		// Turn off double-xp day. 
		echo "Turning off a double-XP day...\n\r"; 
		$sql = "update siteinfo set paramvalue='0' where paramid=".SITEINFO_DOUBLE_XP;
		$result = db_command($sql);
	}*/
	
	$games = db_query("SELECT count(*) FROM farkle_games WHERE gamestart > NOW() - interval '24 hours'", [], SQL_SINGLE_VALUE);
	echo "Today's game count: $games\n\r";
	$alert .= "$games games today.";

	$newPlayers = db_query("SELECT count(*) FROM farkle_players WHERE createdate > NOW() - interval '24 hours'", [], SQL_SINGLE_VALUE);
	echo "Today's new player count: $newPlayers\n\r";
	$alert .= "$newPlayers new players.";

	$activePlayers = db_query("SELECT count(*) FROM farkle_players WHERE lastplayed > NOW() - interval '15 days'", [], SQL_SINGLE_VALUE);
	echo "Active Players (last 15 days): $activePlayers\n\r";

	$activeGames = db_query("SELECT count(*) FROM farkle_games WHERE winningplayer = 0", [], SQL_SINGLE_VALUE);
	echo "Active Games: $activeGames"; 
	
	SendPushNotification( 1, $alert, $sound="newGameTone.aif" );
	
	exit(0);
?>