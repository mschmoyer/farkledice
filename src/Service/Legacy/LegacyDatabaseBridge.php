<?php

namespace App\Service\Legacy;

use PDO;

/**
 * Bridge service that wraps the legacy database connection.
 * Allows Symfony services to use the existing db_connect() function
 * and share the PDO connection with legacy code.
 */
class LegacyDatabaseBridge
{
    private string $projectDir;
    private ?PDO $connection = null;
    private bool $legacyLoaded = false;

    // SQL return type constants (match legacy dbutil.php)
    public const SQL_SINGLE_VALUE = 0;
    public const SQL_SINGLE_ROW = 1;
    public const SQL_MULTI_ROW = 2;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Load the legacy dbutil.php file if not already loaded.
     * Note: This should only be called when legacy functions are needed,
     * not just for database connection.
     */
    private function loadLegacy(): void
    {
        if ($this->legacyLoaded) {
            return;
        }

        // Save current directory
        $originalCwd = getcwd();

        // Change to project root so legacy code can find its paths
        chdir($this->projectDir);

        $dbutilPath = $this->projectDir . '/includes/dbutil.php';
        if (!is_file($dbutilPath)) {
            chdir($originalCwd);
            throw new \RuntimeException("Legacy dbutil.php not found at: {$dbutilPath}");
        }

        // The legacy file requires farkleconfig.class.php
        $configPath = $this->projectDir . '/includes/farkleconfig.class.php';
        if (is_file($configPath) && !class_exists('FarkleConfig', false)) {
            require_once $configPath;
        }

        require_once $dbutilPath;
        $this->legacyLoaded = true;

        // Restore original directory
        chdir($originalCwd);
    }

    /**
     * Get the PDO database connection.
     * Creates a direct connection without loading legacy code to avoid chdir issues.
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    /**
     * Create a PDO connection using environment variables.
     * This mirrors the logic in dbutil.php but without the legacy dependencies.
     */
    private function createConnection(): PDO
    {
        // Check for DATABASE_URL first (Heroku/Symfony format)
        $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

        if ($databaseUrl && !empty($databaseUrl)) {
            // Parse DATABASE_URL format: postgresql://user:pass@host:port/dbname
            $urlParts = parse_url($databaseUrl);

            $host = $urlParts['host'] ?? 'localhost';
            $port = $urlParts['port'] ?? '5432';
            $username = $urlParts['user'] ?? '';
            $password = $urlParts['pass'] ?? '';
            $dbname = isset($urlParts['path']) ? ltrim($urlParts['path'], '/') : '';

            // Remove query string from dbname if present
            if (str_contains($dbname, '?')) {
                $dbname = explode('?', $dbname)[0];
            }
        } else {
            // Fall back to individual environment variables
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'db';
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '5432';
            $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'farkle_user';
            $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? 'farkle_pass';
            $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'farkle_db';
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Execute a SELECT query using the legacy helper.
     *
     * @param string $sql The SQL query to execute
     * @param int $returnType One of SQL_SINGLE_VALUE, SQL_SINGLE_ROW, SQL_MULTI_ROW
     * @return mixed Query results
     */
    public function selectQuery(string $sql, int $returnType = self::SQL_MULTI_ROW): mixed
    {
        $this->loadLegacy();

        if (function_exists('db_select_query')) {
            return db_select_query($sql, $returnType);
        }

        // Fallback: execute directly
        $stmt = $this->getConnection()->query($sql);
        return match ($returnType) {
            self::SQL_SINGLE_VALUE => $stmt->fetchColumn(),
            self::SQL_SINGLE_ROW => $stmt->fetch(PDO::FETCH_ASSOC),
            default => $stmt->fetchAll(PDO::FETCH_ASSOC),
        };
    }

    /**
     * Execute an INSERT/UPDATE query using the legacy helper.
     *
     * @param string $sql The SQL query to execute
     * @return int|false Number of affected rows or false on failure
     */
    public function executeQuery(string $sql): int|false
    {
        $this->loadLegacy();

        if (function_exists('db_insert_update_query')) {
            return db_insert_update_query($sql);
        }

        // Fallback: execute directly
        return $this->getConnection()->exec($sql);
    }

    /**
     * Execute a raw SQL command.
     *
     * @param string $sql The SQL command to execute
     * @return int|false Number of affected rows or false on failure
     */
    public function command(string $sql): int|false
    {
        $this->loadLegacy();

        if (function_exists('db_command')) {
            return db_command($sql);
        }

        return $this->getConnection()->exec($sql);
    }

    /**
     * Get the last inserted ID.
     *
     * @param string $sequenceName The PostgreSQL sequence name
     * @return string|false The last inserted ID
     */
    public function lastInsertId(string $sequenceName): string|false
    {
        $this->loadLegacy();

        if (function_exists('db_insert_id')) {
            return db_insert_id($sequenceName);
        }

        return $this->getConnection()->lastInsertId($sequenceName);
    }

    /**
     * Escape a string for safe SQL usage.
     *
     * @param string $value The value to escape
     * @return string The escaped value
     */
    public function escapeString(string $value): string
    {
        $this->loadLegacy();

        if (function_exists('db_escape_string')) {
            return db_escape_string($value);
        }

        // Remove surrounding quotes from PDO::quote()
        $quoted = $this->getConnection()->quote($value);
        return substr($quoted, 1, -1);
    }
}
