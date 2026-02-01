<?php
/**
 * Integration tests for Farkle game flow.
 *
 * Tests the core 10-round game flow by calling PHP game functions directly.
 * This is a PHPUnit conversion of the original test/api_game_flow_test.php
 * that tested via HTTP API.
 */
namespace Tests\Integration;

use Tests\DatabaseTestCase;
use PDO;

// Include game functions
require_once __DIR__ . '/../../wwwroot/farkleGameFuncs.php';

class GameFlowTest extends DatabaseTestCase
{
    private int $player1Id;
    private int $player2Id;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test players
        $this->player1Id = $this->createTestPlayer('gameflow_p1');
        $this->player2Id = $this->createTestPlayer('gameflow_p2');

        // Set up the username for notifications (not critical but prevents errors)
        $_SESSION['username'] = 'test_user';

        // Initialize farkle session array
        $_SESSION['farkle'] = [];
    }

    /**
     * Test that game constants are properly defined
     */
    public function testGameConstantsAreDefined(): void
    {
        $this->assertTrue(defined('GAME_MODE_STANDARD'), 'GAME_MODE_STANDARD should be defined');
        $this->assertTrue(defined('GAME_MODE_10ROUND'), 'GAME_MODE_10ROUND should be defined');
        $this->assertEquals(1, GAME_MODE_STANDARD, 'GAME_MODE_STANDARD should be 1');
        $this->assertEquals(2, GAME_MODE_10ROUND, 'GAME_MODE_10ROUND should be 2');

        $this->assertTrue(defined('GAME_WITH_RANDOM'), 'GAME_WITH_RANDOM should be defined');
        $this->assertTrue(defined('GAME_WITH_FRIENDS'), 'GAME_WITH_FRIENDS should be defined');
        $this->assertTrue(defined('GAME_WITH_SOLO'), 'GAME_WITH_SOLO should be defined');
        $this->assertEquals(0, GAME_WITH_RANDOM, 'GAME_WITH_RANDOM should be 0');
        $this->assertEquals(1, GAME_WITH_FRIENDS, 'GAME_WITH_FRIENDS should be 1');
        $this->assertEquals(2, GAME_WITH_SOLO, 'GAME_WITH_SOLO should be 2');
    }

    /**
     * Test creating a new 10-round game between two players
     */
    public function testCreateTenRoundGame(): void
    {
        // Set current player in session
        $this->loginAs($this->player1Id);

        // Create game with both players
        $players = json_encode([$this->player1Id, $this->player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        // Should return array without Error key
        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        // Extract game ID (at index 5 per FarkleSendUpdate return structure)
        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned at index 5');
        $this->assertGreaterThan(0, $gameId, 'Game ID should be a positive integer');

        // Verify game in database
        $game = $this->queryRow(
            "SELECT gamemode, maxturns, winningplayer, gamewith FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($game, 'Game should exist in database');
        $this->assertEquals(2, (int)$game['gamemode'], 'Game mode should be 10-round (2)');
        $this->assertEquals(2, (int)$game['maxturns'], 'Game should have 2 players');
        $this->assertEquals(0, (int)$game['winningplayer'], 'Game should not have a winner yet');
        $this->assertEquals(1, (int)$game['gamewith'], 'Game with should be friends (1)');
    }

    /**
     * Test creating a solo game
     */
    public function testCreateSoloGame(): void
    {
        // Set current player in session
        $this->loginAs($this->player1Id);

        // Create solo game with just one player
        $players = json_encode([$this->player1Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_SOLO, GAME_MODE_10ROUND);

        // Should return array without Error key
        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        // Extract game ID
        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned');

        // Verify game in database
        $game = $this->queryRow(
            "SELECT gamemode, maxturns, gamewith, playerstring FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($game, 'Solo game should exist in database');
        $this->assertEquals(2, (int)$game['gamemode'], 'Game mode should be 10-round');
        $this->assertEquals(1, (int)$game['maxturns'], 'Solo game should have 1 player');
        $this->assertEquals(2, (int)$game['gamewith'], 'Game with should be solo (2)');
        $this->assertEquals('Solo Game', $game['playerstring'], 'Solo game name should be "Solo Game"');
    }

    /**
     * Test that rolling dice creates set records
     */
    public function testRollDiceCreatesSetRecords(): void
    {
        // Set current player in session
        $this->loginAs($this->player1Id);

        // Create a game
        $players = json_encode([$this->player1Id, $this->player2Id]);
        $gameResult = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $gameResult[5];

        // Roll dice (first roll with no saved dice)
        $savedDice = json_encode([0, 0, 0, 0, 0, 0]);
        $newDice = null; // Let server generate random dice

        $rollResult = FarkleRoll($this->player1Id, $gameId, $savedDice, $newDice);

        // Result should not have an error
        $this->assertIsArray($rollResult, 'FarkleRoll should return an array');
        $this->assertArrayNotHasKey('Error', $rollResult, 'FarkleRoll should not return an error');

        // Verify a set was created in the database
        $setCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_sets WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->player1Id]
        );

        $this->assertGreaterThan(0, (int)$setCount, 'At least one set should be created after rolling');

        // Verify dice values are in the set
        $setRow = $this->queryRow(
            "SELECT d1, d2, d3, d4, d5, d6 FROM farkle_sets
             WHERE gameid = :gameid AND playerid = :playerid
             ORDER BY setnum DESC LIMIT 1",
            [':gameid' => $gameId, ':playerid' => $this->player1Id]
        );

        $this->assertNotNull($setRow, 'Set row should exist');

        // All dice should be between 0 (not rolled) and 6
        for ($i = 1; $i <= 6; $i++) {
            $dieValue = (int)$setRow["d$i"];
            $this->assertGreaterThanOrEqual(0, $dieValue, "Die $i should be >= 0");
            $this->assertLessThanOrEqual(6, $dieValue, "Die $i should be <= 6");
        }
    }

    /**
     * Test playing a full 10-round game to completion
     */
    public function testPlayFullTenRoundGame(): void
    {
        // Login as player 1
        $this->loginAs($this->player1Id);

        // Create game
        $players = json_encode([$this->player1Id, $this->player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned');

        // Play rounds for Player 1 until they complete 10 rounds
        // The loop may run more than 10 times if there are issues, but we cap at 15 to prevent infinite loops
        $this->loginAs($this->player1Id);
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $p1Round = $this->queryValue(
                "SELECT playerround FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
                [':gameid' => $gameId, ':playerid' => $this->player1Id]
            );
            if ((int)$p1Round > 10) {
                break; // Player 1 has completed all 10 rounds
            }
            $this->playOneRound($gameId, $this->player1Id);
        }

        // Verify player 1 completed 10 rounds (playerround should be 11 after completing round 10)
        $p1Round = $this->queryValue(
            "SELECT playerround FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->player1Id]
        );
        $this->assertGreaterThanOrEqual(11, (int)$p1Round, 'Player 1 should have completed all 10 rounds (playerround >= 11)');

        // Play rounds for Player 2 until they complete 10 rounds
        $this->loginAs($this->player2Id);
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $p2Round = $this->queryValue(
                "SELECT playerround FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
                [':gameid' => $gameId, ':playerid' => $this->player2Id]
            );
            if ((int)$p2Round > 10) {
                break; // Player 2 has completed all 10 rounds
            }
            $this->playOneRound($gameId, $this->player2Id);
        }

        // Verify game completed
        $game = $this->queryRow(
            "SELECT winningplayer, gamefinish FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertGreaterThan(0, (int)$game['winningplayer'], 'Game should have a winner');
        $this->assertNotNull($game['gamefinish'], 'Game finish time should be set');

        // Verify both players completed their rounds
        $p2Round = $this->queryValue(
            "SELECT playerround FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->player2Id]
        );
        $this->assertGreaterThanOrEqual(11, (int)$p2Round, 'Player 2 should have completed all 10 rounds (playerround >= 11)');

        // Verify rounds were logged - should be 20 (10 per player)
        $totalRounds = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_rounds WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );
        $this->assertEquals(20, (int)$totalRounds, 'Should have 20 rounds logged (10 per player)');
    }

    /**
     * Test that player scores match sum of round scores
     */
    public function testScoreIntegrity(): void
    {
        // Login as player 1
        $this->loginAs($this->player1Id);

        // Create a solo game for simpler testing
        $players = json_encode([$this->player1Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_SOLO, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Play 3 rounds
        for ($round = 1; $round <= 3; $round++) {
            $this->playOneRound($gameId, $this->player1Id);
        }

        // Get player score from game
        $playerScore = $this->queryValue(
            "SELECT playerscore FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->player1Id]
        );

        // Get sum of round scores
        $roundSum = $this->queryValue(
            "SELECT COALESCE(SUM(roundscore), 0) FROM farkle_rounds WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->player1Id]
        );

        $this->assertEquals(
            (int)$roundSum,
            (int)$playerScore,
            'Player score should equal sum of round scores'
        );
    }

    /**
     * Test that round number increments correctly
     */
    public function testRoundIncrementsCorrectly(): void
    {
        // Login as player 1
        $this->loginAs($this->player1Id);

        // Create a solo game
        $players = json_encode([$this->player1Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_SOLO, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Verify starting round is 1
        $startRound = $this->queryValue(
            "SELECT playerround FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->player1Id]
        );
        $this->assertEquals(1, (int)$startRound, 'Player should start at round 1');

        // Play one round
        $this->playOneRound($gameId, $this->player1Id);

        // Verify round incremented to 2
        $afterRound = $this->queryValue(
            "SELECT playerround FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->player1Id]
        );
        $this->assertEquals(2, (int)$afterRound, 'Player round should increment to 2 after playing round 1');
    }

    /**
     * Helper: Play one round for the current player
     * Rolls dice, selects scoring dice, and banks (or handles farkle)
     */
    private function playOneRound(int $gameId, int $playerId): void
    {
        // Roll dice (all 6 - no saved dice)
        $savedDice = json_encode([0, 0, 0, 0, 0, 0]);
        $rollResult = FarkleRoll($playerId, $gameId, $savedDice, null);

        if (isset($rollResult['Error'])) {
            // Might not be our turn, or other issue - that's OK for this test
            return;
        }

        // Get the dice from the database
        $diceRow = $this->queryRow(
            "SELECT d1, d2, d3, d4, d5, d6 FROM farkle_sets
             WHERE gameid = :gameid AND playerid = :playerid
             ORDER BY roundnum DESC, setnum DESC LIMIT 1",
            [':gameid' => $gameId, ':playerid' => $playerId]
        );

        if (!$diceRow) {
            return; // No dice found, skip
        }

        $dice = [
            (int)$diceRow['d1'], (int)$diceRow['d2'], (int)$diceRow['d3'],
            (int)$diceRow['d4'], (int)$diceRow['d5'], (int)$diceRow['d6']
        ];

        // Check if farkle (no scoring dice) - server auto-passes on farkle
        if (!$this->hasScoringDice($dice)) {
            return;
        }

        // Select scoring dice
        $savedDice = $this->selectScoringDice($dice);

        // Bank the score
        FarklePass($playerId, $gameId, json_encode($savedDice));
    }

    /**
     * Check if dice array has any scoring dice
     */
    private function hasScoringDice(array $dice): bool
    {
        $counts = array_count_values($dice);

        // Check for 1s or 5s (ignoring 0s which are already-scored dice)
        if (isset($counts[1]) || isset($counts[5])) {
            return true;
        }

        // Check for three of a kind (excluding 0s)
        foreach ($counts as $value => $count) {
            if ($value > 0 && $count >= 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * Select scoring dice from a roll (simple strategy: triples, then 1s and 5s)
     * Returns the saveddice array where saved dice have their value, others are 0
     */
    private function selectScoringDice(array $dice): array
    {
        $savedDice = [0, 0, 0, 0, 0, 0];
        $counts = array_count_values($dice);

        // Look for three of a kind first (excluding 0s)
        $tripleFound = false;
        foreach ($counts as $value => $count) {
            if ($value > 0 && $count >= 3 && !$tripleFound) {
                $selected = 0;
                for ($i = 0; $i < 6; $i++) {
                    if ($dice[$i] == $value && $selected < 3) {
                        $savedDice[$i] = $value;
                        $selected++;
                    }
                }
                $tripleFound = true;
                break;
            }
        }

        // Select any 1s and 5s not already selected (excluding 0s)
        for ($i = 0; $i < 6; $i++) {
            if ($savedDice[$i] == 0 && $dice[$i] > 0) {
                if ($dice[$i] == 1 || $dice[$i] == 5) {
                    $savedDice[$i] = $dice[$i];
                }
            }
        }

        return $savedDice;
    }
}
