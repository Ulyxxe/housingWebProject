// --------------------------------------------------
//                CROUS-X Script
// --------------------------------------------------
// Includes: Dark Mode, Filtering (Sidebar & Search),
// Sorting, Leaflet Map with Marker Clustering,
// Custom Red Circle Map Markers.
// --------------------------------------------------

// --- Dark Mode Toggle ---
const themeToggleButton = document.getElementById("theme-toggle");
if (themeToggleButton) {
    themeToggleButton.addEventListener("click", () => {
        document.body.classList.toggle("dark-mode");
        // Optional: Change icon based on mode
        const icon = themeToggleButton.querySelector('i');
        if (document.body.classList.contains('dark-mode')) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    });
}

// --- Sample Housing Data ---
// IMPORTANT: Replace placeholder lat/lng coordinates with real data!
const allHousingData = [
    // Placeholder coordinates roughly around Paris
    { id: 10, name: "Campus Tower Studio", price: 850, size: 25, type: "Studio", image: null, rating: 4.2, lat: 48.858, lng: 2.294 },
    { id: 9, name: "Downtown Apartment", price: 1200, size: 60, type: "Apartment", image: null, rating: 4.5, lat: 48.860, lng: 2.337 },
    { id: 8, name: "Riverside Shared Room", price: 600, size: 18, type: "Shared Room", image: null, rating: 3.8, lat: 48.853, lng: 2.349 },
    { id: 7, name: "West End House", price: 2500, size: 150, type: "House", image: null, rating: 4.9, lat: 48.869, lng: 2.307 },
    { id: 6, name: "Compact Studio Near Uni", price: 780, size: 22, type: "Studio", image: null, rating: 4.0, lat: 48.846, lng: 2.344 },
    { id: 5, name: "Spacious 2BR Apartment", price: 1600, size: 85, type: "Apartment", image: null, rating: 4.7, lat: 48.873, lng: 2.359 },
    { id: 4, name: "Budget Share House", price: 550, size: 15, type: "Shared Room", image: null, rating: 3.5, lat: 48.886, lng: 2.343 },
    { id: 3, name: "Modern Apartment", price: 1350, size: 70, type: "Apartment", image: null, rating: 4.6, lat: 48.845, lng: 2.372 },
    { id: 2, name: "Large Family House", price: 3200, size: 200, type: "House", image: null, rating: 4.8, lat: 48.838, lng: 2.270 },
    { id: 1, name: "City Center Studio", price: 950, size: 30, type: "Studio", image: null, rating: 4.3, lat: 48.865, lng: 2.320 },
    { id: 11, name: "Quiet House Room", price: 700, size: 20, type: "Shared Room", image: null, rating: 3.9, lat: 48.820, lng: 2.355 },
    { id: 1, name: "KFC Gare de l'Est", address: "31-35 boulevard de Sébastopol, 75001 Paris, France", lat: 48.8625, lng: 2.3491 },
    { id: 2, name: "KFC Place de Clichy", address: "10 bis place de Clichy, 75018 Paris, France", lat: 48.8837, lng: 2.3266 },
    { id: 3, name: "KFC Ménilmontant", address: "150 boulevard de Ménilmontant, 75020 Paris, France", lat: 48.8650, lng: 2.3890 },
    { id: 4, name: "KFC Montparnasse", address: "4 avenue Jean Moulin, 75014 Paris, France", lat: 48.8322, lng: 2.3250 },
    { id: 5, name: "KFC Gare de l'Est", address: "Proche de la station de métro Gare de l'Est, Paris, France", lat: 48.8765, lng: 2.3574 }
];

// --- DOM Element Selection ---
const resultsGrid = document.getElementById('results-grid');
const priceRangeSlider = document.getElementById('price-range');
const sizeRangeSlider = document.getElementById('size-range');
const priceRangeValueSpan = document.getElementById('price-range-value');
const sizeRangeValueSpan = document.getElementById('size-range-value');
const typeCheckboxes = document.querySelectorAll('.filter-type');
const clearFiltersButton = document.getElementById('clear-filters-btn');
const filtersContainer = document.getElementById('filters-container');
const sortButtonsContainer = document.querySelector('.sort-options');
const sortButtons = document.querySelectorAll('.sort-btn');
const searchInput = document.getElementById('search-input');
const mapElement = document.getElementById('map'); // Map container

// --- State Management ---
let activeFilters = {
    // Initialize with HTML default values or reasonable max values
    maxPrice: priceRangeSlider ? parseInt(priceRangeSlider.value, 10) : 10000,
    maxSize: sizeRangeSlider ? parseInt(sizeRangeSlider.value, 10) : 250,
    types: [],
    searchTerm: ''
};
let activeSort = 'new'; // Initial sort state
let map = null; // Holds the Leaflet map instance
let markersLayer = null; // Holds the Leaflet MarkerClusterGroup layer

// --- Map Initialization ---
function initializeMap() {
    if (!mapElement) {
        console.error("Map container element not found.");
        return;
    }
    if (typeof L === 'undefined') {
        console.error("Leaflet library (L) not loaded.");
        return;
    }
    // Check if map is already initialized
    if (map) {
        map.remove();
    }

    // Set initial view coordinates (e.g., center of Paris) and zoom level
    const initialCoords = [48.8566, 2.3522];
    const initialZoom = 12;

    map = L.map(mapElement).setView(initialCoords, initialZoom);

    // Add Tile Layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Initialize the MarkerCluster group if the library is loaded
    if (typeof L.markerClusterGroup === 'function') {
        markersLayer = L.markerClusterGroup();
        map.addLayer(markersLayer);
    } else {
        console.warn("Leaflet.markercluster library not loaded. Markers will not be clustered.");
        // Fallback: Use a regular layer group if markercluster is missing
        markersLayer = L.layerGroup();
        map.addLayer(markersLayer);
    }
}

// --- Render Map Markers (Uses Custom Icons) ---
function renderMapMarkers(housingToDisplay) {
    if (!map || !markersLayer) {
        // console.warn("Map or markers layer not initialized for rendering markers.");
        return;
    }
    if (typeof L === 'undefined') {
        console.error("Leaflet library (L) not loaded for rendering markers.");
        return;
    }

    markersLayer.clearLayers(); // Clear previous markers

    // --- Define our custom icon using L.DivIcon ---
    // This references the '.custom-div-icon' CSS class for styling
    const customMarkerIcon = L.divIcon({
        className: 'custom-div-icon', // CSS class for styling
        html: '',                     // No specific HTML content inside the marker div itself
        iconSize: [24, 24],           // Size of the icon [width, height] in pixels
        iconAnchor: [12, 12],         // Point of the icon which corresponds to marker's location (center for a circle)
        popupAnchor: [0, -12]         // Point from which the popup should open relative to the iconAnchor
    });
    // --- End Custom Icon Definition ---

    // Create markers for the listings to display
    housingToDisplay.forEach(item => {
        if (item.lat == null || item.lng == null) {
            return; // Skip items without coordinates
        }

        // --- Create the marker using the custom icon ---
        const marker = L.marker([item.lat, item.lng], { icon: customMarkerIcon }); // Pass the icon option

        // Define popup content
        const popupContent = `
            <b>${item.name}</b><br>
            Type: ${item.type}<br>
            Price: $${item.price}/month<br>
            Rating: ${item.rating || 'N/A'} ★
        `;
        marker.bindPopup(popupContent);

        // Add the marker (with custom icon) to the layer group (cluster or regular)
        markersLayer.addLayer(marker);
    });
}

// --- Render Housing Grid ---
function renderHousing(housingToDisplay) {
    if (!resultsGrid) {
        console.error("Results grid container not found!");
        return;
    }
    resultsGrid.innerHTML = ''; // Clear previous listings

    if (housingToDisplay.length === 0) {
        resultsGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center;">No housing found matching your criteria.</p>';
        return;
    }

    // Create and append new listing cards
    housingToDisplay.forEach(item => {
        const card = document.createElement('article');
        card.className = 'result-card';
        card.innerHTML = `
            <div class="card-image-placeholder">
                <i class="far fa-image"></i>
                <!-- Future: <img src="${item.image || 'placeholder.jpg'}" alt="${item.name}"> -->
            </div>
            <div class="card-content">
                <h4 class="card-title">${item.name} (${item.type})</h4>
                <p class="card-price">$${item.price}/month</p>
                <p class="card-size" style="font-size: 0.9em; color: var(--light-text);">Size: ${item.size} m²</p>
                <p class="card-rating" style="font-size: 0.9em; color: var(--light-text);">Rating: ${item.rating || 'N/A'} ★</p>
            </div>
        `;
        resultsGrid.appendChild(card);
    });
}

// --- Filtering Logic ---
function filterHousing() {
    let filteredList = [...allHousingData];
    const searchTerm = activeFilters.searchTerm.toLowerCase().trim();

    // Apply sidebar filters
    if (activeFilters.maxPrice !== null) {
        filteredList = filteredList.filter(item => item.price <= activeFilters.maxPrice);
    }
    if (activeFilters.maxSize !== null) {
        filteredList = filteredList.filter(item => item.size <= activeFilters.maxSize);
    }
    if (activeFilters.types.length > 0) {
        filteredList = filteredList.filter(item => activeFilters.types.includes(item.type));
    }

    // Apply search term filter
    if (searchTerm) {
        filteredList = filteredList.filter(item =>
            item.name.toLowerCase().includes(searchTerm)
        );
    }

    return filteredList;
}

// --- Sorting Logic ---
function sortHousing(housingList, sortBy) {
    const sortedList = [...housingList]; // Work on a copy

    switch (sortBy) {
        case 'price-asc':
            sortedList.sort((a, b) => a.price - b.price);
            break;
        case 'price-desc':
            sortedList.sort((a, b) => b.price - a.price);
            break;
        case 'rating':
            // Sort by rating descending (higher rating first), treat null/undefined as lowest
            sortedList.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            break;
        case 'new':
        default: // Default to 'new'
            // Sort by ID descending (assuming higher ID is newer)
            sortedList.sort((a, b) => b.id - a.id);
            break;
    }
    return sortedList;
}

// --- Update Display (Grid & Map) ---
// Central function to update both views based on filters and sort
function updateDisplay() {
    const filteredResults = filterHousing(); // Apply filters (including search)
    const sortedAndFilteredResults = sortHousing(filteredResults, activeSort); // Apply sorting

    // Update the grid with sorted & filtered results
    renderHousing(sortedAndFilteredResults);

    // Update the map markers with *filtered* results (sorting doesn't change *which* markers appear)
    renderMapMarkers(filteredResults);
}

// --- Event Handlers ---

// Handles changes in sidebar filters (sliders, checkboxes)
function handleFilterChange() {
    // Update state from sliders
    if (priceRangeSlider) {
      activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
      if(priceRangeValueSpan) priceRangeValueSpan.textContent = `$${activeFilters.maxPrice}`;
    }
    if (sizeRangeSlider) {
      activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);
      if(sizeRangeValueSpan) sizeRangeValueSpan.textContent = `${activeFilters.maxSize} m²`;
    }
    // Update state from checkboxes
    activeFilters.types = [];
    typeCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            activeFilters.types.push(checkbox.value);
        }
    });

    updateDisplay(); // Re-filter, re-sort, re-render grid & map
}

// Handles clicks on sort buttons
function handleSortChange(newSortValue) {
    if (activeSort === newSortValue) return; // No change needed
    activeSort = newSortValue;

    // Update button active states
    sortButtons.forEach(button => {
        button.classList.toggle('active', button.dataset.sort === activeSort);
    });

    updateDisplay(); // Re-filter, re-sort, re-render grid & map
}

// Handles input in the search bar
function handleSearchInput() {
    if (!searchInput) return;
    activeFilters.searchTerm = searchInput.value; // Update search term state
    updateDisplay(); // Re-filter, re-sort, re-render grid & map
}

// Handles click on the "Clear Filters" button
function clearAllFilters() {
     // Reset sliders to max and update state/display
    if (priceRangeSlider) {
        priceRangeSlider.value = priceRangeSlider.max;
        activeFilters.maxPrice = parseInt(priceRangeSlider.max, 10);
        if (priceRangeValueSpan) priceRangeValueSpan.textContent = `$${activeFilters.maxPrice}`;
    }
     if (sizeRangeSlider) {
        sizeRangeSlider.value = sizeRangeSlider.max;
        activeFilters.maxSize = parseInt(sizeRangeSlider.max, 10);
        if (sizeRangeValueSpan) sizeRangeValueSpan.textContent = `${sizeRangeSlider.max} m²`; // Fix: use max value here
    }

    // Uncheck checkboxes and clear state
    typeCheckboxes.forEach(checkbox => checkbox.checked = false);
    activeFilters.types = [];

    // Clear search input and state
    if (searchInput) {
        searchInput.value = '';
    }
    activeFilters.searchTerm = '';

    // Reset sorting to default ('new') and update buttons
    activeSort = 'new';
    sortButtons.forEach(button => {
        button.classList.toggle('active', button.dataset.sort === 'new');
    });

    updateDisplay(); // Update display to show all listings with default sort
    console.log("Filters Cleared, Sort Reset, Search Cleared");
}

// --- Add Event Listeners ---

// Sidebar Filter Listeners (using event delegation)
if (filtersContainer) {
    filtersContainer.addEventListener('input', (event) => { // Range sliders update on input
        if (event.target.classList.contains('filter-range')) {
            handleFilterChange();
        }
    });
    filtersContainer.addEventListener('change', (event) => { // Checkboxes update on change
        if (event.target.classList.contains('filter-type')) {
            handleFilterChange();
        }
    });
} else {
    console.error("Filters container not found for event listeners.");
}

// Sort Button Listeners (using event delegation)
if (sortButtonsContainer) {
    sortButtonsContainer.addEventListener('click', (event) => {
        const button = event.target.closest('.sort-btn'); // Find the clicked button
        if (button && button.dataset.sort) { // Check if it's a sort button with data-sort
            handleSortChange(button.dataset.sort);
        }
    });
} else {
    console.error("Sort buttons container not found for event listeners.");
}

// Search Input Listener
if (searchInput) {
    // Use 'input' event for dynamic filtering as user types
    searchInput.addEventListener('input', handleSearchInput);
} else {
    console.error("Search input element not found.");
}

// Clear Button Listener
if (clearFiltersButton) {
    clearFiltersButton.addEventListener('click', clearAllFilters);
}

// --- Initial Page Load ---
document.addEventListener('DOMContentLoaded', () => {
    // Initialize the Leaflet map
    initializeMap();

    // Ensure initial filter state matches HTML slider values if they exist
    if (priceRangeSlider) activeFilters.maxPrice = parseInt(priceRangeSlider.value, 10);
    if (sizeRangeSlider) activeFilters.maxSize = parseInt(sizeRangeSlider.value, 10);

    // Set initial display values for sliders
    if (priceRangeSlider && priceRangeValueSpan) {
         priceRangeValueSpan.textContent = `$${priceRangeSlider.value}`;
    }
    if (sizeRangeSlider && sizeRangeValueSpan) {
         sizeRangeValueSpan.textContent = `${sizeRangeSlider.value} m²`;
    }

    // Initial render of grid and map markers based on default filters/sort
    updateDisplay();
});