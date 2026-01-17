'use client';

import { useRouter } from 'next/navigation';
import { useEffect } from 'react';
import { Container, Stack, Badge, Button, Title, Text, Group } from '@mantine/core';
import { PlayerCard } from '@/components/game/PlayerCard';
import { GameCard } from '@/components/game/GameCard';
import { LobbyIdleState } from '@/components/game/LobbyIdleState';
import { useLobbyPolling } from '@/hooks/useLobbyPolling';
import { useAuth } from '@/hooks/useAuth';
import { getMockLobbyData } from '@/lib/mockData';
import styles from './lobby.module.css';

export default function LobbyPage() {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  // Redirect to login if not authenticated
  useEffect(() => {
    if (!isLoading && !user) {
      router.push('/login');
    }
  }, [user, isLoading, router]);

  // Get mock data (will be replaced by real API in task-012)
  const lobbyData = getMockLobbyData();
  const { player, games, hasActiveTournament, isDoubleXP } = lobbyData;

  // Set up polling with mock data handler
  const { isIdle, refresh, resume } = useLobbyPolling({
    enabled: !!user,
    onPoll: async () => {
      // In task-012, this will call the real API endpoint
      // For now, just return mock data
      return getMockLobbyData();
    },
  });

  // Show loading state while checking auth
  if (isLoading) {
    return (
      <Container size="md" py={20}>
        <Text ta="center">Loading...</Text>
      </Container>
    );
  }

  // Show nothing if not authenticated (will redirect)
  if (!user) {
    return null;
  }

  // Show idle state if polling has stopped
  if (isIdle) {
    return (
      <div className={styles.lobbyContainer}>
        <Container size="md" py={20}>
          <LobbyIdleState onResume={resume} />
        </Container>
      </div>
    );
  }

  return (
    <div className={styles.lobbyContainer}>
      <Container size="md" py={20}>
        <Stack gap="md">
          {/* Player Card Section */}
          <div className={styles.playerSection}>
            <PlayerCard {...player} />
          </div>

          {/* Double XP Badge (conditional) */}
          {isDoubleXP && (
            <div className={styles.doubleXpBadge}>
              <Badge
                size="lg"
                variant="gradient"
                gradient={{ from: 'yellow', to: 'orange', deg: 90 }}
                leftSection="⚡"
              >
                DOUBLE XP ACTIVE
              </Badge>
            </div>
          )}

          {/* Active Games Section */}
          <div className={styles.gamesSection}>
            <Title order={2} className={styles.gamesSectionTitle}>
              Active Games
            </Title>

            {games.length > 0 ? (
              <div className={styles.gamesList}>
                {games.map((game) => (
                  <GameCard
                    key={game.id}
                    gameId={game.gameId}
                    opponents={game.opponents}
                    status={game.status}
                    mode={game.mode}
                    currentPlayer={game.currentPlayer}
                    statusMessage={game.statusMessage}
                    onClick={() => {
                      refresh();
                      // Will navigate to game page in future task
                      console.log('Navigate to game:', game.id);
                    }}
                  />
                ))}
              </div>
            ) : (
              <Text className={styles.emptyGamesMessage}>
                No active games. Start a new game to begin playing!
              </Text>
            )}
          </div>

          {/* New Game Button */}
          <div className={styles.newGameSection}>
            <Button
              size="lg"
              className={styles.newGameButton}
              onClick={() => {
                // Refresh polling on user interaction
                refresh();
                // Navigate to new game page
                router.push('/new-game');
              }}
            >
              NEW GAME
            </Button>
          </div>

          {/* Navigation Buttons */}
          <div className={styles.navigationSection}>
            <Group className={styles.navigationButtons}>
              <Button
                variant="default"
                className={styles.navButton}
                onClick={() => {
                  refresh();
                  // Will navigate to profile page in future task
                  console.log('Navigate to profile');
                }}
              >
                Profile
              </Button>
              <Button
                variant="default"
                className={styles.navButton}
                onClick={() => {
                  refresh();
                  // Will navigate to friends page in future task
                  console.log('Navigate to friends');
                }}
              >
                Friends
              </Button>
              <Button
                variant="default"
                className={styles.navButton}
                onClick={() => {
                  refresh();
                  // Will navigate to leaderboard page in future task
                  console.log('Navigate to leaderboard');
                }}
              >
                Leaderboard
              </Button>
            </Group>
          </div>

          {/* Tournament Button (conditional) */}
          {hasActiveTournament && (
            <div className={styles.tournamentSection}>
              <Button
                size="lg"
                className={styles.tournamentButton}
                onClick={() => {
                  refresh();
                  // Will navigate to tournament page in future task
                  console.log('Navigate to tournament');
                }}
              >
                ACTIVE TOURNAMENT
              </Button>
            </div>
          )}
        </Stack>
      </Container>
    </div>
  );
}
