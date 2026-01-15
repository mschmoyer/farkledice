<?php

// Include the SMSifed class.
require_once ('../includes/baseutil.php');
require 'smsified.class.php';

function SendTextMessage( $playerid, $message )
{
	/*
	if( empty($playerid) || empty($message) )
	{
		BaseUtil_Error( __FUNCTION__ . ": missing parameters. Playerid=$playerid, Message=$message" );
		return 0;
	}
	
	// SMSified Account settings.
	$username = "mschmoyer";
	$password = "mustang97sms";
	$senderAddress = "7062517407";

	// One player left. If enabled, let's send them a text. 
	$sql = "select phonenumber from farkle_players where playerid=$playerid";
	$phoneNumber = db_select_query( $sql, SQL_SINGLE_VALUE );
	
	if( !empty($phoneNumber) )
	{
		try {			
			// Create a new instance of the SMSified object.
			$sms = new SMSified($username, $password);
			
			// Send an SMS message and decode the JSON response from SMSified.
			$response = $sms->sendMessage($senderAddress, $phoneNumber, $message );
			$responseJson = json_decode($response);
			
			//var_dump($responseJson);
			
			return 1;
		}
		catch (SMSifiedException $ex) {
			BaseUtil_Debug( __FUNCTION__ . ": ERROR: $ex", 1 );
		}
	}
	*/
	return 0;
}
?>
