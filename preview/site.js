(function () {
  const root = document.documentElement;
  const header = document.querySelector(".site-header");
  const bvi = document.querySelector("[data-bvi]");
  const bviPanel = document.getElementById("bvi-panel");
  const themeBtn = document.querySelector("[data-theme-toggle]");
  const toggle = document.querySelector("[data-nav-toggle]");
  const nav = document.getElementById("primary-nav");

  const BVI_KEY = "ichnm-bvi-settings";
  const THEME_KEY = "ichnm-theme";
  const COOKIE_KEY = "ichnm-cookies";

  function loadBvi() {
    try {
      return Object.assign(
        { on: false, size: "normal", scheme: "bw", images: true, spacing: false },
        JSON.parse(sessionStorage.getItem(BVI_KEY) || "null") || {}
      );
    } catch (err) {
      return { on: false, size: "normal", scheme: "bw", images: true, spacing: false };
    }
  }

  function applyBvi(state) {
    root.classList.toggle("is-bvi", Boolean(state.on));
    if (state.on) {
      root.classList.add("is-entered");
      root.classList.remove("js-motion");
    }
    root.setAttribute("data-bvi-size", state.size || "normal");
    root.setAttribute("data-bvi-scheme", state.scheme || "bw");
    root.classList.toggle("is-bvi-noimg", Boolean(state.on && !state.images));
    root.classList.toggle("is-bvi-spacing", Boolean(state.on && state.spacing));
    if (bvi) bvi.setAttribute("aria-pressed", state.on ? "true" : "false");
    if (bviPanel) bviPanel.hidden = !state.on;
    try {
      sessionStorage.setItem(BVI_KEY, JSON.stringify(state));
      sessionStorage.setItem("ichnm-bvi", state.on ? "1" : "0");
    } catch (err) {}
  }

  let bviState = loadBvi();
  if (sessionStorage.getItem("ichnm-bvi") === "1" && !bviState.on) bviState.on = true;
  applyBvi(bviState);

  if (bvi) {
    bvi.addEventListener("click", function () {
      bviState.on = !bviState.on;
      applyBvi(bviState);
    });
  }
  if (bviPanel) {
    bviPanel.addEventListener("click", function (event) {
      const btn = event.target.closest("button");
      if (!btn) return;
      if (btn.hasAttribute("data-bvi-size")) bviState.size = btn.getAttribute("data-bvi-size");
      if (btn.hasAttribute("data-bvi-scheme")) bviState.scheme = btn.getAttribute("data-bvi-scheme");
      if (btn.hasAttribute("data-bvi-images")) bviState.images = !bviState.images;
      if (btn.hasAttribute("data-bvi-spacing")) bviState.spacing = !bviState.spacing;
      if (btn.hasAttribute("data-bvi-off")) {
        bviState.on = false;
        bviState.size = "normal";
        bviState.scheme = "bw";
        bviState.images = true;
        bviState.spacing = false;
      }
      applyBvi(bviState);
    });
  }

  function applyTheme(night, persist) {
    root.classList.toggle("theme-night", night);
    if (themeBtn) {
      themeBtn.setAttribute("aria-pressed", night ? "true" : "false");
      themeBtn.textContent = night
        ? themeBtn.getAttribute("data-label-day") || "Дневная тема"
        : themeBtn.getAttribute("data-label-night") || "Ночная тема";
    }
    if (persist) {
      try {
        localStorage.setItem(THEME_KEY, night ? "night" : "day");
      } catch (err) {}
    }
  }

  function nightByClock() {
    const h = new Date().getHours();
    return h >= 21 || h < 7;
  }

  const savedTheme = (function () {
    try {
      return localStorage.getItem(THEME_KEY);
    } catch (err) {
      return null;
    }
  })();
  if (savedTheme === "night") applyTheme(true, false);
  else if (savedTheme === "day") applyTheme(false, false);
  else applyTheme(nightByClock(), false);
  if (themeBtn) {
    themeBtn.addEventListener("click", function () {
      applyTheme(!root.classList.contains("theme-night"), true);
    });
  }

  function cookieState() {
    try {
      const raw = localStorage.getItem(COOKIE_KEY);
      if (raw) return JSON.parse(raw);
    } catch (err) {}
    try {
      const match = document.cookie.match(/(?:^|; )ichnm-cookies=([^;]*)/);
      if (match) return JSON.parse(decodeURIComponent(match[1]));
    } catch (err) {}
    return null;
  }

  function saveCookies(state) {
    const payload = JSON.stringify(state);
    try {
      localStorage.setItem(COOKIE_KEY, payload);
    } catch (err) {}
    try {
      document.cookie =
        "ichnm-cookies=" +
        encodeURIComponent(payload) +
        "; path=/; max-age=31536000; SameSite=Lax";
    } catch (err) {}
    root.classList.add("cookies-ok");
  }

  const cookieBanner = document.getElementById("cookie-banner");
  const cookieForm = document.getElementById("cookie-settings");
  const savedCookies = cookieState();
  if (cookieBanner && !savedCookies) cookieBanner.hidden = false;
  if (cookieBanner && savedCookies) cookieBanner.hidden = true;
  if (cookieBanner) {
    cookieBanner.addEventListener("click", function (event) {
      const btn = event.target.closest("[data-cookie]");
      if (!btn) return;
      const act = btn.getAttribute("data-cookie");
      if (act === "settings" && cookieForm) {
        cookieForm.hidden = false;
        return;
      }
      const analytics = act === "accept";
      saveCookies({ necessary: true, analytics: analytics });
      cookieBanner.hidden = true;
    });
  }
  if (cookieForm) {
    cookieForm.addEventListener("submit", function (event) {
      event.preventDefault();
      const analytics = Boolean(cookieForm.querySelector('[name="analytics"]').checked);
      saveCookies({ necessary: true, analytics: analytics });
      if (cookieBanner) cookieBanner.hidden = true;
    });
  }

  const onScroll = function () {
    if (header) header.classList.toggle("is-scrolled", window.scrollY > 24);
  };
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      const open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      if (!open) {
        nav.querySelectorAll("li.is-open").forEach(function (item) {
          setSubmenuOpen(item, false);
        });
      }
    });
    function setSubmenuOpen(item, open) {
      item.classList.toggle("is-open", open);
      const button = item.querySelector(":scope > .nav-parent > .submenu-toggle");
      if (button) button.setAttribute("aria-expanded", open ? "true" : "false");
      if (!open) {
        item.querySelectorAll("li.is-open").forEach(function (child) {
          child.classList.remove("is-open");
          const nested = child.querySelector(":scope > .nav-parent > .submenu-toggle");
          if (nested) nested.setAttribute("aria-expanded", "false");
        });
      }
    }

    nav.addEventListener("click", function (event) {
      const target = event.target;
      if (!(target instanceof Element)) return;
      const button = target.closest(".submenu-toggle");
      const folder = target.closest(".nav-folder");
      if (!button && !folder) return;
      const item = (button || folder).closest("li");
      if (!item || !nav.contains(item)) return;
      event.preventDefault();
      event.stopPropagation();
      setSubmenuOpen(item, !item.classList.contains("is-open"));
    });
  }

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function easeOut(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  function runCount(stat) {
    const target = Number(stat.getAttribute("data-count-to") || "0");
    const num = stat.querySelector(".stat-num");
    if (!num) return;
    if (reduceMotion || root.classList.contains("is-bvi")) {
      num.textContent = String(target);
      return;
    }
    const started = performance.now();
    const duration = 1100;
    function frame(now) {
      const t = Math.min(1, (now - started) / duration);
      num.textContent = String(Math.round(easeOut(t) * target));
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  const stats = document.querySelectorAll(".stat[data-count-to]");
  if (stats.length && "IntersectionObserver" in window) {
    const seen = new WeakSet();
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting || seen.has(entry.target)) return;
          seen.add(entry.target);
          runCount(entry.target);
        });
      },
      { threshold: 0.45 }
    );
    stats.forEach(function (stat) {
      observer.observe(stat);
      stat.addEventListener("click", function () {
        runCount(stat);
      });
      stat.addEventListener("keydown", function (event) {
        if (event.key !== "Enter" && event.key !== " ") return;
        event.preventDefault();
        runCount(stat);
      });
    });
  } else {
    stats.forEach(runCount);
  }

  function bindPubChart() {
    const chart = document.querySelector(".pub-chart");
    if (!chart) return;
    const readout = chart.querySelector(".chart-readout");
    chart.querySelectorAll(".chart-bar").forEach(function (bar) {
      const show = function () {
        if (!readout) return;
        readout.textContent =
          bar.getAttribute("data-year") +
          ": " +
          bar.getAttribute("data-count") +
          " статей (макет)";
      };
      bar.addEventListener("mouseenter", show);
      bar.addEventListener("focus", show);
    });
  }
  bindPubChart();

  const LATTICE_STORE = "ichnm-lattice-v7";
  const LATTICE_DEFAULTS = {
    hexSize: 22,
    idleRadius: 0,
    peakRadius: 18,
    showHex: 0,
    hexLine: 0,
    randomEvery: 1500,
    waveDecay: 0.5,
    waveWidth: 90,
    waveSpeed: 590,
    waveLife: 4.3,
    randomAmp: 0.7,
    paletteStep: 0.09,
  };

  const PALETTE_STOPS = [
    [40, 167, 234],
    [70, 110, 230],
    [140, 80, 210],
    [210, 70, 140],
    [220, 72, 68],
    [232, 128, 48],
    [248, 252, 255],
    [46, 176, 96],
    [40, 167, 234],
  ];

  function paletteRgb(t) {
    const stops = PALETTE_STOPS;
    const u = ((t % 1) + 1) % 1;
    const n = stops.length - 1;
    const x = u * n;
    const i = Math.min(n - 1, Math.floor(x));
    const f = x - i;
    const a = stops[i];
    const b = stops[i + 1];
    return [
      Math.round(a[0] + (b[0] - a[0]) * f),
      Math.round(a[1] + (b[1] - a[1]) * f),
      Math.round(a[2] + (b[2] - a[2]) * f),
    ];
  }

  function loadLatticeConfig() {
    try {
      const raw = localStorage.getItem(LATTICE_STORE);
      const parsed = raw ? JSON.parse(raw) : {};
      const cfg = Object.assign({}, LATTICE_DEFAULTS, parsed);
      delete cfg.cursorMode;
      delete cfg.cursorSigma;
      delete cfg.trailStep;
      delete cfg.trailWidth;
      delete cfg.trailSpeed;
      delete cfg.trailLife;
      delete cfg.trailDecay;
      delete cfg.trailAmp;
      delete cfg.trailVelocityMin;
      delete cfg.negativeEvery;
      delete cfg.negativeAmp;
      delete cfg.randomQuiet;
      return cfg;
    } catch (err) {
      return Object.assign({}, LATTICE_DEFAULTS);
    }
  }

  function rgbToHsl(r, g, b) {
    r /= 255;
    g /= 255;
    b /= 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const l = (max + min) / 2;
    if (max === min) return [0, 0, l];
    const d = max - min;
    const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    let h = 0;
    if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
    else if (max === g) h = (b - r) / d + 2;
    else h = (r - g) / d + 4;
    return [h * 60, s, l];
  }

  function hslToRgb(h, s, l) {
    h = (((h % 360) + 360) % 360) / 360;
    if (s === 0) {
      const v = Math.round(l * 255);
      return [v, v, v];
    }
    const hue2rgb = function (p, q, t) {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    };
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    return [
      Math.round(hue2rgb(p, q, h + 1 / 3) * 255),
      Math.round(hue2rgb(p, q, h) * 255),
      Math.round(hue2rgb(p, q, h - 1 / 3) * 255),
    ];
  }

  function shiftRgb(r, g, b, hueDeg) {
    const hsl = rgbToHsl(r, g, b);
    return hslToRgb(hsl[0] - hueDeg, hsl[1], hsl[2]);
  }

  function cssRgb(r, g, b, a) {
    if (a === undefined || a >= 0.999) return "rgb(" + r + ", " + g + ", " + b + ")";
    return "rgba(" + r + ", " + g + ", " + b + ", " + a + ")";
  }

  function bakeSphereSprite(size, rgb) {
    const sheet = document.createElement("canvas");
    sheet.width = sheet.height = size;
    const g = sheet.getContext("2d");
    const cx = size / 2;
    const cy = size / 2;
    const r = size / 2 - 1.5;
    const tr = rgb[0];
    const tg = rgb[1];
    const tb = rgb[2];
    const mix = function (rr, gg, bb, a) {
      const k = 0.78;
      const r2 = Math.round(rr * (1 - k) + tr * k);
      const g2 = Math.round(gg * (1 - k) + tg * k);
      const b2 = Math.round(bb * (1 - k) + tb * k);
      return cssRgb(r2, g2, b2, a);
    };
    const paint = function (rr, gg, bb, a) {
      return mix(rr, gg, bb, a);
    };
    g.clearRect(0, 0, size, size);
    g.save();
    g.beginPath();
    g.arc(cx, cy, r, 0, Math.PI * 2);
    g.clip();
    const body = g.createRadialGradient(
      cx - r * 0.34,
      cy - r * 0.46,
      r * 0.04,
      cx + r * 0.06,
      cy + r * 0.16,
      r
    );
    body.addColorStop(0, paint(245, 252, 255));
    body.addColorStop(0.12, cssRgb(tr, tg, tb));
    body.addColorStop(0.38, cssRgb(tr, tg, tb));
    body.addColorStop(0.62, paint(14, 78, 132));
    body.addColorStop(0.86, paint(6, 36, 68));
    body.addColorStop(1, paint(2, 12, 28));
    g.fillStyle = body;
    g.fillRect(0, 0, size, size);
    const spec = g.createRadialGradient(
      cx - r * 0.3,
      cy - r * 0.4,
      0,
      cx - r * 0.3,
      cy - r * 0.4,
      r * 0.42
    );
    spec.addColorStop(0, "rgba(255, 255, 255, 0.95)");
    spec.addColorStop(0.18, "rgba(255, 255, 255, 0.55)");
    spec.addColorStop(0.55, paint(180, 230, 255, 0.12));
    spec.addColorStop(1, "rgba(255, 255, 255, 0)");
    g.globalCompositeOperation = "lighter";
    g.fillStyle = spec;
    g.beginPath();
    g.arc(cx, cy, r, 0, Math.PI * 2);
    g.fill();
    const rim = g.createRadialGradient(cx, cy, r * 0.62, cx, cy, r);
    rim.addColorStop(0, "rgba(0, 0, 0, 0)");
    rim.addColorStop(0.75, paint(90, 200, 255, 0));
    rim.addColorStop(1, paint(170, 230, 255, 0.55));
    g.fillStyle = rim;
    g.beginPath();
    g.arc(cx, cy, r, 0, Math.PI * 2);
    g.fill();
    g.restore();
    g.globalCompositeOperation = "destination-in";
    const edge = g.createRadialGradient(cx, cy, r * 0.92, cx, cy, r);
    edge.addColorStop(0, "#000");
    edge.addColorStop(1, "rgba(0,0,0,0)");
    g.fillStyle = edge;
    g.fillRect(0, 0, size, size);
    g.globalCompositeOperation = "source-over";
    return sheet;
  }

  function createLattice(host, canvas, initial) {
    const ctx = canvas.getContext("2d");
    if (!ctx) return null;
    let cfg = Object.assign({}, LATTICE_DEFAULTS, initial || {});
    const atoms = [];
    let waves = [];
    let lastTs = 0;
    let lastRandom = 700;
    let raf = 0;
    let visible = true;
    let paletteT = 0;
    let paletteShow = 0;
    const HUE_STEPS = 24;
    const sprites = [];
    for (let i = 0; i < HUE_STEPS; i++) {
      sprites.push(bakeSphereSprite(128, paletteRgb(i / HUE_STEPS)));
    }

    function maxReach(x, y) {
      const w = canvas.clientWidth;
      const h = canvas.clientHeight;
      return Math.max(
        Math.hypot(Math.max(x, w - x), Math.max(y, h - y)),
        1
      );
    }

    function trimWaves() {
      const cap = 56;
      if (waves.length <= cap) return;
      waves = waves.slice(waves.length - cap);
    }

    function advancePalette() {
      paletteT = (paletteT + Math.max(0.02, cfg.paletteStep || 0.09)) % 1;
    }

    function spawnWave(x, y, kind) {
      if (kind === "trail" || kind === "negative") return;
      waves.push({
        kind: kind,
        x: x,
        y: y,
        age: 0,
        amp: cfg.randomAmp,
        speed: cfg.waveSpeed,
        width: cfg.waveWidth,
        decay: cfg.waveDecay,
        life: cfg.waveLife,
        maxR: maxReach(x, y),
      });
      trimWaves();
    }

    function buildGrid(width, height) {
      atoms.length = 0;
      const size = cfg.hexSize;
      const colW = Math.sqrt(3) * size;
      const rowH = 1.5 * size;
      const cols = Math.ceil(width / colW) + 3;
      const rows = Math.ceil(height / rowH) + 3;
      for (let row = 0; row < rows; row++) {
        const ox = (row % 2) * (colW / 2);
        for (let col = 0; col < cols; col++) {
          atoms.push({
            x: col * colW + ox - colW,
            y: row * rowH - rowH * 0.25,
          });
        }
      }
    }

    function resize() {
      const rect = host.getBoundingClientRect();
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.max(1, Math.round(rect.width * dpr));
      canvas.height = Math.max(1, Math.round(rect.height * dpr));
      canvas.style.width = rect.width + "px";
      canvas.style.height = rect.height + "px";
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      buildGrid(rect.width, rect.height);
    }

    function sampleAtom(atom) {
      const idle = cfg.idleRadius;
      const peak = cfg.peakRadius;
      if (reduceMotion) return idle;
      let field = 0;
      for (let i = 0; i < waves.length; i++) {
        const wave = waves[i];
        const travel = wave.age * wave.speed;
        const dist = Math.hypot(atom.x - wave.x, atom.y - wave.y);
        const delta = dist - travel;
        const sigma = Math.max(wave.width, 1);
        const ring = Math.exp(-Math.pow(delta / sigma, 2));
        const fade = 1 - wave.decay * Math.min(1, travel / wave.maxR);
        const life = Math.max(0, 1 - wave.age / Math.max(wave.life, 0.1));
        field += wave.amp * ring * fade * (0.55 + 0.45 * life);
      }
      const lit = field <= 0 ? 0 : 1 - Math.exp(-field);
      return idle + (peak - idle) * lit;
    }

    function drawHexes() {
      if (!cfg.showHex || cfg.hexLine <= 0) return;
      const size = cfg.hexSize;
      ctx.beginPath();
      for (let i = 0; i < atoms.length; i++) {
        const a = atoms[i];
        for (let k = 0; k < 6; k++) {
          const ang = (Math.PI / 180) * (60 * k - 30);
          const px = a.x + size * Math.cos(ang);
          const py = a.y + size * Math.sin(ang);
          if (k === 0) ctx.moveTo(px, py);
          else ctx.lineTo(px, py);
        }
        ctx.closePath();
      }
      ctx.strokeStyle = "rgba(40, 167, 234," + cfg.hexLine + ")";
      ctx.lineWidth = 1.15;
      ctx.stroke();
    }

    function drawAtom(x, y, r) {
      if (r < 0.35) return;
      const idx = Math.max(
        0,
        Math.min(HUE_STEPS - 1, Math.round(paletteShow * HUE_STEPS) % HUE_STEPS)
      );
      const d = r * 2;
      ctx.drawImage(sprites[idx], x - r, y - r, d, d);
    }

    function draw(ts) {
      raf = requestAnimationFrame(draw);
      if (!visible) return;
      if (root.classList.contains("is-bvi")) {
        ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
        canvas.hidden = true;
        return;
      }
      canvas.hidden = false;
      const dt = lastTs ? Math.min(0.05, (ts - lastTs) / 1000) : 0.016;
      lastTs = ts;
      const width = canvas.clientWidth;
      const height = canvas.clientHeight;
      ctx.clearRect(0, 0, width, height);
      const wrap = ((paletteT - paletteShow + 1.5) % 1) - 0.5;
      paletteShow = (paletteShow + wrap * Math.min(1, dt * 2.4) + 1) % 1;
      drawHexes();
      for (let i = 0; i < atoms.length; i++) {
        const a = atoms[i];
        drawAtom(a.x, a.y, sampleAtom(a));
      }
      if (reduceMotion) return;
      waves = waves.filter(function (wave) {
        wave.age += dt;
        return wave.age < wave.life;
      });
      if (cfg.randomEvery > 0 && ts > lastRandom) {
        lastRandom = ts + cfg.randomEvery + Math.random() * cfg.randomEvery * 0.5;
        const pick = atoms[(Math.random() * atoms.length) | 0];
        if (pick) {
          spawnWave(pick.x, pick.y, "random");
          advancePalette();
        }
      }
    }

    resize();
    window.addEventListener("resize", resize);
    if ("ResizeObserver" in window) new ResizeObserver(resize).observe(host);
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        visible = entries.some(function (entry) {
          return entry.isIntersecting;
        });
      }).observe(host);
    }
    raf = requestAnimationFrame(draw);
    window.addEventListener("pagehide", function () {
      cancelAnimationFrame(raf);
    });

    return {
      getConfig: function () {
        return Object.assign({}, cfg);
      },
      setConfig: function (next) {
        cfg = Object.assign({}, cfg, next);
        resize();
      },
    };
  }

  function bindLatticeDemo(engine) {
    const form = document.getElementById("lattice-form");
    if (!form || !engine) return;
    const jsonOut = document.getElementById("lattice-json");
    const status = document.getElementById("lattice-status");

    function paint() {
      const cfg = engine.getConfig();
      Array.prototype.forEach.call(form.elements, function (el) {
        if (!el.name || !(el.name in cfg)) return;
        if (el.type === "checkbox") {
          el.checked = Boolean(Number(cfg[el.name]));
          return;
        }
        if (el.type === "radio") {
          el.checked = Number(cfg[el.name]) === Number(el.value);
          return;
        }
        el.value = cfg[el.name];
        const out = el.parentElement && el.parentElement.querySelector("output");
        if (out) out.textContent = String(cfg[el.name]);
      });
      if (jsonOut) jsonOut.textContent = JSON.stringify(cfg, null, 2);
    }

    function readForm() {
      const next = {};
      Array.prototype.forEach.call(form.elements, function (el) {
        if (!el.name) return;
        if (el.type === "checkbox") {
          next[el.name] = el.checked ? 1 : 0;
          return;
        }
        if (el.type === "radio") {
          if (el.checked) next[el.name] = Number(el.value);
          return;
        }
        next[el.name] = Number(el.value);
      });
      return next;
    }

    form.addEventListener("input", function () {
      engine.setConfig(readForm());
      paint();
    });
    form.addEventListener("click", function (event) {
      const btn = event.target.closest("[data-lattice-action]");
      if (!btn) return;
      const action = btn.getAttribute("data-lattice-action");
      if (action === "save") {
        const cfg = engine.getConfig();
        localStorage.setItem(LATTICE_STORE, JSON.stringify(cfg));
        if (status) status.textContent = "Сохранено в этом браузере. Главная подхватит профиль после обновления.";
      }
      if (action === "reset") {
        localStorage.removeItem(LATTICE_STORE);
        engine.setConfig(LATTICE_DEFAULTS);
        paint();
        if (status) status.textContent = "Сброшено к значениям по умолчанию.";
      }
      if (action === "copy") {
        const text = JSON.stringify(engine.getConfig(), null, 2);
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(function () {
            if (status) status.textContent = "JSON скопирован. Пришлите его в чат — применю в код.";
          });
        }
      }
      if (action === "download") {
        const blob = new Blob([JSON.stringify(engine.getConfig(), null, 2)], {
          type: "application/json",
        });
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = "ichnm-lattice-profile.json";
        a.click();
        URL.revokeObjectURL(a.href);
        if (status) status.textContent = "Файл скачан. Его тоже можно прислать в чат.";
      }
    });
    paint();
  }

  function bootLattices() {
    const cfg = loadLatticeConfig();
    const nodes = document.querySelectorAll(".hero, .lattice-stage");
    nodes.forEach(function (host) {
      const canvas = host.querySelector(".hero-lattice");
      if (!canvas || !(canvas instanceof HTMLCanvasElement)) return;
      const engine = createLattice(host, canvas, cfg);
      bindLatticeDemo(engine);
    });
  }
  bootLattices();

  function indexPrefix() {
    const url = document.body && document.body.getAttribute("data-search-index");
    if (!url) return "";
    const slash = url.lastIndexOf("/");
    return slash >= 0 ? url.slice(0, slash + 1) : "";
  }

  function esc(value) {
    return String(value).replace(/[&<>"']/g, function (ch) {
      return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[ch];
    });
  }

  function matchSearch(query, items) {
    const needle = query.trim().toLowerCase();
    if (needle.length < 2) return [];
    return items.filter(function (row) {
      return String(row.text || row.title || "")
        .toLowerCase()
        .indexOf(needle) !== -1;
    });
  }

  function renderSearchGroups(rows, prefix) {
    if (!rows.length) {
      return '<p class="search-hint">Ничего не найдено. Попробуйте фамилию, лабораторию, прибор или разработку.</p>';
    }
    const labels = {
      person: "Персоналии",
      unit: "Подразделения",
      facility: "Приборы",
      development: "Разработки",
    };
    const order = ["person", "unit", "facility", "development"];
    const buckets = { person: [], unit: [], facility: [], development: [] };
    rows.forEach(function (row) {
      if (buckets[row.kind]) buckets[row.kind].push(row);
    });
    return order
      .filter(function (kind) {
        return buckets[kind].length;
      })
      .map(function (kind) {
        const links = buckets[kind]
          .map(function (row) {
            const href = prefix + row.href;
            const lead = row.lead ? "<small>" + esc(row.lead) + "</small>" : "";
            return "<a href=\"" + esc(href) + "\">" + esc(row.title) + lead + "</a>";
          })
          .join("");
        return (
          '<section class="search-group"><h2>' +
          labels[kind] +
          "</h2>" +
          links +
          "</section>"
        );
      })
      .join("");
  }

  let searchIndex = null;

  function loadSearchIndex(done) {
    if (searchIndex) {
      done(searchIndex);
      return;
    }
    const url = document.body && document.body.getAttribute("data-search-index");
    if (!url || !window.fetch) {
      done([]);
      return;
    }
    fetch(url)
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        searchIndex = Array.isArray(data) ? data : [];
        done(searchIndex);
      })
      .catch(function () {
        done([]);
      });
  }

  function bindSearch() {
    const overlay = document.getElementById("site-search");
    const liveHost = document.getElementById("search-live");
    const liveInput = document.getElementById("q-live");
    const pageHost = document.getElementById("search-results");
    const pageInput = document.getElementById("q-page");
    const openBtns = document.querySelectorAll("[data-search-open]");
    const prefix = indexPrefix();

    function setOpen(open) {
      if (!overlay) return;
      overlay.hidden = !open;
      root.classList.toggle("is-search-open", open);
      openBtns.forEach(function (btn) {
        btn.setAttribute("aria-expanded", open ? "true" : "false");
      });
      if (open && liveInput) liveInput.focus();
    }

    openBtns.forEach(function (btn) {
      btn.setAttribute("aria-expanded", "false");
      btn.addEventListener("click", function () {
        setOpen(true);
      });
    });
    document.querySelectorAll("[data-search-close]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        setOpen(false);
      });
    });
    if (overlay) {
      overlay.addEventListener("click", function (event) {
        if (event.target === overlay) setOpen(false);
      });
    }
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") setOpen(false);
    });

    function paint(host, query) {
      if (!host) return;
      const q = query.trim();
      if (q.length < 2) {
        host.innerHTML =
          '<p class="search-hint">Введите не меньше двух букв: персоналии, подразделения, разработки.</p>';
        return;
      }
      loadSearchIndex(function (items) {
        host.innerHTML = renderSearchGroups(matchSearch(q, items), prefix);
      });
    }

    if (liveInput) {
      liveInput.addEventListener("input", function () {
        paint(liveHost, liveInput.value);
      });
    }
    if (pageHost) {
      const initial = new URLSearchParams(window.location.search).get("q") || "";
      if (pageInput && initial) pageInput.value = initial;
      if (initial) paint(pageHost, initial);
      if (pageInput) {
        pageInput.addEventListener("input", function () {
          paint(pageHost, pageInput.value);
        });
      }
    }
  }
  bindSearch();

  function enterPage() {
    if (reduceMotion || root.classList.contains("is-bvi")) {
      root.classList.add("is-entered");
      root.classList.remove("js-motion");
      return;
    }
    requestAnimationFrame(function () {
      root.classList.add("is-entered");
    });
  }
  enterPage();
})();
