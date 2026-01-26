# iOS Lobby Implementation Plan

## Overview
Build the Farkle Ten iOS lobby screen using **SwiftUI** (not SpriteKit) to match the web look and feel, connecting to the existing PHP backend API.

## Architecture Decision
- **SwiftUI** for lobby and all UI screens (lists, cards, buttons are UI-native, not game graphics)
- **SpriteKit** reserved for actual dice gameplay (physics, animations)
- Hybrid navigation: SwiftUI hosts SpriteKit via `UIViewControllerRepresentable`

## Project Structure
```
ios-client/Farkle Ten/Farkle Ten/
├── App/
│   ├── FarkleTenApp.swift           # SwiftUI App entry point
│   ├── AppState.swift               # Session state, logged-in user
│   └── Config.swift                 # API environment toggle
├── Models/
│   ├── Player.swift                 # PlayerInfo from API
│   ├── Game.swift                   # Game card model
│   ├── Friend.swift                 # Active friend model
│   └── LobbyResponse.swift          # Full API response
├── Services/
│   ├── APIClient.swift              # HTTP client for farkle_fetch.php
│   ├── SessionManager.swift         # Keychain session storage
│   └── LobbyPollingService.swift    # 10s/20s polling timer
├── Views/
│   ├── Lobby/
│   │   ├── LobbyView.swift          # Main lobby screen
│   │   ├── PlayerCardView.swift     # Player info header
│   │   ├── XPProgressBar.swift      # XP bar component
│   │   ├── GameCardView.swift       # Individual game row
│   │   └── LobbyMenuView.swift      # Navigation buttons
│   ├── Login/
│   │   └── LoginView.swift          # Username/password login
│   └── Common/
│       ├── FeltBackground.swift     # Green felt texture
│       └── FarkleButton.swift       # Styled button
├── Theme/
│   ├── Colors.swift                 # Design tokens from CSS
│   └── Typography.swift             # Font styles
└── Resources/Assets.xcassets/       # Imported images
```

## API Configuration

**Switchable environments** via `Config.swift`:

```swift
enum APIEnvironment {
    case localhost  // http://localhost:8080/farkle_fetch.php
    case production // https://www.farkledice.com/farkle_fetch.php

    var baseURL: String {
        switch self {
        case .localhost: return "http://localhost:8080/farkle_fetch.php"
        case .production: return "https://www.farkledice.com/farkle_fetch.php"
        }
    }
}

// Toggle in Config.swift or via build scheme
static var environment: APIEnvironment = .localhost
```

**Test Credentials:**
- Localhost: `testuser` / `test123`
- Production: Use real account

## API Integration

**Lobby Request:**
```
POST farkle_fetch.php
action=getlobbyinfo&iossessionid=[64-char-hex-session-id]
```

**Response Structure (JSON array):**
| Index | Content |
|-------|---------|
| 0 | Player info (username, level, xp, xp_to_level, cardcolor, playertitle) |
| 1 | Active games array (gameid, playerstring, yourturn, winningplayer, gamemode) |
| 2 | New achievement (or null) |
| 3 | Level up info (or null) |
| 4 | Active tournament (or null) |
| 5 | Active friends array |

## Lobby UI Components

### Player Card (top)
- Profile image (50x50)
- Level badge (colored rounded rect with level number)
- Username (white, bold, text-shadow)
- Player title (gray)
- Achievement score (gold, right-aligned)

### XP Progress Bar
- Green gradient (earned) + gray gradient (remaining)
- Width based on xp / xp_to_level ratio

### Games List
- Orange background: "Waiting on you" (your turn)
- Blue background: Game finished
- Gray background: Waiting on opponent
- Trophy icon overlay for wins

### Menu Buttons (right side)
- New Game (green)
- Tournament (blue)
- My Profile, Friends, Leaderboard, Instructions, Logout

### Active Friends Section
- Friend cards with "Play" buttons
- Shows friends active in last 10 minutes

## Polling Behavior (match web)
- Ticks 1-20: Poll every 10 seconds
- Ticks 21-40: Poll every 20 seconds
- After 40 ticks: Go idle, show refresh button

## Assets to Import
From `wwwroot/images/`:
- `greenfeltbg.png`, `bluefeltbg.png` (backgrounds)
- `dice1-6.png`, `diceFront1-6.png` (dice)
- `farkle_ten_logo.png` (branding)
- `playericons/prestige0-8.png` (level icons)
- `images/achieves/*.png` (32 achievement badges)

## Design Tokens (from CSS)
```swift
// Colors
static let farkleGold = Color(hex: "#FFCC66")
static let feltGreen = Color(hex: "#1a5f1a")
static let gameYourTurn = Color.orange
static let gameWon = Color.green
static let gameLost = Color.red
```

## Implementation Phases

### Phase 1: Foundation (This Plan)
1. Convert app to SwiftUI lifecycle (`FarkleTenApp.swift`)
2. Create `Config.swift` with environment toggle (localhost/production)
3. Create `APIClient` with login + getLobbyInfo
4. Create `SessionManager` for Keychain storage
5. Build `LoginView` (username/password)
6. Build basic `LobbyView` with player card + games list
7. Implement polling service

### Phase 2: Visual Polish
1. Import image assets
2. Add felt background texture
3. Style game cards with status colors
4. Add XP progress bar with gradients

### Phase 3: Full Feature Parity
1. Active friends section
2. Achievement/level-up popups
3. Menu navigation to other screens
4. Connect to SpriteKit game scene

## Critical Reference Files
- `wwwroot/farklePageFuncs.php` - GetLobbyInfo() API response structure
- `wwwroot/farkle_fetch.php` - Main API endpoint router
- `wwwroot/js/farkleLobby.js` - Polling logic, data binding
- `wwwroot/css/farkle.css` - Colors, gradients, layout
- `templates/farkle_div_lobby.tpl` - HTML structure

## Verification
1. Start local Docker: `docker-compose up -d`
2. Build and run iOS app in Simulator
3. Toggle to localhost in Config.swift
4. Login with `testuser` / `test123`
5. Verify lobby displays player info and games list
6. Verify polling updates data every 10 seconds
7. Switch to production, login with real account, verify same behavior
