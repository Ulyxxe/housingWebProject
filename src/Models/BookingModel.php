<?php
// src/Models/BookingModel.php

class BookingModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetches all booking applications for a specific user.
     * Includes details about the housing listing and its primary image.
     *
     * @param int $userId The ID of the user whose applications to fetch.
     * @return array An array of booking applications.
     */
    public function getApplicationsByUserId(int $userId): array {
        $sql = "SELECT 
                    b.booking_id,
                    b.listing_id,
                    b.requested_move_in_date,
                    b.user_notes,
                    b.status AS application_status,
                    b.request_date AS application_date,
                    h.title AS housing_title,
                    h.property_type AS housing_property_type,
                    h.rent_amount AS housing_rent_amount,
                    h.rent_frequency AS housing_rent_frequency,
                    hi.image_url AS housing_primary_image
                FROM bookings b
                JOIN housings h ON b.listing_id = h.listing_id
                LEFT JOIN housing_images hi ON h.listing_id = hi.listing_id AND hi.is_primary = 1
                WHERE b.user_id = :user_id
                ORDER BY b.request_date DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching applications by user ID: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    // You could add methods here like:
    // cancelApplication(int $bookingId, int $userId)
}
?>