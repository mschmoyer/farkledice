'use client';

import { useAuth } from '@/hooks/useAuth';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { Container, Text } from '@mantine/core';
import { NewGameForm } from '@/components/game/NewGameForm';
import { GameConfiguration } from '@/types/game';
import styles from './new-game.module.css';

export default function NewGamePage() {
  const { user, isLoading } = useAuth();
  const router = useRouter();
  const [isCreating, setIsCreating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Redirect to login if not authenticated
  useEffect(() => {
    if (!isLoading && !user) {
      router.push('/login');
    }
  }, [user, isLoading, router]);

  const handleCreateGame = async (config: GameConfiguration) => {
    setIsCreating(true);
    setError(null);

    try {
      const response = await fetch('/api/games', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(config),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to create game');
      }

      // Success - redirect to lobby with success state
      router.push('/lobby?created=true');
    } catch (err) {
      console.error('Error creating game:', err);
      setError(err instanceof Error ? err.message : 'Failed to create game');
      setIsCreating(false);
    }
  };

  const handleCancel = () => {
    router.push('/lobby');
  };

  // Show loading state while checking auth
  if (isLoading) {
    return (
      <div className={styles.pageContainer}>
        <Container size="md" py={20}>
          <Text ta="center" c="white">
            Loading...
          </Text>
        </Container>
      </div>
    );
  }

  // Show nothing if not authenticated (will redirect)
  if (!user) {
    return null;
  }

  return (
    <div className={styles.pageContainer}>
      <Container size="md" py={20}>
        <div className={styles.pageHeader}>
          <Text size="xl" fw={700} className={styles.pageTitle}>
            Create New Game
          </Text>
        </div>

        {error && (
          <div style={{ marginBottom: '20px', padding: '10px', backgroundColor: '#ff4444', color: 'white', borderRadius: '4px' }}>
            Error: {error}
          </div>
        )}

        <div className={styles.formWrapper}>
          <NewGameForm
            onSubmit={handleCreateGame}
            onCancel={handleCancel}
            friends={[]}
            disabled={isCreating}
          />
        </div>

        {isCreating && (
          <div style={{ marginTop: '20px', textAlign: 'center' }}>
            <Text c="white">Creating game...</Text>
          </div>
        )}
      </Container>
    </div>
  );
}
