{*<!--
	farkle_div_lobby.tpl	
	Desc: 
	
	13-Jan-2013		mas		Updates to support Farkle tournaments again. 
-->*}

<!-- LOBBY -->
<div id="divLobby" align="center" style="display: none;">

	<div id="divLobbyPlayerCard" style="margin-bottom: 0px;"></div>
	
	<table style="margin: 1px;" ><tr>
		<td style="font-size: 12px; color: white;">XP: </td>
		<td>
			<table class="xpBarWidth" height="16px" cellpadding="0" cellspacing="0" style="border: 1px solid black; margin: 0px;">
			<tr>
				<td id="xpBar"></td>
				<td id="xpNeg"></td>
			</tr>
			</table>
		</td>
	</tr></table>
		
	<!-- News Tidbit -->

	{*<div id="divLobbyFBLogin" class="fb-login-button" data-show-faces="false" data-width="200" data-max-rows="1"></div>*}
	
	<div style="margin: 8px 0 0 0;">
	
		<table cellpadding="0" cellspacing="3" class="lobbyWidth">
		<tr>
		
		<td class="lobbyGamesCell" width="80%" valign="top" align="right">		
		
			<div id="availableGamesLabel" align="left" style="margin-left: 12px; font-size: .8em;">Available games:</div>
		
			<div id="divLobbyGames" style="margin:0px;"></div>
			
			<div id="divLobbyNoGames" style="display: none;">
				<div align="center"> - No Games - </div>
				<div style="font-size: 10px; margin: 8px;" align="left">
					<p>Welcome to Farkle Ten!</p>
					<p>Want to learn Farkle or refresh your knowledge? View the <b>instructions</b> page for an easy guide.</p>
					<p>After that jump right into the action by choosing <b>New Game</b>. Then, select <b>random game</b> to find a game with a random opponent! The great part about Farkle Ten is you do not have to wait for your opponent to play!</p>
				</div>
			</div>

			<!-- Active Friends Section -->
			<div id="divActiveFriendsSection" style="display: none; margin-top: 12px;">
				<div id="activeFriendsLabel" align="left" style="margin-left: 12px; font-size: .8em;">Active Friends:</div>
				<div id="divActiveFriends" style="margin: 0px;"></div>
			</div>

			<!-- Template for active friend card (hidden) -->
			<div id="divActiveFriendTemplate" style="display: none;">
				<div class="activeFriendOuter" style="width: 250px; margin: 2px 0; display: flex; align-items: stretch;">
					<div class="activeFriendCard friendDiv" style="flex: 1; margin: 0; cursor: pointer; display: flex; align-items: center;">
						<span class="activeFriendName shadowed" style="padding: 6px 10px; color: white;"></span>
					</div>
					<input type="button" class="mobileButton activeFriendPlayBtn" buttoncolor="green" value="Play" style="margin: 0; border-radius: 0 8px 8px 0;">
				</div>
			</div>
		</td>
		
		<td width="20%" valign="top">
		
			<table cellpadding="0" cellspacing="0" >	
			
			<tr><td align="center">
				<span id="lblDoubleXP" class="shadowed" 
					style="display: none; font-weight: 900; font-family: helvetica; color: #a9db80;">Double XP!</span>
			</tr></td>
			
			{*<tr><td>
				<img id="btnLobbyTournament" class="tourneyButton" src="/images/tournaments/farkletournament1.png" 
					onMouseOver="this.style.cursor='hand';" id="btnLobbyTournaments" style="display: none;">
			</tr></td>*}
						
			<tr><td><input type="button" class="mobileButton lobbyButton" value="New Game" 		onClick="ShowNewGame()" 		id="btnLobbyNewGame"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" buttoncolor="blue" 	value="Tournament" 				id="btnLobbyTournament"></td></tr>
			{*<tr><td><input type="button" class="mobileButton lobbyButton" value="Register" 	onClick="ShowRegister()" 		id="btnLobbyRegister" ></td></tr>*}
			<tr><td><input type="button" class="mobileButton lobbyButton" value="My Profile" 	onClick="ShowMyPlayerInfo()" 	id="btnLobbyMyProfile"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Friends" 		onClick="ShowFriends()" 		id="btnLobbyFriends"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Leaderboard" 	onClick="ShowLeaderBoard()" 	id="btnLobbyLeaderboard"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Instructions" 	onClick="ShowInstructions()" 	id="btnLobbyInstructions"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Logout" 		onClick="Logout()" 				id="btnLobbyLogout" ></td></tr>
			{if isset($adminlevel) && $adminlevel > 0}
				<tr><td><input type="button" class="mobileButton lobbyButton" value="Admin" 	onClick="ShowAdmin()" 			id="btnLobbyAdmin" ></td></tr>
			{/if}
			
			
			</table>
			
		</td></table>
	</div>	
	
</div>

<div id="divLobbyIdle" align="center" style="display:none;">
	<br/>
	<br/>
	<p>It appears you have stepped away from the Farkle lobby. Press refresh to return.</p>	
	<input type="button" class="mobileButton" value="Refresh" style="width:110px;" onClick="LobbyBackFromIdle()" buttoncolor="green">
	<br/>
	<br/>
	<br/>
</div>




