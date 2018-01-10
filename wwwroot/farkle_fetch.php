<?php
	/*	
		This is the "fetcher" page for the javascript ajax calls. 
	*/	

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farkleLogin.php'); 
	
	BaseUtil_Debug( "Entered farkle_fetch.php", 7 );
	//include_once("analyticstracking.php");
	
	Farkle_SessSet( );
	
	$g_json = 1; 
	
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
		else if( $p['action'] == 'fblogin' )			$rc = UserFacebookLogin( $p['facebookid'], $p['username'], $p['email'], $p['fullname'], $p['playerid'], 0 );
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
