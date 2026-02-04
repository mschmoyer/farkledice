<?php
/**
 * Leaderboard 2.0 -- Rotating Stat Computations
 *
 * Computes daily/weekly stats for the rotating stat highlights.
 * Called by cron jobs to materialize stats into farkle_lb_stats table.
 */

/**
 * Compute all rotating stats for a given date
 */
function LeaderboardStats_ComputeAll($date = null) {
    if ($date === null) {
        $dateResult = db_query("SELECT (NOW() AT TIME ZONE 'America/Chicago')::DATE as today", [], SQL_SINGLE_VALUE);
        $date = $dateResult;
    }

    LeaderboardStats_HotDice($date);
    LeaderboardStats_FarkleRate($date);
    LeaderboardStats_ComebackKing($date);
    LeaderboardStats_HotStreak($date);
    LeaderboardStats_Consistency($date);
}

/**
 * Hot Dice: Highest single round score for each player on a given date.
 * Source: farkle_rounds.roundscore
 */
function LeaderboardStats_HotDice($date) {
    $sql = "
    INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value, stat_detail)
    SELECT
        r.playerid,
        :lb_date,
        'hot_dice',
        MAX(r.roundscore),
        json_build_object('gameid', (
            SELECT r2.gameid FROM farkle_rounds r2
            WHERE r2.playerid = r.playerid
              AND (r2.rounddatetime AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::DATE = :lb_date2
            ORDER BY r2.roundscore DESC LIMIT 1
        ))::TEXT
    FROM farkle_rounds r
    JOIN farkle_games g ON r.gameid = g.gameid
    WHERE (r.rounddatetime AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::DATE = :lb_date3
      AND g.gamewith IN (0, 1)
      AND r.roundscore > 0
    GROUP BY r.playerid
    ON CONFLICT (playerid, lb_date, stat_type) DO UPDATE SET
        stat_value = EXCLUDED.stat_value,
        stat_detail = EXCLUDED.stat_detail
    ";
    db_execute($sql, [':lb_date' => $date, ':lb_date2' => $date, ':lb_date3' => $date]);
}

/**
 * Farkle Rate: Percentage of rounds that farkled (roundscore = 0).
 * Lower is better (more skilled). Stored as percentage 0-100.
 * Requires minimum 10 rounds for a meaningful rate.
 */
function LeaderboardStats_FarkleRate($date) {
    $sql = "
    INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value, stat_detail)
    SELECT
        r.playerid,
        :lb_date,
        'farkle_rate',
        ROUND(100.0 * SUM(CASE WHEN r.roundscore = 0 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2),
        json_build_object('total_rounds', COUNT(*), 'farkles', SUM(CASE WHEN r.roundscore = 0 THEN 1 ELSE 0 END))::TEXT
    FROM farkle_rounds r
    JOIN farkle_games g ON r.gameid = g.gameid
    WHERE (r.rounddatetime AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::DATE = :lb_date2
      AND g.gamewith IN (0, 1)
    GROUP BY r.playerid
    HAVING COUNT(*) >= 10
    ON CONFLICT (playerid, lb_date, stat_type) DO UPDATE SET
        stat_value = EXCLUDED.stat_value,
        stat_detail = EXCLUDED.stat_detail
    ";
    db_execute($sql, [':lb_date' => $date, ':lb_date2' => $date]);
}

/**
 * Comeback King: Largest deficit overcome to win.
 * Finds games finished on the given date where the winner was trailing
 * after round 5, and records the largest deficit they overcame.
 * The stat_detail includes the number of comeback games found.
 */
function LeaderboardStats_ComebackKing($date) {
    $sql = "
    INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value, stat_detail)
    SELECT
        comebacks.winningplayer,
        :lb_date,
        'comeback_king',
        MAX(comebacks.deficit),
        json_build_object('games', COUNT(*))::TEXT
    FROM (
        SELECT g.gameid, g.winningplayer,
            (opponent_best.max_r5 - winner_r5.w_r5_score) as deficit
        FROM farkle_games g
        JOIN (
            SELECT gameid, playerid, SUM(roundscore) as w_r5_score
            FROM farkle_rounds WHERE roundnum <= 5
            GROUP BY gameid, playerid
        ) winner_r5 ON winner_r5.gameid = g.gameid AND winner_r5.playerid = g.winningplayer
        JOIN (
            SELECT gameid, MAX(r5_total) as max_r5 FROM (
                SELECT gameid, playerid, SUM(roundscore) as r5_total
                FROM farkle_rounds WHERE roundnum <= 5
                GROUP BY gameid, playerid
            ) sub GROUP BY gameid
        ) opponent_best ON opponent_best.gameid = g.gameid
        WHERE (g.gamefinish AT TIME ZONE 'UTC' AT TIME ZONE 'America/Chicago')::DATE = :lb_date2
          AND g.gamewith IN (0, 1)
          AND g.winningplayer IS NOT NULL
          AND winner_r5.w_r5_score < opponent_best.max_r5
    ) comebacks
    GROUP BY comebacks.winningplayer
    HAVING MAX(comebacks.deficit) > 0
    ON CONFLICT (playerid, lb_date, stat_type) DO UPDATE SET
        stat_value = EXCLUDED.stat_value,
        stat_detail = EXCLUDED.stat_detail
    ";
    db_execute($sql, [':lb_date' => $date, ':lb_date2' => $date]);
}

/**
 * Hot Streak: Current win streak from farkle_players.
 * Only includes human players with a streak of 2 or more.
 */
function LeaderboardStats_HotStreak($date) {
    $sql = "
    INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value)
    SELECT playerid, :lb_date, 'hot_streak', current_win_streak
    FROM farkle_players
    WHERE current_win_streak >= 2
      AND is_bot = false
    ON CONFLICT (playerid, lb_date, stat_type) DO UPDATE SET
        stat_value = EXCLUDED.stat_value
    ";
    db_execute($sql, [':lb_date' => $date]);
}

/**
 * Consistency: Standard deviation of top-10 counted game scores (lower = more consistent).
 * Requires at least 5 counted games on the given date for meaningful stddev.
 */
function LeaderboardStats_Consistency($date) {
    $sql = "
    INSERT INTO farkle_lb_stats (playerid, lb_date, stat_type, stat_value, stat_detail)
    SELECT
        playerid,
        :lb_date,
        'consistency',
        ROUND(STDDEV_POP(game_score)::numeric, 2),
        json_build_object('games', COUNT(*), 'avg', ROUND(AVG(game_score)::numeric, 0))::TEXT
    FROM farkle_lb_daily_games
    WHERE lb_date = :lb_date2
      AND counted = TRUE
    GROUP BY playerid
    HAVING COUNT(*) >= 5
    ON CONFLICT (playerid, lb_date, stat_type) DO UPDATE SET
        stat_value = EXCLUDED.stat_value,
        stat_detail = EXCLUDED.stat_detail
    ";
    db_execute($sql, [':lb_date' => $date, ':lb_date2' => $date]);
}

/**
 * Get the current featured stat for display.
 * Rotates weekly based on ISO week number.
 *
 * @return array Associative array with 'type' and 'name' keys
 */
function LeaderboardStats_GetFeaturedStat() {
    $statTypes = ['hot_dice', 'farkle_rate', 'comeback_king', 'hot_streak', 'consistency'];
    $statNames = [
        'hot_dice' => 'Hot Dice -- Highest Single Round',
        'farkle_rate' => 'Farkle Rate -- Lowest Farkle %',
        'comeback_king' => 'Comeback King -- Largest Deficit Overcome',
        'hot_streak' => 'Hot Streak -- Current Win Streak',
        'consistency' => 'Consistency -- Steadiest Scorer'
    ];

    // Rotate daily based on day of year
    $dayOfYear = (int)date('z');
    $currentStat = $statTypes[$dayOfYear % count($statTypes)];

    return [
        'type' => $currentStat,
        'name' => $statNames[$currentStat]
    ];
}

/**
 * Get top entries for a stat type on a given date.
 *
 * @param string $statType The stat type (hot_dice, farkle_rate, comeback_king, hot_streak, consistency)
 * @param string $date The date in YYYY-MM-DD format
 * @param int $limit Maximum number of results (default 25)
 * @return array Array of associative arrays with playerid, username, stat_value, stat_detail
 */
function LeaderboardStats_GetTopForDate($statType, $date, $limit = 25) {
    $limit = intval($limit);

    // For farkle_rate and consistency, lower is better
    $orderDir = ($statType == 'farkle_rate' || $statType == 'consistency') ? 'ASC' : 'DESC';

    $sql = "
    SELECT s.playerid, p.username, s.stat_value, s.stat_detail
    FROM farkle_lb_stats s
    JOIN farkle_players p ON s.playerid = p.playerid
    WHERE s.stat_type = :stat_type AND s.lb_date = :lb_date
    ORDER BY s.stat_value $orderDir
    LIMIT " . $limit;

    $result = db_query($sql, [':stat_type' => $statType, ':lb_date' => $date], SQL_MULTI_ROW);
    return $result ?: [];
}
?>
