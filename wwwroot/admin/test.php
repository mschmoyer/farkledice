<?php		require_once('../../includes/baseutil.php');	require_once('dbutil.php');	require_once('farklePageFuncs.php');	require_once('farkleGameFuncs.php');	require_once('farkleTournament.php');	require_once('farkleGameObject.php');		$game = new FarkleGameInfo( '2001' );		//$game->DumpGameInfo();	$game->GetPlayerData();	$game->DumpPlayerInfo();		var_dump( $game->players['1']['playerid']	);	?>