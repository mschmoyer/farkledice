<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Players | Farkle Ten</title>
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon"/>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .admin-title {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            margin: 0;
        }
        .admin-subtitle {
            color: #aaa;
            font-size: 14px;
            margin-top: 5px;
        }
        .back-link {
            color: #7db9e8;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        /* Search Form */
        .search-form {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .search-form form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            font-size: 16px;
            border: 1px solid #444;
            border-radius: 6px;
            background: #222;
            color: #fff;
        }
        .search-input:focus {
            outline: none;
            border-color: #7db9e8;
        }
        .search-button {
            padding: 10px 20px;
            font-size: 16px;
            background: #1a7a1a;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .search-button:hover {
            background: #228b22;
        }
        .clear-button {
            padding: 10px 20px;
            font-size: 16px;
            background: #555;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .clear-button:hover {
            background: #666;
        }

        /* Players Grid */
        .players-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .no-players {
            text-align: center;
            color: #aaa;
            padding: 40px;
            font-size: 18px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            background: rgba(0,0,0,0.3);
            border-radius: 4px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
        }
        .pagination a:hover {
            background: rgba(0,0,0,0.5);
        }
        .pagination .current {
            background: #1a7a1a;
            font-weight: bold;
        }
        .pagination .disabled {
            color: #666;
            cursor: not-allowed;
        }
        .page-info {
            color: #aaa;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Player Management</h1>
                <div class="admin-subtitle">Total Players: {$totalPlayers}</div>
            </div>
            <a href="/farkle.php" class="back-link">Back to Game</a>
        </div>

        {* Search Form *}
        <div class="search-form">
            <form method="get" action="adminPlayers.php">
                <input type="text"
                       name="search"
                       class="search-input"
                       placeholder="Search by username..."
                       value="{$search}">
                <button type="submit" class="search-button">Search</button>
                {if $search}
                    <a href="adminPlayers.php" class="clear-button">Clear</a>
                {/if}
            </form>
        </div>

        {* Players Grid *}
        {if $players && count($players) > 0}
            <div class="players-grid">
                {foreach from=$players item=player}
                    {include file="../farkle_playercard.tpl"
                        player_id=$player.playerid
                        player_username=$player.username
                        player_level=$player.playerlevel
                        player_lastactive=$player.lastplayed_formatted}
                {/foreach}
            </div>
        {else}
            <div class="no-players">
                {if $search}
                    No players found matching "{$search}"
                {else}
                    No players found
                {/if}
            </div>
        {/if}

        {* Pagination *}
        {if $totalPages > 1}
            <div class="pagination">
                {* Previous link *}
                {if $currentPage > 1}
                    <a href="adminPlayers.php?page={$currentPage - 1}{if $search}&search={$search|escape:'url'}{/if}">Previous</a>
                {else}
                    <span class="disabled">Previous</span>
                {/if}

                {* Page info *}
                <span class="page-info">Page {$currentPage} of {$totalPages}</span>

                {* Next link *}
                {if $currentPage < $totalPages}
                    <a href="adminPlayers.php?page={$currentPage + 1}{if $search}&search={$search|escape:'url'}{/if}">Next</a>
                {else}
                    <span class="disabled">Next</span>
                {/if}
            </div>
        {/if}
    </div>
</body>
</html>
