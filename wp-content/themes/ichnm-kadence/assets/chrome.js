(() => {
  const doc = document.documentElement;
  const THEME_KEY = "ichnm-theme";

  // --- Veil / page-open motion (keep separable from cookie / theme work) ---
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function isBviOn() {
    const body = document.body;
    return !!(
      (body && (body.classList.contains("bvi-active") || body.classList.contains("bvi-body"))) ||
      doc.classList.contains("bvi-active") ||
      doc.classList.contains("is-bvi")
    );
  }

  function enterPage(allowMotion) {
    if (allowMotion && !reduce && !isBviOn()) {
      doc.classList.add("js-motion");
      requestAnimationFrame(() => {
        doc.classList.add("is-entered");
      });
      return;
    }
    doc.classList.remove("js-motion");
    doc.classList.add("is-entered");
  }

  enterPage(true);

  // BVI plugin may add classes after chrome boot; drop motion if it activates.
  if (document.body && typeof MutationObserver === "function") {
    const bviWatch = new MutationObserver(() => {
      if (!isBviOn()) return;
      enterPage(false);
      bviWatch.disconnect();
    });
    bviWatch.observe(document.body, { attributes: true, attributeFilter: ["class"] });
    bviWatch.observe(doc, { attributes: true, attributeFilter: ["class"] });
  }
  // --- end veil / motion ---

  const themeBtn = document.querySelector("[data-theme-toggle]");
  const nightByClock = () => {
    const h = new Date().getHours();
    return h >= 21 || h < 7;
  };
  const defaultFromClock = () => (nightByClock() ? "night" : "day");
  const readThemeMode = () => {
    try {
      return localStorage.getItem(THEME_KEY);
    } catch (err) {
      return null;
    }
  };
  const writeThemeMode = (mode) => {
    try {
      localStorage.setItem(THEME_KEY, mode);
    } catch (err) {
      /* ignore */
    }
  };
  const normalizeThemeMode = (mode) => (mode === "day" || mode === "night" ? mode : null);
  const applyThemeMode = (mode, persist) => {
    const resolved = normalizeThemeMode(mode) || defaultFromClock();
    const night = resolved === "night";
    doc.classList.toggle("theme-night", night);
    doc.dataset.themeMode = resolved;
    if (themeBtn) {
      themeBtn.setAttribute("data-theme-mode", resolved);
      themeBtn.setAttribute("aria-pressed", night ? "true" : "false");
      const day = themeBtn.getAttribute("data-label-day") || "Дневная тема";
      const nightLabel = themeBtn.getAttribute("data-label-night") || "Ночная тема";
      // Button offers the opposite mode (day ↔ night only; no auto).
      themeBtn.textContent = night ? day : nightLabel;
      themeBtn.title = night ? nightLabel : day;
    }
    if (persist) writeThemeMode(resolved);
  };
  let savedTheme = normalizeThemeMode(readThemeMode());
  // First visit / legacy "auto": clock default, do not persist until user toggles.
  applyThemeMode(savedTheme || defaultFromClock(), false);
  if (themeBtn) {
    themeBtn.addEventListener("click", () => {
      const cur = themeBtn.getAttribute("data-theme-mode") || defaultFromClock();
      const next = cur === "night" ? "day" : "night";
      applyThemeMode(next, true);
    });
  }

  const root = document.querySelector(".ichnm-chrome");
  if (root) {
    // Honest preview: paper chrome after ~24px scroll (home styles only).
    const onScroll = () => {
      root.classList.toggle("is-scrolled", window.scrollY > 24);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });

    const toggle = root.querySelector(".ichnm-menu-toggle");
    const nav = root.querySelector("#ichnm-primary-nav");
    const setSubmenuOpen = (item, open) => {
      item.classList.toggle("is-open", open);
      const button = item.querySelector(":scope > .nav-parent > .submenu-toggle");
      if (button) button.setAttribute("aria-expanded", open ? "true" : "false");
      if (!open) {
        item.querySelectorAll("li.is-open").forEach((child) => {
          child.classList.remove("is-open");
          const nested = child.querySelector(":scope > .nav-parent > .submenu-toggle");
          if (nested) nested.setAttribute("aria-expanded", "false");
        });
      }
    };
    if (toggle && nav) {
      toggle.addEventListener("click", () => {
        const open = root.classList.toggle("is-menu-open");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        if (!open) {
          nav.querySelectorAll("li.is-open").forEach((item) => setSubmenuOpen(item, false));
        }
      });
    }
    if (nav) {
      nav.addEventListener("click", (event) => {
        const btn = event.target.closest(".submenu-toggle");
        if (!btn || !nav.contains(btn)) return;
        event.preventDefault();
        const item = btn.closest("li");
        if (!item) return;
        setSubmenuOpen(item, !item.classList.contains("is-open"));
      });
    }
  }

  const search = document.getElementById("ichnm-site-search");
  const openBtn = document.querySelector("[data-ichnm-search-open]");
  const closeBtn = search && search.querySelector("[data-ichnm-search-close]");
  const live = search && search.querySelector("[data-ichnm-search-live]");
  const input = search && search.querySelector('input[type="search"]');

  const closeSearch = () => {
    if (!search) return;
    search.hidden = true;
    doc.classList.remove("is-search-open");
  };
  const openSearch = () => {
    if (!search) return;
    search.hidden = false;
    doc.classList.add("is-search-open");
    if (input) input.focus();
  };

  if (openBtn) openBtn.addEventListener("click", openSearch);
  if (closeBtn) closeBtn.addEventListener("click", closeSearch);
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeSearch();
  });

  if (input && live && Array.isArray(window.ichnmSearchIndex)) {
    const labels = {
      person: "Персоналии",
      unit: "Подразделения",
      facility: "Приборы",
      development: "Разработки",
    };
    input.addEventListener("input", () => {
      const q = (input.value || "").trim().toLowerCase();
      if (q.length < 2) {
        live.innerHTML = "";
        return;
      }
      const hits = window.ichnmSearchIndex.filter((row) => {
        const hay = `${row.title || ""} ${row.meta || ""}`.toLowerCase();
        return hay.includes(q);
      }).slice(0, 12);
      if (!hits.length) {
        live.innerHTML = "<p>Ничего не найдено.</p>";
        return;
      }
      live.innerHTML = hits.map((row) => {
        const kind = labels[row.type] || row.type;
        return `<a href="${row.href}"><strong>${row.title}</strong><small>${kind}</small></a>`;
      }).join("");
    });
  }

  // Map hover cards: open below the pin when it sits in the upper half.
  document.querySelectorAll(".ichnm-world-map .ichnm-map-hotspot").forEach((spot) => {
    const top = parseFloat(String(spot.style.top || "50"));
    if (!Number.isNaN(top) && top < 42) {
      spot.setAttribute("data-pop", "below");
    }
  });

  // --- Cookie consent (preview parity; analytics never loaded in v1) ---
  // --- BVI (plugin class or theme fallback, tickets 51 / 69 / 81) ---
  const BVI_KEY = "ichnm-bvi";

  function hidePageVeil() {
    const veil = document.querySelector(".ichnm-page-veil");
    if (!veil) return;
    veil.style.setProperty("display", "none", "important");
    veil.setAttribute("aria-hidden", "true");
  }

  function clearPluginBviChrome() {
    document.querySelectorAll(".bvi-panel, #bvi-panel").forEach((el) => {
      if (el.closest(".ichnm-bvi-plugin-host")) return;
      el.setAttribute("hidden", "");
      el.style.setProperty("display", "none", "important");
      el.style.setProperty("visibility", "hidden", "important");
      el.style.setProperty("pointer-events", "none", "important");
    });
  }

  const applyBvi = (on, persist) => {
    doc.classList.toggle("is-bvi", on);
    doc.classList.toggle("bvi-active", on);
    if (document.body) {
      document.body.classList.toggle("bvi-active", on);
      document.body.classList.toggle("bvi-body", on);
    }
    // Never remove header tool nodes — only aria state (restore without reload).
    const btn = document.querySelector("[data-bvi-toggle]");
    if (btn) {
      btn.setAttribute("aria-pressed", on ? "true" : "false");
      btn.hidden = false;
      btn.style.removeProperty("display");
    }
    const utilities = document.querySelector(".ichnm-header-utilities");
    if (utilities) {
      utilities.hidden = false;
      utilities.style.removeProperty("display");
    }
    const chrome = document.querySelector(".ichnm-chrome");
    if (chrome) {
      chrome.hidden = false;
      chrome.style.removeProperty("display");
    }
    if (on) {
      enterPage(false);
    } else {
      // Enabling BVI strips js-motion and hides the veil via BVI CSS.
      // Turning BVI off must not re-show a solid navy veil (ticket 81).
      doc.classList.add("is-entered");
      doc.classList.remove("js-motion");
      hidePageVeil();
      clearPluginBviChrome();
    }
    if (persist) {
      try {
        localStorage.setItem(BVI_KEY, on ? "1" : "0");
      } catch (err) {
        /* ignore */
      }
    }
  };
  let bviOn = false;
  try {
    bviOn = localStorage.getItem(BVI_KEY) === "1";
  } catch (err) {
    bviOn = false;
  }
  if (bviOn || isBviOn()) {
    applyBvi(true, false);
  }
  const bviBtn = document.querySelector("[data-bvi-toggle]");
  if (bviBtn) {
    bviBtn.addEventListener("click", () => {
      const wasOn = isBviOn();
      const next = !wasOn;
      const pluginPanel = document.querySelector(".bvi-panel, #bvi-panel");
      const pluginLooksActive =
        wasOn ||
        !!(pluginPanel && !pluginPanel.hasAttribute("hidden") && pluginPanel.offsetParent !== null);
      applyBvi(next, true);
      // Proxy into hidden BVI plugin host only when states need syncing (ticket 52 / 81).
      const pluginLink = document.querySelector(
        ".ichnm-bvi-plugin-host .bvi-open, .ichnm-bvi-plugin-host .bvi-shortcode a, .ichnm-bvi-plugin-host a"
      );
      if (pluginLink && typeof pluginLink.click === "function") {
        try {
          if (next || pluginLooksActive) {
            pluginLink.click();
          }
        } catch (err) {
          /* ignore */
        }
      }
      if (!next) {
        hidePageVeil();
        clearPluginBviChrome();
      }
    });
  }
  // --- end BVI ---

  const COOKIE_KEY = "ichnm-cookies";
  const cookieState = () => {
    try {
      const raw = localStorage.getItem(COOKIE_KEY);
      if (raw) return JSON.parse(raw);
    } catch (err) {
      /* ignore */
    }
    try {
      const match = document.cookie.match(/(?:^|; )ichnm-cookies=([^;]*)/);
      if (match) return JSON.parse(decodeURIComponent(match[1]));
    } catch (err) {
      /* ignore */
    }
    return null;
  };
  const saveCookies = (state) => {
    const payload = JSON.stringify(state);
    try {
      localStorage.setItem(COOKIE_KEY, payload);
    } catch (err) {
      /* ignore quota / private mode */
    }
    try {
      document.cookie =
        "ichnm-cookies=" +
        encodeURIComponent(payload) +
        "; path=/; max-age=31536000; SameSite=Lax";
    } catch (err) {
      /* ignore */
    }
    doc.classList.add("cookies-ok");
  };
  const cookieBanner = document.getElementById("cookie-banner");
  const cookieForm = document.getElementById("cookie-settings");
  const savedCookies = cookieState();
  if (cookieBanner && !savedCookies) cookieBanner.hidden = false;
  if (cookieBanner && savedCookies) cookieBanner.hidden = true;
  if (cookieBanner) {
    cookieBanner.addEventListener("click", (event) => {
      const btn = event.target.closest("[data-cookie]");
      if (!btn) return;
      const act = btn.getAttribute("data-cookie");
      if (act === "settings" && cookieForm) {
        cookieForm.hidden = false;
        return;
      }
      // Preference stored; no analytics scripts ship in v1.
      saveCookies({ necessary: true, analytics: act === "accept" });
      cookieBanner.hidden = true;
    });
  }
  if (cookieForm) {
    cookieForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const analyticsInput = cookieForm.querySelector('[name="analytics"]');
      const analytics = Boolean(analyticsInput && analyticsInput.checked);
      saveCookies({ necessary: true, analytics: analytics });
      if (cookieBanner) cookieBanner.hidden = true;
    });
  }
})();
