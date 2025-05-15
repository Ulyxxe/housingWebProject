<?php
// session_start(); // Keep your PHP session start
// require_once 'config.php'; // Keep your PHP config
?>
<!DOCTYPE html>
<!-- Default lang to 'en', JS will update if needed -->
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CROUS-X | Find Student Housing</title>
    <meta
      name="description"
      content="Search and filter student accommodations in Paris with CROUS-X."
    />
    <link rel="stylesheet" href="style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    
    <!-- noUiSlider CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css" integrity="sha512-qveKnGrvOChbSzAdtSs8p69eoLegNoHPWzf9VolD/ស្លāk5L7gVNHY<seg_22>DqM0H_LgOyTCTHRGl7qSgEV_isQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="icon" type="image/png" href="assets/images/icon.png">

  </head>

  <body>
    <?php require 'header.php'; ?>
    <div class="app-container">
        <div class="main-content-wrapper">
            <aside class="filters-sidebar" id="filters-container">
              <h3 class="sidebar-title" data-lang-key="filters_title">Filters</h3>
              <section class="filter-group">
                <h4 data-lang-key="filter_housing_type">Housing Type</h4>
                <div class="checkbox-item"><input type="checkbox" id="type-studio" class="filter-type" value="Studio"><label for="type-studio" data-lang-key="filter_type_studio">Studio</label></div>
                <div class="checkbox-item"><input type="checkbox" id="type-apartment" class="filter-type" value="Apartment"><label for="type-apartment" data-lang-key="filter_type_apartment">Apartment</label></div>
                <div class="checkbox-item"><input type="checkbox" id="type-shared" class="filter-type" value="Shared Room"><label for="type-shared" data-lang-key="filter_type_shared">Shared Room</label></div>
                <div class="checkbox-item"><input type="checkbox" id="type-house" class="filter-type" value="House"><label for="type-house" data-lang-key="filter_type_house">House</label></div>
              </section>

              <section class="filter-group">
                <div class="range-header">
                  <label data-lang-key="filter_price_range">Price Range</label>
                  <span id="price-range-value-display">$0 - $10000</span>
                </div>
                <div class="noui-slider-container">
                    <div id="price-slider"></div>
                </div>
              </section>

              <section class="filter-group">
                <div class="range-header">
                  <label data-lang-key="filter_size_range">Size Range</label>
                  <span id="size-range-value-display">9 m² - 250 m²</span>
                </div>
                <div class="noui-slider-container">
                    <div id="size-slider"></div>
                </div>
              </section>
              <button id="clear-filters-btn" class="btn btn-secondary full-width" data-lang-key="filter_clear">Clear Filters</button>
            </aside>

            <main class="results-area">
              <div class="search-and-sort">
                <div class="search-container">
                  <input type="search" id="search-input" placeholder="Search by name..." data-lang-key-placeholder="search_placeholder_app" />
                  <button class="search-btn" aria-label="Search"><i class="fas fa-search"></i></button>
                </div>
                <div class="sort-options">
                  <button class="sort-btn active" data-sort="new" data-lang-key="sort_newest_app">Newest</button>
                  <button class="sort-btn" data-sort="price-asc" data-lang-key="sort_price_asc_app">Price <i class="fas fa-arrow-up"></i></button>
                  <button class="sort-btn" data-sort="price-desc" data-lang-key="sort_price_desc_app">Price <i class="fas fa-arrow-down"></i></button>
                  <button class="sort-btn" data-sort="rating" data-lang-key="sort_rating_app">Rating <i class="fas fa-star"></i></button>
                </div>
              </div>

              <div class="results-layout" id="results-layout">
                <div class="results-grid-container">
                  <div class="results-grid" id="results-grid">
                    <p data-lang-key="loading_listings_app">Loading listings...</p>
                  </div>
                </div>
                <div class="map-container-outer">
                    <div class="map-container" id="map-container-sticky">
                        <div id="map"></div>
                    </div>
                </div>
              </div>
            </main>
        </div>
    </div>

    <?php require 'chat-widget.php';?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.8.1/nouislider.min.js" integrity="sha512-g/feAizmeiVKSwvfW0Xk3ZHZqv5Zs8PEXEBKzL15pM0SevEvoX8eJ4yFWbqakvRj7vtw1Q97bLzEpG2IVWX0Mg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="script.js"></script> 
    <script src="chatbot.js"></script>
  </body>
</html>