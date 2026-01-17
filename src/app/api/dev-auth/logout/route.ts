import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';

// DEV ONLY: Local logout for development
export async function POST(req: NextRequest) {
  // Only allow in development on localhost
  if (process.env.NODE_ENV !== 'development') {
    return NextResponse.json({ error: 'Not available in production' }, { status: 403 });
  }

  const cookieStore = await cookies();
  cookieStore.delete('dev-session');

  return NextResponse.json({ success: true, redirectTo: '/login' });
}
