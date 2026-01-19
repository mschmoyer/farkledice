#!/bin/bash
# Watch Claude API logs for AI bot debugging
# Usage: ./scripts/watch-claude-logs.sh [--tail N] [--responses-only]

TAIL_COUNT=50
RESPONSES_ONLY=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --tail) TAIL_COUNT="$2"; shift 2 ;;
        --responses-only) RESPONSES_ONLY=true; shift ;;
        *) shift ;;
    esac
done

LOG_FILE="logs/claude.log"

if [ ! -f "$LOG_FILE" ]; then
    echo "Claude log file not found: $LOG_FILE"
    echo ""
    echo "To enable Claude API logging:"
    echo "  1. Add CLAUDE_LOGGING=true to your .env file"
    echo "  2. Restart containers: docker-compose restart web"
    echo "  3. Trigger some bot activity in the game"
    exit 1
fi

echo "Watching Claude API logs (Ctrl+C to stop)..."
if [ "$RESPONSES_ONLY" = true ]; then
    echo "Filtering: responses only"
fi
echo "---"

if [ "$RESPONSES_ONLY" = true ]; then
    tail -n "$TAIL_COUNT" -f "$LOG_FILE" | grep --line-buffered "\[response\]"
else
    tail -n "$TAIL_COUNT" -f "$LOG_FILE"
fi
