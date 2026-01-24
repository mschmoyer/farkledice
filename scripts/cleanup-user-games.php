#!/usr/bin/env php
<?php
/*
	cleanup-user-games.php

	Forfeits all in-progress games for a user and marks them as acknowledged.
	Useful for cleaning up a user's game state in local development.

	Usage:
		php scripts/cleanup-user-games.php <username>

	Example:
		php scripts/cleanup-user-games.php mschmoyer

	Date		Editor		Change
	----------	----------	----------------------------
	19-Jan-2026	MAS			Initial version
*/

// Run from command line only
if (php_sapi_name() !== 'cli') {
	die("This script must be run from the command line.\n");
}

// Check for username argument
if ($argc < 2) {
	echo "Usage: php scripts/cleanup-user-games.php <username>\n";
	echo "Example: php scripts/cleanup-user-games.php mschmoyer\n";
	exit(1);
}

$username = $argv[1];

// Change to project root directory (script is in scripts/ subdirectory)
chdir(dirname(__DIR__));

echo "========================================\n";
echo "Farkle User Game Cleanup Script\n";
echo "========================================\n\n";
echo "Target user: {$username}\n\n";

// Database connection - standalone (no web dependencies)
function get_db_connection() {
	// Check for Heroku DATABASE_URL first
	$database_url = getenv('DATABASE_URL');

	if ($database_url !== false && !empty($database_url)) {
		// Parse Heroku DATABASE_URL
		$url = parse_url($database_url);
		$host = $url['host'];
		$port = isset($url['port']) ? $url['port'] : 5432;
		$dbname = ltrim($url['path'], '/');
		$username = $url['user'];
		$password = $url['pass'];

		echo "Environment: Heroku (DATABASE_URL detected)\n";
	} else {
		// Use local environment variables or config file
		if (getenv('DB_HOST')) {
			$host = getenv('DB_HOST');
			$port = getenv('DB_PORT') ?: 5432;
			$dbname = getenv('DB_NAME');
			$username = getenv('DB_USER');
			$password = getenv('DB_PASS');
			echo "Environment: Local (env vars)\n";
		} elseif (file_exists('configs/siteconfig.ini')) {
			$config = parse_ini_file('configs/siteconfig.ini');
			$host = $config['db_host'] ?? 'localhost';
			$port = $config['db_port'] ?? 5432;
			$dbname = $config['db_name'] ?? 'farkle_db';
			$username = $config['db_user'] ?? 'farkle_user';
			$password = $config['db_pass'] ?? '';
			echo "Environment: Local (config file)\n";
		} else {
			die("ERROR: No database configuration found.\n");
		}
	}

	echo "Database: {$dbname}\n\n";

	try {
		$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
		$pdo = new PDO($dsn, $username, $password, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]);
		return $pdo;
	} catch (PDOException $e) {
		die("Database connection failed: " . $e->getMessage() . "\n");
	}
}

// Connect to database
$pdo = get_db_connection();

// Get player ID for username
$stmt = $pdo->prepare("SELECT playerid FROM farkle_players WHERE username = ?");
$stmt->execute([$username]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
	die("ERROR: User '{$username}' not found.\n");
}

$playerid = $player['playerid'];
echo "Found player: {$username} (ID: {$playerid})\n\n";

// Count in-progress games
$stmt = $pdo->prepare("
	SELECT COUNT(*) as count
	FROM farkle_games g
	JOIN farkle_games_players gp ON g.gameid = gp.gameid
	WHERE gp.playerid = ? AND (g.winningplayer = 0 OR g.winningplayer IS NULL)
");
$stmt->execute([$playerid]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$inProgressCount = $result['count'];

echo "In-progress games found: {$inProgressCount}\n";

if ($inProgressCount > 0) {
	// Forfeit all in-progress games
	// If playerarray has format [playerid,opponent] or [opponent,playerid], set opponent as winner
	// For solo games, set winner to -1
	$stmt = $pdo->prepare("
		UPDATE farkle_games
		SET winningplayer = CASE
			WHEN playerarray ~ ('\\[' || ? || ',') THEN
				CAST(regexp_replace(playerarray, '\\[' || ? || ',([0-9]+)\\]', '\\1') AS INTEGER)
			WHEN playerarray ~ (',' || ? || '\\]') THEN
				CAST(regexp_replace(playerarray, '\\[([0-9]+),' || ? || '\\]', '\\1') AS INTEGER)
			ELSE -1
		END,
		gamefinish = NOW(),
		winningreason = 'Forfeited (cleanup script)'
		WHERE gameid IN (
			SELECT g.gameid
			FROM farkle_games g
			JOIN farkle_games_players gp ON g.gameid = gp.gameid
			WHERE gp.playerid = ? AND (g.winningplayer = 0 OR g.winningplayer IS NULL)
		)
	");
	$stmt->execute([$playerid, $playerid, $playerid, $playerid, $playerid]);
	$forfeitedCount = $stmt->rowCount();
	echo "Games forfeited: {$forfeitedCount}\n";
}

// Mark all finished games as acknowledged
$stmt = $pdo->prepare("
	UPDATE farkle_games_players
	SET winacknowledged = true
	WHERE playerid = ?
	AND gameid IN (SELECT gameid FROM farkle_games WHERE winningplayer != 0 AND winningplayer IS NOT NULL)
	AND winacknowledged = false
");
$stmt->execute([$playerid]);
$acknowledgedCount = $stmt->rowCount();

echo "Games marked as acknowledged: {$acknowledgedCount}\n";

echo "\n========================================\n";
echo "Cleanup complete for {$username}!\n";
echo "========================================\n";
