<?php

	require('smarty_connect.php');
	
	if( !empty($_REQUEST['pg']) )
		$page = $_REQUEST['pg'];
	
	$smarty->assign('pg', $_REQUEST['pg'] );
	$smarty->display($page.'.tpl');
?>
