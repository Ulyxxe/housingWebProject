<?php
// public/edit-housing.php
session_start(); // Must be at the very top

require_once __DIR__ . '/../config/config.php'; // Defines $pdo
// We are not using HousingController.php directly here based on the constraint.
// Logic will be embedded or use direct model calls if we had one.

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$is_admin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$current_user_id = $_SESSION['user_id'];

// 2. Validate Listing ID from GET parameter
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['admin_message'] = "Invalid or missing listing ID."; // Use admin_message for admin panel feedback
    $_SESSION['admin_message_type'] = "error";
    header("Location: " . ($is_admin ? "admin_manage_listings.php" : "my-listings.php"));
    exit;
}
$listingId = (int)$_GET['id'];

// --- Data for form dropdowns (same as add-housing.php) ---
$property_types = ['Studio', 'Apartment', 'Shared Room', 'House', 'Other'];
$rent_frequencies = ['monthly', 'weekly', 'annually'];
$listing_statuses = ['available', 'pending_approval', 'unavailable', 'rented']; // Added 'rented'

$listing = null;
$form_data_source = []; // To hold data for form population
$page_errors = [];     // To hold errors specific to this page load/submission

// --- Fetch Listing Data ---
try {
    if ($is_admin) {
        // Admin can fetch any listing
        $stmt_listing = $pdo->prepare("SELECT h.* FROM housings h WHERE h.listing_id = :listing_id");
        $stmt_listing->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
    } else {
        // Regular user can only fetch their own listing
        $stmt_listing = $pdo->prepare("SELECT h.* FROM housings h WHERE h.listing_id = :listing_id AND h.user_id = :user_id");
        $stmt_listing->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
        $stmt_listing->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    }
    $stmt_listing->execute();
    $listing = $stmt_listing->fetch(PDO::FETCH_ASSOC);

    if ($listing) {
        // Fetch images
        $stmt_images = $pdo->prepare("SELECT image_id, image_url, is_primary FROM housing_images WHERE listing_id = :listing_id ORDER BY is_primary DESC, image_id ASC");
        $stmt_images->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
        $stmt_images->execute();
        $listing['images'] = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Edit Housing - Fetch Error: " . $e->getMessage());
    $page_errors['database_fetch'] = "Could not load listing data: " . $e->getMessage();
    // No redirect here, let the form display the error
}

if (!$listing && empty($page_errors)) {
    $_SESSION['admin_message'] = "Listing not found or you don't have permission to edit it.";
    $_SESSION['admin_message_type'] = "error";
    header("Location: " . ($is_admin ? "admin_manage_listings.php" : "my-listings.php"));
    exit;
}

// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $listing) { // Ensure $listing was fetched
    // (Validation and Update logic will go here - similar to add-housing.php, adapted for update)
    // This part is extensive and similar to add-housing.php's POST handling.
    // For brevity in this example, we'll assume the update logic is complex and
    // refer to add-housing.php for the pattern.
    // Key differences for update:
    // - SQL will be UPDATE instead of INSERT.
    // - Image handling will involve deleting old, adding new, setting primary.

    $housingData = []; // Data for 'housings' table
    $form_errors_on_submit = [];

    // Retrieve and validate ALL form data (similar to add-housing.php)
    $housingData['title'] = trim($_POST['title'] ?? '');
    if (empty($housingData['title'])) $form_errors_on_submit['title'] = "Title is required.";
    
    $housingData['description'] = trim($_POST['description'] ?? '');
    if (empty($housingData['description'])) $form_errors_on_submit['description'] = "Description is required.";

    $housingData['address_street'] = trim($_POST['address_street'] ?? '');
    // ... (all other address fields) ...
    $housingData['address_city'] = trim($_POST['address_city'] ?? '');
    $housingData['address_state'] = trim($_POST['address_state'] ?? '');
    $housingData['address_zipcode'] = trim($_POST['address_zipcode'] ?? '');
    $housingData['address_country'] = trim($_POST['address_country'] ?? '');
    
    $housingData['latitude'] = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
    $housingData['longitude'] = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
    
    $housingData['property_type'] = in_array($_POST['property_type'] ?? '', $property_types) ? $_POST['property_type'] : null;
    if (empty($housingData['property_type'])) $form_errors_on_submit['property_type'] = "Property type is required.";
    
    $housingData['rent_amount'] = filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
    if ($housingData['rent_amount'] === null || $housingData['rent_amount'] <=0) $form_errors_on_submit['rent_amount'] = "Valid rent amount required.";

    $housingData['rent_frequency'] = in_array($_POST['rent_frequency'] ?? '', $rent_frequencies) ? $_POST['rent_frequency'] : null;
     if (empty($housingData['rent_frequency'])) $form_errors_on_submit['rent_frequency'] = "Rent frequency is required.";

    // ... (num_bedrooms, num_bathrooms, square_footage, availability_date, lease_term, furnished, pets, contact_email, phone, status) ...
    // Example for availability_date
    $availability_date_str = trim($_POST['availability_date'] ?? '');
    if (empty($availability_date_str)) {
        $form_errors_on_submit['availability_date'] = "Availability date is required.";
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $availability_date_str);
        if ($d && $d->format('Y-m-d') === $availability_date_str) {
            $housingData['availability_date'] = $availability_date_str;
        } else {
            $form_errors_on_submit['availability_date'] = "Invalid availability date format. Use YYYY-MM-DD.";
        }
    }
    $housingData['status'] = in_array($_POST['status'] ?? '', $listing_statuses) ? $_POST['status'] : 'pending_approval';
    $housingData['is_furnished'] = isset($_POST['is_furnished']) ? 1 : 0;
    $housingData['allows_pets'] = isset($_POST['allows_pets']) ? 1 : 0;
    // ... add all other fields and their validations from add-housing.php

    // --- Image Deletion ---
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $imageIdToDelete) {
            $imageIdToDelete = (int)$imageIdToDelete;
            // Fetch image URL to delete file
            $stmt_img_url = $pdo->prepare("SELECT image_url FROM housing_images WHERE image_id = :image_id AND listing_id = :listing_id");
            $stmt_img_url->execute(['image_id' => $imageIdToDelete, 'listing_id' => $listingId]);
            $imageUrlToDelete = $stmt_img_url->fetchColumn();

            if ($imageUrlToDelete) {
                $stmt_del_img = $pdo->prepare("DELETE FROM housing_images WHERE image_id = :image_id");
                if ($stmt_del_img->execute(['image_id' => $imageIdToDelete])) {
                    $filePath = __DIR__ . '/' . $imageUrlToDelete; // Assuming paths are relative to public
                    if (strpos($imageUrlToDelete, 'assets/uploads/housing_images/') === 0) { // Basic security check
                       $filePath = __DIR__ . '/../../' . $imageUrlToDelete; // Correct path from public/edit-housing.php
                    } else {
                       $filePath = __DIR__ . '/assets/uploads/housing_images/' . basename($imageUrlToDelete);
                    }
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }
    }

    // --- Set New Primary Image ---
    if (isset($_POST['set_primary_image']) && filter_var($_POST['set_primary_image'], FILTER_VALIDATE_INT)) {
        $newPrimaryImageId = (int)$_POST['set_primary_image'];
        $pdo->beginTransaction();
        try {
            $stmt_unset = $pdo->prepare("UPDATE housing_images SET is_primary = 0 WHERE listing_id = :listing_id");
            $stmt_unset->execute(['listing_id' => $listingId]);
            $stmt_set = $pdo->prepare("UPDATE housing_images SET is_primary = 1 WHERE image_id = :image_id AND listing_id = :listing_id");
            $stmt_set->execute(['image_id' => $newPrimaryImageId, 'listing_id' => $listingId]);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $form_errors_on_submit['primary_image_set'] = "Failed to set primary image: " . $e->getMessage();
        }
    }
    
    // --- Handle New Primary Image Upload (if 'primary_image_new' is provided) ---
    // (Similar logic to add-housing.php, ensure UPLOAD_DIR_BASE is correct)
    define('UPLOAD_DIR_BASE_EDIT', __DIR__ . '/assets/uploads/housing_images/'); // Path from public/edit-housing.php
    define('MAX_FILE_SIZE_EDIT', 5 * 1024 * 1024);
    $allowed_mime_types_edit = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_extensions_edit = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (isset($_FILES['primary_image_new']) && $_FILES['primary_image_new']['error'] === UPLOAD_ERR_OK) {
        // ... (validation for new primary image: type, size) ...
        $file_tmp_path = $_FILES['primary_image_new']['tmp_name'];
        $file_name = $_FILES['primary_image_new']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($_FILES['primary_image_new']['type'], $allowed_mime_types_edit) && $_FILES['primary_image_new']['size'] <= MAX_FILE_SIZE_EDIT) {
            $new_filename = uniqid('primary_', true) . '.' . $file_extension;
            $destination_path = UPLOAD_DIR_BASE_EDIT . $new_filename;
            $relative_path = 'assets/uploads/housing_images/' . $new_filename;
            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                 $pdo->beginTransaction();
                 // Unset old primary
                 $stmt_unset = $pdo->prepare("UPDATE housing_images SET is_primary = 0 WHERE listing_id = :listing_id");
                 $stmt_unset->execute(['listing_id' => $listingId]);
                 // Add new as primary
                 $stmt_add_img = $pdo->prepare("INSERT INTO housing_images (listing_id, image_url, is_primary, uploaded_at) VALUES (:listing_id, :url, 1, NOW())");
                 $stmt_add_img->execute(['listing_id' => $listingId, 'url' => $relative_path]);
                 $pdo->commit();
            } else { $form_errors_on_submit['primary_image_new'] = "Failed to move new primary image."; }
        } else { $form_errors_on_submit['primary_image_new'] = "Invalid file type or size for new primary image."; }
    }

    // --- Handle New Other Images Upload ---
    // (Similar logic to add-housing.php)
    if (isset($_FILES['other_images_new'])) {
        foreach ($_FILES['other_images_new']['name'] as $key => $name) {
            if ($_FILES['other_images_new']['error'][$key] === UPLOAD_ERR_OK) {
                // ... (validation for other images) ...
                 $file_tmp_path_other = $_FILES['other_images_new']['tmp_name'][$key];
                 $file_name_other = $_FILES['other_images_new']['name'][$key];
                 $file_extension_other = strtolower(pathinfo($file_name_other, PATHINFO_EXTENSION));
                 if (in_array($_FILES['other_images_new']['type'][$key], $allowed_mime_types_edit) && $_FILES['other_images_new']['size'][$key] <= MAX_FILE_SIZE_EDIT) {
                    $new_filename_other = uniqid('other_', true) . '_' . $key . '.' . $file_extension_other;
                    $destination_path_other = UPLOAD_DIR_BASE_EDIT . $new_filename_other;
                    $relative_path_other = 'assets/uploads/housing_images/' . $new_filename_other;
                    if (move_uploaded_file($file_tmp_path_other, $destination_path_other)) {
                        $stmt_add_other_img = $pdo->prepare("INSERT INTO housing_images (listing_id, image_url, is_primary, uploaded_at) VALUES (:listing_id, :url, 0, NOW())");
                        $stmt_add_other_img->execute(['listing_id' => $listingId, 'url' => $relative_path_other]);
                    } else { $form_errors_on_submit['other_images_new_'.$key] = "Failed to move image {$file_name_other}."; }
                 } else { $form_errors_on_submit['other_images_new_'.$key] = "Invalid file type or size for image {$file_name_other}."; }
            }
        }
    }


    if (empty($form_errors_on_submit)) {
        // Construct the SET part of the SQL query dynamically
        $setClauses = [];
        foreach (array_keys($housingData) as $key) {
            if ($housingData[$key] !== null || $key === 'lease_term_months' || $key === 'address_state' || $key === 'address_zipcode' || $key === 'contact_phone') { // Allow null for optional fields
                 $setClauses[] = "{$key} = :{$key}";
            }
        }
        $setSql = implode(', ', $setClauses);

        if (!empty($setSql)) {
            $update_sql = "UPDATE housings SET {$setSql}, updated_at = NOW() WHERE listing_id = :listing_id";
            // Add user_id to WHERE clause only if not admin, for an extra layer of security for non-admins
            if (!$is_admin) {
                $update_sql .= " AND user_id = :user_id_owner_check";
            }
            
            try {
                $stmt_update = $pdo->prepare($update_sql);
                // Bind all values from $housingData
                foreach ($housingData as $key => &$value) { // Pass $value by reference
                     if ($housingData[$key] !== null || $key === 'lease_term_months' || $key === 'address_state' || $key === 'address_zipcode' || $key === 'contact_phone') {
                        $stmt_update->bindParam(":{$key}", $value);
                     }
                }
                unset($value); // Unset reference
                $stmt_update->bindParam(':listing_id', $listingId, PDO::PARAM_INT);
                if (!$is_admin) {
                    $stmt_update->bindParam(':user_id_owner_check', $current_user_id, PDO::PARAM_INT);
                }
                
                if ($stmt_update->execute()) {
                    $_SESSION['admin_message'] = "Listing updated successfully!";
                    $_SESSION['admin_message_type'] = "success";
                    // Refresh $listing data after update for display
                    // This is important if we don't redirect immediately
                    header("Location: edit-housing.php?id=" . $listingId . "&update=success"); // Redirect to refresh and show message
                    exit;
                } else {
                    $form_errors_on_submit['database'] = "Failed to update listing details in the database.";
                }
            } catch (PDOException $e) {
                error_log("Update Housing Error: " . $e->getMessage());
                $form_errors_on_submit['database'] = "Database error during update: " . $e->getMessage();
            }
        } else {
             $form_errors_on_submit['general'] = "No data provided to update.";
        }
    }
    // If errors occurred, merge them with page_errors for display
    $page_errors = array_merge($page_errors, $form_errors_on_submit);
    $form_data_source = $_POST; // Re-populate form with submitted data
}


// After POST or for GET request, prepare data for the form.
// If it was a POST and failed, $form_data_source will be $_POST. Otherwise, it's the fetched $listing.
$current_listing_data = $form_data_source ?: ($listing ?? []); // Use submitted data if available, else fetched listing

// If redirected with success, show message
if (isset($_GET['update']) && $_GET['update'] === 'success' && isset($_SESSION['admin_message'])) {
    // Message is already in session, will be displayed by the HTML block
}

$pageTitle = "Edit Listing: " . htmlspecialchars($listing['title'] ?? 'Unknown Listing');
?>
<!DOCTYPE html>
<!-- The rest of the HTML structure for edit_listing.php (form) is largely the same as src/Views/housing/edit_listing.php -->
<!-- Key differences:
    - Action for the form: `edit-housing.php?id=<?php echo htmlspecialchars($listingId); ?>`
    - Values for inputs: `htmlspecialchars($current_listing_data['column_name'] ?? '')`
    - Loop for current images: `foreach ($listing['images'] as $img)`
    - Paths for CSS/JS/Images will be relative to `public/` (e.g., `assets/images/icon.png`, `css/global.css`)
-->
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - CROUS-X</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/forms.css"> 
    <link rel="stylesheet" href="css/add-housing.css"> <!-- Reusing add-housing for form styles -->
    <style>
        .current-images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .current-image-item { position: relative; border: 1px solid var(--grey-border); padding: 0.5rem; border-radius: 6px; text-align: center; background-color: var(--input-bg); }
        .current-image-item img { max-width: 100%; height: 100px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem; }
        .current-image-item label { font-size: 0.8rem; display: block; margin-top: 0.3rem; color: var(--text-secondary); }
        .current-image-item input[type="checkbox"], .current-image-item input[type="radio"] { margin-right: 0.3em; accent-color: var(--accent-primary); }
        .primary-star { color: gold; font-size: 1.2em; }
        .form-group small { display: block; font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.3rem; }
    </style>
</head>
<body>

    <?php require 'header.php'; ?>

    <main class="app-container">
        <div class="add-housing-form-container"> <!-- Reusing class from add-housing.css -->
            <h2 class="add-housing-form-title"><?php echo htmlspecialchars($pageTitle); ?></h2>

            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="form-message <?php echo ($_SESSION['admin_message_type'] ?? 'success') === 'success' ? 'success-message' : 'error-message'; ?>">
                    <i class="fas <?php echo ($_SESSION['admin_message_type'] ?? 'success') === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i> 
                    <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
                </div>
                <?php unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); ?>
            <?php endif; ?>
            
            <?php if (!empty($page_errors)): ?>
                <div class="form-message error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Please correct the following errors:</strong>
                    <ul>
                        <?php foreach ($page_errors as $field => $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($listing): // Only show form if listing data was successfully fetched ?>
            <form action="edit-housing.php?id=<?php echo htmlspecialchars($listingId); ?>" method="post" id="editHousingForm" class="add-housing-form" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($current_listing_data['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($current_listing_data['description'] ?? ''); ?></textarea>
                </div>
                
                <h3 style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Address Details</h3>
                <div class="form-group">
                    <label for="address_street_autocomplete">Search Address (Street, City) *</label>
                    <input type="text" id="address_street_autocomplete" placeholder="Start typing address to update...">
                    <small>Select an address from suggestions to auto-fill fields below. Current: <?php echo htmlspecialchars(($listing['address_street'] ?? '').', '.($listing['address_city'] ?? '')); ?></small>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="address_street">Street Address (Confirmed) *</label>
                        <input type="text" id="address_street" name="address_street" value="<?php echo htmlspecialchars($current_listing_data['address_street'] ?? ''); ?>" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="address_city">City (Confirmed) *</label>
                        <input type="text" id="address_city" name="address_city" value="<?php echo htmlspecialchars($current_listing_data['address_city'] ?? ''); ?>" required readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="address_state">State/Province</label>
                        <input type="text" id="address_state" name="address_state" value="<?php echo htmlspecialchars($current_listing_data['address_state'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="address_zipcode">Zip/Postal Code</label>
                        <input type="text" id="address_zipcode" name="address_zipcode" value="<?php echo htmlspecialchars($current_listing_data['address_zipcode'] ?? ''); ?>" readonly>
                    </div>
                     <div class="form-group">
                        <label for="address_country">Country *</label>
                        <input type="text" id="address_country" name="address_country" value="<?php echo htmlspecialchars($current_listing_data['address_country'] ?? ''); ?>" required readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Latitude *</label>
                        <input type="number" id="latitude" name="latitude" step="any" value="<?php echo htmlspecialchars($current_listing_data['latitude'] ?? ''); ?>" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="longitude">Longitude *</label>
                        <input type="number" id="longitude" name="longitude" step="any" value="<?php echo htmlspecialchars($current_listing_data['longitude'] ?? ''); ?>" required readonly>
                    </div>
                </div>

                <h3 style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Manage Images</h3>
                <?php if (!empty($listing['images'])): ?>
                    <h4>Current Images:</h4>
                    <div class="current-images-grid">
                        <?php foreach ($listing['images'] as $img): ?>
                            <div class="current-image-item">
                                <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="Current image">
                                <input type="checkbox" name="delete_images[]" value="<?php echo $img['image_id']; ?>" id="delete_img_<?php echo $img['image_id']; ?>">
                                <label for="delete_img_<?php echo $img['image_id']; ?>">Delete</label><br>
                                <?php if (!$img['is_primary']): ?>
                                    <input type="radio" name="set_primary_image" value="<?php echo $img['image_id']; ?>" id="set_primary_<?php echo $img['image_id']; ?>">
                                    <label for="set_primary_<?php echo $img['image_id']; ?>">Set as Primary</label>
                                <?php else: ?>
                                    <span class="primary-star" title="Primary Image">★</span> Primary
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No images currently uploaded for this listing.</p>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="primary_image_new">Upload New Primary Image (optional, replaces current primary)</label>
                    <input type="file" id="primary_image_new" name="primary_image_new" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="other_images_new">Upload Additional Images (Max 4, optional)</label>
                    <input type="file" id="other_images_new" name="other_images_new[]" multiple accept="image/*">
                </div>

                <h3 style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Property Details</h3>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="property_type">Property Type *</label>
                        <select id="property_type" name="property_type" required>
                            <option value="">-- Select Type --</option>
                            <?php foreach ($property_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($current_listing_data['property_type']) && $current_listing_data['property_type'] == $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="square_footage">Square Footage (m²) *</label>
                        <input type="number" id="square_footage" name="square_footage" min="1" value="<?php echo htmlspecialchars($current_listing_data['square_footage'] ?? ''); ?>" required>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="num_bedrooms">Number of Bedrooms *</label>
                        <input type="number" id="num_bedrooms" name="num_bedrooms" min="0" value="<?php echo htmlspecialchars($current_listing_data['num_bedrooms'] ?? '1'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="num_bathrooms">Number of Bathrooms *</label>
                        <input type="number" id="num_bathrooms" name="num_bathrooms" min="0" step="0.5" value="<?php echo htmlspecialchars($current_listing_data['num_bathrooms'] ?? '1'); ?>" required>
                    </div>
                </div>

                <h3 style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Rental Information</h3>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="rent_amount">Rent Amount *</label>
                        <input type="number" id="rent_amount" name="rent_amount" min="0.01" step="0.01" value="<?php echo htmlspecialchars($current_listing_data['rent_amount'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="rent_frequency">Rent Frequency *</label>
                        <select id="rent_frequency" name="rent_frequency" required>
                             <option value="">-- Select Frequency --</option>
                            <?php foreach ($rent_frequencies as $freq): ?>
                                <option value="<?php echo htmlspecialchars($freq); ?>" <?php echo (isset($current_listing_data['rent_frequency']) && $current_listing_data['rent_frequency'] == $freq) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($freq)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="availability_date">Availability Date *</label>
                        <input type="date" id="availability_date" name="availability_date" value="<?php echo htmlspecialchars($current_listing_data['availability_date'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lease_term_months">Lease Term (Months, optional)</label>
                        <input type="number" id="lease_term_months" name="lease_term_months" min="1" value="<?php echo htmlspecialchars($current_listing_data['lease_term_months'] ?? ''); ?>">
                    </div>
                </div>

                <h3 style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Features & Contact</h3>
                <div class="form-row">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_furnished" name="is_furnished" value="1" <?php echo (isset($current_listing_data['is_furnished']) && $current_listing_data['is_furnished'] == 1) ? 'checked' : ''; ?>>
                            <label for="is_furnished">Is Furnished?</label>
                        </div>
                    </div>
                     <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="allows_pets" name="allows_pets" value="1" <?php echo (isset($current_listing_data['allows_pets']) && $current_listing_data['allows_pets'] == 1) ? 'checked' : ''; ?>>
                            <label for="allows_pets">Allows Pets?</label>
                        </div>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">Contact Email *</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($current_listing_data['contact_email'] ?? ($_SESSION['email'] ?? '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone (optional)</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($current_listing_data['contact_phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Listing Status *</label>
                    <select id="status" name="status" required>
                        <?php foreach ($listing_statuses as $stat): ?>
                            <option value="<?php echo htmlspecialchars($stat); ?>" <?php echo (isset($current_listing_data['status']) && $current_listing_data['status'] == $stat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $stat))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-submit-listing">Update Listing</button>
            </form>
            <?php else: ?>
                <p>Could not load listing data for editing. It might have been deleted or an error occurred.</p>
                <p><a href="<?php echo $is_admin ? 'admin_manage_listings.php' : 'my-listings.php'; ?>" class="btn btn-secondary">Back to Listings</a></p>
            <?php endif; ?>
        </div>
    </main>

    <?php require 'chat-widget.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- IMPORTANT: Replace YOUR_GOOGLE_API_KEY with your actual Google Maps JavaScript API Key -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_API_KEY_HERE&libraries=places&callback=initAutocomplete"></script>
    
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
    <script>
        let autocomplete;
        function initAutocomplete() {
            const addressInput = document.getElementById('address_street_autocomplete');
            if (!addressInput) { console.error("Address autocomplete input field not found."); return; }
            autocomplete = new google.maps.places.Autocomplete(addressInput, {
                fields: ['address_components', 'geometry', 'name', 'formatted_address']
            });
            autocomplete.addListener('place_changed', fillInAddress);
        }
        function fillInAddress() {
            const place = autocomplete.getPlace();
            if (!place || !place.geometry) { console.warn("No details available for input."); return; }
            document.getElementById('address_street').value = '';
            document.getElementById('address_city').value = '';
            document.getElementById('address_state').value = '';
            document.getElementById('address_zipcode').value = '';
            document.getElementById('address_country').value = '';
            let streetNumber = '', route = '';
            for (const component of place.address_components) {
                const componentType = component.types[0];
                switch (componentType) {
                    case 'street_number': streetNumber = component.long_name; break;
                    case 'route': route = component.long_name; break;
                    case 'locality': case 'postal_town': document.getElementById('address_city').value = component.long_name; break;
                    case 'administrative_area_level_1': document.getElementById('address_state').value = component.short_name; break;
                    case 'postal_code': document.getElementById('address_zipcode').value = component.long_name; break;
                    case 'country': document.getElementById('address_country').value = component.long_name; break;
                }
            }
            document.getElementById('address_street').value = (streetNumber + ' ' + route).trim();
            if (place.geometry.location) {
                document.getElementById('latitude').value = place.geometry.location.lat();
                document.getElementById('longitude').value = place.geometry.location.lng();
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#availability_date", { dateFormat: "Y-m-d" });
        });
    </script>
</body>
</html>