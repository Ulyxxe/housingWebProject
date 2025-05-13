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
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary"> {/* Added default theme/accent */}
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
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="assets/images/icon.png"> 

  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>

  {/* YOUR EXISTING HEADER HTML FROM THE "project's CSS" section. 
      Ensure this header HTML structure is the one compatible with your main style.css.
      For brevity, I'm not repeating the full corrected header here, but it's crucial.
      It should look like:
      <header class="site-header">
        <div class="header-inner">
            <div class="header-group-left">
                <div class="logo"><a href="home.php">CROUS-X</a></div>
                <nav class="main-nav" id="desktop-nav">...</nav>
            </div>
            <div class="header-actions">
                Auth buttons, lang switcher, theme toggle, hamburger
            </div>
        </div>
        <nav class="main-nav mobile-nav-menu" id="mobile-nav">...</nav>
      </header>
  */}
  <header class="site-header">
    <div class="header-inner">
        <div class="header-group-left">
            <div class="logo">
                <a href="home.php">CROUS-X</a>
            </div>
            <nav class="main-nav" id="desktop-nav">
                <ul>
                    <li><a href="home.php" data-i18n-key="nav_news">News stand</a></li>
                    <li><a href="help.php" data-i18n-key="nav_help">Need help ?</a></li>
                    <li><a href="faq.php" data-i18n-key="nav_faq">FAQ</a></li>
                </ul>
            </nav>
        </div>

        <div class="header-actions">
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="auth-button" data-i18n-key="nav_profile"><i class="fas fa-user-circle"></i> <span>My profile</span></a>
                <a href="logout.php" class="auth-button primary" data-i18n-key="nav_logout"><span>Logout</span></a>
            <?php else: ?>
                <a href="login.php" class="auth-button" data-i18n-key="nav_signin"><i class="fas fa-sign-in-alt"></i> <span>Sign in</span></a>
                <a href="register.php" class="auth-button primary" data-i18n-key="nav_register"><span>Register</span></a>
            <?php endif; ?>

            <div class="language-switcher-container">
                <button id="language-switcher-toggle" class="header-button" aria-label="Select language" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-globe"></i>
                </button>
                <div id="language-switcher-dropdown" class="language-switcher-dropdown" role="menu">
                    <button class="language-choice-button" data-lang="en" role="menuitem">English</button>
                    <button class="language-choice-button" data-lang="fr" role="menuitem">Français</button>
                    <button class="language-choice-button" data-lang="es" role="menuitem">Español</button>
                </div>
            </div>

            <button id="theme-toggle" class="theme-toggle-button header-button" aria-label="Toggle dark mode">
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>

            <button class="hamburger" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobile-nav">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </div>
    <nav class="main-nav mobile-nav-menu" id="mobile-nav" aria-hidden="true">
        <ul>
            <li><a href="home.php" data-i18n-key="nav_news">News stand</a></li>
            <li><a href="help.php" data-i18n-key="nav_help">Need help ?</a></li>
            <li><a href="faq.php" data-i18n-key="nav_faq">FAQ</a></li>
            
            <?php if ($isLoggedIn): ?>
                <li><a href="dashboard.php" data-i18n-key="nav_profile">My profile</a></li>
                <li><a href="logout.php" data-i18n-key="nav_logout_mobile">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" data-i18n-key="nav_signin_mobile">Sign in</a></li>
                <li><a href="register.php" data-i18n-key="nav_register_mobile">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
  </header>


  <main class="app-container detail-page-wrapper"> {/* Changed .content-box and .detail-page-main-content to this */}
    <div class="detail-content-area"> {/* New wrapper for max-width and centering */}
      <p class="back-to-listings-link"><a href="home.php"><i class="fas fa-arrow-left"></i> <span data-i18n-key="back_to_listings">Back to Listings</span></a></p>

      <?php if ($error_message): ?>
        <section class="error-message-display">
          <h2>Error</h2>
          <p><?php echo htmlspecialchars($error_message); ?></p>
        </section>
      <?php elseif ($housing): ?>
        
        <div class="housing-detail-grid"> {/* This will be our two-column container */}
            
            <div class="housing-gallery-column">
                {/* 1) Image carousel */}
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
                        $stmt_images = $pdo->prepare("SELECT image_url FROM housing_images WHERE listing_id=? AND is_primary=0 LIMIT 4"); // Limit to 4 thumbnails
                        $stmt_images->execute([$housing_id]);
                        while ($row = $stmt_images->fetch(PDO::FETCH_ASSOC)){
                            $other_images_html .= '<img src="'.htmlspecialchars($row['image_url']).'" alt="Additional photo" class="gallery-thumb-image" data-fullsrc="'.htmlspecialchars($row['image_url']).'">';
                        }
                    }
                    // Add primary image to thumbnails if it exists, to make it clickable too
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
                    <span class="status-indicator-tag" data-i18n-key="status_for_rent">For Rent</span> {/* Example Tag */}
                    <div class="price-display-area">
                        <span class="amount">$<?php echo number_format((float)$housing['rent_amount'], 0); ?></span>
                        <span class="frequency">/ <?php echo htmlspecialchars($housing['rent_frequency']); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($housing['address_street'])): ?>
                <p class="location-address-text"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(implode(', ', array_filter([$housing['address_street'], $housing['address_city']] ) ) ); ?></p>
                <?php endif; ?>
                

                {/* Key details formatted like mockup */}
                <div class="key-info-block">
                    <div class="info-item">
                        <span class="info-label" data-i18n-key="info_label_type">Type</span>
                        <span class="info-value"><?php echo htmlspecialchars($housing['property_type']); ?></span>
                    </div>
                     <div class="info-item">
                        <span class="info-label" data-i18n-key="info_label_bedrooms">Bedrooms</span>
                        <span class="info-value"><?php echo intval($housing['num_bedrooms']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label" data-i18n-key="info_label_bathrooms">Bathrooms</span>
                        <span class="info-value"><?php echo htmlspecialchars($housing['num_bathrooms']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label" data-i18n-key="info_label_size">Size</span>
                        <span class="info-value"><?php echo intval($housing['square_footage']); ?> m²</span>
                    </div>
                    {/* These were not in mockup dropdowns but are useful: */}
                    <div class="info-item">
                        <span class="info-label" data-i18n-key="info_label_furnished">Furnished</span>
                        <span class="info-value"><?php echo $housing['is_furnished'] ? 'Yes' : 'No'; ?></span>
                    </div>
                     <div class="info-item">
                        <span class="info-label" data-i18n-key="info_label_pets">Pets Allowed</span>
                        <span class="info-value"><?php echo $housing['allows_pets'] ? 'Yes' : 'No'; ?></span>
                    </div>
                </div>
                
                {/* Action/Booking Button */}
                <a href="booking.php?id=<?php echo htmlspecialchars($housing['listing_id']); ?>" class="cta-button primary-cta-button" data-i18n-key="apply_booking_button">Apply Now / Request Booking</a>
                
                {/* Accordions for Description & Amenities */}
                <?php if (!empty($housing['description'])): ?>
                <div class="content-accordion-item">
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
                    <div class="content-accordion-item">
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
                 <div class="info-item full-width-info"> {/* Availability Date, full width */}
                    <span class="info-label" data-i18n-key="info_label_available_date">Available from:</span>
                    <span class="info-value"><?php echo htmlspecialchars(date("F j, Y", strtotime($housing['availability_date']))); ?></span>
                </div>
            </div>
        </div> {/* End .housing-detail-grid */}


        {/* Map Section - Full width below grid */}
        <?php if ($housing['latitude'] && $housing['longitude']): ?>
          <section class="page-section-layout map-container-section">
            <h2 class="section-title-styled" data-i18n-key="location_map_title">Location</h2>
            <div id="detailPageMap" class="map-render-area"></div>
          </section>
        <?php endif; ?>
        
        {/* Latest Reviews Section - Full width */}
        <section class="page-section-layout reviews-container-section">
            <h2 class="section-title-styled" data-i18n-key="latest_reviews_title">Latest reviews</h2>
            <div class="reviews-grid-layout">
                {/* Placeholder for PHP loop for reviews. Static examples: */}
                <div class="review-card-item">
                    <div class="review-card-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                    <h3 class="review-card-heading">Great Value</h3>
                    <p class="review-card-text">Really good for the price. Clean and convenient for students.</p>
                    <div class="review-card-author-area">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer" class="author-avatar-image">
                        <div>
                            <span class="author-name-text">Alex R.</span>
                            <span class="review-date-text">November 10, 2023</span>
                        </div>
                    </div>
                </div>
                <div class="review-card-item">
                    <div class="review-card-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <h3 class="review-card-heading">Perfect for Studies!</h3>
                    <p class="review-card-text">Quiet, comfortable, and had everything I needed. Landlord was also very helpful.</p>
                    <div class="review-card-author-area">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer" class="author-avatar-image">
                        <div>
                            <span class="author-name-text">Maria G.</span>
                            <span class="review-date-text">October 28, 2023</span>
                        </div>
                    </div>
                </div>
                 <div class="review-card-item">
                    <div class="review-card-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                    <h3 class="review-card-heading">Good Student Housing</h3>
                    <p class="review-card-text">Met my expectations for student accommodation. Close to campus.</p>
                    <div class="review-card-author-area">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer" class="author-avatar-image">
                        <div>
                            <span class="author-name-text">Sam K.</span>
                            <span class="review-date-text">September 05, 2023</span>
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
    </div> {/* End .detail-content-area */}
  </main>

  {/* Chat Widget (Keep as is from your original/previous setup) */}
  <div id="chat-widget">
    <div id="chat-container" class="chat-hidden">
      <div id="chat-header">
        <span data-i18n-key="chat_title">CROUS-X Assistant</span>
        <button id="chat-close-button" aria-label="Close chat">×</button>
      </div>
      <div id="chat-messages">
        <div class="message bot" data-i18n-key="chat_greeting_detail_page"> {/* Specific greeting */}
          Hello! Viewing details for a specific housing? Ask me anything about it!
        </div>
      </div>
      <div id="chat-input-area">
        <input type="text" id="chat-input" placeholder="Ask about this housing..." data-i18n-key-placeholder="chat_placeholder_ask_housing"/>
        <button id="chat-send-button" aria-label="Send message">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
      <div id="chat-loading" class="chat-hidden">
          <i class="fas fa-spinner fa-spin"></i> <span data-i18n-key="chat_loading_text">Thinking...</span>
      </div>
    </div>
    <button id="chat-toggle-button" aria-label="Toggle chat">
      <i class="fas fa-comments"></i>
    </button>
  </div>

  {/* Scripts */}
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
  
  <?php if ($housing && $housing['latitude'] && $housing['longitude']): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mapElement = document.getElementById('detailPageMap'); // Updated ID
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
    // Basic JS for Gallery Thumbnails, Accordions, Favorite Button
    document.addEventListener('DOMContentLoaded', function() {
        // Gallery Thumbnail Click
        const mainImageDisplay = document.getElementById('housingMainImage');
        const thumbImages = document.querySelectorAll('.gallery-thumb-image');
        thumbImages.forEach(thumb => {
            thumb.addEventListener('click', function() {
                if (mainImageDisplay) mainImageDisplay.src = this.dataset.fullsrc;
                thumbImages.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Accordion Toggle
        const accordionTriggers = document.querySelectorAll('.accordion-trigger-button');
        accordionTriggers.forEach(trigger => {
            trigger.addEventListener('click', function() {
                const panel = this.nextElementSibling;
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                this.setAttribute('aria-expanded', !isExpanded);
                panel.style.maxHeight = !isExpanded ? panel.scrollHeight + "px" : null;
                panel.style.opacity = !isExpanded ? 1 : 0;
                panel.style.paddingTop = !isExpanded ? '0.8rem' : '0'; // Match bottom padding if any
                panel.style.paddingBottom = !isExpanded ? '0.8rem' : '0';
                
                const icon = this.querySelector('.accordion-icon');
                icon.classList.toggle('fa-chevron-up', !isExpanded);
                icon.classList.toggle('fa-chevron-down', isExpanded);
            });
        });

        // Favorite Button Toggle (Visual Only)
        const favoriteButton = document.querySelector('.favorite-toggle-button');
        if (favoriteButton) {
            favoriteButton.addEventListener('click', function() {
                this.classList.toggle('active');
                const icon = this.querySelector('i');
                icon.classList.toggle('far'); // outline
                icon.classList.toggle('fas'); // solid (filled)
            });
        }
    });
  </script>

  <script src="script.js" defer></script> {/* Your main site script for header, theme, lang */}
  <script src="chatbot.js" defer></script>
</body>
</html>