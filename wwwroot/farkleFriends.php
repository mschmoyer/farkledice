<?php
/*
	farkleFriends.php
	
	Functions related to the various operations on each page (not game logic). 
*/

require_once('../includes/baseutil.php');
require_once('dbutil.php');

function AddFriend( $playerid, $identstring, $ident )
{
	if( $ident == 'username' )
	{
		$theIdent = strtolower($identstring);
		$sql = "SELECT min(playerid) FROM farkle_players WHERE lower(username) = :username";

		$friendid = db_query($sql, [':username' => $theIdent], SQL_SINGLE_VALUE);
		if( empty($friendid) )
			return Array('Error' => 'Player not found.');
	}
	else if( $ident == 'email' )
	{
		$theIdent = strtolower($identstring);
		$sql = "SELECT * FROM farkle_players WHERE lower(email) = :email";
		$friendid = db_query($sql, [':email' => $theIdent], SQL_SINGLE_VALUE);
		if( empty($friendid) )
		{
			// No email found. Email this user inviting them to join and play. 
			$subject = $_SESSION['username'] . " wants to play you!";
		
			$message = $_SESSION['username'] . " would like to play you in a game of Farkle. To play this exciting dice game, visit: \r\n" . 
			'http://www.farkledice.com/farkle.php?joinrequestfrom=$playerid\r\n\r\n';				
				
			//TBD: This can be spammy. Limit it somehow. 
			BaseUtil_Debug( "Sending email to $identstring. Subj=[$subject] Msg=[" . strip_tags($message) . "]", 7 );					
			$rc = SendEmail($identstring, $subject, $message);
			//return Array('Error' => 'Farkle Online invitation sent.');
		}
	}
	else if( $ident == 'playerid' )
	{
		$friendid = (int)$identstring;
	}
	
	if( empty($friendid) )
	{
		error_log( "AddFriend - friendid missing. Params: playerid=$playerid, identstring=$identstring, ident=$ident" );
		return Array('Error' => 'No friend selected.');
	}
	
	if( $friendid == $playerid )
	{
		error_log( "AddFriend - friendid equals playerid. Params: playerid=$playerid, identstring=$identstring, ident=$ident" );
		return Array('Error' => 'Cannot friend yourself.');
	}
	
	if( !empty($friendid) )
	{
		$removed = -1;
		$sql = "SELECT removed FROM farkle_friends WHERE sourceid = :sourceid AND friendid = :friendid";
		$removed = db_query($sql, [':sourceid' => $playerid, ':friendid' => $friendid], SQL_SINGLE_VALUE);

		if( $removed == 1 )
		{
			$sql = "UPDATE farkle_friends SET removed = 0 WHERE sourceid = :sourceid AND friendid = :friendid";
			$rc = db_execute($sql, [':sourceid' => $playerid, ':friendid' => $friendid]);
		}
		else if( $removed == 0 )
		{
			$alreadyExists = db_query("SELECT count(*) FROM farkle_friends WHERE sourceid = :sourceid AND friendid = :friendid", [':sourceid' => $playerid, ':friendid' => $friendid], SQL_SINGLE_VALUE);

			if( $alreadyExists )
			{
				return Array('Error' => 'Already friends with this player.');
			}
			else
			{
				$sql = "INSERT INTO farkle_friends (sourceid, friendid) VALUES (:sourceid, :friendid)";
				if( !db_execute($sql, [':sourceid' => $playerid, ':friendid' => $friendid]) )
				{
					// Error already logged by db_execute
					error_log( "Error creating friend" );
				}
			}
		}

		Ach_CheckFriends( $playerid );
		//return $playerid;
	}
	
	$_SESSION['farkle']['friends'] = null; // Clear the friends cache so it re-queries. 
	
	return GetGameFriends( $playerid, true );
}

function RemoveFriend( $playerid, $friendid )
{
	$sql = "SELECT friendid FROM farkle_friends WHERE sourceid = :sourceid AND friendid = :friendid";
	$friendExists = db_query($sql, [':sourceid' => $playerid, ':friendid' => $friendid], SQL_SINGLE_VALUE);

	if( $friendExists )
	{
		$sql = "UPDATE farkle_friends SET removed = 1 WHERE sourceid = :sourceid AND friendid = :friendid";
		db_execute($sql, [':sourceid' => $playerid, ':friendid' => $friendid]);
	}
	else
	{
		$sql = "INSERT INTO farkle_friends (sourceid, friendid, removed) VALUES (:sourceid, :friendid, 1)";
		db_execute($sql, [':sourceid' => $playerid, ':friendid' => $friendid]);
	}

	$_SESSION['farkle']['friends'] = null; // Clear the friends cache so it re-queries.

	return 1;
}

function GetNewGameInfo( $playerid )
{
	$sql = "SELECT count(*) FROM farkle_games a WHERE whostarted = :playerid AND winningplayer = 0";

	$gamesStarted = db_query($sql, [':playerid' => $playerid], SQL_SINGLE_VALUE);
	return Array( GetGameFriends( $playerid ), $gamesStarted );
}

function GetGameFriends( $playerid, $force = false )
{
	BaseUtil_Debug( __FUNCTION__ . " Entered. Playerid=$playerid.", 7 );

	// Use static variable caching with 5-minute TTL
	static $cache = array();
	static $cacheTime = array();
	$cacheTTL = 300; // 5 minutes

	// CRITICAL: Remove old friend data from sessions (cleanup from previous version)
	if (isset($_SESSION['farkle']['friends'])) {
		unset($_SESSION['farkle']['friends']);
	}

	// Check if we have valid cached data for this player
	if( !$force && isset($cache[$playerid]) && isset($cacheTime[$playerid]) )
	{
		if( (time() - $cacheTime[$playerid]) < $cacheTTL )
		{
			BaseUtil_Debug( __FUNCTION__ . " Cached friend data found. Using that.", 7 );
			return $cache[$playerid];
		}
	}

	BaseUtil_Debug( __FUNCTION__ . " No friend data cached. Adding friend data.", 7 );

	// Use numeric comparison for compatibility with smallint columns
	$sql = "SELECT a.username, a.playerid, a.playertitle, a.cardcolor, a.lastplayed
			FROM farkle_players a, farkle_friends b
			WHERE a.playerid = b.friendid AND b.sourceid = :sourceid AND
			a.active = 1 AND b.removed = 0
			ORDER BY lastplayed DESC";

	$players = db_query($sql, [':sourceid' => $playerid], SQL_MULTI_ROW);

	// TBD: Do something if no friends returned.
	if( !isset($players) || empty($players) || count($players) == 0 )
	{
		$players = Array( 'Message' => 'No friends found.' );
	}

	// Store in static cache
	$cache[$playerid] = $players;
	$cacheTime[$playerid] = time();

	return $players;
}

/*
function GetFriends( $playerid )
{

	$sql = "select a.username, a.playerid, a.playertitle, a.cardbg,
		(select count(*) from farkle_games c, farkle_games_players d
			where c.gameid=d.gameid and d.playerid=b.friendid and c.winningplayer=$playerid) as winsagainst,
		(select count(*) from farkle_games e, farkle_games_players f, farkle_games_players g
			where e.gameid=f.gameid and f.playerid=b.friendid and e.gameid=g.gameid and g.playerid=$playerid and e.winningplayer=b.friendid) as lossesagainst
		from farkle_players a, farkle_friends b
		where a.playerid=b.friendid and b.sourceid=$playerid and a.active=true
		order by (lossesagainst+winsagainst) desc";

	$players = db_select_query( $sql, SQL_MULTI_ROW );
	return $players;
}
*/

function GetActiveFriends( $playerid )
{
	// Single query with LEFT JOINs to get friends and their current game/opponent
	// This eliminates N+1 query pattern (was: 1 query for friends + N queries for games)
	//
	// Uses DISTINCT ON to get only one row per friend (most recent game activity)
	// LEFT JOINs ensure friends without active games still appear
	$sql = "SELECT DISTINCT ON (a.playerid)
				a.username, a.playerid, a.playertitle, a.cardcolor,
				COALESCE(a.emoji_reactions, '') as emoji_reactions,
				g.gameid, p2.username as opponent
			FROM farkle_players a
			JOIN farkle_friends b ON a.playerid = b.friendid
			LEFT JOIN farkle_games_players gp ON gp.playerid = a.playerid
			LEFT JOIN farkle_games g ON g.gameid = gp.gameid AND g.winningplayer = 0
			LEFT JOIN farkle_games_players gp2 ON gp2.gameid = g.gameid AND gp2.playerid != a.playerid
			LEFT JOIN farkle_players p2 ON gp2.playerid = p2.playerid
			WHERE b.sourceid = :sourceid
				AND a.active = 1
				AND b.removed = 0
				AND a.lastplayed > NOW() - interval '10 minutes'
			ORDER BY a.playerid, g.gamestart DESC NULLS LAST, a.lastplayed DESC";

	$results = db_query($sql, [':sourceid' => $playerid], SQL_MULTI_ROW);

	if( !$results || count($results) == 0 )
	{
		return Array();
	}

	// Set status based on whether friend has an active game with an opponent
	foreach( $results as &$player )
	{
		if( !empty($player['opponent']) )
		{
			$player['status'] = 'Playing: ' . $player['opponent'];
		}
		else
		{
			$player['status'] = 'In Lobby';
		}
		// Clean up fields not needed by caller
		unset($player['gameid']);
		unset($player['opponent']);
	}

	return $results;
}

?>