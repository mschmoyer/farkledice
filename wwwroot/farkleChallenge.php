<?php
/**
 * farkleChallenge.php
 *
 * Challenge Mode run management functions.
 * Handles starting new runs, resuming runs, and getting challenge status.
 */

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
            WHERE r.playerid = :playerid AND r.status = 'active'
            LIMIT 1";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);
    $activeRun = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($activeRun) {
        $result['has_active_run'] = true;
        $result['active_run'] = [
            'run_id' => $activeRun['run_id'],
            'current_bot_num' => $activeRun['current_bot_num'],
            'money' => $activeRun['money'],
            'dice_saved_total' => $activeRun['dice_saved_total'],
            'started_at' => $activeRun['started_at']
        ];

        // Get current bot info
        $sql = "SELECT * FROM farkle_challenge_bot_lineup WHERE bot_number = :bot_num";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':bot_num' => $activeRun['current_bot_num']]);
        $currentBot = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentBot) {
            $result['active_run']['current_bot'] = $currentBot;
        }

        // Get player's dice inventory
        $sql = "SELECT i.slot_number, d.name, d.effect_value
                FROM farkle_challenge_dice_inventory i
                JOIN farkle_challenge_dice_types d ON d.dice_type_id = i.dice_type_id
                WHERE i.run_id = :run_id
                ORDER BY i.slot_number";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':run_id' => $activeRun['run_id']]);
        $result['active_run']['dice_inventory'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get player's challenge stats
    $sql = "SELECT * FROM farkle_challenge_stats WHERE playerid = :playerid";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats) {
        $result['stats'] = [
            'total_runs' => $stats['total_runs'],
            'total_wins' => $stats['total_wins'],
            'furthest_bot' => $stats['furthest_bot'],
            'total_money_earned' => $stats['total_money_earned'],
            'total_dice_saved' => $stats['total_dice_saved']
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

    // Get bot lineup (first 5 visible, rest hidden until reached)
    $sql = "SELECT bot_number, bot_name, difficulty, target_score, personality
            FROM farkle_challenge_bot_lineup
            ORDER BY bot_number";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $bots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $furthestBot = $result['stats']['furthest_bot'];
    foreach ($bots as $bot) {
        // Show full info for bots up to furthest + 1, hide later bots
        if ($bot['bot_number'] <= $furthestBot + 1 || $bot['bot_number'] <= 3) {
            $result['bot_lineup'][] = $bot;
        } else {
            $result['bot_lineup'][] = [
                'bot_number' => $bot['bot_number'],
                'bot_name' => '???',
                'difficulty' => '???',
                'target_score' => '???',
                'personality' => 'Unknown challenger awaits...'
            ];
        }
    }

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
            WHERE playerid = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);

    if ($stmt->fetch()) {
        return ['error' => 'You already have an active challenge run. Resume or abandon it first.'];
    }

    try {
        $dbh->beginTransaction();

        // Create new run
        $sql = "INSERT INTO farkle_challenge_runs (playerid, status, current_bot_num, money, dice_saved_total)
                VALUES (:playerid, 'active', 1, 0, 0)
                RETURNING run_id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':playerid' => $playerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $runId = $result['run_id'];

        // Initialize 6 standard dice in inventory
        $sql = "INSERT INTO farkle_challenge_dice_inventory (run_id, slot_number, dice_type_id)
                VALUES (:run_id, :slot, 1)";
        $stmt = $dbh->prepare($sql);

        for ($slot = 1; $slot <= 6; $slot++) {
            $stmt->execute([':run_id' => $runId, ':slot' => $slot]);
        }

        // Update or create player stats
        $sql = "INSERT INTO farkle_challenge_stats (playerid, total_runs)
                VALUES (:playerid, 1)
                ON CONFLICT (playerid) DO UPDATE SET total_runs = farkle_challenge_stats.total_runs + 1";
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
            WHERE run_id = :run_id AND playerid = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':run_id' => $runId, ':playerid' => $playerId]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$run) {
        return ['error' => 'No active challenge run found.'];
    }

    $botNum = $run['current_bot_num'];

    // Get the bot for this position
    $sql = "SELECT * FROM farkle_challenge_bot_lineup WHERE bot_number = :bot_num";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':bot_num' => $botNum]);
    $bot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bot) {
        return ['error' => 'Bot not found for position ' . $botNum];
    }

    // Get or create the bot player
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

        $sql = "UPDATE farkle_games SET bot_play_mode = 'interactive' WHERE gameid = :gameid";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':gameid' => $gameId]);

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
            WHERE run_id = :run_id AND playerid = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':run_id' => $runId, ':playerid' => $playerId]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$run) {
        return ['error' => 'No active challenge run found.'];
    }

    $currentBot = $run['current_bot_num'];
    $money = $run['money'] + $diceSaved; // $1 per die saved

    if ($won) {
        // Player won - advance to next bot or complete the challenge
        $nextBot = $currentBot + 1;

        if ($nextBot > 20) {
            // Challenge complete!
            $sql = "UPDATE farkle_challenge_runs
                    SET status = 'completed', current_bot_num = 20,
                        money = :money, dice_saved_total = dice_saved_total + :dice_saved,
                        completed_at = NOW()
                    WHERE run_id = :run_id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([':money' => $money, ':dice_saved' => $diceSaved, ':run_id' => $runId]);

            // Update stats - player completed the challenge!
            $sql = "UPDATE farkle_challenge_stats
                    SET total_wins = total_wins + 1,
                        furthest_bot = GREATEST(furthest_bot, 20),
                        total_money_earned = total_money_earned + :money,
                        total_dice_saved = total_dice_saved + :dice_saved
                    WHERE playerid = :playerid";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([':money' => $money, ':dice_saved' => $diceSaved, ':playerid' => $playerId]);

            return [
                'success' => true,
                'challenge_complete' => true,
                'message' => 'Congratulations! You completed the Challenge!'
            ];
        } else {
            // Advance to next bot
            $sql = "UPDATE farkle_challenge_runs
                    SET current_bot_num = :next_bot, money = :money,
                        dice_saved_total = dice_saved_total + :dice_saved
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
                    SET furthest_bot = GREATEST(furthest_bot, :bot_num),
                        total_money_earned = total_money_earned + :dice_saved,
                        total_dice_saved = total_dice_saved + :dice_saved
                    WHERE playerid = :playerid";
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
                SET status = 'failed', completed_at = NOW()
                WHERE run_id = :run_id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':run_id' => $runId]);

        // Update stats
        $sql = "UPDATE farkle_challenge_stats
                SET furthest_bot = GREATEST(furthest_bot, :bot_num)
                WHERE playerid = :playerid";
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
            SET status = 'abandoned', completed_at = NOW()
            WHERE playerid = :playerid AND status = 'active'";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':playerid' => $playerId]);

    if ($stmt->rowCount() > 0) {
        return ['success' => true, 'message' => 'Challenge run abandoned.'];
    } else {
        return ['error' => 'No active challenge run to abandon.'];
    }
}

?>
