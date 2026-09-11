<?php
/**
 * Kadence child: NAS identity, language switcher, primary menu, stable footer pack.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', static function (): void {
    $parent_style = get_template_directory() . '/style.css';
    if (is_readable($parent_style)) {
        wp_enqueue_style(
            'kadence',
            get_template_directory_uri() . '/style.css',
            [],
            wp_get_theme('kadence')->get('Version')
        );
    }
    wp_enqueue_style(
        'ichnm-kadence',
        get_stylesheet_uri(),
        is_readable($parent_style) ? ['kadence'] : [],
        wp_get_theme()->get('Version')
    );
    $chrome_js = get_stylesheet_directory() . '/assets/chrome.js';
    if (is_readable($chrome_js)) {
        wp_enqueue_script(
            'ichnm-chrome',
            get_stylesheet_directory_uri() . '/assets/chrome.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
        if (function_exists('ichnm_search_catalog')) {
            wp_add_inline_script(
                'ichnm-chrome',
                'window.ichnmSearchIndex = ' . wp_json_encode(ichnm_search_catalog()) . ';',
                'before'
            );
        }
    }
});

function ichnm_theme_model(): array
{
    return function_exists('ichnm_site_model') ? ichnm_site_model() : [];
}

/**
 * Chrome / utility UI strings for the active language (structure shells; body stays RU).
 *
 * @return array<string, string>
 */
function ichnm_chrome_strings(?string $lang = null): array
{
    $lang = $lang ?: (function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru');
    if ($lang === '') {
        $lang = 'ru';
    }
    $pack = [
        'ru' => [
            'lang_aria' => 'Язык',
            'bvi' => 'Версия для слабовидящих',
            'search' => 'Поиск',
            'sitemap' => 'Карта сайта',
            'write' => 'Написать нам',
            'menu' => 'Меню',
            'search_aria' => 'Поиск',
            'search_label' => 'Поиск по сайту',
            'search_placeholder' => 'Персоналии, подразделения, приборы, разработки',
            'find' => 'Найти',
            'close' => 'Закрыть',
            'search_hint' => 'Введите не меньше двух букв. Есть и отдельная страница результатов.',
            'nas' => 'Национальная академия наук Беларуси',
            'cookies' => 'Политика cookie',
            'personal' => 'Персональные данные',
            'sections' => 'Разделы',
        ],
        'en' => [
            'lang_aria' => 'Language',
            'bvi' => 'Visually impaired version',
            'search' => 'Search',
            'sitemap' => 'Sitemap',
            'write' => 'Contact us',
            'menu' => 'Menu',
            'search_aria' => 'Search',
            'search_label' => 'Site search',
            'search_placeholder' => 'People, units, facilities, developments',
            'find' => 'Search',
            'close' => 'Close',
            'search_hint' => 'Enter at least two characters. A full results page is also available.',
            'nas' => 'National Academy of Sciences of Belarus',
            'cookies' => 'Cookie policy',
            'personal' => 'Personal data',
            'sections' => 'Sections',
        ],
        'be' => [
            'lang_aria' => 'Мова',
            'bvi' => 'Версія для слабавідушчых',
            'search' => 'Пошук',
            'sitemap' => 'Карта сайта',
            'write' => 'Напісаць нам',
            'menu' => 'Меню',
            'search_aria' => 'Пошук',
            'search_label' => 'Пошук па сайце',
            'search_placeholder' => 'Персаналіі, падраздзяленні, прыборы, распрацоўкі',
            'find' => 'Знайсці',
            'close' => 'Закрыць',
            'search_hint' => 'Увядзіце не менш за дзве літары. Ёсць і асобная старонка вынікаў.',
            'nas' => 'Нацыянальная акадэмія навук Беларусі',
            'cookies' => 'Палітыка cookie',
            'personal' => 'Персанальныя даныя',
            'sections' => 'Раздзелы',
        ],
        'zh' => [
            'lang_aria' => '语言',
            'bvi' => '视力障碍版本',
            'search' => '搜索',
            'sitemap' => '网站地图',
            'write' => '联系我们',
            'menu' => '菜单',
            'search_aria' => '搜索',
            'search_label' => '站内搜索',
            'search_placeholder' => '人员、单位、设备、研发成果',
            'find' => '搜索',
            'close' => '关闭',
            'search_hint' => '请至少输入两个字符。也可打开完整结果页。',
            'nas' => '白俄罗斯国家科学院',
            'cookies' => 'Cookie 政策',
            'personal' => '个人数据',
            'sections' => '栏目',
        ],
    ];
    return $pack[$lang] ?? $pack['ru'];
}

/**
 * Hub kicker labels for CPT singles.
 *
 * @return array{label:string,href:string}
 */
function ichnm_hub_kicker(string $hub_slug): array
{
    $labels = [
        'news' => ['ru' => 'Новости', 'en' => 'News', 'be' => 'Навіны', 'zh' => '新闻'],
        'events' => ['ru' => 'Мероприятия', 'en' => 'Events', 'be' => 'Мерапрыемствы', 'zh' => '活动'],
        'publications' => ['ru' => 'Публикации', 'en' => 'Publications', 'be' => 'Публікацыі', 'zh' => '出版物'],
        'leadership' => ['ru' => 'Руководство', 'en' => 'Leadership', 'be' => 'Кіраўніцтва', 'zh' => '领导班子'],
        'structure' => ['ru' => 'Структура', 'en' => 'Structure', 'be' => 'Структура', 'zh' => '机构设置'],
        'media_about' => ['ru' => 'СМИ о нас', 'en' => 'Media about us', 'be' => 'СМІ пра нас', 'zh' => '媒体报道'],
    ];
    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
    if ($lang === '') {
        $lang = 'ru';
    }
    $page_slug = $hub_slug === 'media_about' ? 'news' : $hub_slug;
    $page = ichnm_translated_page($page_slug);
    $href = $page instanceof WP_Post
        ? (string) get_permalink($page)
        : home_url('/' . rawurlencode($page_slug) . '/');
    $label = $labels[$hub_slug][$lang] ?? ($labels[$hub_slug]['ru'] ?? $hub_slug);
    return ['label' => $label, 'href' => $href];
}

function ichnm_translated_page(string $slug): ?WP_Post
{
    $ru = null;
    if (function_exists('ichnm_find_ru_page')) {
        $ru = ichnm_find_ru_page($slug);
    }
    if (!$ru instanceof WP_Post) {
        $ru = get_page_by_path($slug);
    }
    if (!$ru instanceof WP_Post) {
        return null;
    }
    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
    if ($lang === '' || $lang === 'ru' || !function_exists('pll_get_post')) {
        return $ru;
    }
    $translated = (int) pll_get_post((int) $ru->ID, $lang);
    if ($translated > 0) {
        $post = get_post($translated);
        if ($post instanceof WP_Post && $post->post_status === 'publish') {
            return $post;
        }
    }
    return $ru;
}

/**
 * Bind institute menu only to the custom chrome location (not Kadence primary).
 */
function ichnm_bind_primary_menu(): void
{
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) {
        $locations = [];
    }
    $menu = wp_get_nav_menu_object('Главное меню ИХНМ');
    $menu_id = $menu ? (int) $menu->term_id : (int) ($locations['ichnm-primary'] ?? 0);
    if ($menu_id <= 0) {
        return;
    }
    $changed = false;
    if ((int) ($locations['ichnm-primary'] ?? 0) !== $menu_id) {
        $locations['ichnm-primary'] = $menu_id;
        $changed = true;
    }
    foreach (['primary', 'mobile', 'secondary'] as $slot) {
        if (!empty($locations[$slot])) {
            $locations[$slot] = 0;
            $changed = true;
        }
    }
    if ($changed) {
        set_theme_mod('nav_menu_locations', $locations);
    }
}
add_action('init', 'ichnm_bind_primary_menu', 30);

function ichnm_render_veil(): void
{
    if (is_front_page()) {
        // Still show a short veil on home for continuity with NAS motion.
    }
    echo '<div class="ichnm-page-veil" aria-hidden="true"></div>';
}

function ichnm_render_chrome_open(): void
{
    echo '<div class="ichnm-chrome"><div class="wrap ichnm-chrome-inner">';
}

function ichnm_render_identity(): void
{
    $model = ichnm_theme_model();
    $ui = ichnm_chrome_strings();
    $short = $model['identity']['short_name'] ?? 'ИХНМ НАН Беларуси';
    $nas = $model['identity']['nas_portal_href'] ?? 'https://nasb.gov.by/rus/index.php';
    $home = function_exists('pll_home_url') ? (string) pll_home_url() : home_url('/');
    echo '<div class="ichnm-identity">';
    echo '<a class="nas-emblem" href="' . esc_url($nas) . '" title="' . esc_attr($ui['nas']) . '">';
    echo '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/nas-emblem.webp') . '" width="220" height="120" alt="' . esc_attr($ui['nas']) . '">';
    echo '</a>';
    echo '<a href="' . esc_url($home) . '">';
    echo '<img class="ichnm-mark" src="' . esc_url(get_stylesheet_directory_uri() . '/assets/ichnm-mark.svg') . '" width="230" height="193" alt="Эмблема ИХНМ">';
    echo '</a>';
    echo '<div class="ichnm-identity-text">';
    echo '<strong>' . esc_html($short) . '</strong>';
    echo '<a href="' . esc_url($nas) . '">' . esc_html($ui['nas']) . '</a>';
    echo '</div></div>';
}

function ichnm_render_language_switch(): void
{
    $model = ichnm_theme_model();
    $ui = ichnm_chrome_strings();
    $langs = $model['languages'] ?? [];
    $search = ichnm_translated_page('search');
    $sitemap = ichnm_translated_page('sitemap');
    $search_href = $search instanceof WP_Post ? get_permalink($search) : home_url('/search/');
    $sitemap_href = $sitemap instanceof WP_Post ? get_permalink($sitemap) : home_url('/sitemap/');

    $current_id = (int) get_queried_object_id();
    $current_lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';

    echo '<div class="ichnm-chrome-tools">';
    if ($langs) {
        echo '<nav class="ichnm-lang-switch" aria-label="' . esc_attr($ui['lang_aria']) . '">';
        foreach ($langs as $lang) {
            $code = strtolower((string) $lang['code']);
            $label = strtoupper($code);
            $href = home_url((string) ($lang['prefix'] ?? '/'));
            if (function_exists('pll_home_url')) {
                $href = (string) pll_home_url($code);
            }
            if ($current_id > 0 && function_exists('pll_get_post')) {
                $translated = pll_get_post($current_id, $code);
                if ($translated) {
                    $href = (string) get_permalink($translated);
                }
            }
            $class = ($code === $current_lang || ($current_lang === '' && $code === 'ru')) ? 'is-current' : '';
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url($href) . '" hreflang="' . esc_attr($code) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
    }
    if (shortcode_exists('bvi')) {
        echo '<div class="ichnm-bvi">' . do_shortcode('[bvi text="' . esc_attr($ui['bvi']) . '"]') . '</div>';
    }
    echo '<button type="button" class="ichnm-tool-btn" data-ichnm-search-open aria-controls="ichnm-site-search">' . esc_html($ui['search']) . '</button>';
    echo '<a class="ichnm-tool-btn" href="' . esc_url($sitemap_href) . '">' . esc_html($ui['sitemap']) . '</a>';
    $feedback = ichnm_translated_page('feedback');
    $feedback_href = $feedback instanceof WP_Post ? get_permalink($feedback) : home_url('/feedback/');
    echo '<a class="ichnm-write-btn" href="' . esc_url($feedback_href) . '">' . esc_html($ui['write']) . '</a>';
    echo '<button type="button" class="ichnm-menu-toggle" aria-expanded="false" aria-controls="ichnm-primary-nav">' . esc_html($ui['menu']) . '</button>';
    echo '</div>';
}

function ichnm_render_primary_menu(): void
{
    if (!has_nav_menu('ichnm-primary')) {
        return;
    }
    echo '<nav id="ichnm-primary-nav" class="ichnm-menu" aria-label="' . esc_attr(ichnm_chrome_strings()['sections']) . '">';
    wp_nav_menu([
        'theme_location' => 'ichnm-primary',
        'container' => false,
        'fallback_cb' => false,
        'depth' => 3,
        'menu_class' => 'ichnm-menu-list',
    ]);
    echo '</nav>';
}

function ichnm_render_chrome_close(): void
{
    echo '</div></div>';
}

function ichnm_render_search_overlay(): void
{
    $ui = ichnm_chrome_strings();
    $search = ichnm_translated_page('search');
    $action = $search instanceof WP_Post ? get_permalink($search) : home_url('/search/');
    echo '<div class="ichnm-site-search" id="ichnm-site-search" hidden role="dialog" aria-modal="true" aria-label="' . esc_attr($ui['search_aria']) . '">';
    echo '<div class="ichnm-site-search-panel">';
    echo '<form class="ichnm-search-form" action="' . esc_url($action) . '" method="get" role="search">';
    echo '<label class="screen-reader-text" for="ichnm-q-live">' . esc_html($ui['search_label']) . '</label>';
    echo '<input id="ichnm-q-live" name="q" type="search" placeholder="' . esc_attr($ui['search_placeholder']) . '" autocomplete="off" minlength="2">';
    echo '<button type="submit" class="ichnm-pill ichnm-pill-primary">' . esc_html($ui['find']) . '</button>';
    echo '<button type="button" class="ichnm-tool-btn" data-ichnm-search-close>' . esc_html($ui['close']) . '</button>';
    echo '</form>';
    echo '<p class="ichnm-search-hint">' . esc_html($ui['search_hint']) . '</p>';
    echo '<div class="ichnm-search-live" data-ichnm-search-live role="status"></div>';
    echo '</div></div>';
}

add_action('wp_body_open', 'ichnm_render_veil', 1);
add_action('wp_body_open', 'ichnm_render_chrome_open', 4);
add_action('wp_body_open', 'ichnm_render_identity', 5);
add_action('wp_body_open', 'ichnm_render_language_switch', 6);
add_action('wp_body_open', 'ichnm_render_primary_menu', 7);
add_action('wp_body_open', 'ichnm_render_chrome_close', 8);
add_action('wp_body_open', 'ichnm_render_search_overlay', 9);

add_action('wp_footer', static function (): void {
    $model = ichnm_theme_model();
    $ui = ichnm_chrome_strings();
    $legal = $model['footer']['legal_links'] ?? [];
    $nas_social = $model['footer']['nas_social'] ?? [];
    $icnm_social = array_values(array_filter(
        $model['footer']['icnm_social'] ?? [],
        static function ($row): bool {
            return !empty($row['href']);
        }
    ));
    $search = ichnm_translated_page('search');
    $sitemap = ichnm_translated_page('sitemap');
    $cookies = ichnm_translated_page('cookies');
    $personal = ichnm_translated_page('personal-data');
    echo '<div class="ichnm-footer-extra"><div class="wrap ichnm-footer-grid">';
    echo '<div class="ichnm-footer-col">';
    echo '<ul class="ichnm-footer-legal">';
    foreach ($legal as $link) {
        echo '<li><a href="' . esc_url($link['href']) . '">' . esc_html($link['title']) . '</a></li>';
    }
    if ($search instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($search)) . '">' . esc_html($ui['search']) . '</a></li>';
    }
    if ($sitemap instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($sitemap)) . '">' . esc_html($ui['sitemap']) . '</a></li>';
    }
    if ($cookies instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($cookies)) . '">' . esc_html($ui['cookies']) . '</a></li>';
    }
    if ($personal instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($personal)) . '">' . esc_html($ui['personal']) . '</a></li>';
    }
    echo '</ul>';
    echo '<ul class="ichnm-footer-social">';
    foreach (array_merge($nas_social, $icnm_social) as $link) {
        $label = $link['label'] ?? $link['network'] ?? '';
        echo '<li><a href="' . esc_url($link['href']) . '">' . esc_html((string) $label) . '</a></li>';
    }
    echo '</ul></div>';
    if (function_exists('ichnm_minsk_map_html')) {
        echo '<div class="ichnm-footer-map">' . ichnm_minsk_map_html() . '</div>';
    }
    echo '</div></div>';
}, 20);

/**
 * Language shells use slugs like news-en; map them onto dedicated hub templates.
 */
add_filter('template_include', static function (string $template): string {
    if (!is_singular('page')) {
        return $template;
    }
    $page = get_queried_object();
    if (!$page instanceof WP_Post) {
        return $template;
    }
    $base = (string) $page->post_name;
    if (function_exists('pll_get_post_language') && function_exists('pll_get_post')) {
        $lang = (string) pll_get_post_language((int) $page->ID);
        if ($lang && $lang !== 'ru') {
            $ru_id = (int) pll_get_post((int) $page->ID, 'ru');
            if ($ru_id > 0) {
                $ru = get_post($ru_id);
                if ($ru instanceof WP_Post) {
                    $base = (string) $ru->post_name;
                }
            }
        }
    }
    $base = preg_replace('/-(en|be|zh)$/', '', $base) ?: $base;
    $map = [
        'news' => 'page-news.php',
        'events' => 'page-events.php',
        'publications' => 'page-publications.php',
        'search' => 'page-search.php',
        'sitemap' => 'page-sitemap.php',
    ];
    if (!isset($map[$base])) {
        return $template;
    }
    $candidate = get_stylesheet_directory() . '/' . $map[$base];
    return is_readable($candidate) ? $candidate : $template;
}, 40);

/**
 * Point the institute chrome menu at Polylang translations when browsing EN/BE/ZH.
 *
 * @param list<\WP_Post> $items
 * @return list<\WP_Post>
 */
add_filter('wp_nav_menu_objects', static function (array $items, $args): array {
    $location = is_object($args) ? (string) ($args->theme_location ?? '') : '';
    if ($location !== 'ichnm-primary' || !function_exists('pll_current_language') || !function_exists('pll_get_post')) {
        return $items;
    }
    $lang = (string) pll_current_language('slug');
    if ($lang === '' || $lang === 'ru') {
        return $items;
    }
    foreach ($items as $item) {
        $object = (string) ($item->object ?? '');
        $type = (string) ($item->type ?? '');
        $object_id = (int) ($item->object_id ?? 0);
        if ($object_id <= 0) {
            continue;
        }
        if (!in_array($type, ['post_type', 'post_type_archive'], true) && $object === 'custom') {
            continue;
        }
        if (!in_array($object, ['page', 'department', 'person', 'news', 'event', 'media_about', 'publication'], true)
            && $type !== 'post_type') {
            continue;
        }
        $translated = (int) pll_get_post($object_id, $lang);
        if ($translated <= 0 || $translated === $object_id) {
            continue;
        }
        $post = get_post($translated);
        if (!$post instanceof WP_Post || $post->post_status !== 'publish') {
            continue;
        }
        $item->object_id = $translated;
        $item->url = (string) get_permalink($translated);
        $item->title = get_the_title($translated);
    }
    return $items;
}, 20, 2);
