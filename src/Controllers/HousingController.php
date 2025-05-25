<?php
// src/Controllers/HousingController.php

require_once __DIR__ . '/../Models/HousingModel.php';

class HousingController {
    private $housingModel;
    private $pdo;

    // --- Configuration for Image Uploads (can be moved to a config file or constants) ---
    private const UPLOAD_DIR_BASE = __DIR__ . '/../../public/assets/uploads/housing_images/'; // Path from src to public
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    private $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];


    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->housingModel = new HousingModel($this->pdo);
    }

    public function myListings() {
        // ... (existing method from previous step) ...
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../../login.php"); // Adjusted path
            exit;
        }
        $userId = $_SESSION['user_id'];
        $listings = $this->housingModel->getListingsByUserId($userId);
        $pageTitle = "My Listings";
        $isLoggedIn = true;
        $userFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        require_once __DIR__ . '/../Views/housing/my_listings.php';
    }

    public function showEditForm(int $listingId) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../../login.php"); // Adjusted path
            exit;
        }
        $userId = $_SESSION['user_id'];
        $listing = $this->housingModel->getListingByIdAndUserId($listingId, $userId);

        if (!$listing) {
            $_SESSION['error_message'] = "Listing not found or you don't have permission to edit it.";
            header("Location: my-listings.php"); // Relative path to the entry point
            exit;
        }

        // Data for form dropdowns (same as add-housing.php)
        $property_types = ['Studio', 'Apartment', 'Shared Room', 'House', 'Other'];
        $rent_frequencies = ['monthly', 'weekly', 'annually'];
        $listing_statuses = ['available', 'pending_approval', 'unavailable']; // Consider 'rented' if applicable

        $pageTitle = "Edit Listing: " . htmlspecialchars($listing['title']);
        $isLoggedIn = true;
        $userFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        
        // Preserve any form data/errors from a failed POST attempt
        $form_data = $_SESSION['form_data'] ?? $listing; // Use session data if exists, else DB data
        $errors = $_SESSION['form_errors'] ?? [];
        unset($_SESSION['form_data'], $_SESSION['form_errors']);

        require_once __DIR__ . '/../Views/housing/edit_listing.php';
    }

    public function handleUpdateListing(int $listingId) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../../login.php"); // Adjusted path
            exit;
        }
        $userId = $_SESSION['user_id'];

        // First, verify ownership again before any processing
        $currentListing = $this->housingModel->getListingByIdAndUserId($listingId, $userId);
        if (!$currentListing) {
            $_SESSION['error_message'] = "Listing not found or you don't have permission to edit it.";
            header("Location: my-listings.php");
            exit;
        }

        $errors = [];
        $housingData = []; // Data for 'housings' table

        // Retrieve and validate form data (similar to add-housing.php)
        // Basic example, expand with all your fields and validations
        $housingData['title'] = trim($_POST['title'] ?? '');
        if (empty($housingData['title'])) $errors['title'] = "Title is required.";
        
        $housingData['description'] = trim($_POST['description'] ?? '');
        // ... (all other field retrievals and validations from add-housing.php)
        $housingData['address_street'] = trim($_POST['address_street'] ?? '');
        $housingData['address_city'] = trim($_POST['address_city'] ?? '');
        $housingData['address_state'] = trim($_POST['address_state'] ?? '');
        $housingData['address_zipcode'] = trim($_POST['address_zipcode'] ?? '');
        $housingData['address_country'] = trim($_POST['address_country'] ?? '');
        
        $housingData['latitude'] = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
        $housingData['longitude'] = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
        
        $property_types = ['Studio', 'Apartment', 'Shared Room', 'House', 'Other'];
        $housingData['property_type'] = in_array($_POST['property_type'] ?? '', $property_types) ? $_POST['property_type'] : null;
        
        $housingData['rent_amount'] = filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
        $rent_frequencies = ['monthly', 'weekly', 'annually'];
        $housingData['rent_frequency'] = in_array($_POST['rent_frequency'] ?? '', $rent_frequencies) ? $_POST['rent_frequency'] : null;
        
        $housingData['num_bedrooms'] = filter_input(INPUT_POST, 'num_bedrooms', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 0]]);
        $housingData['num_bathrooms'] = filter_input(INPUT_POST, 'num_bathrooms', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 0]]);
        $housingData['square_footage'] = filter_input(INPUT_POST, 'square_footage', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]);
        
        $availability_date_str = trim($_POST['availability_date'] ?? '');
        $housingData['availability_date'] = null;
        if (!empty($availability_date_str)) {
            $d = DateTime::createFromFormat('Y-m-d', $availability_date_str);
            if ($d && $d->format('Y-m-d') === $availability_date_str) {
                $housingData['availability_date'] = $availability_date_str;
            } else {
                $errors['availability_date'] = "Invalid availability date format. Use YYYY-MM-DD.";
            }
        } else {
             $errors['availability_date'] = "Availability date is required.";
        }

        $housingData['lease_term_months'] = filter_input(INPUT_POST, 'lease_term_months', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]);
        if (!empty($_POST['lease_term_months']) && $housingData['lease_term_months'] === null) $errors['lease_term_months'] = "Lease term must be a valid number if provided.";


        $housingData['is_furnished'] = isset($_POST['is_furnished']) ? 1 : 0;
        $housingData['allows_pets'] = isset($_POST['allows_pets']) ? 1 : 0;
        
        $housingData['contact_email'] = filter_input(INPUT_POST, 'contact_email', FILTER_VALIDATE_EMAIL);
        if ($housingData['contact_email'] === false) $errors['contact_email'] = "A valid contact email is required.";
        $housingData['contact_phone'] = trim($_POST['contact_phone'] ?? '');
        
        $listing_statuses = ['available', 'pending_approval', 'unavailable'];
        $housingData['status'] = in_array($_POST['status'] ?? '', $listing_statuses) ? $_POST['status'] : 'pending_approval';


        // --- Image Deletion ---
        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $imageIdToDelete) {
                $imageIdToDelete = (int)$imageIdToDelete;
                $imageUrlToDelete = $this->housingModel->deleteImage($imageIdToDelete, $listingId, $userId);
                if ($imageUrlToDelete) {
                    $filePath = self::UPLOAD_DIR_BASE . basename($imageUrlToDelete); // Use basename for security
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }

        // --- Set New Primary Image ---
        if (isset($_POST['set_primary_image']) && filter_var($_POST['set_primary_image'], FILTER_VALIDATE_INT)) {
            $newPrimaryImageId = (int)$_POST['set_primary_image'];
            // Verify this image belongs to this listing (optional, deleteImage already checks ownership via listing)
            $this->housingModel->setPrimaryImage($listingId, $newPrimaryImageId, $userId);
        }


        // --- Handle New Primary Image Upload ---
        if (isset($_FILES['primary_image_new']) && $_FILES['primary_image_new']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['primary_image_new']['tmp_name'];
            $file_name = $_FILES['primary_image_new']['name'];
            $file_size = $_FILES['primary_image_new']['size'];
            $file_type = $_FILES['primary_image_new']['type']; // Using browser-supplied for simplicity here
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($file_type, $this->allowed_mime_types) || !in_array($file_extension, $this->allowed_extensions)) {
                $errors['primary_image_new'] = "Invalid file type for new primary image.";
            } elseif ($file_size > self::MAX_FILE_SIZE) {
                $errors['primary_image_new'] = "New primary image exceeds max size.";
            } else {
                $new_filename = uniqid('primary_', true) . '.' . $file_extension;
                $destination_path = self::UPLOAD_DIR_BASE . $new_filename;
                $relative_path = 'assets/uploads/housing_images/' . $new_filename;

                if (move_uploaded_file($file_tmp_path, $destination_path)) {
                    // Unset old primary, then add new one as primary
                    $this->pdo->beginTransaction();
                    $stmt_unset = $this->pdo->prepare("UPDATE housing_images SET is_primary = 0 WHERE listing_id = :listing_id");
                    $stmt_unset->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
                    $stmt_unset->execute();
                    $this->housingModel->addImage($listingId, $relative_path, true);
                    $this->pdo->commit();
                } else {
                    $errors['primary_image_new_move'] = "Failed to move new primary image.";
                }
            }
        }
        
        // --- Handle New Other Images Upload ---
        if (isset($_FILES['other_images_new'])) {
            foreach ($_FILES['other_images_new']['name'] as $key => $name) {
                if ($_FILES['other_images_new']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_tmp_path = $_FILES['other_images_new']['tmp_name'][$key];
                    $file_name = $name;
                    $file_size = $_FILES['other_images_new']['size'][$key];
                    $file_type = $_FILES['other_images_new']['type'][$key];
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    if (!in_array($file_type, $this->allowed_mime_types) || !in_array($file_extension, $this->allowed_extensions)) {
                        $errors['other_images_new_' . $key] = "Invalid file type for image '{$file_name}'.";
                    } elseif ($file_size > self::MAX_FILE_SIZE) {
                         $errors['other_images_new_' . $key] = "Image '{$file_name}' exceeds max size.";
                    } else {
                        $new_filename = uniqid('other_', true) . '_' . $key . '.' . $file_extension;
                        $destination_path = self::UPLOAD_DIR_BASE . $new_filename;
                        $relative_path = 'assets/uploads/housing_images/' . $new_filename;
                        if (move_uploaded_file($file_tmp_path, $destination_path)) {
                            $this->housingModel->addImage($listingId, $relative_path, false);
                        } else {
                            $errors['other_images_new_move_' . $key] = "Failed to move image '{$name}'.";
                        }
                    }
                }
            }
        }


        if (empty($errors)) {
            if ($this->housingModel->updateListing($listingId, $userId, $housingData)) {
                $_SESSION['success_message'] = "Listing updated successfully!";
                header("Location: my-listings.php");
                exit;
            } else {
                $errors['database'] = "Failed to update listing details in the database.";
            }
        }

        // If errors occurred or DB update failed, store data and errors in session and redirect back to form
        $_SESSION['form_data'] = $_POST; // Save submitted data to re-populate
        $_SESSION['form_errors'] = $errors;
        header("Location: edit-housing.php?id=" . $listingId);
        exit;
    }
}
?>