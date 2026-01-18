<?php
/**
 * Admin Player Detail Page
 *
 * Shows comprehensive player information including all database fields,
 * achievements, game stats, and a 'Copy New Invite Link' button.
 *
 * Requires adminlevel > 0 to access.
 */

require_once('../../includes/baseutil.php');
require_once('dbutil.php');

BaseUtil_SessSet();

// Check admin access - redirect if not admin
if (!isset($_SESSION['adminlevel']) || $_SESSION['adminlevel'] <= 0) {
    header('Location: /farkle.php');
    exit();
}

// Get player ID from GET parameter
$playerid = isset($_GET['playerid']) ? intval($_GET['playerid']) : 0;

if ($playerid <= 0) {
    header('Location: adminPlayers.php');
    exit();
}

// Get all player data
$sql = "SELECT
            playerid,
            username,
            email,
            fullname,
            adminlevel,
            TO_CHAR(created_date, 'Mon DD, YYYY HH12:MI AM') as created_date_formatted,
            TO_CHAR(last_login, 'Mon DD, YYYY HH12:MI AM') as last_login_formatted,
            TO_CHAR(lastplayed, 'Mon DD, YYYY HH12:MI AM') as lastplayed_formatted,
            remoteaddr,
            playerlevel,
            playertitle,
            lobbyimage,
            xp,
            xp_to_level,
            wins,
            losses,
            games_played,
            cardcolor,
            cardbg,
            sendhourlyemails,
            random_selectable,
            totalpoints,
            highestround,
            highest10round,
            farkles,
            prestige,
            titlelevel,
            avgscorepoints,
            roundsplayed,
            rolls,
            stylepoints,
            active
        FROM farkle_players
        WHERE playerid = " . db_escape_string($playerid);

$player = db_select_query($sql, SQL_SINGLE_ROW);

if (!$player) {
    header('Location: adminPlayers.php');
    exit();
}

// Get player's achievements with achievement details
$achievementsSql = "SELECT
                        a.name,
                        a.description,
                        a.xp_reward,
                        TO_CHAR(ap.earned_date, 'Mon DD, YYYY') as earned_date_formatted
                    FROM farkle_achievements_players ap
                    JOIN farkle_achievements a ON a.achievementid = ap.achievementid
                    WHERE ap.playerid = " . db_escape_string($playerid) . "
                    ORDER BY ap.earned_date DESC";

$achievements = db_select_query($achievementsSql, SQL_MULTI_ROW);
if (!$achievements) {
    $achievements = [];
}

// Count total games from farkle_games_players
$gameCountSql = "SELECT COUNT(*) FROM farkle_games_players WHERE playerid = " . db_escape_string($playerid);
$totalGamesParticipated = db_select_query($gameCountSql, SQL_SINGLE_VALUE);

// Count completed games (where gamefinish is not null)
$completedGamesSql = "SELECT COUNT(DISTINCT gp.gameid)
                      FROM farkle_games_players gp
                      JOIN farkle_games g ON g.gameid = gp.gameid
                      WHERE gp.playerid = " . db_escape_string($playerid) . "
                      AND g.gamefinish IS NOT NULL";
$completedGames = db_select_query($completedGamesSql, SQL_SINGLE_VALUE);

// Count active games
$activeGamesSql = "SELECT COUNT(DISTINCT gp.gameid)
                   FROM farkle_games_players gp
                   JOIN farkle_games g ON g.gameid = gp.gameid
                   WHERE gp.playerid = " . db_escape_string($playerid) . "
                   AND g.gamefinish IS NULL";
$activeGames = db_select_query($activeGamesSql, SQL_SINGLE_VALUE);

// Assign template variables
$smarty->assign('player', $player);
$smarty->assign('achievements', $achievements);
$smarty->assign('achievementCount', count($achievements));
$smarty->assign('totalGamesParticipated', $totalGamesParticipated);
$smarty->assign('completedGames', $completedGames);
$smarty->assign('activeGames', $activeGames);

// Display the template
$smarty->display('admin/admin_player_detail.tpl');
?>
