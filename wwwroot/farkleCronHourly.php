#!/usr/bin/php
<?php

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farkleGameFuncs.php');
	require_once('farklePageFuncs.php');
	require_once('farkleTournament.php');
	require_once('farkleUtil.php');
	
	// FUNCTIONS
	
	function NotifyOfUnplayedGames()
	{
		$sql = "select distinct b.playerid as playerid from farkle_games a, farkle_games_players b, farkle_players_devices c
		where a.gameid=b.gameid and a.winningplayer=0 and b.playerid=c.playerid and c.token is not null";
		$players = db_select_query( $sql, SQL_MULTI_ROW );
		
		//foreach( $players as $p )
		//{
			SendPushNotification( GetPlayerListCommaString( $players ) , 'You have Farkle games waiting to be played.', 'newGameTone.aif' ); 
		//}
	}
	
	function EmailMyGames()
	{
		// Get all games for all players that have not been played yet.
		$sql = "select b.playerid, COALESCE(c.fullname, c.username) as username, c.email, a.gameid, a.playerstring,
			TO_CHAR(a.gamestart, 'Mon DD @ HH12:00 AM') as gamestart
			from farkle_games a, farkle_games_players b, farkle_players c
			where a.gameid=b.gameid and c.playerid=b.playerid and a.winningplayer=0 and a.whostarted != c.playerid
			and b.playerround=1 and b.notified=0 and c.sendhourlyemails=1 and c.email is not null and char_length(c.email) >= 5
			order by b.playerid, a.gamestart";
		$games = db_select_query( $sql, SQL_MULTI_ROW );
		if( $games ) $lastPlayerid = $games[0]['playerid']; 
		$msg = "";
		
		for($i=0; $i<count($games); $i++)
		{
			$curGame = $games[$i];
		
			$playerstring = $curGame['playerstring'];
			if ( strpos($playerstring, " vs. ") ) 
			{ // note: three equal signs
				$strSplit = explode(' vs. ', $playerstring);
				$playerstring = 'vs. ' . (strcmp($strSplit[0], $curGame['username']) == 0 ? $strSplit[1] : $strSplit[0] ); 
			}
			
			if( $curGame['playerid'] == $lastPlayerid )
			{
				$msg .= "Game $playerstring - <a href=\"http://www.farkledice.com/wwwroot/farkle.php?resumegameid={$curGame['gameid']}\">[ Play Now! ]</a><br><br>";
			}
			else
			{
				// We're done. Fire off email.
				$lastGame = $games[$i-1];

				if( empty($msg) )
					$msg .= "Game {$lastGame['gameid']}: $playerstring - <a href=\"http://www.farkledice.com/wwwroot/farkle.php?resumegameid={$lastGame['gameid']}\">[ Play Now! ]</a><br>";
				
				$header = "{$lastGame['username']},<br><br> You have new Farkle games waiting to be played! You can use the links below to hop right back into the action.<br><br>";
				$footer = "<br>You are receiving this email because you enabled hourly updates from " . GAME_TITLE . " player settings. You can disable this at any time.";
				
				SendEmail( $lastGame['email'], "New Farkle games to play!", $header . $msg . $footer );
				 
				$msg = "";
			}
			$lastPlayerid = $curGame['playerid'];
		}
		
		if( !empty($msg) )
		{
			$lastGame = $games[count($games)-1];
			$header = "{$lastGame['username']},<br><br> You have new Farkle games waiting to be played! You can use the links below to hop right back into the action.<br><br>";
			$footer = "<br>You are recieving this email because you enabled hourly updates from " . GAME_TITLE . " player settings. You can disable this at any time.";
			
			SendEmail( $lastGame['email'], "New Farkle games to play!", $header . $msg . $footer );
			
			
		}
		
		// Clear any new games from further emails. 
		$sql = "update farkle_games_players set notified=1 where notified=0";
		$result = db_command($sql);
	}
	
	// For testing, this will only email Mike for now...TBD: remove this. 
	//$gEmailEnabled = false;
	EmailMyGames();
	//$gEmailEnabled = true; 
	
	if( date('H') == 10 ) NotifyOfUnplayedGames();
	
	// START CRON JOB CODE
	echo "Started - " . date('h:i:s A') . "<br><br>\n\r\n\r";
	$_SESSION['playerid'] = 0;
	//$gEmailEnabled = false;
	
	//$gEmailEnabled = true;
	CheckTournaments( );
	//$gEmailEnabled = false;
	
	$sql = "select count(*) from farkle_games a where winningplayer=0 and (select count(*) from farkle_games_players where gameid=a.gameid and currentround=11) >= a.maxturns";
	$badGameCount = db_select_query( $sql, SQL_SINGLE_VALUE );

	if( $badGameCount > 0 )
		SendEmail( 'mikeschmoyer@gmail.com', "Farkle Server Issue - Bad Games", "Farkle Ten has detected games that are not finished but have no players with rounds left to player." );
	
	exit(0);
?>