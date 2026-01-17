<?php
/*
	farkleLeaderboard.php
	
	Functions related to the various operations on each page (not game logic). 

	13-Jan-2013		mas		Now shows your current player on the leaderboard (even if not in top 25) 
*/
require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleGameFuncs.php'); 

// This parameter will tell us if the page did something to dirty the leaderboard (such as this player won)
$g_leaderboardDirty = 0; 

// Set this to nothing on page load. 
//$_SESSION['leaderboard']['lastupdatedata'] = '';

if( isset($_GET['action']) )
{
	if( $_GET['action'] == 'updateleaderboards' )
	{
		Leaderboard_RefreshData( true );
		Leaderboard_RefreshDaily();
	}
}

function GetLeaderBoard()
{
	global $g_leaderboardDirty; 
	// Wins and Losses per player
	// Excludes solo games
	// Games played in the last 30 days. 
	
	BaseUtil_Debug( "GetLeaderBoard: Entered.", 1 );
	
	$i=0;
	$lbData = Array();
	$maxRows = 25;
	$playerAgeOutDays = 30;
	$limitClause = " LIMIT 0, $maxRows";
	
	// Check to see if leaderboard data needs refreshing. 
	Leaderboard_RefreshData();
	
	// Return cached data if it was recorded in the last 3 minutes. 
	if( isset($_SESSION['farkle']['lb']) )
	{
		if( (time() - $_SESSION['farkle']['lbTimestamp']) < 60*3 && !$g_leaderboardDirty ); // 3 minutes
			return $_SESSION['farkle']['lb'];
	}
	
	$g_leaderboardDirty = 0; // No longer dirty since we just gave you data. 
	
	$sql = "select paramvalue from siteinfo where paramid=3";
	$dayOfWeek = db_select_query( $sql, SQL_SINGLE_VALUE );
	$_SESSION['farkle']['lb']['dayOfWeek'] = $dayOfWeek; 
	// Today Stats
	
	$maxRows = 3;
	for( $i=0;$i<3;$i++)
	{
		// Highest game scores today
		$sql = "select username, playerid, playerlevel, first_int, lbrank
		from farkle_lbdata 
		where lbindex=$i and lbrank <= $maxRows
		order by lbrank";
		$_SESSION['farkle']['lb'][0][$i] = db_select_query( $sql, SQL_MULTI_ROW );
	}

	$maxRows = 25; 
	for( $i=1; $i<4; $i++ )
	{
		$lbIndex=$i+2;
		// Wins/Losses / Win/Loss Ratio for players with more than 10 games played
		$sql = "select username, playerid, playerlevel, first_int, second_int, first_string, second_string, lbrank
		from farkle_lbdata 
		where lbindex=$lbIndex and lbrank <= $maxRows
		order by lbrank 
		$limitClause";
		$_SESSION['farkle']['lb'][$i] = db_select_query( $sql, SQL_MULTI_ROW );
		
		// This will stuff our player onto the end of the leaderboards if they are not in the top 25 (displacing #25) 
		$found=0;
		foreach( $_SESSION['farkle']['lb'][$i] as $j ) 
			if( $j['playerid'] == $_SESSION['playerid'] ) 
				$found=1;

		if( $found==0 ) {
			$sql = "select username, playerid, playerlevel, first_int, second_int, 
				first_string, second_string, lbrank
				from farkle_lbdata where lbindex=$lbIndex and playerid={$_SESSION['playerid']}";
			$_SESSION['farkle']['lb'][$i][$maxRows-1] = db_select_query( $sql, SQL_SINGLE_ROW );
		}
		
		if( $i==2 )
		{
			// Award the highest 10-round award. 
			if( isset($_SESSION['farkle']['lb'][$i][0]['playerid']) )
				Ach_AwardAchievement( $_SESSION['farkle']['lb'][$i][0]['playerid'], ACH_LB_HIGHESTRND );
		}
	}
	
	$_SESSION['farkle']['lbTimestamp'] = time();
	
	return $_SESSION['farkle']['lb'];
}

function Leaderboard_RefreshData( $force = false )
{
	// Wins and Losses per player
	// Excludes solo games
	// Games played in the last 30 days. 
	
	BaseUtil_Debug( "Leaderboard_RefreshData: Entered.", 1 );
	
	// Is it time to refresh leaderboard data? 
	$sql = "select (paramvalue <= UNIX_TIMESTAMP(NOW()) ) from siteinfo where paramid=1";
	$doRefresh = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	if( !$doRefresh && !$force )
	{
		// It's too early to refresh the leaderboard data. This user will get whatever we already have. 
		return 0; 
	}
	else
	{
		// Set the next refresh to 3 minutes from now. 
		$sql = "update siteinfo set paramvalue=UNIX_TIMESTAMP( NOW() + interval '5' minute ) 
		where paramid=1 and paramname='last_leaderboard_refresh'";
		$result = db_command($sql);	
	}
	
	$i=0;
	$maxRows = 25;
	$playerAgeOutDays = 30;
	
	//$limitClause = " LIMIT 0,$maxRows";
	$limitClause = "";
	
	// Clear out the old data. 
	$sql = "delete from farkle_lbdata where lbindex in (3,4,5)";
	$result = db_command($sql);	
		
	// Wins/Losses / Win/Loss Ratio for players with more than 10 games played
	$sql = "SELECT 0 INTO @rank";
	$result = mysql_query ($sql);
	$sql = "select t1.*, @rank:=@rank+1 AS lbrank from 
		(select 3 as lbindex, playerid, COALESCE(fullname, username) as username, playerlevel,
		wins as first_int, losses as second_int, FORMAT(COALESCE(wins/losses, 1),2) as first_string, 
		null as second_string
		from farkle_players 
		order by wins desc $limitClause) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";	
	$result = db_command($insert_sql);	
	//lastplayed > NOW() - interval '$playerAgeOutDays' day	and
	
	// Highest round
	$sql = "SELECT 0 INTO @rank";
	$result = mysql_query ($sql);
	$sql = "select t1.*, @rank:=@rank+1 AS lbrank from 
		(select 4 as lbindex, playerid, COALESCE(fullname, username) as username, playerlevel,
		0 as first_int, 0 as second_int, FORMAT(highest10Round,0) as first_string, null as second_string
		from farkle_players 	
		order by farkle_players.highest10Round desc $limitClause) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);	
	//where lastplayed > NOW() - interval '$playerAgeOutDays' day
	
	// Achievement Points
	$sql = "SELECT 0 INTO @rank";
	$result = mysql_query ($sql);
	$sql = "select t1.*, @rank:=@rank+1 as lbrank from
		(select 5 as lbindex, a.playerid, COALESCE(fullname, username) as username, a.playerlevel,
		sum(worth) as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players a, farkle_achievements_players b, farkle_achievements c
		where a.playerid=b.playerid and b.achievementid=c.achievementid 		
		group by username
		order by first_int desc $limitClause) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);	
	//and a.lastplayed > NOW() - interval '$playerAgeOutDays' day
	
	return 1;
}

// Called from a nightly cron job
function Leaderboard_RefreshDaily()
{
	$maxDataRows = 35; 
	
	$sql = "delete from farkle_lbdata where lbindex in (0,1,2)";
	$result = db_command($sql);	

	// Update the day of week. 
	$sql = "update siteinfo set paramvalue=TO_CHAR(NOW()-interval'1'day, 'Day, Mon DD') where paramid=3";
	$result = db_command($sql);	
	
	// Today Stats	
	// Highest game scores today
	$sql = "SELECT 0 INTO @rank";
	$result = mysql_query ($sql);
	$sql = "select t1.*, @rank:=@rank+1 as lbrank from 
		(select 0 as lbindex, a.playerid, COALESCE(fullname, username) as username, playerlevel, 
		playerscore as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players a, farkle_games_players b
		where a.playerid=b.playerid and DAY(b.lastplayed) = DAY(NOW()-interval'1'day) and b.lastplayed > NOW() - interval '3' day
		order by playerscore desc LIMIT 0,$maxDataRows) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);	
	
	// Top 5 "Farklers" today
	$sql = "SELECT 0 INTO @rank";
	$result = mysql_query ($sql);
	$sql = "select t1.*, @rank:=@rank+1 as lbrank from
		(select 1 as lbindex, a.playerid, COALESCE(fullname, username) as username, playerlevel,
		count(*) as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players a, farkle_games_players b, farkle_rounds c
		where a.playerid=b.playerid and DAY(b.lastplayed) = DAY(NOW()-interval'1'day) and b.lastplayed > NOW() - interval '3' day
		and a.playerid=c.playerid and b.gameid=c.gameid and c.roundscore=0
		group by username, playerid
		order by first_int desc limit 0,$maxDataRows) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);	
	
	// Today's Most Wins
	$sql = "SELECT 0 INTO @rank";
	$result = mysql_query ($sql);
	$sql = "select t1.*, @rank:=@rank+1 as lbrank from
		(select 2 as lbindex, a.playerid, COALESCE(fullname, username) as username, playerlevel,
		count(*) as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players a, farkle_games c
		where c.winningplayer=a.playerid 
		and DAY(c.gamefinish) = DAY(NOW()-interval'1'day) 
		and c.gamefinish > NOW() - interval '3' day
		and c.gamewith in (".GAME_WITH_RANDOM.",".GAME_WITH_FRIENDS.") 
		group by username, playerid
		order by first_int desc LIMIT 0,$maxDataRows) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";	
	$result = db_command($insert_sql);	
	
	// Give the MVP the achievement
	$sql = "select playerid from farkle_lbdata where lbindex=2 and lbrank=1";
	$mvpPlayerid = db_select_query( $sql, SQL_SINGLE_VALUE );
	if( $mvpPlayerid )
		Ach_AwardAchievement( $mvpPlayerid, ACH_LB_HIGHESTRND );

}
?>
