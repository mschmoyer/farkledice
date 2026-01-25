<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Players | Farkle Ten</title>
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon"/>
    <link rel="stylesheet" type="text/css" href="/css/mobile.css?v={$app_version}" />
    <link rel="stylesheet" type="text/css" href="/css/farkle.css?v={$app_version}" />
    <style>
        body {
            font-family: Verdana, Lucida Grande, sans-serif;
            background-image: url('/images/greenfeltbg.png');
            background-repeat: repeat;
            color: white;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 2px solid #8c3310;
            padding-bottom: 15px;
        }

        .admin-title {
            font-size: 28px;
            font-weight: bold;
            color: #f0b7a1;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .admin-subtitle {
            color: #ddd;
            font-size: 14px;
            margin-top: 5px;
        }

        .back-link {
            color: #f0b7a1;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
            color: #fff;
        }

        /* Search Form */
        .search-form {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #8c3310;
        }

        .search-form form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            font-size: 16px;
            border: 1px solid #666;
            border-radius: 6px;
            background: #222;
            color: #fff;
        }

        .search-input:focus {
            outline: none;
            border-color: #f0b7a1;
        }

        /* Player Cards Grid */
        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .player-card {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 4px;
            border: 1px solid black;
            padding: 8px 10px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .player-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }

        .player-card-avatar {
            width: 42px;
            height: 42px;
            border-radius: 4px;
            background: linear-gradient(135deg, #8c3310, #f0b7a1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
            border: 1px solid black;
            flex-shrink: 0;
        }

        .player-card-info {
            flex: 1;
            min-width: 0;
        }

        .player-card-name {
            font-size: 16px;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }

        .player-card-meta {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
        }

        .player-card-level {
            background-color: #1a7a1a;
            color: #ffff00;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid black;
            font-weight: bold;
            white-space: nowrap;
        }

        .player-card-title {
            color: #aaa;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-card-stats {
            text-align: right;
            flex-shrink: 0;
        }

        .player-card-xp {
            font-size: 20px;
            color: #FFCC66;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .player-card-lastplayed {
            font-size: 11px;
            color: #aaa;
        }

        .admin-badge-small {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #8c3310;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
        }

        .pagination a {
            color: #f0b7a1;
            text-decoration: none;
            font-weight: bold;
        }

        .pagination a:hover {
            text-decoration: underline;
            color: #fff;
        }

        .pagination .disabled {
            color: #666;
            cursor: not-allowed;
        }

        .page-info {
            color: #ddd;
        }

        .no-players {
            text-align: center;
            padding: 40px;
            color: #aaa;
            font-style: italic;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .players-grid {
                grid-template-columns: 1fr;
            }

            .player-card {
                height: auto;
                min-height: 60px;
            }
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
            <a href="/farkle.php" class="back-link">&larr; Back to Lobby</a>
        </div>

        <div class="search-form">
            <form method="get" action="adminPlayers.php">
                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search by username, email, or player ID..."
                    value="{if $search}{$search|escape:'html'}{/if}">
                <input type="submit" class="mobileButton" value="Search">
                {if $search}
                    <a href="adminPlayers.php" class="mobileButton" style="display: inline-block; text-decoration: none;">Clear</a>
                {/if}
            </form>
        </div>

        {if $players && count($players) > 0}
        <div class="players-grid">
            {foreach $players as $player}
            <div class="player-card shadowed"
                 onclick="window.location.href='adminPlayerDetail.php?playerid={$player.playerid}'"
                 style="background-image: url('/images/{if $player.cardbg && $player.cardbg != ''}{$player.cardbg}{else}{$player.cardcolor|default:'green'}feltbg.png{/if}');">

                {if $player.adminlevel > 0}
                    <span class="admin-badge-small">Admin</span>
                {/if}

                <div class="player-card-avatar">
                    {$player.username|substr:0:1|upper}
                </div>

                <div class="player-card-info">
                    <div class="player-card-name">
                        {$player.username|escape:'html'}
                    </div>
                    <div class="player-card-meta">
                        <span class="player-card-level">Lvl {$player.playerlevel}</span>
                        {if $player.playertitle}
                            <span class="player-card-title">{$player.playertitle|escape:'html'}</span>
                        {/if}
                    </div>
                </div>

                <div class="player-card-stats">
                    <div class="player-card-xp">{$player.xp|number_format:0:'':','}</div>
                    <div class="player-card-lastplayed">
                        {if $player.lastplayed}
                            {$player.lastplayed|date_format:"%b %d"}
                        {else}
                            Never
                        {/if}
                    </div>
                </div>
            </div>
            {/foreach}
        </div>
        {else}
        <div class="no-players">
            {if $search}
                No players found matching "{$search|escape:'html'}"
            {else}
                No players found
            {/if}
        </div>
        {/if}

        {if $totalPages > 1}
        <div class="pagination">
            {if $currentPage > 1}
                <a href="adminPlayers.php?page={$currentPage - 1}{if $search}&search={$search|escape:'url'}{/if}">Previous</a>
            {else}
                <span class="disabled">Previous</span>
            {/if}

            <span class="page-info">Page {$currentPage} of {$totalPages}</span>

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
