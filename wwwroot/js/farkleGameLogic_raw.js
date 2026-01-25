
//var gDiceImages = new Array(MAX_DICE);
var gDiceBackImage;
var gDiceImages = Array(MAX_DICE);
var gDiceImageJoker; 
var MAX_DICE = 5; // In an array we want 0-MAX, 6 dice so 0-5

function FarkleDice( value, scored, saved, index )
{
	this.value = value;
	this.scored = scored;
	this.saved = saved;
	this.index = index; 
	this.reset = function() {
		this.value = 0;
		this.scored = 0;
		this.saved = 0;
		this.tempScored = 0;
		
		DrawDice( this );

		this.ImageObj.removeAttribute('scored');		
		this.ImageObj.removeAttribute('rolling');
		this.ImageObj.removeAttribute('saved');
		this.ImageObj.style.display='inline';
	}
}

var dice = new Array(MAX_DICE);

function loadImage(src, onload) {
    // http://www.thefutureoftheweb.com/blog/image-onload-isnt-being-called
    var img = new Image();
    img.src = src;
    return img;
}

// initialize the dice array
function farkleInit(diceBackImg)
{
	var i;	

	// The background of the dice. 
	if(!diceBackImg) diceBackImg = 'dicebackSet6.png';
	gDiceBackImage = loadImage('/images/'+diceBackImg, 0); 
	
	// The black dots. 
	for (i=0;i<=MAX_DICE+1;i++) {
		gDiceImages[i] = loadImage('/images/diceFront'+i+'.png', 0); 
	}
	gDiceImageJoker = loadImage('/images/diceFrontJoker.png', 0); 
	
	for (i=0;i<=MAX_DICE;i++)
	{
		newDice = new FarkleDice( "0", "0", "0", i );
		dice[i] = newDice;
		dice[i].ImageObj = document.getElementById('dice'+i+'Canvas');
		DrawDice( newDice );
	}
	
	FarkleDiceReset();
	$('#divRemind').hide();
	
	lblRoundInfoObj = document.getElementById('lblRoundScore');
	btnRollDiceObj = document.getElementById('btnRollDice');
	btnPassObj = document.getElementById('btnPass');
	divTurnActionObj = document.getElementById('divTurnAction');
	divGameInfoObj = document.getElementById('divGameInfo');
}

function FarkleCalcRoll() {
	return Math.floor( Math.random() * 6 ) + 1; 
}

function DrawDice( theDice ) 
{
	var canvas = document.getElementById("dice"+theDice.index+"Canvas"); //dice0Canvas
	var ctx = canvas.getContext("2d");
	var pos = canvas.position; 
	
	if( theDice.scored == 1 ) {
		ctx.drawImage(gDiceBackImage, 0, 240, 120, 120, 0, 0, 60, 60);
	}else if( theDice.saved == 1 ) {
		ctx.drawImage(gDiceBackImage, 0, 120, 120, 120, 0, 0, 60, 60);
	} else {
		ctx.drawImage(gDiceBackImage, 0, 0, 120, 120, 0, 0, 60, 60);
	}
	
	ctx.drawImage(gDiceImages[theDice.value], 0, 0, 60, 60);
	
	if( theDice.saved || theDice.scored ) {
		$("#dice"+theDice.index+"Canvas").css('margin','60px 1px 1px 1px');
	} else {
		$("#dice"+theDice.index+"Canvas").css('margin','1px 1px 60px 1px');
	}
}

function FarkleDiceReset() {	
	var i;
	for( i=0; i<=MAX_DICE; i++ )
		dice[i].reset();
}

function farkleScoreDice( countOnlySaved ) {
	var scoreValue = 0;
	var numSingleMatches = 0;
	var i;
	var j;
	var threePair = 0; var twoTriplets = 0;
	var invalidSave = 0;
	var prevThreePairMatches = 0;
	var forceThreePair = 0;
	var threePairMatches = 0; 
	
	var tempScored = Array(0, 0, 0, 0, 0, 0);
	
	for( i=0; i<=MAX_DICE; i++ ) {
	
		if( !dice[i].saved || dice[i].scored ) continue; // Skip zeros...these are not saved and thus don't count
	
		//var number = $savedDice[$i];			
		var matches = 0;
		for( j=0; j<=MAX_DICE; j++ ) {
			if( dice[i].value == dice[j].value && dice[j].saved == 1 && tempScored[j] == 0 && dice[j].scored == 0 ) {
				matches++;
				//tempScored[j] = 1;
			}
		}
		
		if( matches == 1 ) numSingleMatches++; // If this gets to 6 we have a straight
	
		if( dice[i].value == 1 ) {
			if( matches < 3 ) {
				scoreValue += matches * 100;
			} else {
				// 1000 for 3 "ones". Double that for 4, and triple for 5. 
				scoreValue += 1000 * (matches - 2);
			}
		} else if( dice[i].value == 5 ) {
			if( matches < 3 ) {
				scoreValue += matches * 50;
			} else {
				// 1000 for 3 "ones". Double that for 4, and triple for 5. 
				scoreValue += 500 * (matches - 2);
			}
		} else {
			if( matches < 3 ) {
				// Invalid -- player tried to save less than 3 of a dice # that was not 1 or 5. 
				//BaseUtil_Debug( "Invalid save.", 1);
				if( tempScored[i] == 0 ) invalidSave = 1;
			} else {
				// The dice value * 100 for triples, double that for quadruples and triple for quintuplets
				scoreValue += ( ( dice[i].value * 100 ) * (matches - 2) );
			}
		}
		
		var pairs = (matches/2);
		if( matches == 2 || matches == 4 || matches == 6 ) threePair += (matches/2);
		if( matches == 3 || matches == 6 ) twoTriplets += (matches/3);
		
		//ConsoleDebug("Matches = $matches, Pairs = $pairs, ThreePair = $threePair, Prev3Pair = $prevThreePairMatches, TwoTriplets = $twoTriplets", 14, "green" );
		if( ( prevThreePairMatches == 2 && pairs == 1 ) || ( prevThreePairMatches == 1 && pairs == 2 )  ) {
			//BaseUtil_Debug( "Taking a three pair over a four of a kind.", 14 );
			forceThreePair = 1;
		}			
		prevThreePairMatches = threePair; // Number of matches matched this go-around. 
		
		for( j=0; j<=MAX_DICE; j++ ) {
			if( dice[i].value == dice[j].value && dice[j].saved == 1 && tempScored[j] == 0 && dice[j].scored == 0 )	{
				tempScored[j] = 1;
			}
		}
		
	}
	
	//if( threePair == 3 && scoreValue < 750 )
	if( (threePair == 3 && scoreValue < 750) || (forceThreePair == 1 ) ) {
		if( scoreValue < 750 ) scoreValue = 750; // Three Pair!
		invalidSave = 0;
	}
	
	if( numSingleMatches == 6 && scoreValue < 1000 ) {
		scoreValue = 1000; // A Straight!
		invalidSave = 0;
	}
	
	if( twoTriplets == 2 && scoreValue < 2500 ) {
		scoreValue = 2500; // Two Triplets!
		invalidSave = 0;
	}

	if( invalidSave ) scoreValue = 0;	
	return scoreValue;
}

function farkleUpdateDice( index, value, scored ) {
	dice[index].ImageObj.removeAttribute('disabled');
	if( value > 0 && value < 7 ) {
		dice[index].ImageObj.removeAttribute('disabled');
		dice[index].value = value;
	}
	
	if( scored ) {
		dice[index].ImageObj.removeAttribute('saved');
		dice[index].ImageObj.setAttribute( "scored", "" );
		dice[index].scored = 1;
	} else {
		dice[index].ImageObj.removeAttribute('scored');
		dice[index].scored = 0;
	}
	
	DrawDice( dice[index] );
}

function farkleSaveDice( dn, force ) {
	if( gGameState != GAME_STATE_ROLLING && gGameState != GAME_STATE_ROLLED ) {
		ConsoleDebug( "Not allowing a dice save because we aren't in a state that can do so. Game state="+gGameState ); 
		return 0; 
	}

	if( dice[dn].scored == 0 ) {
		if( dice[dn].saved == 0 || force ) {
			dice[dn].ImageObj.removeAttribute('rolled');
			dice[dn].ImageObj.setAttribute( "saved", "" );
			dice[dn].saved = 1;
		} else {
			dice[dn].ImageObj.removeAttribute('saved');
			dice[dn].ImageObj.setAttribute( "rolled", "" );
			dice[dn].saved = 0;
		}
	}
	DrawDice( dice[dn] );
	var oldTurnScore = gTurnScore;
	gTurnScore = farkleScoreDice( 0 );
	FarkleGameUpdateRoundScore();
}

function StopRollingAnimation() {
	var i; 
	for( i=0; i<=MAX_DICE; i++ ) {
		if( !dice[i].saved  && !dice[i].scored ) {
			dice[i].ImageObj.removeAttribute('rolling');
		}	
		//DrawDice( dice[i] );
	}
	//$('#divDice').hide().show();
}

function GetDiceValArray() {
	var diceArr = Array(0, 0, 0, 0, 0, 0);	
	var i;
	for( i=0; i<=MAX_DICE; i++ ) {
		diceArr[i] = ( dice[i].saved ) ? parseInt(dice[i].value) : 0;
		diceArr[i] = ( dice[i].scored ) ? 10 : diceArr[i];
	}
	return diceArr;
}