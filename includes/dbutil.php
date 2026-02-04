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
			PDO::ATTR_PERSISTENT => true,  // Enable connection pooling across requests
			PDO::ATTR_TIMEOUT => 5,         // 5 second connection timeout
		]);
		return $g_dbh;
	} catch (PDOException $e) {
		die('Error connecting to database: ' . $e->getMessage());
	}
}

// =============================================================================
// NEW PREPARED STATEMENT FUNCTIONS (use these for all new code)
// =============================================================================

/**
 * Execute a SELECT query with prepared statement
 *
 * @param string $sql SQL with named parameters (:param) or positional (?)
 * @param array $params Parameter values [':param' => value] or [value1, value2, ...]
 * @param int $return_type SQL_SINGLE_VALUE, SQL_SINGLE_ROW, or SQL_MULTI_ROW
 * @return mixed Query result based on return_type
 */
function db_query(string $sql, array $params = [], int $return_type = SQL_MULTI_ROW): mixed
{
	global $g_debug;
	BaseUtil_Debug('Executing prepared query: ' . $sql, 7, "gray");
	BaseUtil_Debug('With params: ' . json_encode($params), 7, "gray");

	if ($g_debug >= 14) $theStartTime = microtime(true);

	try {
		$dbh = db_connect();
		$stmt = $dbh->prepare($sql);
		$stmt->execute($params);

		if ($return_type == SQL_MULTI_ROW) {
			$retval = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} elseif ($return_type == SQL_SINGLE_ROW) {
			$retval = $stmt->fetch(PDO::FETCH_ASSOC);
		} else {
			// SQL_SINGLE_VALUE
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
		BaseUtil_Error(__FUNCTION__ . ": SQL Error [" . $e->getCode() . "]: " . $e->getMessage() . "   SQL = $sql   Params = " . json_encode($params));
		return null;
	}
}

/**
 * Execute an INSERT/UPDATE/DELETE with prepared statement
 *
 * @param string $sql SQL with named parameters (:param) or positional (?)
 * @param array $params Parameter values
 * @return int|false Number of affected rows, or false on error
 */
function db_execute(string $sql, array $params = []): int|false
{
	BaseUtil_Debug('Executing prepared command: ' . $sql, 7, "gray");
	BaseUtil_Debug('With params: ' . json_encode($params), 7, "gray");

	try {
		$dbh = db_connect();
		$stmt = $dbh->prepare($sql);
		$stmt->execute($params);
		$rowCount = $stmt->rowCount();
		BaseUtil_Debug("SQL result: $rowCount rows affected", 7, "gray");
		return $rowCount;
	} catch (PDOException $e) {
		BaseUtil_Error(__FUNCTION__ . ": SQL Error [" . $e->getCode() . "]: " . $e->getMessage() . "   SQL = $sql   Params = " . json_encode($params));
		return false;
	}
}

/**
 * Execute an INSERT with prepared statement and return the last inserted ID
 *
 * @param string $sql SQL with named parameters (:param) or positional (?)
 * @param array $params Parameter values
 * @param string $sequence Optional sequence name for PostgreSQL (usually not needed)
 * @return int|false The last inserted ID, or false on error
 */
function db_insert(string $sql, array $params = [], string $sequence = ''): int|false
{
	$result = db_execute($sql, $params);
	if ($result === false) {
		return false;
	}
	return db_insert_id($sequence);
}

// =============================================================================
// LEGACY FUNCTIONS (deprecated - migrate to prepared statement versions above)
// =============================================================================

/**
 * @deprecated Use db_query() with prepared statements instead
 */
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

/**
 * @deprecated Use db_execute() with prepared statements instead
 */
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

/**
 * @deprecated Use db_execute() with prepared statements instead
 */
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

function db_insert_id(string $sequence = ''): int
{
	try {
		$dbh = db_connect();
		// PostgreSQL uses sequences for auto-increment
		// This gets the last value from the most recently used sequence
		// If a specific sequence is provided, use currval() instead
		if ($sequence) {
			$result = $dbh->query("SELECT currval('$sequence')");
		} else {
			$result = $dbh->query("SELECT lastval()");
		}
		$row = $result->fetch(PDO::FETCH_NUM);
		return $row ? (int)$row[0] : 0;
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