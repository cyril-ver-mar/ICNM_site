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
  const applyTheme = (night, persist) => {
    doc.classList.toggle("theme-night", night);
    if (themeBtn) {
      themeBtn.setAttribute("aria-pressed", night ? "true" : "false");
      themeBtn.textContent = night
        ? themeBtn.getAttribute("data-label-day") || "Дневная тема"
        : themeBtn.getAttribute("data-label-night") || "Ночная тема";
    }
    if (persist) {
      try {
        localStorage.setItem(THEME_KEY, night ? "night" : "day");
      } catch (err) {
        /* ignore quota / private mode */
      }
    }
  };
  const nightByClock = () => {
    const h = new Date().getHours();
    return h >= 21 || h < 7;
  };
  let savedTheme = null;
  try {
    savedTheme = localStorage.getItem(THEME_KEY);
  } catch (err) {
    savedTheme = null;
  }
  if (savedTheme === "night") applyTheme(true, false);
  else if (savedTheme === "day") applyTheme(false, false);
  else applyTheme(nightByClock(), false);
  if (themeBtn) {
    themeBtn.addEventListener("click", () => {
      applyTheme(!doc.classList.contains("theme-night"), true);
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
    if (toggle && nav) {
      toggle.addEventListener("click", () => {
        const open = root.classList.toggle("is-menu-open");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
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
