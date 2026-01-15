
var gUsingFacebook = 0;
var gPlayerImg;
var gPlayerName;
var gPlayerFacebookId;
var gPlayerEmail;

window.fbAsyncInit = function() {
    FB.init({
		appId      : '271148502945493', // App ID
		channelUrl : 'http://www.farkledice.com/channel.html', // Channel File
		status     : true, // check login status
		cookie     : true, // enable cookies to allow the server to access the session
		xfbml      : true,  // parse XFBML
		frictionlessRequests: true,
		useCachedDialogs: true,
		oauth: true
    });

    FB.Event.subscribe('auth.statusChange', handleStatusChange);
  };

// Load the SDK Asynchronously
(function(d){
 var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
 if (d.getElementById(id)) {return;}
 js = d.createElement('script'); js.id = id; js.async = true;
 js.src = "//connect.facebook.net/en_US/all.js";
 ref.parentNode.insertBefore(js, ref);
}(document));

// Login event
function handleStatusChange(response) {
     document.body.className = response.authResponse ? 'connected' : 'not_connected';

     if (response.authResponse) {
       ConsoleDebug(response);
       updateUserInfo(response);
     }
   }
   
function FacebookLogin() 
{
	FB.login(function(response) 
	{
		if (response.authResponse) 
		{
			ConsoleDebug('Initiating a Facebook login...');
			updateUserInfo(response);
		} 
		else 
		{
			ConsoleDebug('User cancelled login or did not fully authorize.');
		}
	}, {scope: 'email,publish_actions'} ); // Did not fix white screen after facebook login -
}
   
function updateUserInfo(response) 
{
	ConsoleDebug('Initiating a Facebook user update...');

	if( $('#divLogin').is(':visible') ) {
		$('#divLogin').hide();
		$('#divLoginLoading').show();
	}
	
	FB.api('/me', function(response) 
	{
		gPlayerFacebookId = response.id;
		gPlayerImg = 'https://graph.facebook.com/' + response.id + '/picture';
		gPlayerName = response.name;
		gPlayerEmail = ( response.email ? response.email : '' );
		
		// Try username, then full name, then facebookid
		if( response.name && response.name.toLowerCase() != 'undefined' )
			username = response.name; 
		else if( response.username && response.username.toLowerCase() != 'undefined' )
			username = response.username;
		else
			username = 'User'+gPlayerFacebookId;
		
		gUsingFacebook = 1;
			
		
		AjaxCallPost2( gAjaxUrl, 
		function() 
		{
			if( $('#divLoginLoading').is(':visible') ) {
				$('#divLogin').show();
				$('#divLoginLoading').hide();
			}
			if( ajaxrequest2.responseText )
			{
				var payload = farkleParseJSON( ajaxrequest2.responseText );
				if( payload['Error'] )
				{
					$('#lblLoginErr').html( payload['Error'] );
				}
				else
				{
					// Successful login
					var willShowLobby = 0;
					if( !playerid )
						willShowLobby = 1;
						
					username = payload.username;
					playerid = payload.playerid;
					$('#txtPassword').val('');					
					if( willShowLobby) ShowLobby();
					
					//$('#divLobbyFBLogin').hide();
					//$('#pLobbyFBLogin').hide();
				}
			}
		}, 
		'action=fblogin&facebookid='+gPlayerFacebookId+'&username='+encodeURIComponent(username)+'&email='+encodeURIComponent(gPlayerEmail)+'&fullname='+encodeURIComponent(gPlayerName)+'&playerid='+playerid );
		
	});
}

function sendRequestViaMultiFriendSelector() {
  FB.ui({method: 'apprequests',
    message: 'Wants to play a game of Farkle with you.',
  }, requestCallback);
}

function requestCallback() {
	//alert('returning');
}

function FacebookPublishGame( title, message, imgFile ) {

	ConsoleDebug('Facebook publishing to stream title=' + title + ', message=' + message );

	// calling the API ...
	var obj = {
	  method: 'feed',
	  redirect_uri: 'http://www.farkledice.com/',
	  link: 'http://www.farkledice.com/',
	  picture: 'http://www.farkledice.com/images/' + imgFile,
	  name: title,	  
	  description: message
	}; //caption: 'Reference Documentation',

	function callback(response) {
	  //document.getElementById('msg').innerHTML = "Post ID: " + response['post_id'];
	}

	FB.ui(obj, callback);
}

function FacebookPublishScore( playerscore ) 
{
	// FOR INITIAL TESTING DEBUG
	if( playerid != 1 ) return 0; 

	ConsoleDebug('Facebook publishing to score ' + playerscore + '' );

	// calling the API ...
	var obj = {
	  method: 'score',
	  redirect_uri: 'http://www.farkledice.com/',
	  score: playerscore
	}; //caption: 'Reference Documentation',

	function callback(response) {
	  //document.getElementById('msg').innerHTML = "Post ID: " + response['post_id'];
	}

	FB.ui(obj, callback);
}

//Prompt the user to login and ask for the 'email' permission
function promptLogin() {
  FB.login(null, {scope: 'email'});
}

//This will prompt the user to grant you acess to a given permission
function promptPermission(permission) {
  FB.login(function(response) {
    if (response.authResponse) {
      checkUserPermissions(permission)
    }
  }, {scope: permission});
}

//See https://developers.facebook.com/docs/reference/api/user/#permissions
function uninstallApp() {
  FB.api('/me/permissions', 'DELETE',
    function(response) {
      window.location.reload();
      // For may instead call logout to clear
      // cache data, ex: using in a PhoneGap app
      //logout();
  });
}

//See https://developers.facebook.com/docs/reference/javascript/FB.logout/
function FB_logout() {
  FB.logout();
}
