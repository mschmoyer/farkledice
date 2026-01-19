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
var gPassTurnTimeout = null; 

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
var GAME_WITH_BOT = 3;

var GAME_STATE_LOADING = 0;
var GAME_STATE_ROLLING = 1;
var GAME_STATE_ROLLED = 2;
var GAME_STATE_PASSED = 3;
var GAME_STATE_WATCHING = 4;

// Bot-related globals
var gBotTurnTimer = null;
var gBotIsPlaying = false;
var gBotPlayerIds = [];  // Array of bot player IDs in current game
var BOT_STEP_DELAY_MS = 800;  // Delay between bot steps for animation 

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

	// Reset bot state
	gBotIsPlaying = false;
	gBotPlayerIds = [];
	if( gBotTurnTimer ) {
		clearTimeout( gBotTurnTimer );
		gBotTurnTimer = null;
	}

	// Clear bot chat messages
	var divBotChatMessages = document.getElementById('divBotChatMessages');
	if( divBotChatMessages ) {
		divBotChatMessages.innerHTML = '';
	}
	var divBotChat = document.getElementById('divBotChat');
	if( divBotChat ) {
		divBotChat.style.display = 'none';
	}

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
		// Check if it's a bot's turn
		var currentTurnPlayer = null;
		for( var i = 0; i < gGamePlayerData.length; i++ ) {
			if( gGamePlayerData[i].playerturn == gGameData.currentturn ) {
				currentTurnPlayer = gGamePlayerData[i];
				break;
			}
		}

		// Show bot's name if it's their turn, otherwise show "Your roll"
		if( currentTurnPlayer && currentTurnPlayer.is_bot ) {
			strInfo = currentTurnPlayer.username + "'s roll --";
		} else {
			strInfo = "Your roll --";
		}

		if( gGameData.currentround == LAST_ROUND )
			strInfo += ' <span style="color: yellow;">Last round!</span>';
		else if( g_myPlayerIndex > -1 )
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
		gBotPlayerIds = [];  // Reset bot player list

		for( i=0; i<gGamePlayerData.length; i++ )
		{
			p = gGamePlayerData[i];

			// Find our player's index in the data and record our player's latest score
			if( p.playerid == playerid ) g_myPlayerIndex = i;

			// Track bot players
			if( p.is_bot ) {
				gBotPlayerIds.push( parseInt(p.playerid) );
				ConsoleDebug( "PopulatePlayerData: Found bot player: " + p.username + " (id=" + p.playerid + ")" );
			}				
		
			scoreStr = "--";
			ConsoleDebug( "PopulatePlayerData: Player " + p.username + " - playerround=" + p.playerround + ", playerscore=" + p.playerscore + ", rollingscore=" + p.rollingscore + ", lastroundscore=" + p.lastroundscore + ", winningplayer=" + gGameData.winningplayer );

			// For active games where opponent hasn't finished
			if( gGameData.winningplayer == 0 && g_myPlayerIndex > -1 &&
				gGamePlayerData[g_myPlayerIndex].playerround < 11 && i != g_myPlayerIndex && p.playerround > 1 )
			{
				scoreStr = addCommas(p.rollingscore);
			}
			// For completed games or players who have played at least one round
			else if( p.playerround > 1 || gGameData.winningplayer > 0 ){
				// Use playerscore if it's a valid number
				var finalScore = p.playerscore;

				// If playerscore is missing, null, or 0, calculate from rollingscore + lastroundscore
				// This handles cases where data might be incomplete
				if( !finalScore || finalScore === '' || finalScore === null || finalScore === undefined ) {
					finalScore = parseInt(p.rollingscore || 0) + parseInt(p.lastroundscore || 0);
					ConsoleDebug( "PopulatePlayerData: Using calculated score for " + p.username + ": " + finalScore );
				}

				scoreStr = addCommas(finalScore);
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

	// Check if it's a bot's turn and start bot play if needed
	Bot_CheckAndStartTurn();

	return 1;
}

// ============================================================================
// BOT TURN HANDLING
// ============================================================================

/**
 * Check if any bot needs to take their turn and start the process
 */
function Bot_CheckAndStartTurn() {
	console.log('Bot_CheckAndStartTurn: CALLED');
	console.log('Bot_CheckAndStartTurn: gGameData:', gGameData);
	console.log('Bot_CheckAndStartTurn: gGamePlayerData:', gGamePlayerData);

	// Only check if game is active and not in watch mode
	if( gGameData.winningplayer > 0 ) {
		console.log( "Bot_CheckAndStartTurn: Game already won, skipping" );
		ConsoleDebug( "Bot_CheckAndStartTurn: Game already won, skipping" );
		return;
	}

	// Don't interrupt if bot is already playing
	if( gBotIsPlaying ) {
		console.log( "Bot_CheckAndStartTurn: Bot already playing, skipping" );
		ConsoleDebug( "Bot_CheckAndStartTurn: Bot already playing, skipping" );
		return;
	}

	// Find which player's turn it is based on currentturn
	var currentTurnPlayer = null;
	for( var i = 0; i < gGamePlayerData.length; i++ ) {
		console.log('Bot_CheckAndStartTurn: Checking player ' + i + ':', gGamePlayerData[i]);
		if( gGamePlayerData[i].playerturn == gGameData.currentturn ) {
			currentTurnPlayer = gGamePlayerData[i];
			console.log('Bot_CheckAndStartTurn: Found current turn player:', currentTurnPlayer);
			break;
		}
	}

	if( !currentTurnPlayer ) {
		console.log( "Bot_CheckAndStartTurn: Could not determine current turn player" );
		ConsoleDebug( "Bot_CheckAndStartTurn: Could not determine current turn player" );
		return;
	}

	console.log('Bot_CheckAndStartTurn: currentTurnPlayer.is_bot =', currentTurnPlayer.is_bot);
	console.log('Bot_CheckAndStartTurn: currentTurnPlayer.playerround =', currentTurnPlayer.playerround);

	// Check if current player is a bot and hasn't finished their rounds
	if( currentTurnPlayer.is_bot && currentTurnPlayer.playerround <= LAST_ROUND ) {
		ConsoleDebug( "Bot_CheckAndStartTurn: It's bot " + currentTurnPlayer.username + "'s turn (round " + currentTurnPlayer.playerround + ")" );
		Bot_StartTurn( currentTurnPlayer );
	}
}

/**
 * Start a bot's turn
 */
function Bot_StartTurn( botPlayer ) {
	console.log( "Bot_StartTurn: Starting turn for " + botPlayer.username, botPlayer );
	ConsoleDebug( "Bot_StartTurn: Starting turn for " + botPlayer.username );

	gBotIsPlaying = true;
	console.log('Bot_StartTurn: Set gBotIsPlaying = true');

	// Clear dice from player's previous turn
	FarkleDiceReset();
	console.log('Bot_StartTurn: Cleared dice state');

	// Show bot thinking message
	var thinkingMsg = botPlayer.username + " " + (botPlayer.playertitle || '') + " is thinking...";
	divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">' + thinkingMsg + '</span>';
	console.log('Bot_StartTurn: Displayed thinking message:', thinkingMsg);

	// Start polling for bot turn state
	console.log('Bot_StartTurn: Scheduling poll in ' + BOT_STEP_DELAY_MS + 'ms');
	setTimeout( function() {
		Bot_PollAndExecuteStep( botPlayer.playerid );
	}, BOT_STEP_DELAY_MS );
}

/**
 * Poll bot status and execute next step
 */
function Bot_PollAndExecuteStep( botPlayerId ) {
	console.log( "Bot_PollAndExecuteStep: Executing step for bot " + botPlayerId );
	console.log( "Bot_PollAndExecuteStep: Game ID:", gGameData.gameid );
	ConsoleDebug( "Bot_PollAndExecuteStep: Executing step for bot " + botPlayerId );

	// Execute the next step (will auto-initialize state if needed)
	Bot_ExecuteNextStep( botPlayerId, null );
}

/**
 * Execute the next step in bot's turn
 */
function Bot_ExecuteNextStep( botPlayerId, currentStatus ) {
	console.log( "Bot_ExecuteNextStep: Executing step for bot " + botPlayerId );
	console.log( "Bot_ExecuteNextStep: Current status:", currentStatus );
	ConsoleDebug( "Bot_ExecuteNextStep: Executing step for bot " + botPlayerId );

	FarkleAjaxCall(
		function() {
			console.log('Bot_ExecuteNextStep: Received response:', ajaxrequest.responseText);
			var stepResult = FarkleParseAjaxResponse( ajaxrequest.responseText );
			console.log('Bot_ExecuteNextStep: Parsed step result:', stepResult);

			if( stepResult && !stepResult.Error ) {
				console.log( "Bot_ExecuteNextStep: Step executed, result=" + stepResult.step );
				ConsoleDebug( "Bot_ExecuteNextStep: Step executed, result=" + stepResult.step );

				// Process the step result
				Bot_ProcessStepResult( botPlayerId, stepResult );
			} else {
				console.error( "Bot_ExecuteNextStep: Failed to execute step: " + (stepResult ? stepResult.Error : "Unknown error") );
				ConsoleError( "Bot_ExecuteNextStep: Failed to execute step: " + (stepResult ? stepResult.Error : "Unknown error") );
				gBotIsPlaying = false;
				// Refresh game state
				farkleGetUpdate();
			}
		},
		'action=executebotstep&gameid=' + gGameData.gameid + '&botplayerid=' + botPlayerId
	);
}

/**
 * Process the result of a bot step and continue turn or end
 */
function Bot_ProcessStepResult( botPlayerId, stepResult ) {
	var step = stepResult.step;
	var message = stepResult.message || '';

	ConsoleDebug( "Bot_ProcessStepResult: Processing step '" + step + "'" );

	// Display bot message if present
	if( message ) {
		Bot_DisplayMessage( message );
	}

	// Handle different step types
	switch( step ) {
		case 'rolled':
			// Bot rolled dice - show the dice
			if( stepResult.dice && stepResult.dice.length > 0 ) {
				Bot_AnimateDiceRoll( stepResult.dice );
			}
			// Continue to next step after animation delay
			setTimeout( function() {
				Bot_PollAndExecuteStep( botPlayerId );
			}, BOT_STEP_DELAY_MS );
			break;

		case 'chose_keepers':
			// Bot chose which dice to keep
			var msg = "Keeping " + (stepResult.kept ? stepResult.kept.description : 'dice') +
			          " for " + (stepResult.kept ? stepResult.kept.points : 0) + " points";
			divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">' + msg + '</span>';

			// Mark the kept dice as saved (purple) visually
			if( stepResult.kept && stepResult.kept.dice ) {
				var keptDice = stepResult.kept.dice.slice(); // Copy the array

				// Match kept dice values to dice positions and mark them as saved
				for( var i = 0; i <= MAX_DICE && keptDice.length > 0; i++ ) {
					// Find a die with a matching value that hasn't been saved yet
					var dieValue = dice[i].value;
					var keptIndex = keptDice.indexOf( dieValue );

					if( keptIndex !== -1 && !dice[i].saved && !dice[i].scored ) {
						// Mark this die as saved (purple)
						dice[i].ImageObj.removeAttribute('rolled');
						dice[i].ImageObj.setAttribute('saved', '');
						dice[i].saved = 1;

						// Remove this value from keptDice so we don't match it again
						keptDice.splice( keptIndex, 1 );

						console.log('Bot_ProcessStepResult: Marked dice[' + i + '] (value=' + dieValue + ') as saved');
					}
				}
			}

			// Continue to next step
			setTimeout( function() {
				Bot_PollAndExecuteStep( botPlayerId );
			}, BOT_STEP_DELAY_MS );
			break;

		case 'roll_again':
			// Bot decided to roll again
			divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">Rolling again...</span>';

			// Continue to next step
			setTimeout( function() {
				Bot_PollAndExecuteStep( botPlayerId );
			}, BOT_STEP_DELAY_MS );
			break;

		case 'banking':
			// Bot decided to bank their score
			divTurnActionObj.innerHTML = '<span style="color: #96D3F2;">Banking score...</span>';

			// Continue to next step (will execute Bot_Step_Banking and return 'completed')
			setTimeout( function() {
				Bot_PollAndExecuteStep( botPlayerId );
			}, BOT_STEP_DELAY_MS );
			break;

		case 'completed':
			// Bot completed their turn - check if they banked or farkled
			if( stepResult.banked ) {
				// Bot banked their score
				var bankMsg = "Banked " + (stepResult.final_score || 0) + " points!";
				divTurnActionObj.innerHTML = '<span style="color: #7CFC00;">' + bankMsg + '</span>';
			} else if( stepResult.farkled ) {
				// Bot farkled
				divTurnActionObj.innerHTML = '<span style="color: red;">Farkled!</span>';
			}

			// End bot turn and refresh game after a delay
			setTimeout( function() {
				gBotIsPlaying = false;
				// Clear dice state before refreshing
				FarkleDiceReset();
				farkleGetUpdate();
			}, SCORE_VIEW_DELAY_MS );
			break;

		default:
			ConsoleError( "Bot_ProcessStepResult: Unknown step type: " + step );
			gBotIsPlaying = false;
			farkleGetUpdate();
			break;
	}
}

/**
 * Animate bot's dice roll
 */
function Bot_AnimateDiceRoll( diceArray ) {
	ConsoleDebug( "Bot_AnimateDiceRoll: Showing dice: " + diceArray.join(',') );

	// Show dice on the table - use 0 for scored so dice appear white/normal when rolled
	for( var i = 0; i < diceArray.length && i <= MAX_DICE; i++ ) {
		if( diceArray[i] ) {
			farkleUpdateDice( i, diceArray[i], 0 );  // 0 = not scored (white dice)
		}
	}
	// Clear remaining dice
	for( var i = diceArray.length; i <= MAX_DICE; i++ ) {
		farkleUpdateDice( i, 0, 0 );
	}
}

/**
 * Display bot message in the UI
 */
function Bot_DisplayMessage( message ) {
	ConsoleDebug( "Bot_DisplayMessage: " + message );

	// Show bot chat window if not already visible
	var divBotChat = document.getElementById('divBotChat');
	if( divBotChat ) {
		divBotChat.style.display = 'block';

		// Add message to chat history
		var divMessages = document.getElementById('divBotChatMessages');
		if( divMessages ) {
			var messageHtml = '<div style="margin-bottom: 6px;">' +
			                  '<span style="color: #96D3F2;">' + message + '</span>' +
			                  '</div>';
			divMessages.innerHTML += messageHtml;

			// Auto-scroll to bottom
			divMessages.scrollTop = divMessages.scrollHeight;
		}
	}
}

function FarkleGamePlayerTag( p, scoreStr ) {
	//&radic;
	newTag = DisplayPlayerTag( 'divGamePlayers', p, scoreStr );
	newTag.css('margin', '0 0 7px 0');
		//.find('.playerAchScore')
		//.append( "<br/><span class='playerCardRound'>"+( p.playerround==11 ? 'Done' : (p.playerround==1?'-':"<span style='color:white'>Rnd</span> "+p.playerround) )+"</span>" );
}


function RollDice() {

	if( btnRollDiceObj.getAttribute('disabled') ) {
		//ConsoleError( "RollDice: Exiting because button was disabled and somehow got clicked" );
		return 0; // Exit if button disabled.
	}

	// If we're in PASSED state, player is skipping the score display delay
	if( gGameState == GAME_STATE_PASSED ) {
		ConsoleDebug( "RollDice: Player skipping score display, canceling timeout and proceeding to next roll." );
		if( gPassTurnTimeout ) {
			clearTimeout( gPassTurnTimeout );
			gPassTurnTimeout = null;
		}
		FarkleGameRollReset();
		return 0;
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

			// Enable roll button immediately so players can skip the delay if desired
			btnRollDiceObj.removeAttribute('disabled');

			ConsoleDebug( "PassTurn: Delaying update hook for "+newDiff+" milliseconds. "+millisDiff+"ms has passed before now." );
			// Still delay the automatic reset to allow score viewing
			if( parseInt(millisDiff) > SCORE_VIEW_DELAY_MS ) {
				FarkleGameRollReset();
			} else {
				gPassTurnTimeout = setTimeout( function() { FarkleGameRollReset(); }, newDiff );
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
	// Facebook publishing removed
	return 0;
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
			//if(typeof _gaq !== 'undefined') _gaq.push( ['_trackEvent', 'Ajax', 'SentReminder', 'Playerid', playerid ]);
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
