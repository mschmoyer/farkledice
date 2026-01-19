<?php
/**
 * Test script for Claude API client
 *
 * Usage:
 *   From Docker: docker exec farkle_web php /var/www/html/wwwroot/test_claude_api.php
 *   Locally: php wwwroot/test_claude_api.php
 */

// Include the Claude API client
require_once __DIR__ . '/farkleBotAI_Claude.php';

echo "=== Claude API Connection Test ===\n\n";

// Test 1: Check API key configuration
echo "Test 1: Checking API key configuration...\n";
$apiKey = getClaudeAPIKey();
if (empty($apiKey)) {
    echo "✗ FAIL: API key not found in environment variables\n";
    echo "  Please set ANTHROPIC_API_KEY in your environment or .env.local\n\n";
    exit(1);
} else {
    echo "✓ PASS: API key found (length: " . strlen($apiKey) . " characters)\n\n";
}

// Test 2: Test API connection
echo "Test 2: Testing API connection...\n";
$result = testClaudeAPIConnection();

if ($result['success']) {
    echo "✓ PASS: API connection successful\n";
    echo "  Model: " . ($result['model'] ?? 'unknown') . "\n";

    if (isset($result['response']['content'][0]['text'])) {
        echo "  Response: " . $result['response']['content'][0]['text'] . "\n";
    }
    echo "\n";
} else {
    echo "✗ FAIL: " . $result['message'] . "\n\n";
    exit(1);
}

// Test 3: Test basic API call
echo "Test 3: Testing basic API call...\n";
$systemPrompt = "You are a dice game expert.";
$messages = [
    [
        'role' => 'user',
        'content' => 'What is the probability of rolling a Farkle (no scoring dice) with 6 dice? Reply with just a number between 0 and 1.'
    ]
];

$response = callClaudeAPI($systemPrompt, $messages);

if (isset($response['error'])) {
    echo "✗ FAIL: API call returned error: " . $response['error'] . "\n\n";
    exit(1);
} else {
    echo "✓ PASS: API call successful\n";

    if (isset($response['content'][0]['text'])) {
        echo "  Response: " . $response['content'][0]['text'] . "\n";
    }

    echo "  Usage: " . ($response['usage']['input_tokens'] ?? 0) . " input tokens, ";
    echo ($response['usage']['output_tokens'] ?? 0) . " output tokens\n";
    echo "\n";
}

// Test 4: Test error handling (invalid request)
echo "Test 4: Testing error handling with empty messages...\n";
$response = callClaudeAPI("Test", []);

if (isset($response['error'])) {
    echo "✓ PASS: Error correctly handled: " . $response['error'] . "\n\n";
} else {
    echo "✗ FAIL: Should have returned an error for empty messages\n\n";
}

echo "=== All Tests Completed ===\n";
echo "Claude API client is ready to use!\n";
