<?php
/*
	farkleGameFuncs.php	
	Desc: Functions related to the game of Farkle. 
	
	17-Oct-2012		mas		Changed "rolling score" to be currentround-1
	13-Jan-2013		mas		Added support of Farkle tournaments. 
*/

require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleAchievements.php');
require_once('farkleDiceScoring.php');
require_once('farklePageFuncs.php');
require_once('farkleTournament.php');
require_once('iphone_funcs.php');
require_once('farkleLevel.php'); 

// Who a game will be against
define( 'GAME_WITH_RANDOM', 		0);		// Game against a random opponent
define( 'GAME_WITH_FRIENDS', 		1);		// Game against selected players
define( 'GAME_WITH_SOLO',			2);		// Single player (no wins or Xp granted) 

/// What kind of farkle game	
define( 'GAME_MODE_STANDARD', 		1); 	// Standard Farkle to 10,000 points.
define( 'GAME_MODE_10ROUND', 		2); 	// 10 Rounds - highest score wins.

define( 'LAST_ROUND', 				10); 	// Last round in a 10-round style game.
define( 'MAX_OVERTIME_ROUNDS',		5);		// Max overtime rounds allowed (sudden death)
define( 'ABSOLUTE_MAX_ROUND',		15);	// Hard cap: LAST_ROUND + MAX_OVERTIME_ROUNDS

define( 'MAX_GAMES_AGAINST_PLAYER', 12); 	// Max number of unfinished games a player can start against another player
define( 'MAX_UNFINISHED_GAMES',		20); 	// Max number of unfinished games a players can start without playing the rest

/*
	Func: FarkleNewGame()
	Desc: This is what a player will call when they create a new game. Has pre-requisite checking. 
	Params: 
		thePlayers		Players participating in the game (json array) e.x. [1,281,31]
		mBreakIn		Break-in amount. 
		pointsToWin		Point value until game completed in standard games
		gameWith		0=random, 1=friends, 2=solo
		gameMode		1=standard, 2=10-round
		tournamentGame	Is this a tournament game? 
		mRandomPlayers	Number of players allowed into this new random game. 
	Returns: 	
		GameID of the newly created game. 
*/
function FarkleNewGame( $thePlayers, $mBreakIn = 500, $pointsToWin = 5000, 
	$gameWith = GAME_WITH_FRIENDS, $gameMode = GAME_MODE_10ROUND, $tournamentGame=false, $mRandomPlayers=2 )
{
	//Tournament scripts are not logged in and thus we can't require a session here. 
	//if( !isset($_SESSION['playerid']) )
	//{
	//	BaseUtil_Error( __FUNCTION__ . ": New game with players $thePlayers attempted to be started while not logged in.");
	//	return Array( 'Error' => 'Must be logged into create a new game', 'NotLoggedIn'=>'1' ); 
	//}

	// Build the array of players. We might recieve bad input like [2,2,1] so we use array_values to shrink
	// the array indexes down so the for loop below will work. 
	$players 		= array_unique( json_decode( $thePlayers ) ); // PlayerIds // Remove duplicates
	$players 		= array_values( $players );
	$gameWith 		= intval($gameWith); 
	$whostarted		= ( isset($_SESSION['playerid']) ? $_SESSION['playerid'] : 0 );
	$breakIn 		= ( $gameMode == GAME_MODE_10ROUND ? 0 : $mBreakIn ); // Don't allow a break-in on 10-round games. 
	$gameDays 		= 3; // 72 hours to finish game. 
	
	// Verify our only two known values (2 and 4) are used. 
	$randomPlayers = $mRandomPlayers; 
	if( $mRandomPlayers != 2 && $mRandomPlayers != 4 ) $randomPlayers = 2; 
	
	BaseUtil_Debug( __FUNCTION__ . ": New game with players = $thePlayers. GameWith=$gameWith, GameMode=$gameMode, Random Player Count=$mRandomPlayers", 14 );
	
	// TBD: detect players array empty. 
	
	if( $gameWith != GAME_WITH_SOLO && $gameMode == GAME_MODE_10ROUND )
	{
		// Count how many other games this player has not even started. 
		$sql = "select count(*) as numGames 
			from farkle_games_players a, farkle_games b
			where a.playerid=$whostarted and a.gameid=b.gameid and gamewith != " . GAME_WITH_SOLO . " and playerround < 11 
			and b.winningplayer=0 and b.whostarted=$whostarted and b.gamewith in (0,1)";
		$notStartedGames = db_select_query( $sql, SQL_SINGLE_VALUE );
		
		if( $notStartedGames >= MAX_UNFINISHED_GAMES )
		{
			BaseUtil_Error( __FUNCTION__ . ": Player {$_SESSION['username']} ($whostarted) has too many games they have not finished ($notStartedGames games). Rejecting new game." );
			return Array( "Error" => "Please finish your rounds on a few of your other farkle games before creating more." );
		}
	}
	
	BaseUtil_Debug( __FUNCTION__ . ": Starting game based on gameWith=$gameWith, String=".GameWithToString($gameWith), 14 ); 
	if( $gameWith == GAME_WITH_FRIENDS )
	{
		// Don't let a player start more than 3 games against an opponent. 
		if( count($players) == 2 ) 
		{
			if( GetGameCountWithPlayer( $players[0], $players[1] ) >= MAX_GAMES_AGAINST_PLAYER && !$tournamentGame )
			{
				// Bail out because this player already has 3 games against this opponent. 				
				BaseUtil_Error( __FUNCTION__ . ": Player {$_SESSION['playerid']} has started too many unfinished games against a player." );
				return Array('Error' => "You have reached the limit of unfinished games against this player. While they finish their games try a random opponent!");
			}
		}
		BaseUtil_Debug( __FUNCTION__ . ": Creating a new Friends game", 14 );		
		$newGameId = CreateGameWithPlayers( $players, $whostarted, $gameDays, $gameWith, $gameMode );
	}
	else if( $gameWith == GAME_WITH_SOLO )
	{
		BaseUtil_Debug( __FUNCTION__ . ": Creating a new Solo game", 14 );
		$newGameId = CreateGameWithPlayers( $players, $whostarted, $gameDays, $gameWith, $gameMode );
	}
	else if( $gameWith == GAME_WITH_RANDOM )
	{
		BaseUtil_Debug( __FUNCTION__ . ": Creating a new Random game", 14 );	
		$newGameId = FarkleJoinRandomGame( $gameMode, $randomPlayers );
	}
	
	if( empty($newGameId) )
	{
		BaseUtil_Error( __FUNCTION__  . ": Error creating new game." ); 
		return Array('Error' => 'Error creating a new game. Please contact admin@farkledice.com!' ); 
	}
	
	return FarkleSendUpdate( $_SESSION['playerid'], $newGameId );
}

/*
	Func: CreateGameWithPlayers()
	Desc: Creates a new game with the specified parameters. This does not check any pre-requisites. 
	Params: 
		$whostarted		Playerid who started the game
		$players		Array of players who will participate in the game
		$expireDays		Days until the game expires via cron job process
		$gameWith		Friends, Random, Solo? 
		$gameMode		10-Round or Standard? 
	Returns: 	
		GameID of the newly created game. 
*/
function CreateGameWithPlayers( $players, $whostarted=0, $expireDays = 2, $gameWith = GAME_WITH_FRIENDS, $gameMode = GAME_MODE_10ROUND )
{
	if( count($players)==0 || empty($expireDays) || empty($gameWith) || empty($gameMode) )
	{
		BaseUtil_Error( __FUNCTION__ . ": Missing parameters. Bailing out. WhoStarted=$whostarted, Players=[" . implode(',',$players) . "], expiredays=$expireDays, gameWith=$gameWith, gameMode=$gameMode");
		error_log( __FUNCTION__ . ": Missing parameters. Bailing out." ); 
		return 0; 
	}
	
	BaseUtil_Debug( __FUNCTION__ . ": Creating new game with playerId's: [" . implode(',',$players) . "]", 14 );

	$currentTurn 	= rand( 1, count($players) ); 			// Pick a random player to start
	$thePlayers 	= json_encode( $players );
	$playerNames 	= "Solo Game";
	$titleRedeemed	= 0;
	if( empty($gameMode) ) $gameMode=GAME_MODE_10ROUND;
	
	// Fix a FRIENDS game that somehow only has 1 player. Convert this to a SOLO game. 
	if( count($players) < 2 && $gameWith != GAME_WITH_SOLO )
	{
		BaseUtil_Error( __FUNCTION__ . ": Player {$_SESSION['username']} ($whostarted) tried to start a game with players: $thePlayers 
			and GameWith=".GameWithToString($gameWith).". Rejecting game." );
		return 0;
	}
	
	// Fix a SOLO game with more than 1 player. 
	if( count($players) > 1 && $gameWith == GAME_WITH_SOLO )
	{
		BaseUtil_Error( __FUNCTION__ . ": Player {$_SESSION['username']} ($whostarted) tried to start a game with players: $thePlayers 
			and GameWith=".GameWithToString($gameWith).". Setting players to this player only." );
		$gameMode = GAME_WITH_SOLO;
		$players = Array( $_SESSION['playerid'] ); 
	}
	
	// Create the game
	$sql = "insert into farkle_games 
			(currentturn, maxturns, gamestart, mintostart, 
			lastturn, whostarted, playerarray, titleredeemed, gamemode, 
			gameexpire, playerstring, gamewith ) 
		values 
			($currentTurn, ".count($players).", NOW(), 0, 
			0, $whostarted, '$thePlayers', $titleRedeemed, $gameMode, 
			NOW() + ($expireDays || ' days')::INTERVAL, '$playerNames', $gameWith )";
	$result = db_command($sql);

	// Get the gameid of the game we just created
	$newGameId = db_insert_id();
	
	if( empty($newGameId ) )
	{
		// Something bad happened. Do no more.
		BaseUtil_Error( __FUNCTION__ . ": Failed to create new game." );
		// Error already logged by db_command
	}
	else
	{
		BaseUtil_Debug( __FUNCTION__ . ": New gameid=$newGameId", 1 );
		
		$playerString = GetFarkleGameName( $newGameId, $gameWith, $players, count($players) );		
		$sql = "update farkle_games set playerstring='$playerString' where gameid=$newGameId";
		$result = db_command($sql);
		
		if( !empty($newGameId) )
		{
			// Friends mode or Solo mode -- insert each player record for this game
			for( $i = 1; $i <= count($players); $i++ )
			{
				$playerIdVal = $players[$i-1];
				
				$sql = "insert into farkle_games_players (gameid, playerid, playerturn, lastplayed, playerround ) 
					values ($newGameId, $playerIdVal, $i, NOW(), 1 )";
				$result = db_command($sql);		

				Ach_CheckFriends( $players[$i-1] ); // Check for achievement starting a game against friends. 
			}
		}
	}
	
	if( count($players) > 1 && !empty($newGameId) && $whostarted > 0 )
	{
		NotifyOtherPlayersInGame( $newGameId, "{$_SESSION['username']} has challenged you!" );	
	}
	
	return $newGameId; 
}

// Returns an array of the players in a game.
function GetGamePlayerids( $gameid )
{
	$gamePlayers = Array();
	$sql = "select playerid from farkle_games_players where gameid=$gameid";
	$data = db_select_query( $sql, SQL_MULTI_ROW );
	foreach( $data as $d )
	{
		array_push($gamePlayers, $d['playerid'] );
	}
	BaseUtil_Debug( __FUNCTION__ . " Dumping players.", 14 ); 	
	return $gamePlayers; 
}

/*
	Func: GetFarkleGameName()
	Desc: Returns a new name for this farkle game (such as "mschmoyer vs. amy" or "3 players"). 
	Params: 
		gameid		Game to create a name for
		gameMode	Important for games like "Solo Game" or "Big Random Game" 
		maxTurns	Helps decide whether name is vs. or "big...something"
	Returns: 	
		playerString for this game
*/
function GetFarkleGameName( $gameid, $gameWith, $thePlayers, $maxTurns=2 )
{
	$playerCount = is_array($thePlayers) ? count($thePlayers) : $thePlayers;
	BaseUtil_Debug( __FUNCTION__ . ": Entered. Gameid=$gameid, gameWith=$gameWith, actualPlayers=$playerCount, maxTurns=$maxTurns", 14 );
	$gameName = "";

	// Bail if we got no gameid
	if( !isset($gameid) || empty($gameid ) || empty($thePlayers) )
	{
		BaseUtil_Error( __FUNCTION__ . ": Missing parameter. Gameid=$gameid, gameWith=$gameWith, playerCount=$playerCount, maxTurns=$maxTurns. Setting name to Farkle Game." );
		return "Farkle Game";
	}
	
	// Solo games are simply called Solo Game
	if( $gameWith == GAME_WITH_SOLO || (empty($gameWith) && $maxTurns<2) ) 
		return "Solo Game"; 
	
	// Might be a random game with only the player or an array of many players
	if( is_array($thePlayers) )
		$players = implode( ",", $thePlayers );
	else
		$players = $thePlayers;
	
	// Get names of players (the starting player first)

	$sql = "select distinct username, playerid
				from farkle_players where playerid in ($players)";
	$pNameData = db_select_query( $sql, SQL_MULTI_ROW );

	
	if( $gameWith == GAME_WITH_RANDOM && count($pNameData) == 1 && $maxTurns == 2 ) 
	{
		// Random games without a player joined just say vs. Random. 
		$gameName = $pNameData[0]['username'] . ' vs. Random';
	}
	else
	{
		if( count($pNameData) == 2 ) 
		{
			// Two player game vs. friends -- simply one vs. the other. 
			$gameName = $pNameData[0]['username'] . ' vs. ' . $pNameData[1]['username'];
		}
		else
		{	
			// More than 2 players. Either each name in a list or "x players".
			if( $gameWith == GAME_WITH_RANDOM )
			{
				$gameName = "Big Random Game"; 
			}
			else
			{
				// Loop through each name and add it with a comma. Then slice off the last comma. 
				foreach( $pNameData as $p )
					$gameName .= $p['username'] . ',';
				$gameName = substr( $gameName, 0, strlen($gameName)-1);
				
				// Name too long. Let's just say X players. 
				if( strlen($gameName) > 16 ) 
					$gameName = count($pNameData) . " players";
			}
		}
	}
	
	if( empty($gameName) )
	{
		BaseUtil_Error( __FUNCTION__ . ": Error naming game! Gameid=$gameid, gameWith=$gameWith, players=$players, maxTurns=$maxTurns. Setting it to something generic." );
		$gameName = "Farkle Game";
	}
	
	return $gameName;
}

/*
	Func: GetGameMaxRound()
	Desc: Returns the max round for a game (handles overtime games).
	Params:
		$gameid		Game to get max round for
	Returns:
		Max round number (10 normally, 11-15 during overtime)
*/
function GetGameMaxRound($gameid)
{
	$sql = "SELECT COALESCE(max_round, " . LAST_ROUND . ") FROM farkle_games WHERE gameid=$gameid";
	$maxRound = db_select_query($sql, SQL_SINGLE_VALUE);
	return empty($maxRound) ? LAST_ROUND : (int)$maxRound;
}

/*
	Func: IsGameInOvertime()
	Desc: Checks if a game is in overtime mode.
	Params:
		$gameid		Game to check
	Returns:
		Boolean - true if game is in overtime
*/
function IsGameInOvertime($gameid)
{
	$sql = "SELECT COALESCE(is_overtime, false) FROM farkle_games WHERE gameid=$gameid";
	$isOvertime = db_select_query($sql, SQL_SINGLE_VALUE);
	return $isOvertime == 't' || $isOvertime === true;
}

/*
	Func: TriggerOvertime()
	Desc: Triggers overtime for a game by incrementing max_round and setting is_overtime flag.
	Params:
		$gameid		Game to put into overtime
	Returns:
		New max round number
*/
function TriggerOvertime($gameid)
{
	$currentMaxRound = GetGameMaxRound($gameid);
	$newMaxRound = $currentMaxRound + 1;

	// Don't exceed the absolute max
	if ($newMaxRound > ABSOLUTE_MAX_ROUND) {
		BaseUtil_Debug(__FUNCTION__ . ": Game $gameid already at max overtime rounds. Cannot trigger more.", 1);
		return $currentMaxRound;
	}

	$sql = "UPDATE farkle_games SET max_round = $newMaxRound, is_overtime = true WHERE gameid = $gameid";
	db_command($sql);

	// Reset all players who were "done" back to the new round
	$sql = "UPDATE farkle_games_players SET playerround = $newMaxRound WHERE gameid = $gameid AND playerround > " . LAST_ROUND;
	db_command($sql);

	BaseUtil_Debug(__FUNCTION__ . ": Game $gameid entered overtime. New max round: $newMaxRound", 1);

	return $newMaxRound;
}

/*
	Func: FarkleJoinRandomGame()
	Desc: First tries to join a random game. If none available will create a new one. 
	Params: 
		gameMode		Only 10-round is currently supported.  
		randomPlayers	# of random players when this game is considered "full" 
	Returns: 	
		Newly created game's ID. 
*/
function FarkleJoinRandomGame( $gameMode=GAME_MODE_10ROUND, $theRandomPlayers=2 )
{
	
	$newGameId = 0;
	$makingNewGame = 0; 
	$randomPlayers = $theRandomPlayers; 
	
	if( empty($randomPlayers) || $randomPlayers < 2 || $randomPlayers > 4 )
	{
		BaseUtil_Error( __FUNCTION__ . ": randomPlayers parameter not set correctly. Adjusting to 2. Value was $randomPlayers" );
		$randomPlayers = 2; 
	}

	// -2 = Look for a random game
	// -1 = Start a new random game
	// 0+ = Look for a random game
	//if( !isset($_SESSION['lastrandomgame']) )
	//	$_SESSION['lastrandomgame'] = -2;
	
	if( isset($_SESSION['lastrandomgame']) )
	{		
		BaseUtil_Debug( __FUNCTION__ . ": LastRandomGame Session var = {$_SESSION['lastrandomgame']}", 1 );
	}
	
	$potSize = GetRandomGamePotSize();
	if( $potSize > 0 ) // Join any existing unfilled random game
	{
		//$newGameId = JoinAlreadyStartedRandomGame( $randomPlayers, $gameMode, $avoidPlayerid );
		$newGameId = JoinAlreadyStartedRandomGame( $randomPlayers, $gameMode );
	}

	if( empty($newGameId) )
	{
		$newGameId = CreateNewRandomGame( $randomPlayers, $gameMode ); 
	}
	
	if( empty($newGameId) )
	{
		BaseUtil_Error( __FUNCTION__ . ": No game joined or created. Did we attempt to make new game? $makingNewGame. GameMode=$gameMode, RandomPlayers=$randomPlayers" ); 
	}
	
	return $newGameId; 
}

function CreateNewRandomGameWithPlayers( $gameMode, $randomPlayers, $avoidPlayerid=0 )
{
	$avoidSql = "";
	$randomPlayerid = 0; 
	
	if( !empty($avoidPlayerid) )
	{
		$avoidSql = " and playerid not in ($avoidPlayerid)";
	}
	
	$sql = "select playerid	from farkle_players
		where lastplayed > NOW() - interval '2 weeks' and random_selectable > 0 $avoidSql
		order by lastplayed desc
		LIMIT 20";
	$randomPlayerid = db_select_query( $sql, SQL_SINGLE_VALUE );	
	
	if( !empty($randomPlayerid) )
		CreateGameWithPlayers( Array( $_SESSION['playerid'], $randomPlayerid ), $_SESSION['playerid'] );

	return $randomPlayerid; 
}

function CreateNewRandomGame( $randomPlayers, $gameMode=GAME_MODE_10ROUND ) 
{
	$gameDays = 2; // Random games go for 48 hours. 
	$newGameId = 0; 
	
	if( empty($gameMode) ) $gameMode=GAME_MODE_10ROUND;
	
	// Create the new random game. 
	$sql = "insert into farkle_games 
			(currentturn, maxturns, gamestart, pointstowin, mintostart,
			whostarted, playerarray, gameMode, gameexpire, 
			playerstring, gamewith ) 
		values 
			(1, $randomPlayers, NOW(), 10000, 0,
			{$_SESSION['playerid']}, '[{$_SESSION['playerid']}]', $gameMode, NOW() + ($gameDays || ' days')::INTERVAL, 
			'Random Game', ".GAME_WITH_RANDOM." )";
			
	if( db_command($sql) )
	{
		// Get the gameid of the game we just created
		$newGameId = db_insert_id();
		
		// Insert this player into the 1 position. 
		$sql = "insert into farkle_games_players 
			(gameid, playerid, playerturn, lastplayed, playerround ) values 
			($newGameId, {$_SESSION['playerid']}, 1, NOW(), 1)";
		if( db_command($sql) )
		{		
			// Update the name of the game.
			$playerString = GetFarkleGameName( $newGameId, GAME_WITH_RANDOM, $_SESSION['playerid'], $randomPlayers );
			$sql = "update farkle_games set playerstring='$playerString' where gameid=$newGameId";
			$result = db_command($sql);
		}
	}
	return $newGameId; 
}

// Return the current number of unfilled random games.
function GetRandomGamePotSize()
{
	$sql = "select count(*) from (select distinct playerstring from farkle_games a 
		where winningplayer=0 and gamewith=0 and
		maxturns > (select count(*) from farkle_games_players b where b.gameid=a.gameid)) c";
	$potSize = db_select_query( $sql, SQL_SINGLE_VALUE );
	return $potSize; 
}

function JoinAlreadyStartedRandomGame( $randomPlayers, $gameMode=GAME_MODE_10ROUND, $avoidPlayerid = 0 ) 
{
	$foundGameId = 0; 
	if( empty($gameMode) ) $gameMode=GAME_MODE_10ROUND;
	
	// Find all games that are missing players, 10-round, started within the last 3 days, and you are not already in. 
	// These are candidates to add this player to. 
	$playersToAvoid = "";
	if( !empty($avoidPlayerid) )
		$playersToAvoid = " and b.playerid != $avoidPlayerid"; 
	
	// 18-Dec-2014: Changed to choose a random row from among the unfilled random games. 
	$sql = "select a.gameid from farkle_games a, farkle_games_players b
				where maxturns > (select count(*) from farkle_games_players where gameid=a.gameid) 
				and maxturns = $randomPlayers and winningplayer = 0 and gameexpire > NOW() and gamestart < NOW()
				and gamemode=$gameMode and gamestart > NOW() - interval '3' day
				and a.gameid=b.gameid and b.playerid != {$_SESSION['playerid']}
				$playersToAvoid
				order by RANDOM() 
				LIMIT 1";
	$foundGameId = db_select_query( $sql, SQL_SINGLE_VALUE );	
	
	if( $foundGameId )
	{
		//error_log( __FUNCTION__ . ": We found a suitable random game ($foundGameId) to join. Adding {$_SESSION['username']} ({$_SESSION['playerid']}) to this game" ); 
		
		$sql = "select max(playerturn) from farkle_games_players where gameid=$foundGameId";
		$highestTurn = db_select_query( $sql, SQL_SINGLE_VALUE );
		
		$sql = "insert into farkle_games_players 
			(gameid, playerid, playerturn, lastplayed, playerround ) values 
			($foundGameId, {$_SESSION['playerid']}, $highestTurn+1, NOW(), 1)";
		if( db_command($sql) )
		{									
			$playerString = GetFarkleGameName( $foundGameId, GAME_WITH_RANDOM, GetGamePlayerids($foundGameId), $randomPlayers );
				
			$sql = "update farkle_games set playerstring = '$playerString' where gameid=$foundGameId";
			$result = db_command($sql);	
		}
		$newGameId = $foundGameId; // This is so we report back to the client what new game you just joined. 			
		$makingNewGame = 0; 

		NotifyOtherPlayersInGame( $newGameId, "{$_SESSION['username']} has joined your game." );
	}
	return $foundGameId; 
}

/*
	Func: GetGameCountWithPlayer()
	Desc: Returns the count of games this player (player1) is already playing with player2. 
	Params: 
		player1		Checking this player for dupes against...  
		player2		...this player. 
	Returns: 	
		Number of dupes already created. 
*/
function GetGameCountWithPlayer( $player1, $player2 )
{
	// Pick the opponent (the only other player...who is not you)
	$opponent = ( $player1 == $_SESSION['playerid'] ? $player2 : $player1 );

	// Count how many other games you are playing with this player already where someone has still not started. 
	$sql = "select count(*) as numGames 
		from farkle_games a, farkle_games_players b, farkle_games_players c
		where a.gameid=b.gameid and a.gameid=c.gameid and b.gameid=c.gameid 
		and a.gamemode=".GAME_MODE_10ROUND."
		and b.playerid=$player1
		and c.playerid=$player2
		and (b.playerround=1 or c.playerround=1)
		and a.maxturns=2
		and a.winningplayer=0";
	$dupeGames = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	return $dupeGames; 
}

/*
	Func: FarkleSendUpdate()
	Desc: Sends game data to the client
	Params: 
		playerid		Should always be the session's player 
		gameid		The game to send an update on. 
	Returns: 	
		Lots of game specific and player related data. 
*/
function FarkleSendUpdate( $playerid, $gameid )
{	
	if( !isset($playerid) || $playerid < 1 || empty($gameid) )
	{
		BaseUtil_Error( __FUNCTION__ . ": Missing parameters. Bailing out. playerid=$playerid, gameid=$gameid");
		return Array( 'Error' => 'Game not found.' );
	}
	
	// Record the last place the player was.
	$_SESSION['farkle']['lastknownscreen'] = 'game';
	$_SESSION['farkle']['lastgameid'] = $gameid; 
	
	// If we're not a member of this game, set the playerid to the most recent roller 
	$sql = "select playerid from farkle_games_players where playerid=$playerid and gameid=$gameid";
	$memberOfGame = db_select_query( $sql, SQL_SINGLE_VALUE );
	if( !$memberOfGame )
	{
		$sql = "select playerid, max(lastplayed) from farkle_games_players where gameid=$gameid group by playerid";
		$mostRecentPlayer = db_select_query( $sql, SQL_SINGLE_VALUE );
		$playerid = $mostRecentPlayer;
	}
	
	if( !isset($playerid) || $playerid < 1 )
	{
		BaseUtil_Error( __FUNCTION__ . ": Missing parameters. Bailing out. playerid=$playerid, gameid=$gameid");
		return Array( 'Error' => 'Game not found.' );
	}
	
	// Get the current and max turn (includes overtime support fields)
	$sql = "select a.currentturn, a.currentround, a.maxturns, a.winningplayer, a.mintostart,
		a.pointstowin, a.gameid, a.gamemode, a.gamewith, b.playerround,
		TO_CHAR(a.gameexpire, 'Mon DD @ HH12:00 AM') as gameexpire,
		TO_CHAR(a.gamefinish, 'Mon DD @ HH12:00 AM') as gamefinish,
		(select playerid from farkle_games_players where playerturn=currentturn and gameid=a.gameid) as currentplayer,
		lastturn, titleredeemed, b.winacknowledged,
		COALESCE(a.max_round, " . LAST_ROUND . ") as max_round,
		COALESCE(a.is_overtime, false) as is_overtime
		from farkle_games a, farkle_games_players b
		where a.gameid=$gameid and a.gameid=b.gameid and b.playerid=$playerid";
		
	$turnData = db_select_query( $sql, SQL_SINGLE_ROW );
	if( !$turnData ) 
	{
		BaseUtil_Error( __FUNCTION__ . ": Error! Game #$gameid returned no turn data." );
		return Array( 'Error' => 'Error getting game information. Please contact admin@farkledice.com' ); 
	}
	$turnDataForReturning = $turnData;
	
	// Get the current round (player's turn in 10-round or the game's turn in standard) 
	$currentRound = $turnData['gamemode'] == GAME_MODE_STANDARD ? $turnData['currentround'] : $turnData['playerround'];
	BaseUtil_Debug( __FUNCTION__ . ": 1CurrentRound = $currentRound, GameMode=" . $turnData['gamemode'], 1 );
	
	// This is a boundary condition for a 10-round game that reports a round higher than the max.
	// Use dynamic max_round to support overtime
	$gameMaxRound = GetGameMaxRound($gameid);
	if( $turnData['gamemode'] == GAME_MODE_10ROUND && (int)$currentRound > $gameMaxRound )
	{
		$currentRound = $gameMaxRound;
	}
	
	BaseUtil_Debug( __FUNCTION__ . ": CurrentRound = $currentRound", 1 );
	
	// If they are viewing a game after it has been won then make sure it dissapears off the main screen and appears on the player screen.
	if( $turnData['winacknowledged'] == '0' && $turnData['winningplayer'] > 0 )
	{
		$sql = "update farkle_games_players set winacknowledged=true where gameid=$gameid and playerid=$playerid";
		$rc = db_command($sql);
	}
	
	if( $turnData['gamemode'] == GAME_MODE_STANDARD )
	{
		$curplayerid = $turnData['currentplayer'];	
		
		// Get current set. If there is no data yet (player has not yet rolled) then show what last round looked like
		$currentSet = FarkleGetCurrentSet( $curplayerid, $gameid, $currentRound);
		$diceSet = $currentSet;
		if( empty($diceSet) )
		{
			$newTurnData = FarkleGetLastTurn( $gameid );

			if( !empty($newTurnData['playerid']) )
			{
				$turnData['currentturn'] = $newTurnData['turn'];
				$currentRound = $newTurnData['round'];
				$curplayerid = $newTurnData['playerid'];
				
				if( !empty($currentRound) )
				{
					$diceSet = FarkleGetCurrentSet( $curplayerid, $gameid, $currentRound);
					BaseUtil_Debug( __FUNCTION__ . ": Trying to get last set with Turn=" . $newTurnData['turn'] . ", Round=" . $newTurnData['round'], 7 );
				}
			}
			else
			{
				//Well, this game is messed up...could be from bad deletes. 
				BaseUtil_Debug( __FUNCTION__ . ": FarkleSendUpdate: Cannot determine current player or round in Game#$gameid", 0 );
				trigger_error("FarkleSendUpdate: Cannot determine current player or round in Game#$gameid", E_USER_ERROR);
			}
		}
		BaseUtil_Debug( __FUNCTION__ . ": Last applicable set = [$diceSet]", 7 );
	}
	else
	{
		$curplayerid = $playerid;
		$diceSet = FarkleGetCurrentSet( $curplayerid, $gameid, $currentRound);
		$currentSet = $diceSet;
		//$currentRound = $turnData['playerround'];
	}

	// Get dice & scored values for the 6 dice. We can tell which dice the player has saved in this data
	$sql = "select d1, d2, d3, d4, d5, d6 from farkle_sets
		where gameid=$gameid and roundnum=$currentRound and playerid=$curplayerid
		order by setnum desc LIMIT 2";
	$setData = db_select_query( $sql, SQL_MULTI_ROW );		
	
	if( !empty($setData) ) $setData = ConvertTableToDiceArray($setData[0]);
		
	// We'll use this to show all the actual dice on the table. 
	$diceOnTable = GetDiceOnTheTable( $curplayerid, $gameid, $currentRound, $diceSet );
	
	$turnDataForReturning['currentset'] = $currentSet;
	
	$rollingScore = "";
	if( $turnData['gamemode'] == GAME_MODE_10ROUND )
		$rollingScore = "COALESCE((select sum(roundscore) from farkle_rounds where playerid=a.playerid and gameid=$gameid and roundnum<$currentRound),0) as rollingscore, ";
	
	// Get information about the players
	$sql = "select b.username, a.playerid, a.playerround,
		a.playerscore, b.cardcolor, b.playerlevel,
		a.lastxpgain, a.lastroundscore, $rollingScore
		COALESCE((select COALESCE(sum(setscore),0) from farkle_sets where playerid=a.playerid and gameid=$gameid and roundnum=$currentRound),0) as roundscore,
		a.playerturn, COALESCE(b.playertitle,'') as playertitle, b.titlelevel,
		NOW() - a.lastplayed as lastplayedseconds,
		b.is_bot, b.bot_algorithm, b.personality_id,
		COALESCE(a.emoji_sent, '') as emoji_sent,
		COALESCE(b.emoji_reactions, '') as emoji_reactions
		from farkle_games_players a, farkle_players b
		where a.gameid=$gameid and a.playerid=b.playerid
		ORDER BY (a.playerid=$playerid) desc, a.playerscore desc, b.lastplayed desc";
	$playerData = db_select_query( $sql, SQL_MULTI_ROW );
	if( !$playerData ) 
	{
		BaseUtil_Error( __FUNCTION__ . ": Error! Could not get player information about game $gameid.");
		return Array( 'Error' => 'Could not get information about players in this game. Please contact admin@farkledice.com' );
	}	
	
	// Check if we should show the emoji picker for this player
	// Conditions: game finished, 2 players only, within 1 day, player hasn't submitted emoji yet
	$showEmojiPicker = false;
	if( $turnDataForReturning['winningplayer'] > 0 && $memberOfGame )
	{
		// Check player count and emoji status
		$sql = "SELECT
			(SELECT COUNT(*) FROM farkle_games_players WHERE gameid = $gameid) as player_count,
			(SELECT emoji_given FROM farkle_games_players WHERE gameid = $gameid AND playerid = $playerid) as emoji_given,
			(SELECT gamefinish FROM farkle_games WHERE gameid = $gameid) as gamefinish";
		$emojiCheck = db_select_query( $sql, SQL_SINGLE_ROW );

		if( $emojiCheck )
		{
			$playerCount = (int)$emojiCheck['player_count'];
			$emojiGiven = ($emojiCheck['emoji_given'] === 't' || $emojiCheck['emoji_given'] === true || $emojiCheck['emoji_given'] == 1);
			$gamefinish = $emojiCheck['gamefinish'];

			// Check if game finished within 1 day
			$gameFinishedRecently = false;
			if( !empty($gamefinish) )
			{
				$sql = "SELECT (NOW() - '$gamefinish'::timestamp) < INTERVAL '1 day' as recent";
				$recentCheck = db_select_query( $sql, SQL_SINGLE_VALUE );
				$gameFinishedRecently = ($recentCheck === 't' || $recentCheck === true);
			}

			// Show emoji picker if: 2 players, game finished recently, and player hasn't submitted emoji
			$showEmojiPicker = ($playerCount == 2 && $gameFinishedRecently && !$emojiGiven);
		}
	}
	$turnDataForReturning['show_emoji_picker'] = $showEmojiPicker;

	return Array( $turnDataForReturning, $playerData, $setData, $diceOnTable, Ach_GetNewAchievement( $playerid ), $gameid, GetNewLevel( $playerid ), GetGameActivityLog( $gameid ) );
}


function FarkleGetCurrentSet( $playerid, $gameid, $currentRound )
{
	$sql = "select max(setnum) from farkle_sets where playerid=$playerid and gameid=$gameid and roundnum=$currentRound";
	$currentSet = db_select_query( $sql, SQL_SINGLE_VALUE );
	return ( empty($currentSet) ? 0 : $currentSet );
}

// Gets the last turn played. This is a little complicated in that the turn might be 1 so the last
// turn will be the max turn and the round be 1-currentround. 
// Returns an array with (player, round, turn) 
function FarkleGetLastTurn( $gameid )
{	
	$sql = "select currentturn, currentround, maxturns 
		from farkle_games where gameid=$gameid";
	$curTurn = db_select_query( $sql, SQL_SINGLE_ROW );
	$turn = $curTurn['currentturn'] -= 1;
	if( $turn <= 0 )
	{
		$round = (int)$curTurn['currentround'] - 1;
		$turn = $curTurn['maxturns'];
	}
	else
	{
		$round = $curTurn['currentround'];
		$turn = $curTurn['maxturns'];
	}
	
	$sql = "select playerid from farkle_games_players where gameid=$gameid and playerturn=$turn";
	$playerid = db_select_query( $sql, SQL_SINGLE_VALUE );
	return Array('playerid'=>$playerid, 'round'=>$round, 'turn'=>$turn );
}	

// Returns an extrapolated set of the actual dice still on the table currently. 
function GetDiceOnTheTable( $playerid, $gameid, $roundnum, $setnum )
{
	$diceOnTable = null;
	if( empty($setnum) || empty($roundnum) || empty($playerid) || empty($gameid) ) 
		return null; 
	
	$sql = "select max(handnum) from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and setnum=$setnum";
	$handNum = db_select_query( $sql, SQL_SINGLE_VALUE );		
	if( empty($handNum) ) return Array( $diceOnTable ); 
	
	$sql = "select
		CASE WHEN d1<>0 THEN d1 ELSE (select max(d1save) from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and handnum=$handNum and d1save between 1 and 6) END d1,
		CASE WHEN d2<>0 THEN d2 ELSE (select max(d2save) from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and handnum=$handNum and d2save between 1 and 6) END d2,
		CASE WHEN d3<>0 THEN d3 ELSE (select max(d3save) from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and handnum=$handNum and d3save between 1 and 6) END d3,
		CASE WHEN d4<>0 THEN d4 ELSE (select max(d4save) from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and handnum=$handNum and d4save between 1 and 6) END d4,
		CASE WHEN d5<>0 THEN d5 ELSE (select max(d5save) from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and handnum=$handNum and d5save between 1 and 6) END d5,
		CASE WHEN d6<>0 THEN d6 ELSE (select max(d6save) from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and handnum=$handNum and d6save between 1 and 6) END d6
	from farkle_sets
	where gameid=$gameid and playerid=$playerid and roundnum=$roundnum and setnum=$setnum";
	$diceOnTable = db_select_query( $sql, SQL_SINGLE_ROW );
	return ConvertTableToDiceArray( $diceOnTable );
}

function FarkleRoll( $playerid, $gameid, $theSavedDice, $theNewDice, $skipNextRound = 0 )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered. Playerid=$playerid, Gameid=$gameid, SavedDice=$theSavedDice, NewDice=$theNewDice, SkipNextRound=$skipNextRound", 7 );
	$currentSet = 0; $cheatDetected = 0; $setScore = 0; $numDiceSaved = 0; $handNum = 1;
	$savedDice = json_decode( $theSavedDice );
	$newDice = json_decode( $theNewDice );
	
	// Record the last place the player was.
	$_SESSION['farkle']['lastknownscreen'] = 'game';
	$_SESSION['farkle']['lastgameid'] = $gameid; 
	
	// Get game & player information
	$sql = "select a.currentturn, b.playerturn, a.gamemode,	a.currentround, b.playerround 
		from farkle_games a, farkle_games_players b
		where a.gameid=$gameid and a.gameid=b.gameid and b.playerid=$playerid";
	$gData = db_select_query( $sql, SQL_SINGLE_ROW );
	
	if( !$gData )
	{
		BaseUtil_Error( __FUNCTION__ . ": ERROR: Player $playerid not part of game $gameid. Rejecting roll." );
		return Array('Error' => 'Cannot roll because you are not sitting at this table.'); 
	}
	
	// Current round is the game round (standard) or the player's round (10-Round)
	$currentRound = $gData['gamemode'] == GAME_MODE_STANDARD ? $gData['currentround'] : $gData['playerround'];
	
	// It's your turn if playerturn=currentturn OR you are playing 10-Round, in which it's always your turn
	$isMyTurn = 0;
	$isMyTurn = (($gData['currentturn'] == $gData['playerturn']) || $gData['gamemode'] == GAME_MODE_10ROUND);
	
	BaseUtil_Debug( __FUNCTION__ . ": CurrentRound=$currentRound, IsMyTurn? [$isMyTurn]", 7 );
	
	// Exit if it is not officially their turn. 
	if( empty($isMyTurn) ) 
	{
		BaseUtil_Error(  __FUNCTION__ . ": Not player $playerid's turn. Game=$gameid" );
		return Array('Error' => 'It is not your turn to roll.');
	}
	
	// Determine if this is the first set of the new round
	$sql = "select max(setnum) from farkle_sets where playerid=$playerid and gameid=$gameid and roundnum=$currentRound";
	$currentSet = db_select_query( $sql, SQL_SINGLE_VALUE );
		
	// We have a previous set, so score incoming dice...
	if( !empty($currentSet) )
	{			
		BaseUtil_Debug( __FUNCTION__ . ": Previous set found. Scoring incoming dice.", 7); 
		
		// Check to see if they saved any new dice. 0 = not saved and 10 = already scored. 
		if( !empty($savedDice) && !in_array( 1, $savedDice) && !in_array( 2, $savedDice) && !in_array( 3, $savedDice) &&
			!in_array( 4, $savedDice) && !in_array( 5, $savedDice) && !in_array( 6, $savedDice))
		{
			BaseUtil_Debug( __FUNCTION__ . ": Player has already rolled. Cannot roll without saving at least one dice.", 1 );
			return Array('Error'=>'You must select at least one dice.'); 
		}	
		
		// Get the current dice values for this game/round/set
		$sql = "select d1, d2, d3, d4, d5, d6, handnum from farkle_sets 
			where playerid=$playerid and gameid=$gameid and roundnum=$currentRound and setnum=$currentSet";
		$lastRolls = db_select_query( $sql, SQL_SINGLE_ROW );
		$handNum = $lastRolls['handnum'];
		
		// Make sure the dice values recieved from the client match what we said exist
		$numDiceSaved = 0;
		for( $i=0; $i<=5; $i++ )
		{
			$j = $i+1;
			if( $lastRolls["d$j"] != 0 && $savedDice[$i] != 0 && $savedDice[$i] != 10 )
			{
				if( (int)$lastRolls["d$j"] % (int)$savedDice[$i] <> 0 )
				{
					BaseUtil_Debug( __FUNCTION__ . ": Cheat detected or network issue. Dice values do not match. Game=$gameid, Player=$playerid, Round=$currentRound, Set=".($currentSet-1), 7 );
					$cheatDetected = 1;
				}
			}
			if( $lastRolls["d$j"] == $savedDice[$i] || $savedDice[$i] == 10 ) $numDiceSaved++; 
		}
		
		// If the dice don't match then don't update the set (bogus data) and return an error. This could
		// also be network connection issues so we shouldn't display anything at the client. 
		if( !$cheatDetected ) 
		{
			$setScore = farkleScoreDice( $savedDice, $playerid );
			
			//$rc = Ach_CheckDice( $playerid, $savedDice );

			$sql = "update farkle_sets set setscore=$setScore, handnum=$handNum, 
			d1save=".$savedDice[0].", d2save=".$savedDice[1].", d3save=".$savedDice[2].", 
			d4save=".$savedDice[3].", d5save=".$savedDice[4].", d6save=".$savedDice[5]." 
			where playerid=$playerid and gameid=$gameid and roundnum=$currentRound and setnum=$currentSet";
			$result = db_command($sql);
		}
		else
		{
			return Array('Error' => 'Network error.');
		}
		
		if( $setScore <= 0 && !empty($currentSet) ) FarklePass( $playerid, $gameid, $savedDice, 1 );
	}
	
	if( !$skipNextRound )
	{
		// Calculate new dice rolls...
		$dice = Array();
		$diceRolled = 0;
		for( $i=0; $i<6; $i++ )
		{
			if( !empty( $savedDice ) && $savedDice[$i] > 0 && $numDiceSaved < 6 )
			{
				$dice[$i] = 0; // An already scored dice. 
			}
			else
			{
				// Roll the dice...
				if( isset($newDice[$i]) )				
					$dice[$i] = $newDice[$i];
				else
					$dice[$i] = rand( 1, 6 );
				
				$diceRolled++;
			}
		}
		
		// If we have finished a hand and are ready to roll 6 more dice, increment the handNum
		if( $numDiceSaved >= 6 )
		{
			$handNum++;
			Ach_AwardAchievement( $playerid, ACH_ROLLALL6DICE );
		}

		// Insert new turn data
		$diceValues = implode( ',', $dice );
		if( empty($currentSet) ) $currentSet = 0;
		$sql = "insert into farkle_sets 
			( playerid, gameid, roundnum, handnum, setnum, d1, d2, d3, d4, d5, d6 ) values 
			( $playerid, $gameid, $currentRound, $handNum, $currentSet+1, $diceValues )";
		$result = db_command($sql);
		
		// We need to check the new roll to see if it's an instant farkle
		$newDiceScore = farkleScoreDice( $dice, $playerid );
		if( $newDiceScore <= 0 ) 
		{
			FarklePass( $playerid, $gameid, $savedDice, 1 ); 
			Ach_AwardAchievement( $playerid, ACH_FARKLE );
			
			// Award the big farkle for farkling on the first roll. 
			if( $numDiceSaved == 0 )
				Ach_AwardAchievement( $playerid, ACH_FARKLE_HARD );
		}
		else
		{
			if( $diceRolled == 1 )
			{
				// Rolled 1 dice and successfully got something. 
				Ach_AwardAchievement( $playerid, ACH_GOT_6TH_DICE );					
			}
		}
		
		// Update the lastplayed and rolls
		$sql = "update farkle_players set lastplayed=NOW(), rolls=rolls+1 where playerid=$playerid";
		$result = db_command($sql);
		
		return Array( FarkleSendUpdate( $playerid, $gameid ), $newDiceScore, $setScore, $numDiceSaved, $dice ); 
	}
	else
	{
		return $setScore; // What do i need here...
	}
}

function FarklePass( $playerid, $gameid, $savedDice, $farkled = 0, $updateTime = 1, $inactivePass=0 )
{
	$roundScore = 0; $setScore = 0; $gameOver = 0; $playerScore = 0; $setNum = 0; 
	
	// Get the current and max turn
	$sql = "select a.*, b.playerround, b.playerturn, b.playerscore
		from farkle_games a, farkle_games_players b
		where a.gameid=b.gameid and a.gameid=$gameid and b.playerid=$playerid";
	$gameData = db_select_query( $sql, SQL_SINGLE_ROW );
	
	// Current round is the game round (standard) or the player's round (10-Round)
	$currentRound = $gameData['gamemode'] == GAME_MODE_STANDARD ? $gameData['currentround'] : $gameData['playerround'];
	$playerScore = $gameData['playerscore'];
	
	// Bail if player is not suppose to be here.
	// Use dynamic max_round to support overtime
	$gameMaxRound = GetGameMaxRound($gameid);
	if( $gameData['gamemode'] == GAME_MODE_10ROUND && $currentRound > $gameMaxRound )
	{
		// This probably occurs from spam clicking on the last round.
		BaseUtil_Debug( __FUNCTION__ . ": Game $gameid already finished (max_round=$gameMaxRound). Player $playerid cannot roll. Rejecting save.", 1 );
		
		// Since we're stopping it here, the player did nothing to warrant an error message. Simply send them a game update again. 
		return FarkleSendUpdate( $playerid, $gameid );
	}
	
	// This will score the last round of dice and the $passed=1 says not to start a new turn
	if( !$farkled )
	{
		$setScore = FarkleRoll( $playerid, $gameid, $savedDice, null, 1 );
		if( isset($setScore['Error']) ) 
		{
			BaseUtil_Error( __FUNCTION__ . ": Error rolling dice for player $playerid in game $gameid. Rejecting save." );
			return $setScore;			
		}
		if( $setScore > 0 )
		{
			// Get values of all sets. We'll sum it up for roundscore and count the number for bonus multipliers
			$sql = "select COALESCE(setscore,0) as setscore from farkle_sets where gameid=$gameid and playerid=$playerid and roundnum=$currentRound";
			$sets = db_select_query( $sql, SQL_MULTI_ROW );
			foreach( $sets as $s ) 
			{ 
				$roundScore += $s['setscore']; 
			}
			$setNum = count( $sets );
		}
	}

	// It's your turn if playerturn=currentturn OR you are playing 10-Round, in which it's always your turn
	// Exit if it is not officially their turn. 
	if( !(($gameData['currentturn'] == $gameData['playerturn']) || $gameData['gamemode'] == GAME_MODE_10ROUND) ) 
	{
		BaseUtil_Error( __FUNCTION__ . ": Not player $playerid turn in game $gameid. Rejecting save." );	
		return Array('Error' => 'Cannot roll. It is not your turn.'); 
	}
	
	BaseUtil_Debug( __FUNCTION__ . ": Player round score=$roundScore, player's previous score=$playerScore.", 7 );
			
	// If they did not reach the breakIn then Farkle the player. Only applies to standard games.
	if( $roundScore < $gameData['mintostart'] && empty($playerScore) && $gameData['gamemode'] != GAME_MODE_10ROUND )
	{
		$roundScore = 0;
		BaseUtil_Debug( __FUNCTION__ . ": Player $playerid did not me4et break-in. Round score is 0.", 1 );
	}

	$playerScore = (int)$gameData['playerscore'] + $roundScore;
	
	// Calculate and insert this round score
	$sql = "insert into farkle_rounds ( playerid, gameid, roundnum, roundscore, rounddatetime ) 
		values ( $playerid, $gameid, $currentRound, $roundScore, NOW() )";
	if( !db_command($sql) )
	{
		BaseUtil_Error( __FUNCTION__ . ": Round $currentRound in game $gameid for player $playerid already scored. Invalid round save. Bailing" );
		return Array( "Error" => "Game Error. Contact admin@farkledice.com" ); 
	}
	
	// Player farkled -- give them achievement.
	if( $roundScore == 0 ) 
	{
		Ach_AwardAchievement( $playerid, ACH_FARKLE );
		Ach_CheckFarkles( $playerid );
	}
	Ach_CheckRoundScore( $playerid, $roundScore ); // Check to see if they got an achievement for score
	
	// Update the game/player data
	$sql = "update farkle_games_players set playerscore=$playerScore, lastroundscore=$roundScore
		where gameid=$gameid and playerid=$playerid"; //, lastxpgain=$xpGain
	$result = db_command($sql);

	// Append data to query if necessary
	$farkleSql = ( $farkled ? ",farkles=farkles+1" : "" );
	$highest10Round = ( $gameData['gamemode'] == GAME_MODE_10ROUND ? ", highest10round=GREATEST(COALESCE(highest10Round,0), $playerScore)" : "" );
	
	if( $roundScore > 750 ) 
	{	
		GivePlayerXP( $playerid, PLAYERLEVEL_GOODROLL_XP );
	}
	if( $roundScore > 1500 ) 
	{
		NotifyOtherPlayersInGame( $gameid, "{$_SESSION['username']} just rolled a $roundScore against you!" ); 
	}
	
	// Update player stats
	$sql = "update farkle_players set  
		lastplayed=NOW(),			
		highestround=GREATEST(COALESCE(highestround,0),$roundScore),
		totalpoints=totalpoints+$roundScore,
		roundsplayed=roundsplayed+1,
		avgscorepoints=avgscorepoints+$roundScore
		$farkleSql $highest10Round	
		where playerid=$playerid";
	$result = db_command($sql);
	
	$nextPlayerRound = (int)$gameData['playerround'] + 1;
	BaseUtil_Debug( __FUNCTION__ . ": Incrementing player round to $nextPlayerRound", 1 );
	
	$updateTimeClause = ( !empty($updateTime) ? ", lastplayed=NOW() " : "" );
	
	// Update the game/player data
	$sql = "update farkle_games_players set 
				inactivepasses=0, 
				playerround=$nextPlayerRound 
				$updateTimeClause 
			where gameid=$gameid and playerid=$playerid";
	$result = db_command($sql);
	
	if( $currentRound >= $gameMaxRound )
	{
		// This player has finished their rounds (including any overtime rounds)
		// XP is given based on standard 10 rounds - overtime doesn't give extra XP
		if( $currentRound == LAST_ROUND || !IsGameInOvertime($gameid) )
		{
			GivePlayerXP( $playerid, PLAYERLEVEL_FINISH_XP + (1 * ($gameData['maxturns'] - 2)) );
		}

		Ach_CheckPerfectGame( $playerid, $gameid );

		// Check if the game is finished (handles overtime tie detection)
		GameIsCompleted( $gameid, $gameData['maxturns']);
	}

	// For bot games in interactive mode (player turns only), advance to next player's turn
	// (Bot turns handle their own advancement in Bot_Step_Banking/Farkled)
	if( $gameData['gamemode'] == GAME_MODE_10ROUND ) {
		$sql = "SELECT bot_play_mode FROM farkle_games WHERE gameid = $gameid";
		$botMode = db_select_query($sql, SQL_SINGLE_VALUE);

		if( $botMode == 'interactive' ) {
			// Check if this is a player (not bot) completing their turn
			$sql = "SELECT is_bot FROM farkle_players WHERE playerid = $playerid";
			$isBot = db_select_query($sql, SQL_SINGLE_VALUE);

			if( !$isBot ) {
				// Get current turn and number of players
				$sql = "SELECT currentturn,
				        (SELECT COUNT(*) FROM farkle_games_players WHERE gameid = $gameid) as num_players
				        FROM farkle_games WHERE gameid = $gameid";
				$turnData = db_select_query($sql, SQL_SINGLE_ROW);

				$nextTurn = ($turnData['currentturn'] % $turnData['num_players']) + 1;
				error_log("FarklePass: Player completed turn, advancing from {$turnData['currentturn']} to $nextTurn for bot game");

				$sql = "UPDATE farkle_games SET currentturn = $nextTurn WHERE gameid = $gameid";
				db_command($sql);
			}
		}
	}

	// Return the player's last round score
	return FarkleSendUpdate( $playerid, $gameid );
}

//10-Round only. Supports overtime rounds for tie-breaking.
function GameIsCompleted( $gameid, $maxTurns )
{
	$highestScore = 0;
	$tieOccurred = 0;
	$i = 0;
	$gameFinished = 0;

	// Get the current max round for this game (handles overtime)
	$gameMaxRound = GetGameMaxRound($gameid);

	// Are all players done with current max round?
	$sql = "select count(*) from farkle_games_players where playerround > $gameMaxRound and gameid=$gameid";
	$playersDone = db_select_query( $sql, SQL_SINGLE_VALUE );

	BaseUtil_Debug( __FUNCTION__ . ": Game $gameid. Players done: $playersDone, Max round: $gameMaxRound", 1 );

	if( $playersDone >= $maxTurns )
	{
		// Select the playerid with the highest score in this game.
		$sql = "select a.playerid, a.playerscore, b.username,
			(select max(roundscore) from farkle_rounds where playerid=a.playerid and gameid=a.gameid) as highestRound
			from farkle_games_players a, farkle_players b
			where a.gameid=$gameid and a.playerid=b.playerid
			order by playerscore DESC, highestRound desc";
		$wp = db_select_query( $sql, SQL_MULTI_ROW );

		if( $wp )
		{
			$winningPlayer = $wp[0]['playerid'];
			$topScore = (int)$wp[0]['playerscore'];

			// Count players tied for the lead
			$tiedPlayers = 0;
			foreach( $wp as $w )
			{
				if( (int)$w['playerscore'] == $topScore ) $tiedPlayers++;
				if( $w['playerscore'] >= $highestScore ) $highestScore = $w['playerscore'];

				Ach_Check10RoundScore( $w['playerid'], $w['playerscore'] );
				$i++;
			}

			$tieOccurred = ($tiedPlayers > 1);

			// If there's a tie and we haven't exceeded max overtime rounds, trigger overtime
			if( $tieOccurred && $gameMaxRound < ABSOLUTE_MAX_ROUND )
			{
				$overtimeRound = $gameMaxRound - LAST_ROUND + 1;
				BaseUtil_Debug( __FUNCTION__ . ": Game $gameid - Tie detected! Top $tiedPlayers players tied at $topScore. Triggering overtime round $overtimeRound.", 1 );

				// Trigger overtime - this will increment max_round and reset tied players
				TriggerOvertime($gameid);

				// Notify players about overtime
				NotifyOtherPlayersInGame( $gameid, "It's a tie! Sudden death overtime round starting!" );

				// Game is NOT finished - continue playing
				return 0;
			}

			// No tie OR max overtime reached - determine winner
			$winReason = "";
			if( IsGameInOvertime($gameid) )
			{
				$overtimeRounds = $gameMaxRound - LAST_ROUND;
				if( $tieOccurred )
				{
					// Tie at max overtime - use highest single round as tiebreaker
					$winReason = "Highest score after $overtimeRounds overtime round(s). " .
						$wp[0]['username'] . " won tiebreaker with highest single round score of " . $wp[0]['highestRound'];
				}
				else
				{
					$winReason = "Highest score after $overtimeRounds overtime round(s)";
				}
			}
			else
			{
				if( $tieOccurred )
				{
					$winReason = "Highest score in 10 rounds. " . $wp[0]['username'] .
						" broke tie with highest single round score of " . $wp[0]['highestRound'];
				}
				else
				{
					$winReason = "Highest score in 10 rounds";
				}
			}

			FarkleWinGame( $gameid, $wp[0]['playerid'], $winReason );

			// Check the winning player's number of wins for an achievement
			if( $maxTurns > 1 )
			{
				Ach_CheckHighestDifferential( $wp[0]['playerid'], $wp[0]['playerscore'], $wp[1]['playerscore'] );
			}

			NotifyOtherPlayersInGame( $gameid, "A game you were playing has finished." );

			$gameFinished = 1;
		}
		else
		{
			BaseUtil_Error( __FUNCTION__ . ": 10-Round game returned no winner data." );
		}
	}

	return $gameFinished;
}

function FarkleWinGame( $gameid, $winnerid, $reason = "", $sendEmail=1, $achieves=1, $checkTournamentRound=1 )
{
	global $g_leaderboardDirty; 
	
	if( empty($winnerid) || empty($gameid) )
	{
		BaseUtil_Error( __FUNCTION__ . ": Missing parameters. GameId=$gameid, WinnerId=$winnerid, reason=$reason, sendEmail=$sendEmail, achieves=$achieves, checkTRound=$checkTournamentRound" );
		error_log( __FUNCTION__ . ": Missing parameters. GameId=$gameid, WinnerId=$winnerid, reason=$reason, sendEmail=$sendEmail, achieves=$achieves, checkTRound=$checkTournamentRound" );
		return 0;
	}
	
	BaseUtil_Debug( __FUNCTION__ . ": Gameid $gameid is won. Player $winnerid is the winner.", 1 );
	$g_leaderboardDirty = 1; // Wins are on leaderboard so dirty it. It will then reload next time viewed. 
	
	// This is the game closer update
	$sql = "update farkle_games set 
		winningplayer=$winnerid, 
		winningreason='$reason', 
		gamefinish=NOW() 
		where gameid=$gameid";
	$result = db_command($sql);
	
	$sql = "select maxturns, gamewith, gamemode 
		from farkle_games where gameid=$gameid";
	$gameData = db_select_query( $sql, SQL_SINGLE_ROW );
	
	// Update wins and losses only if this was not a solo game
	if( (int)$gameData['gamewith'] == GAME_WITH_SOLO )
	{
		// Solo game
		GivePlayerXP( $winnerid, PLAYERLEVEL_SOLO_XP );	
	}
	else
	{
		if( (int)$gameData['maxturns'] > 1 )
		{
			// Give the win & points to the winner
			$sql = "update farkle_players set wins=wins+1 where playerid=$winnerid";
			$result = db_command($sql);
			
			BaseUtil_Debug( "Player $winnerid won game $gameid. Reason=[$reason]. GameWith=".GameWithToString($gameData['gamewith']).", MaxTurns={$gameData['maxturns']}", 1);
			
			// 5xp for win + 1xp per player beyond the 2nd. 
			$xpGain = PLAYERLEVEL_WIN_XP + ( 1 * ( ( (int)$gameData['maxturns']) - 2 ) ); 
			// 3rd parameter is "xp already given". So we call this basically to check for a level up. 
			GivePlayerXP( $winnerid, $xpGain );
		
			// Set a loss for all of the losers
			$sql = "update farkle_players set losses=losses+1 
				where playerid in (select playerid from farkle_games_players where gameid=$gameid and playerid != $winnerid)";
			$result = db_command($sql);
			
			// Check if the player has earned the unique games achievement. 
			Ach_CheckVsPlayers( $winnerid );
		}
	}	
	
	if( $achieves )
	{
		Ach_CheckPlayerWins( $winnerid );
	}
	
	// Check if this was the last tournament game in a round and proceed on. 
	if( !empty($checkTournamentRound) )
	{
		$sql = "select tournamentid from farkle_tournaments_games where gameid=$gameid";
		$tid = db_select_query( $sql, SQL_SINGLE_VALUE );
		if( !empty($tid) && IsTournamentRoundDone( $tid ) == 1 )
		{
			GenerateTournamentRound( $tid );
		
			// Award achievement to all players in this game for playing in a tournament. 
			$sql = "select playerid from farkle_games_players where gameid=$gameid"; 
			$players = db_select_query( $sql, SQL_MULTI_ROW );
			foreach( $players as $p ) 
			{
				Ach_AwardAchievement( $p['playerid'], ACH_TOURNEY_PLAY );
			}
			// Award the winner the achievement for winning a tournament round.
			Ach_AwardAchievement( $winnerid, ACH_TOURNEY_WINRND );
		}
	}
}

/*
	Func: 	FarkleQuitgame
	Params:	
		$playerid 	playerid of the "quitter" 
		$gameid		game being quit
		$forceLoss	Automatically count this as a loss regardless if it should be or not. 
	Returns: 
		1 on success 
*/
function FarkleQuitGame( $playerid, $gameid, $forceLoss = 0 )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered. Quitter:{$_SESSION['username']} ($playerid), Game: $gameid, AutoLoss? $forceLoss", 14 );
	$rc = 0;
	$countsAsLoss = 0;

	// Get the quitter's turn
	$sql = "select playerturn, currentturn, maxturns, playerround, gamewith, whostarted, gamemode,
		(select count(*) from farkle_games_players where gameid=b.gameid) as actualplayers
		from farkle_games_players a, farkle_games b
		where a.playerid=$playerid and a.gameid=$gameid and a.gameid=b.gameid";
	$gameInfo = db_select_query( $sql, SQL_SINGLE_ROW );

	if( empty($gameInfo) ) 
	{
		BaseUtil_Error( __FUNCTION__ . ": gameInfo query returned no data. Playerid=$playerid, Gameid=$gameid, forceLoss=$forceLoss" ); 
		return 0; 
	}

	$quitterTurn = intval($gameInfo['playerturn']);
	$newMaxTurn = intval($gameInfo['maxturns']);
	$currentTurn = intval($gameInfo['currentturn']);
	$gameWith = intval($gameInfo['gamewith']);
	$playerRound = intval($gameInfo['playerround']);
	
	//BaseUtil_Error( __FUNCTION__ . ": Player $playerid is quitting game $gameid. GameWith=$gameWith. GameMode={$gameInfo['gamemode']}. MaxTurns={$gameInfo['maxturns']}. ActualPlayers={$gameInfo['actualplayers']}" ); 
	
	// Counts as loss IF: 
	//		1. ForceLoss is set
	//		2. Player has played a round and it's not a solo game
	//		3. This is not a random game where we played some and then quit before it found any players
	if( $forceLoss || ( $playerRound > 1 && $gameWith != GAME_WITH_SOLO && !($gameInfo['actualplayers'] == 1 && $gameWith == GAME_WITH_RANDOM) ) )
	{
		BaseUtil_Debug( __FUNCTION__ . ": Counting a loss for player {$_SESSION['username']} ($playerid) and game: $gameid", 1 );
		
		// Zero the quitter's score and set them to a completed state. 
		$sql = "update farkle_games_players set playerscore=0, playerround=".(LAST_ROUND+1)." where playerid=$playerid and gameid=$gameid";
		$rc += db_command($sql);
		$countsAsLoss = 1; 
	}
	else 
	{
		BaseUtil_Debug( __FUNCTION__ . ": Allowing player {$_SESSION['username']} ($playerid) to leave game: $gameid without a loss.", 1 );
		
		// Delete the quitter from the game
		$sql = "delete from farkle_games_players where playerid=$playerid and gameid=$gameid";
		$rc += db_command($sql);	
		$countsAsLoss = 0;		
	}

	// If we removed a player from the game, decrement the maxturn
	if( !$countsAsLoss ) $newMaxTurn -= 1;
	
	// If we have less than 2 players and not a random game (a random game will just find a new player)
	// Also delete any game where the last person just quit (even the random games). 
	// Also delete a random game if everybody including the original starter has quit. 
	if( ($newMaxTurn <= 1 && $gameWith != GAME_WITH_RANDOM) || $newMaxTurn <= 0 || ($newMaxTurn < 1 && $gameWith == GAME_WITH_RANDOM) )
	{
		BaseUtil_Debug( __FUNCTION__ . ": deleting game $gameid because we're down to $newMaxTurn player from {$gameInfo['actualplayers']} players", 1 );
		
		// Delete the game because the player was allowed to quit without penalty
		$sql = "delete from farkle_games_players where gameid=$gameid";
		$rc = db_command($sql);
		
		// Save a copy of this deleted game for debugging / reference purposes. 
		//$sql = "insert into farkle_games_deleted select * from farkle_games where gameid=$gameid";
		//$rc = db_command($sql);
		
		// Delete it. 
		$sql = "delete from farkle_games where gameid=$gameid";
		$rc = db_command($sql);
		
	}
	else
	{	
		// If it was the quitter's turn and that is now illegal then move it back to 1 (doesn't really matter for 10-round games) 
		$players = GetGamePlayerids( $gameid ); 
		
		if( count($players) > 0 ) 
		{		
			$playerString = GetFarkleGameName( $gameid, $gameWith, $players, $newMaxTurn );

			// Update the turn information for the game. 			
			$sql = "update farkle_games set maxturns=$newMaxTurn, currentturn=1, playerString='$playerString' where gameid=$gameid";
			$rc += db_command($sql);

			// Now let's check if this game is completed considering a player just quit (big games may now be finished). 
			$gameFinished = GameIsCompleted( $gameid, $newMaxTurn );
			
		}
		else 
		{
			BaseUtil_Debug( __FUNCTION__ . ": Not renaming game because there are no players left.", 1 ); 
			return 0; 
		}
	}
	
	return 1;
}

// Converts an SQL set of dice to a simple array. 
// Example: arr['d1'], arr['d2']  - to -   array( 4, 6 )
function ConvertTableToDiceArray( $tableData )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered.", 14 );
	$retArray = Array();
	$retArray[0] = $tableData['d1'];
	$retArray[1] = $tableData['d2'];
	$retArray[2] = $tableData['d3'];
	$retArray[3] = $tableData['d4'];
	$retArray[4] = $tableData['d5'];
	$retArray[5] = $tableData['d6'];
	return $retArray;
}

// Convert "gameWith" int value to string
function GameWithToString( $gameWith )
{
	return ( $gameWith == GAME_WITH_RANDOM ? 'Random' : ( $gameWith == GAME_WITH_FRIENDS ? 'Friends' : 'Solo' ) );
}

// Convert "gamemode" int value to string
function GameModeToString( $gameMode )
{
	return ( $gameMode == GAME_MODE_10ROUND ? '10-Round' : 'Standard' ); 
}

/*
	Func: GetGameActivityLog()
	Desc: Gets the activity log for a game showing round results
	Params:
		$gameid		The game to get the log for
	Returns:
		Array of round results with player names, scores, and kept dice grouped by hand
		Note: Opponent rounds ahead of the current player's round are hidden
*/
function GetGameActivityLog($gameid) {
	// Get the current player's round to filter opponent data
	$currentPlayerRound = 999; // Default high so we show everything if not logged in
	$myPlayerId = isset($_SESSION['playerid']) ? $_SESSION['playerid'] : 0;

	if ($myPlayerId > 0) {
		$roundSql = "SELECT playerround FROM farkle_games_players WHERE gameid = $gameid AND playerid = $myPlayerId";
		$myRound = db_select_query($roundSql, SQL_SINGLE_VALUE);
		if ($myRound) {
			$currentPlayerRound = intval($myRound);
		}
	}

	$sql = "SELECT r.playerid, r.roundnum, r.roundscore,
				   p.username
			FROM farkle_rounds r
			JOIN farkle_players p ON r.playerid = p.playerid
			WHERE r.gameid = $gameid
			ORDER BY r.rounddatetime ASC";
	$results = db_select_query($sql, SQL_MULTI_ROW);

	if (!$results) return array();

	// Filter and process results
	$filteredResults = array();

	foreach ($results as $entry) {
		$playerid = $entry['playerid'];
		$roundnum = intval($entry['roundnum']);

		// Hide opponent rounds that are at or ahead of the current player's round
		if ($playerid != $myPlayerId && $roundnum >= $currentPlayerRound) {
			continue; // Skip this entry
		}

		// Get all saved dice from all sets in this round, ordered by hand and set
		$diceSql = "SELECT handnum, d1save, d2save, d3save, d4save, d5save, d6save
					FROM farkle_sets
					WHERE gameid = $gameid AND playerid = $playerid AND roundnum = $roundnum
					AND setscore > 0
					ORDER BY handnum ASC, setnum ASC";
		$diceRows = db_select_query($diceSql, SQL_MULTI_ROW);

		// Group dice by hand number
		$hands = array();
		if ($diceRows) {
			foreach ($diceRows as $row) {
				$handNum = intval($row['handnum']);
				if (!isset($hands[$handNum])) {
					$hands[$handNum] = array();
				}
				for ($i = 1; $i <= 6; $i++) {
					$val = intval($row["d{$i}save"]);
					// Only include actual dice values (1-6), not 0 or 10
					if ($val >= 1 && $val <= 6) {
						$hands[$handNum][] = $val;
					}
				}
			}
		}

		// Sort dice within each hand (descending so higher values first)
		foreach ($hands as &$handDice) {
			rsort($handDice);
		}

		// Convert to indexed array of hands
		ksort($hands);
		$entry['dicehands'] = array_values($hands);

		$filteredResults[] = $entry;
	}

	return $filteredResults;
}

function NotifyOtherPlayersInGame( $gameid, $msg )
{
	BaseUtil_Debug( __FUNCTION__ . ": entered. Gameid=$gameid, msg=$msg", 14 );
	$didNotifySomebody = 0; 
	
	if( empty($gameid) || empty($msg) )
	{
		BaseUtil_Error( __FUNCTION__ . ": missing parameters. Msg was $msg" ); 
		return $didNotifySomebody; 
	}
	
	$playerid = $_SESSION['playerid']; // We never want to notify the person who just performed the action. They already know. 
	
	//$startingPlayerName = $_SESSION['username']; 
	
	// Select all the other players who need to be notified.
	$sql = "select a.playerid as playerid
		from farkle_players a, farkle_games_players b, farkle_players_devices d
		where a.playerid=b.playerid and b.gameid=$gameid and a.playerid != $playerid and b.playerid=d.playerid and d.devicetoken is not null"; 
	$players = db_select_query( $sql, SQL_MULTI_ROW );
		
	if( !empty($players) )
	{
		error_log( "Sending push notification to players for game {$gameid} started by player {$playerid}" ); 
		SendPushNotification( GetPlayerListCommaString($players), $msg, "newGameTone.aif" ); // NotifyOtherPlayersInGame
		$didNotifySomebody = 1; 
	}
	return $didNotifySomebody; 
}

function GetPlayerListCommaString( $playersFromSQL )
{
	$pl = array();
	foreach( $playersFromSQL as $p )
	{
		array_push( $pl, $p['playerid'] );
	}
	return implode( ',', $pl );
}

/*
	Func: SubmitEmojiReaction()
	Desc: Handles emoji submission after a game win. Player can send an emoji to their opponent
	      or skip. Either way, marks emoji_given=TRUE so the popup won't appear again for this game.
	Params:
		$playerid		The player submitting the emoji (current session player)
		$gameid			The game this emoji is for
		$emoji			The emoji character (empty string for skip)
	Returns:
		Array with success or error message
*/
function SubmitEmojiReaction( $playerid, $gameid, $emoji = '' )
{
	if( empty($playerid) || empty($gameid) )
	{
		BaseUtil_Error( __FUNCTION__ . ": Missing parameters. playerid=$playerid, gameid=$gameid" );
		return Array( 'Error' => 'Missing required parameters.' );
	}

	$dbh = db_connect();

	// Verify player is a participant in this game
	$sql = "SELECT playerid FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid";
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameid, ':playerid' => $playerid]);
	$isParticipant = $stmt->fetch(PDO::FETCH_ASSOC);

	if( !$isParticipant )
	{
		BaseUtil_Error( __FUNCTION__ . ": Player $playerid is not a participant in game $gameid" );
		return Array( 'Error' => 'You are not a participant in this game.' );
	}

	// Check if emoji was already given for this game
	$sql = "SELECT emoji_given FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid";
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':gameid' => $gameid, ':playerid' => $playerid]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if( $row && $row['emoji_given'] )
	{
		// Already submitted, just return success silently
		return Array( 'Error' => null, 'success' => true, 'message' => 'Emoji already submitted for this game.' );
	}

	// If emoji is provided (not skipped), add it to opponent's emoji_reactions
	if( !empty($emoji) )
	{
		// Get opponent's playerid (for 2-player games, the other player in this game)
		$sql = "SELECT playerid FROM farkle_games_players WHERE gameid = :gameid AND playerid != :playerid LIMIT 1";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':gameid' => $gameid, ':playerid' => $playerid]);
		$opponent = $stmt->fetch(PDO::FETCH_ASSOC);

		if( $opponent )
		{
			$opponentId = $opponent['playerid'];

			// Get current emoji_reactions for opponent
			$sql = "SELECT emoji_reactions FROM farkle_players WHERE playerid = :playerid";
			$stmt = $dbh->prepare($sql);
			$stmt->execute([':playerid' => $opponentId]);
			$opponentData = $stmt->fetch(PDO::FETCH_ASSOC);

			$currentReactions = $opponentData ? $opponentData['emoji_reactions'] : '';

			// Prepend new emoji to front of string
			$newReactions = $emoji . $currentReactions;

			// Trim to keep only the last ~40 characters (from the back)
			// Note: mb_substr handles multi-byte UTF-8 characters properly
			if( mb_strlen($newReactions, 'UTF-8') > 54 )
			{
				$newReactions = mb_substr($newReactions, 0, 54, 'UTF-8');
			}

			// Update opponent's emoji_reactions
			$sql = "UPDATE farkle_players SET emoji_reactions = :reactions WHERE playerid = :playerid";
			$stmt = $dbh->prepare($sql);
			$stmt->execute([':reactions' => $newReactions, ':playerid' => $opponentId]);

			BaseUtil_Debug( __FUNCTION__ . ": Player $playerid sent emoji '$emoji' to opponent $opponentId in game $gameid", 1 );
		}
	}
	else
	{
		BaseUtil_Debug( __FUNCTION__ . ": Player $playerid skipped emoji for game $gameid", 1 );
	}

	// Mark emoji_given = TRUE and store emoji_sent for this player in this game
	$sql = "UPDATE farkle_games_players SET emoji_given = TRUE, emoji_sent = :emoji WHERE gameid = :gameid AND playerid = :playerid";
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':emoji' => $emoji, ':gameid' => $gameid, ':playerid' => $playerid]);

	return Array( 'Error' => null, 'success' => true );
}

?>
