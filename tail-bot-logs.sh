#!/bin/bash
# Watch bot logs in real-time
docker logs -f farkle_web 2>&1 | grep --line-buffered -i "bot_\|claude\|anthropic\|makeai"
