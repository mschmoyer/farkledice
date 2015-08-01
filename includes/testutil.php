<?php

require_once('baseutil.php');
require_once('dbutil.php'); 

function val_assert( $value, $checkFor=1 )
{
	global $g_testNum; 
	$pass = ( $value == $checkFor ); 
	BaseUtil_Debug( __FUNCTION__ . ": Test $g_testNum. Asserting $value==$checkFor. </pre><pre>Got? $value", 1, "purple" );
	if( !$pass ) {
		test_failed( __FUNCTION__ . ": Test $g_testNum. Asserting $value==$checkFor.</pre><pre>Got? $value" ); 
	}
	$g_testNum++; 
	return $pass; 
}

function db_assert( $sql, $checkFor=1 )
{
	global $g_testNum; 
	$value = db_select_query( $sql, SQL_SINGLE_VALUE ); 
	$pass = ( $value == $checkFor ); 
	BaseUtil_Debug( __FUNCTION__ . ": Test $g_testNum. Asserting $sql. </pre><pre>Want result: $checkFor. Got? $value", 1, "purple" );
	if( !$pass ) {
		test_failed(  __FUNCTION__ . ": Test $g_testNum. Asserting $sql. </pre><pre>Want result: $checkFor. Got? $value" );
	}
	$g_testNum++; 
	return $pass; 
}

function test_start( $testingBlock )
{
	global $g_testNum, $g_testBlock;
	BaseUtil_Debug( __FUNCTION__ . ": Starting tests for $testingBlock", 1, "purple" );
	$g_testNum = 1; 
	$g_testBlock = $testingBlock; 
}

function test_failed( $reason )
{
	global $g_testNum, $g_testBlock; 
	echo '<pre style="color:red;">' . $reason . '</pre>';
	echo '<div style="color: red; font-size: 28px;">******** '.$g_testBlock.': TEST ' . $g_testNum . ' FAILED! ********</div>';
	exit(1); 
}

function test_completed()
{
	global $g_debug, $g_testNum; 
	if( $g_debug > 0 ) {
		echo '<div style="color: green; font-size: 28px;">******** '.$g_testNum.' TESTS SUCCESSFUL ********</div>';
		//exit(1);
	}
}

?>