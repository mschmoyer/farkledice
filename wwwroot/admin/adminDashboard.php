<?php
/**
 * Admin Dashboard
 *
 * Displays key metrics: games played, unique players, unfinished games,
 * forfeited games, and games against bots.
 *
 * Requires adminlevel > 0 to access.
 */

require_once('../../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleLogin.php');

Farkle_SessSet();

// Check admin access - redirect if not admin
if (!isset($_SESSION['adminlevel']) || $_SESSION['adminlevel'] <= 0) {
    header('Location: /farkle.php');
    exit();
}

// Games played today (started today)
$gamesToday = db_select_query("
    SELECT COUNT(*) FROM farkle_games
    WHERE DATE(gamestart) = CURRENT_DATE", SQL_SINGLE_VALUE);

// Unique players today
$playersToday = db_select_query("
    SELECT COUNT(DISTINCT playerid) FROM farkle_games_players gp
    JOIN farkle_games g ON g.gameid = gp.gameid
    WHERE DATE(gp.lastplayed) = CURRENT_DATE", SQL_SINGLE_VALUE);

// Unique players this week
$playersWeek = db_select_query("
    SELECT COUNT(DISTINCT playerid) FROM farkle_games_players gp
    JOIN farkle_games g ON g.gameid = gp.gameid
    WHERE gp.lastplayed >= NOW() - INTERVAL '7 days'", SQL_SINGLE_VALUE);

// Unique players this month
$playersMonth = db_select_query("
    SELECT COUNT(DISTINCT playerid) FROM farkle_games_players gp
    JOIN farkle_games g ON g.gameid = gp.gameid
    WHERE gp.lastplayed >= NOW() - INTERVAL '30 days'", SQL_SINGLE_VALUE);

// Unfinished games (no winner yet)
$unfinishedGames = db_select_query("
    SELECT COUNT(*) FROM farkle_games
    WHERE winningplayer = 0", SQL_SINGLE_VALUE);

// Forfeited games (where a player quit)
$forfeitedGames = db_select_query("
    SELECT COUNT(DISTINCT gameid) FROM farkle_games_players
    WHERE quit = 1", SQL_SINGLE_VALUE);

// Games against bots (games with at least one bot player)
$botGames = db_select_query("
    SELECT COUNT(DISTINCT g.gameid) FROM farkle_games g
    JOIN farkle_games_players gp ON g.gameid = gp.gameid
    JOIN farkle_players p ON gp.playerid = p.playerid
    WHERE p.personality_id IS NOT NULL", SQL_SINGLE_VALUE);

// Total registered players
$totalPlayers = db_select_query("
    SELECT COUNT(*) FROM farkle_players
    WHERE active = 1", SQL_SINGLE_VALUE);

// Total games all time
$totalGames = db_select_query("
    SELECT COUNT(*) FROM farkle_games", SQL_SINGLE_VALUE);

// Assign to Smarty
$smarty->assign('gamesToday', $gamesToday ?: 0);
$smarty->assign('playersToday', $playersToday ?: 0);
$smarty->assign('playersWeek', $playersWeek ?: 0);
$smarty->assign('playersMonth', $playersMonth ?: 0);
$smarty->assign('unfinishedGames', $unfinishedGames ?: 0);
$smarty->assign('forfeitedGames', $forfeitedGames ?: 0);
$smarty->assign('botGames', $botGames ?: 0);
$smarty->assign('totalPlayers', $totalPlayers ?: 0);
$smarty->assign('totalGames', $totalGames ?: 0);

$smarty->display('admin_dashboard.tpl');
