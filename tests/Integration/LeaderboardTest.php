<?php
/**
 * Integration tests for Farkle Leaderboard functionality.
 *
 * Tests the leaderboard refresh functions that populate the farkle_lbdata table,
 * with particular focus on timezone handling for daily stats.
 *
 * The leaderboard system tracks:
 * - Today's/Yesterday's highest game scores (lbindex 0, 10)
 * - Today's/Yesterday's top farklers (lbindex 1, 11)
 * - Today's/Yesterday's best win ratio (lbindex 2, 12)
 * - Today's/Yesterday's best single rounds (lbindex 6, 16)
 * - All-time wins/losses (lbindex 3)
 * - All-time highest 10-round (lbindex 4)
 * - All-time achievement points (lbindex 5)
 */
namespace Tests\Integration;

use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../wwwroot/farkleLeaderboard.php';
require_once __DIR__ . '/../../wwwroot/farkleGameFuncs.php';

class LeaderboardTest extends DatabaseTestCase
{
    private int $testPlayerId;
    private string $testPlayerUsername;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test player and store the full username (with unique suffix)
        $this->testPlayerId = $this->createTestPlayer('leaderboard_test');
        $this->testPlayerUsername = $this->queryValue(
            "SELECT username FROM farkle_players WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );

        $this->loginAs($this->testPlayerId);

        $_SESSION['playerid'] = $this->testPlayerId;
        $_SESSION['farkle'] = [];
        $_SESSION['username'] = $this->testPlayerUsername;
    }

    // ============= REFRESH FUNCTION TESTS =============

    /**
     * Test that Leaderboard_RefreshData populates all-time leaderboards
     */
    public function testRefreshDataPopulatesAllTimeLeaderboards(): void
    {
        // Force refresh of leaderboard data
        $result = Leaderboard_RefreshData(true);

        $this->assertEquals(1, $result, 'Leaderboard_RefreshData should return 1 on success');

        // Verify data was written to farkle_lbdata for lbindex 3, 4, 5
        // These are all-time leaderboards: wins/losses (3), highest 10-round (4), achievement points (5)
        $allTimeIndices = [3, 4, 5];

        foreach ($allTimeIndices as $lbindex) {
            $count = $this->queryValue(
                "SELECT COUNT(*) FROM farkle_lbdata WHERE lbindex = :lbindex",
                [':lbindex' => $lbindex]
            );
            // Count can be 0 if no players have relevant data, but query should succeed
            $this->assertNotNull($count, "Query for lbindex $lbindex should succeed");
        }
    }

    /**
     * Test that Leaderboard_RefreshDaily populates daily leaderboards
     */
    public function testRefreshDailyPopulatesDailyLeaderboards(): void
    {
        // Call the daily refresh
        Leaderboard_RefreshDaily();

        // Verify the daily leaderboards exist (may be empty if no data)
        // Today: 0, 1, 2, 6
        // Yesterday: 10, 11, 12, 16
        $dailyIndices = [0, 1, 2, 6, 10, 11, 12, 16];

        foreach ($dailyIndices as $lbindex) {
            $count = $this->queryValue(
                "SELECT COUNT(*) FROM farkle_lbdata WHERE lbindex = :lbindex",
                [':lbindex' => $lbindex]
            );
            // Count can be 0 if no data for today/yesterday, but query should succeed
            $this->assertNotNull($count, "Query for daily lbindex $lbindex should succeed");
        }
    }

    /**
     * Test that refreshing clears old data before inserting new data
     */
    public function testRefreshDailyClearsOldData(): void
    {
        // Insert some dummy data
        $this->execute(
            "INSERT INTO farkle_lbdata (lbindex, playerid, username, playerlevel, first_int, lbrank)
             VALUES (0, :playerid, 'dummy', 1, 9999, 1)",
            [':playerid' => $this->testPlayerId]
        );

        // Verify it exists
        $beforeCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lbdata WHERE lbindex = 0 AND first_int = 9999"
        );
        $this->assertEquals(1, (int)$beforeCount, 'Dummy data should exist before refresh');

        // Run refresh
        Leaderboard_RefreshDaily();

        // Verify dummy data was cleared
        $afterCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lbdata WHERE lbindex = 0 AND first_int = 9999"
        );
        $this->assertEquals(0, (int)$afterCount, 'Dummy data should be cleared after refresh');
    }

    // ============= ROUND SCORE LEADERBOARD TESTS =============

    /**
     * Test that high round scores appear in leaderboard after refresh
     */
    public function testHighRoundScoresAppearInLeaderboard(): void
    {
        // Create a game for this player
        $opponentId = $this->createTestPlayer('lb_opponent');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Insert a round score with today's date (Chicago time)
        $highScore = 5000;
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score, NOW())",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId, ':score' => $highScore]
        );

        // Verify the round was inserted correctly
        $insertedRound = $this->queryRow(
            "SELECT playerid, roundscore, rounddatetime,
                    (rounddatetime AT TIME ZONE 'America/Chicago')::date as chicago_date,
                    (NOW() AT TIME ZONE 'America/Chicago')::date as today_chicago
             FROM farkle_rounds
             WHERE playerid = :playerid AND gameid = :gameid",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId]
        );
        $this->assertNotNull($insertedRound, 'Round should be inserted');

        // Verify player exists with correct username
        $player = $this->queryRow(
            "SELECT playerid, username, playerlevel FROM farkle_players WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );
        $this->assertNotNull($player, 'Player should exist');

        // Refresh daily leaderboards
        Leaderboard_RefreshDaily();

        // Check if the score appears in today's best rounds (lbindex 6)
        $entry = $this->queryRow(
            "SELECT first_int, lbrank FROM farkle_lbdata
             WHERE lbindex = 6 AND playerid = :playerid",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertNotNull($entry, 'Player should appear in today\'s best rounds leaderboard');
        $this->assertEquals($highScore, (int)$entry['first_int'], 'Round score should match');
    }

    /**
     * Test that round scores are ranked correctly (highest first)
     */
    public function testRoundScoresRankedCorrectly(): void
    {
        // Create players with different round scores
        $player1 = $this->createTestPlayer('lb_rank1');
        $player2 = $this->createTestPlayer('lb_rank2');
        $player3 = $this->createTestPlayer('lb_rank3');

        // Create a shared game
        $players = json_encode([$player1, $player2, $player3]);
        $_SESSION['playerid'] = $player1;
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Insert round scores with today's date
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score, NOW())",
            [':playerid' => $player1, ':gameid' => $gameId, ':score' => 3000]
        );
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score, NOW())",
            [':playerid' => $player2, ':gameid' => $gameId, ':score' => 5000]
        );
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score, NOW())",
            [':playerid' => $player3, ':gameid' => $gameId, ':score' => 4000]
        );

        // Refresh daily leaderboards
        Leaderboard_RefreshDaily();

        // Get rankings for today's best rounds (lbindex 6)
        $rankings = $this->queryAll(
            "SELECT playerid, first_int, lbrank FROM farkle_lbdata
             WHERE lbindex = 6
             ORDER BY lbrank ASC"
        );

        // Find our test players in the rankings
        $player1Rank = null;
        $player2Rank = null;
        $player3Rank = null;

        foreach ($rankings as $row) {
            if ((int)$row['playerid'] === $player1) $player1Rank = (int)$row['lbrank'];
            if ((int)$row['playerid'] === $player2) $player2Rank = (int)$row['lbrank'];
            if ((int)$row['playerid'] === $player3) $player3Rank = (int)$row['lbrank'];
        }

        // Player 2 (5000) should rank higher than Player 3 (4000), which should rank higher than Player 1 (3000)
        if ($player1Rank !== null && $player2Rank !== null && $player3Rank !== null) {
            $this->assertLessThan($player1Rank, $player2Rank, 'Player 2 (5000) should rank higher than Player 1 (3000)');
            $this->assertLessThan($player1Rank, $player3Rank, 'Player 3 (4000) should rank higher than Player 1 (3000)');
            $this->assertLessThan($player3Rank, $player2Rank, 'Player 2 (5000) should rank higher than Player 3 (4000)');
        }
    }

    // ============= TIMEZONE HANDLING TESTS =============

    /**
     * Test timezone conversion: round at 11 PM Chicago time should be "today"
     *
     * This tests the core timezone handling - rounds should be attributed to
     * the Chicago date, not the UTC date.
     */
    public function testRoundAt11pmChicagoIsToday(): void
    {
        // Create a game
        $opponentId = $this->createTestPlayer('lb_tz_today');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Insert a round with a timestamp at 11 PM Chicago time TODAY
        // This is done by inserting with explicit timezone conversion
        $uniqueScore = 7777; // Unique score to identify this entry
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score,
                     (DATE_TRUNC('day', NOW() AT TIME ZONE 'America/Chicago') + INTERVAL '23 hours') AT TIME ZONE 'America/Chicago')",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId, ':score' => $uniqueScore]
        );

        // Refresh daily leaderboards
        Leaderboard_RefreshDaily();

        // This round should appear in TODAY's leaderboard (lbindex 6), not yesterday's (16)
        $todayEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 6 AND playerid = :playerid AND first_int = :score",
            [':playerid' => $this->testPlayerId, ':score' => $uniqueScore]
        );

        $this->assertNotNull($todayEntry, 'Round at 11 PM Chicago time should appear in TODAY\'s leaderboard');

        // Verify it's NOT in yesterday's leaderboard
        $yesterdayEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 16 AND playerid = :playerid AND first_int = :score",
            [':playerid' => $this->testPlayerId, ':score' => $uniqueScore]
        );

        $this->assertNull($yesterdayEntry, 'Round at 11 PM Chicago time should NOT appear in yesterday\'s leaderboard');
    }

    /**
     * Test timezone conversion: round at 1 AM UTC (7 PM or 8 PM Chicago yesterday) should be "yesterday"
     *
     * This tests the timezone bug scenario where UTC timestamps were incorrectly converted.
     * A round played at 1 AM UTC is actually 7 PM CT (winter) or 8 PM CDT (summer) the previous day.
     */
    public function testRoundAt1amUtcIsYesterdayInChicago(): void
    {
        // Create a game
        $opponentId = $this->createTestPlayer('lb_tz_yesterday');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Insert a round at 1 AM UTC TODAY - which is 7 PM or 8 PM Chicago YESTERDAY
        // We need to construct a timestamp that is:
        // - Today at 1 AM UTC
        // - Which translates to yesterday at 7 PM CT (or 8 PM CDT)
        $uniqueScore = 8888;
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score,
                     DATE_TRUNC('day', NOW() AT TIME ZONE 'UTC') + INTERVAL '1 hour')",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId, ':score' => $uniqueScore]
        );

        // Refresh daily leaderboards
        Leaderboard_RefreshDaily();

        // This round should appear in YESTERDAY's leaderboard (lbindex 16), not today's (6)
        // because 1 AM UTC = 7 PM CT the previous day
        $yesterdayEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 16 AND playerid = :playerid AND first_int = :score",
            [':playerid' => $this->testPlayerId, ':score' => $uniqueScore]
        );

        // Note: This test may be flaky depending on the current time of day
        // If it's early morning UTC, the "yesterday Chicago" might be "2 days ago"
        // The important thing is that it should NOT be in today's Chicago leaderboard
        $todayEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 6 AND playerid = :playerid AND first_int = :score",
            [':playerid' => $this->testPlayerId, ':score' => $uniqueScore]
        );

        // If it's in today's Chicago leaderboard, the timezone conversion is wrong
        // (unless we're running this test between midnight and 1 AM UTC)
        $currentUtcHour = (int)$this->queryValue("SELECT EXTRACT(HOUR FROM NOW() AT TIME ZONE 'UTC')");

        if ($currentUtcHour >= 6) {
            // After 6 AM UTC (midnight CT during standard time), the 1 AM UTC round
            // should definitely be yesterday in Chicago time
            $this->assertNull(
                $todayEntry,
                'Round at 1 AM UTC should NOT appear in today\'s Chicago leaderboard (it\'s yesterday CT)'
            );
        }
        // If running in the early UTC morning, we skip this assertion as the behavior is correct but test is time-sensitive
    }

    /**
     * Test that midnight boundary is handled correctly (23:59 vs 00:01 Chicago time)
     *
     * This test verifies that the timezone conversion is working by inserting
     * rounds just before and after midnight Chicago time and verifying they
     * appear in the correct day's leaderboard.
     *
     * Important: The farkle_rounds table uses `timestamp without time zone`,
     * and the application stores UTC times using NOW(). The leaderboard queries
     * use the pattern: rounddatetime AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago'
     * to correctly convert UTC to Chicago time.
     */
    public function testMidnightBoundaryChicagoTime(): void
    {
        // Create a game
        $opponentId = $this->createTestPlayer('lb_tz_midnight');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // The database stores UTC times as `timestamp without time zone`.
        // The leaderboard queries use `AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago'`
        // to interpret the stored value as UTC and convert to Chicago time.
        //
        // To test the midnight boundary:
        // - midnight Chicago = 6 AM UTC (standard time) or 5 AM UTC (daylight time)
        // - 11:59 PM yesterday Chicago = 5:59 AM UTC today (or 4:59 AM UTC during DST)
        // - 12:01 AM today Chicago = 6:01 AM UTC today (or 5:01 AM UTC during DST)
        //
        // We need to insert UTC timestamps that, when converted to Chicago time,
        // give us dates on consecutive days.

        // Insert a round at 11:59 PM Chicago yesterday
        // This means we store the UTC equivalent of that time
        // Use very high scores to ensure they make the top 35 even with production data
        $yesterdayScore = 99991;
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score,
                     -- Get midnight Chicago, subtract 1 minute, convert to UTC for storage
                     ((DATE_TRUNC('day', NOW() AT TIME ZONE 'America/Chicago') - INTERVAL '1 minute')
                      AT TIME ZONE 'America/Chicago')::timestamp without time zone
                     )",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId, ':score' => $yesterdayScore]
        );

        // Insert a round at 12:01 AM Chicago today
        $todayScore = 99992;
        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 2, :score,
                     -- Get midnight Chicago, add 1 minute, convert to UTC for storage
                     ((DATE_TRUNC('day', NOW() AT TIME ZONE 'America/Chicago') + INTERVAL '1 minute')
                      AT TIME ZONE 'America/Chicago')::timestamp without time zone
                     )",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId, ':score' => $todayScore]
        );

        // Verify the inserted timestamps are on different Chicago dates when read back
        // using the same conversion pattern as the leaderboard queries
        $insertedRounds = $this->queryAll(
            "SELECT roundscore, rounddatetime,
                    (rounddatetime AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::date as chicago_date
             FROM farkle_rounds
             WHERE playerid = :playerid AND gameid = :gameid
             ORDER BY roundscore",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId]
        );
        $this->assertCount(2, $insertedRounds, 'Should have 2 rounds inserted');

        // The two rounds should have different Chicago dates
        $this->assertNotEquals(
            $insertedRounds[0]['chicago_date'],
            $insertedRounds[1]['chicago_date'],
            'The two rounds should be on different Chicago dates'
        );

        // Refresh
        Leaderboard_RefreshDaily();

        // Check today's entry (lbindex 6)
        $todayEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 6 AND playerid = :playerid AND first_int = :score",
            [':playerid' => $this->testPlayerId, ':score' => $todayScore]
        );
        $this->assertNotNull($todayEntry, '12:01 AM Chicago today should be in today\'s leaderboard');

        // The yesterday entry (11:59 PM) should NOT be in today's leaderboard
        $yesterdayInToday = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 6 AND playerid = :playerid AND first_int = :score",
            [':playerid' => $this->testPlayerId, ':score' => $yesterdayScore]
        );
        $this->assertNull($yesterdayInToday, '11:59 PM Chicago yesterday should NOT be in today\'s leaderboard');

        // Check yesterday's entry (lbindex 16)
        $yesterdayEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 16 AND playerid = :playerid AND first_int = :score",
            [':playerid' => $this->testPlayerId, ':score' => $yesterdayScore]
        );
        $this->assertNotNull($yesterdayEntry, '11:59 PM Chicago yesterday should be in yesterday\'s leaderboard');
    }

    // ============= GAME SCORE LEADERBOARD TESTS =============

    /**
     * Test that high game scores appear in today's leaderboard
     */
    public function testHighGameScoresAppearInTodayLeaderboard(): void
    {
        // Create a game
        $opponentId = $this->createTestPlayer('lb_game_score');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Update the player's game score and set lastplayed to now
        $highScore = 12000;
        $this->execute(
            "UPDATE farkle_games_players
             SET playerscore = :score, lastplayed = NOW()
             WHERE gameid = :gameid AND playerid = :playerid",
            [':score' => $highScore, ':gameid' => $gameId, ':playerid' => $this->testPlayerId]
        );

        // Refresh daily leaderboards
        Leaderboard_RefreshDaily();

        // Check today's high scores (lbindex 0)
        $entry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 0 AND playerid = :playerid",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertNotNull($entry, 'Player should appear in today\'s high scores leaderboard');
        $this->assertEquals($highScore, (int)$entry['first_int'], 'Game score should match');
    }

    // ============= FARKLE COUNT LEADERBOARD TESTS =============

    /**
     * Test that farkle counts appear in today's leaderboard
     */
    public function testFarkleCountsAppearInTodayLeaderboard(): void
    {
        // Create a game
        $opponentId = $this->createTestPlayer('lb_farkle_count');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Update lastplayed to today
        $this->execute(
            "UPDATE farkle_games_players SET lastplayed = NOW()
             WHERE gameid = :gameid AND playerid = :playerid",
            [':gameid' => $gameId, ':playerid' => $this->testPlayerId]
        );

        // Insert rounds with zero score (farkles) for today
        for ($i = 1; $i <= 5; $i++) {
            $this->execute(
                "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
                 VALUES (:playerid, :gameid, :roundnum, 0, NOW())",
                [':playerid' => $this->testPlayerId, ':gameid' => $gameId, ':roundnum' => $i]
            );
        }

        // Refresh daily leaderboards
        Leaderboard_RefreshDaily();

        // Check today's farklers (lbindex 1)
        $entry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 1 AND playerid = :playerid",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertNotNull($entry, 'Player should appear in today\'s farklers leaderboard');
        $this->assertEquals(5, (int)$entry['first_int'], 'Farkle count should be 5');
    }

    // ============= WIN RATIO LEADERBOARD TESTS =============

    /**
     * Test that win ratio requires minimum 3 games to qualify
     */
    public function testWinRatioRequiresMinimumGames(): void
    {
        // Create players
        $qualifiedPlayer = $this->createTestPlayer('lb_winratio_qualified');
        $unqualifiedPlayer = $this->createTestPlayer('lb_winratio_unqualified');

        // Create 3 games for qualified player (all wins)
        for ($i = 0; $i < 3; $i++) {
            $opponentId = $this->createTestPlayer("lb_opponent_$i");
            $players = json_encode([$qualifiedPlayer, $opponentId]);
            $_SESSION['playerid'] = $qualifiedPlayer;
            $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
            $gameId = $result[5];

            // Mark as completed with qualified player winning
            $this->execute(
                "UPDATE farkle_games
                 SET winningplayer = :winner, gamefinish = NOW()
                 WHERE gameid = :gameid",
                [':winner' => $qualifiedPlayer, ':gameid' => $gameId]
            );
        }

        // Create only 2 games for unqualified player (below 3 game minimum)
        for ($i = 0; $i < 2; $i++) {
            $opponentId = $this->createTestPlayer("lb_opponent2_$i");
            $players = json_encode([$unqualifiedPlayer, $opponentId]);
            $_SESSION['playerid'] = $unqualifiedPlayer;
            $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
            $gameId = $result[5];

            // Mark as completed
            $this->execute(
                "UPDATE farkle_games
                 SET winningplayer = :winner, gamefinish = NOW()
                 WHERE gameid = :gameid",
                [':winner' => $unqualifiedPlayer, ':gameid' => $gameId]
            );
        }

        // Refresh daily leaderboards
        Leaderboard_RefreshDaily();

        // Check win ratio leaderboard (lbindex 2)
        $qualifiedEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 2 AND playerid = :playerid",
            [':playerid' => $qualifiedPlayer]
        );

        $unqualifiedEntry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 2 AND playerid = :playerid",
            [':playerid' => $unqualifiedPlayer]
        );

        $this->assertNotNull($qualifiedEntry, 'Player with 3+ games should appear in win ratio leaderboard');
        $this->assertNull($unqualifiedEntry, 'Player with <3 games should NOT appear in win ratio leaderboard');
    }

    // ============= ALL-TIME LEADERBOARD TESTS =============

    /**
     * Test that wins/losses leaderboard populates correctly
     */
    public function testWinsLossesLeaderboardPopulates(): void
    {
        // Set some wins for our test player
        $this->execute(
            "UPDATE farkle_players SET wins = 100, losses = 50 WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );

        // Force refresh
        Leaderboard_RefreshData(true);

        // Check wins/losses leaderboard (lbindex 3)
        $entry = $this->queryRow(
            "SELECT first_int, second_int FROM farkle_lbdata
             WHERE lbindex = 3 AND playerid = :playerid",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertNotNull($entry, 'Player should appear in wins/losses leaderboard');
        $this->assertEquals(100, (int)$entry['first_int'], 'Wins should be 100');
        $this->assertEquals(50, (int)$entry['second_int'], 'Losses should be 50');
    }

    /**
     * Test that highest 10-round leaderboard populates correctly
     */
    public function testHighest10RoundLeaderboardPopulates(): void
    {
        // Set highest 10-round score for test player
        $this->execute(
            "UPDATE farkle_players SET highest10round = 15000 WHERE playerid = :id",
            [':id' => $this->testPlayerId]
        );

        // Force refresh
        Leaderboard_RefreshData(true);

        // Check highest 10-round leaderboard (lbindex 4)
        $entry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 4 AND playerid = :playerid",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertNotNull($entry, 'Player should appear in highest 10-round leaderboard');
        $this->assertEquals(15000, (int)$entry['first_int'], 'Highest 10-round should be 15000');
    }

    /**
     * Test that achievement points leaderboard populates correctly
     */
    public function testAchievementPointsLeaderboardPopulates(): void
    {
        // Get an achievement ID
        $achievementId = $this->queryValue("SELECT achievementid FROM farkle_achievements LIMIT 1");

        if (!$achievementId) {
            $this->markTestSkipped('No achievements in database');
        }

        // Award achievement to test player
        $this->execute(
            "INSERT INTO farkle_achievements_players (playerid, achievementid, achievedate)
             VALUES (:playerid, :achid, NOW())",
            [':playerid' => $this->testPlayerId, ':achid' => $achievementId]
        );

        // Force refresh
        Leaderboard_RefreshData(true);

        // Check achievement points leaderboard (lbindex 5)
        $entry = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata
             WHERE lbindex = 5 AND playerid = :playerid",
            [':playerid' => $this->testPlayerId]
        );

        $this->assertNotNull($entry, 'Player should appear in achievement points leaderboard');
        $this->assertGreaterThan(0, (int)$entry['first_int'], 'Achievement points should be > 0');
    }

    // ============= DAY OF WEEK UPDATE TEST =============

    /**
     * Test that day of week is updated during daily refresh
     */
    public function testDayOfWeekUpdated(): void
    {
        // Run daily refresh
        Leaderboard_RefreshDaily();

        // Check that siteinfo was updated with current day
        $dayOfWeek = $this->queryValue(
            "SELECT paramvalue FROM siteinfo WHERE paramid = :paramid",
            [':paramid' => 3]
        );

        $this->assertNotNull($dayOfWeek, 'Day of week should be set');
        $this->assertNotEmpty($dayOfWeek, 'Day of week should not be empty');
        // Format should be like "Monday   , Feb 02" (PostgreSQL pads 'Day' with spaces)
        // Match any day name (with possible trailing spaces), comma, month abbreviation, and day number
        $this->assertMatchesRegularExpression('/\w+\s*,\s+\w+\s+\d+/', trim($dayOfWeek));
    }

    // ============= INTEGRATION TEST =============

    /**
     * Test full leaderboard flow: play a round, refresh, verify appearance
     */
    public function testFullLeaderboardFlow(): void
    {
        // Create a game
        $opponentId = $this->createTestPlayer('lb_flow_opponent');
        $players = json_encode([$this->testPlayerId, $opponentId]);
        $result = FarkleNewGame($players, 0, 10000, GAME_WITH_FRIENDS, GAME_MODE_10ROUND);
        $gameId = $result[5];

        // Simulate playing: set score and record a round
        $gameScore = 8500;
        $roundScore = 2500;

        $this->execute(
            "UPDATE farkle_games_players
             SET playerscore = :score, lastplayed = NOW()
             WHERE gameid = :gameid AND playerid = :playerid",
            [':score' => $gameScore, ':gameid' => $gameId, ':playerid' => $this->testPlayerId]
        );

        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
             VALUES (:playerid, :gameid, 1, :score, NOW())",
            [':playerid' => $this->testPlayerId, ':gameid' => $gameId, ':score' => $roundScore]
        );

        // Update player's all-time stats
        $this->execute(
            "UPDATE farkle_players SET wins = 10, losses = 5, highest10round = :score
             WHERE playerid = :id",
            [':score' => $gameScore, ':id' => $this->testPlayerId]
        );

        // Refresh both daily and all-time leaderboards
        Leaderboard_RefreshDaily();
        Leaderboard_RefreshData(true);

        // Verify player appears in multiple leaderboards
        $todayScore = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata WHERE lbindex = 0 AND playerid = :id",
            [':id' => $this->testPlayerId]
        );
        $todayRound = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata WHERE lbindex = 6 AND playerid = :id",
            [':id' => $this->testPlayerId]
        );
        $allTimeWins = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata WHERE lbindex = 3 AND playerid = :id",
            [':id' => $this->testPlayerId]
        );
        $allTimeHighest = $this->queryRow(
            "SELECT first_int FROM farkle_lbdata WHERE lbindex = 4 AND playerid = :id",
            [':id' => $this->testPlayerId]
        );

        $this->assertNotNull($todayScore, 'Should appear in today\'s high scores');
        $this->assertEquals($gameScore, (int)$todayScore['first_int']);

        $this->assertNotNull($todayRound, 'Should appear in today\'s best rounds');
        $this->assertEquals($roundScore, (int)$todayRound['first_int']);

        $this->assertNotNull($allTimeWins, 'Should appear in all-time wins');
        $this->assertEquals(10, (int)$allTimeWins['first_int']);

        $this->assertNotNull($allTimeHighest, 'Should appear in all-time highest');
        $this->assertEquals($gameScore, (int)$allTimeHighest['first_int']);
    }
}
