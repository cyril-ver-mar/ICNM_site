"""Preview HTML is a view of the site-model seam, not a second information architecture."""

from __future__ import annotations

from src.core.preview import render_site_files
from src.core.site_model import load_site_model


def test_homepage_preview_has_identity_menu_footer_and_no_3d():
    model = load_site_model()
    files = render_site_files(model)
    home = files["index.html"]
    assert "Институт химии новых материалов" in home.replace("\u00a0", " ")
    assert "Государственное научное учреждение" in home
    assert "Национальная академия наук Беларуси" in home
    for title in model.top_menu_titles():
        assert title in home
    assert "https://president.gov.by/" in home
    assert "https://www.pravo.by/" in home
    assert "data-ichnm-block=\"official_intro\"" in home
    assert "data-ichnm-block=\"news\"" in home
    assert "site.css" in files
    assert "site.js" in files
    assert 'href="site.css"' in home
    assert "Facebook" in home
    assert "Roboto" in home
    assert "Montserrat" in home
    assert "#28a7ea" in files["site.css"]
    assert "Версия для слабовидящих" in home
    assert "К содержанию" in home
    assert "media/ichnm-mark.svg" in home
    assert "hero-figure" not in home
    assert "arc-blue" not in home
    assert "animateMotion" not in home
    assert "Основные направления" in home
    assert "Опыт работы" in home
    assert "data-count-to" in home
    assert "1998" in home
    assert "3d_printing" not in home
    assert "Материалы для 3D" not in home


def test_language_stubs_and_aist():
    model = load_site_model()
    files = render_site_files(model)
    for prefix in ("en", "be", "zh"):
        stub = files[f"{prefix}/index.html"]
        assert "Версия готовится" in stub
    aist = files["aist.html"]
    assert "aist.ichnm.by" in aist
    assert "conf-card" in aist
    assert "conferences/aist-2025/index.html" in aist
    assert "Главная" in files["about.html"]


def test_inner_pages_use_cards_and_empty_states():
    files = render_site_files(load_site_model())
    news = files["news.html"]
    assert "news-card" in news
    assert "СИНЮТИЧ" in news
    assert "Главная" in news
    contacts = files["contacts.html"]
    assert "Михайловский" in contacts
    assert "map-slot" in contacts
    assert "empty-state" in files["vacancies.html"]
    assert "1999" in files["aspirantura.html"]
    assert "cover-card" in files["developments.html"]
    assert "На сайте" in files["index.html"]


def test_department_page_uses_staff_tab_not_personal_sites():
    model = load_site_model()
    files = render_site_files(model)
    structure = files["structure.html"]
    assert "Вкладка сотрудников" not in structure
    assert "Лаборатории" in structure
    assert "Административные подразделения" in structure
    lab = files["labs/nano/index.html"]
    assert 'data-metric="orcid"' in lab
    assert "ORCID" in lab
    assert "Наша команда" in lab
    assert 'href="person.html"' not in structure
    assert "Входит в ИХНМ" not in lab
    assert "Ко всем подразделениям" in lab


def test_media_about_lists_sourced_press_and_navuka():
    html = render_site_files(load_site_model())["media_about.html"]
    assert "gazeta-navuka.by" in html
    assert "На опытных участках" in html or "опытных участках" in html
    assert "sb.by" in html
    assert "belta.by" in html
    assert "Внешние СМИ" in html
    assert "Газета «Навука»" in html


def test_leadership_official_pages_not_lab_minisites():
    files = render_site_files(load_site_model())
    listing = files["leadership.html"]
    assert "Рогачёв" in listing or "Рогачев" in listing
    assert "leadership-rogachev.html" in listing
    assert "Биография и публикации" in listing
    profile = files["leadership-rogachev.html"]
    assert "Биография" in profile
    assert "Научные интересы" in profile
    assert "Избранные публикации" in profile
    assert "Место для официального фото" in profile
    assert "Агабеков" in files["leadership-agabekov.html"]
    assert "Игнатович" in files["leadership-ignatovich.html"]
    assert "Зураев" in files["leadership-zuraev.html"]
    assert "person.html" not in files


def test_nas_emblem_lattice_lab_packs_and_publication_chart():
    files = render_site_files(load_site_model())
    home = files["index.html"]
    assert "media/nas-emblem.webp" in home
    assert "hero-lattice" in home
    assert "class=\"nas-emblem\"" in home
    assert "labs/nano/index.html" in files
    lab = files["labs/nano/index.html"]
    assert "people-list" in lab
    assert "leader-card" in lab
    assert "Наша команда" in lab
    assert "Оборудование" in lab
    assert "Отдельных персональных страниц нет" in lab
    assert "is-lab-site" in lab
    assert "../../index.html" in lab
    assert "fish-banner" in files["charter.html"]
    assert "file-slot" in files["charter.html"]
    assert "file-slot" in files["anti-corruption.html"]
    pubs = files["publications.html"]
    assert "pub-year-data" in pubs
    assert "pub-chart" in pubs
    assert "Макет графика" in pubs
    assert "people-list" in files["lab-nano.html"]
    assert "person.html" not in files
    assert "lattice-demo.html" in files
    demo = files["lattice-demo.html"]
    assert "ichnm-lattice-v7" in files["site.js"]
    assert "paletteStep" in demo
    assert "PALETTE_STOPS" in files["site.js"]
    assert "emitTrail" not in files["site.js"]
    assert "bounceWaves" not in files["site.js"]
    assert "lattice-boundary.html" not in files
    assert "nightByClock" in files["site.js"]
    assert "cookies-ok" in files["index.html"]
    assert "people-list" in files["leadership.html"]
    assert "Приёмная и администрация" not in files["leadership.html"]
    assert "repeat(2" in files["site.css"]
    assert "nav-admin-start" in files["index.html"]
    assert "labs/nano/index.html" in files["index.html"]
    assert "viewBox=\"0 0 24 24\"" in files["index.html"]
    assert "\u00a0Скорины" in files["index.html"]
    assert "engineering.html" in files
    assert "accounting.html" in files
    assert "Тихонов" in files["engineering.html"]
    assert "Бабко" in files["accounting.html"]
    assert "Ко всем подразделениям" in files["hr.html"]
    assert "office-hr-head.html" in files
    assert "smu-chair.html" in files
    assert "office-hr-head.html" in files["hr.html"]
    assert "smu-chair.html" in files["young-scientists.html"]
    assert "council-chair.html" in files["scientific-council.html"]
    assert "council-chair.html" in files
    assert "Место для официального фото" in files["council-chair.html"]
    assert "trimWaves" in files["site.js"]
    assert "lattice-form" in demo
    assert 'class="tool-btn" href=' in files["index.html"]
    assert "Написать нам" in files["index.html"]
    tool_btn_block = files["site.css"].split("\n.tool-btn,\n.menu-toggle {", 1)[1].split("}", 1)[0]
    assert "display: inline-flex" in tool_btn_block
    assert "text-decoration: none" in tool_btn_block
    assert "news/sinyutich-defense-2025/index.html" in files
    assert "cover-card" in files["science.html"]
    assert "world-map" in files["cooperation.html"]
    assert "Центры и договоры" in files["cooperation.html"]
    assert "world.jpg" in files["cooperation.html"]
    assert "Борескова" in files["cooperation.html"]
    assert "chart-sma" in files["publications.html"]
    assert "photo-slot" in files["scientific-council.html"]
    assert "announcement.html" not in files
    assert "picto-strip" in files["index.html"]
    assert "media/footer/president.gif" in files["index.html"]
    assert "media/maps/institute.svg" in files["index.html"]
    assert "cookie-banner" in files["index.html"]
    assert "data-theme-toggle" in files["index.html"]
    assert "bvi-panel" in files["index.html"]
    assert "cookies.html" in files
    assert "personal-data.html" in files
    assert "fish-banner" in files["cookies.html"]
    assert "conferences/aist-2025/index.html" in files
    assert "РЕАКТИВ" in files["events.html"]
    assert "БелСЗМ" in files["aist.html"]
