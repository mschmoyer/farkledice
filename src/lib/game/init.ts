/**
 * Game Initialization Helpers
 *
 * Functions for setting up new games with appropriate defaults
 * and calculating game settings based on mode and type.
 */

import { GameMode } from '@/types/game';

/**
 * Default game settings based on game mode
 */
export interface GameSettings {
  targetScore: number | null;
  breakInScore: number;
  expirationDays: number;
  roundCount: number | null;
}

/**
 * Get default game settings for a game mode
 *
 * @param mode - The game mode (ten_round or standard)
 * @returns Default settings for the mode
 */
export function getDefaultGameSettings(mode: GameMode): GameSettings {
  switch (mode) {
    case 'ten_round':
      return {
        targetScore: null, // 10-Round mode doesn't use target scores
        breakInScore: 0, // 10-Round mode doesn't use break-in
        expirationDays: 3, // 3 days to complete all rounds
        roundCount: 10,
      };

    case 'standard':
      return {
        targetScore: 10000, // Default to 10,000 points
        breakInScore: 500, // Default break-in
        expirationDays: 3, // 3 days of inactivity
        roundCount: null, // Standard mode doesn't have fixed rounds
      };

    default:
      throw new Error(`Unknown game mode: ${mode}`);
  }
}

/**
 * Calculate game expiration timestamp
 *
 * Games expire after N days of inactivity. This calculates
 * the initial expiration time when the game is created.
 *
 * @param mode - The game mode
 * @returns Date object representing when game expires
 */
export function calculateExpiration(mode: GameMode): Date {
  const settings = getDefaultGameSettings(mode);
  const now = new Date();
  const expirationDate = new Date(now.getTime() + settings.expirationDays * 24 * 60 * 60 * 1000);
  return expirationDate;
}

/**
 * Validate game configuration values
 *
 * @param mode - Game mode
 * @param targetScore - Target score (for Standard mode)
 * @param breakInScore - Break-in score (for Standard mode)
 * @returns Validation error message or null if valid
 */
export function validateGameConfig(
  mode: GameMode,
  targetScore?: number,
  breakInScore?: number
): string | null {
  if (mode === 'standard') {
    // Validate target score for Standard mode
    const validTargetScores = [2500, 5000, 10000];
    if (targetScore && !validTargetScores.includes(targetScore)) {
      return `Invalid target score. Must be one of: ${validTargetScores.join(', ')}`;
    }

    // Validate break-in score for Standard mode
    const validBreakInScores = [0, 250, 500, 1000];
    if (breakInScore !== undefined && !validBreakInScores.includes(breakInScore)) {
      return `Invalid break-in score. Must be one of: ${validBreakInScores.join(', ')}`;
    }
  }

  return null;
}
