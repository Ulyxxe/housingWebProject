<?php
// src/Controllers/BookingController.php

require_once __DIR__ . '/../Models/BookingModel.php';

class BookingController {
    private $bookingModel;
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->bookingModel = new BookingModel($this->pdo);
    }

    public function myApplications() {
        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../../login.php"); // Adjust path as needed
            exit;
        }

        $userId = $_SESSION['user_id'];
        $applications = $this->bookingModel->getApplicationsByUserId($userId);

        // Prepare data for the view
        $pageTitle = "My Applications";
        $isLoggedIn = true; // For the header
        $userFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        // Load the view
        require_once __DIR__ . '/../Views/booking/my_applications.php';
    }
}
?>