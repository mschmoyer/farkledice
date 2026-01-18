# Farkle Scripts

This directory contains utility scripts for database migrations and other operational tasks.

## migrate-db.php

Database migration script for initializing PostgreSQL databases (both local and Heroku).

### Usage

```bash
php scripts/migrate-db.php
```

### What it does

- Reads the database schema from `docker/init.sql`
- Parses SQL statements (handling comments and multi-line statements)
- Executes each statement against the database
- Creates all tables, indexes, ENUM types, and sample data
- Handles errors gracefully (skips already-existing objects)
- Provides detailed output of migration progress
- Works with both local (Docker) and production (Heroku) environments

### Environment Detection

The script automatically detects the environment:

- **Heroku**: Uses `DATABASE_URL` environment variable
- **Local**: Uses configuration from `../configs/siteconfig.ini`

### Exit Codes

- `0`: Migration completed successfully
- `1`: Migration completed with errors

### Features

- **Idempotent**: Can be run multiple times safely
- **Transactional**: Uses proper error handling
- **Informative**: Provides detailed status for each operation
- **Portable**: Works on both local and Heroku environments
