<?php
// public/my-listings.php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Save current URL to redirect back after login
    header("Location: login.php");
    exit;
}

// 2. Include Database Configuration & Controller
require_once __DIR__ . '/../config/config.php'; // Defines $pdo
require_once __DIR__ . '/../src/Controllers/HousingController.php';

// 3. Instantiate Controller and Call Action
try {
    $housingController = new HousingController($pdo); // Pass PDO connection
    $housingController->myListings();
} catch (Exception $e) {
    // Handle any exceptions from the controller or model
    error_log("Error in my-listings.php: " . $e->getMessage());
    // You could show a generic error page here
    echo "An unexpected error occurred. Please try again later.";
    // For debugging: echo $e->getMessage();
}

?>