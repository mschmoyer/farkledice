<?php
/*
	dbutil.php
	
	Date		Editor		Change
	----------	----------	----------------------------
	5-May-2011	MAS			Initial version. 

*/
require_once('baseutil.php');
require_once('farkleconfig.class.php'); 

define('SQL_SINGLE_VALUE', 0);
define('SQL_SINGLE_ROW', 1);
define('SQL_MULTI_ROW', 2);

$g_testNum = 0; 
$g_testBlock = "";

function db_connect() 
{	
	$config = new FarkleConfig(); 

	$username = $config->data['dbuser']; 
	$password = $config->data['dbpass']; 
	$host = $config->data['dbhost']; 
	
	$dbh=mysql_connect ($host, $username, $password) or die ('Error connecting to database. Reason: ' . mysql_error());
	mysql_select_db ("mikeschm_db") or die ('Error selecting database: ' . mysql_error());
	return $dbh;
}

function db_select_query( $sql, $return_type = SQL_MULTI_ROW ) 
{	
	global $g_debug;
	BaseUtil_Debug( 'Executing query: ' . $sql, 7, "gray" );
	
	if( $g_debug >= 14 ) $theStartTime = microtime(true);
	$result = mysql_query ($sql);

	if( !$result )
		BaseUtil_Error( __FUNCTION__ . ": SQL Error [" . mysql_errno() . "]: " . mysql_error() . "   SQL = $sql" );
	
	if( $return_type == SQL_MULTI_ROW ) // Can return many rows of data
	{
		$retval = array();
		while ( $row = mysql_fetch_assoc($result) ) 		
			array_push($retval, $row);
	}
	else if( $return_type == SQL_SINGLE_ROW ) // Can return a single row of data
	{
		$retval = mysql_fetch_assoc( $result );
	}
	else // SQL_SINGLE_VALUE -- Returns only a single value. 
	{
		$retval = mysql_fetch_row( $result );
		$retval = $retval[0];
	}		
	if( $g_debug >= 14 ) 
	{
		$theEndTime = microtime(true);
		$theRunTime = (string)($theEndTime-$theStartTime);
		BaseUtil_Debug( "SQL result run time: $theRunTime seconds.", 7, "#AAF" );
	}

	BaseUtil_Debug( 'SQL result in var dump below: ', 7, "gray" );
	if( $g_debug >= 14 ) var_dump( $retval );
	return $retval;
}

function db_command( $sql )
{
	BaseUtil_Debug( 'Executing query: ' . $sql, 7, "gray" );
	$result = mysql_query($sql);
	BaseUtil_Debug( "SQL result: $result", 7, "gray" );
	
	if( !$result )
		BaseUtil_Error( __FUNCTION__ . ": SQL Error [" . mysql_errno() . "]: " . mysql_error() . "   SQL = $sql" );
	
	return $result;
}

$link = db_connect();
?>