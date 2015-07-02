<!-- LEADER BOARD -->
<div id="divLeaderBoard" class="pagelayout" align="center" style="display: none;">

	{*<table width="320px"><tr><td>
		<input type="button" class="mobileButton" buttoncolor="orange" 
			value="<<" onClick="ShowLeaderboardData(1)" style="width: 55px;">
	</td><td align="center">
		<b id="lbTitle">Most Wins</b>
	</td><td align="right">
		<input type="button" class="mobileButton" buttoncolor="orange" 
			value=">>" onClick="ShowLeaderboardData(-1)" style="width: 55px;">
	</td></tr></table>*}
	
	<div align="center">
			
		<table class="pageWidth" style="font-size: 12px;"><tr>
			<td class="farkleTab" id="tabLBToday"  selected="" width="25%"
				onClick="ShowLBTab( 'Today' );">Daily</td>
			<td class="farkleTab" id="tabLBWins" width="25%"
				onClick="ShowLBTab( 'Wins' );">Wins</td>
			<td class="farkleTab" id="tabLB10Round"  width="25%"
				onClick="ShowLBTab( '10Round' );">10 Round</td>			
			<td class="farkleTab" id="tabLBAchieves"  width="25%"
				onClick="ShowLBTab( 'Achieves' );">Achievements</td>
		</tr></table>
		
		<div class="lbTab regularBox" id="tblLBToday" style="margin: 0px; background-color: transparent;">
		
			<p><i>Showing stats for <span id="lblLbTodayDate"></span></i></p>
			
			<div class="regularBox" style="background-color: rgba(241,218,54,0.6);">
				<img src="/images/star.png"> <span style="margin: -5px;"><b>Daily MVP</b></span> <img src="/images/star.png">
				<h2 style="margin: 0px;"><span  class="shadowed" id="lbMVPName"></span></h2>
			</div> 
						
			{*<!-- <div id="farkleMeter" style="width: 200px; height: 160px;"></div> -->*}
			
			<h3 style="margin: 2px;">Highest Game Scores</h3>		
			
			<table class="pageWidth tabLeaderboard" id="tblLbTodayHighScores" cellpadding="3px" cellspacing="0px" style="width: 98%">
				<thead><tr header="">
					<th>Rank</th>
					<th>Player</th>
					<th>Score</th>
				</tr></thead>
				<tbody></tbody>
			</table>
			
			<h3 style="margin: 15px 0 8px; 0">Most Wins</h3>			
			<table class="pageWidth tabLeaderboard" id="tblLbTodayWins" cellpadding="3px" cellspacing="0px" style="width: 98%">
				<thead><tr header="">
					<th>Rank</th>
					<th>Player</th>
					<th>Wins</th>
				</tr></thead>
				<tbody></tbody>
			</table>
			
			<h3 style="margin: 15px 0 8px; 0">Most Farkles</h3>			
			<table class="pageWidth tabLeaderboard" id="tblLbTodayFarkles" cellpadding="3px" cellspacing="0px" style="width: 98%">
				<thead><tr header="">
					<th>Rank</th>
					<th>Player</th>
					<th>Farkles</th>
				</tr></thead>
				<tbody></tbody>
			</table>
			<br/>
		</div>
		
		<table class="lbTab pageWidth tabLeaderboard" id="tblLBWins" cellpadding="3px" cellspacing="0px" style="display: none;">
			<thead><tr header="">
				<th>Rank</th>
				<th>Player</th>
				<th>Wins</th>
				<th>W/L</th>
			</tr></thead>
			<tbody></tbody>
		</table>
		
		<table class="lbTab pageWidth tabLeaderboard" id="tblLB10Round" cellpadding="3px" cellspacing="0px" style="display: none;">
			<thead><tr header="">
				<th>Rank</th>
				<th>Player</th>
				<th>Score</th>
			</tr></thead>
			<tbody></tbody>
		</table>
		
		{*<!--<table class="lbTab pageWidth tabLeaderboard" id="tblLBavground" cellpadding="3px" cellspacing="0px" style="display: none;">
			<thead><tr header="">
				<th>Rank</th>
				<th>Player</th>
				<th>Score</th>
			</tr></thead>
			<tbody></tbody>
		</table> -->*}
		
		<table class="lbTab pageWidth tabLeaderboard" id="tblLBAchieves" cellpadding="3px" cellspacing="0px" style="display: none;">
			<thead><tr header="">
				<th>Rank</th>
				<th>Player</th>
				<th>Points</th>
				{*<th>Prestige</th>*}
			</tr></thead>
			<tbody></tbody>
		</table>
		
		<div id="divLBItem0"></div>
		
		<div id="divLBItem1"></div>
		
		<div id="divLBItem2"></div>
		
		<div id="divLBItem3"></div>
		
	</div>
	
	<input type="button" class="mobileButton" buttoncolor="red" value="Back" onClick="PageGoBack()" style="width: 110px;">
	
</div>