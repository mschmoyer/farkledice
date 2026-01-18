#!/usr/bin/env php
<?php
/*
	sync-leaderboards.php

	Manually sync/refresh all leaderboard data.
	Useful for testing or forcing a refresh.

	Usage:
		# Local
		php scripts/sync-leaderboards.php

		# Heroku
		heroku run php scripts/sync-leaderboards.php -a farkledice
*/

// Run from command line only
if (php_sapi_name() !== 'cli') {
	die("This script must be run from the command line.\n");
}

// Change to wwwroot directory (where the PHP files are)
chdir(dirname(__DIR__) . '/wwwroot');

echo "========================================\n";
echo "Farkle Leaderboard Sync Script\n";
echo "========================================\n\n";

// Load required files
require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleLeaderboard.php');
require_once('farklePageFuncs.php');
require_once('farkleAchievements.php');

// Initialize session (required for some functions)
$_SESSION['playerid'] = 0; // System user

echo "Step 1: Refreshing main leaderboard data...\n";
echo "  - Wins/Losses rankings\n";
echo "  - Highest 10-round scores\n";
echo "  - Achievement points\n";

$result = Leaderboard_RefreshData(true); // Force refresh
if ($result) {
	echo "  ✓ Main leaderboard refresh complete\n\n";
} else {
	echo "  ✗ Main leaderboard refresh failed\n\n";
}

echo "Step 2: Refreshing daily leaderboard stats...\n";
echo "  - Yesterday's highest scores\n";
echo "  - Yesterday's most farkles\n";
echo "  - Yesterday's most wins\n";

Leaderboard_RefreshDaily();
echo "  ✓ Daily leaderboard refresh complete\n\n";

echo "Step 3: Verifying data...\n";

// Check how many entries were created
$sql = "SELECT lbindex, COUNT(*) as count FROM farkle_lbdata GROUP BY lbindex ORDER BY lbindex";
$counts = db_select_query($sql, SQL_MULTI_ROW);

if ($counts && count($counts) > 0) {
	echo "  Leaderboard entries by category:\n";
	$category_names = [
		0 => 'Yesterday\'s High Scores',
		1 => 'Yesterday\'s Farklers',
		2 => 'Yesterday\'s Winners',
		3 => 'Wins/Losses',
		4 => 'Highest 10-Round',
		5 => 'Achievement Points'
	];

	foreach ($counts as $row) {
		$category = $category_names[$row['lbindex']] ?? "Unknown ({$row['lbindex']})";
		echo "    {$category}: {$row['count']} entries\n";
	}
	echo "\n";
} else {
	echo "  ⚠ WARNING: No leaderboard data found!\n\n";
}

echo "========================================\n";
echo "Leaderboard Sync Complete!\n";
echo "========================================\n";

exit(0);
?>
