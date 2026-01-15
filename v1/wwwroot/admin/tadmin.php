<?php
	
require_once('../../includes/baseutil.php');
require_once('../includes/dbutil.php');
require_once('../wwwroot/farklePageFuncs.php');
require_once('../wwwroot/farkleGameFuncs.php');
require_once('../wwwroot/farkleTournament.php');

/*
	tadmin.php?action=create&tname=Beta%20Cup%202&roundhours=24&debug=14
	
	tadmin.php?action=start&tid=2&debug=14
	
	// Create Tournament #8
	tadmin.php?action=create&tname=New%20Year%202012%20Tournament&roundhours=24&debug=14
	tadmin.php?action=start&tid=8&debug=14
*/

if( isset( $_REQUEST['action'] ) )
{
	
	if( $_REQUEST['action'] == 'create' )
	{	
		if( !isset($_REQUEST['tname']) || !isset($_REQUEST['roundhours'])  )
		{
			echo "Missing parameter.";
			exit(1);
		}
	
		$tname = $_REQUEST['tname'];
		$roundhours = $_REQUEST['roundhours'];
		
		
		// Only create the tournament if it is not there already
		//$sql = "select tournamentid from farkle_tournaments where tournamentid=$tid";
		//$tid = db_select_query( $sql, SQL_SINGLE_VALUE );
		
		if( empty($tid) )
		{
			
			echo "Creating $tname tournament...<br>";
			
			// Start a new tournament
			$tid = CreateTournament( 100, T_FORMAT_SINGLE_ELIM, 24, $tname, $roundhours, 1 );
			
			// update the achievement graphic to the beta icon
			$randImg = rand( 1, 3 );
			
			if( isset($_GET['lobbyimage']) )
				$lobbyImage = $_GET['lobbyimage'];
			
			$sql = "update farkle_achievements set 
					imagefile='tournament" . $randImg . ".png'
					where achievementid=(select achievementid from farkle_tournaments where tournamentid=$tid)";
			$result = db_command($sql);	
			
			echo "$tname tournament created. TID = $tid<br>";
			
		}
		else
		{
			echo "Error creating tournament -- already created!<br>";
		}
		
	}
	
	
	if( $_REQUEST['action'] == 'start' )
	{
		$tid = $_REQUEST['tid'];
		$sql = "select roundnum, tournamentid from farkle_tournaments where tournamentid=$tid";
		$t = db_select_query( $sql, SQL_SINGLE_ROW );
		
		if( !empty($t) )
		{
			if( $t['roundnum'] == 0 )
			{
				echo "Starting tournament $tid...<br>";
				$rc = StartTournament( $t['tournamentid'] );
				if( !$rc )
				{
					echo "Error starting tournament $tid -- not enough players!<br>";
				}
			}
			else
			{
				echo "Error starting tournament $tid -- already started!<br>";
			}
		}
		else
		{
			echo "Error starting tournament $tid -- tournament not created!<br>";
		}
	}
	
	echo "tadmin.php finished.<br>";
}
		
?>