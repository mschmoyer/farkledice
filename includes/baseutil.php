<?php
/*
	baseutil.php
	
	Date		Editor		Change
	----------	----------	----------------------------
	5-May-2011	MAS			Initial version. 
	25-Nov-2012	mas			No longer reporting errors to the screen when a page fetch is asking for JSON. 

*/
	$g_debug = 0;
	$g_flushcache = 0;
	$gMobileMode = 0;
	$gTabletMode = 0;
	$g_json = 0; 
	
	$gFolder = 'wwwroot';
	
	// Debug turned off for production
	if( !empty($_REQUEST['debug']) )
		$g_debug = $_REQUEST['debug'];
	
	if( !empty($_REQUEST['flushcache']) )
		$g_flushcache = $_REQUEST['flushcache'];

	function BaseUtil_Error( $msg )
	{
		error_log( $msg );
		BaseUtil_Debug( $msg, 1, "red" );
	}
		
	function BaseUtil_Debug( $msg, $debuglevel, $color="#ff22ff" )
	{
		global $g_debug, $g_json;
		if( $g_debug >= $debuglevel || ($debuglevel == 0 && !$g_json) )
			echo '<pre style="font: monospace; color: '.$color.';">' . $msg . '</pre>';
	}
	
	function BaseUtil_SessSet( $sessName = "FarkleOnline" )
	{
		global $g_debug; 
		if(!isset($_SESSION))
		{ 
			session_name( $sessName );
			if (!session_id()) {
				session_start();
			}
		}
		if( $g_debug >= 14 ) 
		{
			//echo "Dumping Session: ";
			//var_dump($_SESSION);
		}
	}
	
	// Define the root include folder and add it to the path.
	// Also determine what folder we are in for template_dir
	$dir = getcwd();
	$curfolder = basename($dir);
	BaseUtil_Debug( "Current Folder = $curfolder, basename(dir)=".basename($dir).",dir=$dir", 31 );
	
	$basepath = "";
	
	$x = 0;
	while( basename($dir) != $gFolder && $x <= 5 ) 
	{
		$dir = dirname($dir);		
		BaseUtil_Debug( "Working dir = ".$dir, 31 );
		if( basename($dir) != $gFolder ) $curfolder = basename($dir) .'/'. $curfolder;
		//$basepath .= "../";
		$x++;
	}
	$dir = dirname($dir);	
	BaseUtil_Debug( "root dir = $dir", 31 );
	
	BaseUtil_Debug( "before - include_path = " . get_include_path(), 31 );
	set_include_path( get_include_path() . PATH_SEPARATOR . getcwd() . PATH_SEPARATOR . $dir."/includes");
	BaseUtil_Debug( "after - include_path = " . get_include_path(), 31 );
	
	if( $curfolder == $gFolder ) $curfolder = "";
	chdir( $dir . "/$gFolder" );
	
	BaseUtil_Debug( "After - Current Folder = " .getcwd() . ", basename(dir)=".basename($dir).",dir=$dir", 31 );
	
	require("../backbone/libs/Smarty.class.php");
	$smarty = new Smarty();

	$smarty->template_dir = $dir . "/templates/$curfolder";
	BaseUtil_Debug( "Template_dir = ".$smarty->template_dir, 31 );	
	$smarty->compile_dir = 	$dir.'/backbone/templates_c/';
	BaseUtil_Debug( "compile_dir = ".$smarty->compile_dir, 31 );
	$smarty->cache_dir = 	$dir.'/backbone/cache/';
	BaseUtil_Debug( "cache_dir = ".$smarty->cache_dir, 31 );
	$smarty->config_dir = 	$dir.'/backbone/configs/';
	BaseUtil_Debug( "config_dir = ".$smarty->config_dir, 31 );
	
	$smarty->assign('wwwroot', $dir . "/$gFolder");
	BaseUtil_Debug( "wwwroot = ".$dir . "/$gFolder", 31 );
	
	$smarty->assign('basepath', substr($basepath,0,-1));
	BaseUtil_Debug( "basepath = ".substr($basepath,0,-1), 31 );
	
	// Get user agent and determine mobile mode
	if( isset($_SERVER['HTTP_USER_AGENT']) )
	{
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		BaseUtil_Debug( "UserAgent = " . $userAgent, 31 );
		
		if( (stristr( $userAgent, "iPhone" ) || stristr( $userAgent, "Mobile" ) || stristr( $userAgent, "Droid" )) )
		{
			if( stristr( $userAgent, "iPad" ) ) 
			{
				$gTabletMode = 1; 
			}
			else
			{
				// This is a mobile phone. 
				$gMobileMode = 1;
			}
		}
	}
	//if( isset($_SESSION['mobilemode']) )
	//	$gMobileMode = $_SESSION['mobilemode'];
	if( !empty($_REQUEST['mobilemode']) )
		$gMobileMode = 1;
		
	if( !empty($_REQUEST['tabletmode']) )
		$gTabletMode = 1;
		
	$smarty->assign('mobilemode', $gMobileMode);
	$smarty->assign('tabletmode', $gTabletMode);	
	//$_SESSION['mobilemode'] = $gMobileMode;
	BaseUtil_Debug( "MobileMode = $gMobileMode, TabletMode = $gTabletMode", 31 );
	
	
	$smarty->caching = 0; // lifetime is per cache

	//Set the testserver variable so certain functions know whether or not to act like a test server
	/*BaseUtil_Debug( "Server name = " . $_SERVER['SERVER_NAME'], 1 );
	if( strcmp($_SERVER['SERVER_NAME'], "www.farkledice.com") == 0 )
	{
		$smarty->assign('testserver', 1 ); 
		$_SESSION['testserver'] = 1; 
	}
	else
	{*/
	$smarty->assign('testserver', 0 ); 
	$_SESSION['testserver'] = 0; 
	
	
	// set the cache_lifetime for index.tpl to 1 hour
	//$smarty->cache_lifetime = 3600;
	
	//if( !empty($g_flushcache) )
	//	$smarty->caching = 0;
	
?>
