<?php
// src/Models/HousingModel.php

class HousingModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetches all housing listings for a specific user.
     * Includes the primary image for each listing.
     *
     * @param int $userId The ID of the user whose listings to fetch.
     * @return array An array of housing listings.
     */
    public function getListingsByUserId(int $userId): array {
        $sql = "SELECT 
                    h.*, 
                    hi.image_url AS primary_image,
                    u.username AS owner_username 
                FROM housings h
                LEFT JOIN housing_images hi ON h.listing_id = hi.listing_id AND hi.is_primary = 1
                LEFT JOIN users u ON h.user_id = u.user_id
                WHERE h.user_id = :user_id
                ORDER BY h.created_at DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error or handle it as appropriate for your application
            error_log("Error fetching listings by user ID: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    // You can add other housing-related methods here, e.g.:
    // getListingById(int $listingId)
    // deleteListing(int $listingId, int $userId)
    // updateListing(int $listingId, array $data)
}
?>