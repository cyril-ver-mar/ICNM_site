(() => {
  const doc = document.documentElement;
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const bviOn = !!(document.body && (document.body.classList.contains("bvi-active") || document.body.classList.contains("bvi-body")));

  if (!reduce && !bviOn) {
    doc.classList.add("js-motion");
    requestAnimationFrame(() => {
      doc.classList.add("is-entered");
    });
  } else {
    doc.classList.add("is-entered");
  }

  const root = document.querySelector(".ichnm-chrome");
  if (root) {
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
})();
