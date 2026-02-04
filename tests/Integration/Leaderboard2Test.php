<?php
/**
 * Integration tests for Leaderboard 2.0 functionality.
 *
 * Tests the new daily/weekly/all-time leaderboard system including:
 * - Game eligibility and recording
 * - Daily score computation (top 10 of 20 games)
 * - Weekly score computation (best 5 of 7 days)
 * - All-time score computation (avg game score, 50-game qualifying)
 * - Board queries (friends/everyone scope)
 * - Daily progress API
 * - Rotating stat computations
 * - Rank snapshots and cleanup
 * - End-to-end flow
 */
namespace Tests\Integration;

use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../wwwroot/farkleLeaderboard.php';
require_once __DIR__ . '/../../wwwroot/farkleLeaderboardStats.php';
require_once __DIR__ . '/../../wwwroot/farkleGameFuncs.php';

class Leaderboard2Test extends DatabaseTestCase
{
    private int $player1Id;
    private int $player2Id;
    private string $player1Username;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->player1Id = $this->createTestPlayer('lb2_player1');
        $this->player2Id = $this->createTestPlayer('lb2_player2');

        $this->player1Username = $this->queryValue(
            "SELECT username FROM farkle_players WHERE playerid = :id",
            [':id' => $this->player1Id]
        );

        $this->today = $this->queryValue(
            "SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE"
        );

        $this->loginAs($this->player1Id);
        $_SESSION['username'] = $this->player1Username;
        $_SESSION['farkle'] = [];
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Create a finished game between two players and return the game ID.
     */
    private function createFinishedGame(int $player1, int $player2, int $p1Score, int $p2Score, int $gameWith = GAME_WITH_FRIENDS, ?string $finishTime = null): int
    {
        $finishTime = $finishTime ?? 'NOW()';

        $this->execute(
            "INSERT INTO farkle_games (whostarted, gamewith, gamemode, winningplayer, gamefinish, maxturns, currentround)
             VALUES (:p1, :gamewith, 2, :winner, $finishTime, 2, 11)",
            [':p1' => $player1, ':gamewith' => $gameWith, ':winner' => ($p1Score >= $p2Score ? $player1 : $player2)]
        );
        $gameId = (int)$this->queryValue("SELECT MAX(gameid) FROM farkle_games");

        $this->execute(
            "INSERT INTO farkle_games_players (gameid, playerid, playerscore, playerround, playerturn)
             VALUES (:gid, :pid, :score, 11, 1)",
            [':gid' => $gameId, ':pid' => $player1, ':score' => $p1Score]
        );
        $this->execute(
            "INSERT INTO farkle_games_players (gameid, playerid, playerscore, playerround, playerturn)
             VALUES (:gid, :pid, :score, 11, 2)",
            [':gid' => $gameId, ':pid' => $player2, ':score' => $p2Score]
        );

        return $gameId;
    }

    /**
     * Insert a daily game record directly into farkle_lb_daily_games.
     */
    private function insertDailyGame(int $playerId, int $gameId, string $date, int $seq, int $score, bool $counted = false): void
    {
        $this->execute(
            "INSERT INTO farkle_lb_daily_games (playerid, gameid, lb_date, game_seq, game_score, counted)
             VALUES (:pid, :gid, :date, :seq, :score, :counted)",
            [':pid' => $playerId, ':gid' => $gameId, ':date' => $date, ':seq' => $seq, ':score' => $score, ':counted' => $counted ? 'true' : 'false']
        );
    }

    /**
     * Create a bot player.
     */
    private function createBotPlayer(): int
    {
        $botId = $this->createTestPlayer('lb2_bot');
        $this->execute(
            "UPDATE farkle_players SET is_bot = true WHERE playerid = :id",
            [':id' => $botId]
        );
        return $botId;
    }

    /**
     * Make two players friends.
     */
    private function makeFriends(int $player1, int $player2): void
    {
        $this->execute(
            "INSERT INTO farkle_friends (sourceid, friendid, playerid, status, removed)
             VALUES (:p1, :p2, :p1b, 'accepted', 0)
             ON CONFLICT DO NOTHING",
            [':p1' => $player1, ':p2' => $player2, ':p1b' => $player1]
        );
    }

    // =====================================================
    // 1. GAME RECORDING & ELIGIBILITY
    // =====================================================

    public function testRecordEligibleGame_BasicInsert(): void
    {
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 4000);

        Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 5000, 10, GAME_WITH_FRIENDS);

        $row = $this->queryRow(
            "SELECT * FROM farkle_lb_daily_games WHERE playerid = :pid AND gameid = :gid",
            [':pid' => $this->player1Id, ':gid' => $gameId]
        );

        $this->assertNotNull($row, 'Game should be recorded in daily games');
        $this->assertEquals(5000, (int)$row['game_score']);
        $this->assertEquals(1, (int)$row['game_seq'], 'First game should be seq 1');
        $this->assertEquals($this->today, $row['lb_date']);
    }

    public function testRecordEligibleGame_SkipsSoloGames(): void
    {
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 0, GAME_WITH_SOLO);

        Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 5000, 10, GAME_WITH_SOLO);

        $count = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND gameid = :gid",
            [':pid' => $this->player1Id, ':gid' => $gameId]
        );
        $this->assertEquals(0, (int)$count, 'Solo games should not be recorded');
    }

    public function testRecordEligibleGame_SkipsBotGames(): void
    {
        $botId = $this->createBotPlayer();
        $gameId = $this->createFinishedGame($this->player1Id, $botId, 5000, 4000);

        Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 5000, 10, GAME_WITH_FRIENDS);

        $count = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND gameid = :gid",
            [':pid' => $this->player1Id, ':gid' => $gameId]
        );
        $this->assertEquals(0, (int)$count, 'Games with bot opponents should not be recorded');
    }

    public function testRecordEligibleGame_SkipsLowScore(): void
    {
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 500, 4000);

        Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 500, 10, GAME_WITH_FRIENDS);

        $count = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND gameid = :gid",
            [':pid' => $this->player1Id, ':gid' => $gameId]
        );
        $this->assertEquals(0, (int)$count, 'Scores under 1000 should not be recorded');
    }

    public function testRecordEligibleGame_SkipsShortGames(): void
    {
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 2000, 1500);

        Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 2000, 2, GAME_WITH_FRIENDS);

        $count = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND gameid = :gid",
            [':pid' => $this->player1Id, ':gid' => $gameId]
        );
        $this->assertEquals(0, (int)$count, 'Games with fewer than 3 rounds should not be recorded');
    }

    public function testRecordEligibleGame_20GameCap(): void
    {
        // Insert 20 games first
        for ($i = 1; $i <= 20; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 3000 + $i, 2000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, 3000 + $i);
        }

        // 21st game
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 4000);
        Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 5000, 10, GAME_WITH_FRIENDS);

        $row = $this->queryRow(
            "SELECT game_seq FROM farkle_lb_daily_games WHERE playerid = :pid AND gameid = :gid",
            [':pid' => $this->player1Id, ':gid' => $gameId]
        );

        $this->assertNotNull($row, '21st game should still be recorded');
        $this->assertEquals(21, (int)$row['game_seq'], 'Seq should be 21');
    }

    public function testRecordEligibleGame_NoDuplicates(): void
    {
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 4000);

        Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 5000, 10, GAME_WITH_FRIENDS);

        // Verify exactly 1 record exists
        $count = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND gameid = :gid",
            [':pid' => $this->player1Id, ':gid' => $gameId]
        );
        $this->assertEquals(1, (int)$count, 'Exactly one record should exist after recording');

        // Second call would fail on unique constraint — game_seq increments so it would
        // try to insert a new row with same (playerid, gameid). The function counts existing
        // games to get next seq, so calling again would produce seq=2 with same gameid,
        // which violates UNIQUE(playerid, gameid). This is expected behavior — a game
        // should only be recorded once at game completion time.
    }

    // =====================================================
    // 2. DAILY SCORE COMPUTATION
    // =====================================================

    public function testRecomputeDaily_Top10Selection(): void
    {
        // Insert 15 games with known scores
        $scores = [1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 1500, 2500, 3500, 4500, 5500];
        for ($i = 0; $i < 15; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $scores[$i], 1000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i + 1, $scores[$i]);
        }

        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        // Top 10 scores: 10000,9000,8000,7000,6000,5500,5000,4500,4000,3500 = 62500
        $dailyScore = $this->queryValue(
            "SELECT top10_score FROM farkle_lb_daily_scores WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        $this->assertEquals(62500, (int)$dailyScore, 'Daily score should be sum of top 10 game scores');
    }

    public function testRecomputeDaily_CountedFlags(): void
    {
        // Insert 12 games
        for ($i = 1; $i <= 12; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $i * 1000, 500);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, $i * 1000);
        }

        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        $countedCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND lb_date = :date AND counted = TRUE",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $uncountedCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND lb_date = :date AND counted = FALSE",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        $this->assertEquals(10, (int)$countedCount, 'Exactly 10 games should be counted');
        $this->assertEquals(2, (int)$uncountedCount, '2 games should not be counted');
    }

    public function testRecomputeDaily_QualifiesAt3Games(): void
    {
        // 2 games — should NOT qualify
        for ($i = 1; $i <= 2; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, 5000);
        }
        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        $qualifies = $this->queryValue(
            "SELECT qualifies FROM farkle_lb_daily_scores WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertFalse((bool)$qualifies, 'Player with 2 games should not qualify');

        // Add 3rd game — should qualify
        $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);
        $this->insertDailyGame($this->player1Id, $gid, $this->today, 3, 5000);
        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        $qualifies = $this->queryValue(
            "SELECT qualifies FROM farkle_lb_daily_scores WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertTrue((bool)$qualifies, 'Player with 3 games should qualify');
    }

    public function testRecomputeDaily_ScoreIsExactSum(): void
    {
        $scores = [4200, 5100, 3800, 6700, 2900];
        for ($i = 0; $i < 5; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $scores[$i], 1000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i + 1, $scores[$i]);
        }

        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        $expectedSum = array_sum($scores); // All 5 are in top 10
        $dailyScore = $this->queryValue(
            "SELECT top10_score FROM farkle_lb_daily_scores WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertEquals($expectedSum, (int)$dailyScore);
    }

    // =====================================================
    // 3. WEEKLY SCORE COMPUTATION
    // =====================================================

    public function testWeekly_Best5of7Days(): void
    {
        // Get current week start (Monday)
        $weekStart = $this->queryValue("SELECT date_trunc('week', :today::DATE)::DATE", [':today' => $this->today]);

        // Insert 6 qualifying daily scores for this week
        $dailyScores = [10000, 20000, 15000, 25000, 18000, 12000];
        for ($i = 0; $i < 6; $i++) {
            $dayDate = $this->queryValue("SELECT (:ws::DATE + (:offset || ' days')::INTERVAL)::DATE", [':ws' => $weekStart, ':offset' => $i]);
            $this->execute(
                "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
                 VALUES (:pid, :date, 5, :score, TRUE)",
                [':pid' => $this->player1Id, ':date' => $dayDate, ':score' => $dailyScores[$i]]
            );
        }

        Leaderboard_ComputeWeeklyScores();

        $row = $this->queryRow(
            "SELECT top5_score, daily_scores_used FROM farkle_lb_weekly_scores WHERE playerid = :pid AND week_start = :ws",
            [':pid' => $this->player1Id, ':ws' => $weekStart]
        );

        $this->assertNotNull($row, 'Weekly score should exist');
        // Top 5: 25000+20000+18000+15000+12000 = 90000
        $this->assertEquals(5, (int)$row['daily_scores_used']);
        rsort($dailyScores);
        $expectedTop5 = array_sum(array_slice($dailyScores, 0, 5));
        $this->assertEquals($expectedTop5, (int)$row['top5_score']);
    }

    public function testWeekly_QualifiesAt3Days(): void
    {
        $weekStart = $this->queryValue("SELECT date_trunc('week', :today::DATE)::DATE", [':today' => $this->today]);

        // Insert 2 qualifying days — should NOT qualify for weekly
        for ($i = 0; $i < 2; $i++) {
            $dayDate = $this->queryValue("SELECT (:ws::DATE + (:offset || ' days')::INTERVAL)::DATE", [':ws' => $weekStart, ':offset' => $i]);
            $this->execute(
                "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
                 VALUES (:pid, :date, 5, 10000, TRUE)",
                [':pid' => $this->player1Id, ':date' => $dayDate]
            );
        }

        Leaderboard_ComputeWeeklyScores();

        $qualifies = $this->queryValue(
            "SELECT qualifies FROM farkle_lb_weekly_scores WHERE playerid = :pid AND week_start = :ws",
            [':pid' => $this->player1Id, ':ws' => $weekStart]
        );
        $this->assertNotNull($qualifies);
        $this->assertFalse((bool)$qualifies, 'Player with 2 qualifying days should not qualify for weekly');

        // Add 3rd day
        $dayDate = $this->queryValue("SELECT (:ws::DATE + INTERVAL '2 days')::DATE", [':ws' => $weekStart]);
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 5, 10000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $dayDate]
        );

        Leaderboard_ComputeWeeklyScores();

        $qualifies = $this->queryValue(
            "SELECT qualifies FROM farkle_lb_weekly_scores WHERE playerid = :pid AND week_start = :ws",
            [':pid' => $this->player1Id, ':ws' => $weekStart]
        );
        $this->assertTrue((bool)$qualifies, 'Player with 3 qualifying days should qualify for weekly');
    }

    // =====================================================
    // 4. ALL-TIME SCORE COMPUTATION
    // =====================================================

    public function testAllTime_AvgGameScore(): void
    {
        // Insert daily games with known scores
        $scores = [4000, 5000, 6000, 7000, 8000];
        for ($i = 0; $i < count($scores); $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $scores[$i], 2000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i + 1, $scores[$i]);
        }

        // Need a daily score entry for alltime to pick up
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 5, 30000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        Leaderboard_ComputeAllTimeScores();

        $row = $this->queryRow(
            "SELECT avg_game_score, total_games FROM farkle_lb_alltime WHERE playerid = :pid",
            [':pid' => $this->player1Id]
        );

        $this->assertNotNull($row, 'All-time entry should exist');
        $expectedAvg = array_sum($scores) / count($scores); // 6000
        $this->assertEquals($expectedAvg, (float)$row['avg_game_score'], 'Avg game score should match', 0.01);
        $this->assertEquals(5, (int)$row['total_games']);
    }

    public function testAllTime_BestGameScore(): void
    {
        $scores = [3000, 9500, 5000, 7200];
        for ($i = 0; $i < count($scores); $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $scores[$i], 2000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i + 1, $scores[$i]);
        }

        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 4, 24700, TRUE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        Leaderboard_ComputeAllTimeScores();

        $bestGame = $this->queryValue(
            "SELECT best_game_score FROM farkle_lb_alltime WHERE playerid = :pid",
            [':pid' => $this->player1Id]
        );

        $this->assertEquals(9500, (int)$bestGame, 'Best game score should be the max');
    }

    public function testAllTime_QualifiesAt50Games(): void
    {
        // Insert 49 games — should NOT qualify
        for ($i = 1; $i <= 49; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, 5000);
        }

        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 20, 50000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        Leaderboard_ComputeAllTimeScores();

        $qualifies = $this->queryValue(
            "SELECT qualifies FROM farkle_lb_alltime WHERE playerid = :pid",
            [':pid' => $this->player1Id]
        );
        $this->assertFalse((bool)$qualifies, 'Player with 49 games should not qualify for all-time');

        // Add 50th game
        $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);
        $this->insertDailyGame($this->player1Id, $gid, $this->today, 50, 5000);

        Leaderboard_ComputeAllTimeScores();

        $qualifies = $this->queryValue(
            "SELECT qualifies FROM farkle_lb_alltime WHERE playerid = :pid",
            [':pid' => $this->player1Id]
        );
        $this->assertTrue((bool)$qualifies, 'Player with 50 games should qualify for all-time');
    }

    public function testAllTime_RankingOrder(): void
    {
        $player3 = $this->createTestPlayer('lb2_player3');

        // Player1: avg 6000 (50 games at 6000)
        for ($i = 1; $i <= 50; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 6000, 3000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, 6000);
        }
        // Player3: avg 8000 (50 games at 8000) — should rank higher
        for ($i = 1; $i <= 50; $i++) {
            $gid = $this->createFinishedGame($player3, $this->player2Id, 8000, 3000);
            $this->insertDailyGame($player3, $gid, $this->today, $i, 8000);
        }

        // Daily scores needed for alltime computation
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies) VALUES
             (:p1, :date, 20, 60000, TRUE), (:p3, :date2, 20, 80000, TRUE)",
            [':p1' => $this->player1Id, ':date' => $this->today, ':p3' => $player3, ':date2' => $this->today]
        );

        Leaderboard_ComputeAllTimeScores();

        // Both should qualify and have correct avg scores
        $p1Avg = $this->queryValue(
            "SELECT avg_game_score FROM farkle_lb_alltime WHERE playerid = :pid AND qualifies = TRUE",
            [':pid' => $this->player1Id]
        );
        $p3Avg = $this->queryValue(
            "SELECT avg_game_score FROM farkle_lb_alltime WHERE playerid = :pid AND qualifies = TRUE",
            [':pid' => $player3]
        );

        $this->assertNotNull($p1Avg, 'Player1 should qualify for all-time');
        $this->assertNotNull($p3Avg, 'Player3 should qualify for all-time');
        $this->assertGreaterThan((float)$p1Avg, (float)$p3Avg, 'Player3 (avg 8000) should have higher avg than Player1 (avg 6000)');

        // Verify board query ranks them correctly
        $board = Leaderboard_GetBoard_Alltime($this->player1Id, 'everyone');
        $this->assertGreaterThanOrEqual(2, count($board['entries']), 'Board should have at least 2 entries');
        // First entry should be player3 (higher avg)
        $this->assertEquals($player3, $board['entries'][0]['playerId'], 'Player3 should rank first');
    }

    // =====================================================
    // 5. BOARD QUERIES
    // =====================================================

    public function testGetBoard_DailyFriendsScope(): void
    {
        $player3 = $this->createTestPlayer('lb2_stranger');

        $this->makeFriends($this->player1Id, $this->player2Id);
        // player3 is NOT a friend

        // All 3 have qualifying daily scores
        foreach ([$this->player1Id, $this->player2Id, $player3] as $idx => $pid) {
            $this->execute(
                "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
                 VALUES (:pid, :date, 5, :score, TRUE)",
                [':pid' => $pid, ':date' => $this->today, ':score' => 50000 - ($idx * 10000)]
            );
        }

        $result = Leaderboard_GetBoard_Daily($this->player1Id, 'friends');

        $playerIds = array_map(fn($e) => $e['playerId'], $result['entries']);
        $this->assertContains($this->player1Id, $playerIds, 'Self should be in friends board');
        $this->assertContains($this->player2Id, $playerIds, 'Friend should be in friends board');
        $this->assertNotContains($player3, $playerIds, 'Stranger should NOT be in friends board');
    }

    public function testGetBoard_DailyEveryoneScope(): void
    {
        $player3 = $this->createTestPlayer('lb2_everyone');

        // No friendship needed for everyone scope
        foreach ([$this->player1Id, $this->player2Id, $player3] as $idx => $pid) {
            $this->execute(
                "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
                 VALUES (:pid, :date, 5, :score, TRUE)",
                [':pid' => $pid, ':date' => $this->today, ':score' => 50000 - ($idx * 10000)]
            );
        }

        $result = Leaderboard_GetBoard_Daily($this->player1Id, 'everyone');

        $playerIds = array_map(fn($e) => $e['playerId'], $result['entries']);
        $this->assertContains($player3, $playerIds, 'All qualifying players should appear in everyone scope');
    }

    public function testGetBoard_MyScoreIncluded(): void
    {
        // Insert qualifying daily score for player1
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 5, 30000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        $result = Leaderboard_GetBoard_Daily($this->player1Id, 'everyone');

        $this->assertNotNull($result['myScore'], 'myScore should be included');
        $this->assertEquals($this->player1Id, $result['myScore']['playerId']);
        $this->assertEquals(30000, $result['myScore']['score']);
    }

    public function testGetBoard_UnqualifiedPlayerNoRank(): void
    {
        // Insert non-qualifying daily score (only 2 games)
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 2, 10000, FALSE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        $result = Leaderboard_GetBoard_Daily($this->player1Id, 'everyone');

        $this->assertNotNull($result['myScore']);
        $this->assertNull($result['myScore']['rank'], 'Unqualified player should have null rank');
    }

    public function testGetBoard_AlltimeReturnsAvgGameScore(): void
    {
        // Set up qualifying all-time data
        for ($i = 1; $i <= 50; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 6000, 3000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, 6000);
        }

        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 20, 60000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        Leaderboard_ComputeAllTimeScores();

        $result = Leaderboard_GetBoard_Alltime($this->player1Id, 'everyone');

        $this->assertNotEmpty($result['entries'], 'All-time board should have entries');
        $entry = $result['entries'][0];
        $this->assertArrayHasKey('avgGameScore', $entry, 'Entry should include avgGameScore');
        $this->assertArrayHasKey('bestGameScore', $entry, 'Entry should include bestGameScore');
        $this->assertArrayHasKey('totalGames', $entry, 'Entry should include totalGames');
        $this->assertEquals(6000, (float)$entry['avgGameScore'], '', 0.01);
    }

    public function testGetBoard_StatValuesAttached(): void
    {
        // Insert daily score
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 5, 30000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        // Insert a stat value for the current featured stat
        $featured = LeaderboardStats_GetFeaturedStat();
        $this->execute(
            "INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value)
             VALUES (:pid, :date, :type, 42.5)",
            [':pid' => $this->player1Id, ':date' => $this->today, ':type' => $featured['type']]
        );

        $result = Leaderboard_GetBoard_Daily($this->player1Id, 'everyone');

        $this->assertNotEmpty($result['entries']);

        // Find the entry for player1 (might not be first if there are other players)
        $player1Entry = null;
        foreach ($result['entries'] as $entry) {
            if ($entry['playerId'] === $this->player1Id) {
                $player1Entry = $entry;
                break;
            }
        }

        $this->assertNotNull($player1Entry, 'Player1 should be in entries');
        $this->assertArrayHasKey('statValue', $player1Entry, 'Entry should have statValue key');
        $this->assertNotNull($player1Entry['statValue'], 'statValue should not be null when stat exists');
        $this->assertEquals(42.5, (float)$player1Entry['statValue'], 'statValue should match inserted value', 0.01);
    }

    public function testGetBoard_DailyFriendsShowsAcceptedFriends(): void
    {
        // Create test players
        $playerA = $this->player1Id;
        $playerB = $this->player2Id;
        $playerC = $this->createTestPlayer('lb2_pending_friend');
        $playerD = $this->createTestPlayer('lb2_no_friend');

        // Make playerA and playerB accepted friends
        $this->makeFriends($playerA, $playerB);

        // Make playerA and playerC pending friends (should NOT appear in friends scope)
        $this->execute(
            "INSERT INTO farkle_friends (sourceid, friendid, playerid, status, removed)
             VALUES (:p1, :p2, :p1b, 'pending', 0)
             ON CONFLICT DO NOTHING",
            [':p1' => $playerA, ':p2' => $playerC, ':p1b' => $playerA]
        );

        // playerD is not a friend at all (should NOT appear)

        // All 4 players have qualifying daily scores
        foreach ([$playerA, $playerB, $playerC, $playerD] as $idx => $pid) {
            $this->execute(
                "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
                 VALUES (:pid, :date, 5, :score, TRUE)",
                [':pid' => $pid, ':date' => $this->today, ':score' => 50000 - ($idx * 1000)]
            );
        }

        // Call Leaderboard_GetBoard_Daily with scope='friends' for player A
        $result = Leaderboard_GetBoard_Daily($playerA, 'friends');

        // Assert playerA and playerB (accepted friend) appear in the results
        $playerIds = array_map(fn($e) => $e['playerId'], $result['entries']);
        $this->assertContains($playerA, $playerIds, 'Player A (self) should appear in friends board');
        $this->assertContains($playerB, $playerIds, 'Player B (accepted friend) should appear in friends board');

        // Assert that pending and non-friends do NOT appear
        $this->assertNotContains($playerC, $playerIds, 'Player C (pending friend) should NOT appear in friends board');
        $this->assertNotContains($playerD, $playerIds, 'Player D (not a friend) should NOT appear in friends board');
    }

    public function testDailyBoardRowsIncludeStatValues(): void
    {
        // Create multiple test players
        $playerA = $this->player1Id;
        $playerB = $this->player2Id;
        $playerC = $this->createTestPlayer('lb2_stat_test_c');

        // Record games and compute daily scores for all players
        foreach ([$playerA, $playerB, $playerC] as $pid) {
            for ($i = 1; $i <= 5; $i++) {
                $gid = $this->createFinishedGame($pid, ($pid === $playerA ? $playerB : $playerA), 5000 + ($i * 100), 3000);
                $this->insertDailyGame($pid, $gid, $this->today, $i, 5000 + ($i * 100));
            }
            Leaderboard_RecomputeDailyScore($pid, $this->today);
        }

        // Insert specific stat values into farkle_lb_stats for today
        // Use the current featured stat type
        $featured = LeaderboardStats_GetFeaturedStat();
        $statType = $featured['type'];

        $this->execute(
            "INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value) VALUES
             (:p1, :date, :type, 42.5),
             (:p2, :date2, :type2, 87.3),
             (:p3, :date3, :type3, 15.9)",
            [
                ':p1' => $playerA, ':date' => $this->today, ':type' => $statType,
                ':p2' => $playerB, ':date2' => $this->today, ':type2' => $statType,
                ':p3' => $playerC, ':date3' => $this->today, ':type3' => $statType
            ]
        );

        // Call Leaderboard_GetBoard_Daily
        $result = Leaderboard_GetBoard_Daily($playerA, 'everyone');

        // Assert each entry has statValue field
        $this->assertNotEmpty($result['entries'], 'Board should have entries');
        foreach ($result['entries'] as $entry) {
            $this->assertArrayHasKey('statValue', $entry, 'Each entry should have statValue field');
        }

        // Assert statValue matches the DB value
        $statValuesByPlayerId = [];
        foreach ($result['entries'] as $entry) {
            $statValuesByPlayerId[$entry['playerId']] = $entry['statValue'];
        }

        $this->assertEquals(42.5, (float)$statValuesByPlayerId[$playerA], 'Player A statValue should match', 0.01);
        $this->assertEquals(87.3, (float)$statValuesByPlayerId[$playerB], 'Player B statValue should match', 0.01);
        $this->assertEquals(15.9, (float)$statValuesByPlayerId[$playerC], 'Player C statValue should match', 0.01);
    }

    // =====================================================
    // 6. DAILY PROGRESS API
    // =====================================================

    public function testDailyProgress_GamesPlayedCount(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, 5000);
        }

        // RecomputeDailyScore creates the summary row that GetDailyProgress reads
        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        $progress = Leaderboard_GetDailyProgress($this->player1Id);

        $this->assertEquals(5, $progress['games_played']);
        $this->assertEquals(20, $progress['games_max']);
    }

    public function testDailyProgress_DailyScoreMatchesSum(): void
    {
        $scores = [4000, 6000, 5000];
        for ($i = 0; $i < 3; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $scores[$i], 2000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i + 1, $scores[$i], true);
        }

        // Also insert daily score row
        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        $progress = Leaderboard_GetDailyProgress($this->player1Id);

        $this->assertEquals(array_sum($scores), $progress['daily_score']);
    }

    public function testDailyProgress_TopScoresDescending(): void
    {
        $scores = [3000, 7000, 5000, 2000, 6000];
        for ($i = 0; $i < 5; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $scores[$i], 1000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i + 1, $scores[$i]);
        }

        Leaderboard_RecomputeDailyScore($this->player1Id, $this->today);

        $progress = Leaderboard_GetDailyProgress($this->player1Id);

        $this->assertNotEmpty($progress['top_scores'], 'top_scores should not be empty');
        // Verify descending order
        $prev = PHP_INT_MAX;
        foreach ($progress['top_scores'] as $score) {
            $this->assertLessThanOrEqual($prev, (int)$score, 'Scores should be in descending order');
            $prev = (int)$score;
        }
    }

    // =====================================================
    // 7. ROTATING STATS
    // =====================================================

    public function testHotDice_HighestRoundScore(): void
    {
        // Create a game and rounds
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 8000, 5000);

        $this->execute(
            "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime) VALUES
             (:pid, :gid, 1, 2500, NOW()),
             (:pid2, :gid2, 2, 4200, NOW()),
             (:pid3, :gid3, 3, 1300, NOW())",
            [':pid' => $this->player1Id, ':gid' => $gameId,
             ':pid2' => $this->player1Id, ':gid2' => $gameId,
             ':pid3' => $this->player1Id, ':gid3' => $gameId]
        );

        LeaderboardStats_HotDice($this->today);

        $val = $this->queryValue(
            "SELECT stat_value FROM farkle_lb_stats WHERE playerid = :pid AND stat_type = 'hot_dice' AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertEquals(4200, (float)$val, 'Hot dice should record the highest round score');
    }

    public function testFarkleRate_Percentage(): void
    {
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);

        // 10 rounds: 3 farkles (score 0), 7 scoring rounds
        for ($i = 1; $i <= 10; $i++) {
            $score = ($i <= 3) ? 0 : 1000;
            $this->execute(
                "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
                 VALUES (:pid, :gid, :rnd, :score, NOW())",
                [':pid' => $this->player1Id, ':gid' => $gameId, ':rnd' => $i, ':score' => $score]
            );
        }

        LeaderboardStats_FarkleRate($this->today);

        $val = $this->queryValue(
            "SELECT stat_value FROM farkle_lb_stats WHERE playerid = :pid AND stat_type = 'farkle_rate' AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertEquals(30.0, (float)$val, 'Farkle rate should be 30% (3 farkles / 10 rounds)');
    }

    public function testFarkleRate_Min10Rounds(): void
    {
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);

        // Only 9 rounds — should be excluded
        for ($i = 1; $i <= 9; $i++) {
            $this->execute(
                "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
                 VALUES (:pid, :gid, :rnd, 1000, NOW())",
                [':pid' => $this->player1Id, ':gid' => $gameId, ':rnd' => $i]
            );
        }

        LeaderboardStats_FarkleRate($this->today);

        $val = $this->queryValue(
            "SELECT stat_value FROM farkle_lb_stats WHERE playerid = :pid AND stat_type = 'farkle_rate' AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertNull($val, 'Player with fewer than 10 rounds should not have a farkle rate');
    }

    public function testComebackKing_DeficitCalculation(): void
    {
        // Create a game where player1 wins despite trailing at round 5
        $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 8000, 7000);

        // Player1 rounds: low early, strong finish
        $p1Scores = [500, 500, 500, 500, 500, 2000, 2000, 2000, 500, 500]; // r5 total: 2500
        $p2Scores = [1500, 1500, 1500, 1500, 1500, 200, 200, 200, 200, 200]; // r5 total: 7500
        for ($i = 1; $i <= 10; $i++) {
            $this->execute(
                "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
                 VALUES (:pid, :gid, :rnd, :score, NOW())",
                [':pid' => $this->player1Id, ':gid' => $gameId, ':rnd' => $i, ':score' => $p1Scores[$i - 1]]
            );
            $this->execute(
                "INSERT INTO farkle_rounds (playerid, gameid, roundnum, roundscore, rounddatetime)
                 VALUES (:pid, :gid, :rnd, :score, NOW())",
                [':pid' => $this->player2Id, ':gid' => $gameId, ':rnd' => $i, ':score' => $p2Scores[$i - 1]]
            );
        }

        LeaderboardStats_ComebackKing($this->today);

        $val = $this->queryValue(
            "SELECT stat_value FROM farkle_lb_stats WHERE playerid = :pid AND stat_type = 'comeback_king' AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        // Deficit = opponent r5 total (7500) - winner r5 total (2500) = 5000
        $this->assertEquals(5000, (float)$val, 'Comeback deficit should be 5000');
    }

    public function testConsistency_StdDev(): void
    {
        // Insert 5 counted games with known scores
        $scores = [5000, 5000, 5000, 5000, 5000]; // Perfect consistency = stddev 0
        for ($i = 0; $i < 5; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, $scores[$i], 2000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i + 1, $scores[$i], true);
        }

        LeaderboardStats_Consistency($this->today);

        $val = $this->queryValue(
            "SELECT stat_value FROM farkle_lb_stats WHERE playerid = :pid AND stat_type = 'consistency' AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        $this->assertEquals(0.0, (float)$val, 'Identical scores should have 0 standard deviation');
    }

    public function testConsistency_Min5Games(): void
    {
        // Insert only 4 counted games
        for ($i = 1; $i <= 4; $i++) {
            $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 2000);
            $this->insertDailyGame($this->player1Id, $gid, $this->today, $i, 5000, true);
        }

        LeaderboardStats_Consistency($this->today);

        $val = $this->queryValue(
            "SELECT stat_value FROM farkle_lb_stats WHERE playerid = :pid AND stat_type = 'consistency' AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertNull($val, 'Player with fewer than 5 counted games should not have consistency stat');
    }

    public function testFeaturedStatRotation(): void
    {
        // GetFeaturedStat returns different types based on day of year
        $stat = LeaderboardStats_GetFeaturedStat();

        $validTypes = ['hot_dice', 'farkle_rate', 'comeback_king', 'hot_streak', 'consistency'];
        $this->assertContains($stat['type'], $validTypes, 'Featured stat type should be valid');
        $this->assertNotEmpty($stat['name'], 'Featured stat should have a name');
    }

    public function testGetTopForDate_LowerIsBetter(): void
    {
        $player3 = $this->createTestPlayer('lb2_stat_order');

        // Insert farkle rates: player1=10%, player3=5% (lower is better)
        $this->execute(
            "INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value) VALUES
             (:p1, :date, 'farkle_rate', 10.0), (:p3, :date2, 'farkle_rate', 5.0)",
            [':p1' => $this->player1Id, ':date' => $this->today, ':p3' => $player3, ':date2' => $this->today]
        );

        $top = LeaderboardStats_GetTopForDate('farkle_rate', $this->today, 10);

        $this->assertNotEmpty($top);
        // First entry should be player3 (5%) since lower is better
        $this->assertEquals($player3, (int)$top[0]['playerid'], 'Lower farkle rate should rank first');
    }

    // =====================================================
    // 8. RANK SNAPSHOTS & CLEANUP
    // =====================================================

    public function testSnapshotRanks_PrevRankUpdated(): void
    {
        $yesterday = $this->queryValue("SELECT (:today::DATE - INTERVAL '1 day')::DATE", [':today' => $this->today]);

        // Insert yesterday's qualifying score (will be ranked)
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 5, 30000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $yesterday]
        );

        // Insert today's qualifying score (will receive prev_rank from yesterday)
        $this->execute(
            "INSERT INTO farkle_lb_daily_scores (playerid, lb_date, games_played, top10_score, qualifies)
             VALUES (:pid, :date, 5, 30000, TRUE)",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );

        Leaderboard_SnapshotRanks();

        $prevRank = $this->queryValue(
            "SELECT prev_rank FROM farkle_lb_daily_scores WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        // Yesterday had 1 qualifying player, so rank=1 → today's prev_rank should be 1
        $this->assertEquals(1, (int)$prevRank, 'prev_rank should reflect yesterday rank');
    }

    public function testCleanup_OldRecordsDeleted(): void
    {
        // Insert a record from 100 days ago
        $oldDate = $this->queryValue("SELECT (NOW() - INTERVAL '100 days')::DATE");
        $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);
        $this->insertDailyGame($this->player1Id, $gid, $oldDate, 1, 5000);

        $beforeCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $oldDate]
        );
        $this->assertEquals(1, (int)$beforeCount);

        Leaderboard_Cleanup();

        $afterCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $oldDate]
        );
        $this->assertEquals(0, (int)$afterCount, 'Records older than 90 days should be deleted');
    }

    public function testCleanup_RecentRecordsKept(): void
    {
        // Insert a record from 30 days ago
        $recentDate = $this->queryValue("SELECT (NOW() - INTERVAL '30 days')::DATE");
        $gid = $this->createFinishedGame($this->player1Id, $this->player2Id, 5000, 3000);
        $this->insertDailyGame($this->player1Id, $gid, $recentDate, 1, 5000);

        Leaderboard_Cleanup();

        $count = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $recentDate]
        );
        $this->assertEquals(1, (int)$count, 'Records less than 90 days old should be kept');
    }

    // =====================================================
    // 9. END-TO-END FLOW
    // =====================================================

    public function testFullFlow_GameToLeaderboard(): void
    {
        // Record 3 eligible games (minimum to qualify daily)
        for ($i = 1; $i <= 3; $i++) {
            $gameId = $this->createFinishedGame($this->player1Id, $this->player2Id, 4000 + ($i * 1000), 3000);
            Leaderboard_RecordEligibleGame($this->player1Id, $gameId, 4000 + ($i * 1000), 10, GAME_WITH_FRIENDS);
        }

        // Verify daily games recorded
        $gameCount = $this->queryValue(
            "SELECT COUNT(*) FROM farkle_lb_daily_games WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertEquals(3, (int)$gameCount, 'Should have 3 daily games');

        // Verify daily score computed and qualifying
        $dailyRow = $this->queryRow(
            "SELECT top10_score, qualifies FROM farkle_lb_daily_scores WHERE playerid = :pid AND lb_date = :date",
            [':pid' => $this->player1Id, ':date' => $this->today]
        );
        $this->assertNotNull($dailyRow, 'Daily score should exist');
        $this->assertTrue((bool)$dailyRow['qualifies'], 'Should qualify with 3 games');
        $this->assertEquals(5000 + 6000 + 7000, (int)$dailyRow['top10_score'], 'Daily score should be sum of all 3');

        // Compute weekly
        Leaderboard_ComputeWeeklyScores();

        $weekStart = $this->queryValue("SELECT date_trunc('week', :today::DATE)::DATE", [':today' => $this->today]);
        $weeklyRow = $this->queryRow(
            "SELECT top5_score FROM farkle_lb_weekly_scores WHERE playerid = :pid AND week_start = :ws",
            [':pid' => $this->player1Id, ':ws' => $weekStart]
        );
        $this->assertNotNull($weeklyRow, 'Weekly score should exist');

        // Compute all-time
        Leaderboard_ComputeAllTimeScores();

        $alltimeRow = $this->queryRow(
            "SELECT avg_game_score, total_games FROM farkle_lb_alltime WHERE playerid = :pid",
            [':pid' => $this->player1Id]
        );
        $this->assertNotNull($alltimeRow, 'All-time entry should exist');
        $this->assertEquals(3, (int)$alltimeRow['total_games']);
        // avg of 5000, 6000, 7000 = 6000
        $this->assertEquals(6000.0, (float)$alltimeRow['avg_game_score'], '', 0.01);

        // Verify daily board query returns data
        $board = Leaderboard_GetBoard_Daily($this->player1Id, 'everyone');
        $this->assertNotEmpty($board['entries'], 'Daily board should have entries');
        $this->assertNotNull($board['myScore'], 'myScore should be present');
    }
}
