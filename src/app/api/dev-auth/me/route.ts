import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';

// DEV ONLY: Get current dev session
export async function GET(req: NextRequest) {
  // Only allow in development on localhost
  if (process.env.NODE_ENV !== 'development') {
    return NextResponse.json({ error: 'Not available in production' }, { status: 403 });
  }

  try {
    const cookieStore = await cookies();
    const sessionCookie = cookieStore.get('dev-session');

    if (!sessionCookie) {
      return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
    }

    const session = JSON.parse(sessionCookie.value);
    return NextResponse.json(session.user);
  } catch (error) {
    return NextResponse.json({ error: 'Invalid session' }, { status: 401 });
  }
}
