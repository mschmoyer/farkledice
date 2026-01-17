import { useEffect, useRef, useState } from 'react';
import { useLobbyStore } from '@/stores/lobbyStore';

interface LobbyPollingOptions {
  enabled?: boolean;
  onPoll?: () => Promise<any>;
}

interface LobbyPollingResult {
  data: any;
  isIdle: boolean;
  refresh: () => void;
  resume: () => void;
}

/**
 * Custom hook for lobby polling with idle state management
 *
 * Polling strategy:
 * - Polls 1-20: 10 seconds
 * - Polls 21-40: 20 seconds
 * - Poll 41+: Stop and go idle
 *
 * Based on REQ-GAME-018 and REQ-UI-015
 */
export function useLobbyPolling(options: LobbyPollingOptions = {}): LobbyPollingResult {
  const { enabled = true, onPoll } = options;

  const [isIdle, setIsIdle] = useState(false);
  const [data, setData] = useState<any>(null);
  const pollCountRef = useRef(0);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  const { actions } = useLobbyStore();

  // Clear any existing timer
  const clearTimer = () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
      timerRef.current = null;
    }
  };

  // Perform a single poll
  const poll = async () => {
    if (!enabled || isIdle) return;

    try {
      actions.setPolling(true);

      // Fetch lobby data from API
      const response = await fetch('/api/lobby', {
        method: 'GET',
        credentials: 'include', // Include cookies for Auth0 session
      });

      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }

      const result = await response.json();

      // Call the onPoll callback if provided (for custom handling)
      if (onPoll) {
        await onPoll();
      }

      setData(result);
      actions.refresh();

      pollCountRef.current += 1;

      // Determine next interval based on poll count
      if (pollCountRef.current < 20) {
        // Polls 1-20: 10 seconds
        timerRef.current = setTimeout(poll, 10000);
      } else if (pollCountRef.current < 40) {
        // Polls 21-40: 20 seconds
        timerRef.current = setTimeout(poll, 20000);
      } else {
        // Poll 41+: Go idle
        setIsIdle(true);
        actions.setPolling(false);
      }
    } catch (error) {
      console.error('Lobby polling error:', error);
      // Continue polling even on error
      if (pollCountRef.current < 40) {
        timerRef.current = setTimeout(poll, pollCountRef.current < 20 ? 10000 : 20000);
      } else {
        setIsIdle(true);
        actions.setPolling(false);
      }
    }
  };

  // Refresh: perform immediate poll without resetting count
  const refresh = async () => {
    clearTimer();
    await poll();
  };

  // Resume: reset poll count and restart from fast polling
  const resume = () => {
    setIsIdle(false);
    pollCountRef.current = 0;
    clearTimer();
    actions.setPolling(true);
    poll();
  };

  // Start polling on mount
  useEffect(() => {
    if (enabled && !isIdle) {
      poll();
    }

    // Cleanup on unmount
    return () => {
      clearTimer();
      actions.setPolling(false);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enabled, isIdle]);

  return {
    data,
    isIdle,
    refresh,
    resume,
  };
}
