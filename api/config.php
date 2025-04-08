<?php
// api/config.php

/**
 * Configuration for DeepSeek API Key.
 *
 * This file retrieves the DeepSeek API key from an environment variable
 * and defines it as a constant ('DeepSeek_Api_Key') for use in the application.
 */

// --- Define the EXACT Name of the Environment Variable You Set ---
// Make sure this matches the name you used when setting the variable
// in your server environment (e.g., Apache, Nginx, Docker, system).
$envVariableName = 'DEEPSEEK_API_KEY'; // COMMON CONVENTION, CHANGE IF YOURS IS DIFFERENT

// --- Retrieve API Key from Environment ---
// getenv() is generally the most reliable way to fetch environment variables.
$apiKey = getenv($envVariableName);

// --- Define the Constant ---
// The chat_handler.php script expects a constant named 'DeepSeek_Api_Key'.
// We define it here using the value from the environment variable.

// Check if the environment variable was retrieved successfully AND is not empty/whitespace.
if ($apiKey !== false && !empty(trim($apiKey))) {
    // Environment variable found and has a non-empty value.
    define('DEEPSEEK_API_KEY', trim($apiKey));
} else {
    // Environment variable was not found, or it was empty/whitespace.
    // Define the constant as an empty string. The check in chat_handler.php
    // (!defined(...) || empty(...) ) will correctly catch this state.
    define('DEEPSEEK_API_KEY', '');

    // Optional but Recommended: Log a warning server-side during setup/startup
    // if the key is missing, to help with debugging deployment issues.
    // Avoid logging this on every request if possible.
    // error_log("WARNING: Environment variable '{$envVariableName}' for DeepSeek API key is not set or is empty.");
}

// No closing PHP tag needed if this is the end of the file (PHP best practice)