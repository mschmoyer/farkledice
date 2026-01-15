<!-- ADD FRIEND -->

<div id="divFriends" style="display: none;">

	<div id="divFriendButtons" class="loginBox">
	<input id="btnFriendsShowAddFriend" type="button" class="mobileButton" buttoncolor="green" value="Add Friend"
			onClick="ShowAddFriend()" style="width: 150px;">
	
	{*<input type="button" class="mobileButton" value="Facebook Invite" 
					onClick="sendRequestViaMultiFriendSelector()" buttonColor="blue" style="font-size: 14px; padding: 1px;">*}
	
	<div id="divAddFriend" style="display: none;" >

		By Farkle username:<br/>
		<input id="txtAddByUsername" class="mobileText" type="text" placeholder=""><br/>
		<br/>
		By email:<br/>
		<input id="txtAddByEmail" class="mobileText" type="text" placeholder=""><br/>
		<br/>
		
		{*<a href="#" onclick="getUserFriends();">Get friends</a><br/>
		<div id="user-friends"></div>*}
		
		<input id="btnFriendAddFriend" type="button" class="mobileButton" buttoncolor="green" value="Add Friend" onClick="AddFriendPageSubmit()"> 
		<input id="btnFriendCancel" type="button" class="mobileButton" buttoncolor="red" value="Cancel" onClick="ShowFriends()">
	</div>
	</div>

	<div id="friendPageList"></div>
	
	<div id="divFriendTemplate" style="display: none; width: 320px;">
		<div id="divFriendCard" class="friendDiv" style="float: left; margin: 1px;">	
			<img id="friendPageFriendImage">
			
			<div id="friendNameDiv">
				<span id="friendName" style="padding: 6px 0 0 10px; float: left; color: white;" class="shadowed"
					onClick="ShowPlayerInfo( this.attributes['playerid'].value );"></span>
			</div>
			
		</div>

			<div id="friendRemoveDiv" style="float: right;">
				<input id="friendRemoveBtn" type="button" class="mobileButton" buttoncolor="red" value="X" 
					style="margin: 0px 2px 0 2px" onClick="RemoveFriend( this.attributes['playerid'].value );"></div>
				
				<div id="friendPlayDiv" style="float: right;">
				<input id="friendPlayBtn" type="button" class="mobileButton" buttoncolor="green" value="Play"  
					style="margin: 0px 2px 0 2px" onClick="StartGameAgainstPlayer( this.attributes['playerid'].value );"></div>

	</div>
	
	<div id="divFriendButtons2">
		<div id="divLobbyFBInvite" align="center">
			<br/>
			<input type="button" class="mobileButton" value="Invite Facebook Friends!" 
					onClick="sendRequestViaMultiFriendSelector()" buttonColor="blue" {if $mobilemode}style="font-size: 14px;"{/if}>
		</div>
		
		<input type="button" class="mobileButton" buttoncolor="red" value="Back" onClick="PageGoBack()">
	</div>
		
	
</div>