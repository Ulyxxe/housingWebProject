<?php
// public/add-housing.php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// 2. Include Database Configuration
require_once __DIR__ . '/../config/config.php'; // Defines $pdo

// --- Configuration for Image Uploads ---
define('UPLOAD_DIR_BASE', __DIR__ . '/assets/uploads/housing_images/'); // Base directory for uploads
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];


// Initialize variables
$errors = [];
$success_message = '';

$property_types = ['Studio', 'Apartment', 'Shared Room', 'House', 'Other'];
$rent_frequencies = ['monthly', 'weekly', 'annually'];
$listing_statuses = ['available', 'pending_approval', 'unavailable'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Form Data Retrieval ---
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    // ... (all other $_POST retrievals from your previous add-housing.php version)
    $description = trim($_POST['description'] ?? '');
    $address_street = trim($_POST['address_street'] ?? '');
    $address_city = trim($_POST['address_city'] ?? '');
    $address_state = trim($_POST['address_state'] ?? '');
    $address_zipcode = trim($_POST['address_zipcode'] ?? '');
    $address_country = trim($_POST['address_country'] ?? '');
    
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
    
    $property_type = in_array($_POST['property_type'] ?? '', $property_types) ? $_POST['property_type'] : null;
    $rent_amount = filter_input(INPUT_POST, 'rent_amount', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
    $rent_frequency = in_array($_POST['rent_frequency'] ?? '', $rent_frequencies) ? $_POST['rent_frequency'] : null;
    
    $num_bedrooms = filter_input(INPUT_POST, 'num_bedrooms', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 0]]);
    $num_bathrooms = filter_input(INPUT_POST, 'num_bathrooms', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 0]]);
    $square_footage = filter_input(INPUT_POST, 'square_footage', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]);
    
    $availability_date_str = trim($_POST['availability_date'] ?? '');
    $lease_term_months = filter_input(INPUT_POST, 'lease_term_months', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]);
    
    $is_furnished = isset($_POST['is_furnished']) ? 1 : 0;
    $allows_pets = isset($_POST['allows_pets']) ? 1 : 0;
    
    $contact_email = filter_input(INPUT_POST, 'contact_email', FILTER_VALIDATE_EMAIL);
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $status = in_array($_POST['status'] ?? '', $listing_statuses) ? $_POST['status'] : 'pending_approval';

    // --- Basic Validations (Keep your existing ones) ---
    if (empty($title)) $errors['title'] = "Title is required.";
    // ... (all other validations from your previous add-housing.php version)
    if (empty($description)) $errors['description'] = "Description is required.";
    if (empty($address_street)) $errors['address_street'] = "Street address is required.";
    if (empty($address_city)) $errors['address_city'] = "City is required.";
    if (empty($address_country)) $errors['address_country'] = "Country is required.";

    if ($latitude === null || $latitude < -90 || $latitude > 90) $errors['latitude'] = "Valid latitude (-90 to 90) is required.";
    if ($longitude === null || $longitude < -180 || $longitude > 180) $errors['longitude'] = "Valid longitude (-180 to 180) is required.";
    
    if (empty($property_type)) $errors['property_type'] = "Property type is required.";
    if ($rent_amount === null || $rent_amount <= 0) $errors['rent_amount'] = "Valid rent amount is required.";
    if (empty($rent_frequency)) $errors['rent_frequency'] = "Rent frequency is required.";

    if ($num_bedrooms === null) $errors['num_bedrooms'] = "Number of bedrooms must be a valid number (0 or more).";
    if ($num_bathrooms === null) $errors['num_bathrooms'] = "Number of bathrooms must be a valid number (0 or more).";
    if ($square_footage === null) $errors['square_footage'] = "Square footage must be a valid number (1 or more).";

    $availability_date = null;
    if (empty($availability_date_str)) {
        $errors['availability_date'] = "Availability date is required.";
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $availability_date_str);
        if ($d && $d->format('Y-m-d') === $availability_date_str) {
            $availability_date = $availability_date_str;
        } else {
            $errors['availability_date'] = "Invalid availability date format. Use YYYY-MM-DD.";
        }
    }
    
    if (!empty($_POST['lease_term_months']) && $lease_term_months === null) $errors['lease_term_months'] = "Lease term must be a valid number if provided.";
    
    if ($contact_email === false) $errors['contact_email'] = "A valid contact email is required.";
    if (!empty($contact_phone) && !preg_match('/^[0-9\s\+\-\(\)]+$/', $contact_phone)) $errors['contact_phone'] = "Invalid contact phone format.";


    // --- Image Validation ---
    $uploaded_primary_image_path = null;
    $uploaded_other_image_paths = [];

    // Validate Primary Image
    if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['primary_image']['tmp_name'];
        $file_name = $_FILES['primary_image']['name'];
        $file_size = $_FILES['primary_image']['size'];
        $file_type = mime_content_type($file_tmp_path); // More reliable than $_FILES['primary_image']['type']
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_mime_types) || !in_array($file_extension, $allowed_extensions)) {
            $errors['primary_image'] = "Invalid file type for primary image. Allowed: " . implode(', ', $allowed_extensions);
        } elseif ($file_size > MAX_FILE_SIZE) {
            $errors['primary_image'] = "Primary image exceeds maximum size of " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.";
        }
    } elseif (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors['primary_image'] = "Error uploading primary image. Code: " . $_FILES['primary_image']['error'];
    }


    // Validate Other Images (if any)
    if (isset($_FILES['other_images'])) {
        foreach ($_FILES['other_images']['name'] as $key => $name) {
            if ($_FILES['other_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['other_images']['tmp_name'][$key];
                $file_name = $name;
                $file_size = $_FILES['other_images']['size'][$key];
                $file_type = mime_content_type($file_tmp_path);
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_type, $allowed_mime_types) || !in_array($file_extension, $allowed_extensions)) {
                    $errors['other_images_' . $key] = "Invalid file type for image '{$file_name}'. Allowed: " . implode(', ', $allowed_extensions);
                } elseif ($file_size > MAX_FILE_SIZE) {
                    $errors['other_images_' . $key] = "Image '{$file_name}' exceeds maximum size of " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.";
                }
            } elseif ($_FILES['other_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                 $errors['other_images_' . $key] = "Error uploading image '{$name}'. Code: " . $_FILES['other_images']['error'][$key];
            }
        }
    }


    // --- If no errors, insert into database ---
    if (empty($errors)) {
        $pdo->beginTransaction(); // Start transaction
        try {
            $sql_housing = "INSERT INTO housings (user_id, title, description, address_street, address_city, address_state, address_zipcode, address_country, latitude, longitude, property_type, rent_amount, rent_frequency, num_bedrooms, num_bathrooms, square_footage, availability_date, lease_term_months, is_furnished, allows_pets, contact_email, contact_phone, status, created_at, updated_at) 
                            VALUES (:user_id, :title, :description, :address_street, :address_city, :address_state, :address_zipcode, :address_country, :latitude, :longitude, :property_type, :rent_amount, :rent_frequency, :num_bedrooms, :num_bathrooms, :square_footage, :availability_date, :lease_term_months, :is_furnished, :allows_pets, :contact_email, :contact_phone, :status, NOW(), NOW())";
            
            $stmt_housing = $pdo->prepare($sql_housing);
            // Bind all parameters for housings table... (same as your previous version)
            $stmt_housing->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_housing->bindParam(':title', $title);
            $stmt_housing->bindParam(':description', $description);
            $stmt_housing->bindParam(':address_street', $address_street);
            $stmt_housing->bindParam(':address_city', $address_city);
            $stmt_housing->bindParam(':address_state', $address_state);
            $stmt_housing->bindParam(':address_zipcode', $address_zipcode);
            $stmt_housing->bindParam(':address_country', $address_country);
            $stmt_housing->bindParam(':latitude', $latitude);
            $stmt_housing->bindParam(':longitude', $longitude);
            $stmt_housing->bindParam(':property_type', $property_type);
            $stmt_housing->bindParam(':rent_amount', $rent_amount);
            $stmt_housing->bindParam(':rent_frequency', $rent_frequency);
            $stmt_housing->bindParam(':num_bedrooms', $num_bedrooms, PDO::PARAM_INT);
            $stmt_housing->bindParam(':num_bathrooms', $num_bathrooms);
            $stmt_housing->bindParam(':square_footage', $square_footage, PDO::PARAM_INT);
            $stmt_housing->bindParam(':availability_date', $availability_date);
            $stmt_housing->bindParam(':lease_term_months', $lease_term_months, PDO::PARAM_INT);
            $stmt_housing->bindParam(':is_furnished', $is_furnished, PDO::PARAM_INT);
            $stmt_housing->bindParam(':allows_pets', $allows_pets, PDO::PARAM_INT);
            $stmt_housing->bindParam(':contact_email', $contact_email);
            $stmt_housing->bindParam(':contact_phone', $contact_phone);
            $stmt_housing->bindParam(':status', $status);

            if ($stmt_housing->execute()) {
                $listing_id = $pdo->lastInsertId();

                // --- Handle Primary Image Upload ---
                if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                    $file_extension = strtolower(pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION));
                    $new_filename = uniqid('primary_', true) . '.' . $file_extension;
                    $destination_path = UPLOAD_DIR_BASE . $new_filename;
                    $relative_path = 'assets/uploads/housing_images/' . $new_filename; // Path to store in DB

                    if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $destination_path)) {
                        $sql_image = "INSERT INTO housing_images (listing_id, image_url, is_primary, uploaded_at) VALUES (:listing_id, :image_url, 1, NOW())";
                        $stmt_image = $pdo->prepare($sql_image);
                        $stmt_image->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
                        $stmt_image->bindParam(':image_url', $relative_path);
                        $stmt_image->execute();
                    } else {
                        $errors['primary_image_move'] = "Failed to move primary uploaded image.";
                        $pdo->rollBack(); // Rollback if image move fails
                    }
                }
                
                // --- Handle Other Images Upload ---
                if (empty($errors) && isset($_FILES['other_images'])) { // Proceed only if no prior errors
                    foreach ($_FILES['other_images']['name'] as $key => $name) {
                        if ($_FILES['other_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            $new_filename = uniqid('other_', true) . '_' . $key . '.' . $file_extension;
                            $destination_path = UPLOAD_DIR_BASE . $new_filename;
                            $relative_path = 'assets/uploads/housing_images/' . $new_filename;

                            if (move_uploaded_file($_FILES['other_images']['tmp_name'][$key], $destination_path)) {
                                $sql_image = "INSERT INTO housing_images (listing_id, image_url, is_primary, uploaded_at) VALUES (:listing_id, :image_url, 0, NOW())";
                                $stmt_image = $pdo->prepare($sql_image);
                                $stmt_image->bindParam(':listing_id', $listing_id, PDO::PARAM_INT);
                                $stmt_image->bindParam(':image_url', $relative_path);
                                $stmt_image->execute();
                            } else {
                                $errors['other_images_move_' . $key] = "Failed to move uploaded image '{$name}'.";
                                $pdo->rollBack(); // Rollback if any image move fails
                                break; // Exit loop
                            }
                        }
                    }
                }

                if (empty($errors)) {
                    $pdo->commit(); // Commit transaction
                    $success_message = "Housing listing added successfully! It may be pending approval.";
                    $_POST = [];
                } else {
                    // Errors occurred during image processing after housing insert
                    // The rollback should have handled the housing insert, but good to be explicit
                    if (!$pdo->inTransaction()) $pdo->beginTransaction(); // Should not happen if rollback was called
                    $pdo->rollBack();
                    $errors['database'] = "Failed to add listing due to image processing errors. Please try again.";
                }

            } else { // Housing insert failed
                $pdo->rollBack();
                $errors['database'] = "Failed to add listing details. Please try again.";
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Add Housing DB Error: " . $e->getMessage());
            $errors['database'] = "A database error occurred: " . $e->getMessage();
        }
    }
}
$isLoggedIn = true;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="add_housing_page_title">Add New Housing - CROUS-X</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Assuming you've added the CSS from previous step here -->
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <!-- If you created a separate CSS file like add-housing-style.css, link it here: -->
    <!-- <link rel="stylesheet" href="add-housing-style.css"> -->
</head>
<body>

    <?php require 'header.php'; ?>

    <main class="app-container">
        <div class="add-housing-form-container">
            <h2 class="add-housing-form-title" data-i18n-key="add_housing_main_title">Add New Housing Listing</h2>

            <?php if (!empty($success_message)): ?>
                <div class="form-message success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    <p style="margin-top:1rem;"><a href="dashboard.php" class="btn btn-signin" data-i18n-key="add_housing_link_dashboard">Go to Dashboard</a></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors) && isset($errors['database'])): ?>
                 <div class="form-message error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errors['database']); unset($errors['database']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors) && count($errors) > 0 ): ?>
                <div class="form-message error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong data-i18n-key="add_housing_error_heading">Please correct the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $field => $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>


            <?php if (empty($success_message)): ?>
            <form action="add-housing.php" method="post" id="addHousingForm" class="add-housing-form" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title" data-i18n-key="add_housing_label_title">Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description" data-i18n-key="add_housing_label_description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <h3 data-i18n-key="add_housing_subtitle_address" style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Address Details</h3>
                <div class="form-group">
                    <label for="address_street_autocomplete" data-i18n-key="add_housing_label_street_search">Search Address (Street, City) *</label>
                    <input type="text" id="address_street_autocomplete" placeholder="Start typing your address...">
                    <small>Select an address from suggestions to auto-fill fields below.</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address_street" data-i18n-key="add_housing_label_street_confirm">Street Address (Confirmed) *</label>
                        <input type="text" id="address_street" name="address_street" value="<?php echo htmlspecialchars($_POST['address_street'] ?? ''); ?>" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="address_city" data-i18n-key="add_housing_label_city_confirm">City (Confirmed) *</label>
                        <input type="text" id="address_city" name="address_city" value="<?php echo htmlspecialchars($_POST['address_city'] ?? ''); ?>" required readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="address_state" data-i18n-key="add_housing_label_state_confirm">State/Province (Confirmed)</label>
                        <input type="text" id="address_state" name="address_state" value="<?php echo htmlspecialchars($_POST['address_state'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="address_zipcode" data-i18n-key="add_housing_label_zip_confirm">Zip/Postal Code (Confirmed)</label>
                        <input type="text" id="address_zipcode" name="address_zipcode" value="<?php echo htmlspecialchars($_POST['address_zipcode'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="address_country" data-i18n-key="add_housing_label_country_confirm">Country (Confirmed) *</label>
                        <input type="text" id="address_country" name="address_country" value="<?php echo htmlspecialchars($_POST['address_country'] ?? ''); ?>" required readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude" data-i18n-key="add_housing_label_lat">Latitude *</label>
                        <input type="number" id="latitude" name="latitude" step="any" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>" placeholder="e.g., 48.8566" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="longitude" data-i18n-key="add_housing_label_lon">Longitude *</label>
                        <input type="number" id="longitude" name="longitude" step="any" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>" placeholder="e.g., 2.3522" required readonly>
                    </div>
                </div>
                
                <h3 data-i18n-key="add_housing_subtitle_images" style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Images</h3>
                <div class="form-group">
                    <label for="primary_image" data-i18n-key="add_housing_label_primary_image">Primary Image (Displayed First)</label>
                    <input type="file" id="primary_image" name="primary_image" accept="image/*">
                    <?php if (isset($errors['primary_image'])): ?><div class="form-error"><?php echo $errors['primary_image']; ?></div><?php endif; ?>
                    <?php if (isset($errors['primary_image_move'])): ?><div class="form-error"><?php echo $errors['primary_image_move']; ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="other_images" data-i18n-key="add_housing_label_other_images">Other Images (Max 4, optional)</label>
                    <input type="file" id="other_images" name="other_images[]" multiple accept="image/*">
                    <?php 
                    // Display errors for other images
                    foreach ($errors as $key => $error_msg) {
                        if (strpos($key, 'other_images_') === 0) {
                            echo '<div class="form-error">' . htmlspecialchars($error_msg) . '</div>';
                        }
                    }
                    ?>
                </div>


                <h3 data-i18n-key="add_housing_subtitle_property" style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Property Details</h3>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="property_type" data-i18n-key="add_housing_label_proptype">Property Type *</label>
                        <select id="property_type" name="property_type" required>
                            <option value="" data-i18n-key="add_housing_select_proptype">-- Select Type --</option>
                            <?php foreach ($property_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] == $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="square_footage" data-i18n-key="add_housing_label_sqft">Square Footage (m²) *</label>
                        <input type="number" id="square_footage" name="square_footage" min="1" value="<?php echo htmlspecialchars($_POST['square_footage'] ?? ''); ?>" required>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="num_bedrooms" data-i18n-key="add_housing_label_beds">Number of Bedrooms *</label>
                        <input type="number" id="num_bedrooms" name="num_bedrooms" min="0" value="<?php echo htmlspecialchars($_POST['num_bedrooms'] ?? '1'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="num_bathrooms" data-i18n-key="add_housing_label_baths">Number of Bathrooms *</label>
                        <input type="number" id="num_bathrooms" name="num_bathrooms" min="0" step="0.5" value="<?php echo htmlspecialchars($_POST['num_bathrooms'] ?? '1'); ?>" required>
                    </div>
                </div>

                <h3 data-i18n-key="add_housing_subtitle_rent" style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Rental Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="rent_amount" data-i18n-key="add_housing_label_rent">Rent Amount *</label>
                        <input type="number" id="rent_amount" name="rent_amount" min="0.01" step="0.01" value="<?php echo htmlspecialchars($_POST['rent_amount'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="rent_frequency" data-i18n-key="add_housing_label_rentfreq">Rent Frequency *</label>
                        <select id="rent_frequency" name="rent_frequency" required>
                             <option value="" data-i18n-key="add_housing_select_rentfreq">-- Select Frequency --</option>
                            <?php foreach ($rent_frequencies as $freq): ?>
                                <option value="<?php echo htmlspecialchars($freq); ?>" <?php echo (isset($_POST['rent_frequency']) && $_POST['rent_frequency'] == $freq) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($freq)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="availability_date" data-i18n-key="add_housing_label_availdate">Availability Date *</label>
                        <input type="date" id="availability_date" name="availability_date" value="<?php echo htmlspecialchars($_POST['availability_date'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lease_term_months" data-i18n-key="add_housing_label_lease">Lease Term (Months, optional)</label>
                        <input type="number" id="lease_term_months" name="lease_term_months" min="1" value="<?php echo htmlspecialchars($_POST['lease_term_months'] ?? ''); ?>">
                    </div>
                </div>

                <h3 data-i18n-key="add_housing_subtitle_features" style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Features & Contact</h3>
                <div class="form-row">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_furnished" name="is_furnished" value="1" <?php echo (isset($_POST['is_furnished'])) ? 'checked' : ''; ?>>
                            <label for="is_furnished" data-i18n-key="add_housing_label_furnished">Is Furnished?</label>
                        </div>
                    </div>
                     <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="allows_pets" name="allows_pets" value="1" <?php echo (isset($_POST['allows_pets'])) ? 'checked' : ''; ?>>
                            <label for="allows_pets" data-i18n-key="add_housing_label_pets">Allows Pets?</label>
                        </div>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email" data-i18n-key="add_housing_label_contactemail">Contact Email *</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($_POST['contact_email'] ?? $_SESSION['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone" data-i18n-key="add_housing_label_contactphone">Contact Phone (optional)</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status" data-i18n-key="add_housing_label_status">Listing Status *</label>
                    <select id="status" name="status" required>
                        <?php foreach ($listing_statuses as $stat): ?>
                            <option value="<?php echo htmlspecialchars($stat); ?>" <?php echo ((isset($_POST['status']) && $_POST['status'] == $stat) || (!isset($_POST['status']) && $stat == 'pending_approval')) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $stat))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-submit-listing" data-i18n-key="add_housing_button_submit">Add Listing</button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <?php require 'chat-widget.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- IMPORTANT: Replace YOUR_GOOGLE_API_KEY with your actual Google Maps JavaScript API Key -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_API_KEY&libraries=places&callback=initAutocomplete"></script>
    
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
    <script>
        let autocomplete;

        function initAutocomplete() {
            const addressInput = document.getElementById('address_street_autocomplete');
            if (!addressInput) {
                console.error("Address autocomplete input field not found.");
                return;
            }

            autocomplete = new google.maps.places.Autocomplete(addressInput, {
                // types: ['address'], // You can restrict to addresses
                componentRestrictions: { country: [] }, // No country restriction by default, or set e.g. 'fr' for France
                fields: ['address_components', 'geometry', 'name', 'formatted_address']
            });

            autocomplete.addListener('place_changed', fillInAddress);
        }

        function fillInAddress() {
            const place = autocomplete.getPlace();
            if (!place || !place.geometry) {
                // User entered the name of a Place that was not suggested and
                // pressed the Enter key, or the Place Details request failed.
                console.warn("No details available for input: '" + place.name + "' or geocoding failed.");
                return;
            }

            // Clear previous values
            document.getElementById('address_street').value = '';
            document.getElementById('address_city').value = '';
            document.getElementById('address_state').value = '';
            document.getElementById('address_zipcode').value = '';
            document.getElementById('address_country').value = '';

            let streetNumber = '';
            let route = '';

            // Get each component of the address from the place details,
            // and then fill-in the corresponding field on the form.
            for (const component of place.address_components) {
                const componentType = component.types[0];
                switch (componentType) {
                    case 'street_number':
                        streetNumber = component.long_name;
                        break;
                    case 'route': // Street name
                        route = component.long_name;
                        break;
                    case 'locality': // City
                    case 'postal_town': // City for UK
                        document.getElementById('address_city').value = component.long_name;
                        break;
                    case 'administrative_area_level_1': // State or Province
                        document.getElementById('address_state').value = component.short_name;
                        break;
                    case 'postal_code':
                        document.getElementById('address_zipcode').value = component.long_name;
                        break;
                    case 'country':
                        document.getElementById('address_country').value = component.long_name;
                        break;
                }
            }
            
            document.getElementById('address_street').value = (streetNumber + ' ' + route).trim();


            // Fill latitude and longitude
            if (place.geometry.location) {
                document.getElementById('latitude').value = place.geometry.location.lat();
                document.getElementById('longitude').value = place.geometry.location.lng();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#availability_date", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
            // The initAutocomplete function will be called by the Google Maps API script load (callback=initAutocomplete)
        });
    </script>
</body>
</html>