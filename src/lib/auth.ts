import { redirect } from 'next/navigation';
import { cookies } from 'next/headers';
import { auth0 } from './auth0';

/**
 * Get dev session if in development mode
 */
async function getDevSession() {
  if (process.env.NODE_ENV !== 'development') {
    return null;
  }

  try {
    const cookieStore = await cookies();
    const devSessionCookie = cookieStore.get('dev-session');

    if (!devSessionCookie) {
      return null;
    }

    const devSession = JSON.parse(devSessionCookie.value);
    return devSession;
  } catch {
    return null;
  }
}

/**
 * Get the current user session (server-side)
 * Returns null if not authenticated
 * Checks both Auth0 and dev sessions
 */
export async function getUserSession() {
  // Try dev session first in development
  if (process.env.NODE_ENV === 'development') {
    const devSession = await getDevSession();
    if (devSession) {
      return devSession;
    }
  }

  // Fall back to Auth0 session
  const session = await auth0.getSession();
  return session;
}

/**
 * Require authentication for a page
 * Redirects to login if not authenticated
 * Returns the user session
 */
export async function requireAuth() {
  const session = await getUserSession();

  if (!session) {
    redirect('/login');
  }

  return session;
}

/**
 * Higher-order function to protect server components
 * Usage:
 * export default withAuth(async function ProtectedPage() { ... })
 */
export function withAuth<T extends (...args: any[]) => any>(
  Component: T
): T {
  return (async (...args: any[]) => {
    await requireAuth();
    return Component(...args);
  }) as T;
}
