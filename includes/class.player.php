<?php

class Player {
	
	private $data = array();	
	
	public __construct() {

	}
	
	// $p1 = Player::withRow( $data[1] ); 
	function withRow( $rowData ) {
		// Assumed to be a row similar to the one in the query below (but in a query fetching multiple players)
		$instance = new self(); 
		$this->data = $rowData; 
		return $instance; 
	}
	
	// $p2 = Player::withID( 102 ); 
	function withID( $playerId ) {
		$instance = new self();
		// Total points
		$sql = "select
			username, email, sendhourlyemails, random_selectable, playerid, playertitle, cardcolor,
			(select sum(worth) 
				from farkle_achievements a, farkle_achievements_players b 
				where a.achievementid=b.achievementid and b.playerid='$playerid') as achscore,
			FORMAT(totalpoints,0) as totalpoints, 
			FORMAT(highestround,0) as highestround,
			TO_CHAR(lastplayed,'Mon DD') as lastplayed,
			FORMAT(COALESCE(avgscorepoints / roundsplayed,0),0) as avground,
			wins, 
			losses,		
			COALESCE(($friendSql),0) as isfriend,
			FORMAT(xp,0) as xp, 
			FORMAT(xp_to_level,0) as xp_to_level,
			FORMAT(stylepoints,0) as stylepoints, 
			playerlevel,
			FORMAT(highest10round,0) as highest10round, 
			FORMAT(farkles,0) as farkles
			from farkle_players where playerid='$playerid'";
		$queryData = db_select_query( $sql, SQL_SINGLE_ROW );
		
		if( $queryData ) {
			if( empty($stats['avground']) ) $stats['avground'] = '0';
			$this->data = $queryData; 
		}
		//GetGames( $playerid, 1, 30, 1), 
		//Player_GetTitleChoices( $stats['playerlevel'] ) );
		return $instance; 
	}
	
	public function __get($member) {
		if (isset($this->data[$member])) {
			return $this->data[$member];
		}
	}

	// $currentPlayer = $p1->playerid; 
	public function __set($member, $value) {
		// The ID of the dataset is read-only
		if ($member == "playerid") {
			return;
		}
		if (isset($this->data[$member])) {
			$this->data[$member] = $value;
		}
	}
	
	/*
		Function: 	IsFriend
		Purpose:	Returns whether the given player ID is a friend of this player 
		Params: 	$playerid = the friend
		Returns: 	TRUE if friends, FALSE otherwise. 
	*/
	public IsFriend( static $playerid ) {
		$results = 0; 
		$sql = "select friendid from farkle_friends where sourceid=$m_playerid and friendid=$playerid"; 
		$results = db_select_query( $sql, SQL_SINGLE_VALUE );
		return ($results>0); 
	}
}


?>