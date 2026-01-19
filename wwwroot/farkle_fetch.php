<?php
	/*	
		This is the "fetcher" page for the javascript ajax calls. 
	*/	

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farkleLogin.php');
	require_once('farkleBackgroundTasks.php');
	require_once('farkleBotTurn.php');
	require_once('farkleBotAI.php');
	require_once('farkleBotMessages.php');

	BaseUtil_Debug( "Entered farkle_fetch.php", 7 );
	//include_once("analyticstracking.php");

	Farkle_SessSet( );

	$g_json = 1;

	// Run background maintenance tasks (throttled to prevent overload)
	// This replaces the need for cron jobs
	BackgroundMaintenance(); 
	
	// Allow post or request. TBD: take GET away. 
	if( isset( $_POST['action'] ) )
		$p = $_POST;
	else if( isset( $_GET['action'] ) )
		$p = $_GET;
	
	// Looks like javascript could return "undefined" in playerid. Set to nothing. 
	if( isset($p['playerid']) && $p['playerid'] == 'undefined' )
		$p['playerid'] = null; 
	
	if( isset($p['ios_app']) ) 
		$_SESSION['ios_app'] = 1;
	
	if( isset( $p['action'] ) )
	{			
		BaseUtil_Debug( "Action=" . $p['action'], 7 );
		// All of the various AJAX call functions
		
		// These functions can be executed while not logged in
		if( $p['action'] == 'register' ) 				$rc = UserRegister( $p['user'], $p['pass'], $p['email'] );
		else if( $p['action'] == 'login' )				$rc = UserLogin( $p['user'], $p['pass'], $p['remember'] );
		else if( $p['action'] == 'forgotpass' )			$rc = ResendPassword( $p['email'] );
		else if( $p['action'] == 'resetpass' )			$rc = ResetPassword( $p['code'], $p['pass'] );
		else
		{
			// These functions you must be logged in to execute
			if( isset($_SESSION['playerid']) ) 
			{
				require_once('farklePageFuncs.php');
				require_once('farkleGameFuncs.php');
				require_once('farkleDiceScoring.php');
				require_once('farkleAchievements.php');
				require_once('farkleTournament.php');
				require_once('farkleLeaderboard.php');
				require_once('farkleFriends.php'); 
			
				if( !isset($p['newdice']) ) $p['newdice'] = null; // Protects against old clients without the javascript update. 
			
				// Lobby Info & Options
				if( $p['action'] == 'logout' )					$rc = UserLogout();	
				else if( $p['action'] == 'iphonetoken' ) 
				{
					//if( empty($p['playerid']) ) $p['playerid'] = 0; // Old App versions
					$rc = AddDeviceToken( $p['device'], $p['devicetoken'], $p['session_id'], $_SESSION['playerid'] );
				}				
				else if( $p['action'] == 'getlobbyinfo' )		$rc = GetLobbyInfo();
				else if( $p['action'] == 'getfriends' )			$rc = GetGameFriends( $_SESSION['playerid'] );
				else if( $p['action'] == 'addfriend' )			$rc = AddFriend( $_SESSION['playerid'], $p['identstring'], $p['ident'] );
				else if( $p['action'] == 'removefriend' )		$rc = RemoveFriend( $_SESSION['playerid'], $p['friendid'] );
				
				// Player Info & Options
				else if( $p['action'] == 'getplayerinfo' )		$rc = GetStats( $p['playerid'] );
				else if( $p['action'] == 'saveoptions' ) 
				{
					if( !isset($p['random_selectable']) ) $p['random_selectable'] = 1; 
					$rc = SaveOptions( $p['email'], $p['sendhourlyemails'], $p['random_selectable'] );
				}
				else if( $p['action'] == 'updatetitle' )		$rc = Player_UpdateTitle( $p['titleid'] );
				else if( $p['action'] == 'getleaderboard' )		$rc = GetLeaderBoard( );
				
				// Game Commands
				else if( $p['action'] == 'startgame' ) 
				{
					if( !isset($p['rp']) ) $p['rp'] = 2; // Default to 2 players. 
					$rc = FarkleNewGame( $p['players'], $p['breakin'], $p['playto'], $p['gamewith'], $p['gamemode'], 0, $p['rp'] );
				} 
				else if( $p['action'] == 'getnewgameinfo' )		$rc = GetNewGameInfo( $_SESSION['playerid'] );
				else if( $p['action'] == 'farklegetupdate' )	$rc = FarkleSendUpdate( $_SESSION['playerid'], $p['gameid'] );
				else if( $p['action'] == 'quitgame')			$rc = FarkleQuitGame( $_SESSION['playerid'], $p['gameid'] );
				else if( $p['action'] == 'farkleroll' )			$rc = FarkleRoll( $_SESSION['playerid'], $p['gameid'], $p['saveddice'], $p['newdice'] );
				else if( $p['action'] == 'farklepass' )			$rc = FarklePass( $_SESSION['playerid'], $p['gameid'], $p['saveddice'] );	

				// Achievements & Levels
				else if( $p['action'] == 'getachievements' )	$rc = GetAchievements( $p['playerid'] );
				else if( $p['action'] == 'ackachievement' ) 	$rc = AckAchievement( $p['playerid'], $p['achievementid'] );
				else if( $p['action'] == 'acklevel' ) 			$rc = AckLevel( $p['playerid'] );
				
				// Tournament Commands
				else if( $p['action'] == 'gettournamentinfo' ) 	$rc = GetTournamentStatus( $p['tid'], $p['playerid'] );
				else if( $p['action'] == 'addplayertotourney' ) $rc = AddPlayerToTournament( $p['tid'], $p['playerid'] );
				else if( $p['action'] == 't_removeplayer' ) 	$rc = RemovePlayerFromTournament( $p['tid'], $p['playerid'] );

				// Bot Player Commands
				else if( $p['action'] == 'startbotgame' )
				{
					try {
						error_log("startbotgame: Starting bot game creation for player {$_SESSION['playerid']}");

						// Validate algorithm parameter
						$algorithm = $_POST['algorithm'] ?? null;
						error_log("startbotgame: Requested difficulty: " . ($algorithm ?? 'NULL'));

						if (!in_array($algorithm, ['easy', 'medium', 'hard'])) {
							error_log("startbotgame: Invalid difficulty provided: $algorithm");
							$rc = Array('Error' => 'Invalid bot difficulty. Choose easy, medium, or hard.');
						} else {
							// Select random bot with matching algorithm
							$sql = "SELECT playerid, username FROM farkle_players
							        WHERE is_bot = TRUE AND bot_algorithm = :algorithm
							        ORDER BY RANDOM() LIMIT 1";
							error_log("startbotgame: Querying for bot with algorithm: $algorithm");

							$dbh = db_connect();
							$stmt = $dbh->prepare($sql);
							$stmt->execute([':algorithm' => $algorithm]);
							$botPlayer = $stmt->fetch(PDO::FETCH_ASSOC);

							if (!$botPlayer) {
								error_log("startbotgame: No bot found for difficulty: $algorithm");
								$rc = Array('Error' => 'No bot available for this difficulty.');
							} else {
								error_log("startbotgame: Selected bot player {$botPlayer['playerid']} ({$botPlayer['username']})");

								// Create 1v1 game (10-round mode)
								$players = json_encode([$_SESSION['playerid'], $botPlayer['playerid']]);
								error_log("startbotgame: Creating game with players: $players");
								error_log("startbotgame: Calling FarkleNewGame with params - breakin:0, playto:10000, gamewith:".GAME_WITH_FRIENDS.", gamemode:".GAME_MODE_10ROUND);

								$gameResult = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND, false, 2);
								error_log("startbotgame: FarkleNewGame returned: " . json_encode($gameResult));

								if (isset($gameResult['Error'])) {
									error_log("startbotgame: FarkleNewGame returned error: {$gameResult['Error']}");
									$rc = $gameResult;
								} else if (is_array($gameResult) && isset($gameResult[0]) && isset($gameResult[0]['gameid'])) {
									// FarkleNewGame returns FarkleSendUpdate result: array with game object at index 0
									$gameid = $gameResult[0]['gameid'];
									error_log("startbotgame: Game created successfully with gameid: {$gameid}");

									// Set bot play mode to interactive
									$sql = "UPDATE farkle_games SET bot_play_mode = 'interactive' WHERE gameid = :gameid";
									$stmt = $dbh->prepare($sql);
									$stmt->execute([':gameid' => $gameid]);
									error_log("startbotgame: Set bot_play_mode to interactive for game {$gameid}");

									// Return full game state (like regular startgame does)
									// FarkleGameStarted expects this format
									$rc = $gameResult;
									$rc['botname'] = $botPlayer['username'];
									$rc['Error'] = null;
									error_log("startbotgame: Success! Returning full game state with gameid {$gameid}");
									error_log("startbotgame: Response structure: " . json_encode(array_keys($rc)));
								} else {
									error_log("startbotgame: FarkleNewGame returned unexpected result (no gameid, no error)");
									$rc = Array('Error' => 'Failed to create bot game.');
								}
							}
						}
					} catch (Exception $e) {
						error_log("startbotgame: EXCEPTION caught: " . $e->getMessage());
						error_log("startbotgame: Exception trace: " . $e->getTraceAsString());
						$rc = Array('Error' => 'An error occurred while creating the bot game: ' . $e->getMessage());
					}
				}
				else if( $p['action'] == 'getbotstatus' )
				{
					$gameId = intval($_POST['gameid'] ?? 0);
					$botPlayerId = intval($_POST['botplayerid'] ?? 0);

					if ($gameId <= 0 || $botPlayerId <= 0) {
						$rc = Array('Error' => 'Invalid game or player ID.');
					} else {
						// Get current bot turn state
						$state = Bot_GetTurnState($gameId, $botPlayerId);

						if ($state) {
							$rc = Array(
								'stateid' => $state['stateid'],
								'current_step' => $state['current_step'],
								'dice_kept' => $state['dice_kept'],
								'turn_score' => $state['turn_score'],
								'dice_remaining' => $state['dice_remaining'],
								'last_roll' => $state['last_roll'],
								'last_message' => $state['last_message'],
								'Error' => null
							);
						} else {
							$rc = Array('Error' => 'No turn state found for this bot.');
						}
					}
				}
				else if( $p['action'] == 'executebotstep' )
				{
					$gameId = intval($_POST['gameid'] ?? 0);
					$botPlayerId = intval($_POST['botplayerid'] ?? 0);

					if ($gameId <= 0 || $botPlayerId <= 0) {
						$rc = Array('Error' => 'Invalid game or player ID.');
					} else {
						// Execute next step in bot's turn
						$result = Bot_ExecuteStep($gameId, $botPlayerId);

						if (isset($result['error'])) {
							$rc = Array('Error' => $result['error']);
						} else {
							// Return step result
							$rc = $result;
							$rc['Error'] = null;
						}
					}
				}

				//else if( $p['action'] == 'sendreminder' )		$rc = SendReminder( $p['gameid'] );
				//else if( $p['action'] == 'redeemtitle' )		$rc = RedeemTitle( $_SESSION['playerid'], $p['gameid'], $p['choice'], $p['titlevalue'] );
				//else if( $p['action'] == 'prestige' ) 		$rc = PlayerPrestige( $p['playerid'] );

				else
				{
					// Unknown action
					error_log( "Unknown action: [{$p['action']}] for player {$_SESSION['username']} ({$_SESSION['playerid']})" );
					$rc = Array( 'Error' => 'Whoops! It appears that your page may be out of date. 
						Please try reloading the page and clearing your cache if you can.' );
				}
			}
			else
			{
				// User attempted to issue a command while not logged in. 
				error_log( "User client issued action: [{$p['action']}] while not logged in." );
				$rc = Array( 'Error' => 'Session expired. Login required.',
							 'LoginRequired' => '1' );
			}
		}
		
		if( !empty($rc) )
		{
			echo json_encode( $rc );	
		}
		else
		{
			if( $p['action'] != 'quitgame' )
				error_log( "Fetch page returned no data. Pid=".(isset($_SESSION['playerid']) ? $_SESSION['playerid'] : "[no pid]").". Action was {$p['action']}" );
		}
			
		exit(0); // Don't draw the page...
	}
?>
