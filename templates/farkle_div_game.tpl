<!-- FARKLE GAME -->

<img id="farkledImage" src="/images/farkled1.png" style="display: none;">

<span id="scorePoints"></span>

<div id="divGame" class="pageWidth" align="center" style="display: none;">
	
	<!-- Game board (dice & buttons) -->
	<div id="divDice" class="diceFrame diceFrameWidth">
		<canvas width="60" height="60" id="dice0Canvas" {if !$mobilemode && !$tabletmode}onMouseDown="SaveDice(0)"{else}ontouchstart="SaveDice(0)"{/if} class="dice" disabled></canvas>
		<canvas width="60" height="60" id="dice1Canvas" {if !$mobilemode && !$tabletmode}onMouseDown="SaveDice(1)"{else}ontouchstart="SaveDice(1)"{/if}  class="dice" disabled></canvas>
		<canvas width="60" height="60" id="dice2Canvas" {if !$mobilemode && !$tabletmode}onMouseDown="SaveDice(2)"{else}ontouchstart="SaveDice(2)"{/if}  class="dice" disabled></canvas>
		<canvas width="60" height="60" id="dice3Canvas" {if !$mobilemode && !$tabletmode}onMouseDown="SaveDice(3)"{else}ontouchstart="SaveDice(3)"{/if}  class="dice" disabled></canvas>
		<canvas width="60" height="60" id="dice4Canvas" {if !$mobilemode && !$tabletmode}onMouseDown="SaveDice(4)"{else}ontouchstart="SaveDice(4)"{/if}  class="dice" disabled></canvas>
		<canvas width="60" height="60" id="dice5Canvas" {if !$mobilemode && !$tabletmode}onMouseDown="SaveDice(5)"{else}ontouchstart="SaveDice(5)"{/if}  class="dice" disabled></canvas>

		
		<div id="divRemind" class="mobileButton tinyButton" buttonColor="red" onClick="SendReminder()">Play!</div>
		
		<!--This div contains info such as "Your turn" and "You farkled!"-->
		<div id="divTurnAction" class="inGameInfo shadowed"></div>
	</div>	
	
	<!-- ROLL and SCORE IT buttons -->
	<table id="tabGameButtons" class="diceFrameWidth gamePlayersDiv">
	<tr>
		<td align="left">
			<input id="btnRollDice" type="button" class="mobileButton" buttoncolor="green"  value="Roll"
				{if !$mobilemode && !$tabletmode}onMouseDown{else}ontouchstart{/if}="RollDice()" style="width:110px; margin: 0px;">
		</td>
		<td width="85px" align="center">
			<span id="lblRoundScore" class="shadowed roundScore">0</span>
		</td>
		<td align="right">	
			<input id="btnPass" type="button" class="mobileButton" buttoncolor="orange" value="Score It" 
				{if !$mobilemode && !$tabletmode}onMouseDown{else}ontouchstart{/if}="PassTurn()" style="width:110px; margin: 0px;">
		</td>
	</tr>
	</table>
	
	<!-- The player tags for each player currently playing -->
	<div id="divGamePlayers" class="gamePlayersDiv"></div>

	<div id="divGameWaitingForPlayers" class="regularBox playerCardWidth" style="display: none; padding: 10px;">
		<p>Looking for player(s) to join...</p>
		<p>You may go ahead and play your rounds. Your score will be counted when a player joins</p>
	</div>

	<!-- Bot chat messages -->
	<div id="divBotChat" class="regularBox playerCardWidth" style="display: none; padding: 10px;">
		<div style="color: #96D3F2; font-weight: bold; margin-bottom: 8px; font-size: 14px;">Bot Chat</div>
		<div id="divBotChatMessages" style="font-size: 13px; line-height: 1.6; max-height: 150px; overflow-y: auto;"></div>
	</div>
	
	<table class="diceFrameWidth">
	<tr>
		<td>	
			<input id="btnGameGoBack" type="button" class="mobileButton" value="Back" 
				onClick="PageGoBack()" 
				style="width:110px; margin: 0px;">
			
		</td>
		<td width="85px" align="center">
			<!-- Left Blank -->&nbsp;
		</td>
		<td align="right">		
			<input id="btnQuitGame" type="button" class="mobileButton" value="Forfeit" buttoncolor="red"
				onClick="QuitGame()" 
				style="width:110px; margin: 0px;">
				
			<input id="btnNewRandom" type="button" class="mobileButton" value="New Random" buttoncolor="green"
				onClick="PlayAnotherRandom()" 
				style="width:110px; display: none; margin: 0px; padding: 0px; font-size: 16px;">	

			<input id="btnPlayAgain" type="button" class="mobileButton" value="Play Again" buttoncolor="green"
				onClick="PlayAgain()" 
				style="width:110px; display: none; margin: 0px; padding: 0px; font-size: 16px;">
		</td>
	</tr></table>

	<!-- Game Info. Such as Break-In and rounds -->
	<div id="divGameInfo">
		<span id="lblGameInfo" style="font-size: 12px;"></span>
	</div>
	
	<p style="font-size: 12px;">
		<span id="lblGameExpires"></span><br/>
		Game #<span id="dbgGameId"></span>
	</p>
</div>






<div id="divGameIdle" align="center" style="display:none;">

	<p>It appears like you have left the table. Press refresh to return to the table.</p>
	
	<input type="button" class="mobileButton" value="Refresh" style="width:110px;" onClick="GameBackFromIdle()" buttoncolor="green">

</div>

<!-- The box that shows your title redemption -->
<div id="divRedeemTitle" class="loginBox" style="display: none; background-color: black; position: absolute; top: 50px; left: 10px; width: 290px; height: 280px;">
	<b>You earned a title!</b><br/><br/>
	Promote yourself to a level <span id="lblMyTitleLevel"></span> title:<br/>
	
	<!-- player titles -->
	<select id="selectTitle0" onChange="SelectTitle(0)">
		<option id="title0None" value="" selected></option>			
	</select> 
	<br/><br/>
	OR 
	<br/><br/>
	Demote opponent to a level <span id="lblOppTitleLevel"></span> title:<br/>
	
	<!-- opponent titles -->
	<select id="selectTitle1" onChange="SelectTitle(1)">
		<option id="title1None" value="" selected></option>		
	</select><br/>	
	<br/>
	<input id="btnRedeemTitle" type="button" class="mobileButton" buttoncolor="green" value="Redeem" onClick="RedeemTitle()"> 
	<input id="btnSkipTitle" type="button" class="mobileButton" buttoncolor="red" value="Skip" onClick="SkipTitle()"> 
</div>