<?php
require_once('../../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleTournament.php');

$gEmailEnabled = false;

$roundsToGo = 30;
if( isset($_REQUEST['rounds']) ) $roundsToGo = $_REQUEST['rounds'];

function TestMakeWinners( $tid, $roundnum )
{
	// Let the first player in each game win (round 2)
	$randWinner = rand(1,2);
	$sql = "select a.gameid, a.playerid from farkle_games_players a, farkle_tournaments_games b
		where a.gameid=b.gameid and b.tournamentid=$tid and a.playerturn=$randWinner
		and b.roundnum=$roundnum and b.gameid > 0";
	$winners = db_select_query( $sql, SQL_MULTI_ROW );
	foreach( $winners as $w )
	{
		FarkleWinGame( $w['gameid'], $w['playerid'] );
	}
}

$tid = CreateTournament( 100, T_FORMAT_DOUBLE_ELIM, 6, "Testers Cup 1", 1, true );

// Add players
AddPlayerToTournament( $tid, 1 );
AddPlayerToTournament( $tid, 2 );
AddPlayerToTournament( $tid, 3 );
AddPlayerToTournament( $tid, 4 );

AddPlayerToTournament( $tid, 8 );
AddPlayerToTournament( $tid, 9 );
AddPlayerToTournament( $tid, 11 );
AddPlayerToTournament( $tid, 12 );
AddPlayerToTournament( $tid, 13 );
AddPlayerToTournament( $tid, 14 );
AddPlayerToTournament( $tid, 15 );
AddPlayerToTournament( $tid, 16 );
AddPlayerToTournament( $tid, 17 );
AddPlayerToTournament( $tid, 18 );
AddPlayerToTournament( $tid, 19 );
AddPlayerToTournament( $tid, 20 );
/*
AddPlayerToTournament( $tid, 24 );
AddPlayerToTournament( $tid, 25 );
AddPlayerToTournament( $tid, 27 );
AddPlayerToTournament( $tid, 29 );

AddPlayerToTournament( $tid, 30);
AddPlayerToTournament( $tid, 31);
AddPlayerToTournament( $tid, 33);
AddPlayerToTournament( $tid, 34);
AddPlayerToTournament( $tid, 35);
AddPlayerToTournament( $tid, 36);
AddPlayerToTournament( $tid, 41);
AddPlayerToTournament( $tid, 43);
AddPlayerToTournament( $tid, 44);
AddPlayerToTournament( $tid, 47);
*/

// Manually start the tournament
StartTournament( $tid );

$round = 1;
$winner = 0;
while( $winner == 0 && $round <= $roundsToGo )
{
	$sql = "select winningplayer from farkle_tournaments where tournamentid=$tid";
	$winner = db_select_query( $sql, SQL_SINGLE_VALUE );
	if( empty($winner) )
	{
		TestMakeWinners( $tid, $round );
		$round++;

		$rc = IsTournamentRoundDone( $tid );
		if( $rc ) GenerateTournamentRound( $tid );
	}
}
?>
