#!/usr/bin/php
<?php

	require_once('../includes/baseutil.php');
	require_once('dbutil.php');
	require_once('farkleGameFuncs.php');
	require_once('farklePageFuncs.php');
	
	
	$sql = "select 
				playerid, 
				username, 
				createdate,
				(select count(*) from farkle_games_players b where b.playerid=a.playerid) as games
			from 
				farkle_players a";
				
	$p = db_select_query( $sql, SQL_MULTI_ROW );
	
	echo '<table border="1" width="80%"><tr><td>Playerid</td><td>Name</td><td>Create Date</td><td>Games</td></tr>' . "\n";
	
	$numPlayers = 0;
	for( $i=0; $i<count($p); $i++ )
	{
		echo "<tr>";
		echo "<td>" . $p[$i]['playerid'] . "</td>";
		echo "<td>" . $p[$i]['username'] . "</td>";
		echo "<td>" . $p[$i]['createdate'] . "</td>";
		echo "<td>" . $p[$i]['games'] . "</td>";
		echo "</tr>\n";
		$numPlayers++;
	}
	
	echo "<br><br><b>Number of Players: $numPlayers";

	exit(0);
?>