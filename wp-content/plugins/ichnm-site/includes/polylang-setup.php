<?php
/**
 * Polylang languages + structure shells (EN/BE/ZH). Body copy stays Russian.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ichnm_i18n_titles(): array
{
    static $data = null;
    if (is_array($data)) {
        return $data;
    }
    $path = __DIR__ . '/i18n-titles.json';
    $raw = is_readable($path) ? file_get_contents($path) : false;
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    $data = is_array($decoded) ? $decoded : ['pages' => [], 'labs' => [], 'notes' => []];
    return $data;
}

function ichnm_pll_ready(): bool
{
    return function_exists('PLL') && PLL() && isset(PLL()->model) && PLL()->model;
}

function ichnm_setup_polylang_languages(): void
{
    if (!ichnm_pll_ready()) {
        return;
    }
    $langs = [
        ['locale' => 'ru_RU', 'slug' => 'ru', 'name' => 'Русский', 'flag' => 'by', 'term_group' => 0],
        ['locale' => 'en_US', 'slug' => 'en', 'name' => 'English', 'flag' => 'us', 'term_group' => 1],
        ['locale' => 'bel', 'slug' => 'be', 'name' => 'Беларуская', 'flag' => 'by', 'term_group' => 2],
        ['locale' => 'zh_CN', 'slug' => 'zh', 'name' => '中文', 'flag' => 'cn', 'term_group' => 3],
    ];
    foreach ($langs as $args) {
        if (PLL()->model->get_language($args['slug'])) {
            continue;
        }
        $result = PLL()->model->languages->add($args);
        if (is_wp_error($result)) {
            error_log('[ichnm] polylang add ' . $args['slug'] . ': ' . $result->get_error_message());
        }
    }

    $options = get_option('polylang');
    if (!is_array($options)) {
        $options = [];
    }
    $options['default_lang'] = 'ru';
    $options['hide_default'] = true;
    $options['force_lang'] = 1;
    $options['rewrite'] = true;
    $options['browser'] = false;
    $options['post_types'] = ['news', 'event', 'media_about', 'publication', 'department', 'person'];
    update_option('polylang', $options);

    if (method_exists(PLL()->model, 'clean_languages_cache')) {
        PLL()->model->clean_languages_cache();
    } elseif (isset(PLL()->model->languages) && method_exists(PLL()->model->languages, 'clean_cache')) {
        PLL()->model->languages->clean_cache();
    }
}

function ichnm_shell_note(string $lang): string
{
    $notes = ichnm_i18n_titles()['notes'] ?? [];
    return (string) ($notes[$lang] ?? '<p><em>Structure shell.</em></p>');
}

function ichnm_translated_title(string $slug, string $lang, string $fallback): string
{
    $pages = ichnm_i18n_titles()['pages'] ?? [];
    return (string) ($pages[$slug][$lang] ?? $fallback);
}

/**
 * RU page by exact slug, ignoring Polylang language filters.
 */
function ichnm_find_ru_page(string $slug): ?WP_Post
{
    $posts = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => 20,
        'suppress_filters' => true,
    ]);
    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        if (function_exists('pll_get_post_language')) {
            $lang = (string) pll_get_post_language((int) $post->ID);
            if ($lang !== '' && $lang !== 'ru') {
                continue;
            }
        }
        if ($post->post_name === $slug) {
            return $post;
        }
    }
    return null;
}

/**
 * Russian titles from site_model menu (never overwrite with i18n shells).
 *
 * @return array<string, string>
 */
function ichnm_ru_page_titles_from_model(): array
{
    $titles = [];
    $walk = static function (array $items) use (&$walk, &$titles): void {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            $title = (string) ($item['title'] ?? '');
            if ($id !== '' && $title !== '') {
                $titles[$id] = $title;
            }
            if (!empty($item['children']) && is_array($item['children'])) {
                $walk($item['children']);
            }
        }
    };
    $model = function_exists('ichnm_site_model') ? ichnm_site_model() : [];
    $walk($model['menu'] ?? []);
    $titles['home'] = $titles['home'] ?? 'Главная';
    $titles['search'] = $titles['search'] ?? 'Поиск';
    $titles['sitemap'] = $titles['sitemap'] ?? 'Карта сайта';
    $titles['cookies'] = $titles['cookies'] ?? 'Файлы cookie';
    $titles['personal-data'] = $titles['personal-data'] ?? 'Персональные данные';
    return $titles;
}

function ichnm_restore_ru_page_titles(): void
{
    foreach (ichnm_ru_page_titles_from_model() as $slug => $title) {
        $ru = ichnm_find_ru_page((string) $slug);
        if (!$ru instanceof WP_Post) {
            continue;
        }
        if ((string) $ru->post_title === $title) {
            continue;
        }
        wp_update_post([
            'ID' => (int) $ru->ID,
            'post_title' => $title,
        ]);
    }
}

function ichnm_assign_default_language_to_existing(): void
{
    if (!function_exists('pll_set_post_language') || !function_exists('pll_get_post_language')) {
        return;
    }
    $types = ['page', 'news', 'event', 'media_about', 'publication', 'department', 'person'];
    foreach ($types as $type) {
        $posts = get_posts([
            'post_type' => $type,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
        ]);
        foreach ($posts as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $current = pll_get_post_language($id);
            if (!$current) {
                pll_set_post_language($id, 'ru');
            }
        }
    }
}

function ichnm_ensure_page_language_shells(): void
{
    if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations') || !function_exists('pll_get_post')) {
        return;
    }
    $slugs = array_keys(ichnm_i18n_titles()['pages'] ?? []);
    $keep = [];
    foreach ($slugs as $slug) {
        $ru = ichnm_find_ru_page((string) $slug);
        if (!$ru instanceof WP_Post) {
            continue;
        }
        pll_set_post_language((int) $ru->ID, 'ru');
        $translations = ['ru' => (int) $ru->ID];
        $keep[(int) $ru->ID] = true;
        foreach (['en', 'be', 'zh'] as $lang) {
            $existing = (int) pll_get_post((int) $ru->ID, $lang);
            // Never treat the RU source as its own translation shell.
            if ($existing === (int) $ru->ID) {
                $existing = 0;
            }
            if ($existing > 0) {
                $existing_post = get_post($existing);
                if (!$existing_post instanceof WP_Post || $existing_post->post_status === 'trash') {
                    $existing = 0;
                }
            }
            $title = ichnm_translated_title($slug, $lang, $ru->post_title);
            $body = ichnm_shell_note($lang) . (string) $ru->post_content;
            $shell_name = $slug . '-' . $lang;
            if ($existing > 0) {
                wp_update_post([
                    'ID' => $existing,
                    'post_title' => $title,
                    'post_name' => $shell_name,
                    'post_content' => $body,
                ]);
                pll_set_post_language($existing, $lang);
                $translations[$lang] = $existing;
                $keep[$existing] = true;
                continue;
            }
            $new_id = (int) wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $shell_name,
                'post_content' => $body,
            ], true);
            if ($new_id <= 0) {
                continue;
            }
            pll_set_post_language($new_id, $lang);
            $translations[$lang] = $new_id;
            $keep[$new_id] = true;
        }
        pll_save_post_translations($translations);
    }
    ichnm_trash_orphan_language_shells('page', $keep);
}

function ichnm_ensure_lab_language_shells(): void
{
    if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations') || !function_exists('pll_get_post')) {
        return;
    }
    $labs = ichnm_i18n_titles()['labs'] ?? [];
    $keep = [];
    foreach ($labs as $slug => $titles) {
        $ru = function_exists('ichnm_find_by_slug') ? ichnm_find_by_slug('department', (string) $slug) : null;
        if (!$ru instanceof WP_Post) {
            continue;
        }
        if (function_exists('pll_get_post_language') && pll_get_post_language((int) $ru->ID) && pll_get_post_language((int) $ru->ID) !== 'ru') {
            continue;
        }
        pll_set_post_language((int) $ru->ID, 'ru');
        $translations = ['ru' => (int) $ru->ID];
        $keep[(int) $ru->ID] = true;
        foreach (['en', 'be', 'zh'] as $lang) {
            $existing = (int) pll_get_post((int) $ru->ID, $lang);
            if ($existing === (int) $ru->ID) {
                $existing = 0;
            }
            if ($existing > 0) {
                $existing_post = get_post($existing);
                if (!$existing_post instanceof WP_Post || $existing_post->post_status === 'trash') {
                    $existing = 0;
                }
            }
            $title = (string) ($titles[$lang] ?? $ru->post_title);
            $body = ichnm_shell_note($lang) . (string) $ru->post_content;
            $shell_name = (string) $slug . '-' . $lang;
            if ($existing > 0) {
                wp_update_post([
                    'ID' => $existing,
                    'post_title' => $title,
                    'post_name' => $shell_name,
                    'post_content' => $body,
                ]);
                pll_set_post_language($existing, $lang);
                update_post_meta($existing, '_ichnm_lab_slug', (string) $slug);
                $translations[$lang] = $existing;
                $keep[$existing] = true;
                continue;
            }
            $new_id = (int) wp_insert_post([
                'post_type' => 'department',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $shell_name,
                'post_content' => $body,
            ], true);
            if ($new_id <= 0) {
                continue;
            }
            pll_set_post_language($new_id, $lang);
            update_post_meta($new_id, '_ichnm_lab_slug', (string) $slug);
            $translations[$lang] = $new_id;
            $keep[$new_id] = true;
        }
        pll_save_post_translations($translations);
    }
    ichnm_trash_orphan_language_shells('department', $keep);
}

/**
 * Trash leftover EN/BE/ZH shells from earlier seed attempts (about-3, nano-12, …).
 *
 * @param array<int, bool> $keep
 */
function ichnm_trash_orphan_language_shells(string $post_type, array $keep): void
{
    if (!function_exists('pll_get_post_language')) {
        return;
    }
    $posts = get_posts([
        'post_type' => $post_type,
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => -1,
        'suppress_filters' => true,
    ]);
    foreach ($posts as $post) {
        $id = (int) $post->ID;
        if (isset($keep[$id])) {
            continue;
        }
        $lang = (string) pll_get_post_language($id);
        $name = (string) $post->post_name;
        if (in_array($lang, ['en', 'be', 'zh'], true) && preg_match('/-(en|be|zh)(-\d+)?$/', $name)) {
            wp_trash_post($id);
            continue;
        }
        // Duplicate RU copies left by earlier shared-slug experiments.
        if (($lang === 'ru' || $lang === '') && preg_match('/-\d+$/', $name) && $post_type === 'page') {
            wp_trash_post($id);
        }
    }
}

function ichnm_setup_polylang_contour(): void
{
    if (!ichnm_pll_ready()) {
        return;
    }
    ichnm_setup_polylang_languages();
    ichnm_assign_default_language_to_existing();
    ichnm_ensure_page_language_shells();
    ichnm_ensure_lab_language_shells();
    ichnm_restore_ru_page_titles();
    flush_rewrite_rules(false);
}
