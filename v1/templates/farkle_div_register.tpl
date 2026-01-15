<!-- REGISTRATION -->
<div id="divRegister" class="pagelayout pageWidth" align="center" style="display: none;">

	<p>
		Registering on Farkle Online is quick, easy, and enables you to play with friends and other farklers online.
		Farkle Online requires very little information to play and we protect your information to the highest standards. 
		Your email is only used for the Farkle Online game to email you (such as game completed or someone has been waiting on you).
		No other players or third parties will know your address.
	</p>

	*<input type="text" 		class="mobileText" 	placeholder="new username"		id="txtRegUser"><br/>
	*<input type="password" 	class="mobileText" 	placeholder="new password" 		id="txtRegPass"><br/>
	*<input type="password" 	class="mobileText" 	placeholder="confirm password" 	id="txtRegPassConfirm"><br/>
	<input type="text" 			class="mobileText" 	placeholder="email" 			id="txtEmail"><br/><br/>
	{*
	*<input type="text" 		class="mobileText" 	placeholder="beta key" 			id="txtChallenge"><br/>
	<p><i>The beta key is the <b>Lone Star State</b>.</i></p>
	*}
	<input type="button" 		class="mobileButton" buttoncolor="green" 			value="Submit" onClick="RegisterUser()">
	<input type="button" 		class="mobileButton" buttoncolor="red" 				value="Cancel" onClick="PageGoBack()"><br/>
	<span id="lblRegError" style="color: yellow; font-weight: bold; font-size: 14px;"></span>
		
</div>