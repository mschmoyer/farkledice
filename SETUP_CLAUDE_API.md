# Claude API Setup Guide

This guide explains how to configure the Claude API for the AI-powered bot feature.

## Prerequisites

- Anthropic API account with API key
- Get your API key from: https://console.anthropic.com/

## Local Development Setup

### 1. Create `.env.local` File

Copy the example environment file and add your API key:

```bash
cp .env.example .env.local
```

### 2. Configure Your API Key

Edit `.env.local` and replace `your_api_key_here` with your actual Anthropic API key:

```bash
ANTHROPIC_API_KEY=sk-ant-api03-xxxxxxxxxxxxxxxxxxxx
```

**IMPORTANT:** Never commit `.env.local` to git. This file is already in `.gitignore`.

### 3. Start Docker with Environment Variables

The `docker-compose.yml` automatically passes environment variables from your shell to the container. Make sure to export the ANTHROPIC_API_KEY before starting Docker:

```bash
# Export the API key from .env.local
export ANTHROPIC_API_KEY=sk-ant-api03-xxxxxxxxxxxxxxxxxxxx

# Start Docker containers
docker-compose up -d
```

**Alternative:** You can source the `.env.local` file directly:

```bash
# Load environment variables from .env.local
set -a
source .env.local
set +a

# Start Docker containers
docker-compose up -d
```

### 4. Test the API Connection

Run the test script to verify the API is configured correctly:

```bash
docker exec farkle_web php /var/www/html/wwwroot/test_claude_api.php
```

Expected output:
```
=== Claude API Connection Test ===

Test 1: Checking API key configuration...
✓ PASS: API key found (length: 108 characters)

Test 2: Testing API connection...
✓ PASS: API connection successful
  Model: claude-3-5-haiku-20241022
  Response: OK

Test 3: Testing basic API call...
✓ PASS: API call successful
  Response: 0.023
  Usage: 45 input tokens, 5 output tokens

Test 4: Testing error handling with empty messages...
✓ PASS: Error correctly handled: API returned error: 400

=== All Tests Completed ===
Claude API client is ready to use!
```

## Heroku Production Setup

For Heroku deployment, set the environment variable using the Heroku CLI:

```bash
heroku config:set ANTHROPIC_API_KEY=sk-ant-api03-xxxxxxxxxxxxxxxxxxxx -a farkledice
```

Verify the configuration:

```bash
heroku config:get ANTHROPIC_API_KEY -a farkledice
```

## API Client Details

### File: `wwwroot/farkleBotAI_Claude.php`

The Claude API client provides the following functions:

#### `getClaudeAPIKey()`
- Retrieves the API key from environment variables
- Checks `getenv()`, `$_ENV`, and `$_SERVER` in that order
- Returns `null` if not configured

#### `callClaudeAPI($systemPrompt, $messages, $tools = null)`
- Main function to call the Claude API
- **Parameters:**
  - `$systemPrompt` (string) - System instructions for Claude
  - `$messages` (array) - Array of message objects `[['role' => 'user', 'content' => '...']]`
  - `$tools` (array|null) - Optional tools for function calling
- **Returns:**
  - Success: Parsed API response array
  - Failure: Array with `'error'` key
- **Configuration:**
  - Model: `claude-3-5-haiku-20241022`
  - Endpoint: `https://api.anthropic.com/v1/messages`
  - Timeout: 5 seconds
  - Max tokens: 1024

#### `testClaudeAPIConnection()`
- Simple test to verify API connectivity
- Returns array with `'success'` and `'message'` keys

### Error Handling

The API client includes comprehensive error handling:
- Missing API key detection
- cURL connection errors
- HTTP error codes (non-200 responses)
- JSON parsing errors
- Timeout handling (5 seconds)

All errors are logged via `error_log()` for debugging.

## Troubleshooting

### Error: "API key not configured"
- Ensure `ANTHROPIC_API_KEY` is set in your environment
- If using Docker, make sure the variable is exported before running `docker-compose up`
- Check that `.env.local` exists and contains your API key

### Error: "API request failed"
- Check your internet connection
- Verify the API key is valid
- Check error logs: `tail -f logs/error.log`

### Error: "API returned error: 401"
- Your API key is invalid or expired
- Get a new API key from https://console.anthropic.com/

### Error: "API returned error: 429"
- You've exceeded your API rate limit
- Wait a few moments and try again
- Consider upgrading your Anthropic plan

## Security Notes

- **Never commit API keys to git**
- The `.env.local` file is in `.gitignore` to prevent accidental commits
- API keys are never logged in error messages
- Use environment variables for all deployments (local and production)
