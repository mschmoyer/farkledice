/*
	farklePage.js	
	Desc: Javascript functions related to the general Farkle operations
	
	13-Jan-2013		mas		Updates to support Farkle tournaments again. 
	13-Jan-2013		mas		Fixed hiding the OK button on a farkle Alert 
*/
var gAjaxUrl = 'farkle_fetch.php';

var username = "";
var playerid = 0; // The global playerid of the player currently logged in
var adminlevel = 0;

var PRESTIGE_POINT_LIMIT = 245;
var playerInfo;
var gameInfo;
var gFriendList;

// The outer divs for each "page"
var divLoginObj;
var divGameObj;
var divLobbyObj;
var divNewGameObj;
var divPlayerInfoObj;
var divLeaderBoardObj;
var divFriendObj;
var divInstructionsObj;
var divMenuObj;
var divTournamentObj;
var divTouranmentListObj;
var lblRegErrorObj;
var divAdminObj;
var divChallengeShopObj;
var divChallengeLobbyObj;

var gPlayerInfoItem = 0;
var gAchievementsLoaded = 0;
var gFriendsLoaded = 0;
//var gCurPlayerTag = 0;

var gLastPage;
var gCurPage;
var gPreloadLeaderboard = 0;
var gLastKnownScreen = '';

var gFriends;

/*
 *	pageInit
 *	Desc: This is the initial javascript function that runs when page is loaded. 
 */
function pageInit( )
{
	gAjaxUrl = AjaxFix_wwwroot( gAjaxUrl ); 

	// Grab data from the hidden fields populated by PHP
	var theUser = $('#dataUsername').val();
	var thePlayerid = $('#dataPlayerid').val();
	//var theAdminLevel = $('#dataAdminLevel').val();
	var theResumeGameId = $('#dataResumegameid').val();
	
	g_friendData =  eval( "(" + $('#m_friendInfo').val() + ")" );
	g_lbData =  	eval( "(" + $('#m_lbInfo').val() + ")" );
	g_playerInfo = 	eval( "(" + $('#m_pInfo').val() + ")" );
	g_lobbyInfo = 	$('#m_lobbyInfo').val();
	gLastKnownScreen = $('#m_lastknownscreen').val();
	
	var cardBg = 'dicebackSet6.png';
	if(g_playerInfo) {
		if(g_playerInfo[0] && g_playerInfo[0].cardBg) cardBg = g_playerInfo[0].cardbg; 
	} 
	farkleInit( cardBg );
	
	$('#m_friendInfo').val('');
	$('#m_lbInfo').val('');
	$('#m_pInfo').val('');
	$('#m_lobbyInfo').val('');
	
	divLoginObj = 			$('#divLogin');
	divGameObj = 			$('#divGame');
	divLobbyObj = 			$('#divLobby');
	lblRegErrorObj = 		$('#lblRegError');
	divNewGameObj = 		$('#divNewGame');
	divPlayerInfoObj = 		$('#divPlayerInfo');
	divLeaderBoardObj = 	$('#divLeaderBoard');
	divFriendObj = 			$('#divFriends');
	divInstructionsObj = 	$('#divInstructions');
	divMenuObj = 			$('#divLobbyMenu');
	divTournamentObj = 		$('#divTournament');
	//divTournamentListObj = 	$('#divTournamentList');
	divAdminObj = 			$('#divAdmin');
	divChallengeShopObj = 	$('#divChallengeShop');
	divChallengeLobbyObj = 	$('#divChallengeLobby');

	if( theResumeGameId ) ConsoleDebug( 'ResumeGameId = '+theResumeGameId ); 
	
	// Default game info
	gameInfo = new Object();
	gameInfo.breakIn = 0;
	gameInfo.playTo = 10000;
	gameInfo.gameMode = 2;
	gameInfo.gameWith = 1;
	gameInfo.gamePlayers = 2;
	
	if( theUser && thePlayerid )
	{
		// already logged in. 
		username = theUser;
		playerid = thePlayerid;
		
		// Handle an admin user
		/*if( theAdminLevel) 
		{
			adminlevel = theAdminLevel;
			ConsoleDebug( 'Admin level of ' + adminlevel + ' detected.' );
		}*/
		
		ConsoleDebug( 'Detected ' + username + ' is already logged in.' );
		
		if( theResumeGameId > 0 )
		{
			// Player is resuming a game. 
			ConsoleDebug('Resuming GameId ' + theResumeGameId );
			ShowFarkleGame( parseInt(theResumeGameId) );
		}
		else
		{
			ConsoleDebug( 'Last screen = '+gLastKnownScreen );
			
			/*
			if( gLastKnownScreen && gLastKnownScreen != 'lobby' ) {
				DoLobbyUpdate( g_lobbyInfo );
				switch(gLastKnownScreen) {
				case 'playerinfo':
					ShowPlayerInfo( $('#m_lastplayerinfoid').val() );
					break;				
				default:
					ConsoleDebug( 'unknown command: '+gLastKnownScreen ); 
				}
			} else {		
				if( g_lobbyInfo )
					DoLobbyUpdate( g_lobbyInfo );
				else
					ShowLobby();
			}*/
			if( g_lobbyInfo )
					DoLobbyUpdate( g_lobbyInfo );
				else
					ShowLobby();
		}
	}
	else
	{
		ConsoleDebug( 'No user logged in. Showing login screen.' );
		ShowLogin();
	}
}	

function HideAllWindows()
{
	ConsoleDebug( 'HideAllWindows - Clearing timeouts and hiding everything');

	clearTimeout( gLobbyTimer );
	clearTimeout( gGameTimer );
	clearTimeout( gTournamentTimer );
	
	lobbyActive = 0;
	gameWindowActive = 0;
	gLobbyGettingInfo = 0;
	
	$('#divLobbyIdle').hide();
	
	if( divInstructionsObj.is(":visible") ) gLastPage = divInstructionsObj;
	divInstructionsObj.hide();
	
	if( divNewGameObj.is(":visible") ) gLastPage = divNewGameObj;
	divNewGameObj.hide();
	
	if( divLobbyObj.is(":visible") ) gLastPage = divLobbyObj;
	divLobbyObj.hide();
	
	if( divGameObj.is(":visible") ) gLastPage = divGameObj;
	divGameObj.hide();
	
	if( divLoginObj.is(":visible") ) gLastPage = divLoginObj;
	divLoginObj.hide();
	$('#divResetPass').hide();
	
	if( divPlayerInfoObj.is(":visible") ) gLastPage = divPlayerInfoObj;
	divPlayerInfoObj.hide();
	
	if( divLeaderBoardObj.is(":visible") ) gLastPage = divLeaderBoardObj;
	divLeaderBoardObj.hide();
	
	if( divFriendObj.is(":visible") ) gLastPage = divFriendObj;
	divFriendObj.hide();
	
	if( divTournamentObj.is(":visible") ) gLastPage = divTournamentObj;
	divTournamentObj.hide();

	//if( divTournamentListObj.is(":visible") ) gLastPage = divTournamentListObj;
	//divTournamentListObj.hide();
	
	if( divAdminObj )
	{
		if( divAdminObj.is(":visible") ) gLastPage = divAdminObj;
		divAdminObj.hide();
	}

	if( divChallengeShopObj )
	{
		if( divChallengeShopObj.is(":visible") ) gLastPage = divChallengeShopObj;
		divChallengeShopObj.hide();
	}
	$('#slotSelectionOverlay').hide();

	if( divChallengeLobbyObj )
	{
		if( divChallengeLobbyObj.is(":visible") ) gLastPage = divChallengeLobbyObj;
		divChallengeLobbyObj.hide();
	}
}

function PageGoBack()
{
	// If no last page then go to lobby. 
	if( !gLastPage ) { 
		ShowLobby();
		return 0; 
	}

	if(	gLastPage == divInstructionsObj ) {
		ShowInstructions(); return 1; 
	} else if( gLastPage == divNewGameObj ) {
		ShowNewGame(); return 1; 
	} else if( gLastPage == divLobbyObj ) {
		ShowLobby(); return 1; 
	} else if( gLastPage == divGameObj ) {
		ShowFarkleGame( gCurGameId ); return 1; 
	} else if( gLastPage == divLoginObj ) { 		
		ShowLogin(); return 1; 
	} else if( gLastPage == divLeaderBoardObj ) { 	
		ShowLeaderBoard(); return 1; 
	} else if( gLastPage == divFriendObj ) { 		
		ShowFriends(); return 1; 
	} else if( gLastPage == divPlayerInfoObj ) {	
		ShowCurPlayerInfo(); return 1; 
	} else if( gLastPage == divTournamentObj ) { 
		ShowTournament( ); return 1; 
	//} else if( gLastPage == divTournamentListObj ) { 
	//	ShowTournamentList( ); return 1; 
	} else if( gLastPage == divAdminObj ) {
		ShowAdmin(); return 1; 
	}
	
	// If nothing else worked go to lobby. 
	ShowLobby();
	return 0; 
}

function ShowNewGame() {	

	HideAllWindows();
	divNewGameObj.show();
	// Defaults
	SelectBreakIn(500);
	SelectPlayTo(10000);
	SelectGamePlayers(2);

	$('#divGameType0').hide();
	$('#divGameType1').hide();
	$('#divGameType2').hide();
	$('#divGameTypes').show();
	$('#divNewGameStart').hide();
	$('#divNewGameRules').hide();

	if( !gFriendList )
	{		
		gFriendList = Array();			
		FarkleAjaxCall( NewGameHook, 'action=getnewgameinfo&playerid='+playerid );
	}
	else
	{
		var i; 
		for( i=0; i< gFriendList.length; i++ )
		{
			unchoosePlayer( i );
		}
	}
}

function NewGameHook() {
	var i;
	var s = "";
	
	var data = FarkleParseAjaxResponse( ajaxrequest.responseText );
	if( data ) {
		var friends = data[0];	
		
		if( friends.length > 0 ) {
		
			$('#divPlayerChoices').html('');
			
			for( i=0; i< friends.length; i++ ) {				
				var friend = friends[i];
				ConsoleDebug( 'adding friend ' + friend.username + ', id=' + friend.playerid );
				s += '<div id="divPlayerChoice'+friend.playerid+'" class="playerChoice" playerid="'+friend.playerid+'" onClick="choosePlayer('+friend.playerid+')">';
				s += '<img id="imgChooseCheck'+friend.playerid+'" class="pCheckBox" src="/images/nocheckmark.png">';
				s += '<span id="imgChooseSpacer'+friend.playerid+'" style="float: right; margin: 3px; width: 4px;"></span>';
				s += friend.username + '</div>';				
				gFriendList.push( friend );
			}
			$('#divPlayerChoices').append(s);
			$('#btnPlayFriends').removeAttr('disabled');
			
		} else {
			$('#btnPlayFriends').attr('disabled','');
		}
	}
}

function choosePlayer( index ) {

	var divChoiceObj = document.getElementById( 'divPlayerChoice'+index );

	if( divChoiceObj.getAttribute('checked') ) {
		$('#imgChooseCheck'+index).attr('src','/images/nocheckmark.png');
		divChoiceObj.removeAttribute('checked');
	} else {
		$('#imgChooseCheck'+index).attr('src','/images/checkmark.png');
		divChoiceObj.setAttribute('checked','true');
	}
}

function unchoosePlayer( index ) {

	var divChoiceObj = document.getElementById( 'divPlayerChoice'+index );
	if( divChoiceObj ) {
		$('#imgChooseCheck'+index).attr('src','/images/nocheckmark.png');
		divChoiceObj.removeAttribute('checked');
	}
}

function StartGameAgainst() {
	StartGameAgainstPlayer( gCurPlayerid ); 
}

// Start a new 10-round game against yourself and the given opponentid
function StartGameAgainstPlayer( opponentid ) {
	// Default game info
	gameInfo = new Object();
	gameInfo.breakIn = 0;
	gameInfo.playTo = 10000;
	gameInfo.gameMode = GAME_MODE_10ROUND;
	gameInfo.gameWith = GAME_WITH_FRIENDS;
	var newGamePlayers = new Array( playerid, opponentid );
	StartGameEx( newGamePlayers );
}

function StartGame() {
	var i;
	var found = 0;
	var divChoiceObj;
	var newGamePlayers = new Array();
	
	if( gameInfo.gameWith == GAME_WITH_FRIENDS ) {	
		for( i=0; i< gFriendList.length; i++ ) {
			//ConsoleDebug(' looking for divPlayerChoice'+gFriendList[i].playerid);
			divChoiceObj = document.getElementById( 'divPlayerChoice'+gFriendList[i].playerid );
			if( divChoiceObj.getAttribute('checked') ) {
				newGamePlayers = newGamePlayers.concat( parseInt(divChoiceObj.getAttribute('playerid')) );
				found++;
			}
		}
	} else if( gameInfo.gameWith == GAME_WITH_SOLO ) {
		newGamePlayers = newGamePlayers.concat( playerid );
	}
	StartGameEx( newGamePlayers );
 
	for( i=0; i< gFriendList.length; i++ )
		unchoosePlayer( i );
}

function StartGameEx( playerArray ) {
	// Enforce maximum of 6 players allowed
	if( playerArray.length > 6 && gameInfo.gameMode == GAME_MODE_STANDARD )	{
		farkleAlert('Maximum 6 players allowed for a standard game.');
		return 0;
	}
	
	// Enforce maximum of 6 players allowed
	if( playerArray.length > 32 && gameInfo.gameMode == GAME_MODE_10ROUND )	{
		farkleAlert('Maximum 32 players allowed for a 10-Round game.');
		return 0;
	}
	
	// Enforce no opponents selected
	if( playerArray.length == 0 && gameInfo.gameWith  == 1  ) {
		farkleAlert('Must choose at least one opponent to continue');
		return 0;
	}

	if( gameInfo.gameWith == GAME_WITH_SOLO ) {
		gameInfo.breakIn = 0;
		gameInfo.gameMode == GAME_MODE_10ROUND;
		farkleAlert( '<br><div align="center">Starting Solo game...</div>', 'lightgreen', 1 );
		
	} else if ( gameInfo.gameWith == GAME_WITH_RANDOM ) {
		farkleAlert( '<br><div align="center">Starting game...</div>', 'lightgreen', 1 );
	} else {
		farkleAlert( '<br><div align="center">Starting game...</div>', 'lightgreen', 1 );
	}

	//if(typeof _gaq !== 'undefined') _gaq.push( ['_trackEvent', 'Ajax', 'StartGame', 'Playerid', playerid ]);

	// Add the player to the game array. 
	var gamePlayers = playerArray.concat( parseInt(playerid) );
	
	var params = 'action=startgame&players='+JSON.stringify(gamePlayers)+'&breakin='+
		gameInfo.breakIn+'&playto='+gameInfo.playTo+'&gamewith='+gameInfo.gameWith+'&gamemode='+gameInfo.gameMode+
		'&rp='+gameInfo.gamePlayers;
		
	AjaxCallPost( gAjaxUrl, 
		function() {
			var data = FarkleParseAjaxResponse( ajaxrequest.responseText );
			if( data ) {
				HideAllWindows();
				divGameObj.show();
				FarkleGameStarted( data );
			}
		}, params );

	return 1; 
}	

function ShowFarkleGame( theGameId )
{
	HideAllWindows();
	divGameObj.show();
	ResumeGame( theGameId );
}

function ShowInstructions() {
	HideAllWindows();
	divGameObj.hide();
	divInstructionsObj.show();
}

function StartSoloGame() {
	gameInfo.gameWith = GAME_WITH_SOLO;
	gameInfo.gameMode = GAME_MODE_10ROUND;
	StartGame();
}

function SelectPlayType( theType, theWith )
{
	$('.gameTypeDiv').css('display','none'); // Hide all of the game type divs
	
	$('#divGameType' + theWith).css('display','block');
	$('#divGameTypes').css('display','none');
	$('#divNewGameStart').css('display','block');
	//$('#btnShowCustomRules').css('display','block');
	
	$('#divNewGameRules').show();
	//$('#btnShowCustomRules').hide();
	
	gameInfo.gameWith = theWith;
	
	$('#btnGame2Player').hide();
	$('#btnGame4Player').hide();
	if( theWith == GAME_WITH_SOLO )
	{
		//$('#btnGameMode1').attr('disabled','');
		
	}
	else
	{
		if( theWith == GAME_WITH_RANDOM )
		{
			$('#divPlayerChoices').hide();
			$('#btnGame2Player').show();
			$('#btnGame4Player').show();
		}
		else
			$('#divPlayerChoices').show();

		
		//$('#btnGameMode1').removeAttr('disabled');
	}	
	SelectGameMode( theType );
} 

function SelectBreakIn( theAmt )
{
	var amt = parseInt(theAmt);
	
	$('#btnBreakIn0').attr('buttoncolor', ( amt == 0 ) ? 'brown' : 'grey' );
	$('#btnBreakIn250').attr('buttoncolor',( amt == 250 ) ? 'brown' : 'grey');
	$('#btnBreakIn500').attr('buttoncolor',( amt == 500 ) ? 'brown' : 'grey');
	$('#btnBreakIn1000').attr('buttoncolor',( amt == 1000 ) ? 'brown' : 'grey');
	gameInfo.breakIn = amt;
}

function SelectPlayTo( theAmt )
{
	var amt = parseInt(theAmt);
	$('#btnPlayTo2500').attr('buttoncolor',( amt==2500 ) ? 'brown' : 'grey');
	$('#btnPlayTo5000').attr('buttoncolor',( amt==5000 ) ? 'brown' : 'grey');
	$('#btnPlayTo10000').attr('buttoncolor',( amt==10000 ) ? 'brown' : 'grey');
	gameInfo.playTo = amt;
}

function SelectGamePlayers( gamePlayers )
{
	gameInfo.gamePlayers = parseInt(gamePlayers);
	
	if( gameInfo.gamePlayers == 2 )
	{
		$('#btnGame2Player').css('border','2px solid yellow');
		$('#btnGame4Player').css('border','1px solid black');
	}
	else if ( gameInfo.gamePlayers == 4 )
	{
		$('#btnGame4Player').css('border','2px solid yellow');
		$('#btnGame2Player').css('border','1px solid black');
	}
}

function SelectGameMode( theMode )
{
	mode = GAME_MODE_10ROUND; 
	gameInfo.gameMode = mode;
}

function ShowNewGameCustomRules()
{
	$('#divNewGameRules').show();
	//$('#btnShowCustomRules').hide();
}

function farkleAlert( msg, color, hideOkButton )
{
	if( !color )
		color = 'red';
		

	$('#farkleAlertMsg').html( msg );
	var falert = $('#farkleAlert');
	falert.attr('feltcolor', color );
	if( hideOkButton )
		$('#farkleAlertOkButton').hide();
	else
		$('#farkleAlertOkButton').show();
	falert.fadeIn(200);
}

function farkleMessage( msg )
{
	$('#farkleAlertMsg').html( msg );
	$('#farkleAlert').show();
}

function farkleAlertHide()
{
	$('#farkleAlert').fadeOut(200);
}

function DisplayPlayerTag( appendDivId, pData, score ) //thePlayerId, name, color, score, title, fbid, allowDupes )
{
	var n;	
	var newCard = 0; 
	
	if( !appendDivId || !pData ) {
		ConsoleDebug( "DisplayPlayerTag: missing parameters. Exiting." ); 
		return 0;
	}
	
	// If we alreayd find this playerid then use that one -- else add one. 
	n = $('#'+appendDivId).children('#playerCard'+pData.playerid);
	if( n.length == 0 ) {
		n = $('#defaultPlayerCard').clone(); 
		newCard = 1;
	}
	
	n.attr('id','playerCard'+pData.playerid ).attr('playerid', pData.playerid);

	if( pData.cardcolor && pData.cardcolor.match(/.png/gi) )
	{
		var imgUrl = "url(/images/playericons/" + pData.cardcolor + ")";
		if( n.css('background-image').indexOf(pData.cardcolor) === -1 )
			n.css('background-image', imgUrl );
	}
	else
	{
		n.css('background-color', ( pData.cardcolor ? pData.cardcolor : 'green' ) );
	}
	n.find('.playerName').html( GetLevelHTML(pData.playerlevel) + pData.username );
	n.find('.playerTitle').html( pData.playertitle );

	if( score.length > 0 ) n.find('.playerAchScore').html( addCommas( score ) );

	n.find('#playerImg').show().attr('src', '/images/stock.png').addClass('playerCardImage');
	
	n.find('#playerImg').off( 'click' );
	n.find('#playerImg').on( 'click', function() {
		PlayerTagClick(n); 
	} );
	
	if( newCard ) {		
		n.show();
		n.appendTo( '#'+appendDivId );
	}
	return n; 
}

function PlayerTagClick(event) {
	ShowPlayerInfo( parseInt( $(event).attr('playerid') ) ); 
}

function DisplayAchievement( appendDivId, ach, showEarnedDate, forceShowImg ) {
	var n;
	if( !appendDivId || !ach ) {
		ConsoleDebug( 'Game error: DisplayAchievement() called with missing parameters.' ); 
		return 0; 
	}
	
	n = $('#'+appendDivId).find( '#divAch'+ach.achievementid );
	
	
	if( n.length == 0 )	{
		// This is all the stuff we do when a new div is created (not a refresh)
		n = $('#divAchTemplate').clone();		
		n.attr('id','divAch'+ach.achievementid );	
		
		if( ach.earned == 1 || forceShowImg == 1 )
		{							
			n.find('#imgShowAch').attr('src', '/images/achieves/' + ach.imagefile);
			n.removeAttr('disabled');
		}
		else
		{
			n.find('#imgShowAch').attr('src', '/images/achieves/noachieve.png').attr('disabled','');
			n.attr('disabled','');
			
		}
		//n.find('#tdAchDesc') = '<b>' + data[0][i].title + '</b><br><span style="font-size:12px;">' + data[0][i].description + '</span>';
		//n.find('#tdAchPoints') = '<b style="font-size: 20px;">' + data[0][i].worth + '</b>';
		n.find('#lblAchTitle').html( ach.title ); 
		n.find('#lblAchDesc').html( ach.description ); 
		n.find('#lblAchPoints').html( ach.worth ); 
		if( ach.earned == 1 && showEarnedDate ) n.find('#lblAchEarned').html( 'Earned: '+ ach.formatteddate ); 
	} else {
		// ?
	}
	
	n.show();
	n.appendTo( '#'+appendDivId );
	
	// Return a reference to the newly created div. 
	return n; 
}

/*  
	Function: DisplayGame()
	
	Parameters: 
		appendDivId - The div of the container that will hold this new game card
		game - Data about the game you are displaying. 
		
	Returns: reference to newly created div of player card. 
*/
function DisplayGame( appendDivId, game, showWinLoss, refPlayer, refPlayerName, customText )
{
	var n;
	var color='white'; 
	var mPlayerString = ( game.playerstring ? game.playerstring : 'Unknown Game' );
	
	if( !appendDivId || !game ) {
		ConsoleDebug( 'Game error: DisplayGame() called with missing parameters.' ); 
		return 0; 
	}
	
	// If we alreayd find this playerid then use that one -- else add one. 
	n = $('#'+appendDivId).find('#gameCard'+game.gameid);
	
	// This is all the stuff we do when a new div is created (not a refresh)
	if( n.length == 0 )	{
		n = $('#defaultGameCard').clone(); 
		
		n.attr('id','gameCard'+game.gameid );	
		
		// Remove user's name from playerstring. So "mschmoyer vs. amy" if I was amy would be "vs. mschmoyer" 
		if( refPlayerName != 0 && mPlayerString && mPlayerString.indexOf('vs.') != -1 )
		{
			var strSplit = game.playerstring.split(' vs. ');
			mPlayerString = ( strSplit[0] == refPlayerName ? strSplit[1] : strSplit[0] ); 
		}
		
		if( $(document).width() < 400 && !customText )
			if( mPlayerString.length > 16 ) mPlayerString = mPlayerString.substr(0, 16)+'...';
		
		n.find('#lblGameCardPlayerstring').html( mPlayerString );
		
		// Add the click event that sends user to the game. 
		n.off( 'click' );
		n.on( 'click', function() {ShowFarkleGame( game.gameid ); } );
	}

	// User's turn? Game is orange
	//if( parseInt(game.yourturn) > 0 && parseInt(game.winningplayer) == 0 ) color='orange';
	
	// Show red/green if desired. Else show "blue" 
	if( game.winningplayer > 0 ) {
		if( showWinLoss ) {
			// Green if user won
			if( game.gamewith == GAME_WITH_SOLO ) {
				color='grey';
				n.find('#lblGameCardInfo').html( game.gamefinish+' - Solo'); 
			} else if( game.winningplayer == refPlayer ) {
				color='green';	
				n.find('#lblGameCardInfo').html( game.gamefinish+' - Win'); 
				n.find('#gameCardImage').attr('src', '/images/trophy.png').show();
			} else {
				color='red';
				n.find('#lblGameCardInfo').html(game.gamefinish+' - Loss'); 
			}
		} else {			
			// Game is finished
			if( refPlayerName == 0 )
				color='grey';
			else
				color='blue'; 
			n.find('#lblGameCardInfo').html('Game finished!'); 			
		}
	} else {	
		if( refPlayerName != 0 &&game.finishedplayers == (game.maxturns-1) && game.playerround == 1 ) {
			// Everybody has played except you. 
			color='orange';
			n.find('#lblGameCardInfo').html('Waiting on you.'); 
		} else if( refPlayerName == 0 || game.finishedplayers < game.maxturns && game.playerround == 11 ) {
			if( refPlayerName == 0 )
				color='blue';
			else
				color='grey'; 
			n.find('#lblGameCardInfo').html('Waiting on others...'); 		
		} else if( game.finishedplayers == 0 && game.playerround == 1 ) {
			color='darkorange'
			n.find('#lblGameCardInfo').html('Nobody has rolled yet.'); 
		} else {
			color='orange'
			n.find('#lblGameCardInfo').html('Game in progress...'); 
		}	
	}
	
	if( customText ) {
		n.find('#lblGameCardInfo').html( customText ); 
	}
	
	//if( winnerid > 0 && playerid1 != playerid && playerid2 != playerid ) color='gray'; // Not your game but finished
	
	n.attr('buttoncolor', color );
	
	// Fix for IE9
	if( $.browser.msie )
		n.css('background-color', color);

	n.show();
	n.appendTo( '#'+appendDivId );
	
	// Return a reference to the newly created div. 
	return n; 
}



