<?php
// src/Views/housing/edit_listing.php
// Expected variables: $pageTitle, $listing (contains current data), $form_data (for re-population), $errors,
// $property_types, $rent_frequencies, $listing_statuses, $isLoggedIn, $userFullName
$current_listing_data = $form_data ?? $listing; // Use re-population data if available, else original
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Edit Listing'); ?> - CROUS-X</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  
    <link rel="icon" type="image/png" href="../../assets/images/icon.png"> <!-- Adjusted path -->
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/components.css">
    <link rel="stylesheet" href="../../css/forms.css"> 
    <link rel="stylesheet" href="../../css/add-housing.css"> <!-- Reusing add-housing for form styles -->
    <style>
        .current-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .current-image-item {
            position: relative;
            border: 1px solid var(--grey-border);
            padding: 0.5rem;
            border-radius: 6px;
            text-align: center;
        }
        .current-image-item img {
            max-width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        .current-image-item label {
            font-size: 0.8rem;
            display: block;
            margin-top: 0.3rem;
        }
        .primary-star {
            color: gold;
            font-size: 1.2em;
        }
    </style>
</head>
<body>

    <?php require __DIR__ . '/../../../public/header.php'; // Adjusted path ?>

    <main class="app-container">
        <div class="add-housing-form-container">
            <h2 class="add-housing-form-title"><?php echo htmlspecialchars($pageTitle); ?></h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="form-message success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    <p style="margin-top:1rem;"><a href="my-listings.php" class="btn btn-signin">Back to My Listings</a></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message']) && !isset($errors['database'])): // General error from controller redirect ?>
                 <div class="form-message error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="form-message error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Please correct the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $field => $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="edit-housing.php?id=<?php echo htmlspecialchars($listing['listing_id']); ?>" method="post" id="editHousingForm" class="add-housing-form" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($current_listing_data['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($current_listing_data['description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Address Fields (pre-filled, consider if Google Places should re-init or just display) -->
                <h3 style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Address Details</h3>
                <div class="form-group">
                    <label for="address_street_autocomplete">Search Address (Street, City) *</label>
                    <input type="text" id="address_street_autocomplete" placeholder="Start typing address to update...">
                    <small>Select an address from suggestions to auto-fill fields below. Current: <?php echo htmlspecialchars($listing['address_street'].', '.$listing['address_city']); ?></small>
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
                <!-- ... other address fields: state, zipcode, country, lat, lon, pre-filled similarly ... -->
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
                                <img src="../../<?php echo htmlspecialchars($img['image_url']); ?>" alt="Current image">
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
                    <label for="other_images_new">Upload Additional Images (optional)</label>
                    <input type="file" id="other_images_new" name="other_images_new[]" multiple accept="image/*">
                </div>


                <h3 style="font-size:1.2em; margin:1.5rem 0 0.8rem; color: var(--text-headings);">Property Details</h3>
                <!-- ... (Property Type, Sqft, Beds, Baths - pre-filled) ... -->
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
                <!-- ... (Rent, Frequency, Availability, Lease - pre-filled) ... -->
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
                <!-- ... (Furnished, Pets, Contact Email/Phone - pre-filled) ... -->
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
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($current_listing_data['contact_email'] ?? $_SESSION['email'] ?? ''); ?>" required>
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
        </div>
    </main>

    <?php require __DIR__ . '/../../../public/chat-widget.php'; // Adjusted path ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_API_KEY&libraries=places&callback=initAutocomplete"></script> <!-- Replace YOUR_GOOGLE_API_KEY -->
    
    <script src="../../script.js" defer></script> <!-- Adjusted path -->
    <script src="../../chatbot.js" defer></script> <!-- Adjusted path -->
    <script>
        // Reusing Google Places Autocomplete from add-housing.php
        let autocomplete;
        function initAutocomplete() {
            const addressInput = document.getElementById('address_street_autocomplete');
            if (!addressInput) { return; }
            autocomplete = new google.maps.places.Autocomplete(addressInput, {
                fields: ['address_components', 'geometry', 'name', 'formatted_address']
            });
            autocomplete.addListener('place_changed', fillInAddress);
        }
        function fillInAddress() {
            const place = autocomplete.getPlace();
            if (!place || !place.geometry) { return; }
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
            flatpickr("#availability_date", {
                dateFormat: "Y-m-d",
                // minDate: "today" // Might not be applicable for editing past listings
            });
        });
    </script>
</body>
</html>