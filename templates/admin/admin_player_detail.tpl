<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - {$player.username|escape} | Farkle Ten</title>
    <link rel="icon" type="image/png" href="/images/site-icon-192.png">
    <link rel="stylesheet" type="text/css" href="/css/mobile.css?v={$app_version}" />
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
        }

        .admin-header {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            border-bottom: 2px solid #8c3310;
        }

        /* Player Card Container */
        .player-card-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        /* Reuse the playerCard style from main site */
        .detail-player-card {
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
            width: 100%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .detail-player-card-avatar {
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

        .detail-player-card-info {
            flex: 1;
            min-width: 0;
        }

        .detail-player-card-name {
            font-size: 16px;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
            margin-bottom: 2px;
        }

        .detail-player-card-meta {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
        }

        .detail-player-card-level {
            background-color: #1a7a1a;
            color: #ffff00;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid black;
            font-weight: bold;
            white-space: nowrap;
        }

        .detail-player-card-title {
            color: #aaa;
            font-size: 14px;
        }

        .detail-player-card-stats {
            text-align: right;
            flex-shrink: 0;
        }

        .detail-player-card-xp {
            font-size: 20px;
            color: #FFCC66;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .detail-player-card-id {
            font-size: 11px;
            color: #aaa;
        }

        .admin-badge-corner {
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

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-section {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #8c3310;
        }

        .info-section h2 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #8c3310;
            color: #f0b7a1;
            font-size: 18px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(140, 51, 16, 0.3);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #aaa;
            font-size: 14px;
        }

        .info-value {
            color: white;
            font-weight: bold;
            font-size: 14px;
            text-align: right;
        }

        .info-value.highlight {
            color: #f0b7a1;
        }

        .info-value.warning {
            color: #ff6b6b;
        }

        .info-value.muted {
            color: #666;
            font-style: italic;
            font-weight: normal;
        }

        /* Achievements */
        .achievements-section {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #8c3310;
        }

        .achievements-section h2 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #8c3310;
            color: #f0b7a1;
            font-size: 18px;
        }

        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .achievement-card {
            background: rgba(140, 51, 16, 0.3);
            border-radius: 6px;
            padding: 15px;
            border: 1px solid rgba(240, 183, 161, 0.3);
        }

        .achievement-name {
            font-weight: bold;
            color: #f0b7a1;
            margin-bottom: 5px;
        }

        .achievement-desc {
            color: #ddd;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .achievement-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #aaa;
        }

        .achievement-xp {
            color: #80a82b;
            font-weight: bold;
        }

        .no-achievements {
            text-align: center;
            color: #666;
            padding: 40px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .player-header-info {
                flex-direction: column;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <a href="adminPlayers.php" class="mobileButton" style="text-decoration: none;">Back</a>
            <a href="/farkle.php" class="mobileButton" style="text-decoration: none;">Lobby</a>
        </div>

        {* Player Card *}
        <div class="player-card-container">
            <div class="detail-player-card shadowed"
                 style="background-image: url('/images/{if $player.cardbg && $player.cardbg != ''}{$player.cardbg}{else}{$player.cardcolor|default:'green'}feltbg.png{/if}');">

                {if $player.adminlevel > 0}
                    <span class="admin-badge-corner">Admin {$player.adminlevel}</span>
                {/if}

                <div class="detail-player-card-avatar">
                    {$player.username|substr:0:1|upper}
                </div>

                <div class="detail-player-card-info">
                    <div class="detail-player-card-name">
                        {$player.username|escape}
                    </div>
                    <div class="detail-player-card-meta">
                        <span class="detail-player-card-level">Lvl {$player.playerlevel}</span>
                        {if $player.playertitle}
                            <span class="detail-player-card-title">{$player.playertitle|escape}</span>
                        {/if}
                    </div>
                </div>

                <div class="detail-player-card-stats">
                    <div class="detail-player-card-xp">{$player.xp|number_format:0:'':','}</div>
                    <div class="detail-player-card-id">ID: {$player.playerid}</div>
                </div>
            </div>

            <button type="button" id="copyInviteLinkBtn" class="mobileButton" data-playerid="{$player.playerid}">
                Copy Invite Link
            </button>
        </div>

        {* Info Sections Grid *}
        <div class="info-grid">
            {* Account Information *}
            <div class="info-section">
                <h2>Account Information</h2>
                <div class="info-row">
                    <span class="info-label">Username</span>
                    <span class="info-value">{$player.username|escape}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value {if !$player.email}muted{/if}">{$player.email|escape|default:'Not set'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value {if !$player.fullname}muted{/if}">{$player.fullname|escape|default:'Not set'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created</span>
                    <span class="info-value">{$player.created_date_formatted|default:'Unknown'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Played</span>
                    <span class="info-value {if !$player.lastplayed_formatted}muted{/if}">{$player.lastplayed_formatted|default:'Never'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last IP Address</span>
                    <span class="info-value {if !$player.remoteaddr}muted{/if}">{$player.remoteaddr|escape|default:'Unknown'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Admin Level</span>
                    <span class="info-value {if $player.adminlevel > 0}warning{/if}">{$player.adminlevel}</span>
                </div>
            </div>

            {* Level & XP *}
            <div class="info-section">
                <h2>Level &amp; Experience</h2>
                <div class="info-row">
                    <span class="info-label">Player Level</span>
                    <span class="info-value highlight">{$player.playerlevel}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current XP</span>
                    <span class="info-value">{$player.xp|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">XP to Next Level</span>
                    <span class="info-value">{$player.xp_to_level|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Prestige</span>
                    <span class="info-value {if $player.prestige > 0}highlight{/if}">{$player.prestige}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Title Level</span>
                    <span class="info-value">{$player.titlelevel}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Style Points</span>
                    <span class="info-value">{$player.stylepoints|number_format}</span>
                </div>
            </div>

            {* Game Statistics *}
            <div class="info-section">
                <h2>Game Statistics</h2>
                <div class="info-row">
                    <span class="info-label">Wins</span>
                    <span class="info-value highlight">{$player.wins|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Losses</span>
                    <span class="info-value">{$player.losses|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Win Rate</span>
                    <span class="info-value">{$player.win_rate}%</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Games</span>
                    <span class="info-value">{$player.total_games|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rounds Played</span>
                    <span class="info-value">{$player.roundsplayed|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Rolls</span>
                    <span class="info-value">{$player.rolls|number_format}</span>
                </div>
            </div>

            {* Scoring Stats *}
            <div class="info-section">
                <h2>Scoring Statistics</h2>
                <div class="info-row">
                    <span class="info-label">Total Points</span>
                    <span class="info-value">{$player.totalpoints|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Avg Score/Round</span>
                    <span class="info-value">{$player.avgscorepoints|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Highest Round (Standard)</span>
                    <span class="info-value highlight">{$player.highestround|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Highest Round (10-Round)</span>
                    <span class="info-value">{$player.highest10round|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Farkles</span>
                    <span class="info-value">{$player.farkles|number_format}</span>
                </div>
            </div>

            {* Settings & Preferences *}
            <div class="info-section">
                <h2>Settings &amp; Preferences</h2>
                <div class="info-row">
                    <span class="info-label">Card Color</span>
                    <span class="info-value">{$player.cardcolor|escape|default:'green'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Card Background</span>
                    <span class="info-value {if !$player.cardbg}muted{/if}">{$player.cardbg|escape|default:'Default'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Hourly Emails</span>
                    <span class="info-value">{if $player.sendhourlyemails}Enabled{else}Disabled{/if}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Random Matchmaking</span>
                    <span class="info-value">{if $player.random_selectable}Enabled{else}Disabled{/if}</span>
                </div>
            </div>
        </div>

        {* Achievements Section *}
        <div class="achievements-section">
            <h2>Achievements ({$achievementCount})</h2>
            {if $achievements && count($achievements) > 0}
                <div class="achievements-grid">
                    {foreach from=$achievements item=achievement}
                        <div class="achievement-card">
                            <div class="achievement-name">{$achievement.name|escape}</div>
                            <div class="achievement-desc">{$achievement.description|escape}</div>
                            <div class="achievement-meta">
                                <span class="achievement-xp">+{$achievement.xp_reward} XP</span>
                                <span>Earned: {$achievement.earned_date_formatted}</span>
                            </div>
                        </div>
                    {/foreach}
                </div>
            {else}
                <div class="no-achievements">No achievements earned yet.</div>
            {/if}
        </div>
    </div>

    <script>
    document.getElementById('copyInviteLinkBtn').addEventListener('click', function() {
        var btn = this;
        var playerid = btn.getAttribute('data-playerid');
        var originalText = btn.innerHTML;

        // Disable button while processing
        btn.disabled = true;
        btn.innerHTML = 'Generating...';

        // Make AJAX POST request to generate reinvite token
        fetch('adminGenerateReinvite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'playerid=' + encodeURIComponent(playerid)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Build full URL for clipboard (use current origin)
                var fullUrl = window.location.origin + data.url;

                // Copy to clipboard
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(fullUrl).then(function() {
                        btn.innerHTML = '✓ Copied!';
                        setTimeout(function() {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }, 2000);
                    }).catch(function(err) {
                        console.error('Failed to copy:', err);
                        alert('Link copied to clipboard (fallback): ' + fullUrl);
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
                } else {
                    // Fallback for older browsers
                    alert('Copy this link: ' + fullUrl);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } else {
                alert('Error: ' + (data.error || 'Failed to generate invite link'));
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(function(error) {
            console.error('Request failed:', error);
            alert('Failed to generate invite link. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
    </script>
</body>
</html>
