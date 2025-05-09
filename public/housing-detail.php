<?php
// public/housing-detail.php
session_start();

// 1) Database connection
require_once __DIR__ . '/../config/config.php'; // Defines $pdo

// Determine if user is logged in for header links
$isLoggedIn = isset($_SESSION['user_id']); // Matches authenticate.php and dashboard.php session key

$housing = null;
$error_message = null;
$housing_id = null; // Initialize housing_id

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $error_message = 'Invalid or missing housing ID.';
} else {
    $housing_id = (int)$_GET['id']; // Store the ID for later use
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
<html lang="en"> <!-- Language will be updated by script.js -->
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>
    <?php echo $housing ? htmlspecialchars($housing['title']) . ' - CROUS-X' : 'Housing Details - CROUS-X'; ?>
  </title>
  
  <!-- CSS Includes -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"/>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="assets/images/icon.png"> <!-- Make sure path is correct -->

  <!-- JS for Markdown (used by chatbot) -->
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>

  <header class="site-header">
    <a href="home.php" class="logo-link"><div class="logo">CROUS-X</div></a>

    <button class="hamburger" aria-label="Toggle navigation menu" aria-expanded="false">
      <span class="bar"></span>
      <span class="bar"></span>
      <span class="bar"></span>
    </button>

    <nav class="main-nav" aria-hidden="true">
      <ul>
        <li><a href="home.php" data-i18n-key="nav_news">News stand</a></li>
        <li><a href="help.php" data-i18n-key="nav_help">Need help ?</a></li>
        <li><a href="faq.php" data-i18n-key="nav_faq">FAQ</a></li>
        
        <?php if ($isLoggedIn): ?>
          <li><a href="dashboard.php" data-i18n-key="nav_profile">My profile</a></li>
          <li><a href="logout.php" class="btn btn-signin">Logout</a></li> <!-- Re-using btn-signin style for logout -->
        <?php else: ?>
          <li><a href="login.php" class="btn btn-signin" data-i18n-key="nav_signin">Sign in</a></li>
          <li><a href="register.php" class="btn btn-register" data-i18n-key="nav_register">Register</a></li>
        <?php endif; ?>

        <li class="language-switcher">
          <button id="language-toggle" aria-label="Select language" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-globe"></i>
            <span class="current-lang">EN</span>
          </button>
          <ul id="language-options" class="language-dropdown" role="menu">
            <li role="menuitem"><a href="#en" data-lang="en">English</a></li>
            <li role="menuitem"><a href="#fr" data-lang="fr">Français</a></li>
            <li role="menuitem"><a href="#es" data-lang="es">Español</a></li>
          </ul>
        </li>
        <li>
          <button id="theme-toggle" class="btn btn-dark-mode" aria-label="Toggle dark mode">
            <i class="fas fa-moon"></i>
          </button>
        </li>
      </ul>
    </nav>
  </header>

  <main class="content-box detail-page-main-content"> <!-- Using content-box for consistent styling -->
    <div class="detail-container">
      <p class="back-to-listings"><a href="home.php"><i class="fas fa-arrow-left"></i> Back to Listings</a></p>

      <?php if ($error_message): ?>
        <section class="error-message-section">
          <h2>Error</h2>
          <p><?php echo htmlspecialchars($error_message); ?></p>
        </section>
      <?php elseif ($housing): ?>
        <h1><?php echo htmlspecialchars($housing['title']); ?></h1>

        <!-- 1) Image carousel -->
        <section class="detail-section carousel-section">
          <!-- <h2>Gallery</h2> remove if not needed -->
          <div class="carousel">
            <?php if ($housing['primary_image']): ?>
              <img src="<?php echo htmlspecialchars($housing['primary_image']); ?>" alt="Primary photo of <?php echo htmlspecialchars($housing['title']); ?>">
            <?php else: ?>
              <div class="image-placeholder-detail">
                <i class="far fa-image"></i>
                <span>No primary image</span>
              </div>
            <?php endif; ?>
            <?php
            if ($housing_id) { // Only query if housing_id is valid
                $stmt_images = $pdo->prepare("SELECT image_url FROM housing_images WHERE listing_id=? AND is_primary=0 LIMIT 5");
                $stmt_images->execute([$housing_id]);
                while ($row = $stmt_images->fetch(PDO::FETCH_ASSOC)): ?>
                  <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Additional photo of <?php echo htmlspecialchars($housing['title']); ?>">
                <?php endwhile;
            } ?>
          </div>
        </section>

        <!-- 2) Description -->
        <?php if (!empty($housing['description'])): ?>
          <section class="detail-section description-section">
            <h2>Description</h2>
            <p><?php echo nl2br(htmlspecialchars($housing['description'])); ?></p>
          </section>
        <?php endif; ?>

        <!-- 3) Key details -->
        <section class="detail-section key-details-section">
          <h2>Details</h2>
          <ul class="key-details-list">
            <li><strong>Type:</strong> <?php echo htmlspecialchars($housing['property_type']); ?></li>
            <li><strong>Rent:</strong> $<?php echo number_format((float)$housing['rent_amount'], 2); ?> / 
                <?php echo htmlspecialchars($housing['rent_frequency']); ?></li>
            <li><strong>Bedrooms:</strong> <?php echo intval($housing['num_bedrooms']); ?></li>
            <li><strong>Bathrooms:</strong> <?php echo htmlspecialchars($housing['num_bathrooms']); ?></li>
            <li><strong>Size:</strong> <?php echo intval($housing['square_footage']); ?> m²</li>
            <li><strong>Furnished:</strong> <?php echo $housing['is_furnished'] ? 'Yes' : 'No'; ?></li>
            <li><strong>Pets Allowed:</strong> <?php echo $housing['allows_pets'] ? 'Yes' : 'No'; ?></li>
            <li><strong>Available from:</strong> <?php echo htmlspecialchars(date("F j, Y", strtotime($housing['availability_date']))); ?></li>
          </ul>
        </section>

        <!-- 4) Amenities -->
        <?php
        if ($housing_id) { // Only query if housing_id is valid
            $stmt_amenities = $pdo->prepare("SELECT a.name FROM housing_amenities ha JOIN amenities a USING(amenity_id) WHERE ha.listing_id=?");
            $stmt_amenities->execute([$housing_id]);
            $amenities_list = $stmt_amenities->fetchAll(PDO::FETCH_COLUMN);
            if ($amenities_list): ?>
              <section class="detail-section amenities-section">
                <h2>Amenities</h2>
                <ul class="amenities-list">
                  <?php foreach ($amenities_list as $amenity): ?>
                    <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($amenity); ?></li>
                  <?php endforeach; ?>
                </ul>
              </section>
            <?php endif;
        } ?>

        <!-- 5) Map -->
        <?php if ($housing['latitude'] && $housing['longitude']): ?>
          <section class="detail-section map-section">
            <h2>Location</h2>
            <div id="detail-map"></div>
          </section>
        <?php endif; ?>
        
        <!-- 6) Action/Booking Button (Placeholder) -->
        <section class="detail-section action-section">
            
                  
<a href="booking.php?id=<?php echo htmlspecialchars($housing['listing_id']); ?>" class="btn btn-register btn-apply">Apply Now / Request Booking</a>

    
        </section>

      <?php else: ?>
        <section class="info-message-section">
          <p>No housing details to display.</p>
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
        <div class="message bot" data-i18n-key="chat_greeting">
          Hi there! How can I help you navigate CROUS-X today?
        </div>
      </div>
      <div id="chat-input-area">
        <input type="text" id="chat-input" placeholder="Ask a question..." data-i18n-key-placeholder="chat_placeholder"/>
        <button id="chat-send-button" aria-label="Send message">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
      <div id="chat-loading" class="chat-hidden"> <!-- Ensure data-i18n-key is not on the hidden div but its content if needed -->
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
    // Initialize map for this specific page
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

  <script src="script.js" defer></script>
  <script src="chatbot.js" defer></script>
</body>
</html>