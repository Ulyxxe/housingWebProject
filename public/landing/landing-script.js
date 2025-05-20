document.addEventListener("DOMContentLoaded", () => {
  const IS_MOBILE_BREAKPOINT = 768;
  let currentScrollIndex = 0;
  let isScrollAnimating = false;
  // ... (all other existing 'let' and 'const' variables from your previous script) ...
  const SCROLL_ANIMATION_DURATION = 700; // Keep this

  const selectors = {
    /* ... Keep your full selectors object ... */
  };
  const translations = {
    /* ... Keep your full translations object ... */
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
    if (!valueString) return 0;
    if (valueString.includes("px")) return parseFloat(valueString);
    if (valueString.includes("rem"))
      return parseFloat(valueString) * rootFontSize;
    if (valueString.includes("em"))
      return parseFloat(valueString) * rootFontSize; // Assuming relative to root for simplicity
    return parseFloat(valueString) || 0; // Fallback for unitless or unrecognized
  }

  function getHeaderClearanceInPx() {
    let totalClearance = 100; // Default fallback

    if (
      selectors.siteHeader &&
      getComputedStyle(selectors.siteHeader).position === "fixed"
    ) {
      const headerStyle = getComputedStyle(selectors.siteHeader);
      const rootStyle = getComputedStyle(selectors.htmlElement);
      const rootFontSize = parseFloat(rootStyle.fontSize);

      const headerElementHeightPx = selectors.siteHeader.offsetHeight; // Element's full rendered height

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
      const approxClearanceCssVar = getComputedStyle(selectors.htmlElement)
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
    const clearance = getHeaderClearanceInPx();
    selectors.htmlElement.style.setProperty(
      "--actual-header-clearance-for-content",
      `${clearance}px`
    );
  }

  function initializeApp() {
    setDynamicHeaderClearanceCssVar(); // Set it on initial load

    // ... (Keep ALL your existing setup function calls: setupThemeSwitcher, setupLanguageSwitcher, etc.)
    setupScrollManager(); // Ensure this is called
    // ...

    window.addEventListener("resize", () => {
      clearTimeout(selectors.scrollContainer.__resizeTimeout);
      selectors.scrollContainer.__resizeTimeout = setTimeout(() => {
        setDynamicHeaderClearanceCssVar(); // Update on resize

        const isCurrentlyMobile = isMobile();
        // ... (rest of your existing resize logic for body class, cursor, etc.)

        // Adjust scroll position if not animating
        if (
          !isScrollAnimating &&
          selectors.sections.length > 0 &&
          currentScrollIndex < selectors.sections.length
        ) {
          const targetSection = selectors.sections[currentScrollIndex];
          if (targetSection) {
            const newTargetScrollTop = targetSection.offsetTop; // Scroll to section's top
            // Only adjust if significantly off to prevent jitter
            if (
              Math.abs(
                selectors.scrollContainer.scrollTop - newTargetScrollTop
              ) > 10
            ) {
              selectors.scrollContainer.scrollTop = newTargetScrollTop;
            }
          }
        }
        updateScrollVisibility(); // Always update visibility states
      }, 200);
    });
    // ... (Rest of initializeApp like current year)
  }

  function scrollToSection(index) {
    index = clamp(index, 0, selectors.sections.length - 1);
    const targetSection = selectors.sections[index];
    if (!targetSection) {
      console.error(
        "scrollToSection: Target section not found for index:",
        index
      );
      return;
    }

    // The target is simply the offsetTop of the section.
    // The section's own CSS padding-top (set by --actual-header-clearance-for-content)
    // will handle the space for the header.
    const calculatedTargetScrollTop = targetSection.offsetTop;

    // Only animate if not already very close to target
    // OR if currentScrollIndex is different (meaning we are intentionally changing sections)
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

    // No need to handle background shape animations here if CSS is driving it by .is-visible

    currentScrollIndex = index; // Update current index
    // updateScrollVisibility(); // Call before animation to update nav links quickly if desired, or let animateScroll's end call it.

    animateScroll(calculatedTargetScrollTop);
  }

  function updateScrollVisibility() {
    const containerHeight = selectors.scrollContainer.clientHeight;
    const scrollTop = selectors.scrollContainer.scrollTop;
    // The "effective top" of the viewport for content is where the header ends.
    const headerClearance = getHeaderClearanceInPx(); // Use the accurate dynamic value

    let determinedIndex = -1;
    let minDistanceToConsiderVisible = Infinity;

    selectors.sections.forEach((section, idx) => {
      const sectionTop = section.offsetTop; // Top of the section element itself (includes its padding-top)
      const sectionBottom = sectionTop + section.offsetHeight;

      // A section is "active" if its top edge (which includes the header clearance padding)
      // is at or very near the scroll container's top.
      const distance = Math.abs(scrollTop - sectionTop);

      // Also consider if a good portion of the section is visible overall
      // Viewport content area starts after headerClearance
      const viewportContentAreaTop = scrollTop; // ScrollTop is where the section's padding-top should align
      const viewportContentAreaBottom = scrollTop + containerHeight;

      // Content of the section starts at sectionTop (due to its own padding-top)
      // and ends at sectionBottom.
      const overlapStart = Math.max(sectionTop, viewportContentAreaTop);
      const overlapEnd = Math.min(sectionBottom, viewportContentAreaBottom);
      const visibleHeight = Math.max(0, overlapEnd - overlapStart);

      if (visibleHeight > containerHeight * 0.2) {
        // If at least 20% of section is visible
        if (distance < minDistanceToConsiderVisible) {
          minDistanceToConsiderVisible = distance;
          determinedIndex = idx;
        }
      }
    });

    if (determinedIndex === -1 && selectors.sections.length > 0) {
      // Fallback
      let closestDist = Infinity;
      selectors.sections.forEach((section, idx) => {
        const dist = Math.abs(scrollTop - section.offsetTop);
        if (dist < closestDist) {
          closestDist = dist;
          determinedIndex = idx;
        }
      });
    }

    determinedIndex = clamp(
      determinedIndex,
      0,
      selectors.sections.length > 0 ? selectors.sections.length - 1 : 0
    );

    selectors.sections.forEach((section, idx) => {
      const isCurrent = idx === determinedIndex;
      section.classList.toggle("is-visible", isCurrent);
    });

    updateActiveNavLink(determinedIndex);

    if (!isScrollAnimating) {
      currentScrollIndex = determinedIndex;
    }
    checkFirstScroll();
  }

  // ... (COPY ALL OTHER JS FUNCTIONS FROM YOUR PREVIOUS **COMPLETE** SCRIPT)
  // Ensure functions like: animateScroll, setupScrollManager, updateActiveNavLink, checkFirstScroll,
  // all event handlers (handleWheelScroll, handleTouchStart, etc.),
  // setupThemeSwitcher, setupLanguageSwitcher, setupCursorAndLight, and their helpers
  // are present and complete. The snippet above only shows the most critical modifications.

  initializeApp();
});
