<?php
	if( isset($_SESSION['username']) )
	{
		$smarty->assign('my_username', $_SESSION['username'] );
		$smarty->assign('my_firstname', $_SESSION['firstname'] );
		$smarty->assign('my_lastname', $_SESSION['lastname'] );
	}
	else
	{
		// Redirect to login page. 
		//header( 'Location: login.php' );
	}
?>