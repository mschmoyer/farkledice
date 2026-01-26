# iOS Client - CLAUDE.md

This folder contains the native iOS client for Farkle Ten.

## Project Overview

**Project Name:** Farkle Ten
**Location:** `ios-client/Farkle Ten/`
**Architecture:** SwiftUI (lobby/menus) + SpriteKit (dice gameplay)
**Language:** Swift
**Minimum iOS:** 16.0

## Technology Stack

| Component | Technology |
|-----------|------------|
| UI Framework | SwiftUI (lobby, login, menus) |
| Dice Gameplay | SpriteKit (reserved for future) |
| State Management | ObservableObject + @EnvironmentObject |
| Networking | URLSession async/await |
| Session Storage | Keychain |
| Testing | XCTest, Swift Testing |

## Directory Structure

```
ios-client/Farkle Ten/Farkle Ten/
├── App/
│   ├── FarkleTenApp.swift           # SwiftUI App entry point (@main)
│   ├── AppState.swift               # Global session state, login management
│   └── Config.swift                 # API environment toggle (localhost/prod)
├── Models/
│   ├── Player.swift                 # Player info from API
│   ├── Game.swift                   # Active game model
│   ├── Friend.swift                 # Active friend model
│   ├── LobbyResponse.swift          # Full lobby API response parser
│   ├── Dice.swift                   # Dice model (value, saved, scored)
│   ├── GamePlayer.swift             # Player in game context
│   ├── GameState.swift              # Core game state (ObservableObject)
│   ├── GameResponse.swift           # Game API response parser
│   └── ActivityLogEntry.swift       # Activity log entry model
├── Services/
│   ├── APIClient.swift              # HTTP client for farkle_fetch.php
│   ├── SessionManager.swift         # Keychain session storage
│   ├── LobbyPollingService.swift    # 10s/20s polling timer
│   ├── DiceScoringEngine.swift      # Client-side scoring logic
│   └── PushNotificationManager.swift # APNs push notification handling
├── Views/
│   ├── Lobby/
│   │   ├── LobbyView.swift          # Main lobby screen
│   │   ├── PlayerCardView.swift     # Player info header
│   │   ├── XPProgressBar.swift      # XP progress bar
│   │   ├── GameCardView.swift       # Game list row
│   │   └── LobbyMenuView.swift      # Navigation buttons
│   ├── Game/
│   │   ├── GameView.swift           # Main game container
│   │   ├── GameNavBar.swift         # Reusable nav: home icon + logo
│   │   ├── DiceAreaView.swift       # 6 dice in 2x3 grid
│   │   ├── DieView.swift            # Single die with tap/animation
│   │   ├── GameControlsView.swift   # Roll/Bank buttons
│   │   ├── RoundScoreView.swift     # Score with fire effects
│   │   ├── GamePlayerCardView.swift # In-game player info
│   │   ├── ActivityLogView.swift    # Round history
│   │   ├── BotChatView.swift        # Bot messages display
│   │   ├── FarkleOverlay.swift      # "FARKLE!" animation
│   │   └── GameFinishedView.swift   # End game summary
│   ├── Login/
│   │   └── LoginView.swift          # Username/password login
│   └── Common/
│       ├── FeltBackground.swift     # Green felt texture
│       └── FarkleButton.swift       # Styled button
├── Theme/
│   ├── Colors.swift                 # Design tokens from web CSS
│   └── Typography.swift             # Font styles
├── AppDelegate.swift                # UIKit delegate (legacy, kept for compatibility)
├── GameScene.swift                  # SpriteKit scene (future dice gameplay)
├── GameViewController.swift         # SpriteKit view controller (future)
└── Assets.xcassets/                 # Images, colors, app icon
```

## Opening the Project

```bash
# Open in Xcode
open "ios-client/Farkle Ten/Farkle Ten.xcodeproj"
```

## Running the App

1. Open project in Xcode
2. Select a simulator or connected device (iOS 16.0+)
3. Press `Cmd+R` to build and run

## Checking Build Logs

Xcode build logs are stored in DerivedData. To extract errors from the most recent build:

```bash
# Find build logs
ls -la ~/Library/Developer/Xcode/DerivedData/Farkle_Ten-*/Logs/Build/*.xcactivitylog

# Extract errors from a specific log (logs are gzip compressed)
gunzip -c ~/Library/Developer/Xcode/DerivedData/Farkle_Ten-*/Logs/Build/LOGFILE.xcactivitylog | strings | grep -i "error:"
```

## API Configuration

Toggle between environments in `App/Config.swift`:

```swift
// For local Docker testing:
Config.environment = .localhost  // http://localhost:8080/farkle_fetch.php

// For production:
Config.environment = .production // https://www.farkledice.com/farkle_fetch.php
```

In debug builds, you can also tap the environment indicator on the login screen to switch.

**Test Credentials (localhost):** `testuser` / `test123`

## API Integration

**Login:**
```
POST farkle_fetch.php
action=login&user=[MD5_of_username]&pass=[MD5_of_password]&remember=1
```

**Lobby Info:**
```
POST farkle_fetch.php
action=getlobbyinfo&iossessionid=[64-char-session-id]
```

The lobby response is a JSON array with indices:
- `[0]` Player info (username, level, xp, xp_to_level, cardcolor, playertitle)
- `[1]` Active games array
- `[2]` New achievement (or null)
- `[3]` Level up info (or null)
- `[4]` Active tournament (or null)
- `[5]` Active friends array

## Polling Behavior

Matches web behavior:
- Ticks 1-20: Poll every 10 seconds
- Ticks 21-40: Poll every 20 seconds
- After 40 ticks: Go idle, show refresh button

## Current Implementation Status

### Completed (Phase 1 - Foundation):
- [x] SwiftUI App lifecycle
- [x] Config with environment switching
- [x] APIClient with login and getLobbyInfo
- [x] SessionManager with Keychain storage
- [x] LoginView with username/password
- [x] LobbyView with player card, games list
- [x] Polling service
- [x] Theme colors and typography

### Completed (Phase 2 - Game View):
- [x] GameView - Main game container
- [x] DieView/DiceAreaView - 6-dice display with animations
- [x] GameControlsView - Roll/Bank buttons
- [x] RoundScoreView - Score with fire effects
- [x] GamePlayerCardView - In-game player info
- [x] ActivityLogView - Round history with Unicode dice
- [x] BotChatView - Bot messages display
- [x] FarkleOverlay - FARKLE animation
- [x] GameFinishedView - End game summary
- [x] LobbyView → GameView navigation

### Completed (Phase 3 - Polish):
- [x] Push notifications (APNs HTTP/2 + JWT)

### TODO (Phase 3 - Polish):
- [ ] Import image assets from web
- [ ] Add felt background texture
- [ ] Trophy icon for wins
- [ ] New game creation flow
- [ ] Achievement/level-up popups
- [ ] Menu navigation screens

## Architecture Notes

**Hybrid SwiftUI + SpriteKit:**
- SwiftUI for all non-game UI (lobby, menus, settings)
- SpriteKit reserved for dice rolling gameplay (future)
- Navigation via SwiftUI, game view hosted via `UIViewControllerRepresentable`

**State Management:**
- `AppState` is the single source of truth for session
- Passed down via `@EnvironmentObject`
- Polling managed at app level, not per-view

## Dependencies

Uses only Apple frameworks:
- SwiftUI
- CryptoKit (for MD5 hashing)
- Security (for Keychain)
- SpriteKit (reserved for gameplay)
- GameplayKit (reserved for AI)

## Related Documentation

- [SwiftUI Tutorials](https://developer.apple.com/tutorials/swiftui)
- [SpriteKit Programming Guide](https://developer.apple.com/documentation/spritekit)
- Main project CLAUDE.md: `../CLAUDE.md`
- Implementation plan: `ios-client/PLAN.md`
