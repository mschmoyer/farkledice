
{*include file="../header.tpl" title="Farkle Reports"*}

<div style="background-color: white;">

	<table>
	<tr><td>Number of Players:</td><td>{$numPlayers}</td></tr>
	<tr><td>Games Played:</td><td>{$numGames}</td></tr>
	</table> 
	
	<table style="color: black;">
		<tr>
			<td><b>ID</b></td>
			<td><b>Player</b></td>
			<td><b>Last Played</b></td>
			<td><b>Played</b></td>
			<td><b>Started</b></td>			
		</tr>
	{foreach from=$players item=p}
		<tr>
			<td>{$p.playerid}</td>
			<td>{$p.name|truncate:20}</td>
			<td>{$p.lastplayedgame}</td>
			<td align="right">{$p.games}</td>
			<td align="right">{$p.games_started}</td>
		</tr>
	{/foreach}
	</table>
	<br/>
	<br/>
	<br/>
</div>

{*include file="../footer.tpl"*}