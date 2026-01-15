{*<!--
	farkle_div_tournament.tpl	
	Desc: 
	
	13-Jan-2013		mas		Updates to support Farkle tournaments again. 
-->*}

{* The tournament page for one specific tournament *}
<div id="divTournament" class="pagelayout" align="center" style="display: none;">

	<!-- Tournament Header --> 
	<table cellspacing="4">
	<tr><td colspan="2">
		<b><span id="tname"></span><br/></b>		
	</td></tr>
	<tr><td>
		<img id="imgTournamentAch" src="/images/achieves/tournament4.png" height="42px" width="42px" >
	</td><td>
		<span style="font-size: 14px;"><span id="lblTStartsLabel">Starts:</span> <span id="lblTStarts"></span>
	</td></tr></table>

	<!--Show the winner when the tournament is completed-->
	<div id="divTournamentWinner" class="regularBox">
		The winner is...<br/>
		<h1 class="shadowed"><span id="lblTournamentWinner"></span></h1>
		Achievement awarded:
		<div class="achievementBox shadow" id="divTournamentAch2" style="max-width: 470px;"></div>
		<p>Congratulations and thanks to all who participated!</p>
	</div>
	
	<!--The header when a tournament is in progress-->
	<div id="divTournamentGamesWrapper">
		<span id="lblTInProgress">Tournament in progress...</span>
		<div id="divTournamentGames" style="max-width: 470px;"></div>
		<br/>
		<hr/>
	</div>
	
	<!--The rules sheet-->
	<div id="divTournamentRules" class="pageWidth" align="center" style="font-size: 12px;">		
	
		{*<p style="font-size: 12px;">You have until the time limit to complete your game in the current round of the tournament. The highest score in 10 rounds or the highest score when the time runs out wins.</p>*}
	
		<table cellspacing="4" style="font-size: 12px;">
		
			<tr style="display: none;"> <td align="right">Game Mode:</td>		<td><b><span id="lblGameMode"></span></b></td>		</tr>
			<tr style="display: none;"> <td align="right">Points to win:</td>	<td><b><span id="lblTPointsToWin"></span></b></td>	</tr>
			<tr style="display: none;"> <td align="right">Break-in:</td>		<td><b><span id="lblTBreakIn"></span></b></td>		</tr>
			
			<tr> <td align="right">Round limit:</td>	<td><span id="lblRoundHours"></span> hours</td>		</tr>
			<tr> <td align="right">Format:</td>			<td><span id="lblTFormat"></span></td>				</tr>
			<tr> <td align="right">Max Players:</td>	<td><span id="lblTMaxPlayers"></span></td>			</tr>
		</table>
		
		<div id="divTournamentAchieve">
			<br/>
			Win the tournament to earn this achievement:			
			<div class="achievementBox shadow" id="divTournamentAch" style="max-width: 470px;"></div>
		</div>		
	</div>
	
	<!--List of players participating in the tournament--> 
	<div id="divTournamentPlayersWrapper">
		<hr/>
		Participants:<br/>
		<div id="divTournamentPlayers"></div>
		<br/>
		<div id="divTournamentWaiting" class="regularBox" style="display: none; padding: 10px;">
			<p>Waiting for more player(s) to join...</p>
			<p>Join now and we'll send a notification when the tournament begins!</p>
		</div>
		<input id="divJoinTournament" type="button" class="mobileButton" buttoncolor="green" value="Join!" 
			onClick="JoinTournament()" style="width: 220px;">
		
	</div>
	<br/>
	<hr/>
	<input id="divQuitTournament" type="button" class="mobileButton" buttoncolor="red" value="Leave" 
			onClick="QuitTournament()">
			
	<input type="button" class="mobileButton" value="Lobby" onClick="ShowLobby()" style="width: 110px;">
	<br/>
	<br/>
</div>

{* The tournament separator object *}
<div id="defaultTournamentRoundSep" style="display: none;">
	<br/>
	~* Round <span id="lblRoundNum">1</span> *~
</div>

{* Tournament page for the list of tournaments you are in *}
{*<!--
<div id="divTournamentList" class="pagelayout" align="center" style="display: none;">

	<p>Play Farkle tournaments for special achievements!</p>
	<br/>
		Current: <br/>
		<br/>
		<a class="mobileButton" buttonColor="green" style="text-decoration: none;" onClick="ShowTournamentEx( 3 );">Farkle Cup</a>
		<br/>
		<br/>
		<br/>
		<br/>
		
		Completed: <br/>
		<br/>
		<a class="mobileButton" buttonColor="orange" style="text-decoration: none;" onClick="ShowTournamentEx( 1 );">Giant Cup</a>
		<br/>
		<br/>
		<a class="mobileButton" buttonColor="orange" style="text-decoration: none;" onClick="ShowTournamentEx( 2 );">Patriot Cup</a>
		<br/>
		<br/>
	<br/>
	<input type="button" class="mobileButton" buttoncolor="red" value="Lobby" onClick="ShowLobby()" style="width: 110px;"><br/>
	<br/>
</div>
-->*}