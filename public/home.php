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
    <link rel="stylesheet" href="crous-x-app-style.css" /> <!-- NEW CSS FILENAME -->
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
    <header class="site-header"> <!-- Using landing page's header structure -->
      <div class="header-inner">
        <div class="header-group-left">
          <div class="logo">
            <a href="crous-x-landing.html" aria-label="Go to Landing Page">CROUS-X</a> <!-- Link to landing -->
          </div>
          <nav class="main-nav"> <!-- Standard nav for app page -->
            <ul>
              <li><a href="help.php" data-lang-key="nav_help">Need help?</a></li>
              <li><a href="faq.php" data-lang-key="nav_faq">FAQ</a></li>
              <li><a href="dashboard.php" data-lang-key="nav_profile">My profile</a></li>
              <!-- Admin link might be conditional based on PHP session -->
              <?php // if (isset($_SESSION['user_is_admin']) && $_SESSION['user_is_admin']): ?>
              <!-- <li><a href="admin.php" data-lang-key="nav_admin_app">Admin</a></li> -->
              <?php // endif; ?>
            </ul>
          </nav>
        </div>

        <div class="header-group-right">
          <div class="header-actions">
            <button
              id="language-switcher-toggle"
              class="header-button icon-button"
              aria-label="Choose language"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <i class="fas fa-globe" aria-hidden="true"></i>
            </button>
            <button
              id="theme-toggle"
              class="header-button icon-button theme-toggle-button"
              aria-label="Toggle theme"
            >
              <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" > <circle cx="12" cy="12" r="5" /> <line x1="12" y1="1" x2="12" y2="3" /> <line x1="12" y1="21" x2="12" y2="23" /> <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" /> <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" /> <line x1="1" y1="12" x2="3" y2="12" /> <line x1="21" y1="12" x2="23" y2="12" /> <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" /> <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" /> </svg>
              <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" > <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" /> </svg>
            </button>
            <!-- Conditional Login/Register or User Info based on PHP session -->
            <?php // if (isset($_SESSION['user_id'])): ?>
            <!-- Example: <span class="user-greeting" data-lang-key="nav_welcome">Welcome, <?php // echo htmlspecialchars($_SESSION['username']); ?>!</span> -->
            <!-- <a href="logout.php" class="header-button auth-button" data-lang-key="nav_logout">Logout</a> -->
            <?php // else: ?>
            <a href="login.php" class="header-button auth-button" data-lang-key="nav_signin_app">Sign In</a>
            <a href="register.php" class="header-button auth-button primary" data-lang-key="nav_register_app">Register</a>
            <?php // endif; ?>
          </div>
        </div>
        <div id="language-switcher-dropdown" class="language-switcher-dropdown" aria-hidden="true">
            <button class="language-choice-button" data-lang="en" aria-label="Switch to English">English</button>
            <button class="language-choice-button" data-lang="fr" aria-label="Switch to French">Français</button>
            <button class="language-choice-button" data-lang="es" aria-label="Switch to Spanish">Español</button>
        </div>
      </div>
    </header>

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
    <script src="crous-x-app-script.js"></script> <!-- NEW JS FILENAME -->
    <!-- chatbot.js should be integrated into crous-x-app-script.js or loaded after if it depends on elements created by the main script -->
  </body>
</html>