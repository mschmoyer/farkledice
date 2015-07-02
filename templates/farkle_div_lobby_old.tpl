<!-- LOBBY -->
<div id="divLobby" class="pagelayout" align="center" style="display: none;">

	<div id="divLobbyPlayerCard"></div>
	
	<!-- News Tidbit -->
	{*
	<p style="font-size: 12px;">
		Try a game of 10-round
	</p>
	*}
	
	<div style="margin: 14px 0 0 0;">
		<table cellpadding="0" cellspacing="0" width="{if !$mobilemode}50%{/if}">
		<tr><td width="60%" valign="top">

		{*
		<div id="divTournaments" align="center" style="margin: 2px;">
			<img src="/images/tournaments/patriotcup.png" onClick="ShowTournamentEx( 2 );">
		</div>
		*}
		
		<div id="divLobbyGames"></div>
		
		<div id="divLobbyNoGames" style="display: none;">
			<div align="center"> - No Games - </div>
			<p>If you don't know how to play Farkle or need a refresher check out the Instructions.</p>
			<p>If you think you're ready to play some Farkle, click "New Game" to jump right into a game!</p>
		</div>
		
		</td><td width="40%" valign="top">
		
			<table cellpadding="0" cellspacing="0" >
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Tournaments" onClick="ShowTournamentList()" 		id="btnLobbyTournaments" buttoncolor="green"></tr></td> 
			<tr><td><input type="button" class="mobileButton lobbybutton" value="New Game" 		onClick="ShowNewGame()" 		id="btnLobbyNewGame"></tr></td>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Register" 		onClick="ShowRegister()" 		id="btnLobbyRegister" ></tr></td>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Instructions" 	onClick="ShowInstructions()" 	id="btnLobbyInstructions"></tr></td>
			{* <tr><td><input type="button" class="mobileButton lobbybutton" value="Add Friend" 	onClick="ShowAddFriend()" 		id="btnLobbyAddFriend"></tr></td> *}
			<tr><td><input type="button" class="mobileButton lobbybutton" value="My Profile" 	onClick="ShowMyPlayerInfo()" 	id="btnLobbyMyProfile"></tr></td>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Leaderboard" 	onClick="ShowLeaderBoard()" 	id="btnLobbyLeaderboard"></tr></td>
			<tr><td><input type="button" class="mobileButton lobbybutton" value="Logout" 		onClick="Logout()" 				id="btnLobbyLogout" ></tr></td>
			</table>
			
		</td></table>
	</div>
	
	<br/>
	<br/>
	<!-- Footer -->
	<div align="center">
		{literal}
		<table><tr>
		<td>
		<a href="https://twitter.com/FarkleOnline" class="twitter-follow-button" data-show-count="false"
			data-width="70px" data-show-screen-name="false">Follow @FarkleOnline</a>
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
		</td><td>
		<div class="fb-like" data-href="http://www.facebook.com/pages/Farkle-Online-Community/298444696874306" 
			data-send="false" data-layout="button_count" data-width="100" data-show-faces="false"></div>
		</td><td>
		
		{/literal}
		
		
			<div class="fb-login-button" data-show-faces="false" data-width="200" data-max-rows="1"></div>
			
		</td>
		</tr>
		<tr><td colspan="3" width="320px"><br/>
			<p style="font-size: 10px; margin: 0 0 0 8px;">
				This new build is testing the capabilities of Facebook integration. Your facebook friends who 
				play Farkle should now show up when you choose New Game. To do so you must login at least once
				using Facebook and add this app (you can click the Login button above). Share with friends!
			</p></td></tr>
			</table>
	</div>
	
</div>

<div id="divLobbyIdle" align="center" style="display:none;">
	<br/>
	<br/>
	<p>It appears the lobby has gone idle.</p>	
	<input type="button" class="mobileButton" value="Refresh" style="width:110px;" onClick="GameBackFromIdle()" buttoncolor="green">
	<br/>
	<br/>
	<br/>
</div>



