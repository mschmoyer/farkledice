# Farkle Dice Playwright Tests

This directory contains automated end-to-end tests for the Farkle Dice game using Playwright.

## Setup

1. Install dependencies:
   ```bash
   npm install
   npx playwright install
   ```

2. Make sure Docker is running with the Farkle application:
   ```bash
   docker-compose up -d
   ```

3. Verify the app is accessible at http://localhost:8080

## Running Tests

Run tests in headless mode:
```bash
npm test
```

Run tests with browser visible:
```bash
npm run test:headed
```

Debug tests interactively:
```bash
npm run test:debug
```

## Test Description

### `farkle-game.spec.ts`

This test automates a complete 10-round Farkle game:

1. **User Creation**: Generates a unique, realistic username (e.g., "LuckyRoller4231")
2. **Login**: Registers and logs in as the new user
3. **Game Selection**:
   - Attempts to join the top available game
   - If no games exist, creates a new random game
4. **Gameplay**: Plays all 10 rounds by:
   - Waiting for the player's turn
   - Rolling the dice
   - Selecting all scoreable dice:
     - Triples (3+ of the same number)
     - All 1s (100 points each)
     - All 5s (50 points each)
   - Passing the turn (scoring the selected dice)
5. **Completion**: Takes a screenshot of the final game state

## Test Strategy

The test implements a conservative scoring strategy:
- Always selects triples first (highest value)
- Then selects all available 1s
- Then selects all available 5s
- Never re-rolls (passes after first roll each turn)
- Accepts Farkles when they occur

This ensures the test completes reliably without getting stuck in complex decision-making.

## Screenshots

Failed tests automatically capture screenshots in `playwright-report/`.
The final game state is saved to `tests/screenshots/game-complete.png`.

## Troubleshooting

**Test times out waiting for turn:**
- The game may be waiting for other players
- Solo games should work without delays
- Random games may have delays if waiting for opponents

**Cannot find dice values:**
- Ensure the game JavaScript has fully loaded
- The test reads from `window.dice` global variable

**Login fails:**
- Username might already exist (very unlikely with random generation)
- Check that the database is accessible from Docker
