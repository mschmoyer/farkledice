<?php
/**
 * farkleChallenge.php
 *
 * Challenge Mode run management functions.
 * Handles starting new runs, resuming runs, and getting challenge status.
 */

require_once('farkleChallengeConfig.php');

/**
 * Get the player's challenge status including active run, stats, and bot lineup
 * @param int $playerId The player ID
 * @return array Challenge status data
 */
function Challenge_GetStatus($playerId) {
    $dbh = db_connect();

    $result = [
        'has_active_run' => false,
        'active_run' => null,
        'stats' => null,
        'bot_lineup' => []
    ];

    // Check for active run
    $sql = "SELECT r.*,
                   (SELECT COUNT(*) FROM farkle_challenge_dice_inventory WHERE run_id = r.run_id) as dice_count
            FROM farkle_challenge_runs r
            WHERE r.player_id = :playerid AND r.status = 'active'
            LIMIT 1";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);
    $activeRun = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($activeRun) {
        $result['has_active_run'] = true;

        // Get current bot info from config
        $currentBotNum = $activeRun['current_bot_number'];
        $currentBot = Challenge_GetBotConfig($currentBotNum);

        $result['active_run'] = [
            'run_id' => $activeRun['run_id'],
            'current_bot_num' => $currentBotNum,
            'money' => $activeRun['current_money'],
            'dice_saved_total' => $activeRun['total_dice_saved'],
            'started_at' => $activeRun['created_date'],
            'current_bot' => $currentBot ? [
                'bot_name' => $currentBot['name'],
                'title' => $currentBot['title'],
                'difficulty' => $currentBot['difficulty'],
                'target_score' => $currentBot['point_target'],
                'description' => $currentBot['description'],
                'rules_display' => $currentBot['rules_display'],
                'rules' => $currentBot['rules'],
            ] : null
        ];

        // Get player's dice inventory
        $sql = "SELECT i.dice_slot AS slot_number, d.name, d.effect_value
                FROM farkle_challenge_dice_inventory i
                JOIN farkle_challenge_dice_types d ON d.dice_type_id = i.dice_type_id
                WHERE i.run_id = :run_id
                ORDER BY i.dice_slot";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':run_id' => $activeRun['run_id']]);
        $result['active_run']['dice_inventory'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get player's challenge stats
    $sql = "SELECT * FROM farkle_challenge_stats WHERE player_id = :playerid";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats) {
        $result['stats'] = [
            'total_runs' => $stats['total_runs'],
            'total_wins' => $stats['completed_runs'],
            'furthest_bot' => $stats['furthest_bot_reached'],
            'total_money_earned' => $stats['total_money_earned'],
            'total_dice_saved' => $stats['total_dice_purchased'] // Using total_dice_purchased as proxy
        ];
    } else {
        $result['stats'] = [
            'total_runs' => 0,
            'total_wins' => 0,
            'furthest_bot' => 0,
            'total_money_earned' => 0,
            'total_dice_saved' => 0
        ];
    }

    // Get bot lineup from config (some hidden based on progress)
    $furthestBot = $result['stats']['furthest_bot'];
    $result['bot_lineup'] = Challenge_GetBotListForLobby($furthestBot);

    return $result;
}

/**
 * Start a new challenge run for the player
 * @param int $playerId The player ID
 * @return array Result with run_id or error
 */
function Challenge_StartRun($playerId) {
    $dbh = db_connect();

    // Check for existing active run
    $sql = "SELECT run_id FROM farkle_challenge_runs
            WHERE player_id = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);

    if ($stmt->fetch()) {
        return ['error' => 'You already have an active challenge run. Resume or abandon it first.'];
    }

    try {
        $dbh->beginTransaction();

        // Create new run
        $sql = "INSERT INTO farkle_challenge_runs (player_id, status, current_bot_number, current_money, total_dice_saved)
                VALUES (:playerid, 'active', 1, 0, 0)
                RETURNING run_id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':playerid' => $playerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $runId = $result['run_id'];

        // Initialize 6 standard dice in inventory
        $sql = "INSERT INTO farkle_challenge_dice_inventory (run_id, dice_slot, dice_type_id)
                VALUES (:run_id, :slot, 1)";
        $stmt = $dbh->prepare($sql);

        for ($slot = 1; $slot <= 6; $slot++) {
            $stmt->execute([':run_id' => $runId, ':slot' => $slot]);
        }

        // Update or create player stats
        $sql = "INSERT INTO farkle_challenge_stats (player_id, total_runs)
                VALUES (:playerid, 1)
                ON CONFLICT (player_id) DO UPDATE SET total_runs = farkle_challenge_stats.total_runs + 1";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':playerid' => $playerId]);

        $dbh->commit();

        // Get full status to return
        $status = Challenge_GetStatus($playerId);
        $status['success'] = true;
        $status['message'] = 'Challenge run started! Good luck!';

        return $status;

    } catch (Exception $e) {
        $dbh->rollBack();
        return ['error' => 'Failed to start challenge run: ' . $e->getMessage()];
    }
}

/**
 * Get the next bot for the current run and create a game against them
 * @param int $playerId The player ID
 * @param int $runId The run ID
 * @return array Result with game info or error
 */
function Challenge_StartBotGame($playerId, $runId) {
    $dbh = db_connect();

    // Get the active run
    $sql = "SELECT * FROM farkle_challenge_runs
            WHERE run_id = :run_id AND player_id = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':run_id' => $runId, ':playerid' => $playerId]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$run) {
        return ['error' => 'No active challenge run found.'];
    }

    $botNum = $run['current_bot_number'];

    // Get the bot for this position (with aliased columns for JS compatibility)
    $sql = "SELECT b.bot_number, b.display_name AS bot_name, p.difficulty,
                   b.point_target AS target_score, b.description AS personality,
                   b.personality_id, b.special_rules, b.bot_dice_types
            FROM farkle_challenge_bot_lineup b
            JOIN farkle_bot_personalities p ON b.personality_id = p.personality_id
            WHERE b.bot_number = :bot_num";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':bot_num' => $botNum]);
    $bot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bot) {
        return ['error' => 'Bot not found for position ' . $botNum];
    }

    // Get or create the bot player (use display_name to find the bot player)
    $sql = "SELECT playerid FROM farkle_players WHERE username = :username AND is_bot = TRUE";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':username' => $bot['bot_name']]);
    $botPlayer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$botPlayer) {
        return ['error' => 'Bot player not found: ' . $bot['bot_name']];
    }

    // Create a 10-round game against this bot
    $players = json_encode([$playerId, $botPlayer['playerid']]);

    // Use existing FarkleNewGame function
    require_once('farkleGameFuncs.php');
    $gameResult = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND, false, 2);

    if (isset($gameResult['Error'])) {
        return ['error' => $gameResult['Error']];
    }

    // Mark the game as a challenge game and set bot mode
    if (is_array($gameResult) && isset($gameResult[0]) && isset($gameResult[0]['gameid'])) {
        $gameId = $gameResult[0]['gameid'];

        $sql = "UPDATE farkle_games SET
                    bot_play_mode = 'interactive',
                    is_challenge_game = TRUE,
                    challenge_run_id = :run_id,
                    challenge_bot_number = :bot_num
                WHERE gameid = :gameid";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':run_id' => $runId, ':bot_num' => $botNum, ':gameid' => $gameId]);

        return [
            'success' => true,
            'game_id' => $gameId,
            'bot_name' => $bot['bot_name'],
            'bot_number' => $botNum,
            'difficulty' => $bot['difficulty'],
            'game_data' => $gameResult
        ];
    }

    return ['error' => 'Failed to create game against bot.'];
}

/**
 * Record the result of a challenge game (win or loss)
 * @param int $playerId The player ID
 * @param int $runId The run ID
 * @param bool $won Whether the player won
 * @param int $diceSaved Number of dice saved during the game
 * @return array Result
 */
function Challenge_RecordGameResult($playerId, $runId, $won, $diceSaved = 0) {
    $dbh = db_connect();

    // Get the active run
    $sql = "SELECT * FROM farkle_challenge_runs
            WHERE run_id = :run_id AND player_id = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':run_id' => $runId, ':playerid' => $playerId]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$run) {
        return ['error' => 'No active challenge run found.'];
    }

    $currentBot = $run['current_bot_number'];
    $money = $run['current_money'] + $diceSaved; // $1 per die saved

    if ($won) {
        // Player won - advance to next bot or complete the challenge
        $nextBot = $currentBot + 1;

        if ($nextBot > 20) {
            // Challenge complete!
            $sql = "UPDATE farkle_challenge_runs
                    SET status = 'completed', current_bot_number = 20,
                        current_money = :money, total_dice_saved = total_dice_saved + :dice_saved,
                        completed_date = NOW()
                    WHERE run_id = :run_id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([':money' => $money, ':dice_saved' => $diceSaved, ':run_id' => $runId]);

            // Update stats - player completed the challenge!
            $sql = "UPDATE farkle_challenge_stats
                    SET completed_runs = completed_runs + 1,
                        furthest_bot_reached = GREATEST(furthest_bot_reached, 20),
                        total_money_earned = total_money_earned + :money
                    WHERE player_id = :playerid";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([':money' => $money, ':playerid' => $playerId]);

            return [
                'success' => true,
                'challenge_complete' => true,
                'message' => 'Congratulations! You completed the Challenge!'
            ];
        } else {
            // Advance to next bot
            $sql = "UPDATE farkle_challenge_runs
                    SET current_bot_number = :next_bot, current_money = :money,
                        total_dice_saved = total_dice_saved + :dice_saved
                    WHERE run_id = :run_id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([
                ':next_bot' => $nextBot,
                ':money' => $money,
                ':dice_saved' => $diceSaved,
                ':run_id' => $runId
            ]);

            // Update furthest bot stat if needed
            $sql = "UPDATE farkle_challenge_stats
                    SET furthest_bot_reached = GREATEST(furthest_bot_reached, :bot_num),
                        total_money_earned = total_money_earned + :dice_saved
                    WHERE player_id = :playerid";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([':bot_num' => $currentBot, ':dice_saved' => $diceSaved, ':playerid' => $playerId]);

            return [
                'success' => true,
                'next_bot' => $nextBot,
                'money' => $money,
                'show_shop' => true
            ];
        }
    } else {
        // Player lost - end the run
        $sql = "UPDATE farkle_challenge_runs
                SET status = 'failed', completed_date = NOW()
                WHERE run_id = :run_id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':run_id' => $runId]);

        // Update stats
        $sql = "UPDATE farkle_challenge_stats
                SET furthest_bot_reached = GREATEST(furthest_bot_reached, :bot_num)
                WHERE player_id = :playerid";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':bot_num' => $currentBot - 1, ':playerid' => $playerId]);

        return [
            'success' => true,
            'run_ended' => true,
            'defeated_by' => $currentBot,
            'message' => 'You were defeated by Bot #' . $currentBot . '. Better luck next time!'
        ];
    }
}

/**
 * Abandon an active challenge run
 * @param int $playerId The player ID
 * @return array Result
 */
function Challenge_AbandonRun($playerId) {
    $dbh = db_connect();

    $sql = "UPDATE farkle_challenge_runs
            SET status = 'abandoned', completed_date = NOW()
            WHERE player_id = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);

    if ($stmt->rowCount() > 0) {
        return ['success' => true, 'message' => 'Challenge run abandoned.'];
    } else {
        return ['error' => 'No active challenge run to abandon.'];
    }
}

?>
