#!/bin/bash
# Condensed log viewer for Docker farkle_web container
# Usage: ./scripts/watch-logs.sh [--errors-only] [--no-images] [--tail N]

ERRORS_ONLY=false
NO_IMAGES=false
TAIL_COUNT=30

while [[ $# -gt 0 ]]; do
    case $1 in
        --errors-only) ERRORS_ONLY=true; shift ;;
        --no-images) NO_IMAGES=true; shift ;;
        --tail) TAIL_COUNT="$2"; shift 2 ;;
        *) shift ;;
    esac
done

docker logs --tail "$TAIL_COUNT" -f farkle_web 2>&1 | awk -v errors_only="$ERRORS_ONLY" -v no_images="$NO_IMAGES" '
BEGIN {
    RED="\033[31m"
    YELLOW="\033[33m"
    WHITE="\033[37m"
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

    printf "%s[%s] ERR  %s%s\n", RED, time, msg, RESET
    fflush()
    next
}

# Access log lines - skip if errors_only
/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+.*"(GET|POST|PUT|DELETE|HEAD|OPTIONS)/ {
    if (errors_only == "true") next
    if (no_images == "true" && /GET \/images\//) next

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
        color = WHITE
    }

    printf "%s[%s] %s  %s%s\n", color, time, status, req, RESET
    fflush()
    next
}

# Skip empty lines
/^[[:space:]]*$/ { next }

# Other lines (dimmed) - skip if errors_only
{
    if (errors_only == "true") next

    # Extract time
    if (match($0, /[0-9]{2}:[0-9]{2}:[0-9]{2}/)) {
        time = substr($0, RSTART, RLENGTH)
    } else {
        time = "--:--:--"
    }

    # Clean up the message
    msg = $0
    gsub(/\[[A-Za-z]+ [A-Za-z]+ [0-9]+ [0-9:.]+ [0-9]+\] /, "", msg)
    gsub(/\[.*:notice\] /, "", msg)
    gsub(/\[pid [0-9]+:tid [0-9]+\] /, "", msg)

    printf "%s[%s] INFO %s%s\n", DIM, time, msg, RESET
    fflush()
}
'
