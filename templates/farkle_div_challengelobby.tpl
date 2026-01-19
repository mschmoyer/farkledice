{*<!--
	farkle_div_challengelobby.tpl
	Desc: Challenge Mode lobby - start new runs, resume, view bot lineup

	19-Jan-2026		Created for Challenge Mode feature
-->*}

<!-- CHALLENGE LOBBY -->
<div id="divChallengeLobby" align="center" style="display: none;">

	<!-- Header -->
	<div style="margin: 5px;">
		<span class="shadowed" style="font-size: 20px;">CHALLENGE MODE</span>
		<input type="button" class="mobileButton" buttoncolor="red"
			value="Back" onClick="ShowLobby()" style="width: 80px; float: right;">
	</div>

	<!-- Active Run Section (shown when player has active run) -->
	<div id="divChallengeActiveRun" class="regularBox" style="display: none; margin: 5px;">
		<div class="shadowed" style="font-size: 16px;">ACTIVE RUN</div>

		<div style="margin: 5px; font-size: 14px;">
			<div>Current Bot: #<span id="challengeCurrentBotNum">1</span> - <span id="challengeCurrentBotName">Byte</span></div>
			<div>Difficulty: <span id="challengeCurrentBotDiff">Easy</span></div>
			<div style="font-family: 'Courier New', monospace;">Money: $<span id="challengeRunMoney">0</span></div>
		</div>

		<div style="margin: 5px;">
			<div style="font-size: 12px; color: #ccc;">Your Dice:</div>
			<div id="divChallengeRunDice" style="margin: 5px;">
				<!-- Dice inventory shown here -->
			</div>
		</div>

		<input type="button" class="mobileButton" buttoncolor="yellow"
			value="CONTINUE RUN" onClick="ChallengeResumeRun()"
			style="width: 200px; font-size: 18px; margin: 5px;">

		<div style="margin-top: 10px;">
			<input type="button" class="mobileButton" buttoncolor="grey"
				value="Abandon Run" onClick="ChallengeAbandonRun()"
				style="width: 120px; font-size: 12px;">
		</div>
	</div>

	<!-- No Active Run Section (shown when player can start new run) -->
	<div id="divChallengeNoRun" class="regularBox" style="margin: 5px;">
		<div class="shadowed" style="font-size: 16px;">THE GAUNTLET</div>

		<p style="font-size: 14px; margin: 5px;">
			Beat 20 AI bots in sequence<br/>
			Earn $1 per die you save<br/>
			Buy dice in shop after each win<br/>
			Lose once and start over
		</p>

		<input type="button" class="mobileButton" buttoncolor="yellow"
			value="START CHALLENGE" onClick="ChallengeStartRun()"
			style="width: 220px; font-size: 20px; margin: 5px;">
	</div>

	<!-- Bot Lineup Preview -->
	<div class="regularBox" style="margin: 5px;">
		<div class="shadowed" style="font-size: 16px;">THE 20 BOTS</div>

		<div id="divChallengeBotLineup" style="font-size: 12px; margin: 5px; text-align: left;">
			<!-- Bot lineup populated by JavaScript -->
		</div>
	</div>

	<!-- Player Stats -->
	<div class="regularBox" style="margin: 5px;">
		<div class="shadowed" style="font-size: 16px;">YOUR STATS</div>

		<div style="font-size: 14px; margin: 5px;">
			<table width="100%" style="color: white;">
				<tr>
					<td>Total Runs:</td>
					<td align="right"><span id="challengeStatRuns">0</span></td>
				</tr>
				<tr>
					<td>Wins:</td>
					<td align="right"><span id="challengeStatWins">0</span></td>
				</tr>
				<tr>
					<td>Furthest Bot:</td>
					<td align="right">#<span id="challengeStatFurthest">0</span></td>
				</tr>
				<tr>
					<td>Total Dice Saved:</td>
					<td align="right"><span id="challengeStatDiceSaved">0</span></td>
				</tr>
			</table>
		</div>
	</div>

</div>
