#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SESSION_NAME="farkle-server"
LOG_FILE="./logs/docker-server.log"

# Create logs directory if it doesn't exist
mkdir -p ./logs

echo -e "${GREEN}Starting Farkle Ten Server...${NC}"

# Check if tmux is installed
if ! command -v tmux &> /dev/null; then
    echo -e "${RED}Error: tmux is not installed${NC}"
    echo "Install with: brew install tmux (macOS) or apt-get install tmux (Linux)"
    exit 1
fi

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo -e "${RED}Error: Docker is not running${NC}"
    echo "Please start Docker Desktop first"
    exit 1
fi

# Check if session already exists
if tmux has-session -t $SESSION_NAME 2>/dev/null; then
    echo -e "${YELLOW}Warning: Server is already running in tmux session '$SESSION_NAME'${NC}"
    echo ""
    echo "Options:"
    echo "  1. Attach to existing session: tmux attach -t $SESSION_NAME"
    echo "  2. Stop and restart: ./stop.sh && ./start.sh"
    echo "  3. View logs: tail -f $LOG_FILE"
    exit 0
fi

# Start Docker Compose
echo -e "${GREEN}Starting Docker containers...${NC}"
docker-compose up -d

# Wait for containers to be ready
echo -e "${GREEN}Waiting for services to be ready...${NC}"
sleep 3

# Create a new tmux session in detached mode
echo -e "${GREEN}Creating tmux session '$SESSION_NAME'...${NC}"
tmux new-session -d -s $SESSION_NAME

# Clear the log file
> $LOG_FILE

# Start logging in the tmux session
tmux send-keys -t $SESSION_NAME "docker-compose logs -f --tail=100 2>&1 | tee -a $LOG_FILE" C-m

echo ""
echo -e "${GREEN}✅ Farkle Ten Server is running!${NC}"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}📱 Access Points:${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🌐 Game:       http://localhost:8080"
echo "🗄️  phpMyAdmin: http://localhost:8081"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}👤 Test Credentials:${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Username: testuser"
echo "Password: test123"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}📋 Useful Commands:${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Attach to logs:  tmux attach -t $SESSION_NAME"
echo "View log file:   tail -f $LOG_FILE"
echo "Stop server:     ./stop.sh"
echo "Detach from tmux: Ctrl+B, then D"
echo ""

# Optional: Auto-attach to tmux session
read -p "Do you want to attach to the log session now? [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Attaching to tmux session... (Press Ctrl+B then D to detach)${NC}"
    sleep 1
    tmux attach -t $SESSION_NAME
fi
