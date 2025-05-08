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
  const customMarkerIcon =
    typeof L !== "undefined"
      ? L.divIcon({
          className: "custom-div-icon",
          html: "",
          iconSize: [24, 24],
          iconAnchor: [12, 12],
          popupAnchor: [0, -12],
        })
      : null;

  // --- DOM Element Selection ---
  const hamburgerButton = document.querySelector(".hamburger");
  const mainNav = document.querySelector(".main-nav");
  const themeToggleButton = document.getElementById("theme-toggle");
  const languageToggle = document.getElementById("language-toggle");
  const languageOptions = document.getElementById("language-options");
  const currentLangSpan = languageToggle
    ? languageToggle.querySelector(".current-lang")
    : null;
  const filtersContainer = document.getElementById("filters-container");
  const priceRangeSlider = document.getElementById("price-range");
  const sizeRangeSlider = document.getElementById("size-range");
  const priceRangeValueSpan = document.getElementById("price-range-value");
  const sizeRangeValueSpan = document.getElementById("size-range-value");
  const typeCheckboxes = document.querySelectorAll(".filter-type");
  const clearFiltersButton = document.getElementById("clear-filters-btn");
  const resultsGrid = document.getElementById("results-grid");
  const sortButtonsContainer = document.querySelector(".sort-options");
  const sortButtons = document.querySelectorAll(".sort-btn");
  const searchInput = document.getElementById("search-input");
  const mapElement = document.getElementById("map");
  const mapToggleButton = document.getElementById("map-toggle-button");
  const resultsLayout = document.querySelector(".results-layout");
  const mapContainer = document.querySelector(".map-container");
  const mapResizeHandle = document.getElementById("map-resize-handle");

  // --- State Management ---
  let currentLanguageData = {};
  let currentLangCode = DEFAULT_LANG;
  let activeFilters = {
    maxPrice: priceRangeSlider ? parseInt(priceRangeSlider.max, 10) : 10000,
    maxSize: sizeRangeSlider ? parseInt(sizeRangeSlider.max, 10) : 250,
    types: [],
    searchTerm: "",
  };
  let activeSort = "new";
  let map = null;
  let markersLayer = null;
  let isResizingMap = false;
  let mapResizeStartX, mapResizeInitialWidth;

  // --- Data Fetching ---
  async function fetchHousingData() {
    try {
      // Ensure the API path is correct relative to where the script is loaded from or use absolute path
      const res = await fetch("./api/getHousing.php"); // Assuming API is relative to the root HTML
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      const data = await res.json();
      window.allHousingData = data; // Make it globally accessible for now
      updateDisplay();
    } catch (e) {
      console.error("Error loading housing data:", e);
      window.allHousingData = []; // Set to empty array on error to prevent further issues
      updateDisplay(); // Still update display to show "no results" or similar
    }
  }

  // --- Internationalization (i18n) ---
  async function loadLanguage(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) {
      lang = DEFAULT_LANG;
    }
    try {
      const languagesPath = "./languages/";
      const response = await fetch(
        `${languagesPath}${lang}.json?v=${Date.now()}`
      );
      if (!response.ok) {
        throw new Error(
          `HTTP error! status: ${response.status}, failed to fetch ${response.url}`
        );
      }
      currentLanguageData = await response.json();
      currentLangCode = lang;
      applyTranslations();
      updateLanguageSwitcherState(lang);
      localStorage.setItem("selectedLanguage", lang);
    } catch (error) {
      console.error(`Could not load language file for ${lang}:`, error);
      if (lang !== DEFAULT_LANG) {
        await loadLanguage(DEFAULT_LANG);
      }
    }
  }

  function applyTranslations() {
    if (!currentLanguageData || Object.keys(currentLanguageData).length === 0)
      return;
    document.querySelectorAll("[data-i18n-key]").forEach((el) => {
      const key = el.getAttribute("data-i18n-key");
      if (currentLanguageData[key] !== undefined)
        el.textContent = currentLanguageData[key];
    });
    document.querySelectorAll("[data-i18n-key-placeholder]").forEach((el) => {
      const key = el.getAttribute("data-i18n-key-placeholder");
      if (currentLanguageData[key] !== undefined)
        el.placeholder = currentLanguageData[key];
    });
    document.querySelectorAll("[data-i18n-key-aria-label]").forEach((el) => {
      const key = el.getAttribute("data-i18n-key-aria-label");
      if (currentLanguageData[key] !== undefined)
        el.setAttribute("aria-label", currentLanguageData[key]);
    });
    document.querySelectorAll("[data-i18n-key-title]").forEach((el) => {
      const key = el.getAttribute("data-i18n-key-title");
      if (currentLanguageData[key] !== undefined)
        el.title = currentLanguageData[key];
    });
  }

  function updateLanguageSwitcherState(lang) {
    if (currentLangSpan) currentLangSpan.textContent = lang.toUpperCase();
    document.documentElement.lang = lang;
  }

  function getInitialLanguage() {
    const savedLang = localStorage.getItem("selectedLanguage");
    if (savedLang && SUPPORTED_LANGS.includes(savedLang)) return savedLang;
    const browserLang = navigator.language.split("-")[0];
    if (SUPPORTED_LANGS.includes(browserLang)) return browserLang;
    return DEFAULT_LANG;
  }

  // --- Map Functions ---
  function initializeMap() {
    if (!mapElement || typeof L === "undefined") return false;
    if (map) map.remove();
    try {
      map = L.map(mapElement).setView(MAP_INITIAL_COORDS, MAP_INITIAL_ZOOM);
      L.tileLayer(MAP_TILE_URL, {
        maxZoom: MAP_MAX_ZOOM,
        attribution: MAP_ATTRIBUTION,
      }).addTo(map);
      markersLayer =
        typeof L.markerClusterGroup === "function"
          ? L.markerClusterGroup()
          : L.layerGroup();
      map.addLayer(markersLayer);
      return true;
    } catch (error) {
      console.error("Error initializing Leaflet map:", error);
      map = null;
      return false;
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
        marker.bindPopup(
          `<b>${item.title}</b><br>Type: ${item.property_type}<br>Price: $${
            item.rent_amount
          }/month<br>Rating: ${item.rating ?? "N/A"} ★`
        );
        markersLayer.addLayer(marker);
      }
    });
  }

  function invalidateMapSize() {
    if (map) {
      setTimeout(() => {
        try {
          map.invalidateSize({ animate: true });
        } catch (error) {
          console.error("Error during map.invalidateSize():", error);
        }
      }, MAP_INVALIDATE_DELAY);
    }
  }

  // --- UI Rendering ---
  function renderHousing(housingToDisplay) {
    if (!resultsGrid) return;
    resultsGrid.innerHTML = "";
    if (housingToDisplay.length === 0) {
      const noResultsMessage = document.createElement("p");
      noResultsMessage.setAttribute("data-i18n-key", "no_results");
      noResultsMessage.textContent = "No results found matching your criteria.";
      resultsGrid.appendChild(noResultsMessage);
      if (typeof applyTranslations === "function") applyTranslations();
      return;
    }
    housingToDisplay.forEach((item) => {
      const link = document.createElement("a");
      link.href = `housing-detail.php?id=${item.listing_id}`;
      link.className = "result-card-link";
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
        </div>`;
      link.appendChild(card);
      resultsGrid.appendChild(link);
    });
  }

  function updateSliderValueDisplay(slider, span, prefix = "", suffix = "") {
    if (slider && span) span.textContent = `${prefix}${slider.value}${suffix}`;
  }

  // --- Filtering & Sorting ---
  function filterHousing() {
    const { maxPrice, maxSize, types, searchTerm } = activeFilters;
    const term = searchTerm.toLowerCase().trim();
    if (!Array.isArray(window.allHousingData)) return [];
    return window.allHousingData.filter((item) => {
      const priceMatch = item.rent_amount <= maxPrice;
      const sizeMatch = item.square_footage <= maxSize;
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
      default:
        sorted.sort((a, b) => b.listing_id - a.listing_id);
    }
    return sorted;
  }

  // --- Core Update ---
  function updateDisplay() {
    if (!resultsGrid && !mapElement) return;
    try {
      const filtered = filterHousing();
      const sortedFiltered = sortHousing(filtered, activeSort);
      if (resultsGrid) renderHousing(sortedFiltered);
      if (map && markersLayer) renderMapMarkers(filtered);
    } catch (error) {
      console.error("Error during updateDisplay:", error);
    }
  }

  // --- Event Handlers ---
  function handleDarkModeToggle() {
    if (!themeToggleButton) return;
    const body = document.body;
    body.classList.toggle("dark-mode");
    const isDarkMode = body.classList.contains("dark-mode");
    localStorage.setItem("darkMode", isDarkMode ? "enabled" : "disabled");
    const icon = themeToggleButton.querySelector("i");
    if (icon) icon.className = isDarkMode ? "fas fa-sun" : "fas fa-moon";
    if (map) invalidateMapSize();
  }

  function handleFilterChange() {
    if (priceRangeSlider)
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider)
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);
    activeFilters.types = [];
    if (typeCheckboxes) {
      typeCheckboxes.forEach((cb) => {
        if (cb.checked) activeFilters.types.push(cb.value);
      });
    }
    updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");
    updateDisplay();
  }

  function handleSortChange(event) {
    const button = event.target.closest(".sort-btn");
    if (button && button.dataset.sort && button.dataset.sort !== activeSort) {
      activeSort = button.dataset.sort;
      if (sortButtons) {
        sortButtons.forEach((btn) =>
          btn.classList.toggle("active", btn === button)
        );
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
    if (typeCheckboxes) typeCheckboxes.forEach((cb) => (cb.checked = false));
    activeFilters.types = [];
    if (searchInput) searchInput.value = "";
    activeFilters.searchTerm = "";
    activeSort = "new";
    if (sortButtons) {
      sortButtons.forEach((btn) =>
        btn.classList.toggle("active", btn.dataset.sort === "new")
      );
    }
    updateDisplay();
  }

  function handleMapToggle() {
    if (!resultsLayout || !mapToggleButton) return;
    const isHidden = resultsLayout.classList.toggle("map-hidden");
    mapToggleButton.setAttribute("aria-expanded", !isHidden);
    const icon = mapToggleButton.querySelector("i");
    const text = mapToggleButton.querySelector("span");
    if (icon)
      icon.className = isHidden ? "fas fa-map-location-dot" : "fas fa-compress";
    if (text) text.textContent = isHidden ? "Show Map" : "Hide Map";
    if (!isHidden) invalidateMapSize();
  }

  function handleMapResizeStart(event) {
    if (
      !mapResizeHandle ||
      !mapContainer ||
      !resultsLayout ||
      !mapResizeHandle.contains(event.target)
    )
      return;
    event.preventDefault();
    isResizingMap = true;
    mapResizeStartX = event.touches ? event.touches[0].clientX : event.clientX;
    mapResizeInitialWidth = mapContainer.offsetWidth;
    document.body.classList.add("map-resizing");
    document.addEventListener("mousemove", handleMapResizeMove);
    document.addEventListener("mouseup", handleMapResizeEnd);
    document.addEventListener("touchmove", handleMapResizeMove, {
      passive: false,
    });
    document.addEventListener("touchend", handleMapResizeEnd);
  }

  function handleMapResizeMove(event) {
    if (!isResizingMap || !mapContainer) return;
    if (map && map.dragging.enabled()) map.dragging.disable();
    const currentX = event.touches ? event.touches[0].clientX : event.clientX;
    const dx = currentX - mapResizeStartX;
    let newWidth = mapResizeInitialWidth - dx;
    const gridContainer = document.querySelector(".results-grid-container");
    const availableWidth =
      resultsLayout.offsetWidth - mapResizeHandle.offsetWidth;
    const minGridAllowedWidth = gridContainer ? MIN_GRID_WIDTH : 0;
    newWidth = Math.max(MIN_MAP_WIDTH, newWidth);
    newWidth = Math.min(availableWidth - minGridAllowedWidth, newWidth);
    mapContainer.style.flex = `0 0 ${newWidth}px`;
    if (event.touches) event.preventDefault();
  }

  function handleMapResizeEnd() {
    if (!isResizingMap) return;
    isResizingMap = false;
    document.body.classList.remove("map-resizing");
    if (map && !map.dragging.enabled()) map.dragging.enable();
    document.removeEventListener("mousemove", handleMapResizeMove);
    document.removeEventListener("mouseup", handleMapResizeEnd);
    document.removeEventListener("touchmove", handleMapResizeMove);
    document.removeEventListener("touchend", handleMapResizeEnd);
    invalidateMapSize();
  }

  function handleLanguageChange(event) {
    event.preventDefault();
    const targetLink = event.target.closest("a[data-lang]"); // Ensure we get the link if click is on child
    if (!targetLink) return;

    const selectedLang = targetLink.getAttribute("data-lang");

    if (selectedLang && selectedLang !== currentLangCode) {
      loadLanguage(selectedLang).then(() => {
        // Ensure dependent UI updates happen after language is fully loaded and applied
        closeLanguageDropdown();
        if (hamburgerButton && hamburgerButton.classList.contains("active")) {
          closeHamburgerMenu(); // Use the dedicated close function
        }
      });
    } else if (selectedLang) {
      closeLanguageDropdown();
      if (hamburgerButton && hamburgerButton.classList.contains("active")) {
        closeHamburgerMenu();
      }
    }
  }

  // --- Helper functions for UI states ---
  function openHamburgerMenu() {
    if (!hamburgerButton || !mainNav) return;
    hamburgerButton.classList.add("active");
    mainNav.classList.add("active");
    hamburgerButton.setAttribute("aria-expanded", "true");
    mainNav.setAttribute("aria-hidden", "false");
    mainNav.removeAttribute("inert"); // Make interactive
    document.body.classList.add("nav-open");
  }

  function closeHamburgerMenu() {
    if (!hamburgerButton || !mainNav) return;
    hamburgerButton.classList.remove("active");
    mainNav.classList.remove("active");
    hamburgerButton.setAttribute("aria-expanded", "false");
    mainNav.setAttribute("aria-hidden", "true");
    mainNav.setAttribute("inert", ""); // Make non-interactive
    document.body.classList.remove("nav-open");

    // Close language dropdown if it's open within the nav
    if (languageOptions && languageOptions.classList.contains("show")) {
      closeLanguageDropdown();
    }
    // IMPORTANT: Move focus back to the hamburger button
    if (hamburgerButton) hamburgerButton.focus();
  }

  function openLanguageDropdown() {
    if (!languageOptions || !languageToggle) return;
    languageOptions.classList.add("show");
    languageToggle.setAttribute("aria-expanded", "true");
  }

  function closeLanguageDropdown() {
    if (!languageOptions || !languageToggle) return;
    languageOptions.classList.remove("show");
    languageToggle.setAttribute("aria-expanded", "false");
  }

  // --- Event Listener Setup ---
  function setupEventListeners() {
    if (hamburgerButton && mainNav) {
      hamburgerButton.setAttribute("aria-expanded", "false");
      mainNav.setAttribute("aria-hidden", "true");
      mainNav.setAttribute("inert", ""); // Initially inert

      hamburgerButton.addEventListener("click", () => {
        const isActive = mainNav.classList.contains("active");
        if (isActive) {
          closeHamburgerMenu();
        } else {
          openHamburgerMenu();
        }
      });

      mainNav
        .querySelectorAll("a, button:not(#language-toggle)")
        .forEach((item) => {
          item.addEventListener("click", () => {
            if (mainNav.classList.contains("active")) {
              closeHamburgerMenu();
            }
          });
        });

      document.addEventListener("click", (event) => {
        if (
          mainNav.classList.contains("active") &&
          !mainNav.contains(event.target) &&
          !hamburgerButton.contains(event.target)
        ) {
          closeHamburgerMenu();
        }
      });
    }

    if (languageToggle && languageOptions) {
      languageToggle.addEventListener("click", (event) => {
        event.stopPropagation();
        if (languageOptions.classList.contains("show")) {
          closeLanguageDropdown();
        } else {
          openLanguageDropdown();
        }
      });
      languageOptions.addEventListener("click", handleLanguageChange); // Delegate to parent
      document.addEventListener("click", (event) => {
        if (
          languageOptions.classList.contains("show") &&
          !languageToggle.contains(event.target) &&
          !languageOptions.contains(event.target)
        ) {
          closeLanguageDropdown();
        }
      });
    }

    if (themeToggleButton)
      themeToggleButton.addEventListener("click", handleDarkModeToggle);
    if (filtersContainer) {
      filtersContainer.addEventListener("input", (e) => {
        if (e.target.matches("#price-range, #size-range")) handleFilterChange();
      });
      filtersContainer.addEventListener("change", (e) => {
        if (e.target.matches(".filter-type")) handleFilterChange();
      });
    }
    if (clearFiltersButton)
      clearFiltersButton.addEventListener("click", clearAllFilters);
    if (sortButtonsContainer)
      sortButtonsContainer.addEventListener("click", handleSortChange);
    if (searchInput) searchInput.addEventListener("input", handleSearchInput);
    if (mapToggleButton)
      mapToggleButton.addEventListener("click", handleMapToggle);
    if (mapResizeHandle) {
      mapResizeHandle.addEventListener("mousedown", handleMapResizeStart);
      mapResizeHandle.addEventListener("touchstart", handleMapResizeStart, {
        passive: false,
      });
    }
  }

  function applyPersistedTheme() {
    const persistedDarkMode = localStorage.getItem("darkMode");
    const body = document.body;
    const isCurrentlyDark = body.classList.contains("dark-mode");
    if (persistedDarkMode === "enabled" && !isCurrentlyDark) {
      body.classList.add("dark-mode");
    } else if (persistedDarkMode !== "enabled" && isCurrentlyDark) {
      body.classList.remove("dark-mode");
    }
    const isDarkModeFinal = body.classList.contains("dark-mode");
    const icon = themeToggleButton
      ? themeToggleButton.querySelector("i")
      : null;
    if (icon) icon.className = isDarkModeFinal ? "fas fa-sun" : "fas fa-moon";
  }

  // --- Initialization ---
  async function initialize() {
    applyPersistedTheme();
    const initialLang = getInitialLanguage();
    await loadLanguage(initialLang);

    if (priceRangeSlider)
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider)
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);
    updateSliderValueDisplay(priceRangeSlider, priceRangeValueSpan, "$");
    updateSliderValueDisplay(sizeRangeSlider, sizeRangeValueSpan, "", " m²");

    let mapInitialized = false;
    if (mapElement && typeof L !== "undefined") {
      mapInitialized = initializeMap();
    }

    setupEventListeners(); // Setup listeners after initial state is set

    // Fetch data after initial language and theme are set, but before initial display
    // if the display depends on this data.
    if (typeof window.allHousingData === "undefined") {
      // Fetch only if not already fetched
      await fetchHousingData(); // Now this happens before the first updateDisplay that needs it
    }

    if (resultsGrid || (mapElement && mapInitialized)) {
      updateDisplay();
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize);
  } else {
    initialize();
  }
})();
