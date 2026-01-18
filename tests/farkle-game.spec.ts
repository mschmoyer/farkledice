import { test, expect, Page } from '@playwright/test';

/**
 * Generates a unique, realistic Farkle player username
 */
function generateFarkleUsername(): string {
  const adjectives = [
    'Lucky', 'Dizzy', 'Rolling', 'Swift', 'Fierce', 'Bold', 'Wild', 'Clever',
    'Mighty', 'Rapid', 'Sharp', 'Quick', 'Sly', 'Daring', 'Noble', 'Brave'
  ];

  const nouns = [
    'Roller', 'Dicer', 'Gambler', 'Player', 'Ace', 'Champion', 'Master', 'Pro',
    'Shark', 'Hustler', 'Legend', 'King', 'Queen', 'Knight', 'Wizard', 'Ninja'
  ];

  const adjective = adjectives[Math.floor(Math.random() * adjectives.length)];
  const noun = nouns[Math.floor(Math.random() * nouns.length)];
  const number = Math.floor(Math.random() * 9999);

  return `${adjective}${noun}${number}`;
}

/**
 * Reads the canvas element to determine the dice value
 * This analyzes the dice image to determine what number is showing
 */
async function getDiceValue(page: Page, diceIndex: number): Promise<number> {
  // Get the dice data from the game state
  const diceValue = await page.evaluate((index) => {
    // @ts-ignore - accessing global game variables
    if (window.dice && window.dice[index]) {
      // @ts-ignore
      return parseInt(window.dice[index].value);
    }
    return 0;
  }, diceIndex);

  return diceValue;
}

/**
 * Checks if a dice is already scored or saved
 */
async function isDiceScored(page: Page, diceIndex: number): Promise<boolean> {
  return await page.evaluate((index) => {
    // @ts-ignore
    if (window.dice && window.dice[index]) {
      // @ts-ignore
      return window.dice[index].scored === 1 || window.dice[index].scored === true;
    }
    return false;
  }, diceIndex);
}

/**
 * Checks if a dice is saved but not scored
 */
async function isDiceSaved(page: Page, diceIndex: number): Promise<boolean> {
  return await page.evaluate((index) => {
    // @ts-ignore
    if (window.dice && window.dice[index]) {
      // @ts-ignore
      return window.dice[index].saved === 1 || window.dice[index].saved === true;
    }
    return false;
  }, diceIndex);
}

/**
 * Counts how many dice show a specific value and are not scored
 */
async function countDiceValue(page: Page, value: number): Promise<number> {
  let count = 0;
  for (let i = 0; i < 6; i++) {
    const isScored = await isDiceScored(page, i);
    if (!isScored) {
      const diceVal = await getDiceValue(page, i);
      if (diceVal === value) {
        count++;
      }
    }
  }
  return count;
}

/**
 * Selects all scoreable dice (1s, 5s, and triples)
 * Returns true if any dice were selected
 */
async function selectScoreableDice(page: Page): Promise<boolean> {
  console.log('Analyzing dice to select scoreable ones...');

  // First, get all dice values that are not scored
  const diceValues: { index: number; value: number; scored: boolean; saved: boolean }[] = [];
  for (let i = 0; i < 6; i++) {
    const value = await getDiceValue(page, i);
    const scored = await isDiceScored(page, i);
    const saved = await isDiceSaved(page, i);
    diceValues.push({ index: i, value, scored, saved });
    console.log(`Dice ${i}: value=${value}, scored=${scored}, saved=${saved}`);
  }

  // Count occurrences of each value (excluding scored dice)
  const valueCounts = new Map<number, number[]>();
  for (const dice of diceValues) {
    if (!dice.scored) {
      if (!valueCounts.has(dice.value)) {
        valueCounts.set(dice.value, []);
      }
      valueCounts.get(dice.value)!.push(dice.index);
    }
  }

  let selectedAny = false;

  // Strategy: Select triples first, then 1s and 5s

  // 1. Select triples (3 or more of same value)
  for (const [value, indices] of valueCounts.entries()) {
    if (indices.length >= 3) {
      console.log(`Found triple or more of ${value}s (count: ${indices.length})`);
      // Select all dice of this value
      for (const index of indices) {
        if (!diceValues[index].saved) {
          await page.click(`#dice${index}Canvas`);
          console.log(`Selected dice ${index} (value: ${value}) for triple`);
          selectedAny = true;
          await page.waitForTimeout(100);
        }
      }
    }
  }

  // 2. Select all 1s (worth 100 points each, or 1000 for triple)
  if (valueCounts.has(1)) {
    const ones = valueCounts.get(1)!;
    if (ones.length < 3) { // If not already selected as triple
      for (const index of ones) {
        if (!diceValues[index].saved) {
          await page.click(`#dice${index}Canvas`);
          console.log(`Selected dice ${index} (value: 1)`);
          selectedAny = true;
          await page.waitForTimeout(100);
        }
      }
    }
  }

  // 3. Select all 5s (worth 50 points each, or 500 for triple)
  if (valueCounts.has(5)) {
    const fives = valueCounts.get(5)!;
    if (fives.length < 3) { // If not already selected as triple
      for (const index of fives) {
        if (!diceValues[index].saved) {
          await page.click(`#dice${index}Canvas`);
          console.log(`Selected dice ${index} (value: 5)`);
          selectedAny = true;
          await page.waitForTimeout(100);
        }
      }
    }
  }

  console.log(`Selected scoreable dice: ${selectedAny ? 'Yes' : 'No'}`);
  return selectedAny;
}

/**
 * Waits for the game state to change (indicating turn is complete)
 */
async function waitForTurnComplete(page: Page, timeout = 30000): Promise<void> {
  const startTime = Date.now();

  while (Date.now() - startTime < timeout) {
    // Check if it's our turn again or game is over
    const gameState = await page.evaluate(() => {
      // @ts-ignore
      return window.gGameState;
    });

    const turnActionText = await page.locator('#divTurnAction').textContent();

    // If we see "Your turn" or "Roll" button is enabled, our turn is ready
    if (turnActionText?.includes('Your turn') || gameState === 1) {
      console.log('Turn complete - ready for next turn');
      return;
    }

    // Check if game is over
    if (turnActionText?.includes('Game Over') || turnActionText?.includes('won')) {
      console.log('Game finished');
      return;
    }

    await page.waitForTimeout(500);
  }

  throw new Error('Timeout waiting for turn to complete');
}

test.describe('Farkle Game Automation', () => {
  test('Play a full 10-round game', async ({ page }) => {
    const username = generateFarkleUsername();
    const password = 'test123';

    console.log(`Generated username: ${username}`);

    // Navigate to the game
    await page.goto('/');

    // Wait for page to load
    await page.waitForSelector('#divLogin', { state: 'visible', timeout: 10000 });

    // Register new user
    console.log('Registering new user...');
    await page.click('input[value="New Farkle Player"]');
    await page.waitForTimeout(500);

    await page.fill('#txtRegUser', username);
    await page.fill('#txtRegPass', password);
    await page.click('input[value="Create"]');

    // Wait for login to complete and lobby to appear
    await page.waitForSelector('#divLobby', { state: 'visible', timeout: 15000 });
    console.log('Successfully logged in and reached lobby');

    // Check for available games
    await page.waitForTimeout(1000);

    const availableGamesVisible = await page.locator('#divLobbyGames').isVisible();
    const noGamesVisible = await page.locator('#divLobbyNoGames').isVisible();

    let gameStarted = false;

    // Try to join an existing game first
    if (availableGamesVisible && !noGamesVisible) {
      console.log('Checking for available games...');
      const gameCards = await page.locator('#divLobbyGames .gameCard').count();

      if (gameCards > 0) {
        console.log(`Found ${gameCards} available game(s), joining the first one`);
        await page.locator('#divLobbyGames .gameCard').first().click();
        gameStarted = true;
      }
    }

    // If no games available, create a random game
    if (!gameStarted) {
      console.log('No available games found, creating random game...');
      await page.click('input[value="New Game"]');
      await page.waitForTimeout(500);

      // Click "Play Random"
      await page.click('img[onclick*="SelectPlayType(2,0)"]');
      await page.waitForTimeout(500);

      // Select 2 players
      const twoPlayerBtn = await page.locator('input[value="2 Players"]');
      if (await twoPlayerBtn.isVisible()) {
        await twoPlayerBtn.click();
        await page.waitForTimeout(500);
      }

      // Start the game
      await page.click('input[value="Start Game"]');
    }

    // Wait for game to load
    await page.waitForSelector('#divGame', { state: 'visible', timeout: 10000 });
    console.log('Game started!');

    // Play 10 rounds
    for (let round = 1; round <= 10; round++) {
      console.log(`\n=== Starting Round ${round} ===`);

      // Wait a bit for the round to be ready
      await page.waitForTimeout(2000);

      // Check if it's our turn
      let isOurTurn = false;
      let attempts = 0;
      const maxAttempts = 60; // Wait up to 60 seconds for our turn

      while (!isOurTurn && attempts < maxAttempts) {
        const turnText = await page.locator('#divTurnAction').textContent();
        const rollButtonDisabled = await page.locator('#btnRollDice').getAttribute('disabled');

        if (turnText?.includes('Your turn') || rollButtonDisabled === null) {
          isOurTurn = true;
          console.log('It is our turn');
        } else {
          console.log(`Waiting for our turn... (${attempts + 1}/${maxAttempts})`);
          await page.waitForTimeout(1000);
          attempts++;
        }
      }

      if (!isOurTurn) {
        console.log('Timeout waiting for our turn, skipping this round');
        continue;
      }

      // Roll the dice
      console.log('Rolling dice...');
      await page.click('#btnRollDice');
      await page.waitForTimeout(2000); // Wait for dice to roll and animation to complete

      // Select all scoreable dice
      const selectedDice = await selectScoreableDice(page);

      if (!selectedDice) {
        console.log('FARKLED! No scoreable dice found.');
      } else {
        console.log('Scoreable dice selected');
      }

      // Wait a moment for the score to update
      await page.waitForTimeout(500);

      // Click "Score It" to pass the turn
      console.log('Passing turn...');
      await page.click('#btnPass');

      // Wait for turn to complete
      await page.waitForTimeout(2000);

      // Wait for next turn or game end
      try {
        await waitForTurnComplete(page, 30000);
      } catch (error) {
        console.log('Could not detect turn completion, continuing...');
      }

      // Check if game is over
      const gameOverText = await page.locator('#divTurnAction').textContent();
      if (gameOverText?.includes('Game Over') || gameOverText?.includes('won')) {
        console.log(`Game ended after round ${round}`);
        break;
      }
    }

    console.log('\n=== Game Complete ===');

    // Take a screenshot of the final state
    await page.screenshot({ path: 'tests/screenshots/game-complete.png', fullPage: true });

    // Wait a bit to see final results
    await page.waitForTimeout(3000);
  });
});
