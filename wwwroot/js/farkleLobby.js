
var lobbyActive = 0;
var gLobbyTimer;
var gLobbyTimer_ticks = 0;
var gLobbyGettingInfo = 0;
var gLobbyAjaxTimeout = 0; 
var gCurPlayerTag = 0;

function ShowLobby()
{
	if( playerid == 0 ) 
	{
		ConsoleDebug( 'ShowLobby: Current PlayerId is 0 or undefined. Returning player to login screen.' );
		ShowLogin();
		return 0;
	}
	HideAllWindows();
	
	divLobbyObj.show();
	
	//$('#divLobbyPlayerCard').empty();
	StartLobbyTimer();
}

function StartLobbyTimer()
{
	ConsoleDebug( 'StartLobbyTimer: restarting Lobby timer' );
	timer_ticks = 0; // Refresh timer ticks to 0 (speed it backup).
	gLobbyTimer_ticks = 0;
	clearTimeout( gLobbyTimer );
	GetLobbyInfo( 1 );
}

function GetLobbyInfo( repeat )
{
	ConsoleDebug( 'GetLobbyInfo: Lobby ticks: ' + gLobbyTimer_ticks + ', repeat?' + repeat );
	
	if( !gLobbyGettingInfo ) 
	{
		ConsoleDebug( 'GetLobbyInfo: Doing actual lobby update...' ); 
		var params = 'action=getlobbyinfo&playerid='+playerid;
		gLobbyGettingInfo = 1;
		
		AjaxCallPost( gAjaxUrl, function() {
			gLobbyGettingInfo = 0;	
			clearTimeout( gLobbyAjaxTimeout ); 
			if( ajaxrequest.responseText )
				DoLobbyUpdateEx( ajaxrequest.responseText );
		}, params );	
		
		gLobbyAjaxTimeout = setTimeout( function() { gLobbyGettingInfo = 0; }, 15000 ); 
	} else {
		gLobbyGettingInfo = 0; 
	}
	
	if( repeat )
	{
		gLobbyTimer_ticks++;
		if( gLobbyTimer_ticks < 20 )
		{
			gLobbyTimer = setTimeout( function(){ GetLobbyInfo(1);}, 10000); 
		}
		else if( gLobbyTimer_ticks < 40 )
		{
			gLobbyTimer = setTimeout( function(){ GetLobbyInfo(1);}, 20000); 
		}
		else
		{
			LobbyGoIdle();
		}		
	}
}

function DoLobbyUpdate( inputData )
{
	ConsoleDebug( 'DoLobbyUpdate: restarting Lobby timer' );
	timer_ticks = 0; // Refresh timer ticks to 0 (speed it backup).
	gLobbyTimer_ticks = 0;
	clearTimeout( gLobbyTimer );
	gLobbyTimer = setTimeout( function(){ GetLobbyInfo(1);}, 10000); 
	$("#divLobby").css('display','block');
	DoLobbyUpdateEx( inputData );
}

function DoLobbyUpdateEx( inputData )
{
	//ConsoleDebug( 'DoLobbyUpdateEx - Entered.' );

	if( !inputData )
	{
		ConsoleDebug( 'DoLobbyUpdateEx - no input data.');
	}
	
	// Successful register. 
	data = eval( "(" + inputData + ")" );
	data[0].playerid=playerid;
	
	if( username != data[0].username ) {
		username = data[0].username;		
		$('#lblPlayerNameLobby').html('"'+username+'"');
	}
	
	if( gCurPlayerTag != playerid )
	{
		$('#divLobbyPlayerCard').empty();
		var newTag = DisplayPlayerTag( 'divLobbyPlayerCard', data[0], '' );
		newTag.css('margin-bottom', '0px');
		gCurPlayerTag = playerid;
		
		// Get Width
		
	}

	if( data[1] ) // Games in progress
	{
		var s = "";
		
		$('#divLobbyGames').html('');
		
		for( i=0; i < data[1].length; i++ )
		{
			var g = data[1][i];
			var n = DisplayGame( 'divLobbyGames', g, 0, playerid, username );
			//n.width( '95%' );
			//n.height( '28px' );
			//ConsoleDebug('Adding game ' + g.gameid);
			n.addClass( 'lobbyGameCard' ); 
		}
		if( data[1].length == 0 )
		{
			$('#btnLobbyNewGame').attr('buttoncolor','green');
			$('#divLobbyNoGames').show();
		}
		else
		{
			$('#btnLobbyNewGame').removeAttr('buttoncolor');
			$('#divLobbyNoGames').hide();
		}
		
		LobbyUpdateXPBar( data[0] );
		
		if( username.indexOf('guest') == 0 )
		{
			s += '<p>Click "Register" to create a username and set an email. Then you can earn ranking on leaderboards and play with friends!</p>';
		}
		
		$('#divGamesInProgress').html(s);
	}
	
	CheckForAchievement( data[2] );	
	CheckForLevel( data[3] );
	
	var tButton = $('#btnLobbyTournament');
	if( data[4].tournamentid ) {		
		tButton.attr('src', '/images/tournaments/'+data[4].lobbyImage);
		tButton.off('click');
		tButton.on('click', function() { ShowTournamentEx( data[4].tournamentid ) } );
		tButton.show();
	} else {
		tButton.off('click').hide();
	}
	
	if( $('#m_doublexp').val() > 0 ) {
		$('#lblDoubleXP').fadeIn();
	} else {
		$('#lblDoubleXP').hide();
	}

	return 1;
}

function LobbyUpdateXPBar( playerData ) {
	// Get Width
	var maxWidth = window.getComputedStyle(document.getElementById("divLobbyPlayerCard")).width.substr(0,3);
	var xpPercent =( parseInt(playerData.xp) / parseInt(playerData.xp_to_level))*100 ;
	var xpNeg = ( 100 - xpPercent); 
	//var barWidth = Math.max( parseInt(maxWidth * xpPercent) - 2, 0 );
	//var negWidth = Math.max( maxWidth-barWidth - 2, 0 ); 
	$('#xpBar').css('width', parseInt(xpPercent)+'%' );
	$('#xpNeg').css('width', parseInt(xpNeg)+'%' );
}

function LobbyGoIdle()
{
	$('#divLobby').hide();
	$('#divLobbyIdle').show();
	clearTimeout( gLobbyTimer );
}

function LobbyBackFromIdle()
{
	$('#divLobby').show();
	$('#divLobbyIdle').hide();
	StartLobbyTimer();
}