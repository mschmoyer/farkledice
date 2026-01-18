<?php

class FarkleConfig {
	public $data;
	function __construct() {
		if( isset($_SESSION['FarkleConfig'] ) ) {
			$this->data = $_SESSION['FarkleConfig'];
		} else {
			// Priority 1: DATABASE_URL environment variable (Heroku)
			if (getenv('DATABASE_URL')) {
				$db = parse_url(getenv('DATABASE_URL'));
				$this->data = array();
				$this->data['dbhost'] = $db['host'];
				$this->data['dbport'] = isset($db['port']) ? $db['port'] : 5432;
				$this->data['dbuser'] = $db['user'];
				$this->data['dbpass'] = $db['pass'];
				$this->data['dbname'] = ltrim($db['path'], '/');
			}
			// Priority 2: Individual environment variables (Docker)
			else if (getenv('DB_HOST')) {
				$this->data = array();
				$this->data['dbhost'] = getenv('DB_HOST');
				$this->data['dbport'] = getenv('DB_PORT') ? getenv('DB_PORT') : 5432;
				$this->data['dbuser'] = getenv('DB_USER');
				$this->data['dbpass'] = getenv('DB_PASS');
				$this->data['dbname'] = getenv('DB_NAME');
			}
			// Priority 3: Config file (local development)
			else {
				$this->data = parse_ini_file( "../configs/siteconfig.ini" );
			}
			$_SESSION['FarkleConfig'] = $this->data;
		}
		//var_dump($data);
	}
}	

?>