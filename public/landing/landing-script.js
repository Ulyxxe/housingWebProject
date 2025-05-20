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

  const sectionAnimationState = {
    1: { shapeTimeoutId: null },
    3: { shapeTimeoutId: null },
  };

  const SCROLL_ANIMATION_DURATION = 700; // Duration for smooth scroll

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

  function initializeApp() {
    setupThemeSwitcher();
    setupLanguageSwitcher();
    setupScrollManager();
    setupCursorAndLight();
    setupBackgroundShapeAnimations();

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
        // Recalculate current section visibility and potentially adjust scroll on resize
        // This is important if sections have dynamic heights or if viewport changes significantly
        if (!isScrollAnimating) {
          const currentSectionElement = selectors.sections[currentScrollIndex];
          if (currentSectionElement) {
            // Re-calculate offset for the current section and gently scroll to it
            // This avoids jumps if the layout changed due to resize
            let headerOffset = 0;
            if (
              selectors.siteHeader &&
              getComputedStyle(selectors.siteHeader).position === "fixed"
            ) {
              headerOffset = selectors.siteHeader.offsetHeight;
              const headerTopStyle = getComputedStyle(selectors.siteHeader).top;
              if (
                headerTopStyle &&
                headerTopStyle !== "auto" &&
                headerTopStyle !== "0px"
              ) {
                // Attempt to parse rem/px. For simplicity, assuming 1rem = 16px for this calculation if needed.
                // A more robust solution would convert rem to px dynamically.
                const rootFontSize = parseFloat(
                  getComputedStyle(document.documentElement).fontSize
                );
                if (headerTopStyle.includes("rem")) {
                  headerOffset += parseFloat(headerTopStyle) * rootFontSize;
                } else {
                  headerOffset += parseFloat(headerTopStyle);
                }
              }
              headerOffset += 20; // Visual padding
            }
            const targetScrollTop =
              currentSectionElement.offsetTop - headerOffset;
            // Only adjust if significantly different to avoid jitter
            if (
              Math.abs(selectors.scrollContainer.scrollTop - targetScrollTop) >
              5
            ) {
              selectors.scrollContainer.scrollTop = targetScrollTop;
            }
          }
        }
        updateScrollVisibility(); // Always update visibility states
      }, 200);
    });

    const currentYearEl = document.getElementById("current-year");
    if (currentYearEl) currentYearEl.textContent = new Date().getFullYear();
  }

  function handleVisibilityChange() {
    if (document.hidden) {
      stopAllBackgroundShapeAnimations();
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
      if (
        currentScrollIndex >= 0 &&
        currentScrollIndex < selectors.sections.length
      ) {
        handleSectionBackgroundShapeAnimations(currentScrollIndex, "start");
      }
    }
  }

  function setupThemeSwitcher() {
    if (!selectors.themeToggleButton) return;
    applyInitialTheme();
    selectors.themeToggleButton.addEventListener("click", toggleTheme);
  }
  function applyTheme(theme) {
    if (theme) selectors.htmlElement.setAttribute("data-theme", theme);
  }
  function applyInitialTheme() {
    const storedTheme = localStorage.getItem("crousXTheme") || "dark";
    applyTheme(storedTheme);
  }
  function toggleTheme() {
    const currentTheme = selectors.htmlElement.getAttribute("data-theme");
    const targetTheme = currentTheme === "dark" ? "light" : "dark";
    applyTheme(targetTheme);
    localStorage.setItem("crousXTheme", targetTheme);
  }

  function setupLanguageSwitcher() {
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

  function applyLanguage(lang) {
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
    const button = event.target.closest(".language-choice-button");
    if (!button) return;
    const chosenLang = button.dataset.lang;
    if (chosenLang) {
      applyLanguage(chosenLang);
      closeLanguageDropdown();
    }
  }

  function setupCursorAndLight() {
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
      selectors.sections.forEach((sec) => sec.classList.add("is-visible"));
      if (selectors.scrollIndicator)
        selectors.scrollIndicator.style.display = "none";
      selectors.htmlElement.style.overflow = "";
      selectors.body.style.overflow = "";
      selectors.scrollContainer.style.overflowY = "";
      selectors.scrollContainer.style.scrollBehavior = "";
      return;
    }
    selectors.scrollContainer.scrollTop = 0;
    currentScrollIndex = 0;
    selectors.htmlElement.style.overflow = "hidden";
    selectors.body.style.overflow = "hidden";
    selectors.scrollContainer.style.overflowY = "scroll";
    selectors.scrollContainer.style.scrollBehavior = "auto"; // Important for custom animation
    requestAnimationFrame(() => {
      setTimeout(updateScrollVisibility, 50);
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
          // Only update if not currently animating
          updateScrollVisibility();
        }
      },
      { passive: true }
    );
  }

  function animateScroll(targetScrollTop) {
    if (
      isScrollAnimating &&
      Math.abs(selectors.scrollContainer.scrollTop - targetScrollTop) < 5
    ) {
      // Already animating to a very close position, or already there
      return;
    }

    if (scrollAnimationId) cancelAnimationFrame(scrollAnimationId);
    isScrollAnimating = true;
    closeLanguageDropdown(); // Close dropdown if open during scroll

    const startScrollTop = selectors.scrollContainer.scrollTop;
    const distance = targetScrollTop - startScrollTop;

    // If no distance to scroll, finish immediately
    if (Math.abs(distance) < 1) {
      isScrollAnimating = false;
      scrollAnimationId = null;
      updateScrollVisibility(); // Ensure visibility is updated
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
        selectors.scrollContainer.scrollTop = targetScrollTop; // Ensure final position
        isScrollAnimating = false;
        scrollAnimationId = null;
        updateScrollVisibility(); // Update visibility after animation
      }
    };
    scrollAnimationId = requestAnimationFrame(step);
  }

  // =====================================================================
  // === CORRECTED scrollToSection FUNCTION ==============================
  // =====================================================================
  function scrollToSection(index) {
    index = clamp(index, 0, selectors.sections.length - 1);

    // Small optimization: if already at the target and not animating, just update visibility
    // Only return if truly at the section and not just having the same index from a previous partial scroll
    const currentSectionElement = selectors.sections[index];
    if (!currentSectionElement) return; // Should not happen if index is clamped

    let currentHeaderOffset = 0;
    if (
      selectors.siteHeader &&
      getComputedStyle(selectors.siteHeader).position === "fixed"
    ) {
      currentHeaderOffset = selectors.siteHeader.offsetHeight;
      const headerTopStyle = getComputedStyle(selectors.siteHeader).top;
      if (
        headerTopStyle &&
        headerTopStyle !== "auto" &&
        headerTopStyle !== "0px"
      ) {
        const rootFontSize = parseFloat(
          getComputedStyle(document.documentElement).fontSize
        );
        if (headerTopStyle.includes("rem")) {
          currentHeaderOffset += parseFloat(headerTopStyle) * rootFontSize;
        } else {
          currentHeaderOffset += parseFloat(headerTopStyle); // Assumes px
        }
      }
      currentHeaderOffset += 20; // Add 20px visual padding below header
    }
    const calculatedTargetScrollTop =
      currentSectionElement.offsetTop - currentHeaderOffset;

    if (
      index === currentScrollIndex &&
      !isScrollAnimating &&
      Math.abs(
        selectors.scrollContainer.scrollTop - calculatedTargetScrollTop
      ) < 5
    ) {
      updateScrollVisibility();
      return;
    }

    const targetSection = selectors.sections[index]; // Re-fetch in case it was undefined before
    if (!targetSection) return;

    handleSectionBackgroundShapeAnimations(currentScrollIndex, "stop");
    currentScrollIndex = index; // Set the new index
    // updateScrollVisibility(); // Update visibility immediately for nav link, etc.

    // === Calculate headerOffset dynamically ===
    let headerOffset = 0;
    if (
      selectors.siteHeader &&
      getComputedStyle(selectors.siteHeader).position === "fixed"
    ) {
      headerOffset = selectors.siteHeader.offsetHeight; // Actual height of the header element

      // Add the `top` offset of the header itself (e.g., `top: 1.5rem;`)
      const headerTopValue = getComputedStyle(selectors.siteHeader).top;
      if (
        headerTopValue &&
        headerTopValue !== "auto" &&
        headerTopValue !== "0px"
      ) {
        const rootFontSize = parseFloat(
          getComputedStyle(document.documentElement).fontSize
        ); // Get root font size for rem conversion
        if (headerTopValue.includes("rem")) {
          headerOffset += parseFloat(headerTopValue) * rootFontSize;
        } else {
          headerOffset += parseFloat(headerTopValue); // Assumes px
        }
      }
      // Add a small additional padding for visual comfort below the header
      headerOffset += 20; // Adjust this 20px value as needed for visual spacing
    }

    const targetScrollTop = targetSection.offsetTop - headerOffset;

    animateScroll(targetScrollTop);
    // updateScrollVisibility() will be called at the end of animateScroll
  }
  // =====================================================================
  // === END OF CORRECTED scrollToSection FUNCTION =======================
  // =====================================================================

  function updateScrollVisibility() {
    const containerHeight = selectors.scrollContainer.clientHeight;
    const scrollTop = selectors.scrollContainer.scrollTop;

    let determinedIndex = -1; // Initialize to -1 to ensure it gets set
    let minPositiveDistanceToViewportTop = Infinity;

    selectors.sections.forEach((section, idx) => {
      const sectionTop = section.offsetTop;
      const sectionBottom = sectionTop + section.offsetHeight;

      // Calculate distance of section top from current viewport top
      const distanceToViewportTop = sectionTop - scrollTop;

      // Check if the section is "mostly" in view or the one whose top is closest to viewport top
      // A section is "active" if its top is at or above the viewport top, but not too far above,
      // OR if it's the first section and the scroll is near the top.
      const viewThreshold = containerHeight * 0.5; // Section considered active if its top is within top 50% of viewport

      if (
        distanceToViewportTop < viewThreshold &&
        distanceToViewportTop > -section.offsetHeight + viewThreshold
      ) {
        // Prioritize section whose top is closest to (or just above) the viewport top
        if (
          distanceToViewportTop >= -(section.offsetHeight * 0.2) &&
          distanceToViewportTop < minPositiveDistanceToViewportTop
        ) {
          minPositiveDistanceToViewportTop = distanceToViewportTop;
          determinedIndex = idx;
        }
      }
    });

    // Fallback if no section met the precise criteria (e.g., during fast scroll or odd positions)
    if (determinedIndex === -1) {
      let closestDist = Infinity;
      selectors.sections.forEach((section, idx) => {
        const dist = Math.abs(scrollTop - section.offsetTop);
        if (dist < closestDist) {
          closestDist = dist;
          determinedIndex = idx;
        }
      });
    }

    determinedIndex = clamp(determinedIndex, 0, selectors.sections.length - 1);

    selectors.sections.forEach((section, idx) => {
      const isCurrent = idx === determinedIndex;
      const wasVisible = section.classList.contains("is-visible");
      section.classList.toggle("is-visible", isCurrent);
      if (isCurrent && !wasVisible)
        handleSectionBackgroundShapeAnimations(idx, "start");
      else if (!isCurrent && wasVisible)
        handleSectionBackgroundShapeAnimations(idx, "stop");
    });

    updateActiveNavLink(determinedIndex);

    // Only update currentScrollIndex if not animating.
    // The animation itself will set currentScrollIndex upon completion or when scrollToSection is called.
    if (!isScrollAnimating) {
      currentScrollIndex = determinedIndex;
    }
    checkFirstScroll();
  }

  function updateActiveNavLink(activeSectionIndex) {
    if (
      activeSectionIndex < 0 ||
      activeSectionIndex >= selectors.sections.length
    )
      return; // Guard
    const currentSectionId = selectors.sections[activeSectionIndex]?.id;
    if (!currentSectionId) return;

    selectors.navLinks?.forEach((link) => {
      const linkHref = link.getAttribute("href");
      const linkTargetId =
        linkHref === "#" || (linkHref === "#hero" && activeSectionIndex === 0)
          ? "hero" // Special case for logo/hero link pointing to the first section
          : linkHref?.replace("#", "");

      link.classList.toggle(
        "active-nav-link",
        linkTargetId && linkTargetId === currentSectionId
      );
    });
  }

  function checkFirstScroll() {
    if (
      !firstScrollDone &&
      selectors.scrollContainer.scrollTop > 50 && // Check actual scroll position
      selectors.scrollIndicator
    ) {
      selectors.body.classList.add("has-scrolled");
      firstScrollDone = true;
    }
  }

  function handleWheelScroll(event) {
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
          return; // Allow native scroll within dropdown
      }
      event.preventDefault(); // Prevent page scroll if dropdown is open and not scrolling internally
      return;
    } else {
      event.preventDefault(); // Prevent default window scroll if dropdown is not open
    }

    const now = Date.now();
    if (isScrollAnimating || now - lastScrollInitiationTime < 800) return; // Debounce/throttle

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
    if (isLanguageDropdownVisible || event.touches.length !== 1) return;
    touchStartY = event.touches[0].clientY;
    touchStartX = event.touches[0].clientX;
    touchStartTime = Date.now();
  }
  function handleTouchMove(event) {
    if (
      isLanguageDropdownVisible ||
      isScrollAnimating ||
      event.touches.length !== 1
    )
      return;
    const deltaY = event.touches[0].clientY - touchStartY;
    const deltaX = event.touches[0].clientX - touchStartX;
    if (Math.abs(deltaY) > Math.abs(deltaX) && Math.abs(deltaY) > 10)
      // Prioritize vertical swipes
      event.preventDefault(); // Prevent native scroll if a vertical swipe is detected
  }
  function handleTouchEnd(event) {
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
      // Debounce
      touchStartY = 0;
      touchStartX = 0;
      touchStartTime = 0;
      return;
    }

    let targetIndex = currentScrollIndex;
    let shouldScroll = false;

    // Check for vertical swipe
    if (Math.abs(swipeDistanceY) > Math.abs(swipeDistanceX) * 1.5) {
      // More vertical than horizontal
      const isFastSwipe =
        Math.abs(swipeDistanceY) > 10 && swipeTime < TOUCH_TIME_THRESHOLD;
      const isLongSwipe = Math.abs(swipeDistanceY) > TOUCH_SWIPE_THRESHOLD_Y;

      if (isFastSwipe || isLongSwipe) {
        if (
          swipeDistanceY < 0 && // Swipe Up (content moves up, scroll down)
          currentScrollIndex < selectors.sections.length - 1
        ) {
          targetIndex++;
          shouldScroll = true;
        } else if (swipeDistanceY > 0 && currentScrollIndex > 0) {
          // Swipe Down (content moves down, scroll up)
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
    touchStartTime = 0; // Reset touch coordinates
  }

  function handleKeydownScroll(event) {
    if (event.key === "Escape") {
      if (isLanguageDropdownVisible) {
        closeLanguageDropdown();
        event.preventDefault();
        return;
      }
    }
    if (isLanguageDropdownVisible) {
      // If dropdown is open, let it handle its own keyboard navigation if any, or prevent page scroll
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
        // Allow Tab for accessibility within the dropdown
        if (event.key !== "Tab") event.preventDefault();
      }
      return;
    }

    if (
      isScrollAnimating ||
      event.metaKey ||
      event.ctrlKey ||
      event.altKey
      // Allow Shift + Space for scrolling up
    ) {
      if (event.key === " " && event.shiftKey) {
        // Continue to allow Shift+Space
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
    if (isInput) return; // Don't interfere with typing

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
      case " ": // Space bar
        if (event.shiftKey) {
          // Shift + Space scrolls up
          if (currentScrollIndex > 0) {
            targetIndex--;
            shouldScroll = true;
          }
        } else {
          // Space scrolls down
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
        return; // Do nothing for other keys
    }

    if (preventDefault) event.preventDefault();

    const now = Date.now();
    if (
      shouldScroll &&
      targetIndex !== currentScrollIndex &&
      now - lastScrollInitiationTime > 500
    ) {
      // Debounce
      lastScrollInitiationTime = now;
      scrollToSection(targetIndex);
    }
  }

  function handleNavLinkScroll(event) {
    event.preventDefault();
    if (isScrollAnimating) return;

    const targetIdAttr = event.currentTarget.getAttribute("href");
    try {
      if (!targetIdAttr || targetIdAttr === "#") {
        // Link to top or empty href
        scrollToSection(0);
        return;
      }

      const targetElementId = targetIdAttr.replace("#", "");
      const targetElement = document.getElementById(targetElementId);

      if (!targetElement) {
        // Target ID not found on page
        console.warn(
          `Target element with ID "${targetElementId}" not found. Scrolling to top.`
        );
        scrollToSection(0);
        return;
      }

      // Find if the target is one of our scroll-sections
      const targetIndex = selectors.sections.findIndex(
        (sec) => sec.id === targetElement.id
      );

      if (targetIndex !== -1) {
        // It's one of our main sections
        scrollToSection(clamp(targetIndex, 0, selectors.sections.length - 1));
      } else {
        // It's some other anchor on the page, not a main section.
        // Fallback to browser's native scrollIntoView if it's not a managed section.
        // This might be less smooth if it conflicts with the overall scroll container logic.
        // For now, let's try to scroll to its top within our container.
        console.warn(
          `Target element "${targetElementId}" is not a managed scroll-section. Attempting direct scroll.`
        );
        let headerOffset = 0;
        if (
          selectors.siteHeader &&
          getComputedStyle(selectors.siteHeader).position === "fixed"
        ) {
          headerOffset = selectors.siteHeader.offsetHeight;
          const headerTopValue = getComputedStyle(selectors.siteHeader).top;
          if (
            headerTopValue &&
            headerTopValue !== "auto" &&
            headerTopValue !== "0px"
          ) {
            const rootFontSize = parseFloat(
              getComputedStyle(document.documentElement).fontSize
            );
            headerOffset += headerTopValue.includes("rem")
              ? parseFloat(headerTopValue) * rootFontSize
              : parseFloat(headerTopValue);
          }
          headerOffset += 20;
        }
        const directTargetScrollTop = targetElement.offsetTop - headerOffset;
        animateScroll(directTargetScrollTop);
      }
    } catch (error) {
      console.error("Error handling nav link scroll:", error);
      scrollToSection(0); // Fallback to safety
    }
  }

  function setupBackgroundShapeAnimations() {
    // CSS handles this based on .is-visible class now
  }
  function handleSectionBackgroundShapeAnimations(sectionIndex, action) {
    if (document.hidden) return; // Handled by CSS via .is-visible
  }
  function stopAllBackgroundShapeAnimations() {
    // Handled by CSS via .is-visible
  }

  function handleGlobalClick(event) {
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
