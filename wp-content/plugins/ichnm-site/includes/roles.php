<?php
/**
 * Feed-editor role: CRUD on news / events / media_about / publications only.
 * No rights to edit vitrine pages, structure CPTs, or Appearance chrome (menus/themes).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @return list<string> */
function ichnm_feed_post_types(): array
{
    return ['news', 'event', 'media_about', 'publication'];
}

/**
 * Caps granted to Редактор лент ИХНМ.
 * Feed CPTs use capability_type=post, so post caps cover all four types;
 * map_meta_cap then denies non-feed post types.
 *
 * @return array<string, bool>
 */
function ichnm_feed_editor_granted_caps(): array
{
    return [
        'read' => true,
        'upload_files' => true,
        'edit_posts' => true,
        'edit_others_posts' => true,
        'edit_published_posts' => true,
        'publish_posts' => true,
        'delete_posts' => true,
        'delete_others_posts' => true,
        'delete_published_posts' => true,
        'edit_private_posts' => true,
        'delete_private_posts' => true,
        'read_private_posts' => true,
    ];
}

/**
 * Caps this role must never keep (chrome, pages, users, plugins).
 *
 * @return list<string>
 */
function ichnm_feed_editor_denied_caps(): array
{
    return [
        'edit_pages',
        'edit_others_pages',
        'edit_published_pages',
        'edit_private_pages',
        'publish_pages',
        'delete_pages',
        'delete_others_pages',
        'delete_published_pages',
        'delete_private_pages',
        'read_private_pages',
        'edit_theme_options',
        'customize',
        'switch_themes',
        'install_themes',
        'update_themes',
        'delete_themes',
        'edit_themes',
        'manage_options',
        'activate_plugins',
        'edit_plugins',
        'install_plugins',
        'update_plugins',
        'delete_plugins',
        'edit_users',
        'create_users',
        'delete_users',
        'list_users',
        'promote_users',
        'manage_categories',
        'moderate_comments',
        'edit_comment',
        'unfiltered_html',
        'import',
        'export',
    ];
}

function ichnm_user_is_feed_editor(?WP_User $user = null): bool
{
    if ($user === null) {
        $user = wp_get_current_user();
    }
    if (!$user instanceof WP_User || (int) $user->ID <= 0) {
        return false;
    }
    return in_array('ichnm_feed_editor', (array) $user->roles, true);
}

/**
 * Create or refresh the feed-editor role from the granted/denied lists.
 * Called on plugin activation and every init seed so caps stay in sync.
 */
function ichnm_register_feed_editor_role(): void
{
    $granted = ichnm_feed_editor_granted_caps();
    $role = get_role('ichnm_feed_editor');
    if ($role === null) {
        add_role('ichnm_feed_editor', 'Редактор лент ИХНМ', $granted);
        $role = get_role('ichnm_feed_editor');
    }
    if (!$role instanceof WP_Role) {
        return;
    }
    foreach ($granted as $cap => $allow) {
        if ($allow) {
            $role->add_cap($cap);
        } else {
            $role->remove_cap($cap);
        }
    }
    foreach (ichnm_feed_editor_denied_caps() as $cap) {
        $role->remove_cap($cap);
    }
}

/**
 * Vitrine + hub + utility page slugs that must stay out of the feed editor.
 * Language shells (slug-en / slug-be / slug-zh) inherit the base slug lock.
 *
 * @return list<string>
 */
function ichnm_locked_page_slugs(): array
{
    static $slugs = null;
    if (is_array($slugs)) {
        return $slugs;
    }
    $slugs = ['home', 'news', 'events', 'publications', 'search', 'sitemap', 'privacy'];
    if (function_exists('ichnm_site_model') && function_exists('ichnm_walk_menu')) {
        foreach (ichnm_walk_menu(ichnm_site_model()['menu'] ?? []) as $item) {
            if (($item['kind'] ?? '') !== 'vitrine') {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            if ($id !== '') {
                $slugs[] = $id;
            }
        }
    }
    $slugs = array_values(array_unique($slugs));
    return $slugs;
}

/**
 * True when this post must not be edited by the feed-editor role.
 */
function ichnm_post_is_locked_for_feed_editor(?WP_Post $post): bool
{
    if (!$post instanceof WP_Post) {
        return true;
    }
    $type = $post->post_type;
    if (in_array($type, ichnm_feed_post_types(), true)) {
        return false;
    }
    if ($type === 'attachment') {
        return false;
    }
    // Pages (vitrines/hubs/utilities/shells), department, person, default post, menus, etc.
    return true;
}

add_filter('user_has_cap', static function (array $allcaps, array $caps, array $args, WP_User $user): array {
    if (!ichnm_user_is_feed_editor($user)) {
        return $allcaps;
    }
    foreach (ichnm_feed_editor_denied_caps() as $cap) {
        $allcaps[$cap] = false;
    }
    return $allcaps;
}, 10, 4);

add_filter('map_meta_cap', static function (array $caps, string $cap, int $user_id, array $args): array {
    $user = get_userdata($user_id);
    if (!$user instanceof WP_User || !ichnm_user_is_feed_editor($user)) {
        return $caps;
    }

    $post_caps = [
        'edit_post',
        'delete_post',
        'publish_post',
        'edit_page',
        'delete_page',
    ];
    if (!in_array($cap, $post_caps, true)) {
        return $caps;
    }

    $post_id = isset($args[0]) ? (int) $args[0] : 0;
    if ($post_id <= 0) {
        return $caps;
    }
    $post = get_post($post_id);
    if (ichnm_post_is_locked_for_feed_editor($post)) {
        $caps[] = 'do_not_allow';
    }
    return $caps;
}, 10, 4);

/**
 * Last line of defence: refuse insert/update of non-feed types (admin + REST).
 */
add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    if (!ichnm_user_is_feed_editor() || current_user_can('manage_options')) {
        return $data;
    }
    $type = (string) ($data['post_type'] ?? 'post');
    if (in_array($type, ['revision', 'attachment', 'nav_menu_item'], true)) {
        // nav_menu_item still needs edit_theme_options (denied); revisions follow parent.
        if ($type === 'nav_menu_item') {
            wp_die(
                esc_html('Редактор лент ИХНМ не меняет меню шапки и подвала.'),
                esc_html('Доступ ограничен'),
                ['response' => 403]
            );
        }
        return $data;
    }
    if (!in_array($type, ichnm_feed_post_types(), true)) {
        wp_die(
            esc_html('Редактор лент ИХНМ публикует только новости, мероприятия, «СМИ о нас» и публикации.'),
            esc_html('Доступ ограничен'),
            ['response' => 403]
        );
    }
    return $data;
}, 5, 2);

add_action('admin_menu', static function (): void {
    if (!ichnm_user_is_feed_editor() || current_user_can('manage_options')) {
        return;
    }
    remove_menu_page('edit.php');
    remove_menu_page('edit.php?post_type=page');
    remove_menu_page('edit.php?post_type=department');
    remove_menu_page('edit.php?post_type=person');
    remove_menu_page('themes.php');
    remove_menu_page('customize.php');
    remove_menu_page('nav-menus.php');
    remove_menu_page('widgets.php');
    remove_menu_page('tools.php');
    remove_menu_page('options-general.php');
    remove_submenu_page('index.php', 'update-core.php');
}, 999);

add_action('load-post.php', static function (): void {
    if (!ichnm_user_is_feed_editor() || current_user_can('manage_options')) {
        return;
    }
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if ($post_id <= 0) {
        return;
    }
    $post = get_post($post_id);
    if (ichnm_post_is_locked_for_feed_editor($post)) {
        wp_die(
            esc_html('Редактор лент ИХНМ не может менять витринные страницы, шапку/подвал или структуру. Обратитесь к администратору сайта.'),
            esc_html('Доступ ограничен'),
            ['response' => 403]
        );
    }
});

add_action('load-post-new.php', static function (): void {
    if (!ichnm_user_is_feed_editor() || current_user_can('manage_options')) {
        return;
    }
    $type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : 'post';
    if (!in_array($type, ichnm_feed_post_types(), true)) {
        wp_die(
            esc_html('Редактор лент ИХНМ создаёт только новости, мероприятия, «СМИ о нас» и публикации.'),
            esc_html('Доступ ограничен'),
            ['response' => 403]
        );
    }
});

add_action('admin_bar_menu', static function (WP_Admin_Bar $bar): void {
    if (!ichnm_user_is_feed_editor() || current_user_can('manage_options')) {
        return;
    }
    $bar->remove_node('customize');
    $bar->remove_node('themes');
    $bar->remove_node('menus');
    $bar->remove_node('widgets');
    $bar->remove_node('new-page');
    $bar->remove_node('new-content');
}, 999);
