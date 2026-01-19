
var gDebugLevel = 0; 

function FarkleParseAjaxResponse( data ) {
	farkleAlertHide();
	if( !data )	{
		// We got no response - bad. 
		//farkleAlert( 'No response from server. Please contact admin@farkledice.com' ); 
		return 0; 
	} else {
		var jsonData = farkleParseJSON( data );
		if( jsonData.Error ) {
			farkleAlert( jsonData.Error ); 
			return 0; 
		}
		if( jsonData.LoginRequired ) {
			ShowLogin();
			return 0; 
		}
		return jsonData; 
	}
}

// Farkle-specific wrapper function
function FarkleAjaxCall( func, params ) {
	AjaxCallPost( gAjaxUrl, func, params); 
}

function farkleParseJSON( data ) {
	//eval( "(" + data + ")" )
	var newData; 
	
	// This should remove any non-JSON characters before the first bracket
	var firstBracket = Math.min(data.indexOf( '{' ), data.indexOf( '[' )); 
	if( firstBracket > -1 ) {
		newData = data.substr( firstBracket, data.length );
		var debugData = data.substr( 0, firstBracket ); 
		ConsoleDebug( "First bracket at string index "+firstBracket+", Bad JSON: " + debugData ); 
	} else {
		newData = data; 
	}

	return jQuery.parseJSON( newData );  
}

// "Include" a javascript file
function addJavascript(jsname,pos) {
	var th = document.getElementsByTagName(pos)[0];
	var s = document.createElement('script');
	s.setAttribute('type','text/javascript');
	s.setAttribute('src',jsname);
	th.appendChild(s);
} 

function ConsoleError( message ) {

	if( playerid == 1 ) {
		console.error( message );
	}
}

function ConsoleDebug( message )
{
	// Only print debug for player 1 (mike) 
	if( playerid == 1 )
		console.log( message ); 
}

function ParseForSQLError( data )
{
	var newData = data;
	if( data.charAt(0) !== '{' )
	{
		// We have an error. Let's strip it and print it. 
		var endOfError = Math.min( data.indexOf('{'), data.indexOf('[') ); 
		var errorString = data.substring( 0, endOfError ); // Make sure it includes all of the pre tag. 
		ConsoleDebug( errorString ); 
		newData = data.substring( endOfError, data.length );
		
	}
	ConsoleDebug( 'Returning Parsed responseData: '+newData ); 
	return newData; 
}

function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function addCommas(nStr)
{
	if( !isNumber(nStr) ) return nStr;
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

Date.prototype.getMonthName = function(lang) {
    lang = lang && (lang in Date.locale) ? lang : 'en';
    return Date.locale[lang].month_names[this.getMonth()];
};

Date.prototype.getMonthNameShort = function(lang) {
    lang = lang && (lang in Date.locale) ? lang : 'en';
    return Date.locale[lang].month_names_short[this.getMonth()];
};

Date.locale = {
    en: {
       month_names: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
       month_names_short: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    }
};

function updateOrientation()
{  
    var newWidth = "320px";  
    switch(window.orientation)
	{  
        case 0:  
        newWidth = "320px"; 
        break;  
  
        case -90:  
        newWidth = "480px"; 
        break;  
  
        case 90:  
        newWidth = "480px";  
        break;  
  
        case 180:  
        newWidth = "320px";  
        break;  
    }  
	$("#divGlobalBody").css('width', newWidth);
} 

function validateEmail(email) { 
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
} 