<?php

require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleAchievements.php'); 

define( 'PLAYERLEVEL_WIN_XP', 		5 );
define( 'PLAYERLEVEL_SOLO_XP',		3 );
define( 'PLAYERLEVEL_FINISH_XP', 	5 );
define( 'PLAYERLEVEL_GOODROLL_XP',	1 ); 
define( 'PLAYERLEVEL_ACHIEVE_XP', 	20 );

define( 'MAX_LEVEL', 				70 ); 
define( 'PRESTIGE_POINT_LIMIT', 	245 );

define( 'SITEINFO_DOUBLE_XP', 		4); 

if( isset( $_GET['fixlevels'] ) )
{
	CalcTrueLevelFromXP( );
	exit(1);
}

function farkleLevel_Test( $playerid )
{
	require_once('testutil.php'); 
	
	test_start( "farkleLevel.php" );
	// Stat reset
	db_command("update farkle_players set xp=0, xp_to_level=40, playerlevel=1 where playerid=$playerid");
	
	// TEST 1: Earn some XP
	GivePlayerXP( $playerid, 1 ); // Give 1 xp (should not result in a level)
	db_assert("select 1 from farkle_players where playerid=$playerid and playerlevel=1 and xp=1");
	
	// TEST 2: Gain a level from XP gain
	GivePlayerXp( $playerid, 40 ); // Give 40 xp (should result in level 2)
	// This tests CheckForLevel()
	// This tests GetNewLevel()
	// This tests CalcNextLevelXP()
	db_assert("select 1 from farkle_players where playerid=$playerid and playerlevel=2 and xp=1 and xp_to_level > 40");
	
	// Test 3: The player acks seeing this level
	AckLevel( $playerid );
	db_assert("select 1 from farkle_players where level_acked=true");
	
	// Test 4: Check CalcNextLevelXP() for accuracy
	val_assert( CalcNextLevelXP(3), 46 ); // Answer should be 46 
	val_assert( CalcNextLevelXP(6), 53 ); // Answer should be 53  
	val_assert( CalcNextLevelXP(9), 62 ); // Answer should be 62  
	
	test_completed();
}

function IsDoubleXP()
{
	if( !isset($_COOKIE['double_xp']) )
	{
		$sql = "select paramvalue from siteinfo where paramid=".SITEINFO_DOUBLE_XP;
		$double_xp = db_select_query( $sql, SQL_SINGLE_VALUE );
		setcookie( "double_xp", $double_xp, 60*60*3 ); // Expire in 3 hours
		$_SESSION['double_xp'] = $double_xp; 
		
		BaseUtil_Debug( __FUNCTION__ . ": Is it double XP Day? (Using DB value) $double_xp", 7 );
		return $double_xp; 
	} 
	else
	{
		BaseUtil_Debug( __FUNCTION__ . ": Is it double XP Day? (Using cookie value) {$_COOKIE['double_xp']}", 7 );
		return $_COOKIE['double_xp']; 
	}
}

/* 	This function has two optional parameters: $xp and $xp_to_level. If left off this function
	will always check for levels. Otherwise if provided it will do the check without another query.
	If xp_already_given is true, then we won't do the xp query either. */
function GivePlayerXP( $playerid, $amt )
{
	BaseUtil_Debug( __FUNCTION__ . ": giving player $playerid - $amt experience.", 14 );
	
	if( empty($playerid) )
	{
		BaseUtil_Error( __FUNCTION__ . ": missing playerid. Bailing out." );
		return 0; 
	}
	
	if( isset($_SESSION['double_xp']) && $_SESSION['double_xp'] > 0 )
	{
		$amt *= 2; // Double it. 
	}
	
	$sql = "update farkle_players set xp=xp+$amt where playerid=$playerid";
	$result = db_command($sql);
	
	$levelsGained = CheckForLevel( $playerid );		
	return $amt; 
}

function CheckForLevel( $playerid )
{
	$levelsGained = 0;
	
	if( empty($playerid) )
	{
		error_log( __FUNCTION__ . ": Function called with missing parameters.Pid=$playerid, xp=$xp, xpLevel=$xp_to_level, Level=$level " );
		return 0; 
	}
	
	// Query xp & level data. 
	$sql = "select xp, xp_to_level, playerlevel from farkle_players where playerid=$playerid"; 
	$pData = db_select_query( $sql, SQL_SINGLE_ROW );
	$newXP = $pData['xp']; 
	$xp_to_level = $pData['xp_to_level'];
	$newLevel = $pData['playerlevel']; 
	
	if( $newXP >= $xp_to_level && $newXP > 0 && $xp_to_level > 0 )
	{
		// Loop to determine how many levels the player has gained. 
		// We might have just recieved a bulk amount of XP so we need to check for many levels. 
		do 
		{
			$newLevel++; 
			$levelsGained++;
			$newXP -= $xp_to_level;
			$xp_to_level = CalcNextLevelXP( $newLevel-1 ); 
			
			BaseUtil_Debug( __FUNCTION__ . ": Player $playerid gained a level. They are now level $newLevel. They need $xp_to_level XP to level again. Current XP is $newXP.", 14 );
		}
		while( $newXP >= $xp_to_level );

		$cardcolor = ( $newLevel > 9 ? "prestige" . floor($newLevel / 10) . ".png" : "green" );
		if( $newLevel >= MAX_LEVEL+10 )
			$cardcolor = "prestige7.png";
		if( $newLevel >= 100 )
			$cardcolor = "prestige8.png";
		
		// Update the player's XP, etc.
		$sql = "update farkle_players set playerlevel=playerlevel+$levelsGained,
				xp_to_level=$xp_to_level, xp=$newXP, level_acked=false
				where playerid=$playerid";
		$result = db_command($sql);
		
		// Award level achievement
		Ach_CheckLevel( $playerid, $newLevel );
		
		return $levelsGained; 
	}
	return 0; 
}

function GetNewLevel( $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );

	// Get any un-awarded levels
	$sql = "select playerid, playerlevel from farkle_players where playerid=$playerid and level_acked=false";
	$levelData = db_select_query( $sql, SQL_SINGLE_ROW );
	if( isset($levelData['playerlevel']) ) 
	{
		$rewardString = "";
		if( ((int)$levelData['playerlevel']) % 3 == 0 ) {
			$rewardString = "You have earned a new title!";
		}
		if( strlen($rewardString) > 0 ) $rewardString .= "<br/>";
		if( ((int)$levelData['playerlevel']) % 10 == 0 ) {
			$rewardString .= "You have earned a new background!";
		}
		$levelData['rewardstring'] = $rewardString; 
		return $levelData;
	}
	return 0;		
}

function AckLevel( $playerid )
{
	if( !empty($playerid) )
	{
		// Set this as awarded so we don't show it again
		$sql = "update farkle_players set level_acked=true where playerid=$playerid";
		$result = db_command($sql);		
		return 1; 
	}
	return 0;
}

function CalcNextLevelXP( $level )
{
	// 70 (base) + 1.2% per level 
	return (int)(40*(pow(1.05,$level)));
}

function CalcTrueLevelFromXP( )
{
	$sql = "select playerid, xp, xp_to_level, playerlevel from farkle_players where playerid=35221"; 
	$players = db_select_query( $sql, SQL_MULTI_ROW );
	
	foreach( $players as $p )
	{
		$testLevel = 1; 
		do
		{
			$testXP = CalcNextLevelXP( $testLevel );
			if( $testXP < (int)$p['xp_to_level']) $testLevel++;
		} while( $testXP < (int)$p['xp_to_level']);
		
		if( abs($p['playerlevel'] - $testLevel) > 1 )
			echo "<span style='color: red;'>Player {$p['playerid']} current level is {$p['playerlevel']}. Should be: $testLevel<br/></span>";
		else
			echo "Player {$p['playerid']} current level is {$p['playerlevel']}. Should be: $testLevel<br/>";
	}
	echo "Processed " . count($players) . " players.";
}

/*function PlayerPrestige( $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": Entered. Player $playerid is trying to prestige.", 7 );
	$rc = 0; 
	
	if( $playerid != $_SESSION['playerid'] )
	{
		error_log( __FUNCTION__ . " - player $playerid is trying to prestige as someone else." );
		return Array( 'Error', 'Error saving statistics.' );
	}
	
	$sql = "select prestige, (select sum(a.worth) from farkle_achievements a, farkle_achievements_players b where a.achievementid=b.achievementid and b.playerid=$playerid) as achpoints from farkle_players where playerid=$playerid";
	$pInfo = db_select_query( $sql, SQL_SINGLE_ROW );
	$prestige = $pInfo['prestige'];

	if( $pInfo['achpoints'] < PRESTIGE_POINT_LIMIT )
	{
		error_log( __FUNCTION__ . " - player $playerid tried to prestige when they did not have enough points" );
		return Array( 'Error' => 'Not enough achievement points to prestige.' );
	}
	
	// Save player state before prestige
	$rc += db_command("delete from farkle_players_backup where playerid=$playerid");
	$rc += db_command("insert into farkle_players_backup select * from farkle_players where playerid=$playerid");

	// Save player's achievements before prestige
	$rc += db_command("delete from farkle_achievements_players_backup where playerid=$playerid");
	$rc += db_command("insert into farkle_achievements_players_backup select * from farkle_achievements_players where playerid=$playerid");
	
	if( $rc == 4 )
	{
		$prestige += 1;
		if( db_command("update farkle_players set farkles=0, wins=0, losses=0, totalpoints=0, highestround=0, prestige=$prestige, cardcolor='prestige$prestige.png', titlelevel=3, playertitle=null, lastplayed=NOW() where playerid=$playerid") )
		{
			$rc = db_command("delete from farkle_achievements_players where playerid=$playerid");
		}
	}
	else
	{
		error_log( __FUNCTION__ . " - Aborting prestige because backup failed.", 1 );
		return Array( 'Error' => 'Error reseting statistics.' );
	}
	return Array('prestige' => $prestige); 
}*/

?>