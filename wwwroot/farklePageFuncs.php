<?php
/*
	farklePageFuncs.php
	
	Functions related to the various operations on each page (not game logic). 
*/

require_once('../includes/baseutil.php');
require_once('dbutil.php');
//require_once("facebook.php");
require_once('farkleAchievements.php');
require_once('farkleLevel.php');
require_once('farkleUtil.php'); 

define( 'GAME_TITLE', 'Farkle Ten' );

define( 'FARKLE_MAJOR_VERSION', '2' );
define( 'FARKLE_MINOR_VERSION', '0' );
define( 'FARKLE_REV_VERSION', 	'0' );

$gEmailEnabled = 1;

$gTitles = array( 
				0=>'',
				3=>'the Prospect',
				6=>'the Joker',
				9=>'the Princess',
				12=>'the Scary Clown',
				15=>'the Farkled',
				18=>'the Average Joe',
				21=>'the Wicked',
				24=>'the Sexy Lady',
				27=>'the Gamer',
				30=>'the Notorious',
				33=>'the Lucky Dog',
				36=>'the Veteran',
				39=>'the Samsquash',
				42=>'the Dola',
				45=>'the Star',
				48=>'the Professional',
				51=>'the Stud',
				54=>'the Dice Master',
				57=>'the Chosen One',
				60=>'the King of Farkle', 
				100=>'the Centurion');		
				
function SendEmail( $addr, $v_subj, $v_msg )
{
	global $gEmailEnabled;
	$rc = 0;
	
	$subj = GAME_TITLE . " - " . $v_subj; // Add the product before the subject
	
	// Add the global footer. 
	$msg = $v_msg . "\r\n\r\nPlay " . GAME_TITLE . " at www.farkledice.com";

	$headers = 'From: admin@farkledice.com' . "\r\n";
	$headers .= 'Reply-To: admin@farkledice.com' . "\r\n";
	$headers .= 'X-Mailer: PHP/' . phpversion();
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	
	if( !empty($gEmailEnabled) )
	{
		$rc = mail( $addr, $subj, $msg, $headers );
		BaseUtil_Debug( "SendEmail [ENABLED]: Emailing " . $addr . ". Subj=[$subj] Msg=[" . strip_tags($msg) . "]", 2 );
		error_log( __FUNCTION__ . " - Emailing $addr:  Subj=$subj, Msg Snip=" . substr( $msg, 0, 50 ) );
	}
	else
	{
		BaseUtil_Debug( "SendEmail [DISABLED]: Emailing " . $addr . ". Subj=[$subj] Msg=[" . strip_tags($msg) . "]", 2 );
		error_log( __FUNCTION__ . " - Skipping emailing $addr:  Subj=$subj, Msg Snip=" . substr( $msg, 0, 50 ) );
	}
	return $rc;
}

function GetStats( $playerid, $recordInSession = 1 )
{
	if( !isset($playerid) || empty($playerid) ) 
	{
		error_log( __FUNCTION__ . ": Error - missing or invalid patronid." );
		return 0; 
	}
	if( !isset($_SESSION['playerid']) )
	{
		error_log( __FUNCTION__ . ": No player logged in." );
		return 0; 
	}
	
	if( $recordInSession )
	{
		$_SESSION['farkle']['lastknownscreen'] = 'playerinfo';
		$_SESSION['farkle']['lastplayerinfoid'] = $playerid;
	}
	$usernameSql = "select IFNULL(fullname, username) as username from farkle_players where playerid='$playerid'";
	$friendSql = "select 1 from farkle_friends where sourceid='" . $_SESSION['playerid'] . "' and friendid=$playerid";
	
	// Total points
	$sql = "select 
		IFNULL(fullname, username) as username, email, sendhourlyemails, random_selectable, playerid, playertitle, cardcolor,
		(select sum(worth) 
			from farkle_achievements a, farkle_achievements_players b 
			where a.achievementid=b.achievementid and b.playerid='$playerid') as achscore,
		FORMAT(totalpoints,0) as totalpoints, 
		FORMAT(highestround,0) as highestround,
		DATE_FORMAT(lastplayed,'%b %D') as lastplayed,
		FORMAT(COALESCE(avgscorepoints / roundsplayed,0),0) as avground,
		wins, 
		losses,		
		IFNULL(($friendSql),0) as isfriend,
		FORMAT(xp,0) as xp, 
		FORMAT(xp_to_level,0) as xp_to_level,
		FORMAT(stylepoints,0) as stylepoints, 
		playerlevel,
		FORMAT(highest10round,0) as highest10round, 
		FORMAT(farkles,0) as farkles
		from farkle_players where playerid='$playerid'";
	$stats = db_select_query( $sql, SQL_SINGLE_ROW );
	 
	if( empty($stats['avground']) ) $stats['avground'] = '0';

	return Array( 	$stats, 
					GetGames( $playerid, 1, 30, 1), 
					Player_GetTitleChoices( $stats['playerlevel'] ) );
}

function GetPlayerInfo( $playerid )
{
	$sql = "select IFNULL(fullname, username) as username, playertitle, cardcolor, cardbg, 
			facebookid,	playerlevel, xp, xp_to_level,
			(select sum(worth) 
				from farkle_achievements a, farkle_achievements_players b 
				where a.achievementid=b.achievementid and b.playerid=$playerid) as achscore			
		from farkle_players 
		where playerid=$playerid";
	$playerInfo = db_select_query( $sql, SQL_SINGLE_ROW );
	
	return $playerInfo;
}

function GetLobbyInfo( )
{
	if( !isset($_SESSION['playerid']) || empty($_SESSION['playerid']) ) 
	{
		error_log( __FUNCTION__ . ": Error - missing or invalid patronid." );
		return 0; 
	}
	
	$_SESSION['farkle']['lastknownscreen'] = 'lobby';
	
	$playerid = $_SESSION['playerid'];
	
	if( date('j') >= 24 && date('j') <= 25 && date('n') == 12 ) {
		// christmas eve or christmas
		Ach_AwardAchievement( $_SESSION['playerid'], ACH_HOLIDAY );
	}
	
	return Array( 	GetPlayerInfo( $playerid ), 
					GetGames( $playerid, 0, 30, 0), 
					Ach_GetNewAchievement( $playerid ), 
					GetNewLevel( $playerid ),
					GetActiveTournaments() );
}

function GetGames( $playerid, $completed, $limit = 20, $skipSolo = 0 )
{
	if( !isset($playerid) || empty($playerid) ) 
	{
		error_log( __FUNCTION__ . ": Missing parameters. Playerid=$playerid, completed=$completed, limit=$limit, skipSolo=$skipSolo" );
		return 0; 
	}
	
	if( $completed > 0 ) 
	{
		// We want only the completed games. 
		$winPlayerClause = " b.winningplayer > 0 ";
		$orderByClause = " b.gamefinish desc ";
	}
	else
	{
		// We want completed and unfinished games. We want to put completed games on top. 
		$winPlayerClause = " (winningplayer=0 or (winningplayer>0 and a.winacknowledged=0)) and a.playerturn < 999 ";
		$orderByClause = " b.winningplayer desc, 
						(finishedplayers=b.maxturns-1 && playerround>1 && playerround < 11) desc, 
						(finishedplayers=b.maxturns-1 && playerround=1) desc, 
						(a.playerround>1 and a.playerround < 11 and finishedplayers < b.maxturns) desc,
						(a.playerround<11) desc, 
						b.gamestart asc ";
	}
	
	$skipSql = "";
	if( $skipSolo ) $skipSql = " and b.maxturns > 1 ";
	
	$sql = "select * from (
		select a.gameid, b.currentturn, b.maxturns, b.winningplayer, a.playerturn, a.playerround, b.gamemode,
		DATE_FORMAT(b.gamefinish,'%b %D') as gamefinish, b.playerstring,
		((a.playerturn=b.currentturn and b.gamemode=1) or (a.playerround<11 and b.gamemode=2)) as yourturn,
		(select count(*) from farkle_games_players where gameid=b.gameid and playerround>=11 and b.gamemode=2) as finishedplayers
		from farkle_games_players a, farkle_games b 
		where playerid='$playerid' and a.gameid=b.gameid $skipSql
		and $winPlayerClause 
		order by $orderByClause) x
		LIMIT 0, $limit";
		
	$gamedata = db_select_query( $sql, SQL_MULTI_ROW );
	
	return $gamedata;
}

function Player_UpdateTitle( $titleid )
{
	global $gTitles; 
	
	$sql = "select playerlevel from farkle_players where playerid={$_SESSION['playerid']}";
	$playerlevel = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	if( $titleid > $playerlevel )
	{
		// This title ID is too high for the player's level (might have been javascript client manipulation). Don't pass go. 
		error_log( "Player {$_SESSION['username']} ({$_SESSION['playerid']}) - illegal title update. Level=$playerlevel, Title ID=$titleid" );
		return 0; 
	}
	else
	{
		// Title ID is valid for this player's level. Update it. 
		$sql = "update farkle_players set playertitle='" . $gTitles[$titleid] . "' where playerid={$_SESSION['playerid']}";
		db_command($sql);
		return 1; 
	}
	return 0; 
}

// Return each title visible to a player based on their level (approx. one title every 3 levels) 
function Player_GetTitleChoices( $level )
{
	global $gTitles; 
	
	// Check for valid level range. 
	if( $level <= 0 || $level > 999 ) return 0; 
	
	$titles = Array();
	
	foreach( $gTitles as $i=>$t )
		if( $i < $level ) 
			array_push( $titles, Array( 'level'=>$i, 'title'=>$t ) ); 

	return $titles; 
}

// Options from the player info "Options" screen. 
function SaveOptions( $email, $sendHourlyEmails=1, $random_selectable=1 )
{
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
	{
		error_log( __FUNCTION__ . 'Invalid email: $email'); 
		return 0; 
	}
	
	if( $sendHourlyEmails != 1 && $sendHourlyEmails != 0 )
	{
		error_log( __FUNCTION__ . 'Invalid SendHourlyEmail boolean value'); 
		return 0; 
	}
	
	$sql = "update farkle_players set email='$email', sendhourlyemails=$sendHourlyEmails, random_selectable=$random_selectable
		where playerid={$_SESSION['playerid']}";
	$result = db_command($sql);
	
	return 1; 
}

/*function SendReminder( $gameid )
{
	// Get the email of the current player in this game
	$sql = "select email from farkle_players a, farkle_games b, farkle_games_players c
		where b.gameid=$gameid and c.gameid=b.gameid and b.currentturn=c.playerturn and a.playerid=c.playerid";
	$currentEmail = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	if( !empty($currentEmail) )
	{
		$un = strtoupper($_SESSION['username']);
		$subject = "$un wants you to play your turn!";
		
		$message = "Its your turn and $un is ready to play! Click the link below to continue your game.\r\n" . 
		'http://www.farkledice.com/farkle.php?resumegameid=' . $gameid . "\r\n\r\n" .
		'To unsubscribe you may quit the Farkle game in the link above.';				

		BaseUtil_Debug( "Sending email to $currentEmail. Subj=[$subject] Msg=[" . strip_tags($message) . "]", 7 );
			
		$rc = SendEmail($currentEmail, $subject, $message);
	}
	return 0;
}*/

/*
function GameWinningEmail( $gameid, $winnerid, $reason )
{
	BaseUtil_Debug( "GameWinningEmail: entered.", 7 );	
	
	// Select all the losers of the game
	$sql = "select email, IFNULL(a.fullname, a.username) as username, c.playerscore as score
		from farkle_players a, farkle_games b, farkle_games_players c
		where b.gameid=$gameid and c.gameid=b.gameid and a.playerid=c.playerid
		order by score desc";
	$gameEmails = db_select_query( $sql, SQL_MULTI_ROW );
	
	$sql = "select username from farkle_players a, farkle_games b
		where a.playerid=b.winningplayer and b.gameid=$gameid";
	$winningUsername = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	$un = strtoupper( $winningUsername );
	$subject = "Farkle game $gameid has been won!";
	
	$message = "And the winner is...\r\n" . 
		$un . "!\r\n\r\n";
	
	if( !empty($reason) )
		$message .= "Reason: " . $reason . "\r\n\r\n";
	
	$message .= "Scores: \r\n";
	foreach( $gameEmails as $g )
	{
		if( empty($g['score']) ) $g['score'] = 0;
		$message .= $g['username'] . "\t\t\t" . $g['score'] . "\r\n";
	}
	
	$rc = 0;
	foreach( $gameEmails as $g )
	{
		if( !empty( $g['email'] ) )
		{				
			$rc = SendEmail( $g['email'], $subject, $message );
		}
	}
	return $rc;
}*/
	
?>
