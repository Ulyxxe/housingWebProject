<?php
// public/add-housing.php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Redirect back here after login
    header("Location: login.php");
    exit;
}

// 2. Include Database Configuration
require_once __DIR__ . '/../config/config.php'; // Defines $pdo

// Initialize variables
$errors = [];
$success_message = '';

// Define options for select fields
$property_types = ['Studio', 'Apartment', 'Shared Room', 'House', 'Other'];
$rent_frequencies = ['monthly', 'weekly', 'annually'];
$listing_statuses = ['available', 'pending_approval', 'unavailable']; // Example statuses

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Retrieve and Sanitize Form Data ---
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
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
    $num_bathrooms = filter_input(INPUT_POST, 'num_bathrooms', FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 0]]); // Allow 0.5
    $square_footage = filter_input(INPUT_POST, 'square_footage', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]);
    
    $availability_date_str = trim($_POST['availability_date'] ?? '');
    $lease_term_months = filter_input(INPUT_POST, 'lease_term_months', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['min_range' => 1]]);
    
    $is_furnished = isset($_POST['is_furnished']) ? 1 : 0;
    $allows_pets = isset($_POST['allows_pets']) ? 1 : 0;
    
    $contact_email = filter_input(INPUT_POST, 'contact_email', FILTER_VALIDATE_EMAIL);
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $status = in_array($_POST['status'] ?? '', $listing_statuses) ? $_POST['status'] : 'pending_approval';


    // --- Basic Validations ---
    if (empty($title)) $errors['title'] = "Title is required.";
    if (empty($description)) $errors['description'] = "Description is required.";
    if (empty($address_street)) $errors['address_street'] = "Street address is required.";
    if (empty($address_city)) $errors['address_city'] = "City is required.";
    if (empty($address_country)) $errors['address_country'] = "Country is required.";

    if ($latitude === null || $latitude < -90 || $latitude > 90) $errors['latitude'] = "Valid latitude is required.";
    if ($longitude === null || $longitude < -180 || $longitude > 180) $errors['longitude'] = "Valid longitude is required.";
    
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
    // Basic phone validation (can be improved)
    if (!empty($contact_phone) && !preg_match('/^[0-9\s\+\-\(\)]+$/', $contact_phone)) $errors['contact_phone'] = "Invalid contact phone format.";


    // --- If no errors, insert into database ---
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO housings (user_id, title, description, address_street, address_city, address_state, address_zipcode, address_country, latitude, longitude, property_type, rent_amount, rent_frequency, num_bedrooms, num_bathrooms, square_footage, availability_date, lease_term_months, is_furnished, allows_pets, contact_email, contact_phone, status, created_at, updated_at) 
                    VALUES (:user_id, :title, :description, :address_street, :address_city, :address_state, :address_zipcode, :address_country, :latitude, :longitude, :property_type, :rent_amount, :rent_frequency, :num_bedrooms, :num_bathrooms, :square_footage, :availability_date, :lease_term_months, :is_furnished, :allows_pets, :contact_email, :contact_phone, :status, NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':address_street', $address_street);
            $stmt->bindParam(':address_city', $address_city);
            $stmt->bindParam(':address_state', $address_state);
            $stmt->bindParam(':address_zipcode', $address_zipcode);
            $stmt->bindParam(':address_country', $address_country);
            $stmt->bindParam(':latitude', $latitude);
            $stmt->bindParam(':longitude', $longitude);
            $stmt->bindParam(':property_type', $property_type);
            $stmt->bindParam(':rent_amount', $rent_amount);
            $stmt->bindParam(':rent_frequency', $rent_frequency);
            $stmt->bindParam(':num_bedrooms', $num_bedrooms, PDO::PARAM_INT);
            $stmt->bindParam(':num_bathrooms', $num_bathrooms);
            $stmt->bindParam(':square_footage', $square_footage, PDO::PARAM_INT);
            $stmt->bindParam(':availability_date', $availability_date);
            $stmt->bindParam(':lease_term_months', $lease_term_months, PDO::PARAM_INT); // Bind as INT, allow NULL
            $stmt->bindParam(':is_furnished', $is_furnished, PDO::PARAM_INT);
            $stmt->bindParam(':allows_pets', $allows_pets, PDO::PARAM_INT);
            $stmt->bindParam(':contact_email', $contact_email);
            $stmt->bindParam(':contact_phone', $contact_phone);
            $stmt->bindParam(':status', $status);

            if ($stmt->execute()) {
                $success_message = "Housing listing added successfully! It may be pending approval.";
                // Clear POST data to prevent form resubmission issues if user refreshes
                $_POST = []; 
            } else {
                $errors['database'] = "Failed to add listing. Please try again.";
            }

        } catch (PDOException $e) {
            error_log("Add Housing DB Error: " . $e->getMessage());
            $errors['database'] = "A database error occurred: " . $e->getMessage(); // Show detailed error for dev
            // $errors['database'] = "A database error occurred. Please try again later."; // For production
        }
    }
}
$isLoggedIn = true; // For header.php
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
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <style>
        /* Minimal additional styles for add-housing form */
        .add-housing-form-container {
            background-color: var(--container-bg);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--grey-border);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 800px; /* Wider form for more fields */
            margin: 2rem auto;
        }
        [data-theme="dark"] .add-housing-form-container {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .add-housing-form-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-headings);
            margin-bottom: 1.8rem;
            text-align: center;
        }
        .add-housing-form .form-group { margin-bottom: 1.2rem; text-align: left; }
        .add-housing-form label { display: block; font-size: 0.9rem; font-weight: 500; color: var(--text-secondary); margin-bottom: 0.5rem; }
        .add-housing-form input[type="text"],
        .add-housing-form input[type="email"],
        .add-housing-form input[type="tel"],
        .add-housing-form input[type="number"],
        .add-housing-form input[type="date"],
        .add-housing-form textarea,
        .add-housing-form select {
            width: 100%;
            padding: 0.75rem 0.8rem;
            background-color: var(--input-bg);
            border: 1px solid var(--grey-border);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 0.95rem;
            transition: border-color var(--transition-smooth), box-shadow var(--transition-smooth);
        }
        .add-housing-form input:focus, 
        .add-housing-form textarea:focus, 
        .add-housing-form select:focus { outline: none; border-color: var(--accent-primary); box-shadow: 0 0 0 3px rgba(var(--accent-primary-rgb),0.2); }
        .add-housing-form textarea { min-height: 100px; resize: vertical; }
        .add-housing-form .checkbox-group { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.3rem; }
        .add-housing-form .checkbox-group input[type="checkbox"] { width: auto; margin-right: 0.3rem; accent-color: var(--accent-primary); }
        .add-housing-form .form-row { display: flex; gap: 1rem; }
        .add-housing-form .form-row .form-group { flex: 1; }
        .btn-submit-listing { width: 100%; padding: 0.8rem 1.5rem; font-size: 1rem; font-weight: 500; border-radius: 8px; border: none; cursor: pointer; transition: background-color var(--transition-smooth), transform var(--transition-smooth); background-color: var(--accent-primary); color: var(--bg-primary); }
        [data-theme="dark"] .btn-submit-listing { color: #000; }
        .btn-submit-listing:hover { filter: brightness(0.9); transform: translateY(-2px); }
        .form-error { color: #d9534f; font-size: 0.85em; margin-top: 0.2em; }
        .form-message.error-message ul { list-style-type: disc; padding-left: 20px; }
        @media (max-width: 600px) { .add-housing-form .form-row { flex-direction: column; gap: 0; } }
    </style>
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


            <?php if (empty($success_message)): // Only show form if not successful ?>
            <form action="add-housing.php" method="post" id="addHousingForm" class="add-housing-form">
                
                <div class="form-group">
                    <label for="title" data-i18n-key="add_housing_label_title">Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    <?php if (isset($errors['title'])): ?><div class="form-error"><?php echo $errors['title']; ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description" data-i18n-key="add_housing_label_description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <?php if (isset($errors['description'])): ?><div class="form-error"><?php echo $errors['description']; ?></div><?php endif; ?>
                </div>

                <h3 data-i18n-key="add_housing_subtitle_address" style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Address Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="address_street" data-i18n-key="add_housing_label_street">Street Address *</label>
                        <input type="text" id="address_street" name="address_street" value="<?php echo htmlspecialchars($_POST['address_street'] ?? ''); ?>" required>
                        <?php if (isset($errors['address_street'])): ?><div class="form-error"><?php echo $errors['address_street']; ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="address_city" data-i18n-key="add_housing_label_city">City *</label>
                        <input type="text" id="address_city" name="address_city" value="<?php echo htmlspecialchars($_POST['address_city'] ?? ''); ?>" required>
                        <?php if (isset($errors['address_city'])): ?><div class="form-error"><?php echo $errors['address_city']; ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="address_state" data-i18n-key="add_housing_label_state">State/Province</label>
                        <input type="text" id="address_state" name="address_state" value="<?php echo htmlspecialchars($_POST['address_state'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address_zipcode" data-i18n-key="add_housing_label_zip">Zip/Postal Code</label>
                        <input type="text" id="address_zipcode" name="address_zipcode" value="<?php echo htmlspecialchars($_POST['address_zipcode'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address_country" data-i18n-key="add_housing_label_country">Country *</label>
                        <input type="text" id="address_country" name="address_country" value="<?php echo htmlspecialchars($_POST['address_country'] ?? ''); ?>" required>
                        <?php if (isset($errors['address_country'])): ?><div class="form-error"><?php echo $errors['address_country']; ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude" data-i18n-key="add_housing_label_lat">Latitude *</label>
                        <input type="number" id="latitude" name="latitude" step="any" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>" placeholder="e.g., 48.8566" required>
                         <?php if (isset($errors['latitude'])): ?><div class="form-error"><?php echo $errors['latitude']; ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="longitude" data-i18n-key="add_housing_label_lon">Longitude *</label>
                        <input type="number" id="longitude" name="longitude" step="any" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>" placeholder="e.g., 2.3522" required>
                        <?php if (isset($errors['longitude'])): ?><div class="form-error"><?php echo $errors['longitude']; ?></div><?php endif; ?>
                    </div>
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
                        <?php if (isset($errors['property_type'])): ?><div class="form-error"><?php echo $errors['property_type']; ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="square_footage" data-i18n-key="add_housing_label_sqft">Square Footage (m²) *</label>
                        <input type="number" id="square_footage" name="square_footage" min="1" value="<?php echo htmlspecialchars($_POST['square_footage'] ?? ''); ?>" required>
                        <?php if (isset($errors['square_footage'])): ?><div class="form-error"><?php echo $errors['square_footage']; ?></div><?php endif; ?>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group">
                        <label for="num_bedrooms" data-i18n-key="add_housing_label_beds">Number of Bedrooms *</label>
                        <input type="number" id="num_bedrooms" name="num_bedrooms" min="0" value="<?php echo htmlspecialchars($_POST['num_bedrooms'] ?? '1'); ?>" required>
                        <?php if (isset($errors['num_bedrooms'])): ?><div class="form-error"><?php echo $errors['num_bedrooms']; ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="num_bathrooms" data-i18n-key="add_housing_label_baths">Number of Bathrooms *</label>
                        <input type="number" id="num_bathrooms" name="num_bathrooms" min="0" step="0.5" value="<?php echo htmlspecialchars($_POST['num_bathrooms'] ?? '1'); ?>" required>
                        <?php if (isset($errors['num_bathrooms'])): ?><div class="form-error"><?php echo $errors['num_bathrooms']; ?></div><?php endif; ?>
                    </div>
                </div>

                <h3 data-i18n-key="add_housing_subtitle_rent" style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Rental Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="rent_amount" data-i18n-key="add_housing_label_rent">Rent Amount *</label>
                        <input type="number" id="rent_amount" name="rent_amount" min="0.01" step="0.01" value="<?php echo htmlspecialchars($_POST['rent_amount'] ?? ''); ?>" required>
                        <?php if (isset($errors['rent_amount'])): ?><div class="form-error"><?php echo $errors['rent_amount']; ?></div><?php endif; ?>
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
                         <?php if (isset($errors['rent_frequency'])): ?><div class="form-error"><?php echo $errors['rent_frequency']; ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="availability_date" data-i18n-key="add_housing_label_availdate">Availability Date *</label>
                        <input type="date" id="availability_date" name="availability_date" value="<?php echo htmlspecialchars($_POST['availability_date'] ?? ''); ?>" required>
                        <?php if (isset($errors['availability_date'])): ?><div class="form-error"><?php echo $errors['availability_date']; ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="lease_term_months" data-i18n-key="add_housing_label_lease">Lease Term (Months, optional)</label>
                        <input type="number" id="lease_term_months" name="lease_term_months" min="1" value="<?php echo htmlspecialchars($_POST['lease_term_months'] ?? ''); ?>">
                         <?php if (isset($errors['lease_term_months'])): ?><div class="form-error"><?php echo $errors['lease_term_months']; ?></div><?php endif; ?>
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
                         <?php if (isset($errors['contact_email'])): ?><div class="form-error"><?php echo $errors['contact_email']; ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone" data-i18n-key="add_housing_label_contactphone">Contact Phone (optional)</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                        <?php if (isset($errors['contact_phone'])): ?><div class="form-error"><?php echo $errors['contact_phone']; ?></div><?php endif; ?>
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
                     <?php if (isset($errors['status'])): ?><div class="form-error"><?php echo $errors['status']; ?></div><?php endif; ?>
                </div>


                <button type="submit" class="btn-submit-listing" data-i18n-key="add_housing_button_submit">Add Listing</button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <?php require 'chat-widget.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="script.js" defer></script>
    <script src="chatbot.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#availability_date", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });

            // Add i18n attributes for dynamic text based on your existing script.js if needed
            // e.g., for select options placeholders if your script handles that.
        });
    </script>
</body>
</html>