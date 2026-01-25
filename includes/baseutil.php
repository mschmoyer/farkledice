<?php
/*
	baseutil.php

	Date		Editor		Change
	----------	----------	----------------------------
	5-May-2011	MAS			Initial version.
	25-Nov-2012	mas			No longer reporting errors to the screen when a page fetch is asking for JSON.

*/

	// Application Version (Major.Minor.Revision)
	// Major: Breaking changes or major milestones
	// Minor: New features and significant changes
	// Revision: Bug fixes and small tweaks
	define('APP_VERSION', '2.1.2');

	// Redirect apex domain to www subdomain (for custom domain setup)
	if (isset($_SERVER['HTTP_HOST'])) {
		$host = $_SERVER['HTTP_HOST'];

		// Check if we're on the apex domain (without www)
		if ($host === 'farkledice.com') {
			// Build the redirect URL
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
			$uri = $_SERVER['REQUEST_URI'] ?? '/';

			// Redirect to www version (301 permanent redirect)
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . $protocol . '://www.farkledice.com' . $uri);
			exit();
		}
	}

	// Enable error logging to Docker stdout/stderr
	ini_set('log_errors', '1');
	ini_set('error_log', 'php://stderr');
	ini_set('display_errors', '0');
	ini_set('error_reporting', E_ALL);

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
			// Note: Database session handler is initialized in dbutil.php
			// This ensures sessions are stored in the database for Heroku compatibility
			session_name( $sessName );

			// Only use secure cookies when actually on HTTPS
			$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			           || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
			ini_set('session.cookie_secure', $isHttps ? '1' : '0');
			ini_set('session.cookie_httponly', '1');
			ini_set('session.cookie_samesite', 'Lax');
			ini_set('session.cookie_path', '/');  // Ensure cookie works across all paths including /admin/

			if (!session_id()) {
				session_start();
			}

			// Set testserver flag after session is started
			if (!isset($_SESSION['testserver'])) {
				$_SESSION['testserver'] = 0;
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

	// Load Smarty via Composer autoloader
	require_once($dir . '/vendor/autoload.php');
	$smarty = new Smarty();

	$smarty->template_dir = $dir . "/templates/$curfolder";
	BaseUtil_Debug( "Template_dir = " . (is_array($smarty->template_dir) ? print_r($smarty->template_dir, true) : $smarty->template_dir), 31 );

	// Detect if running on Heroku (ephemeral filesystem)
	$is_heroku = (getenv('DATABASE_URL') !== false || getenv('DYNO') !== false);

	if ($is_heroku) {
		// Use /tmp on Heroku (ephemeral but writable)
		$compile_dir = '/tmp/smarty/templates_c';
		$cache_dir = '/tmp/smarty/cache';

		// Create directories if they don't exist
		if (!file_exists($compile_dir)) {
			mkdir($compile_dir, 0777, true);
		}
		if (!file_exists($cache_dir)) {
			mkdir($cache_dir, 0777, true);
		}
	} else {
		// Use existing directories locally
		$compile_dir = $dir.'/backbone/templates_c/';
		$cache_dir = $dir.'/backbone/cache/';
	}

	$smarty->compile_dir = $compile_dir;
	BaseUtil_Debug( "compile_dir = ".$smarty->compile_dir, 31 );
	$smarty->cache_dir = $cache_dir;
	BaseUtil_Debug( "cache_dir = ".$smarty->cache_dir, 31 );
	$smarty->config_dir = 	$dir.'/backbone/configs/';
	BaseUtil_Debug( "config_dir = " . (is_array($smarty->config_dir) ? print_r($smarty->config_dir, true) : $smarty->config_dir), 31 );
	
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
	$smarty->assign('mobileMode', $gMobileMode);  // PHP 8.3 fix: case-sensitive variable for template
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
	// Commented out: This was causing session to start before custom handler could be registered
	// $_SESSION['testserver'] will be set in BaseUtil_SessSet() after session starts properly
	// $_SESSION['testserver'] = 0;

	// Pass app version to templates
	$smarty->assign('app_version', APP_VERSION); 
	
	
	// set the cache_lifetime for index.tpl to 1 hour
	//$smarty->cache_lifetime = 3600;
	
	//if( !empty($g_flushcache) )
	//	$smarty->caching = 0;
	
?>
