<?php
/**
 * Integration tests for Farkle Profile page functionality.
 *
 * Tests the PHP backend functions that power the player profile,
 * including stats, achievements, completed games, and title selection.
 */
namespace Tests\Integration;

use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../wwwroot/farklePageFuncs.php';
require_once __DIR__ . '/../../wwwroot/farkleGameFuncs.php';
require_once __DIR__ . '/../../wwwroot/farkleAchievements.php';
require_once __DIR__ . '/../../wwwroot/farkleLevel.php';

class ProfileTest extends DatabaseTestCase
{
    private int $testPlayerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPlayerId = $this->createTestPlayer('profile_test');
        $this->loginAs($this->testPlayerId);

        // Also set up session for GetStats which requires $_SESSION['playerid']
        $_SESSION['playerid'] = $this->testPlayerId;
        $_SESSION['farkle'] = [];
        $_SESSION['username'] = 'profile_test_user';

        // Ensure the $gTitles global is available - it's defined in farklePageFuncs.php
        // but may not be accessible due to PHPUnit's test isolation
        if (!isset($GLOBALS['gTitles'])) {
            // Define it directly if not set
            $GLOBALS['gTitles'] = [
                0=>'',
                3=>'the Prospect',
                6=>'the Joker',
                9=>'the Princess',
                12=>'the Scary Clown',
                15=>'the Farkled',
                18=>'the Average Joe',
                21=>'the Wicked',
                24=>'the Sexy Lady',
                27=>'the Gamer',
                30=>'the Notorious',
                33=>'the Lucky Dog',
                36=>'the Veteran',
                39=>'the Samsquash',
                42=>'the Dola',
                45=>'the Star',
                48=>'the Professional',
                51=>'the Stud',
                54=>'the Dice Master',
                57=>'the Chosen One',
                60=>'the King of Farkle',
                100=>'the Centurion'
            ];
        }
    }

    // ============= BASIC PROFILE VIEW TESTS =============

    public function testGetPlayerInfoReturnsBasicData(): void
    {
        $info = GetPlayerInfo($this->testPlayerId);

        $this->assertIsArray($info);
        $this->assertArrayHasKey('username', $info);
        $this->assertArrayHasKey('playerlevel', $info);
        $this->assertArrayHasKey('xp', $info);
    }

    public function testGetStatsReturnsFullProfileData(): void
    {
        $stats = GetStats($this->testPlayerId);

        $this->assertIsArray($stats);
        $this->assertCount(3, $stats); // [player_data, completed_games, titles]

        // Player data
        $playerData = $stats[0];
        $this->assertArrayHasKey('username', $playerData);
        $this->assertArrayHasKey('wins', $playerData);
        $this->assertArrayHasKey('losses', $playerData);
        $this->assertArrayHasKey('totalpoints', $playerData);
        $this->assertArrayHasKey('playerlevel', $playerData);
        $this->assertArrayHasKey('xp', $playerData);
    }

    // ============= STATS DISPLAY TESTS =============

    public function testProfileShowsWinsAndLosses(): void
    {
        $stats = GetStats($this->testPlayerId);
        $playerData = $stats[0];

        $this->assertArrayHasKey('wins', $playerData);
        $this->assertArrayHasKey('losses', $playerData);
        $this->assertIsNumeric($playerData['wins']);
        $this->assertIsNumeric($playerData['losses']);
    }

    public function testProfileShowsFarkleStats(): void
    {
        $stats = GetStats($this->testPlayerId);
        $playerData = $stats[0];

        $this->assertArrayHasKey('farkles', $playerData);
        // farkle_pct might be null for new players
    }

    public function testProfileShowsHighScores(): void
    {
        $stats = GetStats($this->testPlayerId);
        $playerData = $stats[0];

        $this->assertArrayHasKey('totalpoints', $playerData);
        $this->assertArrayHasKey('highestround', $playerData);
        $this->assertArrayHasKey('highest10round', $playerData);
    }

    public function testProfileShowsLevelAndXp(): void
    {
        $stats = GetStats($this->testPlayerId);
        $playerData = $stats[0];

        $this->assertArrayHasKey('playerlevel', $playerData);
        $this->assertArrayHasKey('xp', $playerData);
        $this->assertArrayHasKey('xp_to_level', $playerData);
        $this->assertGreaterThanOrEqual(1, (int)$playerData['playerlevel']);
    }

    // ============= TITLE CHANGE TESTS =============

    public function testGetTitleChoicesForLevel1(): void
    {
        $titles = Player_GetTitleChoices(1);

        $this->assertIsArray($titles);
        // Level 1 player may have 0 or 1 title option depending on implementation
        // The $gTitles array has titles at levels 0, 3, 6, 9... where 0 is empty string
        // Player_GetTitleChoices returns titles where level < player_level
        // For level 1: only level 0 title qualifies (empty string)
        $this->assertLessThanOrEqual(1, count($titles));
    }

    public function testGetTitleChoicesIncreasesWithLevel(): void
    {
        $titlesLevel1 = Player_GetTitleChoices(1);
        $titlesLevel10 = Player_GetTitleChoices(10);

        $this->assertGreaterThanOrEqual(count($titlesLevel1), count($titlesLevel10));
    }

    public function testUpdateTitleSucceedsWithValidTitle(): void
    {
        // Set player to level 10 to have access to titles up to level 9
        $this->execute(
            "UPDATE farkle_players SET playerlevel = 10 WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );

        // Verify our update took effect
        $verifyLevel = $this->queryValue(
            "SELECT playerlevel FROM farkle_players WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );
        $this->assertEquals(10, (int)$verifyLevel, 'Player level should be updated to 10');

        // Update to title at level 6 ("the Joker") - should be allowed for level 10
        $result = Player_UpdateTitle(6);
        $this->assertEquals(1, $result, 'Player_UpdateTitle should return 1 for valid title');

        // Verify title was changed to "the Joker"
        $title = $this->queryValue(
            "SELECT playertitle FROM farkle_players WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );
        $this->assertEquals('the Joker', $title);
    }

    public function testUpdateTitleFailsIfTitleLevelTooHigh(): void
    {
        // Player is level 1 by default, trying to set level 50 title should fail
        $result = Player_UpdateTitle(50);

        $this->assertEquals(0, $result);
    }

    // ============= PREVIOUS GAMES TESTS =============

    public function testCompletedGamesEmptyForNewPlayer(): void
    {
        $stats = GetStats($this->testPlayerId);
        $completedGames = $stats[1];

        // New player has no completed games
        $this->assertEmpty($completedGames);
    }

    public function testCompletedGamesShowsFinishedGames(): void
    {
        // Create and complete a game
        $opponentId = $this->createTestPlayer('profile_opponent');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5] ?? $result['gameid'] ?? null;

        // Mark game as completed
        $this->execute(
            "UPDATE farkle_games SET winningplayer = :winner, gamefinish = NOW() WHERE gameid = :gameid",
            [':winner' => $this->testPlayerId, ':gameid' => $gameId]
        );

        // Get completed games via GetGames function
        $completedGames = GetGames($this->testPlayerId, 1, 20, 0);

        $this->assertNotEmpty($completedGames);
        $this->assertCount(1, $completedGames);
    }

    public function testCanClickIntoCompletedGame(): void
    {
        // Create and complete a game
        $opponentId = $this->createTestPlayer('profile_click_game');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5] ?? $result['gameid'] ?? null;

        // Mark as complete
        $this->execute(
            "UPDATE farkle_games SET winningplayer = :winner, gamefinish = NOW() WHERE gameid = :gameid",
            [':winner' => $this->testPlayerId, ':gameid' => $gameId]
        );

        // Simulate "clicking" into game - verify we can load it
        $gameData = $this->queryRow(
            "SELECT * FROM farkle_games WHERE gameid = :gameid",
            [':gameid' => $gameId]
        );

        $this->assertNotNull($gameData);
        $this->assertEquals($this->testPlayerId, $gameData['winningplayer']);
    }

    // ============= ACHIEVEMENTS TESTS =============

    public function testAchievementScoreInProfile(): void
    {
        $stats = GetStats($this->testPlayerId);
        $playerData = $stats[0];

        // achscore field exists (may be null for new player)
        $this->assertArrayHasKey('achscore', $playerData);
    }

    public function testGetAchievementsForPlayer(): void
    {
        // Get all achievements the player has earned (GetAchievements returns [achievements, totalPoints])
        $achievementsData = GetAchievements($this->testPlayerId);

        // Function returns an array with [achievements, totalPoints]
        $this->assertIsArray($achievementsData);
        $this->assertCount(2, $achievementsData);

        $achievements = $achievementsData[0];
        $this->assertIsArray($achievements);
    }

    public function testAchievementAwardedShowsInProfile(): void
    {
        // Award an achievement to the test player
        // ACH_FIRST_WIN is usually ID 1 - check what achievements exist
        $achId = $this->queryValue("SELECT achievementid FROM farkle_achievements LIMIT 1");

        if ($achId) {
            // Award it
            $this->execute(
                "INSERT INTO farkle_achievements_players (playerid, achievementid, achievedate)
                 VALUES (:playerid, :achid, NOW())",
                [':playerid' => $this->testPlayerId, ':achid' => $achId]
            );

            // Check achscore updated
            $stats = GetStats($this->testPlayerId);
            // achscore should now be non-null
            $this->assertNotNull($stats[0]['achscore']);
        } else {
            $this->markTestSkipped('No achievements defined in database');
        }
    }

    // ============= RICH DATA TESTS (using mschmoyer if exists) =============

    public function testRichUserProfileHasStats(): void
    {
        $mschmoyerId = $this->queryValue(
            "SELECT playerid FROM farkle_players WHERE username = 'mschmoyer'"
        );

        if (!$mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found');
        }

        $_SESSION['playerid'] = $mschmoyerId;
        $stats = GetStats($mschmoyerId);

        $this->assertNotEmpty($stats[0]);

        // Should have some game history
        $wins = (int)$stats[0]['wins'];
        $losses = (int)$stats[0]['losses'];
        $this->assertGreaterThan(0, $wins + $losses, 'mschmoyer should have played games');
    }

    public function testRichUserHasCompletedGames(): void
    {
        $mschmoyerId = $this->queryValue(
            "SELECT playerid FROM farkle_players WHERE username = 'mschmoyer'"
        );

        if (!$mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found');
        }

        $_SESSION['playerid'] = $mschmoyerId;
        $stats = GetStats($mschmoyerId);
        $completedGames = $stats[1];

        $this->assertNotEmpty($completedGames, 'mschmoyer should have completed games');
    }

    public function testRichUserHasAchievements(): void
    {
        $mschmoyerId = $this->queryValue(
            "SELECT playerid FROM farkle_players WHERE username = 'mschmoyer'"
        );

        if (!$mschmoyerId) {
            $this->markTestSkipped('mschmoyer user not found');
        }

        $achievementsData = GetAchievements($mschmoyerId);
        $achievements = $achievementsData[0];

        // Filter to only earned achievements
        $earnedAchievements = array_filter($achievements, function($a) {
            return (int)$a['earned'] === 1;
        });

        $this->assertNotEmpty($earnedAchievements, 'mschmoyer should have achievements');
    }
}
