<?php
/**
 * Admin Generate Reinvite Token Endpoint
 *
 * Generates a new reinvite token for a player, stores it with 7-day expiry,
 * and returns the reinvite URL for the admin to copy to clipboard.
 *
 * Requires adminlevel > 0 to access.
 * Accepts POST parameter: playerid (required)
 * Returns JSON: {success: true, url: "..."} or {success: false, error: "..."}
 */

require_once('../../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleLogin.php');

// Set JSON content type
header('Content-Type: application/json');

// Initialize session
Farkle_SessSet();

// Check admin access
if (!isset($_SESSION['adminlevel']) || $_SESSION['adminlevel'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
    exit();
}

// Get and validate playerid
$playerid = isset($_POST['playerid']) ? intval($_POST['playerid']) : 0;

if ($playerid <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid player ID']);
    exit();
}

// Verify the player exists
$checkSql = "SELECT playerid FROM farkle_players WHERE playerid = " . db_escape_string($playerid);
$player = db_select_query($checkSql, SQL_SINGLE_ROW);

if (!$player) {
    echo json_encode(['success' => false, 'error' => 'Player not found']);
    exit();
}

// Generate a unique 64-character hex token
$token = bin2hex(random_bytes(32));

// Calculate expiry as NOW + 7 days
// PostgreSQL interval syntax
$updateSql = "UPDATE farkle_players
              SET reinvite_token = '" . db_escape_string($token) . "',
                  reinvite_expires = NOW() + INTERVAL '7 days'
              WHERE playerid = " . db_escape_string($playerid);

$result = db_command($updateSql);

if ($result === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to generate token']);
    exit();
}

// Build the reinvite URL (use relative URL so it works on localhost and production)
$reinviteUrl = "/reinvite.php?token=" . $token;

// Return success with the URL
echo json_encode([
    'success' => true,
    'url' => $reinviteUrl
]);
?>
