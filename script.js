// ==========================================
//          CROUS-X Script
// ==========================================
// Includes: Dark Mode, Filtering, Sorting,
// Leaflet Map with Clustering & Custom Icons,
// Toggleable Map View.
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
  const mapToggleButton = document.getElementById("map-toggle-button"); // <-- Added
  const resultsLayout = document.getElementById("results-layout"); // <-- Added

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
      image: null,
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
      return;
    }
    if (map) map.remove(); // Remove existing map instance if present

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
        map.invalidateSize();
        console.log("Map size invalidated.");
        // Optional: Recenter map after resize if needed
        // map.flyTo(MAP_INITIAL_COORDS, map.getZoom());
      }, MAP_INVALIDATE_DELAY);
    }
  }

  // ==========================================
  //          UI Rendering Functions
  // ==========================================

  function renderHousing(housingToDisplay) {
    if (!resultsGrid) {
      console.error("Results grid container not found.");
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
    const filteredResults = filterHousing();
    const sortedAndFilteredResults = sortHousing(filteredResults, activeSort);

    renderHousing(sortedAndFilteredResults); // Update grid
    renderMapMarkers(filteredResults); // Update map markers (only filtered needed)
  }

  // ==========================================
  //          Event Handlers
  // ==========================================

  function handleDarkModeToggle() {
    document.body.classList.toggle("dark-mode");
    const isDarkMode = document.body.classList.contains("dark-mode");
    const icon = themeToggleButton.querySelector("i");
    if (icon) {
      icon.className = isDarkMode ? "fas fa-sun" : "fas fa-moon"; // Simpler toggle
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
    typeCheckboxes.forEach((checkbox) => {
      if (checkbox.checked) {
        activeFilters.types.push(checkbox.value);
      }
    });

    // Update display values (no need for if check here, function handles it)
    updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");

    updateDisplay();
  }

  function handleSortChange(event) {
    const button = event.target.closest(".sort-btn");
    if (button && button.dataset.sort && button.dataset.sort !== activeSort) {
      activeSort = button.dataset.sort;
      // Update button active states
      sortButtons.forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.sort === activeSort);
      });
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
    typeCheckboxes.forEach((checkbox) => (checkbox.checked = false));
    activeFilters.types = [];

    // Reset search and state
    if (searchInput) searchInput.value = "";
    activeFilters.searchTerm = "";

    // Reset sorting and button states
    activeSort = "new";
    sortButtons.forEach((button) => {
      button.classList.toggle("active", button.dataset.sort === "new");
    });

    updateDisplay();
    console.log("Filters, Sort, Search Cleared");
  }

  // --- Map Toggle Event Handler ---
  function handleMapToggle() {
    if (!resultsLayout || !mapToggleButton) {
      console.error("Map layout or toggle button not found.");
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

  // ==========================================
  //          Event Listener Setup
  // ==========================================

  function setupEventListeners() {
    if (themeToggleButton) {
      themeToggleButton.addEventListener("click", handleDarkModeToggle);
    }

    // Use event delegation for filters
    if (filtersContainer) {
      filtersContainer.addEventListener("input", (event) => {
        if (event.target.classList.contains("filter-range")) {
          handleFilterChange(); // Range sliders trigger this
        }
      });
      filtersContainer.addEventListener("change", (event) => {
        if (event.target.classList.contains("filter-type")) {
          handleFilterChange(); // Checkboxes trigger this
        }
      });
    } else if (document.querySelector(".filters-sidebar")) {
      console.warn(
        "Filters container with ID 'filters-container' not found for event listeners."
      );
    }

    // Use event delegation for sort buttons
    if (sortButtonsContainer) {
      sortButtonsContainer.addEventListener("click", handleSortChange);
    } else if (document.querySelector(".sort-options")) {
      console.warn(
        "Sort buttons container with class '.sort-options' not found for event listeners."
      );
    }

    if (searchInput) {
      searchInput.addEventListener("input", handleSearchInput);
    } else if (document.getElementById("search-input")) {
      console.warn("Search input element not found.");
    }

    if (clearFiltersButton) {
      clearFiltersButton.addEventListener("click", clearAllFilters);
    }

    // --- Add Map Toggle Listener ---
    if (mapToggleButton) {
      mapToggleButton.addEventListener("click", handleMapToggle);
    } else if (document.getElementById("map-toggle-button")) {
      console.warn("Map toggle button element not found.");
    }
  }

  // ==========================================
  //          Initialization on DOM Load
  // ==========================================

  function initialize() {
    console.log("Initializing CROUS-X Script...");

    // Set initial state based on default HTML values (if elements exist)
    if (priceRangeSlider)
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider)
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);

    // Update display values for sliders on load
    updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");

    // Initialize the map (only if map element exists)
    if (mapElement && typeof L !== "undefined") {
      console.log("Initializing Map...");
      initializeMap();
    } else if (mapElement) {
      console.error("Map element found, but Leaflet (L) is not defined.");
    }

    // Initial display render (only if results grid exists and map has initialized or doesn't exist)
    if (resultsGrid && (map || !mapElement)) {
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
