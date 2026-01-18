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

echo "========================================\n";
echo "Farkle Database Migration Script\n";
echo "========================================\n\n";

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
		echo "Database: {$dbname}\n\n";
	} else {
		// Use local environment variables or config file
		if (getenv('DB_HOST')) {
			$host = getenv('DB_HOST');
			$port = getenv('DB_PORT') ?: 5432;
			$dbname = getenv('DB_NAME');
			$username = getenv('DB_USER');
			$password = getenv('DB_PASS');
			echo "Environment: Local (environment variables)\n";
		} else {
			// Read from config file
			$config_file = '../configs/siteconfig.ini';
			if (!file_exists($config_file)) {
				die("ERROR: Config file not found and no DATABASE_URL set\n");
			}
			$config = parse_ini_file($config_file);
			$host = $config['dbhost'];
			$port = $config['dbport'] ?? 5432;
			$dbname = $config['dbname'];
			$username = $config['dbuser'];
			$password = $config['dbpass'];
			echo "Environment: Local (config file)\n";
		}
		echo "Database: {$dbname}\n\n";
	}

	// Create PDO connection
	try {
		$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
		$pdo = new PDO($dsn, $username, $password, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]);
		return $pdo;
	} catch (PDOException $e) {
		die("ERROR: Database connection failed: " . $e->getMessage() . "\n");
	}
}

$dbh = get_db_connection();

// Function to execute SQL file
function execute_sql_file($dbh, $sql_file) {
	if (!file_exists($sql_file)) {
		echo "WARNING: SQL file not found: {$sql_file}\n";
		return ['success' => 0, 'errors' => 0];
	}

	echo "\nReading SQL file: {$sql_file}\n";
	$sql_content = file_get_contents($sql_file);
	if ($sql_content === false) {
		echo "ERROR: Failed to read SQL file\n";
		return ['success' => 0, 'errors' => 1];
	}

	echo "SQL file loaded successfully (" . strlen($sql_content) . " bytes)\n";

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

	echo "Found " . count($statements) . " SQL statements\n";

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
		} elseif (stripos($statement, 'ALTER TABLE') !== false) {
			preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches);
			$statement_type = 'ALTER TABLE ' . ($matches[1] ?? '');
		} elseif (stripos($statement, 'DO $$') !== false) {
			$statement_type = 'DO BLOCK (migration)';
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

	echo "----------------------------------------\n";

	return ['success' => $success_count, 'errors' => $error_count];
}

// Execute main schema file
echo "Step 1: Executing base schema (init.sql)\n";
$result1 = execute_sql_file($dbh, 'docker/init.sql');

// Execute migration file
echo "\nStep 2: Executing schema migrations (migrate-schema.sql)\n";
$result2 = execute_sql_file($dbh, 'docker/migrate-schema.sql');

// Summary
$total_success = $result1['success'] + $result2['success'];
$total_errors = $result1['errors'] + $result2['errors'];

echo "\n========================================\n";
echo "Migration Summary\n";
echo "========================================\n";
echo "Successful statements: {$total_success}\n";
echo "Errors: {$total_errors}\n\n";

if ($total_errors === 0) {
	echo "Migration completed successfully!\n";
	exit(0);
} else {
	echo "Migration completed with errors.\n";
	echo "Please review the errors above.\n";
	exit(1);
}
?>
