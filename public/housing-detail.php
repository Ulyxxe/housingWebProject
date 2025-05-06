<?php
// public/housing-detail.php

// --- Include your existing config.php ---
// Make sure the path is correct relative to this file.
// Example: if config.php is at the project root (housingWebProject/config.php)
// and you've defined ROOT_PATH in THIS file or it's defined globally first:

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__)); // housingWebProject/
}
require_once ROOT_PATH . '/config/config.php'; // << ADJUST THIS PATH

// If config.php already started the session, you might not need this line here.
// But it's safe to have if (session_status() === PHP_SESSION_NONE) { session_start(); }
// or ensure config.php handles it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$housing_item = null;
$error_message = null;
// $db should now be available globally if your config.php creates it.

// --- Check if $db connection was successful (if config.php might not die on failure) ---
if (empty($db)) { // Or if ($db === null) or if (!$db)
    $error_message = "Database service is currently unavailable. Please try again later.";
} else {
    try {
        if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
            $housing_id = (int)$_GET['id'];

            $stmt = $db->prepare("SELECT * FROM housings WHERE listing_id = :id");
            $stmt->bindParam(':id', $housing_id, PDO::PARAM_INT);
            $stmt->execute();
            $housing_item = $stmt->fetch();

            if (!$housing_item) {
                $error_message = "Housing listing not found.";
            }
            if ($housing_item && isset($housing_item['title'])) {
                 $page_title = htmlspecialchars($housing_item['title']);
            }

        } else {
            $error_message = "Invalid or missing housing ID provided.";
        }
    } catch (PDOException $e) {
        $error_message = "A database query error occurred.";
        error_log("PDO Query Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    }
}

// $db = null; // You might close the connection here or at the very end of the script if config.php doesn't.
// If config.php handles connection globally, it might also handle closing, or rely on PHP to auto-close.

// The rest of your HTML for public/housing-detail.php (as in the previous example) follows...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : ($housing_item && isset($housing_item['title']) ? htmlspecialchars($housing_item['title']) : 'Housing Details'); ?> - CROUS-X</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="icon" type="image/png" href="images/icon.png" />
    <style>
        /* ... your detail styles ... */
    </style>
</head>
<body>
    <?php
        // Make sure path to header.php is correct if it's not in public/
        // If header.php is in public/
        include 'header.php';
        // If header.php is in housingWebProject/includes/header.php
        // include ROOT_PATH . '/includes/header.php';
    ?>
    <div class="main-content-wrapper">
        <div class="content-box detail-container">
            <div class="back-link-container">
                <a href="index.php" class="back-link">← Back to Listings</a>
            </div>
            <?php if ($error_message): ?>
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif ($housing_item): ?>
                <!-- ... your HTML to display $housing_item ... -->
                 <h1><?php echo htmlspecialchars($housing_item['title']); ?></h1>
                 <?php if (!empty($housing_item['image'])): ?>
                    <img src="<?php echo htmlspecialchars(str_starts_with($housing_item['image'], 'http') ? $housing_item['image'] : 'images/' . $housing_item['image']); ?>" alt="<?php echo htmlspecialchars($housing_item['title']); ?>" class="detail-image">
                <?php endif; ?>
                <!-- ... all other detail sections ... -->
                 <?php if (isset($housing_item['latitude']) && isset($housing_item['longitude']) && $housing_item['latitude'] !== null && $housing_item['longitude'] !== null): ?>
                <div class="detail-section">
                    <h2>Location</h2>
                    <div id="detail-map" class="detail-map"></div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p>Housing details are not available for the selected listing.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php // include 'footer.php'; ?>

    <?php if ($housing_item && isset($housing_item['latitude']) && isset($housing_item['longitude']) && $housing_item['latitude'] !== null && $housing_item['longitude'] !== null): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('detail-map').setView([<?php echo $housing_item['latitude']; ?>, <?php echo $housing_item['longitude']; ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        L.marker([<?php echo $housing_item['latitude']; ?>, <?php echo $housing_item['longitude']; ?>]).addTo(map)
            .bindPopup(<?php echo json_encode(htmlspecialchars($housing_item['title'])); ?>)
            .openPopup();
    </script>
    <?php endif; ?>
    <script src="script.js"></script>
</body>
</html>