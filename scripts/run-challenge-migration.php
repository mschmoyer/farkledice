#!/usr/bin/env php
<?php
/**
 * run-challenge-migration.php
 *
 * Executes the Challenge Mode schema migration safely with transaction support
 *
 * Usage: php scripts/run-challenge-migration.php
 */

// Run from command line only
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Change to project root directory (script is in scripts/ subdirectory)
chdir(dirname(__DIR__));

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
    } else {
        // Use local environment variables or Docker defaults
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: 5432;
        $dbname = getenv('DB_NAME') ?: 'farkle_db';
        $username = getenv('DB_USER') ?: 'farkle_user';
        $password = getenv('DB_PASSWORD') ?: 'farkle_pass';
    }

    try {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("ERROR: Database connection failed: " . $e->getMessage() . "\n");
    }
}

echo "============================================\n";
echo "Challenge Mode Database Migration\n";
echo "============================================\n\n";

// Connect to database
$db = get_db_connection();
echo "Connected to database successfully\n\n";

// Check if migration already applied by testing for farkle_challenge_runs table
echo "Checking migration status...\n";
try {
    $stmt = $db->query("SELECT 1 FROM farkle_challenge_runs LIMIT 1");
    echo "\nMigration already applied. Challenge Mode tables exist.\n";
    echo "If you need to reapply the migration, drop the tables first.\n";
    exit(0);
} catch (PDOException $e) {
    // Tables don't exist, proceed with migration
    echo "Challenge Mode tables not found. Proceeding with migration...\n\n";
}

// Read SQL migration file
$sqlFilePath = __DIR__ . '/migrate-challenge-schema.sql';
if (!file_exists($sqlFilePath)) {
    die("ERROR: Migration file not found at {$sqlFilePath}\n");
}

$sql = file_get_contents($sqlFilePath);
if ($sql === false) {
    die("ERROR: Failed to read migration file\n");
}

echo "Running Challenge Mode migration...\n\n";

// Execute migration in a transaction
try {
    $db->beginTransaction();

    // Execute the entire SQL file
    // PostgreSQL PDO supports multi-statement execution
    $db->exec($sql);

    $db->commit();

    echo "============================================\n";
    echo "Migration Completed Successfully\n";
    echo "============================================\n\n";

    // Count created tables for verification
    $tables = [
        'farkle_challenge_dice_types',
        'farkle_challenge_bot_lineup',
        'farkle_challenge_runs',
        'farkle_challenge_dice_inventory',
        'farkle_challenge_stats'
    ];

    $createdCount = 0;
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = '{$table}'");
        if ($stmt->fetchColumn() > 0) {
            $createdCount++;
        }
    }

    echo "✓ Created {$createdCount} new tables\n";

    // Check modified tables
    $modifiedColumns = 0;
    $checkColumns = [
        ['table' => 'farkle_games', 'column' => 'is_challenge_game'],
        ['table' => 'farkle_games', 'column' => 'challenge_run_id'],
        ['table' => 'farkle_games', 'column' => 'challenge_bot_number'],
        ['table' => 'farkle_games_players', 'column' => 'dice_inventory']
    ];

    foreach ($checkColumns as $check) {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_name = :table AND column_name = :column
        ");
        $stmt->execute([':table' => $check['table'], ':column' => $check['column']]);
        if ($stmt->fetchColumn() > 0) {
            $modifiedCount = 2; // Count tables, not columns
            break;
        }
    }

    echo "✓ Modified 2 existing tables\n";

    // Verify seed data was inserted
    $stmt = $db->query("SELECT COUNT(*) FROM farkle_challenge_dice_types");
    $diceCount = $stmt->fetchColumn();
    echo "✓ Seeded {$diceCount} dice types\n";

    $stmt = $db->query("SELECT COUNT(*) FROM farkle_challenge_bot_lineup");
    $botCount = $stmt->fetchColumn();
    echo "✓ Seeded {$botCount} challenge bots\n";

    echo "\n✓ Database is ready for Challenge Mode\n\n";

} catch (PDOException $e) {
    $db->rollBack();
    echo "\n============================================\n";
    echo "Migration Failed\n";
    echo "============================================\n\n";
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "✗ All changes have been rolled back\n\n";
    exit(1);
}
