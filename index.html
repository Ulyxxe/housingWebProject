<!DOCTYPE html>
<html lang="en">
  <!-- JS will update this lang attribute -->
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CROUS-X</title>
    <!-- CSS Includes -->
    <!-- Leaflet CSS (Map CSS)-->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />

    <!-- Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <!-- Marker Cluster CSS (Markers on the map) -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
    />
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
    />
    <link rel="stylesheet" href="style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
      integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="icon" type="image/png" href="images/icon.png" />
  </head>
  <body>
    <header class="site-header">
      <div class="logo">CROUS-X</div>

      <button class="hamburger" aria-label="Toggle navigation menu">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </button>

      <nav class="main-nav">
        <ul>
          <li><a href="#" data-i18n-key="nav_news">News stand</a></li>
          <li><a href="#" data-i18n-key="nav_help">Need help ?</a></li>
          <li><a href="#" data-i18n-key="nav_profile">My profile</a></li>

          <li class="language-switcher">
            <button
              id="language-toggle"
              aria-label="Select language"
              aria-haspopup="true"
              aria-expanded="false"
            >
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
            <button
              id="theme-toggle"
              class="btn btn-dark-mode"
              aria-label="Toggle dark mode"
            >
              <i class="fas fa-moon"></i>
              <!-- Moon Icon for dark mode switch, modified by JS -->
            </button>
          </li>
          <li>
            <a
              href="login.html"
              class="btn btn-signin"
              data-i18n-key="nav_signin"
              >Sign in</a
            >
          </li>
          <li>
            <a
              href="register.html"
              class="btn btn-register"
              data-i18n-key="nav_register"
              >Register</a
            >
          </li>
        </ul>
      </nav>
    </header>

    <!-- Chat Widget -->
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
          <input
            type="text"
            id="chat-input"
            placeholder="Ask a question..."
            data-i18n-key-placeholder="chat_placeholder"
          />
          <button id="chat-send-button" aria-label="Send message">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
        <div id="chat-loading" class="chat-hidden" data-i18n-key="chat_loading">
          <i class="fas fa-spinner fa-spin"></i> Thinking...
        </div>
      </div>
      <button id="chat-toggle-button" aria-label="Toggle chat">
        <i class="fas fa-comments"></i>
      </button>
    </div>

    <!-- Main Content -->
    <div class="main-content-wrapper">
      <aside class="filters-sidebar" id="filters-container">
        <!-- Example: Add keys to filter titles and labels -->
        <section class="filter-group">
          <h3 data-i18n-key="filter_housing_type">Housing Type</h3>
          <div class="checkbox-item">
            <input
              type="checkbox"
              id="type-studio"
              class="filter-type"
              value="Studio"
            />
            <label for="type-studio" data-i18n-key="filter_type_studio"
              >Studio</label
            >
          </div>
          <div class="checkbox-item">
            <input
              type="checkbox"
              id="type-apartment"
              class="filter-type"
              value="Apartment"
            />
            <label for="type-apartment" data-i18n-key="filter_type_apartment"
              >Apartment</label
            >
          </div>
          <div class="checkbox-item">
            <input
              type="checkbox"
              id="type-shared"
              class="filter-type"
              value="Shared Room"
            />
            <label for="type-shared" data-i18n-key="filter_type_shared"
              >Shared Room</label
            >
          </div>
          <div class="checkbox-item">
            <input
              type="checkbox"
              id="type-house"
              class="filter-type"
              value="House"
            />
            <label for="type-house" data-i18n-key="filter_type_house"
              >House</label
            >
          </div>
        </section>

        <section class="filter-group">
          <div class="range-header">
            <label for="price-range" data-i18n-key="filter_max_price"
              >Max Price</label
            >
            <span id="price-range-value">$10000</span>
          </div>
          <input
            type="range"
            id="price-range"
            name="price-range"
            min="0"
            max="10000"
            value="10000"
            class="slider filter-range"
          />
        </section>

        <section class="filter-group">
          <div class="range-header">
            <label for="size-range" data-i18n-key="filter_max_size"
              >Max Size</label
            >
            <span id="size-range-value">250 m²</span>
          </div>
          <input
            type="range"
            id="size-range"
            name="size-range"
            min="9"
            max="250"
            value="250"
            class="slider filter-range"
          />
        </section>

        <button
          id="clear-filters-btn"
          class="btn"
          data-i18n-key="filter_clear"
          style="
            width: 100%;
            margin-top: 15px;
            background-color: var(--light-text);
            color: white;
          "
        >
          Clear Filters
        </button>
      </aside>

      <main class="results-area">
        <div class="search-and-sort">
          <div class="search-container">
            <input
              type="search"
              id="search-input"
              placeholder="Search by name..."
              data-i18n-key-placeholder="search_placeholder"
            />
            <button class="search-btn"><i class="fas fa-search"></i></button>
          </div>
          <div class="sort-options">
            <button
              class="sort-btn active"
              data-sort="new"
              data-i18n-key="sort_new"
            >
              <i class="fas fa-check"></i> New
            </button>
            <button
              class="sort-btn"
              data-sort="price-asc"
              data-i18n-key="sort_price_asc"
            >
              Price ascending
            </button>
            <button
              class="sort-btn"
              data-sort="price-desc"
              data-i18n-key="sort_price_desc"
            >
              Price descending
            </button>
            <button
              class="sort-btn"
              data-sort="rating"
              data-i18n-key="sort_rating"
            >
              Rating
            </button>
          </div>
        </div>

        <div class="results-layout">
          <div class="results-grid-container">
            <div class="results-grid" id="results-grid">
              <!-- Dynamic content - translations might need to be applied when rendering cards if names/types are translated -->
            </div>
          </div>
          <div class="map-container">
            <div id="map"></div>
            <div id="map-resize-handle"></div>
          </div>
        </div>
      </main>
    </div>

    <!-- Scripts -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="script.js"></script>
    <script src="chatbot.js"></script>
    <!-- Ensure chatbot.js also handles potential translations if needed -->
  </body>
</html>
