

function ShowLogin() {
	HideAllWindows();
	divLoginObj.show();
}

function ShowForgotPassword() {
	$('#divResetPass').show();
	$('#divLogin').hide();
}

function Login() {
	var theUser = $('#txtUsername').val().toLowerCase();
	var thePass = $('#txtPassword').val();
	var rememberMe = $('#chkRememberMe').val();
	
	//_gaq.push( ['_trackEvent', 'Ajax', 'LoginAttempt', 'Username', theUser ]);
	
	if( theUser && thePass ) {
	
		theUser = MD5( theUser );
		thePass = MD5( thePass );
		
		FarkleAjaxCall(	function() {
			var data = FarkleParseAjaxResponse( ajaxrequest.responseText );
			if( data ) {
				// Successful login
				username = data.username;
				playerid = data.playerid;
				$('#txtPassword').val('');						
				ShowLobby();
			} else {
				$('#lblLoginErr').html( data.Error );
			}
		}, 
		'action=login&user='+theUser+'&pass='+thePass+'&remember='+rememberMe );
		
	} else {
	
		$('#lblLoginErr').html('Username and password required.');
	}
}

function SendPasswordReset() {

	var theEmail = $('#txtForgotEmail').val();
	var encodedEmail = encodeURIComponent(theEmail);
	
	if( confirm( 'Are you sure you would like to reset your password?' ) ) {
		FarkleAjaxCall(	function() {
			var data = FarkleParseAjaxResponse( ajaxrequest.responseText );
			if( data ) {
				farkleAlert('Password email sent.');
			}
		}, 
		'action=forgotpass&email='+encodedEmail );
	}
}

function SetNewPassword() {

	var theCode			= $('#txtForgotCode').val();
	var thePass 		= $('#txtForgotPass').val();
	var thePassConfirm 	= $('#txtForgotPassConfirm').val();
	
	//_gaq.push( ['_trackEvent', 'Ajax', 'ForgotPassAttempt' ]);
	
	if( thePass.length < 6 || thePass.length > 32 )	{
		$('#lblRegError').html('Invalid password. Must be between 6 and 32 characters.');
		$('#txtRegPass').css('border', '2px solid red');
		$('#txtRegPassConfirm').css('border','2px solid red');
		
	} else {	
		// Everything ok. Do registration. 
		
		FarkleAjaxCall(	function ()	{
			var data = FarkleParseAjaxResponse( ajaxrequest.responseText );
			if( data ) {
				farkleAlert('Password reset!');
				ShowLogin();
			} else {
				$('#txtRegUser').css('border','2px solid red');
			}
		},
		'action=resetpass&code='+theCode+'&pass='+MD5(thePass) );
	}
}

function RegisterUser() {
	var theUser 		= $('#txtRegUser').val().toLowerCase();
	var thePass 		= $('#txtRegPass').val();
	//var thePassConfirm 	= $('#txtRegPassConfirm').val();
	var theEmail 		= $('#txtEmail').val();
	//var challenge 		= $('#txtChallenge').val();
	
	//_gaq.push( ['_trackEvent', 'Ajax', 'RegistrationAttempt', 'Username', theUser ]);
	
	if( theUser.length < 3 || theUser.length > 32 ) {
		$('#lblRegError').html('Invalid username. Must be between 3 and 32 characters.');
		$('#txtRegUser').css('border', '2px solid red');
		
	} else if( thePass.length < 6 || thePass.length > 32 ) {
		$('#lblRegError').html('Invalid password. Must be between 6 and 32 characters.');
		$('#txtRegPass').css('border', '2px solid red');
		$('#txtRegPassConfirm').css('border','2px solid red');
		
	} else {	
		// Everything ok. Do registration. 
		
		FarkleAjaxCall(	function ()	{
			var data = FarkleParseAjaxResponse( ajaxrequest.responseText );
			if( data ) {
				if( data.playerid && data.username )
				{
					username = data.username;
					playerid = data.playerid;
					
					// Clear input after use. 
					$('#txtRegUser').val('');
					$('#txtRegPass').val('');					
					$('#txtEmail').val('');
					
					ShowLobby();
					return 1;
				}
			} else {
				$('#txtRegUser').css('border','2px solid red');
			}
		},
		'action=register&user='+theUser+'&pass='+MD5(thePass)+'&email='+theEmail );
	}
}

function Logout() {
	if ( confirm("Are you sure you want to logout?") ) {
		//_gaq.push( ['_trackEvent', 'Ajax', 'Logout', 'Username', username ]);
		username = "";
		playerid = 0;
		FarkleAjaxCall(function(){ return 1; }, 'action=logout' );
		ShowLogin();
	}
}

