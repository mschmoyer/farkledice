import { prisma } from '@/lib/prisma';

/**
 * Get or create a user from Auth0 profile
 * This is called on every authenticated API request to ensure the user exists
 */
export async function getOrCreateUser(auth0User: {
  sub: string;
  name?: string;
  email?: string;
  picture?: string;
}) {
  // Try to find existing user
  let user = await prisma.user.findUnique({
    where: { auth0Id: auth0User.sub },
    include: { stats: true },
  });

  // If user doesn't exist, create them
  if (!user) {
    // Generate username from Auth0 name or email
    let username = auth0User.name || auth0User.email?.split('@')[0] || 'Player';

    // Ensure username is unique
    const existingUser = await prisma.user.findUnique({
      where: { username },
    });

    if (existingUser) {
      // Add random number to make it unique
      username = `${username}${Math.floor(Math.random() * 10000)}`;
    }

    // Create user with stats in a transaction
    user = await prisma.$transaction(async (tx) => {
      const newUser = await tx.user.create({
        data: {
          auth0Id: auth0User.sub,
          username,
          email: auth0User.email || null,
          picture: auth0User.picture || null,
          level: 1,
          xp: 0,
          title: null,
          achievementScore: 0,
        },
      });

      // Create initial stats
      await tx.userStats.create({
        data: {
          userId: newUser.id,
          gamesPlayed: 0,
          gamesWon: 0,
          totalScore: 0,
          highestRound: 0,
          highestGame: 0,
          totalFarkles: 0,
        },
      });

      // Return user with stats
      return tx.user.findUniqueOrThrow({
        where: { id: newUser.id },
        include: { stats: true },
      });
    });
  }

  return user;
}

/**
 * Update user's last login timestamp
 */
export async function updateLastLogin(userId: string) {
  await prisma.user.update({
    where: { id: userId },
    data: { updatedAt: new Date() },
  });
}
