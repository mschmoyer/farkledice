# Heroku Deployment Guide

This guide provides step-by-step instructions for deploying Farkle Ten to Heroku.

## Prerequisites

Before deploying to Heroku, ensure you have:

- **Heroku CLI installed**: Download from [https://devcenter.heroku.com/articles/heroku-cli](https://devcenter.heroku.com/articles/heroku-cli)
- **Git repository**: Your code must be in a Git repository
- **Heroku account**: Sign up at [https://signup.heroku.com/](https://signup.heroku.com/)
- **Heroku CLI authenticated**: Run `heroku login` to authenticate

## Initial Setup

### 1. Create the Heroku Application

From your project root directory, create a new Heroku app:

```bash
heroku create farkledice
```

This creates an app named "farkledice" and adds a git remote called "heroku" to your repository.

**Note**: If the app name is already taken, choose a different name or let Heroku generate one:

```bash
heroku create  # Generates a random name
```

### 2. Add PostgreSQL Database

Add the Heroku PostgreSQL addon with the Mini plan:

```bash
heroku addons:create heroku-postgresql:mini --app farkledice
```

This automatically sets the `DATABASE_URL` environment variable, which the application uses to connect to the database.

### 3. Verify Buildpack Configuration

The PHP buildpack should be automatically detected via `composer.json`. Verify it's set:

```bash
heroku buildpacks --app farkledice
```

You should see `heroku/php` listed. If not, add it manually:

```bash
heroku buildpacks:set heroku/php --app farkledice
```

## Configuration

### Environment Variables

The application automatically uses Heroku-provided environment variables:

- **DATABASE_URL**: Automatically set by the PostgreSQL addon (format: `postgres://user:pass@host:port/dbname`)
- **SESSION_SECRET**: Configured in `app.json` for session encryption
- **APP_ENV**: Set to "production" via `app.json`

View all config variables:

```bash
heroku config --app farkledice
```

No additional environment variables need to be set manually for basic deployment.

## Database Migration

After the PostgreSQL addon is provisioned, initialize the database schema:

```bash
heroku run php scripts/migrate-db.php --app farkledice
```

### What the Migration Does

The migration script (`scripts/migrate-db.php`):

1. Reads the complete schema from `docker/init.sql`
2. Parses and executes all SQL statements (CREATE TABLE, CREATE INDEX, etc.)
3. Creates all necessary database tables and indexes
4. Inserts sample/seed data if present in the schema
5. Handles idempotency (safe to run multiple times - skips existing objects)
6. Provides detailed progress output for each statement

The migration creates all core tables including:
- Game tables (games, game players, game history)
- Player tables (users, sessions, statistics)
- Social features (friends, achievements, tournaments)
- Leaderboards and rankings

## Deployment

### 1. Deploy Your Code

Push your code to Heroku (assuming you're on the `modernization/phase-1` branch):

```bash
git push heroku modernization/phase-1:main
```

If you're on the `master` or `main` branch:

```bash
git push heroku main
```

Heroku will:
1. Detect the PHP buildpack
2. Install Composer dependencies
3. Build the application
4. Start the web dyno using the `Procfile` configuration

### 2. Monitor the Deployment

Watch the deployment logs in real-time:

```bash
heroku logs --tail --app farkledice
```

Press `Ctrl+C` to stop tailing logs.

### 3. Open the Application

Once deployed, open your app in a browser:

```bash
heroku open --app farkledice
```

## Post-Deployment Verification

### 1. Check Application Status

Verify the app is running:

```bash
heroku ps --app farkledice
```

You should see the web dyno in the "up" state.

### 2. Test Database Connection

Connect to the PostgreSQL database to verify tables were created:

```bash
heroku pg:psql --app farkledice
```

Once connected, run:

```sql
\dt                    -- List all tables
\d farkle_games       -- Describe the games table
SELECT COUNT(*) FROM farkle_players;  -- Test a query
\q                    -- Quit
```

### 3. Verify Session Storage

Sessions are stored in the database (not filesystem) due to Heroku's ephemeral filesystem. Verify the sessions table exists:

```bash
heroku pg:psql --app farkledice -c "SELECT COUNT(*) FROM farkle_sessions;"
```

### 4. Test Login

Navigate to your application and test user registration/login:

1. Go to `https://farkledice.herokuapp.com`
2. Try creating an account
3. Verify you can log in
4. Check that session persists across page refreshes

### 5. Review Application Logs

Check for any errors or warnings:

```bash
heroku logs --tail --app farkledice
```

Common things to check:
- Database connection successful
- Smarty template compilation working
- No PHP errors or warnings
- Session handling working correctly

## Common Troubleshooting

### Application Error (500)

If you see an application error:

1. Check logs: `heroku logs --tail --app farkledice`
2. Look for PHP errors or database connection issues
3. Verify DATABASE_URL is set: `heroku config:get DATABASE_URL --app farkledice`

### Database Connection Failures

If the app can't connect to the database:

1. Verify addon is provisioned: `heroku addons --app farkledice`
2. Check DATABASE_URL format: `heroku config:get DATABASE_URL --app farkledice`
3. Re-run migration: `heroku run php scripts/migrate-db.php --app farkledice`

### Smarty Template Errors

If you see template compilation errors:

1. Templates are compiled to `/tmp` on Heroku (ephemeral but writable)
2. Check `includes/baseutil.php` for correct compile directory configuration
3. Verify permissions: `heroku run ls -la /tmp --app farkledice`

### Session Issues (Users Logged Out)

If users are unexpectedly logged out:

1. Verify database-backed sessions are working: `heroku pg:psql --app farkledice -c "SELECT COUNT(*) FROM farkle_sessions;"`
2. Check session configuration in `includes/baseutil.php`
3. Verify SESSION_SECRET is set: `heroku config:get SESSION_SECRET --app farkledice`

### Page Not Found (404)

If you get 404 errors:

1. Check that `Procfile` points to correct document root: `web: vendor/bin/heroku-php-apache2 wwwroot/`
2. Verify `.htaccess` in `wwwroot/` has correct rewrite rules
3. Check logs for routing issues

## Ongoing Maintenance

### Viewing Logs

View recent logs:

```bash
heroku logs --app farkledice
```

View logs in real-time:

```bash
heroku logs --tail --app farkledice
```

Filter for errors only:

```bash
heroku logs --app farkledice | grep -i error
```

### Scaling Dynos

Check current dyno configuration:

```bash
heroku ps --app farkledice
```

Scale web dynos (free tier allows 1 dyno):

```bash
heroku ps:scale web=1 --app farkledice
```

### Database Backups

Heroku PostgreSQL mini plan includes automatic daily backups. View backups:

```bash
heroku pg:backups --app farkledice
```

Create a manual backup:

```bash
heroku pg:backups:capture --app farkledice
```

Download a backup:

```bash
heroku pg:backups:download --app farkledice
```

### Database Maintenance

View database info:

```bash
heroku pg:info --app farkledice
```

Run database maintenance (vacuuming, analyzing):

```bash
heroku pg:psql --app farkledice -c "VACUUM ANALYZE;"
```

### Restarting the Application

If you need to restart the app:

```bash
heroku restart --app farkledice
```

### Running Commands

Execute one-off commands:

```bash
heroku run php scripts/some-script.php --app farkledice
```

Open a bash shell:

```bash
heroku run bash --app farkledice
```

## Architecture Notes

### Database-Backed Sessions

Farkle Ten uses database-backed sessions instead of file-based sessions. This is necessary because:

- **Heroku's filesystem is ephemeral**: Files written to disk are lost when dynos restart
- **Multi-dyno deployments**: Session files wouldn't be shared across multiple dynos
- **Database persistence**: Sessions stored in the `farkle_sessions` table persist across restarts

Session handling is configured in `includes/baseutil.php` using PDO session handlers.

### Smarty Template Compilation

Smarty templates are compiled to `/tmp` on Heroku:

- **Writeable location**: `/tmp` is the only writeable directory on Heroku dynos
- **Ephemeral but acceptable**: Templates are recompiled on first use after dyno restart
- **Performance**: Compiled templates are cached during dyno lifetime for performance

This is configured automatically in `includes/baseutil.php` when `DATABASE_URL` is detected.

### Database Connection

The application automatically detects and parses Heroku's `DATABASE_URL` environment variable:

- **Format**: `postgres://username:password@host:port/database`
- **Parsing**: Done in `includes/dbutil.php` via `parse_url()`
- **Fallback**: Uses local config file (`../configs/siteconfig.ini`) when not on Heroku
- **PDO usage**: Uses PDO with PostgreSQL driver for database operations

### Apache Configuration

The `Procfile` specifies the document root:

```
web: vendor/bin/heroku-php-apache2 wwwroot/
```

This tells Heroku's Apache to serve files from the `wwwroot/` directory, with URL rewriting handled by `.htaccess`.

## Deployment Checklist

Use this checklist for each deployment:

- [ ] Code committed to Git
- [ ] All tests passing locally
- [ ] Database schema changes reflected in `docker/init.sql`
- [ ] Environment variables configured (if new ones added)
- [ ] Push code to Heroku
- [ ] Run database migration (if schema changed)
- [ ] Verify deployment: `heroku open`
- [ ] Check logs for errors: `heroku logs --tail`
- [ ] Test critical functionality (login, game creation, etc.)

## Additional Resources

- [Heroku PHP Documentation](https://devcenter.heroku.com/categories/php-support)
- [Heroku PostgreSQL](https://devcenter.heroku.com/categories/postgres-basics)
- [Heroku CLI Commands](https://devcenter.heroku.com/articles/heroku-cli-commands)
- [Deploying PHP Apps on Heroku](https://devcenter.heroku.com/articles/deploying-php)
