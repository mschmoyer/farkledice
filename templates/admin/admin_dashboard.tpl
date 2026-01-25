<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard | Farkle Ten</title>
    <link rel="icon" type="image/png" href="/images/site-icon-192.png">
    <link rel="stylesheet" type="text/css" href="/css/mobile.css?v={$app_version}" />
    <link rel="stylesheet" type="text/css" href="/css/farkle.css?v={$app_version}" />
    <style>
        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .metric-card {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #8c3310;
            text-align: center;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }

        .metric-value {
            font-size: 42px;
            font-weight: bold;
            color: #FFCC66;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
            line-height: 1.1;
        }

        .metric-label {
            font-size: 14px;
            color: #f0b7a1;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Section headers */
        .section-header {
            font-size: 18px;
            color: #f0b7a1;
            margin: 30px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #8c3310;
        }

        /* Highlight cards */
        .metric-card.highlight {
            border-color: #1a5a7a;
            background: rgba(26, 90, 122, 0.3);
        }

        .metric-card.highlight .metric-value {
            color: #7fcfff;
        }

        .metric-card.warning {
            border-color: #c9a227;
            background: rgba(201, 162, 39, 0.2);
        }

        .metric-card.warning .metric-value {
            color: #ffd700;
        }

        .admin-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 10px;
        }

        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .metric-value {
                font-size: 32px;
            }

            .metric-label {
                font-size: 12px;
            }
        }
    </style>
</head>
<body style="background-image:url('/images/greenfeltbg.png'); margin: 0px;">

<div align="center">
<div align="center" id="outerGlobalDiv">

    <img class="imagebutton homeimage mobileButton" alt="Back to Lobby"
        src="/images/home.png" onclick="window.location.href='/farkle.php'">

    <img alt="Farkle Ten" src="/images/farkle_ten_logo.png"
        class="titleImage" style="width: 213px; margin: -4px 0 15px -106px; top: 8px;">

    <img alt="Farkle Green Bar" src="/images/greentitlebar.png" class="titleBar">

    <div style="height: 50px;"></div>

    <div class="admin-container">
        <div style="margin-bottom: 20px;">
            <input type="button" class="mobileButton" value="Player Admin" onclick="window.location.href='adminPlayers.php'">
        </div>

        <h2 class="section-header">Today's Activity</h2>
        <div class="metrics-grid">
            <div class="metric-card highlight">
                <div class="metric-value">{$gamesToday|number_format:0:'':','}</div>
                <div class="metric-label">Games Today</div>
            </div>
            <div class="metric-card highlight">
                <div class="metric-value">{$playersToday|number_format:0:'':','}</div>
                <div class="metric-label">Players Today</div>
            </div>
        </div>

        <h2 class="section-header">Player Activity</h2>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value">{$playersWeek|number_format:0:'':','}</div>
                <div class="metric-label">Players This Week</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{$playersMonth|number_format:0:'':','}</div>
                <div class="metric-label">Players This Month</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{$totalPlayers|number_format:0:'':','}</div>
                <div class="metric-label">Total Players</div>
            </div>
        </div>

        <h2 class="section-header">Game Stats</h2>
        <div class="metrics-grid">
            <div class="metric-card warning">
                <div class="metric-value">{$unfinishedGames|number_format:0:'':','}</div>
                <div class="metric-label">Unfinished Games</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{$forfeitedGames|number_format:0:'':','}</div>
                <div class="metric-label">Forfeited Games</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{$botGames|number_format:0:'':','}</div>
                <div class="metric-label">Bot Games</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{$totalGames|number_format:0:'':','}</div>
                <div class="metric-label">Total Games</div>
            </div>
        </div>
    </div>

</div>
</div>
</body>
</html>
