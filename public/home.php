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
    <title>CROUS-X | Find Student Housing</title> <!-- App-specific title -->
    <meta
      name="description"
      content="Search and filter student accommodations in Paris with CROUS-X."
    />
    <!-- <link rel="icon" href="assets/images/crous-x-icon.svg" type="image/svg+xml" /> -->
    <link rel="stylesheet" href="style.css" /> <!-- NEW CSS FILENAME -->
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
    <!-- Removed your old style.css link, assuming crous-x-app-style.css replaces it -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script> <!-- For chat markdown -->
  </head>

  <body>
    <?php require 'header.php'; // Or require_once if you prefer ?>
    <!-- App Content Area -->
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
                  <label for="price-range" data-lang-key="filter_max_price">Max Price</label>
                  <span id="price-range-value">$10000</span>
                </div>
                <input type="range" id="price-range" name="price-range" min="0" max="10000" value="10000" class="slider filter-range" />
              </section>
              <section class="filter-group">
                <div class="range-header">
                  <label for="size-range" data-lang-key="filter_max_size">Max Size</label>
                  <span id="size-range-value">250 m²</span>
                </div>
                <input type="range" id="size-range" name="size-range" min="9" max="250" value="250" class="slider filter-range" />
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
                    <!-- Housing cards will be injected here by JS -->
                    <p data-lang-key="loading_listings_app">Loading listings...</p>
                  </div>
                </div>
                <div class="map-container-outer"> <!-- New wrapper for sticky positioning -->
                    <div class="map-container" id="map-container-sticky"> <!-- ID for JS targeting -->
                        <div id="map"></div>
                        <!-- Map resize handle removed for simplicity in this integration, can be added back if desired -->
                    </div>
                </div>
              </div>
            </main>
        </div>
    </div>

    <!-- Chat Widget (using your existing structure) -->
    <div id="chat-widget">
      <div id="chat-container" class="chat-hidden">
        <div id="chat-header">
          <span data-lang-key="chat_title_app">CROUS-X Assistant</span>
          <button id="chat-close-button" aria-label="Close chat">×</button>
        </div>
        <div id="chat-messages">
          <div class="message bot" data-lang-key="chat_greeting_app">
            Hi there! How can I help you navigate CROUS-X today?
          </div>
        </div>
        <div id="chat-input-area">
          <input type="text" id="chat-input" placeholder="Ask a question..." data-lang-key-placeholder="chat_placeholder_app" />
          <button id="chat-send-button" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
        </div>
        <div id="chat-loading" class="chat-hidden" data-lang-key="chat_loading_app">
          <i class="fas fa-spinner fa-spin"></i> Thinking...
        </div>
      </div>
      <button id="chat-toggle-button" aria-label="Toggle chat">
        <i class="fas fa-comments"></i>
      </button>
    </div>

    <!-- No cursor-dot or background-light from landing page here for app simplicity -->

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="script.js"></script> 
    <script src="chatbot.js"></script>
    <!-- chatbot.js should be integrated into crous-x-app-script.js or loaded after if it depends on elements created by the main script -->
  </body>
</html>