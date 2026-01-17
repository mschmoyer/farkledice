import { useUser } from '@auth0/nextjs-auth0/client';
import { useState, useEffect } from 'react';

interface DevUser {
  sub: string;
  email: string;
  name: string;
  nickname: string;
  picture: string;
  email_verified: boolean;
}

interface UseAuthResult {
  user: DevUser | any | undefined;
  isLoading: boolean;
  error?: Error;
}

/**
 * Custom hook that works with both Auth0 and dev sessions
 * In development, checks dev session first, then falls back to Auth0
 */
export function useAuth(): UseAuthResult {
  const { user: auth0User, isLoading: auth0Loading, error: auth0Error } = useUser();
  const [devUser, setDevUser] = useState<DevUser | null>(null);
  const [devLoading, setDevLoading] = useState(true);
  const isDevelopment = process.env.NODE_ENV === 'development';

  useEffect(() => {
    // Only check for dev session in development
    if (!isDevelopment) {
      setDevLoading(false);
      return;
    }

    // Check for dev session
    fetch('/api/dev-auth/me')
      .then((res) => {
        if (res.ok) {
          return res.json();
        }
        return null;
      })
      .then((user) => {
        setDevUser(user);
        setDevLoading(false);
      })
      .catch(() => {
        setDevUser(null);
        setDevLoading(false);
      });
  }, [isDevelopment]);

  // In development, prefer dev user if available
  if (isDevelopment) {
    if (devLoading) {
      return { user: undefined, isLoading: true };
    }
    if (devUser) {
      return { user: devUser, isLoading: false };
    }
  }

  // Fall back to Auth0 user
  return {
    user: auth0User,
    isLoading: auth0Loading,
    error: auth0Error,
  };
}
