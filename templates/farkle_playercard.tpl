{*
    Reusable PlayerCard Component

    Required variables:
        $player_id       - Player's ID (for link)
        $player_username - Player's username to display
        $player_level    - Player's level
        $player_lastactive - Last active timestamp (can be formatted or raw)

    Optional variables:
        $player_link_url - Custom URL (defaults to adminPlayerDetail.php?playerid={$player_id})
        $player_clickable - Set to false to disable clicking (default: true)
*}

<div class="playercard-component"
     style="display: inline-block;
            background: linear-gradient(135deg, rgba(0,50,100,0.8) 0%, rgba(0,30,60,0.9) 100%);
            border-radius: 8px;
            border: 1px solid #333;
            padding: 10px 15px;
            margin: 5px;
            min-width: 200px;
            max-width: 280px;
            cursor: {if !isset($player_clickable) || $player_clickable !== false}pointer{else}default{/if};
            transition: transform 0.15s ease, box-shadow 0.15s ease;"
     {if !isset($player_clickable) || $player_clickable !== false}
     onclick="window.location.href='{if isset($player_link_url)}{$player_link_url}{else}adminPlayerDetail.php?playerid={$player_id}{/if}'"
     onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.3)';"
     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';"
     {/if}>

    {* Username - prominent display *}
    <div style="font-size: 16px;
                font-weight: bold;
                color: #fff;
                margin-bottom: 4px;
                text-shadow: 1px 1px 2px #000;">
        {$player_username|escape:'html'}
    </div>

    {* Level and Last Active - secondary info *}
    <div style="display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 12px;">

        {* Level badge *}
        <span class="playerCardLevel"
              style="background-color: #1a7a1a;
                     color: #ffff00;
                     padding: 2px 6px;
                     border-radius: 4px;
                     border: 1px solid #000;
                     font-weight: bold;">
            Lvl {$player_level|default:1}
        </span>

        {* Last active timestamp *}
        <span style="color: #aaa;
                     font-size: 11px;">
            {if isset($player_lastactive) && $player_lastactive}
                {$player_lastactive}
            {else}
                Never
            {/if}
        </span>
    </div>
</div>
