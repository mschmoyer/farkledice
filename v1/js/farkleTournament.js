/*
	farklePage.js	
	Desc: Javascript functions related to the general Farkle operations
	
	13-Jan-2013		mas		Updates to support Farkle tournaments again. 
*/

var gTournamentFreshLoad = 0;
var gTournamentTimer;
var gCurTournament = 0;

/*function ShowTournamentList()
{
	HideAllWindows();
	divTournamentListObj.show();
}*/

function ShowTournament()
{
	ShowTournamentEx( gCurTournament );
}

function ShowTournamentEx( tid )
{
	HideAllWindows();
	
	if( gCurTournament != tid )
		farkleAlert( '<br><div align="center">Loading Tournament...</div>', 'lightgreen', 1 );
	else
		divTournamentObj.show();
		
	gCurTournament = tid; 
	
	gTournamentFreshLoad = 1;
	GetTournamentUpdate();
}

function GetTournamentUpdate()
{
	AjaxCallPost( gAjaxUrl, 
		function ShowTournamentHook() 
		{
			if( ajaxrequest.responseText )
			{
				// Successful register. 
				var data = farkleParseJSON( ajaxrequest.responseText );
				
				var tGames = data[1];
				
				$('#tname').html( data[0].tname );
				
				if( data[0].participant == 0 && data[0].roundnum == 0 ) {
					$('#divJoinTournament').show();
					$('#divQuitTournament').hide();
				} else {
					$('#divJoinTournament').hide();
					$('#divQuitTournament').show();
				}
				
				if( data[0].gamemode == 2 )
				{
					$('#lblGameMode').html( '10-Round' );
					$('#lblTPointsToWin').html( 'Highest score in 10 rounds' );
					$('#lblTBreakIn').html( 'None' );
				}
				else
				{
					$('#lblGameMode').html( 'Standard Farkle' );
					$('#lblTPointsToWin').html( addCommas(data[0].pointstowin) + ' pts' );
					$('#lblTBreakIn').html( data[0].mintostart + ' pts' );
				}
				
				$('#lblRoundHours').html( data[0].roundhours );
				$('#lblTMaxPlayers').html( data[0].playercap + ' (' + data[0].numplayers + ' joined)' );
				$('#imgTournamentAch').attr('src','/images/achieves/' + data[0].imagefile );
				
				var format;
				if( data[0].tformat == 0 ) format = 'Dynamic Elimination';
				if( data[0].tformat == 1 ) format = 'Single Elimination';
				if( data[0].tformat == 2 ) format = 'Double Elimination';
				$('#lblTFormat').html( format );
				
				if( data[0].roundnum == 0 )
				{
					var startcondition;
					if( data[0].startcondition == 0 ) startcondition = 'Manual';
					if( data[0].startcondition == 1 ) startcondition = data[0].launchdate;
					if( data[0].startcondition == 2 ) startcondition = data[0].playercap + ' players required';
					$('#lblTStarts').html( startcondition );
					$('#lblTStartsLabel').html( 'Starts:' );
				}
				else
				{
					//nextrounddate
					$('#lblTStarts').html( data[0].nextrounddate );
					$('#lblTStartsLabel').html( 'Next Round:' );
				}
				
				if( data[0].winningplayer > 0 ) // TOURNAMENT FINISHED
				{
					$('#divTournamentAchieve').hide();
					$('#divTournamentAch').hide();
					$('#divTournamentAch2').show();
					$('#divQuitTournament').hide();
					DisplayAchievement( 'divTournamentAch2', data[0], 0, 1 ); 
					$('#divTournamentWinner').show();
					$('#divTournamentGamesWrapper').show();
					$('#divTournamentPlayersWrapper').hide();
					
					var winningUsername = '';
					for( i=0; i<data[2].length; i++ )
					{
						if( data[2][i].playerid == data[0].winningplayer )
						{
							winningUsername = data[2][i].username;
							break;
						}
					}
					$('#lblTournamentWinner').html( winningUsername );
					DrawTournamentParticipants( data[2] );
					$('#lblTStarts').html( '' );
					$('#lblTStartsLabel').html( 'Tournament finished.' );
					$('#lblTInProgress').html( '' );
				}
				else
				{
					$('#divTournamentAchieve').show();
					$('#divTournamentAch2').hide();
					$('#divTournamentAch').show();
					DisplayAchievement( 'divTournamentAch', data[0], 0, 1 ); 
					
					if( data[0].roundnum > 0 ) // TOURNAMENT IN PROGRESS
					{
						$('#divTournamentPlayersWrapper').hide();
						$('#divTournamentGamesWrapper').show();
						$('#divTournamentWinner').hide();
						$('#divQuitTournament').hide();
						$('#lblTInProgress').html('Tournament in progress...');

						if( gTournamentFreshLoad == 1 )
						{
							$('#divTournamentGames').html('');
							gTournamentFreshLoad = 0;
						}
					}
					else // TOURNAMENT NOT STARTED
					{
						// Game not started yet. 
						$('#divTournamentWinner').hide();
						$('#divTournamentPlayersWrapper').show();
						$('#divTournamentGamesWrapper').hide();
						
						if( data[0].numplayers < data[0].playercap ) 
							$('#divTournamentWaiting').show();
						
						DrawTournamentParticipants( data[2] );
					}
				}
				
				if( data[0].roundnum > 0 )
					DrawTournamentGames( tGames );
				
				farkleAlertHide(); 				
				divTournamentObj.show();
				
				gTournamentTimer = setTimeout( GetTournamentUpdate, 15000);
			}
		}, 
		'action=gettournamentinfo&playerid='+playerid+'&tid='+gCurTournament );
}
 
function DrawTournamentParticipants( participants )
{
	for( i=0; i<participants.length; i++ )
	{
		newTag = DisplayPlayerTag( 'divTournamentPlayers', participants[i], 0 );
	}
}
 
function DrawTournamentGames( tGames )
{
	var yourturn = 0;
	var playerIsParticipant = 0; 
	var curRoundNum = 9999;
	var customText; 
	
	for( var i=0; i<tGames.length; i++ )
	{	
		if( tGames[i].roundnum < curRoundNum )
		{
			curRoundNum = tGames[i].roundnum;
			n = $('#divTournamentGames').find('#tRoundSep'+curRoundNum);
			if( n.length == 0 )
			{
				n = $('#defaultTournamentRoundSep').clone(); 
			}	 
			n.attr('id','tRoundSep'+curRoundNum );
			n.find('#lblRoundNum').html( curRoundNum );
			n.show();
			n.appendTo( '#divTournamentGames' );
		}
	
		yourturn = 0;
		var p1dat = tGames[i].firstplayerscore;
		var p2dat = tGames[i].secondplayerscore; 
		
		playerIsParticipant = 0; 
		if( tGames[i].p1id==playerid ) playerIsParticipant = 1; 
		if( tGames[i].p2id==playerid ) playerIsParticipant = 2; 

		
		if( tGames[i].winningplayer > 0 ) {
			if( playerIsParticipant ) {
				if( tGames[i].winningplayer == playerid )
					customText = "You won!";
				else
					customText = "You lost!";
			} else {
				var winningName, winningScore, losingScore; 
				if( tGames[i].p1id == tGames[i].winningplayer ) {
					winningName = tGames[i].p1u; 
					winningScore = p1dat; 
					losingScore = p2dat; 
				} else {
					winningName = tGames[i].p2u; 
					winningScore = p2dat; 
					losingScore = p1dat; 
				}
				var scoreDiff = winningScore - losingScore; 
				
				customText = winningName + " won by " + scoreDiff + " points.";				
			}
		} else {
			if( playerIsParticipant )
				customText = 0; 
			else {
			
				if( tGames[i].p1rnd == 1 && tGames[i].p2rnd == 1 )
					customText = "Neither player has played yet.";
				else if( p1dat == p2dat )
					customText = "Game currently tied at " + p1dat;
				else if( p1dat > p2dat )
					customText = tGames[i].p1u + " leading " + tGames[i].p2u + " by " + (p1dat-p2dat) + " pts...";
				else
					customText = tGames[i].p2u + " leading " + tGames[i].p1u + " by " + (p2dat-p1dat) + " pts...";
			
				//customText = 'Current Score: ' + p1dat + ' to ' + p2dat;
			}
				//customText = tGames[i].p1u + ':' + p1dat + '  -----  ' + tGames[i].p2u + ':' + p2dat;
		}

		//DisplayTournamentGame( 'divTournamentGames', tGames[i], (playerIsParticipant ? 1 : 0 ), playerid, username, customText );
	
		DisplayTournamentGame( 'divTournamentGames', tGames[i].gameid, tGames[i].p1id, tGames[i].p1u, 
			tGames[i].p2id, tGames[i].p2u, tGames[i].winningplayer, (playerIsParticipant ? 1 : 0 ), p1dat, p2dat, customText )
	
	}
	
}

function JoinTournament()
{
	farkleAlert( '<br><div align="center">Submitting registration...</div>', 'lightgreen', 1 );
	AjaxCallPost( gAjaxUrl, 
		function JoinTournamentHook() 
		{
			farkleAlertHide();
			if( ajaxrequest.responseText )
			{
				// Successful register. 
				var data = farkleParseJSON( ajaxrequest.responseText );
				
				if( data['Error'])
				{
					farkleAlert( data['Error'] );
				}
				else
				{
					$('#divJoinTournament').hide();
					$('#divQuitTournament').show();
					clearTimeout( gTournamentTimer );
					GetTournamentUpdate();
				}
			}
		}, 
		'action=addplayertotourney&playerid='+playerid+'&tid='+gCurTournament );
}

function QuitTournament() {

	if( confirm( "Are you sure you would like to drop out of this tournament? You may join again as long as it has not started." ) ) {
		AjaxCallPost( gAjaxUrl, 
		function () {
			if( ajaxrequest.responseText )	{
				// Successful register. 
				var data = farkleParseJSON( ajaxrequest.responseText );
				
				if( data['Error']) {
					farkleAlert( data['Error'] );
				}
				else {
					$('#divTournamentPlayers').find('#playerCard'+playerid).remove();
					$('#divJoinTournament').show();
					$('#divQuitTournament').hide();
					clearTimeout( gTournamentTimer );
					GetTournamentUpdate();
				}
			}
		}, 
		'action=t_removeplayer&playerid='+playerid+'&tid='+gCurTournament );
	}
}

//DisplayTournamentGame( 'divTournamentGames', tGames[i], (playerIsParticipant ? 1 : 0 ), playerid, username, customText );
function DisplayTournamentGame( appendDivId, gameid, playerid1, username1, playerid2, 
	username2, winnerid, yourturn, player1score, player2score, data2 )
{
	var n;
	var theId = gameid + '-' + playerid1 + '-' + playerid2;
	
	if( !appendDivId ) return 0; 
	
	// If we alreayd find this playerid then use that one -- else add one. 
	n = $('#'+appendDivId).find('#gameCard'+theId);
	if( n.length == 0 )
	{
		n = $('#defaultGameCard').clone(); 
	}	 
	
	n.attr('id','gameCard'+theId );
	
	var color='blue'; 
	if( yourturn && winnerid==0 ) color='orange'; // Your turn
	//if( !yourturn && winnerid==0 ) color='white'; // Not your turn but game in progress
	if( winnerid > 0 && winnerid == playerid ) color='green'; // You won
	if( winnerid > 0 && winnerid != playerid ) color='red';  // You lost
	if( winnerid > 0 && playerid1 != playerid && playerid2 != playerid ) color='gray'; // Not your game but finished
	
	n.attr('buttoncolor', color ); // Green=in progress, Gray = finished
	
	// Fix for IE9
	if( $.browser.msie )
		n.css('background-color', color);

	if( playerid2 == 0 )
	{
		// Tournament bye round
		n.find('#gameCardVs').html( '' );
		n.find('#lblGameCardPlayerstring').html( '(Bye Round)' );
	}
	else
	{
		var pStr = "";
		
		if( winnerid > 0 && winnerid == playerid1 ) 
			pStr = "<img src='/images/trophy.png'> <span style='color: yellow;'>"+username1 + " vs. " + username2;
		else if( winnerid > 0 && winnerid == playerid2 ) 
			pStr = username1 + " vs. "+username2 + " <img src='/images/trophy.png'> <span style='color: yellow;'>";
		else 
			pStr = username1 + " vs. " + username2;
		
		n.find('#lblGameCardPlayerstring').html( pStr );
		n.find('#lblGameCardInfo').html( data2 ); 
		n.off( 'click' );
		n.on( 'click', function() {ShowFarkleGame( gameid ); } );
	}
	
	n.show();
	if( winnerid == playerid1 )	n.find('#player1winimg').show();
	if( winnerid == playerid2 )	n.find('#player2winimg').show();
	
	if( player1score ) n.find('#player1score').html( '<br>' + addCommas(player1score) );
	if( player2score ) n.find('#player2score').html( '<br>' + addCommas(player2score) );
	
	n.appendTo( '#'+appendDivId );
	
	// Return a reference to the newly created div. 
	return n; 
}
