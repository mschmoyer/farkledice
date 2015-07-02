<?php

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farklePageFuncs.php');
	
	BaseUtil_SessSet( );
	
	$loginSucceeded = 0;
	if( isset( $_COOKIE['username'] ) && isset( $_COOKIE['password'] ) )
	{
		// Cookie exists, do a login using the cookie values. 
		$loginSucceeded = UserLogin( $_COOKIE['username'], $_COOKIE['password'], 0 );
		
		// This will set the session vars that are used below to skip past the login page. 
	}
	
	// If we did not login any which way -- create a guest account
	if( empty($loginSucceeded) )
	{
		// But only create it if we haven't logged out on this device before. 
		// If a device is auto logged in and logs out this would create a 2nd guest account OR if they log out of their real account. 
		//if( !isset($_COOKIE['logout']) )
			//CreateGuestUser();
	}
	
	// The incoming link from email has a request param (?resumegameid=xxx). 
	// If a browser refreshes this page it annoyingly reloads this game over and over again. 
	// So I stuff it in the session and reload the page with no params. 
	if( isset( $_REQUEST['resumegameid'] ) )
	{
		$_SESSION['resumegameid'] = $_REQUEST['resumegameid'];
		header('Location: farkle.php');
	}		
	
	// Here is where we pickup the session, assign the var that will do the action and unset the session var. 
	if( isset( $_SESSION['resumegameid'] ) )
	{
		$smarty->assign('resumegameid', $_SESSION['resumegameid'] );
		unset( $_SESSION['resumegameid'] );
	}

	// Pass these to the template where javascript can pick them up
	if( isset($_SESSION['username']) )
	{
		$smarty->assign('username',$_SESSION['username'] );
		$smarty->assign('playerid',$_SESSION['playerid'] );
	}

	$smarty->display('farkle.tpl');
?>
