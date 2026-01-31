<?php

namespace App\Service\Legacy;

/**
 * Bridge service for legacy base utilities.
 * Wraps functionality from includes/baseutil.php.
 */
class BaseUtilBridge
{
    private string $projectDir;
    private bool $initialized = false;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Initialize the legacy baseutil.php.
     * This loads global variables and sets up Smarty.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $baseutilPath = $this->projectDir . '/includes/baseutil.php';
        if (is_file($baseutilPath)) {
            // Save current directory
            $originalCwd = getcwd();

            // Change to project root (baseutil expects to be included from there)
            chdir($this->projectDir);

            require_once $baseutilPath;

            // Restore directory
            chdir($originalCwd);
        }

        $this->initialized = true;
    }

    /**
     * Check if mobile mode is enabled.
     *
     * @return bool True if mobile mode
     */
    public function isMobileMode(): bool
    {
        global $gMobileMode;
        $this->initialize();
        return (bool) ($gMobileMode ?? false);
    }

    /**
     * Check if tablet mode is enabled.
     *
     * @return bool True if tablet mode
     */
    public function isTabletMode(): bool
    {
        global $gTabletMode;
        $this->initialize();
        return (bool) ($gTabletMode ?? false);
    }

    /**
     * Get the application version.
     *
     * @return string The version string
     */
    public function getAppVersion(): string
    {
        return defined('APP_VERSION') ? APP_VERSION : '0.0.0';
    }

    /**
     * Output debug information if debug mode is enabled.
     *
     * @param string $message The debug message
     * @param int $level The debug level
     * @param string $color The HTML color for the message
     */
    public function debug(string $message, int $level = 7, string $color = '#ff22ff'): void
    {
        $this->initialize();
        if (function_exists('BaseUtil_Debug')) {
            BaseUtil_Debug($message, $level, $color);
        }
    }

    /**
     * Get the current debug level.
     *
     * @return int The debug level
     */
    public function getDebugLevel(): int
    {
        global $g_debug;
        return (int) ($g_debug ?? 0);
    }

    /**
     * Check if debug mode is enabled for a specific level.
     *
     * @param int $level The level to check
     * @return bool True if debug is enabled for this level
     */
    public function isDebugEnabled(int $level): bool
    {
        return ($this->getDebugLevel() & $level) === $level;
    }

    /**
     * Detect device type from user agent.
     *
     * @return string 'mobile', 'tablet', or 'desktop'
     */
    public function detectDeviceType(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (empty($userAgent)) {
            return 'desktop';
        }

        // Check for tablets first (iPad, Android tablets)
        if (stristr($userAgent, 'iPad') || (stristr($userAgent, 'Android') && !stristr($userAgent, 'Mobile'))) {
            return 'tablet';
        }

        // Check for mobile devices
        if (stristr($userAgent, 'iPhone') || stristr($userAgent, 'Mobile') || stristr($userAgent, 'Android')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
