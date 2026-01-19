#!/bin/bash
# Condensed log viewer for Docker farkle_web container
# Usage: ./scripts/watch-logs.sh [--errors-only]

ERRORS_ONLY=false
if [[ "$1" == "--errors-only" ]]; then
    ERRORS_ONLY=true
fi

docker logs -f farkle_web 2>&1 | awk -v errors_only="$ERRORS_ONLY" '
BEGIN {
    RED="\033[31m"
    GREEN="\033[32m"
    YELLOW="\033[33m"
    DIM="\033[90m"
    RESET="\033[0m"
}

# Error/warning log lines
/^\[.*\] \[.*:(error|warn)\]/ || /PHP (Fatal|Warning|Notice|Error|Parse)/ || /Stack trace:/ || /^\s+#[0-9]+/ {
    # Extract time
    if (match($0, /[0-9]{2}:[0-9]{2}:[0-9]{2}/)) {
        time = substr($0, RSTART, RLENGTH)
    } else {
        time = "--:--:--"
    }
    # Clean up the message
    msg = $0
    gsub(/\[[A-Za-z]+ [A-Za-z]+ [0-9]+ [0-9:.]+ [0-9]+\] /, "", msg)
    gsub(/\[.*:(error|warn)\] /, "", msg)
    gsub(/\[pid [0-9]+\] /, "", msg)
    gsub(/\[client [0-9.:]+\] /, "", msg)

    # Truncate long messages
    if (length(msg) > 120) msg = substr(msg, 1, 117) "..."

    printf "%s[%s] ERR  %s%s\n", RED, time, msg, RESET
    next
}

# Access log lines - skip if errors_only
/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+.*"(GET|POST|PUT|DELETE|HEAD|OPTIONS)/ {
    if (errors_only == "true") next

    # Extract time
    if (match($0, /[0-9]{2}:[0-9]{2}:[0-9]{2}/)) {
        time = substr($0, RSTART, RLENGTH)
    } else {
        time = "--:--:--"
    }

    # Extract request method and path
    if (match($0, /"[A-Z]+ [^"]+"/)) {
        req = substr($0, RSTART+1, RLENGTH-2)
        # Shorten the request
        gsub(/ HTTP\/[0-9.]+/, "", req)
    } else {
        req = "?"
    }

    # Extract status code
    if (match($0, /" [0-9]{3} /)) {
        status = substr($0, RSTART+2, 3)
    } else {
        status = "???"
    }

    # Color based on status
    if (status >= 400) {
        color = RED
    } else if (status >= 300) {
        color = YELLOW
    } else {
        color = GREEN
    }

    # Truncate long requests
    if (length(req) > 60) req = substr(req, 1, 57) "..."

    printf "%s[%s] %s  %s%s\n", color, time, status, req, RESET
    next
}

# Skip empty lines
/^[[:space:]]*$/ { next }

# Other lines (dimmed) - skip if errors_only
{
    if (errors_only == "true") next

    msg = $0
    if (length(msg) > 100) msg = substr(msg, 1, 97) "..."
    printf "%s%s%s\n", DIM, msg, RESET
}
'
