import { Container, Title, Text, Stack, Card, Group, Badge } from '@mantine/core';
import { TestButton } from '@/components/shared/TestButton';

export default function Home() {
  return (
    <Container size="md" py={50}>
      <Stack gap="xl">
        <div style={{ textAlign: 'center' }}>
          <Title order={1} size="3rem" fw={900} style={{ color: '#2d5a27' }}>
            Farkle Ten
          </Title>
          <Text size="xl" c="dimmed" mt="md">
            Roll the dice. Beat your friends.
          </Text>
        </div>

        <Card shadow="md" padding="xl" radius="md" withBorder>
          <Stack gap="md">
            <Group justify="space-between">
              <Title order={2} size="h3">
                Stack Setup Complete
              </Title>
              <Badge color="green" size="lg">
                Active
              </Badge>
            </Group>

            <Text size="sm" c="dimmed">
              The following technologies have been successfully configured:
            </Text>

            <Stack gap="xs">
              <Group>
                <Badge color="blue">Next.js 16</Badge>
                <Text size="sm">React-based full-stack framework</Text>
              </Group>
              <Group>
                <Badge color="cyan">Mantine v7</Badge>
                <Text size="sm">UI component library with hooks</Text>
              </Group>
              <Group>
                <Badge color="grape">Zustand</Badge>
                <Text size="sm">Lightweight client state management</Text>
              </Group>
              <Group>
                <Badge color="indigo">TypeScript</Badge>
                <Text size="sm">Type-safe JavaScript</Text>
              </Group>
            </Stack>

            <div style={{ marginTop: '1rem' }}>
              <TestButton />
            </div>
          </Stack>
        </Card>

        <Card shadow="sm" padding="lg" radius="md" withBorder>
          <Title order={3} size="h4" mb="md">
            Zustand Stores Created
          </Title>
          <Stack gap="xs">
            <Text size="sm">
              <strong>authStore:</strong> Authentication state management
            </Text>
            <Text size="sm">
              <strong>lobbyStore:</strong> Lobby and game list management
            </Text>
            <Text size="sm">
              <strong>gameStore:</strong> Current game state and dice logic
            </Text>
            <Text size="sm">
              <strong>profileStore:</strong> Player profile and stats
            </Text>
            <Text size="sm">
              <strong>notificationStore:</strong> In-app notifications
            </Text>
          </Stack>
        </Card>
      </Stack>
    </Container>
  );
}
