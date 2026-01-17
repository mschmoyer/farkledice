import { PrismaClient } from '@prisma/client';
import { PrismaPg } from '@prisma/adapter-pg';
import { Pool } from 'pg';

/**
 * Prisma Client Singleton
 *
 * This prevents multiple instances of Prisma Client in development
 * where hot-reloading can create new instances on each reload.
 *
 * Prisma 7 requires database configuration via adapter or accelerateUrl
 */

const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined;
};

// Create PostgreSQL pool for the adapter
const pool = new Pool({
  connectionString: process.env.DATABASE_URL || 'postgresql://localhost:5432/farkle_dev',
});

const adapter = new PrismaPg(pool);

export const prisma =
  globalForPrisma.prisma ??
  new PrismaClient({
    adapter,
    log: process.env.NODE_ENV === 'development' ? ['query', 'error', 'warn'] : ['error'],
  });

if (process.env.NODE_ENV !== 'production') {
  globalForPrisma.prisma = prisma;
}

export default prisma;
