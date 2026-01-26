<?php
/*
	farkleTournament.php	
	Desc: Functions related to Farkle tournaments. 
	
	13-Jan-2013		mas		Brought functionality back from dead. Updated functions. Added CreateMonthlyTournament
*/
require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleGameFuncs.php');

define( 'T_FORMAT_DYNAMIC',			0 ); // Chooses single or double elim based on # of players
define( 'T_FORMAT_SINGLE_ELIM',		1 ); // Single elimination tournament
define( 'T_FORMAT_DOUBLE_ELIM',		2 ); // Double elimination tournament

define( 'T_PARTICIPATE_XP', 		20 ); 
define( 'T_WIN_XP', 				100 ); 

// Start a new tournament
function CreateMonthlyTournament()
{
	$tid = 0; 

	$active = GetActiveTournaments();
	if( isset( $active['tournamentid'] ) ) {
		error_log( __FUNCTION__ . ": A tournament is already started. Not starting another one." ); 
		BaseUtil_Error( __FUNCTION__ . ": A tournament is already started. Not starting another one." ); 
		return 0; 
	}
	
	$tName = date('F j') . " Farkle Open Tournament"; 	
	$tid = CreateTournament( 64, T_FORMAT_SINGLE_ELIM, 24, $tName, "tButton1.png", 24, 1, 1 );
	
	if( $tid )
	{
		// update the achievement graphic to the beta icon
		$randImg = rand( 1, 3 );
		
		$sql = "update farkle_achievements set imagefile='tournament" . $randImg . ".png' where achievementid=(
			select achievementid from farkle_tournaments where tournamentid=$tid)";
		$result = db_command($sql);	
	}
	return $tid; 
}

function CreateTournament( $playercap, $tformat, $launchHours, $name, $lobbyImage="tButton1.png", 
		$roundHours=72, $giveAchievement=0, $startCondition=0 )
{
	BaseUtil_Debug( "CreateTournament: Entered.", 14 );
	
	$achSelect = ""; 
	$achValue = "";
	$newTournamentId = 0; 
	
	if( !empty($giveAchievement) ) 
	{
		// Create an achievement for winning this tournament. 
		// get the highest achievementid over 1000
		$sql = "select max(achievementid) from farkle_achievements where achievementid >= 1000";
		$maxAchId = db_select_query( $sql, SQL_SINGLE_VALUE );
		if( $maxAchId < 1000 ) $maxAchId = 999;
		$maxAchId++;
		$randIcon = "tournament" . rand(1,3) . ".png";
		
		$sql = "insert into farkle_achievements values ($maxAchId, 'Win $name',
			CONCAT('Hosted ', TO_CHAR( NOW() + ($launchHours || ' hours')::INTERVAL, 'Mon DD, YYYY') ), 35, '$randIcon')";
		$rc = db_command($sql);
		
		BaseUtil_Debug( "CreateTournament: Will award achievement #$maxAchId for winning this tournament.", 1 );
		
		$achSelect = ", achievementid";
		$achValue = ", $maxAchId";
	}
		
	$sql = "insert into farkle_tournaments
		( playercap, launchdate, tformat, tname, pointstowin, mintostart, startcondition, lobbyimage, roundhours $achSelect )
		values
		( $playercap, NOW() + ($launchHours || ' hours')::INTERVAL, $tformat, '$name', 10000, 0, $startCondition, '$lobbyImage', $roundHours $achValue )";
	$rc = db_command($sql);
	$newTournamentId = db_insert_id();
	
	return $newTournamentId;
}

function AddPlayerToTournament( $tid, $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": Entered. Tid=$tid, Playerid=$playerid", 14 );
	
	$sql = "select count(*) from farkle_tournaments_players where playerid=$playerid and tournamentid=$tid";
	
	// Only players who are not playing another tournament may join.
	//$sql = "select count(*) from farkle_tournaments_players a, farkle_tournaments b
	//	where a.playerid=$playerid and a.tournamentid=b.tournamentid and b.winningplayer=0";
	$alreadyInT = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	if( !empty($alreadyInT) )
	{
		BaseUtil_Debug( __FUNCTION__ . ": Player $playerid is already in tournament $tid.", 1 );
		return Array('Error'=>'You are already playing in a tournament. Currently limited to 1 tournament.');
	}
	else
	{
		// Get the highest seed value
		$sql = "select max(seednum) from farkle_tournaments_players where tournamentid=$tid";
		$highestSeed = db_select_query( $sql, SQL_SINGLE_VALUE );
		
		// Add this player to the tournament players
		$highestSeed++;
		$sql = "insert into farkle_tournaments_players (tournamentid, playerid, seednum ) values 
			( $tid, $playerid, $highestSeed )";
			
		if( db_command($sql) )
		{		
			BaseUtil_Debug( __FUNCTION__ . ": Added player $playerid to tournament $tid.", 1 );
			
			// Get the player cap for this tournament
			$sql = "select playercap from farkle_tournaments where tournamentid=$tid";
			$playerCap = db_select_query( $sql, SQL_SINGLE_VALUE );
			
			if( $playerCap <= $highestSeed )
			{
				// Tournament is full -- let's start it. 
				error_log(  __FUNCTION__ . ": Tournament $tid now has $highestSeed players. Cap is $playerCap. We're at the cap so the tournament is starting." );
				BaseUtil_Debug( __FUNCTION__ . ": Tournament $tid now has $highestSeed players. Cap is $playerCap. We're at the cap so the tournament is starting.", 1 );
				StartTournament( $tid );
			}	
		}
	}
	return 1; // Success
}

function RemovePlayerFromTournament( $tid, $playerid )
{
	$sql = "delete from farkle_tournaments_players where tournamentid=$tid and playerid=$playerid";
	$rc = db_command($sql);
	return $rc; 
}

// Begins Round 1 of a tournament
function StartTournament( $tid )
{
	BaseUtil_Debug( __FUNCTION__ . ": Entered. Tid=$tid", 14 );
	$nextRound = 0; 
	
	$sql = "select playerid from farkle_tournaments_players where tournamentid=$tid";
	$players = db_select_query( $sql, SQL_MULTI_ROW );
	
	if( count($players) < 2 )
	{
		BaseUtil_Error( __FUNCTION__ . ": We have less than two players for tournament $tid. We cannot start it now." );
		return 0; 
	}
	else
	{
		// update the start date
		$sql = "update farkle_tournaments set startdate=NOW() where tournamentid=$tid";
		if( db_command($sql) )
		{		
			$seeds = Array();
			
			// Loop through the players and randomize their "seednum". 
			foreach( $players as $p )
			{
				$found = 0;
				while( !$found )
				{
					$newseed = rand( 1, count($players) );
					if( empty($seeds[$newseed]) )
					{
						$seeds[$newseed] = 1;
						$found = 1;
					}
				}
				$sql = "update farkle_tournaments_players set seednum=$newseed where playerid={$p['playerid']}";
				$rc = db_command($sql);
			}

			$nextRound = GenerateTournamentRound( $tid );
		}
	}
	return $nextRound; // Success
}


// Check to see if all games in this round of the tournament have been played
// Returns: 1=yes, 0=no
function IsTournamentRoundDone( $tid )
{
	// See how many games are still unfinished
	$sql = "select count(*) from farkle_games a, farkle_tournaments_games b
		where a.gameid=b.gameid and b.tournamentid=$tid and a.winningplayer=0
		and b.roundnum=(select max(roundnum) from farkle_tournaments where tournamentid=$tid)";
	$gamesUnfinished = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	$sql = "select roundnum from farkle_tournaments where tournamentid=$tid";
	$roundnum = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	BaseUtil_Debug( __FUNCTION__ . ": Tournament $tid still has $gamesUnfinished games unfinished in round $roundnum.", 1 );
	
	// If unfinished games or roundnum = 0
	if( $gamesUnfinished > 0 || $roundnum == 0 ) 
		return 0;
	
	BaseUtil_Debug( __FUNCTION__ . ": No games left in round $roundnum of tournament $tid. Starting new round.", 1 );
	
	// The round is finished. 
	return 1; 
}

function EndLastTournamentRound( $tid, $lastRound )
{
	BaseUtil_Debug( "EndLastTournamentRound: Entered. TID=$tid", 14 );
	
	// Anybody who did not finish a game and it is their turn is included here. 
	// They recieve a loss. 
	$sql = "select MAX(b.playerscore), b.playerid, a.gameid
		from farkle_games a, farkle_games_players b, farkle_tournaments_games c
		where a.gameid=b.gameid and b.gameid=c.gameid
		and c.tournamentid=$tid and roundnum=$lastRound and a.winningplayer=0
		and b.playerscore=(select max(playerscore) from farkle_games_players where gameid=a.gameid)
		group by b.playerid, a.gameid";
		
	$pData = db_select_query( $sql, SQL_MULTI_ROW );
	
	foreach( $pData as $p )
	{
		// This game is ending because one or more of the players did not play their rounds. 
		error_log( __FUNCTION__ . ": Player {$p['playerid']} wins game {$p['gameid']} in touranment $tid due to other players inactivity." ); 
		BaseUtil_Debug( __FUNCTION__ . ": Player {$p['playerid']} wins game {$p['gameid']} in touranment $tid due to other players inactivity.", 1 );
			
		FarkleWinGame( $p['gameid'], $p['playerid'], "Tournament round time ran out. Last player who played wins.", 1, 0, 0 );
	}
	
	// Tack on a *tournament* loss for all the losers last round for all players who were NOT winners
	$sql = "update farkle_tournaments_players set losses=losses+1 
		where tournamentid=$tid and playerid in (
			select b.playerid from farkle_tournaments_games a, farkle_games_players b, farkle_games c
			where a.roundnum=$lastRound and a.tournamentid=$tid and a.gameid=c.gameid and c.gameid=b.gameid 
				and b.playerid <> c.winningplayer)";
	$rc = db_command($sql);
	
	return 1;
}


function CheckTournaments( )
{
	// Check to see if any tournaments are due for a round end. Run in the Cron job.
	$sql = "select tournamentid, roundnum, roundstartdate, NOW() + (roundhours || ' HOURS')::INTERVAL, roundhours,
		(NOW()-(roundstartdate + (roundhours || ' HOURS')::INTERVAL)) as timedelta,
		 (select min(winningplayer)
			from farkle_games b, farkle_tournaments_games c
			where b.gameid=c.gameid and c.tournamentid=a.tournamentid) as lowestWinnerId
		from farkle_tournaments a
		where winningplayer=0 and roundnum > 0";
	$tData = db_select_query( $sql, SQL_MULTI_ROW );
	
	foreach( $tData as $t )
	{
		// Time has passed OR no games are left unplayed...
		if( $t['timedelta'] > 0 || $t['lowestWinnerId'] > 0 )
		{
			error_log( __FUNCTION__ . ": Generating round {$t['roundnum']} + 1 for tournament {$t['tournamentid']}" ); 
			BaseUtil_Debug( __FUNCTION__ . ": Generating round {$t['roundnum']} + 1 for tournament {$t['tournamentid']}", 1 );
			// It is past the allotted time (such as 72 hours). Do next round. 
			GenerateTournamentRound( $t['tournamentid'] );
		}
	}
	
	// Check to see if any tournaments are ready to start. 
	$sql = "select min(tournamentid) from farkle_tournaments 
		where launchdate <= NOW() and startcondition=1 and winningplayer=0 and roundnum=0";
	$readyTid = db_select_query( $sql, SQL_SINGLE_VALUE );
	if( $readyTid )
	{
		error_log( __FUNCTION__ . ": Starting tournament $readyTid." );
		BaseUtil_Debug( __FUNCTION__ . ": Starting tournament $readyTid.", 1 );
		StartTournament( $readyTid ); 
	}
}

// Generates new games for the next round of the tournament
// Takes into account things like byes and winner/loser brackets
function GenerateTournamentRound( $tid )
{
	$errorOccurred = 0; 
	BaseUtil_Debug( __FUNCTION__ . ": Entered. TID=$tid", 14 );
	
	// Get tournament data
	$sql = "select tname, roundnum, playercap, pointstowin, mintostart, winningplayer, roundhours, gamemode, tFormat
		from farkle_tournaments where tournamentid=$tid";
	$tData = db_select_query( $sql, SQL_SINGLE_ROW );
	$lossesTillDone = $tData['tFormat'];
	
	// Exit if the tournament has already been won
	if( $tData['winningplayer'] > 0 ) return 0;
	
	// Get the last round played (or 1 if just starting)
	$lastRound = $tData['roundnum'];
	if( empty($lastRound) )
		$lastRound = 0;
	
	$nextRound = ( $lastRound == 0 ) ? 1 : $lastRound + 1;
	BaseUtil_Debug( __FUNCTION__ . ": LastRound: $lastRound, NextRound: $nextRound.", 1 );
	
	// Flip the games for bye-game fairness
	// Round 1: 1/2/3/4/5/6/7 = 1v2, 3v4, 5v6, 7 bye
	// Round 2: 7/6/4/2 = 7v6, 4v2
	// Round 3: 7/4 = 7v4, etc
	if( $nextRound % 2 == 0 )
		$orderDir = "DESC";
	else
		$orderDir = "ASC";
	
	if( $lastRound == 0 )
	{
		// Select all the players who have joined the tournament
		$sql = "SELECT a.playerid, a.username, b.seednum 
			FROM farkle_players a, farkle_tournaments_players b
			WHERE a.playerid=b.playerid and b.tournamentid=$tid
			ORDER BY 3 $orderDir";	
	}
	else
	{
		// Tack on a loss for all the losers last round
		EndLastTournamentRound( $tid, $lastRound );

		// Select all players with less than the # losses for removal from tournament
		// This will flip-flop order based on $orderDir above. It will also always place
		// players with more losses at the top and thus players with the least losses have a higher
		// chance to get a bye round. 
		$sql = "SELECT * FROM (
			select (select count(*) from farkle_tournaments_games where tournamentid=$tid and byeplayerid=d.playerid) as byes,
				a.playerid, a.username, d.seednum, d.losses, a.email
				from farkle_players a, farkle_tournaments_games c, farkle_tournaments_players d
				where
					d.losses < $lossesTillDone and
					d.tournamentid=$tid and
					a.playerid=d.playerid
			UNION
			select (select count(*) from farkle_tournaments_games where tournamentid=$tid and byeplayerid=b.playerid) as byes,
				b.playerid, b.username, c.seednum, c.losses, b.email
				from farkle_tournaments_games a, farkle_players b, farkle_tournaments_players c
				where a.byeplayerid > 0 and a.gameid=0 and a.tournamentid=$tid and a.roundnum=$lastRound and
				a.byeplayerid=b.playerid and 
				c.playerid=b.playerid and c.tournamentid=a.tournamentid
		) e ORDER BY 1 desc, 5 desc, 4 $orderDir";
	}
	
	$roundPlayers = db_select_query( $sql, SQL_MULTI_ROW );
	
	error_log( "GenerateTournamentRound: Starting round $nextRound. Count of players still in=".count($roundPlayers) );
	
	if( count($roundPlayers) == 1 )
	{
		// Tournament is down to the last player -- he has won!
		$rc = TournamentFinish( $tid, $roundPlayers[0]['playerid'] );
		return $rc; 
	}
	
	$roundDays = ((int)$tData['roundhours'] / 24);
	
	$i = 0;
	while( $i < count($roundPlayers) )
	{			
		if( $i+1 < count($roundPlayers) )
		{
			// Grab the next two players and make a game with them
			//$gamePlayers = "[" . $roundPlayers[$i]['playerid'] . "," . $roundPlayers[$i+1]['playerid'] . "]";
			$gamePlayers = Array( $roundPlayers[$i]['playerid'], $roundPlayers[$i+1]['playerid'] );
				
			// Create the new game. 
			//error_log( "GenerateTournamentRound: New tournament game: {$gamePlayers[0]} vs. {$gamePlayers[1]}" );
			
			$newGameId = CreateGameWithPlayers( $gamePlayers, $roundPlayers[$i]['playerid'], $roundDays,
				GAME_WITH_FRIENDS, GAME_MODE_10ROUND );
				
			if( $newGameId > 0 )
			{				
				BaseUtil_Debug( __FUNCTION__ . ": Started a tournament game with player {$gamePlayers[0]} vs. {$gamePlayers[1]}", 1 );
				
				// Insert the tournament/game information
				$sql = "insert into farkle_tournaments_games (tournamentid,gameid,roundnum,byeplayerid) VALUES
					( $tid, $newGameId, $nextRound, 0)";
				$rc = db_command($sql);
				
				// Email exists and it is length>5 - (smallest email is "a@b.c")
				if( isset($roundPlayers[$i]['email']) && strlen($roundPlayers[$i]['email']) > 5 ) {
					if( $lastRound > 0 )
					{
						// Send "next round" email
						SendEmail( $roundPlayers[$i]['email'], "{$tData['tname']} - new game available to play!", 
							"{$roundPlayers[$i]['username']}, \r\n\r\nYou have made it to round $nextRound of [tname]! This means you have another 24 hours to finish your next tournament game. Good luck!\r\n\r\nClick this link to go straight to your game: <a href=\"http://www.farkledice.com/wwwroot/farkle.php?resumegameid=$newGameId\">[ Play Now! ]</a>" );
					} 
					else
					{
						// Send "welcome to tournament" email
						SendEmail( $roundPlayers[$i]['email'], "{$tData['tname']} has begun!", 
							"{$roundPlayers[$i]['username']}, \r\n\r\nWelcome to the {$tData['tname']} event! This is an elimination style tournament using the standard 10 round rules in Farkle Ten. You will have {$tData['roundhours']} hours to finish your tournament game. Good luck!\r\n\r\nClick this link to go straight to your game: <a href=\"http://www.farkledice.com/wwwroot/farkle.php?resumegameid=$newGameId\">[ Play Now! ]</a>" );
					}
				}
			}
			else
			{
				BaseUtil_Error( __FUNCTION__ . ": Error creating tournament games." );
				$errorOccurred = 1; 
				break;
			}
		}
		else
		{
			BaseUtil_Debug( __FUNCTION__ . ": Player {$roundPlayers[$i]['playerid']} gets a bye round.", 1 );
			error_log( "GenerateTournamentRound: bye round given to {$roundPlayers[$i]['playerid']}" );
			
			// Only one player left...he gets a bye round!
			$sql = "insert into farkle_tournaments_games (tournamentid,gameid,roundnum,byeplayerid) VALUES
				( $tid, 0, $nextRound, {$roundPlayers[$i]['playerid']})";
			$rc = db_command($sql);   
		}
		$i+=2;
	}
	
	if( !$errorOccurred )
	{
		// Increment the tournament round
		$sql = "update farkle_tournaments set roundnum=$nextRound, roundstartdate=NOW() where tournamentid=$tid";
		$rc = db_command($sql);
		BaseUtil_Debug( __FUNCTION__ . ": Num players in tournament $tid = " . count($roundPlayers), 1 );
	}
	return $nextRound; 
}
	
function TournamentFinish( $tid, $winningplayer )
{
	// Set the winning player
	BaseUtil_Debug( __FUNCTION__ . ": Tournament $tid finished, player $winningplayer won!", 1 );
	$sql = "update farkle_tournaments set winningplayer=$winningplayer, finishdate=NOW() where tournamentid=$tid";
	$rc = db_command($sql);
	
	// Information on the winning player
	$sql = "select a.username, b.roundnum, b.achievementid
		from farkle_players a, farkle_tournaments b
		where a.playerid=$winningplayer and b.tournamentid=$tid";
	$w = db_select_query( $sql, SQL_SINGLE_ROW );
	$un = ucwords(strtolower($w['username']));
	
	// Award the winning player the achievmeent for this tournament
	if( $w['achievementid'] > 0 )
	{
		Ach_AwardAchievement( $winningplayer, $w['achievementid'] );
	}
	
	// Give the winner some XP
	GivePlayerXP( $winningplayer, T_WIN_XP );
		
	// Give each player some XP for participating
	$sql = "select a.playerid from farkle_players a, farkle_tournaments_players b 
		where a.playerid=b.playerid and b.tournamentid=$tid"; 
	$players = db_select_query( $sql, SQL_MULTI_ROW );
	foreach( $players as $p )
		GivePlayerXP( $p['playerid'], T_PARTICIPATE_XP ); 
		
	// Email the tournament players letting them know who won. 
	EmailTournamentPlayers( $tid, "[tname] - a winner has been crowned!", "After {$w['roundnum']} rounds of exciting play, $un has been crowned champion! $un will will recieve a unique achievement worth 35 achievement points and ".T_WIN_XP." XP for their victory!\r\n\r\nThanks to all the players who participated and good luck next time!");  
	
	return $rc; 
}

function GetTournamentStatus( $tid, $playerid )
{
	$gameInfo = 0;
	$sql = "select TO_CHAR(a.launchdate, 'Mon DD @ HH12:00 AM') as launchdate, a.finishdate,
			a.winningplayer, a.roundnum, a.tname, a.tformat, a.roundhours, a.pointstowin, a.mintostart,
			a.playercap, a.startcondition, gamemode,
			j.imagefile as imagefile, j.title, j.description, j.worth,
			TO_CHAR( (a.roundstartdate + (a.roundhours || ' hours')::INTERVAL), 'Mon DD @ HH12:00 AM') as nextrounddate,
			(select count(*) from farkle_tournaments_players b where b.tournamentid=a.tournamentid) as numplayers,
			COALESCE((select 1 from farkle_tournaments_players c where c.tournamentid=a.tournamentid and playerid=$playerid),0) as participant
		FROM farkle_tournaments a, farkle_achievements j where a.achievementid=j.achievementid and tournamentid=$tid";
	$tInfo = db_select_query( $sql, SQL_SINGLE_ROW );
	
	//if( $tInfo['roundnum'] > 0 && $tInfo['winningplayer'] == 0 )
	if( $tInfo['roundnum'] > 0 )
	{
		$innerPiece = "select a.roundnum, b.winningplayer, a.gameid, b.gamemode, b.currentturn, b.playerstring,
				c.playerid as p1id,
				c.username as p1u,
				d.playerround as p1rnd,
				d.playerscore as firstplayerscore,
				e.playerid as p2id,
				e.username as p2u, 
				f.playerround as p2rnd,
				f.playerscore as secondplayerscore,
				(c.playerid=$playerid || e.playerid=$playerid) as yourplayer
			from 
				farkle_tournaments_games a, 
				farkle_games b, 
				farkle_players c, farkle_games_players d,
				farkle_players e, farkle_games_players f
			where 
				a.tournamentid=$tid and
				a.gameid=b.gameid and 
				d.gameid=b.gameid and 
				f.gameid=b.gameid and 
				c.playerid=d.playerid and d.playerturn=1 and d.gameid=b.gameid and
				e.playerid=f.playerid and f.playerturn=2 and f.gameid=b.gameid
			";
		$innerPiece .= " UNION ";
		$innerPiece .= "select a.roundnum, a.byeplayerid as winningplayer, 0 as gameid, 0 asgamemode, 0 as currentturn,
				CONCAT(c.username,' - Bye Round') as playerstring,
				a.byeplayerid as p1id, c.username as p1u, 0 as p1s, 0 as p1rnd, 
				'0' as p2id, 'bye' as p2u, 0 as p2s, 0 as p2rnd,
				 (c.playerid=$playerid) as yourplayer
			from farkle_tournaments_games a, farkle_players c
			where a.tournamentid=$tid and a.byeplayerid=c.playerid";
			
		$sql = "select * from (" . $innerPiece . ") g ORDER BY roundnum desc, (winningplayer>0) ASC, yourplayer desc, p1id ASC";
		$gameInfo = db_select_query( $sql, SQL_MULTI_ROW );
	}

	$sql = "select a.username, a.playerid, a.playertitle, a.cardcolor, a.playerlevel,
		b.seednum
		from farkle_players a, farkle_tournaments_players b
		where a.playerid=b.playerid and b.tournamentid=$tid
		order by seednum";
	$pInfo = db_select_query( $sql, SQL_MULTI_ROW );
	//$gameInfo = 0;

	
	return Array( $tInfo, $gameInfo, $pInfo );
}

// Tokens: [tname] = tournament name
function EmailTournamentPlayers( $tid, $v_subj, $v_msg, $all=1 )
{
	// Email all the players letting them know the tournament has started. 
	$sql = "select a.playerid, email, devicetoken from farkle_players a, farkle_tournaments_players b, farkle_tournaments c
		where a.playerid=b.playerid and b.tournamentid=$tid and c.tournamentid=b.tournamentid";
	
	if( !$all ) $sql .= " and b.losses < c.tFormat";
	
	$recipients = db_select_query( $sql, SQL_MULTI_ROW );
	
	$sql = "select tname from farkle_tournaments where tournamentid=$tid";
	$tData = db_select_query( $sql, SQL_SINGLE_ROW );
	
	$subj = str_replace( "[tname]", $tData['tname'], $v_subj );
	$msg = str_replace( "[tname]", $tData['tname'], $v_msg );
	
	foreach( $recipients as $r )
	{
		if( !empty($r['devicetoken']) ) 
		{
			SendPushNotification( $r['playerid'], $v_msg, "newGameTone.aif" );
		}
		else
		{
			if( isset($r['email']) && strlen($r['email']) > 5 ) 
			{
				SendEmail( $r['email'], $subj, $msg );
			}
		}
	}	
}

function GetActiveTournaments()
{
	// Get the lowest unfinished tournament.
	$sql = "select min(tournamentid) as tournamentid, lobbyImage, EXTRACT(DAY FROM (now() - MAX(finishdate))) as DaysSinceFinished
	from farkle_tournaments
	where winningplayer = 0 or finishdate > NOW() - interval '3' day
	group by lobbyimage";
	$tInfo = db_select_query( $sql, SQL_SINGLE_ROW );
	return $tInfo;
}

?>