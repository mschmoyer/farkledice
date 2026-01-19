#!/usr/bin/env php
<?php
/*
	link_bot_players.php

	Links existing bot player accounts to their matching AI personalities.
	Creates missing bot player accounts for personalities without players.

	Usage:
		# Local
		php scripts/link_bot_players.php

		# Heroku
		heroku run php scripts/link_bot_players.php -a farkledice
*/

// Run from command line only
if (php_sapi_name() !== 'cli') {
	die("This script must be run from the command line.\n");
}

// Change to wwwroot directory (where the PHP files are)
chdir(dirname(__DIR__) . '/wwwroot');

echo "========================================\n";
echo "Bot Player Linking Script\n";
echo "========================================\n\n";

// Load required files
require_once('../includes/baseutil.php');
require_once('dbutil.php');

// Get database connection
$dbh = db_connect();
if (!$dbh) {
	die("ERROR: Could not connect to database.\n");
}

// ================================================================
// Step 1: Link existing bot players to matching personalities
// ================================================================

echo "Step 1: Linking existing bot players to personalities...\n\n";

$linked = 0;
$notfound = 0;

// Get all bot players
$sql = "SELECT playerid, username FROM farkle_players WHERE is_bot = true ORDER BY username";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$botPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($botPlayers as $bot) {
	// Find matching personality by name
	$sql = "SELECT personality_id FROM farkle_bot_personalities WHERE name = :name";
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':name' => $bot['username']]);
	$personality = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($personality) {
		// Link bot player to personality
		$sql = "UPDATE farkle_players SET personality_id = :personality_id WHERE playerid = :playerid";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':personality_id' => $personality['personality_id'],
			':playerid' => $bot['playerid']
		]);
		echo "  [LINK] {$bot['username']} (playerid {$bot['playerid']}) → personality_id {$personality['personality_id']}\n";
		$linked++;
	} else {
		echo "  [WARN] No personality found for bot: {$bot['username']}\n";
		$notfound++;
	}
}

echo "\n";
echo "  Linked: {$linked} bot players\n";
echo "  Not found: {$notfound} personalities\n\n";

// ================================================================
// Step 2: Create missing bot players for personalities
// ================================================================

echo "Step 2: Creating missing bot players for personalities...\n\n";

$created = 0;

// Get all personalities
$sql = "SELECT personality_id, name, difficulty FROM farkle_bot_personalities ORDER BY difficulty, name";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$personalities = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($personalities as $p) {
	// Check if bot player exists for this personality
	$sql = "SELECT playerid FROM farkle_players WHERE personality_id = :personality_id";
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':personality_id' => $p['personality_id']]);
	$existing = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($existing) {
		echo "  [SKIP] {$p['name']} - player already exists\n";
		continue;
	}

	// Create bot player account
	// Map difficulty to legacy bot_algorithm for compatibility
	$botAlgorithm = $p['difficulty']; // 'easy', 'medium', or 'hard'

	$sql = "INSERT INTO farkle_players
		(username, password, is_bot, bot_algorithm, personality_id, level, xp, wins, losses, games_played, totalpoints, farkles, highest10round, active)
		VALUES
		(:username, '', true, :bot_algorithm, :personality_id, 1, 0, 0, 0, 0, 0, 0, 0, true)
		RETURNING playerid";

	try {
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':username' => $p['name'],
			':bot_algorithm' => $botAlgorithm,
			':personality_id' => $p['personality_id']
		]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$playerId = $result['playerid'];

		echo "  [CREATE] {$p['name']} ({$p['difficulty']}) - playerid {$playerId}\n";
		$created++;
	} catch (PDOException $e) {
		echo "  [ERROR] Failed to create bot player for {$p['name']}: " . $e->getMessage() . "\n";
	}
}

echo "\n";
echo "  Created: {$created} new bot players\n\n";

// ================================================================
// Step 3: Verification
// ================================================================

echo "========================================\n";
echo "Verification\n";
echo "========================================\n\n";

// Check each personality has a bot player
$sql = "SELECT p.personality_id, p.name, p.difficulty, fp.playerid, fp.username
	FROM farkle_bot_personalities p
	LEFT JOIN farkle_players fp ON fp.personality_id = p.personality_id
	ORDER BY p.difficulty, p.name";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$complete = 0;
$missing = 0;

echo "Bot Player → Personality Links:\n\n";
foreach ($results as $row) {
	if ($row['playerid']) {
		echo "  ✓ {$row['name']} ({$row['difficulty']}) → playerid {$row['playerid']}\n";
		$complete++;
	} else {
		echo "  ✗ {$row['name']} ({$row['difficulty']}) → NO PLAYER\n";
		$missing++;
	}
}

echo "\n";
echo "  Complete: {$complete} personalities with players\n";
echo "  Missing: {$missing} personalities without players\n";

if ($missing > 0) {
	echo "\n  WARNING: Some personalities are missing bot players!\n";
}

echo "\n========================================\n";
echo "Linking Complete!\n";
echo "========================================\n\n";

exit(0);
?>
