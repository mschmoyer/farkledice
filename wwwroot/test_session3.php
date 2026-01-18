<?php
/*
	test_session3.php
	Minimal session test - no output before session setup
*/

// Capture any output
ob_start();

require_once('../includes/dbutil.php');

// Check if session handler was registered
$handler_registered = session_id() === '' && function_exists('init_database_session_handler');

// Now start session
session_name("FarkleOnline");
session_start();

$session_id = session_id();

// Set data
if (!isset($_SESSION['counter'])) {
	$_SESSION['counter'] = 0;
}
$_SESSION['counter']++;

// Check database
$dbh = db_connect();
$stmt = $dbh->prepare("SELECT session_id, length(session_data) as len, last_access FROM farkle_sessions WHERE session_id = :sid");
$stmt->execute([':sid' => $session_id]);
$db_session = $stmt->fetch();

// Get any buffered output (errors/warnings)
$errors = ob_get_clean();

// Now we can output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
</head>
<body>
    <h1>Minimal Session Test</h1>

    <?php if ($errors): ?>
        <div style="background: #fee; border: 1px solid #c00; padding: 10px; margin: 10px 0;">
            <strong>Errors/Warnings:</strong><br>
            <?php echo $errors; ?>
        </div>
    <?php endif; ?>

    <p><strong>Session ID:</strong> <?php echo htmlspecialchars($session_id); ?></p>
    <p><strong>Counter:</strong> <?php echo $_SESSION['counter']; ?></p>

    <?php if ($db_session): ?>
        <p style="color: green;"><strong>✓ Session found in database!</strong></p>
        <ul>
            <li>Session ID: <?php echo htmlspecialchars($db_session['session_id']); ?></li>
            <li>Data Length: <?php echo $db_session['len']; ?> bytes</li>
            <li>Last Access: <?php echo $db_session['last_access']; ?></li>
        </ul>
    <?php else: ?>
        <p style="color: red;"><strong>✗ Session NOT in database</strong></p>
    <?php endif; ?>

    <p><a href="test_session3.php">Refresh</a></p>
</body>
</html>
