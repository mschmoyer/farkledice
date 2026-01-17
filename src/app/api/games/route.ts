/**
 * Games API Endpoint
 *
 * POST /api/games - Create a new game
 *
 * Handles game creation based on GameConfiguration from the New Game form.
 * Supports random matchmaking, friend games, and solo practice.
 *
 * REQ-024: Support creating 10-Round mode games
 * REQ-025: Support creating Standard mode games
 * REQ-026: Allow random game matchmaking (2 or 4 players)
 * REQ-027: Allow friend game creation with friend selection
 * REQ-028: Allow solo practice games
 * REQ-029: Configure game options (break-in, points to win for Standard)
 */

import { NextResponse } from 'next/server';
import { auth0 } from '@/lib/auth0';
import { getPlayerData } from '@/lib/queries/lobby';
import { getOrCreateUser } from '@/lib/queries/users';
import { createGame } from '@/lib/queries/games';
import { validateGameConfig } from '@/lib/game/init';
import { logger } from '@/lib/logger';
import { GameConfiguration } from '@/types/game';

/**
 * POST /api/games
 *
 * Create a new game
 *
 * Request body: GameConfiguration
 * Response: { success: true, gameId: string, message: string }
 * Error: { error: string, details?: string }
 */
export async function POST(request: Request) {
  try {
    // Check authentication
    const session = await auth0.getSession();

    if (!session || !session.user) {
      logger.warn('Games API: Unauthorized game creation attempt');
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const auth0UserId = session.user.sub;
    logger.info('Games API: Game creation request', { auth0UserId });

    // Get or create user (auto-creates on first login)
    const user = await getOrCreateUser({
      sub: session.user.sub,
      name: session.user.name,
      email: session.user.email,
      picture: session.user.picture,
    });

    // Get player data from database using database user ID
    const player = await getPlayerData(user.id);

    if (!player) {
      logger.error('Games API: Player data retrieval failed', { auth0UserId, userId: user.id });
      return NextResponse.json(
        { error: 'Player not found', details: 'User account not properly set up' },
        { status: 404 }
      );
    }

    // Parse and validate request body
    let config: GameConfiguration;
    try {
      config = await request.json();
    } catch (parseError) {
      logger.warn('Games API: Invalid JSON in request body');
      return NextResponse.json(
        { error: 'Invalid request body', details: 'Request body must be valid JSON' },
        { status: 400 }
      );
    }

    // Validate required fields
    if (!config.type || !config.mode) {
      logger.warn('Games API: Missing required fields', { config });
      return NextResponse.json(
        { error: 'Invalid game configuration', details: 'Missing required fields: type, mode' },
        { status: 400 }
      );
    }

    // Validate game type
    const validTypes = ['random', 'friend', 'practice'];
    if (!validTypes.includes(config.type)) {
      logger.warn('Games API: Invalid game type', { type: config.type });
      return NextResponse.json(
        { error: 'Invalid game configuration', details: `Invalid game type: ${config.type}` },
        { status: 400 }
      );
    }

    // Validate game mode
    const validModes = ['ten_round', 'standard'];
    if (!validModes.includes(config.mode)) {
      logger.warn('Games API: Invalid game mode', { mode: config.mode });
      return NextResponse.json(
        { error: 'Invalid game configuration', details: `Invalid game mode: ${config.mode}` },
        { status: 400 }
      );
    }

    // Validate type-specific requirements
    if (config.type === 'random') {
      const validPlayerCounts = [2, 4];
      if (config.playerCount && !validPlayerCounts.includes(config.playerCount)) {
        logger.warn('Games API: Invalid player count for random game', { playerCount: config.playerCount });
        return NextResponse.json(
          { error: 'Invalid game configuration', details: 'Player count must be 2 or 4 for random games' },
          { status: 400 }
        );
      }
    } else if (config.type === 'friend') {
      if (!config.friendIds || config.friendIds.length === 0) {
        logger.warn('Games API: No friends selected for friend game');
        return NextResponse.json(
          { error: 'Invalid game configuration', details: 'Must select at least one friend for friend games' },
          { status: 400 }
        );
      }

      // Validate max players based on mode
      const maxPlayers = config.mode === 'ten_round' ? 32 : 6;
      if (config.friendIds.length > maxPlayers - 1) {
        // -1 because creator is also a player
        logger.warn('Games API: Too many friends selected', { count: config.friendIds.length, max: maxPlayers });
        return NextResponse.json(
          {
            error: 'Invalid game configuration',
            details: `Maximum ${maxPlayers} players allowed for ${config.mode} mode`,
          },
          { status: 400 }
        );
      }
    } else if (config.type === 'practice') {
      // Solo practice must use 10-Round mode
      if (config.mode !== 'ten_round') {
        logger.warn('Games API: Invalid mode for practice game', { mode: config.mode });
        return NextResponse.json(
          { error: 'Invalid game configuration', details: 'Practice games must use 10-Round mode' },
          { status: 400 }
        );
      }
    }

    // Validate game configuration values
    const configError = validateGameConfig(config.mode, config.targetScore, config.breakInScore);
    if (configError) {
      logger.warn('Games API: Invalid game configuration values', { config, error: configError });
      return NextResponse.json(
        { error: 'Invalid game configuration', details: configError },
        { status: 400 }
      );
    }

    // Create the game
    const result = await createGame(config, player.id);

    if (!result.success || !result.gameId) {
      logger.error('Games API: Game creation failed', { playerId: player.id, error: result.error });
      return NextResponse.json(
        { error: 'Failed to create game', details: result.error || 'Unknown error' },
        { status: 500 }
      );
    }

    logger.info('Games API: Game created successfully', {
      gameId: result.gameId,
      playerId: player.id,
      username: player.username,
      type: config.type,
      mode: config.mode,
    });

    // Return success response
    return NextResponse.json(
      {
        success: true,
        gameId: result.gameId,
        message: 'Game created successfully',
      },
      { status: 201 }
    );
  } catch (error) {
    logger.error('Games API: Unexpected error', {
      error: error instanceof Error ? error.message : 'Unknown error',
      stack: error instanceof Error ? error.stack : undefined,
    });

    return NextResponse.json(
      { error: 'Internal server error', details: 'An unexpected error occurred' },
      { status: 500 }
    );
  }
}
