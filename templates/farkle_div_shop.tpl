{*<!--
	farkle_div_shop.tpl
	Desc: Challenge Mode shop interface for purchasing special dice

	19-Jan-2026		Created for Challenge Mode feature
-->*}

<!-- CHALLENGE SHOP -->
<div id="divChallengeShop" align="center" style="display: none;">

	<!-- Victory Header -->
	<div class="regularBox" style="margin: 5px;">
		<div class="shadowed" style="font-size: 20px;">VICTORY!</div>
		<p style="font-size: 14px; margin: 5px;">
			You defeated Bot #<span id="shopBotNum">1</span> - <span id="shopBotName">Byte</span>
		</p>
		<div style="font-size: 16px; font-family: 'Courier New', monospace;">
			Money: <span id="shopMoney">$0</span>
		</div>
	</div>

	<!-- Shop Header -->
	<div class="regularBox" style="margin: 5px;">
		<div class="shadowed" style="font-size: 16px;">DICE SHOP</div>
		<p style="font-size: 14px; margin: 5px;">Choose a die (or skip to save money)</p>
	</div>

	<!-- Dice Cards Container (populated by JavaScript) -->
	<div id="divShopDiceCards" style="margin: 5px;">
		<!-- Dice cards will be inserted here by farkleShop.js -->
	</div>

	<!-- Current Dice Inventory -->
	<div class="regularBox" style="margin: 5px;">
		<div style="font-size: 14px;">YOUR DICE:</div>
		<div id="divShopDiceInventory" style="margin: 5px;">
			<!-- 6 dice slots shown here -->
		</div>
	</div>

	<!-- Navigation -->
	<div style="margin: 10px;">
		<input type="button" class="mobileButton" buttoncolor="orange"
			value="SKIP" onClick="SkipShop()" style="width: 120px;">
		<input type="button" class="mobileButton" buttoncolor="green"
			value="CONTINUE" onClick="ContinueToNextBot()"
			style="width: 150px; display: none;" id="btnContinueAfterPurchase">
	</div>

</div>

<!-- SLOT SELECTION MODAL -->
<div id="slotSelectionOverlay" style="display: none; position: fixed;
	top: 0; left: 0; width: 100%; height: 100%;
	background: rgba(0,0,0,0.7); z-index: 1000;"
	onclick="CloseSlotModal()">

	<!-- Modal content -->
	<div class="bot-select-modal" style="margin: 50px auto; max-width: 90%;"
		onclick="event.stopPropagation()" ontouchstart="">

		<h2 style="color: #333; font-size: 20px; margin: 0 0 8px 0;">
			SELECT DICE TO REPLACE
		</h2>

		<div style="color: #666; font-size: 14px; margin: 5px;">
			You're purchasing:<br/>
			<span id="purchaseDiceName" style="font-weight: bold;"></span><br/>
			Cost: <span style="font-family: 'Courier New', monospace;">$<span id="purchasePrice">0</span></span>
		</div>

		<div style="color: #333; font-size: 14px; margin: 8px 0;">
			Which die should it replace?
		</div>

		<!-- Dice slots (clickable) -->
		<div id="divSlotSelection" style="margin: 8px 0;">
			<!-- 6 slots populated by JavaScript -->
		</div>

		<div id="slotSelectedLabel" style="display: none; color: #f7ef00;
			font-size: 14px; margin: 5px;">
			SELECTED
		</div>

		<div style="margin: 10px;">
			<input type="button" class="mobileButton" buttoncolor="grey"
				value="CANCEL" onClick="CloseSlotModal()" style="width: 110px;">
			<input type="button" class="mobileButton" buttoncolor="yellow" disabled
				value="CONFIRM" onClick="ConfirmPurchase()" id="btnConfirmPurchase"
				style="width: 110px;">
		</div>
	</div>

</div>
