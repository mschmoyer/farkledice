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
		$theIdent = strtolower(mysql_real_escape_string($identstring));
		$sql = "select min(playerid) from farkle_players 
			where lower(username)='$theIdent' or lower(fullname)='$theIdent'";
			
		$friendid = db_select_query( $sql, SQL_SINGLE_VALUE );
		if( empty($friendid) )
			return Array('Error' => 'Player not found.');
	}
	else if( $ident == 'email' )
	{
		$theIdent = strtolower(mysql_real_escape_string($identstring));
		$sql = "select * from farkle_players where lower(email)='$theIdent'";
		$friendid = db_select_query( $sql, SQL_SINGLE_VALUE );
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
		$sql = "select removed from farkle_friends where sourceid=$playerid and friendid=$friendid";
		$removed = db_select_query( $sql, SQL_SINGLE_VALUE );
	
		if( $removed == 1 )
		{
			$sql = "update farkle_friends set removed=0 where sourceid=$playerid and friendid=$friendid";
			$rc = db_command($sql);
		}
		else if( $removed == 0 )
		{
			$alreadyExists = db_select_query( "select count(*) from farkle_friends where sourceid=$playerid and friendid=$friendid", SQL_SINGLE_VALUE );
		
			if( $alreadyExists ) 
			{
				return Array('Error' => 'Already friends with this player.');
			}
			else
			{
				$sql = "insert into farkle_friends (sourceid, friendid) values ($playerid, $friendid)";
				if( !db_command($sql) )
				{
					$err = mysql_errno();
					error_log( "Error creating friend - $err" ); 
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
	$sql = "select friendid from farkle_friends where sourceid=$playerid and friendid=$friendid";
	$friendExists = db_select_query( $sql, SQL_SINGLE_VALUE );

	if( $friendExists )
	{
		$sql = "update farkle_friends set removed=1 where sourceid=$playerid and friendid=$friendid";
	}
	else
	{
		$sql = "insert into farkle_friends (sourceid, friendid, removed) values ($playerid, $friendid, 1 )";
	}
	db_command($sql);
	
	$_SESSION['farkle']['friends'] = null; // Clear the friends cache so it re-queries. 
	
	return 1;
}

function GetNewGameInfo( $playerid )
{
	$sql = "select count(*) from farkle_games a where whostarted=$playerid and winningplayer=0";
	
	$gamesStarted = db_select_query( $sql, SQL_SINGLE_VALUE );
	return Array( GetGameFriends( $playerid ), $gamesStarted );
}

function GetGameFriends( $playerid, $force = false )
{
	BaseUtil_Debug( __FUNCTION__ . " Entered. Playerid=$playerid.", 7 );
	$players = ""; 
	
	//if( !isset($_SESSION['farkle']['friends']) || empty($_SESSION['farkle']['friends']) || $force )
	//{
		BaseUtil_Debug( __FUNCTION__ . " No friend data cached. Adding friend data.", 7 );
		global $facebook;
		$user = $facebook->getUser();
		
		$fbWhere = "";
		// Update the facebook friends. 
		if ($user) {
			try {
				$params = array(
				'method' => 'fql.query',
				'query' => "select uid, name, username FROM user WHERE uid IN (select uid2 from friend where uid1=me()) and is_app_user = 1",
			);
			$result = $facebook->api($params);
			if ($result) {
				$ids='';
				foreach( $result as $r )
					$ids .= "'" . $r['uid'] . "',";
					
				if( strlen($ids) > 0 ) $ids = substr($ids, 0, -1);
				
				BaseUtil_Debug( __FUNCTION__ . " Adding facebook friends with ids $ids to query.", 7 );
				
				$fbWhere = " UNION
				select IFNULL(fullname,username) as username, playerid, playertitle, cardcolor, facebookid, lastplayed
				from farkle_players a
				where facebookid in ($ids) and not exists (select removed from farkle_friends where sourceid=$playerid and friendid=a.playerid and removed=1)";
			}
		  } catch (FacebookApiException $e) {
			//login()
		  }
		}else{
		// login()
		}

		$fbAnd = ( !empty($fbWhere) ? " and a.facebookid is null " : "" );
		
		$sql = "select IFNULL(fullname,username) as username, a.playerid, a.playertitle, a.cardcolor, null as facebookid, a.lastplayed
				from farkle_players a, farkle_friends b
				where a.playerid=b.friendid and b.sourceid=$playerid and 
				a.active=1 $fbAnd and b.removed=0
				$fbWhere
				order by lastplayed desc";
		
		$players = db_select_query( $sql, SQL_MULTI_ROW );
		$_SESSION['farkle']['friends'] = $players; 
	//}
	//else
	//{
	//	BaseUtil_Debug( __FUNCTION__ . " Cached friend data found. Using that.", 7 );
		
		// Return cached friend info
	//	return $_SESSION['farkle']['friends']; 
	//}
	
	// TBD: Do something if no friends returned. 
	if( !isset($players) || empty($players) || count($players) == 0 )
	{
		$players = Array( 'Message' => 'No friends found.' ); 
	}
	
	return $players;
}

/*
function GetFriends( $playerid )
{
	
	$sql = "select IFNULL(a.fullname, a.username) as username, a.playerid, a.playertitle, a.cardbg, a.facebookid,
		(select count(*) from farkle_games c, farkle_games_players d 
			where c.gameid=d.gameid and d.playerid=b.friendid and c.winningplayer=$playerid) as winsagainst,
		(select count(*) from farkle_games e, farkle_games_players f, farkle_games_players g 
			where e.gameid=f.gameid and f.playerid=b.friendid and e.gameid=g.gameid and g.playerid=$playerid and e.winningplayer=b.friendid) as lossesagainst
		from farkle_players a, farkle_friends b
		where a.playerid=b.friendid and b.sourceid=$playerid and a.active=1
		order by (lossesagainst+winsagainst) desc";

	$players = db_select_query( $sql, SQL_MULTI_ROW );
	return $players;
}
*/

?>