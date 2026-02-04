<?php
/*
	farkleLeaderboard.php
	
	Functions related to the various operations on each page (not game logic). 

	13-Jan-2013		mas		Now shows your current player on the leaderboard (even if not in top 25) 
*/
require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleLeaderboardStats.php');
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

	// Use static variable caching instead of session storage
	static $cacheData = null;
	static $cacheTime = 0;

	// CRITICAL: Remove old leaderboard data from sessions (cleanup from previous version)
	if (isset($_SESSION['farkle']['lb'])) {
		unset($_SESSION['farkle']['lb']);
	}

	$i=0;
	$lbData = Array();
	$maxRows = 25;
	$playerAgeOutDays = 30;
	$limitClause = " LIMIT $maxRows";

	// Check to see if leaderboard data needs refreshing.
	Leaderboard_RefreshData();

	// Return cached data if it was recorded in the last 3 minutes.
	if( $cacheData !== null && $cacheTime > 0 )
	{
		if( (time() - $cacheTime) < 60*3 && !$g_leaderboardDirty ) // 3 minutes
		{
			return $cacheData;
		}
	}

	$g_leaderboardDirty = 0; // No longer dirty since we just gave you data.

	// Initialize cache array
	$lbCache = array(); 
	
	$sql = "SELECT paramvalue FROM siteinfo WHERE paramid = :paramid";
	$dayOfWeek = db_query($sql, [':paramid' => 3], SQL_SINGLE_VALUE);
	$lbCache['dayOfWeek'] = $dayOfWeek;
	// Today Stats

	$maxRows = 3;
	for( $i=0;$i<3;$i++)
	{
		// Daily stats: high scores (0), farkles (1), win ratio (2)
		$sql = "SELECT username, playerid, playerlevel, first_int, second_int, first_string, lbrank
		FROM farkle_lbdata
		WHERE lbindex = :lbindex AND lbrank <= :maxrows
		ORDER BY lbrank";
		$lbCache[0][$i] = db_query($sql, [':lbindex' => $i, ':maxrows' => $maxRows], SQL_MULTI_ROW);
	}

	// Daily stat: best rounds (lbindex 6, stored at index 3)
	$sql = "SELECT username, playerid, playerlevel, first_int, lbrank
		FROM farkle_lbdata
		WHERE lbindex = :lbindex AND lbrank <= :maxrows
		ORDER BY lbrank";
	$lbCache[0][3] = db_query($sql, [':lbindex' => 6, ':maxrows' => $maxRows], SQL_MULTI_ROW);

	// Yesterday's stats (lbindex 10, 11, 12, 16)
	// High scores (10), farkles (11), win ratio (12)
	for( $i=0; $i<3; $i++ )
	{
		$yesterdayIndex = $i + 10;  // 10, 11, 12
		$sql = "SELECT username, playerid, playerlevel, first_int, second_int, first_string, lbrank
			FROM farkle_lbdata
			WHERE lbindex = :lbindex AND lbrank <= :maxrows
			ORDER BY lbrank";
		$lbCache['yesterday'][$i] = db_query($sql, [':lbindex' => $yesterdayIndex, ':maxrows' => $maxRows], SQL_MULTI_ROW);
	}

	// Yesterday's best rounds (lbindex 16)
	$sql = "SELECT username, playerid, playerlevel, first_int, lbrank
		FROM farkle_lbdata
		WHERE lbindex = :lbindex AND lbrank <= :maxrows
		ORDER BY lbrank";
	$lbCache['yesterday'][3] = db_query($sql, [':lbindex' => 16, ':maxrows' => $maxRows], SQL_MULTI_ROW);

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
		$lbCache[$i] = db_query($sql, [':lbindex' => $lbIndex, ':maxrows' => $maxRows, ':limitrows' => $maxRows], SQL_MULTI_ROW);

		// This will stuff our player onto the end of the leaderboards if they are not in the top 25 (displacing #25)
		$found=0;
		if( !empty($lbCache[$i]) && is_array($lbCache[$i]) ) {
			foreach( $lbCache[$i] as $j )
				if( $j['playerid'] == $_SESSION['playerid'] )
					$found=1;
		}

		if( $found==0 ) {
			$sql = "SELECT username, playerid, playerlevel, first_int, second_int,
				first_string, second_string, lbrank
				FROM farkle_lbdata WHERE lbindex = :lbindex AND playerid = :playerid";
			$lbCache[$i][$maxRows-1] = db_query($sql, [':lbindex' => $lbIndex, ':playerid' => $_SESSION['playerid']], SQL_SINGLE_ROW);
		}

		if( $i==2 )
		{
			// Award the highest 10-round award.
			if( isset($lbCache[$i][0]['playerid']) )
				Ach_AwardAchievement( $lbCache[$i][0]['playerid'], ACH_LB_HIGHESTRND );
		}
	}

	// Store in static cache
	$cacheData = $lbCache;
	$cacheTime = time();

	return $lbCache;
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
	// Note: Timestamps are stored as UTC in 'timestamp without time zone' columns.
	// We must first interpret them as UTC, then convert to Chicago time.
	// Highest game scores today
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 0 as lbindex, a.playerid, a.username, a.playerlevel,
		playerscore as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_games_players b
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
		ORDER BY playerscore desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Top farklers today (rounds with zero score)
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 1 as lbindex, a.playerid, a.username, a.playerlevel,
		count(*) as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_games_players b, farkle_rounds c
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
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
			WHERE (g.gamefinish AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
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
		WHERE (r.rounddatetime AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date
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
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
		ORDER BY playerscore desc LIMIT " . (int)$maxDataRows . ") t1";
	$insert_sql = "INSERT INTO farkle_lbdata ($sql)";
	$result = db_execute($insert_sql);

	// Yesterday's top farklers (lbindex 11)
	$sql = "SELECT t1.*, ROW_NUMBER() OVER () as lbrank FROM
		(SELECT 11 as lbindex, a.playerid, a.username, a.playerlevel,
		count(*) as first_int, 0 as second_int, null as first_string, null as second_string
		FROM farkle_players a, farkle_games_players b, farkle_rounds c
		WHERE a.playerid=b.playerid AND (b.lastplayed AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
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
			WHERE (g.gamefinish AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
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
		WHERE (r.rounddatetime AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date = (NOW() AT TIME ZONE 'America/Chicago')::date - INTERVAL '1 day'
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

// ============================================================
// Leaderboard 2.0: Daily Score Functions
// ============================================================

/**
 * Record an eligible game for daily leaderboard tracking.
 * Called after each game finishes for every player in the game.
 *
 * @param int $playerId The player to record for
 * @param int $gameId The completed game
 * @param int $score The player's final score
 * @param int $roundsPlayed Number of rounds played
 * @param int $gameWith Game type (0=random, 1=friends, 2=solo)
 */
function Leaderboard_RecordEligibleGame($playerId, $gameId, $score, $roundsPlayed, $gameWith)
{
	// Skip solo games
	if ($gameWith == GAME_WITH_SOLO) {
		return;
	}

	// Skip games where any opponent is a bot
	$sql = "SELECT COUNT(*) FROM farkle_games_players gp
		JOIN farkle_players p ON gp.playerid = p.playerid
		WHERE gp.gameid = :gameid AND p.is_bot = true AND gp.playerid != :playerid";
	$botCount = db_query($sql, [':gameid' => $gameId, ':playerid' => $playerId], SQL_SINGLE_VALUE);
	if ($botCount > 0) {
		return;
	}

	// Skip low scores or very short games
	if ($score < 1000 || $roundsPlayed < 3) {
		return;
	}

	// Get today's date in Central Time
	$today = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE", [], SQL_SINGLE_VALUE);

	// Atomically compute game_seq and insert in a single statement to avoid race conditions
	$sql = "INSERT INTO farkle_lb_daily_games (playerid, gameid, lb_date, game_seq, game_score, counted)
		SELECT :playerid, :gameid, :lb_date,
			COALESCE(MAX(game_seq), 0) + 1,
			:game_score,
			COALESCE(MAX(game_seq), 0) + 1 <= 20
		FROM farkle_lb_daily_games
		WHERE playerid = :playerid2 AND lb_date = :lb_date2
		ON CONFLICT (playerid, gameid) DO NOTHING";
	db_execute($sql, [
		':playerid' => $playerId,
		':playerid2' => $playerId,
		':gameid' => $gameId,
		':lb_date' => $today,
		':lb_date2' => $today,
		':game_score' => $score
	]);

	// Recompute daily score
	Leaderboard_RecomputeDailyScore($playerId, $today);
}

/**
 * Recompute a player's daily leaderboard score for a given date.
 * Takes the top 10 scores from their first 20 eligible games.
 *
 * @param int $playerId The player
 * @param string $date The date (YYYY-MM-DD)
 */
function Leaderboard_RecomputeDailyScore($playerId, $date)
{
	// Get top 10 scores from first 20 games
	$sql = "SELECT game_score FROM farkle_lb_daily_games
		WHERE playerid = :playerid AND lb_date = :lb_date AND game_seq <= 20
		ORDER BY game_score DESC LIMIT 10";
	$topScores = db_query($sql, [':playerid' => $playerId, ':lb_date' => $date], SQL_MULTI_ROW);

	$top10Score = 0;
	if ($topScores) {
		foreach ($topScores as $row) {
			$top10Score += (int)$row['game_score'];
		}
	}

	// Get total games played (within 20-game cap)
	$sql = "SELECT COUNT(*) FROM farkle_lb_daily_games
		WHERE playerid = :playerid AND lb_date = :lb_date AND game_seq <= 20";
	$gamesPlayed = (int)db_query($sql, [':playerid' => $playerId, ':lb_date' => $date], SQL_SINGLE_VALUE);

	$qualifies = ($gamesPlayed >= 3);

	// Update counted flags: first reset all, then mark top 10
	db_execute("UPDATE farkle_lb_daily_games SET counted = FALSE WHERE playerid = :playerid AND lb_date = :lb_date",
		[':playerid' => $playerId, ':lb_date' => $date]);

	db_execute("UPDATE farkle_lb_daily_games SET counted = TRUE WHERE id IN (
		SELECT id FROM farkle_lb_daily_games
		WHERE playerid = :playerid AND lb_date = :lb_date AND game_seq <= 20
		ORDER BY game_score DESC LIMIT 10
	)", [':playerid' => $playerId, ':lb_date' => $date]);

	// Upsert into daily scores
	$sql = "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
		VALUES (:playerid, :lb_date, :games_played, :top10_score, :qualifies)
		ON CONFLICT (playerid, lb_date) DO UPDATE SET
			games_played = EXCLUDED.games_played,
			top10_score = EXCLUDED.top10_score,
			qualifies = EXCLUDED.qualifies";
	db_execute($sql, [
		':playerid' => $playerId,
		':lb_date' => $date,
		':games_played' => $gamesPlayed,
		':top10_score' => $top10Score,
		':qualifies' => $qualifies ? 'true' : 'false'
	]);
}

/**
 * Recompute weekly scores for all players who have daily scores this week.
 * Takes each player's top 5 qualifying daily scores from the current week (Mon-Sun).
 * Called hourly from cron.
 */
function Leaderboard_ComputeWeeklyScores()
{
	// Get Monday of current week and end of week (next Monday) in Central Time
	$weekStart = db_query("SELECT date_trunc('week', (NOW() AT TIME ZONE 'America/Chicago'))::DATE", [], SQL_SINGLE_VALUE);
	$weekEnd = db_query("SELECT (date_trunc('week', (NOW() AT TIME ZONE 'America/Chicago')) + INTERVAL '7 days')::DATE", [], SQL_SINGLE_VALUE);

	$sql = "
	INSERT INTO farkle_lb_weekly_scores (playerid, week_start, daily_scores_used, top5_score, qualifies)
	SELECT
		sub.playerid,
		:week_start,
		sub.days_used,
		sub.top5,
		(sub.days_used >= 3) as qualifies
	FROM (
		SELECT
			playerid,
			COUNT(*) as days_used,
			SUM(daily_score) as top5
		FROM (
			SELECT
				playerid,
				top10_score as daily_score,
				ROW_NUMBER() OVER (PARTITION BY playerid ORDER BY top10_score DESC) as rn
			FROM farkle_lb_daily_scores
			WHERE lb_date >= :week_start2
			  AND lb_date < :week_end
			  AND qualifies = TRUE
		) ranked
		WHERE rn <= 5
		GROUP BY playerid
	) sub
	ON CONFLICT (playerid, week_start) DO UPDATE SET
		daily_scores_used = EXCLUDED.daily_scores_used,
		top5_score = EXCLUDED.top5_score,
		qualifies = EXCLUDED.qualifies
	";

	db_execute($sql, [
		':week_start' => $weekStart,
		':week_start2' => $weekStart,
		':week_end' => $weekEnd
	]);
}

/**
 * Recompute all-time leaderboard scores for all players from their complete daily score history.
 * Called nightly from cron.
 */
function Leaderboard_ComputeAllTimeScores()
{
	// Compute daily-based stats (kept for historical tracking)
	$sql = "
	INSERT INTO farkle_lb_alltime (playerid, qualifying_days, total_daily_score, avg_daily_score, best_day_score, qualifies, last_updated)
	SELECT
		playerid,
		COUNT(*) as qualifying_days,
		SUM(top10_score) as total_daily_score,
		AVG(top10_score) as avg_daily_score,
		MAX(top10_score) as best_day_score,
		FALSE,
		NOW()
	FROM farkle_lb_daily_scores
	WHERE qualifies = TRUE
	GROUP BY playerid
	ON CONFLICT (playerid) DO UPDATE SET
		qualifying_days = EXCLUDED.qualifying_days,
		total_daily_score = EXCLUDED.total_daily_score,
		avg_daily_score = EXCLUDED.avg_daily_score,
		best_day_score = EXCLUDED.best_day_score,
		last_updated = NOW()
	";
	db_execute($sql);

	// Compute per-game stats (primary metric: avg game score, qualifying = 50+ games)
	$sql = "
	UPDATE farkle_lb_alltime a SET
		avg_game_score = sub.avg_game_score,
		best_game_score = sub.best_game_score,
		total_games = sub.total_games,
		qualifies = (sub.total_games >= 50)
	FROM (
		SELECT playerid,
			ROUND(AVG(game_score), 2) as avg_game_score,
			MAX(game_score) as best_game_score,
			COUNT(*) as total_games
		FROM farkle_lb_daily_games
		GROUP BY playerid
	) sub
	WHERE a.playerid = sub.playerid
	";
	db_execute($sql);
}

/**
 * Snapshot current ranks into prev_rank for movement arrows.
 * For daily: copies yesterday's rank as prev_rank on today's rows.
 * For weekly: snapshots current week ranks as prev_rank.
 * For alltime: snapshots current ranks as prev_rank.
 * Called nightly from cron.
 */
function Leaderboard_SnapshotRanks()
{
	$today = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE", [], SQL_SINGLE_VALUE);
	$yesterday = db_query("SELECT ((NOW() AT TIME ZONE 'America/Chicago')::DATE - INTERVAL '1 day')::DATE", [], SQL_SINGLE_VALUE);

	// Update today's daily prev_rank from yesterday's rank
	$sql = "
	UPDATE farkle_lb_daily_scores ds_today
	SET prev_rank = ds_yesterday.final_rank
	FROM (
		SELECT playerid, ROW_NUMBER() OVER (ORDER BY top10_score DESC) as final_rank
		FROM farkle_lb_daily_scores
		WHERE lb_date = :yesterday AND qualifies = TRUE
	) ds_yesterday
	WHERE ds_today.playerid = ds_yesterday.playerid
	  AND ds_today.lb_date = :today
	";
	db_execute($sql, [':yesterday' => $yesterday, ':today' => $today]);

	// Snapshot weekly ranks: store current rank as prev_rank for current week
	$weekStart = db_query("SELECT date_trunc('week', (NOW() AT TIME ZONE 'America/Chicago'))::DATE", [], SQL_SINGLE_VALUE);
	$prevWeekStart = db_query("SELECT (date_trunc('week', (NOW() AT TIME ZONE 'America/Chicago')) - INTERVAL '7 days')::DATE", [], SQL_SINGLE_VALUE);

	$sql = "
	UPDATE farkle_lb_weekly_scores ws_current
	SET prev_rank = ws_prev.final_rank
	FROM (
		SELECT playerid, ROW_NUMBER() OVER (ORDER BY top5_score DESC) as final_rank
		FROM farkle_lb_weekly_scores
		WHERE week_start = :prev_week AND qualifies = TRUE
	) ws_prev
	WHERE ws_current.playerid = ws_prev.playerid
	  AND ws_current.week_start = :current_week
	";
	db_execute($sql, [':prev_week' => $prevWeekStart, ':current_week' => $weekStart]);

	// Snapshot alltime ranks: copy current computed rank into prev_rank
	$sql = "
	UPDATE farkle_lb_alltime at_main
	SET prev_rank = ranked.current_rank
	FROM (
		SELECT playerid, ROW_NUMBER() OVER (ORDER BY avg_daily_score DESC) as current_rank
		FROM farkle_lb_alltime
		WHERE qualifies = TRUE
	) ranked
	WHERE at_main.playerid = ranked.playerid
	";
	db_execute($sql);
}

/**
 * Clean up old daily game detail records.
 * Keeps 90 days of game-level detail; daily scores stay forever.
 * Called nightly from cron.
 */
function Leaderboard_Cleanup()
{
	db_execute("DELETE FROM farkle_lb_daily_games WHERE lb_date < (NOW() AT TIME ZONE 'America/Chicago')::DATE - INTERVAL '90 days'");
}

/**
 * Get a player's daily leaderboard progress for today.
 *
 * @param int $playerId The player
 * @return array Associative array with games_played, games_max, daily_score, top_scores, qualifies
 */
function Leaderboard_GetDailyProgress($playerId)
{
	// Get today's date in Central Time
	$today = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE", [], SQL_SINGLE_VALUE);

	// Get daily score summary
	$sql = "SELECT games_played, top10_score, qualifies FROM farkle_lb_daily_scores
		WHERE playerid = :playerid AND lb_date = :lb_date";
	$summary = db_query($sql, [':playerid' => $playerId, ':lb_date' => $today], SQL_SINGLE_ROW);

	// Get individual game scores (within 20-game cap, sorted by score desc)
	$sql = "SELECT game_score, counted FROM farkle_lb_daily_games
		WHERE playerid = :playerid AND lb_date = :lb_date AND game_seq <= 20
		ORDER BY game_score DESC";
	$gameScores = db_query($sql, [':playerid' => $playerId, ':lb_date' => $today], SQL_MULTI_ROW);

	if (!$summary) {
		return [
			'games_played' => 0,
			'games_max' => 20,
			'daily_score' => 0,
			'top_scores' => [],
			'qualifies' => false
		];
	}

	$topScores = [];
	if ($gameScores) {
		foreach ($gameScores as $gs) {
			$topScores[] = (int)$gs['game_score'];
		}
	}

	return [
		'games_played' => (int)$summary['games_played'],
		'games_max' => 20,
		'daily_score' => (int)$summary['top10_score'],
		'top_scores' => $topScores,
		'qualifies' => (bool)$summary['qualifies']
	];
}

// ============================================================
// Leaderboard 2.0: Board Query Functions
// ============================================================

/**
 * Get leaderboard data for a specific tier and scope.
 *
 * @param int $playerId The requesting player
 * @param string $tier 'daily', 'weekly', or 'alltime'
 * @param string $scope 'friends' or 'everyone'
 * @return array Leaderboard response with entries and myScore
 */
/**
 * Build the featured stat data for the leaderboard response.
 */
function Leaderboard_BuildFeaturedStat($date)
{
	$featured = LeaderboardStats_GetFeaturedStat();
	$topEntries = LeaderboardStats_GetTopForDate($featured['type'], $date, 1);

	$leader = '';
	if (!empty($topEntries)) {
		$top = $topEntries[0];
		$val = is_numeric($top['stat_value']) ? round((float)$top['stat_value'], 1) : $top['stat_value'];
		$leader = $top['username'] . ' — ' . $val;
	}

	return [
		'type' => $featured['type'],
		'title' => $featured['name'],
		'label' => "Today's Featured Stat",
		'leader' => $leader
	];
}

/**
 * Get all player stat values for a given stat type and date.
 * Returns associative array keyed by playerid.
 */
function Leaderboard_GetStatValuesForDate($statType, $date)
{
	$sql = "SELECT playerid, stat_value FROM farkle_lb_stats
		WHERE stat_type = :stat_type AND lb_date = :lb_date";
	$rows = db_query($sql, [':stat_type' => $statType, ':lb_date' => $date], SQL_MULTI_ROW);

	$values = [];
	if ($rows) {
		foreach ($rows as $row) {
			$val = (float)$row['stat_value'];
			// Format: round to 1 decimal for rates/consistency, integer for scores/streaks
			if ($statType === 'farkle_rate' || $statType === 'consistency') {
				$values[(int)$row['playerid']] = round($val, 1);
			} else {
				$values[(int)$row['playerid']] = (int)$val;
			}
		}
	}
	return $values;
}

function Leaderboard_GetBoard($playerId, $tier, $scope)
{
	// Validate inputs
	if (!in_array($tier, ['daily', 'weekly', 'alltime'])) {
		$tier = 'daily';
	}
	if (!in_array($scope, ['friends', 'everyone'])) {
		$scope = 'friends';
	}

	switch ($tier) {
		case 'daily':
			return Leaderboard_GetBoard_Daily($playerId, $scope);
		case 'weekly':
			return Leaderboard_GetBoard_Weekly($playerId, $scope);
		case 'alltime':
			return Leaderboard_GetBoard_Alltime($playerId, $scope);
		default:
			return ['entries' => [], 'myScore' => null, 'tier' => $tier, 'scope' => $scope];
	}
}

/**
 * Get daily leaderboard data.
 *
 * @param int $playerId The requesting player
 * @param string $scope 'friends' or 'everyone'
 * @return array
 */
function Leaderboard_GetBoard_Daily($playerId, $scope)
{
	$today = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE", [], SQL_SINGLE_VALUE);

	if ($scope === 'friends') {
		$sql = "SELECT ds.playerid, p.username, ds.games_played, ds.top10_score, ds.rank, ds.prev_rank
			FROM farkle_lb_daily_scores ds
			JOIN farkle_players p ON ds.playerid = p.playerid
			WHERE ds.lb_date = :today
			  AND ds.qualifies = TRUE
			  AND (ds.playerid = :pid OR ds.playerid IN (
			    SELECT CASE WHEN f.sourceid = :pid2 THEN f.friendid ELSE f.sourceid END
			    FROM farkle_friends f
			    WHERE (f.sourceid = :pid3 OR f.friendid = :pid4) AND f.status = 'accepted' AND f.removed = 0
			  ))
			ORDER BY ds.top10_score DESC
			LIMIT 25";
		$rows = db_query($sql, [
			':today' => $today,
			':pid' => $playerId,
			':pid2' => $playerId,
			':pid3' => $playerId,
			':pid4' => $playerId
		], SQL_MULTI_ROW);
	} else {
		$sql = "SELECT ds.playerid, p.username, ds.games_played, ds.top10_score, ds.rank, ds.prev_rank
			FROM farkle_lb_daily_scores ds
			JOIN farkle_players p ON ds.playerid = p.playerid
			WHERE ds.lb_date = :today
			  AND ds.qualifies = TRUE
			ORDER BY ds.top10_score DESC
			LIMIT 25";
		$rows = db_query($sql, [':today' => $today], SQL_MULTI_ROW);
	}

	$entries = [];
	if ($rows) {
		$rank = 1;
		foreach ($rows as $row) {
			$entries[] = [
				'playerId' => (int)$row['playerid'],
				'username' => $row['username'],
				'score' => (int)$row['top10_score'],
				'gamesPlayed' => (int)$row['games_played'],
				'rank' => $rank,
				'prevRank' => $row['prev_rank'] !== null ? (int)$row['prev_rank'] : null,
				'isMe' => ((int)$row['playerid'] === (int)$playerId)
			];
			$rank++;
		}
	}

	// Get myScore separately if not in top 25
	$myScore = Leaderboard_GetMyScore_Daily($playerId, $today, $entries, $scope);

	// Get featured stat and attach per-player values
	$featuredStat = Leaderboard_BuildFeaturedStat($today);
	$statValues = Leaderboard_GetStatValuesForDate($featuredStat['type'], $today);
	for ($i = 0; $i < count($entries); $i++) {
		$pid = $entries[$i]['playerId'];
		$entries[$i]['statValue'] = isset($statValues[$pid]) ? $statValues[$pid] : null;
	}
	if ($myScore) {
		$pid = $myScore['playerId'];
		$myScore['statValue'] = isset($statValues[$pid]) ? $statValues[$pid] : null;
	}

	return [
		'entries' => $entries,
		'myScore' => $myScore,
		'featuredStat' => $featuredStat,
		'tier' => 'daily',
		'scope' => $scope
	];
}

/**
 * Get the current player's daily score data.
 *
 * @param int $playerId
 * @param string $today
 * @param array $entries Already-fetched entries to check if player is included
 * @param string $scope
 * @return array
 */
function Leaderboard_GetMyScore_Daily($playerId, $today, $entries, $scope)
{
	// Check if already in the entries
	foreach ($entries as $entry) {
		if ($entry['isMe']) {
			return $entry;
		}
	}

	// Query the player's own data
	$sql = "SELECT ds.playerid, p.username, ds.games_played, ds.top10_score, ds.qualifies, ds.rank, ds.prev_rank
		FROM farkle_lb_daily_scores ds
		JOIN farkle_players p ON ds.playerid = p.playerid
		WHERE ds.playerid = :playerid AND ds.lb_date = :today";
	$row = db_query($sql, [':playerid' => $playerId, ':today' => $today], SQL_SINGLE_ROW);

	if (!$row) {
		return [
			'playerId' => (int)$playerId,
			'username' => isset($_SESSION['username']) ? $_SESSION['username'] : '',
			'score' => 0,
			'gamesPlayed' => 0,
			'rank' => null,
			'prevRank' => null,
			'isMe' => true
		];
	}

	// Compute the player's actual rank within the chosen scope
	if ($scope === 'everyone') {
		$sql = "SELECT COUNT(*) + 1 FROM farkle_lb_daily_scores
			WHERE lb_date = :today AND qualifies = TRUE AND top10_score > :score";
		$myRank = (int)db_query($sql, [':today' => $today, ':score' => (int)$row['top10_score']], SQL_SINGLE_VALUE);
	} else {
		$sql = "SELECT COUNT(*) + 1 FROM farkle_lb_daily_scores ds
			WHERE ds.lb_date = :today AND ds.qualifies = TRUE AND ds.top10_score > :score
			AND (ds.playerid = :pid OR ds.playerid IN (
			    SELECT CASE WHEN f.sourceid = :pid2 THEN f.friendid ELSE f.sourceid END
			    FROM farkle_friends f
			    WHERE (f.sourceid = :pid3 OR f.friendid = :pid4) AND f.status = 'accepted' AND f.removed = 0
			))";
		$myRank = (int)db_query($sql, [
			':today' => $today,
			':score' => (int)$row['top10_score'],
			':pid' => $playerId,
			':pid2' => $playerId,
			':pid3' => $playerId,
			':pid4' => $playerId
		], SQL_SINGLE_VALUE);
	}

	return [
		'playerId' => (int)$row['playerid'],
		'username' => $row['username'],
		'score' => (int)$row['top10_score'],
		'gamesPlayed' => (int)$row['games_played'],
		'rank' => (bool)$row['qualifies'] ? $myRank : null,
		'prevRank' => $row['prev_rank'] !== null ? (int)$row['prev_rank'] : null,
		'isMe' => true
	];
}

/**
 * Get weekly leaderboard data.
 *
 * @param int $playerId The requesting player
 * @param string $scope 'friends' or 'everyone'
 * @return array
 */
function Leaderboard_GetBoard_Weekly($playerId, $scope)
{
	$weekStart = db_query("SELECT date_trunc('week', (NOW() AT TIME ZONE 'America/Chicago'))::DATE", [], SQL_SINGLE_VALUE);

	if ($scope === 'friends') {
		$sql = "SELECT ws.playerid, p.username, ws.daily_scores_used, ws.top5_score, ws.rank, ws.prev_rank
			FROM farkle_lb_weekly_scores ws
			JOIN farkle_players p ON ws.playerid = p.playerid
			WHERE ws.week_start = :week_start
			  AND ws.qualifies = TRUE
			  AND (ws.playerid = :pid OR ws.playerid IN (
			    SELECT CASE WHEN f.sourceid = :pid2 THEN f.friendid ELSE f.sourceid END
			    FROM farkle_friends f
			    WHERE (f.sourceid = :pid3 OR f.friendid = :pid4) AND f.status = 'accepted' AND f.removed = 0
			  ))
			ORDER BY ws.top5_score DESC
			LIMIT 25";
		$rows = db_query($sql, [
			':week_start' => $weekStart,
			':pid' => $playerId,
			':pid2' => $playerId,
			':pid3' => $playerId,
			':pid4' => $playerId
		], SQL_MULTI_ROW);
	} else {
		$sql = "SELECT ws.playerid, p.username, ws.daily_scores_used, ws.top5_score, ws.rank, ws.prev_rank
			FROM farkle_lb_weekly_scores ws
			JOIN farkle_players p ON ws.playerid = p.playerid
			WHERE ws.week_start = :week_start
			  AND ws.qualifies = TRUE
			ORDER BY ws.top5_score DESC
			LIMIT 25";
		$rows = db_query($sql, [':week_start' => $weekStart], SQL_MULTI_ROW);
	}

	$entries = [];
	if ($rows) {
		$rank = 1;
		foreach ($rows as $row) {
			$entries[] = [
				'playerId' => (int)$row['playerid'],
				'username' => $row['username'],
				'score' => (int)$row['top5_score'],
				'daysPlayed' => (int)$row['daily_scores_used'],
				'rank' => $rank,
				'prevRank' => $row['prev_rank'] !== null ? (int)$row['prev_rank'] : null,
				'isMe' => ((int)$row['playerid'] === (int)$playerId)
			];
			$rank++;
		}
	}

	// Get myScore separately if not in top 25
	$myScore = Leaderboard_GetMyScore_Weekly($playerId, $weekStart, $entries, $scope);

	// Get day-by-day breakdown for current player
	$dayScores = Leaderboard_GetWeekDayScores($playerId, $weekStart);

	// Get featured stat and attach per-player values
	$today = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE", [], SQL_SINGLE_VALUE);
	$featuredStat = Leaderboard_BuildFeaturedStat($today);
	$statValues = Leaderboard_GetStatValuesForDate($featuredStat['type'], $today);
	for ($i = 0; $i < count($entries); $i++) {
		$pid = $entries[$i]['playerId'];
		$entries[$i]['statValue'] = isset($statValues[$pid]) ? $statValues[$pid] : null;
	}
	if ($myScore) {
		$pid = $myScore['playerId'];
		$myScore['statValue'] = isset($statValues[$pid]) ? $statValues[$pid] : null;
	}

	return [
		'entries' => $entries,
		'myScore' => $myScore,
		'dayScores' => $dayScores,
		'featuredStat' => $featuredStat,
		'tier' => 'weekly',
		'scope' => $scope
	];
}

/**
 * Get the current player's weekly score data.
 *
 * @param int $playerId
 * @param string $weekStart
 * @param array $entries
 * @param string $scope
 * @return array
 */
function Leaderboard_GetMyScore_Weekly($playerId, $weekStart, $entries, $scope)
{
	// Check if already in the entries
	foreach ($entries as $entry) {
		if ($entry['isMe']) {
			return $entry;
		}
	}

	$sql = "SELECT ws.playerid, p.username, ws.daily_scores_used, ws.top5_score, ws.qualifies, ws.rank, ws.prev_rank
		FROM farkle_lb_weekly_scores ws
		JOIN farkle_players p ON ws.playerid = p.playerid
		WHERE ws.playerid = :playerid AND ws.week_start = :week_start";
	$row = db_query($sql, [':playerid' => $playerId, ':week_start' => $weekStart], SQL_SINGLE_ROW);

	if (!$row) {
		return [
			'playerId' => (int)$playerId,
			'username' => isset($_SESSION['username']) ? $_SESSION['username'] : '',
			'score' => 0,
			'daysPlayed' => 0,
			'rank' => null,
			'prevRank' => null,
			'isMe' => true
		];
	}

	// Compute actual rank within scope
	if ($scope === 'everyone') {
		$sql = "SELECT COUNT(*) + 1 FROM farkle_lb_weekly_scores
			WHERE week_start = :week_start AND qualifies = TRUE AND top5_score > :score";
		$myRank = (int)db_query($sql, [':week_start' => $weekStart, ':score' => (int)$row['top5_score']], SQL_SINGLE_VALUE);
	} else {
		$sql = "SELECT COUNT(*) + 1 FROM farkle_lb_weekly_scores ws
			WHERE ws.week_start = :week_start AND ws.qualifies = TRUE AND ws.top5_score > :score
			AND (ws.playerid = :pid OR ws.playerid IN (
			    SELECT CASE WHEN f.sourceid = :pid2 THEN f.friendid ELSE f.sourceid END
			    FROM farkle_friends f
			    WHERE (f.sourceid = :pid3 OR f.friendid = :pid4) AND f.status = 'accepted' AND f.removed = 0
			))";
		$myRank = (int)db_query($sql, [
			':week_start' => $weekStart,
			':score' => (int)$row['top5_score'],
			':pid' => $playerId,
			':pid2' => $playerId,
			':pid3' => $playerId,
			':pid4' => $playerId
		], SQL_SINGLE_VALUE);
	}

	return [
		'playerId' => (int)$row['playerid'],
		'username' => $row['username'],
		'score' => (int)$row['top5_score'],
		'daysPlayed' => (int)$row['daily_scores_used'],
		'rank' => (bool)$row['qualifies'] ? $myRank : null,
		'prevRank' => $row['prev_rank'] !== null ? (int)$row['prev_rank'] : null,
		'isMe' => true
	];
}

/**
 * Get day-by-day daily scores for the current week (Mon-Sun) for a player.
 *
 * @param int $playerId
 * @param string $weekStart Monday date of the current week
 * @return array Array of day score objects
 */
function Leaderboard_GetWeekDayScores($playerId, $weekStart)
{
	$today = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE", [], SQL_SINGLE_VALUE);
	$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

	// Get week end date
	$weekEnd = db_query("SELECT (:week_start::DATE + INTERVAL '7 days')::DATE", [':week_start' => $weekStart], SQL_SINGLE_VALUE);

	// Get all daily scores for this player in the current week (Mon through Sun)
	$sql = "SELECT lb_date, top10_score, qualifies FROM farkle_lb_daily_scores
		WHERE playerid = :playerid
		AND lb_date >= :week_start
		AND lb_date < :week_end
		ORDER BY lb_date";
	$rows = db_query($sql, [
		':playerid' => $playerId,
		':week_start' => $weekStart,
		':week_end' => $weekEnd
	], SQL_MULTI_ROW);

	// Index by date for easy lookup
	$scoresByDate = [];
	if ($rows) {
		foreach ($rows as $row) {
			$scoresByDate[$row['lb_date']] = $row;
		}
	}

	// Generate each day of the week using date arithmetic
	$dayDates = db_query("SELECT generate_series(0, 6) as day_offset,
		(:week_start::DATE + generate_series(0, 6) * INTERVAL '1 day')::DATE as day_date",
		[':week_start' => $weekStart], SQL_MULTI_ROW);

	$dayScores = [];
	if ($dayDates) {
		foreach ($dayDates as $idx => $dayRow) {
			$dayDate = $dayRow['day_date'];
			$state = 'future';
			$score = 0;

			if ($dayDate === $today) {
				$state = 'today';
			} elseif ($dayDate < $today) {
				$state = 'played';
			}

			if (isset($scoresByDate[$dayDate])) {
				$score = (int)$scoresByDate[$dayDate]['top10_score'];
				if ($state === 'future') {
					$state = 'played'; // Has data, so it was played
				}
			} elseif ($state === 'played') {
				$state = 'missed'; // Past day with no data
			}

			$dayScores[] = [
				'day' => $dayNames[$idx],
				'date' => $dayDate,
				'score' => $score,
				'state' => $state
			];
		}
	}

	return $dayScores;
}

/**
 * Get all-time leaderboard data.
 *
 * @param int $playerId The requesting player
 * @param string $scope 'friends' or 'everyone'
 * @return array
 */
function Leaderboard_GetBoard_Alltime($playerId, $scope)
{
	if ($scope === 'friends') {
		$sql = "SELECT at.playerid, p.username, at.avg_game_score, at.best_game_score,
				at.total_games, at.rank, at.prev_rank
			FROM farkle_lb_alltime at
			JOIN farkle_players p ON at.playerid = p.playerid
			WHERE at.qualifies = TRUE
			  AND (at.playerid = :pid OR at.playerid IN (
			    SELECT CASE WHEN f.sourceid = :pid2 THEN f.friendid ELSE f.sourceid END
			    FROM farkle_friends f
			    WHERE (f.sourceid = :pid3 OR f.friendid = :pid4) AND f.status = 'accepted' AND f.removed = 0
			  ))
			ORDER BY at.avg_game_score DESC
			LIMIT 25";
		$rows = db_query($sql, [
			':pid' => $playerId,
			':pid2' => $playerId,
			':pid3' => $playerId,
			':pid4' => $playerId
		], SQL_MULTI_ROW);
	} else {
		$sql = "SELECT at.playerid, p.username, at.avg_game_score, at.best_game_score,
				at.total_games, at.rank, at.prev_rank
			FROM farkle_lb_alltime at
			JOIN farkle_players p ON at.playerid = p.playerid
			WHERE at.qualifies = TRUE
			ORDER BY at.avg_game_score DESC
			LIMIT 25";
		$rows = db_query($sql, [], SQL_MULTI_ROW);
	}

	$entries = [];
	if ($rows) {
		$rank = 1;
		foreach ($rows as $row) {
			$entries[] = [
				'playerId' => (int)$row['playerid'],
				'username' => $row['username'],
				'avgGameScore' => round((float)$row['avg_game_score']),
				'bestGameScore' => (int)$row['best_game_score'],
				'totalGames' => (int)$row['total_games'],
				'rank' => $rank,
				'prevRank' => $row['prev_rank'] !== null ? (int)$row['prev_rank'] : null,
				'isMe' => ((int)$row['playerid'] === (int)$playerId)
			];
			$rank++;
		}
	}

	// Get myScore separately if not in top 25
	$myScore = Leaderboard_GetMyScore_Alltime($playerId, $entries, $scope);

	return [
		'entries' => $entries,
		'myScore' => $myScore,
		'tier' => 'alltime',
		'scope' => $scope
	];
}

/**
 * Get the current player's all-time score data.
 *
 * @param int $playerId
 * @param array $entries
 * @param string $scope
 * @return array
 */
function Leaderboard_GetMyScore_Alltime($playerId, $entries, $scope)
{
	// Check if already in the entries
	foreach ($entries as $entry) {
		if ($entry['isMe']) {
			return $entry;
		}
	}

	$sql = "SELECT at.playerid, p.username, at.avg_game_score, at.best_game_score,
			at.total_games, at.qualifies, at.rank, at.prev_rank
		FROM farkle_lb_alltime at
		JOIN farkle_players p ON at.playerid = p.playerid
		WHERE at.playerid = :playerid";
	$row = db_query($sql, [':playerid' => $playerId], SQL_SINGLE_ROW);

	if (!$row) {
		return [
			'playerId' => (int)$playerId,
			'username' => isset($_SESSION['username']) ? $_SESSION['username'] : '',
			'avgGameScore' => 0,
			'bestGameScore' => 0,
			'totalGames' => 0,
			'rank' => null,
			'prevRank' => null,
			'isMe' => true
		];
	}

	// Compute actual rank within scope
	if ($scope === 'everyone') {
		$sql = "SELECT COUNT(*) + 1 FROM farkle_lb_alltime
			WHERE qualifies = TRUE AND avg_game_score > :score";
		$myRank = (int)db_query($sql, [':score' => (float)$row['avg_game_score']], SQL_SINGLE_VALUE);
	} else {
		$sql = "SELECT COUNT(*) + 1 FROM farkle_lb_alltime at
			WHERE at.qualifies = TRUE AND at.avg_game_score > :score
			AND (at.playerid = :pid OR at.playerid IN (
			    SELECT CASE WHEN f.sourceid = :pid2 THEN f.friendid ELSE f.sourceid END
			    FROM farkle_friends f
			    WHERE (f.sourceid = :pid3 OR f.friendid = :pid4) AND f.status = 'accepted' AND f.removed = 0
			))";
		$myRank = (int)db_query($sql, [
			':score' => (float)$row['avg_game_score'],
			':pid' => $playerId,
			':pid2' => $playerId,
			':pid3' => $playerId,
			':pid4' => $playerId
		], SQL_SINGLE_VALUE);
	}

	return [
		'playerId' => (int)$row['playerid'],
		'username' => $row['username'],
		'avgGameScore' => round((float)$row['avg_game_score']),
		'bestGameScore' => (int)$row['best_game_score'],
		'totalGames' => (int)$row['total_games'],
		'rank' => (bool)$row['qualifies'] ? $myRank : null,
		'prevRank' => $row['prev_rank'] !== null ? (int)$row['prev_rank'] : null,
		'isMe' => true
	];
}
/**
 * Get post-game leaderboard feedback for the player.
 * Called after a game finishes to show a toast with the player's daily progress.
 *
 * @param int $playerId The player
 * @return array|null Feedback data or null if not eligible
 */
function Leaderboard_GetPostGameFeedback($playerId)
{
	$today = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE as today", [], SQL_SINGLE_ROW);
	if (!$today) return null;
	$today = $today['today'];

	// Get the player's daily progress
	$progress = Leaderboard_GetDailyProgress($playerId);
	if (!$progress || $progress['games_played'] == 0) return null;

	// Get the last game's rank in their top 10
	$lastGame = db_query(
		"SELECT game_score, game_seq, counted FROM farkle_lb_daily_games WHERE playerid = :pid AND lb_date = :today ORDER BY created_at DESC LIMIT 1",
		[':pid' => $playerId, ':today' => $today],
		SQL_SINGLE_ROW
	);

	if (!$lastGame) return null;

	$rankInTop10 = 0;
	$counted = ($lastGame['counted'] === 't' || $lastGame['counted'] === true || $lastGame['counted'] == 1);
	if ($counted) {
		// Find its rank among top 10
		$topScores = $progress['top_scores'];
		for ($i = 0; $i < count($topScores); $i++) {
			if ($topScores[$i] == $lastGame['game_score']) {
				$rankInTop10 = $i + 1;
				break;
			}
		}
	}

	return [
		'is_eligible' => true,
		'rank_in_top10' => $rankInTop10,
		'games_remaining' => max(0, 20 - $progress['games_played']),
		'daily_score' => $progress['daily_score']
	];
}
?>
