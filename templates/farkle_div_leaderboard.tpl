<!-- LEADER BOARD -->
<div id="divLeaderBoard" class="pagelayout" align="center" style="display: none;">

	<!-- Daily Game Counter (always visible) -->
	<div id="daily-game-counter" class="lb2-daily-counter">
		<span class="counter-label">Daily Games:</span>
		<span class="counter-value" id="lb2CounterText">0 / 20</span>
		<div class="counter-bar">
			<div class="counter-bar-fill" id="lb2CounterBar" style="width: 0%"></div>
		</div>
		<span class="counter-label">Score: <strong id="lb2CounterScore" style="color:#8BC34A">0</strong></span>
	</div>

	<!-- Board Tabs: Daily | Weekly | All-Time -->
	<table class="pageWidth" style="font-size: 12px;"><tr>
		<td class="farkleTab" id="tabLB2Daily" selected="" width="33%"
			onClick="switchLeaderboardTier('daily')">Daily</td>
		<td class="farkleTab" id="tabLB2Weekly" width="33%"
			onClick="switchLeaderboardTier('weekly')">Weekly</td>
		<td class="farkleTab" id="tabLB2Alltime" width="33%"
			onClick="switchLeaderboardTier('alltime')">All-Time</td>
	</tr></table>

	<div class="regularBox" style="margin: 0px; background-color: transparent;">

		<!-- Friends / Everyone Toggle -->
		<div style="text-align: center; padding: 8px 0;">
			<div class="segmented-toggle" id="lb2ScopeToggle">
				<button class="segment-btn active" id="btnLB2Friends" onclick="switchLeaderboardScope('friends')">Friends</button>
				<button class="segment-btn" id="btnLB2Everyone" onclick="switchLeaderboardScope('everyone')">Everyone</button>
			</div>
		</div>

		<!-- Rotating Stat Banner (daily/weekly only) -->
		<div id="lb2StatBanner" class="lb2-stat-banner" style="display:none;">
			<div class="lb2-stat-label" id="lb2StatLabel">Today's Featured Stat</div>
			<div class="lb2-stat-title" id="lb2StatTitle"></div>
			<div class="lb2-stat-leader" id="lb2StatLeader"></div>
		</div>

		<!-- Post-game feedback toast -->
		<div id="lb2Toast" class="lb2-toast" style="display:none;">
			<span id="lb2ToastText"></span>
		</div>

		<!-- Weekly day badges (weekly tab only) -->
		<div id="lb2WeeklyBadges" class="lb2-weekly-badges" style="display:none;">
			<div class="lb2-section-label">Your Week (Best 5 of 7 count)</div>
			<div id="lb2DayBadgeRow" class="lb2-day-badge-row"></div>
		</div>

		<!-- All-time description -->
		<div id="lb2AlltimeDesc" class="lb2-section-label" style="display:none;">
			Career Rating — Avg Daily Score (min 10 days)
		</div>

		<!-- Main leaderboard table -->
		<table class="pageWidth tabLeaderboard lb2-table" id="tblLB2Main" cellpadding="3px" cellspacing="0px" style="width: 98%">
			<thead><tr header="" id="lb2TableHeader">
				<th style="text-align: left; width: 30px;"></th>
				<th style="text-align: left; width: 24px;"></th>
				<th style="text-align: left;">Player</th>
				<th style="text-align: center;" id="lb2StatCol">Stat</th>
				<th style="text-align: right;" id="lb2ScoreCol">Score</th>
			</tr></thead>
			<tbody id="lb2TableBody"></tbody>
		</table>

		<!-- Head-to-head card (2-3 friends) -->
		<div id="lb2H2HCard" class="lb2-h2h-card" style="display:none;"></div>

		<!-- Your score summary bar -->
		<div id="lb2ScoreBar" class="lb2-score-bar">
			<div class="lb2-score-bar-left">
				<span style="color:#aaa;" id="lb2RankLabel">Your Rank</span>
				<strong id="lb2RankValue" style="color:#FFD700;"></strong>
			</div>
			<div class="lb2-score-bar-right">
				<span style="color:#aaa;" id="lb2GapLabel">Gap to #1</span>
				<strong id="lb2GapValue"></strong>
			</div>
		</div>

	</div>

	<input type="button" class="mobileButton" buttoncolor="red" value="Back" onClick="PageGoBack()" style="width: 110px;">

</div>
