import { Card, Title, Text, Button, Stack } from '@mantine/core';
import styles from './LobbyIdleState.module.css';

interface LobbyIdleStateProps {
  onResume: () => void;
}

/**
 * Component displayed when lobby polling goes idle
 * Implements REQ-UI-015: Idle State Handling
 */
export function LobbyIdleState({ onResume }: LobbyIdleStateProps) {
  return (
    <Card className={styles.idleCard} shadow="md" padding="xl" radius="md">
      <Stack align="center" gap="md">
        <Title order={3} className={styles.idleTitle}>
          Lobby is Idle
        </Title>
        <Text className={styles.idleText} ta="center">
          Auto-refresh has been paused due to inactivity.
          <br />
          Click the button below to resume updates.
        </Text>
        <Button
          size="lg"
          className={styles.refreshButton}
          onClick={onResume}
        >
          Refresh Lobby
        </Button>
      </Stack>
    </Card>
  );
}
