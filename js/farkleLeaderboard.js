

var gLeaderboardLoaded = 0;
var gLBLastUpdateData = '';
//var gLeaderboardItem = 0;
//var LEADERBOARD_ITEMS_MAX = 4;
var farkleMeter = 0; 

var g_lbData; 

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

function PopulateLeaderboard()
{
	var i, j, k, idx, lbData, lbRow, lbStars, lbPlayerid;
	var upperBound;
	var newTag;
	var maxRows = 25;
	
	idx = 0;
	
	$('#lblLbTodayDate').html( g_lbData.dayOfWeek ); 
	
	// Today's high scores
	lbData = g_lbData[idx][0];
	ConsoleDebug( 'Populating todays high scores' );
	$("#tblLbTodayHighScores > tbody").empty();
	for( j=0; j < Math.min(lbData.length,maxRows); j++ )
	{
		lbPlayerid = lbData[j].playerid;
		lbRow = $('#tblLbTodayHighScores > tbody:last').append(
			'<tr '+(lbPlayerid==playerid?'row3':(j%2==0?'row1':'row2'))+' playerid="' + lbPlayerid + '">'+
				'<td>'+lbData[j].lbrank+'</td>'+
				'<td>'+GetLevelHTML( lbData[j].playerlevel )+lbData[j].username+'</td>'+
				'<td align="center">'+lbData[j].first_int+'</td>'+
			'</tr>');
	}
	
	// Today's farkles
	lbData = g_lbData[idx][1];
	ConsoleDebug( 'Populating todays farkles' );
	$("#tblLbTodayFarkles > tbody").empty();
	for( j=0; j < Math.min(lbData.length,maxRows); j++ )
	{
		lbPlayerid = lbData[j].playerid;
		lbRow = $('#tblLbTodayFarkles > tbody:last').append(
			'<tr '+(lbPlayerid==playerid?'row3':(j%2==0?'row1':'row2'))+' playerid="' + lbPlayerid + '">'+
				'<td>'+lbData[j].lbrank+'</td>'+
				'<td>'+GetLevelHTML( lbData[j].playerlevel )+lbData[j].username+'</td>'+
				'<td align="center">'+lbData[j].first_int+'</td>'+
			'</tr>');
	}
	
	// Today's Most Wins
	lbData = g_lbData[idx][2];
	ConsoleDebug( 'Populating todays most wins' );
	$("#tblLbTodayWins > tbody").empty();
	for( j=0; j < Math.min(lbData.length,maxRows); j++ )
	{
		if( j==0 )
		{
			$('#lbMVPName').html( GetLevelHTML( lbData[j].playerlevel )+lbData[j].username );
		}
	
		lbPlayerid = lbData[j].playerid;
		lbRow = $('#tblLbTodayWins > tbody:last').append(
			'<tr '+(lbPlayerid==playerid?'row3':(j%2==0?'row1':'row2'))+' playerid="' + lbPlayerid + '">'+
				'<td>'+lbData[j].lbrank+'</td>'+
				'<td>'+GetLevelHTML( lbData[j].playerlevel )+lbData[j].username+'</td>'+
				'<td align="center">'+lbData[j].first_int+'</td>'+
			'</tr>');
	}
	
	idx++;
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
				'<td align="center">'+lbData[j].first_int+'</td>'+
				'<td align="center">'+lbData[j].first_string+'</td>'+
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
				'<td align="center">'+lbData[j].first_string+'</td>'+
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
				'<td align="center">'+lbData[j].first_int+'</td>'+
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