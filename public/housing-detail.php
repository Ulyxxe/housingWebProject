<?php
// public/housing-detail.php
session_start();

// 1) Database connection
require_once __DIR__ . '/../config/config.php'; // Defines $pdo

// Determine if user is logged in for header links
$isLoggedIn = isset($_SESSION['user_id']);
$current_user_id = $_SESSION['user_id'] ?? null; // Get current user's ID if logged in

$housing = null;
$error_message = null;
$housing_id = null;
$reviews = [];
$gallery_images = []; // Initialize gallery images

// Helper function to render star ratings
function render_stars_for_detail_page(int $rating, int $max_stars = 5) {
    // ... (same function as before) ...
    $output = '<div class="review-card-stars">';
    for ($i = 1; $i <= $max_stars; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="fas fa-star"></i>'; // Full star
        } else {
            $output .= '<i class="far fa-star"></i>'; // Empty star
        }
    }
    $output .= '</div>';
    return $output;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $error_message = 'Invalid or missing housing ID.';
} else {
    $housing_id = (int)$_GET['id'];
    try {
        // Fetch main housing record + primary image
        $stmt_housing = $pdo->prepare(<<<SQL
SELECT
  h.*,
  hi.image_url AS primary_image
FROM housings AS h
LEFT JOIN housing_images AS hi
  ON hi.listing_id = h.listing_id
 AND hi.is_primary = 1
WHERE h.listing_id = :housing_id
SQL
        );
        $stmt_housing->bindParam(':housing_id', $housing_id, PDO::PARAM_INT);
        $stmt_housing->execute();
        $housing = $stmt_housing->fetch(PDO::FETCH_ASSOC);

        if (!$housing) {
            $error_message = 'Listing not found.';
        } else {
            // Fetch images for the gallery
            $stmt_images_gallery = $pdo->prepare("SELECT image_url, is_primary FROM housing_images WHERE listing_id = :listing_id ORDER BY is_primary DESC, image_id ASC");
            $stmt_images_gallery->bindParam(':listing_id', $housing_id, PDO::PARAM_INT);
            $stmt_images_gallery->execute();
            $gallery_images = $stmt_images_gallery->fetchAll(PDO::FETCH_ASSOC);

            // Fetch approved reviews for this listing
            $reviews_limit = 5;
            $sql_reviews = "SELECT r.*, u.username, u.first_name, u.last_name
                            FROM reviews r
                            JOIN users u ON r.user_id = u.user_id
                            WHERE r.listing_id = :listing_id AND r.is_approved = 1
                            ORDER BY r.review_date DESC
                            LIMIT :limit_val";
            $stmt_reviews = $pdo->prepare($sql_reviews);
            $stmt_reviews->bindParam(':listing_id', $housing_id, PDO::PARAM_INT);
            $stmt_reviews->bindParam(':limit_val', $reviews_limit, PDO::PARAM_INT);
            $stmt_reviews->execute();
            $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("housing-detail.php DB error: " . $e->getMessage());
        $error_message = 'A database error occurred while fetching details. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<!-- ... (head and existing HTML structure remains the same up to the reviews section) ... -->
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>
    <?php echo $housing ? htmlspecialchars($housing['title']) . ' - CROUS-X' : 'Housing Details - CROUS-X'; ?>
  </title>
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"/>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css"> <!-- Ensure forms.css is linked -->
  <link rel="stylesheet" href="css/housing-detail.css">
  <link rel="icon" type="image/png" href="assets/images/icon.png"> 

  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script> 
</head>
<body>

  <?php require 'header.php'; ?>

  <main class="app-container detail-page-wrapper">
    <div class="detail-content-area">
      <p class="back-to-listings-link"><a href="home.php"><i class="fas fa-arrow-left"></i> <span data-i18n-key="back_to_listings">Back to Listings</span></a></p>

      <?php if (isset($_SESSION['review_message'])): ?>
          <div class="form-message <?php echo ($_SESSION['review_message_type'] ?? 'success') === 'success' ? 'success-message' : 'error-message'; ?>">
              <i class="fas <?php echo ($_SESSION['review_message_type'] ?? 'success') === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
              <?php echo htmlspecialchars($_SESSION['review_message']); ?>
          </div>
          <?php unset($_SESSION['review_message'], $_SESSION['review_message_type']); ?>
      <?php endif; ?>

      <?php if ($housing): ?>
        
        <!-- ... (Housing Gallery and Info Column as before) ... -->
        <div class="housing-detail-grid">
            
            <div class="housing-gallery-column">
                <section class="detail-gallery-section">
                    <div class="main-image-wrapper">
                        <button class="action-icon-button favorite-toggle-button" aria-label="Add to favorites"><i class="far fa-heart"></i></button>
                        <?php if ($housing['primary_image']): ?>
                        <img id="housingMainImage" src="<?php echo htmlspecialchars($housing['primary_image']); ?>" alt="Primary photo of <?php echo htmlspecialchars($housing['title']); ?>" class="current-gallery-image">
                        <?php else: ?>
                        <div class="image-placeholder-large">
                            <i class="far fa-image"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    if (!empty($gallery_images) && count($gallery_images) > 1): 
                    ?>
                    <div class="gallery-thumbnails-container">
                        <?php foreach ($gallery_images as $img_item): ?>
                            <img src="<?php echo htmlspecialchars($img_item['image_url']); ?>" 
                                 alt="Thumbnail of <?php echo htmlspecialchars($housing['title']); ?>" 
                                 class="gallery-thumb-image <?php echo ($img_item['image_url'] == $housing['primary_image']) ? 'active' : ''; ?>" 
                                 data-fullsrc="<?php echo htmlspecialchars($img_item['image_url']); ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="housing-info-column">
                 <!-- ... (Title, Meta, Price, Address, Key Info, CTA Button, Description, Amenities, Availability Date as before) ... -->
                <h1 class="housing-main-title"><?php echo htmlspecialchars($housing['title']); ?></h1>
                <div class="housing-top-meta">
                    <span class="status-indicator-tag mockup-style" data-i18n-key="status_for_rent">Premium</span> 
                    <div class="price-display-area">
                        <span class="amount">$<?php echo number_format((float)$housing['rent_amount'], 0); ?></span>
                        <span class="frequency">/ <?php echo htmlspecialchars($housing['rent_frequency']); ?></span>
                    </div>
                </div>
                <?php if (!empty($housing['address_street'])): ?>
                <p class="location-address-text detail-page-address-mockup"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(implode(', ', array_filter([$housing['address_street'], $housing['address_city']] ) ) ); ?></p>
                <?php endif; ?>
                <div class="key-info-block-mockup-layout">
                     <?php
                    $key_details_for_mockup = [
                        ['label_key' => 'info_label_type', 'label_text' => 'Type', 'value' => htmlspecialchars($housing['property_type'])],
                        ['label_key' => 'info_label_bedrooms', 'label_text' => 'Bedrooms', 'value' => intval($housing['num_bedrooms'])],
                        ['label_key' => 'info_label_bathrooms', 'label_text' => 'Bathrooms', 'value' => htmlspecialchars($housing['num_bathrooms'])],
                        ['label_key' => 'info_label_size', 'label_text' => 'Size', 'value' => intval($housing['square_footage']) . ' m²'],
                        ['label_key' => 'info_label_furnished', 'label_text' => 'Furnished', 'value' => $housing['is_furnished'] ? 'Yes' : 'No'],
                        ['label_key' => 'info_label_pets', 'label_text' => 'Pets Allowed', 'value' => $housing['allows_pets'] ? 'Yes' : 'No'],
                    ];
                    foreach ($key_details_for_mockup as $detail) { /* ... (same loop as before) ... */ 
                        if (!empty($detail['value']) || $detail['value'] === 0 || $detail['value'] === '0' || (is_string($detail['value']) && strtolower($detail['value']) === 'no') ) {
                            echo '<div class="info-item-mockup">';
                            echo '  <span class="info-label-mockup" data-i18n-key="' . $detail['label_key'] . '">' . htmlspecialchars($detail['label_text']) . '</span>';
                            echo '  <div class="info-value-wrapper-mockup">';
                            echo '    <span class="info-value">' . htmlspecialchars($detail['value']) . '</span>';
                            echo '    <i class="fas fa-chevron-down mockup-dropdown-arrow"></i>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                <a href="booking.php?id=<?php echo htmlspecialchars($housing['listing_id']); ?>" class="cta-button primary-cta-button mockup-style" data-i18n-key="apply_booking_button">Apply Now / Request Booking</a>
                <?php if (!empty($housing['description'])): ?>
                <div class="content-accordion-item mockup-style">
                    <button class="accordion-trigger-button" aria-expanded="false"><span data-i18n-key="description_heading_accordion">Description</span><i class="fas fa-chevron-down accordion-icon"></i></button>
                    <div class="accordion-panel-content"><p><?php echo nl2br(htmlspecialchars($housing['description'])); ?></p></div>
                </div>
                <?php endif; ?>
                <?php
                $amenities_list = []; 
                if ($housing_id) { /* ... (amenities fetching as before) ... */ 
                     try {
                        $stmt_amenities = $pdo->prepare("SELECT a.name FROM housing_amenities ha JOIN amenities a USING(amenity_id) WHERE ha.listing_id=?");
                        $stmt_amenities->execute([$housing_id]);
                        $amenities_list = $stmt_amenities->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {
                        error_log("Error fetching amenities for listing {$housing_id}: ".$e->getMessage());
                    }
                }
                if ($amenities_list): ?>
                <div class="content-accordion-item mockup-style">
                     <button class="accordion-trigger-button" aria-expanded="false"><span data-i18n-key="amenities_heading_accordion">Amenities</span><i class="fas fa-chevron-down accordion-icon"></i></button>
                    <div class="accordion-panel-content"><ul class="amenities-styled-list"><?php foreach ($amenities_list as $amenity): ?><li><i class="fas fa-check"></i> <?php echo htmlspecialchars($amenity); ?></li><?php endforeach; ?></ul></div>
                </div>
                <?php endif; ?>
                 <div class="info-item-mockup full-width-info" style="grid-column: 1 / -1; margin-top: 0.5rem;"> 
                    <span class="info-label-mockup" data-i18n-key="info_label_available_date">Available from</span>
                     <div class="info-value-wrapper-mockup"><span class="info-value"><?php echo htmlspecialchars(date("F j, Y", strtotime($housing['availability_date']))); ?></span></div>
                </div>
            </div>
        </div>

        <?php if ($housing['latitude'] && $housing['longitude']): ?>
          <section class="page-section-layout map-container-section">
            <h2 class="section-title-styled" data-i18n-key="location_map_title">Location</h2>
            <div id="detailPageMap" class="map-render-area"></div>
          </section>
        <?php endif; ?>
        
        <section class="page-section-layout reviews-container-section mockup-style">
            <h2 class="section-title-styled" data-i18n-key="latest_reviews_title">Latest Reviews</h2>
            <!-- Existing Reviews Display (as before) -->
            <?php if (empty($reviews)): ?>
                <div class="info-message-display no-reviews">
                    <i class="fas fa-comment-slash"></i>
                    <p data-i18n-key="no_reviews_yet">No reviews yet for this listing.</p>
                </div>
            <?php else: ?>
                <div class="reviews-grid-layout mockup-style">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card-item">
                            <?php echo render_stars_for_detail_page((int)$review['rating']); ?>
                            <h3 class="review-card-heading"><?php echo htmlspecialchars($review['title'] ?? 'Review'); ?></h3> 
                            <p class="review-card-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p> 
                            <div class="review-card-author-area">
                                <img src="assets/images/placeholder-avatar.png" alt="Reviewer" class="author-avatar-image">
                                <div>
                                    <span class="author-name-text">
                                        <?php 
                                        $reviewerName = trim(htmlspecialchars($review['first_name'] ?? '') . ' ' . htmlspecialchars($review['last_name'] ?? ''));
                                        echo $reviewerName ?: htmlspecialchars($review['username']); 
                                        ?>
                                    </span>
                                    <span class="review-date-text"><?php echo htmlspecialchars(date("F j, Y", strtotime($review['review_date']))); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ADD REVIEW FORM SECTION -->
        <?php if ($isLoggedIn && $housing): // Show form only if user is logged in and housing details are loaded ?>
        <section class="page-section-layout add-review-section">
            <h2 class="section-title-styled" data-i18n-key="write_review_title">Write a Review</h2>
            <form action="submit_review.php" method="POST" class="add-review-form styled-form">
                <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($housing_id); ?>">
                
                <div class="form-group">
                    <label for="rating" data-i18n-key="review_rating_label">Your Rating *</label>
                    <div class="star-rating-input">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required />
                            <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="review_title" data-i18n-key="review_title_label">Review Title (Optional)</label>
                    <input type="text" id="review_title" name="review_title" class="form-control" 
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['review_title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="comment" data-i18n-key="review_comment_label">Your Comment *</label>
                    <textarea id="comment" name="comment" rows="5" class="form-control" required><?php echo htmlspecialchars($_SESSION['form_data']['comment'] ?? ''); ?></textarea>
                </div>
                <?php unset($_SESSION['form_data']); // Clear form data after displaying ?>

                <button type="submit" class="btn btn-primary btn-submit-review" data-i18n-key="review_submit_button">Submit Review</button>
            </form>
        </section>
        <?php elseif (!$isLoggedIn && $housing): // Prompt to login if not logged in ?>
        <section class="page-section-layout add-review-section">
             <div class="info-message-display">
                <p data-i18n-key="login_to_review_prompt">Please <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">log in</a> or <a href="register.php">register</a> to write a review.</p>
            </div>
        </section>
        <?php endif; ?>
        <!-- END ADD REVIEW FORM SECTION -->


      <?php elseif ($error_message): ?>
        <!-- ... (Error message display as before) ... -->
         <section class="error-message-display" style="padding: 2rem; text-align: center; background-color: var(--input-bg); border-radius: 8px;">
          <h2 style="color: var(--accent-secondary); margin-bottom: 1rem;">Error</h2>
          <p><?php echo htmlspecialchars($error_message); ?></p>
          <p style="margin-top: 1.5rem;"><a href="home.php" class="btn btn-secondary">Return to Listings</a></p>
        </section>
      <?php else: ?>
        <!-- ... (No housing details message as before) ... -->
        <section class="info-message-display" style="padding: 2rem; text-align: center;">
          <p data-i18n-key="no_housing_details_found">No housing details to display or listing not found.</p>
          <p style="margin-top: 1.5rem;"><a href="home.php" class="btn btn-secondary">Return to Listings</a></p>
        </section>
      <?php endif; ?>
    </div>
  </main>

  <?php require 'chat-widget.php'; ?>
  
  <!-- ... (JavaScript includes and scripts as before) ... -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
  
  <?php if ($housing && $housing['latitude'] && $housing['longitude']): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() { /* ... (map script as before) ... */
      const mapElement = document.getElementById('detailPageMap');
      if (mapElement) {
        const lat = <?php echo $housing['latitude']; ?>;
        const lon = <?php echo $housing['longitude']; ?>;
        const title = <?php echo json_encode($housing['title']); ?>;

        const detailMap = L.map('detailPageMap').setView([lat, lon], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© OpenStreetMap contributors'
        }).addTo(detailMap);
        L.marker([lat, lon]).addTo(detailMap).bindPopup(title);
      }
    });
  </script>
  <?php endif; ?>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() { /* ... (gallery and accordion script as before) ... */
        const mainImageDisplay = document.getElementById('housingMainImage');
        const thumbImages = document.querySelectorAll('.gallery-thumb-image');
        thumbImages.forEach(thumb => {
            thumb.addEventListener('click', function() {
                if (mainImageDisplay) mainImageDisplay.src = this.dataset.fullsrc;
                thumbImages.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        const accordionTriggers = document.querySelectorAll('.accordion-trigger-button');
        accordionTriggers.forEach(trigger => {
            trigger.addEventListener('click', function() {
                const panel = this.nextElementSibling;
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                this.setAttribute('aria-expanded', String(!isExpanded)); 
                if (!isExpanded) {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                    panel.style.opacity = 1;
                    panel.style.paddingTop = '0.8rem'; 
                    panel.style.paddingBottom = '0.8rem';
                } else {
                    panel.style.maxHeight = null;
                    panel.style.opacity = 0;
                    panel.style.paddingTop = '0'; 
                    panel.style.paddingBottom = '0';
                }
                
                const icon = this.querySelector('.accordion-icon');
                if (icon) { 
                    icon.classList.toggle('fa-chevron-up', !isExpanded);
                    icon.classList.toggle('fa-chevron-down', isExpanded);
                }
            });
        });

        const favoriteButton = document.querySelector('.favorite-toggle-button');
        if (favoriteButton) {
            favoriteButton.addEventListener('click', function() {
                this.classList.toggle('active');
                const iconEl = this.querySelector('i');
                iconEl.classList.toggle('far'); 
                iconEl.classList.toggle('fas'); 
            });
        }
    });
  </script>

  <script src="script.js" defer></script>
  <script src="chatbot.js" defer></script>
</body>
</html>