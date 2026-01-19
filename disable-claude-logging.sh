#!/bin/bash
# Disable Claude API logging

echo "Disabling Claude API logging..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Error: .env file not found"
    exit 1
fi

# Update CLAUDE_LOGGING to false
if grep -q "^CLAUDE_LOGGING=" .env; then
    sed -i.bak 's/^CLAUDE_LOGGING=.*/CLAUDE_LOGGING=false/' .env
    echo "Updated CLAUDE_LOGGING=false in .env"
else
    echo "CLAUDE_LOGGING not found in .env (already disabled)"
fi

# Restart containers to pick up the new env var
echo "Restarting containers..."
docker-compose restart web

echo ""
echo "✓ Claude logging disabled!"
