<?php
// api/chat_handler.php

// Include the configuration file with the API key
// Use absolute path for reliability
require_once __DIR__ . '/config.php';

// Set the correct header for JSON response VERY EARLY
header('Content-Type: application/json');

// --- API Key Check ---
// Check if the constant is defined and not the placeholder value
if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY) || OPENAI_API_KEY === 'sk-YourActualOpenAIapiKeyHEREq9x...') {
     http_response_code(500); // Internal Server Error
     // Log the error on the server for debugging, don't expose details to the client
     error_log("FATAL: OpenAI API Key is not configured correctly in config.php or is still the placeholder.");
     // Send a generic error message to the client
     echo json_encode(['error' => 'AI service configuration error. Please contact support.']);
     exit; // Stop execution
}
$apiKey = OPENAI_API_KEY; // Get key from config


// --- Get Input ---
$rawData = file_get_contents('php://input');
$requestData = json_decode($rawData);

// --- Input Validation ---
if (!$requestData || !isset($requestData->message) || empty(trim($requestData->message))) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}
$userMessage = trim($requestData->message);


// --- Prepare OpenAI API Request ---
$apiUrl = 'https://api.openai.com/v1/chat/completions';

$payload = json_encode([
    'model' => 'gpt-3.5-turbo', // Or 'gpt-4', 'gpt-4-turbo-preview' etc.
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are a helpful assistant for the CROUS-X website, a platform for finding student housing. Answer questions concisely about: navigating the site (finding filters, search bar, map), explaining housing types (Studio, Apartment, Shared Room, House), understanding login/registration, and general tips for using the website. Do NOT provide external links or information unrelated to the CROUS-X website itself. Keep responses brief and friendly.'
            // You might add more specific instructions or examples here
        ],
        [
            'role' => 'user',
            'content' => $userMessage
        ]
        // Consider adding conversation history for better context in future versions
    ],
    'max_tokens' => 150,   // Limit response length to prevent overly long answers
    'temperature' => 0.7, // Balance creativity and determinism
    // 'n' => 1,             // We only want one response choice
    // 'stop' => null        // Default stop sequences
]);

// --- Use cURL to make the API call ---
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey // Crucial: Send the API Key securely
]);
// Timeouts to prevent hanging indefinitely
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout (seconds)
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Total operation timeout (seconds)

// Execute the request
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// --- Handle Response ---
if ($curlError) {
    http_response_code(500);
    error_log("cURL Error contacting OpenAI: " . $curlError);
    echo json_encode(['error' => 'Failed to communicate with AI service (Network). Please try again later.']);
    exit;
}

if ($httpcode >= 400) {
    // OpenAI returned an error (e.g., 401 Unauthorized, 429 Rate Limit, 500 Server Error)
    http_response_code(500); // Send a generic 500 to client
    error_log("OpenAI API Error: HTTP Status " . $httpcode . " | Response: " . $response); // Log details server-side
    $errorDetails = json_decode($response);
    $errorMessage = $errorDetails->error->message ?? 'Unknown error from AI service.';
    echo json_encode(['error' => 'AI service returned an error: ' . $errorMessage]);
    exit;
}

// Decode the successful JSON response from OpenAI
$responseData = json_decode($response);

// Extract the AI's reply text
// Use null coalescing operator for safety
$aiReply = $responseData->choices[0]->message->content ?? null;

if ($aiReply === null) {
    http_response_code(500);
    error_log("Failed to parse AI response or content missing. Response: " . $response);
    echo json_encode(['error' => 'Received an invalid response from the AI service.']);
    exit;
}

// --- Send Success Response to Client ---
// Respond with only the reply content
echo json_encode(['reply' => trim($aiReply)]);

?>