<?php
// public/housing-detail.php

session_start();

// 1) Load your DB connection
//    This assumes config.php lives at project_root/config/config.php
require_once __DIR__ . '/../config/config.php';

$housing_item  = null;
$error_message = null;

// 2) Validate & fetch
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $error_message = 'Invalid or missing housing ID.';
} else {
    $housing_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare('
            SELECT
                h.*,
                hi.image_url AS primary_image
            FROM housings AS h
            LEFT JOIN housing_images AS hi
              ON hi.listing_id = h.listing_id
             AND hi.is_primary = 1
            WHERE h.listing_id = ?
        ');
        $stmt->execute([$housing_id]);
        $housing_item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$housing_item) {
            $error_message = 'This listing could not be found.';
        }
    } catch (PDOException $e) {
        error_log('Housing-detail query error: ' . $e->getMessage());
        $error_message = 'A database error occurred.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>
    <?= $housing_item ? htmlspecialchars($housing_item['title']) : 'Housing Details' ?>
  </title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Quick inline tweaks — move into style.css if you like */
    .detail-container { max-width:800px; margin:40px auto; }
    .carousel img { width:100%; height:auto; display:block; }
    .amenities { list-style:none; padding:0; display:flex; flex-wrap:wrap; gap:8px; }
    .amenities li { background:#eee; padding:6px 10px; border-radius:4px; }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="detail-container">
    <a href="index.php">← Back to Listings</a>

    <?php if ($error_message): ?>
      <h2>Error</h2>
      <p><?= htmlspecialchars($error_message) ?></p>

    <?php else: ?>
      <h1><?= htmlspecialchars($housing_item['title']) ?></h1>

      <!-- 3) Image carousel -->
      <div class="carousel">
        <?php if ($housing_item['primary_image']): ?>
          <img src="<?= htmlspecialchars($housing_item['primary_image']) ?>"
               alt="Primary photo">
        <?php endif; ?>
        <?php
        // Fetch up to 5 more images
        $imgStmt = $pdo->prepare('
          SELECT image_url FROM housing_images
          WHERE listing_id = ? AND is_primary = 0
          LIMIT 5
        ');
        $imgStmt->execute([$housing_id]);
        while ($img = $imgStmt->fetch(PDO::FETCH_ASSOC)): ?>
          <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Photo">
        <?php endwhile; ?>
      </div>

      <!-- 4) Description -->
      <?php if (!empty($housing_item['description'])): ?>
        <section>
          <h2>Description</h2>
          <p><?= nl2br(htmlspecialchars($housing_item['description'])) ?></p>
        </section>
      <?php endif; ?>

      <!-- 5) Details -->
      <section>
        <h2>Details</h2>
        <ul>
          <li><strong>Type:</strong> <?= htmlspecialchars($housing_item['property_type']) ?></li>
          <li><strong>Rent:</strong> $<?= number_format($housing_item['rent_amount'],2) ?> / <?= htmlspecialchars($housing_item['rent_frequency']) ?></li>
          <li><strong>Bedrooms:</strong> <?= intval($housing_item['num_bedrooms']) ?></li>
          <li><strong>Bathrooms:</strong> <?= htmlspecialchars($housing_item['num_bathrooms']) ?></li>
          <li><strong>Square Footage:</strong> <?= intval($housing_item['square_footage']) ?> m²</li>
          <li><strong>Furnished:</strong> <?= $housing_item['is_furnished'] ? 'Yes' : 'No' ?></li>
          <li><strong>Pets Allowed:</strong> <?= $housing_item['allows_pets'] ? 'Yes' : 'No' ?></li>
          <li><strong>Availability:</strong> <?= htmlspecialchars($housing_item['availability_date']) ?></li>
        </ul>
      </section>

      <!-- 6) Amenities -->
      <?php
      $amStmt = $pdo->prepare('
        SELECT a.name
          FROM housing_amenities AS ha
          JOIN amenities AS a USING(amenity_id)
         WHERE ha.listing_id = ?
      ');
      $amStmt->execute([$housing_id]);
      $amenities = $amStmt->fetchAll(PDO::FETCH_COLUMN);
      if ($amenities): ?>
        <section>
          <h2>Amenities</h2>
          <ul class="amenities">
            <?php foreach ($amenities as $amen): ?>
              <li><?= htmlspecialchars($amen) ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <!-- 7) Map -->
      <?php if ($housing_item['latitude'] && $housing_item['longitude']): ?>
        <section>
          <h2>Location</h2>
          <div id="detail-map" style="height:300px;"></div>
          <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
          <script>
            const map = L.map('detail-map')
              .setView([<?= $housing_item['latitude'] ?>, <?= $housing_item['longitude'] ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              maxZoom:19, attribution:'© OpenStreetMap contributors'
            }).addTo(map);
            L.marker([<?= $housing_item['latitude'] ?>, <?= $housing_item['longitude'] ?>])
             .addTo(map).bindPopup(<?= json_encode($housing_item['title']) ?>);
          </script>
        </section>
      <?php endif; ?>

    <?php endif; ?>
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>
