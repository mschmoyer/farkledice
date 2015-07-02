var watchId = 0;

function geo_start_location_watching( success_func, error_func )
{
	watchId = navigator.geolocation.watchPosition( 
		success_func, 
		error_func, 
		{enableHighAccuracy:true, maximumAge:30000, timeout:27000} );
}

function geo_stop_location_watching()
{
	navigator.geolocation.cancelWatch( watchId );
}

