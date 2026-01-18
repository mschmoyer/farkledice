<!-- LOGIN SCREEN -->
<div id="divLogin" align="center" style="display: none;">
	
	<div class="loginBox">
		Welcome to Farkle Ten, the classic game of rolling dice with friends! 
	</div>
	
	<div class="loginBox">
		<input type="button" class="mobileButton" buttonColor="green" value="Farkle Login" onClick="$('#farkleLoginDiv').toggle();" style="width: 250px;">
		
		<div id="farkleLoginDiv" style="display: none;">
			
				{*Already have a player account?<br/>*}
				{if !$mobileMode}Username or Email: {/if}<input type="text" class="mobileText" placeholder="username or email" id="txtUsername" rows="20"><br/>
				{if !$mobileMode}Password: {/if}<input type="password" class="mobileText" placeholder="password" id="txtPassword"><br/>
				<input type="button" class="mobileButton" buttoncolor="green" value="Login" onClick="Login()">

				<span id="lblLoginErr" style="color: yellow; font-weight: bold;"></span>
			
		</div>
		<br/>
		<input type="button" class="mobileButton" buttonColor="orange" value="New Farkle Player" onClick="$('#farkleRegisterDiv').toggle();" style="width: 250px;">
		
		<div id="farkleRegisterDiv" style="display: none;">
			
				{*Register a new player account:<br/>*}
				{if !$mobileMode}Username: {/if}<input type="text" class="mobileText" placeholder="new username" id="txtRegUser"><br/>
				{if !$mobileMode}Password: {/if}<input type="password" class="mobileText" placeholder="new password" id="txtRegPass"><br/>
				{*
					{if !$mobileMode}Email:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {/if}<input type="text" class="mobileText" 	placeholder="email" id="txtEmail"><br/>
				*}
				<input type="button" class="mobileButton" buttoncolor="orange" value="Create" onClick="RegisterUser()">
			
				<span id="lblRegError" style="color: yellow; font-weight: bold; font-size: 14px;"></span>
			
		</div>
	</div>
	<div class="loginBox">
		<p><a href="javascript:void(0)" onClick="ShowInstructions()">How do you play farkle?</a></p>
		<br/>
		<p><a href="javascript:void(0)" onClick="ShowForgotPassword()">Forgot my Farkle password</a></p>
	</div>
</div>

<div id="divLoginLoading" class="pagelayout pageWidth" align="center" style="display: none;">
	
	<div class="loginBox shadowed" align="center">	
		<br/>
		<h2>Logging in...</h2>
		<br/>
	</div>

</div>

<div id="divResetPass" class="pagelayout pageWidth" align="center" style="display: none;">
	
	<div class="loginBox">
		<p>Enter your email to reset your password and recieve a limited time link to set a new password:</p>
		<input type="text" class="mobileText" placeholder="email" id="txtForgotEmail"><br/>
		<input type="button" class="mobileButton" buttoncolor="orange" value="Reset Password" onClick="SendPasswordReset()">
		<br/>
	</div>
	
	<div class="loginBox">
		<p>Paste the code you recieved via email into here and choose a new password:</p>
		
		<input type="text" class="mobileText" placeholder="code" id="txtForgotCode"><br/>
		<input type="password" class="mobileText" placeholder="new password" id="txtForgotPass"><br/>
		<input type="password" class="mobileText" placeholder="confirm password" id="txtForgotPassConfirm"><br/>
		<input type="button" class="mobileButton" buttoncolor="green" value="Set Password" onClick="SetNewPassword()">
		
		<span id="lblRegError" style="color: yellow; font-weight: bold; font-size: 14px;"></span>
	</div>
	<br/>
	
	<br/>
</div>
