// Game information
var gGameData;					// Game data for selected game
var gGamePlayerData;			// The player data for the selected game
var gCurGameId = 0;				// The game this player is currently viewing.
var gLastTurn = 0;				// Used to display who scored how much last round.
var gGameState = 0;				// 1 = your turn, 2 = opponent turn
var gLastGameState = 0;
var gLastRoundNum = 0; 			// Used to keep track of solo games
var gGameIsWon = 0; 

var gDiceData;					// Dice data including which is saved and/or scored
var gDiceOnTable;				// Compilation of actual dice on the table
var gFarkleDice;				// Helps to show accurate dice when you farkle
var gLastFarkle = 0;
var gTempRollData = 0;

var g_myPlayerIndex = -1;

var gRoundScore = 0; 
var gTurnScore = 0;

// Timer Variables
var gGameTimer;
var timer_ticks = 0;
var gDisplayingAchievement = 0;
var gGameAjaxStatus = 0;
var gAjaxStartTimestamp = 0; 

var btnRollDiceObj;
var btnPassObj;
var divTurnActionObj;
var divGameInfoObj;

var quitCountsAsLoss = 0;
// ------------------------------------------
// Constants
var GAME_MODE_STANDARD = 1;
var GAME_MODE_10ROUND = 2;

var GAME_WITH_RANDOM = 0;
var GAME_WITH_FRIENDS = 1;
var GAME_WITH_SOLO = 2;

var GAME_STATE_LOADING = 0;
var GAME_STATE_ROLLING = 1;
var GAME_STATE_ROLLED = 2; 
var GAME_STATE_PASSED = 3; 
var GAME_STATE_WATCHING = 4; 

var LAST_ROUND = 10;

var PLAYERLEVEL_FINISH_XP = 5; 
var PLAYERLEVEL_WIN_XP = 5; 
var PLAYERLEVEL_GOODROLL_XP = 1; 
var PLAYERLEVEL_SOLO_XP = 3; 

var SCORE_VIEW_DELAY_MS = 1500;
	
function FarkleResetGame( theGameId )
{
	ConsoleDebug( ' ** Resetting Farkle Game Page ** ' ); 

	// Reset game variables
	gGameData = new Object();
	gGamePlayerData = new Object(); 
	
	gCurGameId = theGameId;	
	gGameData.gameid = theGameId; 
	gGameAjaxStatus = 0;
	FarkleGameUpdateState( GAME_STATE_LOADING );
	
	$('#divGamePlayers').empty();		
	FarkleDiceReset();	
}
	
function ResumeGame( theGameId )
{
	ConsoleDebug( 'Resuming Farkle game #'+theGameId+'...' ); 
	FarkleResetGame( theGameId );
	//clearTimeout( gLobbyTimer );
	//StartTimer();
	farkleGetUpdate();
}

function FarkleGameStarted( data )
{
	ConsoleDebug( "Displaying game #"+data[0].gameid+". We already have game data." ); 
	FarkleResetGame( data[0].gameid );
	GameUpdateEx( data );
}

function StartTimer()
{
	timer_ticks = 0; // Refresh gGameTimer ticks to 0 (speed it backup ).
	clearTimeout( gGameTimer );			
	farkleGetUpdate( 1 );		
}

function DelayGameUpdate( milliseconds )
{
	timer_ticks = 0; // Refresh gGameTimer ticks to 0 (speed it backup ).
	clearTimeout( gGameTimer );	
	gGameTimer = setTimeout( function() {farkleGetUpdate( 1 ); }, milliseconds );
}

function farkleGetUpdate()
{
	// Only do the update if no other update is pending
	gGameAjaxStatus = 1;
	FarkleAjaxCall( FarkleGameUpdateHook, 'action=farklegetupdate&gameid='+gGameData.gameid );
	//GameTimerTick( repeat );
}

function FarkleGameUpdateHook() {	
	ConsoleDebug( "Recieved Farkle game update data." ); 
	gGameAjaxStatus = 0;			
	var data = FarkleParseAjaxResponse( ajaxrequest.responseText );
	if( data ) {
		GameUpdateEx( data );
	} else {			
		clearTimeout( gGameTimer );
		ShowLobby();
	}
}

function GameTimerTick( repeat ) {
	ConsoleDebug( 'GameTimerTick: Game ticks: ' + timer_ticks + ', repeat?' + repeat );
	if( repeat ) {
		timer_ticks++;
		if( timer_ticks < 30 ) {
			clearTimeout( gGameTimer );	
			gGameTimer = setTimeout( function() { farkleGetUpdate( 1 ); }, 10000); // 10 second fast poll
		} else {
			GameGoIdle();
		}
	}
}

function GameUpdateEx( gameData ) {

	if( !gameData[0] ) {
		ConsoleError( "Game update called with no game data. Bailing." ); 
		return 0; 
	}
	
	ConsoleDebug( "Game Data Update..." ); 
	
	// Only do update if the response is valid for current game
	if( gCurGameId == gameData[0].gameid ) {
		// Store game data in some global variables
		if( gGameData ) gLastTurn = gGameData.currentturn;
		
		gGameData = gameData[0]; 
		PopulatePlayerData( gameData[1] );	
		
		gDiceData = gameData[2];
		gDiceOnTable = gameData[3];	

		if( gGameData.winningplayer > 0 ) {
			$('#lblGameExpires').html( 'Game was finished on ' + gGameData.gamefinish );
		} else {
			$('#lblGameExpires').html( 'Game will expire on ' + gGameData.gameexpire );
		}
		
		if( gGameData.gamewith == GAME_WITH_SOLO )
			$('#lblGameInfo').html( "<i>Solo games do not give wins and limited XP.</i>" );
			
		$('#dbgGameId').html( gGameData.gameid );
		
		CheckForAchievement( gameData[4] );
		// What is 5? 
		CheckForLevel( gameData[6] );
		
		if( gGameData.winningplayer > 0 ) {
			FarkleGameDisplayEnded();
		} else {				
			FarkleGameActiveMode();
		}
	}
}
		
function FarkleGameActiveMode() {
	
	ConsoleDebug( "Doing an active game update. Player is on round "+gGamePlayerData[g_myPlayerIndex].playerround );
	$('#btnPlayAgain').hide();
	$('#btnNewRandom').hide();
	$('#btnPostGame').hide();
	
	$('#btnRollDice').show();
	$('#btnPass').show();

	if( gGamePlayerData[g_myPlayerIndex].playerround <= LAST_ROUND ) {
	
		gGameAjaxStatus = 0;
		gRoundScore = parseInt(gGamePlayerData[g_myPlayerIndex].roundscore);
		FarkleGameUpdateState( GAME_STATE_ROLLING );
		
		$('#btnRollDice').removeAttr('disabled').show();
		$('#btnPass').removeAttr('disabled').show();
		$('#btnPostGame').hide();	

		FarkleGameDisplayDice();
		FarkleGameUpdateRoundScore();
		
	} else {
	
		FarkleGameWatchMode();
	}
}

function FarkleGameWatchMode() {

	ConsoleDebug( "Setting game to watch mode." ); 
	FarkleGameUpdateState( GAME_STATE_WATCHING );
	
	$('#btnRollDice').attr('disabled','');
	$('#btnPass').attr('disabled','');
	
	GameTimerTick( 1 );
}

function FarkleGameDisplayEnded() {
		
	ConsoleDebug( "Updating game page to show a finished game." );
	// Let the player choose a title if desired.
	if( g_myPlayerIndex > -1 &&	gGameData.winningplayer == gGamePlayerData[g_myPlayerIndex].playerid )
		ShowXPGain( 5 + (gGameData.maxturns-2) );

	FarkleDiceReset(); 
	
	var gameWinMsg;

	if( gGameData.gamewith == GAME_WITH_SOLO ) {
		gameWinMsg = "Game finished. Final score: " + gGamePlayerData[g_myPlayerIndex].playerscore;
	} else {
		if( gGameData.winningplayer == playerid ) {
			gameWinMsg = "You won!";
		} else {
			gameWinMsg = "You lost. Winning player: " + gGamePlayerData[getOpponentIndexById( gGameData.winningplayer )].username;
		}
	}
		
	$('#btnPostGame').show();
	$('#btnRollDice').hide();
		
	// Show the game ending XP gain for player
	var gameXP = 0;
	if( gGameData.winningplayer == playerid && gGameData.maxturns > 1 )	gameXP = 10 + (gGameData.maxturns-2); 
	else if( gGameData.winningplayer != playerid && gGameData.maxturns > 1 ) gameXP = 5 + (gGameData.maxturns-2); 
	else gameXP = 3; 

	ShowXPGain( gameXP );
	
	$('#lblGameExpires').html( 'Game ended: ' + gGameData.gamefinish );
	divTurnActionObj.innerHTML = gameWinMsg;
	gGameState == 3;
	clearTimeout( gGameTimer );
	FarkleGameDisplayDice();
	
	// Modify the layout to prevent weird things from happening and show play again button. 
	$('#btnQuitGame').hide();
	btnRollDiceObj.setAttribute('disabled','');
	btnPassObj.setAttribute('disabled','');		

	$('#btnPlayAgain').hide();
	$('#btnNewRandom').hide();
	if( gGameData.gamewith == GAME_WITH_RANDOM )
		$('#btnNewRandom').show();
	else
		$('#btnPlayAgain').show();
}


function FarkleGameUpdateRoundScore() {
	lblRoundInfoObj.innerHTML = gRoundScore + gTurnScore;
}

function FarkleGameUpdateState( newGameState ) {

	var strInfo = "";
	
	if( gLastGameState == newGameState ) {
		ConsoleDebug( "FarkleGameUpdateState: state unchanged. Bailing." ); 
		return 0; 
	}	
	gGameState = newGameState; 

	if( gGameState == GAME_STATE_LOADING ) {
		strInfo = "Loading..."; 
		
	} else if( gGameState == GAME_STATE_WATCHING ) {
		strInfo = "Waiting for players to finish...";
		
	} else if( gGameState == GAME_STATE_PASSED ) {
		strInfo = DisplayLastDiceScore();
	
	} else if( gGameState == GAME_STATE_ROLLED ) {
		strInfo = "Calculating...";
		 
	} else if( gGameState == GAME_STATE_ROLLING ) {
		strInfo = "Your roll --";
		if( gGameData.currentround == LAST_ROUND ) 
			strInfo += ' <span style="color: yellow;">Last round!</span>';
		else 
			strInfo += ' Round: <span style="color: #96D3F2;">' + gGamePlayerData[g_myPlayerIndex].playerround + '</span> of <span style="color: #96D3F2;">'+LAST_ROUND+'</span>';
	}
	
	ConsoleDebug( "Game state is now "+gGameState ); 
	divTurnActionObj.innerHTML = strInfo;
	return 1; 
}

function FarkleGameDisplayDice( )
{	
	ConsoleDebug( "FarkleGameDisplayDice: Dice on table = "+gDiceOnTable+"  and dice data="+gDiceData ); 
	
	if( !gDiceData || !gDiceOnTable ) {
		//this is just an empty roll. ConsoleError( "FarkleGameDisplayDice: Missing dice data." ); 
		return 0; 
	}

	if( gGameData.winningplayer == 0 && gGameData.currentset == 0 ) {
		ConsoleError( "FarkleGameDisplayDice: not in a state to display dice." ); 
		return 0;
	}
	
	var i;
	for( i=0; i<=MAX_DICE; i++ )
		farkleUpdateDice( i, gDiceOnTable[i], ((gDiceData[i] > 0) ? 0 : 1) );
}


function PopulatePlayerData( thePlayerData )
{
	gGamePlayerData = thePlayerData;
	if( !gGamePlayerData )
	{
		ConsoleError( "PopulatePlayerData: missing player data for game." ); 
		return 0; 
		
	} else {	
		var i;
		var scoreStr = "";
		var roundStr = "";
		var p; 
		
		ConsoleDebug( "PopulatePlayerData: populating. Number of players: "+thePlayerData.length ); 
		
		// Show the "Looking for players..." dialog if we started a random game and are awaiting another player
		if( gGamePlayerData.length < gGameData.maxturns && gGameData.gamewith == GAME_WITH_RANDOM )
			$('#divGameWaitingForPlayers').show();
		else
			$('#divGameWaitingForPlayers').hide();
		
		g_myPlayerIndex = -1;
		for( i=0; i<gGamePlayerData.length; i++ )
		{
			p = gGamePlayerData[i];
			
			// Find our player's index in the data and record our player's latest score
			if( p.playerid == playerid ) g_myPlayerIndex = i;				
		
			scoreStr = "--";
			if( gGameData.winningplayer == 0 && g_myPlayerIndex > -1 &&
				gGamePlayerData[g_myPlayerIndex].playerround < 11 && i != g_myPlayerIndex && p.playerround > 1 )
			{
				//newTag.find('.playerAchScore').html('????');
				scoreStr = addCommas(p.rollingscore);
			} else if( p.playerround > 1 ){
				scoreStr = p.playerscore; 
			}
			
			FarkleGamePlayerTag( p, scoreStr ); 
		}
		
		if( g_myPlayerIndex == -1 )	{
			// The user is not a participant in this game. Don't show them any game buttons. 
			$('#tabGameButtons').hide();
			$('#btnQuitGame').hide();			
		} else {
			$('#tabGameButtons').show();
			$('#btnQuitGame').show();
		}

		if( g_myPlayerIndex == -1 )
		{
			// This handles when you are viewing somebody else's game. 
			$('#btnQuitGame').hide();
		} else {
			if( gGamePlayerData[g_myPlayerIndex].playerround < 2 ) {
				$('#btnQuitGame').val('Refuse').attr('buttoncolor', 'orange');
				quitCountsAsLoss = 0;
			} else {
				$('#btnQuitGame').val('Forfeit').attr('buttoncolor', 'red');
				quitCountsAsLoss = 1;
			}
		}
	}
	return 1; 
}

function FarkleGamePlayerTag( p, scoreStr ) {
	//&radic;
	newTag = DisplayPlayerTag( 'divGamePlayers', p, scoreStr );
	newTag.css('margin', '0 0 7px 0')
		.find('.playerAchScore')
		.append( "<br/><span class='playerCardRound'>"+( p.playerround==11 ? 'Done' : (p.playerround==1?'-':"<span style='color:white'>Rnd</span> "+p.playerround) )+"</span>" );
}


function RollDice() {

	if( btnRollDiceObj.getAttribute('disabled') ) {
		//ConsoleError( "RollDice: Exiting because button was disabled and somehow got clicked" ); 
		return 0; // Exit if button disabled. 
	}
	
	var diceScore = farkleScoreDice( );
	
	// Check the score of the player's chosen dice. If it's nothing, this is an invalid save
	if( diceScore == 0 && dice[0].value > 0 && dice[0].value < 7 ) {
	
		ConsoleDebug( "RollDice: Invalid dice save. Reseting dice and bailing." ); 
		divTurnActionObj.innerHTML = '<span style="color: red; font-size: 14px;">Selected dice are not worth any points.</span>';
		for( i=0; i<=MAX_DICE; i++ )
			if( dice[i].saved == 1 ) SaveDice(i); // Unsave all saved dice
			
		return 0;
	}
	
	btnRollDiceObj.setAttribute('disabled','');
	btnPassObj.setAttribute('disabled','');
	
	var numSavedDice = 0;
	for( i=0; i<=MAX_DICE; i++ )
		if( dice[i].saved ) 
			numSavedDice++;
	
	var unrolledDice = JSON.stringify( GetDiceValArray() ); 
	
	var diceArr = Array(0, 0, 0, 0, 0, 0);	
	for( i=0; i<=MAX_DICE; i++ ) {
		// If saved or scored OR we've rolled all dice THEN go ahead and reset them. 
		if( (!dice[i].saved && !dice[i].scored) || numSavedDice == 6 ) {
			dice[i].saved = 0; 
			dice[i].scored = 0; 
			dice[i].ImageObj.removeAttribute('scored');
			dice[i].ImageObj.removeAttribute('saved');
			dice[i].ImageObj.setAttribute('rolling','');
			
			dice[i].value = parseInt( FarkleCalcRoll() );
			
			diceArr[i] = dice[i].value; 
		}
		DrawDice( dice[i] );
	}
	
	var rolledDice = JSON.stringify( diceArr ); 
	
	FarkleGameUpdateState( GAME_STATE_ROLLED ); 

	// Set the saved dice to scored. 
	for( i=0; i<=MAX_DICE; i++ )
		if( dice[i].saved )
			dice[i].scored = 1; 

	gGameAjaxStatus = 1;
	
	FarkleAjaxCall(FarkleGameRollHook, 
		'action=farkleroll&gameid='+gGameData.gameid+'&saveddice='+unrolledDice+'&newdice='+rolledDice );
		
	//DelayGameUpdate( 15000 );
}
	
function FarkleGameRollHook()
{
	gGameAjaxStatus = 0;
	var rollData = FarkleParseAjaxResponse( ajaxrequest.responseText );
	if( rollData ) {
		if( rollData[3] == 6 ) 
			FarkleDiceReset(); // All dice scored. Give new set of dice. 
		
		gRoundScore = parseInt(rollData[2]);
		gTurnScore = farkleScoreDice( 0 );
		ConsoleDebug( "After roll, roundscore="+gRoundScore+", turnScore="+gTurnScore ); 
		
		FarkleGameUpdateState( GAME_STATE_ROLLING );
		lblRoundInfoObj.innerHTML = gRoundScore + gTurnScore;
		btnRollDiceObj.removeAttribute('disabled');
		btnPassObj.removeAttribute('disabled');
		
		StopRollingAnimation();		
		gFarkleDice = rollData[4]; // These are the dice to display if you farkled. 	
		//FarkleGameDisplayDice();
		DisplayLastDiceScore();
		
		// Check remaining unscored dice for a farkle. 
		gTempRollData = rollData[0];
		if( rollData[1] <= 0 )
			FarkleGameFarkle();
		else
			GameUpdateEx( gTempRollData );
	}
}

function PassTurn( ) {

	var diceScore = farkleScoreDice( );
	
	// Check the score of the player's chosen dice. If it's nothing, this is an invalid save
	if( diceScore == 0 && dice[0].value > 0 && dice[0].value < 7 ) {
		ConsoleDebug( "PassTurn: Invalid dice save. Reseting dice and bailing." ); 
		divTurnActionObj.innerHTML = '<span style="color: red; font-size: 14px;">Invalid save. Must save scoring dice.</span>';
		for( i=0; i<=MAX_DICE; i++ )
			if( dice[i].saved == 1 ) SaveDice(i); // Unsave all saved dice
			
		return 0;
	}
	
	
	
	// On the player's last roll, show xp gain
	if( gGamePlayerData[g_myPlayerIndex].playerround == LAST_ROUND ) {
		if( gGameData.maxturns > 1 ) 
			ShowXPGain( 5 + (gGameData.maxturns-2) );
		else
			ShowXPGain( 3 ); 
	} else {	
		if( (diceScore+gRoundScore) >= 750 )
			ShowXPGain( 1 );
	}
	
	gTurnScore = 0;
	gGamePlayerData[g_myPlayerIndex].lastroundscore = gRoundScore + diceScore; 
	gGamePlayerData[g_myPlayerIndex].playerround = parseInt(gGamePlayerData[g_myPlayerIndex].playerround) + 1; 
	//DisplayLastDiceScore();
	
	var scoreStr = addCommas( parseInt(gGamePlayerData[g_myPlayerIndex].playerscore)+parseInt(gGamePlayerData[g_myPlayerIndex].lastroundscore));
	FarkleGamePlayerTag( gGamePlayerData[g_myPlayerIndex], scoreStr ); 
	
	FarkleGameUpdateState( GAME_STATE_PASSED );
	btnRollDiceObj.setAttribute('disabled','');
	btnPassObj.setAttribute('disabled','');
	
	// Display the player's last score. 	
	gGameAjaxStatus = 1;
	gAjaxStartTimestamp = new Date().getTime();
	
	FarkleAjaxCall(	function() { 
			gGameAjaxStatus = 0; 
			// We want to give 2.5 seconds to read the user's score no matter how long the ajax takes (but no longer). 
			var nowMillis = new Date().getTime(); 
			var millisDiff = nowMillis - gAjaxStartTimestamp;
			var newDiff = parseInt(SCORE_VIEW_DELAY_MS-millisDiff);
			
			ConsoleDebug( "PassTurn: Delaying update hook for "+newDiff+" milliseconds. "+millisDiff+"ms has passed before now." ); 
			if( parseInt(millisDiff) > SCORE_VIEW_DELAY_MS ) {
				FarkleGameRollReset(); 
			} else {
				setTimeout( function() { FarkleGameRollReset(); }, newDiff ); 
			}
		}, 
		'action=farklepass&gameid='+gGameData.gameid+'&saveddice='+JSON.stringify( GetDiceValArray() ) );
	//DelayGameUpdate( 15000 );
}

function FarkleGameRollReset() {
	FarkleDiceReset();
	FarkleGameUpdateState( GAME_STATE_ROLLING ); 
	FarkleGameUpdateHook();
}

function DisplayLastDiceScore()
{
	var retStr = "?";
	
	if( gGamePlayerData[g_myPlayerIndex].lastroundscore > 0 )
	{
		retStr = 'You scored ' + gGamePlayerData[g_myPlayerIndex].lastroundscore + '!';
	}
	else
	{
		if( gGamePlayerData[g_myPlayerIndex].playerscore == 0 )
			retStr = "You farkled!<br>";
			
		// Reset all the dice to blanks
		for( i=0; i<=MAX_DICE; i++ )
		{
			if( gFarkleDice )
				if( gFarkleDice[i] > 0 && gFarkleDice[i] < 7 )
					farkleUpdateDice( i, gFarkleDice[i], 0 );
		}
	}
	return retStr;
}

function FarkleGameFarkle() {

	gRoundScore = 0;
	gTurnScore = 0;
	ConsoleDebug('Player farkled.');

	// MAX: 4
	//var randomnumber=Math.floor(Math.random()*1) + 1;
	var randomnumber=1;
	$('#farkledImage').attr('src','/images/farkled' + randomnumber + '.png' );
	$('#farkledImage').show();
	setTimeout( function() {
		$('#farkledImage').attr('show','1');
		setTimeout( function() {
			$('#farkledImage').removeAttr('show');
			$('#farkledImage').attr('hide','1');
			setTimeout( function() {
				$('#farkledImage').hide();
				$('#farkledImage').removeAttr('hide');
				FarkleDiceReset();
				GameUpdateEx( gTempRollData );
			},200);
		},2000);
	}, 200);
}

function SaveDice( dn )
{
	farkleSaveDice( dn, 0 ); // Update the dice [saved] property (4th parameter)
	//DelayGameUpdate( 15000 );
}

function getOpponentIndexById( playerid )
{
	for( i=0; i<gGamePlayerData.length; i++ )
		if( gGamePlayerData[i].playerid == playerid )
			return i;

	return 0;
}

function FarklePublishGame( ) 
{
	if( !gUsingFacebook ) return 0; 
	
	var msg = "";
	var msgFound = 0;
	var imgFile = '';
		
	// A 2-player game
	if( gGameData.maxturns == 2 )
	{
		var oppindex = ( g_myPlayerIndex == 0 ? 1 : 0 );
		var oppusername = gGamePlayerData[oppindex].username;
		var oppscore = gGamePlayerData[oppindex].playerscore;
		
		var pindex = g_myPlayerIndex; 
		var pusername = gGamePlayerData[g_myPlayerIndex].username; 
		var pscore = gGamePlayerData[g_myPlayerIndex].playerscore;
		
		var scoreDiff = pscore - oppscore;
		
		if( gGameData.winningplayer == playerid )
		{
			// You won this game -- eligable for "winning" posts. 		
			if( scoreDiff > 3000 )
				msg = "Dominated "+oppusername+" in a game of Farkle by "+scoreDiff+" points!";
			else if( scoreDiff < 500 )
				msg = "Won a close game against "+oppusername+" with a score of "+pscore+" to "+oppscore+"!";
			else
				msg = "Beat "+oppusername+" in a game of Farkle";
				
			FacebookPublishGame( 'Farkle Ten', msg, '/images/apple-touch-icon.png' );
			return 1; 
		}
		else
		{
			// I lost to this player. 
			if( scoreDiff > 3000 )
				msg = oppusername+" dominated me in a game of Farkle by "+scoreDiff+" points!";
			else if( scoreDiff < 500 )
				msg = "Lost a close game to "+oppusername+" with a score of "+pscore+" to "+oppscore+"!";
			else
				msg = "Lost to "+oppusername+" in a game of Farkle";
				
			FacebookPublishGame( 'Farkle Ten', msg, '/images/apple-touch-icon.png' );
			return 1; 
		}
	}
	else if( gGameData.maxturns > 2 )
	{
		var names = new Array();
		var p;
		var i=0;
		for( p in gGamePlayerData )
			names[i++] = gGamePlayerData[p].username; 
		var nameListStr = names.toString(); // Make array comma separated
		nameListStr.replace(",",", "); // Add a space after each comma
		
		if( gGameData.winningplayer == playerid )
		{
			// Beat a lot of players
			msg = "Prevailed in a big game of Farkle against "+nameListStr;
			FacebookPublishGame( 'Farkle Ten', msg, '/images/apple-touch-icon.png' );
			return 1; 
		}
		else
		{
			// Lost to a lot of players
			msg = "Was knocked out of a big game of Farkle with "+nameListStr;
			FacebookPublishGame( 'Farkle Ten', msg, '/images/apple-touch-icon.png' );
			return 1; 
		}
	}
	else if( gGameData.maxturns == 1 )
	{
		var myScore = gGamePlayerData[0].playerscore;
		msg = 'Scored ' + myScore + ' in a solo game of Farkle';

		if( myScore > 8000 ) 
			msg = 'Dominated a solo game of Farkle with a score of ' + myScore + '!';

		FacebookPublishGame( 'Farkle Ten', msg, '/images/apple-touch-icon.png' );
	}
}

function GameGoIdle()
{
	$('#divGame').hide();
	$('#divGameIdle').show();
	clearTimeout( gGameTimer );
}

function GameBackFromIdle()
{
	$('#divGameIdle').hide();
	$('#divGame').show();	
	StartTimer();
}

function QuitGame()
{
	var doQuit = 0;
	if( quitCountsAsLoss == 0 ) {
		doQuit = 1;
	} else {
		if ( confirm("Quitting this game will count as a loss because you have already played. Are you sure you want to forfeit? ") ) {
			doQuit = 1;
		}
	}

	if( doQuit ) {
		ConsoleDebug('User has chosen to quit game ' + gGameData.gameid );
		FarkleAjaxCall(function() { 
			ShowLobby(); 
		}, 
		'action=quitgame&playerid='+playerid+'&gameid='+gGameData.gameid );
	}
}

function CheckForAchievement( achData )
{
	if( achData && gDisplayingAchievement == 0 )
	{	
		ConsoleDebug( "Showing an achievement - achievementid = " + achData.achievementid ); 
		gDisplayingAchievement = 1;
		// We have an achievement to display. 			

		$('#divAchBoxContainer').empty();
		$('#divShowAchievement').fadeIn();
		
		achData.earned = 1; 
		DisplayAchievement( 'divAchBoxContainer', achData, 0 );
		
		FarkleAjaxCall(	function() {}, 
			'action=ackachievement&playerid='+playerid+'&achievementid='+achData.achievementid );
		
		var t2 = setTimeout( function() { 		
			var t3 = setTimeout( function() { 
					gDisplayingAchievement = 0; 
					$('#divShowAchievement').fadeOut();
					}, 600 );
			}, 4000 );
	}
}

function CheckForLevel( levelData )
{
	if( levelData && gDisplayingAchievement == 0 )
	{
		gDisplayingAchievement = 1;
		$('#divShowLevel').find('#lblLevelUpReward').empty().html( levelData.rewardstring ); 
		$('#divShowLevel').fadeIn();
		
		// TBD: Update the lobby card
		FarkleAjaxCall(	function() {}, 'action=acklevel&playerid='+playerid );
		
		var t2 = setTimeout( function() { 
			var t3 = setTimeout( function() { 
				gDisplayingAchievement = 0; 
				$('#divShowLevel').fadeOut();
				}, 600 );
			}, 3000 );
	}
}

function ShowXPGain( amt ) 
{
	if( $('#m_doublexp').val() > 0 ) {
		amt *= 2; // Double it. 
	}

	$('#divXP').show();
	$('#divXP').attr('slideup','');
	$('#divXPamt').html('+'+amt);
	
	var t2 = setTimeout( function() { 
			document.getElementById('divXP').removeAttribute('slideup'); 
			gDisplayingAchievement = 0; 
			var t3 = setTimeout( function() { 
				$('#divXP').hide();
				}, 600 );
		}, 1500 );
	
}

function PlayAnotherRandom()
{
	// Default game info
	gameInfo = new Object();
	gameInfo.breakIn = gGameData.mintostart;
	gameInfo.playTo = gGameData.pointstowin;
	gameInfo.gameMode = gGameData.gamemode;
	gameInfo.gameWith = GAME_WITH_RANDOM;
	gameInfo.gamePlayers = 2; 

	var newGamePlayers = new Array( playerid ); 
	StartGameEx( newGamePlayers );
}

function PlayAgain( )
{
	// Default game info
	gameInfo = new Object();
	gameInfo.breakIn = gGameData.mintostart;
	gameInfo.playTo = gGameData.pointstowin;
	gameInfo.gameMode = gGameData.gamemode;
	gameInfo.gameWith = gGameData.gamewith;
	
	var newGamePlayers = new Array();
	for( i=0; i< gGamePlayerData.length; i++ )
	{
		// Add selected players but NOT the current player (it will be added later)
		if( gGamePlayerData[i].playerid != playerid )
			newGamePlayers = newGamePlayers.concat( parseInt(gGamePlayerData[i].playerid) );
	}
	
	StartGameEx( newGamePlayers );
}	

/*function SendReminder()
{
	if( !$('#divRemind').attr('disabled') )
	{
		if ( confirm("Send email to alert " + gGamePlayerData[gCurrentPlayerTurnIndex].username + " it's their turn?") )
		{
			_gaq.push( ['_trackEvent', 'Ajax', 'SentReminder', 'Playerid', playerid ]);
			FarkleAjaxCall(	function ReminderHook()	{
				if( ajaxrequest.responseText ) {
					var rc = farkleParseJSON( ajaxrequest.responseText );					
				}
			}, 
			'action=sendreminder&gameid=' + gGameData.gameid );
			
			$('#divRemind').attr('disabled','');
			$('#divRemind').hide();
		}
	}
}*/
