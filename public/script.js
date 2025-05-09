// ==========================================
//          CROUS-X App Script
// ==========================================
// Integrates: Dark Mode, Language Switcher (from landing, fetching external JSON)
// Keeps: Filtering, Sorting, Map, Hamburger, Chatbot (from your original app)
// ==========================================

(function () {
  // IIFE to encapsulate scope

  // --- Configuration & Constants ---
  const DEFAULT_LANG = "en";
  const SUPPORTED_LANGS = ["en", "fr", "es"];
  const LANGUAGES_PATH = "./languages/"; // Path to your language JSON files
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
    priceRangeSlider: document.getElementById("price-range"),
    sizeRangeSlider: document.getElementById("size-range"),
    priceRangeValueSpan: document.getElementById("price-range-value"),
    sizeRangeValueSpan: document.getElementById("size-range-value"),
    typeCheckboxes: document.querySelectorAll(".filter-type"),
    clearFiltersButton: document.getElementById("clear-filters-btn"),
    resultsGrid: document.getElementById("results-grid"),
    sortButtonsContainer: document.querySelector(".sort-options"),
    sortButtons: document.querySelectorAll(".sort-btn"),
    searchInput: document.getElementById("search-input"),
    mapElement: document.getElementById("map"),
    resultsLayout: document.getElementById("results-layout"),
    mapContainerSticky: document.getElementById("map-container-sticky"),
    chatWidget: document.getElementById("chat-widget"),
    chatToggleButton: document.getElementById("chat-toggle-button"),
    chatContainer: document.getElementById("chat-container"),
    chatCloseButton: document.getElementById("chat-close-button"),
    chatMessages: document.getElementById("chat-messages"),
    chatInput: document.getElementById("chat-input"),
    chatSendButton: document.getElementById("chat-send-button"),
    chatLoading: document.getElementById("chat-loading"),
  };

  // --- State Management ---
  let currentLanguageData = {};
  let currentLangCode = DEFAULT_LANG;
  let isLanguageDropdownVisible = false;
  let activeFilters = {
    maxPrice: selectors.priceRangeSlider
      ? parseInt(selectors.priceRangeSlider.max, 10)
      : 10000,
    maxSize: selectors.sizeRangeSlider
      ? parseInt(selectors.sizeRangeSlider.max, 10)
      : 250,
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
      const res = await fetch("./api/getHousing.php");
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      const data = await res.json();
      window.allHousingData = data;
    } catch (e) {
      console.error("Error loading housing data:", e);
      window.allHousingData = [];
    }
    updateDisplay();
  }

  // --- Internationalization (i18n) - Fetches external JSON ---
  async function loadLanguage(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) lang = DEFAULT_LANG;
    try {
      // Fetch the language file from the LANGUAGES_PATH
      const response = await fetch(
        `${LANGUAGES_PATH}${lang}.json?v=${Date.now()}`
      ); // Cache bust
      if (!response.ok) {
        console.error(
          `Failed to fetch ${lang}.json, status: ${response.status}`
        );
        // Fallback to default language if the requested one fails
        if (lang !== DEFAULT_LANG) return loadLanguage(DEFAULT_LANG);
        // If default also fails, we might be in trouble or use a hardcoded minimal set
        currentLanguageData = {}; // Clear previous data
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
      // Attempt to load default language as a final fallback if not already trying
      if (lang !== DEFAULT_LANG && currentLangCode !== DEFAULT_LANG) {
        await loadLanguage(DEFAULT_LANG);
      } else if (
        lang === DEFAULT_LANG &&
        Object.keys(currentLanguageData).length === 0
      ) {
        // If default language itself failed to load, log and potentially show error to user
        console.error("CRITICAL: Default language file could not be loaded.");
        // You could set a very minimal hardcoded English set here as an ultimate fallback
        // currentLanguageData = { "error_loading_lang": "Error loading language." };
        // applyTranslations();
      }
    }
  }

  function applyTranslations() {
    if (!currentLanguageData || Object.keys(currentLanguageData).length === 0) {
      console.warn("No language data loaded to apply translations.");
      return;
    }
    document.querySelectorAll("[data-lang-key]").forEach((el) => {
      const key = el.dataset.langKey;
      let translation = currentLanguageData[key] || `[${key}]`; // Show key if translation missing
      if (key === "footer_copyright_main" || key === "footer_copyright") {
        // Example for dynamic content
        translation = translation.replace("{year}", new Date().getFullYear());
      }
      el.textContent = translation;
    });
    document.querySelectorAll("[data-lang-key-placeholder]").forEach((el) => {
      const key = el.dataset.langKeyPlaceholder;
      if (currentLanguageData[key] !== undefined)
        el.placeholder = currentLanguageData[key];
      else el.placeholder = `[${key}]`;
    });
    // Add more for aria-label, title if needed
    document.querySelectorAll("[data-lang-key-aria-label]").forEach((el) => {
      const key = el.getAttribute("data-lang-key-aria-label");
      if (currentLanguageData[key] !== undefined)
        el.setAttribute("aria-label", currentLanguageData[key]);
      else el.setAttribute("aria-label", `[${key}]`);
    });
    document.querySelectorAll("[data-lang-key-title]").forEach((el) => {
      const key = el.getAttribute("data-lang-key-title");
      if (currentLanguageData[key] !== undefined)
        el.title = currentLanguageData[key];
      else el.title = `[${key}]`;
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
            `filter_type_${item.property_type?.toLowerCase().replace(" ", "_")}`
          ] || item.property_type;
        const ratingText = currentLanguageData?.rating_prefix || "Rating";
        const priceText = currentLanguageData?.price_prefix || "Price"; // Assuming you might add this key
        const perMonthText = currentLanguageData?.per_month_suffix || "/month";

        marker.bindPopup(
          `<b>${item.title}</b><br>${typeText}<br>${priceText}: $${
            item.rent_amount
          }${perMonthText}<br>${ratingText}: ${item.rating ?? "N/A"} ★`
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
      applyTranslations(); // Apply translation for the "no results" message
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
          `filter_type_${item.property_type?.toLowerCase().replace(" ", "_")}`
        ] || item.property_type;

      card.innerHTML = `
        <div class="card-image-placeholder">
          ${
            item.image
              ? `<img src="${
                  item.image.startsWith("http")
                    ? item.image
                    : "./uploads/" + item.image
                }" alt="${item.title}" loading="lazy">`
              : `<i class="far fa-image"></i>`
          }
        </div>
        <div class="card-content">
          <h4 class="card-title">${item.title} (${propertyTypeText})</h4>
          <p class="card-price">$${item.rent_amount}${perMonthText}</p>
          <p class="card-size">${sizeText}: ${item.square_footage} m²</p>
          <p class="card-rating">${ratingText}: ${
        item.rating ?? "N/A"
      } <i class="fas fa-star"></i></p>
        </div>`;
      link.appendChild(card);
      selectors.resultsGrid.appendChild(link);
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
      const searchMatch =
        !term ||
        item.title.toLowerCase().includes(term) ||
        (item.address && item.address.toLowerCase().includes(term));
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
        sorted.sort((a, b) => (b.listing_id || 0) - (a.listing_id || 0));
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

  function handleFilterChange() {
    if (selectors.priceRangeSlider)
      activeFilters.maxPrice = parseInt(selectors.priceRangeSlider.value, 10);
    if (selectors.sizeRangeSlider)
      activeFilters.maxSize = parseInt(selectors.sizeRangeSlider.value, 10);
    activeFilters.types = [];
    if (selectors.typeCheckboxes) {
      selectors.typeCheckboxes.forEach((cb) => {
        if (cb.checked) activeFilters.types.push(cb.value);
      });
    }
    updateSliderValueDisplay(
      selectors.priceRangeSlider,
      selectors.priceRangeValueSpan,
      "$"
    );
    updateSliderValueDisplay(
      selectors.sizeRangeSlider,
      selectors.sizeRangeValueSpan,
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
    if (selectors.priceRangeSlider) {
      selectors.priceRangeSlider.value = selectors.priceRangeSlider.max;
      activeFilters.maxPrice = parseInt(selectors.priceRangeSlider.max, 10);
      updateSliderValueDisplay(
        selectors.priceRangeSlider,
        selectors.priceRangeValueSpan,
        "$"
      );
    }
    if (selectors.sizeRangeSlider) {
      selectors.sizeRangeSlider.value = selectors.sizeRangeSlider.max;
      activeFilters.maxSize = parseInt(selectors.sizeRangeSlider.max, 10);
      updateSliderValueDisplay(
        selectors.sizeRangeSlider,
        selectors.sizeRangeValueSpan,
        "",
        " m²"
      );
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

  function toggleLanguageDropdown(event) {
    event.stopPropagation();
    isLanguageDropdownVisible = !isLanguageDropdownVisible;
    selectors.siteHeader?.classList.toggle(
      "language-dropdown-visible",
      isLanguageDropdownVisible
    );
    selectors.languageSwitcherToggleButton?.setAttribute(
      "aria-expanded",
      isLanguageDropdownVisible
    );
    selectors.languageSwitcherDropdown?.setAttribute(
      "aria-hidden",
      !isLanguageDropdownVisible
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
        // Update dynamic parts of UI that depend on language, e.g., re-render cards
        updateDisplay(); // Re-render cards and map popups with new language
      });
    } else if (chosenLang) {
      closeLanguageDropdown();
    }
  }

  // Hamburger Menu Logic (from your original script, ensure selectors.mainNav is correct for app)
  function setupHamburger() {
    if (selectors.hamburgerButton && selectors.mainNav) {
      // Initial ARIA setup
      const isMobileNavInitiallyActive =
        selectors.mainNav.classList.contains("active"); // Or however you track active
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
        if (isActive) {
          selectors.mainNav.removeAttribute("inert");
        } else {
          selectors.mainNav.setAttribute("inert", "");
          // If language dropdown was open inside mobile nav, close it
          if (isLanguageDropdownVisible) closeLanguageDropdown();
        }
        // Toggle body class to prevent scrolling when mobile nav is open
        document.body.classList.toggle("nav-open", isActive);
      });

      // Close mobile nav when a link inside it is clicked (excluding language toggle itself)
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

      // Close mobile nav if clicked outside
      document.addEventListener("click", (event) => {
        if (
          selectors.mainNav.classList.contains("active") &&
          !selectors.mainNav.contains(event.target) &&
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
          !selectors.languageSwitcherToggleButton.contains(event.target) &&
          !selectors.languageSwitcherDropdown.contains(event.target)
        ) {
          closeLanguageDropdown();
        }
      });
    }
    if (selectors.filtersContainer) {
      selectors.filtersContainer.addEventListener("input", (e) => {
        if (e.target.matches("#price-range, #size-range")) handleFilterChange();
      });
      selectors.filtersContainer.addEventListener("change", (e) => {
        if (e.target.matches(".filter-type")) handleFilterChange();
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

    setupHamburger(); // Call hamburger setup

    // Chat
    if (selectors.chatToggleButton && selectors.chatContainer) {
      selectors.chatToggleButton.addEventListener("click", () => {
        constisHidden = selectors.chatContainer.classList.toggle("chat-hidden");
        selectors.chatToggleButton.setAttribute("aria-expanded", !isHidden);
        if (!isHidden && selectors.chatInput) selectors.chatInput.focus();
      });
    }
    if (selectors.chatCloseButton && selectors.chatContainer) {
      selectors.chatCloseButton.addEventListener("click", () => {
        selectors.chatContainer.classList.add("chat-hidden");
        selectors.chatToggleButton.setAttribute("aria-expanded", "false");
      });
    }
    // Chat send logic from your chatbot.js should be integrated or called here
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

    if (selectors.priceRangeSlider && selectors.priceRangeValueSpan) {
      activeFilters.maxPrice = parseInt(selectors.priceRangeSlider.value, 10);
      updateSliderValueDisplay(
        selectors.priceRangeSlider,
        selectors.priceRangeValueSpan,
        "$"
      );
    }
    if (selectors.sizeRangeSlider && selectors.sizeRangeValueSpan) {
      activeFilters.maxSize = parseInt(selectors.sizeRangeSlider.value, 10);
      updateSliderValueDisplay(
        selectors.sizeRangeSlider,
        selectors.sizeRangeValueSpan,
        "",
        " m²"
      );
    }

    let mapInitialized = false;
    if (selectors.mapElement && typeof L !== "undefined") {
      mapInitialized = initializeMap();
    }

    setupEventListeners();
    await fetchHousingData(); // This calls updateDisplay internally

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
