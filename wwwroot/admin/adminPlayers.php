<?php
/**
 * Admin Players List Page
 *
 * Lists all players with search, pagination, and clickable player cards
 * sorted by last active (most recent first).
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

// Pagination settings
$perPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the SQL query
$whereClause = '';
if (!empty($search)) {
    $escapedSearch = db_escape_string($search);
    $whereClause = "WHERE username ILIKE '%{$escapedSearch}%'";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM farkle_players {$whereClause}";
$totalPlayers = db_select_query($countSql, SQL_SINGLE_VALUE);
$totalPages = ceil($totalPlayers / $perPage);

// Ensure page is within valid range
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Get players sorted by last active (most recent first)
$sql = "SELECT
            playerid,
            username,
            playerlevel,
            TO_CHAR(lastplayed, 'Mon DD, YYYY HH12:MI AM') as lastplayed_formatted,
            lastplayed
        FROM farkle_players
        {$whereClause}
        ORDER BY lastplayed DESC NULLS LAST
        LIMIT {$perPage} OFFSET {$offset}";

$players = db_select_query($sql, SQL_MULTI_ROW);

// Assign template variables
$smarty->assign('players', $players);
$smarty->assign('totalPlayers', $totalPlayers);
$smarty->assign('currentPage', $page);
$smarty->assign('totalPages', $totalPages);
$smarty->assign('perPage', $perPage);
$smarty->assign('search', htmlspecialchars($search));

// Display the template
$smarty->display('admin_players.tpl');
?>
