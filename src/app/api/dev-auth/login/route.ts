import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';

// DEV ONLY: Local authentication for development
export async function POST(req: NextRequest) {
  // Only allow in development on localhost
  if (process.env.NODE_ENV !== 'development') {
    return NextResponse.json({ error: 'Not available in production' }, { status: 403 });
  }

  const hostname = req.headers.get('host') || '';
  if (!hostname.includes('localhost') && !hostname.includes('127.0.0.1')) {
    return NextResponse.json({ error: 'Only available on localhost' }, { status: 403 });
  }

  try {
    const body = await req.json();
    const { email, username } = body;

    if (!email) {
      return NextResponse.json({ error: 'Email required' }, { status: 400 });
    }

    // Create a fake session that mimics Auth0's session structure
    const devSession = {
      user: {
        sub: `dev|${Date.now()}`, // Fake user ID
        email,
        name: username || email.split('@')[0],
        nickname: username || email.split('@')[0],
        picture: `https://ui-avatars.com/api/?name=${encodeURIComponent(username || email)}`,
        email_verified: true,
      },
      accessToken: 'dev-access-token',
      idToken: 'dev-id-token',
    };

    // Store session in a cookie (simplified version of what Auth0 does)
    const cookieStore = await cookies();
    cookieStore.set('dev-session', JSON.stringify(devSession), {
      httpOnly: true,
      secure: false, // localhost doesn't use HTTPS
      sameSite: 'lax',
      maxAge: 60 * 60 * 24 * 7, // 1 week
      path: '/',
    });

    return NextResponse.json({
      success: true,
      user: devSession.user,
      redirectTo: '/lobby'
    });
  } catch (error) {
    console.error('Dev auth error:', error);
    return NextResponse.json({ error: 'Login failed' }, { status: 500 });
  }
}
