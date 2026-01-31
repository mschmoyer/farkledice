<!-- RELEASE NOTES -->
<div id="divReleaseNotes" class="pagelayout" align="center" style="display: none;">

	<div class="loginBox" style="text-align: left; max-width: 500px; padding: 15px; font-size: 14px;">
		<h2 style="text-align: center; margin-top: 5px; margin-bottom: 15px; font-size: 22px;">Release Notes</h2>

		{if isset($release_notes.releases)}
			{foreach $release_notes.releases as $release}
				<div style="margin-bottom: 20px;">
					<div style="color: #FFCC66; font-size: 18px; font-weight: bold;">
						v{$release.version}
						{if isset($release.headline)}
							<span style="color: #64B5F6;"> - {$release.headline}</span>
						{/if}
					</div>
					<div style="font-size: 12px; color: #ccc; margin-bottom: 5px;">
						{$release.date|date_format:"%b %e, %Y"}
					</div>
					<ul style="margin: 0; padding-left: 20px; color: #ddd; font-size: 14px;">
						{foreach $release.notes as $note}
							<li style="margin-bottom: 5px;">{$note}</li>
						{/foreach}
					</ul>
				</div>
			{/foreach}
		{else}
			<p style="text-align: center; color: #fff;">No release notes available.</p>
		{/if}
	</div>

	<input type="button" class="mobileButton" buttoncolor="red" value="Back" onClick="PageGoBack()" style="width: 110px; margin-top: 10px;">

</div>
