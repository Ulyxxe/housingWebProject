<?php
// src/Models/HousingModel.php

class HousingModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getListingsByUserId(int $userId): array {
        // ... (existing method from previous step) ...
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
            error_log("Error fetching listings by user ID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches a single housing listing by its ID and user ID (owner).
     *
     * @param int $listingId The ID of the listing.
     * @param int $userId The ID of the user who should own the listing.
     * @return array|false The listing data if found and owned by user, false otherwise.
     */
    public function getListingByIdAndUserId(int $listingId, int $userId) {
        // Fetches main listing data and current images
        $sql = "SELECT h.* 
                FROM housings h
                WHERE h.listing_id = :listing_id AND h.user_id = :user_id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $listing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($listing) {
                // Fetch images separately
                $stmt_images = $this->pdo->prepare("SELECT image_id, image_url, is_primary FROM housing_images WHERE listing_id = :listing_id ORDER BY is_primary DESC, image_id ASC");
                $stmt_images->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
                $stmt_images->execute();
                $listing['images'] = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
            }
            return $listing; // Returns the listing array (with images) or false if not found/not owned
        } catch (PDOException $e) {
            error_log("Error fetching listing by ID and User ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing housing listing.
     *
     * @param int $listingId The ID of the listing to update.
     * @param int $userId The ID of the user performing the update (owner).
     * @param array $data The associative array of data to update for the 'housings' table.
     * @return bool True on success, false on failure.
     */
    public function updateListing(int $listingId, int $userId, array $data): bool {
        // Construct the SET part of the SQL query dynamically
        $setClauses = [];
        foreach (array_keys($data) as $key) {
            $setClauses[] = "{$key} = :{$key}";
        }
        $setSql = implode(', ', $setClauses);

        if (empty($setSql)) {
            return false; // No data to update
        }

        $sql = "UPDATE housings SET {$setSql}, updated_at = NOW() 
                WHERE listing_id = :listing_id AND user_id = :user_id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $data['listing_id'] = $listingId; // Add listing_id to data for binding
            $data['user_id'] = $userId;       // Add user_id for binding to WHERE clause

            // Bind all values from $data array
            foreach ($data as $key => $value) {
                // Determine PDO param type (basic example, can be more sophisticated)
                $paramType = PDO::PARAM_STR;
                if (is_int($value)) {
                    $paramType = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                    $paramType = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $paramType = PDO::PARAM_NULL;
                }
                $stmt->bindValue(":{$key}", $value, $paramType);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating listing: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Adds an image to a listing.
     */
    public function addImage(int $listingId, string $imageUrl, bool $isPrimary = false): bool {
        $sql = "INSERT INTO housing_images (listing_id, image_url, is_primary, uploaded_at) 
                VALUES (:listing_id, :image_url, :is_primary, NOW())";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
            $stmt->bindParam(':image_url', $imageUrl);
            $stmt->bindParam(':is_primary', $isPrimary, PDO::PARAM_BOOL);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Removes an image by its ID.
     * Also returns the image_url to allow file deletion.
     */
    public function deleteImage(int $imageId, int $listingId, int $userId): ?string {
        // First, get the image_url and verify ownership of the listing
        $sql_select = "SELECT hi.image_url 
                       FROM housing_images hi
                       JOIN housings h ON hi.listing_id = h.listing_id
                       WHERE hi.image_id = :image_id AND hi.listing_id = :listing_id AND h.user_id = :user_id";
        $stmt_select = $this->pdo->prepare($sql_select);
        $stmt_select->bindParam(':image_id', $imageId, PDO::PARAM_INT);
        $stmt_select->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
        $stmt_select->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt_select->execute();
        $image = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$image) {
            return null; // Image not found or not owned
        }

        $sql_delete = "DELETE FROM housing_images WHERE image_id = :image_id";
        try {
            $stmt_delete = $this->pdo->prepare($sql_delete);
            $stmt_delete->bindParam(':image_id', $imageId, PDO::PARAM_INT);
            if ($stmt_delete->execute()) {
                return $image['image_url'];
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error deleting image: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sets a specific image as primary for a listing, unsetting others.
     */
    public function setPrimaryImage(int $listingId, int $imageId, int $userId): bool {
        $this->pdo->beginTransaction();
        try {
            // Ensure the listing belongs to the user
            $stmt_check = $this->pdo->prepare("SELECT user_id FROM housings WHERE listing_id = :listing_id");
            $stmt_check->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
            $stmt_check->execute();
            $owner = $stmt_check->fetchColumn();
            if ($owner != $userId) {
                $this->pdo->rollBack();
                return false; // User doesn't own this listing
            }

            // Unset current primary
            $stmt_unset = $this->pdo->prepare("UPDATE housing_images SET is_primary = 0 WHERE listing_id = :listing_id");
            $stmt_unset->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
            $stmt_unset->execute();

            // Set new primary
            $stmt_set = $this->pdo->prepare("UPDATE housing_images SET is_primary = 1 WHERE image_id = :image_id AND listing_id = :listing_id");
            $stmt_set->bindParam(':image_id', $imageId, PDO::PARAM_INT);
            $stmt_set->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
            $stmt_set->execute();
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error setting primary image: " . $e->getMessage());
            return false;
        }
    }
}
?>