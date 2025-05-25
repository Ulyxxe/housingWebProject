<?php
namespace App\Models; // Namespace based on composer.json

use PDO; // So you can use PDO type hint
use PDOException;

class HousingModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    public function getAllListings(): array {
        try {
            $sql = "SELECT h.*, hi.image_url AS primary_image -- Changed alias to primary_image
                    FROM housings h
                    LEFT JOIN housing_images hi ON h.listing_id = hi.listing_id AND hi.is_primary = 1";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error, throw exception, or return empty array/null
            error_log("Error fetching all listings: " . $e->getMessage());
            return [];
        }
    }

    public function findById(int $id): ?array {
        try {
            $sql = "SELECT h.*, hi.image_url AS primary_image
                    FROM housings h
                    LEFT JOIN housing_images hi ON h.listing_id = hi.listing_id AND hi.is_primary = 1
                    WHERE h.listing_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $housing = $stmt->fetch(PDO::FETCH_ASSOC);
            return $housing ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching housing by ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    // Add other methods like addListing, updateListing, etc.
    // Example for add-housing.php logic:
    public function addListing(array $data): ?int {
        // (Simplified - add full validation and error handling)
        // Ensure all required fields are in $data
        $sql_housing = "INSERT INTO housings (user_id, title, description, address_street, /* ... more columns ... */ status, created_at, updated_at)
                        VALUES (:user_id, :title, :description, :address_street, /* ... more params ... */ :status, NOW(), NOW())";
        try {
            $stmt_housing = $this->db->prepare($sql_housing);
            // Bind all parameters from $data array
            $stmt_housing->bindParam(':user_id', $data['user_id']);
            $stmt_housing->bindParam(':title', $data['title']);
            // ... bind all other parameters ...
            $stmt_housing->bindParam(':status', $data['status']);

            $stmt_housing->execute();
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Add Housing DB Error: " . $e->getMessage());
            return null;
        }
    }

    public function addImage(int $listingId, string $imageUrl, bool $isPrimary): bool {
        $sql = "INSERT INTO housing_images (listing_id, image_url, is_primary, uploaded_at)
                VALUES (:listing_id, :image_url, :is_primary, NOW())";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
            $stmt->bindParam(':image_url', $imageUrl);
            $stmt->bindParam(':is_primary', $isPrimary, PDO::PARAM_BOOL);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Add Image DB Error: " . $e->getMessage());
            return false;
        }
    }
}
?>