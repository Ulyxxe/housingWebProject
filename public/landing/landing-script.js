document.addEventListener("DOMContentLoaded", () => {
  const IS_MOBILE_BREAKPOINT = 768;
  let currentScrollIndex = 0;
  let isScrollAnimating = false;
  let scrollAnimationId = null;
  let firstScrollDone = false;
  let mouseX = 0,
    mouseY = 0;
  let rafIdCursor = null;
  let isLanguageDropdownVisible = false;
  let lastScrollInitiationTime = 0;
  let currentLightScale = 1.0,
    targetLightScale = 1.0,
    scaleAnimationStartTime = null;
  const SCALE_ANIMATION_DURATION = 400;
  let touchStartY = 0,
    touchStartX = 0,
    touchStartTime = 0;
  const TOUCH_SWIPE_THRESHOLD_Y = 50,
    TOUCH_TIME_THRESHOLD = 300;
  const SCROLL_ANIMATION_DURATION = 700;

  // !!!!! PASTE YOUR FULL 'selectors' OBJECT HERE !!!!!
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
    languageChoiceButtons: null, // Will be populated
    cursorDot: document.getElementById("cursor-dot"),
    backgroundLight: document.getElementById("background-light"),
    interactiveElements: document.querySelectorAll(
      'a, button, .feature-card, .header-button, [role="button"]'
    ),
  };

  // !!!!! PASTE YOUR FULL 'translations' OBJECT HERE !!!!!
  const translations = {
    en: {
      /* ... */
    },
    fr: {
      /* ... */
    },
    es: {
      /* ... */
    }, // Example structure
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

  function getCssPixelValue(valueString, rootFontSize) {
    if (typeof valueString !== "string" || !valueString.trim()) return 0;
    const trimmedValue = valueString.trim();
    if (trimmedValue.endsWith("px")) return parseFloat(trimmedValue);
    if (trimmedValue.endsWith("rem"))
      return parseFloat(trimmedValue) * rootFontSize;
    if (trimmedValue.endsWith("em"))
      return parseFloat(trimmedValue) * rootFontSize; // Context dependent, but often root for vars
    const num = parseFloat(trimmedValue);
    return isNaN(num) ? 0 : num; // Fallback for unitless or unparsed
  }

  function getHeaderClearanceInPx() {
    let totalClearance = 100; // Sensible default

    if (
      selectors.siteHeader &&
      getComputedStyle(selectors.siteHeader).position === "fixed"
    ) {
      const headerStyle = getComputedStyle(selectors.siteHeader);
      const rootStyle = getComputedStyle(selectors.htmlElement);
      const rootFontSize = parseFloat(rootStyle.fontSize);

      const headerElementHeightPx = selectors.siteHeader.offsetHeight;

      const headerCssTopValue =
        rootStyle.getPropertyValue("--header-css-top-val").trim() ||
        headerStyle.top;
      const headerCssTopPx = getCssPixelValue(headerCssTopValue, rootFontSize);

      const visualGapBelowValue = rootStyle
        .getPropertyValue("--header-visual-gap-below-val")
        .trim();
      const visualGapBelowPx = getCssPixelValue(
        visualGapBelowValue,
        rootFontSize
      );

      totalClearance =
        headerCssTopPx + headerElementHeightPx + visualGapBelowPx;
    } else {
      // Fallback if header isn't fixed, try to use the CSS variable if available
      const approxClearanceCssVar =
        selectors.htmlElement.style
          .getPropertyValue("--actual-header-clearance-for-content")
          .trim() ||
        getComputedStyle(selectors.htmlElement)
          .getPropertyValue("--actual-header-clearance-for-content")
          .trim();
      totalClearance =
        getCssPixelValue(
          approxClearanceCssVar,
          parseFloat(getComputedStyle(selectors.htmlElement).fontSize)
        ) || totalClearance;
    }
    return Math.max(20, totalClearance); // Ensure a minimum clearance
  }

  function setDynamicHeaderClearanceCssVar() {
    if (!selectors.htmlElement) return;
    const clearance = getHeaderClearanceInPx();
    selectors.htmlElement.style.setProperty(
      "--actual-header-clearance-for-content",
      `${clearance}px`
    );
  }

  function initializeApp() {
    setDynamicHeaderClearanceCssVar();

    // --- START: PASTE YOUR EXISTING initializeApp content HERE ---
    // (setupThemeSwitcher, setupLanguageSwitcher, setupScrollManager, setupCursorAndLight, etc.)
    setupThemeSwitcher();
    setupLanguageSwitcher();
    setupScrollManager();
    setupCursorAndLight();
    // setupBackgroundShapeAnimations(); // CSS handles

    document.addEventListener("click", handleGlobalClick);
    document.addEventListener("visibilitychange", handleVisibilityChange);

    if (isMobile()) {
      selectors.body.classList.add("is-mobile");
      if (selectors.siteHeader)
        selectors.siteHeader.style.removeProperty("animation");
    } else {
      selectors.body.classList.remove("is-mobile");
      if (selectors.siteHeader)
        selectors.siteHeader.style.removeProperty("animation");
    }

    window.addEventListener("resize", () => {
      clearTimeout(selectors.scrollContainer.__resizeTimeout);
      selectors.scrollContainer.__resizeTimeout = setTimeout(() => {
        setDynamicHeaderClearanceCssVar();
        const isCurrentlyMobile = isMobile();
        const wasMobile = selectors.body.classList.contains("is-mobile");
        if (wasMobile !== isCurrentlyMobile) {
          selectors.body.classList.toggle("is-mobile", isCurrentlyMobile);
          setupCursorAndLight(); // Re-setup cursor based on mobile state
          if (isCurrentlyMobile && selectors.siteHeader)
            selectors.siteHeader.style.setProperty(
              "animation",
              "none",
              "important"
            );
          else if (selectors.siteHeader)
            selectors.siteHeader.style.removeProperty("animation");
        }

        if (
          !isScrollAnimating &&
          selectors.sections.length > 0 &&
          currentScrollIndex < selectors.sections.length
        ) {
          const targetSection = selectors.sections[currentScrollIndex];
          if (targetSection) {
            const newTargetScrollTop = targetSection.offsetTop;
            if (
              Math.abs(
                selectors.scrollContainer.scrollTop - newTargetScrollTop
              ) > 10
            ) {
              selectors.scrollContainer.scrollTop = newTargetScrollTop;
            }
          }
        }
        updateScrollVisibility();
      }, 200);
    });

    const currentYearEl = document.getElementById("current-year");
    if (currentYearEl) currentYearEl.textContent = new Date().getFullYear();
    // --- END: PASTE YOUR EXISTING initializeApp content HERE ---

    if (
      selectors.scrollContainer &&
      selectors.scrollContainer.scrollTop === 0 &&
      currentScrollIndex === 0
    ) {
      setTimeout(updateScrollVisibility, 150); // Initial update with slight delay
    }
  }

  function scrollToSection(index) {
    index = clamp(index, 0, selectors.sections.length - 1);
    const targetSection = selectors.sections[index];
    if (!targetSection) return;

    const calculatedTargetScrollTop = targetSection.offsetTop;

    if (
      Math.abs(
        selectors.scrollContainer.scrollTop - calculatedTargetScrollTop
      ) < 5 &&
      currentScrollIndex === index &&
      !isScrollAnimating
    ) {
      updateScrollVisibility();
      return;
    }
    currentScrollIndex = index;
    animateScroll(calculatedTargetScrollTop);
  }

  function updateScrollVisibility() {
    if (
      !selectors.scrollContainer ||
      !selectors.sections ||
      selectors.sections.length === 0
    )
      return;

    const containerHeight = selectors.scrollContainer.clientHeight;
    const scrollTop = selectors.scrollContainer.scrollTop;

    let determinedIndex = -1;
    let minDistance = Infinity;

    // This simple check assumes that the top of the section (which includes its own padding-top for header clearance)
    // being closest to the scrollTop is the active one.
    selectors.sections.forEach((section, idx) => {
      const sectionTop = section.offsetTop;
      const distanceToViewportTop = Math.abs(scrollTop - sectionTop);

      if (distanceToViewportTop < minDistance) {
        minDistance = distanceToViewportTop;
        determinedIndex = idx;
      }
    });

    determinedIndex = clamp(determinedIndex, 0, selectors.sections.length - 1);

    selectors.sections.forEach((section, idx) => {
      section.classList.toggle("is-visible", idx === determinedIndex);
    });
    updateActiveNavLink(determinedIndex);
    if (!isScrollAnimating) {
      currentScrollIndex = determinedIndex;
    }
    checkFirstScroll();
  }

  // --- START: PASTE ALL YOUR OTHER JS FUNCTIONS HERE ---
  // (animateScroll, setupScrollManager, updateActiveNavLink, checkFirstScroll,
  // all event handlers, setupThemeSwitcher & helpers, setupLanguageSwitcher & helpers,
  // setupCursorAndLight & helpers, handleGlobalClick, handleVisibilityChange)
  // FROM YOUR PREVIOUS COMPLETE SCRIPT.
  // Ensure they are complete and correct. I'll add stubs for a few critical ones.

  function animateScroll(targetScrollTop) {
    if (scrollAnimationId) cancelAnimationFrame(scrollAnimationId);
    isScrollAnimating = true;
    closeLanguageDropdown(); // Assuming this function exists
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

  function setupScrollManager() {
    /* PASTE YOUR FULL setupScrollManager HERE */
  }
  function updateActiveNavLink(activeSectionIndex) {
    /* PASTE YOUR FULL updateActiveNavLink HERE */
  }
  function checkFirstScroll() {
    /* PASTE YOUR FULL checkFirstScroll HERE */
  }
  function handleWheelScroll(event) {
    /* PASTE YOUR FULL handleWheelScroll HERE */
  }
  function handleTouchStart(event) {
    /* PASTE YOUR FULL handleTouchStart HERE */
  }
  function handleTouchMove(event) {
    /* PASTE YOUR FULL handleTouchMove HERE */
  }
  function handleTouchEnd(event) {
    /* PASTE YOUR FULL handleTouchEnd HERE */
  }
  function handleKeydownScroll(event) {
    /* PASTE YOUR FULL handleKeydownScroll HERE */
  }
  function handleNavLinkScroll(event) {
    /* PASTE YOUR FULL handleNavLinkScroll HERE */
  }
  function setupThemeSwitcher() {
    /* PASTE YOUR FULL setupThemeSwitcher AND HELPERS HERE */
  }
  function setupLanguageSwitcher() {
    /* PASTE YOUR FULL setupLanguageSwitcher AND HELPERS HERE */
  }
  function setupCursorAndLight() {
    /* PASTE YOUR FULL setupCursorAndLight AND HELPERS HERE */
  }
  function handleGlobalClick(event) {
    /* PASTE YOUR FULL handleGlobalClick HERE */
  }
  function handleVisibilityChange() {
    /* PASTE YOUR FULL handleVisibilityChange HERE */
  }
  // --- END: PASTE ALL OTHER JS FUNCTIONS HERE ---

  initializeApp();
});
