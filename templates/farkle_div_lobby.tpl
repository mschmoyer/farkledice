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
						
			<tr><td><input type="button" class="mobileButton lobbybutton" value="New Game" 		onClick="ShowNewGame()" 		id="btnLobbyNewGame"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbybutton" buttoncolor="blue" 	value="Tournament" 				id="btnLobbyTournament"></td></tr>
			{*<tr><td><input type="button" class="mobileButton lobbybutton" value="Register" 	onClick="ShowRegister()" 		id="btnLobbyRegister" ></td></tr>*}
			<tr><td><input type="button" class="mobileButton lobbybutton" value="My Profile" 	onClick="ShowMyPlayerInfo()" 	id="btnLobbyMyProfile"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Friends" 		onClick="ShowFriends()" 		id="btnLobbyFriends"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Leaderboard" 	onClick="ShowLeaderBoard()" 	id="btnLobbyLeaderboard"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Instructions" 	onClick="ShowInstructions()" 	id="btnLobbyInstructions"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Logout" 		onClick="Logout()" 				id="btnLobbyLogout" ></td></tr>
			{if isset($adminlevel) && $adminlevel > 0 && 1==0}
				<tr><td><input type="button" class="mobileButton lobbybutton" value="Admin" 	onClick="ShowAdmin()" 			id="btnLobbyAdmin" ></td></tr>
			{/if}
			
			<tr>
				<td align="center" style="height: 34px;">
					
					{*<!--literal}
					<script>
						!function(d,s,id){
							var js,fjs=d.getElementsByTagName(s)[0];
							if(!d.getElementById(id)){
								js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";
								fjs.parentNode.insertBefore(js,fjs);
							}
						}
						(document,"script","twitter-wjs");
					</script>
					{/literal-->*}	
					<div class="fb-like" data-href="http://www.facebook.com/pages/Farkle-Online-Community/298444696874306" 
						data-send="false" data-layout="button_count" data-width="100" data-show-faces="false"></div>

				</td>
			</tr>
			
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




