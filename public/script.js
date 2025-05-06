// ==========================================
//          CROUS-X Script
// ==========================================
// Includes: Dark Mode, Filtering, Sorting, Map,
// Hamburger Menu, Language Switcher (i18n), Chatbot support
// ==========================================

(function () {
  // IIFE to encapsulate scope

  // --- Configuration & Constants ---
  const DEFAULT_LANG = "en";
  const SUPPORTED_LANGS = ["en", "fr", "es"];
  const MAP_INITIAL_COORDS = [48.8566, 2.3522]; // Paris center
  const MAP_INITIAL_ZOOM = 12;
  const MAP_MAX_ZOOM = 19;
  const MAP_TILE_URL = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
  const MAP_ATTRIBUTION = "© OpenStreetMap contributors";
  const MAP_INVALIDATE_DELAY = 50; // ms delay before invalidating map size
  const MIN_MAP_WIDTH = 200; // Minimum dimensions for resize
  const MIN_GRID_WIDTH = 200; // Minimum width for results grid during resize
  // Define custom map marker icon once (ensure Leaflet 'L' is available)
  const customMarkerIcon =
    typeof L !== "undefined"
      ? L.divIcon({
          className: "custom-div-icon",
          html: "", // Could add an <i> element here if needed
          iconSize: [24, 24],
          iconAnchor: [12, 12],
          popupAnchor: [0, -12],
        })
      : null;

  // --- DOM Element Selection ---
  // Grouped for clarity
  // Header / Nav
  const hamburgerButton = document.querySelector(".hamburger");
  const mainNav = document.querySelector(".main-nav");
  const themeToggleButton = document.getElementById("theme-toggle");
  const languageToggle = document.getElementById("language-toggle");
  const languageOptions = document.getElementById("language-options");
  const currentLangSpan = languageToggle
    ? languageToggle.querySelector(".current-lang")
    : null;

  // Filters / Sidebar
  const filtersContainer = document.getElementById("filters-container");
  const priceRangeSlider = document.getElementById("price-range");
  const sizeRangeSlider = document.getElementById("size-range");
  const priceRangeValueSpan = document.getElementById("price-range-value");
  const sizeRangeValueSpan = document.getElementById("size-range-value");
  const typeCheckboxes = document.querySelectorAll(".filter-type");
  const clearFiltersButton = document.getElementById("clear-filters-btn");

  // Results / Main Area
  const resultsGrid = document.getElementById("results-grid");
  const sortButtonsContainer = document.querySelector(".sort-options");
  const sortButtons = document.querySelectorAll(".sort-btn");
  const searchInput = document.getElementById("search-input");

  // Map specific
  const mapElement = document.getElementById("map");
  const mapToggleButton = document.getElementById("map-toggle-button"); // If you add one
  const resultsLayout = document.querySelector(".results-layout");
  const mapContainer = document.querySelector(".map-container"); // Map container for resize
  const mapResizeHandle = document.getElementById("map-resize-handle");

  // --- State Management ---
  let currentLanguageData = {}; // To hold the loaded language JSON
  let currentLangCode = DEFAULT_LANG;
  let activeFilters = {
    maxPrice: priceRangeSlider ? parseInt(priceRangeSlider.max, 10) : 10000, // Default max from slider or fallback
    maxSize: sizeRangeSlider ? parseInt(sizeRangeSlider.max, 10) : 250, // Default max from slider or fallback
    types: [],
    searchTerm: "",
  };
  let activeSort = "new"; // Default sort
  let map = null;
  let markersLayer = null;
  let isResizingMap = false;
  let mapResizeStartX, mapResizeInitialWidth; // Only need width for horizontal resize

  async function fetchHousingData() {
    try {
      const res = await fetch("/api/getHousing.php");
      const data = await res.json();
      // now each `item` is the raw row from your `housings` table,
      // so allHousingData = array of objects with
      // listing_id, title, rent_amount, square_footage, property_type, latitude, longitude, rating, image, etc.
      window.allHousingData = data;
      updateDisplay();
    } catch (e) {
      console.error("Error loading housing data", e);
    }
  }

  // Fetch the housing data after the DOM has loaded
  document.addEventListener("DOMContentLoaded", () => {
    fetchHousingData();

    // Also run other initialization code (if needed)
  });

  // ==========================================
  //          Internationalization (i18n) Functions
  // ==========================================

  /**
   * Loads the language JSON file and applies translations to the page.
   * @param {string} lang - The language code (e.g., 'en', 'fr').
   */
  async function loadLanguage(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) {
      console.warn(
        `Language '${lang}' not supported. Falling back to '${DEFAULT_LANG}'.`
      );
      lang = DEFAULT_LANG;
    }

    try {
      // Construct the path relative to the HTML file's location
      const languagesPath = "./languages/"; // Assuming languages folder is at the same level as index.html
      const response = await fetch(
        `${languagesPath}${lang}.json?v=${Date.now()}`
      ); // Add cache buster

      if (!response.ok) {
        throw new Error(
          `HTTP error! status: ${response.status}, failed to fetch ${response.url}`
        );
      }
      currentLanguageData = await response.json();
      currentLangCode = lang; // Update current language state
      applyTranslations();
      updateLanguageSwitcherState(lang); // Update button text and HTML lang attribute
      localStorage.setItem("selectedLanguage", lang); // Save preference
      console.log(`Language loaded: ${lang}`);
    } catch (error) {
      console.error(`Could not load language file for ${lang}:`, error);
      // Optionally load default language as fallback on error
      if (lang !== DEFAULT_LANG) {
        console.warn(
          `Attempting to load default language '${DEFAULT_LANG}' as fallback.`
        );
        await loadLanguage(DEFAULT_LANG);
      }
    }
  }

  /**
   * Applies the loaded translations from `currentLanguageData` to elements with data-i18n-key attributes.
   */
  function applyTranslations() {
    if (!currentLanguageData || Object.keys(currentLanguageData).length === 0) {
      console.warn("No language data loaded, cannot apply translations.");
      return;
    }

    document.querySelectorAll("[data-i18n-key]").forEach((element) => {
      const key = element.getAttribute("data-i18n-key");
      if (currentLanguageData[key] !== undefined) {
        // Check if key exists
        // Use textContent for safety unless HTML is explicitly needed
        element.textContent = currentLanguageData[key];
      } else {
        console.warn(
          `Missing translation for key: ${key} in language ${currentLangCode}`
        );
      }
    });

    // Handle placeholder translations
    document
      .querySelectorAll("[data-i18n-key-placeholder]")
      .forEach((element) => {
        const key = element.getAttribute("data-i18n-key-placeholder");
        if (currentLanguageData[key] !== undefined) {
          element.placeholder = currentLanguageData[key];
        } else {
          console.warn(
            `Missing placeholder translation for key: ${key} in language ${currentLangCode}`
          );
        }
      });

    // Handle aria-label translations
    document
      .querySelectorAll("[data-i18n-key-aria-label]")
      .forEach((element) => {
        const key = element.getAttribute("data-i18n-key-aria-label");
        if (currentLanguageData[key] !== undefined) {
          element.setAttribute("aria-label", currentLanguageData[key]);
        } else {
          console.warn(
            `Missing aria-label translation for key: ${key} in language ${currentLangCode}`
          );
        }
      });

    // Handle title attribute translations
    document.querySelectorAll("[data-i18n-key-title]").forEach((element) => {
      const key = element.getAttribute("data-i18n-key-title");
      if (currentLanguageData[key] !== undefined) {
        element.title = currentLanguageData[key];
      } else {
        console.warn(
          `Missing title translation for key: ${key} in language ${currentLangCode}`
        );
      }
    });

    // Add more attribute handlers as needed (e.g., data-i18n-key-value)
  }

  /**
   * Updates the language switcher button text and the root html lang attribute.
   * @param {string} lang - The currently selected language code.
   */
  function updateLanguageSwitcherState(lang) {
    if (currentLangSpan) {
      currentLangSpan.textContent = lang.toUpperCase();
    }
    // Update the main lang attribute for accessibility and SEO
    document.documentElement.lang = lang;
  }

  /**
   * Gets the initial language based on localStorage or browser settings.
   * @returns {string} The determined language code.
   */
  function getInitialLanguage() {
    const savedLang = localStorage.getItem("selectedLanguage");
    if (savedLang && SUPPORTED_LANGS.includes(savedLang)) {
      return savedLang;
    }
    // Optional: Detect browser language (simple version)
    const browserLang = navigator.language.split("-")[0];
    if (SUPPORTED_LANGS.includes(browserLang)) {
      // console.log(`Detected browser language: ${browserLang}`);
      return browserLang;
    }
    return DEFAULT_LANG; // Default language
  }

  // ==========================================
  //          Map Functions
  // ==========================================
  function initializeMap() {
    if (!mapElement || typeof L === "undefined") {
      console.error("Map container or Leaflet library not found.");
      return false; // Indicate failure
    }
    if (map) map.remove(); // Remove existing map instance if present

    try {
      map = L.map(mapElement).setView(MAP_INITIAL_COORDS, MAP_INITIAL_ZOOM);

      L.tileLayer(MAP_TILE_URL, {
        maxZoom: MAP_MAX_ZOOM,
        attribution: MAP_ATTRIBUTION,
      }).addTo(map);

      // Initialize marker layer (clustered or regular)
      if (typeof L.markerClusterGroup === "function") {
        markersLayer = L.markerClusterGroup();
      } else {
        console.warn(
          "Leaflet.markercluster not loaded. Using basic layer group."
        );
        markersLayer = L.layerGroup();
      }
      map.addLayer(markersLayer);
      console.log("Map initialized successfully.");
      return true; // Indicate success
    } catch (error) {
      console.error("Error initializing Leaflet map:", error);
      map = null; // Ensure map is null if init failed
      return false; // Indicate failure
    }
  }

  function renderMapMarkers(filtered) {
    if (!map || !markersLayer) return;
    markersLayer.clearLayers();

    filtered.forEach((item) => {
      if (item.latitude != null && item.longitude != null) {
        const marker = L.marker([item.latitude, item.longitude], {
          icon: customMarkerIcon,
        });
        marker.bindPopup(`
          <b>${item.title}</b><br>
          Type: ${item.property_type}<br>
          Price: $${item.rent_amount}/month<br>
          Rating: ${item.rating ?? "N/A"} ★
        `);
        markersLayer.addLayer(marker);
      }
    });
  }

  // --- Function to Handle Map Invalidation ---
  function invalidateMapSize() {
    if (map) {
      // Delay slightly to ensure the container is visible and has dimensions after CSS transition/render.
      setTimeout(() => {
        try {
          map.invalidateSize({ animate: true }); // Animate the resize
          console.log("Map size invalidated.");
        } catch (error) {
          console.error("Error during map.invalidateSize():", error);
        }
      }, MAP_INVALIDATE_DELAY);
    } else {
      console.warn(
        "Cannot invalidate size: Map object is null or not initialized."
      );
    }
  }

  // ==========================================
  //          UI Rendering Functions
  // ==========================================
  function renderHousing(housingToDisplay) {
    if (!resultsGrid) return;
    resultsGrid.innerHTML = "";

    if (housingToDisplay.length === 0) {
      // ... same no-results code ...
      return;
    }

    housingToDisplay.forEach((item) => {
      const card = document.createElement("article");
      card.className = "result-card";

      card.innerHTML = `
        <div class="card-image-placeholder">
          ${
            item.image
              ? `<img src="${item.image}" alt="${item.title}" loading="lazy">`
              : `<i class="far fa-image"></i>`
          }
        </div>
        <div class="card-content">
          <h4 class="card-title">${item.title} (${item.property_type})</h4>
          <p class="card-price">$${item.rent_amount}/month</p>
          <p class="card-size">Size: ${item.square_footage} m²</p>
          <p class="card-rating">Rating: ${item.rating ?? "N/A"} ★</p>
        </div>
      `;
      resultsGrid.appendChild(card);
    });
  }

  function updateSliderValueDisplay(slider, span, prefix = "", suffix = "") {
    if (slider && span) {
      span.textContent = `${prefix}${slider.value}${suffix}`;
    }
  }

  // ==========================================
  //          Filtering & Sorting Logic
  // ==========================================
  function filterHousing() {
    const { maxPrice, maxSize, types, searchTerm } = activeFilters;
    const term = searchTerm.toLowerCase().trim();
    if (!Array.isArray(allHousingData)) return [];

    return allHousingData.filter((item) => {
      const priceMatch = maxPrice === null || item.rent_amount <= maxPrice;
      const sizeMatch = maxSize === null || item.square_footage <= maxSize;
      const typeMatch =
        types.length === 0 || types.includes(item.property_type);
      const searchMatch = !term || item.title.toLowerCase().includes(term);
      return priceMatch && sizeMatch && typeMatch && searchMatch;
    });
  }

  function sortHousing(housingList, sortBy) {
    const sorted = [...housingList];
    switch (sortBy) {
      case "price-asc":
        sorted.sort((a, b) => a.rent_amount - b.rent_amount);
        break;
      case "price-desc":
        sorted.sort((a, b) => b.rent_amount - a.rent_amount);
        break;
      case "rating":
        sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0));
        break;
      case "new":
      default:
        // newest = highest listing_id first
        sorted.sort((a, b) => b.listing_id - a.listing_id);
        break;
    }
    return sorted;
  }
  // ==========================================
  //          Core Update Function
  // ==========================================
  function updateDisplay() {
    // Only run if the necessary elements exist (e.g., on index.html)
    if (!resultsGrid && !mapElement) {
      // If neither the grid nor the map element exists, likely not on the main page.
      return;
    }

    try {
      const filteredResults = filterHousing();
      const sortedAndFilteredResults = sortHousing(filteredResults, activeSort);

      // Update grid only if it exists
      if (resultsGrid) {
        renderHousing(sortedAndFilteredResults);
      }

      // Update map markers only if map and layer exist
      if (map && markersLayer) {
        renderMapMarkers(filteredResults); // Often better to show all filtered markers regardless of sort
      }
    } catch (error) {
      console.error("Error during updateDisplay:", error);
    }
  }

  // ==========================================
  //          Event Handlers
  // ==========================================
  // --- Theme ---
  function handleDarkModeToggle() {
    if (!themeToggleButton) return;
    const body = document.body;
    body.classList.toggle("dark-mode");
    const isDarkMode = body.classList.contains("dark-mode");
    localStorage.setItem("darkMode", isDarkMode ? "enabled" : "disabled"); // Persist preference
    const icon = themeToggleButton.querySelector("i");
    if (icon) {
      icon.className = isDarkMode ? "fas fa-sun" : "fas fa-moon";
    }
    // Invalidate map size after theme change if map exists, as tile filter changes
    if (map) {
      invalidateMapSize();
      // Optional: Force reload tiles if filter is aggressive
      // map.eachLayer(layer => { if (layer instanceof L.TileLayer) layer.redraw(); });
    }
  }

  // --- Filters ---
  function handleFilterChange() {
    // Update state from sliders
    if (priceRangeSlider)
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider)
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);

    // Update state from checkboxes
    activeFilters.types = [];
    if (typeCheckboxes) {
      typeCheckboxes.forEach((checkbox) => {
        if (checkbox.checked) {
          activeFilters.types.push(checkbox.value);
        }
      });
    }

    // Update display values
    updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");

    updateDisplay();
  }

  // --- Sorting ---
  function handleSortChange(event) {
    const button = event.target.closest(".sort-btn"); // Use closest for clicks on icon
    if (button && button.dataset.sort && button.dataset.sort !== activeSort) {
      activeSort = button.dataset.sort;
      if (sortButtons) {
        sortButtons.forEach((btn) => {
          btn.classList.toggle("active", btn === button); // Toggle based on the clicked button instance
        });
      }
      updateDisplay();
    }
  }

  // --- Search ---
  function handleSearchInput() {
    if (!searchInput) return;
    activeFilters.searchTerm = searchInput.value;
    updateDisplay();
  }

  // --- Clear Filters ---
  function clearAllFilters() {
    // Reset sliders and update state/display
    if (priceRangeSlider) {
      const maxPrice = priceRangeSlider.max;
      priceRangeSlider.value = maxPrice;
      activeFilters.maxPrice = parseInt(maxPrice, 10);
      updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    }
    if (sizeRangeSlider) {
      const maxSize = sizeRangeSlider.max;
      sizeRangeSlider.value = maxSize;
      activeFilters.maxSize = parseInt(maxSize, 10);
      updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");
    }

    // Reset checkboxes and state
    if (typeCheckboxes) {
      typeCheckboxes.forEach((checkbox) => (checkbox.checked = false));
    }
    activeFilters.types = [];

    // Reset search and state
    if (searchInput) searchInput.value = "";
    activeFilters.searchTerm = "";

    // Reset sorting and button states
    activeSort = "new";
    if (sortButtons) {
      sortButtons.forEach((button) => {
        button.classList.toggle("active", button.dataset.sort === "new");
      });
    }

    updateDisplay();
    console.log("Filters, Sort, Search Cleared");
  }

  // --- Map Toggle --- (If button exists)
  function handleMapToggle() {
    if (!resultsLayout || !mapToggleButton) {
      console.error("Map layout or toggle button not found for toggle action.");
      return;
    }

    const isHidden = resultsLayout.classList.toggle("map-hidden");
    const icon = mapToggleButton.querySelector("i");
    const text = mapToggleButton.querySelector("span"); // Assuming span holds text

    mapToggleButton.setAttribute("aria-expanded", isHidden ? "false" : "true");
    if (icon)
      icon.className = isHidden ? "fas fa-map-location-dot" : "fas fa-compress"; // Example icons
    if (text) text.textContent = isHidden ? "Show Map" : "Hide Map"; // Example text

    // Invalidate map size ONLY when it becomes visible
    if (!isHidden) {
      invalidateMapSize();
    }
  }

  // --- Map Resize ---
  function handleMapResizeStart(event) {
    // Check if the target is the handle or inside it, and if map elements exist
    if (
      !mapResizeHandle ||
      !mapContainer ||
      !resultsLayout ||
      !mapResizeHandle.contains(event.target)
    )
      return;

    // Prevent text selection during drag, only if starting on handle
    event.preventDefault();

    isResizingMap = true;
    mapResizeStartX = event.touches ? event.touches[0].clientX : event.clientX;
    mapResizeInitialWidth = mapContainer.offsetWidth;

    document.body.classList.add("map-resizing"); // Add class for cursor/user-select

    document.addEventListener("mousemove", handleMapResizeMove);
    document.addEventListener("mouseup", handleMapResizeEnd);
    document.addEventListener("touchmove", handleMapResizeMove, {
      passive: false,
    }); // passive:false to allow preventDefault
    document.addEventListener("touchend", handleMapResizeEnd);
    console.log("Map resize started");
  }

  function handleMapResizeMove(event) {
    if (!isResizingMap || !mapContainer) return;

    // Optional: Disable map dragging during resize for smoother experience
    if (map && map.dragging.enabled()) map.dragging.disable();

    // Handle touch and mouse events
    const currentX = event.touches ? event.touches[0].clientX : event.clientX;
    const dx = currentX - mapResizeStartX;

    let newWidth = mapResizeInitialWidth - dx; // Subtract dx because handle is on right, moving right decreases map width

    // Apply constraints
    const gridContainer = document.querySelector(".results-grid-container");
    const availableWidth =
      resultsLayout.offsetWidth - mapResizeHandle.offsetWidth; // Total space minus handle width
    const minGridAllowedWidth = gridContainer ? MIN_GRID_WIDTH : 0; // Use defined constant

    newWidth = Math.max(MIN_MAP_WIDTH, newWidth); // Min map width
    newWidth = Math.min(availableWidth - minGridAllowedWidth, newWidth); // Max map width (leave space for grid)

    // Update map container's flex-basis for dynamic resizing
    mapContainer.style.flex = `0 0 ${newWidth}px`;

    // Optional: Update grid container's flex properties if needed (usually flex: 1 1 auto works)
    // if (gridContainer) gridContainer.style.flex = '1 1 auto';

    // Prevent scrolling on touch devices during drag
    if (event.touches) {
      event.preventDefault();
    }
  }

  function handleMapResizeEnd() {
    if (!isResizingMap) return;

    isResizingMap = false;
    document.body.classList.remove("map-resizing"); // Remove cursor override class

    // Re-enable map dragging if it was disabled
    if (map && !map.dragging.enabled()) map.dragging.enable();

    // Remove document-level listeners
    document.removeEventListener("mousemove", handleMapResizeMove);
    document.removeEventListener("mouseup", handleMapResizeEnd);
    document.removeEventListener("touchmove", handleMapResizeMove);
    document.removeEventListener("touchend", handleMapResizeEnd);

    // IMPORTANT: Invalidate map size after resizing is finished
    invalidateMapSize();
    console.log("Map resize finished.");
  }

  // --- Language Switcher Click Handler ---
  function handleLanguageChange(event) {
    event.preventDefault(); // Prevent page jump from href="#"
    const selectedLang = event.target.getAttribute("data-lang");

    if (selectedLang && selectedLang !== currentLangCode) {
      console.log(`Attempting to load language: ${selectedLang}`);
      loadLanguage(selectedLang); // Load and apply the new language

      // Close dropdowns after selection
      if (languageOptions) languageOptions.classList.remove("show");
      if (languageToggle) languageToggle.setAttribute("aria-expanded", "false");

      // Close mobile nav if open
      if (hamburgerButton && hamburgerButton.classList.contains("active")) {
        hamburgerButton.click(); // Simulate click to close
      }
    } else if (selectedLang) {
      // Language already selected, just close dropdowns
      if (languageOptions) languageOptions.classList.remove("show");
      if (languageToggle) languageToggle.setAttribute("aria-expanded", "false");
      if (hamburgerButton && hamburgerButton.classList.contains("active")) {
        hamburgerButton.click();
      }
    }
  }

  // ==========================================
  //          Event Listener Setup
  // ==========================================
  function setupEventListeners() {
    console.log("Setting up event listeners...");

    // --- Hamburger Menu ---
    if (hamburgerButton && mainNav) {
      hamburgerButton.setAttribute("aria-expanded", "false");
      mainNav.setAttribute("aria-hidden", "true");

      hamburgerButton.addEventListener("click", () => {
        const isActive = hamburgerButton.classList.toggle("active");
        mainNav.classList.toggle("active"); // Use .active class
        hamburgerButton.setAttribute("aria-expanded", isActive);
        mainNav.setAttribute("aria-hidden", !isActive);
        document.body.classList.toggle("nav-open", isActive); // Optional: for body scroll lock
      });

      // Close menu if a nav link/button (except language toggle) is clicked
      mainNav
        .querySelectorAll("a, button:not(#language-toggle)")
        .forEach((item) => {
          item.addEventListener("click", () => {
            if (hamburgerButton.classList.contains("active")) {
              hamburgerButton.click(); // Simulate click to close
            }
          });
        });

      // Close menu if clicking outside the nav (when it's open)
      document.addEventListener("click", (event) => {
        if (
          mainNav.classList.contains("active") &&
          !mainNav.contains(event.target) &&
          !hamburgerButton.contains(event.target)
        ) {
          hamburgerButton.click(); // Simulate click to close
        }
      });
    } else {
      console.warn("Hamburger button or main navigation element not found.");
    }

    // --- Language Switcher ---
    if (languageToggle && languageOptions) {
      languageToggle.addEventListener("click", (event) => {
        event.stopPropagation();
        const isExpanded =
          languageToggle.getAttribute("aria-expanded") === "true";
        languageOptions.classList.toggle("show");
        languageToggle.setAttribute("aria-expanded", !isExpanded);
      });

      // Use event delegation on the UL for language option clicks
      languageOptions.addEventListener("click", (event) => {
        if (
          event.target.tagName === "A" &&
          event.target.hasAttribute("data-lang")
        ) {
          handleLanguageChange(event);
        }
      });

      // Close dropdown if clicking outside
      document.addEventListener("click", (event) => {
        if (
          languageOptions.classList.contains("show") &&
          !languageToggle.contains(event.target) &&
          !languageOptions.contains(event.target)
        ) {
          languageOptions.classList.remove("show");
          languageToggle.setAttribute("aria-expanded", "false");
        }
      });
    } else {
      console.warn("Language toggle button or options list not found.");
    }

    // --- Theme Toggle ---
    if (themeToggleButton) {
      themeToggleButton.addEventListener("click", handleDarkModeToggle);
    } else {
      console.warn("Theme toggle button not found.");
    }

    // --- Filters ---
    if (filtersContainer) {
      filtersContainer.addEventListener("input", (event) => {
        if (event.target.matches("#price-range, #size-range")) {
          handleFilterChange();
        }
      });
      filtersContainer.addEventListener("change", (event) => {
        if (event.target.matches(".filter-type")) {
          handleFilterChange();
        }
      });
    } else if (document.querySelector(".filters-sidebar")) {
      console.warn(
        "Filters sidebar found, but container ID 'filters-container' not found."
      );
    }
    if (clearFiltersButton) {
      clearFiltersButton.addEventListener("click", clearAllFilters);
    } else if (document.getElementById("clear-filters-btn")) {
      console.warn("Clear filters button not found.");
    }

    // --- Sorting ---
    if (sortButtonsContainer) {
      sortButtonsContainer.addEventListener("click", handleSortChange);
    } else if (document.querySelector(".sort-options")) {
      console.warn("Sort buttons container element not found.");
    }

    // --- Search ---
    if (searchInput) {
      searchInput.addEventListener("input", handleSearchInput);
    } else if (document.getElementById("search-input")) {
      console.warn("Search input element not found.");
    }

    // --- Map Toggle Button --- (Keep if you add the button)
    if (mapToggleButton) {
      mapToggleButton.addEventListener("click", handleMapToggle);
    }

    // --- Map Resize Handle ---
    if (mapResizeHandle) {
      mapResizeHandle.addEventListener("mousedown", handleMapResizeStart);
      mapResizeHandle.addEventListener("touchstart", handleMapResizeStart, {
        passive: false,
      });
    } else if (mapElement && resultsLayout) {
      console.warn("Map resize handle element (#map-resize-handle) not found.");
    }

    console.log("Event listeners setup complete.");
  }

  // --- Apply Persisted Dark Mode ---
  function applyPersistedTheme() {
    const persistedDarkMode = localStorage.getItem("darkMode");
    const body = document.body;
    const needsToggle =
      (persistedDarkMode === "enabled" &&
        !body.classList.contains("dark-mode")) ||
      (persistedDarkMode !== "enabled" && body.classList.contains("dark-mode"));

    if (needsToggle) {
      body.classList.toggle("dark-mode");
    }

    // Always update the icon based on the final state
    const isDarkMode = body.classList.contains("dark-mode");
    const icon = themeToggleButton
      ? themeToggleButton.querySelector("i")
      : null;
    if (icon) {
      icon.className = isDarkMode ? "fas fa-sun" : "fas fa-moon";
    }
  }

  // ==========================================
  //          Initialization on DOM Load
  // ==========================================
  async function initialize() {
    // Make initialize async to await language load
    console.log("Initializing CROUS-X Script...");

    applyPersistedTheme(); // Apply theme first

    // Determine and load initial language
    const initialLang = getInitialLanguage();
    await loadLanguage(initialLang); // Wait for initial language to load before proceeding

    // Set initial filter state based on default HTML values
    if (priceRangeSlider)
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider)
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);
    updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");

    // Initialize the map
    let mapInitialized = false;
    if (mapElement && typeof L !== "undefined") {
      console.log("Initializing Map...");
      mapInitialized = initializeMap(); // Check if successful
    } else if (mapElement) {
      console.error("Map element found, but Leaflet (L) is not defined.");
    }

    // Attach all event listeners AFTER initial setup like language/theme
    setupEventListeners();

    // Initial display render (grid/map) AFTER setup and language load
    if (resultsGrid || (mapElement && mapInitialized)) {
      console.log("Performing initial display update...");
      updateDisplay(); // Uses current filters/sort state
    } else if (document.querySelector(".results-area")) {
      // Only warn if the results area exists but grid/map aren't ready
      console.warn(
        "Results grid/map not ready. Initial display update skipped."
      );
    }

    console.log("CROUS-X Script Initialized Successfully.");
  }

  // Run initialization when the DOM is fully loaded
  // Use 'interactive' to potentially run slightly earlier than 'complete'
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize);
  } else {
    // DOMContentLoaded has already fired
    initialize();
  }

  //#########################################
  // DETAILED HOUSING CARD RENDERING FUNCTION
  //#########################################

  function renderHousing(housingToDisplay) {
    if (!resultsGrid) return;
    resultsGrid.innerHTML = ""; // Clear previous results

    if (housingToDisplay.length === 0) {
      // Create a paragraph for the "no results" message
      const noResultsMessage = document.createElement("p");
      noResultsMessage.setAttribute("data-i18n-key", "no_results"); // For translation
      noResultsMessage.textContent = "No results found matching your criteria."; // Default text
      resultsGrid.appendChild(noResultsMessage);

      // Attempt to apply translation if the function is available
      if (typeof applyTranslations === "function") {
        applyTranslations(); // This will translate the newly added element
      }
      return;
    }

    housingToDisplay.forEach((item) => {
      // 1. Create the anchor tag
      const link = document.createElement("a");
      link.href = `./src/housing-detail.php?id=${item.listing_id}`; // Use the unique ID
      link.className = "result-card-link"; // For styling the link wrapper

      // 2. Create the card article (as you were doing)
      const card = document.createElement("article");
      card.className = "result-card";
      // You don't strictly need data-id on the card if the link handles navigation,
      // but it can be useful for other JS interactions if needed.
      // card.dataset.id = item.listing_id;

      card.innerHTML = `
      <div class="card-image-placeholder">
        ${
          item.image
            ? `<img src="${item.image}" alt="${item.title}" loading="lazy">`
            : `<i class="far fa-image"></i>`
        }
      </div>
      <div class="card-content">
        <h4 class="card-title">${item.title} (${item.property_type})</h4>
        <p class="card-price">$${item.rent_amount}/month</p>
        <p class="card-size">Size: ${item.square_footage} m²</p>
        <p class="card-rating">Rating: ${item.rating ?? "N/A"} ★</p>
      </div>
    `;

      // 3. Append the card to the link, and the link to the grid
      link.appendChild(card);
      resultsGrid.appendChild(link);
    });
  }

  // ... (rest of your script.js) ...

  // Make sure your "no_results" key is in your language JSON files:
  // e.g., in en.json:
  // {
  //   ...
  //   "no_results": "No results found matching your criteria.",
  //   ...
  // }
  // e.g., in fr.json:
  // {
  //   ...
  //   "no_results": "Aucun résultat ne correspond à vos critères.",
  //   ...
  // }
})(); // End IIFE
