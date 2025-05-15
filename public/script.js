(function () {
  // IIFE to encapsulate scope

  // --- Configuration & Constants ---
  const DEFAULT_LANG = "en";
  const SUPPORTED_LANGS = ["en", "fr", "es"];
  const LANGUAGES_PATH = "./languages/";
  const MAP_INITIAL_COORDS = [48.8566, 2.3522];
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
  const PRICE_STEP = 50;

  const INITIAL_MIN_SIZE = 9;
  const INITIAL_MAX_SIZE = 250;
  const SIZE_STEP = 1;

  // --- DOM Element Selection ---
  // We will define these inside initialize after DOMContentLoaded to ensure elements exist
  let selectors = {};

  // --- State Management ---
  let currentLanguageData = {};
  let currentLangCode = DEFAULT_LANG;
  let isLanguageDropdownVisible = false;
  let activeFilters = {
    minPrice: INITIAL_MIN_PRICE,
    maxPrice: INITIAL_MAX_PRICE,
    minSize: INITIAL_MIN_SIZE,
    maxSize: INITIAL_MAX_SIZE,
    types: [],
    searchTerm: "",
  };
  let activeSort = "new";
  let map = null;
  let markersLayer = null;
  window.allHousingData = [];

  // noUiSlider instances
  let priceSliderInstance = null;
  let sizeSliderInstance = null;

  function populateSelectors() {
    selectors = {
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
      languageChoiceButtons: null, // Will be populated in setupEventListeners
      filtersContainer: document.getElementById("filters-container"),
      priceSliderElement: document.getElementById("price-slider"),
      priceRangeValueDisplay: document.getElementById(
        "price-range-value-display"
      ),
      sizeSliderElement: document.getElementById("size-slider"),
      sizeRangeValueDisplay: document.getElementById(
        "size-range-value-display"
      ),
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
  }

  // --- Data Fetching, i18n, Map Functions ---
  async function fetchHousingData() {
    try {
      const res = await fetch("./api/getHousing.php");
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      const data = await res.json();
      window.allHousingData = data;
    } catch (e) {
      console.error("Error loading housing data:", e);
      if (selectors.resultsGrid) {
        selectors.resultsGrid.innerHTML = `<p data-lang-key="error_loading_listings_app">Error loading listings. Please try again later.</p>`;
        applyTranslations();
      }
      window.allHousingData = [];
    }
    updateDisplay();
  }
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
      // console.warn("No language data loaded to apply translations.");
      return;
    }
    document.querySelectorAll("[data-lang-key]").forEach((el) => {
      const key = el.dataset.langKey;
      let translation =
        currentLanguageData[key] || el.textContent || `[${key}]`; // Keep existing text as fallback
      if (key === "footer_copyright_main")
        translation = translation.replace("{year}", new Date().getFullYear());
      el.textContent = translation;
    });
    document.querySelectorAll("[data-lang-key-placeholder]").forEach((el) => {
      const key = el.dataset.langKeyPlaceholder;
      el.placeholder =
        currentLanguageData[key] !== undefined
          ? currentLanguageData[key]
          : el.placeholder || `[${key}]`;
    });
    document.querySelectorAll("[data-lang-key-aria-label]").forEach((el) => {
      const key = el.getAttribute("data-lang-key-aria-label");
      el.setAttribute(
        "aria-label",
        currentLanguageData[key] !== undefined
          ? currentLanguageData[key]
          : el.getAttribute("aria-label") || `[${key}]`
      );
    });
    document.querySelectorAll("[data-lang-key-title]").forEach((el) => {
      const key = el.getAttribute("data-lang-key-title");
      el.title =
        currentLanguageData[key] !== undefined
          ? currentLanguageData[key]
          : el.title || `[${key}]`;
    });
  }

  function updateLanguageSwitcherState(lang) {
    if (selectors.htmlElement) selectors.htmlElement.lang = lang;
    if (selectors.languageChoiceButtons) {
      selectors.languageChoiceButtons.forEach((button) => {
        button.classList.toggle("active", button.dataset.lang === lang);
      });
    }
  }

  function getInitialLanguage() {
    const savedLang = localStorage.getItem("crousXAppLang");
    if (savedLang && SUPPORTED_LANGS.includes(savedLang)) return savedLang;
    const browserLang = navigator.language.split("-")[0];
    if (SUPPORTED_LANGS.includes(browserLang)) return browserLang;
    return DEFAULT_LANG;
  }
  function initializeMap() {
    if (!selectors.mapElement || typeof L === "undefined") {
      // console.warn("Map element or Leaflet library not found.");
      return false;
    }
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
              .replace(/\s+/g, "_")}`
          ] || item.property_type;
        const ratingText = currentLanguageData?.rating_prefix || "Rating";
        const priceText = currentLanguageData?.price_prefix || "Price";
        const perMonthText = currentLanguageData?.per_month_suffix || "/month";
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
      const noResultsContainer = document.createElement("div");
      noResultsContainer.className = "no-results-found";
      const icon = document.createElement("i");
      icon.className = "fas fa-search-minus no-results-icon";
      const messageHeading = document.createElement("h4");
      messageHeading.setAttribute("data-lang-key", "no_results_heading_app");
      messageHeading.textContent = "No Matches Found";
      const messageText = document.createElement("p");
      messageText.setAttribute("data-lang-key", "no_results_suggestion_app");
      messageText.textContent = "Try adjusting your filters or search term.";
      noResultsContainer.appendChild(icon);
      noResultsContainer.appendChild(messageHeading);
      noResultsContainer.appendChild(messageText);
      selectors.resultsGrid.appendChild(noResultsContainer);
      applyTranslations();
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
      const propertyTypeKey = `filter_type_${item.property_type
        ?.toLowerCase()
        .replace(/\s+/g, "_")}`;
      const propertyTypeText =
        currentLanguageData[propertyTypeKey] || item.property_type;
      card.innerHTML = `
        <div class="card-image-placeholder">
          ${
            item.primary_image
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

  function updateNoUiSliderDisplay(
    values,
    displaySpan,
    prefix = "",
    suffix = ""
  ) {
    if (displaySpan && values && values.length === 2) {
      const minValue = Math.round(parseFloat(values[0]));
      const maxValue = Math.round(parseFloat(values[1]));
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
      default:
        sorted.sort(
          (a, b) =>
            (parseInt(b.listing_id) || 0) - (parseInt(a.listing_id) || 0)
        );
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
      if (map && markersLayer) renderMapMarkers(filtered);
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
    if (map) invalidateMapSize();
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
        updateDisplay();
      });
    } else if (chosenLang) {
      closeLanguageDropdown();
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
          selectors.mainNav &&
          selectors.mainNav.classList.contains("active") &&
          !selectors.mainNav.contains(event.target) &&
          selectors.hamburgerButton &&
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

  function handleCheckboxFilterChange() {
    activeFilters.types = [];
    if (selectors.typeCheckboxes) {
      selectors.typeCheckboxes.forEach((cb) => {
        if (cb.checked) activeFilters.types.push(cb.value);
      });
    }
    updateDisplay();
  }

  function clearAllFilters() {
    if (priceSliderInstance) {
      priceSliderInstance.set([INITIAL_MIN_PRICE, INITIAL_MAX_PRICE]);
    } else {
      activeFilters.minPrice = INITIAL_MIN_PRICE;
      activeFilters.maxPrice = INITIAL_MAX_PRICE;
      if (selectors.priceRangeValueDisplay) {
        // Check if display element exists
        updateNoUiSliderDisplay(
          [INITIAL_MIN_PRICE, INITIAL_MAX_PRICE],
          selectors.priceRangeValueDisplay,
          "$"
        );
      }
    }

    if (sizeSliderInstance) {
      sizeSliderInstance.set([INITIAL_MIN_SIZE, INITIAL_MAX_SIZE]);
    } else {
      activeFilters.minSize = INITIAL_MIN_SIZE;
      activeFilters.maxSize = INITIAL_MAX_SIZE;
      if (selectors.sizeRangeValueDisplay) {
        // Check if display element exists
        updateNoUiSliderDisplay(
          [INITIAL_MIN_SIZE, INITIAL_MAX_SIZE],
          selectors.sizeRangeValueDisplay,
          "",
          " m²"
        );
      }
    }

    if (selectors.typeCheckboxes)
      selectors.typeCheckboxes.forEach((cb) => (cb.checked = false));
    activeFilters.types = [];
    if (selectors.searchInput) selectors.searchInput.value = "";
    activeFilters.searchTerm = "";
    activeSort = "new";
    if (selectors.sortButtons) {
      selectors.sortButtons.forEach((btn) =>
        btn.classList.toggle("active", btn.dataset.sort === "new")
      );
    }
    updateDisplay();
  }

  // --- noUiSlider Initialization ---
  function initializeNoUiSliders() {
    if (typeof noUiSlider === "undefined") {
      console.error(
        "noUiSlider library is not loaded. Sliders will not function."
      );
      return false; // Indicate failure
    }

    if (selectors.priceSliderElement) {
      try {
        priceSliderInstance = noUiSlider.create(selectors.priceSliderElement, {
          start: [activeFilters.minPrice, activeFilters.maxPrice],
          connect: true,
          step: PRICE_STEP,
          range: {
            min: INITIAL_MIN_PRICE,
            max: INITIAL_MAX_PRICE,
          },
          format: {
            to: function (value) {
              return Math.round(value);
            },
            from: function (value) {
              return Number(value);
            },
          },
          // Accessibility
          pips: { mode: "steps", density: 4, filter: () => -1 }, // Hidden pips for screen readers
          tooltips: [true, true], // Show tooltips
        });
        priceSliderInstance.on("update", function (values) {
          activeFilters.minPrice = parseFloat(values[0]);
          activeFilters.maxPrice = parseFloat(values[1]);
          updateNoUiSliderDisplay(
            values,
            selectors.priceRangeValueDisplay,
            "$"
          );
          updateDisplay();
        });
      } catch (e) {
        console.error(
          "Failed to initialize price slider:",
          e,
          selectors.priceSliderElement
        );
        priceSliderInstance = null;
      }
    } else {
      // console.warn("Price slider element not found.");
    }

    if (selectors.sizeSliderElement) {
      try {
        sizeSliderInstance = noUiSlider.create(selectors.sizeSliderElement, {
          start: [activeFilters.minSize, activeFilters.maxSize],
          connect: true,
          step: SIZE_STEP,
          range: {
            min: INITIAL_MIN_SIZE,
            max: INITIAL_MAX_SIZE,
          },
          format: {
            to: function (value) {
              return Math.round(value);
            },
            from: function (value) {
              return Number(value);
            },
          },
          pips: { mode: "steps", density: 4, filter: () => -1 },
          tooltips: [true, true],
        });
        sizeSliderInstance.on("update", function (values) {
          activeFilters.minSize = parseFloat(values[0]);
          activeFilters.maxSize = parseFloat(values[1]);
          updateNoUiSliderDisplay(
            values,
            selectors.sizeRangeValueDisplay,
            "",
            " m²"
          );
          updateDisplay();
        });
      } catch (e) {
        console.error(
          "Failed to initialize size slider:",
          e,
          selectors.sizeSliderElement
        );
        sizeSliderInstance = null;
      }
    } else {
      // console.warn("Size slider element not found.");
    }
    return true; // Indicate success or partial success
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
      // Populate selectors.languageChoiceButtons here as dropdown is now certainly in DOM
      selectors.languageChoiceButtons =
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
      selectors.filtersContainer.addEventListener("change", (e) => {
        if (e.target.matches(".filter-type")) {
          handleCheckboxFilterChange();
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
    if (!selectors.htmlElement) return; // Guard clause
    const persistedTheme =
      localStorage.getItem("crousXAppTheme") ||
      (window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light");
    selectors.htmlElement.setAttribute("data-theme", persistedTheme);
  }

  // --- Initialization ---
  async function initialize() {
    populateSelectors(); // Populate selectors object now that DOM is ready
    applyPersistedTheme();
    const initialLang = getInitialLanguage();
    await loadLanguage(initialLang); // This calls applyTranslations

    // Initialize noUiSliders
    const slidersInitialized = initializeNoUiSliders();

    // Set initial display for sliders, especially if noUiSlider failed or for immediate UI update
    if (selectors.priceRangeValueDisplay) {
      updateNoUiSliderDisplay(
        [activeFilters.minPrice, activeFilters.maxPrice], // Use current activeFilters values
        selectors.priceRangeValueDisplay,
        "$"
      );
    }
    if (selectors.sizeRangeValueDisplay) {
      updateNoUiSliderDisplay(
        [activeFilters.minSize, activeFilters.maxSize],
        selectors.sizeRangeValueDisplay,
        "",
        " m²"
      );
    }

    handleCheckboxFilterChange(); // Process initial checkbox state and trigger display update

    let mapInitialized = false;
    if (selectors.mapElement && typeof L !== "undefined") {
      mapInitialized = initializeMap();
    }

    setupEventListeners();
    await fetchHousingData(); // This will call updateDisplay

    if (selectors.mapContainerSticky && mapInitialized) {
      const resizeObserver = new ResizeObserver(() => invalidateMapSize());
      resizeObserver.observe(selectors.mapContainerSticky);
      setTimeout(invalidateMapSize, 300);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize);
  } else {
    initialize(); // Or setTimeout(initialize, 0) for extreme cases
  }
})();
