/**
 * Database queries for game operations
 *
 * Functions for creating games, managing players, and game data access
 */

import { prisma } from '@/lib/prisma';
import { GameMode, GameType, GameStatus, PlayerStatus } from '@prisma/client';
import { GameConfiguration } from '@/types/game';
import { logger } from '@/lib/logger';
import { calculateExpiration, getDefaultGameSettings } from '@/lib/game/init';

/**
 * Game creation result
 */
export interface CreateGameResult {
  success: boolean;
  gameId?: string;
  error?: string;
}

/**
 * Create a new game
 *
 * @param config - Game configuration from the New Game form
 * @param creatorId - Internal user ID of the game creator
 * @returns Game creation result with gameId or error
 */
export async function createGame(
  config: GameConfiguration,
  creatorId: string
): Promise<CreateGameResult> {
  try {
    logger.info('Creating new game', { config, creatorId });

    // Convert frontend types to Prisma enums
    const mode = config.mode === 'ten_round' ? GameMode.TEN_ROUND : GameMode.STANDARD;
    const type = config.type === 'random' ? GameType.RANDOM :
                 config.type === 'friend' ? GameType.FRIEND :
                 GameType.PRACTICE;

    // Get default settings and apply overrides
    const defaults = getDefaultGameSettings(config.mode);
    const targetScore = config.targetScore ?? defaults.targetScore;
    const breakInScore = config.breakInScore ?? defaults.breakInScore;
    const expiresAt = calculateExpiration(config.mode);

    // Use a transaction to create game and add players atomically
    const result = await prisma.$transaction(async (tx) => {
      // Create the game record
      const game = await tx.game.create({
        data: {
          mode,
          type,
          status: GameStatus.WAITING, // Will be updated to ACTIVE when all players join
          targetScore,
          breakInScore,
          expiresAt,
        },
      });

      logger.debug('Game record created', { gameId: game.id, mode, type });

      // Add the creator as the first player
      await tx.gamePlayer.create({
        data: {
          gameId: game.id,
          userId: creatorId,
          playerNumber: 1,
          score: 0,
          currentRound: 1,
          status: PlayerStatus.ACTIVE,
        },
      });

      let playerIds: string[] = [creatorId];

      // Handle different game types
      if (type === GameType.FRIEND && config.friendIds && config.friendIds.length > 0) {
        // Add selected friends as players
        playerIds = [creatorId, ...config.friendIds];

        for (let i = 0; i < config.friendIds.length; i++) {
          await tx.gamePlayer.create({
            data: {
              gameId: game.id,
              userId: config.friendIds[i],
              playerNumber: i + 2, // Creator is 1, friends start at 2
              score: 0,
              currentRound: 1,
              status: PlayerStatus.ACTIVE,
            },
          });
        }

        // All friends are added, set game to ACTIVE
        await tx.game.update({
          where: { id: game.id },
          data: { status: GameStatus.ACTIVE },
        });

        logger.info('Friend game created with all players', { gameId: game.id, playerCount: playerIds.length });
      } else if (type === GameType.RANDOM) {
        // Find random opponents
        const playerCount = config.playerCount ?? 2;
        const opponentCount = playerCount - 1; // Subtract creator

        const opponents = await findRandomOpponents(opponentCount, creatorId, tx);

        if (opponents.length === opponentCount) {
          // Found enough opponents, add them to the game
          playerIds = [creatorId, ...opponents.map((o) => o.id)];

          for (let i = 0; i < opponents.length; i++) {
            await tx.gamePlayer.create({
              data: {
                gameId: game.id,
                userId: opponents[i].id,
                playerNumber: i + 2, // Creator is 1, opponents start at 2
                score: 0,
                currentRound: 1,
                status: PlayerStatus.ACTIVE,
              },
            });
          }

          // All players found, set game to ACTIVE
          await tx.game.update({
            where: { id: game.id },
            data: { status: GameStatus.ACTIVE },
          });

          logger.info('Random game created with all players', { gameId: game.id, playerCount: playerIds.length });
        } else {
          // Not enough opponents yet, game stays in WAITING status
          logger.info('Random game created, waiting for opponents', { gameId: game.id, found: opponents.length, needed: opponentCount });
        }
      } else if (type === GameType.PRACTICE) {
        // Solo practice game - only the creator, set to ACTIVE immediately
        await tx.game.update({
          where: { id: game.id },
          data: { status: GameStatus.ACTIVE },
        });

        logger.info('Practice game created', { gameId: game.id });
      }

      return game.id;
    });

    logger.info('Game creation successful', { gameId: result });

    return {
      success: true,
      gameId: result,
    };
  } catch (error) {
    logger.error('Failed to create game', {
      error: error instanceof Error ? error.message : 'Unknown error',
      stack: error instanceof Error ? error.stack : undefined,
      config,
      creatorId,
    });

    return {
      success: false,
      error: error instanceof Error ? error.message : 'Failed to create game',
    };
  }
}

/**
 * Find random opponents for matchmaking
 *
 * Finds users who:
 * - Are not the creator
 * - Have fewer than 20 active games (not overwhelmed)
 * - Are available for random matchmaking
 *
 * @param count - Number of opponents needed
 * @param excludeUserId - User ID to exclude (the creator)
 * @param tx - Prisma transaction client (optional)
 * @returns Array of user objects (may be less than count if not enough available)
 */
export async function findRandomOpponents(
  count: number,
  excludeUserId: string,
  tx?: any
): Promise<Array<{ id: string; username: string }>> {
  const client = tx ?? prisma;

  try {
    logger.debug('Finding random opponents', { count, excludeUserId });

    // Find users who:
    // 1. Are not the excluded user
    // 2. Have played at least one game (experienced players)
    // 3. Don't have too many active games already
    const candidates = await client.user.findMany({
      where: {
        id: {
          not: excludeUserId,
        },
        games: {
          some: {}, // Has at least one game record
        },
      },
      select: {
        id: true,
        username: true,
        games: {
          where: {
            game: {
              status: {
                in: [GameStatus.WAITING, GameStatus.ACTIVE],
              },
            },
          },
          select: {
            id: true,
          },
        },
      },
      take: count * 3, // Get more candidates than needed for filtering
    });

    // Filter to users with fewer than 20 active games
    const available = candidates
      .filter((user: { id: string; username: string; games: { id: string }[] }) => user.games.length < 20)
      .map((user: { id: string; username: string; games: { id: string }[] }) => ({
        id: user.id,
        username: user.username,
      }))
      .slice(0, count); // Take only what we need

    logger.debug('Found random opponents', { requested: count, found: available.length });

    return available;
  } catch (error) {
    logger.error('Error finding random opponents', {
      error: error instanceof Error ? error.message : 'Unknown error',
      count,
      excludeUserId,
    });
    return [];
  }
}

/**
 * Add players to an existing game
 *
 * Used when additional players join a game after creation
 * (e.g., random matchmaking fulfilled later)
 *
 * @param gameId - Game ID
 * @param playerIds - Array of user IDs to add
 * @returns Success boolean
 */
export async function addPlayersToGame(gameId: string, playerIds: string[]): Promise<boolean> {
  try {
    logger.info('Adding players to game', { gameId, playerIds });

    await prisma.$transaction(async (tx) => {
      // Get current player count
      const existingPlayers = await tx.gamePlayer.count({
        where: { gameId },
      });

      // Add new players with sequential player numbers
      for (let i = 0; i < playerIds.length; i++) {
        await tx.gamePlayer.create({
          data: {
            gameId,
            userId: playerIds[i],
            playerNumber: existingPlayers + i + 1,
            score: 0,
            currentRound: 1,
            status: PlayerStatus.ACTIVE,
          },
        });
      }
    });

    logger.info('Players added successfully', { gameId, count: playerIds.length });
    return true;
  } catch (error) {
    logger.error('Failed to add players to game', {
      error: error instanceof Error ? error.message : 'Unknown error',
      gameId,
      playerIds,
    });
    return false;
  }
}
