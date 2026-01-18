
{include file="header.tpl" title="Farkle Ten"}

<img class="imagebutton homeimage mobileButton" id="imgHomeIcon" alt="Show Farkle Lobby"
	src="/images/home.png" onClick="ShowLobby()">


{include file="farkle_div_login.tpl"}

{include file="farkle_div_lobby.tpl"}

{include file="farkle_div_addfriend.tpl"}

{include file="farkle_div_playerinfo.tpl"}

{include file="farkle_div_leaderboard.tpl"}

{include file="farkle_div_newgame.tpl"}

{include file="farkle_div_game.tpl"}

{include file="farkle_div_instructions.tpl"}

{include file="farkle_div_tournament.tpl"}

{if isset($adminlevel) && $adminlevel > 0}
	{include file="farkle_div_admin.tpl"}
{/if}

{include file="footer.tpl"}

{* The Game Card object *}
<div id="defaultGameCard" class="gameCard mobilebutton" style="display: none;">


	<table width="100%" height="100%" cellpadding="0" cellspacing="1"> 

	<tr>	
		<td style="white-space: nowrap;" align="center">
			<div id="lblGameCardPlayerstring" class="gameCardNameText"></div>
		</td>
	</tr>
	
	<tr>
		<td align="center">
			<span id="lblGameCardInfo" class="gameCardInfoText">Game in progress...</span>
		</td>
	</tr>
	
	</table>
	<img id="gameCardImage" src="/images/trophy.png" style="float: right; margin: -28px 0 0 0; display: none;">
</div>

<!-- Farkle Alert -->
<div align="center">
	<div id="farkleAlert" class="farkleAlert loginBox shadow" feltcolor="red" style="display: none;">
		<p style="margin: -5px 0 10px 0;"><span id="farkleAlertMsg"></span></p>
		<div align="center" id="farkleAlertOkButton">
			<input  type="button" class="mobileButton" value="Ok" 
				onClick="document.getElementById('farkleAlert').style.display='none';" 
				style="width: 110px;">
		</div>
	</div>
</div>

{* Player Card Object *}
<div class="playerCard playerCardWidth" id="defaultPlayerCard" style="display: none;">
	<table width="100%" height="100%" cellpadding="0" cellspacing="0"> 
	<tr style="line-height: .7;">
		<td id="playerImgTd" rowspan="2" width="42px">
			<img id="playerImg" alt="Player icon" height="32px" width="32px">
		</td>
		<td><div class="playerName shadowed"></div></td>
		<td rowspan="2" align="right"><span class="playerAchScore shadowed"></span></td>
	</tr><tr>
		<td colspan="2"><span class="playerTitle shadowed"></span></td>
	</tr>
	</table>
	
</div>

{*
<!-- Farkle Confirm - Currently Unused -->
<div align="center">
	<div id="farkleConfirm" class="farkleAlert tabletop shadow" feltcolor="blue" style="display: none;">
		<p style="margin: -5px 0 10px 0;"><span id="farkleConfirmMsg"></span></p>
		<div align="center">
			<input type="button" class="mobileButton" value="Yes" buttoncolor="green" onClick="farklConfirmCallback(true)" style="width: 110px;">
			<input type="button" class="mobileButton" value="No" buttoncolor="red" onClick="farklConfirmCallback(false)" style="width: 110px;">
		</div>
	</div>
</div>
*}

<!-- Achievement Template --> 
<div id="divAchTemplate" style="display: none;">
	<table id="tabAchTable" width="100%">
		<tr>
			<td width="34px"><img id="imgShowAch" class="achImage" alt="Achievement icon" src=""></td>
			<td>
				<span id="lblAchTitle" style="font-size: 12px; font-weight: 900;"></span>
				<br/>
				<span id="lblAchDesc" style="font-size: 12px;"></span>
				<br/>
				<span id="lblAchEarned" style="font-size: 12px; color: #7db9e8;"></span>
			</td>
			<td align="right">
				<span id="lblAchPoints" class="shadowed" style="color: yellow; font-size: 20px;"></span>
			</td>
		</tr>
	</table>
</div>

<!-- The Achievement Box that pops up -->
<div id="divShowAchievement" class="achievement shadow" feltcolor="blue" style="display: none;">
	<span id="lblNewAchTitle" class="shadowed" style="font-color: yellow;">
		New Achievement!&nbsp;&nbsp;&nbsp;+20<img src="/images/xp.png" style="width: 22px; height: 18px; margin-top: 3px;">
	</span>
	<div id="divAchBoxContainer"></div>
</div>

<!-- The Level Box that pops up -->
<div id="divShowLevel" class="levelup" style="display: none;" align="center">
	<span id="lblLevelUpTitle" class="levelUpTitle shadowed">LEVEL UP!</span><br/>
	<span id="lblLevelUpReward" style="color: black;"></span>
</div>

<!-- The XP Box that pops up -->
<div id="divXP" class="shadow xpgain" feltcolor="blue">{* style="display: none;">*}
	<span id="divXPamt" style="float: left;"></span> <img src="/images/xp.png" style="float: right; " {if $mobilemode}width="40" height="26"{/if}>	
</div>
	
<!-- Various preloaded data pieces --> 
{if isset($username)}
	<input type="hidden" value="{$username}" id="dataUsername">
	<input type="hidden" value="{$playerid}" id="dataPlayerid">
	{*<input type="hidden" value="{$adminlevel}" id="dataAdminLevel">*}
{/if}
{if isset($resumegameid)}<input type="hidden" value="{$resumegameid}" id="dataResumegameid">{/if}

{if isset($lobbyInfo)}<input type="hidden" value='{$lobbyInfo}' id="m_lobbyInfo">{/if}
{if isset($friendInfo)}<input type="hidden" value='{$friendInfo}' id="m_friendInfo">{/if}
{if isset($lbInfo)}<input type="hidden" value='{$lbInfo}' id="m_lbInfo">{/if}
{if isset($pInfo)}<input type="hidden" value='{$pInfo}' id="m_pInfo">{/if}
{if isset($lastknownscreen)}<input type="hidden" value='{$lastknownscreen}' id="m_lastknownscreen">{/if}
{if isset($lastplayerinfoid)}<input type="hidden" value='{$lastplayerinfoid}' id="m_lastplayerinfoid">{/if}
{if isset($double_xp)}<input type="hidden" value='{$double_xp}' id="m_doublexp">{/if}
<!-- End preloaded data --> 
	
<!-- Javascript -->


<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script type="text/javascript" src="/js/util.js?vers=1.1"></script>
<script type="text/javascript" src="/js/ajax.js?vers=1.0"></script>
<script type="text/javascript" src="/js/md5.js?vers=1.0"></script>

<!-- Used for the Farkle-meter 
<script type="text/javascript" src="/js/raphael.2.1.0.min.js?vers=1.0"></script>
<script type="text/javascript" src="/js/justgage.1.0.1.min.js?vers=1.0"></script>-->

<script type="text/javascript" src="/js/farkleLogin.js?vers=1.1"></script>
<script type="text/javascript" src="/js/farkleGame.js?vers=1.2"></script>
<script type="text/javascript" src="/js/farklePage.js?vers=1.5"></script>
<script type="text/javascript" src="/js/farkleLobby.js?vers=1.1"></script>
<script type="text/javascript" src="/js/farkleFriends.js?vers=1.0"></script>

<script type="text/javascript" src="/js/farkleGameLogic.js?vers=1.1"></script>

<script type="text/javascript" src="/js/farkleTournament.js?vers=1.0"></script>
<script type="text/javascript" src="/js/farklePlayerInfo.js?vers=1.1"></script>
<script type="text/javascript" src="/js/farkleLeaderboard.js?vers=1.1"></script>
<script type="text/javascript" src="/js/farkleAdmin.js?vers=1.0"></script>

<script>{literal}
$(document).ready(function()
{
	// The DOM (document object model) is constructed
	// We will initialize and run our plugin here	
	pageInit( {/literal}'{$lobbyinfo}', '{$friendinfo}', '{$lbinfo}', '{$pinfo}'{literal} );
});
{/literal}</script>

