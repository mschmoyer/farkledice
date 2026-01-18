<?php
/*
	test_session.php

	Quick test to verify database-backed sessions are working
*/

require_once('../includes/baseutil.php');
require_once('dbutil.php');

// Check if db_connect function exists
echo "<p>db_connect function exists: " . (function_exists('db_connect') ? 'YES' : 'NO') . "</p>";

// Initialize session
echo "<p>Calling BaseUtil_SessSet()...</p>";
BaseUtil_SessSet();
echo "<p>session_id() = " . session_id() . "</p>";
echo "<p>isset(\$_SESSION) = " . (isset($_SESSION) ? 'YES' : 'NO') . "</p>";

// Set a test value
if (!isset($_SESSION['test_counter'])) {
	$_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;
$_SESSION['last_visit'] = date('Y-m-d H:i:s');

echo "<h1>Session Test</h1>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Visit Counter: " . $_SESSION['test_counter'] . "</p>";
echo "<p>Last Visit: " . $_SESSION['last_visit'] . "</p>";

// Check database
$dbh = db_connect();
$stmt = $dbh->prepare("SELECT COUNT(*) as count FROM farkle_sessions");
$stmt->execute();
$result = $stmt->fetch();

echo "<p>Total sessions in database: " . $result['count'] . "</p>";

// Show this session's data
$stmt = $dbh->prepare("SELECT session_id, length(session_data) as data_length, last_access FROM farkle_sessions WHERE session_id = :session_id");
$stmt->execute([':session_id' => session_id()]);
$session = $stmt->fetch();

if ($session) {
	echo "<p>Current session found in database:</p>";
	echo "<ul>";
	echo "<li>Session ID: " . htmlspecialchars($session['session_id']) . "</li>";
	echo "<li>Data Length: " . $session['data_length'] . " bytes</li>";
	echo "<li>Last Access: " . $session['last_access'] . "</li>";
	echo "</ul>";
} else {
	echo "<p style='color: red;'>Current session NOT found in database!</p>";
}

echo "<p><a href='test_session.php'>Refresh Page</a></p>";
?>
