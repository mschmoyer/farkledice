# Farkle 2026

A modern rebuild of the Farkle dice game using Next.js 14, TypeScript, and a comprehensive technology stack.

## Technology Stack

- **Frontend Framework**: Next.js 14+ (App Router)
- **UI Components**: Mantine v7
- **State Management**: Zustand
- **Authentication**: Auth0
- **Database**: PostgreSQL
- **ORM**: Prisma
- **Testing**: Playwright
- **Hosting**: Heroku

## Getting Started

### Prerequisites

- Node.js 20+
- PostgreSQL database
- Auth0 account

### Local Development

1. Install dependencies:
```bash
npm install
```

2. Set up environment variables:
```bash
cp .env.example .env.local
# Edit .env.local with your local configuration
```

3. Set up database:
```bash
npx prisma migrate dev
```

4. Run development server:
```bash
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) to see the application.

## Project Structure

```
farkle2026/
├── src/
│   ├── app/                 # App Router pages
│   ├── components/          # React components
│   │   ├── auth/           # Authentication components
│   │   ├── game/           # Game UI components
│   │   └── shared/         # Shared components
│   ├── hooks/              # Custom hooks
│   ├── stores/             # Zustand stores
│   ├── lib/                # Utilities and helpers
│   └── styles/             # Global styles
├── prisma/                 # Database schema
├── tests/                  # Playwright tests
└── public/                 # Static assets
```

## Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run start` - Start production server
- `npm run lint` - Run ESLint

## Deployment

This application is configured for deployment on Heroku:

1. Create a Heroku app:
```bash
heroku create farkle-2026
```

2. Add PostgreSQL:
```bash
heroku addons:create heroku-postgresql:essential-0
```

3. Deploy:
```bash
git push heroku main
```

4. Run migrations:
```bash
heroku run npx prisma migrate deploy
```

## License

Private project.
