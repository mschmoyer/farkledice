
var ajaxrequest;
var ajaxfunc;

var ajaxrequest2;
var ajaxfunc2;

var AJAX_READYSTATE_UNINITIALIZED 	= 1; // Has not started loading yet
var AJAX_READYSTATE_LOADING 		= 2; // Is loading
var AJAX_READYSTATE_INTERACTIVE 	= 3; // Has loaded enough and the user can interact with it
var AJAX_READYSTATE_COMPLETE 		= 4; // Fully loaded

var HTTP_STATUS_OK 					= 200;
var HTTP_STATUS_NOT_FOUND 			= 404;

var xmlHttpTimeout;

// This function will execute an ajax call
/*function AjaxCall( url, myFunc )
{
	ajaxrequest = new XMLHttpRequest();
	ajaxfunc = myFunc;
	ajaxrequest.onreadystatechange = AjaxResponse;
	ajaxrequest.open( "GET", url, true );
	ajaxrequest.send();
}*/

/* This is a fix to prevent any old or new javascript combination from trying to load
   "wwwroot/wwwroot/farkle_fetch.php" */
function AjaxFix_wwwroot( url )
{
	var newUrl = url;
	var path = String(document.location.pathname);
	if( path.indexOf( 'wwwroot' ) > 0 )
	{
		newUrl = newUrl.replace( 'wwwroot/', '' ); 
	}
	return newUrl; 
}

function AjaxCallPost( url, myFunc, params )
{
	ajaxrequest = new XMLHttpRequest();
	ajaxfunc = myFunc;
	ajaxrequest.onreadystatechange = AjaxResponse;
	
	ajaxrequest.open( "POST", AjaxFix_wwwroot( url ), true );
	//ajaxrequest.open( "GET", url + '?' + params, true );
	ajaxrequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	
	ajaxrequest.send(params);
}

// This will call the user defined function (like a Hook() function you write)
function AjaxResponse( )
{
	if ( ajaxrequest.readyState == AJAX_READYSTATE_COMPLETE && ajaxrequest.status == HTTP_STATUS_OK )
    {
		ajaxfunc.call();
    }
}

function AjaxCallPost2( url, myFunc, params )
{
	ajaxrequest2 = new XMLHttpRequest();
	ajaxfunc2 = myFunc;
	ajaxrequest2.onreadystatechange = AjaxResponse2;
	ajaxrequest2.open( "POST", AjaxFix_wwwroot( url ), true );
	//ajaxrequest.open( "GET", url + '?' + params, true );
	ajaxrequest2.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	
	ajaxrequest2.send(params);
}


// This will call the user defined function (like a Hook() function you write)
function AjaxResponse2( )
{
	if ( ajaxrequest2.readyState == AJAX_READYSTATE_COMPLETE && ajaxrequest2.status == HTTP_STATUS_OK )
    {
		ajaxfunc2.call();
    }
}