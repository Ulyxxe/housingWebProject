<?php
// src/Controllers/HousingController.php

// Assuming HousingModel.php is in the same directory or autoloaded
require_once __DIR__ . '/../Models/HousingModel.php'; 
// Assuming config.php provides $pdo, or it's passed differently
// For this example, we'll expect $pdo to be available where this controller is used.

class HousingController {
    private $housingModel;
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->housingModel = new HousingModel($this->pdo);
    }

    public function myListings() {
        // Ensure user is logged in (this check should ideally be in the entry point or a middleware)
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php"); // Adjust path as needed
            exit;
        }

        $userId = $_SESSION['user_id'];
        $listings = $this->housingModel->getListingsByUserId($userId);

        // Prepare data for the view
        $pageTitle = "My Listings";
        $isLoggedIn = true; // For the header
        $userFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));


        // Load the view
        // The path to views needs to be correct relative to where this controller is included/used
        // If public/my-listings.php includes this, then the path to views is from public/
        require_once __DIR__ . '/../Views/housing/my_listings.php';
    }

    // You might add other actions like:
    // showEditForm($listingId)
    // handleDeleteListing($listingId)
}
?>