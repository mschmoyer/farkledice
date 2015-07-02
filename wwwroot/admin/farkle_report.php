<?php

	require_once('../../includes/baseutil.php');
	require_once('dbutil.php');
	//require_once('../farkleGameFuncs.php');
	//require_once('../farklePageFuncs.php');
	
	BaseUtil_SessSet( );
	
	$sql = "select 
				playerid, 
				IFNULL(fullname,username) as name, 
				DATE_FORMAT(lastplayed, '%b %D @ %l:00 %p') as lastplayedgame, 
				(select count(*) from farkle_games_players b where b.playerid=a.playerid) as games,
				(select count(*) from farkle_games c where c.whostarted=a.playerid) as games_started
			from 
				farkle_players a
			order by lastplayed desc";
				
	$p = db_select_query( $sql, SQL_MULTI_ROW );
	
	$smarty->assign( 'players', $p );
	$smarty->assign('numPlayers', count($p) );
	
	// Get number of games
	$sql = "select count(*) from farkle_games";
	$numGames = db_select_query( $sql, SQL_SINGLE_VALUE );
	$smarty->assign('numGames', $numGames ); 
	
	$smarty->display('farkle_report.tpl');
?>