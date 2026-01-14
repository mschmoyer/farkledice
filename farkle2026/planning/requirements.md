# Farkle Ten - Comprehensive Requirements Document

**Document Version:** 1.0
**Date:** January 2026
**Purpose:** Complete feature specification for rebuilding the Farkle Ten application

---

## Table of Contents

1. [Game Core Features](#1-game-core-features)
2. [User Account & Authentication](#2-user-account--authentication)
3. [Player Profile & Statistics](#3-player-profile--statistics)
4. [Progression System](#4-progression-system)
5. [Achievement System](#5-achievement-system)
6. [Social Features](#6-social-features)
7. [Tournament System](#7-tournament-system)
8. [Leaderboard System](#8-leaderboard-system)
9. [User Interface & Navigation](#9-user-interface--navigation)
10. [Notifications & Communications](#10-notifications--communications)
11. [Administrative Features](#11-administrative-features)
12. [Technical Requirements](#12-technical-requirements)

---

## 1. Game Core Features

### 1.1 Game Modes

#### REQ-GAME-001: 10-Round Mode (Primary Mode)
The system must support a 10-Round game mode where:
- Each player plays exactly 10 rounds
- The player with the highest total score after 10 rounds wins
- Players can complete their rounds independently (asynchronous play)
- The system must track which round each player is on (1-10, with 11 indicating completion)

#### REQ-GAME-002: Standard Mode (Legacy)
The system should support a Standard game mode where:
- Players race to reach a target point threshold (e.g., 2,500, 5,000, or 10,000 points)
- Players take turns in sequence
- The first player to reach the target score wins
- Optional "break-in" requirement (minimum score to start: 0, 250, 500, or 1,000 points)

### 1.2 Game Types

#### REQ-GAME-003: Random Games
Users must be able to start a game with random opponents where:
- The system matches the player with available opponents
- Player can choose 2-player or 4-player random games
- Players can opt out of random matchmaking via settings (random_selectable flag)
- The system displays "Looking for players to join..." while waiting for opponents
- Players can begin playing before opponents join

#### REQ-GAME-004: Friends Games
Users must be able to start a game with specific friends where:
- The player selects one or more friends from their friends list
- Maximum of 6 players for Standard mode
- Maximum of 32 players for 10-Round mode
- Friends are displayed with checkboxes for selection

#### REQ-GAME-005: Solo Games
Users must be able to play solo practice games where:
- The player plays alone against their own score
- Solo games use 10-Round mode exclusively
- Solo games provide reduced XP rewards (3 XP vs 5+ XP)
- Solo games do not count toward win/loss statistics
- Solo games are clearly labeled in the UI

### 1.3 Dice Mechanics

#### REQ-GAME-006: Dice Display
The system must display 6 dice using HTML5 Canvas where:
- Each die is rendered as a 60x60 pixel canvas element
- Dice backgrounds are customizable (unlocked at certain levels)
- Dice show visual states: normal, saved (selected), scored (locked from previous rolls)
- Saved dice appear in a different position (raised) from unsaved dice

#### REQ-GAME-007: Dice Interaction
Users must be able to interact with dice where:
- Clicking/tapping a die toggles its "saved" state
- Saved dice will not be re-rolled on the next roll
- Scored dice (from previous sets in the same round) cannot be clicked
- Mobile/tablet support via touchstart events
- Desktop support via mousedown events

#### REQ-GAME-008: Rolling Dice
Users must be able to roll dice where:
- Clicking "Roll" re-rolls all unsaved and unscored dice
- Rolling generates random values 1-6 for each die
- A rolling animation is displayed during the roll
- If all 6 dice have been scored, all dice reset for a fresh set
- The "Roll" button is disabled during server communication

### 1.4 Scoring Rules

#### REQ-GAME-009: Single Die Scoring
The system must score individual dice as follows:
- Each 1 = 100 points
- Each 5 = 50 points
- All other single dice (2, 3, 4, 6) = 0 points (invalid to save alone)

#### REQ-GAME-010: Three-of-a-Kind Scoring
The system must score three-of-a-kind as follows:
- Three 1s = 1,000 points
- Three 2s = 200 points
- Three 3s = 300 points
- Three 4s = 400 points
- Three 5s = 500 points
- Three 6s = 600 points

#### REQ-GAME-011: Four/Five/Six-of-a-Kind Scoring
The system must score additional matches as multipliers:
- Four-of-a-kind = (dice value x 100) x 2
- Five-of-a-kind = (dice value x 100) x 3
- Six-of-a-kind = (dice value x 100) x 4
- (For 1s: Four 1s = 2,000, Five 1s = 3,000, Six 1s = 4,000)

#### REQ-GAME-012: Special Combinations
The system must recognize and score special combinations:
- Three Pair (any three pairs) = 750 points
- Straight (1-2-3-4-5-6) = 1,000 points
- Two Triplets (two sets of three-of-a-kind) = 2,500 points

#### REQ-GAME-013: Farkle Detection
The system must detect when a roll results in a "Farkle":
- A Farkle occurs when no scoring dice are rolled
- On a Farkle, the player loses all points accumulated in the current round
- A Farkle animation/image must be displayed
- The round automatically ends after a Farkle

#### REQ-GAME-014: Invalid Save Detection
The system must prevent invalid dice saves:
- Cannot save non-scoring dice (2, 3, 4, 6 individually)
- Cannot pass turn without selecting at least one scoring die
- Display error message when user attempts invalid save

### 1.5 Turn Flow

#### REQ-GAME-015: Round Score Accumulation
The system must track scoring within a round:
- Round score accumulates from multiple rolls within the same round
- Saved dice are locked after rolling (cannot be un-saved)
- Current round score displays prominently during play

#### REQ-GAME-016: Passing Turn (Score It)
Users must be able to end their turn and bank points:
- Clicking "Score It" saves all accumulated points for the round
- Round score is added to player's total score
- System advances player to next round
- If all 6 dice are scored before passing, fresh dice are available

#### REQ-GAME-017: Hot Dice
The system must support "Hot Dice" (all 6 dice scored):
- When all 6 dice have been used for scoring, player gets a fresh set
- Player can continue rolling with all 6 new dice
- Accumulated round score carries over

### 1.6 Game State Management

#### REQ-GAME-018: Asynchronous Play
The system must support asynchronous gameplay:
- Players do not need to be online simultaneously
- Each player's turn data is stored server-side
- Game state updates via AJAX polling (10-second intervals initially)
- After extended inactivity, polling slows to 20-second intervals
- After further inactivity, the game goes "idle" requiring manual refresh

#### REQ-GAME-019: Game Expiration
The system must handle stale games:
- Games have an expiration date/time
- Expired games are automatically resolved:
  - If at least one round played by all: highest score wins
  - If some players haven't played: they may be removed from game
  - Solo games and games with no activity are deleted

#### REQ-GAME-020: Quitting/Forfeiting Games
Users must be able to quit games:
- Before playing first round: "Refuse" option (no penalty)
- After playing: "Forfeit" option (counts as a loss)
- Confirmation dialog required for forfeit
- Forfeiting removes player from the game

#### REQ-GAME-021: Winning Conditions
The system must determine winners:
- 10-Round Mode: Highest score after all players complete 10 rounds
- Standard Mode: First player to reach target score
- Ties: System handles tie-breaking (highest score wins)
- Winner recorded in database and achievements/XP awarded

### 1.7 Post-Game Features

#### REQ-GAME-022: Game Completion Display
Upon game completion, the system must:
- Display winner announcement
- Show final scores for all players
- Award XP based on outcome (win vs loss vs solo)
- Hide forfeit/quit button
- Show "Play Again" button for friend games
- Show "New Random" button for random games

#### REQ-GAME-023: Facebook Sharing
Users should be able to share game results to Facebook:
- Generate contextual messages based on outcome (win/loss/margin)
- Support different message formats for 2-player vs multiplayer games
- Include game score details in share content

---

## 2. User Account & Authentication

### 2.1 Registration

#### REQ-AUTH-001: Username Registration
Users must be able to create accounts with:
- Username (3-32 characters)
- Password (6-32 characters)
- Optional email address
- Validation for username length and format
- Password is hashed with MD5 before transmission

#### REQ-AUTH-002: Guest Accounts
The system should support temporary guest accounts:
- Auto-generated username prefixed with "guest"
- Limited features (no leaderboard ranking)
- Prompt to register for full access

#### REQ-AUTH-003: Username Filtering
The system must filter inappropriate usernames:
- Profanity filter checking against a comprehensive bad word list
- Reject registration if username contains filtered words

### 2.2 Authentication

#### REQ-AUTH-004: Login System
Users must be able to log in with:
- Username and password
- Password hashed client-side with MD5 before submission
- "Remember Me" functionality
- Error messages for invalid credentials

#### REQ-AUTH-005: Facebook OAuth Login
Users must be able to log in via Facebook:
- Facebook OAuth integration
- Auto-create account on first Facebook login
- Link Facebook profile picture to player card
- Support for Facebook ID storage and retrieval

#### REQ-AUTH-006: Session Management
The system must maintain user sessions:
- Server-side session storage
- Session persistence across page refreshes
- Automatic login if valid session exists
- Session timeout handling

### 2.3 Password Management

#### REQ-AUTH-007: Password Reset
Users must be able to reset forgotten passwords:
- Request reset via email address
- Generate unique reset code
- Email reset code to user
- Allow setting new password with valid code
- Reset code expiration

#### REQ-AUTH-008: Logout
Users must be able to log out:
- Confirmation dialog before logout
- Clear session data
- Facebook logout integration
- Return to login screen

---

## 3. Player Profile & Statistics

### 3.1 Player Information

#### REQ-PROFILE-001: Player Card Display
The system must display player information in a card format showing:
- Username
- Player level (numeric badge)
- Player title
- Achievement score
- Profile picture (Facebook or default)
- Background color/image

#### REQ-PROFILE-002: Profile Customization
Users must be able to customize their profile:
- Select player title from earned titles
- Player card background unlocked based on level (every 10 levels)
- Special backgrounds: prestige1.png through prestige8.png

### 3.2 Statistics Tracking

#### REQ-PROFILE-003: Win/Loss Statistics
The system must track competitive statistics:
- Total wins
- Total losses
- Win/loss ratio percentage

#### REQ-PROFILE-004: Scoring Statistics
The system must track scoring statistics:
- Total points scored (all-time)
- Highest single round score
- Highest 10-round game score
- Average round score
- Total farkles

#### REQ-PROFILE-005: Activity Statistics
The system must track activity:
- Rounds played
- Last played date
- Style points (legacy feature)

### 3.3 Game History

#### REQ-PROFILE-006: Completed Games List
Users must be able to view their completed games:
- List of recently completed games (up to 30)
- Show opponent names
- Show win/loss status (color-coded)
- Show game finish date
- Clickable to view game details

### 3.4 Settings/Options

#### REQ-PROFILE-007: Email Settings
Users must be able to manage email preferences:
- Set/update email address
- Email validation
- Toggle hourly game reminder emails

#### REQ-PROFILE-008: Matchmaking Settings
Users must be able to control matchmaking:
- Toggle "random_selectable" to opt in/out of random matching

---

## 4. Progression System

### 4.1 Experience Points (XP)

#### REQ-PROG-001: XP Earning
Users must earn XP through various actions:
- Finish a game (any mode): 5 XP
- Win a multiplayer game: 10 XP + bonus per opponent
- Solo game completion: 3 XP
- Good roll (750+ points in a round): 1 XP
- Rolling special combinations (straight, three pair, two triplets, triple ones or more): 1 XP

#### REQ-PROG-002: XP Display
The system must display XP information:
- Current XP amount
- XP required for next level
- XP progress bar in lobby
- XP gain popup during gameplay (+X animation)

#### REQ-PROG-003: Double XP Events
The system should support double XP periods:
- All XP gains doubled during event
- Visual indicator in lobby when active

### 4.2 Player Levels

#### REQ-PROG-004: Level Progression
The system must implement level progression:
- Players start at level 1
- XP requirements increase with each level
- Level 2 requires 40 XP
- Gradual increase per level
- No apparent level cap (levels 100+ supported)

#### REQ-PROG-005: Level Up Notifications
When a player levels up:
- Display animated "LEVEL UP!" overlay
- Show reward earned for the level
- Acknowledge level-up server-side

### 4.3 Level Rewards

#### REQ-PROG-006: Title Unlocks
Players unlock new titles every 3 levels:
- Level 3: "the Prospect"
- Level 6: "the Joker"
- Level 9: "the Princess"
- Level 12: "the Scary Clown"
- Level 15: "the Farkled"
- Level 18: "the Average Joe"
- Level 21: "the Wicked"
- Level 24: "the Sexy Lady"
- Level 27: "the Gamer"
- Level 30: "the Notorious"
- Level 33: "the Lucky Dog"
- Level 36: "the Veteran"
- Level 39: "the Samsquash"
- Level 42: "the Dola"
- Level 45: "the Star"
- Level 48: "the Professional"
- Level 51: "the Stud"
- Level 54: "the Dice Master"
- Level 57: "the Chosen One"
- Level 60: "the King of Farkle"
- Level 100: "the Centurion"

#### REQ-PROG-007: Card Background Unlocks
Players unlock new card backgrounds every 10 levels:
- Level 10: prestige1.png
- Level 20: prestige2.png
- Level 30: prestige3.png
- Level 40: prestige4.png
- Level 50: prestige5.png
- Level 60: prestige6.png
- Level 70: prestige7.png
- Level 100: prestige8.png

#### REQ-PROG-008: Dice Background Customization
Players can customize dice appearance:
- Multiple dice background sprite sheets available
- Default: dicebackSet6.png
- Stored as player preference (cardbg field)

---

## 5. Achievement System

### 5.1 Achievement Structure

#### REQ-ACH-001: Achievement Definition
Each achievement must have:
- Unique identifier (achievementid)
- Title
- Description
- Point worth (achievement points)
- Image file
- Tracking metric (e.g., totalwins, highscore)
- Threshold value for earning

#### REQ-ACH-002: Achievement Categories
Achievements are organized into categories:
- Scoring achievements (high rounds, totals)
- Win achievements (win streaks, total wins)
- Activity achievements (games played, farkles)
- Special achievements (tournaments, holidays)

### 5.2 Achievement Tracking

#### REQ-ACH-003: Achievement Detection
The system must automatically detect achievement completion:
- Check after each game action
- Compare player stats against achievement thresholds
- Award achievement when threshold is met

#### REQ-ACH-004: Achievement Display
Users must be able to view achievements:
- Full achievement list on profile page
- Earned achievements show image and earned date
- Unearned achievements show locked icon
- Achievement points displayed

#### REQ-ACH-005: Achievement Notifications
When an achievement is earned:
- Display popup with achievement details
- Show achievement image, title, description
- Award bonus XP (+20 XP per achievement)
- Mark achievement as acknowledged in database

### 5.3 Known Achievements

#### REQ-ACH-006: Holiday Achievement
Award special achievement for playing on Christmas:
- Check if date is December 24-25
- Award ACH_HOLIDAY achievement

#### REQ-ACH-007: Tournament Achievement
Award achievement for winning tournaments:
- Each tournament has an associated achievement
- Winner receives the tournament achievement

---

## 6. Social Features

### 6.1 Friends System

#### REQ-SOCIAL-001: Adding Friends
Users must be able to add friends by:
- Username search
- Email search
- Direct from player profile
- Friend request confirmation

#### REQ-SOCIAL-002: Friends List
Users must be able to view their friends:
- List all friends with player cards
- Show friend's username and profile picture
- Click friend to view their profile
- Options to start game or remove friend

#### REQ-SOCIAL-003: Removing Friends
Users must be able to remove friends:
- Confirmation dialog required
- Remove from friends list immediately
- Does not affect existing games

### 6.2 Player Profiles

#### REQ-SOCIAL-004: Viewing Other Players
Users must be able to view other players' profiles:
- Click player card to view profile
- See player's statistics
- See player's achievements (earned ones visible)
- See player's completed games
- Option to add as friend
- Option to start game against them

#### REQ-SOCIAL-005: Player Info Friends Tab
When viewing another player's profile:
- See their friends list
- Navigate to other profiles from there

---

## 7. Tournament System

### 7.1 Tournament Structure

#### REQ-TOURN-001: Tournament Configuration
Tournaments must have:
- Tournament name
- Game mode (Standard or 10-Round)
- Player cap (maximum participants)
- Round duration (hours per round)
- Tournament format (Dynamic/Single/Double Elimination)
- Start condition (manual, scheduled date, player count)
- Associated achievement for winner

#### REQ-TOURN-002: Tournament Formats
The system must support tournament formats:
- Dynamic Elimination
- Single Elimination
- Double Elimination

### 7.2 Tournament Participation

#### REQ-TOURN-003: Joining Tournaments
Users must be able to join tournaments:
- View active/upcoming tournaments from lobby
- Click to view tournament details
- Join button for unstarted tournaments
- Cannot join once tournament has started

#### REQ-TOURN-004: Leaving Tournaments
Users must be able to leave tournaments:
- Quit button for joined, unstarted tournaments
- Confirmation dialog required
- Cannot leave once tournament has started

### 7.3 Tournament Play

#### REQ-TOURN-005: Tournament Rounds
Tournament rounds must:
- Auto-generate matchups for each round
- Support bye rounds for odd player counts
- Track scores for all participants
- Determine round winners
- Advance winners to next round
- Eliminate losers (based on format)

#### REQ-TOURN-006: Tournament Game Display
Tournament games must display:
- Both players' names
- Current scores during play
- Score differential
- Winner indication with trophy icon
- Custom status messages (leading by X points, tied, etc.)

#### REQ-TOURN-007: Tournament Progress Display
Users must be able to view tournament progress:
- Round-by-round game list
- Participant list (before start)
- Current standings
- Next round countdown

### 7.4 Tournament Completion

#### REQ-TOURN-008: Tournament Winner
Upon tournament completion:
- Display winning player name
- Award tournament achievement
- Show final bracket/results
- Tournament marked as complete

---

## 8. Leaderboard System

### 8.1 Leaderboard Categories

#### REQ-LB-001: Daily Leaderboards
The system must track daily statistics:
- Today's High Scores (highest single round)
- Today's Farkles (most farkles in a day)
- Today's Most Wins (wins in a day)

#### REQ-LB-002: All-Time Leaderboards
The system must track all-time statistics:
- Most Wins (total career wins)
- Highest 10-Round Score (best 10-round game)
- Achievement Points (total achievement score)

### 8.2 Leaderboard Display

#### REQ-LB-003: Leaderboard Ranking
Each leaderboard must display:
- Rank number
- Player level badge
- Player username
- Statistic value
- Click to view player profile

#### REQ-LB-004: Leaderboard Tabs
The leaderboard screen must:
- Show daily stats prominently
- Display "MVP" (top winner of the day)
- Tabbed navigation between categories
- Highlight current user's row if ranked

#### REQ-LB-005: Leaderboard Updates
Leaderboards must:
- Update on each page load
- Cache data to avoid unnecessary refreshes
- Show up to 25 entries per category

---

## 9. User Interface & Navigation

### 9.1 Screen Structure

#### REQ-UI-001: Single Page Application
The application must function as a single-page app:
- All "pages" are divs that show/hide
- Navigation via JavaScript functions
- Maintain back navigation history
- Home button returns to lobby

#### REQ-UI-002: Main Screens
The application must include these screens:
- Login Screen
- Lobby Screen
- New Game Screen
- Game Play Screen
- Player Info/Profile Screen
- Friends Screen
- Leaderboard Screen
- Instructions Screen
- Tournament Screen
- Admin Screen (privileged users only)

### 9.2 Lobby

#### REQ-UI-003: Lobby Display
The lobby screen must display:
- Player's card with current stats
- XP progress bar
- List of active games
- Games color-coded by status:
  - Orange: Your turn / waiting on you
  - Dark Orange: Nobody has played yet
  - Grey: Waiting on others
  - Blue: Game finished (unacknowledged)
- Prominent "New Game" button
- Navigation buttons (Profile, Friends, Leaderboard)
- Tournament button when active tournament exists
- Double XP indicator when applicable

#### REQ-UI-004: Active Game Cards
Each game card in lobby must show:
- Opponent name(s)
- Game status text
- Visual indication if it's your turn
- Click to resume game

### 9.3 New Game Screen

#### REQ-UI-005: Game Type Selection
The new game screen must allow:
- Choose game type (Random, Friends, Solo)
- For Random: Choose 2 or 4 players
- For Friends: Select from friends list with checkboxes
- Display selected friends

#### REQ-UI-006: Game Options
The new game screen should allow configuring:
- Break-in amount (0, 250, 500, 1000)
- Points to win (2500, 5000, 10000) for Standard mode
- Game mode is primarily 10-Round

### 9.4 Game Screen

#### REQ-UI-007: Game Layout
The game screen must display:
- Dice frame with 6 dice
- Current round score
- Round/turn information
- Player cards with current scores and round numbers
- Roll and Score It buttons
- Back and Forfeit/Quit buttons
- Game ID and expiration info

#### REQ-UI-008: Game State Indicators
The game screen must show:
- Loading state
- Your turn indicator with round number
- "Last round!" highlight on round 10
- Watching/waiting state when not your turn
- Farkle animation when farkled
- Score popup when finishing a round

### 9.5 Responsive Design

#### REQ-UI-009: Mobile Support
The application must support mobile devices:
- Touch events for dice interaction
- Responsive layouts
- Mobile-optimized button sizes
- Different CSS for mobile/tablet/desktop

#### REQ-UI-010: Device Detection
The system must detect device type:
- iOS app detection
- Mobile browser detection
- Tablet detection
- Apply appropriate styling and behavior

### 9.6 Common UI Components

#### REQ-UI-011: Alert System
The application must have a custom alert system:
- Modal overlay display
- Customizable message
- Color-coded background (red, green, etc.)
- OK button for dismissal
- Auto-dismiss option for notifications

#### REQ-UI-012: Player Card Component
Reusable player card component showing:
- Profile picture
- Username with level badge
- Title
- Score/stats (contextual)

#### REQ-UI-013: Game Card Component
Reusable game card component showing:
- Player names
- Game status
- Color-coded background
- Click action to view/resume game

#### REQ-UI-014: Achievement Card Component
Reusable achievement display showing:
- Achievement icon
- Title and description
- Point value
- Earned date (if applicable)

### 9.7 Idle Detection

#### REQ-UI-015: Idle State Handling
The system must handle user inactivity:
- Lobby goes idle after ~40 polling intervals
- Game screen goes idle after ~30 intervals
- Display "idle" screen with refresh button
- Stop AJAX polling when idle
- Resume polling on user interaction

---

## 10. Notifications & Communications

### 10.1 Email Notifications

#### REQ-NOTIFY-001: Game Turn Emails
The system should support email notifications:
- Notify players when it's their turn
- Configurable hourly email digest
- Include game link in email
- Unsubscribe option

#### REQ-NOTIFY-002: Password Reset Emails
The system must send password reset emails:
- Include reset code
- Link to password reset page
- Expiration notice

### 10.2 Push Notifications

#### REQ-NOTIFY-003: iOS Push Notifications
The system should support iOS push notifications:
- APNS integration
- Notify on turn availability
- Badge count for unfinished games
- Device token registration

### 10.3 In-App Notifications

#### REQ-NOTIFY-004: Achievement Popups
Display achievement earned notifications:
- Animated popup
- Achievement details
- Auto-dismiss after delay

#### REQ-NOTIFY-005: Level Up Popups
Display level up notifications:
- "LEVEL UP!" animation
- Reward description
- Auto-dismiss after delay

#### REQ-NOTIFY-006: XP Gain Animation
Display XP gains during play:
- "+X" animation
- Slide up effect
- Auto-dismiss

---

## 11. Administrative Features

### 11.1 Admin Access

#### REQ-ADMIN-001: Admin Levels
The system must support admin user levels:
- Admin level stored per user
- Admin screens only visible to admins
- Admin actions restricted by level

### 11.2 Tournament Administration

#### REQ-ADMIN-002: Tournament Management
Admins must be able to:
- Create new tournaments
- Configure tournament settings
- Manually start tournaments
- Simulate tournament progression
- View tournament status

### 11.3 Maintenance

#### REQ-ADMIN-003: Stale Game Cleanup
The system must automatically clean up:
- Finish expired games with winners
- Delete abandoned games
- Remove non-playing participants from random games
- Clean up old round/set data

---

## 12. Technical Requirements

### 12.1 Data Storage

#### REQ-TECH-001: Database Tables
The system must maintain these data tables:
- farkle_players (user accounts)
- farkle_games (game instances)
- farkle_games_players (player-game relationships)
- farkle_rounds (round history)
- farkle_sets (dice set data within rounds)
- farkle_achievements (achievement definitions)
- farkle_achievements_players (earned achievements)
- farkle_friends (friend relationships)
- farkle_players_devices (push notification tokens)
- farkle_tournaments (tournament definitions)
- farkle_tournaments_players (tournament participants)
- farkle_tournament_games (tournament game mappings)

### 12.2 API Endpoints

#### REQ-TECH-002: AJAX Actions
The system must support these server actions:
- login - User authentication
- logout - Session termination
- register - New user registration
- forgotpass - Password reset request
- resetpass - Set new password
- startgame - Create new game
- farklegetupdate - Get game state
- farkleroll - Submit dice roll
- farklepass - End turn and bank points
- quitgame - Forfeit/quit game
- getlobbyinfo - Get lobby data
- getplayerinfo - Get player profile
- getachievements - Get achievement list
- ackachievement - Acknowledge achievement popup
- acklevel - Acknowledge level up
- updatetitle - Change player title
- saveoptions - Save user preferences
- getnewgameinfo - Get friends for new game
- getfriends - Get friends list
- addfriend - Add new friend
- removefriend - Remove friend
- getleaderboard - Get leaderboard data
- gettournamentinfo - Get tournament details
- addplayertotourney - Join tournament
- t_removeplayer - Leave tournament
- prestige - Prestige reset (legacy)

### 12.3 Security

#### REQ-TECH-003: Password Security
The system must:
- Hash passwords before transmission (MD5 client-side)
- Store hashed passwords in database
- Never transmit plain text passwords

#### REQ-TECH-004: Session Security
The system must:
- Validate session on all requests
- Prevent cross-player data access
- Validate player ownership of game actions

### 12.4 Performance

#### REQ-TECH-005: Polling Strategy
The system must implement efficient polling:
- Start with 10-second intervals
- Slow to 20-second intervals after ~20 requests
- Go idle after ~40 requests
- Resume fast polling on user action

#### REQ-TECH-006: Data Caching
The system should cache:
- Leaderboard data (avoid re-fetching unchanged data)
- Player info data
- Friend list data

### 12.5 Compatibility

#### REQ-TECH-007: Browser Support
The system must support:
- Modern browsers with HTML5 Canvas
- IE9 fallback styling
- Mobile Safari (iOS)
- Chrome (Android)

#### REQ-TECH-008: Responsive Breakpoints
The system must support multiple screen sizes:
- Desktop (full layout)
- Tablet (adapted layout)
- Mobile (compact layout, <400px special handling)

---

## Appendix A: Dice Scoring Quick Reference

| Combination | Points |
|------------|--------|
| Single 1 | 100 |
| Single 5 | 50 |
| Three 1s | 1,000 |
| Three 2s | 200 |
| Three 3s | 300 |
| Three 4s | 400 |
| Three 5s | 500 |
| Three 6s | 600 |
| Four of a kind | 2x three-of-a-kind value |
| Five of a kind | 3x three-of-a-kind value |
| Six of a kind | 4x three-of-a-kind value |
| Three Pair | 750 |
| Straight (1-2-3-4-5-6) | 1,000 |
| Two Triplets | 2,500 |

---

## Appendix B: XP Awards Reference

| Action | XP |
|--------|-----|
| Finish any game | 5 |
| Win multiplayer game | 10 + (players - 2) |
| Lose multiplayer game | 5 + (players - 2) |
| Complete solo game | 3 |
| Good roll (750+ points) | 1 |
| Earn achievement | 20 |

---

## Appendix C: Level Title Progression

| Level | Title |
|-------|-------|
| 3 | the Prospect |
| 6 | the Joker |
| 9 | the Princess |
| 12 | the Scary Clown |
| 15 | the Farkled |
| 18 | the Average Joe |
| 21 | the Wicked |
| 24 | the Sexy Lady |
| 27 | the Gamer |
| 30 | the Notorious |
| 33 | the Lucky Dog |
| 36 | the Veteran |
| 39 | the Samsquash |
| 42 | the Dola |
| 45 | the Star |
| 48 | the Professional |
| 51 | the Stud |
| 54 | the Dice Master |
| 57 | the Chosen One |
| 60 | the King of Farkle |
| 100 | the Centurion |

---

*End of Requirements Document*
