
var lobbyActive = 0;
var gLobbyTimer;
var gLobbyTimer_ticks = 0;
var gLobbyGettingInfo = 0;
var gLobbyAjaxTimeout = 0;
var gCurPlayerTag = 0;

/**
 * Handle home icon click - reload page if already in lobby, otherwise show lobby
 */
function HomeIconClick()
{
	if( divLobbyObj && divLobbyObj.is(":visible") )
	{
		// Already in lobby, reload the page
		location.reload();
		return;
	}
	ShowLobby();
}

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
		return;
	}

	// Parse response using JSON.parse (safer than eval)
	var data;
	try {
		data = JSON.parse( inputData );
	} catch(e) {
		ConsoleDebug( 'DoLobbyUpdateEx - JSON parse error: ' + e.message );
		return;
	}

	// Check for login required response
	if( data.LoginRequired || data.Error )
	{
		ConsoleDebug( 'DoLobbyUpdateEx - Session expired or error: ' + data.Error );
		ShowLogin();
		return;
	}

	// Check if data is an array as expected
	if( !Array.isArray(data) || !data[0] )
	{
		ConsoleDebug( 'DoLobbyUpdateEx - Unexpected data format' );
		return;
	}

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

	// Active Friends section
	if( data[5] && data[5].length > 0 ) {
		$('#divActiveFriendsSection').show();
		$('#divActiveFriends').empty();

		for( var i = 0; i < data[5].length; i++ ) {
			var friend = data[5][i];
			var outerDiv = $('#divActiveFriendTemplate .activeFriendOuter').clone();
			outerDiv.attr('id', 'objActiveFriend' + friend.playerid);

			// Display "known for" emoji from emoji_reactions
			var friendEmoji = '';
			if( friend.emoji_reactions ) {
				var knownFor = getMostCommonEmoji(friend.emoji_reactions);
				if( knownFor && knownFor.emoji ) {
					friendEmoji = knownFor.emoji;
				}
			}
			outerDiv.find('.activeFriendEmoji').text(friendEmoji);

			var card = outerDiv.find('.activeFriendCard');
			var color = friend.cardcolor || 'green';
			if( color.match && color.match(/.png/gi) )
				card.css('background-image', "url('/images/playericons/" + color + "')");
			else
				card.css('background-color', color);

			card.find('.activeFriendName').text(friend.username);
			card.find('.activeFriendStatus').text(friend.status || 'In Lobby');
			card.attr('playerid', friend.playerid);
			card.on('click', function(e) {
				ShowPlayerInfo($(this).attr('playerid'));
			});

			var playBtn = outerDiv.find('.activeFriendPlayBtn');
			playBtn.attr('playerid', friend.playerid);
			playBtn.on('click', function(e) {
				StartGameAgainstPlayer($(this).attr('playerid'));
			});

			outerDiv.appendTo('#divActiveFriends');
		}
	} else {
		$('#divActiveFriendsSection').hide();
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

/**
 * Show bot game selection modal
 */
function showBotGameModal() {
	console.log('showBotGameModal: Function called');

	// Hide the main game type selection screen
	$('#divGameTypes').hide();

	// Hide other game type divs
	$('#divGameType0').hide();
	$('#divGameType1').hide();
	$('#divGameType2').hide();
	$('#divNewGameStart').hide();

	// Show bot game selection div (similar to Play Random)
	$('#divBotGame').show();
}

/**
 * Close bot game selection modal
 */
function closeBotGameModal() {
	$('#botGameModalOverlay').remove();
}

/**
 * Start a game against a bot
 */
function startBotGame(algorithm) {
	console.log('startBotGame: Starting bot game with algorithm: ' + algorithm);
	console.log('startBotGame: playerid =', playerid);
	console.log('startBotGame: gAjaxUrl =', gAjaxUrl);
	ConsoleDebug('startBotGame: Starting bot game with algorithm: ' + algorithm);

	var params = 'action=startbotgame&playerid=' + playerid + '&algorithm=' + algorithm;
	console.log('startBotGame: Params =', params);
	console.log('startBotGame: About to call AjaxCallPost...');

	AjaxCallPost(gAjaxUrl, function() {
		console.log('startBotGame: AJAX callback fired!');
		console.log('startBotGame: Received response:', ajaxrequest.responseText);

		if (ajaxrequest.responseText) {
			try {
				var response = eval("(" + ajaxrequest.responseText + ")");
				console.log('startBotGame: Parsed response:', response);

				if (response.Error) {
					console.error('startBotGame: Error from server:', response.Error);
					return;
				}

				// Start game using same flow as regular games
				// Response is array format: [gameData, playerData, diceData, ...]
				if (response && response[0] && response[0].gameid) {
					console.log('startBotGame: Game created with ID ' + response[0].gameid + ', bot: ' + response.botname);
					console.log('startBotGame: Full response:', response);
					console.log('startBotGame: Calling HideAllWindows and showing game');

					HideAllWindows();
					divGameObj.show();

					console.log('startBotGame: Calling FarkleGameStarted with response');
					FarkleGameStarted(response);
				} else {
					console.error('startBotGame: No gameid in response:', response);
				}
			} catch (e) {
				console.error('startBotGame: Error parsing response:', e);
				ConsoleDebug('startBotGame: Error parsing response: ' + e);
			}
		} else {
			console.error('startBotGame: No response text received');
		}
	}, params);
}