#!/bin/bash
# Enable Claude API logging

echo "Enabling Claude API logging..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "Error: .env file not found"
    exit 1
fi

# Check if CLAUDE_LOGGING exists in .env
if grep -q "^CLAUDE_LOGGING=" .env; then
    # Update existing value
    sed -i.bak 's/^CLAUDE_LOGGING=.*/CLAUDE_LOGGING=true/' .env
    echo "Updated CLAUDE_LOGGING=true in .env"
else
    # Add the variable
    echo "CLAUDE_LOGGING=true" >> .env
    echo "Added CLAUDE_LOGGING=true to .env"
fi

# Restart containers to pick up the new env var
echo "Restarting containers..."
docker-compose restart web

echo ""
echo "✓ Claude logging enabled!"
echo "Logs will be written to: logs/claude.log"
echo ""
echo "To view logs in real-time:"
echo "  tail -f logs/claude.log"
echo ""
echo "To disable logging:"
echo "  ./disable-claude-logging.sh"
