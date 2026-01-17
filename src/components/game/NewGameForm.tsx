'use client';

import { useState } from 'react';
import { Stepper, Radio, Select, Button, Stack, Text, Title, Group } from '@mantine/core';
import { GameConfiguration, GameMode, GameType, PlayerCount, Friend } from '@/types/game';
import { FriendSelector } from './FriendSelector';
import styles from './NewGameForm.module.css';

interface NewGameFormProps {
  friends: Friend[];
  onSubmit: (config: GameConfiguration) => void;
  onCancel: () => void;
  disabled?: boolean;
}

// Mock friends data (will be replaced by real API data)
const getMockFriends = (): Friend[] => [
  { id: '1', username: 'DiceKing99', level: 12 },
  { id: '2', username: 'FarkleQueen', level: 8 },
  { id: '3', username: 'RollerPro', level: 15 },
  { id: '4', username: 'LuckyDice', level: 5 },
  { id: '5', username: 'ScoreMaster', level: 20 },
];

export function NewGameForm({ friends = getMockFriends(), onSubmit, onCancel, disabled = false }: NewGameFormProps) {
  const [activeStep, setActiveStep] = useState(0);
  const [gameType, setGameType] = useState<GameType | ''>('');
  const [gameMode, setGameMode] = useState<GameMode>('ten_round');
  const [playerCount, setPlayerCount] = useState<PlayerCount>(2);
  const [selectedFriendIds, setSelectedFriendIds] = useState<string[]>([]);
  const [targetScore, setTargetScore] = useState<number>(10000);
  const [breakInScore, setBreakInScore] = useState<number>(0);

  const handleNext = () => {
    setActiveStep((current) => current + 1);
  };

  const handleBack = () => {
    setActiveStep((current) => current - 1);
  };

  const handleSubmit = () => {
    const config: GameConfiguration = {
      type: gameType as GameType,
      mode: gameMode,
    };

    // Add type-specific data
    if (gameType === 'random') {
      config.playerCount = playerCount;
    } else if (gameType === 'friend') {
      config.friendIds = selectedFriendIds;
    }

    // Add mode-specific data
    if (gameMode === 'standard') {
      config.targetScore = targetScore;
      config.breakInScore = breakInScore;
    }

    onSubmit(config);
  };

  // Determine max friends based on game mode
  const maxFriends = gameMode === 'ten_round' ? 31 : 5;

  // Step 1: Choose Game Type
  const renderStep1 = () => (
    <Stack gap="lg">
      <div>
        <Title order={3} className={styles.stepTitle}>
          Choose Game Type
        </Title>
        <Text size="sm" className={styles.stepDescription}>
          Select how you want to play
        </Text>
      </div>

      <Radio.Group value={gameType} onChange={(value) => setGameType(value as GameType)}>
        <Stack gap="md">
          <Radio
            value="random"
            label="Random Matchmaking"
            description="Play with random opponents (2 or 4 players)"
            classNames={{
              root: styles.radioRoot,
              label: styles.radioLabel,
              description: styles.radioDescription,
            }}
          />
          <Radio
            value="friend"
            label="Friends Game"
            description="Select friends from your friends list"
            classNames={{
              root: styles.radioRoot,
              label: styles.radioLabel,
              description: styles.radioDescription,
            }}
          />
          <Radio
            value="practice"
            label="Solo Practice"
            description="Play alone to practice (10-Round mode only, reduced XP)"
            classNames={{
              root: styles.radioRoot,
              label: styles.radioLabel,
              description: styles.radioDescription,
            }}
          />
        </Stack>
      </Radio.Group>

      {gameType === 'random' && (
        <div className={styles.subOption}>
          <Text size="sm" fw={500} mb="xs" className={styles.subOptionLabel}>
            Number of Players
          </Text>
          <Radio.Group
            value={playerCount.toString()}
            onChange={(value) => setPlayerCount(parseInt(value) as PlayerCount)}
          >
            <Group gap="md">
              <Radio value="2" label="2 Players" className={styles.radioSmall} />
              <Radio value="4" label="4 Players" className={styles.radioSmall} />
            </Group>
          </Radio.Group>
        </div>
      )}

      {gameType === 'friend' && (
        <div className={styles.subOption}>
          <Text size="sm" fw={500} mb="xs" className={styles.subOptionLabel}>
            Select Friends
          </Text>
          <FriendSelector
            friends={friends}
            selectedIds={selectedFriendIds}
            onChange={setSelectedFriendIds}
            maxSelection={maxFriends}
          />
        </div>
      )}

      <Group justify="flex-end" mt="xl">
        <Button variant="default" onClick={onCancel} disabled={disabled}>
          Cancel
        </Button>
        <Button onClick={handleNext} disabled={disabled || !gameType || (gameType === 'friend' && selectedFriendIds.length === 0)}>
          Next
        </Button>
      </Group>
    </Stack>
  );

  // Step 2: Choose Game Mode
  const renderStep2 = () => (
    <Stack gap="lg">
      <div>
        <Title order={3} className={styles.stepTitle}>
          Choose Game Mode
        </Title>
        <Text size="sm" className={styles.stepDescription}>
          Select the type of game you want to play
        </Text>
      </div>

      <Radio.Group
        value={gameMode}
        onChange={(value) => {
          setGameMode(value as GameMode);
          // Reset practice to ten_round if mode changes
          if (gameType === 'practice' && value !== 'ten_round') {
            setGameMode('ten_round');
          }
        }}
      >
        <Stack gap="md">
          <Radio
            value="ten_round"
            label="10-Round Mode (Recommended)"
            description="Each player plays exactly 10 rounds. Highest score wins. Asynchronous play."
            classNames={{
              root: styles.radioRoot,
              label: styles.radioLabel,
              description: styles.radioDescription,
            }}
          />
          <Radio
            value="standard"
            label="Standard Mode (Classic)"
            description="Race to reach a target score. Turn-based play."
            disabled={gameType === 'practice'}
            classNames={{
              root: styles.radioRoot,
              label: styles.radioLabel,
              description: styles.radioDescription,
            }}
          />
        </Stack>
      </Radio.Group>

      {gameMode === 'standard' && (
        <div className={styles.standardOptions}>
          <Stack gap="md">
            <Select
              label="Points to Win"
              description="Target score to win the game"
              value={targetScore.toString()}
              onChange={(value) => setTargetScore(parseInt(value || '10000'))}
              data={[
                { value: '2500', label: '2,500 Points' },
                { value: '5000', label: '5,000 Points' },
                { value: '10000', label: '10,000 Points' },
              ]}
              classNames={{
                label: styles.selectLabel,
                description: styles.selectDescription,
              }}
            />

            <Select
              label="Break-In Score"
              description="Minimum score required to start scoring"
              value={breakInScore.toString()}
              onChange={(value) => setBreakInScore(parseInt(value || '0'))}
              data={[
                { value: '0', label: 'No Break-In (0 points)' },
                { value: '250', label: '250 Points' },
                { value: '500', label: '500 Points' },
                { value: '1000', label: '1,000 Points' },
              ]}
              classNames={{
                label: styles.selectLabel,
                description: styles.selectDescription,
              }}
            />
          </Stack>
        </div>
      )}

      <Group justify="space-between" mt="xl">
        <Button variant="default" onClick={handleBack} disabled={disabled}>
          Back
        </Button>
        <Button onClick={handleNext} disabled={disabled}>Next</Button>
      </Group>
    </Stack>
  );

  // Step 3: Confirm and Create
  const renderStep3 = () => {
    const getGameTypeLabel = () => {
      switch (gameType) {
        case 'random':
          return `Random Matchmaking (${playerCount} players)`;
        case 'friend':
          return `Friends Game (${selectedFriendIds.length} friend${
            selectedFriendIds.length !== 1 ? 's' : ''
          })`;
        case 'practice':
          return 'Solo Practice';
        default:
          return '';
      }
    };

    const getGameModeLabel = () => {
      if (gameMode === 'ten_round') {
        return '10-Round Mode';
      } else {
        return `Standard Mode (${targetScore.toLocaleString()} points, break-in: ${breakInScore})`;
      }
    };

    return (
      <Stack gap="lg">
        <div>
          <Title order={3} className={styles.stepTitle}>
            Confirm Game Settings
          </Title>
          <Text size="sm" className={styles.stepDescription}>
            Review your selections before creating the game
          </Text>
        </div>

        <div className={styles.confirmationBox}>
          <Stack gap="md">
            <div className={styles.confirmationItem}>
              <Text size="sm" className={styles.confirmationLabel}>
                Game Type:
              </Text>
              <Text size="md" fw={500} className={styles.confirmationValue}>
                {getGameTypeLabel()}
              </Text>
            </div>

            <div className={styles.confirmationItem}>
              <Text size="sm" className={styles.confirmationLabel}>
                Game Mode:
              </Text>
              <Text size="md" fw={500} className={styles.confirmationValue}>
                {getGameModeLabel()}
              </Text>
            </div>

            {gameType === 'friend' && selectedFriendIds.length > 0 && (
              <div className={styles.confirmationItem}>
                <Text size="sm" className={styles.confirmationLabel}>
                  Selected Friends:
                </Text>
                <Stack gap="xs" mt="xs">
                  {selectedFriendIds.map((friendId) => {
                    const friend = friends.find((f) => f.id === friendId);
                    return friend ? (
                      <Text key={friendId} size="sm" className={styles.friendName}>
                        • {friend.username} (Level {friend.level})
                      </Text>
                    ) : null;
                  })}
                </Stack>
              </div>
            )}

            {gameType === 'practice' && (
              <div className={styles.confirmationWarning}>
                <Text size="sm">
                  Note: Solo practice games provide reduced XP (3 XP) and do not count toward
                  win/loss statistics.
                </Text>
              </div>
            )}
          </Stack>
        </div>

        <Group justify="space-between" mt="xl">
          <Button variant="default" onClick={handleBack} disabled={disabled}>
            Back
          </Button>
          <Button onClick={handleSubmit} size="lg" className={styles.createButton} disabled={disabled}>
            Create Game
          </Button>
        </Group>
      </Stack>
    );
  };

  return (
    <div className={styles.formContainer}>
      <Stepper active={activeStep} className={styles.stepper}>
        <Stepper.Step label="Game Type" description="Choose how to play">
          {renderStep1()}
        </Stepper.Step>

        <Stepper.Step label="Game Mode" description="Select mode & options">
          {renderStep2()}
        </Stepper.Step>

        <Stepper.Step label="Confirm" description="Review & create">
          {renderStep3()}
        </Stepper.Step>
      </Stepper>
    </div>
  );
}
