<?php
session_start(); // Must be the very first thing

// Check if the user is logged in by verifying session variables
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit; // Stop script execution after redirect
}

// The user is authenticated. Get user info safely.
// Use htmlspecialchars to prevent XSS when echoing data later.
$userEmail = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'N/A';
$userType = isset($_SESSION['user_type']) ? htmlspecialchars($_SESSION['user_type']) : 'N/A';
$userID = $_SESSION['user_id']; // Assuming you might use this later

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Dashboard - CROUS-X</title> <!-- Updated Title -->

    <!-- Leaflet CSS (Optional, keep if header needs it or for consistency) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
         integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
         crossorigin=""/>
    <!-- Marker Cluster CSS (Optional) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css"> <!-- Link your existing CSS -->

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="assets/images/png" href="icon.png"> <!-- Your favicon -->
</head>
<body>

    <?php require 'header.php'; // Or require_once if you prefer ?>

    <!-- Use the main content wrapper for consistent padding/layout -->
    <div class="main-content-wrapper dashboard-page-wrapper"> <!-- Added dashboard-page-wrapper class -->

        <div class="dashboard-container"> <!-- New container for dashboard content -->
            <h1>Welcome Back!</h1>
            <p class="user-info">
                You are logged in as: <strong><?= $userEmail ?></strong> <br>
                Account Type: <strong><?= $userType ?></strong>
            </p>

            <div class="dashboard-actions">
                <!-- Add more dashboard links/actions here later -->
                <a href="profile.php" class="btn btn-register">View Profile</a> <!-- Example action -->
                <a href="logout.php" class="btn btn-signin btn-logout">Logout</a> <!-- Styled logout button -->
            </div>

            <div class="dashboard-content">
                <h2>Your Dashboard</h2>
                <p>This is where your personalized information will appear, such as saved listings, application status, etc.</p>
                <!-- Add more dashboard sections/content here -->
                <div class="placeholder-section">
                    <i class="fas fa-clipboard-list"></i>
                    <p>My Applications (Coming Soon)</p>
                </div>
                 <div class="placeholder-section">
                    <i class="fas fa-heart"></i>
                    <p>Saved Listings (Coming Soon)</p>
                 </div>

            </div>

        </div> <!-- End dashboard-container -->

    </div> <!-- End main-content-wrapper -->

    <!-- Include Leaflet JS if needed by header/other elements -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <!-- Your script.js for dark mode toggle and potentially other interactions -->
    <script src="script.js"></script>
</body>
</html>