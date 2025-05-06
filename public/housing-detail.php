<?php
// public/housing-detail.php
session_start();

// 1) Database connection
require_once __DIR__ . '/../config/config.php';

$housing      = null;
$error_message = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $error_message = 'Invalid or missing housing ID.';
} else {
    $id = (int)$_GET['id'];
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
        $stmt->execute([$id]);
        $housing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$housing) {
            $error_message = 'Listing not found.';
        }
    } catch (PDOException $e) {
        error_log("housing-detail error: ".$e->getMessage());
        $error_message = 'A database error occurred.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>
    <?= $housing
        ? htmlspecialchars($housing['title'])
        : 'Housing Details' ?>
  </title>
  <link rel="stylesheet" href="style.css">
  <!-- Leaflet CSS for map -->
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
  />
  <style>
    .detail-container { max-width:800px; margin:40px auto; padding:0 20px; }
    .carousel { display:flex; overflow-x:auto; gap:8px; margin-bottom:20px; }
    .carousel img { height:200px; border-radius:6px; }
    .amenities { list-style:none; padding:0; display:flex; flex-wrap:wrap; gap:8px; }
    .amenities li { background:#eee; padding:6px 10px; border-radius:4px; }
  </style>
</head>
<body>

  <!-- inline header/nav -->
  <header class="site-header">
    <div class="logo">CROUS-X</div>
    <button class="hamburger" aria-label="Toggle menu">
      <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>
    <nav class="main-nav">
      <ul>
        <li><a href="index.php">News stand</a></li>
        <li><a href="help.php">Need help ?</a></li>
        <li><a href="faq.php">FAQ</a></li>
        <li><a href="#">My profile</a></li>
        <li><a href="login.php">Sign in</a></li>
        <li><a href="register.php">Register</a></li>
      </ul>
    </nav>
  </header>

  <div class="detail-container">
    <p><a href="index.php">← Back to Listings</a></p>

    <?php if ($error_message): ?>
      <h2>Error</h2>
      <p><?= htmlspecialchars($error_message) ?></p>
    <?php else: ?>
      <h1><?= htmlspecialchars($housing['title']) ?></h1>

      <!-- 1) Image carousel -->
      <div class="carousel">
        <?php if ($housing['primary_image']): ?>
          <img src="<?= htmlspecialchars($housing['primary_image']) ?>" alt="Photo">
        <?php endif; ?>
        <?php
        $imgs = $pdo
          ->prepare("SELECT image_url FROM housing_images WHERE listing_id=? AND is_primary=0 LIMIT 5");
        $imgs->execute([$id]);
        while ($row = $imgs->fetch(PDO::FETCH_ASSOC)): ?>
          <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Photo">
        <?php endwhile; ?>
      </div>

      <!-- 2) Description -->
      <?php if (!empty($housing['description'])): ?>
        <section>
          <h2>Description</h2>
          <p><?= nl2br(htmlspecialchars($housing['description'])) ?></p>
        </section>
      <?php endif; ?>

      <!-- 3) Key details -->
      <section>
        <h2>Details</h2>
        <ul>
          <li><strong>Type:</strong> <?= htmlspecialchars($housing['property_type']) ?></li>
          <li><strong>Rent:</strong> $<?= number_format($housing['rent_amount'],2) ?> / 
              <?= htmlspecialchars($housing['rent_frequency']) ?></li>
          <li><strong>Bedrooms:</strong> <?= intval($housing['num_bedrooms']) ?></li>
          <li><strong>Bathrooms:</strong> <?= htmlspecialchars($housing['num_bathrooms']) ?></li>
          <li><strong>Size:</strong> <?= intval($housing['square_footage']) ?> m²</li>
          <li><strong>Furnished:</strong> <?= $housing['is_furnished'] ? 'Yes' : 'No' ?></li>
          <li><strong>Pets:</strong> <?= $housing['allows_pets'] ? 'Yes' : 'No' ?></li>
          <li><strong>Available from:</strong> <?= htmlspecialchars($housing['availability_date']) ?></li>
        </ul>
      </section>

      <!-- 4) Amenities -->
      <?php
      $am = $pdo
        ->prepare("SELECT a.name FROM housing_amenities ha JOIN amenities a USING(amenity_id) WHERE ha.listing_id=?");
      $am->execute([$id]);
      $amen = $am->fetchAll(PDO::FETCH_COLUMN);
      if ($amen): ?>
        <section>
          <h2>Amenities</h2>
          <ul class="amenities">
            <?php foreach ($amen as $a): ?>
              <li><?= htmlspecialchars($a) ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <!-- 5) Map -->
      <?php if ($housing['latitude'] && $housing['longitude']): ?>
        <section>
          <h2>Location</h2>
          <div id="detail-map" style="height:300px;border:1px solid #ccc;"></div>
          <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
          <script>
            const map = L.map('detail-map')
              .setView([<?= $housing['latitude'] ?>,<?= $housing['longitude'] ?>],15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
              maxZoom:19, attribution:'© OpenStreetMap contributors'
            }).addTo(map);
            L.marker([<?= $housing['latitude'] ?>,<?= $housing['longitude'] ?>])
             .addTo(map).bindPopup(<?= json_encode($housing['title']) ?>);
          </script>
        </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- your existing JS for menu, dark mode, map resizing, chatbot, etc -->
  <script src="script.js" defer></script>
  <script src="chatbot.js" defer></script>
</body>
</html>
