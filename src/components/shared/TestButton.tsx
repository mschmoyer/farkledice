'use client';

import { Button } from '@mantine/core';
import { IconDice } from '@tabler/icons-react';

export function TestButton() {
  return (
    <Button
      leftSection={<IconDice size={20} />}
      variant="filled"
      size="lg"
      onClick={() => alert('Mantine is working!')}
    >
      Roll Dice
    </Button>
  );
}
