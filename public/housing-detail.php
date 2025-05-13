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
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary"> <!-- Added default theme/accent attributes -->
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

  <header class="site-header">
    <div class="header-inner">
        <div class="header-group-left">
            <div class="logo">
                <a href="home.php">CROUS-X</a>
            </div>
            <nav class="main-nav" id="desktop-nav"> <!-- Desktop Nav -->
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

            <div class="language-switcher-container"> <!-- Wrapper for positioning dropdown -->
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
    <!-- Mobile Navigation Menu (initially hidden, toggled by hamburger) -->
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

  <main class="app-container detail-page-container"> <!-- Replaced content-box, added detail-page-container -->
    <div class="detail-page-content-wrapper"> <!-- New wrapper for max-width and padding -->
      <p class="back-to-listings"><a href="home.php"><i class="fas fa-arrow-left"></i> <span data-i18n-key="back_to_listings">Back to Listings</span></a></p>

      <?php if ($error_message): ?>
        <section class="error-message-section">
          <h2>Error</h2>
          <p><?php echo htmlspecialchars($error_message); ?></p>
        </section>
      <?php elseif ($housing): ?>
        
        <div class="detail-main-layout">
            <div class="detail-image-column">
                <!-- Image carousel -->
                <section class="detail-section carousel-section">
                    <div class="carousel-main-image-wrapper">
                        <button class="favorite-btn" aria-label="Add to favorites"><i class="far fa-heart"></i></button>
                        <?php if ($housing['primary_image']): ?>
                            <img id="mainCarouselImage" src="<?php echo htmlspecialchars($housing['primary_image']); ?>" alt="Primary photo of <?php echo htmlspecialchars($housing['title']); ?>" class="carousel-main-image">
                        <?php else: ?>
                            <div class="image-placeholder-detail carousel-main-image">
                                <i class="far fa-image"></i>
                                <span>No primary image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    $other_images = [];
                    if ($housing_id) { 
                        $stmt_images = $pdo->prepare("SELECT image_url FROM housing_images WHERE listing_id=? AND is_primary=0 LIMIT 4"); // Limit thumbnails
                        $stmt_images->execute([$housing_id]);
                        $other_images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
                    }
                    if ($housing['primary_image'] || !empty($other_images)):
                    ?>
                    <div class="carousel-thumbnails">
                        <?php if ($housing['primary_image']): ?>
                            <img src="<?php echo htmlspecialchars($housing['primary_image']); ?>" alt="Thumbnail 1" class="carousel-thumbnail active" data-fullsrc="<?php echo htmlspecialchars($housing['primary_image']); ?>">
                        <?php endif; ?>
                        <?php foreach ($other_images as $index => $img_data): ?>
                            <img src="<?php echo htmlspecialchars($img_data['image_url']); ?>" alt="Thumbnail <?php echo $index + 2; ?>" class="carousel-thumbnail" data-fullsrc="<?php echo htmlspecialchars($img_data['image_url']); ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="detail-info-column">
                <h1 class="detail-title"><?php echo htmlspecialchars($housing['title']); ?></h1>
                <div class="detail-meta-top">
                    <span class="detail-tag">For Rent</span> <!-- Example Tag -->
                    <div class="detail-price">$<?php echo number_format((float)$housing['rent_amount'], 0); ?> <span class="rent-frequency">/ <?php echo htmlspecialchars($housing['rent_frequency']); ?></span></div>
                </div>
                
                <?php if (!empty($housing['address_street'])): // Assuming you have address fields ?>
                <p class="detail-address"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($housing['address_street'] . ', ' . $housing['address_city']); ?></p>
                <?php endif; ?>

                <!-- Key details (styled more like mockup's "Label: Value" concept) -->
                <section class="detail-section key-details-section">
                  <h2 class="detail-section-title" data-i18n-key="details_heading">Details</h2>
                  <div class="key-details-grid">
                      <div class="detail-item">
                          <span class="detail-label" data-i18n-key="detail_type">Type:</span>
                          <span class="detail-value"><?php echo htmlspecialchars($housing['property_type']); ?></span>
                      </div>
                      <div class="detail-item">
                          <span class="detail-label" data-i18n-key="detail_bedrooms">Bedrooms:</span>
                          <span class="detail-value"><?php echo intval($housing['num_bedrooms']); ?></span>
                      </div>
                      <div class="detail-item">
                          <span class="detail-label" data-i18n-key="detail_bathrooms">Bathrooms:</span>
                          <span class="detail-value"><?php echo htmlspecialchars($housing['num_bathrooms']); ?></span>
                      </div>
                      <div class="detail-item">
                          <span class="detail-label" data-i18n-key="detail_size">Size:</span>
                          <span class="detail-value"><?php echo intval($housing['square_footage']); ?> m²</span>
                      </div>
                      <div class="detail-item">
                          <span class="detail-label" data-i18n-key="detail_furnished">Furnished:</span>
                          <span class="detail-value"><?php echo $housing['is_furnished'] ? 'Yes' : 'No'; ?></span>
                      </div>
                      <div class="detail-item">
                          <span class="detail-label" data-i18n-key="detail_pets">Pets Allowed:</span>
                          <span class="detail-value"><?php echo $housing['allows_pets'] ? 'Yes' : 'No'; ?></span>
                      </div>
                      <div class="detail-item detail-item-full">
                          <span class="detail-label" data-i18n-key="detail_available">Available from:</span>
                          <span class="detail-value"><?php echo htmlspecialchars(date("F j, Y", strtotime($housing['availability_date']))); ?></span>
                      </div>
                  </div>
                </section>

                <section class="detail-section action-section">
                    <a href="booking.php?id=<?php echo htmlspecialchars($housing['listing_id']); ?>" class="btn-apply-detail" data-i18n-key="apply_now_button">Apply Now / Request Booking</a>
                </section>

                 <!-- Accordion for Description -->
                <?php if (!empty($housing['description'])): ?>
                <div class="detail-accordion">
                    <button class="accordion-toggle" aria-expanded="false">
                        <span data-i18n-key="description_heading">Description</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="accordion-content">
                        <p><?php echo nl2br(htmlspecialchars($housing['description'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Accordion for Amenities -->
                <?php
                if ($housing_id) {
                    $stmt_amenities = $pdo->prepare("SELECT a.name FROM housing_amenities ha JOIN amenities a USING(amenity_id) WHERE ha.listing_id=?");
                    $stmt_amenities->execute([$housing_id]);
                    $amenities_list = $stmt_amenities->fetchAll(PDO::FETCH_COLUMN);
                    if ($amenities_list): ?>
                    <div class="detail-accordion">
                        <button class="accordion-toggle" aria-expanded="false">
                            <span data-i18n-key="amenities_heading">Amenities</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <ul class="amenities-list-detail">
                            <?php foreach ($amenities_list as $amenity): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($amenity); ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif;
                } ?>
            </div>
        </div>
        
        <!-- Map (full width below the two columns) -->
        <?php if ($housing['latitude'] && $housing['longitude']): ?>
          <section class="detail-section map-section-detail">
            <h2 class="detail-section-title" data-i18n-key="location_heading">Location</h2>
            <div id="detail-map"></div>
          </section>
        <?php endif; ?>

        <!-- Latest Reviews Section (Placeholder HTML structure based on mockup) -->
        <section class="detail-section reviews-section-detail">
            <h2 class="detail-section-title" data-i18n-key="latest_reviews_heading">Latest reviews</h2>
            <div class="reviews-grid">
                <!-- Example Review Card 1 -->
                <div class="review-card-detail">
                    <div class="review-card-rating">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                    </div>
                    <h3 class="review-card-title">Great Location!</h3>
                    <p class="review-card-body">Loved how close it was to the university and local cafes. The apartment was clean and well-maintained.</p>
                    <div class="review-card-author">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer Jane Doe" class="reviewer-avatar">
                        <div class="reviewer-info">
                            <span class="reviewer-name">Jane Doe</span>
                            <span class="review-date">October 26, 2023</span>
                        </div>
                    </div>
                </div>
                <!-- Example Review Card 2 -->
                <div class="review-card-detail">
                    <div class="review-card-rating">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <h3 class="review-card-title">Absolutely Perfect</h3>
                    <p class="review-card-body">Couldn't ask for a better place. It had all the amenities I needed and the landlord was very responsive.</p>
                    <div class="review-card-author">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer John Smith" class="reviewer-avatar"> <!-- Make sure you have a placeholder image -->
                        <div class="reviewer-info">
                            <span class="reviewer-name">John Smith</span>
                            <span class="review-date">November 02, 2023</span>
                        </div>
                    </div>
                </div>
                <!-- Example Review Card 3 -->
                <div class="review-card-detail">
                    <div class="review-card-rating">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                    </div>
                    <h3 class="review-card-title">Decent Value</h3>
                    <p class="review-card-body">Good for the price, though a bit noisy at times. Overall a satisfactory experience for a student.</p>
                    <div class="review-card-author">
                        <img src="assets/images/placeholder-avatar.png" alt="Reviewer Alex P." class="reviewer-avatar">
                        <div class="reviewer-info">
                            <span class="reviewer-name">Alex P.</span>
                            <span class="review-date">September 15, 2023</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

      <?php else: ?>
        <section class="info-message-section">
          <p data-i18n-key="no_details_message">No housing details to display.</p>
        </section>
      <?php endif; ?>
    </div>
  </main>

  <!-- Chat Widget (Consistent with home.php) -->
  <div id="chat-widget">
    <div id="chat-container" class="chat-hidden">
      <div id="chat-header">
        <span data-i18n-key="chat_title">CROUS-X Assistant</span>
        <button id="chat-close-button" aria-label="Close chat">×</button>
      </div>
      <div id="chat-messages">
        <div class="message bot" data-i18n-key="chat_greeting_detail"> <!-- Potentially different greeting -->
          Hi there! Looking for more info about this housing? Ask me anything!
        </div>
      </div>
      <div id="chat-input-area">
        <input type="text" id="chat-input" placeholder="Ask about this housing..." data-i18n-key-placeholder="chat_placeholder_detail"/>
        <button id="chat-send-button" aria-label="Send message">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
      <div id="chat-loading" class="chat-hidden"> 
          <i class="fas fa-spinner fa-spin"></i> <span data-i18n-key="chat_loading">Thinking...</span>
      </div>
    </div>
    <button id="chat-toggle-button" aria-label="Toggle chat">
      <i class="fas fa-comments"></i>
    </button>
  </div>

  <!-- Scripts -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
  
  <?php if ($housing && $housing['latitude'] && $housing['longitude']): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mapElement = document.getElementById('detail-map');
      if (mapElement) {
        const lat = <?php echo $housing['latitude']; ?>;
        const lon = <?php echo $housing['longitude']; ?>;
        const title = <?php echo json_encode($housing['title']); ?>;

        const detailMap = L.map('detail-map').setView([lat, lon], 15);
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
    // Simple Carousel & Accordion Logic
    document.addEventListener('DOMContentLoaded', function() {
        // Carousel
        const mainImage = document.getElementById('mainCarouselImage');
        const thumbnails = document.querySelectorAll('.carousel-thumbnail');
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                if (mainImage) mainImage.src = this.dataset.fullsrc;
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Accordions
        const accordionToggles = document.querySelectorAll('.accordion-toggle');
        accordionToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                this.setAttribute('aria-expanded', !isExpanded);
                content.style.maxHeight = !isExpanded ? content.scrollHeight + "px" : null;
                content.style.opacity = !isExpanded ? 1 : 0;
                this.querySelector('i').classList.toggle('fa-chevron-down');
                this.querySelector('i').classList.toggle('fa-chevron-up');
            });
        });

        // Favorite button (visual only)
        const favButton = document.querySelector('.favorite-btn');
        if (favButton) {
            favButton.addEventListener('click', function() {
                this.classList.toggle('active');
                const icon = this.querySelector('i');
                icon.classList.toggle('far');
                icon.classList.toggle('fas'); // fas for solid heart
            });
        }
    });
  </script>

  <script src="script.js" defer></script> <!-- General script for header, theme, lang -->
  <script src="chatbot.js" defer></script>
</body>
</html>