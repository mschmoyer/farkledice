<?php
	/* 
		This is the initial page (the index.php if you will)
	*/

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farkleLogin.php');

	$loginSucceeded = Farkle_SessSet();
	

	/*if( isset( $_REQUEST['device'] ) ) {
		$smarty->assign('device', $_REQUEST['device'] ); 
		
		if( strcmp($_REQUEST['device'], "ios_app") == 0 ) {
		
			$hasLoggedIn = 0;
			if( isset( $_REQUEST['iossessionid'] ) ) {
				$hasLoggedIn = 1; 
			}
			$smarty->assign( 'hasLoggedInBefore', $hasLoggedIn ); 
			BaseUtil_Debug( "farkle.php: Hit from iOS app. HasLoggedIn? $hasLoggedIn", 14 );		
		}
	}*/
	
	// The incoming link from email has a request param (?resumegameid=xxx). 
	// If a browser refreshes this page it annoyingly reloads this game over and over again. 
	// So I stuff it in the session and reload the page with no params. 
	if( $loginSucceeded && isset( $_GET['resumegameid'] ) )
	{
		BaseUtil_Debug( "farkle.php: Resuming game {$_GET['resumegameid']}", 14 );
		$smarty->assign('resumegameid', $_REQUEST['resumegameid'] );
	}		
	
	
	if( isset($_SESSION['farkle']['lastknownscreen']) )
	{
		BaseUtil_Debug( "farkle.php: Player was last on screen: {$_SESSION['farkle']['lastknownscreen']}", 14 );
		switch( $_SESSION['farkle']['lastknownscreen'] )
		{
			case 'game':
				$smarty->assign('resumegameid', $_SESSION['farkle']['lastgameid']); 
				break;
			case 'playerinfo':
				$smarty->assign('lastplayerinfoid', $_SESSION['farkle']['lastplayerinfoid']);
				break;
		}
		$smarty->assign('lastknownscreen', $_SESSION['farkle']['lastknownscreen'] );
	}
	else
	{
		$smarty->assign('lastknownscreen', '' );
	}
	
	// Pass these to the template where javascript can pick them up
	if( $loginSucceeded && isset($_SESSION['username']) && isset($_SESSION['playerid']) )
	{
		BaseUtil_Debug( "farkle.php: Player is logged in. Loading data...", 14 );
	
		require_once('farklePageFuncs.php');
		require_once('farkleLeaderboard.php'); 
		require_once('farkleFriends.php'); 
	
		$smarty->assign('username', $_SESSION['username'] );
		$smarty->assign('playerid', $_SESSION['playerid'] );
		$smarty->assign('adminlevel', $_SESSION['adminlevel'] );

		BaseUtil_Debug( "farkle.php: Pre-populating data.", 14 );
		//* Here we pre-populate some data so we don't have to do 4 ajax calls later *//
		
		// Load initial lobby info
		$lobbyInfo = json_encode( GetLobbyInfo( ) );
		BaseUtil_Debug( __FUNCTION__ . " Sending lobby info to JS: " . $lobbyInfo , 7 );		
		$smarty->assign('lobbyInfo', $lobbyInfo );

		if( isset($_SESSION['playerid']) )
		{
			// Friends list
			$friendInfo = json_encode( GetGameFriends( $_SESSION['playerid'], true ) ); 
			BaseUtil_Debug( __FUNCTION__ . " Sending friend info to JS: " . $friendInfo , 7 );
			$smarty->assign('friendInfo', $friendInfo );
			
			// Leaderboard
			$lbInfo = json_encode( GetLeaderBoard( $_SESSION['playerid'] ) ); 
			BaseUtil_Debug( __FUNCTION__ . " Sending leaderboard info to JS: " . $lbInfo , 7 );
			$smarty->assign('lbInfo', $lbInfo );
			
			// Player info
			$pInfo = json_encode( GetStats( $_SESSION['playerid'], 0 ) ); 
			BaseUtil_Debug( __FUNCTION__ . " Sending playerinfo info to JS: " . $pInfo , 7 );
			$smarty->assign('pInfo', $pInfo );
			
			$smarty->assign('double_xp', IsDoubleXP() );
		}
	}

	$smarty->display('farkle.tpl');
?>
