<!-- INSTRUCTIONS -->
<div id="divInstructions" class="pagelayout" align="center" style="display: none;">	

	<div style="font-weight: normal; font-size: 10pt; align: left;">
		<b style="font-size: 14pt;">Welcome to Farkle Ten!</b><br/>
		Farkle Ten is a unique twist on the game of Farkle geared towards fast and fun online
		play. Choose <b>How To Play Farkle</b> for a simple set of instructions on how to play or browse the rest of the Farkle Ten guide for 
		hints and tips!<br/>
	</div>
	
	<input type="button" class="mobileButton" buttonColor="orange" value="How To Play Farkle" style=" width: 280px; display: block;" 
	onClick="$('.instructionPage').hide();$('#divInstructHowToPlay').show();">
	
	<div class="instructionPage loginBox" id="divInstructHowToPlay" 
		style="font-weight: normal; font-size: 10pt; align: left; display: none; margin: 0px 5px 5px 5px;">
		
		
		<div style="font-weight: normal; font-size: 10pt; align: left;">
			<b style="font-size: 14pt;">Object:</b> The player with the highest score in 10 rounds of play wins! This is a modified version of Farkle geared towards faster online play 
			allowing you to finish up a game in one series instead of waiting on the other player over and over again.<br/>
		
			<b style="font-size: 14pt;">How to Play:</b> Each player takes turns rolling the dice. When it's your turn you'll roll all 6 dice. Points can be earned
			as shown below. If none of the dice you rolled score points it's a "Farkle"! Your points this round are loss and your turn is passed.<br/>
			<br/>
			You have the choice to choose one or more dice and "Score It" or try your luck and roll the rest of the dice again. You cannot earn points
			by combining points from a previous roll. For example if you score two <img src="/images/diceFront5.png" class="tinydice"><img src="/images/diceFront5.png" class="tinydice">
			, and then roll another <img src="/images/diceFront5.png" class="tinydice">, you do not get the triple for 500 points but instead will get 150.<br/>
		
		</div>
		
		<br/>
		<img src="/images/diceFront1.png" class="smalldice"> = 100<br/>
		<img src="/images/diceFront5.png" class="smalldice"> = 50<br/>
		<br/>
		<img src="/images/diceFront1.png" class="smalldice">
		<img src="/images/diceFront1.png" class="smalldice">
		<img src="/images/diceFront1.png" class="smalldice"> = 1000<br/>
		
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront2.png" class="smalldice"> = 200<br/>
		
		<img src="/images/diceFront3.png" class="smalldice">
		<img src="/images/diceFront3.png" class="smalldice">
		<img src="/images/diceFront3.png" class="smalldice"> = 300<br/>
		
		<img src="/images/diceFront4.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice"> = 400<br/>
		
		<img src="/images/diceFront5.png" class="smalldice">
		<img src="/images/diceFront5.png" class="smalldice">
		<img src="/images/diceFront5.png" class="smalldice"> = 500<br/>
		
		<img src="/images/diceFront6.png" class="smalldice">
		<img src="/images/diceFront6.png" class="smalldice">
		<img src="/images/diceFront6.png" class="smalldice"> = 600<br/>
		<br/>
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice">
		<img src="/images/diceFront6.png" class="smalldice">
		<img src="/images/diceFront6.png" class="smalldice"><br/>
		Three Pair = 750<br/>
		<br/>
		<img src="/images/diceFront1.png" class="smalldice">
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront3.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice">
		<img src="/images/diceFront5.png" class="smalldice">
		<img src="/images/diceFront6.png" class="smalldice"><br/>
		Straight = 1000<br/>
		<br/>
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront2.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice">
		<img src="/images/diceFront4.png" class="smalldice"><br/>
		Two Triplets = 2500<br/>
		<br/>
	</div>
	
	<input type="button" class="mobileButton" buttonColor="orange" value="Levels & Rewards" style=" width: 280px; display: block;"
		onClick="$('.instructionPage').hide();$('#divInstructLevelsRewards').show();">
	
	<div class="instructionPage loginBox" id="divInstructLevelsRewards" 
		style="font-weight: normal; font-size: 10pt; align: left; display: none; margin: 0px 5px 5px 5px;">
	
		<div style="font-weight: normal; font-size: 10pt; align: left;">
			<b style="font-size: 14pt;">Earning levels:</b> You start at level 1 and require experience points to earn the next level. 
			Playing and winning games is one way to earn experience points and scoring nice rolls is another.<br/>
		</div>
		<br/>
		<table width="95%">
			<tr><td>Finish a game</td><td>5 <img src="/images/xp.png" height="14"></td></tr>
			<tr><td>Win a game</td><td>10 <img src="/images/xp.png" height="14"></td></tr>
			<tr><td>Straight</td><td>1 <img src="/images/xp.png" height="14"></td></tr>
			<tr><td>Three Pair</td><td>1 <img src="/images/xp.png" height="14"></td></tr>
			<tr><td>Two triplets</td><td>1 <img src="/images/xp.png" height="14"></td></tr>
			<tr><td>3,4,5, or 6 Ones</td><td>1 <img src="/images/xp.png" height="14"></td></tr>	
		</table>
		<br/>
		<div style="font-weight: normal; font-size: 10pt; align: left;">
			Earning level 2 will require <b>40</b> <img src="/images/xp.png" height="14"> and the number will gradually rise with each level. 
			<br/>
			<br/>
			<b style="font-size: 14pt;">Card Backgrounds:</b> Every 10 levels gained will earn you a new player badge background. 
			This will distinguish you from the rest of the players and they will see your badge in each game and in your Friends list. <br/>
			<br/>
			<b style="font-size: 14pt;">Titles:</b> You will also gain a player <b>title</b> every 3 levels. These titles can be chosen any time
			from the <b>My Profile</b> screen and allow you to pick a fun title based on your mood or whim. Other players will see your title in game.<br/>
			<br/>			
		</div>
	</div>
	
	<input type="button" class="mobileButton" buttonColor="orange" value="Achievements" style=" width: 280px; display: block;"
		onClick="$('.instructionPage').hide();$('#divInstructAchievements').show();">
	
	<div class="instructionPage loginBox" id="divInstructAchievements" 
		style="font-weight: normal; font-size: 10pt; align: left; display: none; margin: 0px 5px 5px 5px;">
	
		<div style="font-weight: normal; font-size: 10pt; align: left;">
			<b style="font-size: 14pt;">Earning achievements:</b> Achievements are earned by doing special actions in Farkle Ten that go above and beyond the typical gameplay. 
			You might score a really high round, win a lot of games, or make it to the top of the leaderboards.<br/>
			<br/>
			Each achievement is worth achievement points and you can collect these points to move towards the top of the charts and compare with friends.<br/>
			<br/>
		</div>
	</div>
	<br/>
	<br/>
	<input id="btnInstGame" type="button" class="mobileButton" value="Back" onClick="PageGoBack()" style="width: 280px;">
	
</div>