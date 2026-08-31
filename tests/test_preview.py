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
    assert 'href="site.css' in home
    assert "Facebook" in home
    assert "Roboto" in home
    assert "Montserrat" in home
    assert "#28a7ea" in files["site.css"]
    assert "Версия для слабовидящих" in home
    assert "К содержанию" in home
    assert "media/ichnm-mark.svg" in home
    assert 'width="397"' in home
    assert "hero-figure" not in home
    assert "arc-blue" not in home
    assert "animateMotion" not in home
    assert "Основные направления" in home
    assert "Опыт работы" in home
    assert "data-count-to" in home
    assert "1998" in home
    assert "3d_printing" not in home
    assert "Материалы для 3D" not in home


def test_icnm_mark_uses_bew_vector():
    from pathlib import Path

    svg = (Path(__file__).resolve().parents[1] / "assets" / "brand" / "ichnm-mark.svg").read_text(
        encoding="utf-8"
    )
    assert "#9d1c2d" in svg
    assert "#0e4c85" in svg
    assert "#1f2427" in svg


def test_language_structure_mirrors_russian_ia():
    model = load_site_model()
    files = render_site_files(model)
    en = files["en/index.html"]
    assert 'lang="en"' in en
    assert "Institute of Chemistry of New Materials" in en
    assert "English version in preparation" not in en
    assert "Research areas" in files["en/science.html"]
    assert "Structure" in files["en/structure.html"]
    assert "labs/nano/index.html" in files["en/structure.html"]
    assert "Laboratory of Micro- and Nanostructured Systems" in files["en/labs/nano/index.html"]
    assert "../site.css" in files["en/about.html"]
    assert "../../../site.css" in files["en/labs/nano/index.html"]
    assert "../../../labs/nano/index.html" in files["en/labs/nano/index.html"]  # RU switch
    assert "Пра інстытут" in files["be/about.html"]
    assert "机构设置" in files["zh/structure.html"]
    assert "Font size" in en
    assert "Standard version" in en
    assert "On this site" in en
    assert "Cookie policy" in files["en/cookies.html"]
    assert 'lang="en"' in files["en/cookies.html"]
    assert "Шрыфт" in files["be/index.html"]
    assert "标准版" in files["zh/index.html"]
    assert "Версия для слабовидящих" not in en
    assert "Политика cookie" in files["cookies.html"]
    assert files["en/search.json"]
    aist = files["aist.html"]
    assert "aist.ichnm.by" in aist
    assert "conf-card" in aist
    assert "conf-hero" in aist
    assert "conferences/aist-2025/index.html" in aist
    assert "conferences/aist-2009/index.html" in aist
    assert "media/aist/PROGRAMMA_AIST_2025.pdf" in aist
    assert "sidorenko@ichnm.by" in aist
    kids = files["news/children-day-2025/index.html"]
    assert "первичной профсоюзной организации" in kids
    assert "media/news/children-day-2025/image001.jpg" in kids
    assert "появятся после передачи" not in kids
    assert "Королёва Елена Вадимовна" in files["news/sinyutich-defense-2025/index.html"]
    assert "Бею Максиму Петровичу" in files["news/scientific-council-docent-2024/index.html"]
    assert "media/aist/Sbornic_2023.pdf" in files["conferences/aist-2023/index.html"]
    css = files["site.css"]
    assert "html.theme-night .conf-hero," in css
    assert "html.theme-night .conf-hero .hero-pill" in css
    assert "--palette2: #8ec4f0" in css
    assert "Главная" in files["about.html"]
    assert "is-locale-stub" not in files["en/index.html"]



def test_inner_pages_use_cards_and_empty_states():
    files = render_site_files(load_site_model())
    news = files["news.html"]
    assert "news-card" in news
    assert "СИНЮТИЧ" in news
    assert "Главная" in news
    contacts = files["contacts.html"]
    assert "Михайловский" in contacts
    assert "map-slot" in contacts
    assert "city-map" in contacts
    assert "map-pin" in contacts
    assert "empty-state" in files["vacancies.html"]
    assert "1999" in files["aspirantura.html"]
    assert "cover-card" in files["developments.html"]
    assert "На сайте" in files["index.html"]


def test_department_page_uses_staff_tab_and_personal_pages():
    model = load_site_model()
    files = render_site_files(model)
    structure = files["structure.html"]
    assert "Вкладка сотрудников" not in structure
    assert "Лаборатории" in structure
    assert "Административные подразделения" in structure
    assert "Общественные объединения" in structure
    lab = files["labs/nano/index.html"]
    assert "Наша команда" in lab
    assert "персональной странице" in lab
    assert 'data-metric="orcid"' not in lab
    assert 'href="person.html"' not in structure
    assert "Входит в ИХНМ" not in lab
    assert "Ко всем подразделениям" in lab
    assert "people/kulikouskaya/index.html" in lab
    assert 'id="directions"' in lab
    assert "Отдельных персональных страниц нет" not in lab
    person = files["people/kulikouskaya/index.html"]
    assert "ORCID" in person
    assert "orcid.org/0000-0001-6505-3929" in person


def test_media_about_lists_sourced_press_and_navuka():
    html = render_site_files(load_site_model())["media_about.html"]
    assert "gazeta-navuka.by" in html
    assert "На опытных участках" in html or "опытных участках" in html
    assert "sb.by" in html
    assert "belta.by" in html
    assert "Внешние СМИ" in html
    assert "Газета «Навука»" in html
    news = render_site_files(load_site_model())["news.html"]
    assert "СМИ о нас" in news
    assert "Внешние СМИ" in news


def test_leadership_official_pages_link_whole_card():
    files = render_site_files(load_site_model())
    listing = files["leadership.html"]
    assert "Рогачёв" in listing or "Рогачев" in listing
    assert "people/rogachev/index.html" in listing
    assert "Биография и публикации" not in listing
    assert "Михайловский" in listing
    assert "Луковская" in listing
    profile = files["people/rogachev/index.html"]
    assert "Биография" in profile
    assert "Научные интересы" in profile
    assert "Избранные публикации" in profile
    assert "Место для официального фото" in profile
    assert "labs/nano/index.html" in profile
    assert "leadership.html" in profile
    assert "Агабеков" in files["people/agabekov/index.html"]
    assert "Игнатович" in files["people/ignatovich/index.html"]
    assert "Зураев" in files["people/zuraev/index.html"]
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
    assert "Отдельных персональных страниц нет" not in lab
    assert "people/kulikouskaya/index.html" in lab
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
    assert "nav-community-start" in files["index.html"]
    assert "labs/nano/index.html" in files["index.html"]
    assert "viewBox=\"0 0 24 24\"" in files["index.html"]
    assert "\u00a0Скорины" in files["index.html"]
    assert "engineering.html" in files
    assert "accounting.html" in files
    assert "Тихонов" in files["engineering.html"]
    assert "Бабко" in files["accounting.html"]
    assert files["engineering.html"].count('class="leader-card"') == 1
    assert files["accounting.html"].count('class="leader-card"') == 1
    assert "people/tikhonov/index.html" in files["engineering.html"]
    assert "people/babko/index.html" in files["accounting.html"]
    assert "people/chief-engineer" not in files["engineering.html"]
    assert "people/chief-accountant" not in files["accounting.html"]
    assert "Тихонов" not in files["leadership.html"]
    assert "Бабко" not in files["leadership.html"]
    assert "people/tikhonov/index.html" in files
    assert "people/babko/index.html" in files
    assert "people/chief-engineer/index.html" not in files
    assert "people/chief-accountant/index.html" not in files
    assert "Ко всем подразделениям" in files["hr.html"]
    assert "office-hr-head.html" in files
    assert "smu-chair.html" in files
    assert "people/hr-head/index.html" in files["hr.html"]
    assert "people/smu-chair/index.html" in files["young-scientists.html"]
    assert "people/chair/index.html" in files["scientific-council.html"]
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
    assert "Направления работы" in files["science.html"]
    assert "Общеинститутские направления" in files["science.html"]
    assert "Направления лабораторий" in files["science.html"]
    assert "nano-carriers" in files["science.html"]
    assert "Научно-ориентированное образование" in files["index.html"]
    assert "union.html" in files["index.html"]
    assert "young-scientists.html" in files["index.html"]
    assert "Оборудование лабораторий" in files["facilities.html"]
    assert "vaktime-plasma-lab" in files["facilities.html"]
    assert "people/rogachev/index.html" in files["facilities.html"] or "Рогачёв" in files["facilities.html"]
    assert "Публикации лабораторий" in files["publications.html"]
    assert "world-map" in files["cooperation.html"]
    assert "Центры и договоры" not in files["cooperation.html"]
    assert "world.jpg" not in files["cooperation.html"]
    assert "world-outline" in files["cooperation.html"]
    assert "world-land" in files["cooperation.html"]
    assert "M58 78c62-38" not in files["cooperation.html"]
    assert "Партнёры" in files["cooperation.html"]
    assert "Борескова" in files["cooperation.html"]
    assert "chart-sma" in files["publications.html"]
    assert 'pathLength="1"' in files["publications.html"]
    assert 'id="pub-year-2024"' in files["publications.html"]
    assert 'class="pub-year"' in files["labs/nano/index.html"]
    assert "lab-equip-card" in files["labs/nano/index.html"]
    assert "cover-grid is-3" in files["labs/nano/index.html"]
    assert "nav-parent" in files["index.html"]
    assert "nav-folder" in files["index.html"]
    assert "Научная деятельность" in files["index.html"]
    assert 'class="nav-folder"' in files["index.html"]
    assert "photo-slot" in files["scientific-council.html"]
    assert "announcement.html" not in files
    assert "picto-strip" in files["index.html"]
    assert "media/footer/president.gif" in files["index.html"]
    assert "city-map" in files["index.html"]
    assert "data-pin-left" in files["index.html"]
    assert "media/maps/institute.svg" not in files["index.html"]
    assert "cookie-banner" in files["index.html"]
    assert "data-theme-toggle" in files["index.html"]
    assert "bvi-panel" in files["index.html"]
    assert "cookies.html" in files
    assert "personal-data.html" in files
    assert "fish-banner" in files["cookies.html"]
    assert "conferences/aist-2025/index.html" in files
    assert "РЕАКТИВ" in files["events.html"]
    assert "БелСЗМ" in files["aist.html"]
    assert 'id="institute-news"' in files["news.html"]
    assert 'id="smi"' in files["news.html"]
    assert "gazeta-navuka.by" in files["news.html"]
    assert "nav-self" in files["index.html"]
    assert "страница раздела" not in files["index.html"]
    assert "nav-more-label" not in files["index.html"]
    assert "feedback.html" in files["contacts.html"]
    assert "requisites.html" in files["contacts.html"]
    assert "cover-grid is-3" in files["science.html"]
    assert "cover-grid is-3" in files["facilities.html"]
    assert "cover-grid is-3" in files["developments.html"]
    assert "doi.org/10.0000/ichnm.mock.nano-1" in files["publications.html"]
    assert "people/kulikouskaya" not in files["publications.html"]
    assert "orcid.org/0000-0001-6505-3929" in files["people/kulikouskaya/index.html"]
    assert "Наукометрия" in files["people/kulikouskaya/index.html"] or "Профили и показатели" in files["people/rogachev/index.html"]
    assert "vacancies.html" in files["index.html"]
    assert "facilities.html" in files["index.html"]
    assert "publications.html" in files["index.html"]
    assert "research.html" not in files
    assert "Страницы раздела" in files["about.html"]
    assert "structure.html" in files["about.html"]
    assert "facilities.html" in files["about.html"]
    assert "vacancies.html" in files["about.html"]
    assert "section-jump" in files["news.html"]
    assert 'href="#institute-news"' in files["news.html"]
    assert 'href="#smi"' in files["news.html"]
    assert "news.html#smi" in files["index.html"]
    assert "--header-fg" in files["site.css"]
    assert 'content: "+"' in files["site.css"]
    assert "nav-folder" in files["site.css"]
    assert "li.has-children > .nav-parent::after" in files["site.css"]
    assert "setSubmenuOpen" in files["site.js"]
    assert "research.html" not in files["sitemap.html"]
    assert "body.is-home .site-header.is-scrolled" in files["site.css"]
    assert ".site-header .ichnm-menu > ul > li > a:visited" in files["site.css"]


def test_search_sitemap_and_page_veil_exist():
    files = render_site_files(load_site_model())
    assert "search.html" in files
    assert "sitemap.html" in files
    assert "search.json" in files
    home = files["index.html"]
    assert 'data-search-open' in home
    assert "Карта сайта" in home
    assert "page-veil" in home
    assert "js-motion" in home
    assert "@keyframes veil-wipe" in files["site.css"]
    assert "rise-in" in files["site.css"]
    assert "bindSearch" in files["site.js"] or "data-search-open" in files["site.js"]
    sitemap = files["sitemap.html"]
    assert "Карта сайта" in sitemap
    assert "labs/nano/index.html" in sitemap
    search_page = files["search.html"]
    assert "Персоналии" in search_page or "персоналиям" in search_page
    payload = files["search.json"]
    assert '"kind": "person"' in payload
    assert '"kind": "unit"' in payload
    assert '"kind": "facility"' in payload
    assert '"kind": "development"' in payload
    assert "vaktime-plasma-lab" in payload
    assert "Рогачёв" in payload or "Рогачев" in payload
    assert "empty-state" in files["vacancies.html"]
    assert "Место для официального фото" in files["people/rogachev/index.html"]


def test_filled_preview_is_letter_coded_and_separate():
    from src.core.copy import is_filled_copy, use_copy
    from src.core.filled import filled_copy

    honest = render_site_files(load_site_model())
    with use_copy(filled_copy()):
        assert is_filled_copy()
        filled = render_site_files(load_site_model())
    assert not is_filled_copy()
    nano = filled["labs/nano/index.html"]
    films = filled["labs/films/index.html"]
    assert "Адамович" in nano
    assert "Аэрогелевые" in nano
    assert "Борисова" in films
    assert "Адамович" not in films
    assert "Борисова" not in nano
    assert "Васильева" in filled["labs/lcd/index.html"]
    assert "Громова" in filled["labs/composites/index.html"]
    assert "Дмитриева" in filled["labs/woodchem/index.html"]
    assert any(path.startswith("media/fish/") and path.endswith(".svg") for path in filled)
    assert "photo-slot is-filled" in nano
    assert "Макетный файл" in filled["charter.html"]
    assert "младший научный сотрудник" in filled["vacancies.html"]
    assert "empty-state" not in filled["vacancies.html"]
    assert "Наполненный макет" in filled["index.html"]
    assert "Ильина" in filled["young-scientists.html"]
    assert "Фамилия Имя Отчество" in honest["young-scientists.html"]
    assert "Место для официального фото" in honest["people/rogachev/index.html"]
    assert "Адамович" not in honest["labs/nano/index.html"]
    people_page = filled["people/lab-nano-fish-0/index.html"]
    assert "Адамович" in people_page
    assert "../../media/fish/" in people_page
    assert "h-индекс" in people_page
    assert "orcid.org" in people_page
    assert "doi.org/10.0000/ichnm.nano" in filled["publications.html"]
    assert "people/lab-nano-fish" not in filled["publications.html"]
    assert "Рогачёв" in filled["search.json"] or "Рогачев" in filled["search.json"]
    assert "Адамович" in filled["search.json"]


def test_mobile_header_keeps_a_single_bar():
    files = render_site_files(load_site_model())
    home = files["index.html"]
    css = files["site.css"]
    js = files["site.js"]
    assert 'class="header-panel"' in home
    assert 'class="header-utilities"' in home
    assert 'class="brand-title-short"' in home
    assert "ИХНМ" in home
    assert ":has(#primary-nav.is-open)" in css
    assert "@media (min-width: 1181px)" in css
    assert "nav-open" in js
    tools_chunk = home.split('class="header-tools"', 1)[1].split("header-panel", 1)[0]
    assert "data-search-open" not in tools_chunk
    assert "data-nav-toggle" in tools_chunk
    assert "data-search-open" in home


def test_hig_chrome_safe_area_contrast_and_dismiss():
    files = render_site_files(load_site_model())
    home = files["index.html"]
    css = files["site.css"]
    js = files["site.js"]
    assert "viewport-fit=cover" in home
    assert "env(safe-area-inset-top)" in css
    assert "prefers-contrast: more" in css
    assert "aria-modal" in home
    assert "searchOpener" in js
    assert "toggle.focus" in js
    assert "(hover: hover)" in css
