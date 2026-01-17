import { PrismaClient } from '@prisma/client';
import { PrismaPg } from '@prisma/adapter-pg';
import pg from 'pg';

const connectionString = process.env.DATABASE_URL || 'postgresql://farkle:farkle_dev_password@localhost:5432/farkle_dev';
const pool = new pg.Pool({ connectionString });
const adapter = new PrismaPg(pool);
const prisma = new PrismaClient({ adapter });

async function main() {
  console.log('🌱 Seeding database...');

  // Create some sample achievements
  const achievements = [
    {
      name: 'First Game',
      description: 'Complete your first game',
      points: 10,
      category: 'Getting Started',
      threshold: 1,
    },
    {
      name: 'Hot Streak',
      description: 'Roll hot dice 5 times in a row',
      points: 25,
      category: 'Skill',
      threshold: 5,
    },
    {
      name: 'Century Club',
      description: 'Reach level 100',
      points: 100,
      category: 'Progress',
      threshold: 100,
    },
  ];

  for (const ach of achievements) {
    await prisma.achievement.upsert({
      where: { name: ach.name },
      update: {},
      create: ach,
    });
  }

  console.log('✅ Created achievements');
  console.log('🎉 Seeding complete!');
  console.log('\n📝 Note: User accounts will be created automatically when users log in via Auth0');
}

main()
  .catch((e) => {
    console.error('❌ Seeding failed:', e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
    await pool.end();
  });
