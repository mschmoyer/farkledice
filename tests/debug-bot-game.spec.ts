import { test, Page } from '@playwright/test';

test.describe('Debug Bot Game Loading', () => {
  test('Check bot game initialization', async ({ page }) => {
    // Capture all console messages
    const consoleMessages: string[] = [];
    page.on('console', msg => {
      consoleMessages.push(`[${msg.type()}] ${msg.text()}`);
    });

    page.on('pageerror', error => {
      consoleMessages.push(`[PAGE ERROR] ${error.message}`);
    });

    // Login as testuser
    await page.goto('/');
    await page.waitForSelector('#divLogin', { state: 'visible', timeout: 10000 });
    await page.fill('#txtUsername', 'testuser');
    await page.fill('#txtPassword', 'test123');
    await page.click('input[value="Login"]');

    // Wait for lobby
    await page.waitForSelector('#divLobby', { state: 'visible', timeout: 15000 });
    console.log('✓ Reached lobby');

    // Create bot game
    await page.click('input[value="New Game"]');
    await page.waitForTimeout(500);
    await page.click('img[onclick*="showBotGameModal()"]');
    await page.waitForTimeout(500);

    // Click Easy Bot
    await page.waitForSelector('#divBotGame', { state: 'visible', timeout: 5000 });
    console.log('✓ Bot selection UI visible');
    await page.click('#divBotGame input[value*="Easy Bot"]');

    // Wait for game div to appear
    await page.waitForSelector('#divGame', { state: 'visible', timeout: 10000 });
    console.log('✓ Game div visible');

    // Wait a moment for JavaScript to initialize
    await page.waitForTimeout(3000);

    // Get game state info
    const gameInfo = await page.evaluate(() => {
      return {
        // @ts-ignore
        gGameState: typeof window.gGameState !== 'undefined' ? window.gGameState : 'undefined',
        // @ts-ignore
        gGameData: typeof window.gGameData !== 'undefined' ? window.gGameData : 'undefined',
        // @ts-ignore
        gGamePlayerData: typeof window.gGamePlayerData !== 'undefined' ? window.gGamePlayerData : 'undefined',
        // @ts-ignore
        gBotIsPlaying: typeof window.gBotIsPlaying !== 'undefined' ? window.gBotIsPlaying : 'undefined',
        // @ts-ignore
        playerid: typeof window.playerid !== 'undefined' ? window.playerid : 'undefined',
        // @ts-ignore
        divGameVisible: document.getElementById('divGame')?.style.display
      };
    });

    console.log('\n=== Game State Info ===');
    console.log(JSON.stringify(gameInfo, null, 2));

    console.log('\n=== Console Messages ===');
    consoleMessages.forEach(msg => console.log(msg));

    // Take screenshot
    await page.screenshot({ path: 'tests/screenshots/debug-bot-game.png', fullPage: true });
    console.log('✓ Screenshot saved');

    // Keep browser open for a bit
    await page.waitForTimeout(2000);
  });
});
