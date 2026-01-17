'use client';

import { Card, Avatar, Badge, Text, Progress, Group, Stack, Box } from '@mantine/core';
import styles from './PlayerCard.module.css';

export interface PlayerCardProps {
  username: string;
  level: number;
  title?: string;
  achievementScore: number;
  xp: number;
  xpToLevel: number;
  picture?: string;
  cardColor?: string;
}

export function PlayerCard({
  username,
  level,
  title,
  achievementScore,
  xp,
  xpToLevel,
  picture,
  cardColor = 'green'
}: PlayerCardProps) {
  const xpPercent = Math.min((xp / xpToLevel) * 100, 100);
  const xpColor = xpPercent >= 80 ? 'green' : xpPercent >= 50 ? 'blue' : 'gray';

  return (
    <Card
      shadow="sm"
      padding="md"
      radius="md"
      className={styles.playerCard}
      style={{
        backgroundColor: cardColor.endsWith('.png') ? undefined : cardColor,
        backgroundImage: cardColor.endsWith('.png') ? `url(/images/playericons/${cardColor})` : undefined,
        backgroundSize: 'cover'
      }}
    >
      <Group gap="md" wrap="nowrap">
        <Avatar
          src={picture || '/images/stock.png'}
          size={50}
          radius="sm"
          className={styles.playerAvatar}
        />

        <Stack gap={4} style={{ flex: 1 }}>
          <Group gap={8} wrap="nowrap">
            <Badge
              size="sm"
              variant="filled"
              color="green"
              className={styles.levelBadge}
            >
              {level}
            </Badge>
            <Text size="md" fw={500} className={styles.playerName}>
              {username}
            </Text>
          </Group>

          {title && (
            <Text size="xs" c="dimmed" className={styles.playerTitle}>
              {title}
            </Text>
          )}

          <Text size="xs" className={styles.achievementScore}>
            Achievement Score: {achievementScore.toLocaleString()}
          </Text>
        </Stack>
      </Group>

      <Box mt="md">
        <Group justify="space-between" mb={4}>
          <Text size="xs" c="dimmed">
            XP Progress
          </Text>
          <Text size="xs" c="dimmed">
            {xp.toLocaleString()} / {xpToLevel.toLocaleString()}
          </Text>
        </Group>
        <Progress
          value={xpPercent}
          color={xpColor}
          size="md"
          radius="sm"
          className={styles.xpBar}
          animated={xpPercent >= 80}
        />
      </Box>
    </Card>
  );
}
