<?php
/**
 * farkleChallengeScoring.php
 *
 * Dice effect engine for Challenge Mode.
 * Provides hooks to modify dice values, scores, and handle farkle triggers.
 *
 * Effect Types:
 * - Face Changers: Modify dice values before scoring (Lucky, Heavy, Fives)
 * - Score Modifiers: Multiply points after scoring (Double, Triple, Jackpot, Hot, Thrice)
 * - Farkle Triggers: Activate when player farkles (Phoenix, BadLuck, Payday, etc.)
 * - Money Modifiers: Adjust money earned (Midas)
 */

// Effect type constants
define('EFFECT_FACE_CHANGER', 'face_changer');
define('EFFECT_SCORE_MODIFIER', 'score_modifier');
define('EFFECT_FARKLE_TRIGGER', 'farkle_trigger');
define('EFFECT_MONEY_MODIFIER', 'money_modifier');

/**
 * Check if a game is a challenge mode game
 * @param int $gameId The game ID
 * @return array|null The run data if challenge mode, null otherwise
 */
function Challenge_GetRunForGame($gameId) {
    $dbh = db_connect();

    // Check if this game is associated with a challenge run
    $sql = "SELECT r.*, g.gameid
            FROM farkle_challenge_runs r
            JOIN farkle_games g ON g.gameid = :gameid
            WHERE r.playerid = (SELECT whostarted FROM farkle_games WHERE gameid = :gameid2)
            AND r.status = 'active'
            AND r.current_bot_num > 0";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([':gameid' => $gameId, ':gameid2' => $gameId]);
    $run = $stmt->fetch(PDO::FETCH_ASSOC);

    return $run ?: null;
}

/**
 * Get player's dice inventory for a challenge run
 * @param int $runId The challenge run ID
 * @return array Array of equipped dice with their effects
 */
function Challenge_GetPlayerDice($runId) {
    $dbh = db_connect();

    $sql = "SELECT i.slot_number, i.dice_type_id, d.name, d.effect_type, d.effect_value, d.description
            FROM farkle_challenge_dice_inventory i
            JOIN farkle_challenge_dice_types d ON d.dice_type_id = i.dice_type_id
            WHERE i.run_id = :run_id
            ORDER BY i.slot_number";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([':run_id' => $runId]);
    $dice = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index by slot for easy access and extract short_word from effect_value JSON
    $inventory = [];
    foreach ($dice as $die) {
        // Extract short_word from effect_value JSON
        $effectValue = json_decode($die['effect_value'], true);
        $die['short_word'] = isset($effectValue['short_word']) ? $effectValue['short_word'] : '';

        // Map category-based effect_type to our internal types (pass short_word for special cases)
        $die['internal_effect_type'] = Challenge_MapCategoryToEffectType($die['effect_type'], $die['short_word']);

        $inventory[$die['slot_number']] = $die;
    }

    return $inventory;
}

/**
 * Map database category and short_word to internal effect type
 * @param string $category The category from database (e.g., 'farkle_lovers')
 * @param string $shortWord The short word identifier
 * @return string Internal effect type constant
 */
function Challenge_MapCategoryToEffectType($category, $shortWord = '') {
    // Special cases where short_word overrides category
    if ($shortWord === 'MIDAS') {
        return EFFECT_MONEY_MODIFIER;
    }

    $mapping = [
        'farkle_lovers' => EFFECT_FARKLE_TRIGGER,
        'farkle_protection' => EFFECT_FARKLE_TRIGGER,
        'face_changers' => EFFECT_FACE_CHANGER,
        'score_boosters' => EFFECT_SCORE_MODIFIER,
        'none' => ''
    ];

    return isset($mapping[$category]) ? $mapping[$category] : '';
}

/**
 * Apply face changer effects to dice values before scoring
 * Face changers modify the actual dice values
 *
 * @param array $diceValues Array of 6 dice values (1-6, 0 for unsaved)
 * @param array $inventory Player's dice inventory
 * @return array Modified dice values
 */
function Challenge_ApplyFaceChangers($diceValues, $inventory) {
    $modified = $diceValues;

    foreach ($inventory as $slot => $die) {
        if (!$die || $die['internal_effect_type'] !== EFFECT_FACE_CHANGER) continue;

        $slotIndex = $slot - 1; // Convert 1-indexed to 0-indexed
        $originalValue = $modified[$slotIndex];

        if ($originalValue < 1 || $originalValue > 6) continue; // Skip unsaved dice

        switch ($die['short_word']) {
            case 'LUCKY':
                // 6 becomes 5
                if ($originalValue == 6) {
                    $modified[$slotIndex] = 5;
                    BaseUtil_Debug("LUCKY die: slot $slot changed 6 to 5", 7);
                }
                break;

            case 'HEAVY':
                // 2 becomes 1
                if ($originalValue == 2) {
                    $modified[$slotIndex] = 1;
                    BaseUtil_Debug("HEAVY die: slot $slot changed 2 to 1", 7);
                }
                break;

            case 'FIVES':
                // All faces are 5
                $modified[$slotIndex] = 5;
                BaseUtil_Debug("FIVES die: slot $slot forced to 5", 7);
                break;
        }
    }

    return $modified;
}

/**
 * Apply score modifier effects after base scoring
 *
 * @param int $baseScore The base score from farkleScoreDice
 * @param array $diceValues The dice values that were scored
 * @param array $inventory Player's dice inventory
 * @return array ['score' => modified score, 'bonuses' => array of bonus descriptions]
 */
function Challenge_ApplyScoreModifiers($baseScore, $diceValues, $inventory) {
    $score = $baseScore;
    $bonuses = [];
    $multiplier = 1;

    foreach ($inventory as $slot => $die) {
        if (!$die || $die['internal_effect_type'] !== EFFECT_SCORE_MODIFIER) continue;

        $slotIndex = $slot - 1;
        $dieValue = isset($diceValues[$slotIndex]) ? $diceValues[$slotIndex] : 0;

        switch ($die['short_word']) {
            case 'DOUBLE':
                // 2x total score (stacks)
                $multiplier *= 2;
                $bonuses[] = "DOUBLE: 2x multiplier";
                break;

            case 'TRIPLE':
                // 3x score from this specific die
                if ($dieValue == 1) {
                    $bonus = 200; // 1 normally = 100, triple = 300, so +200
                    $score += $bonus;
                    $bonuses[] = "TRIPLE: +$bonus from die slot $slot";
                } elseif ($dieValue == 5) {
                    $bonus = 100; // 5 normally = 50, triple = 150, so +100
                    $score += $bonus;
                    $bonuses[] = "TRIPLE: +$bonus from die slot $slot";
                }
                break;

            case 'JACKPOT':
                // 2x score from this specific die
                if ($dieValue == 1) {
                    $bonus = 100; // 1 normally = 100, double = 200, so +100
                    $score += $bonus;
                    $bonuses[] = "JACKPOT: +$bonus from die slot $slot";
                } elseif ($dieValue == 5) {
                    $bonus = 50; // 5 normally = 50, double = 100, so +50
                    $score += $bonus;
                    $bonuses[] = "JACKPOT: +$bonus from die slot $slot";
                }
                break;

            case 'HOT':
                // +50 each time this die scores then rolls
                // This is tracked per-turn, handled separately
                break;

            case 'THREE':
                // 3s can score alone (30 pts each)
                if ($dieValue == 3) {
                    $bonus = 30;
                    $score += $bonus;
                    $bonuses[] = "THRICE: +$bonus from 3 in slot $slot";
                }
                break;
        }
    }

    // Apply multiplier at the end
    if ($multiplier > 1) {
        $score = $score * $multiplier;
    }

    return [
        'score' => $score,
        'bonuses' => $bonuses,
        'multiplier' => $multiplier
    ];
}

/**
 * Process farkle trigger effects when player farkles
 *
 * @param int $turnScore Current turn score before farkle
 * @param int $roundScore Current round score
 * @param int $numDiceSaved Number of dice saved this turn
 * @param array $inventory Player's dice inventory
 * @return array ['score' => points to award, 'money' => money earned, 'bank_round' => bool, 'effects' => descriptions]
 */
function Challenge_ProcessFarkleTriggers($turnScore, $roundScore, $numDiceSaved, $inventory) {
    $result = [
        'score' => 0,
        'money' => 0,
        'bank_round' => false,
        'effects' => []
    ];

    foreach ($inventory as $slot => $die) {
        if (!$die || $die['internal_effect_type'] !== EFFECT_FARKLE_TRIGGER) continue;

        switch ($die['short_word']) {
            case 'PHOENIX':
                // Score half of turn score instead of losing it all
                $half = floor($turnScore / 2);
                if ($half > $result['score']) {
                    $result['score'] = $half;
                    $result['effects'][] = "PHOENIX: Saved $half points!";
                }
                break;

            case 'BADLUCK':
                // If farkle with 1 or less dice saved, score 1000
                if ($numDiceSaved <= 1) {
                    $result['score'] = max($result['score'], 1000);
                    $result['effects'][] = "BAD LUCK: Scored 1000 points!";
                }
                break;

            case 'FARK$':
                // Earn $3 when you farkle
                $result['money'] += 3;
                $result['effects'][] = "FARKLE PAYDAY: Earned $3!";
                break;

            case 'GAMBLE':
                // Auto-bank round score on farkle
                if ($roundScore > 0) {
                    $result['bank_round'] = true;
                    $result['effects'][] = "GAMBLER: Banked $roundScore round points!";
                }
                break;

            case 'DARE':
                // +200 to next bank (tracked in session/run state)
                // This stacks per farkle
                $result['dare_bonus'] = 200;
                $result['effects'][] = "DAREDEVIL: +200 to next bank!";
                break;

            case 'CUSHION':
                // Keep half of turn score
                $half = floor($turnScore / 2);
                if ($half > $result['score']) {
                    $result['score'] = $half;
                    $result['effects'][] = "CUSHION: Kept $half points!";
                }
                break;

            case 'CHUTE':
                // Auto-bank round score (loses turn score)
                if ($roundScore > 0) {
                    $result['bank_round'] = true;
                    $result['effects'][] = "PARACHUTE: Banked $roundScore round points!";
                }
                break;
        }
    }

    return $result;
}

/**
 * Calculate money modifiers for dice saved
 *
 * @param int $numDiceSaved Number of dice saved this action
 * @param array $inventory Player's dice inventory
 * @return array ['money' => extra money earned, 'effects' => descriptions]
 */
function Challenge_CalculateMoneyModifiers($numDiceSaved, $inventory) {
    $result = [
        'money' => 0,
        'effects' => []
    ];

    foreach ($inventory as $slot => $die) {
        if (!$die || $die['internal_effect_type'] !== EFFECT_MONEY_MODIFIER) continue;

        switch ($die['short_word']) {
            case 'MIDAS':
                // +$1 per die saved
                $bonus = $numDiceSaved;
                $result['money'] += $bonus;
                $result['effects'][] = "MIDAS: +\$$bonus for $numDiceSaved dice saved!";
                break;
        }
    }

    return $result;
}

/**
 * Main scoring function for challenge mode
 * Wraps farkleScoreDice with effect processing
 *
 * @param array $savedDice The saved dice values
 * @param int $playerid The player ID
 * @param int $runId The challenge run ID (null for standard games)
 * @return array ['score' => final score, 'bonuses' => bonus descriptions, 'money' => extra money]
 */
function Challenge_ScoreDice($savedDice, $playerid, $runId = null) {
    // If not challenge mode, use standard scoring
    if (!$runId) {
        return [
            'score' => farkleScoreDice($savedDice, $playerid),
            'bonuses' => [],
            'money' => 0,
            'effects_applied' => false
        ];
    }

    // Get player's dice inventory
    $inventory = Challenge_GetPlayerDice($runId);

    if (empty($inventory)) {
        // No special dice, use standard scoring
        return [
            'score' => farkleScoreDice($savedDice, $playerid),
            'bonuses' => [],
            'money' => 0,
            'effects_applied' => false
        ];
    }

    // Step 1: Apply face changers to modify dice values
    $modifiedDice = Challenge_ApplyFaceChangers($savedDice, $inventory);

    // Step 2: Calculate base score with modified dice
    $baseScore = farkleScoreDice($modifiedDice, $playerid);

    // Step 3: Apply score modifiers
    $scoreResult = Challenge_ApplyScoreModifiers($baseScore, $modifiedDice, $inventory);

    // Step 4: Calculate money modifiers
    $numDiceSaved = count(array_filter($savedDice, function($v) { return $v > 0; }));
    $moneyResult = Challenge_CalculateMoneyModifiers($numDiceSaved, $inventory);

    // Combine all bonuses
    $allBonuses = array_merge($scoreResult['bonuses'], $moneyResult['effects']);

    return [
        'score' => $scoreResult['score'],
        'bonuses' => $allBonuses,
        'money' => $moneyResult['money'],
        'multiplier' => $scoreResult['multiplier'],
        'effects_applied' => true,
        'original_dice' => $savedDice,
        'modified_dice' => $modifiedDice
    ];
}

/**
 * Update challenge run money
 *
 * @param int $runId The challenge run ID
 * @param int $amount Amount to add (can be negative)
 * @return int New money total
 */
function Challenge_UpdateMoney($runId, $amount) {
    $dbh = db_connect();

    $sql = "UPDATE farkle_challenge_runs
            SET money = money + :amount
            WHERE run_id = :run_id
            RETURNING money";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([':amount' => $amount, ':run_id' => $runId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? $result['money'] : 0;
}

/**
 * Get effect type for a dice by short word
 * Maps dice short words to their effect types
 *
 * @param string $shortWord The dice short word
 * @return string The effect type constant
 */
function Challenge_GetEffectType($shortWord) {
    $faceChangers = ['LUCKY', 'HEAVY', 'FIVES'];
    $scoreModifiers = ['DOUBLE', 'TRIPLE', 'JACKPOT', 'HOT', 'THREE'];
    $farkleTriggers = ['PHOENIX', 'BADLUCK', 'FARK$', 'GAMBLE', 'DARE', 'CUSHION', 'CHUTE'];
    $moneyModifiers = ['MIDAS'];

    if (in_array($shortWord, $faceChangers)) return EFFECT_FACE_CHANGER;
    if (in_array($shortWord, $scoreModifiers)) return EFFECT_SCORE_MODIFIER;
    if (in_array($shortWord, $farkleTriggers)) return EFFECT_FARKLE_TRIGGER;
    if (in_array($shortWord, $moneyModifiers)) return EFFECT_MONEY_MODIFIER;

    return '';
}

?>
