<?php
// public/housing-detail.php

// Bootstrap the application (adjust path as needed)
define('ROOT_PATH', __DIR__ . '/..');
require_once ROOT_PATH . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Initialize variables
$housing = null;\$images = [];\$amenities = [];
$error = null;

// Validate ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    \$error = 'Invalid housing ID.';
} else {
    \$id = (int)\$_GET['id'];
    try {
        // Fetch housing record
        \$stmt = \$pdo->prepare("SELECT * FROM housings WHERE listing_id = :id");
        \$stmt->execute([':id' => \$id]);
        \$housing = \$stmt->fetch(PDO::FETCH_ASSOC);
        if (!\$housing) {
            \$error = 'Housing listing not found.';
        } else {
            // Fetch all associated images (primary first)
            \$imgStmt = \$pdo->prepare(
                "SELECT image_url FROM housing_images WHERE listing_id = :id ORDER BY is_primary DESC, id ASC"
            );
            \$imgStmt->execute([':id' => \$id]);
            \$images = \$imgStmt->fetchAll(PDO::FETCH_COLUMN);
            // Fetch amenities
            \$amenStmt = \$pdo->prepare(
                "SELECT a.name, a.icon_url
                 FROM housing_amenities ha
                 JOIN amenities a ON ha.amenity_id = a.amenity_id
                 WHERE ha.listing_id = :id"
            );
            \$amenStmt->execute([':id' => \$id]);
            \$amenities = \$amenStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException \$e) {
        error_log('Housing Detail Query Error: ' . \$e->getMessage());
        \$error = 'A database error occurred.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset(\$housing['title']) ? htmlspecialchars(\$housing['title']) : 'Housing Details'; ?> - CROUS‑X</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* Carousel styles */
    .carousel { position: relative; max-width: 800px; margin: 20px auto; }
    .carousel-images img { width: 100%; display: none; border-radius:8px; }
    .carousel-images img.active { display: block; }
    .carousel-controls { position: absolute; top:50%; width:100%; display:flex; justify-content: space-between; transform: translateY(-50%); }
    .carousel-controls button { background:rgba(0,0,0,0.5); color:#fff; border:none; padding:8px 12px; cursor:pointer; border-radius:4px; }
    /* Amenities list */
    .amenities { list-style:none; display:flex; flex-wrap:wrap; gap:10px; padding:0; margin:0 0 20px; }
    .amenities li { display:flex; align-items:center; gap:5px; font-size:0.95em; }
    .amenities img { width:24px; height:24px; }
    .detail-section { margin-bottom:20px; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="main-content-wrapper">
    <div class="content-box detail-container">
        <a href="index.php" class="back-link">← Back to Listings</a>

        <?php if (\$error): ?>
            <h2>Error</h2>
            <p><?php echo htmlspecialchars(\$error); ?></p>
        <?php else: ?>
            <h1><?php echo htmlspecialchars(\$housing['title']); ?></h1>

            <?php if (!empty(\$images)): ?>
            <div class="carousel">
                <div class="carousel-images">
                    <?php foreach (\$images as \$idx => \$url): ?>
                        <img src="<?php echo htmlspecialchars(\$url); ?>" class="<?php echo \$idx===0?'active':''; ?>">
                    <?php endforeach; ?>
                </div>
                <div class="carousel-controls">
                    <button id="prev-btn">‹ Prev</button>
                    <button id="next-btn">Next ›</button>
                </div>
            </div>
            <?php endif; ?>

            <div class="detail-section">
                <h2>Description</h2>
                <p><?php echo nl2br(htmlspecialchars(\$housing['description'])); ?></p>
            </div>

            <?php if (!empty(\$amenities)): ?>
            <div class="detail-section">
                <h2>Amenities</h2>
                <ul class="amenities">
                    <?php foreach (\$amenities as \$amen): ?>
                        <li>
                            <?php if (!empty(\$amen['icon_url'])): ?>
                                <img src="<?php echo htmlspecialchars(\$amen['icon_url']); ?>" alt="<?php echo htmlspecialchars(\$amen['name']); ?>">
                            <?php endif; ?>
                            <?php echo htmlspecialchars(\$amen['name']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="detail-section">
                <h2>Details</h2>
                <ul>
                    <li><strong>Address:</strong> <?php echo htmlspecialchars(\$housing['address_street'] . ', ' . \$housing['address_city']); ?></li>
                    <li><strong>Type:</strong> <?php echo htmlspecialchars(\$housing['property_type']); ?></li>
                    <li><strong>Rent:</strong> $<?php echo number_format(\$housing['rent_amount'],2); ?> / <?php echo htmlspecialchars(\$housing['rent_frequency']); ?></li>
                    <li><strong>Bedrooms:</strong> <?php echo htmlspecialchars(\$housing['num_bedrooms']); ?></li>
                    <li><strong>Bathrooms:</strong> <?php echo htmlspecialchars(\$housing['num_bathrooms']); ?></li>
                    <li><strong>Size:</strong> <?php echo htmlspecialchars(\$housing['square_footage']); ?> m²</li>
                    <li><strong>Furnished:</strong> <?php echo \$housing['is_furnished']? 'Yes':'No'; ?></li>
                    <li><strong>Pets Allowed:</strong> <?php echo \$housing['allows_pets']? 'Yes':'No'; ?></li>
                    <li><strong>Available from:</strong> <?php echo htmlspecialchars(\$housing['availability_date']); ?></li>
                    <li><strong>Status:</strong> <?php echo htmlspecialchars(\$housing['status']); ?></li>
                </ul>
            </div>

            <?php if (\$housing['latitude'] && \$housing['longitude']): ?>
            <div class="detail-section">
                <h2>Location</h2>
                <div id="detail-map" style="height:300px;"></div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<!-- Inline scripts for carousel and map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Carousel logic
(function(){
    const imgs = document.querySelectorAll('.carousel-images img');
    let idx = 0;
    function show(i){ imgs.forEach((img,j)=> img.classList.toggle('active', j===i)); }
    document.getElementById('prev-btn').addEventListener('click', ()=> { idx = (idx -1 + imgs.length)%imgs.length; show(idx); });
    document.getElementById('next-btn').addEventListener('click', ()=> { idx = (idx +1)%imgs.length; show(idx); });
})();
// Map
<?php if (!empty(\$housing['latitude']) && !empty(\$housing['longitude'])): ?>
var map = L.map('detail-map').setView([<?php echo \$housing['latitude'];?>, <?php echo \$housing['longitude'];?>], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ maxZoom:19 }).addTo(map);
L.marker([<?php echo \$housing['latitude'];?>, <?php echo \$housing['longitude'];?>]).addTo(map)
    .bindPopup(<?php echo json_encode(htmlspecialchars(\$housing['title'])); ?>);
<?php endif; ?>
</script>
</body>
</html>
