/**
 * Lobby API Endpoint
 *
 * GET /api/lobby
 *
 * Returns player data and active games for the lobby page.
 * Requires authentication via Auth0.
 *
 * REQ-015: List all active games for the player
 * REQ-022: Auto-refresh lobby data via polling
 */

import { NextResponse } from 'next/server';
import { auth0 } from '@/lib/auth0';
import { getPlayerData, getActiveGames, getGameStatusDisplay } from '@/lib/queries/lobby';
import { getOrCreateUser } from '@/lib/queries/users';
import { getXPForLevel } from '@/lib/game/xp';
import { logger } from '@/lib/logger';

export async function GET() {
  try {
    // Get the authenticated user session
    const session = await auth0.getSession();

    if (!session || !session.user) {
      logger.warn('Lobby API: Unauthorized access attempt');
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const auth0UserId = session.user.sub;
    logger.debug('Lobby API: Fetching data', { auth0UserId });

    // Get or create user (auto-creates on first login)
    const user = await getOrCreateUser({
      sub: session.user.sub,
      name: session.user.name,
      email: session.user.email,
      picture: session.user.picture,
    });

    // Fetch player data from database using database user ID
    const player = await getPlayerData(user.id);

    if (!player) {
      logger.error('Lobby API: Player data retrieval failed', { auth0UserId, userId: user.id });
      return NextResponse.json({ error: 'Player not found' }, { status: 404 });
    }

    // Calculate XP to next level
    const xpToLevel = getXPForLevel(player.level + 1);

    // Fetch active games for the player
    const activeGames = await getActiveGames(player.id);

    // Transform games into the expected format
    const games = activeGames.map((game) => ({
      id: game.id,
      mode: game.mode.toLowerCase().replace('_', '-'), // TEN_ROUND -> ten-round
      type: game.type.toLowerCase(), // RANDOM -> random
      status: getGameStatusDisplay(game, player.id),
      opponents: game.opponents,
      currentPlayer: game.currentPlayerId,
      currentRound: game.currentRound,
    }));

    // Check for active tournaments (placeholder - will be implemented later)
    const hasActiveTournament = false;

    // Check for double XP events (placeholder - will be implemented later)
    const isDoubleXP = false;

    // Build response
    const response = {
      player: {
        id: player.id,
        username: player.username,
        level: player.level,
        xp: player.xp,
        xpToLevel: xpToLevel,
        title: player.title,
        achievementScore: player.achievementScore,
        picture: player.picture,
      },
      games: games,
      hasActiveTournament: hasActiveTournament,
      isDoubleXP: isDoubleXP,
    };

    logger.info('Lobby API: Successfully fetched lobby data', {
      playerId: player.id,
      username: player.username,
      gameCount: games.length,
    });

    return NextResponse.json(response);
  } catch (error) {
    logger.error('Lobby API: Error fetching lobby data', {
      error: error instanceof Error ? error.message : 'Unknown error',
      stack: error instanceof Error ? error.stack : undefined,
    });

    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}
