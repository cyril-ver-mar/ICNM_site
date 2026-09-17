<?php
/**
 * Plugin Name: ИХНМ — модель сайта
 * Description: Регистрирует ленты, роль редактора и витрину по модели сайта ИХНМ; сеет контент из migrated_copy.
 * Version: 0.2.0
 * Text Domain: ichnm-site
 */

if (!defined('ABSPATH')) {
    exit;
}

function ichnm_readable_json_path(string $mounted, string $fallback): string
{
    if (is_readable($mounted) && (int) filesize($mounted) > 0) {
        return $mounted;
    }
    return $fallback;
}

function ichnm_site_model_path(): string
{
    return ichnm_readable_json_path(
        __DIR__ . '/site-model.json',
        dirname(__DIR__, 3) . '/src/core/site_model.json'
    );
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

function ichnm_migrated_copy_path(): string
{
    return ichnm_readable_json_path(
        __DIR__ . '/migrated-copy.json',
        dirname(__DIR__, 3) . '/src/core/migrated_copy.json'
    );
}

require_once __DIR__ . '/includes/content-sync.php';
require_once __DIR__ . '/includes/polylang-setup.php';
require_once __DIR__ . '/includes/roles.php';

/**
 * Honest home copy for the local theme (same source as HTML preview).
 *
 * @return array{home_intro:string,founded_year:int,labs_count:int,news:array<int,array<string,mixed>>,feed:array<int,array<string,mixed>>,next_event:array<string,mixed>,structure_teaser:string,developments_teaser:string}
 */
function ichnm_home_copy(): array
{
    static $copy = null;
    if (is_array($copy)) {
        return $copy;
    }
    $decoded = ichnm_migrated_copy();
    $labs = $decoded['labs'] ?? [];
    $news = is_array($decoded['news'] ?? null) ? array_slice($decoded['news'], 0, 3) : [];
    foreach ($news as &$item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = (string) ($item['slug'] ?? '');
        $post = ($slug !== '' && function_exists('ichnm_find_by_slug'))
            ? ichnm_find_by_slug('news', $slug)
            : null;
        $item['permalink'] = $post instanceof WP_Post ? (string) get_permalink($post) : '';
        $item['kind'] = 'news';
        $item['kind_label'] = 'Новость';
        $item['sort_date'] = (string) ($item['date'] ?? '');
    }
    unset($item);

    $media = is_array($decoded['media_items'] ?? null) ? array_slice($decoded['media_items'], 0, 3) : [];
    foreach ($media as &$item) {
        if (!is_array($item)) {
            continue;
        }
        $key = 'media-' . md5((string) ($item['href'] ?? $item['title'] ?? ''));
        $post = null;
        $posts = get_posts([
            'post_type' => 'media_about',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_key' => '_ichnm_source_slug',
            'meta_value' => $key,
        ]);
        if ($posts) {
            $post = $posts[0];
        }
        $item['permalink'] = $post instanceof WP_Post
            ? (string) get_permalink($post)
            : (string) ($item['href'] ?? '');
        $item['kind'] = 'media';
        $item['kind_label'] = 'СМИ о нас';
        $item['sort_date'] = (string) ($item['date'] ?? '');
        if (empty($item['date_label']) && !empty($item['date'])) {
            $item['date_label'] = (string) $item['date'];
        }
    }
    unset($item);

    $feed = array_merge($news, $media);
    usort($feed, static function (array $a, array $b): int {
        return strcmp((string) ($b['sort_date'] ?? ''), (string) ($a['sort_date'] ?? ''));
    });
    $feed = array_slice($feed, 0, 4);

    $copy = [
        'home_intro' => (string) ($decoded['home_intro'] ?? ''),
        'founded_year' => (int) ($decoded['founded_year'] ?? 1998),
        'labs_count' => is_array($labs) ? count($labs) : 0,
        'news' => $news,
        'feed' => $feed,
        'next_event' => is_array($decoded['next_event'] ?? null) ? $decoded['next_event'] : [],
        'structure_teaser' => (string) ($decoded['structure_teaser'] ?? ''),
        'developments_teaser' => (string) ($decoded['developments_teaser'] ?? ''),
    ];
    return $copy;
}

function ichnm_register_post_types(): void
{
    $types = [
        'news' => ['Новости', 'Новость', 'news', false],
        // Public URLs match honest preview: /conferences/{slug}/ (not /event/).
        'event' => ['Мероприятия', 'Мероприятие', 'conferences', false],
        'media_about' => ['СМИ о нас', 'Публикация СМИ', 'media-about', true],
        'publication' => ['Публикации', 'Публикация', 'publication', false],
        'department' => ['Подразделения', 'Подразделение', 'labs', true],
        'person' => ['Персоналии', 'Персона', 'people', true],
    ];
    foreach ($types as $id => $pair) {
        register_post_type($id, [
            'labels' => [
                'name' => $pair[0],
                'singular_name' => $pair[1],
            ],
            'public' => true,
            'has_archive' => $pair[3],
            'show_in_rest' => true,
            'menu_position' => 20,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => $pair[2]],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }
}
add_action('init', 'ichnm_register_post_types');

function ichnm_placeholder_body(string $id): string
{
    if ($id === 'aist') {
        return '<p>Базовая справка о конференции AIST. Раздел готовится.</p>'
            . '<p>Регистрация участников пока на сайте <a href="http://aist.ichnm.by/">aist.ichnm.by</a>.</p>';
    }
    if ($id === 'feedback') {
        return '<p>Форма обратной связи будет подключена на PHP-хостинге. Пока используйте контакты института.</p>';
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
    // News / events / publications are hub pages, not raw CPT archives.
    $map = [
        'media_about' => 'media_about',
    ];
    return $map[$menu_id] ?? null;
}

function ichnm_add_menu_branch(int $menu_id, array $items, int $parent_id = 0): void
{
    foreach ($items as $item) {
        $id = $item['id'] ?? '';
        $title = $item['title'] ?? $id;
        $kind = $item['kind'] ?? 'vitrine';
        $feed = ichnm_feed_archive_id($id);
        $args = [
            'menu-item-title' => $title,
            'menu-item-status' => 'publish',
            'menu-item-parent-id' => $parent_id,
        ];

        if ($kind === 'lab') {
            $lab = ichnm_find_by_slug('department', (string) ($item['slug'] ?? ''));
            if ($lab instanceof WP_Post) {
                $args['menu-item-type'] = 'post_type';
                $args['menu-item-object'] = 'department';
                $args['menu-item-object-id'] = $lab->ID;
                $args['menu-item-url'] = get_permalink($lab);
            } else {
                $args['menu-item-type'] = 'custom';
                $args['menu-item-url'] = home_url('/labs/' . rawurlencode((string) ($item['slug'] ?? '')) . '/');
            }
        } elseif ($kind === 'folder') {
            $args['menu-item-type'] = 'custom';
            $args['menu-item-url'] = '#';
        } elseif ($feed && ($archive = get_post_type_archive_link($feed))) {
            $args['menu-item-type'] = 'custom';
            $args['menu-item-url'] = $archive;
        } else {
            $page = get_page_by_path($id);
            if ($page instanceof WP_Post) {
                $args['menu-item-type'] = 'post_type';
                $args['menu-item-object'] = 'page';
                $args['menu-item-object-id'] = $page->ID;
                $args['menu-item-url'] = get_permalink($page);
            } else {
                $args['menu-item-type'] = 'custom';
                $args['menu-item-url'] = home_url('/' . $id . '/');
            }
        }

        $nav_id = wp_update_nav_menu_item($menu_id, 0, $args);
        if ($nav_id && !empty($item['children'])) {
            ichnm_add_menu_branch($menu_id, $item['children'], (int) $nav_id);
        }
    }
}

function ichnm_sync_nav_menu(bool $force = false): void
{
    $location = 'ichnm-primary';
    register_nav_menu($location, 'Главное меню ИХНМ');
    if (!$force && get_option('ichnm_menu_seeded')) {
        return;
    }
    $name = 'Главное меню ИХНМ';
    $menu = wp_get_nav_menu_object($name);
    $menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
    if ($menu_id <= 0) {
        return;
    }
    $existing = wp_get_nav_menu_items($menu_id) ?: [];
    foreach ($existing as $item) {
        wp_delete_post((int) $item->ID, true);
    }
    $tree = ichnm_site_model()['menu'] ?? [];
    if (function_exists('ichnm_menu_with_labs')) {
        $tree = ichnm_menu_with_labs($tree);
    }
    ichnm_add_menu_branch($menu_id, $tree);
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) {
        $locations = [];
    }
    $locations[$location] = $menu_id;
    // Keep institute menu off Kadence primary slots to avoid a second chrome.
    $locations['primary'] = 0;
    $locations['mobile'] = 0;
    set_theme_mod('nav_menu_locations', $locations);
    update_option('ichnm_menu_seeded', 1);
}

function ichnm_activate(): void
{
    ichnm_register_post_types();
    ichnm_register_feed_editor_role();
    ichnm_ensure_pages();
    ichnm_sync_content(true);
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ichnm_activate');

add_action('init', static function (): void {
    register_nav_menu('ichnm-primary', 'Главное меню ИХНМ');
    ichnm_register_feed_editor_role();
    if (!get_option('ichnm_pages_seeded')) {
        ichnm_ensure_pages();
        update_option('ichnm_pages_seeded', 1);
    }
    ichnm_sync_content(false);
    ichnm_sync_nav_menu(false);
}, 20);

/**
 * Feedback form shortcode used on /feedback/.
 */
function ichnm_feedback_form_shortcode(): string
{
    $sent = isset($_GET['ichnm_sent']) && $_GET['ichnm_sent'] === '1';
    $err = isset($_GET['ichnm_err']) ? sanitize_text_field(wp_unslash((string) $_GET['ichnm_err'])) : '';
    $html = '<div class="ichnm-feedback">';
    if ($sent) {
        $html .= '<p class="ichnm-feedback-ok">Сообщение принято. На локальном контуре письмо сохраняется в журнал WordPress; на PHP-хостинге будет уходить на ichnm@ichnm.by.</p>';
    }
    if ($err !== '') {
        $html .= '<p class="ichnm-feedback-err">' . esc_html($err) . '</p>';
    }
    $html .= '<form class="ichnm-feedback-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    $html .= '<input type="hidden" name="action" value="ichnm_feedback">';
    $html .= wp_nonce_field('ichnm_feedback', 'ichnm_feedback_nonce', true, false);
    $html .= '<label for="ichnm-fb-name">Имя</label>';
    $html .= '<input id="ichnm-fb-name" name="ichnm_name" type="text" required maxlength="120">';
    $html .= '<label for="ichnm-fb-email">Email</label>';
    $html .= '<input id="ichnm-fb-email" name="ichnm_email" type="email" required maxlength="180">';
    $html .= '<label for="ichnm-fb-message">Сообщение</label>';
    $html .= '<textarea id="ichnm-fb-message" name="ichnm_message" rows="6" required maxlength="5000"></textarea>';
    $html .= '<button type="submit" class="ichnm-pill ichnm-pill-primary">Отправить</button>';
    $html .= '</form></div>';
    return $html;
}
add_shortcode('ichnm_feedback_form', 'ichnm_feedback_form_shortcode');

/**
 * Cooperation world map: Natural Earth outlines + partner pins (ticket 12).
 */
function ichnm_world_map_shortcode(): string
{
    return function_exists('ichnm_world_map_html') ? ichnm_world_map_html() : '';
}
add_shortcode('ichnm_world_map', 'ichnm_world_map_shortcode');

function ichnm_minsk_map_shortcode(): string
{
    return function_exists('ichnm_minsk_map_html') ? ichnm_minsk_map_html() : '';
}
add_shortcode('ichnm_minsk_map', 'ichnm_minsk_map_shortcode');

function ichnm_handle_feedback(): void
{
    $redirect = get_page_by_path('feedback');
    $target = $redirect instanceof WP_Post ? get_permalink($redirect) : home_url('/feedback/');
    if (!isset($_POST['ichnm_feedback_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['ichnm_feedback_nonce'])), 'ichnm_feedback')) {
        wp_safe_redirect(add_query_arg('ichnm_err', rawurlencode('Сессия формы устарела. Обновите страницу.'), $target));
        exit;
    }
    $name = sanitize_text_field(wp_unslash((string) ($_POST['ichnm_name'] ?? '')));
    $email = sanitize_email(wp_unslash((string) ($_POST['ichnm_email'] ?? '')));
    $message = sanitize_textarea_field(wp_unslash((string) ($_POST['ichnm_message'] ?? '')));
    if ($name === '' || $email === '' || $message === '' || !is_email($email)) {
        wp_safe_redirect(add_query_arg('ichnm_err', rawurlencode('Проверьте имя, email и текст сообщения.'), $target));
        exit;
    }
    $body = "Имя: {$name}\nEmail: {$email}\n\n{$message}\n";
    $sent = wp_mail('ichnm@ichnm.by', 'Обратная связь с сайта ИХНМ', $body, ['Reply-To: ' . $email]);
    // Always keep a local copy for the Docker contour where mail may be unavailable.
    error_log('[ichnm_feedback] ' . str_replace("\n", ' | ', $body));
    update_option('ichnm_last_feedback', [
        'time' => current_time('mysql'),
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'mail_sent' => (bool) $sent,
    ], false);
    wp_safe_redirect(add_query_arg('ichnm_sent', '1', $target));
    exit;
}
add_action('admin_post_ichnm_feedback', 'ichnm_handle_feedback');
add_action('admin_post_nopriv_ichnm_feedback', 'ichnm_handle_feedback');
