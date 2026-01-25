<?php

require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleGameFuncs.php');
//require_once('farklePageFuncs.php');
//require_once('farkleTournament.php');

$g_StaleMsgContent = "";

if( isset($_GET['test']) )
{
	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farkleLogin.php');
	require_once('farkleAchievements.php');
	//$g_debug = 14;
	
	if( $_GET['test'] == 'fixnames' )
	{
		$g_debug=14;
		//FinishStaleGames( $test=1 );
		require_once('farkleGameFuncs.php'); 
		require_once('farkleGameFuncs.php'); 
		
		$sql = "Select gameid, gamewith, maxturns from farkle_games where gamestart > NOW() - interval '20 hours' LIMIT 2000 OFFSET 1";
		$allGames = db_select_query( $sql, SQL_MULTI_ROW );
		foreach( $allGames as $g )
		{
			$newName = GetFarkleGameName( $g['gameid'], $g['gamewith'], GetGamePlayerids($g['gameid']), $g['maxturns'] );
			$sql = "update farkle_games set playerstring='$newName' where gameid={$g['gameid']}";
			db_command($sql);
		}
		
	}
}

function random_string( $numChars, $numNums )
{
	$character_set_array = array( );
	$character_set_array[ ] = array( 'count' => $numChars, 'characters' => 'abcdefghijklmnopqrstuvwxyz' );
	$character_set_array[ ] = array( 'count' => $numNums, 'characters' => '0123456789' );
	$temp_array = array( );
	foreach ( $character_set_array as $character_set )
	{
		for ( $i = 0; $i < $character_set[ 'count' ]; $i++ )
		{
			$temp_array[ ] = $character_set[ 'characters' ][ rand( 0, strlen( $character_set[ 'characters' ] ) - 1 ) ];
		}
	}
	shuffle( $temp_array );
	return implode( '', $temp_array );
}

function HasBadWords( $str )
{
	$badWords = Array('anal','anus','arse','ass','ballsack','balls','bastard','bitch','biatch','bloody','blowjob','blow job','bollock','bollok','boner','boob','bugger','bum','butt','buttplug','clitoris','cock','coon','crap','cunt','damn','dick','dildo','dyke','fag','feck','fellate','fellatio','felching','fuck','f u c k','fudgepacker','fudge packer','flange','Goddamn','God damn','hell','homo','jerk','jizz','knobend','knob end','labia','lmao','lmfao','muff','nigger','nigga','omg','penis','piss','poop','prick','pube','pussy','queer','scrotum','sex','shit','s hit','sh1t','slut','smegma','spunk','tit','tosser','turd','twat','vagina','wank','whore','wtf');
	
	for($i=0;$i<count($badWords);$i++)
	{
		if( strpos( $str, $badWords[$i] ) === false )
		{
			// Word ok
		}
		else
		{
			BaseUtil_Debug( "That string had a bad word in it!", 7 );
			return true;				
		}
	}
	return false;
}

function AppendEmail( $msg)
{
	global $g_StaleMsgContent; 
	$g_StaleMsgContent .= $msg . '\n<br/>'; 
}

// This function will artificially finish a game. Win conditions determined by mode. 
function FinishStaleGames( $test=0 )
{
	global $g_StaleMsgContent; 
	$g_StaleMsgContent = "";
	$gamesFinished = 0; 
	
	BaseUtil_Debug( __FUNCTION__ . ": entered. Test Mode? $test", 14 );
	
	//$sql = "select gameid, gamemode from farkle_games where winningplayer=0 and gamestart < NOW()-interval '1' month";
	
	$sql = "select gameid, gamemode, maxturns, whostarted, gamewith, gamestart,
		(select max(playerround) from farkle_games_players where gameid=a.gameid ) as maxround,
		(select min(playerround) from farkle_games_players where gameid=a.gameid ) as minround,
		(select count(*) from farkle_games_players where gameid=a.gameid ) as numplayers
		from farkle_games a
		where gameexpire < NOW() and winningplayer=0
		LIMIT 400";
	$oldGames = db_select_query( $sql, SQL_MULTI_ROW );
	
	// Don't send emails if it looks like we're about to spam. Some kind of pent-up mass update. 
	$sendEmail = 1;
	if( count($oldGames) > 5 ) $sendEmail = 0;
	
	// Loop through each expired game with no winner
	foreach( $oldGames as $o )
	{
		$gameid = $o['gameid'];
		$modeStr = ( $o['gamemode'] == '1' ? 'Standard' : '10-Round' ); 
		$withStr = ( $o['gamewith'] == '0' ? 'Random' : ( $o['gamewith'] == '1' ? 'Friends' : ( $o['gamewith'] == '2' ? 'Solo' : '--' ) ) );
		
		$g_StaleMsgContent .= '\n';
		AppendEmail( __FUNCTION__ . ": Game #$gameid - started {$o['gamestart']}. Mode= '$modeStr', With= '$withStr', WhoStarted={$o['whostarted']}, minRound={$o['minround']}, maxRound={$o['maxround']}, MaxTurn: {$o['maxturns']}, Actual Players: {$o['numplayers']}" );
			
		// Reasons to delete a game: 
		// 1. Solo game
		// 2. Nobody has played
		// 3. It's a unfinished standard game. 
		// 4; Unknown gameWith (before it was implemented) 

		if( $o['gamemode'] == GAME_MODE_10ROUND && ($o['gamewith'] == GAME_WITH_RANDOM || $o['gamewith'] == GAME_WITH_FRIENDS) )
		{
			if( $o['gamewith'] == GAME_WITH_RANDOM && $o['minround'] == 1 && $o['numplayers'] > 1 )
			{
				// A random game with somebody who has not played. Let's knock them out of the game and let it find another player. 
				AppendEmail(  __FUNCTION__ . ": Knocking players out of random game $gameid because they did not play." );
				
				if( !$test )
				{
					$sql = "delete from farkle_games_players where gameid=$gameid and playerround=1";
					$result = db_command($sql);
					continue; // FOR LOOP NEXT ROW
				}
			}
			
			if( $o['minround'] > 1 )
			{			
				// All players have played at least one round. Highest score wins. 
				
				$sql = "select playerid, max(playerscore) 
					from farkle_games_players 
					where gameid=$gameid 
					group by playerid
					order by 2 desc";
				$winner = db_select_query( $sql, SQL_SINGLE_ROW );
				
				AppendEmail( __FUNCTION__ . ": Giving winner to high score player {$winner['playerid']}" );
				$gamesFinished++; 
				
				if( !$test ) FarkleWinGame( $gameid, $winner['playerid'], "Game time expired.", $sendEmail, 0 );
				continue; // FOR LOOP NEXT ROW
			}
		}
		
		// Somebody has not played in this game at all and it was with friends. We will just delete it. 
		AppendEmail( __FUNCTION__ . ": deleting game." );
			
		if( !$test )
		{
			// Everybody loses -- game simply dissapears. 	
			$sql = "delete from farkle_sets where gameid=$gameid";
			$result = db_command($sql);
			$sql = "delete from farkle_rounds where gameid=$gameid";
			$result = db_command($sql);
			$sql = "delete from farkle_games_players where gameid=$gameid";
			$result = db_command($sql);
			$sql = "delete from farkle_games where gameid=$gameid";
			$result = db_command($sql);
			
			$gamesFinished++; 
		}

	}
	
	if( strlen($g_StaleMsgContent) > 0 )
	{
		//SendEmail( 'mikeschmoyer@gmail.com', 'Farkle Ten - Stale Games Report', $g_StaleMsgContent );
		 
		if( $test )
		{
			$debug = str_replace( '\n', '<br>', $g_StaleMsgContent ); 
			echo $debug; 
		}
		echo __FUNCTION__ . ": Finished $gamesFinished games.\n"; 
	}
}

// Cleanup stale farkle_set data
function CleanupTables()
{
	BaseUtil_Debug( __FUNCTION__ . ": Cleaning up farkle sets for completed games.", 7 );

	$sql = "delete from farkle_sets where gameid in (select gameid from farkle_games where winningplayer > 0)";
	$result = db_command($sql);

	// Cleanup stale farkle_round data
	$sql = "delete from farkle_rounds where gameid in
		(select gameid from farkle_games where winningplayer > 0 and gamefinish < NOW()-interval '31' day)";
	$result = db_command($sql);

	// Cleanup expired sessions (older than 30 days)
	BaseUtil_Debug( __FUNCTION__ . ": Cleaning up expired sessions.", 7 );
	$sql = "DELETE FROM farkle_sessions WHERE last_access < NOW() - INTERVAL '30 days'";
	$result = db_command($sql);

	// Cleanup stale device records (not used in 90 days)
	BaseUtil_Debug( __FUNCTION__ . ": Cleaning up stale device records.", 7 );
	$sql = "DELETE FROM farkle_players_devices WHERE lastused < NOW() - INTERVAL '90 days'";
	$result = db_command($sql);
}

?>