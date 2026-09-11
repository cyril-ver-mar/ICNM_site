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
    $short = $model['identity']['short_name'] ?? 'ИХНМ НАН Беларуси';
    $nas = $model['identity']['nas_portal_href'] ?? 'https://nasb.gov.by/rus/index.php';
    echo '<div class="ichnm-identity">';
    echo '<a class="nas-emblem" href="' . esc_url($nas) . '" title="Национальная академия наук Беларуси">';
    echo '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/nas-emblem.webp') . '" width="220" height="120" alt="Эмблема Национальной академии наук Беларуси">';
    echo '</a>';
    echo '<a href="' . esc_url(home_url('/')) . '">';
    echo '<img class="ichnm-mark" src="' . esc_url(get_stylesheet_directory_uri() . '/assets/ichnm-mark.svg') . '" width="230" height="193" alt="Эмблема ИХНМ">';
    echo '</a>';
    echo '<div class="ichnm-identity-text">';
    echo '<strong>' . esc_html($short) . '</strong>';
    echo '<a href="' . esc_url($nas) . '">Национальная академия наук Беларуси</a>';
    echo '</div></div>';
}

function ichnm_render_language_switch(): void
{
    $model = ichnm_theme_model();
    $langs = $model['languages'] ?? [];
    $search = get_page_by_path('search');
    $sitemap = get_page_by_path('sitemap');
    $search_href = $search instanceof WP_Post ? get_permalink($search) : home_url('/search/');
    $sitemap_href = $sitemap instanceof WP_Post ? get_permalink($sitemap) : home_url('/sitemap/');

    $current_id = (int) get_queried_object_id();
    $current_lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';

    echo '<div class="ichnm-chrome-tools">';
    if ($langs) {
        echo '<nav class="ichnm-lang-switch" aria-label="Язык">';
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
        echo '<div class="ichnm-bvi">' . do_shortcode('[bvi text="Версия для слабовидящих"]') . '</div>';
    }
    echo '<button type="button" class="ichnm-tool-btn" data-ichnm-search-open aria-controls="ichnm-site-search">Поиск</button>';
    echo '<a class="ichnm-tool-btn" href="' . esc_url($sitemap_href) . '">Карта сайта</a>';
    $feedback = get_page_by_path('feedback');
    $feedback_href = $feedback instanceof WP_Post ? get_permalink($feedback) : home_url('/feedback/');
    echo '<a class="ichnm-write-btn" href="' . esc_url($feedback_href) . '">Написать нам</a>';
    echo '<button type="button" class="ichnm-menu-toggle" aria-expanded="false" aria-controls="ichnm-primary-nav">Меню</button>';
    echo '</div>';
}

function ichnm_render_primary_menu(): void
{
    if (!has_nav_menu('ichnm-primary')) {
        return;
    }
    echo '<nav id="ichnm-primary-nav" class="ichnm-menu" aria-label="Разделы">';
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
    $search = get_page_by_path('search');
    $action = $search instanceof WP_Post ? get_permalink($search) : home_url('/search/');
    echo '<div class="ichnm-site-search" id="ichnm-site-search" hidden role="dialog" aria-modal="true" aria-label="Поиск">';
    echo '<div class="ichnm-site-search-panel">';
    echo '<form class="ichnm-search-form" action="' . esc_url($action) . '" method="get" role="search">';
    echo '<label class="screen-reader-text" for="ichnm-q-live">Поиск по сайту</label>';
    echo '<input id="ichnm-q-live" name="q" type="search" placeholder="Персоналии, подразделения, приборы, разработки" autocomplete="off" minlength="2">';
    echo '<button type="submit" class="ichnm-pill ichnm-pill-primary">Найти</button>';
    echo '<button type="button" class="ichnm-tool-btn" data-ichnm-search-close>Закрыть</button>';
    echo '</form>';
    echo '<p class="ichnm-search-hint">Введите не меньше двух букв. Есть и отдельная страница результатов.</p>';
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
    $legal = $model['footer']['legal_links'] ?? [];
    $nas_social = $model['footer']['nas_social'] ?? [];
    $icnm_social = array_values(array_filter(
        $model['footer']['icnm_social'] ?? [],
        static function ($row): bool {
            return !empty($row['href']);
        }
    ));
    $search = get_page_by_path('search');
    $sitemap = get_page_by_path('sitemap');
    $cookies = get_page_by_path('cookies');
    $personal = get_page_by_path('personal-data');
    echo '<div class="ichnm-footer-extra"><div class="wrap ichnm-footer-grid">';
    echo '<div class="ichnm-footer-col">';
    echo '<ul class="ichnm-footer-legal">';
    foreach ($legal as $link) {
        echo '<li><a href="' . esc_url($link['href']) . '">' . esc_html($link['title']) . '</a></li>';
    }
    if ($search instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($search)) . '">Поиск</a></li>';
    }
    if ($sitemap instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($sitemap)) . '">Карта сайта</a></li>';
    }
    if ($cookies instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($cookies)) . '">Политика cookie</a></li>';
    }
    if ($personal instanceof WP_Post) {
        echo '<li><a href="' . esc_url(get_permalink($personal)) . '">Персональные данные</a></li>';
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
