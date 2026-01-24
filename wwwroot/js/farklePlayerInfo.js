
var PLAYERINFO_MAX_ITEM = 4;
var gLastPlayerId = 0; 

var g_playerInfo; 
var g_lastAchPlayerid;

var gCurPlayerid; // The player you are viewing Player Info for. 
var gCurUsername; 

function ShowCurPlayerInfo() { 

	// If we're missing gCurPlayerid here then send them back to the lobby. 
	if( !gCurPlayerid ) {
		ConsoleDebug( "ShowCurPlayerInfo: Error - missing playerid." ); 
		ShowLobby();
		return 0; 
	}
	ShowPlayerInfo( gCurPlayerid, 0 ); 
}

function ShowMyPlayerInfo() { 
	ShowPlayerInfo( playerid, 1 ); 
}

function ShowPlayerInfo( thePlayerId, reload )
{
	if( !thePlayerId ) {
		ConsoleDebug( "ShowPlayerInfo: Error - missing playerid." ); 
		return 0; 
	}

	HideAllWindows();	
	GetPlayerInfoData( thePlayerId );
	divPlayerInfoObj.show();
}
	
function GetPlayerInfoData( thePlayerId )
{
	if( !thePlayerId ) {
		ConsoleDebug( "GetPlayerInfoData: Error - missing playerid." ); 
		return 0; 
	}

	gLastPlayerId = gCurPlayerid; 
	gCurPlayerid = thePlayerId;
	
	gAchievementsLoaded = 0;
	gFriendsLoaded = 0;
	
	if( gCurPlayerid == playerid ) 
	{
		$('#btnPlayNow').hide();
	}
	else
	{
		$('#btnPlayNow').show();
	}
	
	HidePlayerInfoData( 3 );
	HidePlayerInfoData( 1 );
	HidePlayerInfoData( 2 );
	
	gPlayerInfoItem = 4;
	ShowPlayerInfoEx( 0 );

	if( g_playerInfo )
	{
		PopulatePlayerInfo();
	}
	var params = 'action=getplayerinfo&playerid='+gCurPlayerid;
	AjaxCallPost( gAjaxUrl, PlayerInfoHook, params );
}

function PlayerInfoHook()
{
	var i; 
	var picStr; 
	var attribs; 
	
	ConsoleDebug( 'PlayerInfoHook - loading player info data...' );
	
	// Wins/Losses/GamesPlayed
	if( ajaxrequest.responseText )
	{
		g_playerInfo = farkleParseJSON( ajaxrequest.responseText ); 
		PopulatePlayerInfo();
	}
}

function PopulatePlayerInfo()
{
	// Successful register. 
	var playerInfo = g_playerInfo;	
	var completedGames = playerInfo[1];
	var i; 
	
	gCurUsername = playerInfo[0].username;
	
	if( gLastPlayerId != gCurPlayerid )
	{
		$('#divPlayerInfoFriends').html('');
		/*table = document.getElementById('tabAchievements');
		while (table.hasChildNodes())
			table.removeChild(table.firstChild);*/
		$('#divCompletedGamesStats').empty();
	}
	$('#divPlayerInfoTag').empty();
	
	//$('#lblPlayerNamePlayer').html(playerInfo[0].username);
	//$('#lblPlayerTitlePlayer').html(playerTitle);
	
	var newTag = DisplayPlayerTag( 'divPlayerInfoTag', playerInfo[0], '' );
	newTag.off( 'click' );
	
	var winLossRatio = ((parseInt(playerInfo[0].wins) / (parseInt(playerInfo[0].wins) + parseInt(playerInfo[0].losses)) ) * 100).toFixed(0)+'%';
	if( parseInt(playerInfo[0].wins)==0 && parseInt(playerInfo[0].losses) == 0 ) winLossRatio = '<i style="color: grey;">No data</i>';
	$('#lblLevel').html(playerInfo[0].playerlevel);
	$('#lblTitle').html(playerInfo[0].playertitle);
	$('#lblLastPlayed').html(playerInfo[0].lastplayed); 
	$('#lblXp').html(playerInfo[0].xp);
	$('#lblXpToLevel').html(playerInfo[0].xp_to_level);
	$('#lblStyle').html(playerInfo[0].stylepoints);
	
	$('#lblWins').html(playerInfo[0].wins);
	$('#lblLosses').html(playerInfo[0].losses);		
	$('#lblWinLossRatio').html( winLossRatio );
	
	$('#lblTotalPoints').html(playerInfo[0].totalpoints);
	$('#lblHighestRound').html(playerInfo[0].highestround);
	$('#lblAvgRound').html(playerInfo[0].avground);	
	$('#lblHighest10Round').html(playerInfo[0].highest10round);	
	
	$('#lblFarkles').html(playerInfo[0].farkles);	
	
	$('#txtUserEmail').val(playerInfo[0].email);
	$('#displayname').val(playerInfo[0].fullname || '');

	$('#chkEmailMe').attr('checked', (playerInfo[0].sendhourlyemails=='1' ? true : false ) );
	$('#chkRandomSelectable').attr('checked', (playerInfo[0].random_selectable=='1' ? true : false ) );
	
	// Show the titles the player can select. 
	
	if( gCurPlayerid != playerid ) {
	$('#selPlayerTitleSelector').hide();
		$('#lblPlayerTitle').html( playerInfo[0].playertitle );
		
	} else if( playerInfo[2].length == 1 ) {
		$('#selPlayerTitleSelector').hide();
		$('#lblPlayerTitle').html( '<i style="color: grey;">No titles earned</i>' );
		
	} else  {
		$('#lblPlayerTitle').html();
		var mySelect = $('#selPlayerTitleSelector').show();
		var item;
		mySelect.children().remove().end();
		for( var key in playerInfo[2] )
		{
			item = mySelect.append( $('<option></option>').val( playerInfo[2][key].level ).html( playerInfo[2][key].title ) );
			if( playerInfo[2][key].title == playerInfo[0].playertitle )
			{
				$('#selPlayerTitleSelector option:last-child').attr('selected','selected');
			}
		}
	}
	
	// Show the player card backgrounds the player has unlocked
	
	for( i=1; i<9; i++ )
	{
		if( playerInfo[0].playerlevel >= (i*10) ) {
			// We skip from lvl 70 to lvl 100
			if( i < 8 || (i == 8 && playerInfo[0].playerlevel >= 100) )
				$('#pInfoPrestige'+i).attr('src', '/images/playericons/prestige'+i+'.png');
			else 
				$('#pInfoPrestige'+i).attr('src', '/images/playericons/locked.png');
		} else {
			$('#pInfoPrestige'+i).attr('src', '/images/playericons/locked.png');
		}
	}

	/*if( playerInfo[0].achscore >= PRESTIGE_POINT_LIMIT && playerInfo[0].playerid == playerid )
	{
		$('#showPrestigeButton').show();
		$('#showPrestigeInfo').hide();
	}
	else
	{
		$('#showPrestigeButton').hide();
		$('#showPrestigeInfo').show();
	}*/
	
	$('#btnAddFriend').hide();
	$('#btnRemoveFriend').hide();
	if( playerInfo[0].playerid != playerid )
	{
		if( playerInfo[0].isfriend == 0 ) $('#btnAddFriend').show();
		if( playerInfo[0].isfriend == 1 ) $('#btnRemoveFriend').show();
		$('#btnAddFriend').attr('onClick','AddFriend('+playerInfo[0].playerid+",'playerid')");
		$('#btnRemoveFriend').attr('onClick','RemoveFriend('+playerInfo[0].playerid+",'playerid')");
	}

	if( completedGames.length == 0 ) {
		$('#divCompletedGamesStats').html(' - No Games - ');
	} else {
		for( i=0; i< completedGames.length; i++ )
			DisplayGame( "divCompletedGamesStats", completedGames[i], 1, gCurPlayerid, gCurUsername );
	}
}

function doPrestige()
{
	if ( confirm("This will reset ALL achievements and statistics. Are you sure you would like to continue?") )
	{
		var params = 'action=prestige&playerid='+playerid;
		AjaxCallPost( gAjaxUrl, PrestigeHook, params );
	}
}

function PrestigeHook()
{
	if( ajaxrequest.responseText )
	{
		var data = farkleParseJSON( ajaxrequest.responseText );
		
		if( data['prestige'] )
		{
			var prestige = data['prestige'];
			farkleAlert('Your prestige has increased by one to ' + prestige + '!');
			
			window.location.reload();
		}
	}
}

function HidePlayerInfoData( item )
{
	$('#divPlayerInfoItem' + item).removeClass('showLeft');
	$('#divPlayerInfoItem' + item).hide();
}

function ShowPlayerInfoData( nextItem )
{
	HidePlayerInfoData( gPlayerInfoItem );
	gPlayerInfoItem += nextItem;
	ShowPlayerInfoEx( gPlayerInfoItem );
}
function ShowPlayerInfoEx( item )
{
	if( item == 3 && gCurPlayerid != playerid ) return 0; // Don't show options for other players. 
	
	HidePlayerInfoData( gPlayerInfoItem );
	gPlayerInfoItem = item; 	
	
	$('.farkleTab').removeAttr('selected');
	$('#tabPInfo'+gPlayerInfoItem).attr('selected','');
	
	if( gPlayerInfoItem < 0 ) gPlayerInfoItem = PLAYERINFO_MAX_ITEM;
	if( gPlayerInfoItem > PLAYERINFO_MAX_ITEM ) gPlayerInfoItem = 0; // boundaries
	
	if( gPlayerInfoItem == 0 ) $('#piTitle').html( 'Statistics' );
	if( gPlayerInfoItem == 1 ) 
	{
		$('#piTitle').html( 'Achievements' );
		ShowAchievements( gCurPlayerid );	
	}
	if( gPlayerInfoItem == 2 ) $('#piTitle').html( 'Games Played' );
	
	if( gPlayerInfoItem == PLAYERINFO_MAX_ITEM )
	{
		$('#piTitle').html( 'Friends' );
		ShowPlayerInfoFriends( gCurPlayerid );	
	}
	
	$('#divPlayerInfoItem' + gPlayerInfoItem).addClass('showLeft');
	$('#divPlayerInfoItem' + gPlayerInfoItem).show();
}

function ShowAchievements( thePlayerid )
{
	if( gAchievementsLoaded > 1 ) return 0; 
	
	$('#divPlayerInfoAchs').html( 'Loading achievements...'); 
	
	gAchievementsLoaded++;
	AjaxCallPost( gAjaxUrl, 	
		function ShowAchievementsHook()
		{
			if( ajaxrequest.responseText )
			{
				// Load all this player's achievements. 
				var data = farkleParseJSON( ajaxrequest.responseText );		
				if( data )
				{
					LoadAchievements( data[0] );
				}
			}
		}, 
		'action=getachievements&playerid='+thePlayerid );
}

function LoadAchievements( data ) {
	if( !data ) ConsoleDebug( 'Game Error - Missing parameters in LoadAchievements()' ); 
	var i;
	
	if( g_lastAchPlayerid != gCurPlayerid ) {
		//New player so we need to clear the achievement div
		$('#divPlayerInfoAchs').empty(); 
	}
	
	for( i=0; i<data.length; i++ )
		DisplayAchievement( "divPlayerInfoAchs", data[i], 1 );
}

function PlayerInfoOptionsDirty()
{
	// Enable the save options button. 
	$('#btnSaveOptions').removeAttr('disabled');
}

function SaveOptions()
{
	var email = $('#txtUserEmail').val();
	if( !validateEmail(email) )
	{
		alert( 'Invalid email address.');
		return 0;
	}
	// Email address
	var params = '&email=' + email;

	// Display name
	var displayname = document.getElementById('displayname').value;
	params += '&displayname=' + encodeURIComponent(displayname);

	// Send Hourly Emails Checked
	params += '&sendhourlyemails=' + ( $('#chkEmailMe').is(":checked") ? '1' : '0' );

	// Send Hourly Emails Checked
	params += '&random_selectable=' + ( $('#chkRandomSelectable').is(":checked") ? '1' : '0' );
	
	$('#btnSaveOptions').val( 'Saving...').attr('disabled','disabled');
	
	AjaxCallPost( gAjaxUrl, 	
		function ()
		{
			alert('Options saved.'); 
			$('#btnSaveOptions').val( 'Save Options');
		}, 
		'action=saveoptions'+params );
}

function UpdateTitle()
{
	var titleid = $("#selPlayerTitleSelector").val();
	var titleText = $("#selPlayerTitleSelector option:selected").text();
	
	var params = "&titleid=" + titleid; 
	
	AjaxCallPost( gAjaxUrl, 	
		function () {			
			g_playerInfo[0].playertitle = titleText; 
			ConsoleDebug( 'Player title updated to: '+g_playerInfo[0].playertitle ); 
			
			DisplayPlayerTag( 'divPlayerInfoTag', g_playerInfo[0], '' );
		}, 
		'action=updatetitle'+params );
}