<?php
/*
	farkleShop.php
	Desc: Functions for the Challenge Mode dice shop system

	Date		Editor		Change
	----------	----------	----------------------------
	19-Jan-2026	MAS			Initial version for Challenge Mode shop
*/

require_once(__DIR__ . '/../includes/dbutil.php');

/**
 * GetShopDice
 *
 * Get random unique dice that player doesn't already own
 *
 * @param int $runId The current challenge run ID
 * @param int $count Number of dice to return (default 3)
 * @return array|null Array of dice objects or null on error
 *
 * Each die includes: dice_type_id, name, tier, price, effect (description), category (effect_type)
 */
function GetShopDice($runId, $count = 3) {
	$db = db_connect();

	try {
		// Get player's current dice inventory
		$stmt = $db->prepare("
			SELECT dice_type_id
			FROM farkle_challenge_dice_inventory
			WHERE run_id = :run_id
		");
		$stmt->execute(['run_id' => $runId]);
		$ownedDice = $stmt->fetchAll(PDO::FETCH_COLUMN);

		// Build query to get available dice (not owned, enabled only)
		$query = "
			SELECT
				dice_type_id,
				name,
				tier,
				price,
				description as effect,
				effect_type as category
			FROM farkle_challenge_dice_types
			WHERE enabled = true
		";

		// Exclude owned dice if player has any
		if (!empty($ownedDice)) {
			$placeholders = implode(',', array_fill(0, count($ownedDice), '?'));
			$query .= " AND dice_type_id NOT IN ($placeholders)";
		}

		$query .= " ORDER BY RANDOM() LIMIT :count";

		$stmt = $db->prepare($query);

		// Bind owned dice IDs if any
		$paramIndex = 1;
		foreach ($ownedDice as $diceId) {
			$stmt->bindValue($paramIndex++, $diceId, PDO::PARAM_INT);
		}

		// Bind count
		$stmt->bindValue(':count', $count, PDO::PARAM_INT);

		$stmt->execute();
		$availableDice = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return $availableDice;

	} catch (PDOException $e) {
		error_log("GetShopDice error: " . $e->getMessage());
		return null;
	}
}

/**
 * PurchaseDice
 *
 * Purchase a die and replace specified inventory slot
 *
 * @param int $runId The current challenge run ID
 * @param int $diceTypeId The dice type to purchase
 * @param int $slotNumber Inventory slot to replace (1-6)
 * @return array Array with new money, updated inventory, or error message
 */
function PurchaseDice($runId, $diceTypeId, $slotNumber) {
	$db = db_connect();

	try {
		// Validate inputs
		$runId = intval($runId);
		$diceTypeId = intval($diceTypeId);
		$slotNumber = intval($slotNumber);

		if ($slotNumber < 1 || $slotNumber > 6) {
			return ['error' => 'Invalid slot number. Must be 1-6.'];
		}

		// Get player's current money
		$stmt = $db->prepare("
			SELECT current_money
			FROM farkle_challenge_runs
			WHERE run_id = :run_id
		");
		$stmt->execute(['run_id' => $runId]);
		$run = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$run) {
			return ['error' => 'Challenge run not found.'];
		}

		$currentMoney = $run['current_money'];

		// Get dice price and verify it exists
		$stmt = $db->prepare("
			SELECT price, name
			FROM farkle_challenge_dice_types
			WHERE dice_type_id = :dice_type_id AND enabled = true
		");
		$stmt->execute(['dice_type_id' => $diceTypeId]);
		$dice = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$dice) {
			return ['error' => 'Dice type not found or not available.'];
		}

		$price = $dice['price'];

		// Validate player has enough money
		if ($currentMoney < $price) {
			return [
				'error' => 'Not enough money.',
				'current_money' => $currentMoney,
				'price' => $price
			];
		}

		// Check if dice is already owned
		$stmt = $db->prepare("
			SELECT COUNT(*)
			FROM farkle_challenge_dice_inventory
			WHERE run_id = :run_id AND dice_type_id = :dice_type_id
		");
		$stmt->execute([
			'run_id' => $runId,
			'dice_type_id' => $diceTypeId
		]);
		$alreadyOwned = $stmt->fetchColumn() > 0;

		if ($alreadyOwned) {
			return ['error' => 'You already own this die.'];
		}

		// Begin transaction
		$db->beginTransaction();

		try {
			// Update player's money
			$stmt = $db->prepare("
				UPDATE farkle_challenge_runs
				SET current_money = current_money - :price
				WHERE run_id = :run_id
			");
			$stmt->execute([
				'price' => $price,
				'run_id' => $runId
			]);

			// Update inventory slot
			$stmt = $db->prepare("
				UPDATE farkle_challenge_dice_inventory
				SET dice_type_id = :dice_type_id
				WHERE run_id = :run_id AND dice_slot = :slot_number
			");
			$stmt->execute([
				'dice_type_id' => $diceTypeId,
				'run_id' => $runId,
				'slot_number' => $slotNumber
			]);

			// Commit transaction
			$db->commit();

			// Get updated money
			$newMoney = $currentMoney - $price;

			// Get updated inventory
			$stmt = $db->prepare("
				SELECT
					inv.dice_slot,
					inv.dice_type_id,
					dt.name,
					dt.tier,
					dt.price,
					dt.description as effect,
					dt.effect_type as category
				FROM farkle_challenge_dice_inventory inv
				JOIN farkle_challenge_dice_types dt ON inv.dice_type_id = dt.dice_type_id
				WHERE inv.run_id = :run_id
				ORDER BY inv.dice_slot
			");
			$stmt->execute(['run_id' => $runId]);
			$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

			return [
				'success' => true,
				'new_money' => $newMoney,
				'inventory' => $inventory,
				'purchased_dice' => $dice['name']
			];

		} catch (PDOException $e) {
			// Rollback on error
			$db->rollBack();
			error_log("PurchaseDice transaction error: " . $e->getMessage());
			return ['error' => 'Transaction failed. Please try again.'];
		}

	} catch (PDOException $e) {
		error_log("PurchaseDice error: " . $e->getMessage());
		return ['error' => 'An error occurred. Please try again.'];
	}
}

/**
 * ValidatePurchase
 *
 * Check if purchase is valid without executing it
 *
 * @param int $runId The current challenge run ID
 * @param int $diceTypeId The dice type to validate
 * @return array Validation object with can_afford, already_owned, price, current_money
 */
function ValidatePurchase($runId, $diceTypeId) {
	$db = db_connect();

	try {
		$runId = intval($runId);
		$diceTypeId = intval($diceTypeId);

		// Get player's current money
		$stmt = $db->prepare("
			SELECT current_money
			FROM farkle_challenge_runs
			WHERE run_id = :run_id
		");
		$stmt->execute(['run_id' => $runId]);
		$run = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$run) {
			return [
				'error' => 'Challenge run not found.',
				'can_afford' => false,
				'already_owned' => false
			];
		}

		$currentMoney = $run['current_money'];

		// Get dice price
		$stmt = $db->prepare("
			SELECT price
			FROM farkle_challenge_dice_types
			WHERE dice_type_id = :dice_type_id AND enabled = true
		");
		$stmt->execute(['dice_type_id' => $diceTypeId]);
		$dice = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$dice) {
			return [
				'error' => 'Dice type not found.',
				'can_afford' => false,
				'already_owned' => false
			];
		}

		$price = $dice['price'];

		// Check if already owned
		$stmt = $db->prepare("
			SELECT COUNT(*)
			FROM farkle_challenge_dice_inventory
			WHERE run_id = :run_id AND dice_type_id = :dice_type_id
		");
		$stmt->execute([
			'run_id' => $runId,
			'dice_type_id' => $diceTypeId
		]);
		$alreadyOwned = $stmt->fetchColumn() > 0;

		return [
			'can_afford' => ($currentMoney >= $price),
			'already_owned' => $alreadyOwned,
			'price' => $price,
			'current_money' => $currentMoney
		];

	} catch (PDOException $e) {
		error_log("ValidatePurchase error: " . $e->getMessage());
		return [
			'error' => 'An error occurred.',
			'can_afford' => false,
			'already_owned' => false
		];
	}
}

?>
