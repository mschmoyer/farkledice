<?php
/*
	farkleBotAI.php
	Desc: Bot AI decision-making algorithms for keeper selection and roll/bank decisions.
	      Implements three difficulty levels: Easy, Medium, and Hard.

	Changelog:
	18-Jan-2026		mas		Initial implementation with three difficulty levels
*/

require_once('farkleDiceScoring.php');

/**
 * Get all possible scoring combinations from a dice roll
 *
 * This function analyzes a dice roll and returns ALL possible scoring combinations,
 * not just the optimal one. This allows bot algorithms to choose between options.
 *
 * @param array $diceRoll Array of dice values (1-6)
 * @return array Array of combinations: [['dice' => [values], 'points' => score, 'description' => string], ...]
 */
function Bot_GetAllScoringCombinations($diceRoll) {
	$combinations = [];

	// Count each die value
	$counts = array_fill(1, 6, 0);
	foreach ($diceRoll as $die) {
		if ($die >= 1 && $die <= 6) {
			$counts[$die]++;
		}
	}

	// Check for special combinations first (6 dice only)
	if (count($diceRoll) == 6) {
		// Check for straight (1,2,3,4,5,6)
		if ($counts[1] == 1 && $counts[2] == 1 && $counts[3] == 1 &&
		    $counts[4] == 1 && $counts[5] == 1 && $counts[6] == 1) {
			$combinations[] = [
				'dice' => $diceRoll,
				'points' => 1000,
				'description' => 'straight (1-6)'
			];
		}

		// Check for three pairs
		$pairCount = 0;
		$pairDice = [];
		for ($i = 1; $i <= 6; $i++) {
			if ($counts[$i] == 2) {
				$pairCount++;
				$pairDice[] = $i;
				$pairDice[] = $i;
			}
		}
		if ($pairCount == 3) {
			$combinations[] = [
				'dice' => $pairDice,
				'points' => 750,
				'description' => 'three pairs'
			];
		}

		// Check for two triplets
		$tripletCount = 0;
		$tripletDice = [];
		for ($i = 1; $i <= 6; $i++) {
			if ($counts[$i] == 3) {
				$tripletCount++;
				$tripletDice[] = $i;
				$tripletDice[] = $i;
				$tripletDice[] = $i;
			}
		}
		if ($tripletCount == 2) {
			$combinations[] = [
				'dice' => $tripletDice,
				'points' => 2500,
				'description' => 'two triplets'
			];
		}
	}

	// Generate individual scoring combinations
	// Strategy: Try all possible subsets that could score

	// Six of a kind
	for ($i = 1; $i <= 6; $i++) {
		if ($counts[$i] == 6) {
			$dice = array_fill(0, 6, $i);
			$points = ($i == 1) ? 4000 : ($i * 100 * 4); // 1000 * 4 for ones, value * 100 * 4 for others
			$combinations[] = [
				'dice' => $dice,
				'points' => $points,
				'description' => "six {$i}s"
			];
		}
	}

	// Five of a kind
	for ($i = 1; $i <= 6; $i++) {
		if ($counts[$i] >= 5) {
			$dice = array_fill(0, 5, $i);
			$points = ($i == 1) ? 3000 : ($i * 100 * 3); // 1000 * 3 for ones, value * 100 * 3 for others
			$combinations[] = [
				'dice' => $dice,
				'points' => $points,
				'description' => "five {$i}s"
			];
		}
	}

	// Four of a kind
	for ($i = 1; $i <= 6; $i++) {
		if ($counts[$i] >= 4) {
			$dice = array_fill(0, 4, $i);
			$points = ($i == 1) ? 2000 : ($i * 100 * 2); // 1000 * 2 for ones, value * 100 * 2 for others
			$combinations[] = [
				'dice' => $dice,
				'points' => $points,
				'description' => "four {$i}s"
			];
		}
	}

	// Three of a kind
	for ($i = 1; $i <= 6; $i++) {
		if ($counts[$i] >= 3) {
			$dice = array_fill(0, 3, $i);
			$points = ($i == 1) ? 1000 : ($i * 100); // 1000 for three ones, value * 100 for others
			$combinations[] = [
				'dice' => $dice,
				'points' => $points,
				'description' => "three {$i}s"
			];
		}
	}

	// Individual 1s (if not part of a triplet combination above)
	for ($numOnes = 1; $numOnes <= min(2, $counts[1]); $numOnes++) {
		$dice = array_fill(0, $numOnes, 1);
		$points = $numOnes * 100;
		$plural = ($numOnes == 1) ? '' : 's';
		$combinations[] = [
			'dice' => $dice,
			'points' => $points,
			'description' => "{$numOnes} one{$plural}"
		];
	}

	// Individual 5s (if not part of a triplet combination above)
	for ($numFives = 1; $numFives <= min(2, $counts[5]); $numFives++) {
		$dice = array_fill(0, $numFives, 5);
		$points = $numFives * 50;
		$plural = ($numFives == 1) ? '' : 's';
		$combinations[] = [
			'dice' => $dice,
			'points' => $points,
			'description' => "{$numFives} five{$plural}"
		];
	}

	// Combination: Triple + ones/fives
	// Example: [1,1,1,5] or [2,2,2,1,1] or [3,3,3,5,5]
	for ($i = 1; $i <= 6; $i++) {
		if ($counts[$i] >= 3) {
			// Add ones
			for ($numOnes = 1; $numOnes <= min(2, $counts[1]); $numOnes++) {
				if ($i == 1) continue; // Don't double-count ones used in triplet

				$dice = array_merge(array_fill(0, 3, $i), array_fill(0, $numOnes, 1));
				$triplePoints = ($i == 1) ? 1000 : ($i * 100);
				$points = $triplePoints + ($numOnes * 100);
				$combinations[] = [
					'dice' => $dice,
					'points' => $points,
					'description' => "three {$i}s + {$numOnes} one" . ($numOnes > 1 ? 's' : '')
				];
			}

			// Add fives
			for ($numFives = 1; $numFives <= min(2, $counts[5]); $numFives++) {
				if ($i == 5) continue; // Don't double-count fives used in triplet

				$dice = array_merge(array_fill(0, 3, $i), array_fill(0, $numFives, 5));
				$triplePoints = ($i == 1) ? 1000 : ($i * 100);
				$points = $triplePoints + ($numFives * 50);
				$combinations[] = [
					'dice' => $dice,
					'points' => $points,
					'description' => "three {$i}s + {$numFives} five" . ($numFives > 1 ? 's' : '')
				];
			}

			// Add both ones and fives
			if ($i != 1 && $i != 5 && $counts[1] >= 1 && $counts[5] >= 1) {
				$dice = array_merge(array_fill(0, 3, $i), [1], [5]);
				$triplePoints = ($i == 1) ? 1000 : ($i * 100);
				$points = $triplePoints + 100 + 50;
				$combinations[] = [
					'dice' => $dice,
					'points' => $points,
					'description' => "three {$i}s + one 1 + one 5"
				];
			}
		}
	}

	// Combination: ones + fives (without triplet)
	if ($counts[1] >= 1 && $counts[5] >= 1) {
		for ($numOnes = 1; $numOnes <= min(2, $counts[1]); $numOnes++) {
			for ($numFives = 1; $numFives <= min(2, $counts[5]); $numFives++) {
				$dice = array_merge(array_fill(0, $numOnes, 1), array_fill(0, $numFives, 5));
				$points = ($numOnes * 100) + ($numFives * 50);
				$combinations[] = [
					'dice' => $dice,
					'points' => $points,
					'description' => "{$numOnes} one" . ($numOnes > 1 ? 's' : '') . " + {$numFives} five" . ($numFives > 1 ? 's' : '')
				];
			}
		}
	}

	// Sort by points descending, so highest-scoring combos are first
	usort($combinations, function($a, $b) {
		return $b['points'] - $a['points'];
	});

	return $combinations;
}

/**
 * Calculate the probability of farkling based on number of dice
 *
 * These probabilities are calculated based on Farkle rules where only
 * 1s, 5s, and three-of-a-kind score.
 *
 * @param int $numDice Number of dice to roll (1-6)
 * @return float Probability of farkle (0.0 to 1.0)
 */
function Bot_CalculateFarkleProbability($numDice) {
	// Lookup table of empirically calculated farkle probabilities
	$probabilities = [
		1 => 0.6667,  // 66.7% - only 1 and 5 score
		2 => 0.4444,  // 44.4%
		3 => 0.2778,  // 27.8%
		4 => 0.1543,  // 15.4%
		5 => 0.0772,  // 7.7%
		6 => 0.0231   // 2.3%
	];

	return $probabilities[$numDice] ?? 0.0;
}

/**
 * Estimate expected points from rolling N dice
 *
 * This is an approximation based on average scoring outcomes
 *
 * @param int $numDice Number of dice to roll (1-6)
 * @return int Estimated expected points
 */
function Bot_EstimateExpectedPoints($numDice) {
	$expectedPoints = [
		1 => 83,   // Rolling 1 die: 1/6 chance of 100, 1/6 chance of 50
		2 => 100,
		3 => 150,
		4 => 200,
		5 => 250,
		6 => 350
	];

	return $expectedPoints[$numDice] ?? 0;
}

// ============================================================================
// EASY DIFFICULTY ALGORITHMS
// ============================================================================

/**
 * Easy Algorithm: Choose keepers
 *
 * Strategy:
 * - 30% chance to pick a random scoring combination (mistake)
 * - 70% chance to pick the highest-scoring combination
 *
 * @param array $diceRoll Array of dice values
 * @param int $turnScore Current turn score before this roll
 * @param int $diceLeft Number of dice rolled
 * @return array|null ['dice' => [values], 'points' => score, 'description' => string] or null if farkle
 */
function Bot_Easy_ChooseKeepers($diceRoll, $turnScore, $diceLeft) {
	$combos = Bot_GetAllScoringCombinations($diceRoll);

	if (empty($combos)) {
		return null; // Farkle!
	}

	// 30% chance to make a "mistake" and pick a random combo
	if (rand(1, 100) <= 30) {
		return $combos[array_rand($combos)];
	}

	// 70% chance to pick the highest-scoring combo (first in sorted array)
	return $combos[0];
}

/**
 * Easy Algorithm: Decide whether to roll again or bank
 *
 * Strategy:
 * - Bank if turnScore >= random threshold (300-450)
 * - Bank if only 1-2 dice remaining (high farkle risk)
 * - Otherwise roll again
 *
 * @param int $turnScore Current turn score
 * @param int $diceRemaining Number of dice remaining for next roll
 * @param array $gameContext Game state (scores, rounds, etc.)
 * @return bool True to roll again, false to bank
 */
function Bot_Easy_ShouldRollAgain($turnScore, $diceRemaining, $gameContext) {
	// Simple threshold: bank if we have enough points
	$threshold = rand(300, 450);

	if ($turnScore >= $threshold) {
		return false; // Bank it!
	}

	// Avoid rolling with only 1-2 dice (high farkle probability)
	if ($diceRemaining <= 2) {
		return false; // Bank it!
	}

	// Otherwise, keep rolling
	return true;
}

// ============================================================================
// MEDIUM DIFFICULTY ALGORITHMS
// ============================================================================

/**
 * Medium Algorithm: Choose keepers
 *
 * Strategy:
 * - Calculate points-per-die ratio for each combination
 * - Choose the combination with the best ratio
 * - This leaves more dice for the next roll, which is strategic
 *
 * @param array $diceRoll Array of dice values
 * @param int $turnScore Current turn score before this roll
 * @param int $diceLeft Number of dice rolled
 * @return array|null ['dice' => [values], 'points' => score, 'description' => string] or null if farkle
 */
function Bot_Medium_ChooseKeepers($diceRoll, $turnScore, $diceLeft) {
	$combos = Bot_GetAllScoringCombinations($diceRoll);

	if (empty($combos)) {
		return null; // Farkle!
	}

	// Calculate points-per-die ratio for each combo
	$bestCombo = null;
	$bestRatio = 0;

	foreach ($combos as $combo) {
		$numDiceUsed = count($combo['dice']);
		$ratio = $combo['points'] / $numDiceUsed;

		// Bonus for hot dice (using all remaining dice, get to roll 6 again)
		if ($numDiceUsed == $diceLeft) {
			$ratio *= 1.2; // 20% bonus for hot dice potential
		}

		if ($ratio > $bestRatio) {
			$bestRatio = $ratio;
			$bestCombo = $combo;
		}
	}

	return $bestCombo;
}

/**
 * Medium Algorithm: Decide whether to roll again or bank
 *
 * Strategy:
 * - Calculate expected value of rolling again
 * - Consider farkle probability
 * - Account for game position (ahead vs behind)
 *
 * @param int $turnScore Current turn score
 * @param int $diceRemaining Number of dice remaining for next roll
 * @param array $gameContext Game state (scores, rounds, etc.)
 * @return bool True to roll again, false to bank
 */
function Bot_Medium_ShouldRollAgain($turnScore, $diceRemaining, $gameContext) {
	// Get farkle probability
	$farkleProbability = Bot_CalculateFarkleProbability($diceRemaining);

	// Get expected points from next roll
	$expectedPoints = Bot_EstimateExpectedPoints($diceRemaining);

	// Calculate expected value of rolling again
	// EV = (probability of success * (current + expected points)) - (probability of farkle * current points)
	$successProbability = 1.0 - $farkleProbability;
	$evRoll = ($successProbability * ($turnScore + $expectedPoints)) - ($farkleProbability * $turnScore);

	// Value of banking now
	$evBank = $turnScore;

	// Basic decision: roll if expected value is higher
	if ($evRoll > $evBank) {
		// Additional check: consider game position
		$myScore = $gameContext['my_score'] ?? 0;
		$opponentScore = $gameContext['opponent_score'] ?? 0;

		// If we're significantly ahead, be more conservative
		if ($myScore - $opponentScore > 2000 && $turnScore >= 350) {
			return false; // Bank to protect lead
		}

		// If we're behind, be more aggressive
		if ($opponentScore - $myScore > 2000 && $turnScore < 500) {
			return true; // Keep rolling to catch up
		}

		return true; // Roll again
	}

	return false; // Bank it
}

// ============================================================================
// HARD DIFFICULTY ALGORITHMS
// ============================================================================

/**
 * Hard Algorithm: Choose keepers
 *
 * Strategy:
 * - Full expected value calculation considering future rolls
 * - Look ahead to simulate continuing the turn
 * - Choose the combo that maximizes expected total turn score
 *
 * @param array $diceRoll Array of dice values
 * @param int $turnScore Current turn score before this roll
 * @param int $diceLeft Number of dice rolled
 * @return array|null ['dice' => [values], 'points' => score, 'description' => string] or null if farkle
 */
function Bot_Hard_ChooseKeepers($diceRoll, $turnScore, $diceLeft) {
	$combos = Bot_GetAllScoringCombinations($diceRoll);

	if (empty($combos)) {
		return null; // Farkle!
	}

	// For each combo, calculate expected value of continuing with remaining dice
	$bestCombo = null;
	$bestExpectedValue = 0;

	foreach ($combos as $combo) {
		$numDiceUsed = count($combo['dice']);
		$diceRemaining = $diceLeft - $numDiceUsed;

		// Hot dice: if we use all dice, we get to roll 6 again
		if ($diceRemaining == 0) {
			$diceRemaining = 6;
		}

		// Calculate EV of next roll
		$farkleProbability = Bot_CalculateFarkleProbability($diceRemaining);
		$expectedNextPoints = Bot_EstimateExpectedPoints($diceRemaining);

		// Expected value = immediate points + (success probability * expected future points)
		$expectedValue = $combo['points'] + ((1.0 - $farkleProbability) * $expectedNextPoints);

		// Bonus for hot dice (more opportunities)
		if ($diceLeft == $numDiceUsed) {
			$expectedValue *= 1.3; // 30% bonus for hot dice
		}

		if ($expectedValue > $bestExpectedValue) {
			$bestExpectedValue = $expectedValue;
			$bestCombo = $combo;
		}
	}

	return $bestCombo;
}

/**
 * Hard Algorithm: Decide whether to roll again or bank
 *
 * Strategy:
 * - Game-theoretic approach considering game state
 * - Conservative when leading, aggressive when trailing
 * - Optimal risk/reward calculation based on rounds remaining
 *
 * @param int $turnScore Current turn score
 * @param int $diceRemaining Number of dice remaining for next roll
 * @param array $gameContext Game state (scores, rounds, etc.)
 * @return bool True to roll again, false to bank
 */
function Bot_Hard_ShouldRollAgain($turnScore, $diceRemaining, $gameContext) {
	$myScore = $gameContext['my_score'] ?? 0;
	$opponentScore = $gameContext['opponent_score'] ?? 0;
	$currentRound = $gameContext['current_round'] ?? 1;
	$totalRounds = $gameContext['total_rounds'] ?? 10;

	// Calculate game state factors
	$scoreDifference = $myScore - $opponentScore;
	$roundsRemaining = $totalRounds - $currentRound;
	$isLeading = $scoreDifference > 0;

	// Get probabilities
	$farkleProbability = Bot_CalculateFarkleProbability($diceRemaining);
	$expectedPoints = Bot_EstimateExpectedPoints($diceRemaining);

	// Calculate expected value
	$successProbability = 1.0 - $farkleProbability;
	$evRoll = ($successProbability * ($turnScore + $expectedPoints)) - ($farkleProbability * $turnScore);
	$evBank = $turnScore;

	// Base decision threshold
	$evDifference = $evRoll - $evBank;

	// Game-theoretic adjustments

	// 1. Leading strategy: be conservative
	if ($isLeading) {
		$leadMargin = $scoreDifference;

		// Strong lead: very conservative
		if ($leadMargin > 3000) {
			if ($turnScore >= 300) {
				return false; // Bank with any decent score
			}
		}
		// Moderate lead: somewhat conservative
		else if ($leadMargin > 1500) {
			if ($turnScore >= 400 && $farkleProbability > 0.15) {
				return false; // Bank if risk is high
			}
		}
	}

	// 2. Trailing strategy: be aggressive
	else {
		$deficit = abs($scoreDifference);

		// Large deficit: very aggressive
		if ($deficit > 3000) {
			// Need big scores, keep rolling unless risk is extreme
			if ($farkleProbability < 0.5 && $turnScore < 800) {
				return true; // Keep rolling to catch up
			}
		}
		// Moderate deficit: somewhat aggressive
		else if ($deficit > 1500) {
			if ($turnScore < 600 && $farkleProbability < 0.3) {
				return true; // Keep rolling
			}
		}
	}

	// 3. End-game considerations
	if ($roundsRemaining <= 2) {
		// Near end of game, optimize for winning
		$projectedMyScore = $myScore + $turnScore;
		$estimatedOpponentScore = $opponentScore + (300 * $roundsRemaining); // Assume opponent scores 300/round

		// If we need more to win, be aggressive
		if ($projectedMyScore < $estimatedOpponentScore) {
			if ($farkleProbability < 0.4) {
				return true; // Need more points
			}
		}
		// If we're on track to win, be conservative
		else {
			if ($turnScore >= 350) {
				return false; // Bank it and protect lead
			}
		}
	}

	// 4. Risk assessment based on dice count
	if ($diceRemaining <= 2 && $turnScore >= 400) {
		// High risk with few dice, bank if we have decent points
		return false;
	}

	if ($diceRemaining >= 5 && $turnScore < 300) {
		// Low risk with many dice, keep rolling for more points
		return true;
	}

	// Default decision: follow expected value
	return $evDifference > 0;
}

// ============================================================================
// MAIN ENTRY POINT
// ============================================================================

/**
 * Main decision function for bot play
 *
 * This is the primary entry point called by the game engine.
 * It routes to the appropriate algorithm based on bot difficulty.
 *
 * @param array $botPlayer Bot player record with 'bot_algorithm' field
 * @param array $gameData Complete game state
 * @param array $diceRoll Current dice roll to evaluate
 * @param int $turnScore Current turn score before this roll
 * @param int $diceRemaining Number of dice in this roll
 * @return array Decision structure:
 *   - 'keeper_choice': null (farkle) or ['dice' => [...], 'points' => N, 'description' => '...']
 *   - 'should_roll': bool (only valid if keeper_choice is not null)
 *   - 'algorithm': string (which algorithm was used)
 */
function Bot_MakeDecision($botPlayer, $gameData, $diceRoll, $turnScore, $diceRemaining) {
	$algorithm = $botPlayer['bot_algorithm'] ?? 'medium';

	// Step 1: Choose which dice to keep
	$keeperChoice = null;
	switch ($algorithm) {
		case 'easy':
			$keeperChoice = Bot_Easy_ChooseKeepers($diceRoll, $turnScore, $diceRemaining);
			break;
		case 'medium':
			$keeperChoice = Bot_Medium_ChooseKeepers($diceRoll, $turnScore, $diceRemaining);
			break;
		case 'hard':
			$keeperChoice = Bot_Hard_ChooseKeepers($diceRoll, $turnScore, $diceRemaining);
			break;
		default:
			$keeperChoice = Bot_Medium_ChooseKeepers($diceRoll, $turnScore, $diceRemaining);
	}

	// If farkle, return immediately
	if ($keeperChoice === null) {
		return [
			'keeper_choice' => null,
			'should_roll' => false,
			'algorithm' => $algorithm,
			'farkled' => true
		];
	}

	// Step 2: Decide whether to roll again or bank
	$newTurnScore = $turnScore + $keeperChoice['points'];
	$newDiceRemaining = $diceRemaining - count($keeperChoice['dice']);

	// Hot dice: if we used all dice, we get 6 again
	if ($newDiceRemaining == 0) {
		$newDiceRemaining = 6;
	}

	// Build game context for roll decision
	$gameContext = [
		'my_score' => $gameData['bot_score'] ?? 0,
		'opponent_score' => $gameData['opponent_score'] ?? 0,
		'current_round' => $gameData['current_round'] ?? 1,
		'total_rounds' => $gameData['total_rounds'] ?? 10
	];

	$shouldRoll = false;
	switch ($algorithm) {
		case 'easy':
			$shouldRoll = Bot_Easy_ShouldRollAgain($newTurnScore, $newDiceRemaining, $gameContext);
			break;
		case 'medium':
			$shouldRoll = Bot_Medium_ShouldRollAgain($newTurnScore, $newDiceRemaining, $gameContext);
			break;
		case 'hard':
			$shouldRoll = Bot_Hard_ShouldRollAgain($newTurnScore, $newDiceRemaining, $gameContext);
			break;
		default:
			$shouldRoll = Bot_Medium_ShouldRollAgain($newTurnScore, $newDiceRemaining, $gameContext);
	}

	return [
		'keeper_choice' => $keeperChoice,
		'should_roll' => $shouldRoll,
		'algorithm' => $algorithm,
		'farkled' => false,
		'new_turn_score' => $newTurnScore,
		'new_dice_remaining' => $newDiceRemaining
	];
}

?>
