

var gLeaderboardLoaded = 0;
var gLBLastUpdateData = '';
//var gLeaderboardItem = 0;
//var LEADERBOARD_ITEMS_MAX = 4;
var farkleMeter = 0;

var g_lbData;
var g_currentDayView = 'today';  // 'today' or 'yesterday' 

function ShowLeaderBoard()
{
	HideAllWindows();
	divLeaderBoardObj.show();
	
	/*if( !farkleMeter ) {
		farkleMeter = new JustGage({
			id: "farkleMeter", 
			value: 67, 
			min: 0,
			max: 100,
			title: "Farkle-meter",
			startAnimationType: "bounce",
			titleFontColor: "white"
		}); 
	}*/
	
	if( g_lbData )
		PopulateLeaderboard();
		
	GetLeaderBoardData(); 
}
		
function GetLeaderBoardData()
{
	var params = 'action=getleaderboard';
	AjaxCallPost( gAjaxUrl, LeaderBoardHook, params );
}

function GetLevelHTML( level )
{
	return ( '<span class="playerCardLevel">'+level+'</span>&nbsp;' );
}

function LeaderBoardHook()
{
	// Wins/Losses/GamesPlayed
	if( ajaxrequest.responseText )
	{
		var data = farkleParseJSON( ajaxrequest.responseText );
		
		if( data.Message == 'No new data' )
		{
			ConsoleDebug( 'LeaderBoardHook: server reported no new data.');
			return 0; 
		}

		/*if( gLBLastUpdateData == ajaxrequest.responseText )
		{
			ConsoleDebug( 'LeaderBoardHook: Leaderboard data unchanged. Not reloading.');
			return 0; 
		}*/
		
		gLBLastUpdateData = ajaxrequest.responseText;
	
		g_lbData = data; 
		
		PopulateLeaderboard();
	}
}

// Switch between Today and Yesterday views
function switchDayView(day) {
	g_currentDayView = day;

	// Update button states
	$('#btnToday').toggleClass('active', day === 'today');
	$('#btnYesterday').toggleClass('active', day === 'yesterday');

	// Repopulate tables with selected day's data
	PopulateDailyTables();
}

// Populate the Yesterday's MVP section (always shows yesterday's data)
function PopulateMVP() {
	var mvpData = g_lbData.yesterday ? g_lbData.yesterday[2] : null;

	if (mvpData && mvpData.length > 0) {
		var mvp = mvpData[0];
		$('#lbMVPName').html(GetLevelHTML(mvp.playerlevel) + mvp.username);

		var ratio = (mvp.first_int / mvp.second_int).toFixed(2);
		$('#lbMVPStats').html(
			'Win Ratio: ' + ratio + ' | ' +
			'Games: ' + mvp.second_int + ' | ' +
			'Opponents Beaten: ' + mvp.first_int
		);
	} else {
		$('#lbMVPName').html('No MVP yet');
		$('#lbMVPStats').html('Check back tomorrow!');
	}
}

// Populate the daily stats tables based on the current day view (today/yesterday)
function PopulateDailyTables() {
	var dataSource = (g_currentDayView === 'today') ? g_lbData[0] : g_lbData.yesterday;
	var maxRows = 3;
	var j, lbData, lbPlayerid;

	// Update date label
	if (g_currentDayView === 'today') {
		$('#lblLbDateLabel').text('Showing stats for ');
		$('#lblLbTodayDate').text(g_lbData.dayOfWeek);
	} else {
		$('#lblLbDateLabel').text('Showing stats for ');
		$('#lblLbTodayDate').text('Yesterday');
	}

	// Check if we have data
	if (!dataSource) {
		// Clear all tables and show empty state
		$("#tblLbTodayHighScores > tbody").empty();
		$("#tblLbTodayFarkles > tbody").empty();
		$("#tblLbTodayWins > tbody").empty();
		$("#tblLbTodayBestRounds > tbody").empty();
		return;
	}

	// High Scores (index 0)
	lbData = dataSource[0];
	ConsoleDebug('Populating ' + g_currentDayView + ' high scores');
	$("#tblLbTodayHighScores > tbody").empty();
	if (lbData) {
		for (j = 0; j < Math.min(lbData.length, maxRows); j++) {
			lbPlayerid = lbData[j].playerid;
			$('#tblLbTodayHighScores > tbody:last').append(
				'<tr ' + (lbPlayerid == playerid ? 'row3' : (j % 2 == 0 ? 'row1' : 'row2')) + ' playerid="' + lbPlayerid + '">' +
					'<td>' + lbData[j].lbrank + '</td>' +
					'<td>' + GetLevelHTML(lbData[j].playerlevel) + lbData[j].username + '</td>' +
					'<td>' + lbData[j].first_int + '</td>' +
				'</tr>');
		}
	}

	// Farkles (index 1)
	lbData = dataSource[1];
	ConsoleDebug('Populating ' + g_currentDayView + ' farkles');
	$("#tblLbTodayFarkles > tbody").empty();
	if (lbData) {
		for (j = 0; j < Math.min(lbData.length, maxRows); j++) {
			lbPlayerid = lbData[j].playerid;
			$('#tblLbTodayFarkles > tbody:last').append(
				'<tr ' + (lbPlayerid == playerid ? 'row3' : (j % 2 == 0 ? 'row1' : 'row2')) + ' playerid="' + lbPlayerid + '">' +
					'<td>' + lbData[j].lbrank + '</td>' +
					'<td>' + GetLevelHTML(lbData[j].playerlevel) + lbData[j].username + '</td>' +
					'<td>' + lbData[j].first_int + '</td>' +
				'</tr>');
		}
	}

	// Win Ratio (index 2)
	lbData = dataSource[2];
	ConsoleDebug('Populating ' + g_currentDayView + ' best win ratio');
	$("#tblLbTodayWins > tbody").empty();
	if (lbData) {
		for (j = 0; j < Math.min(lbData.length, maxRows); j++) {
			lbPlayerid = lbData[j].playerid;
			$('#tblLbTodayWins > tbody:last').append(
				'<tr ' + (lbPlayerid == playerid ? 'row3' : (j % 2 == 0 ? 'row1' : 'row2')) + ' playerid="' + lbPlayerid + '">' +
					'<td>' + lbData[j].lbrank + '</td>' +
					'<td>' + GetLevelHTML(lbData[j].playerlevel) + lbData[j].username + '</td>' +
					'<td>' + lbData[j].first_string + '</td>' +
					'<td>' + lbData[j].second_int + '</td>' +
				'</tr>');
		}
	}

	// Best Rounds (index 3)
	lbData = dataSource[3];
	ConsoleDebug('Populating ' + g_currentDayView + ' best rounds');
	$("#tblLbTodayBestRounds > tbody").empty();
	if (lbData) {
		for (j = 0; j < Math.min(lbData.length, maxRows); j++) {
			lbPlayerid = lbData[j].playerid;
			$('#tblLbTodayBestRounds > tbody:last').append(
				'<tr ' + (lbPlayerid == playerid ? 'row3' : (j % 2 == 0 ? 'row1' : 'row2')) + ' playerid="' + lbPlayerid + '">' +
					'<td>' + lbData[j].lbrank + '</td>' +
					'<td>' + GetLevelHTML(lbData[j].playerlevel) + lbData[j].username + '</td>' +
					'<td>' + lbData[j].first_int + '</td>' +
				'</tr>');
		}
	}

	// Rebind click handlers for player info
	$(".tabLeaderboard tr").off('click').on('click', function() {
		ShowPlayerInfo($(this).attr("playerid"));
	});
}

function PopulateLeaderboard()
{
	var i, j, k, idx, lbData, lbRow, lbStars, lbPlayerid;
	var upperBound;
	var newTag;
	var maxRows = 25;

	// Populate Yesterday's MVP (always uses yesterday's data)
	PopulateMVP();

	// Populate Daily tables based on current view toggle
	PopulateDailyTables();

	idx = 1;
	lbData = g_lbData[idx];
	ConsoleDebug( 'Populating stats for most wins' );
	$("#tblLBWins > tbody").empty();
	for( j=0; j < Math.min(lbData.length,maxRows); j++ )
	{
		lbPlayerid = lbData[j].playerid;
		lbRow = $('#tblLBWins > tbody:last').append(
			'<tr '+(lbPlayerid==playerid?'row3':(j%2==0?'row1':'row2'))+' playerid="' + lbPlayerid + '">'+
				'<td>'+lbData[j].lbrank+'</td>'+
				'<td>'+GetLevelHTML( lbData[j].playerlevel )+lbData[j].username+'</td>'+
				'<td>'+lbData[j].first_int+'</td>'+
				'<td>'+lbData[j].first_string+'</td>'+
			'</tr>');
	}
	
	idx++;
	lbData = g_lbData[idx];
	ConsoleDebug( 'Populating stats for 10 Round scores' );
	$("#tblLB10Round > tbody").empty();
	for( j=0; j < Math.min(lbData.length,maxRows); j++ )
	{
		lbPlayerid = lbData[j].playerid;
		lbRow = $('#tblLB10Round > tbody:last').append(
			'<tr '+(lbPlayerid==playerid?'row3':(j%2==0?'row1':'row2'))+' playerid="' + lbPlayerid + '">'+
				'<td>'+lbData[j].lbrank+'</td>'+
				'<td>'+GetLevelHTML( lbData[j].playerlevel )+lbData[j].username+'</td>'+
				'<td>'+addCommas(lbData[j].first_int)+'</td>'+
			'</tr>');
	}
	
	/*idx++;
	lbData = g_lbData[idx];
	ConsoleDebug( 'Populating stats for avg scores' );
	$("#tblLBavground > tbody").empty();
	for( j=0; j < Math.min(lbData.length,maxRows); j++ )
	{
		lbPlayerid = lbData[j].playerid;
		lbRow = $('#tblLBavground > tbody:last').append(
			'<tr '+(lbPlayerid==playerid?'row3':(j%2==0?'row1':'row2'))+' playerid="' + lbPlayerid + '">'+
				'<td>'+(j+1)+'</td>'+
				'<td>'+GetLevelHTML( lbData[j].playerlevel )+lbData[j].username+'</td>'+
				'<td align="center">'+lbData[j].avground+'</td>'+
			'</tr>');
	}*/
	
	idx++;
	lbData = g_lbData[idx];
	ConsoleDebug( 'Populating stats for achievement points' );
	$("#tblLBAchieves > tbody").empty();
	for( j=0; j < Math.min(lbData.length,maxRows); j++ )
	{
		//lbStars = '';
		lbPlayerid = lbData[j].playerid;
		
		//for( k=0; k < parseInt(lbData[j].prestige); k++ )
		//	lbStars += '<img src="/images/star.png">';

		lbRow = $('#tblLBAchieves > tbody:last').append(
			'<tr '+(lbPlayerid==playerid?'row3':(j%2==0?'row1':'row2'))+' playerid="' + lbPlayerid + '">'+
				'<td>'+lbData[j].lbrank+'</td>'+
				'<td>'+GetLevelHTML( lbData[j].playerlevel )+lbData[j].username+'</td>'+
				'<td>'+lbData[j].first_int+'</td>'+
			'</tr>');
		// +'<td align="center">'+lbStars+'</td>'+
	}

	// The click handler for each row. 
	$(".tabLeaderboard tr").click(function() {
	   //alert($(this).attr("playerid"));
	   ShowPlayerInfo( $(this).attr("playerid") );
	});
	
	//farkleMeter.refresh( 67 ); 

	gLeaderboardLoaded = 1;
}

function ShowLBTab( lbName )
{
	$('.lbTab').hide();
	$('#tblLB'+lbName).show();
	
	$('.farkleTab').removeAttr('selected');
	$('#tabLB'+lbName).attr('selected','');
}

/*
function ShowLeaderboardData( nextItem )
{
	$('#divLBItem' + gLeaderboardItem).removeClass('showLeft');
	$('#divLBItem' + gLeaderboardItem).hide();
	
	gLeaderboardItem += nextItem;
	if( gLeaderboardItem < 0 ) gLeaderboardItem = LEADERBOARD_ITEMS_MAX-1;
	if( gLeaderboardItem > LEADERBOARD_ITEMS_MAX-1 ) gLeaderboardItem = 0;
	
	var d = new Date();
	if( gLeaderboardItem == 0 ) $('#lbTitle').html( d.getMonthName() + ' wins' );
	if( gLeaderboardItem == 1 ) $('#lbTitle').html( d.getMonthName() + ' round score' );
	if( gLeaderboardItem == 2 ) $('#lbTitle').html( 'Achievement Points' );
	if( gLeaderboardItem == 3 ) $('#lbTitle').html( '10-Round Score' );
	
	$('#divLBItem' + gLeaderboardItem).addClass('showLeft');
	$('#divLBItem' + gLeaderboardItem).show();
}
*/