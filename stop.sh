#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SESSION_NAME="farkle-server"

echo -e "${YELLOW}Stopping Farkle Ten Server...${NC}"

# Check if tmux session exists
if tmux has-session -t $SESSION_NAME 2>/dev/null; then
    echo -e "${GREEN}Killing tmux session '$SESSION_NAME'...${NC}"
    tmux kill-session -t $SESSION_NAME
else
    echo -e "${YELLOW}No tmux session found${NC}"
fi

# Stop Docker Compose
echo -e "${GREEN}Stopping Docker containers...${NC}"
docker-compose down

echo ""
echo -e "${GREEN}✅ Server stopped${NC}"
echo ""
echo "To start again: ./start.sh"
