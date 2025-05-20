document.addEventListener("DOMContentLoaded", () => {
  const IS_MOBILE_BREAKPOINT = 768;
  let currentScrollIndex = 0;
  let isScrollAnimating = false;
  let scrollAnimationId = null;
  let firstScrollDone = false;
  let mouseX = 0;
  let mouseY = 0;
  let rafIdCursor = null;
  let isLanguageDropdownVisible = false;
  let lastScrollInitiationTime = 0;

  let currentLightScale = 1.0;
  let targetLightScale = 1.0;
  let scaleAnimationStartTime = null;
  const SCALE_ANIMATION_DURATION = 400;

  let touchStartY = 0;
  let touchStartX = 0;
  let touchStartTime = 0;
  const TOUCH_SWIPE_THRESHOLD_Y = 50;
  const TOUCH_TIME_THRESHOLD = 300;

  const SCROLL_ANIMATION_DURATION = 700;

  const selectors = {
    body: document.body,
    htmlElement: document.documentElement,
    siteHeader: document.querySelector(".site-header"),
    scrollContainer: document.querySelector(".scroll-container"),
    sections: Array.from(document.querySelectorAll(".scroll-section")),
    navLinks: document.querySelectorAll(
      '.main-nav a[href^="#"], .logo a[href^="#"], .hero-cta.scroll-link'
    ),
    scrollIndicator: document.querySelector(".scroll-indicator"),
    themeToggleButton: document.getElementById("theme-toggle"),
    languageSwitcherToggleButton: document.getElementById(
      "language-switcher-toggle"
    ),
    languageSwitcherDropdown: document.getElementById(
      "language-switcher-dropdown"
    ),
    languageChoiceButtons: null,
    cursorDot: document.getElementById("cursor-dot"),
    backgroundLight: document.getElementById("background-light"),
    interactiveElements: document.querySelectorAll(
      'a, button, .feature-card, .header-button, [role="button"]'
    ),
  };

  const translations = {
    /* ... Your existing translations object ... */
    en: {
      nav_features: "Features",
      nav_process: "Process",
      nav_contact: "Contact",
      nav_listings: "Listings",
      hero_title_brand: "CROUS-X",
      hero_subtitle: "Student Housing, Reimagined.",
      hero_description:
        "Discover verified student accommodations with ease. Filter by price, location, and type to find your perfect home away from home in Paris.",
      hero_cta: "Explore Listings",
      features_title: "Key Features",
      feature_smart_search_title: "Smart Search",
      feature_smart_search_desc:
        "Advanced filters for location, price, size, and amenities to quickly find your ideal match.",
      feature_verified_listings_title: "Verified Listings",
      feature_verified_listings_desc:
        "All accommodations are vetted for quality, safety, and authenticity, ensuring peace of mind.",
      feature_interactive_maps_title: "Interactive Maps",
      feature_interactive_maps_desc:
        "Visualize properties relative to your campus, transport, and local points of interest.",
      feature_direct_communication_title: "Direct Communication",
      feature_direct_communication_desc:
        "Connect directly with landlords or property managers through our secure platform.",
      feature_ai_chatbot_title: "AI Chatbot",
      feature_ai_chatbot_desc:
        "An AI-powered assistant to help you with your search and answer your questions 24/7.",
      feature_housing_insurance_title: "Housing insurance",
      feature_housing_insurance_desc:
        "To protect your belongings, or your stay, we offer the possibility to manage contracts.",
      process_title: "How It Works",
      process_step1_title: "Search & Filter",
      process_step1_desc:
        "Use our intuitive tools to browse available student housing options based on your criteria.",
      process_step2_title: "View & Compare",
      process_step2_desc:
        "Explore detailed listings, view photos, and compare your favorite choices side-by-side.",
      process_step3_title: "Connect & Secure",
      process_step3_desc:
        "Reach out to landlords directly to ask questions and finalize your housing arrangements.",
      contact_title: "Ready to Find Your Place?",
      contact_description:
        "Start your search for the perfect student home today. If you have questions or need assistance, we're here to help.",
      contact_cta: "Explore All Listings",
      contact_support_prefix: "For support, email us at:",
      footer_copyright: "© {year} CROUS-X. Student Housing Made Simple.",
    },
    fr: {
      nav_features: "Fonctionnalités",
      nav_process: "Processus",
      nav_contact: "Contact",
      nav_listings: "Annonces",
      hero_title_brand: "CROUS-X",
      hero_subtitle: "Le Logement Étudiant, Réinventé.",
      hero_description:
        "Découvrez facilement des logements étudiants vérifiés. Filtrez par prix, emplacement et type pour trouver votre foyer idéal à Paris.",
      hero_cta: "Explorer les Annonces",
      features_title: "Fonctionnalités Clés",
      feature_smart_search_title: "Recherche Intelligente",
      feature_smart_search_desc:
        "Filtres avancés par emplacement, prix, taille et commodités pour trouver rapidement votre correspondance idéale.",
      feature_verified_listings_title: "Annonces Vérifiées",
      feature_verified_listings_desc:
        "Tous les logements sont contrôlés pour leur qualité, sécurité et authenticité, garantissant une tranquillité d'esprit.",
      feature_interactive_maps_title: "Cartes Interactives",
      feature_interactive_maps_desc:
        "Visualisez les propriétés par rapport à votre campus, aux transports et aux points d'intérêt locaux.",
      feature_direct_communication_title: "Communication Directe",
      feature_direct_communication_desc:
        "Contactez directement les propriétaires ou gestionnaires via notre plateforme sécurisée.",
      feature_ai_chatbot_title: "Chatbot IA",
      feature_ai_chatbot_desc:
        "Un assistant IA pour vous aider dans votre recherche et répondre à vos questions 24/7.",
      feature_housing_insurance_title: "Assurance Habitation",
      feature_housing_insurance_desc:
        "Pour protéger vos biens ou votre séjour, nous offrons la possibilité de gérer les contrats.",
      process_title: "Comment Ça Marche",
      process_step1_title: "Chercher & Filtrer",
      process_step1_desc:
        "Utilisez nos outils intuitifs pour parcourir les logements étudiants disponibles selon vos critères.",
      process_step2_title: "Voir & Comparer",
      process_step2_desc:
        "Explorez les listes détaillées, consultez les photos et comparez vos choix favoris côte à côte.",
      process_step3_title: "Contacter & Sécuriser",
      process_step3_desc:
        "Contactez directement les propriétaires pour poser des questions et finaliser vos arrangements de logement.",
      contact_title: "Prêt à Trouver Votre Place ?",
      contact_description:
        "Commencez votre recherche du logement étudiant parfait dès aujourd'hui. Si vous avez des questions ou besoin d'aide, nous sommes là.",
      contact_cta: "Explorer Toutes les Annonces",
      contact_support_prefix: "Pour le support, envoyez-nous un email à :",
      footer_copyright: "© {year} CROUS-X. Le Logement Étudiant Simplifié.",
    },
    es: {
      nav_features: "Características",
      nav_process: "Proceso",
      nav_contact: "Contacto",
      nav_listings: "Listados",
      hero_title_brand: "CROUS-X",
      hero_subtitle: "Alojamiento Estudiantil, Reinventado.",
      hero_description:
        "Descubre alojamientos estudiantiles verificados con facilidad. Filtra por precio, ubicación y tipo para encontrar tu hogar perfecto en París.",
      hero_cta: "Explorar Listados",
      features_title: "Características Clave",
      feature_smart_search_title: "Búsqueda Inteligente",
      feature_smart_search_desc:
        "Filtros avanzados por ubicación, precio, tamaño y comodidades para encontrar rápidamente tu opción ideal.",
      feature_verified_listings_title: "Listados Verificados",
      feature_verified_listings_desc:
        "Todos los alojamientos son revisados por calidad, seguridad y autenticidad, garantizando tranquilidad.",
      feature_interactive_maps_title: "Mapas Interactivos",
      feature_interactive_maps_desc:
        "Visualiza propiedades cerca de tu campus, transporte y puntos de interés locales.",
      feature_direct_communication_title: "Comunicación Directa",
      feature_direct_communication_desc:
        "Contacta directamente con propietarios o administradores a través de nuestra plataforma segura.",
      feature_ai_chatbot_title: "Chatbot IA",
      feature_ai_chatbot_desc:
        "Un asistente de IA para ayudarte en tu búsqueda y responder a tus preguntas 24/7.",
      feature_housing_insurance_title: "Seguro de Vivienda",
      feature_housing_insurance_desc:
        "Para proteger tus pertenencias o tu estancia, ofrecemos la posibilidad de gestionar contratos.",
      process_title: "Cómo Funciona",
      process_step1_title: "Buscar & Filtrar",
      process_step1_desc:
        "Usa nuestras herramientas intuitivas para navegar por las opciones de alojamiento estudiantil disponibles según tus criterios.",
      process_step2_title: "Ver & Comparar",
      process_step2_desc:
        "Explora listados detallados, mira fotos y compara tus opciones favoritas lado a lado.",
      process_step3_title: "Contactar & Asegurar",
      process_step3_desc:
        "Contacta directamente a los propietarios para hacer preguntas y finalizar tus arreglos de vivienda.",
      contact_title: "¿Listo para Encontrar Tu Lugar?",
      contact_description:
        "Comienza tu búsqueda del hogar estudiantil perfecto hoy. Si tienes preguntas o necesitas asistencia, estamos aquí para ayudar.",
      contact_cta: "Explorar Todos los Listados",
      contact_support_prefix: "Para soporte, envíanos un correo a:",
      footer_copyright:
        "© {year} CROUS-X. Alojamiento Estudiantil Simplificado.",
    },
  };

  function isMobile() {
    return window.innerWidth <= IS_MOBILE_BREAKPOINT;
  }
  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }
  function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  }

  // Helper function to get computed CSS variable values in pixels
  function getCssVariableInPx(variableName, fallback = 0) {
    if (!selectors.htmlElement) return fallback;
    const rootStyle = getComputedStyle(selectors.htmlElement);
    const value = rootStyle.getPropertyValue(variableName).trim();
    if (!value) return fallback;

    const rootFontSize = parseFloat(rootStyle.fontSize);
    if (value.includes("px")) {
      return parseFloat(value);
    } else if (value.includes("rem")) {
      return parseFloat(value) * rootFontSize;
    } else if (value.includes("em")) {
      // Less common for global vars, but possible
      return parseFloat(value) * rootFontSize; // Assuming em is relative to root for simplicity here
    }
    // Add more unit conversions if needed (e.g., vh, vw, but these are less likely for this var)
    return fallback; // Return fallback if unit is unknown or value is complex
  }

  function initializeApp() {
    setupThemeSwitcher();
    setupLanguageSwitcher();
    setupScrollManager();
    setupCursorAndLight();
    // setupBackgroundShapeAnimations(); // CSS handles this now

    document.addEventListener("click", handleGlobalClick);
    document.addEventListener("visibilitychange", handleVisibilityChange);

    if (isMobile()) {
      selectors.body.classList.add("is-mobile");
      selectors.siteHeader?.style.removeProperty("animation");
    } else {
      selectors.body.classList.remove("is-mobile");
      selectors.siteHeader?.style.removeProperty("animation");
    }

    window.addEventListener("resize", () => {
      clearTimeout(selectors.scrollContainer.__resizeTimeout);
      selectors.scrollContainer.__resizeTimeout = setTimeout(() => {
        const isCurrentlyMobile = isMobile();
        const wasMobile = selectors.body.classList.contains("is-mobile");
        if (wasMobile !== isCurrentlyMobile) {
          selectors.body.classList.toggle("is-mobile", isCurrentlyMobile);
          setupCursorAndLight();
          if (isCurrentlyMobile)
            selectors.siteHeader?.style.setProperty(
              "animation",
              "none",
              "important"
            );
          else selectors.siteHeader?.style.removeProperty("animation");
        }
        // On resize, gently adjust scroll position to maintain current section view
        if (!isScrollAnimating && selectors.sections[currentScrollIndex]) {
          const targetSection = selectors.sections[currentScrollIndex];
          const headerClearance = getHeaderClearanceInPx();
          const targetScrollTop = targetSection.offsetTop - headerClearance;
          if (
            Math.abs(selectors.scrollContainer.scrollTop - targetScrollTop) > 10
          ) {
            // Only if significantly off
            selectors.scrollContainer.scrollTop = Math.max(0, targetScrollTop);
          }
        }
        updateScrollVisibility();
      }, 200);
    });

    const currentYearEl = document.getElementById("current-year");
    if (currentYearEl) currentYearEl.textContent = new Date().getFullYear();

    // Initial call to set up section visibility correctly after DOM is ready and CSS applied
    if (selectors.scrollContainer.scrollTop === 0 && currentScrollIndex === 0) {
      setTimeout(updateScrollVisibility, 100); // Small delay for layout
    }
  }

  function handleVisibilityChange() {
    /* ... Keep existing function ... */
    if (document.hidden) {
      // stopAllBackgroundShapeAnimations(); // CSS handles
      if (rafIdCursor) {
        cancelAnimationFrame(rafIdCursor);
        rafIdCursor = null;
      }
    } else {
      if (
        !isMobile() &&
        rafIdCursor === null &&
        selectors.cursorDot?.classList.contains("visible")
      ) {
        rafIdCursor = requestAnimationFrame(updateCursorAndLightPosition);
      }
      // handleSectionBackgroundShapeAnimations(currentScrollIndex, "start"); // CSS handles
    }
  }
  function setupThemeSwitcher() {
    /* ... Keep existing function ... */
    if (!selectors.themeToggleButton) return;
    applyInitialTheme();
    selectors.themeToggleButton.addEventListener("click", toggleTheme);
  }
  function applyTheme(theme) {
    /* ... Keep existing function ... */
    if (theme) selectors.htmlElement.setAttribute("data-theme", theme);
  }
  function applyInitialTheme() {
    /* ... Keep existing function ... */
    const storedTheme = localStorage.getItem("crousXTheme") || "dark";
    applyTheme(storedTheme);
  }
  function toggleTheme() {
    /* ... Keep existing function ... */
    const currentTheme = selectors.htmlElement.getAttribute("data-theme");
    const targetTheme = currentTheme === "dark" ? "light" : "dark";
    applyTheme(targetTheme);
    localStorage.setItem("crousXTheme", targetTheme);
  }
  function setupLanguageSwitcher() {
    /* ... Keep existing function ... */
    if (
      !selectors.languageSwitcherToggleButton ||
      !selectors.languageSwitcherDropdown
    )
      return;
    selectors.languageChoiceButtons =
      selectors.languageSwitcherDropdown.querySelectorAll(
        ".language-choice-button"
      );
    if (selectors.languageChoiceButtons.length === 0) return;
    applyInitialLanguage();
    selectors.languageSwitcherToggleButton.addEventListener(
      "click",
      toggleLanguageDropdown
    );
    selectors.languageSwitcherDropdown.addEventListener(
      "click",
      handleLanguageChoice
    );
  }
  function toggleLanguageDropdown(event) {
    /* ... Keep existing function ... */
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
    /* ... Keep existing function ... */
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
  function applyLanguage(lang) {
    /* ... Keep existing function ... */
    if (!lang || !translations[lang]) return;
    selectors.htmlElement.setAttribute("lang", lang);
    document.querySelectorAll("[data-lang-key]").forEach((el) => {
      const key = el.dataset.langKey;
      let translation =
        translations[lang][key] || translations.en[key] || el.textContent;
      if (key === "footer_copyright") {
        translation = translation.replace("{year}", new Date().getFullYear());
      }
      el.textContent = translation;
    });
    selectors.languageChoiceButtons?.forEach((button) => {
      button.classList.toggle("active", button.dataset.lang === lang);
    });
    localStorage.setItem("crousXLang", lang);
  }
  function applyInitialLanguage() {
    /* ... Keep existing function ... */
    const storedLang = localStorage.getItem("crousXLang");
    const validLangs = ["en", "fr", "es"];
    const browserLang = navigator.language.split("-")[0];
    let initialLang = "en";
    if (storedLang && validLangs.includes(storedLang)) {
      initialLang = storedLang;
    } else if (validLangs.includes(browserLang)) {
      initialLang = browserLang;
    }
    applyLanguage(initialLang);
  }
  function handleLanguageChoice(event) {
    /* ... Keep existing function ... */
    const button = event.target.closest(".language-choice-button");
    if (!button) return;
    const chosenLang = button.dataset.lang;
    if (chosenLang) {
      applyLanguage(chosenLang);
      closeLanguageDropdown();
    }
  }
  function setupCursorAndLight() {
    /* ... Keep existing function ... */
    const customCursorActive =
      !isMobile() && selectors.cursorDot && selectors.backgroundLight;
    const mobileLightActive = isMobile() && selectors.backgroundLight;
    selectors.body.classList.toggle("custom-cursor-active", customCursorActive);
    document.removeEventListener("mousemove", handleMouseMoveForEffects);
    document.documentElement.removeEventListener(
      "mouseleave",
      handleMouseLeaveEffects
    );
    document.documentElement.removeEventListener(
      "mouseenter",
      handleMouseEnterEffects
    );
    selectors.interactiveElements?.forEach((el) => {
      el.removeEventListener("mouseenter", handleMouseEnterInteractive);
      el.removeEventListener("mouseleave", handleMouseLeaveInteractive);
    });
    if (rafIdCursor) {
      cancelAnimationFrame(rafIdCursor);
      rafIdCursor = null;
    }
    currentLightScale = 1.0;
    targetLightScale = 1.0;
    scaleAnimationStartTime = null;
    if (customCursorActive) {
      selectors.cursorDot.style.display = "";
      selectors.backgroundLight.style.display = "";
      selectors.backgroundLight.classList.remove("is-mobile-animated");
      selectors.backgroundLight.style.transform = `translate(-50%, -50%) scale(${currentLightScale})`;
      document.addEventListener("mousemove", handleMouseMoveForEffects, {
        passive: true,
      });
      document.documentElement.addEventListener(
        "mouseleave",
        handleMouseLeaveEffects
      );
      document.documentElement.addEventListener(
        "mouseenter",
        handleMouseEnterEffects
      );
      selectors.interactiveElements?.forEach((el) => {
        el.addEventListener("mouseenter", handleMouseEnterInteractive);
        el.addEventListener("mouseleave", handleMouseLeaveInteractive);
      });
    } else if (mobileLightActive) {
      selectors.cursorDot?.style.setProperty("display", "none", "important");
      selectors.backgroundLight.style.display = "";
      selectors.backgroundLight.classList.add("is-mobile-animated");
      if (selectors.backgroundLight)
        selectors.backgroundLight.style.removeProperty("transform");
    } else {
      selectors.cursorDot?.style.setProperty("display", "none", "important");
      selectors.backgroundLight?.style.setProperty(
        "display",
        "none",
        "important"
      );
      selectors.backgroundLight?.classList.remove("is-mobile-animated");
    }
  }
  function handleMouseEnterInteractive() {
    /* ... Keep existing function ... */
    if (!isMobile()) {
      selectors.cursorDot?.classList.add("hover");
      if (targetLightScale !== 0.6) {
        targetLightScale = 0.6;
        scaleAnimationStartTime = performance.now();
        if (!rafIdCursor)
          rafIdCursor = requestAnimationFrame(updateCursorAndLightPosition);
      }
      selectors.backgroundLight?.classList.add("is-intensified");
    }
  }
  function handleMouseLeaveInteractive() {
    /* ... Keep existing function ... */
    if (!isMobile()) {
      selectors.cursorDot?.classList.remove("hover");
      if (targetLightScale !== 1.0) {
        targetLightScale = 1.0;
        scaleAnimationStartTime = performance.now();
        if (!rafIdCursor)
          rafIdCursor = requestAnimationFrame(updateCursorAndLightPosition);
      }
      selectors.backgroundLight?.classList.remove("is-intensified");
    }
  }
  function handleMouseMoveForEffects(event) {
    /* ... Keep existing function ... */
    if (isMobile()) return;
    mouseX = event.clientX;
    mouseY = event.clientY;
    if (
      selectors.cursorDot &&
      !selectors.cursorDot.classList.contains("visible")
    )
      selectors.cursorDot.classList.add("visible");
    if (
      selectors.backgroundLight &&
      !selectors.backgroundLight.classList.contains("visible")
    )
      selectors.backgroundLight.classList.add("visible");
    if (rafIdCursor === null)
      rafIdCursor = requestAnimationFrame(updateCursorAndLightPosition);
  }
  function updateCursorAndLightPosition() {
    /* ... Keep existing function ... */
    if (!selectors.backgroundLight || isMobile()) {
      if (rafIdCursor) cancelAnimationFrame(rafIdCursor);
      rafIdCursor = null;
      return;
    }
    const now = performance.now();
    let startScale = 1.0;
    if (scaleAnimationStartTime !== null) {
      const elapsedTime = now - scaleAnimationStartTime;
      const progress = Math.min(elapsedTime / SCALE_ANIMATION_DURATION, 1);
      const easedProgress = easeInOutCubic(progress);
      startScale = targetLightScale === 0.6 ? 1.0 : 0.6;
      currentLightScale =
        startScale + (targetLightScale - startScale) * easedProgress;
      if (progress >= 1) {
        currentLightScale = targetLightScale;
        scaleAnimationStartTime = null;
      }
    } else {
      currentLightScale = targetLightScale;
    }
    if (selectors.cursorDot) {
      selectors.cursorDot.style.left = mouseX + "px";
      selectors.cursorDot.style.top = mouseY + "px";
    }
    selectors.backgroundLight.style.transform = `translate(${mouseX}px, ${mouseY}px) translate(-50%, -50%) scale(${currentLightScale})`;
    rafIdCursor = requestAnimationFrame(updateCursorAndLightPosition);
  }
  function handleMouseLeaveEffects() {
    /* ... Keep existing function ... */
    if (!selectors.cursorDot || !selectors.backgroundLight || isMobile())
      return;
    selectors.cursorDot.classList.remove("visible", "hover");
    selectors.backgroundLight.classList.remove("visible", "is-intensified");
    if (rafIdCursor) {
      cancelAnimationFrame(rafIdCursor);
      rafIdCursor = null;
    }
    targetLightScale = 1.0;
    currentLightScale = 1.0;
    scaleAnimationStartTime = null;
    selectors.backgroundLight.style.transform = `translate(${mouseX}px, ${mouseY}px) translate(-50%, -50%) scale(${currentLightScale})`;
  }
  function handleMouseEnterEffects() {
    /* ... Keep existing function ... */
    if (!selectors.cursorDot || !selectors.backgroundLight || isMobile())
      return;
    if (!selectors.cursorDot.classList.contains("visible"))
      selectors.cursorDot.classList.add("visible");
    if (!selectors.backgroundLight.classList.contains("visible"))
      selectors.backgroundLight.classList.add("visible");
    if (rafIdCursor === null)
      rafIdCursor = requestAnimationFrame(updateCursorAndLightPosition);
  }

  function setupScrollManager() {
    if (!selectors.scrollContainer || selectors.sections.length === 0) {
      console.warn(
        "Scroll container or sections not found. Full page scroll disabled."
      );
      selectors.sections.forEach((sec) => sec.classList.add("is-visible"));
      if (selectors.scrollIndicator)
        selectors.scrollIndicator.style.display = "none";
      selectors.htmlElement.style.overflow = "";
      selectors.body.style.overflow = "";
      if (selectors.scrollContainer) {
        selectors.scrollContainer.style.overflowY = "";
        selectors.scrollContainer.style.scrollBehavior = "";
      }
      return;
    }
    selectors.scrollContainer.scrollTop = 0;
    currentScrollIndex = 0;
    selectors.htmlElement.style.overflow = "hidden";
    selectors.body.style.overflow = "hidden";
    selectors.scrollContainer.style.overflowY = "scroll";
    selectors.scrollContainer.style.scrollBehavior = "auto"; // JS handles smooth scroll

    requestAnimationFrame(() => {
      setTimeout(updateScrollVisibility, 100); // Initial visibility update
    });

    selectors.scrollContainer.addEventListener("wheel", handleWheelScroll, {
      passive: false,
    });
    document.addEventListener("keydown", handleKeydownScroll);
    selectors.scrollContainer.addEventListener("touchstart", handleTouchStart, {
      passive: true,
    });
    selectors.scrollContainer.addEventListener("touchmove", handleTouchMove, {
      passive: false,
    });
    selectors.scrollContainer.addEventListener("touchend", handleTouchEnd, {
      passive: true,
    });
    selectors.navLinks.forEach((link) =>
      link.addEventListener("click", handleNavLinkScroll)
    );
    selectors.scrollContainer.addEventListener(
      "scroll",
      () => {
        closeLanguageDropdown();
        if (!isScrollAnimating) {
          updateScrollVisibility();
        }
      },
      { passive: true }
    );
  }

  function animateScroll(targetScrollTop) {
    if (scrollAnimationId) cancelAnimationFrame(scrollAnimationId);
    isScrollAnimating = true;
    closeLanguageDropdown();
    const startScrollTop = selectors.scrollContainer.scrollTop;
    const distance = targetScrollTop - startScrollTop;
    if (Math.abs(distance) < 1) {
      isScrollAnimating = false;
      scrollAnimationId = null;
      updateScrollVisibility();
      return;
    }
    let startTime = null;
    const step = (currentTime) => {
      if (startTime === null) startTime = currentTime;
      const elapsedTime = currentTime - startTime;
      const progress = Math.min(elapsedTime / SCROLL_ANIMATION_DURATION, 1);
      const easedProgress = easeInOutCubic(progress);
      selectors.scrollContainer.scrollTop =
        startScrollTop + distance * easedProgress;
      if (elapsedTime < SCROLL_ANIMATION_DURATION) {
        scrollAnimationId = requestAnimationFrame(step);
      } else {
        selectors.scrollContainer.scrollTop = targetScrollTop;
        isScrollAnimating = false;
        scrollAnimationId = null;
        updateScrollVisibility();
      }
    };
    scrollAnimationId = requestAnimationFrame(step);
  }

  function getHeaderClearanceInPx() {
    let headerClearance = getCssVariableInPx(
      "--calculated-header-clearance-px",
      100
    ); // Fallback

    if (
      selectors.siteHeader &&
      getComputedStyle(selectors.siteHeader).position === "fixed"
    ) {
      const headerStyle = getComputedStyle(selectors.siteHeader);
      const rootStyle = getComputedStyle(selectors.htmlElement);
      const rootFontSize = parseFloat(rootStyle.fontSize);

      let headerActualHeight = selectors.siteHeader.offsetHeight;
      let headerTopPositionPx = 0;
      const headerTopCss = headerStyle.top;
      if (headerTopCss && headerTopCss !== "auto" && headerTopCss !== "0px") {
        headerTopPositionPx = headerTopCss.includes("rem")
          ? parseFloat(headerTopCss) * rootFontSize
          : parseFloat(headerTopCss);
      }
      let visualGapBelowPx = getCssVariableInPx("--header-visual-gap", 20); // Default 20px gap

      headerClearance =
        headerTopPositionPx + headerActualHeight + visualGapBelowPx;
    }
    return headerClearance;
  }

  function scrollToSection(index) {
    index = clamp(index, 0, selectors.sections.length - 1);
    const targetSection = selectors.sections[index];
    if (!targetSection) return;

    const headerClearance = getHeaderClearanceInPx();
    let calculatedTargetScrollTop = targetSection.offsetTop - headerClearance;
    calculatedTargetScrollTop = Math.max(0, calculatedTargetScrollTop); // Don't scroll to negative

    // Only animate if not already very close to target
    if (
      Math.abs(
        selectors.scrollContainer.scrollTop - calculatedTargetScrollTop
      ) < 5 &&
      currentScrollIndex === index &&
      !isScrollAnimating
    ) {
      updateScrollVisibility(); // Ensure UI is consistent
      return;
    }

    if (currentScrollIndex !== index && !isScrollAnimating) {
      // handleSectionBackgroundShapeAnimations(currentScrollIndex, "stop"); // CSS driven
    }
    currentScrollIndex = index; // Update current index immediately for nav link, etc.
    // updateScrollVisibility(); // Call this before animating to update nav link state quickly

    animateScroll(calculatedTargetScrollTop);
  }

  function updateScrollVisibility() {
    const containerHeight = selectors.scrollContainer.clientHeight;
    const scrollTop = selectors.scrollContainer.scrollTop;
    const headerClearance = getHeaderClearanceInPx(); // Get current header clearance

    let determinedIndex = -1;
    // Threshold for considering a section "in view" based on where its content starts
    // (i.e., top of section element, as its internal padding handles header clearance)
    const effectiveViewportTop = scrollTop + headerClearance;

    let minDistanceToEffectiveTop = Infinity;

    selectors.sections.forEach((section, idx) => {
      const sectionTop = section.offsetTop; // Top of the section element
      const sectionContentStartsAt = sectionTop; // Top of section element IS where content effectively starts due to CSS scroll-margin/JS calc
      const sectionBottom = sectionTop + section.offsetHeight;

      // Is the point where content *should* start visible or just above?
      const distance = Math.abs(effectiveViewportTop - sectionContentStartsAt);

      // Check if the section's content area is overlapping with the visible area below the header
      const visibleContentTop = Math.max(
        sectionContentStartsAt,
        effectiveViewportTop
      );
      const visibleContentBottom = Math.min(
        sectionBottom,
        scrollTop + containerHeight
      );
      const visibleHeight = visibleContentBottom - visibleContentTop;

      if (visibleHeight > containerHeight * 0.1) {
        // At least 10% of section content visible
        if (distance < minDistanceToEffectiveTop) {
          minDistanceToEffectiveTop = distance;
          determinedIndex = idx;
        }
      }
    });

    if (determinedIndex === -1) {
      // Fallback: find closest section top to scroll position
      let closestDist = Infinity;
      selectors.sections.forEach((section, idx) => {
        const dist = Math.abs(
          scrollTop - (section.offsetTop - headerClearance)
        );
        if (dist < closestDist) {
          closestDist = dist;
          determinedIndex = idx;
        }
      });
    }

    determinedIndex = clamp(determinedIndex, 0, selectors.sections.length - 1);

    selectors.sections.forEach((section, idx) => {
      const isCurrent = idx === determinedIndex;
      section.classList.toggle("is-visible", isCurrent);
      // Background shape animations are CSS driven by .is-visible
    });

    updateActiveNavLink(determinedIndex);

    if (!isScrollAnimating) {
      currentScrollIndex = determinedIndex; // Update master index if not mid-animation
    }
    checkFirstScroll();
  }

  function updateActiveNavLink(activeSectionIndex) {
    /* ... Keep existing function from previous good version ... */
    if (
      activeSectionIndex < 0 ||
      activeSectionIndex >= selectors.sections.length
    )
      return;
    const currentSectionId = selectors.sections[activeSectionIndex]?.id;
    if (!currentSectionId) return;
    selectors.navLinks?.forEach((link) => {
      const linkHref = link.getAttribute("href");
      const linkTargetId =
        linkHref === "#" || (linkHref === "#hero" && activeSectionIndex === 0)
          ? "hero"
          : linkHref?.replace("#", "");
      link.classList.toggle(
        "active-nav-link",
        linkTargetId && linkTargetId === currentSectionId
      );
    });
  }
  function checkFirstScroll() {
    /* ... Keep existing function ... */
    if (
      !firstScrollDone &&
      selectors.scrollContainer.scrollTop > 50 &&
      selectors.scrollIndicator
    ) {
      selectors.body.classList.add("has-scrolled");
      firstScrollDone = true;
    }
  }
  function handleWheelScroll(event) {
    /* ... Keep existing function ... */
    if (isLanguageDropdownVisible) {
      const scrollableContent = selectors.languageSwitcherDropdown;
      if (scrollableContent?.contains(event.target)) {
        const isScrollable =
          scrollableContent.scrollHeight > scrollableContent.clientHeight;
        const atTop = event.deltaY < 0 && scrollableContent.scrollTop <= 1;
        const atBottom =
          event.deltaY > 0 &&
          scrollableContent.scrollTop >=
            scrollableContent.scrollHeight - scrollableContent.clientHeight - 1;
        if (
          isScrollable &&
          (!atTop || event.deltaY > 0) &&
          (!atBottom || event.deltaY < 0)
        )
          return;
      }
      event.preventDefault();
      return;
    } else {
      event.preventDefault();
    }
    const now = Date.now();
    if (isScrollAnimating || now - lastScrollInitiationTime < 800) return;
    const direction = event.deltaY > 0 ? "down" : "up";
    let targetIndex = currentScrollIndex;
    if (
      direction === "down" &&
      currentScrollIndex < selectors.sections.length - 1
    )
      targetIndex++;
    else if (direction === "up" && currentScrollIndex > 0) targetIndex--;
    if (targetIndex !== currentScrollIndex) {
      lastScrollInitiationTime = now;
      scrollToSection(targetIndex);
    }
  }
  function handleTouchStart(event) {
    /* ... Keep existing function ... */
    if (isLanguageDropdownVisible || event.touches.length !== 1) return;
    touchStartY = event.touches[0].clientY;
    touchStartX = event.touches[0].clientX;
    touchStartTime = Date.now();
  }
  function handleTouchMove(event) {
    /* ... Keep existing function ... */
    if (
      isLanguageDropdownVisible ||
      isScrollAnimating ||
      event.touches.length !== 1
    )
      return;
    const deltaY = event.touches[0].clientY - touchStartY;
    const deltaX = event.touches[0].clientX - touchStartX;
    if (Math.abs(deltaY) > Math.abs(deltaX) && Math.abs(deltaY) > 10)
      event.preventDefault();
  }
  function handleTouchEnd(event) {
    /* ... Keep existing function ... */
    if (
      isLanguageDropdownVisible ||
      isScrollAnimating ||
      event.changedTouches.length !== 1
    ) {
      touchStartY = 0;
      touchStartX = 0;
      touchStartTime = 0;
      return;
    }
    const endY = event.changedTouches[0].clientY;
    const endX = event.changedTouches[0].clientX;
    const endTime = Date.now();
    const swipeDistanceY = endY - touchStartY;
    const swipeDistanceX = endX - touchStartX;
    const swipeTime = endTime - touchStartTime;
    const now = Date.now();
    if (now - lastScrollInitiationTime < 800) {
      touchStartY = 0;
      touchStartX = 0;
      touchStartTime = 0;
      return;
    }
    let targetIndex = currentScrollIndex;
    let shouldScroll = false;
    if (Math.abs(swipeDistanceY) > Math.abs(swipeDistanceX) * 1.5) {
      const isFastSwipe =
        Math.abs(swipeDistanceY) > 10 && swipeTime < TOUCH_TIME_THRESHOLD;
      const isLongSwipe = Math.abs(swipeDistanceY) > TOUCH_SWIPE_THRESHOLD_Y;
      if (isFastSwipe || isLongSwipe) {
        if (
          swipeDistanceY < 0 &&
          currentScrollIndex < selectors.sections.length - 1
        ) {
          targetIndex++;
          shouldScroll = true;
        } else if (swipeDistanceY > 0 && currentScrollIndex > 0) {
          targetIndex--;
          shouldScroll = true;
        }
      }
    }
    if (shouldScroll && targetIndex !== currentScrollIndex) {
      lastScrollInitiationTime = now;
      scrollToSection(targetIndex);
    }
    touchStartY = 0;
    touchStartX = 0;
    touchStartTime = 0;
  }
  function handleKeydownScroll(event) {
    /* ... Keep existing function ... */
    if (event.key === "Escape") {
      if (isLanguageDropdownVisible) {
        closeLanguageDropdown();
        event.preventDefault();
        return;
      }
    }
    if (isLanguageDropdownVisible) {
      if (
        [
          "ArrowUp",
          "ArrowDown",
          "ArrowLeft",
          "ArrowRight",
          " ",
          "PageUp",
          "PageDown",
          "Home",
          "End",
          "Tab",
        ].includes(event.key)
      ) {
        if (event.key !== "Tab") event.preventDefault();
      }
      return;
    }
    if (isScrollAnimating || event.metaKey || event.ctrlKey || event.altKey) {
      if (event.key === " " && event.shiftKey) {
      } else {
        return;
      }
    }
    const activeEl = document.activeElement;
    const isInput =
      activeEl &&
      (activeEl.tagName === "INPUT" ||
        activeEl.tagName === "TEXTAREA" ||
        activeEl.isContentEditable);
    if (isInput) return;
    let targetIndex = currentScrollIndex;
    let shouldScroll = false;
    let preventDefault = false;
    switch (event.key) {
      case "ArrowDown":
      case "PageDown":
        if (currentScrollIndex < selectors.sections.length - 1) {
          targetIndex++;
          shouldScroll = true;
        }
        preventDefault = true;
        break;
      case " ":
        if (event.shiftKey) {
          if (currentScrollIndex > 0) {
            targetIndex--;
            shouldScroll = true;
          }
        } else {
          if (currentScrollIndex < selectors.sections.length - 1) {
            targetIndex++;
            shouldScroll = true;
          }
        }
        preventDefault = true;
        break;
      case "ArrowUp":
      case "PageUp":
        if (currentScrollIndex > 0) {
          targetIndex--;
          shouldScroll = true;
        }
        preventDefault = true;
        break;
      case "Home":
        if (currentScrollIndex !== 0) {
          targetIndex = 0;
          shouldScroll = true;
        }
        preventDefault = true;
        break;
      case "End":
        if (currentScrollIndex !== selectors.sections.length - 1) {
          targetIndex = selectors.sections.length - 1;
          shouldScroll = true;
        }
        preventDefault = true;
        break;
      default:
        return;
    }
    if (preventDefault) event.preventDefault();
    const now = Date.now();
    if (
      shouldScroll &&
      targetIndex !== currentScrollIndex &&
      now - lastScrollInitiationTime > 500
    ) {
      lastScrollInitiationTime = now;
      scrollToSection(targetIndex);
    }
  }
  function handleNavLinkScroll(event) {
    /* ... Keep existing function from previous good version ... */
    event.preventDefault();
    if (isScrollAnimating) return;
    const targetIdAttr = event.currentTarget.getAttribute("href");
    try {
      if (!targetIdAttr || targetIdAttr === "#") {
        scrollToSection(0);
        return;
      }
      const targetElementId = targetIdAttr.replace("#", "");
      const targetElement = document.getElementById(targetElementId);
      if (!targetElement) {
        scrollToSection(0);
        return;
      }
      const targetIndex = selectors.sections.findIndex(
        (sec) => sec.id === targetElement.id
      );
      if (targetIndex !== -1) {
        scrollToSection(clamp(targetIndex, 0, selectors.sections.length - 1));
      } else {
        const headerClearance = getHeaderClearanceInPx();
        const directTargetScrollTop = targetElement.offsetTop - headerClearance;
        animateScroll(Math.max(0, directTargetScrollTop));
      }
    } catch (error) {
      scrollToSection(0);
    }
  }
  function handleGlobalClick(event) {
    /* ... Keep existing function ... */
    if (
      isLanguageDropdownVisible &&
      selectors.languageSwitcherToggleButton &&
      !selectors.languageSwitcherToggleButton.contains(event.target) &&
      selectors.languageSwitcherDropdown &&
      !selectors.languageSwitcherDropdown.contains(event.target)
    ) {
      closeLanguageDropdown();
    }
  }

  initializeApp();
});
