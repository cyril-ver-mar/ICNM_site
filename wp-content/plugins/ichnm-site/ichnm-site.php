<?php
/**
 * Plugin Name: ИХНМ — модель сайта
 * Description: Регистрирует ленты, роль редактора и плейсхолдерные витринные страницы по модели сайта ИХНМ.
 * Version: 0.1.0
 * Text Domain: ichnm-site
 */

if (!defined('ABSPATH')) {
    exit;
}

function ichnm_site_model_path(): string
{
    $mounted = __DIR__ . '/site-model.json';
    if (is_readable($mounted)) {
        return $mounted;
    }
    return dirname(__DIR__, 3) . '/src/core/site_model.json';
}

function ichnm_site_model(): array
{
    static $model = null;
    if (is_array($model)) {
        return $model;
    }
    $path = ichnm_site_model_path();
    $raw = is_readable($path) ? file_get_contents($path) : false;
    if ($raw === false) {
        $model = [];
        return $model;
    }
    $decoded = json_decode($raw, true);
    $model = is_array($decoded) ? $decoded : [];
    return $model;
}

function ichnm_register_post_types(): void
{
    $labels = [
        'news' => ['Новости', 'Новость'],
        'event' => ['Мероприятия', 'Мероприятие'],
        'announcement' => ['Объявления', 'Объявление'],
        'media_about' => ['СМИ о нас', 'Публикация СМИ'],
        'publication' => ['Публикации', 'Публикация'],
        'department' => ['Подразделения', 'Подразделение'],
    ];
    foreach ($labels as $id => $pair) {
        register_post_type($id, [
            'labels' => [
                'name' => $pair[0],
                'singular_name' => $pair[1],
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'menu_position' => 20,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => str_replace('_', '-', $id)],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }
}
add_action('init', 'ichnm_register_post_types');

function ichnm_register_feed_editor_role(): void
{
    add_role('ichnm_feed_editor', 'Редактор лент ИХНМ', [
        'read' => true,
        'upload_files' => true,
        'edit_posts' => true,
        'edit_published_posts' => true,
        'publish_posts' => true,
        'delete_posts' => true,
        'delete_published_posts' => true,
    ]);
}

add_filter('user_has_cap', static function (array $allcaps, array $caps, array $args, WP_User $user): array {
    if (!in_array('ichnm_feed_editor', (array) $user->roles, true)) {
        return $allcaps;
    }
    $blocked = ['edit_pages', 'edit_theme_options', 'switch_themes', 'customize', 'install_themes', 'edit_themes'];
    foreach ($blocked as $cap) {
        $allcaps[$cap] = false;
    }
    return $allcaps;
}, 10, 4);

add_action('admin_menu', static function (): void {
    if (!current_user_can('publish_posts') || current_user_can('manage_options')) {
        return;
    }
    remove_menu_page('edit.php');
    remove_menu_page('edit.php?post_type=page');
    remove_menu_page('themes.php');
});

function ichnm_placeholder_body(string $id): string
{
    if ($id === 'aist') {
        return '<p>Базовая справка о конференции AIST. Раздел готовится.</p>'
            . '<p>Регистрация участников пока на сайте <a href="http://aist.ichnm.by/">aist.ichnm.by</a>.</p>';
    }
    return '<p>Плейсхолдер. Текст будет перенесён с ichnm.by или придёт в пакете к переключению.</p>';
}

function ichnm_walk_menu(array $items): array
{
    $flat = [];
    foreach ($items as $item) {
        $flat[] = $item;
        if (!empty($item['children'])) {
            $flat = array_merge($flat, ichnm_walk_menu($item['children']));
        }
    }
    return $flat;
}

function ichnm_ensure_pages(): void
{
    $model = ichnm_site_model();
    foreach (ichnm_walk_menu($model['menu'] ?? []) as $item) {
        if (($item['kind'] ?? 'vitrine') !== 'vitrine') {
            continue;
        }
        $id = $item['id'] ?? '';
        if ($id === '') {
            continue;
        }
        $existing = get_page_by_path($id);
        if ($existing instanceof WP_Post) {
            continue;
        }
        wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => $id,
            'post_title' => $item['title'],
            'post_content' => ichnm_placeholder_body($id),
        ]);
    }
}

function ichnm_feed_archive_id(string $menu_id): ?string
{
    $map = [
        'news' => 'news',
        'events' => 'event',
        'announcement' => 'announcement',
        'media_about' => 'media_about',
        'publications' => 'publication',
    ];
    return $map[$menu_id] ?? null;
}

function ichnm_add_menu_branch(int $menu_id, array $items, int $parent_id = 0): void
{
    foreach ($items as $item) {
        $id = $item['id'] ?? '';
        $title = $item['title'] ?? $id;
        $feed = ichnm_feed_archive_id($id);
        $page = $feed ? null : get_page_by_path($id);
        $args = [
            'menu-item-title' => $title,
            'menu-item-status' => 'publish',
            'menu-item-parent-id' => $parent_id,
        ];
        if ($feed && ($archive = get_post_type_archive_link($feed))) {
            $args['menu-item-type'] = 'custom';
            $args['menu-item-url'] = $archive;
        } elseif ($page instanceof WP_Post) {
            $args['menu-item-type'] = 'post_type';
            $args['menu-item-object'] = 'page';
            $args['menu-item-object-id'] = $page->ID;
            $args['menu-item-url'] = get_permalink($page);
        } else {
            $args['menu-item-type'] = 'custom';
            $args['menu-item-url'] = home_url('/' . $id . '/');
        }
        $nav_id = wp_update_nav_menu_item($menu_id, 0, $args);
        if ($nav_id && !empty($item['children'])) {
            ichnm_add_menu_branch($menu_id, $item['children'], (int) $nav_id);
        }
    }
}

function ichnm_sync_nav_menu(): void
{
    $location = 'ichnm-primary';
    register_nav_menu($location, 'Главное меню ИХНМ');
    if (get_option('ichnm_menu_seeded')) {
        return;
    }
    $name = 'Главное меню ИХНМ';
    $menu = wp_get_nav_menu_object($name);
    $menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
    if ($menu_id <= 0) {
        return;
    }
    $existing = wp_get_nav_menu_items($menu_id);
    if (empty($existing)) {
        ichnm_add_menu_branch($menu_id, ichnm_site_model()['menu'] ?? []);
    }
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) {
        $locations = [];
    }
    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
    update_option('ichnm_menu_seeded', 1);
}

function ichnm_activate(): void
{
    ichnm_register_post_types();
    if (get_role('ichnm_feed_editor') === null) {
        ichnm_register_feed_editor_role();
    }
    ichnm_ensure_pages();
    ichnm_sync_nav_menu();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ichnm_activate');

add_action('init', static function (): void {
    register_nav_menu('ichnm-primary', 'Главное меню ИХНМ');
    if (get_role('ichnm_feed_editor') === null) {
        ichnm_register_feed_editor_role();
    }
    if (!get_option('ichnm_pages_seeded')) {
        ichnm_ensure_pages();
        update_option('ichnm_pages_seeded', 1);
    }
    ichnm_sync_nav_menu();
});
