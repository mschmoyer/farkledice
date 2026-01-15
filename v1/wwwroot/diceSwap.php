<?php

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	
	$sql = "select cardbg from farkle_players where playerid=1";
	$diceBackImg = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	if( $diceBackImg == 'dicebackSet7.png' ) {
		$sql = "update farkle_players set cardbg=null where playerid=1";
		echo "Mike's dice set to normal.";
	} else {
		$sql = "update farkle_players set cardbg='dicebackSet7.png' where playerid=1";
		echo "Mike's dice set to very Fuzzy!";
	}
	$result = db_command($sql);
	
?>