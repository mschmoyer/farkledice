<?php
	/*
		farkleDiceScoring.php
		Desc: This file contains the function that calculates score for dice values. 
	
		Changelog: 
		13-Jan-2012		mas		Fixed code not awarding six-of-a-kind with one's. 
	*/
	
	/*if( isset($_GET['test']) )
	{
		require_once('farklePageFuncs.php'); 
		$loginSucceeded = Farkle_SessSet();
		
		if( $_SESSION['adminlevel'] > 0 )
		{		
			require_once('../includes/baseutil.php');
			require_once('dbutil.php');
			require_once('farkleAchievements.php');
			$g_debug = 14;
			
			if( $_GET['test'] == 'score' )
			{
				$savedDice = array();
				$dice = $_GET['dice'];
				for( $i=0; $i<6; $i++ )
					array_push( $savedDice, substr($dice, $i, 1) );
				
				echo "Test result: " . farkleScoreDice( $savedDice, $_SESSION['playerid'] );
			}
		}
		else
		{
			header('farkle.php');
		}
	}*/
	
	function farkleScoreDice( $savedDice, $playerid )
	{
		$scoreValue = 0;
		$numSingleMatches = 0;
		$threePair = 0; 
		$twoTriplets = 0;
		$invalidSave = 0;
		$prevThreePairMatches = 0;
		$forceThreePair = 0;
		
		if( empty($savedDice) || !is_array($savedDice) ) 
		{
			BaseUtil_Error( "Invalid parameter passed to farkleScoreDice.");
			return 0;
		}
		
		BaseUtil_Debug( "Scoring Dice: " . implode( ', ', $savedDice ), 7);
		
		for( $i=0; $i<count($savedDice); $i++ )
		{
			if( $savedDice[$i] < 1 || $savedDice[$i] > 6 ) continue; // Skip zeros...these are not saved and thus don't count
		
			$number = $savedDice[$i];			
			$matches = 0;
			for( $j=0; $j<count($savedDice); $j++ )
			{
				if( $number == $savedDice[$j] )
				{
					$matches++;
					$savedDice[$j] = 0;
				}
			}
			
			if( $matches == 1 ) $numSingleMatches++; // If this gets to 6 we have a straight
		
			if( $number == 1 )
			{
				if( $matches < 3 )
				{
					$scoreValue += $matches * 100;
					BaseUtil_Debug( "Matched $matches 'ones'. Score=$scoreValue", 7);
				}
				else
				{
					// 1000 for 3 "ones". Double that for 4, and triple for 5. 
					$scoreValue += 1000 * ($matches - 2);
					BaseUtil_Debug( "Matched $matches 'ones'. Score=$scoreValue", 7);
				}
			}
			else if( $number == 5 )
			{
				if( $matches < 3 )
				{
					$scoreValue += $matches * 50;
					BaseUtil_Debug( "Matched $matches 'fives'. Score=$scoreValue", 7);
				}
				else
				{					
					// 1000 for 3 "ones". Double that for 4, and triple for 5. 
					$scoreValue += 500 * ($matches - 2);
					BaseUtil_Debug( "Matched $matches 'fives'. Score=$scoreValue", 7);
				}
			}
			else
			{
				if( $matches < 3 )
				{
					// Invalid -- player tried to save less than 3 of a dice # that was not 1 or 5. 
					//BaseUtil_Debug( "Invalid save.", 1);
					//$invalidSave=1;
					//return 0;
				}
				else
				{
					// The dice value * 100 for triples, double that for quadruples and triple for quintuplets
					BaseUtil_Debug( "Matched $matches '$number' dice. Score=$scoreValue", 7);
					$scoreValue += ( ( $number * 100 ) * ($matches - 2) );
				}
			}
			
			$pairs = ($matches/2);
			if( $matches == 2 || $matches == 4 || $matches == 6 ) $threePair += $pairs;
			if( $matches == 3 || $matches == 6 ) $twoTriplets += ($matches/3);
			
			BaseUtil_Debug( "Matches = $matches, Pairs = $pairs, ThreePair = $threePair, Prev3Pair = $prevThreePairMatches, TwoTriplets = $twoTriplets", 14, "green" );
			if( ( $prevThreePairMatches == 2 && $pairs == 1 ) || ( $prevThreePairMatches == 1 && $pairs == 2 )  )
			{
				BaseUtil_Debug( "Taking a three pair over a four of a kind.", 14 );
				$forceThreePair = 1;
			}			
			$prevThreePairMatches = $threePair; // Number of matches matched this go-around. 
			
			if( $matches == 6 )
			{
				BaseUtil_Debug( "Six of a kind with dice value: $number", 1 ); 
				if( $number == 1 )
					Ach_AwardAchievement( $playerid, ACH_SIX_KIND_ONES ); // Six one's
				else
					Ach_AwardAchievement( $playerid, ACH_SIX_KIND ); // Six of something else
			}
			
		}
		
		
		// Special Case: [3][5][5][5][5][3]. That can be taken as 4x5's or 3 pairs of 3, 5, and 5. One would let
		// the player roll again and might be more favorable than the 1,000 points. I handle this by saving
		// the matches from the last loop iteration.
		// New exception: [1][1][1][1][5][5]. This will still allow a reroll but should be scored as 2100. 
		if( ($threePair == 3 && $scoreValue < 750) || ($forceThreePair == 1) )
		{
			if( $scoreValue < 750 ) $scoreValue = 750; // Three Pair!
			$invalidSave=0;
		
			if( $playerid ) Ach_AwardAchievement( $playerid, ACH_THREEPAIR );
			BaseUtil_Debug( "Three Pair! Score=$scoreValue", 7);
		}
		
		if( $numSingleMatches == 6 && $scoreValue < 1000 ) 
		{
			$scoreValue = 1000; // A Straight!
			$invalidSave=0;
		
			if( $playerid ) Ach_AwardAchievement( $playerid, ACH_STRAIGHT );
			BaseUtil_Debug( "Straight! Score=$scoreValue", 7);
		}
		
		if( $twoTriplets == 2 && $scoreValue < 2500 )
		{
			$scoreValue = 2500; // Two Triplets!
			$invalidSave=0;
		
			if( $playerid ) Ach_AwardAchievement( $playerid, ACH_TWOTRIPLETS );
			BaseUtil_Debug( "Two Triplets! Score=$scoreValue", 7);
		}
		
		BaseUtil_Debug( "Dice scored: $scoreValue", 7);
		
		return $scoreValue;
	}
	
?>