#!/bin/bash
# Restore a Heroku backup to local Docker PostgreSQL
# Usage: ./scripts/restore-db-to-local.sh [backup-file]
#        ./scripts/restore-db-to-local.sh                    # Uses latest backup
#        ./scripts/restore-db-to-local.sh local/db-backups/backup-20240101-120000.dump

set -e

BACKUP_DIR="local/db-backups"
CONTAINER="farkle_db"
DB_NAME="farkle_db"
DB_USER="farkle_user"

# Find backup file
if [ -n "$1" ]; then
    BACKUP_FILE="$1"
else
    # Use the most recent backup
    BACKUP_FILE=$(ls -t "$BACKUP_DIR"/*.dump 2>/dev/null | head -1)
fi

if [ -z "$BACKUP_FILE" ] || [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: No backup file found"
    echo "Usage: $0 [backup-file]"
    echo ""
    echo "Available backups:"
    ls -lh "$BACKUP_DIR"/*.dump 2>/dev/null || echo "  (none)"
    exit 1
fi

echo "Backup file: $BACKUP_FILE"
echo "Target: Docker container '$CONTAINER' -> database '$DB_NAME'"
echo ""
read -p "This will REPLACE all data in local database. Continue? (y/N) " confirm

if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "Aborted."
    exit 0
fi

echo ""
echo "Copying backup to container..."
docker cp "$BACKUP_FILE" "$CONTAINER:/tmp/restore.dump"

echo "Dropping and recreating database..."
docker exec "$CONTAINER" psql -U "$DB_USER" -d postgres -c "DROP DATABASE IF EXISTS $DB_NAME;"
docker exec "$CONTAINER" psql -U "$DB_USER" -d postgres -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;"

echo "Restoring backup..."
docker exec "$CONTAINER" pg_restore -U "$DB_USER" -d "$DB_NAME" --no-owner --no-privileges /tmp/restore.dump || true

echo "Cleaning up..."
docker exec "$CONTAINER" rm /tmp/restore.dump

echo ""
echo "Restore complete!"
echo ""
echo "Quick verification:"
docker exec "$CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "SELECT COUNT(*) as players FROM farkle_players;"
