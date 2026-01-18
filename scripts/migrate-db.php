#!/usr/bin/env php
<?php
/*
	migrate-db.php

	Database migration script for Heroku PostgreSQL setup.
	Reads and executes the schema from docker/init.sql.

	Usage:
		php scripts/migrate-db.php

	Date		Editor		Change
	----------	----------	----------------------------
	17-Jan-2026	MAS			Initial version for Heroku deployment
*/

// Run from command line only
if (php_sapi_name() !== 'cli') {
	die("This script must be run from the command line.\n");
}

// Change to project root directory (script is in scripts/ subdirectory)
chdir(dirname(__DIR__));

// Include database utilities
require_once('includes/dbutil.php');

echo "========================================\n";
echo "Farkle Database Migration Script\n";
echo "========================================\n\n";

// Check which environment we're running in
$database_url = getenv('DATABASE_URL');
if ($database_url !== false && !empty($database_url)) {
	$url_parts = parse_url($database_url);
	$dbname = isset($url_parts['path']) ? ltrim($url_parts['path'], '/') : 'unknown';
	echo "Environment: Heroku (DATABASE_URL detected)\n";
	echo "Database: {$dbname}\n\n";
} else {
	echo "Environment: Local (using config file)\n";
	$config = new FarkleConfig();
	$dbname = $config->data['dbname'] ?? 'farkle_db';
	echo "Database: {$dbname}\n\n";
}

// Read the SQL file
$sql_file = 'docker/init.sql';
if (!file_exists($sql_file)) {
	die("ERROR: SQL file not found: {$sql_file}\n");
}

echo "Reading SQL file: {$sql_file}\n";
$sql_content = file_get_contents($sql_file);
if ($sql_content === false) {
	die("ERROR: Failed to read SQL file\n");
}

echo "SQL file loaded successfully (" . strlen($sql_content) . " bytes)\n\n";

// Get database connection
echo "Connecting to database...\n";
try {
	$dbh = db_connect();
	echo "Connected successfully\n\n";
} catch (Exception $e) {
	die("ERROR: Failed to connect to database: " . $e->getMessage() . "\n");
}

// Split SQL into individual statements
// We need to be careful with multi-line statements and comments
echo "Parsing SQL statements...\n";
$statements = [];
$current_statement = '';
$lines = explode("\n", $sql_content);

foreach ($lines as $line) {
	// Remove inline comments (anything after -- on a line)
	$comment_pos = strpos($line, '--');
	if ($comment_pos !== false) {
		$line = substr($line, 0, $comment_pos);
	}

	$line = trim($line);

	// Skip empty lines
	if (empty($line)) {
		continue;
	}

	// Add line to current statement
	$current_statement .= $line . ' ';

	// Check if statement is complete (ends with semicolon)
	if (substr(rtrim($line), -1) === ';') {
		$statements[] = trim($current_statement);
		$current_statement = '';
	}
}

// Add any remaining statement
if (!empty(trim($current_statement))) {
	$statements[] = trim($current_statement);
}

echo "Found " . count($statements) . " SQL statements\n\n";

// Execute statements
$success_count = 0;
$error_count = 0;

echo "Executing SQL statements...\n";
echo "----------------------------------------\n";

foreach ($statements as $index => $statement) {
	$statement_num = $index + 1;

	// Determine statement type for better output
	$statement_type = 'UNKNOWN';
	if (stripos($statement, 'CREATE TABLE') !== false) {
		preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $matches);
		$statement_type = 'CREATE TABLE ' . ($matches[1] ?? '');
	} elseif (stripos($statement, 'CREATE INDEX') !== false) {
		preg_match('/CREATE INDEX.*?(\w+)/i', $statement, $matches);
		$statement_type = 'CREATE INDEX ' . ($matches[1] ?? '');
	} elseif (stripos($statement, 'CREATE TYPE') !== false) {
		preg_match('/CREATE TYPE\s+(\w+)/i', $statement, $matches);
		$statement_type = 'CREATE TYPE ' . ($matches[1] ?? '');
	} elseif (stripos($statement, 'INSERT INTO') !== false) {
		preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches);
		$statement_type = 'INSERT INTO ' . ($matches[1] ?? '');
	}

	echo "[{$statement_num}/" . count($statements) . "] {$statement_type}... ";

	try {
		$dbh->exec($statement);
		echo "OK\n";
		$success_count++;
	} catch (PDOException $e) {
		$error_msg = $e->getMessage();

		// Check if error is benign (e.g., "already exists")
		if (stripos($error_msg, 'already exists') !== false) {
			echo "SKIPPED (already exists)\n";
			$success_count++;
		} elseif (stripos($error_msg, 'duplicate key') !== false) {
			echo "SKIPPED (duplicate)\n";
			$success_count++;
		} else {
			echo "FAILED\n";
			echo "   Error: " . $error_msg . "\n";
			$error_count++;

			// Show first 100 chars of statement for debugging
			$preview = substr($statement, 0, 100);
			if (strlen($statement) > 100) {
				$preview .= '...';
			}
			echo "   Statement: " . $preview . "\n";
		}
	}
}

echo "----------------------------------------\n\n";

// Summary
echo "========================================\n";
echo "Migration Summary\n";
echo "========================================\n";
echo "Total statements: " . count($statements) . "\n";
echo "Successful: {$success_count}\n";
echo "Errors: {$error_count}\n\n";

if ($error_count === 0) {
	echo "Migration completed successfully!\n";
	exit(0);
} else {
	echo "Migration completed with errors.\n";
	echo "Please review the errors above.\n";
	exit(1);
}
?>
