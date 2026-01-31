<!-- EMOJI PICKER POPUP -->

<div id="emojiPickerOverlay" class="emoji-picker-overlay" style="display:none;">
	<div class="emoji-picker-modal">
		<div class="emoji-picker-header">How'd they roll?</div>
		<div class="emoji-grid">
			{* Row 1 (positive) *}
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😂')">😂</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😊')">😊</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🎉')">🎉</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('❤️')">❤️</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🔥')">🔥</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('💀')">💀</button>

			{* Row 2 (competitive) *}
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😎')">😎</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🏆')">🏆</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('👏')">👏</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('💪')">💪</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🎲')">🎲</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🤝')">🤝</button>

			{* Row 3 (reactions) *}
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('👍')">👍</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😢')">😢</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😤')">😤</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🤣')">🤣</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('💯')">💯</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('⭐')">⭐</button>

			{* Row 4 (playful) *}
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('💩')">💩</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😈')">😈</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🙄')">🙄</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🤔')">🤔</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('👀')">👀</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🫡')">🫡</button>

			{* Row 5 (expressive) *}
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🥳')">🥳</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😭')">😭</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🫠')">🫠</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('👋')">👋</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🎯')">🎯</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😏')">😏</button>

			{* Row 6 (misc) *}
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🙈')">🙈</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('💔')">💔</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🤯')">🤯</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('😴')">😴</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🍀')">🍀</button>
			<button class="emoji-btn mobileButton" onclick="SendEmojiReaction('🎰')">🎰</button>
		</div>
		<button class="emoji-skip-btn mobileButton" buttoncolor="red" onclick="SkipEmojiReaction()">Skip</button>
	</div>
</div>

{* Hidden field to store current game ID for emoji picker *}
<input type="hidden" id="emojiPickerGameId" value="">
