'use client';

import { Card, Text, Group, Badge, Avatar, Stack } from '@mantine/core';
import styles from './GameCard.module.css';

export type GameStatus = 'your-turn' | 'not-started' | 'waiting' | 'finished';
export type GameMode = '10-round' | 'standard';

export interface GameCardProps {
  gameId: string | number;
  opponents: string[];
  status: GameStatus;
  mode: GameMode;
  currentPlayer?: string;
  statusMessage?: string;
  onClick?: () => void;
}

export function GameCard({
  gameId,
  opponents,
  status,
  mode,
  currentPlayer,
  statusMessage,
  onClick
}: GameCardProps) {
  // Get status color based on game status
  const getStatusColor = (): string => {
    switch (status) {
      case 'your-turn':
        return '#ff8c00'; // Orange: Your turn / waiting on you
      case 'not-started':
        return '#cc5500'; // Dark Orange: Nobody has played yet
      case 'waiting':
        return '#666'; // Gray: Waiting on others
      case 'finished':
        return '#4299e1'; // Blue: Game finished (unacknowledged)
      default:
        return '#666';
    }
  };

  // Get status text if not provided
  const getStatusText = (): string => {
    if (statusMessage) return statusMessage;

    switch (status) {
      case 'your-turn':
        return 'Your Turn';
      case 'not-started':
        return 'Nobody has rolled yet';
      case 'waiting':
        return currentPlayer ? `Waiting for ${currentPlayer}` : 'Waiting on others...';
      case 'finished':
        return 'Game finished!';
      default:
        return 'Game in progress...';
    }
  };

  // Format opponent list (show up to 3, then "+X more")
  const getOpponentDisplay = (): string => {
    if (opponents.length === 0) return 'Unknown Opponents';
    if (opponents.length <= 3) return opponents.join(', ');

    const displayNames = opponents.slice(0, 3).join(', ');
    const remaining = opponents.length - 3;
    return `${displayNames}, +${remaining} more`;
  };

  const statusColor = getStatusColor();
  const statusText = getStatusText();
  const opponentDisplay = getOpponentDisplay();

  return (
    <Card
      shadow="sm"
      padding="md"
      radius="md"
      className={styles.gameCard}
      style={{
        backgroundColor: statusColor,
        cursor: onClick ? 'pointer' : 'default'
      }}
      onClick={onClick}
    >
      <Group justify="space-between" wrap="nowrap">
        <Stack gap={4} style={{ flex: 1, minWidth: 0 }}>
          <Group gap="xs" wrap="nowrap">
            <Avatar.Group>
              {opponents.slice(0, 3).map((opponent, index) => (
                <Avatar
                  key={index}
                  size={28}
                  radius="xl"
                  src={null}
                  alt={opponent}
                  className={styles.opponentAvatar}
                >
                  {opponent.charAt(0).toUpperCase()}
                </Avatar>
              ))}
            </Avatar.Group>

            <Text
              size="md"
              fw={500}
              className={styles.opponentText}
              style={{
                overflow: 'hidden',
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap'
              }}
            >
              {opponentDisplay}
            </Text>
          </Group>

          <Text size="sm" className={styles.statusText}>
            {statusText}
          </Text>
        </Stack>

        <Badge
          size="md"
          variant="filled"
          color="dark"
          className={styles.modeBadge}
        >
          {mode === '10-round' ? '10-Round' : 'Standard'}
        </Badge>
      </Group>
    </Card>
  );
}
