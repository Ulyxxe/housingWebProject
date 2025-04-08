// ==========================================
//          CROUS-X Script
// ==========================================
// Includes: Dark Mode, Filtering, Sorting,
// Leaflet Map with Clustering & Custom Icons,
// Toggleable Map View, Resizable Map.
// ==========================================

(function () {
  // IIFE to encapsulate scope

  // --- Configuration & Constants ---
  const MAP_INITIAL_COORDS = [48.8566, 2.3522]; // Paris center
  const MAP_INITIAL_ZOOM = 12;
  const MAP_MAX_ZOOM = 19;
  const MAP_TILE_URL = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
  const MAP_ATTRIBUTION = "© OpenStreetMap contributors";
  const MAP_INVALIDATE_DELAY = 50; // ms delay before invalidating map size
  const MIN_MAP_WIDTH = 200; // Minimum dimensions for resize
  const MIN_MAP_HEIGHT = 150;

  // Define custom map marker icon once
  const customMarkerIcon =
    typeof L !== "undefined"
      ? L.divIcon({
          className: "custom-div-icon",
          html: "",
          iconSize: [24, 24],
          iconAnchor: [12, 12],
          popupAnchor: [0, -12],
        })
      : null; // Define only if Leaflet is loaded

  // --- DOM Element Selection ---
  const themeToggleButton = document.getElementById("theme-toggle");
  const resultsGrid = document.getElementById("results-grid");
  const priceRangeSlider = document.getElementById("price-range");
  const sizeRangeSlider = document.getElementById("size-range");
  const priceRangeValueSpan = document.getElementById("price-range-value");
  const sizeRangeValueSpan = document.getElementById("size-range-value");
  const typeCheckboxes = document.querySelectorAll(".filter-type");
  const clearFiltersButton = document.getElementById("clear-filters-btn");
  const filtersContainer = document.getElementById("filters-container");
  const sortButtonsContainer = document.querySelector(".sort-options");
  const sortButtons = document.querySelectorAll(".sort-btn");
  const searchInput = document.getElementById("search-input");
  const mapElement = document.getElementById("map");
  const mapToggleButton = document.getElementById("map-toggle-button");
  const resultsLayout = document.getElementById("results-layout");
  const mapResizeHandle = document.getElementById("map-resize-handle"); // <-- Added for resize

  // --- State Management ---
  let activeFilters = {
    maxPrice: 10000, // Default max
    maxSize: 250, // Default max
    types: [],
    searchTerm: "",
  };
  let activeSort = "new"; // Default sort
  let map = null;
  let markersLayer = null;
  let isResizingMap = false; // <-- Added state for resizing
  let mapResizeStartX,
    mapResizeStartY,
    mapResizeInitialWidth,
    mapResizeInitialHeight; // <-- Added state

  // --- Sample Data ---
  const allHousingData = [
    {
      id: 10,
      name: "Campus Tower Studio",
      price: 850,
      size: 25,
      type: "Studio",
      image: null,
      rating: 4.2,
      lat: 48.858,
      lng: 2.294,
    },
    {
      id: 9,
      name: "Downtown Apartment",
      price: 1200,
      size: 60,
      type: "Apartment",
      image: null,
      rating: 4.5,
      lat: 48.86,
      lng: 2.337,
    },
    {
      id: 8,
      name: "Riverside Shared Room",
      price: 600,
      size: 18,
      type: "Shared Room",
      image: null,
      rating: 3.8,
      lat: 48.853,
      lng: 2.349,
    },
    {
      id: 7,
      name: "West End House",
      price: 2500,
      size: 150,
      type: "House",
      image: null,
      rating: 4.9,
      lat: 48.869,
      lng: 2.307,
    },
    {
      id: 6,
      name: "Compact Studio Near Uni",
      price: 780,
      size: 22,
      type: "Studio",
      image: null,
      rating: 4.0,
      lat: 48.846,
      lng: 2.344,
    },
    {
      id: 5,
      name: "Spacious 2BR Apartment",
      price: 1600,
      size: 85,
      type: "Apartment",
      image: null,
      rating: 4.7,
      lat: 48.873,
      lng: 2.359,
    },
    {
      id: 4,
      name: "Budget Share House",
      price: 550,
      size: 15,
      type: "Shared Room",
      image: null,
      rating: 3.5,
      lat: 48.886,
      lng: 2.343,
    },
    {
      id: 3,
      name: "Modern Apartment",
      price: 1350,
      size: 70,
      type: "Apartment",
      image: null,
      rating: 4.6,
      lat: 48.845,
      lng: 2.372,
    },
    {
      id: 2,
      name: "Large Family House",
      price: 3200,
      size: 200,
      type: "House",
      image: null,
      rating: 4.8,
      lat: 48.838,
      lng: 2.27,
    },
    {
      id: 1,
      name: "City Center Studio",
      price: 950,
      size: 30,
      type: "Studio",
      image: null,
      rating: 4.3,
      lat: 48.865,
      lng: 2.32,
    },
    {
      id: 11,
      name: "Quiet House Room",
      price: 700,
      size: 20,
      type: "Shared Room",
      image: null,
      rating: 3.9,
      lat: 48.82,
      lng: 2.355,
    },
    // Fake KFC locations
    {
      id: 12,
      name: "Colonel's Loft",
      price: 999,
      size: 28,
      type: "Studio",
      image: null,
      rating: 4.1,
      lat: 48.8625,
      lng: 2.3491,
    },
    {
      id: 13,
      name: "Fried View Apartment",
      price: 1450,
      size: 65,
      type: "Apartment",
      image: null,
      rating: 4.4,
      lat: 48.8837,
      lng: 2.3266,
    },
    {
      id: 14,
      name: "Bucket Room in Ménilmontant",
      price: 580,
      size: 17,
      type: "Shared Room",
      image: null,
      rating: 3.7,
      lat: 48.865,
      lng: 2.389,
    },
    {
      id: 15,
      name: "Zinger's Terrace House",
      price: 2100,
      size: 140,
      type: "House",
      image: null,
      rating: 4.6,
      lat: 48.8322,
      lng: 2.325,
    },
    {
      id: 16,
      name: "Spicy Studio Gare de l'Est",
      price: 890,
      size: 24,
      type: "Studio",
      image: "icon.png",
      rating: 4.0,
      lat: 48.8765,
      lng: 2.3574,
    },
  ];

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
      // console.warn("Map, markers layer, or custom icon not ready for rendering markers."); // Can be noisy
      return;
    }

    markersLayer.clearLayers();

    housingToDisplay.forEach((item) => {
      if (item.lat != null && item.lng != null) {
        // Check for valid coordinates
        const marker = L.marker([item.lat, item.lng], {
          icon: customMarkerIcon,
        });
        const popupContent = `
                    <b>${item.name}</b><br>
                    Type: ${item.type}<br>
                    Price: $${item.price}/month<br>
                    Rating: ${item.rating || "N/A"} ★
                `;
        marker.bindPopup(popupContent);
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
          map.invalidateSize();
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
    if (!resultsGrid) {
      // This is expected if not on the main page
      // console.warn("Results grid container not found.");
      return;
    }

    resultsGrid.innerHTML = ""; // Clear previous listings

    if (housingToDisplay.length === 0) {
      resultsGrid.innerHTML =
        '<p style="grid-column: 1 / -1; text-align: center;">No housing found matching your criteria.</p>';
      return;
    }

    housingToDisplay.forEach((item) => {
      const card = document.createElement("article");
      card.className = "result-card";
      card.innerHTML = `
                <div class="card-image-placeholder">
                    <i class="far fa-image"></i>
                    <!-- <img src="${item.image || "placeholder.jpg"}" alt="${
        item.name
      }"> -->
                </div>
                <div class="card-content">
                    <h4 class="card-title">${item.name} (${item.type})</h4>
                    <p class="card-price">$${item.price}/month</p>
                    <p class="card-size" style="font-size: 0.9em; color: var(--light-text);">Size: ${
                      item.size
                    } m²</p>
                    <p class="card-rating" style="font-size: 0.9em; color: var(--light-text);">Rating: ${
                      item.rating || "N/A"
                    } ★</p>
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
    const lowerCaseSearchTerm = searchTerm.toLowerCase().trim();

    // Ensure data exists before filtering
    if (!Array.isArray(allHousingData)) {
      console.error("Housing data is not available or not an array.");
      return [];
    }

    return allHousingData.filter((item) => {
      const priceMatch = maxPrice === null || item.price <= maxPrice;
      const sizeMatch = maxSize === null || item.size <= maxSize;
      const typeMatch = types.length === 0 || types.includes(item.type);
      const searchMatch =
        !lowerCaseSearchTerm ||
        item.name.toLowerCase().includes(lowerCaseSearchTerm);
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
        sortedList.sort((a, b) => (b.rating || 0) - (a.rating || 0));
        break;
      case "new":
      default:
        sortedList.sort((a, b) => b.id - a.id); // Assuming higher ID is newer
        break;
    }
    return sortedList;
  }

  // ==========================================
  //          Core Update Function
  // ==========================================

  function updateDisplay() {
    // Only run if the necessary elements exist (e.g., on index.html)
    if (!resultsGrid || (!map && mapElement)) {
      // Don't try to update if grid is missing, or map element exists but map object failed init
      return;
    }

    try {
      const filteredResults = filterHousing();
      const sortedAndFilteredResults = sortHousing(filteredResults, activeSort);

      renderHousing(sortedAndFilteredResults); // Update grid
      if (map && markersLayer) {
        // Check map objects before rendering markers
        renderMapMarkers(filteredResults); // Update map markers
      }
    } catch (error) {
      console.error("Error during updateDisplay:", error);
    }
  }

  // ==========================================
  //          Event Handlers
  // ==========================================

  function handleDarkModeToggle() {
    if (!themeToggleButton) return;
    document.body.classList.toggle("dark-mode");
    const isDarkMode = document.body.classList.contains("dark-mode");
    const icon = themeToggleButton.querySelector("i");
    if (icon) {
      icon.className = isDarkMode ? "fas fa-sun" : "fas fa-moon";
    }
  }

  function handleFilterChange() {
    // Update state from sliders
    if (priceRangeSlider)
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider)
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);

    // Update state from checkboxes
    activeFilters.types = [];
    if (typeCheckboxes) {
      // Check if checkboxes exist
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

  function handleSortChange(event) {
    const button = event.target.closest(".sort-btn");
    if (button && button.dataset.sort && button.dataset.sort !== activeSort) {
      activeSort = button.dataset.sort;
      if (sortButtons) {
        // Check if sortButtons exist
        sortButtons.forEach((btn) => {
          btn.classList.toggle("active", btn.dataset.sort === activeSort);
        });
      }
      updateDisplay();
    }
  }

  function handleSearchInput() {
    if (!searchInput) return;
    activeFilters.searchTerm = searchInput.value;
    updateDisplay();
  }

  function clearAllFilters() {
    // Reset sliders and update state/display
    if (priceRangeSlider) {
      priceRangeSlider.value = priceRangeSlider.max;
      activeFilters.maxPrice = parseInt(priceRangeSlider.max, 10);
      updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    }
    if (sizeRangeSlider) {
      sizeRangeSlider.value = sizeRangeSlider.max;
      activeFilters.maxSize = parseInt(sizeRangeSlider.max, 10);
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

  // --- Map Toggle Event Handler ---
  function handleMapToggle() {
    if (!resultsLayout || !mapToggleButton) {
      console.error("Map layout or toggle button not found for toggle action.");
      return;
    }

    const isHidden = resultsLayout.classList.toggle("map-hidden");
    const icon = mapToggleButton.querySelector("i");
    const text = mapToggleButton.querySelector("span");

    // Update button appearance and ARIA attribute
    mapToggleButton.setAttribute("aria-expanded", isHidden ? "false" : "true");
    if (icon)
      icon.className = isHidden ? "fas fa-map-location-dot" : "fas fa-compress";
    if (text) text.textContent = isHidden ? "Show Map" : "Hide Map";

    // Invalidate map size ONLY when it becomes visible
    if (!isHidden) {
      invalidateMapSize();
    }
  }

  // --- Map Resizing Event Handlers ---
  function handleMapResizeStart(event) {
    // Only proceed if the event target is the handle itself or its descendant
    if (
      !mapResizeHandle ||
      !mapResizeHandle.contains(event.target) ||
      !mapElement
    )
      return;

    // Prevent default drag behavior ONLY for the handle to allow map dragging elsewhere
    if (event.target === mapResizeHandle) {
      event.preventDefault();
    }

    isResizingMap = true;
    const currentX = event.touches ? event.touches[0].clientX : event.clientX;
    const currentY = event.touches ? event.touches[0].clientY : event.clientY;
    mapResizeStartX = currentX;
    mapResizeStartY = currentY;
    mapResizeInitialWidth = mapElement.offsetWidth;
    mapResizeInitialHeight = mapElement.offsetHeight;

    document.body.classList.add("map-resizing"); // Add class for cursor/user-select

    // Add listeners to the whole document to capture mouse movements anywhere
    document.addEventListener("mousemove", handleMapResizeMove);
    document.addEventListener("mouseup", handleMapResizeEnd);
    // Add touch listeners as well for mobile
    document.addEventListener("touchmove", handleMapResizeMove, {
      passive: false,
    }); // Prevent scroll during drag
    document.addEventListener("touchend", handleMapResizeEnd);
    console.log("Map resize started");
  }

  function handleMapResizeMove(event) {
    if (!isResizingMap || !mapElement) return;

    // Optional: Prevent map drag/zoom while resizing its container
    if (map && map.dragging) map.dragging.disable();
    if (map && map.touchZoom) map.touchZoom.disable();
    if (map && map.doubleClickZoom) map.doubleClickZoom.disable();
    if (map && map.scrollWheelZoom) map.scrollWheelZoom.disable();

    // Handle touch events
    const currentX = event.touches ? event.touches[0].clientX : event.clientX;
    const currentY = event.touches ? event.touches[0].clientY : event.clientY;

    const dx = currentX - mapResizeStartX;
    const dy = currentY - mapResizeStartY;

    let newWidth = mapResizeInitialWidth + dx;
    let newHeight = mapResizeInitialHeight + dy;

    // Apply minimum size constraints
    newWidth = Math.max(MIN_MAP_WIDTH, newWidth);
    newHeight = Math.max(MIN_MAP_HEIGHT, newHeight);

    // Apply new dimensions directly to the map element's style
    mapElement.style.width = `${newWidth}px`;
    mapElement.style.height = `${newHeight}px`;

    // Prevent scroll during touch drag
    if (event.touches) {
      event.preventDefault();
    }
  }

  function handleMapResizeEnd() {
    if (!isResizingMap) return;

    isResizingMap = false;
    document.body.classList.remove("map-resizing"); // Remove class

    // Remove document listeners
    document.removeEventListener("mousemove", handleMapResizeMove);
    document.removeEventListener("mouseup", handleMapResizeEnd);
    document.removeEventListener("touchmove", handleMapResizeMove);
    document.removeEventListener("touchend", handleMapResizeEnd);

    // Re-enable map interactions if they were disabled
    if (map && map.dragging) map.dragging.enable();
    if (map && map.touchZoom) map.touchZoom.enable();
    if (map && map.doubleClickZoom) map.doubleClickZoom.enable();
    if (map && map.scrollWheelZoom) map.scrollWheelZoom.enable();

    // IMPORTANT: Invalidate map size after resizing is finished
    invalidateMapSize(); // Use the existing function
    console.log("Map resize finished.");
  }

  // ==========================================
  //          Event Listener Setup
  // ==========================================

  function setupEventListeners() {
    // --- Theme Toggle ---
    if (themeToggleButton) {
      themeToggleButton.addEventListener("click", handleDarkModeToggle);
    }

    // --- Filters ---
    if (filtersContainer) {
      filtersContainer.addEventListener("input", (event) => {
        if (event.target.classList.contains("filter-range")) {
          handleFilterChange();
        }
      });
      filtersContainer.addEventListener("change", (event) => {
        if (event.target.classList.contains("filter-type")) {
          handleFilterChange();
        }
      });
    } else if (document.querySelector(".filters-sidebar")) {
      console.warn("Filters container with ID 'filters-container' not found.");
    }
    if (clearFiltersButton) {
      clearFiltersButton.addEventListener("click", clearAllFilters);
    }

    // --- Sorting ---
    if (sortButtonsContainer) {
      sortButtonsContainer.addEventListener("click", handleSortChange);
    } else if (document.querySelector(".sort-options")) {
      console.warn(
        "Sort buttons container with class '.sort-options' not found."
      );
    }

    // --- Search ---
    if (searchInput) {
      searchInput.addEventListener("input", handleSearchInput);
    } else if (document.getElementById("search-input")) {
      console.warn("Search input element not found.");
    }

    // --- Map Toggle Button ---
    if (mapToggleButton) {
      mapToggleButton.addEventListener("click", handleMapToggle);
    } else if (document.getElementById("map-toggle-button")) {
      console.warn("Map toggle button element not found.");
    }

    // --- Map Resize Handle ---
    if (mapResizeHandle) {
      mapResizeHandle.addEventListener("mousedown", handleMapResizeStart);
      mapResizeHandle.addEventListener("touchstart", handleMapResizeStart, {
        passive: false,
      });
    } else if (mapElement) {
      console.warn("Map resize handle element (#map-resize-handle) not found.");
    }
  }

  // ==========================================
  //          Initialization on DOM Load
  // ==========================================

  function initialize() {
    console.log("Initializing CROUS-X Script...");

    // Set initial filter state based on default HTML values (if elements exist)
    if (priceRangeSlider)
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider)
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);

    // Update display values for sliders on load
    updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");

    let mapInitialized = false;
    // Initialize the map (only if map element exists and Leaflet loaded)
    if (mapElement && typeof L !== "undefined") {
      console.log("Initializing Map...");
      mapInitialized = initializeMap(); // Check if successful
    } else if (mapElement) {
      console.error("Map element found, but Leaflet (L) is not defined.");
    }

    // Initial display render (only if grid exists and map is ready or not needed)
    if (resultsGrid && (!mapElement || mapInitialized)) {
      console.log("Performing initial display update...");
      updateDisplay();
    } else if (document.querySelector(".results-area")) {
      console.warn(
        "Results grid not found or map failed to initialize. Initial housing display skipped."
      );
    }

    // Attach all event listeners
    setupEventListeners();

    console.log("CROUS-X Script Initialized Successfully.");
  }

  // Run initialization when the DOM is fully loaded
  document.addEventListener("DOMContentLoaded", initialize);
})(); // End IIFE