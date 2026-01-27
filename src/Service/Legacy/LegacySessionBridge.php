<?php

namespace App\Service\Legacy;

/**
 * Bridge service for managing sessions shared between Symfony and legacy code.
 * Ensures both applications use the same session with the same handler.
 */
class LegacySessionBridge
{
    private string $projectDir;
    private LegacyDatabaseBridge $db;
    private bool $initialized = false;

    public function __construct(string $projectDir, LegacyDatabaseBridge $db)
    {
        $this->projectDir = $projectDir;
        $this->db = $db;
    }

    /**
     * Initialize the legacy session handler and start the session.
     * This should be called before any session access.
     */
    public function initializeSession(): void
    {
        if ($this->initialized) {
            return;
        }

        // Don't reinitialize if session is already active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->initialized = true;
            return;
        }

        // Get the database connection first
        $pdo = $this->db->getConnection();

        // Load and register the legacy session handler
        $handlerPath = $this->projectDir . '/includes/session-handler.php';
        if (is_file($handlerPath)) {
            require_once $handlerPath;

            if (function_exists('init_database_session_handler')) {
                init_database_session_handler($pdo);
            }
        }

        // Configure session settings to match legacy
        if (session_status() === PHP_SESSION_NONE) {
            session_name('FarkleOnline');

            ini_set('session.gc_maxlifetime', 604800);  // 7 days
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 100);

            // Detect HTTPS
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
                    $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            ini_set('session.cookie_secure', $isHttps ? '1' : '0');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_lifetime', 604800);

            session_start();
        }

        $this->initialized = true;
    }

    /**
     * Get the current player ID from the session.
     *
     * @return int|null The player ID or null if not logged in
     */
    public function getPlayerId(): ?int
    {
        $this->initializeSession();
        return isset($_SESSION['playerid']) ? (int) $_SESSION['playerid'] : null;
    }

    /**
     * Get the current username from the session.
     *
     * @return string|null The username or null if not logged in
     */
    public function getUsername(): ?string
    {
        $this->initializeSession();
        return $_SESSION['username'] ?? null;
    }

    /**
     * Check if the current user is authenticated.
     *
     * @return bool True if logged in
     */
    public function isAuthenticated(): bool
    {
        $this->initializeSession();
        return isset($_SESSION['playerid']) && isset($_SESSION['username']);
    }

    /**
     * Get the admin level of the current user.
     *
     * @return int The admin level (0 = normal user)
     */
    public function getAdminLevel(): int
    {
        $this->initializeSession();
        return isset($_SESSION['adminlevel']) ? (int) $_SESSION['adminlevel'] : 0;
    }

    /**
     * Check if the current user is an admin.
     *
     * @return bool True if admin
     */
    public function isAdmin(): bool
    {
        return $this->getAdminLevel() > 0;
    }

    /**
     * Get a session value.
     *
     * @param string $key The session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The session value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->initializeSession();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     *
     * @param string $key The session key
     * @param mixed $value The value to set
     */
    public function set(string $key, mixed $value): void
    {
        $this->initializeSession();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a value from the 'farkle' session namespace.
     *
     * @param string $key The key within $_SESSION['farkle']
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The value
     */
    public function getFarkle(string $key, mixed $default = null): mixed
    {
        $this->initializeSession();
        return $_SESSION['farkle'][$key] ?? $default;
    }

    /**
     * Set a value in the 'farkle' session namespace.
     *
     * @param string $key The key within $_SESSION['farkle']
     * @param mixed $value The value to set
     */
    public function setFarkle(string $key, mixed $value): void
    {
        $this->initializeSession();
        if (!isset($_SESSION['farkle'])) {
            $_SESSION['farkle'] = [];
        }
        $_SESSION['farkle'][$key] = $value;
    }

    /**
     * Destroy the session (logout).
     */
    public function destroy(): void
    {
        $this->initializeSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->initialized = false;
    }
}
