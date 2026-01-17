import fs from 'fs';
import path from 'path';

const isDev = process.env.NODE_ENV === 'development';
const logDir = path.join(process.cwd(), 'logs');
const logFile = path.join(logDir, `app-${new Date().toISOString().split('T')[0]}.log`);

// Ensure log directory exists in dev
if (isDev && !fs.existsSync(logDir)) {
  fs.mkdirSync(logDir, { recursive: true });
}

type LogLevel = 'info' | 'warn' | 'error' | 'debug';

export function log(level: LogLevel, message: string, meta?: object) {
  const timestamp = new Date().toISOString();
  const logEntry = {
    timestamp,
    level,
    message,
    ...meta,
  };

  const logLine = JSON.stringify(logEntry) + '\n';

  // Always log to console
  console[level === 'debug' ? 'log' : level](logLine);

  // Write to file only in development
  if (isDev) {
    fs.appendFileSync(logFile, logLine);
  }
}

export const logger = {
  info: (msg: string, meta?: object) => log('info', msg, meta),
  warn: (msg: string, meta?: object) => log('warn', msg, meta),
  error: (msg: string, meta?: object) => log('error', msg, meta),
  debug: (msg: string, meta?: object) => log('debug', msg, meta),
};

/**
 * Example Usage:
 *
 * In API routes:
 * ```typescript
 * import { logger } from '@/lib/logger';
 *
 * export async function POST(request: Request) {
 *   try {
 *     const body = await request.json();
 *     logger.info('Game created', { gameId: body.gameId, playerId: body.playerId });
 *     // ... rest of your code
 *   } catch (error) {
 *     logger.error('Failed to create game', { error: error.message });
 *     return NextResponse.json({ error: 'Internal error' }, { status: 500 });
 *   }
 * }
 * ```
 *
 * In Server Components:
 * ```typescript
 * import { logger } from '@/lib/logger';
 *
 * export default async function LobbyPage() {
 *   logger.info('Lobby page rendered');
 *
 *   try {
 *     const games = await getGamesForUser();
 *     logger.debug('Fetched games', { count: games.length });
 *     // ... render component
 *   } catch (error) {
 *     logger.error('Failed to fetch games', { error: error.message });
 *   }
 * }
 * ```
 *
 * Log levels:
 * - logger.info() - General information, important events
 * - logger.warn() - Warning conditions that should be reviewed
 * - logger.error() - Error conditions requiring attention
 * - logger.debug() - Detailed debug information for development
 *
 * Log output:
 * - Development: Logs written to both console AND logs/app-YYYY-MM-DD.log
 * - Production: Logs written to console only (Heroku handles log aggregation)
 */
