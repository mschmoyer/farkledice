<?php
require_once('../includes/baseutil.php');
require_once('dbutil.php');
require_once('farkleGameFuncs.php');

class FarkleGameInfo {
	public $gameid;
	public $mintostart;
	public $currentturn;
	public $currentround;
	public $maxturns;
	public $winningplayer;
	public $gamestart;
	public $pointstowin;
	public $gamefinish;
	public $lastturn;
	public $titleredeemed;
	public $whostarted;
	public $playerarray;
	public $gamemode;
	
	public $gDataArr;
	public $players = Array();

	function __construct( $mGameId ) {
		$sql = "select * from farkle_games where gameid=$mGameId";
		$gd = db_select_query( $sql, SQL_SINGLE_ROW );
		
		$this->gameid = $mGameId;
		$this->mintostart = $gd['mintostart'];
		$this->currentturn = $gd['currentturn'];
		$this->currentround = $gd['currentround'];
		$this->maxturns = $gd['maxturns'];
		$this->winningplayer = $gd['winningplayer'];
		$this->gamestart = $gd['gamestart'];
		$this->pointstowin = $gd['pointstowin'];
		$this->gamefinish = $gd['gamefinish'];
		$this->lastturn = $gd['lastturn'];
		$this->titleredeemed = $gd['titleredeemed'];
		$this->whostarted = $gd['whostarted'];
		$this->playerarray = $gd['playerarray'];
		$this->gamemode = $gd['gamemode'];
		$this->gDataArr = $gd;
	}
	
	// This is an associative array based on PlayerId
	function GetPlayerData() {
		$sql = "select a.*, b.username, b.fullname, b.email
			from farkle_games_players a, farkle_players b
			where gameid=$this->gameid and a.playerid=b.playerid";
		$pd = db_select_query( $sql, SQL_MULTI_ROW );
		
		$theArray = Array();
		foreach( $pd as $p )
		{
			$newItem = array( $p['playerid'] => $p );
			echo "<br>";
			var_dump($theArray);
			if( empty($theArray) ) 
				$theArray = $newItem;
			else
				$theArray = array_merge( $theArray, (array)$newItem );
		}
		$this->players = $theArray;
	}
	
	
	
	function UpdatePlayer( $playerId ) {
	
	}
	
	function DumpGameInfo() {
		foreach( $this->gDataArr as $k => $v )
			echo "$k = $v<br>";
	}
	
	function DumpPlayerInfo() {
		if( isset($this->players) ) {
			foreach( $this->players as $p ) {
				echo "<br>";
				foreach( $p as $k => $v )
				{
					echo "$k = $v<br>";
				}
			}
		}
	}
}
	
	

?>