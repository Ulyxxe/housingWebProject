(function () {
  // IIFE to encapsulate scope

  // --- Configuration & Constants ---
  const DEFAULT_LANG = "en";
  const SUPPORTED_LANGS = ["en", "fr", "es"];
  const LANGUAGES_PATH = "./languages/"; // Path to your language JSON files
  const MAP_INITIAL_COORDS = [48.8566, 2.3522]; // Paris center
  const MAP_INITIAL_ZOOM = 12;
  const MAP_MAX_ZOOM = 19;
  const MAP_TILE_URL = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
  const MAP_ATTRIBUTION = "© OpenStreetMap contributors";
  const MAP_INVALIDATE_DELAY = 80;
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

  const INITIAL_MIN_PRICE = 0;
  const INITIAL_MAX_PRICE = 10000;
  const INITIAL_MIN_SIZE = 9;
  const INITIAL_MAX_SIZE = 250;

  // --- DOM Element Selection ---
  const selectors = {
    body: document.body,
    htmlElement: document.documentElement,
    siteHeader: document.querySelector(".site-header"),
    hamburgerButton: document.querySelector(".hamburger"),
    mainNav: document.querySelector(".main-nav"),
    themeToggleButton: document.getElementById("theme-toggle"),
    languageSwitcherToggleButton: document.getElementById(
      "language-switcher-toggle"
    ),
    languageSwitcherDropdown: document.getElementById(
      "language-switcher-dropdown"
    ),
    languageChoiceButtons: null,
    filtersContainer: document.getElementById("filters-container"),

    // Dual sliders and display spans:
    priceRangeMinSlider: document.getElementById("price-range-min"),
    priceRangeMaxSlider: document.getElementById("price-range-max"),
    priceRangeValueDisplay: document.getElementById(
      "price-range-value-display"
    ),
    sizeRangeMinSlider: document.getElementById("size-range-min"),
    sizeRangeMaxSlider: document.getElementById("size-range-max"),
    sizeRangeValueDisplay: document.getElementById("size-range-value-display"),

    typeCheckboxes: document.querySelectorAll(".filter-type"),
    clearFiltersButton: document.getElementById("clear-filters-btn"),
    resultsGrid: document.getElementById("results-grid"),
    sortButtonsContainer: document.querySelector(".sort-options"),
    sortButtons: document.querySelectorAll(".sort-btn"),
    searchInput: document.getElementById("search-input"),
    mapElement: document.getElementById("map"),
    resultsLayout: document.getElementById("results-layout"),
    mapContainerSticky: document.getElementById("map-container-sticky"),
  };

  // --- State Management ---
  let currentLanguageData = {};
  let currentLangCode = DEFAULT_LANG;
  let isLanguageDropdownVisible = false;
  let activeFilters = {
    minPrice: selectors.priceRangeMinSlider
      ? parseInt(selectors.priceRangeMinSlider.value, 10)
      : INITIAL_MIN_PRICE,
    maxPrice: selectors.priceRangeMaxSlider
      ? parseInt(selectors.priceRangeMaxSlider.value, 10)
      : INITIAL_MAX_PRICE,
    minSize: selectors.sizeRangeMinSlider
      ? parseInt(selectors.sizeRangeMinSlider.value, 10)
      : INITIAL_MIN_SIZE,
    maxSize: selectors.sizeRangeMaxSlider
      ? parseInt(selectors.sizeRangeMaxSlider.value, 10)
      : INITIAL_MAX_SIZE,
    types: [],
    searchTerm: "",
  };
  let activeSort = "new";
  let map = null;
  let markersLayer = null;
  window.allHousingData = [];

  // --- Data Fetching ---
  async function fetchHousingData() {
    try {
      const res = await fetch("./api/getHousing.php"); // Ensure this API endpoint is correct
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      const data = await res.json();
      window.allHousingData = data;
    } catch (e) {
      console.error("Error loading housing data:", e);
      if (selectors.resultsGrid) {
        selectors.resultsGrid.innerHTML = `<p data-lang-key="error_loading_listings_app">Error loading listings. Please try again later.</p>`;
        applyTranslations(); // Apply translation to the error message
      }
      window.allHousingData = []; // Set to empty array on error
    }
    updateDisplay(); // Call updateDisplay after fetching or on error
  }

  // --- Internationalization (i18n) ---
  async function loadLanguage(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) lang = DEFAULT_LANG;
    try {
      const response = await fetch(
        `${LANGUAGES_PATH}${lang}.json?v=${Date.now()}`
      );
      if (!response.ok) {
        if (lang !== DEFAULT_LANG) return loadLanguage(DEFAULT_LANG);
        throw new Error(
          `Could not load default language file ${DEFAULT_LANG}.json`
        );
      }
      currentLanguageData = await response.json();
      currentLangCode = lang;
      applyTranslations();
      updateLanguageSwitcherState(lang);
      localStorage.setItem("crousXAppLang", lang);
    } catch (error) {
      console.error(`Could not load language for ${lang}:`, error);
      if (lang !== DEFAULT_LANG && currentLangCode !== DEFAULT_LANG)
        await loadLanguage(DEFAULT_LANG);
      else if (
        lang === DEFAULT_LANG &&
        Object.keys(currentLanguageData).length === 0
      )
        console.error("CRITICAL: Default language file could not be loaded.");
    }
  }

  function applyTranslations() {
    if (!currentLanguageData || Object.keys(currentLanguageData).length === 0) {
      console.warn("No language data loaded to apply translations.");
      return;
    }
    document.querySelectorAll("[data-lang-key]").forEach((el) => {
      const key = el.dataset.langKey;
      let translation = currentLanguageData[key] || `[${key}]`;
      if (key === "footer_copyright_main")
        // Example of dynamic content in translation
        translation = translation.replace("{year}", new Date().getFullYear());
      el.textContent = translation;
    });
    document.querySelectorAll("[data-lang-key-placeholder]").forEach((el) => {
      const key = el.dataset.langKeyPlaceholder;
      el.placeholder =
        currentLanguageData[key] !== undefined
          ? currentLanguageData[key]
          : `[${key}]`;
    });
    document.querySelectorAll("[data-lang-key-aria-label]").forEach((el) => {
      const key = el.getAttribute("data-lang-key-aria-label");
      el.setAttribute(
        "aria-label",
        currentLanguageData[key] !== undefined
          ? currentLanguageData[key]
          : `[${key}]`
      );
    });
    document.querySelectorAll("[data-lang-key-title]").forEach((el) => {
      const key = el.getAttribute("data-lang-key-title");
      el.title =
        currentLanguageData[key] !== undefined
          ? currentLanguageData[key]
          : `[${key}]`;
    });
  }

  function updateLanguageSwitcherState(lang) {
    selectors.htmlElement.lang = lang;
    selectors.languageChoiceButtons?.forEach((button) => {
      button.classList.toggle("active", button.dataset.lang === lang);
    });
  }

  function getInitialLanguage() {
    const savedLang = localStorage.getItem("crousXAppLang");
    if (savedLang && SUPPORTED_LANGS.includes(savedLang)) return savedLang;
    const browserLang = navigator.language.split("-")[0];
    if (SUPPORTED_LANGS.includes(browserLang)) return browserLang;
    return DEFAULT_LANG;
  }

  // --- Map Functions ---
  function initializeMap() {
    if (!selectors.mapElement || typeof L === "undefined") return false;
    if (map) map.remove();
    try {
      map = L.map(selectors.mapElement).setView(
        MAP_INITIAL_COORDS,
        MAP_INITIAL_ZOOM
      );
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
      console.error("Error initializing map:", error);
      map = null;
      return false;
    }
  }

  function renderMapMarkers(filteredData) {
    if (!map || !markersLayer) return;
    markersLayer.clearLayers();
    filteredData.forEach((item) => {
      if (item.latitude != null && item.longitude != null) {
        const marker = L.marker([item.latitude, item.longitude], {
          icon: customMarkerIcon,
        });
        const typeText =
          currentLanguageData[
            `filter_type_${item.property_type
              ?.toLowerCase()
              .replace(/\s+/g, "_")}` // Handle spaces in type
          ] || item.property_type;
        const ratingText = currentLanguageData?.rating_prefix || "Rating";
        const priceText = currentLanguageData?.price_prefix || "Price";
        const perMonthText = currentLanguageData?.per_month_suffix || "/month"; // or use item.rent_frequency
        marker.bindPopup(
          `<b>${item.title}</b><br>${typeText}<br>${priceText}: $${
            item.rent_amount
          } ${
            item.rent_frequency === "monthly"
              ? perMonthText
              : "/" + item.rent_frequency
          }<br>${ratingText}: ${item.rating ?? "N/A"} ★`
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
          console.error("Error invalidating map size:", error);
        }
      }, MAP_INVALIDATE_DELAY);
    }
  }

  // --- UI Rendering ---
  function renderHousing(housingToDisplay) {
    if (!selectors.resultsGrid) return;
    selectors.resultsGrid.innerHTML = "";
    if (housingToDisplay.length === 0) {
      const noResultsMessage = document.createElement("p");
      noResultsMessage.setAttribute("data-lang-key", "no_results_app");
      selectors.resultsGrid.appendChild(noResultsMessage);
      applyTranslations(); // Translate the "no results" message
      return;
    }
    housingToDisplay.forEach((item) => {
      const link = document.createElement("a");
      link.href = `housing-detail.php?id=${item.listing_id}`;
      link.className = "result-card-link";
      const card = document.createElement("article");
      card.className = "result-card";
      const sizeText = currentLanguageData?.size_prefix || "Size";
      const ratingText = currentLanguageData?.rating_prefix || "Rating";
      const perMonthText = currentLanguageData?.per_month_suffix || "/month";
      const propertyTypeText =
        currentLanguageData[
          `filter_type_${item.property_type
            ?.toLowerCase()
            .replace(/\s+/g, "_")}` // Handle multi-word types like "Shared Room"
        ] || item.property_type;
      card.innerHTML = `
        <div class="card-image-placeholder">
          ${
            item.primary_image // Use primary_image from your API
              ? `<img src="${item.primary_image}" alt="${item.title}" loading="lazy">`
              : `<i class="far fa-image"></i>`
          }
        </div>
        <div class="card-content">
          <h4 class="card-title">${item.title} (${propertyTypeText})</h4>
          <p class="card-price">$${item.rent_amount} ${
        item.rent_frequency === "monthly"
          ? perMonthText
          : "/" + item.rent_frequency
      }</p>
          <p class="card-size">${sizeText}: ${item.square_footage} m²</p>
          <p class="card-rating">${ratingText}: ${
        item.rating ?? "N/A"
      } <i class="fas fa-star"></i></p>
        </div>`;
      link.appendChild(card);
      selectors.resultsGrid.appendChild(link);
    });
  }

  function updateRangeValueDisplay(
    minSlider,
    maxSlider,
    displaySpan,
    prefix = "",
    suffix = ""
  ) {
    if (minSlider && maxSlider && displaySpan) {
      const minValue = parseInt(minSlider.value, 10);
      const maxValue = parseInt(maxSlider.value, 10);
      displaySpan.textContent = `${prefix}${minValue}${suffix} - ${prefix}${maxValue}${suffix}`;
    }
  }

  // --- Filtering & Sorting ---
  function filterHousing() {
    const { minPrice, maxPrice, minSize, maxSize, types, searchTerm } =
      activeFilters;
    const term = searchTerm.toLowerCase().trim();
    if (!Array.isArray(window.allHousingData)) return [];

    return window.allHousingData.filter((item) => {
      const priceMatch =
        parseFloat(item.rent_amount) >= minPrice &&
        parseFloat(item.rent_amount) <= maxPrice;
      const sizeMatch =
        parseInt(item.square_footage) >= minSize &&
        parseInt(item.square_footage) <= maxSize;
      const typeMatch =
        types.length === 0 || types.includes(item.property_type);
      const searchMatch =
        !term ||
        item.title.toLowerCase().includes(term) ||
        (item.address_street &&
          item.address_street.toLowerCase().includes(term)) ||
        (item.address_city && item.address_city.toLowerCase().includes(term));
      return priceMatch && sizeMatch && typeMatch && searchMatch;
    });
  }

  function sortHousing(housingList, sortBy) {
    const sorted = [...housingList];
    switch (sortBy) {
      case "price-asc":
        sorted.sort(
          (a, b) => parseFloat(a.rent_amount) - parseFloat(b.rent_amount)
        );
        break;
      case "price-desc":
        sorted.sort(
          (a, b) => parseFloat(b.rent_amount) - parseFloat(a.rent_amount)
        );
        break;
      case "rating":
        sorted.sort(
          (a, b) => (parseFloat(b.rating) || 0) - (parseFloat(a.rating) || 0)
        );
        break;
      default: // 'new' or any other
        sorted.sort(
          (a, b) =>
            (parseInt(b.listing_id) || 0) - (parseInt(a.listing_id) || 0)
        ); // Assuming newer items have higher IDs
    }
    return sorted;
  }

  // --- Core Update ---
  function updateDisplay() {
    if (!selectors.resultsGrid && !selectors.mapElement) return;
    try {
      const filtered = filterHousing();
      const sortedFiltered = sortHousing(filtered, activeSort);
      if (selectors.resultsGrid) renderHousing(sortedFiltered);
      if (map && markersLayer) renderMapMarkers(filtered); // Render markers based on filtered data only for map
    } catch (error) {
      console.error("Error during updateDisplay:", error);
    }
  }

  // --- Event Handlers ---
  function handleDarkModeToggle() {
    if (!selectors.themeToggleButton) return;
    const currentTheme = selectors.htmlElement.getAttribute("data-theme");
    const targetTheme = currentTheme === "dark" ? "light" : "dark";
    selectors.htmlElement.setAttribute("data-theme", targetTheme);
    localStorage.setItem("crousXAppTheme", targetTheme);
    if (map) invalidateMapSize(); // Invalidate map if theme changes
  }

  function handleFilterChange(event) {
    if (selectors.priceRangeMinSlider && selectors.priceRangeMaxSlider) {
      let minPriceVal = parseInt(selectors.priceRangeMinSlider.value, 10);
      let maxPriceVal = parseInt(selectors.priceRangeMaxSlider.value, 10);

      if (
        event &&
        event.target === selectors.priceRangeMinSlider &&
        minPriceVal > maxPriceVal
      ) {
        selectors.priceRangeMaxSlider.value = minPriceVal;
        maxPriceVal = minPriceVal;
      } else if (
        event &&
        event.target === selectors.priceRangeMaxSlider &&
        maxPriceVal < minPriceVal
      ) {
        selectors.priceRangeMinSlider.value = maxPriceVal;
        minPriceVal = maxPriceVal;
      }
      activeFilters.minPrice = minPriceVal;
      activeFilters.maxPrice = maxPriceVal;
    }

    if (selectors.sizeRangeMinSlider && selectors.sizeRangeMaxSlider) {
      let minSizeVal = parseInt(selectors.sizeRangeMinSlider.value, 10);
      let maxSizeVal = parseInt(selectors.sizeRangeMaxSlider.value, 10);

      if (
        event &&
        event.target === selectors.sizeRangeMinSlider &&
        minSizeVal > maxSizeVal
      ) {
        selectors.sizeRangeMaxSlider.value = minSizeVal;
        maxSizeVal = minSizeVal;
      } else if (
        event &&
        event.target === selectors.sizeRangeMaxSlider &&
        maxSizeVal < minSizeVal
      ) {
        selectors.sizeRangeMinSlider.value = maxSizeVal;
        minSizeVal = maxSizeVal;
      }
      activeFilters.minSize = minSizeVal;
      activeFilters.maxSize = maxSizeVal;
    }

    activeFilters.types = [];
    if (selectors.typeCheckboxes) {
      selectors.typeCheckboxes.forEach((cb) => {
        if (cb.checked) activeFilters.types.push(cb.value);
      });
    }

    updateRangeValueDisplay(
      selectors.priceRangeMinSlider,
      selectors.priceRangeMaxSlider,
      selectors.priceRangeValueDisplay,
      "$"
    );
    updateRangeValueDisplay(
      selectors.sizeRangeMinSlider,
      selectors.sizeRangeMaxSlider,
      selectors.sizeRangeValueDisplay,
      "",
      " m²"
    );
    updateDisplay();
  }

  function handleSortChange(event) {
    const button = event.target.closest(".sort-btn");
    if (button && button.dataset.sort && button.dataset.sort !== activeSort) {
      activeSort = button.dataset.sort;
      if (selectors.sortButtons) {
        selectors.sortButtons.forEach((btn) =>
          btn.classList.toggle("active", btn === button)
        );
      }
      updateDisplay();
    }
  }

  function handleSearchInput() {
    if (!selectors.searchInput) return;
    activeFilters.searchTerm = selectors.searchInput.value;
    updateDisplay();
  }

  function clearAllFilters() {
    if (selectors.priceRangeMinSlider) {
      selectors.priceRangeMinSlider.value = selectors.priceRangeMinSlider.min;
      activeFilters.minPrice = parseInt(selectors.priceRangeMinSlider.min, 10);
    }
    if (selectors.priceRangeMaxSlider) {
      selectors.priceRangeMaxSlider.value = selectors.priceRangeMaxSlider.max;
      activeFilters.maxPrice = parseInt(selectors.priceRangeMaxSlider.max, 10);
    }
    updateRangeValueDisplay(
      selectors.priceRangeMinSlider,
      selectors.priceRangeMaxSlider,
      selectors.priceRangeValueDisplay,
      "$"
    );

    if (selectors.sizeRangeMinSlider) {
      selectors.sizeRangeMinSlider.value = selectors.sizeRangeMinSlider.min;
      activeFilters.minSize = parseInt(selectors.sizeRangeMinSlider.min, 10);
    }
    if (selectors.sizeRangeMaxSlider) {
      selectors.sizeRangeMaxSlider.value = selectors.sizeRangeMaxSlider.max;
      activeFilters.maxSize = parseInt(selectors.sizeRangeMaxSlider.max, 10);
    }
    updateRangeValueDisplay(
      selectors.sizeRangeMinSlider,
      selectors.sizeRangeMaxSlider,
      selectors.sizeRangeValueDisplay,
      "",
      " m²"
    );

    if (selectors.typeCheckboxes)
      selectors.typeCheckboxes.forEach((cb) => (cb.checked = false));
    activeFilters.types = [];
    if (selectors.searchInput) selectors.searchInput.value = "";
    activeFilters.searchTerm = "";
    activeSort = "new"; // Reset sort to default
    if (selectors.sortButtons) {
      selectors.sortButtons.forEach((btn) =>
        btn.classList.toggle("active", btn.dataset.sort === "new")
      );
    }
    updateDisplay();
  }

  function toggleLanguageDropdown(event) {
    event.stopPropagation();
    isLanguageDropdownVisible = !isLanguageDropdownVisible;
    selectors.siteHeader?.classList.toggle(
      "language-dropdown-visible",
      isLanguageDropdownVisible
    );
    selectors.languageSwitcherToggleButton?.setAttribute(
      "aria-expanded",
      isLanguageDropdownVisible.toString()
    );
    selectors.languageSwitcherDropdown?.setAttribute(
      "aria-hidden",
      (!isLanguageDropdownVisible).toString()
    );
  }

  function closeLanguageDropdown() {
    if (isLanguageDropdownVisible) {
      isLanguageDropdownVisible = false;
      selectors.siteHeader?.classList.remove("language-dropdown-visible");
      selectors.languageSwitcherToggleButton?.setAttribute(
        "aria-expanded",
        "false"
      );
      selectors.languageSwitcherDropdown?.setAttribute("aria-hidden", "true");
    }
  }

  function handleLanguageChoice(event) {
    const button = event.target.closest(".language-choice-button");
    if (!button) return;
    const chosenLang = button.dataset.lang;
    if (chosenLang && chosenLang !== currentLangCode) {
      loadLanguage(chosenLang).then(() => {
        closeLanguageDropdown();
        updateDisplay(); // Re-render with new language strings
      });
    } else if (chosenLang) {
      closeLanguageDropdown(); // Close even if same language clicked
    }
  }

  function setupHamburger() {
    if (selectors.hamburgerButton && selectors.mainNav) {
      const isMobileNavInitiallyActive =
        selectors.mainNav.classList.contains("active");
      selectors.hamburgerButton.setAttribute(
        "aria-expanded",
        isMobileNavInitiallyActive.toString()
      );
      selectors.mainNav.setAttribute(
        "aria-hidden",
        (!isMobileNavInitiallyActive).toString()
      );
      if (!isMobileNavInitiallyActive)
        selectors.mainNav.setAttribute("inert", "");

      selectors.hamburgerButton.addEventListener("click", () => {
        const isActive = selectors.mainNav.classList.toggle("active");
        selectors.hamburgerButton.classList.toggle("active");
        selectors.hamburgerButton.setAttribute(
          "aria-expanded",
          isActive.toString()
        );
        selectors.mainNav.setAttribute("aria-hidden", (!isActive).toString());
        if (isActive) selectors.mainNav.removeAttribute("inert");
        else selectors.mainNav.setAttribute("inert", "");
        document.body.classList.toggle("nav-open", isActive);
        if (!isActive && isLanguageDropdownVisible) closeLanguageDropdown();
      });

      selectors.mainNav
        .querySelectorAll(
          "a:not(#language-switcher-toggle), button:not(#language-switcher-toggle)"
        )
        .forEach((item) => {
          item.addEventListener("click", () => {
            if (selectors.mainNav.classList.contains("active")) {
              selectors.mainNav.classList.remove("active");
              selectors.hamburgerButton.classList.remove("active");
              selectors.hamburgerButton.setAttribute("aria-expanded", "false");
              selectors.mainNav.setAttribute("aria-hidden", "true");
              selectors.mainNav.setAttribute("inert", "");
              document.body.classList.remove("nav-open");
              if (isLanguageDropdownVisible) closeLanguageDropdown();
            }
          });
        });
      document.addEventListener("click", (event) => {
        if (
          selectors.mainNav && // Check if mainNav exists
          selectors.mainNav.classList.contains("active") &&
          !selectors.mainNav.contains(event.target) &&
          selectors.hamburgerButton && // Check if hamburgerButton exists
          !selectors.hamburgerButton.contains(event.target)
        ) {
          selectors.mainNav.classList.remove("active");
          selectors.hamburgerButton.classList.remove("active");
          selectors.hamburgerButton.setAttribute("aria-expanded", "false");
          selectors.mainNav.setAttribute("aria-hidden", "true");
          selectors.mainNav.setAttribute("inert", "");
          document.body.classList.remove("nav-open");
          if (isLanguageDropdownVisible) closeLanguageDropdown();
        }
      });
    }
  }

  // --- Event Listener Setup ---
  function setupEventListeners() {
    if (selectors.themeToggleButton)
      selectors.themeToggleButton.addEventListener(
        "click",
        handleDarkModeToggle
      );
    if (
      selectors.languageSwitcherToggleButton &&
      selectors.languageSwitcherDropdown
    ) {
      selectors.languageChoiceButtons = // Ensure this is populated
        selectors.languageSwitcherDropdown.querySelectorAll(
          ".language-choice-button"
        );
      selectors.languageSwitcherToggleButton.addEventListener(
        "click",
        toggleLanguageDropdown
      );
      selectors.languageSwitcherDropdown.addEventListener(
        "click",
        handleLanguageChoice
      );
      document.addEventListener("click", (event) => {
        if (
          isLanguageDropdownVisible &&
          selectors.languageSwitcherToggleButton &&
          !selectors.languageSwitcherToggleButton.contains(event.target) &&
          selectors.languageSwitcherDropdown &&
          !selectors.languageSwitcherDropdown.contains(event.target)
        ) {
          closeLanguageDropdown();
        }
      });
    }
    if (selectors.filtersContainer) {
      selectors.filtersContainer.addEventListener("input", (e) => {
        if (e.target.matches(".filter-range")) {
          handleFilterChange(e);
        }
      });
      selectors.filtersContainer.addEventListener("change", (e) => {
        if (e.target.matches(".filter-type")) {
          handleFilterChange(e);
        }
      });
    }
    if (selectors.clearFiltersButton)
      selectors.clearFiltersButton.addEventListener("click", clearAllFilters);
    if (selectors.sortButtonsContainer)
      selectors.sortButtonsContainer.addEventListener(
        "click",
        handleSortChange
      );
    if (selectors.searchInput)
      selectors.searchInput.addEventListener("input", handleSearchInput);

    setupHamburger();
  }

  function applyPersistedTheme() {
    const persistedTheme =
      localStorage.getItem("crousXAppTheme") ||
      (window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light");
    selectors.htmlElement.setAttribute("data-theme", persistedTheme);
  }

  // --- Initialization ---
  async function initialize() {
    applyPersistedTheme();
    const initialLang = getInitialLanguage();
    await loadLanguage(initialLang);

    // Initialize activeFilters and display for dual sliders
    if (
      selectors.priceRangeMinSlider &&
      selectors.priceRangeMaxSlider &&
      selectors.priceRangeValueDisplay
    ) {
      // Set slider initial values from constants if DOM values are default max/max (or min/min)
      if (
        parseInt(selectors.priceRangeMinSlider.value) ===
          parseInt(selectors.priceRangeMinSlider.max) &&
        parseInt(selectors.priceRangeMaxSlider.value) ===
          parseInt(selectors.priceRangeMaxSlider.max)
      ) {
        selectors.priceRangeMinSlider.value = INITIAL_MIN_PRICE;
        selectors.priceRangeMaxSlider.value = INITIAL_MAX_PRICE;
      }
      activeFilters.minPrice = parseInt(
        selectors.priceRangeMinSlider.value,
        10
      );
      activeFilters.maxPrice = parseInt(
        selectors.priceRangeMaxSlider.value,
        10
      );
      updateRangeValueDisplay(
        selectors.priceRangeMinSlider,
        selectors.priceRangeMaxSlider,
        selectors.priceRangeValueDisplay,
        "$"
      );
    } else {
      // Fallback if sliders are not found
      activeFilters.minPrice = INITIAL_MIN_PRICE;
      activeFilters.maxPrice = INITIAL_MAX_PRICE;
    }

    if (
      selectors.sizeRangeMinSlider &&
      selectors.sizeRangeMaxSlider &&
      selectors.sizeRangeValueDisplay
    ) {
      if (
        parseInt(selectors.sizeRangeMinSlider.value) ===
          parseInt(selectors.sizeRangeMinSlider.max) &&
        parseInt(selectors.sizeRangeMaxSlider.value) ===
          parseInt(selectors.sizeRangeMaxSlider.max)
      ) {
        selectors.sizeRangeMinSlider.value = INITIAL_MIN_SIZE;
        selectors.sizeRangeMaxSlider.value = INITIAL_MAX_SIZE;
      }
      activeFilters.minSize = parseInt(selectors.sizeRangeMinSlider.value, 10);
      activeFilters.maxSize = parseInt(selectors.sizeRangeMaxSlider.value, 10);
      updateRangeValueDisplay(
        selectors.sizeRangeMinSlider,
        selectors.sizeRangeMaxSlider,
        selectors.sizeRangeValueDisplay,
        "",
        " m²"
      );
    } else {
      // Fallback
      activeFilters.minSize = INITIAL_MIN_SIZE;
      activeFilters.maxSize = INITIAL_MAX_SIZE;
    }

    let mapInitialized = false;
    if (selectors.mapElement && typeof L !== "undefined") {
      mapInitialized = initializeMap();
    }

    setupEventListeners();
    await fetchHousingData(); // This will call updateDisplay which uses activeFilters

    if (selectors.mapContainerSticky && mapInitialized) {
      const resizeObserver = new ResizeObserver(() => invalidateMapSize());
      resizeObserver.observe(selectors.mapContainerSticky);
      setTimeout(invalidateMapSize, 300);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize);
  } else {
    initialize();
  }
})();
