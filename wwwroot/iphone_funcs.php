<?php
/*
	iphone_funcs.php
	Desc: Functions related to iOS and iPhone operations including APNs push notifications.

	13-Jan-2013		mas		Updated in support of Farkle tournaments.
	25-Jan-2026		mas		Modernized to HTTP/2 APNs with JWT authentication.
*/

require_once('../includes/baseutil.php');
require_once('dbutil.php');

// APNs endpoints
define('APNS_ENDPOINT_SANDBOX', 'https://api.sandbox.push.apple.com/3/device/');
define('APNS_ENDPOINT_PRODUCTION', 'https://api.push.apple.com/3/device/');

/**
 * Get APNs configuration from environment variables or config file
 *
 * @return array|false Configuration array or false if not configured
 */
function getAPNsConfig()
{
	$config = array();

	// Try environment variables first (Heroku)
	$config['key_id'] = getenv('APNS_KEY_ID');
	$config['team_id'] = getenv('APNS_TEAM_ID');
	$config['bundle_id'] = getenv('APNS_BUNDLE_ID');
	$config['key_content'] = getenv('APNS_KEY_CONTENT');
	$config['environment'] = getenv('APNS_ENVIRONMENT') ?: 'sandbox';

	// If env vars not set, try config file
	if (empty($config['key_id']) || empty($config['team_id'])) {
		$configFile = '../configs/apns_config.ini';
		if (file_exists($configFile)) {
			$iniConfig = parse_ini_file($configFile);
			if ($iniConfig) {
				$config['key_id'] = $iniConfig['apns_key_id'] ?? '';
				$config['team_id'] = $iniConfig['apns_team_id'] ?? '';
				$config['bundle_id'] = $iniConfig['apns_bundle_id'] ?? '';
				$config['environment'] = $iniConfig['apns_environment'] ?? 'sandbox';

				// Read key from file
				$keyPath = $iniConfig['apns_key_path'] ?? '';
				if (!empty($keyPath) && file_exists($keyPath)) {
					$config['key_content'] = file_get_contents($keyPath);
				}
			}
		}
	}

	// Validate required fields
	if (empty($config['key_id']) || empty($config['team_id']) ||
		empty($config['bundle_id']) || empty($config['key_content'])) {
		BaseUtil_Debug(__FUNCTION__ . ": APNs not configured (missing key_id, team_id, bundle_id, or key_content)", 1);
		return false;
	}

	return $config;
}

/**
 * Generate JWT token for APNs authentication
 *
 * @param array $config APNs configuration
 * @return string|false JWT token or false on error
 */
function generateAPNsJWT($config)
{
	$keyId = $config['key_id'];
	$teamId = $config['team_id'];
	$keyContent = $config['key_content'];

	// JWT Header
	$header = array(
		'alg' => 'ES256',
		'kid' => $keyId
	);

	// JWT Payload
	$payload = array(
		'iss' => $teamId,
		'iat' => time()
	);

	// Encode header and payload
	$headerEncoded = base64url_encode(json_encode($header));
	$payloadEncoded = base64url_encode(json_encode($payload));

	// Create signature
	$dataToSign = $headerEncoded . '.' . $payloadEncoded;

	// Parse the private key
	$privateKey = openssl_pkey_get_private($keyContent);
	if (!$privateKey) {
		BaseUtil_Error(__FUNCTION__ . ": Failed to parse APNs private key: " . openssl_error_string());
		return false;
	}

	// Sign with ES256 (ECDSA with SHA-256)
	$signature = '';
	if (!openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
		BaseUtil_Error(__FUNCTION__ . ": Failed to sign JWT: " . openssl_error_string());
		return false;
	}

	// Convert DER signature to raw format (64 bytes: 32 for R, 32 for S)
	$signature = derToRaw($signature);
	if ($signature === false) {
		BaseUtil_Error(__FUNCTION__ . ": Failed to convert DER signature to raw format");
		return false;
	}

	$signatureEncoded = base64url_encode($signature);

	return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

/**
 * Base64 URL encode (RFC 4648)
 */
function base64url_encode($data)
{
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Convert DER encoded signature to raw format for JWT
 * APNs requires the raw R||S format, not DER
 */
function derToRaw($der)
{
	// DER format: 0x30 [total-length] 0x02 [r-length] [r] 0x02 [s-length] [s]
	$pos = 0;

	// Skip sequence tag and length
	if (ord($der[$pos++]) !== 0x30) return false;
	$totalLen = ord($der[$pos++]);
	if ($totalLen > 127) {
		$numLenBytes = $totalLen - 128;
		$pos += $numLenBytes;
	}

	// Parse R
	if (ord($der[$pos++]) !== 0x02) return false;
	$rLen = ord($der[$pos++]);
	$r = substr($der, $pos, $rLen);
	$pos += $rLen;

	// Parse S
	if (ord($der[$pos++]) !== 0x02) return false;
	$sLen = ord($der[$pos++]);
	$s = substr($der, $pos, $sLen);

	// Pad or trim to 32 bytes each
	$r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
	$s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

	// Ensure exactly 32 bytes each
	$r = substr($r, -32);
	$s = substr($s, -32);

	return $r . $s;
}

/**
 * Send APNs push notification via HTTP/2
 *
 * @param string $deviceToken Device token (hex string)
 * @param array $payload Push notification payload
 * @param array $config APNs configuration
 * @param int $badge Badge number
 * @return bool Success status
 */
function sendAPNsRequest($deviceToken, $payload, $config, $badge = 0)
{
	// Get endpoint based on environment
	$endpoint = ($config['environment'] === 'production')
		? APNS_ENDPOINT_PRODUCTION
		: APNS_ENDPOINT_SANDBOX;

	$url = $endpoint . $deviceToken;

	// Generate JWT
	$jwt = generateAPNsJWT($config);
	if (!$jwt) {
		return false;
	}

	$payloadJson = json_encode($payload);

	// Set up cURL request with HTTP/2
	$ch = curl_init($url);

	curl_setopt_array($ch, array(
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
		CURLOPT_POSTFIELDS => $payloadJson,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array(
			'Authorization: bearer ' . $jwt,
			'apns-topic: ' . $config['bundle_id'],
			'apns-push-type: alert',
			'apns-priority: 10',
			'Content-Type: application/json'
		),
		CURLOPT_TIMEOUT => 30
	));

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);

	if ($httpCode === 200) {
		BaseUtil_Debug(__FUNCTION__ . ": Push sent successfully to $deviceToken", 7);
		return true;
	} else {
		$errorMsg = __FUNCTION__ . ": Push failed. HTTP $httpCode";
		if (!empty($curlError)) {
			$errorMsg .= ", cURL error: $curlError";
		}
		if (!empty($response)) {
			$responseData = json_decode($response, true);
			if (isset($responseData['reason'])) {
				$errorMsg .= ", Reason: " . $responseData['reason'];
			}
		}
		BaseUtil_Error($errorMsg);
		return false;
	}
}

/**
 * Send push notification to one or more players
 *
 * @param string|int $playerid One or more player IDs (comma-separated string or single ID)
 * @param string $alert Alert message to display
 * @param string $sound Sound file name
 * @return int 1 on success, 0 on failure
 */
function SendPushNotification($playerid, $alert, $sound = "default")
{
	BaseUtil_Debug(__FUNCTION__ . ": Entered. Sound=$sound, Alert=$alert", 1);

	if (empty($playerid) || empty($alert)) {
		BaseUtil_Error(__FUNCTION__ . ": missing parameters.");
		return 0;
	}

	// Get APNs configuration
	$config = getAPNsConfig();
	if (!$config) {
		BaseUtil_Debug(__FUNCTION__ . ": APNs not configured, skipping push notification", 1);
		return 0;
	}

	// Query for device tokens - updated to use 'token' column instead of 'devicetoken'
	$sql = "SELECT c.playerid, d.token,
			(SELECT COUNT(*)
				FROM farkle_games a, farkle_games_players b
				WHERE a.gameid=b.gameid AND b.playerid=c.playerid AND a.winningplayer=0 AND
					((a.gamemode=2 AND b.playerRound < 11) OR (a.gamemode=1 AND a.currentplayer=b.playerid))) as unfinished_games
		FROM farkle_players c, farkle_players_devices d
		WHERE c.playerid=d.playerid AND d.device='ios_app' AND d.token IS NOT NULL AND d.token != ''";

	// Handle multiple player IDs
	if (strpos($playerid, ',') !== false) {
		$sql .= " AND c.playerid IN ($playerid)";
	} else {
		$sql .= " AND c.playerid = " . intval($playerid);
	}

	$pData = db_select_query($sql, SQL_MULTI_ROW);

	if (!$pData || count($pData) === 0) {
		BaseUtil_Debug(__FUNCTION__ . ": No registered push notification devices for player(s) $playerid", 7);
		return 0;
	}

	$successCount = 0;

	foreach ($pData as $p) {
		$token = $p['token'];
		$badgeNumber = intval($p['unfinished_games']);
		$currentPlayerId = $p['playerid'];

		// Clean token (remove any spaces or angle brackets from legacy formats)
		$token = str_replace(array('<', '>', ' '), '', $token);

		// Validate token format (should be 64 hex characters)
		if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
			BaseUtil_Debug(__FUNCTION__ . ": Invalid token format for player $currentPlayerId: $token", 7);
			continue;
		}

		BaseUtil_Debug(__FUNCTION__ . ": Sending to player $currentPlayerId, token: " . substr($token, 0, 8) . "..., badge: $badgeNumber", 1);

		// Build APNs payload
		$payload = array(
			'aps' => array(
				'alert' => $alert,
				'badge' => $badgeNumber,
				'sound' => $sound
			)
		);

		// Send push notification
		if (sendAPNsRequest($token, $payload, $config, $badgeNumber)) {
			$successCount++;
			error_log(__FUNCTION__ . ": Push notification sent to Player #$currentPlayerId");
			BaseUtil_Debug(__FUNCTION__ . ": Push notification sent to Player #$currentPlayerId. Payload=" . json_encode($payload), 7);
		}
	}

	return ($successCount > 0) ? 1 : 0;
}

?>
