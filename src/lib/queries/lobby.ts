/**
 * Database queries for lobby data
 *
 * Functions for fetching player information and active games
 * to display in the lobby page.
 */

import { prisma } from '@/lib/prisma';
import { GameMode, GameType, GameStatus } from '@prisma/client';

/**
 * Player data returned for lobby display
 */
export interface LobbyPlayerData {
  id: string;
  username: string;
  level: number;
  xp: number;
  title: string | null;
  achievementScore: number;
  picture: string | null;
}

/**
 * Game data returned for lobby display
 */
export interface LobbyGameData {
  id: string;
  mode: GameMode;
  type: GameType;
  status: GameStatus;
  currentRound: number;
  currentPlayerId: string | null;
  opponents: string[];
  updatedAt: Date;
}

/**
 * Fetch player data for lobby display
 *
 * @param userId - The user's ID (from auth)
 * @returns Player data or null if not found
 */
export async function getPlayerData(userId: string): Promise<LobbyPlayerData | null> {
  const user = await prisma.user.findUnique({
    where: { id: userId }, // Changed from auth0Id to id
    select: {
      id: true,
      username: true,
      level: true,
      xp: true,
      title: true,
      achievementScore: true,
      picture: true,
    },
  });

  return user;
}

/**
 * Fetch active games for a player
 *
 * Returns games where:
 * - User is a player in the game
 * - Game status is not FINISHED
 * - Ordered by most recent first
 *
 * @param userId - The user's internal ID (not auth0Id)
 * @returns Array of game data
 */
export async function getActiveGames(userId: string): Promise<LobbyGameData[]> {
  // First, get all games where the user is a player and game is not finished
  const gamePlayers = await prisma.gamePlayer.findMany({
    where: {
      userId: userId,
      game: {
        status: {
          not: GameStatus.FINISHED,
        },
      },
    },
    include: {
      game: {
        include: {
          players: {
            include: {
              user: {
                select: {
                  id: true,
                  username: true,
                },
              },
            },
            orderBy: {
              playerNumber: 'asc',
            },
          },
        },
      },
    },
    orderBy: {
      game: {
        updatedAt: 'desc',
      },
    },
  });

  // Transform the data into the format expected by the lobby
  const games: LobbyGameData[] = gamePlayers.map((gp) => {
    const game = gp.game;

    // Get opponents (all players except the current user)
    const opponents = game.players
      .filter((p) => p.userId !== userId)
      .map((p) => p.user.username);

    // Determine current player - find the player whose turn it is
    // For now, we'll use simple round-robin logic based on currentRound
    // This will be refined when game logic is fully implemented
    let currentPlayerId: string | null = null;
    if (game.status === GameStatus.ACTIVE && game.players.length > 0) {
      // Simple round-robin: current player index = (currentRound - 1) % playerCount
      const currentPlayerIndex = 0; // Simplified for now - will be determined by actual game state
      currentPlayerId = game.players[currentPlayerIndex]?.userId || null;
    }

    // Get current round from the user's GamePlayer record
    const currentRound = gp.currentRound;

    return {
      id: game.id,
      mode: game.mode,
      type: game.type,
      status: game.status,
      currentRound: currentRound,
      currentPlayerId: currentPlayerId,
      opponents: opponents,
      updatedAt: game.updatedAt,
    };
  });

  return games;
}

/**
 * Get game status string for display
 *
 * Determines the display status based on game state and current player
 *
 * @param game - Game data
 * @param currentUserId - Current user's ID
 * @returns Status string: 'your-turn' | 'waiting' | 'finished'
 */
export function getGameStatusDisplay(
  game: LobbyGameData,
  currentUserId: string
): 'your-turn' | 'waiting' | 'finished' {
  if (game.status === GameStatus.FINISHED) {
    return 'finished';
  }

  if (game.status === GameStatus.WAITING) {
    return 'waiting';
  }

  // Active game - check if it's user's turn
  if (game.currentPlayerId === currentUserId) {
    return 'your-turn';
  }

  return 'waiting';
}
