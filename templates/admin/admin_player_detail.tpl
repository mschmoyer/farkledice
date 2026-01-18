<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Player Detail | Farkle Ten</title>
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
        .back-link {
            color: #7db9e8;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        /* Player Header Card */
        .player-header {
            background: rgba(0,0,0,0.4);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .player-header-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .player-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2d5016 0%, #1a3009 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            color: #7CFC00;
            border: 3px solid #4a7c23;
        }
        .player-header-text h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
        }
        .player-header-text .player-id {
            color: #888;
            font-size: 14px;
        }
        .player-header-text .player-level {
            color: #7CFC00;
            font-size: 16px;
            margin-top: 5px;
        }
        .player-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .player-status.active {
            background: #1a7a1a;
            color: #7CFC00;
        }
        .player-status.inactive {
            background: #7a1a1a;
            color: #ff6b6b;
        }

        /* Copy Invite Link Button */
        .invite-btn {
            padding: 12px 24px;
            font-size: 16px;
            background: linear-gradient(135deg, #0066cc 0%, #004499 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .invite-btn:hover {
            background: linear-gradient(135deg, #0077ee 0%, #0055bb 100%);
            transform: translateY(-1px);
        }
        .invite-btn:active {
            transform: translateY(0);
        }
        .invite-btn .icon {
            font-size: 18px;
        }

        /* Info Sections */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-section {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 20px;
        }
        .info-section h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #7db9e8;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #aaa;
            font-size: 14px;
        }
        .info-value {
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }
        .info-value.highlight {
            color: #7CFC00;
        }
        .info-value.warning {
            color: #ffcc00;
        }
        .info-value.muted {
            color: #666;
        }

        /* Achievements Section */
        .achievements-section {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .achievements-section h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #7db9e8;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 10px;
        }
        .achievement-card {
            background: rgba(0,0,0,0.2);
            border-radius: 6px;
            padding: 12px;
            display: flex;
            flex-direction: column;
        }
        .achievement-name {
            font-weight: bold;
            color: #ffcc00;
            margin-bottom: 4px;
        }
        .achievement-desc {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 6px;
        }
        .achievement-meta {
            font-size: 11px;
            color: #666;
            display: flex;
            justify-content: space-between;
        }
        .achievement-xp {
            color: #7CFC00;
        }
        .no-achievements {
            color: #666;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        /* Admin Badge */
        .admin-badge {
            background: #cc6600;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .player-header {
                flex-direction: column;
                text-align: center;
            }
            .player-header-info {
                flex-direction: column;
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
            <a href="adminPlayers.php" class="back-link">&larr; Back to Player List</a>
            <a href="/farkle.php" class="back-link">Back to Game</a>
        </div>

        {* Player Header Card *}
        <div class="player-header">
            <div class="player-header-info">
                <div class="player-avatar">
                    {$player.username|substr:0:1|upper}
                </div>
                <div class="player-header-text">
                    <h1>
                        {$player.username|escape}
                        {if $player.adminlevel > 0}
                            <span class="admin-badge">Admin Level {$player.adminlevel}</span>
                        {/if}
                    </h1>
                    <div class="player-id">Player ID: {$player.playerid}</div>
                    <div class="player-level">Level {$player.playerlevel} {if $player.playertitle}- {$player.playertitle|escape}{/if}</div>
                    <span class="player-status {if $player.active}active{else}inactive{/if}">
                        {if $player.active}Active{else}Inactive{/if}
                    </span>
                </div>
            </div>
            <button type="button" id="copyInviteLinkBtn" class="invite-btn" data-playerid="{$player.playerid}">
                <span class="icon">&#128279;</span>
                Copy New Invite Link
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
                    <span class="info-label">Last Login</span>
                    <span class="info-value {if !$player.last_login_formatted}muted{/if}">{$player.last_login_formatted|default:'Never'}</span>
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
                    <span class="info-label">Player Title</span>
                    <span class="info-value {if !$player.playertitle}muted{/if}">{$player.playertitle|escape|default:'None'}</span>
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
                    <span class="info-value">
                        {if ($player.wins + $player.losses) > 0}
                            {($player.wins / ($player.wins + $player.losses) * 100)|string_format:"%.1f"}%
                        {else}
                            N/A
                        {/if}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Games Played (stored)</span>
                    <span class="info-value">{$player.games_played|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Games Participated</span>
                    <span class="info-value">{$totalGamesParticipated|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Completed Games</span>
                    <span class="info-value">{$completedGames|number_format}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Active Games</span>
                    <span class="info-value">{$activeGames|number_format}</span>
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
                    <span class="info-label">Lobby Image</span>
                    <span class="info-value {if !$player.lobbyimage}muted{/if}">{$player.lobbyimage|escape|default:'Default'}</span>
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
        btn.innerHTML = '<span class="icon">&#8987;</span> Generating...';

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
                navigator.clipboard.writeText(fullUrl).then(function() {
                    // Show success feedback
                    btn.innerHTML = '<span class="icon">&#10003;</span> Link Copied!';
                    btn.style.background = 'linear-gradient(135deg, #1a7a1a 0%, #0f4f0f 100%)';

                    // Reset button after 3 seconds
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                        btn.style.background = '';
                        btn.disabled = false;
                    }, 3000);
                }).catch(function(err) {
                    // Clipboard API failed, show URL in prompt as fallback
                    prompt('Copy this invite link:', fullUrl);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            } else {
                // Show error
                alert('Error: ' + (data.error || 'Failed to generate invite link'));
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(function(error) {
            // Network or parsing error
            alert('Error: Failed to generate invite link. Please try again.');
            console.error('Reinvite error:', error);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
    </script>
</body>
</html>
