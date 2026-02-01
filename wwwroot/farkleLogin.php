<?php

require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleAchievements.php');
require_once('farkleUtil.php');

function Farkle_SessSet()
{
	BaseUtil_Debug( __FUNCTION__ . " entered.", 7 );
	BaseUtil_SessSet( );
	
	$loginSucceeded = null;
	$rememberCredentials = 1;
	
	// Test login as someone else. 
	/*if( $_SESSION['playerid'] == 1 && isset($_GET['adminlogintest']) )
	{
		$loginSucceeded = UserLoginSafe( $_GET['adminlogintest'], $remember=1 );
		return ( isset($loginSucceeded['Error']) ? 0 : 1 );
	}*/
	
	//A login sessionid exists, go ahead and reuse it. 
	if( isset( $_REQUEST['iossessionid'] ) )
	{
		// A login attempt from the iPhone (this is the session_id) 
		BaseUtil_Debug( __FUNCTION__ . " IOS session found.", 14 );
		$loginSucceeded = UserLoginSafe( $_REQUEST['iossessionid'], $rememberCredentials );
	}
	else if( isset( $_COOKIE['farklesession'] )  )
	{
		BaseUtil_Debug( __FUNCTION__ . " cookie session found.", 14 );
		$loginSucceeded = UserLoginSafe( $_COOKIE['farklesession'], $rememberCredentials );
	}
	
	if( !isset($loginSucceeded['Error']) && !$loginSucceeded && isset( $_SESSION['farklesession'] )  )
	{
		BaseUtil_Debug( __FUNCTION__ . " session variable found.", 14 );
		$loginSucceeded = UserLoginSafe( $_SESSION['farklesession'], $rememberCredentials );
	}

	$haveLogin = 1; 
	if( !isset($loginSucceeded) || isset($loginSucceeded['Error']) ) {
		$haveLogin = 0; 
	}

	BaseUtil_Debug( __FUNCTION__ . " Returning: $haveLogin", 14 );
	return $haveLogin;
}

function UserLogout()
{
	global $g_debug; 
	
	// Clear the session from this device's row
	if( isset($_SESSION['farklesession']) )
	{
		$sql = "update farkle_players_devices set sessionid='LoggedOut{$_SESSION['farklesession']}', lastused=NOW() 
				where playerid={$_SESSION['playerid']} and sessionid='{$_SESSION['farklesession']}'";
		$rc = db_command($sql);
	}
	
	if( isset($_SESSION['username']) ) 
	{
		setcookie('username', '', time() - 3600 );
		unset( $_SESSION['username'] );
	}
	if( isset($_SESSION['password']) ) 
	{
		setcookie('password', '', time() - 3600 );
		unset( $_SESSION['playerid'] );
	}
	if( isset($_SESSION['farklesession']) ) 
	{
		setcookie('farklesession', '', time() - 3600 );
		unset( $_SESSION['farklesession'] );
	}
	setcookie('loggedin', '0', time() - 3600 );
	
	if( $g_debug >= 7 ) 
	{
		BaseUtil_Debug( __FUNCTION__ . ": Session vars now=", 7 );
		var_dump( $_SESSION['username'] ); 
		var_dump( $_SESSION['password'] ); 
		var_dump( $_SESSION['farklesession'] );
		
		BaseUtil_Debug( __FUNCTION__ . ": Cookie vars now=", 7 );
		var_dump( $_COOKIE['username'] ); 
		var_dump( $_COOKIE['password'] ); 
		var_dump( $_COOKIE['farklesession'] ); 
	}
	
	return 1;
}

function UserLoginSafe( $sessionid, $remember=1 )
{
	BaseUtil_Debug( __FUNCTION__ . " entered.", 7 );
	
	// Attempt to find the selected session id 
	$sql = "select a.username, a.playerid as playerid, adminlevel, b.agentString
		from farkle_players a, farkle_players_devices b
		where a.playerid=b.playerid and b.sessionid='$sessionid'";			
	$pInfo = db_select_query( $sql, SQL_SINGLE_ROW );
	
	if( $pInfo )
	{
		$agentString = isset($pInfo['agentString']) ? $pInfo['agentString'] : 'unknown';
		BaseUtil_Debug( __FUNCTION__ . ": Success. User {$pInfo['username']} logged in. Agent={$agentString}", 7 );
		if( isset($_COOKIE['farklesession']) ) $_SESSION['farklesession'] = $_COOKIE['farklesession'];
		LoginSuccess( $pInfo, $remember );
		return $pInfo;
	}
	
	BaseUtil_Debug( __FUNCTION__ . " - session [$sessionid] was not found. Login failed.",1 );
	return Array('Error' => 'Username or password incorrect.');
}

// This function will remain in place until old sessions are migrated to the new table (or until reasonable to leave it in). 
function RegenerateDevice( $sessionid, $playerid )
{
	// Attempt to find a sessionid in farkle_players that does not exist in devices. 
	$sql = "select playerid, sessionid from farkle_players_devices where sessionid='$sessionid'";
	$prev = db_select_query( $sql, SQL_SINGLE_ROW );
	
	if( !$prev ) 
	{
		BaseUtil_Debug( __FUNCTION__ . ": Migrating player {$playerid} session to new table.", 1 );
		// No device found -- but they do have a session. 
		// This is old data so we'll make a new device with the current user agent. 
		$agentString = ( !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "unrecognized" );

		$sql = "insert into farkle_players_devices (playerid, sessionid, lastused, agentstring) values 
				({$playerid}, '{$sessionid}', NOW(), '{$agentString}')";
		$rc = db_command($sql);
		return 1; 
	}
	return 0; 
}

function LoginSuccess( $pInfo, $remember=1 )
{
	BaseUtil_Debug( "User " . $pInfo['username'] . " logged in.", 7 );
	
	$_SESSION['username'] = $pInfo['username'];
	$_SESSION['playerid'] = $pInfo['playerid'];
	if( isset($pInfo['adminlevel']) ) $_SESSION['adminlevel'] = $pInfo['adminlevel'];
	
	// Update IP address and mark as active on login
	$remoteIP = $_SERVER['REMOTE_ADDR'];
	$sql = "update farkle_players set remoteaddr='{$remoteIP}', lastplayed=NOW() where playerid='{$pInfo['playerid']}'";
	$rc = db_command($sql);
	
	return 1;
}

function LoginGenerateSession( $playerid, $remember=1, $device='web' )
{
	if( empty($playerid) ) 
	{
		BaseUtil_Error( __FUNCTION__ . ": Missing parameter. Playerid=$playerid, Remember=$remember" ); 
		return 0; 
	}

	// Create a cryptographically secure session ID
	$sessionid = bin2hex(random_bytes(32));
	$agentString = ( !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "unrecognized" );
	
	if( isset($_SESSION['ios_app']) ) {
		$device = 'ios_app';
	}
	
	if( $remember )
	{
		// New device
		$sql = "insert into farkle_players_devices (playerid, sessionid, lastused, agentstring, device) values
				($playerid, '$sessionid', NOW(), '$agentString', '$device')
				ON CONFLICT (playerid, device) DO UPDATE SET sessionid='$sessionid', agentstring='$agentString', lastused=NOW()";
		$rc = db_command($sql);
		
		// Remember the sessionid for a month
		setcookie('farklesession', $sessionid, time()+20*365*24*60*60 ); // Not MD5
		setcookie('playerid', $playerid, time()+20*365*24*60*60 ); // Not MD5
		setcookie('loggedin', '1', time()+20*365*24*60*60 ); // Not MD5
		$_SESSION['farklesession'] = $sessionid;
	}

	return $rc; 
}

function UserLogin( $user, $pass, $remember=1 )
{
	BaseUtil_Debug( __FUNCTION__ . " entered.", 7 );
		
	$sql = "select username, playerid, adminlevel, sessionid
		from farkle_players
		where (MD5(username)='$user' OR MD5(LOWER(email))='$user') and password=CONCAT('$pass',MD5(salt))";	
		
	$pInfo = db_select_query( $sql, SQL_SINGLE_ROW );
	if( $pInfo )
	{
		LoginGenerateSession( $pInfo['playerid'] );
		LoginSuccess( $pInfo, $remember );
		return $pInfo;
	}
	
	return Array('Error' => 'Username or password incorrect.');
}

function UserRegister( $user, $pass, $email, $remember = 0, $registeringGuest = 0 )
{	
	BaseUtil_Debug( "UserRegister: entered.", 7 );
	if( !empty($email) ) $email = filter_var($email, FILTER_SANITIZE_EMAIL);
	
	$salt = "35td2c";
	
	$aValid = array('-', '_');
	if(!ctype_alnum(str_replace($aValid, '', $user))) 
	{
		return Array( "Error" => "Username may only consist of characters, numbers, or an underscore.");
	} 
	if( strlen($user) < 3 || strlen($user) > 32 ) 
	{
		return Array( "Error" => "Username must be between 3 and 32 characters");
	}
	if( HasBadWords($user) )
	{
		return Array( "Error" => "Username may not contain bad words.");
	}
	
	//$sql = "select username, email from farkle_players where username = '$user' or email='$email'";
	$sql = "select username from farkle_players where username = '$user'";
	$userExists = db_select_query( $sql, SQL_SINGLE_ROW );
	
	if( !empty($userExists) ) 
	{
		if( strcmp($email, $userExists['email']) == 0 )
		{
			$existingValue = 'email';
		} 
		else if( strcmp($user, $userExists['username']) == 0 ) 
		{
			$existingValue = 'username';
		}
		else 
		{
			$existingValue = "unknown type of login";			
		}
		BaseUtil_Error( __FUNCTION__ . " - Existing $existingValue found. User inputted: $user. In DB: User={$userExists['username']}, Email={$userExists['email']}" );

		return Array('Error' => "Username or email already exists. If you forgot your information, try the Forgot my Farkle Password link on the home page.");		
	}
	
	$sess_user = "null";
	if( isset($_SESSION['username']) ) $sess_user = $_SESSION['username'];
	if( stripos( $sess_user, 'guest') === 0 )
	{
		// Logged in as a guest, so just transfer their information over to the guest account. 
		$sql = "update farkle_players set username='$user', password=CONCAT('$pass',MD5('$salt')), 
			email='$email', salt='$salt' where playerid=" . $_SESSION['playerid'];
		BaseUtil_Debug( "Updating guest account to real account.", 7 );
	} 
	else
	{		
		// Allow new user
		$remoteIp = $_SERVER['REMOTE_ADDR'];
		$sql = "insert into farkle_players (username, password, email, salt, lastplayed, createdate, remoteaddr ) 
			values ('$user', CONCAT('$pass',MD5('$salt')), '$email', '$salt', NOW(), NOW(), '$remoteIp' )";	
		BaseUtil_Debug( "Inserting new account.", 7 );
	}
	if( db_command($sql) )
	{
		$userinfo = UserLogin( md5($user), $pass, $remember );
		return $userinfo;	
	}
	
	return Array("Message", "Successfully registered $user");
}



function ResendPassword( $email )
{
	BaseUtil_Debug( "ResendPassword: entered.", 7 );	
	// Get the email of the current player in this game
	$sql = "select playerid from farkle_players
		where lower(email)=lower('$email')";
	$playerid = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	$resetPassCode = random_string( 16, 16 );
	
	if( !empty($playerid ) )
	{
		BaseUtil_Debug( "ResendPassword: Updating passcode for playerid $playerid.", 7 );	
		
		$sql = "update farkle_players set resetpasscode='$resetPassCode' where playerid=$playerid";
		db_command($sql);
		
		$subject = "Your farkle password reset code";
		
		$message = "A farkle password reset code has been sent. You can enter this password at\r\n
		www.farkledice.com on the Forgot my Password page. This code will only work for 24 hours.\r\n
		\r\n
		Your code is: $resetPassCode\r\n
		\r\n
		If you still have trouble logging in please email admin@farkledice.com";				

		BaseUtil_Debug( "Sending email to $email. Subj=[$subject] Msg=[" . strip_tags($message) . "]", 7 );
			
		$rc = SendEmail($email, $subject, $message);
	}
	else
	{
		BaseUtil_Debug( __FUNCTION__ . ": User tried to send password reset to unknown account. Email=$email.", 1);
		return Array('Error'=>'An account does not exist with this email address');
	}
	return Array('Success'=>'1');
}

function ResetPassword( $code, $pass )
{
	BaseUtil_Debug( "ResetPassword: entered.", 7 );	
	$salt = "35td2c";
	//CONCAT('$pass',MD5('$salt'))
	$sql = "select playerid from farkle_players where resetpasscode='$code'";
	$playerid = db_select_query( $sql, SQL_SINGLE_VALUE );
	if( !empty($playerid) )
	{
		$sql = "update farkle_players set password=CONCAT('$pass',MD5('$salt')) where playerid=$playerid";
		db_command($sql);
		return Array('Success'=>'1');
	}
	
	BaseUtil_Debug( __FUNCTION__ . ": Bad password reset value. Code=$code", 1);
	return Array('Error'=>'Invalid password reset code.');
}

function AddDeviceToken( $device, $deviceToken, $sessionid, $playerid )
{
	$resp = Array( 'Error' => 'Unknown error' ); 
	BaseUtil_Debug( __FUNCTION__ . ": attempting to add $device token for $playerid. deviceToken=$deviceToken, session=$sessionid", 7 ); 
	
	$agentString = ( !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "unrecognized" );
	$sql = "insert into farkle_players_devices (playerid, sessionid, device, token, lastused, agentstring)
			values ('$playerid', '$sessionid', '$device', '$deviceToken', NOW(), '$agentString')
			ON CONFLICT (playerid, device) DO UPDATE SET token='$deviceToken', lastused=NOW(), agentstring='$agentString'";

	if( db_command($sql) )
	{
		$sql = "select playerid, sessionid, device, token from farkle_players_devices where playerid=1 and device='$device'";
		$data = db_select_query( $sql, SQL_SINGLE_ROW );
		
		BaseUtil_Debug( __FUNCTION__ . ": Success. table now contains playerid={$data['playerid']}, sess={$data['sessionid']}, token={$data['token']}", 14 );
		
		$resp = Array( 'Message' => 'Token Accepted', 'Playerid' => $playerid, 'Token' => $deviceToken );
	}
	else
	{
		BaseUtil_Error( __FUNCTION__ . ": Error updating player $playerid devicetoken." ); 
		$resp = Array( 'Error' => 'Error updating player devicetoken.' );
	}
	
	return $resp;
}

?>