<?php
namespace Tests;

use PDO;

/**
 * Base test case for tests requiring database access.
 *
 * Each test runs within a transaction that is rolled back after the test,
 * ensuring test isolation and no persistent test data.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $db = null;

    /**
     * Set up database connection once for the test class
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Get database connection
        self::$db = db_connect();
    }

    /**
     * Start a transaction before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Start transaction for test isolation
        self::$db->beginTransaction();

        // Initialize session array if not set
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }

    /**
     * Roll back the transaction after each test
     */
    protected function tearDown(): void
    {
        // Roll back to undo any database changes from the test
        if (self::$db->inTransaction()) {
            self::$db->rollBack();
        }

        parent::tearDown();
    }

    /**
     * Create a test player in the database
     *
     * @param string $username Base username (will have unique suffix added)
     * @param string $password Plain text password
     * @return int The new player's ID
     */
    protected function createTestPlayer(string $username, string $password = 'testpass'): int
    {
        $uniqueUsername = $username . '_' . uniqid();
        $hashedPassword = md5($password) . md5(''); // Match app's password hashing (pass + salt)

        $sql = "INSERT INTO farkle_players (username, password, salt, email, active)
                VALUES (:username, :password, '', :email, true)
                RETURNING playerid";

        $stmt = self::$db->prepare($sql);
        $stmt->execute([
            ':username' => $uniqueUsername,
            ':password' => $hashedPassword,
            ':email' => $uniqueUsername . '@test.com'
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['playerid'];
    }

    /**
     * Execute a query and return a single value
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return mixed The first column of the first row
     */
    protected function queryValue(string $sql, array $params = []): mixed
    {
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : null;
    }

    /**
     * Execute a query and return a single row
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null The row as associative array
     */
    protected function queryRow(string $sql, array $params = []): ?array
    {
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Execute a query and return all rows
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Array of rows
     */
    protected function queryAll(string $sql, array $params = []): array
    {
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute an INSERT/UPDATE/DELETE statement
     *
     * @param string $sql SQL statement
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Set the current session player ID (simulates logged-in user)
     *
     * @param int $playerId The player ID to set in session
     */
    protected function loginAs(int $playerId): void
    {
        $_SESSION['playerid'] = $playerId;
    }
}
