
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="add_housing_page_title">Add New Housing - CROUS-X</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <link rel="stylesheet" href="css/global.css">   <!-- General styles, variables -->
    <link rel="stylesheet" href="css/header.css">   <!-- Header styling -->
    <link rel="stylesheet" href="css/components.css"> <!-- Chat widget, common buttons -->
    <link rel="stylesheet" href="css/forms.css"> 
       <!-- General form styling -->

    <!-- Page-Specific CSS -->

    <link rel="stylesheet" href="css/add-housing.css"> <!-- Styles ONLY for this page -->
    
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
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAGnyXt9y762yqiQmuSnUo5ffAp5GAWWL4&libraries=places&callback=initAutocomplete"></script>
    
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