#!/usr/bin/env php
<?php
/**
 * seed_challenge_bots.php
 *
 * Populates farkle_challenge_bot_lineup table with 20 bot configurations
 *
 * Usage: php scripts/seed_challenge_bots.php
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
echo "Farkle Challenge Bot Lineup Seed Script\n";
echo "========================================\n\n";

// Define all 20 bots with progressive difficulty
$bots = [
    // TIER 1: Bots 1-5 (Easy) - 2500-3000 points, standard dice only
    [
        'bot_number' => 1,
        'personality_id' => 1, // Byte
        'display_name' => 'Bot #1 - Byte the Beginner',
        'point_target' => 2500,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 1, 1, 1]),
        'description' => 'Friendly beginner bot. Great for warming up!'
    ],
    [
        'bot_number' => 2,
        'personality_id' => 2, // Chip
        'display_name' => 'Bot #2 - Chip the Cheerful',
        'point_target' => 2600,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 1, 1, 1]),
        'description' => 'Enthusiastic beginner who celebrates every roll!'
    ],
    [
        'bot_number' => 3,
        'personality_id' => 3, // Beep
        'display_name' => 'Bot #3 - Beep the Bewildered',
        'point_target' => 2700,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 1, 1, 1]),
        'description' => 'Still learning the rules. Easy opponent!'
    ],
    [
        'bot_number' => 4,
        'personality_id' => 4, // Spark
        'display_name' => 'Bot #4 - Spark the Optimist',
        'point_target' => 2800,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 1, 1, 1]),
        'description' => 'Always hopeful, but not always lucky!'
    ],
    [
        'bot_number' => 5,
        'personality_id' => 5, // Dot
        'display_name' => 'Bot #5 - Dot the Teacher',
        'point_target' => 3000,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 1, 1, 1]),
        'description' => 'Helpful and patient. Last of the easy bots!'
    ],

    // TIER 2: Bots 6-10 (Medium) - 3000-3500 points, some special dice
    [
        'bot_number' => 6,
        'personality_id' => 6, // Cyber
        'display_name' => 'Bot #6 - Cyber the Analyst',
        'point_target' => 3100,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 1, 2, 3]), // 4 standard, Lucky Die, Farkle Shield
        'description' => 'Analytical strategist with a lucky die!'
    ],
    [
        'bot_number' => 7,
        'personality_id' => 7, // Logic
        'display_name' => 'Bot #7 - Logic the Planner',
        'point_target' => 3200,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 1, 3, 4]), // 4 standard, Farkle Shield, Penny Pincher
        'description' => 'Methodical and protected from farkles!'
    ],
    [
        'bot_number' => 8,
        'personality_id' => 8, // Binary
        'display_name' => 'Bot #8 - Binary the Chaotic',
        'point_target' => 3300,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 2, 5, 8]), // 3 standard, Lucky Die, Double Die, Reroll Token
        'description' => 'Chaos agent with unpredictable dice!'
    ],
    [
        'bot_number' => 9,
        'personality_id' => 9, // Glitch
        'display_name' => 'Bot #9 - Glitch the Witty',
        'point_target' => 3400,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 1, 2, 6, 7]), // 3 standard, Lucky Die, Wild Die, Straight Booster
        'description' => 'Sarcastic and well-equipped with special dice!'
    ],
    [
        'bot_number' => 10,
        'personality_id' => 10, // Echo
        'display_name' => 'Bot #10 - Echo the Zen Master',
        'point_target' => 3500,
        'special_rules' => json_encode(['none' => true]),
        'bot_dice_types' => json_encode([1, 1, 2, 3, 5, 9]), // 2 standard, Lucky Die, Farkle Shield, Double Die, Triple Ones
        'description' => 'Philosophical and balanced. Medium tier complete!'
    ],

    // TIER 3: Bots 11-15 (Hard) - 3500-4000 points, more special dice, some penalties
    [
        'bot_number' => 11,
        'personality_id' => 11, // Neural
        'display_name' => 'Bot #11 - Neural the Calculated',
        'point_target' => 3600,
        'special_rules' => json_encode(['player_farkle_penalty' => 100]),
        'bot_dice_types' => json_encode([1, 1, 2, 5, 6, 9]), // 2 standard, Lucky Die, Double Die, Wild Die, Triple Ones
        'description' => 'You lose 100 points on farkle. Risk taker with good dice!'
    ],
    [
        'bot_number' => 12,
        'personality_id' => 12, // Quantum
        'display_name' => 'Bot #12 - Quantum the Probabilist',
        'point_target' => 3700,
        'special_rules' => json_encode(['player_farkle_penalty' => 150]),
        'bot_dice_types' => json_encode([1, 2, 5, 6, 10, 12]), // 1 standard, Lucky Die, Double Die, Wild Die, Golden Die, Six Magnet
        'description' => 'You lose 150 points on farkle. Probability master with amazing dice!'
    ],
    [
        'bot_number' => 13,
        'personality_id' => 13, // Apex
        'display_name' => 'Bot #13 - Apex the Optimal',
        'point_target' => 3800,
        'special_rules' => json_encode(['player_farkle_penalty' => 200]),
        'bot_dice_types' => json_encode([1, 5, 6, 9, 10, 13]), // 1 standard, Double Die, Wild Die, Triple Ones, Golden Die, Bonus Roller
        'description' => 'You lose 200 points on farkle. Ruthlessly optimized!'
    ],
    [
        'bot_number' => 14,
        'personality_id' => 14, // Sigma
        'display_name' => 'Bot #14 - Sigma the Cold',
        'point_target' => 3900,
        'special_rules' => json_encode(['player_farkle_penalty' => 250, 'bot_has_farkle_immunity' => true]),
        'bot_dice_types' => json_encode([1, 6, 9, 10, 11, 13]), // 1 standard, Wild Die, Triple Ones, Golden Die, Farkle Immunity, Bonus Roller
        'description' => 'You lose 250 points on farkle. Bot has farkle immunity!'
    ],
    [
        'bot_number' => 15,
        'personality_id' => 15, // Prime
        'display_name' => 'Bot #15 - Prime the Showoff',
        'point_target' => 4000,
        'special_rules' => json_encode(['player_farkle_penalty' => 300, 'bot_has_farkle_immunity' => true]),
        'bot_dice_types' => json_encode([5, 6, 9, 10, 11, 14]), // Double Die, Wild Die, Triple Ones, Golden Die, Farkle Immunity, Point Doubler
        'description' => 'You lose 300 points on farkle. Cocky showoff with point doubler!'
    ],

    // TIER 4: Bots 16-19 (Very Hard) - 4000-4500 points, powerful special dice, harsh penalties
    [
        'bot_number' => 16,
        'personality_id' => 11, // Neural (reused)
        'display_name' => 'Bot #16 - Neural Advanced',
        'point_target' => 4100,
        'special_rules' => json_encode(['player_farkle_penalty' => 400, 'bot_has_farkle_immunity' => true, 'bot_score_multiplier' => 1.1]),
        'bot_dice_types' => json_encode([5, 6, 10, 11, 13, 14]), // Double Die, Wild Die, Golden Die, Farkle Immunity, Bonus Roller, Point Doubler
        'description' => 'You lose 400 points on farkle. Bot scores 10% more points!'
    ],
    [
        'bot_number' => 17,
        'personality_id' => 12, // Quantum (reused)
        'display_name' => 'Bot #17 - Quantum Evolved',
        'point_target' => 4200,
        'special_rules' => json_encode(['player_farkle_penalty' => 500, 'bot_has_farkle_immunity' => true, 'bot_score_multiplier' => 1.2]),
        'bot_dice_types' => json_encode([6, 10, 11, 12, 14, 16]), // Wild Die, Golden Die, Farkle Immunity, Six Magnet, Point Doubler, Streak Keeper
        'description' => 'You lose 500 points on farkle. Bot scores 20% more points!'
    ],
    [
        'bot_number' => 18,
        'personality_id' => 13, // Apex (reused)
        'display_name' => 'Bot #18 - Apex Maximum',
        'point_target' => 4300,
        'special_rules' => json_encode(['player_farkle_penalty' => 600, 'bot_has_farkle_immunity' => true, 'bot_score_multiplier' => 1.25]),
        'bot_dice_types' => json_encode([10, 11, 12, 13, 14, 16]), // Golden Die, Farkle Immunity, Six Magnet, Bonus Roller, Point Doubler, Streak Keeper
        'description' => 'You lose 600 points on farkle. Bot scores 25% more points!'
    ],
    [
        'bot_number' => 19,
        'personality_id' => 14, // Sigma (reused)
        'display_name' => 'Bot #19 - Sigma Supreme',
        'point_target' => 4400,
        'special_rules' => json_encode(['player_farkle_penalty' => 750, 'bot_has_farkle_immunity' => true, 'bot_score_multiplier' => 1.3]),
        'bot_dice_types' => json_encode([10, 11, 13, 14, 15, 16]), // Golden Die, Farkle Immunity, Bonus Roller, Point Doubler, Money Printer, Streak Keeper
        'description' => 'You lose 750 points on farkle. Bot scores 30% more points!'
    ],

    // TIER 5: Bot 20 (Boss) - 4500+ points, all powerful dice, severe penalties
    [
        'bot_number' => 20,
        'personality_id' => 15, // Prime (reused)
        'display_name' => 'Bot #20 - PRIME ULTIMATE',
        'point_target' => 4500,
        'special_rules' => json_encode([
            'player_farkle_penalty' => 1000,
            'bot_has_farkle_immunity' => true,
            'bot_score_multiplier' => 1.5,
            'player_max_bank_limit' => 2000
        ]),
        'bot_dice_types' => json_encode([10, 11, 13, 14, 15, 16]), // Golden Die, Farkle Immunity, Bonus Roller, Point Doubler, Money Printer, Streak Keeper
        'description' => 'FINAL BOSS: You lose 1000 points on farkle. Bot scores 50% more. You can only bank 2000 points per turn!'
    ]
];

// Connect to database
$db = get_db_connection();
echo "Connected to database successfully\n\n";

// Start transaction
$db->beginTransaction();

try {
    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO farkle_challenge_bot_lineup
        (bot_number, personality_id, display_name, point_target, special_rules, bot_dice_types, description)
        VALUES (:bot_number, :personality_id, :display_name, :point_target, :special_rules, :bot_dice_types, :description)
        ON CONFLICT (bot_number) DO NOTHING
    ");

    $insertedCount = 0;

    foreach ($bots as $bot) {
        $stmt->execute([
            ':bot_number' => $bot['bot_number'],
            ':personality_id' => $bot['personality_id'],
            ':display_name' => $bot['display_name'],
            ':point_target' => $bot['point_target'],
            ':special_rules' => $bot['special_rules'],
            ':bot_dice_types' => $bot['bot_dice_types'],
            ':description' => $bot['description']
        ]);

        // Check if row was actually inserted
        if ($stmt->rowCount() > 0) {
            $insertedCount++;
            echo "Inserted: {$bot['display_name']} (target: {$bot['point_target']}, personality: {$bot['personality_id']})\n";
        } else {
            echo "Skipped (already exists): {$bot['display_name']}\n";
        }
    }

    // Commit transaction
    $db->commit();

    echo "\n=== SUCCESS ===\n";
    echo "Seeded {$insertedCount} challenge bots successfully\n";
    echo "Total bots in lineup: 20\n";
    echo "\nDifficulty Breakdown:\n";
    echo "  Bots 1-5   (Easy):      2500-3000 points, standard dice only\n";
    echo "  Bots 6-10  (Medium):    3000-3500 points, some special dice\n";
    echo "  Bots 11-15 (Hard):      3500-4000 points, special dice + penalties\n";
    echo "  Bots 16-19 (Very Hard): 4000-4500 points, powerful dice + harsh penalties\n";
    echo "  Bot 20     (Boss):      4500 points, ultimate challenge!\n";

} catch (Exception $e) {
    // Rollback on error
    $db->rollBack();
    die("\nERROR: Failed to seed bot lineup: " . $e->getMessage() . "\n");
}
