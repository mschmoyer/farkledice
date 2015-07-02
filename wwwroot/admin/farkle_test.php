<?php

	
	require_once('../../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farklePageFuncs.php');
	require_once('farkleGameFuncs.php');
	require_once('farkleDiceScoring.php');
	require_once('farkleAchievements.php');
	
	if( $g_debug == 0 ) $g_debug = 1; // Standard level 7 debug for test

	$g_step = 0;
	$gEmailEnabled = false;

	$smarty->assign('version', FARKLE_MAJOR_VERSION.'.'.FARKLE_MINOR_VERSION.'.'.FARKLE_REV_VERSION );
	
	function DoTest( $testName, $rc, $testfor = '1' )
	{
		global $g_step;
		$g_step += 1;
		$testSuccess = 0;
		$retVal = $rc;
		//var_dump($rc);
		if( strlen($testfor) > 1 )
		{
			//BaseUtil_Debug("Testing for phrase",1);
			if( isset($rc['Error']) ) $retVal = $rc['Error'];
			//var_dump($retVal);
			if( is_array( $retVal ) )
				$testSuccess = 0;
			else
				if( strcmp($retVal,$testfor) == 0 ) $testSuccess = 1;
		}
		else if( $testfor == '1' )
		{
			//BaseUtil_Debug("Testing for 1",1);
			if( isset($rc['Error']) ) $retVal = 0;
			if( !empty($retVal) ) $testSuccess = 1;
		}
		else
		{
			//BaseUtil_Debug("Testing for 0",1);
			if( isset($rc['Error']) ) $retVal = 0;
			if( empty($retVal) ) $testSuccess = 1;
		}
		
		if( $testSuccess )
		{
			BaseUtil_Debug("<b>$g_step - $testName</b>: Test successful. Testing for: [$testfor], RC=[$retVal]", 1, 'green');
		}
		else
		{
			BaseUtil_Debug("<b>$g_step - $testName</b>: Test failed. Testing for: [$testfor], RC=[$retVal]", 1, 'red');
			exit(0);
		}
		return $retVal; 
	}
	
	function DoTestQuery( $testName, $sql, $result )
	{
		global $g_step;
		$g_step += 1;
		
		$rc = db_select_query( $sql, SQL_SINGLE_VALUE );
		$testSuccess = ( $rc == $result );
		if( $testSuccess )
		{
			BaseUtil_Debug("<b>$g_step - $testName</b>: Test successful. Testing for: [$result], RC=[$rc]", 1, 'green');
		}
		else
		{
			BaseUtil_Debug("<b>$g_step - $testName</b>: Test failed. Testing for: [$result], RC=[$rc]", 1, 'red');
			exit(0);
		}
		return $testSuccess;
	}
	
	function DoQuery( $sql )
	{
		return db_select_query( $sql, SQL_SINGLE_VALUE );
	}
	
	
	BaseUtil_SessSet( );
	
	$testPlayerid = 14;	
	DoTest('testuser1 login', UserLogin( md5('testuser1'), md5('warhammer'), 0 ), 1 );
		
	// Excercise registration...
	
	/*
	DoTest('Re-register existing user', UserRegister( 'mschmoyer', md5('testpass'), 'mas@cbord.com' ), 0 );	
	DoTest('Register empty username', UserRegister( '', md5('testpass'), 'mas@cbord.com' ), 0 );	
	DoTest('Register username with bad word', UserRegister( 'assclown', md5('testpass'), 'mas@cbord.com' ), 0 );	
	DoTest('Register username with bad word at the end', UserRegister( 'clownass', md5('testpass'), 'mas@cbord.com' ), 0 );	
	DoTest('Register username too long', UserRegister( 'thisismyusernamethatismuchtoolongforausername', md5('testpass'), 'mas@cbord.com' ), 0 );
	DoTest('Register username spaces', UserRegister( ' this user name has spaces in it ', md5('testpass'), 'mas@cbord.com' ), 0 );
	DoTest('Register html tags', UserRegister( '<b>BoldUser!</b>', md5('testpass'), 'mas@cbord.com' ), 0 );
	
	DoTest('Add friend whose user does not exist', AddFriend( $testPlayerid, 'asldkrjoeirnd', 'username' ), 'Username not found.' );
	DoTest('Add testuser2 as friend via username', AddFriend( $testPlayerid, 'testuser2', 'username' ), 1 );
	db_command("delete from farkle_friends where sourceid=14 and friendid=15");
	DoTest('Remove friend we just added', RemoveFriend( 14, 15 ), 1 );
	DoTest('Add testuser2 as friend via email', AddFriend( $testPlayerid, 'testuser2@mikeschmoyer.com', 'email' ), 1 );
	DoTest('Remove friend we just added 2', RemoveFriend( 14, 15 ), 1 );
	DoTest('Add friend whose email does not exist (should email)', AddFriend( $testPlayerid, 'testuser3@mikeschmoyer.com', 'email' ), 1 );
	*/
		
	// Run through a game scenario...
	
	$newGameId = DoTest('Game #1: create (mschmoyer vs testuser1)', 
		FarkleNewGame( json_encode( Array('1','14') ), '300', '5000', GAME_WITH_FRIENDS, GAME_MODE_STANDARD ), 1 );
	
	$curTurn = DoQuery("select currentturn from farkle_games where gameid=$newGameId");
	$curPlayer = DoQuery("select playerid from farkle_games_players a, farkle_games b where a.gameid=b.gameid and a.playerturn=b.currentturn and a.gameid=$newGameId");

	DoTest('Game #1: Roll out of turn', FarkleRoll( 15, $newGameId, '[1,2,3,4,5,6]' ), "You cannot roll." );
	
	// Set that last roll to something known to us [1,5,3,3,3,4]
	
	DoTest('Game #1: First roll attempt.', FarkleRoll( $curPlayer, $newGameId, '[0,0,0,0,0,0]' ), 1 );	
	DoTest('Game #1: Spam roll button #1', FarkleRoll( $curPlayer, $newGameId, '[0,0,0,0,0,0]' ), 0 );
	DoTest('Game #1: Spam roll button #2', FarkleRoll( $curPlayer, $newGameId, '[0,0,0,0,0,0]' ), 0 );
	DoTest('Game #1: Spam roll button #3', FarkleRoll( $curPlayer, $newGameId, '[0,0,0,0,0,0]' ), 0 );
	
	db_command("update farkle_sets set d1=1, d2=5, d3=3, d4=3, d5=3, d6=4 where gameid=$newGameId" );
	
	DoTest("Game #1: Roll triple 3's. $curPlayer", FarkleRoll( $curPlayer, $newGameId, '[0,0,3,3,3,0]' ), 1 );
	
	DoTestQuery( "Game #1: Haven't passed yet. farkle_rounds should be NULL.", "select sum(roundscore) from farkle_rounds where gameid=$newGameId and playerid=$testPlayerid", 0 );
	
	// Set dice to known roll
	db_command("update farkle_sets set d1=1, d2=3, d6=6 where gameid=$newGameId and setnum=2" );
	
	DoTest("Game #1: Take a [1] and pass for player $curPlayer", FarklePass( $curPlayer, $newGameId, '[1,0,10,10,10,0]' ), 1 );
		
	DoTestQuery( "Game #1: Query round score - should be 400", 
		"select sum(roundscore) from farkle_rounds where gameid=$newGameId", '400' );
	
	DoTestQuery( "Game #1: Did we move on to player 2's turn? (currentturn!=lastturn)", 
		"select currentturn<>$curTurn from farkle_games where gameid=$newGameId", '1' );
	
	// It is now the 2nd player's turn. 
	$curPlayer = DoQuery("select playerid from farkle_games_players a, farkle_games b where a.gameid=b.gameid and a.playerturn=b.currentturn and a.gameid=$newGameId");

	DoTest('Game #1: Player 2 roll', FarkleRoll( $curPlayer, $newGameId, '[0,0,0,0,0,0]' ), 1 );
	
	// Update roll to something we know. [1,5,2,2,2,4]
	db_command("update farkle_sets set d1=1, d2=5, d3=2, d4=2, d5=2, d6=4 where gameid=$newGameId" );
	
	DoTest("Game #1: Score triple 2's. Won't meet break-in. $curPlayer", FarklePass( $curPlayer, $newGameId, '[0,0,2,2,2,0]' ), 1 );
	
	DoTestQuery( "Game #1: Query player1 score - should be 0", 
		"select sum(roundscore) from farkle_rounds where gameid=$newGameId and playerid=$curPlayer", '0' );
	
		// Cleanup
	if( $g_debug < 7 )
	{
		db_command("delete from farkle_games_players where gameid=$newGameId");
		db_command("delete from farkle_games where gameid=$newGameId");
		db_command("delete from farkle_sets where gameid=$newGameId");
		db_command("delete from farkle_rounds where gameid=$newGameId");
	}
	
	// Test a 10-ROUND game
	$newGameId = DoTest('Game $newGameId: create (mschmoyer vs testuser1)', 
		FarkleNewGame( json_encode( Array('1','14') ), '0', '5000', GAME_WITH_FRIENDS, GAME_MODE_10ROUND ), 1 );
	
	$curTurn = DoQuery("select currentturn from farkle_games where gameid=$newGameId");
	$curPlayer = DoQuery("select playerid from farkle_games_players a, farkle_games b where a.gameid=b.gameid and a.playerturn=b.currentturn and a.gameid=$newGameId");

	DoTest("Game $newGameId: Player 1 first roll.", FarkleRoll( 1, $newGameId, "[0,0,0,0,0,0]" ), 1 );
	DoTest("Game $newGameId: Player 14 first roll.", FarkleRoll( 14, $newGameId, "[0,0,0,0,0,0]" ), 1 );
	DoTest("Game $newGameId: Player 14 spam roll button #1", FarkleRoll( 14, $newGameId, "[0,0,0,0,0,0]" ), 0 );
	DoTest("Game $newGameId: Player 14 spam roll button #2", FarkleRoll( 14, $newGameId, "[0,0,0,0,0,0]" ), 0 );
	
	db_command("update farkle_sets set d1=1, d2=5, d3=3, d4=3, d5=3, d6=4 where gameid=$newGameId" );
	
	DoTest("Game $newGameId: Player 1 takes a 1[100]", FarklePass( 1, $newGameId, "[1,0,0,0,0,0]" ), 1 );
	DoTest("Game $newGameId: Player 14 takes triple 3's[300]", FarklePass( 14, $newGameId, "[0,0,3,3,3,0]" ), 1 );
	
	DoTestQuery( "Game $newGameId: Player 1 should have 100", 
		"select sum(roundscore) from farkle_rounds where gameid=$newGameId and playerid=1", '100' );
	
	DoTestQuery( "Game $newGameId: Player 14 should have 300", 
		"select sum(roundscore) from farkle_rounds where gameid=$newGameId and playerid=14", '300' );
	
	DoTest("Game $newGameId: Player 1 second roll.", FarkleRoll( 1, $newGameId, "[0,0,0,0,0,0]" ), 1 );
	DoTest("Game $newGameId: Player 14 second roll.", FarkleRoll( 14, $newGameId, "[0,0,0,0,0,0]" ), 1 );
	
	/*
		
		$curDice = DoTestQuery("select CONCAT(d1,',',d2,',',d3,',',d4,',',d5,',',d6) 
		from farkle_games a, farkle_games_players b, farkle_sets c
		where a.gameid=b.gameid and b.gameid=c.gameid and a.gameid=299
		and a.currentturn=b.playerturn and a.currentround=c.roundnum");	
	*/
		
?>