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
		// Manual leaderboard refresh - show output
		header('Content-Type: text/html; charset=utf-8');
		echo "<!DOCTYPE html><html><head><title>Leaderboard Sync</title>";
		echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;}";
		echo ".success{color:#0f0;} .info{color:#4af;} .error{color:#f44;} .header{color:#ff0;font-size:1.2em;}</style>";
		echo "</head><body>";
		echo "<div class='header'>========================================<br>";
		echo "Farkle Leaderboard Manual Sync<br>";
		echo "========================================</div><br>";

		echo "<div class='info'>Started: " . date('Y-m-d H:i:s') . "</div><br>";

		// Check database connection and siteinfo table
		echo "<div class='info'>Checking database connection...</div>";
		try {
			$check = db_select_query("SELECT COUNT(*) FROM siteinfo", SQL_SINGLE_VALUE);
			echo "<div class='success'>✓ Database connected, siteinfo has {$check} entries</div><br>";
		} catch (Exception $e) {
			echo "<div class='error'>✗ Database connection ERROR: " . htmlspecialchars($e->getMessage()) . "</div><br>";
			echo "</body></html>";
			exit(1);
		}

		echo "<div class='info'>Step 1: Refreshing main leaderboard data...</div>";
		echo "<div style='margin-left:20px;'>";
		echo "- Wins/Losses rankings<br>";
		echo "- Highest 10-round scores<br>";
		echo "- Achievement points<br>";
		echo "</div>";

		try {
			$result1 = Leaderboard_RefreshData( true );
			if ($result1) {
				echo "<div class='success'>✓ Main leaderboard refresh complete</div><br>";
			} else {
				echo "<div class='error'>✗ Main leaderboard refresh skipped (throttle check)</div><br>";
			}
		} catch (Exception $e) {
			echo "<div class='error'>✗ Main leaderboard refresh ERROR: " . htmlspecialchars($e->getMessage()) . "</div><br>";
			error_log("Leaderboard refresh error: " . $e->getMessage());
		}

		echo "<div class='info'>Step 2: Refreshing daily leaderboard stats...</div>";
		echo "<div style='margin-left:20px;'>";
		echo "- Yesterday's highest scores<br>";
		echo "- Yesterday's most farkles<br>";
		echo "- Yesterday's most wins<br>";
		echo "</div>";

		try {
			Leaderboard_RefreshDaily();
			echo "<div class='success'>✓ Daily leaderboard refresh complete</div><br>";
		} catch (Exception $e) {
			echo "<div class='error'>✗ Daily leaderboard refresh ERROR: " . htmlspecialchars($e->getMessage()) . "</div><br>";
			error_log("Daily leaderboard refresh error: " . $e->getMessage());
		}

		echo "<div class='info'>Step 3: Verifying data...</div>";

		// Check how many entries were created
		$sql = "SELECT lbindex, COUNT(*) as count FROM farkle_lbdata GROUP BY lbindex ORDER BY lbindex";
		$counts = db_select_query($sql, SQL_MULTI_ROW);

		if ($counts && count($counts) > 0) {
			echo "<div style='margin-left:20px;'>Leaderboard entries by category:<br>";
			$category_names = [
				0 => 'Yesterday\'s High Scores',
				1 => 'Yesterday\'s Farklers',
				2 => 'Yesterday\'s Winners',
				3 => 'Wins/Losses',
				4 => 'Highest 10-Round',
				5 => 'Achievement Points'
			];

			$total = 0;
			foreach ($counts as $row) {
				$category = $category_names[$row['lbindex']] ?? "Unknown ({$row['lbindex']})";
				echo "<div class='info'>&nbsp;&nbsp;{$category}: {$row['count']} entries</div>";
				$total += $row['count'];
			}
			echo "<br><div class='success'>Total entries: {$total}</div>";
		} else {
			echo "<div class='error'>⚠ WARNING: No leaderboard data found!</div>";
		}

		echo "<br><div class='header'>========================================<br>";
		echo "Leaderboard Sync Complete!<br>";
		echo "========================================</div>";
		echo "<div class='info'>Finished: " . date('Y-m-d H:i:s') . "</div>";
		echo "</body></html>";
		exit(0);
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
	$limitClause = " LIMIT $maxRows";
	
	// Check to see if leaderboard data needs refreshing. 
	Leaderboard_RefreshData();
	
	// Return cached data if it was recorded in the last 3 minutes.
	if( isset($_SESSION['farkle']['lb']) && isset($_SESSION['farkle']['lbTimestamp']) )
	{
		if( (time() - $_SESSION['farkle']['lbTimestamp']) < 60*3 && !$g_leaderboardDirty ) // 3 minutes
		{
			return $_SESSION['farkle']['lb'];
		}
	}
	
	$g_leaderboardDirty = 0; // No longer dirty since we just gave you data. 
	
	$sql = "select paramvalue from siteinfo where paramid=3";
	$dayOfWeek = db_select_query( $sql, SQL_SINGLE_VALUE );
	$_SESSION['farkle']['lb']['dayOfWeek'] = $dayOfWeek; 
	// Today Stats
	
	$maxRows = 3;
	for( $i=0;$i<3;$i++)
	{
		// Daily stats: high scores (0), farkles (1), win ratio (2)
		$sql = "select username, playerid, playerlevel, first_int, second_int, first_string, lbrank
		from farkle_lbdata
		where lbindex=$i and lbrank <= $maxRows
		order by lbrank";
		$_SESSION['farkle']['lb'][0][$i] = db_select_query( $sql, SQL_MULTI_ROW );
	}

	// Daily stat: best rounds (lbindex 6, stored at index 3)
	$sql = "select username, playerid, playerlevel, first_int, lbrank
		from farkle_lbdata
		where lbindex=6 and lbrank <= $maxRows
		order by lbrank";
	$_SESSION['farkle']['lb'][0][3] = db_select_query( $sql, SQL_MULTI_ROW );

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
		if( !empty($_SESSION['farkle']['lb'][$i]) && is_array($_SESSION['farkle']['lb'][$i]) ) {
			foreach( $_SESSION['farkle']['lb'][$i] as $j )
				if( $j['playerid'] == $_SESSION['playerid'] )
					$found=1;
		}

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
	$sql = "select (paramvalue::numeric <= EXTRACT(EPOCH FROM NOW()) ) from siteinfo where paramid=1";
	$doRefresh = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	if( !$doRefresh && !$force )
	{
		// It's too early to refresh the leaderboard data. This user will get whatever we already have. 
		return 0; 
	}
	else
	{
		// Set the next refresh to 3 minutes from now.
		$sql = "update siteinfo set paramvalue=EXTRACT(EPOCH FROM (NOW() + interval '5' minute))
		where paramid=1 and paramname='last_leaderboard_refresh'";
		$result = db_command($sql);
	}
	
	$i=0;
	$maxRows = 25;
	$playerAgeOutDays = 30;

	//$limitClause = " LIMIT $maxRows";
	$limitClause = "";
	
	// Clear out the old data. 
	$sql = "delete from farkle_lbdata where lbindex in (3,4,5)";
	$result = db_command($sql);	
		
	// Wins/Losses / Win/Loss Ratio for players with more than 10 games played
	// PostgreSQL: No need for @rank user variable - use ROW_NUMBER() instead
	$sql = "select t1.*, ROW_NUMBER() OVER () AS lbrank from
		(select 3 as lbindex, playerid, COALESCE(fullname, username) as username, playerlevel,
		wins as first_int, losses as second_int, TO_CHAR(COALESCE(wins::numeric/NULLIF(losses,0), 1),'FM999999990.00') as first_string,
		null as second_string
		from farkle_players
		order by wins desc $limitClause) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);	
	//lastplayed > NOW() - interval '$playerAgeOutDays' day	and
	
	// Highest round
	// PostgreSQL: No need for @rank user variable - use ROW_NUMBER() instead
	$sql = "select t1.*, ROW_NUMBER() OVER () AS lbrank from
		(select 4 as lbindex, playerid, COALESCE(fullname, username) as username, playerlevel,
		highest10Round as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players
		where highest10Round IS NOT NULL
		order by farkle_players.highest10Round desc $limitClause) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);	
	//where lastplayed > NOW() - interval '$playerAgeOutDays' day
	
	// Achievement Points
	// PostgreSQL: No need for @rank user variable - use ROW_NUMBER() instead
	$sql = "select t1.*, ROW_NUMBER() OVER () as lbrank from
		(select 5 as lbindex, a.playerid, COALESCE(fullname, username) as username, a.playerlevel,
		sum(worth) as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players a, farkle_achievements_players b, farkle_achievements c
		where a.playerid=b.playerid and b.achievementid=c.achievementid
		group by a.playerid, a.username, a.fullname, a.playerlevel
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
	
	$sql = "delete from farkle_lbdata where lbindex in (0,1,2,6)";
	$result = db_command($sql);	

	// Update the day of week.
	$sql = "update siteinfo set paramvalue=TO_CHAR(NOW(), 'Day, Mon DD') where paramid=3";
	$result = db_command($sql);

	// Today Stats
	// Highest game scores today
	$sql = "select t1.*, ROW_NUMBER() OVER () as lbrank from
		(select 0 as lbindex, a.playerid, COALESCE(fullname, username) as username, playerlevel,
		playerscore as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players a, farkle_games_players b
		where a.playerid=b.playerid and DATE(b.lastplayed) = CURRENT_DATE
		order by playerscore desc LIMIT $maxDataRows) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);

	// Top farklers today (rounds with zero score)
	$sql = "select t1.*, ROW_NUMBER() OVER () as lbrank from
		(select 1 as lbindex, a.playerid, COALESCE(a.fullname, a.username) as username, a.playerlevel,
		count(*) as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_players a, farkle_games_players b, farkle_rounds c
		where a.playerid=b.playerid and DATE(b.lastplayed) = CURRENT_DATE
		and a.playerid=c.playerid and b.gameid=c.gameid and c.roundscore=0
		group by a.username, a.playerid, a.fullname, a.playerlevel
		order by first_int desc LIMIT $maxDataRows) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);

	// Today's best win ratio (weighted by players beaten, min 3 games to qualify)
	// first_int = players beaten, second_int = games played, first_string = win%
	$sql = "select t1.*, ROW_NUMBER() OVER () as lbrank from
		(select 2 as lbindex, sub.playerid,
			COALESCE(p.fullname, p.username) as username, p.playerlevel,
			sub.players_beaten as first_int,
			sub.games_played as second_int,
			sub.win_pct || '%' as first_string,
			null as second_string
		from (
			select gp.playerid,
				SUM(CASE WHEN g.winningplayer = gp.playerid
					THEN (SELECT COUNT(*) FROM farkle_games_players x WHERE x.gameid = g.gameid) - 1
					ELSE 0 END) as players_beaten,
				COUNT(*) as games_played,
				ROUND(SUM(CASE WHEN g.winningplayer = gp.playerid THEN 1 ELSE 0 END)::numeric / COUNT(*) * 100) as win_pct
			from farkle_games g
			join farkle_games_players gp on g.gameid = gp.gameid
			where DATE(g.gamefinish) = CURRENT_DATE
			and g.gamewith in (".GAME_WITH_RANDOM.",".GAME_WITH_FRIENDS.")
			group by gp.playerid
			having COUNT(*) >= 3
		) sub
		join farkle_players p on p.playerid = sub.playerid
		order by (sub.players_beaten::numeric / sub.games_played) desc, sub.win_pct desc
		LIMIT $maxDataRows) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);

	// Today's best single rounds (highest round scores)
	$sql = "select t1.*, ROW_NUMBER() OVER () as lbrank from
		(select 6 as lbindex, a.playerid, COALESCE(a.fullname, a.username) as username, a.playerlevel,
		r.roundscore as first_int, 0 as second_int, null as first_string, null as second_string
		from farkle_rounds r
		join farkle_players a on a.playerid = r.playerid
		where DATE(r.rounddatetime) = CURRENT_DATE
		and r.roundscore > 0
		order by r.roundscore desc LIMIT $maxDataRows) t1";
	$insert_sql = "insert into farkle_lbdata ($sql)";
	$result = db_command($insert_sql);

	// Give the MVP achievement to the top player
	$sql = "select playerid from farkle_lbdata where lbindex=2 and lbrank=1";
	$mvpPlayerid = db_select_query( $sql, SQL_SINGLE_VALUE );
	if( $mvpPlayerid )
		Ach_AwardAchievement( $mvpPlayerid, ACH_LB_HIGHESTRND );

}
?>
