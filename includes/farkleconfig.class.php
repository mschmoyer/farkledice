<?php

class FarkleConfig {
	public $data; 
	function __construct() {
		if( isset($_SESSION['FarkleConfig'] ) ) {
			$this->data = $_SESSION['FarkleConfig']; 
		} else {
			$this->data = parse_ini_file( "../configs/siteconfig.ini" ); 
			$_SESSION['FarkleConfig'] = $data; 
		}
		//var_dump($data); 
	}
}	

?>