// ==========================================
//          CROUS-X Script
// ==========================================
// Includes: Dark Mode, Filtering, Sorting, Map (Toggleable, Resizable),
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
  const MAP_INVALIDATE_DELAY = 100; // ms delay before invalidating map size (increased slightly)
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
  const resultsLayout = document.getElementById("results-layout"); // *** Use ID for layout ***
  const mapContainer = document.querySelector(".map-container"); // Map container for resize
  const mapResizeHandle = document.getElementById("map-resize-handle");
  const mapToggleButton = document.getElementById("map-toggle-button"); // *** ADDED ***

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
  let allHousingData = []; // Initialize as empty array

  function fetchHousingData() {
    fetch("/api/getHousing.php") // Assuming your API endpoint
      .then((response) => {
        if (!response.ok) {
          // Check if response is JSON before parsing
          const contentType = response.headers.get("content-type");
          if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json().then((errorData) => {
              throw new Error(
                `Network response was not OK: ${
                  response.statusText
                }. Server response: ${JSON.stringify(errorData)}`
              );
            });
          } else {
            return response.text().then((textData) => {
              throw new Error(
                `Network response was not OK: ${response.statusText}. Server response (non-JSON): ${textData}`
              );
            });
          }
        }
        return response.json();
      })
      .then((data) => {
        // Add basic validation if possible
        if (!Array.isArray(data)) {
          console.warn("Received non-array data from API:", data);
          // Handle this case - maybe set to empty array or show user error
          window.allHousingData = [];
        } else {
          // Set the global housing data variable to the fetched data
          window.allHousingData = data;
          console.log(
            "Housing data fetched successfully:",
            window.allHousingData.length,
            "items"
          );
          // Call your display function to update the page
          updateDisplay();
        }
      })
      .catch((error) => {
        console.error("Error fetching or processing housing data:", error);
        window.allHousingData = []; // Ensure it's an empty array on error
        updateDisplay(); // Update display to show "no results" or error message
        // Optionally: Display a user-friendly error message on the page
        if (resultsGrid) {
          resultsGrid.innerHTML = `<p style="grid-column: 1 / -1; text-align: center; padding: 20px; color: red;">Could not load housing data. Please try again later.</p>`;
        }
      });
  }

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

  function renderMapMarkers(housingToDisplay) {
    // Guard clauses
    if (!map || !markersLayer || !customMarkerIcon) {
      if (!map)
        console.warn("renderMapMarkers skipped: Map object not available.");
      if (!markersLayer)
        console.warn("renderMapMarkers skipped: Markers layer not available.");
      if (!customMarkerIcon)
        console.warn(
          "renderMapMarkers skipped: Custom icon not defined (Leaflet likely not loaded fully)."
        );
      return;
    }

    markersLayer.clearLayers();

    housingToDisplay.forEach((item) => {
      if (item.lat != null && item.lng != null) {
        // Check for valid coordinates
        try {
          const marker = L.marker([item.lat, item.lng], {
            icon: customMarkerIcon,
          });
          // Basic popup content - does not use i18n keys from JSON
          // You could fetch translated strings here if needed:
          // const typeLabel = currentLanguageData['popup_type_label'] || 'Type';
          const popupContent = `
                        <b>${item.name}</b><br>
                        Type: ${item.type}<br>
                        Price: $${item.price}/month<br>
                        Rating: ${item.rating || "N/A"} ★
                    `;
          marker.bindPopup(popupContent);
          markersLayer.addLayer(marker);
        } catch (error) {
          console.error(`Error creating marker for item ${item.id}:`, error);
        }
      } else {
        // Optional: Log items missing coordinates
        // console.warn(`Item ${item.id} (${item.name}) missing valid coordinates.`);
      }
    });
    console.log(`Rendered ${markersLayer.getLayers().length} markers on map.`);
  }

  // --- Function to Handle Map Invalidation ---
  function invalidateMapSize() {
    if (map) {
      // Delay slightly to ensure the container is visible and has dimensions after CSS transition/render.
      setTimeout(() => {
        try {
          // Check if the map container is actually visible before invalidating
          const mapContainerElement = map.getContainer();
          if (mapContainerElement.offsetParent !== null) {
            // Check if visible
            map.invalidateSize({ animate: true }); // Animate the resize
            console.log("Map size invalidated.");
          } else {
            console.log(
              "Map size invalidation skipped: Map container not visible."
            );
          }
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
    if (!resultsGrid) {
      // Expected if not on the main page, don't treat as error
      return;
    }

    resultsGrid.innerHTML = ""; // Clear previous listings

    if (housingToDisplay.length === 0) {
      // Create a paragraph for the no results message
      const noResultsMessage = document.createElement("p");
      noResultsMessage.style.gridColumn = "1 / -1"; // Span across all grid columns
      noResultsMessage.style.textAlign = "center";
      noResultsMessage.style.padding = "20px";

      // Check if data fetch failed or if it's just no results from filters
      if (
        !window.allHousingData ||
        (window.allHousingData.length === 0 &&
          activeFilters.searchTerm === "" &&
          activeFilters.types.length === 0)
      ) {
        // Likely API fetch failed or returned empty initially
        noResultsMessage.setAttribute("data-i18n-key", "error_loading_data");
        noResultsMessage.textContent = "Could not load housing data."; // Default error text
        noResultsMessage.style.color = "red";
        if (currentLanguageData?.error_loading_data) {
          noResultsMessage.textContent = currentLanguageData.error_loading_data;
        }
      } else {
        // Filters resulted in no matches
        noResultsMessage.setAttribute("data-i18n-key", "no_results_found");
        noResultsMessage.textContent =
          "No housing found matching your criteria."; // Default text
        if (currentLanguageData?.no_results_found) {
          noResultsMessage.textContent = currentLanguageData.no_results_found;
        }
      }
      resultsGrid.appendChild(noResultsMessage);
      return;
    }

    housingToDisplay.forEach((item) => {
      const card = document.createElement("article");
      card.className = "result-card";
      // NOTE: Content here uses item properties directly.
      // If 'item.type' etc. needed translation based on current language,
      // you would look it up in `currentLanguageData` here.
      // Example: const translatedType = currentLanguageData[`housing_type_${item.type.toLowerCase().replace(' ','_')}`] || item.type;
      card.innerHTML = `
                <div class="card-image-placeholder">
                    ${
                      item.image
                        ? `<img src="${item.image}" alt="${
                            item.name || "Housing image"
                          }" loading="lazy">` // Add loading="lazy"
                        : '<i class="far fa-image"></i>'
                    }
                </div>
                <div class="card-content">
                    <h4 class="card-title">${item.name} (${item.type})</h4>
                    <p class="card-price">$${item.price}/month</p>
                    <p class="card-size">Size: ${item.size} m²</p>
                    <p class="card-rating">Rating: ${item.rating || "N/A"} ★</p>
                </div>
            `;
      resultsGrid.appendChild(card);
    });
    console.log(`Rendered ${housingToDisplay.length} housing cards.`);
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
    const lowerCaseSearchTerm = searchTerm.toLowerCase().trim();

    // Ensure data exists before filtering
    if (!Array.isArray(window.allHousingData)) {
      // Use window.allHousingData
      console.error("Housing data is not available or not an array.");
      return [];
    }

    return window.allHousingData.filter((item) => {
      // Use window.allHousingData
      const priceMatch = maxPrice === null || item.price <= maxPrice;
      const sizeMatch = maxSize === null || item.size <= maxSize;
      const typeMatch = types.length === 0 || types.includes(item.type);
      const searchMatch =
        !lowerCaseSearchTerm ||
        (item.name && item.name.toLowerCase().includes(lowerCaseSearchTerm)); // Check if item.name exists
      return priceMatch && sizeMatch && typeMatch && searchMatch;
    });
  }

  function sortHousing(housingList, sortBy) {
    const sortedList = [...housingList]; // Create a copy to sort
    switch (sortBy) {
      case "price-asc":
        sortedList.sort((a, b) => a.price - b.price);
        break;
      case "price-desc":
        sortedList.sort((a, b) => b.price - a.price);
        break;
      case "rating":
        // Sort by rating descending, put items without rating at the end
        sortedList.sort((a, b) => (b.rating ?? -1) - (a.rating ?? -1));
        break;
      case "new":
      default:
        // Assuming higher ID is newer
        sortedList.sort((a, b) => (b.id ?? 0) - (a.id ?? 0));
        break;
    }
    return sortedList;
  }

  // ==========================================
  //          Core Update Function
  // ==========================================
  function updateDisplay() {
    // Only run if the necessary elements exist (e.g., on index.html)
    if (!resultsGrid && !mapElement) {
      // If neither the grid nor the map element exists, likely not on the main page.
      console.log(
        "updateDisplay skipped: Neither results grid nor map element found."
      );
      return;
    }
    console.log(
      "Updating display with filters:",
      activeFilters,
      "and sort:",
      activeSort
    );

    try {
      const filteredResults = filterHousing();
      const sortedAndFilteredResults = sortHousing(filteredResults, activeSort);

      // Update grid only if it exists
      if (resultsGrid) {
        renderHousing(sortedAndFilteredResults);
      }

      // Update map markers only if map and layer exist and map isn't hidden
      if (
        map &&
        markersLayer &&
        resultsLayout &&
        !resultsLayout.classList.contains("map-hidden")
      ) {
        renderMapMarkers(filteredResults); // Show all filtered markers regardless of sort
      } else if (
        map &&
        resultsLayout &&
        resultsLayout.classList.contains("map-hidden")
      ) {
        console.log("Map marker rendering skipped: Map is hidden.");
      }
    } catch (error) {
      console.error("Error during updateDisplay:", error);
      if (resultsGrid) {
        resultsGrid.innerHTML = `<p style="grid-column: 1 / -1; text-align: center; padding: 20px; color: orange;">An error occurred while updating results.</p>`;
      }
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
    if (
      map &&
      resultsLayout &&
      !resultsLayout.classList.contains("map-hidden")
    ) {
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

  // --- Map Toggle --- *** ADDED ***
  function handleMapToggle() {
    if (!resultsLayout || !mapToggleButton) {
      console.error("Map layout or toggle button not found for toggle action.");
      return;
    }

    const isHidden = resultsLayout.classList.toggle("map-hidden");
    const isPressed = !isHidden; // Map is visible when NOT hidden

    const icon = mapToggleButton.querySelector("i");
    const textSpan = mapToggleButton.querySelector("span");

    mapToggleButton.setAttribute("aria-pressed", isPressed); // Use aria-pressed for toggle state

    // Update Icon and Text based on visibility
    if (icon) {
      icon.className = isPressed ? "fas fa-compress" : "fas fa-expand"; // Compress when visible, Expand when hidden
    }
    if (textSpan) {
      const textKey = isPressed ? "toggle_map_hide" : "toggle_map_show";
      const defaultText = isPressed ? "Hide Map" : "Show Map";
      textSpan.setAttribute("data-i18n-key", textKey); // Update key for translation
      textSpan.textContent = currentLanguageData[textKey] || defaultText; // Update text immediately
    }
    // Update Title Attribute
    const titleKey = isPressed
      ? "toggle_map_hide_title"
      : "toggle_map_show_title";
    const defaultTitle = isPressed ? "Hide the map view" : "Show the map view";
    mapToggleButton.setAttribute("data-i18n-key-title", titleKey);
    mapToggleButton.title = currentLanguageData[titleKey] || defaultTitle;

    // Invalidate map size ONLY when it becomes visible
    if (isPressed) {
      // If map is now visible (isHidden is false)
      console.log("Map toggled to visible, invalidating size...");
      invalidateMapSize();
      // Re-render markers if they weren't rendered while hidden
      if (map && markersLayer) {
        renderMapMarkers(filterHousing());
      }
    } else {
      console.log("Map toggled to hidden.");
      // Optional: Clear markers when hidden if performance is an issue
      // if (markersLayer) markersLayer.clearLayers();
    }
  }

  // --- Map Resize ---
  function handleMapResizeStart(event) {
    // Check if the target is the handle or inside it, and if map elements exist
    if (
      !mapResizeHandle ||
      !mapContainer ||
      !resultsLayout ||
      !mapResizeHandle.contains(event.target) ||
      resultsLayout.classList.contains("map-hidden") // Don't resize if map is hidden
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
    // Ensure resultsLayout is defined and get its offsetWidth
    const availableWidth = resultsLayout
      ? resultsLayout.offsetWidth - mapResizeHandle.offsetWidth
      : window.innerWidth; // Fallback to window width
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
    const anchor = event.target.closest("a[data-lang]"); // Find the anchor tag clicked
    if (!anchor) return; // Exit if click wasn't on a language link

    const selectedLang = anchor.getAttribute("data-lang");

    if (selectedLang && selectedLang !== currentLangCode) {
      console.log(`Attempting to load language: ${selectedLang}`);
      loadLanguage(selectedLang).then(() => {
        // Re-apply translations to dynamically added text, like map toggle button
        if (mapToggleButton && resultsLayout) {
          const isPressed =
            mapToggleButton.getAttribute("aria-pressed") === "true";
          const textSpan = mapToggleButton.querySelector("span");
          const textKey = isPressed ? "toggle_map_hide" : "toggle_map_show";
          const defaultText = isPressed ? "Hide Map" : "Show Map";
          if (textSpan) {
            textSpan.textContent = currentLanguageData[textKey] || defaultText;
          }
          const titleKey = isPressed
            ? "toggle_map_hide_title"
            : "toggle_map_show_title";
          const defaultTitle = isPressed
            ? "Hide the map view"
            : "Show the map view";
          mapToggleButton.title = currentLanguageData[titleKey] || defaultTitle;
        }
        // Also potentially re-render map popups if their content uses translations
        if (
          map &&
          markersLayer &&
          resultsLayout &&
          !resultsLayout.classList.contains("map-hidden")
        ) {
          renderMapMarkers(filterHousing()); // Re-render markers with new language potentially affecting popups
        }
      }); // Load and apply the new language

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
      // mainNav.setAttribute("aria-hidden", "true"); // Managed by .active class now

      hamburgerButton.addEventListener("click", () => {
        const isActive = hamburgerButton.classList.toggle("active");
        mainNav.classList.toggle("active"); // Use .active class
        hamburgerButton.setAttribute("aria-expanded", isActive);
        // mainNav.setAttribute("aria-hidden", !isActive); // Optional, CSS handles visibility
        document.body.classList.toggle("nav-open", isActive); // Optional: for body scroll lock
      });

      // Close menu if a nav link/button (except language toggle) is clicked
      mainNav
        .querySelectorAll("a, button:not(#language-toggle)")
        .forEach((item) => {
          item.addEventListener("click", (event) => {
            // Make sure not to close if it's just opening language dropdown
            const langToggleClicked = event.target.closest("#language-toggle");
            if (
              hamburgerButton.classList.contains("active") &&
              !langToggleClicked
            ) {
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
        event.stopPropagation(); // Prevent document listener from closing it immediately
        const isExpanded =
          languageToggle.getAttribute("aria-expanded") === "true";
        languageOptions.classList.toggle("show");
        languageToggle.setAttribute("aria-expanded", !isExpanded);
      });

      // Use event delegation on the UL for language option clicks
      languageOptions.addEventListener("click", handleLanguageChange);

      // Close dropdown if clicking outside
      document.addEventListener("click", (event) => {
        if (
          languageOptions.classList.contains("show") &&
          !languageToggle.contains(event.target) && // Click wasn't the toggle itself
          !languageOptions.contains(event.target) // Click wasn't inside the dropdown
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
      // Use 'input' for immediate feedback, 'change' for after blur
      searchInput.addEventListener("input", handleSearchInput);
      // Also handle 'search' event (when user clears via 'x' button)
      searchInput.addEventListener("search", handleSearchInput);
    } else if (document.getElementById("search-input")) {
      console.warn("Search input element not found.");
    }

    // --- Map Toggle Button --- *** ADDED ***
    if (mapToggleButton) {
      mapToggleButton.addEventListener("click", handleMapToggle);
    } else if (document.getElementById("map")) {
      // Only warn if map exists but button doesn't
      console.warn("Map Toggle Button (#map-toggle-button) not found.");
    }

    // --- Map Resize Handle ---
    if (mapResizeHandle) {
      mapResizeHandle.addEventListener("mousedown", handleMapResizeStart);
      mapResizeHandle.addEventListener("touchstart", handleMapResizeStart, {
        passive: false,
      });
    } else if (mapElement && resultsLayout) {
      // Don't warn if map doesn't exist anyway
      console.log(
        "Map resize handle element (#map-resize-handle) not found (resize disabled)."
      );
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

    // Fetch initial housing data AFTER map/DOM might be ready
    if (resultsGrid || (mapElement && mapInitialized)) {
      fetchHousingData(); // This will call updateDisplay internally on success/error
    } else {
      console.log("Initial data fetch skipped: Results grid/map not ready.");
    }

    // NO longer call updateDisplay here, fetchHousingData will do it.
    // if (resultsGrid || (mapElement && mapInitialized)) {
    //   console.log("Performing initial display update...");
    //   updateDisplay(); // Uses current filters/sort state
    // } else if (document.querySelector(".results-area")) {
    //   // Only warn if the results area exists but grid/map aren't ready
    //   console.warn(
    //     "Results grid/map not ready. Initial display update skipped."
    //   );
    // }

    console.log("CROUS-X Script Initialized Successfully.");
  }

  // Run initialization when the DOM is fully loaded
  // Use 'interactive' to potentially run slightly earlier than 'complete'
  if (
    document.readyState === "complete" ||
    (document.readyState !== "loading" && !document.documentElement.doScroll)
  ) {
    initialize();
  } else {
    document.addEventListener("DOMContentLoaded", initialize);
  }
})(); // End IIFE
