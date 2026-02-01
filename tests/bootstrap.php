<?php
/**
 * PHPUnit Bootstrap
 *
 * Sets up the test environment by configuring autoloading and including
 * necessary application files.
 */

// Set working directory to wwwroot (matches app behavior)
chdir(__DIR__ . '/../wwwroot');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load base utilities (but don't start sessions in test mode)
define('TESTING_MODE', true);

// Load the base utility functions
require_once __DIR__ . '/../includes/baseutil.php';

// Load database utilities
require_once __DIR__ . '/../includes/dbutil.php';

// Load achievements (needed for dice scoring which calls Ach_AwardAchievement)
require_once __DIR__ . '/../wwwroot/farkleAchievements.php';

// Register test namespace autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Tests\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
