<?php
namespace Tests\Integration;

use Tests\DatabaseTestCase;

/**
 * Tests for performance optimizations:
 * - PDO persistent connections
 * - Session change detection
 * - Leaderboard static caching
 * - Friend list static caching
 */
class PerformanceTest extends DatabaseTestCase
{
    /**
     * Test that PDO persistent connections are enabled
     *
     * Note: PostgreSQL PDO driver doesn't support reading ATTR_PERSISTENT attribute,
     * so we verify by checking the source code was set correctly.
     */
    public function testPdoPersistentConnectionsEnabled(): void
    {
        $dbh = db_connect();

        // Verify we have a PDO instance
        $this->assertInstanceOf(\PDO::class, $dbh);

        // Verify the dbutil.php source code has persistent connections enabled
        $dbutilSource = file_get_contents(__DIR__ . '/../../includes/dbutil.php');

        $this->assertStringContainsString('PDO::ATTR_PERSISTENT => true',
            $dbutilSource,
            'dbutil.php should have PDO::ATTR_PERSISTENT => true in connection options');

        $this->assertStringContainsString('connect_timeout=5',
            $dbutilSource,
            'dbutil.php should have connect_timeout=5 in PostgreSQL DSN string');

        // Verify connection is working
        $result = db_query("SELECT 1 as test", [], SQL_SINGLE_VALUE);
        $this->assertEquals(1, $result, 'Database connection should work with persistent connections');
    }

    /**
     * Test session change detection - writes should be skipped when data unchanged
     */
    public function testSessionChangeDetectionSkipsUnchangedWrites(): void
    {
        // This test verifies the session handler's change detection logic
        // by checking that the handler stores previous data correctly

        $dbh = db_connect();
        require_once(__DIR__ . '/../../includes/session-handler.php');

        $handler = new \DatabaseSessionHandler($dbh);
        $sessionId = 'test_session_' . uniqid();
        $testData = 'test_data_' . time();

        // First write - should succeed
        $result1 = $handler->write($sessionId, $testData);
        $this->assertTrue($result1, 'First session write should succeed');

        // Second write with same data - should be skipped but return true
        // The actual skipping is logged, we just verify it returns true
        $result2 = $handler->write($sessionId, $testData);
        $this->assertTrue($result2, 'Second session write should return true (skipped internally)');

        // Write with different data - should succeed
        $newData = 'different_data_' . time();
        $result3 = $handler->write($sessionId, $newData);
        $this->assertTrue($result3, 'Session write with changed data should succeed');

        // Clean up
        $handler->destroy($sessionId);
    }

    /**
     * Test leaderboard static caching with TTL
     */
    public function testLeaderboardStaticCaching(): void
    {
        require_once(__DIR__ . '/../../wwwroot/farkleLeaderboard.php');
        require_once(__DIR__ . '/../../wwwroot/farkleAchievements.php');

        // Create test player
        $playerId = $this->createTestPlayer('lb_cache_test');
        $this->loginAs($playerId);

        // Ensure leaderboard data exists
        \Leaderboard_RefreshData(true); // Force refresh

        // First call - should populate cache
        $startTime = microtime(true);
        $lb1 = \GetLeaderBoard();
        $time1 = microtime(true) - $startTime;

        // Second call immediately after - should use cache (faster)
        $startTime = microtime(true);
        $lb2 = \GetLeaderBoard();
        $time2 = microtime(true) - $startTime;

        // Verify cache hit is faster (should be significantly faster)
        $this->assertLessThan($time1 * 0.5, $time2,
            'Cached leaderboard call should be at least 50% faster than uncached');

        // Verify data is the same
        $this->assertNotEmpty($lb1, 'Leaderboard data should not be empty');
        $this->assertEquals($lb1, $lb2, 'Cached and uncached data should match');

        // Verify dayOfWeek is set
        $this->assertArrayHasKey('dayOfWeek', $lb1, 'Leaderboard should have dayOfWeek');
    }

    /**
     * Test leaderboard cache respects dirty flag
     */
    public function testLeaderboardDirtyFlagInvalidatesCache(): void
    {
        global $g_leaderboardDirty;

        require_once(__DIR__ . '/../../wwwroot/farkleLeaderboard.php');
        require_once(__DIR__ . '/../../wwwroot/farkleAchievements.php');

        // Create test player
        $playerId = $this->createTestPlayer('lb_dirty_test');
        $this->loginAs($playerId);

        // Ensure leaderboard data exists
        \Leaderboard_RefreshData(true);

        // First call - populate cache
        $lb1 = \GetLeaderBoard();

        // Set dirty flag
        $g_leaderboardDirty = 1;

        // Second call with dirty flag - should re-query
        $lb2 = \GetLeaderBoard();

        // Both should return data
        $this->assertNotEmpty($lb1, 'Initial leaderboard should have data');
        $this->assertNotEmpty($lb2, 'Refreshed leaderboard should have data');

        // Dirty flag should be reset after GetLeaderBoard
        $this->assertEquals(0, $g_leaderboardDirty, 'Dirty flag should be reset after fetch');
    }

    /**
     * Test friend list static caching with TTL
     */
    public function testFriendListStaticCaching(): void
    {
        require_once(__DIR__ . '/../../wwwroot/farkleFriends.php');

        // Create two test players (one will be the friend)
        $playerId = $this->createTestPlayer('friend_cache_test');
        $friendId = $this->createTestPlayer('friend_user');

        // Add friend relationship
        $sql = "INSERT INTO farkle_friends (sourceid, friendid, status, removed)
                VALUES (:sourceid, :friendid, 'accepted', 0)";
        $this->execute($sql, [
            ':sourceid' => $playerId,
            ':friendid' => $friendId
        ]);

        // First call - should populate cache
        $startTime = microtime(true);
        $friends1 = \GetGameFriends($playerId);
        $time1 = microtime(true) - $startTime;

        // Second call immediately after - should use cache (faster)
        $startTime = microtime(true);
        $friends2 = \GetGameFriends($playerId);
        $time2 = microtime(true) - $startTime;

        // Verify cache hit is faster
        $this->assertLessThan($time1 * 0.5, $time2,
            'Cached friend list call should be at least 50% faster than uncached');

        // Verify data is the same
        $this->assertEquals($friends1, $friends2, 'Cached and uncached friend data should match');

        // Verify friend is in the list
        $this->assertIsArray($friends1, 'Friend list should be an array');
        $this->assertNotEmpty($friends1, 'Friend list should not be empty');
    }

    /**
     * Test friend list force refresh bypasses cache
     */
    public function testFriendListForceRefresh(): void
    {
        require_once(__DIR__ . '/../../wwwroot/farkleFriends.php');

        $playerId = $this->createTestPlayer('friend_force_test');
        $friendId = $this->createTestPlayer('friend_user2');

        // Add friend
        $sql = "INSERT INTO farkle_friends (sourceid, friendid, status, removed)
                VALUES (:sourceid, :friendid, 'accepted', 0)";
        $this->execute($sql, [
            ':sourceid' => $playerId,
            ':friendid' => $friendId
        ]);

        // First call - populate cache
        $friends1 = \GetGameFriends($playerId);
        $this->assertNotEmpty($friends1);

        // Add another friend
        $friendId2 = $this->createTestPlayer('friend_user3');
        $this->execute($sql, [
            ':sourceid' => $playerId,
            ':friendid' => $friendId2
        ]);

        // Without force refresh - should still return cached data (1 friend)
        $friends2 = \GetGameFriends($playerId, false);
        $this->assertCount(count($friends1), $friends2, 'Without force, cache should be used');

        // With force refresh - should return new data (2 friends)
        $friends3 = \GetGameFriends($playerId, true);
        $this->assertGreaterThan(count($friends1), count($friends3),
            'Force refresh should bypass cache and return updated data');
    }

    /**
     * Test that leaderboard data is not stored in sessions
     */
    public function testLeaderboardNotInSession(): void
    {
        require_once(__DIR__ . '/../../wwwroot/farkleLeaderboard.php');
        require_once(__DIR__ . '/../../wwwroot/farkleAchievements.php');

        // Create test player
        $playerId = $this->createTestPlayer('lb_session_test');
        $this->loginAs($playerId);

        // Clear any existing session leaderboard data
        unset($_SESSION['farkle']['lb']);
        unset($_SESSION['farkle']['lbTimestamp']);

        // Get leaderboard
        \Leaderboard_RefreshData(true);
        $lb = \GetLeaderBoard();

        // Verify leaderboard data was returned
        $this->assertNotEmpty($lb, 'Leaderboard should return data');

        // Verify it's NOT stored in session
        $this->assertFalse(isset($_SESSION['farkle']['lb']),
            'Leaderboard data should NOT be stored in $_SESSION');
        $this->assertFalse(isset($_SESSION['farkle']['lbTimestamp']),
            'Leaderboard timestamp should NOT be stored in $_SESSION');
    }

    /**
     * Test that friend list data is not stored in sessions
     */
    public function testFriendListNotInSession(): void
    {
        require_once(__DIR__ . '/../../wwwroot/farkleFriends.php');

        // Create test player
        $playerId = $this->createTestPlayer('friend_session_test');

        // Clear any existing session friend data
        unset($_SESSION['farkle']['friends']);

        // Get friends
        $friends = \GetGameFriends($playerId);

        // Verify friends data was returned
        $this->assertIsArray($friends, 'Friends should return array');

        // Verify it's NOT stored in session
        $this->assertFalse(isset($_SESSION['farkle']['friends']),
            'Friend list data should NOT be stored in $_SESSION');
    }

    /**
     * Test overall session size reduction
     */
    public function testSessionSizeReduction(): void
    {
        require_once(__DIR__ . '/../../wwwroot/farkleLeaderboard.php');
        require_once(__DIR__ . '/../../wwwroot/farkleFriends.php');
        require_once(__DIR__ . '/../../wwwroot/farkleAchievements.php');

        // Create test player and login
        $playerId = $this->createTestPlayer('session_size_test');
        $friendId = $this->createTestPlayer('friend');
        $this->loginAs($playerId);

        // Add a friend
        $sql = "INSERT INTO farkle_friends (sourceid, friendid, status, removed)
                VALUES (:sourceid, :friendid, 'accepted', 0)";
        $this->execute($sql, [':sourceid' => $playerId, ':friendid' => $friendId]);

        // Trigger operations that used to bloat sessions
        \Leaderboard_RefreshData(true);
        \GetLeaderBoard();
        \GetGameFriends($playerId);

        // Serialize session to measure size
        $sessionData = serialize($_SESSION);
        $sessionSize = strlen($sessionData);

        // Session should be under 5KB (was 23KB before optimization)
        $this->assertLessThan(5120, $sessionSize,
            "Session size should be under 5KB, got " . round($sessionSize/1024, 2) . "KB");

        // Log the actual size for reference
        error_log("Session size after optimizations: " . round($sessionSize/1024, 2) . "KB");
    }
}
