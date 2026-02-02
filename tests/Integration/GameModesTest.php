<?php
/**
 * Integration tests for all Farkle game modes.
 *
 * Tests creating games with different game modes and player configurations:
 * - Random games (2 and 4 players)
 * - Solo games
 * - Games with bot opponents
 * - Games with friends
 */
namespace Tests\Integration;

use Tests\DatabaseTestCase;
use PDO;

// Include game functions
require_once __DIR__ . '/../../wwwroot/farkleGameFuncs.php';
require_once __DIR__ . '/../../wwwroot/farkleFriends.php';

class GameModesTest extends DatabaseTestCase
{
    /**
     * Test creating a random game with 2 players
     */
    public function testPlayRandomTwoPlayers(): void
    {
        // Create a test player who will start the game
        $playerId = $this->createTestPlayer('random2p_player');
        $this->loginAs($playerId);

        // Initialize session for game creation
        $_SESSION['username'] = 'random2p_player';
        $_SESSION['farkle'] = [];

        // Create a random game for 2 players
        $players = json_encode([$playerId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_RANDOM, GAME_MODE_10ROUND, false, 2);

        // Verify game created successfully
        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        // Extract game ID (at index 5 per FarkleSendUpdate return structure)
        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned at index 5');
        $this->assertGreaterThan(0, $gameId, 'Game ID should be a positive integer');

        // Verify game in database
        $game = $this->queryRow(
            "SELECT gamemode, gamewith, maxturns, winningplayer
             FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($game, 'Game should exist in database');
        $this->assertEquals(GAME_MODE_10ROUND, (int)$game['gamemode'], 'Game mode should be 10-round');
        $this->assertEquals(GAME_WITH_RANDOM, (int)$game['gamewith'], 'Game with should be random (0)');
        $this->assertEquals(2, (int)$game['maxturns'], 'Random game should be created for 2 players');
        $this->assertEquals(0, (int)$game['winningplayer'], 'Game should not have a winner yet');

        // Verify player record exists in farkle_games_players
        $playerRecord = $this->queryRow(
            "SELECT playerid, playerscore, playerround
             FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $playerId]
        );

        $this->assertNotNull($playerRecord, 'Player record should exist in farkle_games_players');
        $this->assertEquals($playerId, (int)$playerRecord['playerid'], 'Player ID should match');
        $this->assertEquals(0, (int)$playerRecord['playerscore'], 'Initial score should be 0');
        $this->assertEquals(1, (int)$playerRecord['playerround'], 'Player should start at round 1');
    }

    /**
     * Test creating a random game with 4 players
     */
    public function testPlayRandomFourPlayers(): void
    {
        // Create a test player who will start the game
        $playerId = $this->createTestPlayer('random4p_player');
        $this->loginAs($playerId);

        // Initialize session for game creation
        $_SESSION['username'] = 'random4p_player';
        $_SESSION['farkle'] = [];

        // Create a random game for 4 players
        $players = json_encode([$playerId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_RANDOM, GAME_MODE_10ROUND, false, 4);

        // Verify game created successfully
        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned');

        // Verify game in database
        $game = $this->queryRow(
            "SELECT gamemode, gamewith, maxturns
             FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($game, 'Game should exist in database');
        $this->assertEquals(GAME_MODE_10ROUND, (int)$game['gamemode'], 'Game mode should be 10-round');
        $this->assertEquals(GAME_WITH_RANDOM, (int)$game['gamewith'], 'Game with should be random (0)');
        $this->assertEquals(4, (int)$game['maxturns'], 'Random game should be created for 4 players');

        // Verify player record exists
        $playerRecord = $this->queryRow(
            "SELECT playerid FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $playerId]
        );

        $this->assertNotNull($playerRecord, 'Player record should exist in farkle_games_players');
    }

    /**
     * Test creating a solo game
     */
    public function testPlaySolo(): void
    {
        // Create a test player
        $playerId = $this->createTestPlayer('solo_player');
        $this->loginAs($playerId);

        // Initialize session
        $_SESSION['username'] = 'solo_player';
        $_SESSION['farkle'] = [];

        // Create solo game with just one player
        $players = json_encode([$playerId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_SOLO, GAME_MODE_10ROUND);

        // Verify game created successfully
        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned');

        // Verify game in database
        $game = $this->queryRow(
            "SELECT gamemode, gamewith, maxturns, playerstring, winningplayer
             FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($game, 'Solo game should exist in database');
        $this->assertEquals(GAME_MODE_10ROUND, (int)$game['gamemode'], 'Game mode should be 10-round');
        $this->assertEquals(GAME_WITH_SOLO, (int)$game['gamewith'], 'Game with should be solo (2)');
        $this->assertEquals(1, (int)$game['maxturns'], 'Solo game should have 1 player');
        $this->assertEquals('Solo Game', $game['playerstring'], 'Solo game name should be "Solo Game"');
        $this->assertEquals(0, (int)$game['winningplayer'], 'Game should not have a winner yet');

        // Verify player record exists
        $playerRecord = $this->queryRow(
            "SELECT playerid, playerscore, playerround
             FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $playerId]
        );

        $this->assertNotNull($playerRecord, 'Player record should exist in farkle_games_players');
        $this->assertEquals($playerId, (int)$playerRecord['playerid'], 'Player ID should match');
        $this->assertEquals(0, (int)$playerRecord['playerscore'], 'Initial score should be 0');
        $this->assertEquals(1, (int)$playerRecord['playerround'], 'Player should start at round 1');
    }

    /**
     * Test creating a game against a bot
     */
    public function testPlayWithBot(): void
    {
        // Create a test player
        $playerId = $this->createTestPlayer('bot_opponent');
        $this->loginAs($playerId);

        // Create a test bot player
        $botId = $this->createBotPlayer('TestBot', 'easy');

        // Initialize session
        $_SESSION['username'] = 'bot_opponent';
        $_SESSION['farkle'] = [];

        // Create a game with the bot (using GAME_WITH_FRIENDS mode)
        $players = json_encode([$playerId, $botId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        // Verify game created successfully
        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned');

        // Verify game in database
        $game = $this->queryRow(
            "SELECT gamemode, gamewith, maxturns, winningplayer
             FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($game, 'Game should exist in database');
        $this->assertEquals(GAME_MODE_10ROUND, (int)$game['gamemode'], 'Game mode should be 10-round');
        $this->assertEquals(GAME_WITH_FRIENDS, (int)$game['gamewith'], 'Game with should be friends (1)');
        $this->assertEquals(2, (int)$game['maxturns'], 'Game should have 2 players');
        $this->assertEquals(0, (int)$game['winningplayer'], 'Game should not have a winner yet');

        // Verify both player records exist
        $playerCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );
        $this->assertEquals(2, (int)$playerCount, 'Should have 2 player records');

        // Verify human player record
        $humanRecord = $this->queryRow(
            "SELECT playerid FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $playerId]
        );
        $this->assertNotNull($humanRecord, 'Human player record should exist');

        // Verify bot player record
        $botRecord = $this->queryRow(
            "SELECT playerid FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $botId]
        );
        $this->assertNotNull($botRecord, 'Bot player record should exist');

        // Verify bot is actually a bot
        $botData = $this->queryRow(
            "SELECT is_bot, bot_algorithm FROM farkle_players WHERE playerid = :playerid",
            [':playerid' => $botId]
        );
        $this->assertTrue((bool)$botData['is_bot'], 'Player should be marked as a bot');
        $this->assertEquals('easy', $botData['bot_algorithm'], 'Bot should have correct algorithm');
    }

    /**
     * Test creating a game with a friend
     */
    public function testPlayWithFriend(): void
    {
        // Create two test players
        $player1Id = $this->createTestPlayer('friend1');
        $player2Id = $this->createTestPlayer('friend2');

        // Login as player 1
        $this->loginAs($player1Id);
        $_SESSION['username'] = 'friend1';
        $_SESSION['farkle'] = [];

        // Make them friends by directly inserting into the database
        // Production schema uses sourceid (who initiated) and friendid
        $this->execute(
            "INSERT INTO farkle_friends (sourceid, friendid, removed)
             VALUES (:sourceid, :friendid, 0)",
            [
                ':sourceid' => $player1Id,
                ':friendid' => $player2Id
            ]
        );

        // Verify friendship exists in database
        $friendship = $this->queryRow(
            "SELECT sourceid, friendid, removed
             FROM farkle_friends WHERE sourceid = :sourceid AND friendid = :friendid",
            [':sourceid' => $player1Id, ':friendid' => $player2Id]
        );
        $this->assertNotNull($friendship, 'Friendship should exist in database');
        $this->assertEquals($player1Id, (int)$friendship['sourceid'], 'Source player should be player 1');
        $this->assertEquals($player2Id, (int)$friendship['friendid'], 'Friend should be player 2');
        $this->assertEquals(0, (int)$friendship['removed'], 'Friendship should not be removed');

        // Now create a game with the friend
        $players = json_encode([$player1Id, $player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        // Verify game created successfully
        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        $gameId = $result[5] ?? null;
        $this->assertNotNull($gameId, 'Game ID should be returned');

        // Verify game in database
        $game = $this->queryRow(
            "SELECT gamemode, gamewith, maxturns, whostarted, winningplayer
             FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($game, 'Game should exist in database');
        $this->assertEquals(GAME_MODE_10ROUND, (int)$game['gamemode'], 'Game mode should be 10-round');
        $this->assertEquals(GAME_WITH_FRIENDS, (int)$game['gamewith'], 'Game with should be friends (1)');
        $this->assertEquals(2, (int)$game['maxturns'], 'Game should have 2 players');
        $this->assertEquals($player1Id, (int)$game['whostarted'], 'Player 1 should have started the game');
        $this->assertEquals(0, (int)$game['winningplayer'], 'Game should not have a winner yet');

        // Verify both player records exist in farkle_games_players
        $playerCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );
        $this->assertEquals(2, (int)$playerCount, 'Should have 2 player records');

        // Verify player 1 record
        $player1Record = $this->queryRow(
            "SELECT playerid, playerscore, playerround
             FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $player1Id]
        );
        $this->assertNotNull($player1Record, 'Player 1 record should exist');
        $this->assertEquals($player1Id, (int)$player1Record['playerid'], 'Player 1 ID should match');
        $this->assertEquals(0, (int)$player1Record['playerscore'], 'Player 1 initial score should be 0');
        $this->assertEquals(1, (int)$player1Record['playerround'], 'Player 1 should start at round 1');

        // Verify player 2 record
        $player2Record = $this->queryRow(
            "SELECT playerid, playerscore, playerround
             FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $player2Id]
        );
        $this->assertNotNull($player2Record, 'Player 2 record should exist');
        $this->assertEquals($player2Id, (int)$player2Record['playerid'], 'Player 2 ID should match');
        $this->assertEquals(0, (int)$player2Record['playerscore'], 'Player 2 initial score should be 0');
        $this->assertEquals(1, (int)$player2Record['playerround'], 'Player 2 should start at round 1');
    }

    /**
     * Helper method to create a bot player in the database
     *
     * @param string $username Bot username
     * @param string $botAlgorithm Bot difficulty (easy, medium, hard)
     * @return int The bot's player ID
     */
    private function createBotPlayer(string $username, string $botAlgorithm = 'easy'): int
    {
        $uniqueUsername = $username . '_' . uniqid();

        $sql = "INSERT INTO farkle_players (username, password, salt, email, is_bot, bot_algorithm, active)
                VALUES (:username, '', '', :email, true, :bot_algorithm, 1)
                RETURNING playerid";

        $stmt = self::$db->prepare($sql);
        $stmt->execute([
            ':username' => $uniqueUsername,
            ':email' => $uniqueUsername . '@bot.test',
            ':bot_algorithm' => $botAlgorithm
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['playerid'];
    }
}
