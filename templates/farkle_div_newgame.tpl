
<div id="divNewGame" align="center" style="display: none; max-width: 600px;">

	<!-- NEW GAME SCREEN - CHOOSE GAME TYPE -->
	<div id="divGameTypes" align="center">	
		<img src="/images/btn_invitefriends.png" onClick="sendRequestViaMultiFriendSelector()"><br/>
		<img src="/images/btn_playfriends.png" 	onClick="SelectPlayType(2,1)"><br/>
		<img src="/images/btn_playrandom.png"	onClick="SelectPlayType(2,0)"><br/>
		<img src="/images/btn_solo.png"			onClick="StartSoloGame()"><br/>
		<br/>		
		<input type="button" class="mobileButton" value="Back" buttoncolor="red" 
			onClick="ShowLobby()" style="width: 250px;">		
	</div>

	<div>
		{* RANDOM MODE *}
		<div id="divGameType0" class="loginBox" style="display: none; margin: 5px;">
			
			<input id="btnGame2Player" type="button" class="mobileButton" buttoncolor="brown" value="2 Players" onClick="SelectGamePlayers(2)">
			<input id="btnGame4Player" type="button" class="mobileButton" buttoncolor="brown" value="4 Players" onClick="SelectGamePlayers(4)">
		
			<p>If an open random table is found, you will join that. Otherwise we'll sit you down at a new table and let you play while we look for 
			other players to join you.</p>
		</div>
		
		{* SOLO MODE *}
		<div id="divGameType2" class="loginBox" style="display: none; margin: 5px;">
			
			<p style="margin: -8px 0 10px 0;">A solo game of Farkle where the goal is to get the highest score in 10 rounds. In this mode, you
			can still earn achievements but games do not count for wins.</p>
		</div>
		
		<div id="divNewGameStart"  class="loginBox" align="center" style="display:none;">
			<input type="button" class="mobileButton" buttoncolor="green" value="Start Game" onClick="StartGame()" style="width: 140px;">
			<input type="button" class="mobileButton" buttoncolor="red" value="Back" onClick="ShowNewGame()" style="width: 90px;">
			
			{*<input id="btnShowCustomRules" type="button" class="mobileButton" value="Custom Rules" onClick="ShowNewGameCustomRules()" style="width: 250px;">*}
		</div>
		
		{* FRIENDS MODE *}
		<div id="divGameType1" class="gameTypeDiv" align="center" style="display: none; margin: 20px;">
			<span style="margin: 4px;">Choose opponents:</span><br/>
			<div id="divPlayerChoices"><br/><div class="loginBox">Loading... </div><br/><br/></div>		
			<input type="button" class="mobileButton" value="Add Friend" onClick="ShowAddFriend()" style="width: 220px;">
		</div>
		
		
		
		
	</div>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
</div>
