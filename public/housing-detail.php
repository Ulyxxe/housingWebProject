<?php
// public/housing-detail.php
session_start();

// 1) Database connection
require_once __DIR__ . '/../config/config.php'; // Defines $pdo

// Determine if user is logged in for header links
$isLoggedIn = isset($_SESSION['user_id']);

$housing = null;
$error_message = null;
$housing_id = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $error_message = 'Invalid or missing housing ID.';
} else {
    $housing_id = (int)$_GET['id'];
    try {
        // fetch main record + primary image
        $stmt = $pdo->prepare(<<<SQL
SELECT
  h.*,
  hi.image_url AS primary_image
FROM housings AS h
LEFT JOIN housing_images AS hi
  ON hi.listing_id = h.listing_id
 AND hi.is_primary = 1
WHERE h.listing_id = ?
SQL
        );
        $stmt->execute([$housing_id]);
        $housing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$housing) {
            $error_message = 'Listing not found.';
        }
    } catch (PDOException $e) {
        error_log("housing-detail error: ".$e->getMessage());
        $error_message = 'A database error occurred.';
    }
}
?>
<!DOCTYPE html>
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
  <link rel="stylesheet" href="style.css">
   <link rel="stylesheet" href="css/housing-detail.css">
  <link rel="icon" type="image/png" href="assets/images/icon.png"> 

  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script> 
</head>
<body>

  <?php require 'header.php'; ?>

  <main class="app-container detail-page-wrapper">
    <div class="detail-content-area">
      <p class="back-to-listings-link"><a href="home.php"><i class="fas fa-arrow-left"></i> <span data-i18n-key="back_to_listings">Back to Listings</span></a></p>

      <?php if ($error_message): ?>
        <section class="error-message-display">
          <h2>Error</h2>
          <p><?php echo htmlspecialchars($error_message); ?></p>
        </section>
      <?php elseif ($housing): ?>
        
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
                    $other_images_html = '';
                    if ($housing_id) { 
                        $stmt_images = $pdo->prepare("SELECT image_url FROM housing_images WHERE listing_id=? AND is_primary=0 LIMIT 4");
                        $stmt_images->execute([$housing_id]);
                        while ($row = $stmt_images->fetch(PDO::FETCH_ASSOC)){
                            $other_images_html .= '<img src="'.htmlspecialchars($row['image_url']).'" alt="Additional photo" class="gallery-thumb-image" data-fullsrc="'.htmlspecialchars($row['image_url']).'">';
                        }
                    }
                    if ($housing['primary_image'] || $other_images_html):
                    ?>
                    <div class="gallery-thumbnails-container">
                        <?php if ($housing['primary_image']): ?>
                            <img src="<?php echo htmlspecialchars($housing['primary_image']); ?>" alt="Primary photo thumbnail" class="gallery-thumb-image active" data-fullsrc="<?php echo htmlspecialchars($housing['primary_image']); ?>">
                        <?php endif; ?>
                        <?php echo $other_images_html; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="housing-info-column">
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

                    foreach ($key_details_for_mockup as $detail) {
                        // Only render if there's a value. For boolean 'No', it's fine.
                        if (!empty($detail['value']) || $detail['value'] === 0 || $detail['value'] === '0' || (is_string($detail['value']) && strtolower($detail['value']) === 'no') ) {
                            echo '<div class="info-item-mockup">';
                            echo '  <span class="info-label-mockup" data-i18n-key="' . $detail['label_key'] . '">' . htmlspecialchars($detail['label_text']) . '</span>'; // JS will fill with i18n
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
                    <button class="accordion-trigger-button" aria-expanded="false">
                        <span data-i18n-key="description_heading_accordion">Description</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-panel-content">
                        <p><?php echo nl2br(htmlspecialchars($housing['description'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                if ($housing_id) {
                    $stmt_amenities = $pdo->prepare("SELECT a.name FROM housing_amenities ha JOIN amenities a USING(amenity_id) WHERE ha.listing_id=?");
                    $stmt_amenities->execute([$housing_id]);
                    $amenities_list = $stmt_amenities->fetchAll(PDO::FETCH_COLUMN);
                    if ($amenities_list): ?>
                    <div class="content-accordion-item mockup-style">
                        <button class="accordion-trigger-button" aria-expanded="false">
                            <span data-i18n-key="amenities_heading_accordion">Amenities</span>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </button>
                        <div class="accordion-panel-content">
                            <ul class="amenities-styled-list">
                            <?php foreach ($amenities_list as $amenity): ?>
                                <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($amenity); ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif;
                } ?>
                 <div class="info-item-mockup full-width-info" style="grid-column: 1 / -1; margin-top: 0.5rem;"> 
                    <span class="info-label-mockup" data-i18n-key="info_label_available_date">Available from</span>
                     <div class="info-value-wrapper-mockup">
                        <span class="info-value"><?php echo htmlspecialchars(date("F j, Y", strtotime($housing['availability_date']))); ?></span>
                        
                    </div>
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
            <h2 class="section-title-styled" data-i18n-key="latest_reviews_title">Latest reviews</h2>
            <div class="reviews-grid-layout mockup-style">
                <div class="review-card-item">
                    <div class="review-card-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                    <h3 class="review-card-heading">Review title</h3> 
                    <p class="review-card-text">Review body. This is an example of a review for the housing unit. It could be a bit longer.</p> {/* Mockup text */}
                    <div class="review-card-author-area">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer" class="author-avatar-image">
                        <div>
                            <span class="author-name-text">Reviewer name</span>
                            <span class="review-date-text">Date</span>
                        </div>
                    </div>
                </div>
                <div class="review-card-item">
                     <div class="review-card-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <h3 class="review-card-heading">Another Review Title</h3>
                    <p class="review-card-text">This is another example. The user found this place to be excellent and well-maintained.</p>
                    <div class="review-card-author-area">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer" class="author-avatar-image">
                        <div>
                            <span class="author-name-text">Jane Doe</span>
                            <span class="review-date-text">A month ago</span>
                        </div>
                    </div>
                </div>
                 <div class="review-card-item">
                    <div class="review-card-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i></div>
                    <h3 class="review-card-heading">Decent Place</h3>
                    <p class="review-card-text">It was okay for the price. Some minor issues but overall a satisfactory stay for a student.</p>
                    <div class="review-card-author-area">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer" class="author-avatar-image">
                        <div>
                            <span class="author-name-text">John Smith</span>
                            <span class="review-date-text">Two weeks ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

      <?php else: ?>
        <section class="info-message-display">
          <p data-i18n-key="no_housing_details_found">No housing details to display.</p>
        </section>
      <?php endif; ?>
    </div>
  </main>

  <?php require 'chat-widget.php'; ?>
  
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
  
  <?php if ($housing && $housing['latitude'] && $housing['longitude']): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
    document.addEventListener('DOMContentLoaded', function() {
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
                
                this.setAttribute('aria-expanded', !isExpanded);
                panel.style.maxHeight = !isExpanded ? panel.scrollHeight + "px" : null;
                panel.style.opacity = !isExpanded ? 1 : 0;
                // Added padding transition for smoother look with mockup style
                panel.style.paddingTop = !isExpanded ? '0.8rem' : '0'; 
                panel.style.paddingBottom = !isExpanded ? '0.8rem' : '0';
                
                const icon = this.querySelector('.accordion-icon');
                if (icon) { // Ensure icon exists
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