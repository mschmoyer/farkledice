<?php
/*
	index.php

	Entry point for the application.
	Handles domain redirects and forwards to main application.
*/

// Redirect apex domain to www subdomain
if (isset($_SERVER['HTTP_HOST'])) {
	$host = $_SERVER['HTTP_HOST'];

	// Check if we're on the apex domain (without www)
	if ($host === 'farkledice.com') {
		// Build the redirect URL
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$uri = $_SERVER['REQUEST_URI'] ?? '/';

		// Redirect to www version
		$redirect_url = $protocol . '://www.farkledice.com' . $uri;

		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $redirect_url);
		exit();
	}
}

// Forward to main application
header('Location: farkle.php');

?>