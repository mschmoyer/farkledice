import { NextResponse } from 'next/server';
import { logger } from '@/lib/logger';

export async function GET() {
  // Test all log levels
  logger.info('Logger test - info level', { testId: 'test-001', timestamp: Date.now() });
  logger.warn('Logger test - warn level', { testId: 'test-002', status: 'warning' });
  logger.error('Logger test - error level', { testId: 'test-003', errorCode: 500 });
  logger.debug('Logger test - debug level', { testId: 'test-004', debug: true });

  return NextResponse.json({
    success: true,
    message: 'Logger test completed. Check console and logs/app-YYYY-MM-DD.log in development mode.',
  });
}
