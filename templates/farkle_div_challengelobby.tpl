{*<!--
	farkle_div_challengelobby.tpl
	Desc: Challenge Mode lobby - start new runs, resume, view bot lineup

	19-Jan-2026		Created for Challenge Mode feature
-->*}

<!-- CHALLENGE LOBBY -->
<div id="divChallengeLobby" align="center" style="display: none;">

	<!-- Header with money on right -->
	<div style="margin: 5px; display: flex; justify-content: center; align-items: center; position: relative;">
		<span class="shadowed" style="font-size: 20px;">Gauntlet Mode</span>
		<span id="challengeMoneyHeader" class="shadowed" style="display: none; position: absolute; right: 10px; font-size: 26px; font-weight: 900; font-family: 'Courier New', monospace; color: #feca57;">$<span id="challengeRunMoney">0</span></span>
	</div>

	<!-- Active Run Section (shown when player has active run) -->
	<div id="divChallengeActiveRun" style="display: none;">
		<!-- Buttons at top, outside the box -->
		<div style="margin: 10px;">
			<input type="button" class="mobileButton" buttoncolor="green"
				value="Continue" onClick="ChallengeResumeRun()"
				style="width: 120px;">
			<input type="button" class="mobileButton" buttoncolor="red"
				value="Abandon" onClick="ChallengeAbandonRun()"
				style="width: 120px; margin-left: 5px;">
		</div>

		<div class="regularBox" style="margin: 5px;">
			<div style="margin: 5px;">
				<div style="font-size: 12px; color: #ccc;">Your Dice:</div>
				<div id="divChallengeRunDice" class="dice-grid" style="margin: 5px;">
					<!-- Dice inventory shown here -->
				</div>
			</div>
		</div>
	</div>

	<!-- No Active Run Section (shown when player can start new run) -->
	<div id="divChallengeNoRun" class="regularBox" style="margin: 5px;">
		<p style="font-size: 14px; margin: 5px;">
			Beat 20 AI bots in sequence<br/>
			Earn $1 per die you save<br/>
			Buy dice in shop after each win<br/>
			Lose once and start over
		</p>

		<input type="button" class="mobileButton"
			value="Start Challenge" onClick="ChallengeStartRun()"
			style="width: 200px; margin: 5px;">
	</div>

	<!-- Bot Lineup Preview -->
	<div class="regularBox" style="margin: 5px;">
		<div class="shadowed" style="font-size: 16px;">The 20 Bots</div>

		<div id="divChallengeBotLineup" style="font-size: 12px; margin: 5px; text-align: left;">
			<!-- Bot lineup populated by JavaScript -->
		</div>
	</div>

	<!-- Player Stats -->
	<div class="regularBox" style="margin: 5px;">
		<div class="shadowed" style="font-size: 16px;">Your Stats</div>

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
