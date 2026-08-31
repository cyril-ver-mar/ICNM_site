<?php
/**
 * Kadence child: NAS identity, language switcher, stable footer pack.
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
});

function ichnm_theme_model(): array
{
    return function_exists('ichnm_site_model') ? ichnm_site_model() : [];
}

function ichnm_render_identity(): void
{
    $model = ichnm_theme_model();
    $name = $model['identity']['legal_name'] ?? 'ИХНМ НАН Беларуси';
    $nas = $model['identity']['nas_portal_href'] ?? 'https://nasb.gov.by/rus/index.php';
    echo '<div class="ichnm-identity">';
    echo '<a class="nas-emblem" href="' . esc_url($nas) . '">';
    echo '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/nas-emblem.webp') . '" width="220" height="120" alt="Эмблема Национальной академии наук Беларуси">';
    echo '</a> ';
    echo '<img class="ichnm-mark" src="' . esc_url(get_stylesheet_directory_uri() . '/assets/ichnm-mark.svg') . '" width="230" height="193" alt="Эмблема ИХНМ"> ';
    echo '<strong>' . esc_html($name) . '</strong> · ';
    echo '<a href="' . esc_url($nas) . '">Национальная академия наук Беларуси</a>';
    echo '</div>';
}

function ichnm_render_language_switch(): void
{
    $model = ichnm_theme_model();
    $langs = $model['languages'] ?? [];
    if (!$langs) {
        return;
    }
    echo '<nav class="ichnm-lang-switch" aria-label="Язык">';
    foreach ($langs as $lang) {
        $code = strtoupper((string) $lang['code']);
        $href = (string) $lang['prefix'];
        $class = ($lang['code'] === 'ru') ? 'is-current' : '';
        echo '<a class="' . esc_attr($class) . '" href="' . esc_url($href) . '">' . esc_html($code) . '</a> ';
    }
    echo '</nav>';
}

add_action('wp_body_open', 'ichnm_render_identity', 5);
add_action('wp_body_open', 'ichnm_render_language_switch', 6);
add_action('wp_body_open', static function (): void {
    if (!has_nav_menu('ichnm-primary')) {
        return;
    }
    echo '<nav class="ichnm-menu" aria-label="Разделы">';
    wp_nav_menu([
        'theme_location' => 'ichnm-primary',
        'container' => false,
        'fallback_cb' => false,
    ]);
    echo '</nav>';
}, 7);

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
    echo '<div class="ichnm-footer-extra">';
    echo '<ul class="ichnm-footer-legal">';
    foreach ($legal as $link) {
        echo '<li><a href="' . esc_url($link['href']) . '">' . esc_html($link['title']) . '</a></li>';
    }
    echo '</ul>';
    echo '<ul class="ichnm-footer-social">';
    foreach (array_merge($nas_social, $icnm_social) as $link) {
        echo '<li><a href="' . esc_url($link['href']) . '">' . esc_html($link['network']) . '</a></li>';
    }
    echo '</ul></div>';
}, 20);
