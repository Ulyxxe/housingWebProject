<?php
// public/my-applications.php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// 2. Include Database Configuration & Controller
require_once __DIR__ . '/../config/config.php'; // Defines $pdo
require_once __DIR__ . '/../src/Controllers/BookingController.php'; // Assuming new controller

// 3. Instantiate Controller and Call Action
try {
    $bookingController = new BookingController($pdo);
    $bookingController->myApplications();
} catch (Exception $e) {
    error_log("Error in my-applications.php: " . $e->getMessage());
    echo "An unexpected error occurred. Please try again later.";
    // For debugging: echo $e->getMessage();
}
?>