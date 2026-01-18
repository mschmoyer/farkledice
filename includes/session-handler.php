<?php
/*
	session-handler.php

	Custom session handler for database-backed sessions.
	Required for Heroku deployment where filesystem is ephemeral.

	Date		Editor		Change
	----------	----------	----------------------------
	17-Jan-2026	MAS			Initial version for Heroku deployment
*/

class DatabaseSessionHandler implements SessionHandlerInterface
{
	private $dbh;
	private $maxlifetime;

	public function __construct($dbh)
	{
		$this->dbh = $dbh;
		// Get session max lifetime from php.ini or use 1 hour default
		$this->maxlifetime = ini_get('session.gc_maxlifetime') ?: 3600;
	}

	/**
	 * Open session storage
	 * @param string $save_path
	 * @param string $session_name
	 * @return bool
	 */
	public function open($save_path, $session_name): bool
	{
		// Database connection already established, nothing to do
		return true;
	}

	/**
	 * Close session storage
	 * @return bool
	 */
	public function close(): bool
	{
		// Keep database connection open for other operations
		return true;
	}

	/**
	 * Read session data
	 * @param string $session_id
	 * @return string|false
	 */
	public function read($session_id): string|false
	{
		try {
			$stmt = $this->dbh->prepare(
				"SELECT session_data FROM farkle_sessions WHERE session_id = :session_id"
			);
			$stmt->execute([':session_id' => $session_id]);

			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($row) {
				// Update last access time
				$update = $this->dbh->prepare(
					"UPDATE farkle_sessions SET last_access = CURRENT_TIMESTAMP WHERE session_id = :session_id"
				);
				$update->execute([':session_id' => $session_id]);

				return $row['session_data'];
			}

			// Return empty string if session not found
			return '';
		} catch (PDOException $e) {
			error_log("Session read error: " . $e->getMessage());
			return '';
		}
	}

	/**
	 * Write session data
	 * @param string $session_id
	 * @param string $session_data
	 * @return bool
	 */
	public function write($session_id, $session_data): bool
	{
		try {
			error_log("Session write called for ID: $session_id, data length: " . strlen($session_data));

			// Use INSERT ... ON CONFLICT to handle both insert and update
			$stmt = $this->dbh->prepare(
				"INSERT INTO farkle_sessions (session_id, session_data, last_access)
				 VALUES (:session_id, :session_data, CURRENT_TIMESTAMP)
				 ON CONFLICT (session_id)
				 DO UPDATE SET session_data = EXCLUDED.session_data,
				              last_access = CURRENT_TIMESTAMP"
			);

			$stmt->execute([
				':session_id' => $session_id,
				':session_data' => $session_data
			]);

			error_log("Session write successful");
			return true;
		} catch (PDOException $e) {
			error_log("Session write error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Destroy a session
	 * @param string $session_id
	 * @return bool
	 */
	public function destroy($session_id): bool
	{
		try {
			$stmt = $this->dbh->prepare(
				"DELETE FROM farkle_sessions WHERE session_id = :session_id"
			);
			$stmt->execute([':session_id' => $session_id]);

			return true;
		} catch (PDOException $e) {
			error_log("Session destroy error: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Garbage collection - remove old sessions
	 * @param int $maxlifetime
	 * @return int|false Number of deleted sessions
	 */
	public function gc($maxlifetime): int|false
	{
		try {
			// Delete sessions older than maxlifetime
			$stmt = $this->dbh->prepare(
				"DELETE FROM farkle_sessions
				 WHERE last_access < (CURRENT_TIMESTAMP - INTERVAL '" . (int)$maxlifetime . " seconds')"
			);
			$stmt->execute();

			return $stmt->rowCount();
		} catch (PDOException $e) {
			error_log("Session GC error: " . $e->getMessage());
			return false;
		}
	}
}

/**
 * Initialize database-backed session handler
 * Call this function before starting the session
 */
function init_database_session_handler($dbh)
{
	$handler = new DatabaseSessionHandler($dbh);
	session_set_save_handler($handler, true);
}

?>
