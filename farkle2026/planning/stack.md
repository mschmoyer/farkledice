# Farkle 2026 - Technology Stack

This document details the modern technology stack for rebuilding Farkle Ten.

## Overview

| Layer | Technology | Purpose |
|-------|------------|---------|
| Frontend Framework | Next.js 14+ | React-based full-stack framework |
| UI Components | Mantine v7 | Component library with hooks |
| State Management | Zustand | Lightweight client state |
| Authentication | Auth0 | Identity management with custom UI |
| Database | PostgreSQL | Relational database |
| Testing | Playwright | End-to-end testing |
| Hosting | Heroku | Cloud platform deployment |

---

## Frontend

### Next.js 14+

**Why Next.js:**
- Server-side rendering (SSR) for fast initial loads
- App Router with React Server Components
- API routes for backend endpoints
- Built-in optimization (images, fonts, scripts)
- TypeScript support out of the box

**Configuration:**
```javascript
// next.config.js
/** @type {import('next').NextConfig} */
const nextConfig = {
  experimental: {
    serverActions: true,
  },
}
```

**Project Structure:**
```
farkle2026/
├── src/
│   ├── app/                 # App Router pages
│   │   ├── layout.tsx       # Root layout (with Auth0 UserProvider)
│   │   ├── page.tsx         # Home/lobby page
│   │   ├── login/           # Custom login page
│   │   ├── game/[id]/       # Game play pages
│   │   ├── profile/         # Profile pages
│   │   ├── leaderboard/     # Leaderboard pages
│   │   ├── tournament/      # Tournament pages
│   │   └── api/
│   │       └── auth/[auth0]/ # Auth0 SDK route handlers
│   ├── components/
│   │   ├── auth/            # Custom login form components
│   │   ├── game/            # Game UI components
│   │   └── shared/          # Shared components
│   ├── hooks/               # Custom hooks
│   ├── stores/              # Zustand stores
│   ├── lib/                 # Utilities and helpers
│   └── styles/              # Global styles
├── prisma/                  # Database schema
├── tests/                   # Playwright tests
└── public/                  # Static assets
```

### Mantine v7

**Why Mantine:**
- Modern, accessible component library
- Built-in dark mode support
- Hooks library for common UI patterns
- Form handling with validation
- Notifications system
- Responsive design utilities

**Key Components to Use:**
- `AppShell` - Main layout structure
- `Card` - Player cards, game cards
- `Button`, `ActionIcon` - Interactive elements
- `Modal` - Dialogs and alerts
- `Notifications` - Toast notifications
- `Table` - Leaderboards
- `Tabs` - Navigation between views
- `Avatar` - Player avatars
- `Progress` - XP/level progress bars

**Setup:**
```typescript
// src/app/layout.tsx
import '@mantine/core/styles.css';
import { MantineProvider, ColorSchemeScript } from '@mantine/core';

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <head>
        <ColorSchemeScript />
      </head>
      <body>
        <MantineProvider>{children}</MantineProvider>
      </body>
    </html>
  );
}
```

### Zustand

**Why Zustand:**
- Minimal boilerplate
- No providers needed
- Works with React Server Components
- Built-in devtools support
- Persist middleware for local storage

**Store Structure:**
```typescript
// src/stores/gameStore.ts
import { create } from 'zustand';

interface GameState {
  currentGame: Game | null;
  dice: DiceState[];
  turnScore: number;
  actions: {
    rollDice: () => void;
    selectDie: (index: number) => void;
    scoreTurn: () => void;
    passTurn: () => void;
  };
}

export const useGameStore = create<GameState>((set, get) => ({
  // ... implementation
}));
```

**Stores to Create:**
- `useAuthStore` - Authentication state
- `useGameStore` - Current game state
- `useLobbyStore` - Lobby and game list
- `useProfileStore` - Player profile and stats
- `useNotificationStore` - In-app notifications

---

## Authentication

### Auth0

**Why Auth0:**
- Enterprise-grade identity management
- Social login providers (Google, Facebook, Apple) built-in
- Customizable Universal Login with branding
- JWT-based authentication
- Built-in security (MFA, anomaly detection, brute force protection)
- No need to store/manage passwords

**Auth0 Setup:**

1. Create Auth0 Application (Regular Web Application)
2. Configure allowed callback URLs, logout URLs, and origins
3. Set up social connections (Google, Facebook)
4. Customize Universal Login branding

**Next.js Integration with Auth0 SDK:**

```typescript
// src/app/api/auth/[auth0]/route.ts
import { handleAuth, handleLogin } from '@auth0/nextjs-auth0';

export const GET = handleAuth({
  login: handleLogin({
    authorizationParams: {
      // Request additional scopes if needed
      scope: 'openid profile email',
    },
  }),
});
```

**Protecting Pages:**
```typescript
// src/app/layout.tsx
import { UserProvider } from '@auth0/nextjs-auth0/client';

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>
        <UserProvider>
          <MantineProvider>{children}</MantineProvider>
        </UserProvider>
      </body>
    </html>
  );
}
```

**Server-side Session Access:**
```typescript
// In Server Components or API routes
import { getSession } from '@auth0/nextjs-auth0';

export default async function ProfilePage() {
  const session = await getSession();
  if (!session) redirect('/api/auth/login');

  const { user } = session;
  // user.sub = Auth0 user ID
  // user.email, user.name, user.picture
}
```

**Client-side Hook:**
```typescript
'use client';
import { useUser } from '@auth0/nextjs-auth0/client';

export function UserMenu() {
  const { user, isLoading } = useUser();

  if (isLoading) return <Skeleton />;
  if (!user) return <LoginButton />;

  return <Avatar src={user.picture} alt={user.name} />;
}
```

### Custom Login Screen

Auth0 supports fully customized login experiences using **Universal Login with Custom UI**.

**Option 1: Auth0 Universal Login Customization (Recommended)**

Customize via Auth0 Dashboard > Branding > Universal Login:

```html
<!-- Auth0 Dashboard > Branding > Universal Login > Advanced Options -->
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Farkle - Login</title>
  <style>
    :root {
      --farkle-green: #2d5a27;
      --farkle-gold: #d4af37;
      --farkle-felt: #35654d;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--farkle-green) 0%, var(--farkle-felt) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
    }

    .login-container {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 48px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
      max-width: 400px;
      width: 100%;
    }

    .logo {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo img {
      height: 80px;
    }

    .logo h1 {
      color: var(--farkle-green);
      font-size: 28px;
      margin: 16px 0 8px;
    }

    .logo p {
      color: #666;
      font-size: 14px;
    }

    /* Auth0 Lock widget styling overrides */
    .auth0-lock-header {
      display: none !important;
    }

    .auth0-lock-widget {
      box-shadow: none !important;
    }

    .auth0-lock-submit {
      background: var(--farkle-green) !important;
    }

    .auth0-lock-submit:hover {
      background: var(--farkle-gold) !important;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">
      <img src="{{config.extraParams.logo_url}}" alt="Farkle Dice">
      <h1>Farkle Ten</h1>
      <p>Roll the dice. Beat your friends.</p>
    </div>
    <div id="auth0-login-container"></div>
  </div>

  <script src="https://cdn.auth0.com/js/lock/12.0/lock.min.js"></script>
  <script>
    var lock = new Auth0Lock(
      '{{config.clientID}}',
      '{{config.auth0Domain}}',
      {
        container: 'auth0-login-container',
        auth: {
          redirectUrl: '{{config.callbackURL}}',
          responseType: 'code',
          params: { scope: 'openid profile email' }
        },
        theme: {
          primaryColor: '#2d5a27',
          logo: '{{config.extraParams.logo_url}}'
        },
        languageDictionary: {
          title: '',
          signUpTitle: 'Create Account',
          loginSubmitLabel: 'Roll In',
          signUpSubmitLabel: 'Join the Game'
        },
        socialButtonStyle: 'big',
        allowSignUp: true,
        allowForgotPassword: true
      }
    );
    lock.show();
  </script>
</body>
</html>
```

**Option 2: Embedded Login with Custom React Components**

For full control, use Auth0 SPA SDK with custom Mantine components:

```typescript
// src/components/auth/CustomLoginForm.tsx
'use client';

import { useState } from 'react';
import {
  Paper,
  TextInput,
  PasswordInput,
  Button,
  Title,
  Text,
  Divider,
  Group,
  Stack,
  Anchor,
  Center,
  Box,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { IconBrandGoogle, IconBrandFacebook, IconDice } from '@tabler/icons-react';
import { useAuth0 } from '@auth0/auth0-react';
import classes from './CustomLoginForm.module.css';

export function CustomLoginForm() {
  const { loginWithRedirect } = useAuth0();
  const [isSignUp, setIsSignUp] = useState(false);

  const form = useForm({
    initialValues: {
      email: '',
      password: '',
      username: '',
    },
    validate: {
      email: (val) => (/^\S+@\S+$/.test(val) ? null : 'Invalid email'),
      password: (val) => (val.length >= 8 ? null : 'Password must be at least 8 characters'),
    },
  });

  const handleSubmit = async (values: typeof form.values) => {
    await loginWithRedirect({
      authorizationParams: {
        screen_hint: isSignUp ? 'signup' : 'login',
        login_hint: values.email,
      },
    });
  };

  const handleSocialLogin = (connection: string) => {
    loginWithRedirect({
      authorizationParams: {
        connection,
      },
    });
  };

  return (
    <Box className={classes.wrapper}>
      <Paper className={classes.form} radius="lg" p={40} withBorder>
        <Center mb="xl">
          <IconDice size={48} className={classes.logo} />
        </Center>

        <Title order={2} className={classes.title} ta="center" mb="xs">
          {isSignUp ? 'Join Farkle Ten' : 'Welcome Back'}
        </Title>

        <Text c="dimmed" size="sm" ta="center" mb="xl">
          {isSignUp
            ? 'Create an account to start rolling'
            : 'Sign in to continue your games'}
        </Text>

        <Stack gap="md" mb="md">
          <Button
            leftSection={<IconBrandGoogle size={20} />}
            variant="default"
            size="md"
            onClick={() => handleSocialLogin('google-oauth2')}
          >
            Continue with Google
          </Button>

          <Button
            leftSection={<IconBrandFacebook size={20} />}
            variant="default"
            size="md"
            onClick={() => handleSocialLogin('facebook')}
          >
            Continue with Facebook
          </Button>
        </Stack>

        <Divider label="Or continue with email" labelPosition="center" my="lg" />

        <form onSubmit={form.onSubmit(handleSubmit)}>
          <Stack gap="md">
            {isSignUp && (
              <TextInput
                label="Username"
                placeholder="DiceMaster2026"
                {...form.getInputProps('username')}
              />
            )}

            <TextInput
              label="Email"
              placeholder="you@example.com"
              {...form.getInputProps('email')}
            />

            <PasswordInput
              label="Password"
              placeholder="Your password"
              {...form.getInputProps('password')}
            />

            {!isSignUp && (
              <Anchor
                component="button"
                type="button"
                c="dimmed"
                size="xs"
                onClick={() => loginWithRedirect({
                  authorizationParams: { screen_hint: 'reset-password' }
                })}
              >
                Forgot password?
              </Anchor>
            )}

            <Button type="submit" fullWidth size="md" className={classes.submitButton}>
              {isSignUp ? 'Create Account' : 'Sign In'}
            </Button>
          </Stack>
        </form>

        <Text ta="center" mt="md" size="sm">
          {isSignUp ? 'Already have an account? ' : "Don't have an account? "}
          <Anchor component="button" onClick={() => setIsSignUp(!isSignUp)}>
            {isSignUp ? 'Sign in' : 'Sign up'}
          </Anchor>
        </Text>
      </Paper>
    </Box>
  );
}
```

```css
/* src/components/auth/CustomLoginForm.module.css */
.wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, #2d5a27 0%, #35654d 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.form {
  max-width: 420px;
  width: 100%;
  background: rgba(255, 255, 255, 0.98);
}

.logo {
  color: #2d5a27;
}

.title {
  color: #2d5a27;
}

.submitButton {
  background: #2d5a27;
}

.submitButton:hover {
  background: #3d7a37;
}
```

**Auth0 Dashboard Settings:**

| Setting | Value |
|---------|-------|
| Application Type | Regular Web Application |
| Allowed Callback URLs | `http://localhost:3000/api/auth/callback, https://farkle-2026.herokuapp.com/api/auth/callback` |
| Allowed Logout URLs | `http://localhost:3000, https://farkle-2026.herokuapp.com` |
| Allowed Web Origins | `http://localhost:3000, https://farkle-2026.herokuapp.com` |

**Social Connections to Enable:**
- Google OAuth 2.0
- Facebook Login
- Apple Sign In (optional)

---

## Backend

### Next.js API Routes

API routes will be organized in the App Router:

```
src/app/api/
├── auth/
│   └── [auth0]/route.ts     # Auth0 SDK handlers (login, logout, callback, me)
├── games/
│   ├── route.ts              # GET list, POST create
│   ├── [id]/route.ts         # GET/PUT/DELETE game
│   ├── [id]/roll/route.ts    # POST roll dice
│   ├── [id]/score/route.ts   # POST score turn
│   └── [id]/pass/route.ts    # POST pass turn
├── players/
│   ├── [id]/route.ts
│   └── [id]/stats/route.ts
├── friends/route.ts
├── leaderboard/route.ts
├── tournaments/route.ts
└── achievements/route.ts
```

### PostgreSQL

**Why PostgreSQL:**
- Robust relational database
- JSON support for flexible data
- Full-text search capabilities
- Strong Heroku integration
- Prisma ORM support

**Prisma Schema (prisma/schema.prisma):**
```prisma
generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

model User {
  id            String    @id @default(cuid())
  auth0Id       String    @unique              // Auth0 user sub (e.g., "auth0|123" or "google-oauth2|456")
  username      String    @unique
  email         String?   @unique
  picture       String?                        // Profile picture URL from Auth0
  createdAt     DateTime  @default(now())
  level         Int       @default(1)
  xp            Int       @default(0)

  gamesAsPlayer1  Game[]  @relation("Player1")
  gamesAsPlayer2  Game[]  @relation("Player2")
  achievements    UserAchievement[]
  friends         Friendship[]  @relation("UserFriends")
  friendOf        Friendship[]  @relation("FriendOf")
}

model Game {
  id          String    @id @default(cuid())
  mode        GameMode
  type        GameType
  state       GameState
  player1Id   String
  player2Id   String?
  player1     User      @relation("Player1", fields: [player1Id], references: [id])
  player2     User?     @relation("Player2", fields: [player2Id], references: [id])
  currentTurn Int       @default(1)
  createdAt   DateTime  @default(now())
  updatedAt   DateTime  @updatedAt

  rounds      Round[]
}

// Additional models: Round, Achievement, UserAchievement,
// Friendship, Tournament, TournamentParticipant, LeaderboardEntry
```

### Logging (Development Mode)

**File-based logging in development:**

```typescript
// src/lib/logger.ts
import fs from 'fs';
import path from 'path';

const isDev = process.env.NODE_ENV === 'development';
const logDir = path.join(process.cwd(), 'logs');
const logFile = path.join(logDir, `app-${new Date().toISOString().split('T')[0]}.log`);

// Ensure log directory exists in dev
if (isDev && !fs.existsSync(logDir)) {
  fs.mkdirSync(logDir, { recursive: true });
}

type LogLevel = 'info' | 'warn' | 'error' | 'debug';

export function log(level: LogLevel, message: string, meta?: object) {
  const timestamp = new Date().toISOString();
  const logEntry = {
    timestamp,
    level,
    message,
    ...meta,
  };

  const logLine = JSON.stringify(logEntry) + '\n';

  // Always log to console
  console[level === 'debug' ? 'log' : level](logLine);

  // Write to file only in development
  if (isDev) {
    fs.appendFileSync(logFile, logLine);
  }
}

export const logger = {
  info: (msg: string, meta?: object) => log('info', msg, meta),
  warn: (msg: string, meta?: object) => log('warn', msg, meta),
  error: (msg: string, meta?: object) => log('error', msg, meta),
  debug: (msg: string, meta?: object) => log('debug', msg, meta),
};
```

**Usage:**
```typescript
import { logger } from '@/lib/logger';

// In API routes
logger.info('Game created', { gameId: game.id, playerId: user.id });
logger.error('Failed to roll dice', { error: err.message, gameId });
```

**Log file location:** `farkle2026/logs/app-YYYY-MM-DD.log`

---

## Testing

### Playwright

**Why Playwright:**
- Cross-browser testing (Chrome, Firefox, Safari)
- Auto-wait for elements
- Network interception for mocking
- Visual regression testing
- Parallel test execution

**Configuration (playwright.config.ts):**
```typescript
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
    { name: 'mobile', use: { ...devices['iPhone 13'] } },
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
  },
});
```

**Test Structure:**
```
tests/
├── auth/
│   ├── login.spec.ts
│   └── register.spec.ts
├── game/
│   ├── create-game.spec.ts
│   ├── dice-rolling.spec.ts
│   ├── scoring.spec.ts
│   └── game-flow.spec.ts
├── social/
│   ├── friends.spec.ts
│   └── leaderboard.spec.ts
└── fixtures/
    └── test-data.ts
```

---

## Deployment

### Heroku

**Why Heroku:**
- Simple deployment workflow
- PostgreSQL add-on (Heroku Postgres)
- Automatic SSL
- Easy environment variable management
- Review apps for PRs

**Required Add-ons:**
- Heroku Postgres (database)
- Papertrail (log management - production)

**Environment Variables:**
```bash
# Database
DATABASE_URL=postgres://...

# Auth0 Configuration
AUTH0_SECRET=<long-random-string>              # Generate with: openssl rand -hex 32
AUTH0_BASE_URL=https://farkle-2026.herokuapp.com
AUTH0_ISSUER_BASE_URL=https://your-tenant.auth0.com
AUTH0_CLIENT_ID=your-client-id
AUTH0_CLIENT_SECRET=your-client-secret

# Environment
NODE_ENV=production
```

**Local Development (.env.local):**
```bash
AUTH0_SECRET=<generate-locally>
AUTH0_BASE_URL=http://localhost:3000
AUTH0_ISSUER_BASE_URL=https://your-tenant.auth0.com
AUTH0_CLIENT_ID=your-client-id
AUTH0_CLIENT_SECRET=your-client-secret
DATABASE_URL=postgresql://localhost:5432/farkle_dev
```

**Procfile:**
```
web: npm run start
```

**Deployment Commands:**
```bash
# Initial setup
heroku create farkle-2026
heroku addons:create heroku-postgresql:essential-0

# Deploy
git push heroku main

# Run migrations
heroku run npx prisma migrate deploy
```

---

## Development Workflow

### Local Setup

```bash
# Install dependencies
cd farkle2026
npm install

# Set up environment
cp .env.example .env.local
# Edit .env.local with local database URL

# Set up database
npx prisma migrate dev

# Run development server
npm run dev
```

### Scripts (package.json)

```json
{
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start",
    "lint": "next lint",
    "test": "playwright test",
    "test:ui": "playwright test --ui",
    "db:migrate": "prisma migrate dev",
    "db:push": "prisma db push",
    "db:studio": "prisma studio",
    "db:seed": "prisma db seed"
  }
}
```

### Dependencies

```json
{
  "dependencies": {
    "next": "^14.0.0",
    "@auth0/nextjs-auth0": "^3.5.0",
    "@mantine/core": "^7.0.0",
    "@mantine/hooks": "^7.0.0",
    "@mantine/notifications": "^7.0.0",
    "@mantine/form": "^7.0.0",
    "@tabler/icons-react": "^3.0.0",
    "zustand": "^4.4.0",
    "@prisma/client": "^5.0.0"
  },
  "devDependencies": {
    "typescript": "^5.0.0",
    "@types/react": "^18.0.0",
    "@types/node": "^20.0.0",
    "prisma": "^5.0.0",
    "@playwright/test": "^1.40.0",
    "postcss": "^8.0.0",
    "postcss-preset-mantine": "^1.0.0"
  }
}
```

---

## Migration Strategy

### Phase 1: Core Infrastructure
1. Set up Next.js project with TypeScript
2. Configure Mantine theming
3. Set up Prisma with PostgreSQL
4. Integrate Auth0 with custom login screen

### Phase 2: Game Engine
1. Port dice scoring logic
2. Implement game state management
3. Build dice rendering (Canvas or CSS)
4. Create game play UI

### Phase 3: Features
1. Player profiles and stats
2. Friend system
3. Leaderboards
4. Achievements

### Phase 4: Advanced Features
1. Tournament system
2. Real-time updates (consider WebSockets)
3. Push notifications
4. Mobile optimization

### Phase 5: Launch
1. Data migration from legacy system
2. Beta testing
3. Production deployment
