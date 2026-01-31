<!-- PLAYER INFO -->
<div id="divPlayerInfo" class="farklePage" align="center" style="display: none;">

	<div id="divPlayerInfoTag" style="margin-bottom: 8px;"></div>

	<table class="pageWidth"><tr>
		<td class="farkleTab" id="tabPInfo0" selected="" width="33%"
			onClick="ShowPlayerInfoEx( 0 );">Stats</td>
		{*<td class="farkleTab" id="tabPInfo3"  width="25%"
			onClick="ShowPlayerInfoEx( 3 );">Options</td>*}
		<td class="farkleTab" id="tabPInfo1"  width="33%"
			onClick="ShowPlayerInfoEx( 1 );">Achievements</td>
		<td class="farkleTab" id="tabPInfo2"  width="33%"
			onClick="ShowPlayerInfoEx( 2 );">Games</td>

	</tr></table>
	
	<div class="playerInfoContainer" id="divPlayerInfoItem0" align="center" style="margin: 0px;">
		<table style="margin: 3px;" cellpadding="3px" class="statsTable">
		<colgroup>
			<col style="width: 50%;">
			<col style="width: 50%;">
		</colgroup>

		{* Level & Progress Section *}
		<tr><td colspan="2" class="section-header">Level & Progress</td></tr>
		<tr><td align="right">Level:</td><td><span id="lblLevel" style="color: #9DF497;"></span></td></tr>
		<tr><td align="right">Title:</td><td><span id="lblPlayerTitle"></span><select id="selPlayerTitleSelector" onChange="UpdateTitle()"></select></td></tr>
		<tr><td align="right">XP:</td><td><span id="lblXp"></span> / <span id="lblXpToLevel" style="color: yellow;"></span></td></tr>
		<tr><td colspan="2">
			<div class="xp-progress-container">
				<div class="xp-progress-bar" id="xpProgressBar"></div>
			</div>
		</td></tr>

		{* Opponent Reactions Section *}
		<tr id="trEmojiHeader" style="display: none;"><td colspan="2" class="section-header">Opponent Reactions</td></tr>
		<tr id="trEmojiStream" style="display: none;">
			<td align="right">Reactions:</td>
			<td></td>
		</tr>
		<tr id="trEmojiStreamContent" style="display: none;">
			<td colspan="2" style="text-align: center;"><span id="lblEmojiStream" style="font-size: 18px; word-wrap: break-word; line-height: 1.6;"></span></td>
		</tr>
		<tr id="trEmojiKnownFor" style="display: none;">
			<td align="right">Known for:</td>
			<td><span id="lblMostCommonEmoji" style="font-size: 32px;"></span></td>
		</tr>

		{* Win/Loss Record Section *}
		<tr><td colspan="2" class="section-header">Win/Loss Record</td></tr>
		<tr><td align="right">Wins:</td><td><span id="lblWins">0</span></td></tr>
		<tr><td align="right">Losses:</td><td><span id="lblLosses">0</span></td></tr>
		<tr><td align="right">Win/Loss %:</td><td><span id="lblWinLossRatio"></span></td></tr>

		{* Game Stats Section *}
		<tr><td colspan="2" class="section-header">Game Stats</td></tr>
		<tr><td align="right">Total Points:</td><td><span id="lblTotalPoints"></span></td></tr>
		<tr><td align="right">Highest Round:</td><td><span id="lblHighestRound"></span></td></tr>
		<tr><td align="right">Average Round:</td><td><span id="lblAvgRound"></span></td></tr>
		<tr><td align="right">Highest Score:</td><td><span id="lblHighest10Round"></span></td></tr>
		<tr><td align="right">Total Farkles:</td><td><span id="lblFarkles" style="color: #FF6B6B;"></span></td></tr>
		<tr><td align="right">Farkle Rate:</td><td><span id="lblFarklePct" style="color: #FF6B6B;"></span></td></tr>

		{* Activity Section - commented out for now
		<tr><td colspan="2" class="section-header">Activity</td></tr>
		<tr><td align="right">Last Played:</td><td><span id="lblLastPlayed"></span></td></tr>
		*}

		<tr><td colspan="2">
			<br/>
			Player card backgrounds unlocked:<br/>
			<img id="pInfoPrestige1" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<img id="pInfoPrestige2" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<img id="pInfoPrestige3" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<img id="pInfoPrestige4" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<img id="pInfoPrestige5" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<img id="pInfoPrestige6" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<img id="pInfoPrestige7" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<img id="pInfoPrestige8" src="/images/playericons/locked.png" class="playerCardBg"><br/>
			<br/>
		</td></tr>
		</table>
		
		<!--
		<div width="{if $mobileMode}300px{else}50%{/if}" class="loginBox">
			<div id="showPrestigeButton" style="display: none;">
				<b>Prestige Available!</b><br/>
				<p style="font-size: 10px;">Because you have scored over 245 achievement points you are eligable to "Prestige" up. When you prestiege, you will reset all of your statistics and achievements back to 0. Your player will then show a unique graphic showing other players that you are at a higher level of prestige.</p> 
				<p style="font-size: 10px; color: yellow;"><b>This will reset ALL achievements and statistics!</b></p>
				<input type="button" class="mobileButton" buttonColor="brown" value="Reset All Statistics" onClick="doPrestige();">
			</div>
			<div id="showPrestigeInfo">
				<p style="font-size: 10px;">Prestige: score 245 or more achievement points and you will be eligable to "prestige" up. Your statistics and achievements will be reset and you will earn a special graphic for your player tag showing other players that you are at a higher level of prestige.</p> 
				<input type="button" class="mobileButton" buttonColor="gray" value="Reset All Statistics" disabled>
			</div>
		</div>
		-->
	</div>
	
	<div class="playerInfoContainer" id="divPlayerInfoItem3" align="center" style="margin: 0px;">

		{* Display Name feature removed - now using username everywhere
		<div style="margin-bottom: 15px;">
			<label style="font-weight: bold;">Display Name:</label><br/>
			<input type="text" id="displayname" maxlength="30" style="width: 200px; padding: 5px;"
				   value=""
				   onchange="PlayerInfoOptionsDirty();"
				   onkeyup="PlayerInfoOptionsDirty();" />
			<div style="font-size: 11px; color: #666; margin-top: 3px;">
				Shown to other players instead of your username. Letters, numbers, spaces, and emoji allowed.
			</div>
		</div>
		<hr style="margin: 15px 0; border: 0; border-top: 1px solid #ddd;" />
		*}

		<table style="margin: 3px;" cellpadding="3px">

		<tr>
			<td><b>Email game updates</b></td>
			<td><input type="checkbox" id="chkEmailMe" onClick="PlayerInfoOptionsDirty()"><td>
		</tr>
		
		<tr><td style="font-size: 12px; color: #dddddd;">Send me an email once an hour if there are any new Farkle games started against me.</td></tr>
		
		<tr><td colspan="2"><input type="email" id="txtUserEmail" value="myemail@test.com" style="width:250px;" onChange="PlayerInfoOptionsDirty()"></td></tr>
		
		
		<tr><td><b>Play Me!</b></td><td><input type="checkbox" id="chkRandomSelectable" onClick="PlayerInfoOptionsDirty()"><td></tr>
		
		<tr><td style="font-size: 12px; color: #dddddd;">When Play Me! is enabled, this feature will allow you to be selected as a participant when other players start a new Random Game. You must have played in the last 2 weeks to be eligible to be selected.</td></tr>
		
		</table>
		
		<input id="btnSaveOptions" type="button" class="mobileButton" buttoncolor="green" value="Save Options" onClick="SaveOptions()" disabled>

	</div>
	
	<!--Achievements-->
	<div id="divPlayerInfoItem1" class="playerInfoContainer" align="center">
	
		<div id="divPlayerInfoAchs"></div>
	
		{*<div align="center">
			
			<table style="display: none;">
				<tr id="trAchievementExample">
					<td width="34px"><img src="/images/achieves/onestar.png" width="32px"></td>
					<td></td>
					<td></td>
				</tr>
			</table>	
			
			<table id="tabAchievements" class="loginBox pageWidth" cellpadding="2px"></table>	
			
		</div>*}
	</div>
	
	<!--Games-->
	<div id="divPlayerInfoItem2" class="playerInfoContainer" align="center">
		<div id="divCompletedGamesStats">
			{*<div class="gameChoice" onClick="Resumegame(28)"></div><br/>*}
		</div>
	</div>
	
	<input type="button" class="mobileButton" buttoncolor="red" value="Back" onClick="PageGoBack()" style="width: 110px;">
	
	<input id="btnPlayNow" type="button" class="mobileButton" buttoncolor="green" value="Play Now!" onClick="StartGameAgainst()">
	<input id="btnAddFriend" type="button" class="mobileButton" buttoncolor="green" value="Add Friend" onClick="AddFriend()">
	<input id="btnRemoveFriend" type="button" class="mobileButton" buttoncolor="red" value="Remove Friend" onClick="RemoveFriend()" style="display: none;">
	
</div>