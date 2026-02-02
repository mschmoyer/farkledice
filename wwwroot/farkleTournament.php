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

		$sql = "UPDATE farkle_achievements SET imagefile = :imagefile WHERE achievementid = (
			SELECT achievementid FROM farkle_tournaments WHERE tournamentid = :tid)";
		$result = db_execute($sql, [':imagefile' => 'tournament' . $randImg . '.png', ':tid' => $tid]);
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
		$sql = "SELECT max(achievementid) FROM farkle_achievements WHERE achievementid >= :minid";
		$maxAchId = db_query($sql, [':minid' => 1000], SQL_SINGLE_VALUE);
		if( $maxAchId < 1000 ) $maxAchId = 999;
		$maxAchId++;
		$randIcon = "tournament" . rand(1,3) . ".png";

		$sql = "INSERT INTO farkle_achievements VALUES (:achid, :achname,
			CONCAT('Hosted ', TO_CHAR( NOW() + (:launchhours || ' hours')::INTERVAL, 'Mon DD, YYYY') ), 35, :randicon)";
		$rc = db_execute($sql, [':achid' => $maxAchId, ':achname' => 'Win ' . $name, ':launchhours' => $launchHours, ':randicon' => $randIcon]);

		BaseUtil_Debug( "CreateTournament: Will award achievement #$maxAchId for winning this tournament.", 1 );

		$achSelect = ", achievementid";
		$achValue = ", :achid";
	}

	$sql = "INSERT INTO farkle_tournaments
		( playercap, launchdate, tformat, tname, pointstowin, mintostart, startcondition, lobbyimage, roundhours $achSelect )
		VALUES
		( :playercap, NOW() + (:launchhours || ' hours')::INTERVAL, :tformat, :tname, 10000, 0, :startcondition, :lobbyimage, :roundhours $achValue )";
	$params = [
		':playercap' => $playercap,
		':launchhours' => $launchHours,
		':tformat' => $tformat,
		':tname' => $name,
		':startcondition' => $startCondition,
		':lobbyimage' => $lobbyImage,
		':roundhours' => $roundHours
	];
	if( !empty($giveAchievement) ) {
		$params[':achid'] = $maxAchId;
	}
	$rc = db_execute($sql, $params);
	$newTournamentId = db_insert_id();
	
	return $newTournamentId;
}

function AddPlayerToTournament( $tid, $playerid )
{
	BaseUtil_Debug( __FUNCTION__ . ": Entered. Tid=$tid, Playerid=$playerid", 14 );

	$sql = "SELECT count(*) FROM farkle_tournaments_players WHERE playerid = :playerid AND tournamentid = :tid";
	$alreadyInT = db_query($sql, [':playerid' => $playerid, ':tid' => $tid], SQL_SINGLE_VALUE);

	if( !empty($alreadyInT) )
	{
		BaseUtil_Debug( __FUNCTION__ . ": Player $playerid is already in tournament $tid.", 1 );
		return Array('Error'=>'You are already playing in a tournament. Currently limited to 1 tournament.');
	}
	else
	{
		// Get the highest seed value
		$sql = "SELECT max(seednum) FROM farkle_tournaments_players WHERE tournamentid = :tid";
		$highestSeed = db_query($sql, [':tid' => $tid], SQL_SINGLE_VALUE);

		// Add this player to the tournament players
		$highestSeed++;
		$sql = "INSERT INTO farkle_tournaments_players (tournamentid, playerid, seednum) VALUES
			(:tid, :playerid, :seednum)";

		if( db_execute($sql, [':tid' => $tid, ':playerid' => $playerid, ':seednum' => $highestSeed]) )
		{
			BaseUtil_Debug( __FUNCTION__ . ": Added player $playerid to tournament $tid.", 1 );

			// Get the player cap for this tournament
			$sql = "SELECT playercap FROM farkle_tournaments WHERE tournamentid = :tid";
			$playerCap = db_query($sql, [':tid' => $tid], SQL_SINGLE_VALUE);

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
	$sql = "DELETE FROM farkle_tournaments_players WHERE tournamentid = :tid AND playerid = :playerid";
	$rc = db_execute($sql, [':tid' => $tid, ':playerid' => $playerid]);
	return $rc;
}

// Begins Round 1 of a tournament
function StartTournament( $tid )
{
	BaseUtil_Debug( __FUNCTION__ . ": Entered. Tid=$tid", 14 );
	$nextRound = 0;

	$sql = "SELECT playerid FROM farkle_tournaments_players WHERE tournamentid = :tid";
	$players = db_query($sql, [':tid' => $tid], SQL_MULTI_ROW);

	if( count($players) < 2 )
	{
		BaseUtil_Error( __FUNCTION__ . ": We have less than two players for tournament $tid. We cannot start it now." );
		return 0;
	}
	else
	{
		// update the start date
		$sql = "UPDATE farkle_tournaments SET startdate = NOW() WHERE tournamentid = :tid";
		if( db_execute($sql, [':tid' => $tid]) )
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
				$sql = "UPDATE farkle_tournaments_players SET seednum = :seednum WHERE playerid = :playerid";
				$rc = db_execute($sql, [':seednum' => $newseed, ':playerid' => $p['playerid']]);
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
	$sql = "SELECT count(*) FROM farkle_games a, farkle_tournaments_games b
		WHERE a.gameid = b.gameid AND b.tournamentid = :tid AND a.winningplayer = 0
		AND b.roundnum = (SELECT max(roundnum) FROM farkle_tournaments WHERE tournamentid = :tid2)";
	$gamesUnfinished = db_query($sql, [':tid' => $tid, ':tid2' => $tid], SQL_SINGLE_VALUE);

	$sql = "SELECT roundnum FROM farkle_tournaments WHERE tournamentid = :tid";
	$roundnum = db_query($sql, [':tid' => $tid], SQL_SINGLE_VALUE);

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
	$sql = "SELECT MAX(b.playerscore), b.playerid, a.gameid
		FROM farkle_games a, farkle_games_players b, farkle_tournaments_games c
		WHERE a.gameid = b.gameid AND b.gameid = c.gameid
		AND c.tournamentid = :tid AND roundnum = :lastround AND a.winningplayer = 0
		AND b.playerscore = (SELECT max(playerscore) FROM farkle_games_players WHERE gameid = a.gameid)
		GROUP BY b.playerid, a.gameid";

	$pData = db_query($sql, [':tid' => $tid, ':lastround' => $lastRound], SQL_MULTI_ROW);

	foreach( $pData as $p )
	{
		// This game is ending because one or more of the players did not play their rounds.
		error_log( __FUNCTION__ . ": Player {$p['playerid']} wins game {$p['gameid']} in touranment $tid due to other players inactivity." );
		BaseUtil_Debug( __FUNCTION__ . ": Player {$p['playerid']} wins game {$p['gameid']} in touranment $tid due to other players inactivity.", 1 );

		FarkleWinGame( $p['gameid'], $p['playerid'], "Tournament round time ran out. Last player who played wins.", 1, 0, 0 );
	}

	// Tack on a *tournament* loss for all the losers last round for all players who were NOT winners
	$sql = "UPDATE farkle_tournaments_players SET losses = losses + 1
		WHERE tournamentid = :tid AND playerid IN (
			SELECT b.playerid FROM farkle_tournaments_games a, farkle_games_players b, farkle_games c
			WHERE a.roundnum = :lastround AND a.tournamentid = :tid2 AND a.gameid = c.gameid AND c.gameid = b.gameid
				AND b.playerid <> c.winningplayer)";
	$rc = db_execute($sql, [':tid' => $tid, ':lastround' => $lastRound, ':tid2' => $tid]);

	return 1;
}


function CheckTournaments( )
{
	// Check to see if any tournaments are due for a round end. Run in the Cron job.
	$sql = "SELECT tournamentid, roundnum, roundstartdate, NOW() + (roundhours || ' HOURS')::INTERVAL, roundhours,
		(NOW()-(roundstartdate + (roundhours || ' HOURS')::INTERVAL)) as timedelta,
		 (SELECT min(winningplayer)
			FROM farkle_games b, farkle_tournaments_games c
			WHERE b.gameid = c.gameid AND c.tournamentid = a.tournamentid) as lowestWinnerId
		FROM farkle_tournaments a
		WHERE winningplayer = 0 AND roundnum > 0";
	$tData = db_query($sql, [], SQL_MULTI_ROW);

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
	$sql = "SELECT min(tournamentid) FROM farkle_tournaments
		WHERE launchdate <= NOW() AND startcondition = 1 AND winningplayer = 0 AND roundnum = 0";
	$readyTid = db_query($sql, [], SQL_SINGLE_VALUE);
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
	$sql = "SELECT tname, roundnum, playercap, pointstowin, mintostart, winningplayer, roundhours, gamemode, tFormat
		FROM farkle_tournaments WHERE tournamentid = :tid";
	$tData = db_query($sql, [':tid' => $tid], SQL_SINGLE_ROW);
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
			WHERE a.playerid = b.playerid AND b.tournamentid = :tid
			ORDER BY 3 $orderDir";
		$roundPlayers = db_query($sql, [':tid' => $tid], SQL_MULTI_ROW);
	}
	else
	{
		// Tack on a loss for all the losers last round
		EndLastTournamentRound( $tid, $lastRound );

		// Select all players with less than the # losses for removal from tournament
		// This will flip-flop order based on $orderDir above. It will also always place
		// players with more losses at the top and thus players with the least losses have a higher
		// chance to get a bye round.
		// Note: Complex query with subqueries - using integer casting for safety
		$sql = "SELECT * FROM (
			SELECT (SELECT count(*) FROM farkle_tournaments_games WHERE tournamentid = " . (int)$tid . " AND byeplayerid = d.playerid) as byes,
				a.playerid, a.username, d.seednum, d.losses, a.email
				FROM farkle_players a, farkle_tournaments_games c, farkle_tournaments_players d
				WHERE
					d.losses < " . (int)$lossesTillDone . " AND
					d.tournamentid = " . (int)$tid . " AND
					a.playerid = d.playerid
			UNION
			SELECT (SELECT count(*) FROM farkle_tournaments_games WHERE tournamentid = " . (int)$tid . " AND byeplayerid = b.playerid) as byes,
				b.playerid, b.username, c.seednum, c.losses, b.email
				FROM farkle_tournaments_games a, farkle_players b, farkle_tournaments_players c
				WHERE a.byeplayerid > 0 AND a.gameid = 0 AND a.tournamentid = " . (int)$tid . " AND a.roundnum = " . (int)$lastRound . " AND
				a.byeplayerid = b.playerid AND
				c.playerid = b.playerid AND c.tournamentid = a.tournamentid
		) e ORDER BY 1 desc, 5 desc, 4 $orderDir";
		$roundPlayers = db_query($sql, [], SQL_MULTI_ROW);
	}
	
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
				$sql = "INSERT INTO farkle_tournaments_games (tournamentid, gameid, roundnum, byeplayerid) VALUES
					(:tid, :gameid, :roundnum, 0)";
				$rc = db_execute($sql, [':tid' => $tid, ':gameid' => $newGameId, ':roundnum' => $nextRound]);
				
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
			$sql = "INSERT INTO farkle_tournaments_games (tournamentid, gameid, roundnum, byeplayerid) VALUES
				(:tid, 0, :roundnum, :byeplayerid)";
			$rc = db_execute($sql, [':tid' => $tid, ':roundnum' => $nextRound, ':byeplayerid' => $roundPlayers[$i]['playerid']]);
		}
		$i+=2;
	}

	if( !$errorOccurred )
	{
		// Increment the tournament round
		$sql = "UPDATE farkle_tournaments SET roundnum = :roundnum, roundstartdate = NOW() WHERE tournamentid = :tid";
		$rc = db_execute($sql, [':roundnum' => $nextRound, ':tid' => $tid]);
		BaseUtil_Debug( __FUNCTION__ . ": Num players in tournament $tid = " . count($roundPlayers), 1 );
	}
	return $nextRound;
}
	
function TournamentFinish( $tid, $winningplayer )
{
	// Set the winning player
	BaseUtil_Debug( __FUNCTION__ . ": Tournament $tid finished, player $winningplayer won!", 1 );
	$sql = "UPDATE farkle_tournaments SET winningplayer = :winningplayer, finishdate = NOW() WHERE tournamentid = :tid";
	$rc = db_execute($sql, [':winningplayer' => $winningplayer, ':tid' => $tid]);

	// Information on the winning player
	$sql = "SELECT a.username, b.roundnum, b.achievementid
		FROM farkle_players a, farkle_tournaments b
		WHERE a.playerid = :playerid AND b.tournamentid = :tid";
	$w = db_query($sql, [':playerid' => $winningplayer, ':tid' => $tid], SQL_SINGLE_ROW);
	$un = ucwords(strtolower($w['username']));

	// Award the winning player the achievmeent for this tournament
	if( $w['achievementid'] > 0 )
	{
		Ach_AwardAchievement( $winningplayer, $w['achievementid'] );
	}

	// Give the winner some XP
	GivePlayerXP( $winningplayer, T_WIN_XP );

	// Give each player some XP for participating
	$sql = "SELECT a.playerid FROM farkle_players a, farkle_tournaments_players b
		WHERE a.playerid = b.playerid AND b.tournamentid = :tid";
	$players = db_query($sql, [':tid' => $tid], SQL_MULTI_ROW);
	foreach( $players as $p )
		GivePlayerXP( $p['playerid'], T_PARTICIPATE_XP );

	// Email the tournament players letting them know who won.
	EmailTournamentPlayers( $tid, "[tname] - a winner has been crowned!", "After {$w['roundnum']} rounds of exciting play, $un has been crowned champion! $un will will recieve a unique achievement worth 35 achievement points and ".T_WIN_XP." XP for their victory!\r\n\r\nThanks to all the players who participated and good luck next time!");

	return $rc;
}

function GetTournamentStatus( $tid, $playerid )
{
	$gameInfo = 0;
	$sql = "SELECT TO_CHAR(a.launchdate, 'Mon DD @ HH12:00 AM') as launchdate, a.finishdate,
			a.winningplayer, a.roundnum, a.tname, a.tformat, a.roundhours, a.pointstowin, a.mintostart,
			a.playercap, a.startcondition, gamemode,
			j.imagefile as imagefile, j.title, j.description, j.worth,
			TO_CHAR( (a.roundstartdate + (a.roundhours || ' hours')::INTERVAL), 'Mon DD @ HH12:00 AM') as nextrounddate,
			(SELECT count(*) FROM farkle_tournaments_players b WHERE b.tournamentid = a.tournamentid) as numplayers,
			COALESCE((SELECT 1 FROM farkle_tournaments_players c WHERE c.tournamentid = a.tournamentid AND playerid = :playerid), 0) as participant
		FROM farkle_tournaments a, farkle_achievements j WHERE a.achievementid = j.achievementid AND tournamentid = :tid";
	$tInfo = db_query($sql, [':playerid' => $playerid, ':tid' => $tid], SQL_SINGLE_ROW);

	if( $tInfo['roundnum'] > 0 )
	{
		// Complex query with multiple subqueries - using integer casting for safety
		$innerPiece = "SELECT a.roundnum, b.winningplayer, a.gameid, b.gamemode, b.currentturn, b.playerstring,
				c.playerid as p1id,
				c.username as p1u,
				d.playerround as p1rnd,
				d.playerscore as firstplayerscore,
				e.playerid as p2id,
				e.username as p2u,
				f.playerround as p2rnd,
				f.playerscore as secondplayerscore,
				(c.playerid = " . (int)$playerid . " OR e.playerid = " . (int)$playerid . ") as yourplayer
			FROM
				farkle_tournaments_games a,
				farkle_games b,
				farkle_players c, farkle_games_players d,
				farkle_players e, farkle_games_players f
			WHERE
				a.tournamentid = " . (int)$tid . " AND
				a.gameid = b.gameid AND
				d.gameid = b.gameid AND
				f.gameid = b.gameid AND
				c.playerid = d.playerid AND d.playerturn = 1 AND d.gameid = b.gameid AND
				e.playerid = f.playerid AND f.playerturn = 2 AND f.gameid = b.gameid
			";
		$innerPiece .= " UNION ";
		$innerPiece .= "SELECT a.roundnum, a.byeplayerid as winningplayer, 0 as gameid, 0 as gamemode, 0 as currentturn,
				CONCAT(c.username,' - Bye Round') as playerstring,
				a.byeplayerid as p1id, c.username as p1u, 0 as p1s, 0 as p1rnd,
				'0' as p2id, 'bye' as p2u, 0 as p2s, 0 as p2rnd,
				 (c.playerid = " . (int)$playerid . ") as yourplayer
			FROM farkle_tournaments_games a, farkle_players c
			WHERE a.tournamentid = " . (int)$tid . " AND a.byeplayerid = c.playerid";

		$sql = "SELECT * FROM (" . $innerPiece . ") g ORDER BY roundnum desc, (winningplayer > 0) ASC, yourplayer desc, p1id ASC";
		$gameInfo = db_query($sql, [], SQL_MULTI_ROW);
	}

	$sql = "SELECT a.username, a.playerid, a.playertitle, a.cardcolor, a.playerlevel,
		b.seednum
		FROM farkle_players a, farkle_tournaments_players b
		WHERE a.playerid = b.playerid AND b.tournamentid = :tid
		ORDER BY seednum";
	$pInfo = db_query($sql, [':tid' => $tid], SQL_MULTI_ROW);

	return Array( $tInfo, $gameInfo, $pInfo );
}

// Tokens: [tname] = tournament name
function EmailTournamentPlayers( $tid, $v_subj, $v_msg, $all=1 )
{
	// Email all the players letting them know the tournament has started.
	$sql = "SELECT a.playerid, email, devicetoken FROM farkle_players a, farkle_tournaments_players b, farkle_tournaments c
		WHERE a.playerid = b.playerid AND b.tournamentid = :tid AND c.tournamentid = b.tournamentid";

	if( !$all ) $sql .= " AND b.losses < c.tFormat";

	$recipients = db_query($sql, [':tid' => $tid], SQL_MULTI_ROW);

	$sql = "SELECT tname FROM farkle_tournaments WHERE tournamentid = :tid";
	$tData = db_query($sql, [':tid' => $tid], SQL_SINGLE_ROW);

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
	$sql = "SELECT min(tournamentid) as tournamentid, lobbyImage, EXTRACT(DAY FROM (now() - MAX(finishdate))) as DaysSinceFinished
	FROM farkle_tournaments
	WHERE winningplayer = 0 OR finishdate > NOW() - interval '3' day
	GROUP BY lobbyimage";
	$tInfo = db_query($sql, [], SQL_SINGLE_ROW);
	return $tInfo;
}

?>