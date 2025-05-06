<?php
// api/chat_handler.php

$host   = getenv('MYSQL_HOST');
$dbname = getenv('MYSQL_DATABASE');
$dbUser = getenv('MYSQL_USER');
$dbPass = getenv('MYSQL_PASSWORD');

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // === UPDATED QUERY ===
    // Pull from housings, aliasing columns to match the old field names
    $sql = <<<SQL
SELECT
  h.listing_id    AS id,
  h.title         AS name,
  h.rent_amount   AS price,
  h.square_footage AS size,
  h.property_type AS type,
  h.rating,
  h.latitude      AS lat,
  h.longitude     AS lng
FROM housings AS h
SQL;

    $stmt = $pdo->query($sql);
    $housingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build a summary string of housing data
    $housingSummary = "Housing Listings: ";
    foreach ($housingData as $listing) {
        $housingSummary .= "ID {$listing['id']} - {$listing['name']} ({$listing['type']}, \$"
            . number_format($listing['price'], 2)
            . ", {$listing['size']} sqm, Rating: {$listing['rating']}, "
            . "Location: [{$listing['lat']}, {$listing['lng']}]); ";
    }

} catch (PDOException $e) {
    error_log("Housing Data Query Error: " . $e->getMessage());
    $housingSummary = "No housing data available.";
}

// Include the configuration file with the API key
require_once __DIR__ . '/config.php';

// Send JSON header
header('Content-Type: application/json');

// --- API Key Check ---
if (!defined('DEEPSEEK_API_KEY') || empty(DEEPSEEK_API_KEY) || DEEPSEEK_API_KEY === 'DEEPSEEK_API_KEY') {
     http_response_code(500);
     error_log("FATAL: DeepSeek API Key misconfigured.");
     echo json_encode(['error' => 'AI service configuration error.']);
     exit;
}
$apiKey = DEEPSEEK_API_KEY;

// --- Get Input ---
$rawData = file_get_contents('php://input');
$requestData = json_decode($rawData);

// --- Input Validation ---
if (!$requestData || !isset($requestData->message) || !trim($requestData->message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}
$userMessage = trim($requestData->message);

// --- DeepSeek API Request Prep ---
$apiUrl = 'https://api.deepseek.com/chat/completions';
$systemPrompt = 'You are a helpful assistant for the CROUS-X website. '
    . 'Here is the current housing data: ' . $housingSummary;

$payload = json_encode([
    'model'       => 'deepseek-chat',
    'messages'    => [
        ['role'=>'system','content'=>$systemPrompt],
        ['role'=>'user','content'=>$userMessage]
    ],
    'max_tokens'  => 200,
    'temperature' => 0.7,
]);

// --- cURL Call to DeepSeek ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST,           1);
curl_setopt($ch, CURLOPT_POSTFIELDS,     $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER,     [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT,        30);

$response  = curl_exec($ch);
$httpcode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// --- Handle cURL Errors ---
if ($curlError) {
    http_response_code(500);
    error_log("cURL Error contacting DeepSeek API: " . $curlError);
    echo json_encode(['error' => 'Failed to communicate with AI service.']);
    exit;
}
if ($httpcode >= 400) {
    http_response_code(500);
    error_log("DeepSeek API Error [{$httpcode}]: {$response}");
    $err = json_decode($response)->error->message ?? 'Unknown error';
    echo json_encode(['error' => 'AI service returned an error: ' . $err]);
    exit;
}

// --- Parse and Return AI Reply ---
$respData = json_decode($response, true);
$aiReply  = $respData['choices'][0]['message']['content'] ?? null;
if (!$aiReply) {
    http_response_code(500);
    error_log("Invalid DeepSeek response: {$response}");
    echo json_encode(['error' => 'Invalid response from AI service.']);
    exit;
}

echo json_encode(['reply' => trim($aiReply)]);
