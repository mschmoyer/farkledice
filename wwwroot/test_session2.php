<?php
/*
	test_session2.php
	Direct session test without baseutil
*/

require_once('../includes/dbutil.php');
require_once('../includes/session-handler.php');

echo "<h1>Direct Session Test</h1>";

// Set up handler before session starts
$dbh = db_connect();
init_database_session_handler($dbh);

echo "<p>Session handler registered</p>";

// Start session
session_name("FarkleOnline");
session_start();

echo "<p>session_start() called</p>";
echo "<p>Session ID: " . session_id() . "</p>";

// Set data
if (!isset($_SESSION['counter'])) {
	$_SESSION['counter'] = 0;
}
$_SESSION['counter']++;

echo "<p>Counter: " . $_SESSION['counter'] . "</p>";

// Check database
$stmt = $dbh->prepare("SELECT session_id, length(session_data) as len, last_access FROM farkle_sessions");
$stmt->execute();
$sessions = $stmt->fetchAll();

echo "<p>Sessions in database: " . count($sessions) . "</p>";
foreach ($sessions as $sess) {
	echo "<p>- ID: " . htmlspecialchars($sess['session_id']) . ", Length: " . $sess['len'] . ", Access: " . $sess['last_access'] . "</p>";
}
?>
