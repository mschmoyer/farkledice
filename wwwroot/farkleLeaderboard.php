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
			$check = db_query("SELECT COUNT(*) FROM siteinfo", [], SQL_SINGLE_VALUE);
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
		$counts = db_query($sql, [], SQL_MULTI_ROW);

		if ($counts && count($counts) > 0) {
			echo "<div style='margin-left:20px;'>Leaderboard entries by category:<br>";
			$category_names = [
				0 => 'Today\'s High Scores',
				1 => 'Today\'s Farklers',
				2 => 'Today\'s Win Ratio',
				3 => 'Wins/Losses',
				4 => 'Highest 10-Round',
				5 => 'Achievement Points',
				6 => 'Today\'s Best Rounds',
				10 => 'Yesterday\'s High Scores',
				11 => 'Yesterday\'s Farklers',
				12 => 'Yesterday\'s Win Ratio (MVP)',
				16 => 'Yesterday\'s Best Rounds'
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
	
	$sql = "SELECT paramvalue FROM siteinfo WHERE paramid = :paramid";
	$dayOfWeek = db_query($sql, [':paramid' => 3], SQL_SINGLE_VALUE);
	$_SESSION['farkle']['lb']['dayOfWeek'] = $dayOfWeek;
	// Today Stats

	$maxRows = 3;
	for( $i=0;$i<3;$i++)
	{
		// Daily stats: high scores (0), farkles (1), win ratio (2)
		$sql = "SELECT username, playerid, playerlevel, first_int, second_int, first_string, lbrank
		FROM farkle_lbdata
		WHERE lbindex = :lbindex AND lbrank <= :maxrows
		ORDER BY lbrank";
		$_SESSION['farkle']['lb'][0][$i] = db_query($sql, [':lbindex' => $i, ':maxrows' => $maxRows], SQL_MULTI_ROW);
	}

	// Daily stat: best rounds (lbindex 6, stored at index 3)
	$sql = "SELECT username, playerid, playerlevel, first_int, lbrank
		FROM farkle_lbdata
		WHERE lbindex = :lbindex AND lbrank <= :maxrows
		ORDER BY lbrank";
	$_SESSION['farkle']['lb'][0][3] = db_query($sql, [':lbindex' => 6, ':maxrows' => $maxRows], SQL_MULTI_ROW);

	// Yesterday's stats (lbindex 10, 11, 12, 16)
	// High scores (10), farkles (11), win ratio (12)
	for( $i=0; $i<3; $i++ )
	{
		$yesterdayIndex = $i + 10;  // 10, 11, 12
		$sql = "SELECT username, playerid, playerlevel, first_int, second_int, first_string, lbrank
			FROM farkle_lbdata
			WHERE lbindex = :lbindex AND lbrank <= :maxrows
			ORDER BY lbrank";
		$_SESSION['farkle']['lb']['yesterday'][$i] = db_query($sql, [':lbindex' => $yesterdayIndex, ':maxrows' => $maxRows], SQL_MULTI_ROW);
	}

	// Yesterday's best rounds (lbindex 16)
	$sql = "SELECT username, playerid, playerlevel, first_int, lbrank
		FROM farkle_lbdata
		WHERE lbindex = :lbindex AND lbrank <= :maxrows
		ORDER BY lbrank";
	$_SESSION['farkle']['lb']['yesterday'][3] = db_query($sql, [':lbindex' => 16, ':maxrows' => $maxRows], SQL_MULTI_ROW);

	$maxRows = 25;
	for( $i=1; $i<4; $i++ )
	{
		$lbIndex=$i+2;
		// Wins/Losses / Win/Loss Ratio for players with more than 10 games played
		$sql = "SELECT username, playerid, playerlevel, first_int, second_int, first_string, second_string, lbrank
		FROM farkle_lbdata
		WHERE lbindex = :lbindex AND lbrank <= :maxrows
		ORDER BY lbrank
		LIMIT :limitrows";
		$_SESSION['farkle']['lb'][$i] = db_query($sql, [':lbindex' => $lbIndex, ':maxrows' => $maxRows, ':limitrows' => $maxRows], SQL_MULTI_ROW);

		// This will stuff our player onto the end of the leaderboards if they are not in the top 25 (displacing #25)
		$found=0;
		if( !empty($_SESSION['farkle']['lb'][$i]) && is_array($_SESSION['farkle']['lb'][$i]) ) {
			foreach( $_SESSION['farkle']['lb'][$i] as $j )
				if( $j['playerid'] == $_SESSION['playerid'] )
					$found=1;
		}

		if( $found==0 ) {
			$sql = "SELECT username, playerid, playerlevel, first_int, second_int,
				first_string, second_string, lbrank
				FROM farkle_lbdata WHERE lbindex = :lbindex AND playerid = :playerid";
			$_SESSION['farkle']['lb'][$i][$maxRows-1] = db_query($sql, [':lbindex' => $lbIndex, ':playerid' => $_SESSION['playerid']], SQL_SINGLE_ROW);
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
	$sql = "SELECT (paramvalue::numeric <= EXTRACT(EPOCH FROM NOW()) ) FROM siteinfo WHERE paramid = :paramid";
	$doRefresh = db_query($sql, [':paramid' => 1], SQL_SINGLE_VALUE);

	if( !$doRefresh && !$force )
	{
		// It's too early to refresh the leaderboard data. This user will get whatever we already have.
		return 0;
	}
	else
	{
		// Set the next refresh to 3 minutes from now.
		$sql = "UPDATE siteinfo SET paramvalue = EXTRACT(EPOCH FROM (NOW() + interval '5' minute))
		WHERE paramid = :paramid AND paramname = :paramname";
		$result = db_execute($sql, [':paramid' => 1, ':paramname' => 'last_leaderboard_refresh']);
	}
	
	$i=0;
	$maxRows = 25;
	$playerAgeOutDays = 30;

	// Clear out the old data.
	$sql = "DELETE FROM farkle_lbdata WHERE lbindex IN (3,4,5)";
	$result = db_execute($sql);

	// Wins/Losses / Win/Loss Ratio for players with more than 10 games played
	// PostgreSQL: No need for @rank user variable - use ROW_NUMBER() instead
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () AS lbrank FROM
		(SELECT 3 as lbindex, playerid, username, playerlevel,
		wins as first_int, losses as second_int, TO_CHAR(COALESCE(wins::numeric/NULLIF(losses,0), 1),'FM999999990.00') as first_string,
		null as second_string
		FROM farkle_players
		ORDER BY wins desc) t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Highest round
	// PostgreSQL: No need for @rank user variable - use ROW_NUMBER() instead
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () AS lbrank FROM
		(SELECT 4 as lbindex, playerid, username, playerlevel,
		highest10Round as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players
		WHERE highest10Round IS NOT NULL
		ORDER BY farkle_players.highest10Round desc) t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Achievement Points
	// PostgreSQL: No need for @rank user variable - use ROW_NUMBER() instead
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 5 as lbindex, a.playerid, a.username, a.playerlevel,
		sum(worth) as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_achievements_players b, farkle_achievements c
		WHERE a.playerid=b.playerid and b.achievementid=c.achievementid
		GROUP BY a.playerid, a.username, a.playerlevel
		ORDER BY first_int desc) t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	return 1;
}

// Called from a nightly cron job
function Leaderboard_RefreshDaily()
{
	$maxDataRows = 35;

	// Delete both today's data (0,1,2,6) and yesterday's data (10,11,12,16)
	$sql = "DELETE FROM farkle_lbdata WHERE lbindex IN (0,1,2,6,10,11,12,16)";
	$result = db_execute($sql);

	// Update the day of week (Central Time).
	$sql = "UPDATE siteinfo SET paramvalue = TO_CHAR(NOW() AT TIME ZONE 'America/Chicago', 'Day, Mon DD') WHERE paramid = :paramid";
	$result = db_execute($sql, [':paramid' => 3]);

	// Today Stats (all date comparisons use Central Time)
	// Highest game scores today
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 0 as lbindex, a.playerid, a.username, a.playerlevel,
		playerscore as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_games_players b
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
		ORDER BY playerscore desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Top farklers today (rounds with zero score)
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 1 as lbindex, a.playerid, a.username, a.playerlevel,
		count(*) as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_games_players b, farkle_rounds c
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
		AND a.playerid=c.playerid AND b.gameid=c.gameid AND c.roundscore=0
		GROUP BY a.username, a.playerid, a.playerlevel
		ORDER BY first_int desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Today's best win ratio (weighted by players beaten, min 3 games to qualify)
	// first_int = players beaten, second_int = games played, first_string = "ratio (X beaten)"
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 2 as lbindex, sub.playerid,
			p.username, p.playerlevel,
			sub.players_beaten as first_int,
			sub.games_played as second_int,
			ROUND(sub.players_beaten::numeric / sub.games_played, 2) || ' (' || sub.players_beaten || ' beaten)' as first_string,
			null as second_string
		FROM (
			SELECT gp.playerid,
				SUM(CASE WHEN g.winningplayer = gp.playerid
					THEN (SELECT COUNT(*) FROM farkle_games_players x WHERE x.gameid = g.gameid) - 1
					ELSE 0 END) as players_beaten,
				COUNT(*) as games_played
			FROM farkle_games g
			JOIN farkle_games_players gp on g.gameid = gp.gameid
			WHERE (g.gamefinish AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
			AND g.gamewith in (".GAME_WITH_RANDOM.",".GAME_WITH_FRIENDS.")
			GROUP BY gp.playerid
			HAVING COUNT(*) >= 3
		) sub
		JOIN farkle_players p on p.playerid = sub.playerid
		ORDER BY (sub.players_beaten::numeric / sub.games_played) desc, sub.players_beaten desc
		LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Today's best single rounds (highest round scores)
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 6 as lbindex, a.playerid, a.username, a.playerlevel,
		r.roundscore as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_rounds r
		JOIN farkle_players a on a.playerid = r.playerid
		WHERE (r.rounddatetime AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
		AND r.roundscore > 0
		ORDER BY r.roundscore desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// ============================================================
	// YESTERDAY'S STATS (lbindex 10, 11, 12, 16)
	// ============================================================

	// Yesterday's highest game scores (lbindex 10)
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 10 as lbindex, a.playerid, a.username, a.playerlevel,
		playerscore as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_games_players b
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
		ORDER BY playerscore desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Yesterday's top farklers (lbindex 11)
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 11 as lbindex, a.playerid, a.username, a.playerlevel,
		count(*) as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_games_players b, farkle_rounds c
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
		AND a.playerid=c.playerid AND b.gameid=c.gameid AND c.roundscore=0
		GROUP BY a.username, a.playerid, a.playerlevel
		ORDER BY first_int desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Yesterday's best win ratio with Bayesian scoring (lbindex 12) - used for MVP
	// Bayesian formula: (n / (n + m)) * observed_ratio + (m / (n + m)) * prior
	// where n = games played, m = confidence parameter (10), prior = 0.5
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 12 as lbindex, sub.playerid,
			p.username, p.playerlevel,
			sub.players_beaten as first_int,
			sub.games_played as second_int,
			ROUND(sub.players_beaten::numeric / sub.games_played, 2) || ' (' || sub.players_beaten || ' beaten)' as first_string,
			ROUND(
				(sub.games_played::numeric / (sub.games_played + 10)) * (sub.players_beaten::numeric / sub.games_played)
				+ (10.0 / (sub.games_played + 10)) * 0.5
			, 3)::text as second_string
		FROM (
			SELECT gp.playerid,
				SUM(CASE WHEN g.winningplayer = gp.playerid
					THEN (SELECT COUNT(*) FROM farkle_games_players x WHERE x.gameid = g.gameid) - 1
					ELSE 0 END) as players_beaten,
				COUNT(*) as games_played
			FROM farkle_games g
			JOIN farkle_games_players gp on g.gameid = gp.gameid
			WHERE (g.gamefinish AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
			AND g.gamewith in (".GAME_WITH_RANDOM.",".GAME_WITH_FRIENDS.")
			GROUP BY gp.playerid
			HAVING COUNT(*) >= 3
		) sub
		JOIN farkle_players p on p.playerid = sub.playerid
		ORDER BY
			(sub.games_played::numeric / (sub.games_played + 10)) * (sub.players_beaten::numeric / sub.games_played)
			+ (10.0 / (sub.games_played + 10)) * 0.5 DESC,
			sub.players_beaten desc
		LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Yesterday's best single rounds (lbindex 16)
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 16 as lbindex, a.playerid, a.username, a.playerlevel,
		r.roundscore as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_rounds r
		JOIN farkle_players a on a.playerid = r.playerid
		WHERE (r.rounddatetime AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
		AND r.roundscore > 0
		ORDER BY r.roundscore desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Give the MVP achievement to yesterday's top player (lbindex=12, Bayesian scored)
	$sql = "SELECT playerid FROM farkle_lbdata WHERE lbindex = :lbindex AND lbrank = :lbrank";
	$mvpPlayerid = db_query($sql, [':lbindex' => 12, ':lbrank' => 1], SQL_SINGLE_VALUE);
	if( $mvpPlayerid )
		Ach_AwardAchievement( $mvpPlayerid, ACH_LB_HIGHESTRND );

}
?>
