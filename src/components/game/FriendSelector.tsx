'use client';

import { Checkbox, Stack, Text, Avatar, Group } from '@mantine/core';
import { Friend } from '@/types/game';
import styles from './FriendSelector.module.css';

interface FriendSelectorProps {
  friends: Friend[];
  selectedIds: string[];
  onChange: (selectedIds: string[]) => void;
  maxSelection?: number;
}

export function FriendSelector({
  friends,
  selectedIds,
  onChange,
  maxSelection,
}: FriendSelectorProps) {
  const handleToggle = (friendId: string) => {
    const isSelected = selectedIds.includes(friendId);

    if (isSelected) {
      // Remove from selection
      onChange(selectedIds.filter((id) => id !== friendId));
    } else {
      // Add to selection (if not at max)
      if (!maxSelection || selectedIds.length < maxSelection) {
        onChange([...selectedIds, friendId]);
      }
    }
  };

  if (friends.length === 0) {
    return (
      <Text className={styles.emptyMessage}>
        You don't have any friends yet. Add friends to play with them!
      </Text>
    );
  }

  return (
    <div className={styles.container}>
      {maxSelection && (
        <Text size="sm" className={styles.maxSelectionHint}>
          Select up to {maxSelection} friend{maxSelection !== 1 ? 's' : ''}
          {selectedIds.length > 0 && ` (${selectedIds.length} selected)`}
        </Text>
      )}

      <Stack gap="xs" className={styles.friendsList}>
        {friends.map((friend) => {
          const isSelected = selectedIds.includes(friend.id);
          const isDisabled =
            !isSelected && maxSelection !== undefined && selectedIds.length >= maxSelection;

          return (
            <div
              key={friend.id}
              className={`${styles.friendItem} ${isSelected ? styles.selected : ''} ${
                isDisabled ? styles.disabled : ''
              }`}
              onClick={() => !isDisabled && handleToggle(friend.id)}
            >
              <Group gap="sm" className={styles.friendContent}>
                <Checkbox
                  checked={isSelected}
                  disabled={isDisabled}
                  onChange={() => !isDisabled && handleToggle(friend.id)}
                  className={styles.checkbox}
                />
                <Avatar
                  src={friend.avatarUrl}
                  alt={friend.username}
                  radius="xl"
                  size="md"
                  className={styles.avatar}
                >
                  {friend.username.charAt(0).toUpperCase()}
                </Avatar>
                <div className={styles.friendInfo}>
                  <Text size="md" fw={500} className={styles.username}>
                    {friend.username}
                  </Text>
                  <Text size="sm" className={styles.level}>
                    Level {friend.level}
                  </Text>
                </div>
              </Group>
            </div>
          );
        })}
      </Stack>
    </div>
  );
}
