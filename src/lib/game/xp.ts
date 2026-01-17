/**
 * XP (Experience Points) calculation utilities
 *
 * XP system for player progression and leveling.
 * Based on REQ-GAME-016: Players earn XP from games.
 * Level 2 requires 40 XP, with gradual increase for higher levels.
 */

/**
 * Calculate XP required to reach a specific level
 *
 * Formula: Level 2 = 40 XP, with progressive scaling for higher levels
 * - Level 2: 40 XP
 * - Level 3: 80 XP (40 + 40)
 * - Level 4: 130 XP (80 + 50)
 * - Level 5: 190 XP (130 + 60)
 * - And so on, with +10 XP increment per level
 *
 * @param level - The target level (must be >= 1)
 * @returns Total XP required to reach that level
 */
export function getXPForLevel(level: number): number {
  if (level <= 1) {
    return 0;
  }

  if (level === 2) {
    return 40;
  }

  // Calculate cumulative XP for levels 2+
  let totalXP = 40; // Base XP for level 2
  let incrementPerLevel = 40; // Starting increment

  for (let i = 3; i <= level; i++) {
    incrementPerLevel += 10; // Increase by 10 for each subsequent level
    totalXP += incrementPerLevel;
  }

  return totalXP;
}

/**
 * Calculate XP needed to reach the next level from current XP
 *
 * @param currentLevel - Player's current level
 * @param currentXP - Player's current XP
 * @returns XP needed to reach next level
 */
export function getXPToNextLevel(currentLevel: number, currentXP: number): number {
  const xpForNextLevel = getXPForLevel(currentLevel + 1);
  return xpForNextLevel - currentXP;
}

/**
 * Calculate player's level based on current XP
 *
 * @param xp - Current XP amount
 * @returns Player's level
 */
export function getLevelFromXP(xp: number): number {
  if (xp < 40) {
    return 1;
  }

  let level = 1;
  while (getXPForLevel(level + 1) <= xp) {
    level++;
  }

  return level;
}

/**
 * XP rewards based on game performance
 * These values match the v1 Farkle system
 */
export const XP_REWARDS = {
  WIN_GAME: 10,
  COMPLETE_GAME: 5,
  FORFEIT_GAME: -5,
} as const;
