(() => {
  const doc = document.documentElement;
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const bviOn = !!(document.body && (document.body.classList.contains("bvi-active") || document.body.classList.contains("bvi-body")));
  const THEME_KEY = "ichnm-theme";

  if (!reduce && !bviOn) {
    doc.classList.add("js-motion");
    requestAnimationFrame(() => {
      doc.classList.add("is-entered");
    });
  } else {
    doc.classList.add("is-entered");
  }

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
})();
