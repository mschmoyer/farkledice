/**
 * Game Types and Interfaces
 */

export type GameMode = 'ten_round' | 'standard';
export type GameType = 'random' | 'friend' | 'practice';
export type PlayerCount = 2 | 4;

export interface GameConfiguration {
  type: GameType;
  mode: GameMode;
  playerCount?: PlayerCount; // For random games
  friendIds?: string[]; // For friend games
  targetScore?: number; // For standard mode (2500, 5000, 10000)
  breakInScore?: number; // For standard mode (0, 250, 500, 1000)
}

export interface Friend {
  id: string;
  username: string;
  level: number;
  avatarUrl?: string;
}
