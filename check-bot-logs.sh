#!/bin/bash
# Check for bot and AI activity in logs

echo "=== Recent Bot Game Starts ==="
docker logs --tail 200 farkle_web 2>&1 | grep "startbotgame:" | tail -5

echo ""
echo "=== Bot Turn Executions ==="
docker logs --tail 200 farkle_web 2>&1 | grep "Bot_ExecuteStep:" | tail -5

echo ""
echo "=== AI Decision Making ==="
docker logs --tail 200 farkle_web 2>&1 | grep -i "makeaidecision\|claude\|anthropic" | tail -5

echo ""
echo "=== Recent PHP Errors ==="
docker logs --tail 200 farkle_web 2>&1 | grep "error" | grep -i "bot\|ai\|claude" | tail -5

echo ""
echo "=== Check if ANTHROPIC_API_KEY is set ==="
docker exec farkle_web bash -c 'if [ -n "$ANTHROPIC_API_KEY" ]; then echo "✓ API key is set (length: ${#ANTHROPIC_API_KEY})"; else echo "✗ API key NOT set"; fi'
