<?php
// api/chat_handler.php

// Include the configuration file with the API key
// Use absolute path for reliability
require_once __DIR__ . '/config.php';

// Set the correct header for JSON response VERY EARLY
header('Content-Type: application/json');

// --- API Key Check ---
// Check if the constant is defined, not empty, and not the placeholder value
if (!defined('DeepSeek_Api_Key') || empty(DeepSeek_Api_Key) || DeepSeek_Api_Key === 'sk-YourActualDeepSeekapiKeyHEREq9x...') {
     http_response_code(500); // Internal Server Error
     // Log the error on the server for debugging, don't expose details to the client
     error_log("FATAL: DeepSeek API Key is not configured correctly in config.php or is still the placeholder.");
     // Send a generic error message to the client
     echo json_encode(['error' => 'AI service configuration error. Please contact support.']);
     exit; // Stop execution
}
// Use the API key defined in config.php (ensure this holds your ACTUAL DeepSeek key)
$apiKey = DeepSeek_Api_Key;


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


// --- Prepare DeepSeek API Request ---
// ***** MODIFIED: Use the official DeepSeek API endpoint *****
$apiUrl = 'https://api.deepseek.com/chat/completions'; // CORRECT DEEPSEEK ENDPOINT

$payload = json_encode([
    // ***** MODIFIED: Use a valid DeepSeek model name *****
    // Common options: 'deepseek-chat' or 'deepseek-coder'
    // Choose 'deepseek-chat' for general conversational tasks.
    'model' => 'deepseek-chat',

    'messages' => [
        [
            'role' => 'system',
            // Keep the system prompt as it's relevant to the CROUS-X assistant task
            'content' => 'You are a helpful assistant for the CROUS-X website, a platform for finding student housing. Answer questions concisely about: navigating the site (finding filters, search bar, map), explaining housing types (Studio, Apartment, Shared Room, House), understanding login/registration, and general tips for using the website. Do NOT provide external links or information unrelated to the CROUS-X website itself. Keep responses brief and friendly.'
        ],
        [
            'role' => 'user',
            'content' => $userMessage
        ]
        // Consider adding conversation history for better context in future versions
    ],
    'max_tokens' => 150,   // Limit response length (adjust as needed)
    'temperature' => 0.7, // Adjust creativity vs determinism
    // 'n' => 1,             // Default: We only want one response choice
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
    'Authorization: Bearer ' . $apiKey // Send the DeepSeek API Key
]);
// Timeouts to prevent hanging indefinitely
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout (seconds)
curl_setopt($ch, CURLOPT_TIMEOUT, 30);       // Total operation timeout (seconds)

// Execute the request
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// --- Handle Response ---
if ($curlError) {
    http_response_code(500);
    // Log the specific error source for easier debugging server-side
    error_log("cURL Error contacting DeepSeek API: " . $curlError);
    echo json_encode(['error' => 'Failed to communicate with AI service (Network). Please try again later.']);
    exit;
}

if ($httpcode >= 400) {
    // DeepSeek API returned an error
    http_response_code(500); // Send a generic 500 to the client
    error_log("DeepSeek API Error: HTTP Status " . $httpcode . " | Response: " . $response); // Log details server-side
    $errorDetails = json_decode($response);
    // Attempt to parse the specific error message from DeepSeek's response
    $errorMessage = $errorDetails->error->message ?? 'Unknown error from AI service.';
    echo json_encode(['error' => 'AI service returned an error: ' . $errorMessage]);
    exit;
}

// Decode the successful JSON response from DeepSeek
$responseData = json_decode($response);

// Extract the AI's reply text using the standard OpenAI-compatible structure
// Use null coalescing operator for safety
$aiReply = $responseData->choices[0]->message->content ?? null;

if ($aiReply === null) {
    http_response_code(500);
    error_log("Failed to parse DeepSeek response or content missing. Response: " . $response);
    echo json_encode(['error' => 'Received an invalid response from the AI service.']);
    exit;
}

// --- Send Success Response to Client ---
// Respond with only the reply content
echo json_encode(['reply' => trim($aiReply)]);

?>