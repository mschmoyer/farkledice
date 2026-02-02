<?php
/*
	farkleAchievements.php
	Desc: This file contains functions related to earning achievements

	Changelog: 
	13-Jan-2012		mas		Ach vs. different players was made to award post-humously. 
*/
require_once( 'farkleLevel.php' ); 

define( 'ACH_WINS1', 			1); // Win 5 games
define( 'ACH_WINS2', 			2); // Win 25 games
define( 'ACH_WINS3', 			3); // Win 50 games
define( 'ACH_ROUND_1000', 		4); // Score 1000 in one round
define( 'ACH_ROUND_2500', 		5); // Score 2500 in one round
define( 'ACH_ROUND_5000', 		6);	// Score 5000 in one round
define( 'ACH_BETA_TESTER', 		7);
define( 'ACH_FARKLE', 			8);
define( 'ACH_RIVALS_5', 		9);	// Beat the same player 5 times
define( 'ACH_BEATDOWN_3000', 	10 );
define( 'ACH_BEATDOWN_6000', 	11 );	
define( 'ACH_FARKLE_HARD', 		12 );
define( 'ACH_LB_HIGHESTRND',	13 ); // Highest person on the leaderboard (score) 
define( 'ACH_FRIENDS1',			14 );
define( 'ACH_FRIENDS2',			15 );

define( 'ACH_10ROUNDSCORE1', 	16 );
define( 'ACH_10ROUNDSCORE2', 	17 );
define( 'ACH_10ROUNDSCORE3', 	18 );

define( 'ACH_ROLLALL6DICE', 	19 );
define( 'ACH_GOT_6TH_DICE', 	20 );

define( 'ACH_THREEPAIR', 		21 );
define( 'ACH_STRAIGHT', 		22 );	
define( 'ACH_TWOTRIPLETS', 		23 );

define( 'ACH_WINS4',			24 );
define( 'ACH_WINS5',			25 );

define( 'ACH_FACEBOOK', 		26 ); 

define( 'ACH_FARKLES1', 		27 ); //Farkle 10 times
define( 'ACH_FARKLES2', 		28 ); //Farkle 50 times
define( 'ACH_FARKLES3', 		29 ); //Farkle 100 times

define( 'ACH_RIVALS_15', 		30); // Beat the same player 15 times	
define( 'ACH_RIVALS_50', 		31); // Beat the same player 50 times	

define( 'ACH_LEVEL_10', 		32); 
define( 'ACH_LEVEL_20', 		33); 
define( 'ACH_LEVEL_30', 		34); 
define( 'ACH_LEVEL_40', 		35); 
define( 'ACH_LEVEL_50', 		36); 
define( 'ACH_LEVEL_60', 		37); 

define( 'ACH_WINS6', 			38); 

define( 'ACH_VS_PLAYERS_1', 	39); 
define( 'ACH_VS_PLAYERS_2', 	40); 
define( 'ACH_VS_PLAYERS_3', 	41); 

define( 'ACH_SIX_KIND',			42); 
define( 'ACH_SIX_KIND_ONES', 	43);
define( 'ACH_PERFECT_GAME', 	44);

define( 'ACH_HOLIDAY', 			45);
define( 'ACH_TOURNEY_PLAY', 	46);
define( 'ACH_TOURNEY_WINRND', 	47); 

define( 'ACH_LEVEL_100', 		48);

// ACHIEVEMENT IDEAS: 
/*
	Perfect Game 			Finish a 10-round game with zero farkles
	What are the Odds 		Roll one dice and score
	Sextuplets				Roll six of any one dice value. 
	Perfect Roll			Roll six one's. 
	Cliffhanger				Win by 100 or less points. 
	
*/

// Note: Achievements starting at 1000 are reserved for tournaments

function farkleAchievements_Test( $playerid )
{
	require_once('testutil.php'); 
	
		// Stat reset
	db_command("delete from farkle_achievements_players where playerid=$playerid");
	
	test_start( "farkleAchievements.php" );

	// Test 1: Give player a level achievement for earning level 30
	Ach_CheckLevel( $playerid, 30 ); 
	db_assert("select 1 from farkle_achievements_players where playerid=$playerid and achievementid=".ACH_LEVEL_30);
 
	// Test 2: Try this again. 
	Ach_CheckLevel( $playerid, 30 ); 
	db_assert("select 1 from farkle_achievements_players where playerid=$playerid and achievementid=".ACH_LEVEL_30);
 
	// Test 3: Perfect Game (should fail -- we did not participate in this game) 
	Ach_CheckPerfectGame( $playerid, 1 );
	db_assert("select 1 from farkle_achievements_players where playerid=$playerid and achievementid=".ACH_PERFECT_GAME, 0 );
	
	//val_assert( CalcNextLevelXP(9), 62 ); // Answer should be 62  
	Ach_CheckVsPlayers( $playerid );
	
	test_completed();
}

function Ach_CheckLevel( $playerid, $playerlevel )
{
	if( empty($playerid) || empty($playerlevel) ) 
	{
		BaseUtil_Error( __FUNCTION__ . ": Parameters not set. Playerid=$playerid. Level=$playerlevel. Bailing." );
		return 0; 
	}
	$achAwarded = 0; 
	
	if		( $playerlevel == 10 ) $achAwarded = Ach_AwardAchievement( $playerid, ACH_LEVEL_10 );
	else if ( $playerlevel == 20 ) $achAwarded = Ach_AwardAchievement( $playerid, ACH_LEVEL_20 );
	else if ( $playerlevel == 30 ) $achAwarded = Ach_AwardAchievement( $playerid, ACH_LEVEL_30 );
	else if ( $playerlevel == 40 ) $achAwarded = Ach_AwardAchievement( $playerid, ACH_LEVEL_40 );
	else if ( $playerlevel == 50 ) $achAwarded = Ach_AwardAchievement( $playerid, ACH_LEVEL_50 );
	else if ( $playerlevel == 60 ) $achAwarded = Ach_AwardAchievement( $playerid, ACH_LEVEL_60 );
	else if ( $playerlevel == 100 ) $achAwarded = Ach_AwardAchievement( $playerid, ACH_LEVEL_100 ); 
	return $achAwarded; 
}

function Ach_CheckPerfectGame( $playerid, $gameid )
{
	$achAwarded = 0;
	// Give this player the perfect game achievement if they earned it.
	$sql = "SELECT count(*) FROM farkle_rounds WHERE roundscore = 0 AND gameid = :gameid AND playerid = :playerid";
	$farkles = db_query($sql, [':gameid' => $gameid, ':playerid' => $playerid], SQL_SINGLE_VALUE);

	if( $farkles && $farkles == 0 )
		$achAwarded = Ach_AwardAchievement( $playerid, ACH_PERFECT_GAME );

	return $achAwarded;
}

// Number of different games the player has started against another player. 
function Ach_CheckVsPlayers( $playerid )
{
	if( empty($playerid) ) 
	{
		BaseUtil_Error( __FUNCTION__ . ": Parameters not set. Playerid=$playerid. Bailing." );
		return 0; 
	}

	$achievementAwarded = 0;

	$sql = "SELECT c.playerid, count(*) as numGames
		FROM farkle_games a, farkle_games_players b, farkle_games_players c
		WHERE a.gameid = b.gameid AND a.gameid = c.gameid AND a.gamemode = 2
		AND b.playerid = :playerid
		AND c.playerid <> :playerid2
		AND a.winningplayer > 0
		AND a.gamestart > '2012-12-17'
		GROUP BY c.playerid";

	$numDiffPlayersArr = db_query($sql, [':playerid' => $playerid, ':playerid2' => $playerid], SQL_MULTI_ROW);
	$numDiffPlayers = count($numDiffPlayersArr);
	
	if( $numDiffPlayers >= 5 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_VS_PLAYERS_1 );
		
	if( $numDiffPlayers >= 20 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_VS_PLAYERS_2 );
		
	if( $numDiffPlayers >= 50 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_VS_PLAYERS_3 );
		
	return $achievementAwarded; 
}

function Ach_CheckFarkles( $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );

	// Get count of all friends who our player has started a game against
	$sql = "SELECT farkles FROM farkle_players WHERE playerid = :playerid";
	$numFarkles = db_query($sql, [':playerid' => $playerid], SQL_SINGLE_VALUE);
	
	$achievementAwarded = 0;
	if( $numFarkles == 100 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_FARKLES3 );
	else if( $numFarkles == 50 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_FARKLES2 );
	else if( $numFarkles == 10 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_FARKLES1 );
	
	return $achievementAwarded;
}

function Ach_CheckFriends( $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );

	// Get count of all friends who our player has started a game against
	$sql = "SELECT count(*) FROM farkle_friends a
		WHERE a.sourceid = :sourceid AND
			EXISTS(SELECT * FROM farkle_games_players b WHERE a.friendid = b.playerid)";
	$numFriends = db_query($sql, [':sourceid' => $playerid], SQL_SINGLE_VALUE);
	
	$achievementAwarded = 0;
	
	if( $numFriends >= 10 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_FRIENDS2 );
	else if( $numFriends >= 3 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_FRIENDS1 );
		
	return $achievementAwarded;
}

function Ach_Check10RoundScore( $playerid, $playerScore )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );
	
	$achievementAwarded = 0;
	if( $playerScore >= 5000 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_10ROUNDSCORE1 );
	if( $playerScore >= 10000 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_10ROUNDSCORE2 );
	if( $playerScore >= 12500 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_10ROUNDSCORE3 );
	
	return $achievementAwarded;
}

function Ach_CheckHighestDifferential( $playerid, $playerScore, $nextHighestScore )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );
	
	// NOTE: Probably would be better 2000-4000-6000
	$achievementAwarded = 0;
	if( $playerScore - $nextHighestScore >= 3000 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_BEATDOWN_3000 );
	if( $playerScore - $nextHighestScore >= 6000 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_BEATDOWN_6000 );
	
	return $achievementAwarded;
}

function Ach_CheckPlayerWins( $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );

	$aa = 0;

	$sql = "SELECT wins FROM farkle_players WHERE playerid = :playerid";

	$numWins = db_query($sql, [':playerid' => $playerid], SQL_SINGLE_VALUE);
	
	// NOTE: Probably should be: 1, 5, 10, 50, 100
	if( $numWins >= 5 && $numWins < 25 ) $aa =  Ach_AwardAchievement( $playerid, ACH_WINS1 );
	if( $numWins >= 25 && $numWins < 50) $aa =  Ach_AwardAchievement( $playerid, ACH_WINS2 );
	if( $numWins >= 50 && $numWins < 100) $aa =  Ach_AwardAchievement( $playerid, ACH_WINS3 );
	if( $numWins >= 100 && $numWins < 200) $aa =  Ach_AwardAchievement( $playerid, ACH_WINS4 );
	if( $numWins >= 200 && $numWins < 1000) $aa =  Ach_AwardAchievement( $playerid, ACH_WINS5 );
	if( $numWins >= 1000 ) $aa =  Ach_AwardAchievement( $playerid, ACH_WINS6 );
	return $aa;
}

function Ach_CheckRoundScore( $playerid, $roundscore )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );
	
	// NOTE: probably could be 1k, 2k, 3k, 4k
	$achievementAwarded = 0;
	if( $roundscore >= 1000 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_ROUND_1000 );
	if( $roundscore >= 2500 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_ROUND_2500 );
	if( $roundscore >= 5000 )
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_ROUND_5000 );
	
	return $achievementAwarded;
}

function Ach_GetNewAchievement( $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );

	// Get any un-awarded achievements
	$sql = "SELECT b.achievementid as achievementid, title, description, worth, imagefile
			FROM farkle_achievements_players a, farkle_achievements b
			WHERE a.playerid = :playerid AND a.achievementid = b.achievementid AND a.awarded = false LIMIT 1";
	$achData = db_query($sql, [':playerid' => $playerid], SQL_SINGLE_ROW);
	if( isset($achData) ) return $achData;
	return 0;
}

// Award given achievement if it has not been given. 
function Ach_AwardAchievement( $playerid, $achievementid )
{
	global $gTestMode; 
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );
	
	if( empty($playerid) )
	{
		BaseUtil_Error( __FUNCTION__ . ": missing playerid." );
	}
	
	if( Ach_CheckForAchievement( $playerid, $achievementid ) == 0 )
	{
		if( empty($gTestMode) )
		{
			// Give 20XP for earning an achievement. Do not include the "level" achievements (recursion)
			if( $achievementid < 32 || $achievementid > 37 ) {
				//error_log( "Giving player $playerid " . PLAYERLEVEL_ACHIEVE_XP . " xp for earning achievement $achievementid." );
				//GivePlayerXP( $playerid, PLAYERLEVEL_ACHIEVE_XP );
			}

			$sql = "INSERT INTO farkle_achievements_players (playerid, achievementid, achievedate)
				VALUES (:playerid, :achievementid, NOW())";
			$result = db_execute($sql, [':playerid' => $playerid, ':achievementid' => $achievementid]);
		}
		return $achievementid;
	}
	else
	{
		BaseUtil_Debug( __FUNCTION__ . ": player $playerid already had achievement $achievementid.", 14 );
	}
	return 0;
}

// Return true (1) if they have given achievement
function Ach_CheckForAchievement( $playerid, $achievementid )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );

	if( empty($playerid) || empty($achievementid) ) {
		BaseUtil_Error( "Ach_CheckForAchievement - missing required parameter. pid=$playerid, achid=$achievementid" );
		return 0;
	}

	$theyHaveIt = 0;
	$sql = "SELECT count(*) FROM farkle_achievements_players WHERE playerid = :playerid AND achievementid = :achievementid";
	$theyHaveIt = db_query($sql, [':playerid' => $playerid, ':achievementid' => $achievementid], SQL_SINGLE_VALUE);

	if( $theyHaveIt > 0 )
		return 1;

	return 0;
}

function GetAchievements( $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );

	$achData = Array();

	// Get all achievements but set earned=1 for ones the player has
	$sql = "SELECT 1 as earned, a.*, b.achievedate, TO_CHAR(b.achievedate, 'Mon DD, YYYY') as formatteddate
				FROM farkle_achievements a, farkle_achievements_players b
				WHERE a.achievementid = b.achievementid AND b.playerid = :playerid
			UNION
			SELECT 0 as earned, c.*, null as achievedate, TO_CHAR(NOW(), 'Mon DD, YYYY') as formatteddate
				FROM farkle_achievements c
				WHERE NOT EXISTS (SELECT 1 FROM farkle_achievements_players d
					WHERE d.achievementid = c.achievementid AND d.playerid = :playerid2)
					AND c.achievementid < 1000
			ORDER BY earned DESC, achievedate DESC, worth DESC";

	$achData = db_query($sql, [':playerid' => $playerid, ':playerid2' => $playerid], SQL_MULTI_ROW);	
	
	$totalPoints = 0;
	if( count($achData) > 0 )
	{			
		foreach( $achData as $a )
		{
			if( (int)$a['earned'] == 1 )
				$totalPoints += $a['worth'];
		}
	}
	return Array($achData, $totalPoints);
}

function AckAchievement( $playerid, $achievementid )
{
	if( !empty($achievementid) && !empty($playerid) )
	{
		// Set this as awarded so we don't show it again
		$sql = "UPDATE farkle_achievements_players SET awarded = true WHERE playerid = :playerid AND achievementid = :achievementid";
		$result = db_execute($sql, [':playerid' => $playerid, ':achievementid' => $achievementid]);
		return 1;
	}
	return 0;
}

// Check rivalry achievements - beat the same player multiple times
function Ach_CheckRivals( $playerid, $loser )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );
	$achievementAwarded = 0;

	if( $playerid == $loser ) return 0;
	if( empty($playerid) || empty($loser) ) return 0;

	$sql = "SELECT count(*) FROM farkle_games a, farkle_games_players b, farkle_games_players c
		WHERE a.gameid = b.gameid AND a.gameid = c.gameid
		AND b.playerid = :playerid AND c.playerid = :loser
		AND a.winningplayer = :winnerid";
	$numTimesBeaten = db_query($sql, [':playerid' => $playerid, ':loser' => $loser, ':winnerid' => $playerid], SQL_SINGLE_VALUE);

	if( $numTimesBeaten >= 5 )
		$achievementAwarded = Ach_AwardAchievement( $playerid, ACH_RIVALS_5 );
	if( $numTimesBeaten >= 15 )
		$achievementAwarded = Ach_AwardAchievement( $playerid, ACH_RIVALS_15 );
	if( $numTimesBeaten >= 50 )
		$achievementAwarded = Ach_AwardAchievement( $playerid, ACH_RIVALS_50 );

	return $achievementAwarded;
}

//NOTE: This should probably dissapear. 
/*function Ach_CheckRandomAchieves( $playerid )
{
	$achievementAwarded = 0;
	if( (int)FARKLE_MAJOR_VERSION < 1 )
	{
		$achievementAwarded =  Ach_AwardAchievement( $playerid, ACH_BETA_TESTER );
	}
	return $achievementAwarded;
}*/		
?>