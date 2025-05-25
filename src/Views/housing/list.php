<?php
// src/Views/housing/list.php
// This view corresponds to your old public/home.php
// $pageTitle is passed from the controller
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'CROUS-X'; ?> | CROUS-X</title>
    <meta name="description" content="Search and filter student accommodations in Paris with CROUS-X." />
    <!-- IMPORTANT: Update CSS/JS paths to be absolute from the public root -->
    <link rel="stylesheet" href="/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="icon" type="image/png" href="/assets/images/icon.png">
</head>
<body>
    <?php require __DIR__ . '/../layout/header.php'; // Include header partial ?>

    <div class="app-container">
        <div class="main-content-wrapper">
            <aside class="filters-sidebar" id="filters-container">
              <!-- ... your filters HTML from home.php ... -->
            </aside>
            <main class="results-area">
              <!-- ... your search/sort and results-grid HTML from home.php ... -->
            </main>
        </div>
    </div>

    <?php require __DIR__ . '/../layout/chat-widget.php'; // Include chat widget partial ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.8.1/nouislider.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <!-- IMPORTANT: Update JS paths -->
    <script src="/script.js"></script>
    <script src="/chatbot.js"></script>
</body>
</html>