# Bot Player Feature

**Status:** Planning
**Created:** 2026-01-18

## Overview

Add AI bot opponents with three difficulty levels that level up, have personalities, and provide interactive commentary. Bots can play in real-time for "Play a Bot" mode or instantly fill unfilled random games.

## Task Breakdown

This feature is split into three parallel development tracks:

### 1. [Database Schema](./task-database.md)
- PostgreSQL ENUM types for bot algorithms
- Column additions to `farkle_players` and `farkle_games`
- Bot turn state tracking table
- Seed 9 bot player accounts (3 per difficulty)
- Migration scripts for local and Heroku

**Estimated Complexity:** Medium
**Dependencies:** None (can start immediately)

### 2. [Backend PHP](./task-backend.md)
- Bot AI algorithms (keeper selection + roll decisions)
- Personality message system with 400+ messages
- Interactive turn state machine
- Background bot fill for random games
- Timed task consolidation
- AJAX endpoints

**Estimated Complexity:** High
**Dependencies:** Database schema must be complete

### 3. [Frontend JavaScript & UI](./task-frontend.md)
- Bot game lobby UI
- Interactive turn display with animations
- Bot chat message area
- Turn polling and state updates
- Mobile/tablet support

**Estimated Complexity:** Medium
**Dependencies:** Backend must be partially complete (at least message system)

## Development Strategy

### Sequential Phases

**Phase 1:** Database Schema (1-2 days)
- Complete all DB changes
- Seed bot accounts
- Test in Docker

**Phase 2:** Backend Core (3-4 days)
- Implement all 3 bot algorithms
- Build message system with personalities
- Create turn state machine

**Phase 3:** Backend + Frontend Integration (2-3 days)
- Wire up AJAX endpoints
- Build frontend polling
- Create UI components
- Test interactive bot gameplay

**Phase 4:** Background Fill & Polish (1-2 days)
- Implement timed task system
- Add bot auto-fill logic
- Final testing and deployment

### Parallel Development

Once Phase 1 (Database) is complete, Backend and Frontend can work in parallel:
- Backend developer: Focuses on AI algorithms and message system
- Frontend developer: Builds UI components and chat display (using mock data initially)
- Integration happens in Phase 3

## Key Features

✅ **Real Player Accounts** - Bots are actual `farkle_players` records
✅ **Level Progression** - Bots earn XP and level up over time
✅ **Personality System** - 9 unique bots with distinct personalities
✅ **400+ Messages** - Rich commentary system (50+ messages per situation × 8 situations)
✅ **Two Play Modes** - Interactive (visible turns) and Instant (background fills)
✅ **Mathematical AI** - Three algorithms: Easy (mistakes), Medium (tactical), Hard (optimal)

## Bot Roster

### Easy (Friendly, Learning)
- **Byte the Rookie Bot** - Enthusiastic, uses emojis
- **Chip the Friendly Bot** - Encouraging and supportive
- **Beep the Learning Bot** - Robot references, makes mistakes

### Medium (Tactical, Strategic)
- **Cyber the Tactical Bot** - Military-themed language
- **Logic the Strategic Bot** - Rational and methodical
- **Binary the Calculated Bot** - Numbers-focused

### Hard (Advanced, Masterful)
- **Neural the Master Bot** - Neural network references
- **Quantum the Perfect Bot** - Physics and probability
- **Apex the Supreme Bot** - Superior and condescending

## Success Metrics

- Bots complete games without errors
- Interactive mode feels responsive (<500ms per step)
- Random games filled within 5 min of timeout
- Each bot has distinct personality in messages
- User engagement increases with bot option

## See Also

- [Original implementation plan](../bot-player-implementation.md) - Comprehensive design doc
- [Task: Database](./task-database.md)
- [Task: Backend](./task-backend.md)
- [Task: Frontend](./task-frontend.md)
