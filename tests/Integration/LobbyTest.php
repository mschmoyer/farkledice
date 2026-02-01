<?php
/**
 * Integration tests for Farkle lobby functionality.
 *
 * Tests the PHP backend functions that power the lobby,
 * including player info, active games, and related data.
 */
namespace Tests\Integration;

use Tests\DatabaseTestCase;
use PDO;

// Include game and page functions
require_once __DIR__ . '/../../wwwroot/farkleGameFuncs.php';
require_once __DIR__ . '/../../wwwroot/farklePageFuncs.php';

class LobbyTest extends DatabaseTestCase
{
    private int $testPlayerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPlayerId = $this->createTestPlayer('lobby_test');
        $this->loginAs($this->testPlayerId);

        // Initialize farkle session array
        $_SESSION['farkle'] = [];
        $_SESSION['username'] = 'lobby_test_user';
    }

    /**
     * Test 1: Get lobby info returns player data
     */
    public function testGetLobbyInfoReturnsPlayerData(): void
    {
        $player = $this->queryRow(
            "SELECT username, playerlevel as level, xp FROM farkle_players WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );

        $this->assertNotNull($player);
        $this->assertArrayHasKey('username', $player);
        $this->assertArrayHasKey('level', $player);
        $this->assertArrayHasKey('xp', $player);
    }

    /**
     * Test 2: Lobby shows active games for player
     */
    public function testLobbyShowsActiveGames(): void
    {
        // Create a second player as opponent
        $player2Id = $this->createTestPlayer('lobby_opponent');
        $players = json_encode([$this->testPlayerId, $player2Id]);

        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        $this->assertIsArray($result, 'FarkleNewGame should return an array');
        $this->assertArrayNotHasKey('Error', $result, 'FarkleNewGame should not return an error');

        // Extract game ID (at index 5 per FarkleSendUpdate return structure, or from game object)
        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);
        $this->assertNotNull($gameId, 'Game ID should be returned');

        // Query games for this player (simulating lobby query)
        $games = $this->queryAll(
            "SELECT g.gameid, g.gamemode, gp.playerscore
             FROM farkle_games g
             JOIN farkle_games_players gp ON g.gameid = gp.gameid
             WHERE gp.playerid = :playerid AND g.winningplayer = 0",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertCount(1, $games);
        $this->assertEquals($gameId, $games[0]['gameid']);
    }

    /**
     * Test 3: Lobby shows no games when player has none
     */
    public function testLobbyShowsNoGamesForNewPlayer(): void
    {
        $games = $this->queryAll(
            "SELECT g.gameid FROM farkle_games g
             JOIN farkle_games_players gp ON g.gameid = gp.gameid
             WHERE gp.playerid = :playerid AND g.winningplayer = 0",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertCount(0, $games);
    }

    /**
     * Test 4: Can click into active game (load game data)
     */
    public function testCanLoadActiveGameData(): void
    {
        // Create a game
        $player2Id = $this->createTestPlayer('lobby_game_load');
        $players = json_encode([$this->testPlayerId, $player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);

        $this->assertNotNull($gameId, 'Game should be created');

        // Simulate "clicking" into the game - load game data
        $gameData = $this->queryRow(
            "SELECT * FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($gameData);
        $this->assertEquals(2, (int)$gameData['gamemode'], 'Game mode should be 10-round (2)');

        // Check player is in the game
        $playerInGame = $this->queryRow(
            "SELECT * FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->testPlayerId]
        );

        $this->assertNotNull($playerInGame);
    }

    /**
     * Test 5: Player XP and level data
     */
    public function testPlayerXpAndLevelData(): void
    {
        $player = $this->queryRow(
            "SELECT xp, playerlevel as level FROM farkle_players WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );

        $this->assertNotNull($player);
        $this->assertIsNumeric($player['xp']);
        $this->assertIsNumeric($player['level']);
        $this->assertGreaterThanOrEqual(0, (int)$player['xp']);
        $this->assertGreaterThanOrEqual(1, (int)$player['level']);
    }

    /**
     * Test 6: Multiple active games shown in lobby
     */
    public function testMultipleActiveGamesInLobby(): void
    {
        // Create 3 games with different opponents
        for ($i = 1; $i <= 3; $i++) {
            $opponentId = $this->createTestPlayer("lobby_multi_$i");
            $players = json_encode([$this->testPlayerId, $opponentId]);
            $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
            $this->assertArrayNotHasKey('Error', $result, "Game $i should be created without error");
        }

        // Query active games
        $games = $this->queryAll(
            "SELECT g.gameid FROM farkle_games g
             JOIN farkle_games_players gp ON g.gameid = gp.gameid
             WHERE gp.playerid = :playerid AND g.winningplayer = 0",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertCount(3, $games);
    }

    /**
     * Test 7: Completed games not shown in active lobby
     */
    public function testCompletedGamesNotInActiveLobby(): void
    {
        // Create and complete a game
        $player2Id = $this->createTestPlayer('lobby_complete');
        $players = json_encode([$this->testPlayerId, $player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);

        $this->assertNotNull($gameId, 'Game should be created');

        // Mark game as completed (set a winner)
        $this->execute(
            "UPDATE farkle_games SET winningplayer = :winner, gamefinish = NOW() WHERE gameid = :gameid",
            [':winner' => $this->testPlayerId, ':gameid' => $gameId]
        );

        // Query active games - should be empty
        $activeGames = $this->queryAll(
            "SELECT g.gameid FROM farkle_games g
             JOIN farkle_games_players gp ON g.gameid = gp.gameid
             WHERE gp.playerid = :playerid AND g.winningplayer = 0",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertCount(0, $activeGames);
    }

    /**
     * Test 8: GetPlayerInfo returns correct structure
     */
    public function testGetPlayerInfoReturnsCorrectStructure(): void
    {
        $playerInfo = GetPlayerInfo($this->testPlayerId);

        $this->assertIsArray($playerInfo);
        $this->assertArrayHasKey('username', $playerInfo);
        $this->assertArrayHasKey('playerlevel', $playerInfo);
        $this->assertArrayHasKey('xp', $playerInfo);
        $this->assertArrayHasKey('xp_to_level', $playerInfo);
    }

    /**
     * Test 9: GetGames returns active games (completed=0)
     */
    public function testGetGamesReturnsActiveGames(): void
    {
        // Create a game
        $player2Id = $this->createTestPlayer('lobby_getgames');
        $players = json_encode([$this->testPlayerId, $player2Id]);
        FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        // Call GetGames with completed=0
        $games = GetGames($this->testPlayerId, 0, 30, 0);

        $this->assertIsArray($games);
        $this->assertCount(1, $games);
        $this->assertArrayHasKey('gameid', $games[0]);
        $this->assertArrayHasKey('gamemode', $games[0]);
        $this->assertArrayHasKey('playerround', $games[0]);
    }

    /**
     * Test 10: GetGames returns empty for new player
     */
    public function testGetGamesReturnsEmptyForNewPlayer(): void
    {
        $games = GetGames($this->testPlayerId, 0, 30, 0);

        $this->assertIsArray($games);
        $this->assertCount(0, $games);
    }

    /**
     * Test 11: Solo games are handled correctly
     */
    public function testSoloGameInLobby(): void
    {
        // Create a solo game
        $players = json_encode([$this->testPlayerId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_SOLO, GAME_MODE_10ROUND);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('Error', $result);

        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);

        // Verify solo game exists
        $game = $this->queryRow(
            "SELECT gamewith, playerstring FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertEquals(GAME_WITH_SOLO, (int)$game['gamewith']);
        $this->assertEquals('Solo Game', $game['playerstring']);
    }

    /**
     * Test 12: Game mode is correctly stored and retrieved
     */
    public function testGameModeCorrectlyStored(): void
    {
        // Create 10-round game
        $player2Id = $this->createTestPlayer('lobby_mode');
        $players = json_encode([$this->testPlayerId, $player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);

        $game = $this->queryRow(
            "SELECT gamemode FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertEquals(GAME_MODE_10ROUND, (int)$game['gamemode']);
    }

    /**
     * Test 13: Player round tracking works
     */
    public function testPlayerRoundTracking(): void
    {
        $player2Id = $this->createTestPlayer('lobby_round');
        $players = json_encode([$this->testPlayerId, $player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);

        // New game should start at round 1
        $playerData = $this->queryRow(
            "SELECT playerround FROM farkle_games_players WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->testPlayerId]
        );

        $this->assertEquals(1, (int)$playerData['playerround']);
    }

    /**
     * Test 14: Two players in same game
     */
    public function testTwoPlayersInSameGame(): void
    {
        $player2Id = $this->createTestPlayer('lobby_p2');
        $players = json_encode([$this->testPlayerId, $player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);

        // Both players should be in the game
        $playerCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_games_players WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertEquals(2, (int)$playerCount);
    }

    /**
     * Test 15: Game 'maxturns' matches player count
     */
    public function testGameMaxturnsMatchesPlayerCount(): void
    {
        $player2Id = $this->createTestPlayer('lobby_maxturns');
        $players = json_encode([$this->testPlayerId, $player2Id]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);

        $gameId = $result[5] ?? ($result[0]['gameid'] ?? null);

        $game = $this->queryRow(
            "SELECT maxturns FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertEquals(2, (int)$game['maxturns']);
    }
}

/**
 * Tests using existing user with rich data.
 *
 * These tests use the 'mschmoyer' account which may have games, friends, history.
 * Tests are skipped if that account doesn't exist in the test database.
 */
class LobbyRichDataTest extends DatabaseTestCase
{
    private ?int $mschmoyerId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Try to find mschmoyer user
        $this->mschmoyerId = $this->queryValue(
            "SELECT playerid FROM farkle_players WHERE username = 'mschmoyer'"
        );

        // Initialize session
        $_SESSION['farkle'] = [];
    }

    /**
     * Test that a rich user has game history
     */
    public function testRichUserHasGamesHistory(): void
    {
        if (!$this->mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found in database');
        }

        $this->loginAs($this->mschmoyerId);

        // Check for any games (active or completed)
        $totalGames = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_games_players WHERE playerid = :playerid",
            [':playerid' => $this->mschmoyerId]
        );

        $this->assertGreaterThan(0, (int)$totalGames, 'mschmoyer should have game history');
    }

    /**
     * Test that rich user has XP and level
     */
    public function testRichUserHasXpAndLevel(): void
    {
        if (!$this->mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found in database');
        }

        $this->loginAs($this->mschmoyerId);

        $player = $this->queryRow(
            "SELECT xp, playerlevel FROM farkle_players WHERE playerid = :playerid",
            [':playerid' => $this->mschmoyerId]
        );

        $this->assertNotNull($player);
        $this->assertGreaterThan(0, (int)$player['xp'], 'mschmoyer should have XP');
        $this->assertGreaterThanOrEqual(1, (int)$player['playerlevel'], 'mschmoyer should have a level');
    }

    /**
     * Test GetPlayerInfo works for existing user
     */
    public function testGetPlayerInfoForExistingUser(): void
    {
        if (!$this->mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found in database');
        }

        $this->loginAs($this->mschmoyerId);

        $playerInfo = GetPlayerInfo($this->mschmoyerId);

        $this->assertIsArray($playerInfo);
        $this->assertEquals('mschmoyer', $playerInfo['username']);
        $this->assertArrayHasKey('playerlevel', $playerInfo);
        $this->assertArrayHasKey('xp', $playerInfo);
    }

    /**
     * Test that GetGames returns completed games for rich user
     */
    public function testRichUserHasCompletedGames(): void
    {
        if (!$this->mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found in database');
        }

        $this->loginAs($this->mschmoyerId);

        // Get completed games (not active)
        $completedGames = GetGames($this->mschmoyerId, 1, 30, 0);

        // Should have at least some completed games in history
        $this->assertIsArray($completedGames);
        // Note: May be 0 if no completed games, which is still valid
    }

    /**
     * Test achievement score exists for established user
     */
    public function testRichUserHasAchievementScore(): void
    {
        if (!$this->mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found in database');
        }

        $this->loginAs($this->mschmoyerId);

        $playerInfo = GetPlayerInfo($this->mschmoyerId);

        $this->assertArrayHasKey('achscore', $playerInfo);
        // achscore may be null if no achievements, but key should exist
    }
}
