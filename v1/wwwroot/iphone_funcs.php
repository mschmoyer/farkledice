<?php
/*
	iphone_funcs.php	
	Desc: Functions related to iOS and iPhone operations. 
	
	13-Jan-2013		mas		Updated in support of Farkle tournaments. 
*/

require_once('../includes/baseutil.php');
require_once('dbutil.php');

if( isset($_GET['test']) )
{
	//SendPushNotification( "2e67eba5 4e6b4681 6d452703 a659e4ad 272f0b90 bf884240 546e2601 ec3142e7>", "Test from Farkle Server", 3, "newGameTone.aif" ); 
}

//$playerid = one or more playerids. 
function SendPushNotification( $playerid, $alert, $sound="newGameTone.aif" )
{
	BaseUtil_Debug( __FUNCTION__ . ": Entered. Sound=$sound, Alert=$alert", 1 );
	
	if( empty($playerid) || empty($alert) )
	{
		BaseUtil_Error( __FUNCTION__ . ": missing parameters." ); 
		return 0; 
	}
	
	// DISABLED!!! 
	
	//BaseUtil_Debug( __FUNCTION__ . ": Disabled in code.", 1 );
	//return 0; 
	
	// DISABLED!! 
	
	$sql = "select c.playerid, d.token as token, 
			(select count(*) 
				from farkle_games a, farkle_games_players b 
				where a.gameid=b.gameid and b.playerid=c.playerid and a.winningplayer=0 and 
					( (a.gamemode=2 && b.playerRound < 11) || (a.gamemode=1 && a.currentturn=b.playerturn) ) ) as unfinished_games
		from farkle_players c, farkle_players_devices d 
		where c.playerid=d.playerid and d.device='ios_app' and d.token is not null";		
		
	if( strpos($playerid,',') !== 0 ) 
	{
		$sql .= " and c.playerid in ($playerid)"; 
	} else {
		$sql .= " and c.playerid='$playerid'";
	}
	
	$pData = db_select_query( $sql, SQL_MULTI_ROW );
	
	if( $pData ) 
	{
		// Create a stream to the server
		$ctx = stream_context_create();
		
		stream_context_set_option($ctx, 'ssl', 'local_cert', 'apns_dev.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', '');
		
		BaseUtil_Debug( __FUNCTION__ . ": CTX=$ctx", 1 );
		
		$apns = stream_socket_client(
			'ssl://gateway.sandbox.push.apple.com:2195',
		$error,
		$errorString,
		60,
		STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

		// You can access the errors using the variables $error and $errorString
		BaseUtil_Debug( __FUNCTION__ . ": APNS=$apns", 1 );
		
		if( $apns )
		{
			foreach( $pData as $p )
			{
				$token = $p['token']; 
				$badgeNumber = $p['unfinished_games']; 
				$playerid = $p['playerid'];

				$token = str_replace('<','', $token);
				$token = str_replace('>','', $token);

				BaseUtil_Debug( __FUNCTION__ . ": DeviceToken $token. Badge=$badgeNumber", 1 );
				
				// Now we need to create JSON which can be sent to APNS
				$load = array(
					'aps' => array(
						'alert' => $alert,
						'badge' => $badgeNumber,
						'sound' => $sound
					)
				);
				$payload = json_encode($load);
				// The payload needs to be packed before it can be sent
				$apnsMessage = chr(0) . chr(0) . chr(32);
				$apnsMessage .= pack('H*', str_replace(' ', '', $token));
				$apnsMessage .= chr(0) . chr(strlen($payload)) . $payload;
				 
				// Write the payload to the APNS
				fwrite($apns, $apnsMessage);
				error_log( __FUNCTION__ .": Push notification to Player #$playerid. Payload=$payload" ); 
				BaseUtil_Debug( __FUNCTION__ .": Push notification to Player #$playerid. Payload=$payload", 7 );
			
			}
			
			// Close the connection
			fclose($apns);
			
			return 1; 
		
		}
		else
		{
			BaseUtil_Debug( __FUNCTION__ .": Error [$error] - $errorString", 7 );
		}
	}
	else
	{
		BaseUtil_Debug( __FUNCTION__ .": no registered push notification devices.", 7 );
	}
	
	return 0;
	
}

?>