<?php
// public/edit-housing.php
session_start(); // Must be at the very top

require_once __DIR__ . '/../config/config.php'; // Defines $pdo
require_once __DIR__ . '/../src/Controllers/HousingController.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Save current URL
    header("Location: login.php");
    exit;
}

// 2. Validate Listing ID from GET parameter
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Invalid or missing listing ID.";
    header("Location: my-listings.php"); // Redirect to user's listings page
    exit;
}
$listingId = (int)$_GET['id'];

// 3. Instantiate Controller
$housingController = new HousingController($pdo);

// 4. Route to appropriate controller action based on request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the form submission for updating the listing
    $housingController->handleUpdateListing($listingId);
} else {
    // Display the edit form (GET request)
    $housingController->showEditForm($listingId);
}
?>