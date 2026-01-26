<?php
/**
 * Test script for APNs push notifications
 *
 * Usage:
 * 1. Configure APNs in configs/apns_config.ini or environment variables
 * 2. Run: php test_push.php [playerid]
 *
 * This script tests the push notification system by sending a test message
 * to the specified player (or player ID 1 by default).
 */

require_once('../farkleGameFuncs.php');
require_once('../iphone_funcs.php');

// Disable session for CLI usage
if (php_sapi_name() === 'cli') {
    echo "=== APNs Push Notification Test ===\n\n";

    // Get player ID from command line or default to 1
    $playerId = isset($argv[1]) ? intval($argv[1]) : 1;

    echo "Testing push notification to player ID: $playerId\n\n";

    // Test 1: Check APNs configuration
    echo "1. Checking APNs configuration...\n";
    $config = getAPNsConfig();
    if ($config) {
        echo "   [OK] Configuration loaded\n";
        echo "   - Key ID: " . substr($config['key_id'], 0, 4) . "...\n";
        echo "   - Team ID: " . substr($config['team_id'], 0, 4) . "...\n";
        echo "   - Bundle ID: {$config['bundle_id']}\n";
        echo "   - Environment: {$config['environment']}\n";
    } else {
        echo "   [FAIL] APNs not configured\n";
        echo "   Please configure APNs in configs/apns_config.ini or set environment variables\n";
        exit(1);
    }

    // Test 2: Check if player has registered device
    echo "\n2. Checking for registered devices...\n";
    $sql = "SELECT playerid, device, token, lastused FROM farkle_players_devices
            WHERE playerid = $playerId AND device = 'ios_app' AND token IS NOT NULL";
    $devices = db_select_query($sql, SQL_MULTI_ROW);

    if ($devices && count($devices) > 0) {
        echo "   [OK] Found " . count($devices) . " device(s)\n";
        foreach ($devices as $device) {
            echo "   - Token: " . substr($device['token'], 0, 16) . "...\n";
            echo "     Last used: {$device['lastused']}\n";
        }
    } else {
        echo "   [WARN] No iOS devices registered for player $playerId\n";
        echo "   Run the iOS app and log in to register a device token.\n";
        exit(0);
    }

    // Test 3: Generate JWT
    echo "\n3. Testing JWT generation...\n";
    $jwt = generateAPNsJWT($config);
    if ($jwt) {
        echo "   [OK] JWT generated successfully\n";
        echo "   - Length: " . strlen($jwt) . " characters\n";
    } else {
        echo "   [FAIL] JWT generation failed\n";
        exit(1);
    }

    // Test 4: Send test push notification
    echo "\n4. Sending test push notification...\n";
    $testMessage = "Test notification from Farkle server at " . date('Y-m-d H:i:s');

    $result = SendPushNotification($playerId, $testMessage);

    if ($result) {
        echo "   [OK] Push notification sent successfully!\n";
        echo "   Message: $testMessage\n";
    } else {
        echo "   [FAIL] Push notification failed\n";
        echo "   Check error logs for details.\n";
        exit(1);
    }

    echo "\n=== Test Complete ===\n";
    exit(0);
}

// Web access - require admin login
BaseUtil_SessSet();

if (!isset($_SESSION['playerid']) || !isAdmin($_SESSION['playerid'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

$playerId = isset($_GET['playerid']) ? intval($_GET['playerid']) : $_SESSION['playerid'];
$message = isset($_GET['message']) ? $_GET['message'] : "Test notification from Farkle";

$result = SendPushNotification($playerId, $message);

echo json_encode([
    'success' => $result == 1,
    'playerid' => $playerId,
    'message' => $message
]);
