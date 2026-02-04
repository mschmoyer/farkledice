{*<!--
	farkle_div_lobby.tpl
	Desc:

	13-Jan-2013		mas		Updates to support Farkle tournaments again.
-->*}

<!-- LOBBY -->
<div id="divLobby" class="hidden">

	<div id="divLobbyPlayerCard"></div>

	<table class="lobbyXpTable"><tr>
		<td class="lobbyXpLabel">XP: </td>
		<td>
			<table class="xpBarWidth lobbyXpBarTable">
			<tr>
				<td id="xpBar"></td>
				<td id="xpNeg"></td>
			</tr>
			</table>
		</td>
	</tr></table>

	<!-- News Tidbit -->

	{*<div id="divLobbyFBLogin" class="fb-login-button" data-show-faces="false" data-width="200" data-max-rows="1"></div>*}

	<div class="lobbyContent">

		<table class="lobbyLayoutTable">
		<tr>

		<td class="lobbyGamesCell lobbyGamesColumn" valign="top">

			<div id="availableGamesLabel" class="lobbyLabel">Available games:</div>

			<div id="divLobbyGames"></div>

			<div id="divLobbyNoGames" class="hidden">
				<div class="lobbyNoGamesCenter"> - No Games - </div>
				<div class="lobbyNoGamesText">
					<p>Welcome to Farkle Ten!</p>
					<p>Want to learn Farkle or refresh your knowledge? View the <b>instructions</b> page for an easy guide.</p>
					<p>After that jump right into the action by choosing <b>New Game</b>. Then, select <b>random game</b> to find a game with a random opponent! The great part about Farkle Ten is you do not have to wait for your opponent to play!</p>
				</div>
			</div>

			<!-- Active Friends Section -->
			<div id="divActiveFriendsSection" class="hidden">
				<div id="activeFriendsLabel" class="lobbyLabel">Active Friends:</div>
				<div id="divActiveFriends"></div>
			</div>

			<!-- Template for active friend card (hidden) -->
			<div id="divActiveFriendTemplate" class="hidden">
				<div class="activeFriendOuter">
					<div class="activeFriendCard friendDiv">
						<span class="activeFriendEmoji"></span>
						<div class="activeFriendInfo">
							<span class="activeFriendName shadowed"></span>
							<span class="activeFriendStatus"></span>
						</div>
					</div>
					<input type="button" class="mobileButton activeFriendPlayBtn" buttoncolor="green" value="Play">
				</div>
			</div>
		</td>

		<td class="lobbyButtonsColumn">

			<!-- Leaderboard 2.0: Daily Game Counter -->
			<div id="lobby-daily-counter" class="lb2-lobby-counter" style="display:none;">
				<span id="lobbyCounterLabel">Daily Games: </span>
				<span id="lobbyCounterValue" class="lb2-counter-highlight">0/20</span>
				<span id="lobbyCounterSep"> | </span>
				<span id="lobbyCounterScoreLabel">Top 10: </span>
				<span id="lobbyCounterScoreValue" class="lb2-counter-highlight">0</span>
			</div>

			<table class="lobbyButtonsTable">

			<tr><td>
				<span id="lblDoubleXP" class="shadowed hidden">Double XP!</span>
			</td></tr>

			{*<tr><td>
				<img id="btnLobbyTournament" class="tourneyButton" src="/images/tournaments/farkletournament1.png"
					onMouseOver="this.style.cursor='hand';" id="btnLobbyTournaments" class="hidden">
			</td></tr>*}

			<tr><td><input type="button" class="mobileButton lobbyButton" value="New Game" 		onClick="ShowNewGame()" 		id="btnLobbyNewGame"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" buttoncolor="blue" 	value="Tournament" 				id="btnLobbyTournament"></td></tr>
			{*<tr><td><input type="button" class="mobileButton lobbyButton" value="Register" 	onClick="ShowRegister()" 		id="btnLobbyRegister" ></td></tr>*}
			<tr><td><input type="button" class="mobileButton lobbyButton" value="My Profile" 	onClick="ShowMyPlayerInfo()" 	id="btnLobbyMyProfile"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Friends" 		onClick="ShowFriends()" 		id="btnLobbyFriends"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Leaderboard" 	onClick="ShowLeaderBoard()" 	id="btnLobbyLeaderboard"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Instructions" 	onClick="ShowInstructions()" 	id="btnLobbyInstructions"></td></tr>
			<tr><td><input type="button" class="mobileButton lobbyButton" value="Logout" 		onClick="Logout()" 				id="btnLobbyLogout" ></td></tr>
			<tr id="trLobbyAdmin" {if !isset($adminlevel) || $adminlevel == 0}class="hidden"{/if}><td><input type="button" class="mobileButton lobbyButton" value="Admin" 	onClick="ShowAdmin()" 			id="btnLobbyAdmin" ></td></tr>

			</table>

		</td></tr></table>
	</div>

</div>

<div id="divLobbyIdle" class="hidden">
	<br/>
	<br/>
	<p>It appears you have stepped away from the Farkle lobby. Press refresh to return.</p>
	<input type="button" class="mobileButton lobbyIdleRefreshBtn" value="Refresh" onClick="LobbyBackFromIdle()" buttoncolor="green">
	<br/>
	<br/>
	<br/>
</div>
