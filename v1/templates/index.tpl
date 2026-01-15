
{include file="header.tpl" title=Home}

<script type="text/javascript" src="/js/ajax.js"></script>
<script type="text/javascript" src="/js/geolocation.js"></script>
<script type="text/javascript" src="index.js"></script>

<style type="text/css">{literal}
	.mobile_button
	{
		background: white;
		border-radius: 8px;
		border: 1px solid black;
		height: 47px;
		font-size: 20px;
		font-weight: bold;
	}
{/literal}</style>

<script type="text/javascript">geo_start_location_watching( index_geo_success );</script>

<div id="divPing" align="center">
	<input class="mobile_button" type="button" onClick="Ping()" value="Mobile Button">
</div>

<div id="divPong" align="center" style="display: none;">
	<input class="mobile_button" type="button" onClick="Pong()" value="Pong">
</div>

<div align="center">
	<span id="lblInfo"></span>
</div>

{include file="footer.tpl"}