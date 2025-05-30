<!DOCTYPE html>
<!-- Default lang to 'en', JS will update if needed -->
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CROUS-X | Student Housing Reimagined</title>
    <meta
      name="description"
      content="Find your perfect student accommodation with CROUS-X. Easy search, verified listings, and a supportive community."
    />
    <link rel="icon" href="images/crous-x-icon.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="landing/landing-style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap"
      rel="stylesheet"
    />
    <!-- Font Awesome for icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
  </head>

  <body>
    <header class="site-header">
      <div class="header-inner">
        <div class="header-group-left">
          <div class="logo">
            <a href="#hero" aria-label="Scroll to top">CROUS-X</a>
          </div>
          <nav class="main-nav">
            <ul>
              <!-- Add data-lang-key attributes for dynamic text (if implementing full i18n) -->
              <li>
                <a href="#features" data-lang-key="nav_features">Features</a>
              </li>
              <li>
                <a href="#how-it-works" data-lang-key="nav_process">Process</a>
              </li>
              <li>
                <a href="#contact" data-lang-key="nav_contact">Contact</a>
              </li>
            </ul>
          </nav>
        </div>

        <div class="header-group-right">
          <div class="header-actions">
            <a
              href="home.php"
              class="header-button listings-button"
              aria-label="View Listings"
            >
              <i class="fas fa-th-list" aria-hidden="true"></i>
              <span data-lang-key="nav_listings">Listings</span>
            </a>
            <!-- Language Switcher Button -->
            <button
              id="language-switcher-toggle"
              class="header-button icon-button"
              aria-label="Choose language"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <i class="fas fa-globe" aria-hidden="true"></i>
            </button>
            <button
              id="theme-toggle"
              class="header-button icon-button theme-toggle-button"
              aria-label="Toggle theme"
            >
              <svg
                class="sun-icon"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <circle cx="12" cy="12" r="5" />
                <line x1="12" y1="1" x2="12" y2="3" />
                <line x1="12" y1="21" x2="12" y2="23" />
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                <line x1="1" y1="12" x2="3" y2="12" />
                <line x1="21" y1="12" x2="23" y2="12" />
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
              </svg>
              <svg
                class="moon-icon"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
              </svg>
            </button>
          </div>
        </div>
        <!-- Language Switcher Dropdown -->
        <div
          id="language-switcher-dropdown"
          class="language-switcher-dropdown"
          aria-hidden="true"
        >
          <button
            class="language-choice-button"
            data-lang="en"
            aria-label="Switch to English"
          >
            English
          </button>
          <button
            class="language-choice-button"
            data-lang="fr"
            aria-label="Switch to French"
          >
            Français
          </button>
          <button
            class="language-choice-button"
            data-lang="es"
            aria-label="Switch to Spanish"
          >
            Español
          </button>
        </div>
      </div>
    </header>

    <div class="scroll-container">
      <section id="hero" class="scroll-section" data-section-index="0">
        <div class="section-content hero-content">
          <h1 class="hero-title" data-lang-key="hero_title_brand">CROUS-X</h1>
          <h2 class="hero-subtitle" data-lang-key="hero_subtitle">
            Student Housing, Reimagined.
          </h2>
          <p class="hero-description" data-lang-key="hero_description">
            Discover verified student accommodations with ease. Filter by price,
            location, and type to find your perfect home away from home in
            Paris.
          </p>
          <a
            href="home.php"
            class="hero-cta scroll-link"
            data-lang-key="hero_cta"
            >Explore Listings</a
          >
          <div class="scroll-indicator">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M12 5v14M19 12l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </section>

      <section
        id="features"
        class="scroll-section alternate-bg"
        data-section-index="1"
      >
        <div class="section-content">
          <h2 class="section-title" data-lang-key="features_title">
            Key Features
          </h2>
          <div class="features-grid">
            <div class="feature-card">
              <div class="feature-card-icon">
                <i class="fas fa-search-location"></i>
              </div>
              <h3 data-lang-key="feature_smart_search_title">Smart Search</h3>
              <p data-lang-key="feature_smart_search_desc">
                Advanced filters for location, price, size, and amenities to
                quickly find your ideal match.
              </p>
            </div>
            <div class="feature-card">
              <div class="feature-card-icon">
                <i class="fas fa-check-circle"></i>
              </div>
              <h3 data-lang-key="feature_verified_listings_title">
                Verified Listings
              </h3>
              <p data-lang-key="feature_verified_listings_desc">
                All accommodations are vetted for quality, safety, and
                authenticity, ensuring peace of mind.
              </p>
            </div>
            <div class="feature-card">
              <div class="feature-card-icon">
                <i class="fas fa-map-marked-alt"></i>
              </div>
              <h3 data-lang-key="feature_interactive_maps_title">
                Interactive Maps
              </h3>
              <p data-lang-key="feature_interactive_maps_desc">
                Visualize properties relative to your campus, transport, and
                local points of interest.
              </p>
            </div>
            <div class="feature-card">
              <div class="feature-card-icon">
                <i class="fas fa-comments"></i>
              </div>
              <h3 data-lang-key="feature_direct_communication_title">
                Direct Communication
              </h3>
              <p data-lang-key="feature_direct_communication_desc">
                Connect directly with landlords or property managers through our
                secure platform.
              </p>
            </div>
            <div class="feature-card">
              <div class="feature-card-icon"><i class="fas fa-robot"></i></div>
              <h3 data-lang-key="feature_ai_chatbot_title">AI Chatbot</h3>
              <p data-lang-key="feature_ai_chatbot_desc">
                An AI-powered assistant to help you with your search and answer
                your questions 24/7.
              </p>
            </div>
            <div class="feature-card">
              <div class="feature-card-icon">
                <i class="fas fa-file-contract"></i>
              </div>
              <h3 data-lang-key="feature_housing_insurance_title">
                Housing insurance
              </h3>
              <p data-lang-key="feature_housing_insurance_desc">
                To protect your belongings, or your stay, we offer the
                possibility to manage contracts.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section id="how-it-works" class="scroll-section" data-section-index="2">
        <div class="section-content">
          <h2 class="section-title" data-lang-key="process_title">
            How It Works
          </h2>
          <div class="process-steps">
            <div class="process-step">
              <div class="process-step-icon"><i class="fas fa-filter"></i></div>
              <span class="process-step-number">01</span>
              <h3 data-lang-key="process_step1_title">Search & Filter</h3>
              <p data-lang-key="process_step1_desc">
                Use our intuitive tools to browse available student housing
                options based on your criteria.
              </p>
            </div>
            <div class="process-step">
              <div class="process-step-icon"><i class="fas fa-eye"></i></div>
              <span class="process-step-number">02</span>
              <h3 data-lang-key="process_step2_title">View & Compare</h3>
              <p data-lang-key="process_step2_desc">
                Explore detailed listings, view photos, and compare your
                favorite choices side-by-side.
              </p>
            </div>
            <div class="process-step">
              <div class="process-step-icon">
                <i class="fas fa-handshake"></i>
              </div>
              <span class="process-step-number">03</span>
              <h3 data-lang-key="process_step3_title">Connect & Secure</h3>
              <p data-lang-key="process_step3_desc">
                Reach out to landlords directly to ask questions and finalize
                your housing arrangements.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section
        id="contact"
        class="scroll-section contact-section alternate-bg"
        data-section-index="3"
      >
        <div class="animated-shapes">
          <div class="shape shape-4"></div>
          <div class="shape shape-5"></div>
          <div class="shape shape-6"></div>
        </div>
        <div class="noise-overlay"></div>
        <div class="section-content contact-content">
          <h2 class="section-title" data-lang-key="contact_title">
            Ready to Find Your Place?
          </h2>
          <p data-lang-key="contact_description">
            Start your search for the perfect student home today. If you have
            questions or need assistance, we're here to help.
          </p>
          <a href="home.php" class="contact-button" data-lang-key="contact_cta">
            <span>Explore All Listings</span>
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
          <div class="contact-info">
            <p data-lang-key="contact_support_prefix">
              For support, email us at:
              <a href="mailto:support@crous-x-example.com"
                >support@crous-x-example.com</a
              >
            </p>
          </div>
        </div>
      </section>

      <footer class="site-footer-main">
        <p data-lang-key="footer_copyright">
          © <span id="current-year"></span> CROUS-X. Student Housing Made Simple
          by La Friteuse.
        </p>
        <div class="footer-socials">
          <a href="#" aria-label="Facebook"
            ><i class="fab fa-facebook-f"></i
          ></a>
          <a href="#" aria-label="Instagram"
            ><i class="fab fa-instagram"></i
          ></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        </div>
      </footer>
    </div>

    <div id="cursor-dot"></div>
    <div id="background-light"></div>

    <script src="landing/landing-script.js"></script>
  </body>
</html>
