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

Run specific tests:
```bash
# Run only the algorithmic bot test
npx playwright test tests/farkle-game.spec.ts

# Run only the AI bot test
npx playwright test tests/farkle-ai-bot-game.spec.ts

# Run AI bot test with browser visible
npx playwright test tests/farkle-ai-bot-game.spec.ts --headed

# Run AI bot test in debug mode
npx playwright test tests/farkle-ai-bot-game.spec.ts --debug
```

## Test Descriptions

### `farkle-game.spec.ts`

This test automates a complete 10-round Farkle game against the original algorithmic bots:

1. **User Creation**: Generates a unique, realistic username (e.g., "LuckyRoller4231")
2. **Login**: Registers and logs in as the new user
3. **Game Selection**:
   - Clicks "Play a Bot" button
   - Selects Easy difficulty from the bot modal
   - Uses algorithmic bot logic (not AI)
4. **Gameplay**: Plays all 10 rounds by:
   - Waiting for the player's turn
   - Rolling the dice
   - Selecting all scoreable dice:
     - Triples (3+ of the same number)
     - All 1s (100 points each)
     - All 5s (50 points each)
   - Passing the turn (scoring the selected dice)
5. **Completion**: Takes a screenshot of the final game state

### `farkle-ai-bot-game.spec.ts`

This test automates a complete 10-round Farkle game against Claude AI bots with personality:

1. **User Creation**: Generates a unique, realistic username
2. **Login**: Registers and logs in as the new user
3. **AI Bot Selection**:
   - Clicks "Play a Bot" button
   - Uses the new simplified UI with Easy/Medium/Hard buttons
   - Selects "Easy Bot" which randomly picks an AI personality from the Easy tier
   - AI bots use Claude API for decision-making and personality-driven chat
4. **Gameplay**: Plays all 10 rounds with:
   - Extended timeout for AI API calls (150 seconds vs 120)
   - Same conservative dice selection strategy
   - Chat message capture to log AI bot personality interactions
   - Bot turns execute automatically via `Bot_ExecuteStep()` with Claude decision-making
5. **Completion**:
   - Logs AI chat messages from the game
   - Takes screenshot of final state with chat visible

**Key Differences from Algorithmic Bot Test:**
- Uses new simplified bot selection UI (`#divBotGame` with Easy/Medium/Hard buttons)
- AI bots take 1-2 seconds longer per turn due to API calls
- Includes chat message logging to capture bot personality
- Increased timeout values to accommodate AI response time
- Bot behavior is personality-driven and varies between games

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
- AI bot games: Check API_KEY is configured and Claude API is responding

**AI bot test specific timeouts:**
- AI bots make API calls to Claude which can take 1-3 seconds per turn
- The test uses extended timeouts (150s) to accommodate this
- If timeouts still occur, check:
  - Claude API key is set in environment variables
  - API quota is not exceeded
  - Network connectivity to Claude API

**Cannot find dice values:**
- Ensure the game JavaScript has fully loaded
- The test reads from `window.dice` global variable

**Login fails:**
- Username might already exist (very unlikely with random generation)
- Check that the database is accessible from Docker

**AI bot not appearing in selection:**
- Make sure you're on the `feature/ai-bot-players` branch
- The simplified bot UI should show Easy/Medium/Hard buttons
- Old bot selection UI shows `.bot-option` elements in a modal

**Chat messages not captured:**
- Chat div may not be visible initially
- AI personalities generate messages during their turns
- Check console logs for captured messages
