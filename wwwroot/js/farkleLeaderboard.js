

var gLeaderboardLoaded = 0;
var gLBLastUpdateData = '';
//var gLeaderboardItem = 0;
//var LEADERBOARD_ITEMS_MAX = 4;
var farkleMeter = 0;

var g_lbData;
var g_currentDayView = 'today';  // 'today' or 'yesterday'

// ============================================================
// Leaderboard 2.0 Global State
// ============================================================
var g_lb2 = {
	currentTier: 'daily',
	currentScope: 'friends',
	data: {},       // cached board data keyed by "tier_scope"
	progress: null, // daily progress data
	fetchedAt: {}   // cache timestamps
};

function ShowLeaderBoard()
{
	HideAllWindows();
	divLeaderBoardObj.show();

	// Initialize Leaderboard 2.0
	InitLeaderboard2();
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

// ============================================================
// Leaderboard 2.0
// ============================================================

/**
 * Initialize Leaderboard 2.0 — called when the leaderboard page is shown.
 */
function InitLeaderboard2() {
	GetDailyProgress();
	GetLeaderBoard2Data(g_lb2.currentTier, g_lb2.currentScope);
}

/**
 * Fetch leaderboard data for a given tier and scope via AJAX.
 * Caches the result and triggers a render on success.
 */
function GetLeaderBoard2Data(tier, scope) {
	var params = 'action=getleaderboard2&tier=' + encodeURIComponent(tier) + '&scope=' + encodeURIComponent(scope);
	AjaxCallPost2(gAjaxUrl, function() {
		if (ajaxrequest2.responseText) {
			var data = farkleParseJSON(ajaxrequest2.responseText);
			if (data && !data.Error) {
				var cacheKey = tier + '_' + scope;
				g_lb2.data[cacheKey] = data;
				g_lb2.fetchedAt[cacheKey] = Date.now();
				RenderLeaderboard2();
			} else {
				ConsoleDebug('GetLeaderBoard2Data: Error or empty response');
			}
		}
	}, params);
}

/**
 * Fetch the current player's daily progress (games played, score).
 */
function GetDailyProgress() {
	var params = 'action=getdailyprogress';
	AjaxCallPost(gAjaxUrl, function() {
		if (ajaxrequest.responseText) {
			var data = farkleParseJSON(ajaxrequest.responseText);
			if (data && !data.Error) {
				g_lb2.progress = data;
				UpdateDailyCounter();
			} else {
				ConsoleDebug('GetDailyProgress: Error or empty response');
			}
		}
	}, params);
}

/**
 * Update the daily game counter UI in both the leaderboard header
 * and the lobby sidebar.
 */
function UpdateDailyCounter() {
	if (!g_lb2.progress) return;

	var played = g_lb2.progress.games_played || 0;
	var max = g_lb2.progress.games_max || 20;
	var score = g_lb2.progress.daily_score || 0;
	var pct = max > 0 ? Math.min((played / max) * 100, 100) : 0;

	// Leaderboard header counter
	var counterText = document.getElementById('lb2CounterText');
	if (counterText) counterText.textContent = played + ' / ' + max;

	var counterBar = document.getElementById('lb2CounterBar');
	if (counterBar) counterBar.style.width = pct + '%';

	var counterScore = document.getElementById('lb2CounterScore');
	if (counterScore) counterScore.textContent = formatNumber(score);

	// Lobby counter (if elements exist)
	var lobbyCounter = document.getElementById('lobbyCounterValue');
	if (lobbyCounter) lobbyCounter.textContent = played + '/' + max;

	var lobbyScore = document.getElementById('lobbyCounterScoreValue');
	if (lobbyScore) lobbyScore.textContent = formatNumber(score);

	var lobbyDiv = document.getElementById('lobby-daily-counter');
	if (lobbyDiv) lobbyDiv.style.display = '';
}

/**
 * Switch the active leaderboard tier (daily, weekly, alltime).
 * Updates tab UI, shows/hides tier-specific elements, and fetches data if stale.
 */
function switchLeaderboardTier(tier) {
	g_lb2.currentTier = tier;

	// Update tab selected states
	$('#tabLB2Daily').removeAttr('selected');
	$('#tabLB2Weekly').removeAttr('selected');
	$('#tabLB2Alltime').removeAttr('selected');
	$('#tabLB2' + tier.charAt(0).toUpperCase() + tier.slice(1)).attr('selected', '');

	// Show/hide tier-specific elements
	var weeklyBadges = document.getElementById('lb2WeeklyBadges');
	if (weeklyBadges) weeklyBadges.style.display = (tier === 'weekly') ? '' : 'none';

	var alltimeDesc = document.getElementById('lb2AlltimeDesc');
	if (alltimeDesc) alltimeDesc.style.display = (tier === 'alltime') ? '' : 'none';

	var statBanner = document.getElementById('lb2StatBanner');
	if (statBanner) statBanner.style.display = (tier === 'daily' || tier === 'weekly') ? '' : 'none';

	// Update column header labels
	var scoreCol = document.getElementById('lb2ScoreCol');
	if (scoreCol) {
		if (tier === 'daily') scoreCol.textContent = 'Daily Score';
		else if (tier === 'weekly') scoreCol.textContent = 'Weekly Score';
		else scoreCol.textContent = 'Avg Game';
	}

	// Check cache age (60s), fetch if stale, else render from cache
	var cacheKey = tier + '_' + g_lb2.currentScope;
	var now = Date.now();
	if (g_lb2.fetchedAt[cacheKey] && (now - g_lb2.fetchedAt[cacheKey]) < 60000 && g_lb2.data[cacheKey]) {
		RenderLeaderboard2();
	} else {
		GetLeaderBoard2Data(tier, g_lb2.currentScope);
	}
}

/**
 * Switch the leaderboard scope (friends or everyone).
 * Updates toggle button active states and fetches/renders data.
 */
function switchLeaderboardScope(scope) {
	g_lb2.currentScope = scope;

	// Update toggle button active states
	$('#btnLB2Friends').toggleClass('active', scope === 'friends');
	$('#btnLB2Everyone').toggleClass('active', scope === 'everyone');

	// Check cache age (60s), fetch if stale, else render from cache
	var cacheKey = g_lb2.currentTier + '_' + scope;
	var now = Date.now();
	if (g_lb2.fetchedAt[cacheKey] && (now - g_lb2.fetchedAt[cacheKey]) < 60000 && g_lb2.data[cacheKey]) {
		RenderLeaderboard2();
	} else {
		GetLeaderBoard2Data(g_lb2.currentTier, scope);
	}
}

/**
 * Main render dispatcher for Leaderboard 2.0.
 * Gets data from cache for the current tier+scope and calls sub-renderers.
 */
function RenderLeaderboard2() {
	var cacheKey = g_lb2.currentTier + '_' + g_lb2.currentScope;
	var data = g_lb2.data[cacheKey];
	if (!data) {
		ConsoleDebug('RenderLeaderboard2: No data for ' + cacheKey);
		return;
	}

	var board = data.entries || [];

	// Render the main board table
	if (board.length > 0) {
		RenderBoard(board);
	}

	// Render the score summary bar
	if (data.myScore) {
		RenderScoreBar(data.myScore, board);
	}

	// Render stat banner and update stat column header
	if (data.featuredStat) {
		RenderStatBanner(data.featuredStat);
		var statCol = document.getElementById('lb2StatCol');
		if (statCol) {
			// Extract short name from "Hot Dice -- Highest Single Round" → "Hot Dice"
			var shortName = data.featuredStat.title ? data.featuredStat.title.split(' -- ')[0] : 'Stat';
			statCol.textContent = (g_lb2.currentTier === 'alltime') ? '' : shortName;
		}
	}

	// Render weekly day badges (weekly tier only)
	if (g_lb2.currentTier === 'weekly' && data.dayScores) {
		RenderWeeklyBadges(data.dayScores);
	}

	// Render head-to-head card (friends scope, 2-3 players)
	if (g_lb2.currentScope === 'friends' && board.length >= 2 && board.length <= 3) {
		RenderH2HCard(board);
	} else {
		var h2hCard = document.getElementById('lb2H2HCard');
		if (h2hCard) h2hCard.style.display = 'none';
	}

	// Show post-game toast if provided
	if (data.toast) {
		ShowLB2Toast(data.toast);
	}
}

/**
 * Render the leaderboard table body with player rows.
 */
function RenderBoard(board) {
	var tbody = document.getElementById('lb2TableBody');
	if (!tbody) return;
	tbody.innerHTML = '';

	for (var i = 0; i < board.length; i++) {
		var entry = board[i];
		var rank = i + 1;
		var tr = document.createElement('tr');
		tr.className = 'lb2-row' + (entry.isMe ? ' lb2-row-me' : '');
		tr.setAttribute('playerid', entry.playerId || '');

		// Rank cell
		var rankClass = rank <= 3 ? ' lb2-rank-' + rank : '';

		// Arrow cell
		var arrowHtml = getArrowHtml(rank, entry.prevRank);

		// Player cell with name, games info, and label
		var label = getPlayfulLabel(entry, board, rank);
		var gamesInfo = '';
		if (g_lb2.currentTier === 'daily') {
			gamesInfo = '<div class="lb2-player-games">' + (entry.gamesPlayed || 0) + '/20 games</div>';
		} else if (g_lb2.currentTier === 'weekly') {
			gamesInfo = '<div class="lb2-player-games">' + (entry.daysPlayed || '') + ' days played</div>';
		} else {
			gamesInfo = '<div class="lb2-player-games">' + (entry.totalGames || '') + ' games · best: ' + formatNumber(entry.bestGameScore || 0) + '</div>';
		}

		// Stat cell (featured stat value if available)
		var statHtml = '';
		if (entry.statValue !== null && entry.statValue !== undefined) {
			statHtml = '<span class="lb2-stat-value">' + entry.statValue + '</span>';
		}

		// Score cell
		var scoreHtml = '';
		if (g_lb2.currentTier === 'alltime') {
			scoreHtml = '<span class="lb2-rating-badge">' + formatNumber(entry.avgGameScore || entry.score || 0) + '</span>';
		} else {
			scoreHtml = formatNumber(entry.score || 0);
		}

		tr.innerHTML =
			'<td class="lb2-rank' + rankClass + '">' + rank + '</td>' +
			'<td class="lb2-col-arrow">' + arrowHtml + '</td>' +
			'<td><div class="lb2-player-col"><div><div class="lb2-player-name">' + escapeHtml(entry.username || '') + '</div>' + gamesInfo + '</div>' + label + '</div></td>' +
			'<td class="lb2-col-stat">' + statHtml + '</td>' +
			'<td class="lb2-col-score">' + scoreHtml + '</td>';

		tbody.appendChild(tr);
	}

	// Bind click handlers for player info
	$('#tblLB2Main tbody tr').off('click').on('click', function() {
		var pid = $(this).attr('playerid');
		if (pid) ShowPlayerInfo(pid);
	});
}

/**
 * Return HTML for the rank movement arrow indicator.
 */
function getArrowHtml(rank, prevRank) {
	if (prevRank === null || prevRank === undefined) return '<span class="lb2-arrow-new">NEW</span>';
	if (rank < prevRank) return '<span class="lb2-arrow-up">&#9650;</span>';
	if (rank > prevRank) return '<span class="lb2-arrow-down">&#9660;</span>';
	return '<span class="lb2-arrow-same">&#9644;</span>';
}

/**
 * Return a playful label span for a board entry relative to the current player.
 */
function getPlayfulLabel(entry, board, rank) {
	if (entry.isMe) return ''; // no label for yourself

	// Find my entry
	var myEntry = null;
	for (var i = 0; i < board.length; i++) {
		if (board[i].isMe) { myEntry = board[i]; break; }
	}
	if (!myEntry) return '';

	var entryScore = entry.score || entry.avgGameScore || 0;
	var myScore = myEntry.score || myEntry.avgGameScore || 0;
	var diff = Math.abs(entryScore - myScore);

	if (entry.gamesPlayed >= 20 && g_lb2.currentTier === 'daily')
		return '<span class="lb2-label lb2-label-lead">All done</span>';
	if (diff < 500)
		return '<span class="lb2-label lb2-label-close">Right behind you</span>';
	if (entry.prevRank && rank < entry.prevRank - 1)
		return '<span class="lb2-label lb2-label-catching">Catching up...</span>';
	if (rank === 1)
		return '<span class="lb2-label lb2-label-lead">Pace setter</span>';
	if (entryScore > myScore && diff > 1500)
		return '<span class="lb2-label lb2-label-lead">Comfortable lead</span>';

	return '';
}

/**
 * Render the weekly day badges showing each day's score and state.
 */
function RenderWeeklyBadges(dayScores) {
	var container = document.getElementById('lb2DayBadgeRow');
	if (!container) return;
	container.innerHTML = '';
	var days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

	for (var i = 0; i < 7; i++) {
		var ds = dayScores && dayScores[i] ? dayScores[i] : {score: 0, state: 'future'};
		var badge = document.createElement('div');
		badge.className = 'lb2-day-badge lb2-day-badge-' + ds.state;
		badge.innerHTML = '<span class="lb2-day-label">' + days[i] + '</span>' +
			'<span class="lb2-day-score">' + (ds.score > 0 ? formatScore(ds.score) : '\u2014') + '</span>';
		container.appendChild(badge);
	}
}

/**
 * Render the head-to-head card for small friend groups (2-3 players).
 */
function RenderH2HCard(board) {
	var card = document.getElementById('lb2H2HCard');
	if (!card) return;
	if (board.length < 2 || board.length > 3) {
		card.style.display = 'none';
		return;
	}

	var p1 = board[0];
	var p2 = board[1];
	var p1Score = p1.score || p1.avgGameScore || 0;
	var p2Score = p2.score || p2.avgGameScore || 0;
	card.style.display = '';
	card.innerHTML = '<div class="lb2-h2h-players">' +
		'<div class="lb2-h2h-player">' +
			'<div class="lb2-h2h-name">' + escapeHtml(p1.username || '') + '</div>' +
			'<div class="lb2-h2h-score lb2-h2h-score-winning">' + formatNumber(p1Score) + '</div>' +
		'</div>' +
		'<div class="lb2-h2h-vs">vs</div>' +
		'<div class="lb2-h2h-player">' +
			'<div class="lb2-h2h-name">' + escapeHtml(p2.username || '') + '</div>' +
			'<div class="lb2-h2h-score lb2-h2h-score-losing">' + formatNumber(p2Score) + '</div>' +
		'</div>' +
		'</div>';
}

/**
 * Render the bottom summary bar showing rank and gap to leader.
 */
function RenderScoreBar(myScore, board) {
	if (!myScore) return;

	var myRank = myScore.rank || 0;
	var rankEl = document.getElementById('lb2RankValue');
	if (rankEl) rankEl.textContent = '#' + myRank + ' of ' + board.length;

	var myScoreVal = myScore.score || myScore.avgGameScore || 0;
	var topScoreVal = board.length > 0 ? (board[0].score || board[0].avgGameScore || 0) : 0;
	var gap = (board.length > 0 && myRank > 1) ? Math.round(myScoreVal - topScoreVal) : 0;
	var gapEl = document.getElementById('lb2GapValue');
	if (gapEl) {
		gapEl.textContent = gap >= 0 ? '+' + formatNumber(gap) : formatNumber(gap);
		gapEl.style.color = gap >= 0 ? '#8BC34A' : '#f44336';
	}
}

/**
 * Render the rotating stat banner (daily/weekly tiers).
 */
function RenderStatBanner(stat) {
	var banner = document.getElementById('lb2StatBanner');
	if (!banner) return;

	if (!stat || !stat.title) {
		banner.style.display = 'none';
		return;
	}

	banner.style.display = '';
	var labelEl = document.getElementById('lb2StatLabel');
	if (labelEl) labelEl.textContent = stat.label || 'Featured Stat';

	var titleEl = document.getElementById('lb2StatTitle');
	if (titleEl) titleEl.textContent = stat.title || '';

	var leaderEl = document.getElementById('lb2StatLeader');
	if (leaderEl) leaderEl.textContent = stat.leader || '';
}

/**
 * Show a temporary post-game feedback toast.
 */
function ShowLB2Toast(message) {
	var toast = document.getElementById('lb2Toast');
	var toastText = document.getElementById('lb2ToastText');
	if (!toast || !toastText) return;

	toastText.textContent = message;
	toast.style.display = '';

	setTimeout(function() {
		toast.style.display = 'none';
	}, 4000);
}

// ============================================================
// Leaderboard 2.0 Helper Functions
// ============================================================

/**
 * Format a number with comma separators (e.g. 12345 -> "12,345").
 */
function formatNumber(n) {
	return n ? n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0';
}

/**
 * Format a score in compact form (e.g. 1500 -> "2K", 800 -> "800").
 */
function formatScore(n) {
	if (n >= 1000) return Math.round(n / 1000) + 'K';
	return n.toString();
}

/**
 * Escape a string for safe HTML insertion.
 */
function escapeHtml(str) {
	var div = document.createElement('div');
	div.textContent = str;
	return div.innerHTML;
}