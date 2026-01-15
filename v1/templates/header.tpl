<!--<!DOCTYPE html>-->
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<meta name="Description" content="{$title} is a lightning fast online game of Farkle. Earn rewards, achievements, and compete for a top spot on the leaderboards!">
	
	<meta property="og:url" content="http://www.farkledice.com"/>    
	<meta property="og:image" content="http://www.farkledice.com/images/fb-icon.png"/>
    <meta property="og:title" content="{$title}"/>
    <meta property="og:type" content="game"/>   
    <meta property="og:site_name" content="{$title}"/>
    <meta property="fb:admins" content="9202698"/>
    <meta property="og:description" content="{$title} is a lightning fast online game of Farkle. Earn rewards, achievements, and compete for a top spot on the leaderboards!"/>
	<meta property="fb:app_id" content="271148502945493"/>
	
	{if $mobilemode || $tabletmode}
		<meta name="apple-mobile-web-app-capable" content="yes" />
		
		{*{if $mobilemode}
			<meta name="viewport" content="width=device-width, user-scalable=0"> <!-- user-scalable=no;"/> -->
		{else}
			<meta name="viewport" content="width=device-width, initial-scale=1.5, maximum-scale=2, minimum-scale=0.9, user-scalable=0">
		{/if}*}
		<meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<!--<meta name="viewport" content="width=240, height=320, user-scalable=yes,initial-scale=1.0, maximum-scale=5.0, minimum-scale=1.0" />-->
		<link rel="stylesheet" type="text/css" href="/css/mobile.css?modified=18-dec-2012" />
		
		<meta name="apple-mobile-web-app-title" content="{$title}">
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/images/apple-touch-icon-114x114-precomposed.png" />
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/images/apple-touch-icon-72x72-precomposed.png" />
		<link rel="apple-touch-icon-precomposed" href="/images/apple-touch-icon-57x57-precomposed.png" />
		<link rel="apple-touch-icon" href="/images/apple-touch-icon.png" />
		
		<!-- iPhone -->
		<link href="/images/apple-startup-iPhone.png" media="(device-width: 320px) and (device-height: 480px) and (-webkit-device-pixel-ratio: 1)" rel="apple-touch-startup-image">

		<!-- iPhone (Retina) -->
		<link href="/images/apple-startup-iPhone-RETINA.png" media="(device-width: 320px) and (device-height: 480px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">

		{*
		<!-- iPhone 5 -->
		<link href="http://www.example.com/mobile/images/apple-startup-iPhone-Tall-RETINA.png"  media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">

		<!-- iPad Portrait -->
		<link href="http://www.example.com/mobile/images/apple-startup-iPad-Portrait.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: portrait) and (-webkit-device-pixel-ratio: 1)" rel="apple-touch-startup-image">

		<!-- iPad Landscape -->
		<link href="http://www.example.com/mobile/images/apple-startup-iPad-Landscape.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: landscape) and (-webkit-device-pixel-ratio: 1)" rel="apple-touch-startup-image">

		<!-- iPad Portrait (Retina) -->
		<link href="http://www.example.com/mobile/images/apple-startup-iPad-RETINA-Portrait.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: portrait) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">

		<!-- iPad Landscape (Retina) -->
		<link href="http://www.example.com/mobile/images/apple-startup-iPad-RETINA-Landscape.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: landscape) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
		*}		
	{/if}

	
	<title>{$title} | The exciting dice game of strategy and luck</title>
	
	<link rel="icon" href="/images/favicon.ico" type="image/x-icon"/>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
	
	{literal}
	<script type="text/javascript">

		//window.applicationCache.swapCache();
	
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-28314970-1']);
	  _gaq.push(['_trackPageview']);

	  (function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>
	{/literal}
	
	<link rel="stylesheet" type="text/css" href="/css/mobile.css?vers=1.1" />
	<link rel="stylesheet" type="text/css" href="/css/farkle.css?vers=1.1" />
	{*{if $mobilemode}<link rel="stylesheet" type="text/css" href="/css/mobile_farkle.css" />{/if}*}
		
	<script type="text/javascript" src="/js/bubble_util.js"></script>
	<script type="text/javascript" src="/js/farkle_bookmark_bubble.js"></script>
		
</head>

<body style="background-image:url('/images/greenfeltbg.png'); margin: 0px;" onorientationchange="updateOrientation();">  


<div align="center">
<div align="center" id="outerGlobalDiv">

	<img alt="Farkle Ten - Game of strategy and luck!" src="/images/farkle_ten_logo.png" 
		class="titleImage" style="width: 213px; margin: -4px 0 15px -106px; top: 8px;">

	<img alt="Farkle Green Bar" src="/images/greentitlebar.png" class="titleBar">

	<div style="height: 50px;">
	</div>	

