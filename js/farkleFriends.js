
var g_friendData; 

function ShowFriends()
{
	HideAllWindows();
	$('#divFriends').show();
	$('#btnFriendsShowAddFriend').show();
	$('#divAddFriend').hide();
	GetFriendPageFriends( 0 );
}

function ShowAddFriend()
{
	HideAllWindows();
	$('#divFriends').show();
	$('#divAddFriend').show();
	$('#btnFriendsShowAddFriend').hide();
}

function AddFriend( identstring, ident )
{
	AjaxCallPost( gAjaxUrl, AddFriendHook, 
		'action=addfriend&identstring=' + identstring + '&ident=' + ident );
}

function RemoveFriend( playerid )
{
	if( confirm( 'Are you sure you would like to remove this player from your friends list?' ) )
	{
		$( '#objFriend'+playerid ).remove();
		
		AjaxCallPost( gAjaxUrl, 
			function () 
			{ 
				$('#btnAddFriend').show(); 
				$('#btnRemoveFriend').hide(); 
			}, 
			'action=removefriend&friendid=' + playerid );
	}
}

function AddFriendPageSubmit()
{
	var theUser = $('#txtAddByUsername').val();
	var email = $('#txtAddByEmail').val();
	var params = '';
	
	if( !email && !theUser )
	{
		farkleAlert('Must enter an email or username.');
		return 0; 
	}
	
	if( theUser )
		params = 'action=addfriend&identstring=' + theUser + '&ident=username';
	else
		params = 'action=addfriend&identstring=' + email + '&ident=email';	

	$('#btnFriendAddFriend').val( 'Adding...').attr('disabled','');
	$('#btnFriendCancel').attr('disabled','');
		
	AjaxCallPost( gAjaxUrl, AddFriendHook, params );
}

function AddFriendHook()
{
	if( ajaxrequest.responseText )
	{
		var data = farkleParseJSON( ajaxrequest.responseText );
		if( data.Error ) 
		{
			farkleAlert( data.Error );
			return 0; 
		}
		
		g_friendData = data; 
		PopulateFriendPageFriends();
		
		farkleAlert( 'Friend added.' );
		//ShowLobby();
	}
	$('#btnFriendAddFriend').val( 'Add Friend').removeAttr('disabled');
	$('#btnFriendCancel').removeAttr('disabled');
}

function GetFriendPageFriends( force )
{
	if( !g_friendData || force )
	{
	AjaxCallPost( gAjaxUrl, 
		function () 
		{ 
			if( ajaxrequest.responseText )
			{
				// Store the friend data. 
				g_friendData = farkleParseJSON( ajaxrequest.responseText );
				PopulateFriendPageFriends();
			}
		}, 
		'action=getfriends' );
	}
	else
	{
		PopulateFriendPageFriends();
	}
}

function PopulateFriendPageFriends() 
{
	var i;
	var n; 
	var outerDiv; 
	for( i=0; i<g_friendData.length;i++)
	{		
		var thePlayerId = g_friendData[i].playerid;
		var color = g_friendData[i].cardcolor; 
		var fbid = g_friendData[i].facebooid; 
		if( !color ) color = 'green';	
		
		// If we alreayd find this playerid then use that one -- else add one. 
		outerDiv = $('#friendPageList').find('#objFriend'+thePlayerId);
		if( outerDiv.length == 0 )
		{
			outerDiv = $('#divFriendTemplate').clone(); 
		}	
		outerDiv.attr('playerid', thePlayerId );
		outerDiv.attr('id','objFriend'+thePlayerId );
		
		n = outerDiv.find('#divFriendCard');
		n.attr('playerid', thePlayerId );
		
		
		if( color.match(/.png/gi) )
			n.css('background-image', "url('/images/playericons/" + color + "')");
		else
			n.css('background-color', color );

		n.find('#friendName').html( g_friendData[i].username );
		
		if( fbid ) {
			var fbImage = 'https://graph.facebook.com/' + fbid + '/picture';
			n.find('#friendPageFriendImage').show().attr('src', fbImage);
		}
		else {
			n.find('#friendPageFriendImage').hide();
		}
		outerDiv.show();
		
		
		// Clicking the player's name will go to their profile page. 
		var friendClick = n.click( function( e ) { ShowPlayerInfo( e.currentTarget.getAttribute("playerid") ) } );
		
		var delButton = outerDiv.find('#friendRemoveBtn').attr('playerid', thePlayerId ); //.click( function( e ) { RemoveFriend( e.currentTarget.getAttribute("playerid") ); } );
			
		var playButton = outerDiv.find('#friendPlayBtn').attr('playerid', thePlayerId ); //.click( function( e ) { StartGameAgainstPlayer( e.currentTarget.getAttribute("playerid") ); } );
		
		outerDiv.appendTo( '#friendPageList' );
	}
}