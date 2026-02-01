<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

	<title>{$title} | The exciting dice game of strategy and luck</title>
	<meta name="description" content="{$title} is a lightning fast online game of Farkle. Earn rewards, achievements, and compete for a top spot on the leaderboards!">

	<!-- Open Graph / Facebook / SMS Link Previews -->
	<meta property="og:type" content="website">
	<meta property="og:url" content="https://www.farkledice.com/">
	<meta property="og:title" content="{$title} - Free Online Dice Game">
	<meta property="og:description" content="Play Farkle online! A fast-paced dice game of strategy and luck. Challenge friends, earn achievements, and climb the leaderboards.">
	<meta property="og:image" content="https://www.farkledice.com/images/farkle-ten-hero.png">
	<meta property="og:image:width" content="1456">
	<meta property="og:image:height" content="816">
	<meta property="og:site_name" content="{$title}">

	<!-- Twitter Card -->
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="{$title} - Free Online Dice Game">
	<meta name="twitter:description" content="Play Farkle online! A fast-paced dice game of strategy and luck. Challenge friends and climb the leaderboards.">
	<meta name="twitter:image" content="https://www.farkledice.com/images/farkle-ten-hero.png">

	<!-- Theme Color for browser UI -->
	<meta name="theme-color" content="#2e7d32">
	<meta name="msapplication-TileColor" content="#2e7d32">

	<!-- iOS Web App Configuration -->
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="{$title}">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">

	<!-- Icons -->
	<link rel="icon" type="image/png" href="/images/site-icon-192.png">
	<link rel="icon" type="image/png" sizes="512x512" href="/images/site-icon-512.png">
	<link rel="apple-touch-icon" href="/images/site-icon-180.png">

	<!-- Web App Manifest -->
	<link rel="manifest" href="/manifest.json">

	<link rel="stylesheet" type="text/css" href="/css/mobile.css?v={$app_version}" />
	<link rel="stylesheet" type="text/css" href="/css/farkle.css?v={$app_version}" />
	<link rel="stylesheet" type="text/css" href="/css/lobby.css?v={$app_version}" />
	{*{if $mobilemode}<link rel="stylesheet" type="text/css" href="/css/mobile_farkle.css" />{/if}*}

	<script type="text/javascript" src="/js/bubble_util.js?v={$app_version}"></script>
	<script type="text/javascript" src="/js/farkle_bookmark_bubble.js?v={$app_version}"></script>
		
</head>

<body style="background-image:url('/images/greenfeltbg.png'); margin: 0px;" onorientationchange="updateOrientation();">  


<div align="center">
<div align="center" id="outerGlobalDiv">

	<img alt="Farkle Ten - Game of strategy and luck!" src="/images/farkle_ten_logo.png" 
		class="titleImage" style="width: 213px; margin: -4px 0 15px -106px; top: 8px;">

	<img alt="Farkle Green Bar" src="/images/greentitlebar.png" class="titleBar">

	<div style="height: 50px;">
	</div>	

