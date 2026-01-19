<?php
/*
	dbutil.php

	Date		Editor		Change
	----------	----------	----------------------------
	5-May-2011	MAS			Initial version.
	17-Jan-2026	MAS			Migrated to PDO with PostgreSQL support

*/
require_once('baseutil.php');
require_once('farkleconfig.class.php');

define('SQL_SINGLE_VALUE', 0);
define('SQL_SINGLE_ROW', 1);
define('SQL_MULTI_ROW', 2);

$g_testNum = 0;
$g_testBlock = "";

// Global PDO connection to avoid reconnecting on every query
$g_dbh = null;

function db_connect()
{
	global $g_dbh;

	// Return existing connection if available
	if ($g_dbh !== null) {
		return $g_dbh;
	}

	// Check for Heroku DATABASE_URL first
	$database_url = getenv('DATABASE_URL');

	if ($database_url !== false && !empty($database_url)) {
		// Parse DATABASE_URL format: postgres://user:pass@host:port/dbname
		$url_parts = parse_url($database_url);

		$host = $url_parts['host'] ?? 'localhost';
		$port = $url_parts['port'] ?? '5432';
		$username = $url_parts['user'] ?? '';
		$password = $url_parts['pass'] ?? '';
		// Database name is in path with leading slash, so strip it
		$dbname = isset($url_parts['path']) ? ltrim($url_parts['path'], '/') : '';
	} else {
		// Fall back to config file or individual environment variables
		$config = new FarkleConfig();

		$dbname = $config->data['dbname'] ?? getenv('DB_NAME') ?? 'farkle_db';
		$username = $config->data['dbuser'] ?? getenv('DB_USER') ?? 'farkle_user';
		$password = $config->data['dbpass'] ?? getenv('DB_PASS') ?? 'farkle_pass';
		$host = $config->data['dbhost'] ?? getenv('DB_HOST') ?? 'db';
		$port = $config->data['dbport'] ?? getenv('DB_PORT') ?? '5432';
	}

	try {
		$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
		$g_dbh = new PDO($dsn, $username, $password, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		]);
		return $g_dbh;
	} catch (PDOException $e) {
		die('Error connecting to database: ' . $e->getMessage());
	}
}

function db_select_query($sql, $return_type = SQL_MULTI_ROW)
{
	global $g_debug;
	BaseUtil_Debug('Executing query: ' . $sql, 7, "gray");

	if ($g_debug >= 14) $theStartTime = microtime(true);

	try {
		$dbh = db_connect();
		$stmt = $dbh->query($sql);

		if ($return_type == SQL_MULTI_ROW) {
			// Can return many rows of data
			$retval = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} elseif ($return_type == SQL_SINGLE_ROW) {
			// Can return a single row of data
			$retval = $stmt->fetch(PDO::FETCH_ASSOC);
		} else {
			// SQL_SINGLE_VALUE -- Returns only a single value
			$row = $stmt->fetch(PDO::FETCH_NUM);
			$retval = $row ? $row[0] : null;
		}

		if ($g_debug >= 14) {
			$theEndTime = microtime(true);
			$theRunTime = (string)($theEndTime - $theStartTime);
			BaseUtil_Debug("SQL result run time: $theRunTime seconds.", 7, "#AAF");
		}

		BaseUtil_Debug('SQL result in var dump below: ', 7, "gray");
		if ($g_debug >= 14) var_dump($retval);

		return $retval;
	} catch (PDOException $e) {
		BaseUtil_Error(__FUNCTION__ . ": SQL Error [" . $e->getCode() . "]: " . $e->getMessage() . "   SQL = $sql");
	}
}

function db_command($sql)
{
	BaseUtil_Debug('Executing query: ' . $sql, 7, "gray");

	try {
		$dbh = db_connect();
		$result = $dbh->exec($sql);
		BaseUtil_Debug("SQL result: $result", 7, "gray");
		return $result;
	} catch (PDOException $e) {
		BaseUtil_Error(__FUNCTION__ . ": SQL Error [" . $e->getCode() . "]: " . $e->getMessage() . "   SQL = $sql");
	}
}

function db_insert_update_query($sql)
{
	global $g_debug;
	BaseUtil_Debug('Executing query: ' . $sql, 7, "gray");

	try {
		$dbh = db_connect();
		$result = $dbh->exec($sql);

		if ($result === false) {
			$error = $dbh->errorInfo();
			BaseUtil_Error(__FUNCTION__ . ": SQL Error [{$error[1]}]: {$error[2]}   SQL = $sql");
		}

		return $result;
	} catch (PDOException $e) {
		BaseUtil_Error(__FUNCTION__ . ": SQL Error [" . $e->getCode() . "]: " . $e->getMessage() . "   SQL = $sql");
	}
}

function db_insert_id()
{
	try {
		$dbh = db_connect();
		// PostgreSQL uses sequences for auto-increment
		// This gets the last value from the most recently used sequence
		$result = $dbh->query("SELECT lastval()");
		$row = $result->fetch(PDO::FETCH_NUM);
		return $row ? $row[0] : 0;
	} catch (PDOException $e) {
		// If no sequence has been used yet, lastval() throws an error
		// Return 0 in that case
		return 0;
	}
}

function db_escape_string($str)
{
	$dbh = db_connect();
	// PDO::quote() adds quotes around the string, so we need to remove them
	$quoted = $dbh->quote($str);
	// Remove the leading and trailing quotes
	return substr($quoted, 1, -1);
}

$link = db_connect();

// Initialize database-backed session handler for Heroku compatibility
// This must be done BEFORE session_start() is called anywhere
if (!session_id()) {
	require_once('session-handler.php');
	init_database_session_handler($link);
}
?>