#!/bin/bash
# Backup Heroku PostgreSQL database to local folder
# Usage: ./scripts/backup-heroku-db.sh

set -e

APP_NAME="farkledice"
BACKUP_DIR="local/db-backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup-$TIMESTAMP.dump"

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

echo "Creating backup on Heroku..."
heroku pg:backups:capture -a "$APP_NAME"

echo "Downloading backup..."
heroku pg:backups:download -a "$APP_NAME" -o "$BACKUP_FILE"

echo ""
echo "Backup saved to: $BACKUP_FILE"
echo "Size: $(du -h "$BACKUP_FILE" | cut -f1)"

# List recent backups
echo ""
echo "Recent backups:"
ls -lh "$BACKUP_DIR"/*.dump 2>/dev/null | tail -5
