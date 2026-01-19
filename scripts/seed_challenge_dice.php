#!/usr/bin/env php
<?php
/**
 * seed_challenge_dice.php
 *
 * Populates farkle_challenge_dice_types table with special dice from JSON
 *
 * Usage: php scripts/seed_challenge_dice.php
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

echo "========================================\n";
echo "Farkle Challenge Dice Seed Script\n";
echo "========================================\n\n";

// Read JSON file
$jsonPath = 'planning/challenge-mode/special-dice.json';
if (!file_exists($jsonPath)) {
    die("ERROR: JSON file not found at {$jsonPath}\n");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);

if ($data === null) {
    die("ERROR: Failed to parse JSON file\n");
}

echo "Loaded " . count($data['dice']) . " special dice from JSON\n\n";

// Connect to database
$db = get_db_connection();
echo "Connected to database successfully\n\n";

// Start transaction
$db->beginTransaction();

try {
    // Insert Standard Die first (dice_type_id = 1)
    $stmt = $db->prepare("
        INSERT INTO farkle_challenge_dice_types
        (dice_type_id, name, description, tier, price, effect_type, effect_value, enabled)
        VALUES (1, 'Standard Die', 'Regular six-sided die with no special effects', 'standard', 0, 'none', '{}', true)
        ON CONFLICT (dice_type_id) DO NOTHING
    ");
    $stmt->execute();

    $insertedCount = 1;
    echo "Inserted Standard Die (id=1)\n";

    // Insert each special die from JSON
    // Map JSON fields to database schema:
    // - effect → description
    // - category → effect_type
    // - shortWord → stored in effect_value JSON
    $stmt = $db->prepare("
        INSERT INTO farkle_challenge_dice_types
        (name, description, tier, price, effect_type, effect_value, enabled)
        VALUES (:name, :description, :tier, :price, :effect_type, :effect_value, true)
        ON CONFLICT (name) DO NOTHING
    ");

    foreach ($data['dice'] as $die) {
        // Store shortWord in effect_value JSON for future reference
        $effectValue = json_encode(['short_word' => $die['shortWord']]);

        $stmt->execute([
            ':name' => $die['name'],
            ':description' => $die['effect'], // Map effect to description
            ':tier' => $die['tier'],
            ':price' => $die['price'],
            ':effect_type' => $die['category'], // Map category to effect_type
            ':effect_value' => $effectValue
        ]);

        // Check if row was actually inserted
        if ($stmt->rowCount() > 0) {
            $insertedCount++;
            echo "Inserted: {$die['name']} (type: {$die['category']}, tier: {$die['tier']}, price: \${$die['price']})\n";
        } else {
            echo "Skipped (already exists): {$die['name']}\n";
        }
    }

    // Commit transaction
    $db->commit();

    echo "\n=== SUCCESS ===\n";
    echo "Seeded {$insertedCount} dice types successfully\n";
    echo "Total dice in database: " . (count($data['dice']) + 1) . " (including Standard Die)\n";

} catch (Exception $e) {
    // Rollback on error
    $db->rollBack();
    die("\nERROR: Failed to seed dice types: " . $e->getMessage() . "\n");
}
