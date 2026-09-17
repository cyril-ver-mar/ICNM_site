<?php
/**
 * Idempotent import of honest migrated_copy into pages and feed CPTs.
 */

if (!defined('ABSPATH')) {
    exit;
}

const ICHNM_CONTENT_SEED_VERSION = 30;

function ichnm_migrated_copy(): array
{
    static $copy = null;
    if (is_array($copy)) {
        return $copy;
    }
    $path = ichnm_migrated_copy_path();
    $raw = is_readable($path) ? file_get_contents($path) : false;
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    $copy = is_array($decoded) ? $decoded : [];
    return $copy;
}

function ichnm_source_url(string $relative): string
{
    return content_url('uploads/ichnm-source/' . ltrim($relative, '/'));
}

function ichnm_source_path(string $relative): string
{
    return WP_CONTENT_DIR . '/uploads/ichnm-source/' . ltrim($relative, '/');
}

/**
 * Inline Natural Earth SVG (theme fills via .world-ocean / .world-land).
 */
function ichnm_world_outline_svg(): string
{
    $path = ichnm_source_path('maps/world-countries.svg');
    if (!is_readable($path)) {
        return '';
    }
    $raw = (string) file_get_contents($path);
    $raw = preg_replace('/<\?xml[^?]*\?>/', '', $raw) ?? $raw;
    return trim($raw);
}

/**
 * @param array{paragraphs?:list<string>,list?:list<string>,file_slots?:list<string>,empty_slot?:string} $block
 */
function ichnm_blocks_html(array $block): string
{
    $html = '';
    foreach ($block['paragraphs'] ?? [] as $para) {
        $para = trim((string) $para);
        if ($para === '') {
            continue;
        }
        $html .= '<p>' . esc_html($para) . '</p>';
    }
    $items = $block['list'] ?? [];
    if ($items) {
        $html .= '<ul>';
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $html .= '<li>' . esc_html($item) . '</li>';
        }
        $html .= '</ul>';
    }
    $empty = trim((string) ($block['empty_slot'] ?? ''));
    if ($empty !== '' && !$items) {
        $html .= '<p class="ichnm-empty-slot">' . esc_html($empty) . '</p>';
    }
    $slots = $block['file_slots'] ?? [];
    if (is_array($slots) && $slots) {
        $html .= ichnm_file_slots_html($slots);
    }
    return $html;
}

/**
 * Honest PDF reception shelf — labels only, never mock downloads.
 *
 * @param list<string> $labels
 */
function ichnm_file_slots_html(array $labels): string
{
    $items = '';
    foreach ($labels as $label) {
        $label = trim((string) $label);
        if ($label === '') {
            continue;
        }
        $items .= '<li class="file-slot"><span>' . esc_html($label) . '</span>';
        $items .= '<small>Файл не загружен</small></li>';
    }
    if ($items === '') {
        return '';
    }
    return '<ul class="file-shelf" aria-label="Слоты для документов">' . $items . '</ul>';
}

function ichnm_find_by_slug(string $post_type, string $slug): ?WP_Post
{
    $meta_key = match ($post_type) {
        'department' => '_ichnm_lab_slug',
        'person' => '_ichnm_person_id',
        default => '',
    };

    $candidates = [];
    if ($meta_key !== '') {
        $candidates = get_posts([
            'post_type' => $post_type,
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => 50,
            'meta_key' => $meta_key,
            'meta_value' => $slug,
            'suppress_filters' => true,
        ]);
    }
    if (!$candidates) {
        $candidates = get_posts([
            'post_type' => $post_type,
            'name' => $slug,
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => 50,
            'suppress_filters' => true,
        ]);
    }

    $ru = null;
    $exact = null;
    foreach ($candidates as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $lang = function_exists('pll_get_post_language') ? (string) pll_get_post_language((int) $post->ID) : 'ru';
        if ($post->post_name === $slug && ($lang === 'ru' || $lang === '')) {
            return $post;
        }
        if (($lang === 'ru' || $lang === '') && $ru === null) {
            $ru = $post;
        }
        if ($post->post_name === $slug && $exact === null) {
            $exact = $post;
        }
    }
    return $ru ?? $exact;
}

function ichnm_upsert_post(array $args, string $meta_key, string $meta_value): int
{
    $existing = get_posts([
        'post_type' => $args['post_type'],
        'post_status' => 'any',
        'posts_per_page' => 50,
        'meta_key' => $meta_key,
        'meta_value' => $meta_value,
        'suppress_filters' => true,
    ]);
    $target = null;
    foreach ($existing as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $lang = function_exists('pll_get_post_language') ? (string) pll_get_post_language((int) $post->ID) : 'ru';
        if ($lang === 'ru' || $lang === '') {
            $target = $post;
            break;
        }
    }
    if ($target === null && $existing) {
        $target = $existing[0];
    }
    if ($target instanceof WP_Post) {
        $args['ID'] = (int) $target->ID;
        $result = wp_update_post($args, true);
    } else {
        $result = wp_insert_post($args, true);
    }
    if (is_wp_error($result)) {
        return 0;
    }
    $id = (int) $result;
    if ($id > 0) {
        update_post_meta($id, $meta_key, $meta_value);
        if (function_exists('pll_set_post_language') && !pll_get_post_language($id)) {
            pll_set_post_language($id, 'ru');
        }
    }
    return $id;
}

function ichnm_configure_site_contour(): void
{
    if (get_option('permalink_structure') !== '/%postname%/') {
        update_option('permalink_structure', '/%postname%/');
    }

    $home = get_page_by_path('home');
    if (!$home instanceof WP_Post) {
        $home_id = (int) wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'home',
            'post_title' => 'Главная',
            'post_content' => '',
        ]);
    } else {
        $home_id = (int) $home->ID;
    }
    if ($home_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
    }

    $sample = get_page_by_path('sample-page');
    if ($sample instanceof WP_Post) {
        wp_trash_post((int) $sample->ID);
    }

    flush_rewrite_rules(false);
}

function ichnm_sync_page_bodies(): void
{
    $pages = ichnm_migrated_copy()['pages'] ?? [];
    if (!is_array($pages)) {
        return;
    }
    foreach ($pages as $slug => $block) {
        if (!is_array($block)) {
            continue;
        }
        // Feed shells stay as archives; skip news/events placeholder pages if present.
        if (in_array($slug, ['news', 'events', 'announcement', 'media_about'], true)) {
            continue;
        }
        $page = get_page_by_path((string) $slug);
        if (!$page instanceof WP_Post) {
            continue;
        }
        $html = ichnm_blocks_html($block);
        if ($html === '') {
            continue;
        }
        if ((string) $page->post_content === $html) {
            continue;
        }
        wp_update_post([
            'ID' => (int) $page->ID,
            'post_content' => $html,
        ]);
    }

    foreach (ichnm_migrated_copy()['admin_units'] ?? [] as $unit) {
        if (!is_array($unit)) {
            continue;
        }
        $slug = (string) ($unit['id'] ?? '');
        $page = $slug !== '' ? get_page_by_path($slug) : null;
        if (!$page instanceof WP_Post) {
            continue;
        }
        $html = ichnm_admin_unit_html($unit);
        if ($html === '') {
            continue;
        }
        wp_update_post([
            'ID' => (int) $page->ID,
            'post_title' => (string) ($unit['title'] ?? $page->post_title),
            'post_content' => $html,
        ]);
    }

    ichnm_fill_leadership_page();
    ichnm_import_catalogue_detail_pages();
    ichnm_fill_catalogue_pages();
    ichnm_fill_about_pages();
    ichnm_fill_council_page();
    ichnm_fill_cooperation_page();
    ichnm_fill_contacts_pages();
    ichnm_fill_feedback_page();
    ichnm_ensure_news_hub_page();
    ichnm_ensure_publications_hub_page();
    ichnm_ensure_events_hub_page();
    ichnm_ensure_utility_pages();
    ichnm_fill_education_and_documents_hubs();
}

/**
 * @return array<string, array<string, mixed>>
 */
function ichnm_people_index(): array
{
    $index = [];
    foreach (ichnm_migrated_copy()['people'] ?? [] as $person) {
        if (!is_array($person) || empty($person['id'])) {
            continue;
        }
        $index[(string) $person['id']] = $person;
    }
    foreach (ichnm_migrated_copy()['leadership_people'] ?? [] as $person) {
        if (!is_array($person) || empty($person['id'])) {
            continue;
        }
        $id = (string) $person['id'];
        $index[$id] = array_merge($index[$id] ?? [], $person);
    }
    return $index;
}

function ichnm_person_permalink(string $id): string
{
    $post = ichnm_find_by_slug('person', $id);
    return $post instanceof WP_Post ? (string) get_permalink($post) : home_url('/people/' . rawurlencode($id) . '/');
}

/**
 * Admin / public unit page body matching preview/{hr|accounting|…}.html
 */
function ichnm_admin_unit_html(array $unit): string
{
    $structure = get_page_by_path('structure');
    $structure_href = $structure instanceof WP_Post ? (string) get_permalink($structure) : home_url('/structure/');
    $parts = [];
    $parts[] = '<p class="unit-back"><a href="' . esc_url($structure_href) . '">Ко всем подразделениям</a></p>';
    if (!empty($unit['phone'])) {
        $parts[] = '<p>Тел. подразделения: ' . esc_html((string) $unit['phone']) . '</p>';
    }

    $people = is_array($unit['people'] ?? null) ? $unit['people'] : [];
    if ($people) {
        $parts[] = '<div class="people-list">';
        foreach ($people as $person) {
            if (!is_array($person)) {
                continue;
            }
            $id = (string) ($person['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $parts[] = ichnm_person_card_html($person, $id);
        }
        $parts[] = '</div>';
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Состав подразделения появится после передачи материалов.</p>';
    }
    return implode('', $parts);
}

/**
 * Import people listed only under admin_units / SMU placeholders.
 */
function ichnm_import_admin_unit_people(): void
{
    $rows = [];
    foreach (ichnm_migrated_copy()['admin_units'] ?? [] as $unit) {
        if (!is_array($unit)) {
            continue;
        }
        $unit_title = (string) ($unit['title'] ?? $unit['id'] ?? '');
        foreach ($unit['people'] ?? [] as $person) {
            if (!is_array($person) || empty($person['id'])) {
                continue;
            }
            $id = (string) $person['id'];
            $rows[$id] = array_merge($rows[$id] ?? [], $person, [
                '_unit_title' => $unit_title,
            ]);
        }
    }
    // Preview SMU seats (honest placeholders until names arrive).
    foreach ([
        ['id' => 'smu-chair', 'name' => 'Фамилия Имя Отчество', 'role' => 'Председатель совета молодых учёных', 'initials' => 'П'],
        ['id' => 'smu-deputy', 'name' => 'Фамилия Имя Отчество', 'role' => 'Заместитель председателя', 'initials' => 'З'],
        ['id' => 'smu-secretary', 'name' => 'Фамилия Имя Отчество', 'role' => 'Секретарь', 'initials' => 'С'],
    ] as $person) {
        $id = $person['id'];
        if (!isset($rows[$id])) {
            $rows[$id] = $person;
        }
    }

    foreach ($rows as $person) {
        $id = (string) ($person['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $parts = [];
        if (!empty($person['role'])) {
            $parts[] = '<p><strong>' . esc_html((string) $person['role']) . '</strong></p>';
        }
        if (!empty($person['_unit_title'])) {
            $parts[] = '<p>' . esc_html((string) $person['_unit_title']) . '</p>';
        }
        $contacts = [];
        if (!empty($person['phone'])) {
            $contacts[] = esc_html((string) $person['phone']);
        }
        if (!empty($person['email'])) {
            $email = (string) $person['email'];
            $contacts[] = '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        if ($contacts) {
            $parts[] = '<p>' . implode(' · ', $contacts) . '</p>';
        }
        $parts[] = '<p>Развёрнутая биография появится после передачи материалов Институтом.</p>';
        ichnm_upsert_post([
            'post_type' => 'person',
            'post_status' => 'publish',
            'post_name' => $id,
            'post_title' => (string) ($person['name'] ?? $id),
            'post_content' => implode('', $parts),
        ], '_ichnm_person_id', $id);
    }
}

function ichnm_fill_leadership_page(): void
{
    $page = get_page_by_path('leadership');
    if (!$page instanceof WP_Post) {
        return;
    }
    $copy = ichnm_migrated_copy();
    $intro = ichnm_blocks_html(is_array($copy['pages']['leadership'] ?? null) ? $copy['pages']['leadership'] : []);
    $people = ichnm_people_index();
    $order = $copy['leadership_order'] ?? array_keys($people);
    $cards = ['<div class="ichnm-card-grid ichnm-leadership-grid">'];
    foreach ($order as $id) {
        $id = (string) $id;
        $person = $people[$id] ?? null;
        if (!$person) {
            continue;
        }
        $cards[] = ichnm_person_card_html($person, $id);
    }
    $cards[] = '</div>';
    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => $intro . implode('', $cards),
    ]);
}

/**
 * Index labs by id and lab-{slug} for catalogue attribution.
 *
 * @return array<string, array>
 */
function ichnm_labs_by_id(): array
{
    $labs = [];
    foreach (ichnm_migrated_copy()['labs'] ?? [] as $lab) {
        if (!is_array($lab)) {
            continue;
        }
        if (!empty($lab['id'])) {
            $labs[(string) $lab['id']] = $lab;
        }
        if (!empty($lab['slug'])) {
            $labs['lab-' . $lab['slug']] = $lab;
        }
    }
    return $labs;
}

/**
 * Contact line matching roster.person_contact_line (name · phone · email).
 */
function ichnm_person_contact_line(string $person_id): string
{
    if ($person_id === '') {
        return '';
    }
    $people = ichnm_people_index();
    if (!isset($people[$person_id]) || !is_array($people[$person_id])) {
        return '';
    }
    $row = $people[$person_id];
    $bits = [trim((string) ($row['name'] ?? ''))];
    if (!empty($row['phone'])) {
        $bits[] = (string) $row['phone'];
    }
    if (!empty($row['email'])) {
        $bits[] = (string) $row['email'];
    }
    $bits = array_values(array_filter($bits, static function ($b) {
        return $b !== '';
    }));
    return implode(' · ', $bits);
}

/**
 * Lab-sourced catalogue rows (directions / developments / equipment), like roster.py.
 *
 * @return list<array>
 */
function ichnm_lab_catalogue_rows(string $field): array
{
    $rows = [];
    foreach (ichnm_migrated_copy()['labs'] ?? [] as $lab) {
        if (!is_array($lab)) {
            continue;
        }
        $lab_id = (string) ($lab['id'] ?? '');
        $lab_title = (string) ($lab['title'] ?? '');
        $head_id = (string) ($lab['head_id'] ?? '');
        foreach ($lab[$field] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = $item;
            $row['lab_id'] = $lab_id;
            $row['lab_title'] = $lab_title;
            if ($field === 'directions') {
                $row['head_id'] = $head_id;
            } elseif ($field === 'developments' && empty($row['staff_id'])) {
                $row['staff_id'] = $head_id;
            }
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Institute-level items not already present (by slug) in lab-sourced rows.
 *
 * @param list<array> $lab_rows
 * @param list<array> $institute_items
 * @return list<array>
 */
function ichnm_institute_catalogue_rows(array $lab_rows, array $institute_items): array
{
    $seen = [];
    foreach ($lab_rows as $row) {
        $slug = (string) ($row['slug'] ?? '');
        if ($slug !== '') {
            $seen[$slug] = true;
        }
    }
    $rows = [];
    foreach ($institute_items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = (string) ($item['slug'] ?? '');
        if ($slug !== '' && isset($seen[$slug])) {
            continue;
        }
        $rows[] = $item;
    }
    return $rows;
}

/**
 * Hub titles for catalogue parents (mirrors catalogue_detail.PARENT_TITLES).
 *
 * @return array<string, string>
 */
function ichnm_catalogue_parent_titles(): array
{
    return [
        'science' => 'Направления работы',
        'developments' => 'Разработки',
        'facilities' => 'Материальная база',
    ];
}

/**
 * Contact / attribution line like roster.catalog_meta.
 */
function ichnm_catalogue_meta(array $item): string
{
    $bits = [];
    $lab_title = trim((string) ($item['lab_title'] ?? ''));
    if ($lab_title === '') {
        $lab_id = (string) ($item['lab_id'] ?? '');
        $labs = ichnm_labs_by_id();
        if ($lab_id !== '' && isset($labs[$lab_id])) {
            $lab_title = trim((string) ($labs[$lab_id]['title'] ?? ''));
        }
    }
    if ($lab_title !== '') {
        $bits[] = $lab_title;
    }
    $staff_id = (string) ($item['staff_id'] ?? $item['head_id'] ?? '');
    $contact = $staff_id !== '' ? ichnm_person_contact_line($staff_id) : '';
    if ($contact !== '') {
        $bits[] = $contact;
    } elseif (!empty($item['contacts'])) {
        $bits[] = (string) $item['contacts'];
    }
    return implode(' · ', $bits);
}

/**
 * All catalogue rows for one hub parent (institute + lab-sourced).
 *
 * @return list<array>
 */
function ichnm_catalogue_rows_for_parent(string $parent): array
{
    $copy = ichnm_migrated_copy();
    if ($parent === 'science') {
        $institute = is_array($copy['science_topics'] ?? null) ? $copy['science_topics'] : [];
        return array_merge($institute, ichnm_lab_catalogue_rows('directions'));
    }
    if ($parent === 'developments') {
        $lab_rows = ichnm_lab_catalogue_rows('developments');
        $institute = ichnm_institute_catalogue_rows(
            $lab_rows,
            is_array($copy['developments_items'] ?? null) ? $copy['developments_items'] : []
        );
        return array_merge($institute, $lab_rows);
    }
    if ($parent === 'facilities') {
        $lab_rows = ichnm_lab_catalogue_rows('equipment');
        $institute = ichnm_institute_catalogue_rows(
            $lab_rows,
            is_array($copy['facilities_items'] ?? null) ? $copy['facilities_items'] : []
        );
        return array_merge($institute, $lab_rows);
    }
    return [];
}

/**
 * Detail permalink for a catalogue slug under science|developments|facilities.
 */
function ichnm_catalogue_detail_permalink(string $parent, string $slug): string
{
    $slug = sanitize_title($slug);
    if ($slug === '' || !isset(ichnm_catalogue_parent_titles()[$parent])) {
        return '';
    }
    $page = get_page_by_path($parent . '/' . $slug);
    if ($page instanceof WP_Post) {
        return (string) get_permalink($page);
    }
    return home_url('/' . $parent . '/' . rawurlencode($slug) . '/');
}

/**
 * Honest catalogue detail body (mirrors catalogue_detail_html; theme renders h1).
 */
function ichnm_catalogue_detail_html(string $parent, array $item): string
{
    $title = (string) ($item['title'] ?? '');
    $lead = trim((string) ($item['lead'] ?? ''));
    $is_facilities = ($parent === 'facilities');
    $detail_text = trim((string) ($item['spec'] ?? $item['product'] ?? ''));
    $contacts = trim((string) ($item['contacts'] ?? ''));
    if ($contacts === '') {
        $contacts = ichnm_catalogue_meta($item);
    }
    if ($contacts === '') {
        $contacts = 'ichnm@ichnm.by';
    }

    $parts = [];
    $parts[] = ichnm_photo_slot_html($title);
    if ($lead !== '') {
        $parts[] = '<p>' . esc_html($lead) . '</p>';
    }
    $parts[] = '<h2>Описание и контакты</h2>';
    $parts[] = '<p>' . esc_html($contacts) . '</p>';

    $lab_id = (string) ($item['lab_id'] ?? '');
    $labs = ichnm_labs_by_id();
    if ($lab_id !== '' && isset($labs[$lab_id])) {
        $lab = $labs[$lab_id];
        $lab_slug = (string) ($lab['slug'] ?? '');
        $lab_title = trim((string) ($item['lab_title'] ?? $lab['title'] ?? $lab_slug));
        if ($lab_slug !== '' && $lab_title !== '') {
            $href = home_url('/labs/' . rawurlencode($lab_slug) . '/');
            $parts[] = '<p>Лаборатория: <a href="' . esc_url($href) . '">' . esc_html($lab_title) . '</a></p>';
        }
    }

    $staff_id = (string) ($item['staff_id'] ?? $item['head_id'] ?? '');
    if ($staff_id !== '') {
        $label = ichnm_person_contact_line($staff_id);
        if ($label !== '') {
            $parts[] = '<p>Закреплено: <a href="' . esc_url(ichnm_person_permalink($staff_id)) . '">'
                . esc_html($label) . '</a></p>';
        }
    }

    $parts[] = '<h2>' . esc_html($is_facilities ? 'Спецификация' : 'Продукт / результат') . '</h2>';
    if ($detail_text !== '') {
        $parts[] = '<p>' . esc_html($detail_text) . '</p>';
    }

    $parent_titles = ichnm_catalogue_parent_titles();
    $parent_page = get_page_by_path($parent);
    $parent_href = $parent_page instanceof WP_Post
        ? (string) get_permalink($parent_page)
        : home_url('/' . $parent . '/');
    $parent_title = $parent_titles[$parent] ?? $parent;
    $parts[] = '<p><a href="' . esc_url($parent_href) . '">' . esc_html($parent_title) . '</a></p>';

    return implode('', $parts);
}

/**
 * Seed child pages at /science|developments|facilities/{slug}/ from migrated copy.
 */
function ichnm_import_catalogue_detail_pages(): void
{
    foreach (array_keys(ichnm_catalogue_parent_titles()) as $parent) {
        $parent_page = get_page_by_path($parent);
        if (!$parent_page instanceof WP_Post) {
            continue;
        }
        $parent_id = (int) $parent_page->ID;
        foreach (ichnm_catalogue_rows_for_parent($parent) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $slug = sanitize_title((string) ($item['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $title = (string) ($item['title'] ?? $slug);
            ichnm_upsert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_name' => $slug,
                'post_title' => $title,
                'post_content' => ichnm_catalogue_detail_html($parent, $item),
                'post_parent' => $parent_id,
            ], '_ichnm_catalogue_key', $parent . ':' . $slug);
        }
    }
}

/**
 * Catalogue card grid with lab + staff attribution (links when ids known).
 * When $parent is set, card titles link to /{parent}/{slug}/ detail pages.
 */
function ichnm_catalogue_cards_html(string $title, array $items, string $parent = '', string $lab_key = 'lab_id'): string
{
    if (!$items) {
        return '';
    }
    $labs = ichnm_labs_by_id();
    $parts = ['<h2>' . esc_html($title) . '</h2>', '<div class="ichnm-card-grid ichnm-catalogue-grid">'];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $parts[] = '<article class="ichnm-catalogue-card">';
        $slug = sanitize_title((string) ($item['slug'] ?? ''));
        $card_title = (string) ($item['title'] ?? '');
        $detail_href = ($parent !== '' && $slug !== '')
            ? ichnm_catalogue_detail_permalink($parent, $slug)
            : '';
        if ($slug !== '' && $detail_href !== '') {
            $parts[] = '<h3 id="' . esc_attr($slug) . '"><a href="' . esc_url($detail_href) . '">'
                . esc_html($card_title) . '</a></h3>';
        } elseif ($slug !== '') {
            $parts[] = '<h3 id="' . esc_attr($slug) . '">' . esc_html($card_title) . '</h3>';
        } else {
            $parts[] = '<h3>' . esc_html($card_title) . '</h3>';
        }
        if (!empty($item['lead'])) {
            $parts[] = '<p>' . esc_html((string) $item['lead']) . '</p>';
        }
        if (!empty($item['product'])) {
            $parts[] = '<p>' . esc_html((string) $item['product']) . '</p>';
        }
        if (!empty($item['spec'])) {
            $parts[] = '<p>' . esc_html((string) $item['spec']) . '</p>';
        }

        $meta_bits = [];
        $lab_id = (string) ($item[$lab_key] ?? '');
        $lab = ($lab_id !== '' && isset($labs[$lab_id])) ? $labs[$lab_id] : null;
        $lab_title = (string) ($item['lab_title'] ?? '');
        if ($lab !== null) {
            $lab_slug = (string) ($lab['slug'] ?? '');
            if ($lab_title === '') {
                $lab_title = (string) ($lab['title'] ?? $lab_slug);
            }
            if ($lab_slug !== '' && $lab_title !== '') {
                $href = home_url('/labs/' . rawurlencode($lab_slug) . '/');
                $meta_bits[] = '<a href="' . esc_url($href) . '">' . esc_html($lab_title) . '</a>';
            } elseif ($lab_title !== '') {
                $meta_bits[] = esc_html($lab_title);
            }
        } elseif ($lab_title !== '') {
            $meta_bits[] = esc_html($lab_title);
        }

        $staff_id = (string) ($item['staff_id'] ?? $item['head_id'] ?? '');
        $contact_line = $staff_id !== '' ? ichnm_person_contact_line($staff_id) : '';
        if ($contact_line !== '') {
            $person_href = ichnm_person_permalink($staff_id);
            $meta_bits[] = '<a href="' . esc_url($person_href) . '">' . esc_html($contact_line) . '</a>';
        } elseif (!empty($item['contacts'])) {
            $meta_bits[] = esc_html((string) $item['contacts']);
        }

        if ($meta_bits) {
            $parts[] = '<p class="ichnm-catalogue-meta">' . implode(' · ', $meta_bits) . '</p>';
        }
        $parts[] = '</article>';
    }
    $parts[] = '</div>';
    return implode('', $parts);
}

function ichnm_fill_catalogue_pages(): void
{
    $copy = ichnm_migrated_copy();

    $science = get_page_by_path('science');
    if ($science instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['science'] ?? null) ? $copy['pages']['science'] : []);
        $institute = is_array($copy['science_topics'] ?? null) ? $copy['science_topics'] : [];
        $labs = ichnm_lab_catalogue_rows('directions');
        $html .= ichnm_catalogue_cards_html('Общеинститутские направления', $institute, 'science');
        $html .= ichnm_catalogue_cards_html('Направления лабораторий', $labs, 'science');
        wp_update_post(['ID' => (int) $science->ID, 'post_content' => $html]);
    }

    $developments = get_page_by_path('developments');
    if ($developments instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['developments'] ?? null) ? $copy['pages']['developments'] : []);
        $lab_rows = ichnm_lab_catalogue_rows('developments');
        $institute = ichnm_institute_catalogue_rows(
            $lab_rows,
            is_array($copy['developments_items'] ?? null) ? $copy['developments_items'] : []
        );
        if ($institute) {
            $html .= ichnm_catalogue_cards_html('Общеинститутские разработки', $institute, 'developments');
        }
        $html .= ichnm_catalogue_cards_html('Разработки лабораторий', $lab_rows, 'developments');
        wp_update_post(['ID' => (int) $developments->ID, 'post_content' => $html]);
    }

    $facilities = get_page_by_path('facilities');
    if ($facilities instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['facilities'] ?? null) ? $copy['pages']['facilities'] : []);
        $lab_rows = ichnm_lab_catalogue_rows('equipment');
        $institute = ichnm_institute_catalogue_rows(
            $lab_rows,
            is_array($copy['facilities_items'] ?? null) ? $copy['facilities_items'] : []
        );
        if ($institute) {
            $html .= ichnm_catalogue_cards_html('Общеинститутское оснащение', $institute, 'facilities');
        }
        $html .= ichnm_catalogue_cards_html('Оборудование лабораторий', $lab_rows, 'facilities');
        wp_update_post(['ID' => (int) $facilities->ID, 'post_content' => $html]);
    }

    $structure = get_page_by_path('structure');
    if ($structure instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['structure'] ?? null) ? $copy['pages']['structure'] : []);
        if (!empty($copy['structure_teaser'])) {
            $html = '<p>' . esc_html((string) $copy['structure_teaser']) . '</p>' . $html;
        }
        $html .= '<h2>Лаборатории</h2><ul class="ichnm-structure-labs">';
        foreach ($copy['labs'] ?? [] as $lab) {
            if (!is_array($lab) || empty($lab['slug'])) {
                continue;
            }
            $href = home_url('/labs/' . rawurlencode((string) $lab['slug']) . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html((string) ($lab['title'] ?? $lab['slug'])) . '</a></li>';
        }
        $html .= '</ul>';
        $html .= '<h2>Административные подразделения</h2><ul>';
        foreach ($copy['admin_units'] ?? [] as $unit) {
            if (!is_array($unit) || empty($unit['id'])) {
                continue;
            }
            $href = home_url('/' . rawurlencode((string) $unit['id']) . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html((string) ($unit['title'] ?? $unit['id'])) . '</a></li>';
        }
        $html .= '</ul>';
        // Community after admin (DECISIONS); links only — no staff dump on the hub.
        $html .= '<h2>Общественные объединения</h2><ul>';
        foreach (['union' => 'Профсоюз', 'young-scientists' => 'Совет молодых учёных'] as $slug => $title) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<li><a href="' . esc_url((string) $href) . '">' . esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        wp_update_post(['ID' => (int) $structure->ID, 'post_content' => $html]);
    }
}

function ichnm_ensure_news_hub_page(): void
{
    $existing = get_page_by_path('news');
    $intro = ichnm_blocks_html(is_array(ichnm_migrated_copy()['pages']['news'] ?? null) ? ichnm_migrated_copy()['pages']['news'] : []);
    if ($intro === '') {
        $intro = '<p>Новости Института и публикации СМИ об ИХНМ.</p>';
    }
    // Marker for the theme template; live lists are queried in page-news.php.
    $body = $intro . '<!-- ichnm:news-hub -->';
    if ($existing instanceof WP_Post) {
        wp_update_post([
            'ID' => (int) $existing->ID,
            'post_content' => $body,
            'post_title' => 'Новости',
        ]);
        return;
    }
    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => 'news',
        'post_title' => 'Новости',
        'post_content' => $body,
    ]);
}

function ichnm_person_card_html(array $person, string $id): string
{
    // Whole card is the hyperlink — no «Биография и публикации» caption (DECISIONS).
    $href = ichnm_person_permalink($id);
    $name = (string) ($person['name'] ?? $id);
    $role = (string) ($person['role'] ?? '');
    $degree = (string) ($person['degree'] ?? '');
    $phone = (string) ($person['phone'] ?? '');
    $initials = (string) ($person['initials'] ?? mb_substr($name, 0, 1));
    $html = '<a class="ichnm-person-card" href="' . esc_url($href) . '">';
    $html .= '<span class="ichnm-person-card-photo" aria-hidden="true">' . esc_html($initials) . '</span>';
    $html .= '<span class="ichnm-person-card-body">';
    $html .= '<span class="ichnm-person-card-role">' . esc_html($role) . '</span>';
    $html .= '<span class="ichnm-person-card-name">' . esc_html($name) . '</span>';
    if ($degree !== '') {
        $html .= '<span class="ichnm-person-card-degree">' . esc_html($degree) . '</span>';
    }
    if ($phone !== '') {
        $html .= '<span class="ichnm-person-card-phone">Тел. ' . esc_html($phone) . '</span>';
    }
    $html .= '</span></a>';
    return $html;
}

/**
 * Locked scientific-metric field order (DECISIONS / site_model.staff.metric_fields).
 *
 * @return list<string>
 */
function ichnm_staff_metric_fields(): array
{
    return ['orcid', 'google_scholar', 'scopus_author', 'elibrary', 'researchgate'];
}

/**
 * @return array{0:string,1:string} href, title
 */
function ichnm_unit_link(string $unit_id): array
{
    $unit_id = trim($unit_id);
    if ($unit_id === '') {
        return ['#', ''];
    }

    $copy = ichnm_migrated_copy();
    foreach ($copy['labs'] ?? [] as $lab) {
        if (!is_array($lab)) {
            continue;
        }
        $lab_id = (string) ($lab['id'] ?? '');
        $slug = (string) ($lab['slug'] ?? '');
        if ($lab_id === $unit_id || ($slug !== '' && ('lab-' . $slug) === $unit_id)) {
            $href = $slug !== '' ? home_url('/labs/' . rawurlencode($slug) . '/') : home_url('/structure/');
            $title = (string) ($lab['title'] ?? ($slug !== '' ? $slug : $unit_id));
            return [$href, $title];
        }
    }

    $titles = [
        'leadership' => 'Руководство',
        'scientific-council' => 'Учёный совет',
        'hr' => 'Отдел кадров',
        'labor-protection' => 'Охрана труда',
        'engineering' => 'Главный инженер',
        'accounting' => 'Бухгалтерия',
        'union' => 'Профсоюз',
        'young-scientists' => 'Совет молодых учёных',
        'structure' => 'Структура',
    ];
    foreach ($copy['admin_units'] ?? [] as $unit) {
        if (!is_array($unit) || empty($unit['id'])) {
            continue;
        }
        if ((string) $unit['id'] === $unit_id) {
            $titles[$unit_id] = (string) ($unit['title'] ?? $unit_id);
            break;
        }
    }

    $page = get_page_by_path($unit_id);
    $href = $page instanceof WP_Post
        ? (string) get_permalink($page)
        : home_url('/' . rawurlencode($unit_id) . '/');
    $title = $titles[$unit_id] ?? $unit_id;
    return [$href, $title];
}

/**
 * @param list<mixed> $affiliations
 */
function ichnm_person_affiliations_html(array $affiliations): string
{
    $items = [];
    foreach ($affiliations as $aff) {
        if (!is_array($aff)) {
            continue;
        }
        $unit_id = trim((string) ($aff['unit_id'] ?? ''));
        if ($unit_id === '') {
            continue;
        }
        [$href, $title] = ichnm_unit_link($unit_id);
        $role = trim((string) ($aff['role'] ?? ''));
        $label = $title;
        if ($role !== '') {
            $label = $title . ' — ' . $role;
        }
        $items[] = '<li><a href="' . esc_url($href) . '">' . esc_html($label) . '</a></li>';
    }
    if (!$items) {
        return '';
    }
    return '<h2>Подразделения</h2><ul class="plain-list">' . implode('', $items) . '</ul>';
}

function ichnm_profile_href(string $field, string $raw): string
{
    $value = trim($raw);
    if ($value === '') {
        return '';
    }
    $lower = strtolower($value);
    if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
        return $value;
    }
    if ($field === 'orcid') {
        return 'https://orcid.org/' . $value;
    }
    return '';
}

/**
 * Metrics block in locked network order; omit empty networks.
 */
function ichnm_person_metrics_html(array $person): string
{
    $labels = [
        'orcid' => 'ORCID',
        'google_scholar' => 'Google Scholar',
        'scopus_author' => 'Scopus Author',
        'elibrary' => 'eLIBRARY / РИНЦ',
        'researchgate' => 'ResearchGate',
    ];
    $profiles = is_array($person['profiles'] ?? null) ? $person['profiles'] : [];
    if (!empty($person['orcid']) && empty($profiles['orcid'])) {
        $profiles['orcid'] = (string) $person['orcid'];
    }
    $bibliometrics = is_array($person['bibliometrics'] ?? null) ? $person['bibliometrics'] : [];

    $rows = [];
    foreach (ichnm_staff_metric_fields() as $field) {
        $raw = (string) ($profiles[$field] ?? $person[$field] ?? '');
        $href = ichnm_profile_href($field, $raw);
        $stats = is_array($bibliometrics[$field] ?? null) ? $bibliometrics[$field] : [];
        $h_index = array_key_exists('h_index', $stats) ? $stats['h_index'] : null;
        $citations = array_key_exists('citations', $stats) ? $stats['citations'] : null;
        if ($h_index === '') {
            $h_index = null;
        }
        if ($citations === '') {
            $citations = null;
        }
        if ($href === '' && $h_index === null && $citations === null) {
            continue;
        }
        $bits = [];
        if ($h_index !== null) {
            $bits[] = 'h-индекс ' . esc_html((string) $h_index);
        }
        if ($citations !== null) {
            $bits[] = 'цитирований ' . esc_html((string) $citations);
        }
        if ($href !== '') {
            $bits[] = '<a href="' . esc_url($href) . '" rel="noopener noreferrer">профиль</a>';
        }
        $label = $labels[$field] ?? $field;
        $rows[] = '<dt>' . esc_html($label) . '</dt><dd>' . ($bits ? implode(' · ', $bits) : '—') . '</dd>';
    }

    if (!$rows) {
        return '<div class="empty-state"><h2>Профили и показатели ещё не указаны</h2>'
            . '<p>ORCID, Google Scholar, Scopus, eLIBRARY/РИНЦ и ResearchGate появятся '
            . 'после передачи ссылок. Индекс Хирша и число цитирований вносит '
            . 'сотрудник или редактор — сайт базы сам не опрашивает.</p></div>';
    }

    return '<h2>Наукометрия</h2>'
        . '<p class="metrics-note">Цифры и ссылки вносит сотрудник или редактор. '
        . 'Сайт не подтягивает базы автоматически.</p>'
        . '<dl class="metrics-list">' . implode('', $rows) . '</dl>';
}

function ichnm_import_council_people(): void
{
    foreach (ichnm_migrated_copy()['council_people'] ?? [] as $person) {
        if (!is_array($person) || empty($person['id'])) {
            continue;
        }
        $id = (string) $person['id'];
        $existing = ichnm_find_by_slug('person', $id);
        if ($existing instanceof WP_Post) {
            continue;
        }
        $parts = [];
        if (!empty($person['role'])) {
            $parts[] = '<p><strong>' . esc_html((string) $person['role']) . '</strong></p>';
        }
        if (!empty($person['degree'])) {
            $parts[] = '<p>' . esc_html((string) $person['degree']) . '</p>';
        }
        $contacts = [];
        if (!empty($person['phone'])) {
            $contacts[] = esc_html((string) $person['phone']);
        }
        if (!empty($person['email'])) {
            $email = (string) $person['email'];
            $contacts[] = '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        if ($contacts) {
            $parts[] = '<p>' . implode(' · ', $contacts) . '</p>';
        }
        $parts[] = '<p>Карточка учёного совета. Развёрнутая биография появится после передачи материалов Институтом.</p>';
        ichnm_upsert_post([
            'post_type' => 'person',
            'post_status' => 'publish',
            'post_name' => $id,
            'post_title' => (string) ($person['name'] ?? $id),
            'post_content' => implode('', $parts),
        ], '_ichnm_person_id', $id);
    }
}

function ichnm_fill_council_page(): void
{
    $page = get_page_by_path('scientific-council');
    if (!$page instanceof WP_Post) {
        return;
    }
    $copy = ichnm_migrated_copy();
    $intro = ichnm_blocks_html(is_array($copy['pages']['scientific-council'] ?? null) ? $copy['pages']['scientific-council'] : []);
    $people = ichnm_people_index();
    // Same whole-card pattern as Руководство (photo slot, role, contacts → people/{id}/).
    $cards = ['<div class="people-list ichnm-card-grid ichnm-leadership-grid">'];
    foreach ($copy['council_people'] ?? [] as $row) {
        if (!is_array($row) || empty($row['id'])) {
            continue;
        }
        $id = (string) $row['id'];
        $person = array_merge($people[$id] ?? [], $row);
        $cards[] = ichnm_person_card_html($person, $id);
    }
    $cards[] = '</div>';
    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => $intro . implode('', $cards),
    ]);
}

function ichnm_world_map_html(): string
{
    $partners = ichnm_migrated_copy()['partners'] ?? [];
    if (!is_array($partners) || !$partners) {
        return '';
    }
    $outline = ichnm_world_outline_svg();
    if ($outline === '') {
        return '';
    }
    $html = '<figure class="ichnm-world-map" aria-label="Карта научного сотрудничества">';
    // Natural Earth outlines — CSS fills (white land / light ocean), not a raster atlas.
    $html .= $outline;
    foreach ($partners as $partner) {
        if (!is_array($partner)) {
            continue;
        }
        $slug = sanitize_title((string) ($partner['slug'] ?? $partner['title'] ?? 'partner'));
        $x = (float) ($partner['x'] ?? 50);
        $y = (float) ($partner['y'] ?? 50);
        $title = (string) ($partner['title'] ?? '');
        $place = (string) ($partner['place'] ?? '');
        $note = (string) ($partner['note'] ?? '');
        $html .= '<div class="ichnm-map-hotspot"'
            . ($y < 42.0 ? ' data-pop="below"' : '')
            . ' style="left:' . esc_attr((string) $x) . '%;top:' . esc_attr((string) $y) . '%">';
        $html .= '<button type="button" class="ichnm-map-pin" aria-describedby="ichnm-pop-' . esc_attr($slug) . '">';
        $html .= '<span class="screen-reader-text">' . esc_html($title) . '</span></button>';
        $html .= '<div class="ichnm-map-pop" id="ichnm-pop-' . esc_attr($slug) . '">';
        $html .= ichnm_photo_slot_html($title !== '' ? $title : $slug);
        if ($place !== '') {
            $html .= '<p class="ichnm-map-place">' . esc_html($place) . '</p>';
        }
        $html .= '<h3>' . esc_html($title) . '</h3>';
        if ($note !== '') {
            $html .= '<p>' . esc_html($note) . '</p>';
        }
        $html .= '</div></div>';
    }
    $html .= '</figure>';
    return $html;
}

function ichnm_minsk_map_html(): string
{
    // Prefer repo assets path (Docker mounts assets → uploads/ichnm-source).
    $candidates = [
        WP_CONTENT_DIR . '/uploads/ichnm-source/maps/minsk.svg',
        dirname(__DIR__, 4) . '/assets/maps/minsk.svg',
    ];
    $path = '';
    $raw = '';
    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            $path = $candidate;
            $raw = (string) file_get_contents($candidate);
            break;
        }
    }
    $left = '65.66';
    $top = '29.13';
    if ($raw !== '') {
        if (preg_match('/data-pin-left="([\d.]+)"/', $raw, $m)) {
            $left = $m[1];
        }
        if (preg_match('/data-pin-top="([\d.]+)"/', $raw, $m)) {
            $top = $m[1];
        }
    }
    // Inline SVG so theme CSS can paint .world-ocean / .world-land (img cannot).
    $svg = '';
    if ($raw !== '') {
        $svg = preg_replace('/^<\?xml[^>]*>\s*/', '', $raw) ?? $raw;
        $svg = preg_replace('/\saria-hidden="[^"]*"/', '', $svg) ?? $svg;
    }
    $html = '<figure class="ichnm-world-map ichnm-city-map" aria-label="Минск, ул. Ф. Скорины, 36">';
    if ($svg !== '') {
        $html .= $svg;
    } elseif ($path !== '') {
        $html .= '<img class="ichnm-world-map-bg" src="' . esc_url(ichnm_source_url('maps/minsk.svg')) . '" alt="" width="800" height="500" loading="lazy">';
    }
    $html .= '<div class="ichnm-map-hotspot"'
        . ((float) $top < 42.0 ? ' data-pop="below"' : '')
        . ' style="left:' . esc_attr($left) . '%;top:' . esc_attr($top) . '%">';
    $html .= '<span class="ichnm-map-pin" title="ул. Ф. Скорины, 36"></span>';
    $html .= '<div class="ichnm-map-pop is-open">';
    $html .= '<p class="ichnm-map-place">Минск</p>';
    $html .= '<h3>ул. Ф. Скорины, 36</h3>';
    $html .= '<p>ГНУ «Институт химии новых материалов НАН Беларуси»</p>';
    $html .= '</div></div></figure>';
    return $html;
}

function ichnm_fill_cooperation_page(): void
{
    $page = get_page_by_path('cooperation');
    if (!$page instanceof WP_Post) {
        return;
    }
    $copy = ichnm_migrated_copy();
    $html = ichnm_blocks_html(is_array($copy['pages']['cooperation'] ?? null) ? $copy['pages']['cooperation'] : []);
    $partners = $copy['partners'] ?? [];
    if (is_array($partners) && $partners) {
        $html .= '<h2>Партнёры</h2><div class="ichnm-card-grid ichnm-catalogue-grid">';
        foreach ($partners as $partner) {
            if (!is_array($partner)) {
                continue;
            }
            $html .= '<article class="ichnm-catalogue-card">';
            if (!empty($partner['place'])) {
                $html .= '<p class="ichnm-catalogue-meta">' . esc_html((string) $partner['place']) . '</p>';
            }
            $html .= '<h3>' . esc_html((string) ($partner['title'] ?? '')) . '</h3>';
            if (!empty($partner['note'])) {
                $html .= '<p>' . esc_html((string) $partner['note']) . '</p>';
            }
            $html .= '</article>';
        }
        $html .= '</div>';
    }
    $html .= '<section class="ichnm-coop-map" aria-labelledby="ichnm-coop-map-title">';
    $html .= '<h2 id="ichnm-coop-map-title">Карта сотрудничества</h2>';
    // Shortcode renders inline Natural Earth SVG at runtime (avoids kses stripping <svg>).
    $html .= '[ichnm_world_map]';
    $html .= '</section>';
    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => $html,
    ]);
}

function ichnm_fill_contacts_pages(): void
{
    $copy = ichnm_migrated_copy();
    $contacts = get_page_by_path('contacts');
    if ($contacts instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['contacts'] ?? null) ? $copy['pages']['contacts'] : []);
        $feedback = get_page_by_path('feedback');
        $requisites = get_page_by_path('requisites');
        $html .= '<p>';
        if ($feedback instanceof WP_Post) {
            $html .= '<a class="ichnm-pill ichnm-pill-primary" href="' . esc_url(get_permalink($feedback)) . '">Написать нам</a> ';
        }
        if ($requisites instanceof WP_Post) {
            $html .= '<a class="ichnm-pill" href="' . esc_url(get_permalink($requisites)) . '">Реквизиты</a>';
        }
        $html .= '</p>';
        $html .= '<h2>Как нас найти</h2>[ichnm_minsk_map]';
        wp_update_post(['ID' => (int) $contacts->ID, 'post_content' => $html]);
    }

    $requisites_page = get_page_by_path('requisites');
    if ($requisites_page instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['requisites'] ?? null) ? $copy['pages']['requisites'] : []);
        wp_update_post(['ID' => (int) $requisites_page->ID, 'post_content' => $html]);
    }
}

function ichnm_fill_feedback_page(): void
{
    $page = get_page_by_path('feedback');
    if (!$page instanceof WP_Post) {
        $page_id = (int) wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'feedback',
            'post_title' => 'Обратная связь',
            'post_content' => '',
        ]);
        $page = $page_id > 0 ? get_post($page_id) : null;
    }
    if (!$page instanceof WP_Post) {
        return;
    }
    $appeals = get_page_by_path('e-appeals');
    $html = '<p>Письмо в институт по общим вопросам. Официальные обращения по Закону об обращениях граждан — на отдельной странице';
    if ($appeals instanceof WP_Post) {
        $html .= ' <a href="' . esc_url(get_permalink($appeals)) . '">электронных обращений</a>';
    }
    $html .= '.</p>';
    $html .= '[ichnm_feedback_form]';
    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => $html,
    ]);
}

function ichnm_ensure_publications_hub_page(): void
{
    $intro = ichnm_blocks_html(is_array(ichnm_migrated_copy()['pages']['publications'] ?? null) ? ichnm_migrated_copy()['pages']['publications'] : []);
    if ($intro === '') {
        $intro = '<p>Каталог публикаций Института. DOI и лаборатория указываются у каждой записи.</p>';
    }
    $body = $intro . '<!-- ichnm:publications-hub -->';
    $existing = get_page_by_path('publications');
    if ($existing instanceof WP_Post) {
        wp_update_post([
            'ID' => (int) $existing->ID,
            'post_title' => 'Публикации',
            'post_content' => $body,
        ]);
        return;
    }
    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => 'publications',
        'post_title' => 'Публикации',
        'post_content' => $body,
    ]);
}

/**
 * True when a DOI/cite is preview fish (not honest starter catalogue).
 */
function ichnm_publication_row_is_mock(array $row): bool
{
    $doi = strtolower(trim((string) ($row['doi'] ?? '')));
    if ($doi !== '' && str_contains($doi, 'ichnm.mock')) {
        return true;
    }
    $cite = (string) ($row['cite'] ?? '');
    return str_contains(mb_strtolower($cite), 'макет');
}

/**
 * Institute hub rows: top-level publications_items, then non-mock lab pack rows.
 *
 * @return list<array{row: array, lab_slug: string, lab_title: string, index: int}>
 */
function ichnm_publication_import_rows(): array
{
    $copy = ichnm_migrated_copy();
    $out = [];
    $seen = [];

    $top = $copy['publications_items'] ?? $copy['publications'] ?? [];
    if (is_array($top)) {
        foreach (array_values($top) as $index => $row) {
            if (!is_array($row) || ichnm_publication_row_is_mock($row)) {
                continue;
            }
            $cite = trim((string) ($row['cite'] ?? ''));
            if ($cite === '') {
                continue;
            }
            $lab_slug = trim((string) ($row['lab_slug'] ?? ''));
            if ($lab_slug === '' && !empty($row['lab_id'])) {
                $lab_id = (string) $row['lab_id'];
                $lab_slug = str_starts_with($lab_id, 'lab-') ? substr($lab_id, 4) : $lab_id;
            }
            $lab_title = trim((string) ($row['laboratory'] ?? $row['lab_title'] ?? $lab_slug));
            $doi = trim((string) ($row['doi'] ?? ''));
            $key = strtolower($lab_slug . '|' . $cite . '|' . $doi);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'row' => $row,
                'lab_slug' => $lab_slug,
                'lab_title' => $lab_title,
                'index' => (int) $index,
            ];
        }
    }

    foreach ($copy['labs'] ?? [] as $lab) {
        if (!is_array($lab)) {
            continue;
        }
        $lab_slug = (string) ($lab['slug'] ?? '');
        $lab_title = (string) ($lab['title'] ?? $lab_slug);
        foreach (array_values($lab['publications'] ?? []) as $index => $row) {
            if (!is_array($row) || ichnm_publication_row_is_mock($row)) {
                continue;
            }
            $cite = trim((string) ($row['cite'] ?? ''));
            if ($cite === '') {
                continue;
            }
            $doi = trim((string) ($row['doi'] ?? ''));
            $key = strtolower($lab_slug . '|' . $cite . '|' . $doi);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'row' => $row,
                'lab_slug' => $lab_slug,
                'lab_title' => $lab_title,
                'index' => (int) $index,
            ];
        }
    }

    return $out;
}

function ichnm_import_publications(): void
{
    foreach (ichnm_publication_import_rows() as $entry) {
        $row = $entry['row'];
        $lab_slug = (string) $entry['lab_slug'];
        $lab_title = (string) $entry['lab_title'];
        $index = (int) $entry['index'];
        $cite = trim((string) ($row['cite'] ?? ''));
        $doi = trim((string) ($row['doi'] ?? ''));
        $year = (int) ($row['year'] ?? 0);
        $key = 'pub-' . md5($lab_slug . '|' . $cite . '|' . $doi);
        $html = '<p class="ichnm-pub-cite">' . esc_html($cite) . '</p>';
        if ($doi !== '') {
            $href = str_starts_with(strtolower($doi), 'http') ? $doi : ('https://doi.org/' . $doi);
            $label = $doi;
            foreach (['https://doi.org/', 'http://doi.org/', 'doi:'] as $prefix) {
                if (str_starts_with(strtolower($label), $prefix)) {
                    $label = substr($label, strlen($prefix));
                    break;
                }
            }
            $html .= '<p class="ichnm-pub-doi"><a href="' . esc_url($href) . '" rel="noopener noreferrer">DOI: ' . esc_html($label) . '</a></p>';
        }
        if ($lab_slug !== '') {
            $html .= '<p class="ichnm-pub-lab"><a href="' . esc_url(home_url('/labs/' . rawurlencode($lab_slug) . '/')) . '">' . esc_html($lab_title !== '' ? $lab_title : $lab_slug) . '</a></p>';
        } elseif ($lab_title !== '') {
            $html .= '<p class="ichnm-pub-lab">' . esc_html($lab_title) . '</p>';
        }
        $post_date = ($year > 1990 ? (string) $year : '2024') . '-06-15 12:00:00';
        $title = mb_substr($cite, 0, 120);
        $slug_base = $lab_slug !== '' ? $lab_slug : 'institute';
        ichnm_upsert_post([
            'post_type' => 'publication',
            'post_status' => 'publish',
            'post_name' => sanitize_title($slug_base . '-pub-' . ($index + 1) . '-' . substr(md5($cite), 0, 6)),
            'post_title' => $title,
            'post_content' => $html,
            'post_date' => $post_date,
            'post_date_gmt' => get_gmt_from_date($post_date),
        ], '_ichnm_source_slug', $key);
        $post = get_posts([
            'post_type' => 'publication',
            'meta_key' => '_ichnm_source_slug',
            'meta_value' => $key,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);
        if ($post) {
            update_post_meta((int) $post[0]->ID, '_ichnm_lab_slug', $lab_slug);
            update_post_meta((int) $post[0]->ID, '_ichnm_doi', $doi);
            if ($year > 0) {
                update_post_meta((int) $post[0]->ID, '_ichnm_year', $year);
            }
        }
    }
}

function ichnm_publication_chart_html(): string
{
    $data = ichnm_migrated_copy()['publication_years'] ?? [];
    if (!is_array($data)) {
        return '';
    }
    $series = is_array($data['series'] ?? null) ? $data['series'] : [];
    if (!$series) {
        return '';
    }
    $max = 1;
    foreach ($series as $row) {
        $max = max($max, (int) ($row['count'] ?? 0));
    }
    $note = (string) ($data['note'] ?? '');
    $html = '';
    if ($note !== '') {
        $html .= '<p class="ichnm-chart-note" role="note">' . esc_html($note) . '</p>';
    }
    $caption = function_exists('ichnm_hub_strings')
        ? (string) (ichnm_hub_strings()['articles_by_year'] ?? 'Статьи по годам')
        : 'Статьи по годам';
    $html .= '<figure class="ichnm-pub-chart"><figcaption>' . esc_html($caption) . '</figcaption>';
    $html .= '<ul class="ichnm-chart-bars">';
    foreach ($series as $row) {
        $year = (int) ($row['year'] ?? 0);
        $count = (int) ($row['count'] ?? 0);
        $pct = max(8, (int) round(100 * $count / $max));
        $html .= '<li><span class="ichnm-chart-col" style="--ichnm-bar:' . esc_attr((string) $pct) . '%" title="' . esc_attr((string) $count) . '"></span>';
        $html .= '<span class="ichnm-chart-year">' . esc_html((string) $year) . '</span>';
        $html .= '<span class="ichnm-chart-count">' . esc_html((string) $count) . '</span></li>';
    }
    $html .= '</ul></figure>';
    return $html;
}

function ichnm_fill_education_and_documents_hubs(): void
{
    $copy = ichnm_migrated_copy();
    $pages = is_array($copy['pages'] ?? null) ? $copy['pages'] : [];

    $education_children = [
        'aspirantura' => 'Аспирантура',
        'doctorate' => 'Докторантура',
        'defense-council' => 'Совет по защитам',
        'internships' => 'Стажировки',
        'courses' => 'Курсы',
    ];
    foreach ($education_children as $slug => $_title) {
        ichnm_fill_vitrine_page_from_copy($slug, is_array($pages[$slug] ?? null) ? $pages[$slug] : []);
    }

    $education = get_page_by_path('education');
    if ($education instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($pages['education'] ?? null) ? $pages['education'] : []);
        $html .= '<h2>Разделы</h2><ul class="ichnm-hub-links">';
        foreach ($education_children as $slug => $title) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        wp_update_post(['ID' => (int) $education->ID, 'post_content' => $html]);
    }

    $document_children = [
        'charter' => 'Устав',
        'anti-corruption' => 'Антикоррупция',
        'e-appeals' => 'Электронные обращения',
    ];
    foreach ($document_children as $slug => $_title) {
        ichnm_fill_vitrine_page_from_copy($slug, is_array($pages[$slug] ?? null) ? $pages[$slug] : []);
    }

    $documents = get_page_by_path('documents');
    if ($documents instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($pages['documents'] ?? null) ? $pages['documents'] : []);
        $html .= '<h2>Документы</h2><ul class="ichnm-hub-links">';
        foreach ($document_children as $slug => $title) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        $requisites = get_page_by_path('requisites');
        $requisites_href = $requisites instanceof WP_Post
            ? (string) get_permalink($requisites)
            : home_url('/requisites/');
        $html .= '<p class="ichnm-chart-note">Реквизиты института — в разделе <a href="'
            . esc_url($requisites_href)
            . '">Контакты → Реквизиты</a>, не здесь.</p>';
        wp_update_post(['ID' => (int) $documents->ID, 'post_content' => $html]);
    }

    $union = get_page_by_path('union');
    if ($union instanceof WP_Post) {
        $structure = get_page_by_path('structure');
        $structure_href = $structure instanceof WP_Post ? (string) get_permalink($structure) : home_url('/structure/');
        $html = '<p class="unit-back"><a href="' . esc_url($structure_href) . '">Ко всем подразделениям</a></p>';
        $html .= ichnm_blocks_html(is_array($pages['union'] ?? null) ? $pages['union'] : []);
        $html .= '<p class="ichnm-chart-note">Это страница первичной организации института, а не ссылка на общеакадемический сайт <a href="https://profnan.by/">profnan.by</a>.</p>';
        wp_update_post(['ID' => (int) $union->ID, 'post_content' => $html]);
    }

    $smu = get_page_by_path('young-scientists');
    if ($smu instanceof WP_Post) {
        $structure = get_page_by_path('structure');
        $structure_href = $structure instanceof WP_Post ? (string) get_permalink($structure) : home_url('/structure/');
        $html = '<p class="unit-back"><a href="' . esc_url($structure_href) . '">Ко всем подразделениям</a></p>';
        $html .= ichnm_blocks_html(is_array($pages['young-scientists'] ?? null) ? $pages['young-scientists'] : []);
        // Honest photo-slot cards (same helper as leadership / council) until names arrive.
        $smu_people = [
            ['id' => 'smu-chair', 'name' => 'Фамилия Имя Отчество', 'role' => 'Председатель совета молодых учёных', 'initials' => 'П'],
            ['id' => 'smu-deputy', 'name' => 'Фамилия Имя Отчество', 'role' => 'Заместитель председателя', 'initials' => 'З'],
            ['id' => 'smu-secretary', 'name' => 'Фамилия Имя Отчество', 'role' => 'Секретарь', 'initials' => 'С'],
        ];
        $html .= '<div class="people-list ichnm-card-grid ichnm-leadership-grid">';
        foreach ($smu_people as $person) {
            $html .= ichnm_person_card_html($person, (string) $person['id']);
        }
        $html .= '</div>';
        wp_update_post(['ID' => (int) $smu->ID, 'post_content' => $html]);
    }

    $vacancies = get_page_by_path('vacancies');
    if ($vacancies instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($pages['vacancies'] ?? null) ? $pages['vacancies'] : []);
        $feedback = get_page_by_path('feedback');
        if ($feedback instanceof WP_Post) {
            $html .= '<p><a class="ichnm-pill ichnm-pill-primary" href="' . esc_url(get_permalink($feedback)) . '">Написать нам</a></p>';
        }
        wp_update_post(['ID' => (int) $vacancies->ID, 'post_content' => $html]);
    }
}

/**
 * @param array{paragraphs?:list<string>,list?:list<string>,file_slots?:list<string>,empty_slot?:string} $block
 */
function ichnm_fill_vitrine_page_from_copy(string $slug, array $block): void
{
    $page = get_page_by_path($slug);
    if (!$page instanceof WP_Post) {
        return;
    }
    $html = ichnm_blocks_html($block);
    if ($html === '') {
        return;
    }
    wp_update_post(['ID' => (int) $page->ID, 'post_content' => $html]);
}

function ichnm_import_news(): void
{
    foreach (ichnm_migrated_copy()['news'] ?? [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = (string) ($item['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $html = ichnm_blocks_html(['paragraphs' => $item['paragraphs'] ?? []]);
        if (!empty($item['source_href'])) {
            $html .= '<p><a href="' . esc_url((string) $item['source_href']) . '">Источник на ichnm.by</a></p>';
        }
        $images = $item['images'] ?? [];
        if (is_array($images) && $images) {
            $html .= '<div class="ichnm-news-gallery">';
            foreach ($images as $image) {
                $src = ichnm_source_url('news/' . $slug . '/' . ltrim((string) $image, '/'));
                $html .= '<p><img src="' . esc_url($src) . '" alt="" loading="lazy"></p>';
            }
            $html .= '</div>';
        }
        $date = (string) ($item['date'] ?? current_time('Y-m-d'));
        ichnm_upsert_post([
            'post_type' => 'news',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => (string) ($item['title'] ?? $slug),
            'post_content' => $html,
            'post_date' => $date . ' 10:00:00',
            'post_date_gmt' => get_gmt_from_date($date . ' 10:00:00'),
        ], '_ichnm_source_slug', $slug);
    }
}

function ichnm_import_media_about(): void
{
    foreach (ichnm_migrated_copy()['media_items'] ?? [] as $index => $item) {
        if (!is_array($item)) {
            continue;
        }
        $key = 'media-' . md5((string) ($item['href'] ?? $item['title'] ?? $index));
        $slug = sanitize_title((string) ($item['title'] ?? $key));
        $html = '';
        if (!empty($item['outlet'])) {
            $html .= '<p><strong>' . esc_html((string) $item['outlet']) . '</strong></p>';
        }
        if (!empty($item['summary'])) {
            $html .= '<p>' . esc_html((string) $item['summary']) . '</p>';
        }
        if (!empty($item['href'])) {
            $html .= '<p><a href="' . esc_url((string) $item['href']) . '">Читать у издателя</a></p>';
        }
        $date = (string) ($item['date'] ?? current_time('Y-m-d'));
        ichnm_upsert_post([
            'post_type' => 'media_about',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => (string) ($item['title'] ?? $slug),
            'post_content' => $html,
            'post_date' => $date . ' 12:00:00',
            'post_date_gmt' => get_gmt_from_date($date . ' 12:00:00'),
        ], '_ichnm_source_slug', $key);
    }
}

/**
 * Home / events banners read next_event from migrated_copy.
 * Do not seed a separate CPT row that collides with conference aist-2025
 * (previously produced /event/aist-2025-2/). Trash any stale duplicate.
 */
function ichnm_import_next_event(): void
{
    $conference_slugs = [];
    foreach (ichnm_migrated_copy()['conferences'] ?? [] as $conf) {
        if (is_array($conf) && !empty($conf['slug'])) {
            $conference_slugs[(string) $conf['slug']] = true;
        }
    }

    $stale = get_posts([
        'post_type' => 'event',
        'post_status' => 'any',
        'posts_per_page' => 50,
        'meta_key' => '_ichnm_source_slug',
        'meta_value' => 'aist-2025',
        'suppress_filters' => true,
    ]);
    foreach ($stale as $post) {
        if ($post instanceof WP_Post) {
            wp_trash_post((int) $post->ID);
        }
    }

    $event = ichnm_migrated_copy()['next_event'] ?? null;
    if (!is_array($event) || empty($event['title'])) {
        return;
    }
    $slug = 'aist-2025';
    if (isset($conference_slugs[$slug])) {
        // Conference import owns /conferences/aist-2025/ with materials.
        return;
    }
    $html = '<p>' . esc_html((string) ($event['when'] ?? '')) . '</p>';
    if (!empty($event['href'])) {
        $html .= '<p><a href="' . esc_url((string) $event['href']) . '">Регистрация / сайт серии</a></p>';
    }
    $html .= '<p>Подробности цикла — в разделе <a href="' . esc_url(home_url('/aist/')) . '">AIST</a>.</p>';
    ichnm_upsert_post([
        'post_type' => 'event',
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => (string) $event['title'],
        'post_content' => $html,
        'post_date' => '2025-10-21 09:00:00',
        'post_date_gmt' => get_gmt_from_date('2025-10-21 09:00:00'),
    ], '_ichnm_source_slug', $slug);
}

function ichnm_photo_slot_html(string $label, string $initials = ''): string
{
    $html = '<div class="photo-slot" role="img" aria-label="' . esc_attr('Место для фото: ' . $label) . '">';
    if ($initials !== '') {
        $html .= '<span>' . esc_html($initials) . '</span>';
    } else {
        $html .= '<span aria-hidden="true">На</span><p>Фото появится после передачи файла</p>';
    }
    $html .= '</div>';
    return $html;
}

function ichnm_lab_pack_html(array $lab): string
{
    $title = (string) ($lab['title'] ?? 'Лаборатория');
    $slug = (string) ($lab['slug'] ?? '');
    $people_index = function_exists('ichnm_people_index') ? ichnm_people_index() : [];
    $head_id = (string) ($lab['head_id'] ?? '');

    $staff_ids = [];
    if ($head_id !== '') {
        $staff_ids[] = $head_id;
    }
    foreach ($lab['staff_ids'] ?? [] as $sid) {
        $sid = (string) $sid;
        if ($sid !== '' && !in_array($sid, $staff_ids, true)) {
            $staff_ids[] = $sid;
        }
    }

    $resolve_person = static function (string $sid) use ($people_index, $head_id): ?array {
        $person = $people_index[$sid] ?? null;
        if (!$person) {
            $post = ichnm_find_by_slug('person', $sid);
            if ($post instanceof WP_Post) {
                $person = [
                    'id' => $sid,
                    'name' => get_the_title($post),
                    'role' => ($sid === $head_id) ? 'Заведующий лабораторией' : 'Сотрудник',
                    'initials' => mb_substr(get_the_title($post), 0, 1),
                ];
            }
        }
        if (!$person) {
            return null;
        }
        if ($sid === $head_id && empty($person['role'])) {
            $person['role'] = 'Заведующий лабораторией';
        }
        return $person;
    };

    $nav = [
        'about' => 'О лаборатории',
        'directions' => 'Направления',
        'projects' => 'Действующие и завершённые научные проекты',
        'equipment' => 'Оборудование',
        'services' => 'Услуги и разработки',
        'staff' => 'Команда',
        'pubs' => 'Публикации',
        'contacts' => 'Контакты',
    ];

    $parts = [];
    $parts[] = '<nav class="lab-local" aria-label="Разделы лаборатории">';
    foreach ($nav as $id => $label) {
        $parts[] = '<a href="#' . esc_attr($id) . '">' . esc_html($label) . '</a>';
    }
    $parts[] = '</nav>';

    if (!empty($lab['kicker'])) {
        $parts[] = '<p class="lab-kicker">' . esc_html((string) $lab['kicker']) . '</p>';
    }

    // About
    $about = trim((string) ($lab['about'] ?? ''));
    $parts[] = '<section id="about" class="lab-split">';
    $parts[] = ichnm_photo_slot_html($title);
    $parts[] = '<div><h2>О лаборатории</h2>';
    if ($about !== '') {
        $parts[] = '<p>' . esc_html($about) . '</p>';
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Текст о лаборатории появится после передачи материалов.</p>';
    }
    $parts[] = '</div></section>';

    // Directions
    $directions = is_array($lab['directions'] ?? null) ? $lab['directions'] : [];
    $parts[] = '<section id="directions"><h2>Направления</h2>';
    if ($directions) {
        $parts[] = '<ul class="ichnm-lab-dir-list">';
        foreach ($directions as $row) {
            if (is_string($row)) {
                $parts[] = '<li><strong>' . esc_html($row) . '</strong></li>';
                continue;
            }
            if (!is_array($row)) {
                continue;
            }
            $d_title = (string) ($row['title'] ?? $row['name'] ?? '');
            $lead = (string) ($row['lead'] ?? '');
            if ($d_title === '') {
                continue;
            }
            $parts[] = '<li><strong>' . esc_html($d_title) . '</strong>';
            if ($lead !== '') {
                $parts[] = '<p>' . esc_html($lead) . '</p>';
            }
            $parts[] = '</li>';
        }
        $parts[] = '</ul>';
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Направления появятся после передачи перечня.</p>';
    }
    $parts[] = '</section>';

    // Projects
    $projects = is_array($lab['projects'] ?? null) ? $lab['projects'] : [];
    $active = [];
    $done = [];
    foreach ($projects as $project) {
        if (!is_array($project)) {
            continue;
        }
        $line = trim((string) ($project['title'] ?? ''));
        if ($line === '') {
            continue;
        }
        $meta = [];
        if (!empty($project['years'])) {
            $meta[] = (string) $project['years'];
        }
        if (!empty($project['lead'])) {
            $meta[] = (string) $project['lead'];
        }
        $item = '<li><strong>' . esc_html($line) . '</strong>';
        if ($meta) {
            $item .= '<p>' . esc_html(implode(' · ', $meta)) . '</p>';
        }
        $item .= '</li>';
        if (($project['status'] ?? '') === 'completed') {
            $done[] = $item;
        } else {
            $active[] = $item;
        }
    }
    $parts[] = '<section id="projects"><h2>Действующие и завершённые научные проекты</h2>';
    if ($active || $done) {
        if ($active) {
            $parts[] = '<h3>Действующие</h3><ul class="ichnm-lab-project-list">' . implode('', $active) . '</ul>';
        }
        if ($done) {
            $parts[] = '<h3>Завершённые</h3><ul class="ichnm-lab-project-list">' . implode('', $done) . '</ul>';
        }
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Перечень проектов появится после передачи материалов Институтом.</p>';
    }
    $parts[] = '</section>';

    // Equipment
    $equipment = is_array($lab['equipment'] ?? null) ? $lab['equipment'] : [];
    $parts[] = '<section id="equipment"><h2>Оборудование</h2>';
    if ($equipment) {
        $parts[] = '<ul class="lab-equip">';
        foreach ($equipment as $row) {
            if (!is_array($row) && !is_string($row)) {
                continue;
            }
            $e_title = is_string($row) ? $row : (string) ($row['title'] ?? $row['name'] ?? '');
            if ($e_title === '') {
                continue;
            }
            $e_slug = is_array($row) ? (string) ($row['slug'] ?? '') : '';
            $lead = is_array($row) ? (string) ($row['lead'] ?? $row['spec'] ?? '') : '';
            $href = $e_slug !== '' ? home_url('/facilities/#' . sanitize_title($e_slug)) : '';
            $parts[] = '<li class="lab-equip-item">';
            if ($href !== '') {
                $parts[] = '<a class="lab-equip-card" href="' . esc_url($href) . '">';
            } else {
                $parts[] = '<div class="lab-equip-card">';
            }
            $parts[] = ichnm_photo_slot_html($e_title);
            $parts[] = '<h3>' . esc_html($e_title) . '</h3>';
            if ($lead !== '') {
                $parts[] = '<p>' . esc_html($lead) . '</p>';
            }
            $parts[] = $href !== '' ? '</a>' : '</div>';
            $parts[] = '</li>';
        }
        $parts[] = '</ul>';
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Список приборов появится из материальной базы.</p>';
    }
    $parts[] = '</section>';

    // Services / developments
    $developments = is_array($lab['developments'] ?? null) ? $lab['developments'] : [];
    $parts[] = '<section id="services"><h2>Услуги и разработки</h2>';
    if ($developments) {
        $parts[] = '<ul class="lab-equip">';
        foreach ($developments as $row) {
            if (!is_array($row)) {
                continue;
            }
            $d_title = (string) ($row['title'] ?? '');
            if ($d_title === '') {
                continue;
            }
            $d_slug = (string) ($row['slug'] ?? '');
            $lead = (string) ($row['lead'] ?? '');
            $href = $d_slug !== '' ? home_url('/developments/#' . sanitize_title($d_slug)) : '';
            $parts[] = '<li class="lab-equip-item">';
            if ($href !== '') {
                $parts[] = '<a class="lab-equip-card" href="' . esc_url($href) . '">';
            } else {
                $parts[] = '<div class="lab-equip-card">';
            }
            $parts[] = ichnm_photo_slot_html($d_title);
            $parts[] = '<h3>' . esc_html($d_title) . '</h3>';
            if ($lead !== '') {
                $parts[] = '<p>' . esc_html($lead) . '</p>';
            }
            $parts[] = $href !== '' ? '</a>' : '</div>';
            $parts[] = '</li>';
        }
        $parts[] = '</ul>';
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Разработки лаборатории появятся после передачи каталога.</p>';
    }
    $parts[] = '</section>';

    // Staff
    $parts[] = '<section id="staff"><h2>Наша команда</h2><div class="staff-tab">';
    $parts[] = '<p>Карточка ведёт на персональную страницу. У человека может быть несколько подразделений. Индекс Хирша и профили баз — на персональной странице.</p>';
    if ($staff_ids) {
        $parts[] = '<div class="people-list">';
        foreach ($staff_ids as $sid) {
            $person = $resolve_person($sid);
            if (!$person) {
                continue;
            }
            $parts[] = ichnm_person_card_html($person, $sid);
        }
        $parts[] = '</div>';
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Состав появится после передачи списка лабораторией.</p>';
    }
    $parts[] = '</div></section>';

    // Publications
    $publications = is_array($lab['publications'] ?? null) ? $lab['publications'] : [];
    $parts[] = '<section id="pubs"><h2>Избранные публикации</h2>';
    if ($publications) {
        $by_year = [];
        foreach ($publications as $pub) {
            if (!is_array($pub)) {
                continue;
            }
            $year = (string) ($pub['year'] ?? '—');
            $by_year[$year][] = $pub;
        }
        krsort($by_year);
        foreach ($by_year as $year => $rows) {
            $parts[] = '<h3 id="pub-year-' . esc_attr(sanitize_title($year)) . '">' . esc_html($year) . '</h3><ul class="ichnm-lab-pub-list">';
            foreach ($rows as $pub) {
                $cite = (string) ($pub['cite'] ?? $pub['title'] ?? '');
                if ($cite === '') {
                    continue;
                }
                $doi = trim((string) ($pub['doi'] ?? ''));
                $parts[] = '<li>' . esc_html($cite);
                if ($doi !== '') {
                    $parts[] = ' · <a href="' . esc_url('https://doi.org/' . ltrim($doi, '/')) . '" rel="noopener noreferrer">DOI</a>';
                }
                $parts[] = '</li>';
            }
            $parts[] = '</ul>';
        }
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Избранные публикации появятся после передачи списка.</p>';
    }
    $parts[] = '</section>';

    // Contacts
    $parts[] = '<section id="contacts" class="lab-contacts"><h2>Контакты</h2>';
    $parts[] = '<p>Адрес: 220084, г. Минск, ул. Ф. Скорины, 36</p>';
    if (!empty($lab['phone'])) {
        $parts[] = '<p>Тел. ' . esc_html((string) $lab['phone']) . '</p>';
    }
    if (!empty($lab['email'])) {
        $email = (string) $lab['email'];
        $parts[] = '<p>E-mail: <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></p>';
    }
    if ($head_id !== '') {
        $head = $resolve_person($head_id);
        if ($head) {
            $line = 'Заведующий: ' . (string) ($head['name'] ?? '');
            $bits = [];
            if (!empty($lab['phone'])) {
                $bits[] = (string) $lab['phone'];
            }
            if (!empty($lab['email'])) {
                $bits[] = (string) $lab['email'];
            }
            if ($bits) {
                $line .= ' · ' . implode(' · ', $bits);
            }
            $parts[] = '<p>' . esc_html($line) . '</p>';
        }
    }
    $parts[] = '</section>';

    return implode('', $parts);
}

function ichnm_import_labs(): void
{
    foreach (ichnm_migrated_copy()['labs'] ?? [] as $lab) {
        if (!is_array($lab)) {
            continue;
        }
        $slug = (string) ($lab['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        ichnm_upsert_post([
            'post_type' => 'department',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => (string) ($lab['title'] ?? $slug),
            'post_content' => ichnm_lab_pack_html($lab),
        ], '_ichnm_lab_slug', $slug);
    }
    ichnm_dedupe_department_posts();
}

/**
 * Keep one RU lab per slug; trash bare-slug EN/BE/ZH leftovers that break /labs/{slug}/.
 */
function ichnm_dedupe_department_posts(): void
{
    $slugs = [];
    foreach (ichnm_migrated_copy()['labs'] ?? [] as $lab) {
        if (is_array($lab) && !empty($lab['slug'])) {
            $slugs[(string) $lab['slug']] = (string) ($lab['title'] ?? $lab['slug']);
        }
    }
    if (!$slugs) {
        return;
    }

    $all = get_posts([
        'post_type' => 'department',
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => -1,
        'suppress_filters' => true,
    ]);

    foreach ($slugs as $slug => $ru_title) {
        $canonical = ichnm_find_by_slug('department', $slug);
        if (!$canonical instanceof WP_Post) {
            continue;
        }
        $canonical_id = (int) $canonical->ID;
        if (function_exists('pll_set_post_language')) {
            pll_set_post_language($canonical_id, 'ru');
        }
        wp_update_post([
            'ID' => $canonical_id,
            'post_name' => $slug,
            'post_title' => $ru_title,
        ]);
        update_post_meta($canonical_id, '_ichnm_lab_slug', $slug);

        foreach ($all as $post) {
            if (!$post instanceof WP_Post) {
                continue;
            }
            $id = (int) $post->ID;
            if ($id === $canonical_id) {
                continue;
            }
            $meta = (string) get_post_meta($id, '_ichnm_lab_slug', true);
            $name = (string) $post->post_name;
            $related = ($meta === $slug)
                || $name === $slug
                || preg_match('/^' . preg_quote($slug, '/') . '(-\d+|-(en|be|zh)(-\d+)?)$/', $name);
            if (!$related) {
                continue;
            }
            $lang = function_exists('pll_get_post_language') ? (string) pll_get_post_language($id) : 'ru';
            if ($lang === 'ru' || $lang === '') {
                wp_trash_post($id);
                continue;
            }
            // Old shared-slug shells (post_name === slug) break the singular query.
            if ($name === $slug || preg_match('/^' . preg_quote($slug, '/') . '-\d+$/', $name)) {
                wp_trash_post($id);
            }
        }
    }
}

function ichnm_import_people(): void
{
    $rows = [];
    foreach (ichnm_migrated_copy()['people'] ?? [] as $person) {
        if (is_array($person) && !empty($person['id'])) {
            $rows[(string) $person['id']] = $person;
        }
    }
    foreach (ichnm_migrated_copy()['leadership_people'] ?? [] as $person) {
        if (!is_array($person) || empty($person['id'])) {
            continue;
        }
        $id = (string) $person['id'];
        $rows[$id] = array_merge($rows[$id] ?? [], $person);
    }

    foreach ($rows as $person) {
        $id = (string) ($person['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $parts = [];
        if (!empty($person['role'])) {
            $parts[] = '<p><strong>' . esc_html((string) $person['role']) . '</strong></p>';
        }
        if (!empty($person['degree'])) {
            $parts[] = '<p>' . esc_html((string) $person['degree']) . '</p>';
        }
        $parts[] = ichnm_blocks_html(['paragraphs' => $person['bio'] ?? []]);
        $affiliations = $person['affiliations'] ?? [];
        if (is_array($affiliations) && $affiliations) {
            $aff_html = ichnm_person_affiliations_html($affiliations);
            if ($aff_html !== '') {
                $parts[] = $aff_html;
            }
        }
        $parts[] = ichnm_person_metrics_html($person);
        $contacts = [];
        if (!empty($person['phone'])) {
            $contacts[] = esc_html((string) $person['phone']);
        }
        if (!empty($person['email'])) {
            $email = (string) $person['email'];
            $contacts[] = '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        if ($contacts) {
            array_unshift($parts, '<p>' . implode(' · ', $contacts) . '</p>');
        }
        ichnm_upsert_post([
            'post_type' => 'person',
            'post_status' => 'publish',
            'post_name' => $id,
            'post_title' => (string) ($person['name'] ?? $id),
            'post_content' => implode('', $parts),
        ], '_ichnm_person_id', $id);
    }
}

/**
 * One archive card: title links to /conferences/{slug}/ (preview parity).
 *
 * @param array<string,mixed> $conf
 */
function ichnm_conference_archive_card_html(array $conf): string
{
    $slug = (string) ($conf['slug'] ?? '');
    $title = (string) ($conf['title'] ?? $slug);
    if ($slug === '' || $title === '') {
        return '';
    }
    $href = home_url('/conferences/' . rawurlencode($slug) . '/');
    $html = '<article class="ichnm-catalogue-card conf-card">';
    if (!empty($conf['series'])) {
        $html .= '<p class="ichnm-catalogue-meta">' . esc_html((string) $conf['series']) . '</p>';
    }
    $html .= '<h3><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></h3>';
    if (!empty($conf['when'])) {
        $html .= '<p>' . esc_html((string) $conf['when']) . '</p>';
    }
    $html .= '</article>';
    return $html;
}

function ichnm_fill_aist_page(): void
{
    $page = get_page_by_path('aist');
    if (!$page instanceof WP_Post) {
        return;
    }
    $copy = ichnm_migrated_copy();
    $hub = is_array($copy['aist_hub'] ?? null) ? $copy['aist_hub'] : [];
    $parts = [];
    foreach ($hub['lead'] ?? [] as $para) {
        $parts[] = '<p>' . esc_html((string) $para) . '</p>';
    }
    $parts[] = '<p>Регистрация участников — на <a href="http://aist.ichnm.by/">aist.ichnm.by</a>.</p>';
    if (!empty($hub['phone']) || !empty($hub['email'])) {
        $line = esc_html((string) ($hub['secretary_role'] ?? 'Секретарь'));
        if (!empty($hub['phone'])) {
            $line .= ' · ' . esc_html((string) $hub['phone']);
        }
        if (!empty($hub['email'])) {
            $email = (string) $hub['email'];
            $line .= ' · <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        $parts[] = '<p>' . $line . '</p>';
    }

    $conferences = $copy['conferences'] ?? [];
    if (is_array($conferences) && $conferences) {
        // Current cycle files stay on the hub; archive titles link to /conferences/{slug}/.
        $current = null;
        foreach ($conferences as $conf) {
            if (is_array($conf) && ($conf['slug'] ?? '') === 'aist-2025') {
                $current = $conf;
                break;
            }
        }
        if (is_array($current)) {
            $parts[] = '<h2>Материалы текущего цикла</h2>';
            $files = $current['files'] ?? [];
            if (is_array($files) && $files) {
                $parts[] = '<ul>';
                foreach ($files as $file) {
                    if (!is_array($file)) {
                        continue;
                    }
                    $name = (string) ($file['file'] ?? '');
                    $label = (string) ($file['label'] ?? $name);
                    if ($name === '') {
                        continue;
                    }
                    $href = ichnm_source_url('aist/' . $name);
                    $parts[] = '<li><a href="' . esc_url($href) . '">' . esc_html($label) . '</a></li>';
                }
                $parts[] = '</ul>';
            }
            $curr_slug = (string) ($current['slug'] ?? 'aist-2025');
            $parts[] = '<p><a href="' . esc_url(home_url('/conferences/' . rawurlencode($curr_slug) . '/')) . '">'
                . esc_html((string) ($current['title'] ?? $curr_slug)) . '</a> — страница цикла.</p>';
        }

        $aist_rows = [];
        $other_rows = [];
        foreach ($conferences as $conf) {
            if (!is_array($conf) || empty($conf['slug'])) {
                continue;
            }
            if (($conf['kind'] ?? '') === 'aist') {
                $aist_rows[] = $conf;
            } else {
                $other_rows[] = $conf;
            }
        }
        if ($aist_rows) {
            $parts[] = '<h2>Архив AIST</h2><div class="ichnm-card-grid">';
            foreach ($aist_rows as $conf) {
                $parts[] = ichnm_conference_archive_card_html($conf);
            }
            $parts[] = '</div>';
        }
        if ($other_rows) {
            $parts[] = '<h2>Другие конференции Института</h2><div class="ichnm-card-grid">';
            foreach ($other_rows as $conf) {
                $parts[] = ichnm_conference_archive_card_html($conf);
            }
            $parts[] = '</div>';
        }
    }

    $press = $hub['press'] ?? [];
    if (is_array($press) && $press) {
        $parts[] = '<h2>СМИ о AIST</h2><ul>';
        foreach ($press as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = (string) ($row['title'] ?? '');
            $href = (string) ($row['href'] ?? '');
            if ($title === '' || $href === '') {
                continue;
            }
            $parts[] = '<li><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></li>';
        }
        $parts[] = '</ul>';
    }

    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => implode('', $parts),
    ]);
}

/**
 * Inject laboratory packs under Структура (labs first, then admin / union / SMU).
 *
 * @param list<array<string,mixed>> $menu
 * @return list<array<string,mixed>>
 */
function ichnm_menu_with_labs(array $menu): array
{
    $lab_items = [];
    foreach (ichnm_migrated_copy()['labs'] ?? [] as $lab) {
        if (!is_array($lab) || empty($lab['slug'])) {
            continue;
        }
        $lab_items[] = [
            'id' => 'lab-' . $lab['slug'],
            'title' => (string) ($lab['title'] ?? $lab['slug']),
            'kind' => 'lab',
            'slug' => (string) $lab['slug'],
        ];
    }
    if (!$lab_items) {
        return $menu;
    }

    $inject = static function (array &$items) use (&$inject, $lab_items): bool {
        foreach ($items as &$item) {
            if (($item['id'] ?? '') === 'structure') {
                $item['children'] = array_merge($lab_items, $item['children'] ?? []);
                return true;
            }
            if (!empty($item['children']) && $inject($item['children'])) {
                return true;
            }
        }
        return false;
    };
    $inject($menu);
    return $menu;
}

function ichnm_enable_bvi_plugin(): void
{
    $option = get_option('bvi-option');
    if (!is_array($option)) {
        $option = [];
    }
    $option['bviActive'] = 'true';
    if (empty($option['bviLinkText'])) {
        $option['bviLinkText'] = 'Версия для слабовидящих';
    }
    if (empty($option['bviLang'])) {
        $option['bviLang'] = 'ru-RU';
    }
    update_option('bvi-option', $option);
}

function ichnm_import_conference_events(): void
{
    foreach (ichnm_migrated_copy()['conferences'] ?? [] as $conf) {
        if (!is_array($conf)) {
            continue;
        }
        $slug = (string) ($conf['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $html = '';
        if (!empty($conf['when'])) {
            $html .= '<p><strong>' . esc_html((string) $conf['when']) . '</strong></p>';
        }
        $html .= ichnm_blocks_html(['paragraphs' => $conf['paragraphs'] ?? []]);
        $files = $conf['files'] ?? [];
        if (is_array($files) && $files) {
            $html .= '<ul>';
            foreach ($files as $file) {
                if (!is_array($file) || empty($file['file'])) {
                    continue;
                }
                $href = ichnm_source_url('aist/' . ltrim((string) $file['file'], '/'));
                $label = (string) ($file['label'] ?? $file['file']);
                $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($label) . '</a></li>';
            }
            $html .= '</ul>';
        }
        $html .= '<p>Регистрация серии — на <a href="http://aist.ichnm.by/">aist.ichnm.by</a>. Раздел на сайте: <a href="' . esc_url(home_url('/aist/')) . '">AIST</a>.</p>';
        $year = (int) ($conf['year'] ?? 2024);
        $date = (string) $year . '-10-01 10:00:00';
        ichnm_upsert_post([
            'post_type' => 'event',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => (string) ($conf['title'] ?? $slug),
            'post_content' => $html,
            'post_date' => $date,
            'post_date_gmt' => get_gmt_from_date($date),
        ], '_ichnm_source_slug', 'conf-' . $slug);
    }
}

function ichnm_ensure_events_hub_page(): void
{
    $intro = ichnm_blocks_html(is_array(ichnm_migrated_copy()['pages']['events'] ?? null) ? ichnm_migrated_copy()['pages']['events'] : []);
    if ($intro === '') {
        $intro = '<p>Мероприятия Института. Ближайший цикл — конференция AIST.</p>';
    }
    $body = $intro . '<!-- ichnm:events-hub -->';
    $existing = get_page_by_path('events');
    if ($existing instanceof WP_Post) {
        wp_update_post([
            'ID' => (int) $existing->ID,
            'post_title' => 'Мероприятия',
            'post_content' => $body,
        ]);
        return;
    }
    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => 'events',
        'post_title' => 'Мероприятия',
        'post_content' => $body,
    ]);
}

function ichnm_fill_about_pages(): void
{
    $copy = ichnm_migrated_copy();

    $overview = get_page_by_path('about-overview');
    if ($overview instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['about-overview'] ?? null) ? $copy['pages']['about-overview'] : []);
        $photos = $copy['about_photos'] ?? [];
        if (is_array($photos) && $photos) {
            $html .= '<h2>Фотоархив</h2><div class="ichnm-about-gallery">';
            foreach ($photos as $photo) {
                if (!is_array($photo)) {
                    continue;
                }
                $src = (string) ($photo['src'] ?? '');
                $src = preg_replace('#^media/#', '', $src) ?? $src;
                if ($src === '') {
                    continue;
                }
                $caption = (string) ($photo['caption'] ?? '');
                $html .= '<figure class="ichnm-about-figure">';
                $html .= '<img src="' . esc_url(ichnm_source_url($src)) . '" alt="' . esc_attr($caption) . '" loading="lazy">';
                if ($caption !== '') {
                    $html .= '<figcaption>' . esc_html($caption) . '</figcaption>';
                }
                $html .= '</figure>';
            }
            $html .= '</div>';
        }
        wp_update_post(['ID' => (int) $overview->ID, 'post_content' => $html]);
    }

    $about = get_page_by_path('about');
    if ($about instanceof WP_Post) {
        $block = is_array($copy['pages']['about'] ?? null) ? $copy['pages']['about'] : [];
        $html = ichnm_blocks_html($block);
        $list = $block['list'] ?? [];
        if (is_array($list) && $list) {
            // Already rendered by blocks_html if list key present — avoid double.
            // blocks_html already includes list as <ul>. Add awards heading only if missing.
            if (!str_contains($html, '<ul>')) {
                $html .= '<h2>Достижения</h2>' . ichnm_blocks_html(['list' => $list]);
            } else {
                $html = preg_replace('/<ul>/', '<h2>Достижения</h2><ul>', $html, 1) ?? $html;
            }
        }
        $overview_page = get_page_by_path('about-overview');
        if ($overview_page instanceof WP_Post) {
            $html .= '<p><a class="ichnm-pill" href="' . esc_url(get_permalink($overview_page)) . '">Сведения об институте</a></p>';
        }
        wp_update_post(['ID' => (int) $about->ID, 'post_content' => $html]);
    }
}

function ichnm_ensure_utility_pages(): void
{
    $pages = [
        'search' => [
            'title' => 'Поиск',
            'body' => '<p>Поиск по персоналиям, подразделениям, приборам и разработкам.</p><!-- ichnm:search-hub -->',
        ],
        'sitemap' => [
            'title' => 'Карта сайта',
            'body' => '<p>Все публичные разделы одним списком, как на портале НАН.</p><!-- ichnm:sitemap-hub -->',
        ],
        'cookies' => [
            'title' => 'Политика cookie',
            'body' => '<p>Сайт использует технические cookie, необходимые для работы сессии WordPress и режима для слабовидящих. Маркетинговых трекеров в v1 нет.</p><p>Продолжая пользоваться сайтом, вы соглашаетесь с использованием технических cookie. Подробности по персональным данным — на отдельной странице.</p>',
        ],
        'personal-data' => [
            'title' => 'Персональные данные',
            'body' => '<p>Обработка персональных данных посетителей и корреспондентов ведётся в соответствии с законодательством Республики Беларусь.</p><p>Запросы, связанные с персональными данными, направляйте через <a href="' . esc_url(home_url('/e-appeals/')) . '">электронные обращения</a> или форму <a href="' . esc_url(home_url('/feedback/')) . '">обратной связи</a>.</p>',
        ],
    ];
    foreach ($pages as $slug => $row) {
        $existing = get_page_by_path($slug);
        if ($existing instanceof WP_Post) {
            wp_update_post([
                'ID' => (int) $existing->ID,
                'post_title' => $row['title'],
                'post_content' => $row['body'],
            ]);
            continue;
        }
        wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => $row['title'],
            'post_content' => $row['body'],
        ]);
    }
}

/**
 * @return list<array{type:string,title:string,href:string,meta:string}>
 */
function ichnm_search_catalog(): array
{
    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
    if ($lang === '') {
        $lang = 'ru';
    }
    static $cache = [];
    if (isset($cache[$lang]) && is_array($cache[$lang])) {
        return $cache[$lang];
    }
    $rows = [];

    $people = get_posts([
        'post_type' => 'person',
        'posts_per_page' => 200,
        'post_status' => 'publish',
        'suppress_filters' => true,
    ]);
    foreach ($people as $post) {
        if (function_exists('pll_get_post_language')) {
            $post_lang = (string) pll_get_post_language((int) $post->ID);
            if ($post_lang && $post_lang !== $lang && !($lang === 'ru' && $post_lang === '')) {
                continue;
            }
        }
        $rows[] = [
            'type' => 'person',
            'title' => get_the_title($post),
            'href' => (string) get_permalink($post),
            'meta' => wp_strip_all_tags((string) $post->post_content),
        ];
    }

    $seen_labs = [];
    $labs = get_posts([
        'post_type' => 'department',
        'posts_per_page' => 100,
        'post_status' => 'publish',
        'suppress_filters' => true,
    ]);
    foreach ($labs as $post) {
        $slug = (string) get_post_meta((int) $post->ID, '_ichnm_lab_slug', true);
        if ($slug === '') {
            $slug = (string) $post->post_name;
        }
        $slug = preg_replace('/-(en|be|zh)$/', '', $slug) ?: $slug;
        if (isset($seen_labs[$slug])) {
            continue;
        }
        $target = $post;
        if (function_exists('pll_get_post') && function_exists('pll_get_post_language')) {
            $ru = ichnm_find_by_slug('department', $slug);
            if ($ru instanceof WP_Post) {
                $translated = (int) pll_get_post((int) $ru->ID, $lang);
                if ($translated > 0) {
                    $translated_post = get_post($translated);
                    if ($translated_post instanceof WP_Post && $translated_post->post_status === 'publish') {
                        $target = $translated_post;
                    } else {
                        $target = $ru;
                    }
                } else {
                    $target = $ru;
                }
            }
        }
        $seen_labs[$slug] = true;
        $rows[] = [
            'type' => 'unit',
            'title' => get_the_title($target),
            'href' => (string) get_permalink($target),
            'meta' => 'Лаборатория',
        ];
    }
    foreach (['hr', 'labor-protection', 'engineering', 'accounting', 'union', 'young-scientists'] as $slug) {
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post) {
            $rows[] = [
                'type' => 'unit',
                'title' => get_the_title($page),
                'href' => (string) get_permalink($page),
                'meta' => 'Подразделение',
            ];
        }
    }
    $copy = ichnm_migrated_copy();
    foreach ($copy['facilities_items'] ?? [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $f_slug = sanitize_title((string) ($item['slug'] ?? ''));
        $rows[] = [
            'type' => 'facility',
            'title' => (string) ($item['title'] ?? ''),
            'href' => $f_slug !== ''
                ? ichnm_catalogue_detail_permalink('facilities', $f_slug)
                : home_url('/facilities/'),
            'meta' => (string) ($item['lead'] ?? $item['spec'] ?? 'Прибор'),
        ];
    }
    foreach ($copy['developments_items'] ?? [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $d_slug = sanitize_title((string) ($item['slug'] ?? ''));
        $rows[] = [
            'type' => 'development',
            'title' => (string) ($item['title'] ?? ''),
            'href' => $d_slug !== ''
                ? ichnm_catalogue_detail_permalink('developments', $d_slug)
                : home_url('/developments/'),
            'meta' => (string) ($item['lead'] ?? $item['product'] ?? 'Разработка'),
        ];
    }
    $cache[$lang] = $rows;
    return $rows;
}

/**
 * @return list<array{type:string,title:string,href:string,meta:string}>
 */
function ichnm_search_query(string $q, int $limit = 40): array
{
    $q = trim(mb_strtolower($q));
    if (mb_strlen($q) < 2) {
        return [];
    }
    $hits = [];
    foreach (ichnm_search_catalog() as $row) {
        $hay = mb_strtolower($row['title'] . ' ' . $row['meta']);
        if (mb_strpos($hay, $q) === false) {
            continue;
        }
        $hits[] = $row;
        if (count($hits) >= $limit) {
            break;
        }
    }
    return $hits;
}

function ichnm_sitemap_html(): string
{
    $tree = function_exists('ichnm_menu_with_labs')
        ? ichnm_menu_with_labs(ichnm_site_model()['menu'] ?? [])
        : (ichnm_site_model()['menu'] ?? []);
    $hub = function_exists('ichnm_hub_strings') ? ichnm_hub_strings() : [];
    $page_for = static function (string $slug): ?WP_Post {
        if (function_exists('ichnm_translated_page')) {
            $page = ichnm_translated_page($slug);
            if ($page instanceof WP_Post) {
                return $page;
            }
        }
        return get_page_by_path($slug);
    };

    $render = static function (array $items) use (&$render, $page_for): string {
        if (!$items) {
            return '';
        }
        $html = '<ul>';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            $title = (string) ($item['title'] ?? $id);
            $kind = (string) ($item['kind'] ?? 'vitrine');
            if ($kind === 'folder') {
                $html .= '<li><span>' . esc_html($title) . '</span>';
            } elseif ($kind === 'lab') {
                $slug = (string) ($item['slug'] ?? '');
                $html .= '<li><a href="' . esc_url(home_url('/labs/' . rawurlencode($slug) . '/')) . '">' . esc_html($title) . '</a>';
            } else {
                $page = $page_for($id);
                $href = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . rawurlencode($id) . '/');
                if ($page instanceof WP_Post && $page->post_title !== '') {
                    $title = (string) $page->post_title;
                }
                $html .= '<li><a href="' . esc_url((string) $href) . '">' . esc_html($title) . '</a>';
            }
            if (!empty($item['children'])) {
                $html .= $render($item['children']);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    };

    $aria = (string) ($hub['sitemap_aria'] ?? 'Карта сайта');
    $html = '<nav class="ichnm-sitemap" aria-label="' . esc_attr($aria) . '">' . $render($tree) . '</nav>';
    $html .= '<h2>' . esc_html((string) ($hub['leadership'] ?? 'Руководство')) . '</h2><ul class="ichnm-hub-links">';
    foreach (ichnm_migrated_copy()['leadership_order'] ?? [] as $id) {
        $person = ichnm_find_by_slug('person', (string) $id);
        if (!$person instanceof WP_Post) {
            continue;
        }
        $html .= '<li><a href="' . esc_url(get_permalink($person)) . '">' . esc_html(get_the_title($person)) . '</a></li>';
    }
    $html .= '</ul>';
    $search = $page_for('search');
    if ($search instanceof WP_Post) {
        $html .= '<p><a href="' . esc_url(get_permalink($search)) . '">' . esc_html((string) ($hub['open_search'] ?? 'Открыть поиск')) . '</a></p>';
    }
    return $html;
}

function ichnm_sync_content(bool $force = false): void
{
    $current = (int) get_option('ichnm_content_seed_version', 0);
    if (!$force && $current >= ICHNM_CONTENT_SEED_VERSION) {
        return;
    }
    ichnm_configure_site_contour();
    ichnm_enable_bvi_plugin();
    ichnm_ensure_pages();
    ichnm_import_people();
    ichnm_import_council_people();
    ichnm_import_admin_unit_people();
    ichnm_import_labs();
    ichnm_import_news();
    ichnm_import_media_about();
    ichnm_import_next_event();
    ichnm_import_conference_events();
    ichnm_import_publications();
    ichnm_sync_page_bodies();
    ichnm_fill_aist_page();
    if (function_exists('ichnm_setup_polylang_contour')) {
        ichnm_setup_polylang_contour();
    }
    delete_option('ichnm_menu_seeded');
    ichnm_sync_nav_menu(true);
    update_option('ichnm_content_seed_version', ICHNM_CONTENT_SEED_VERSION);
    flush_rewrite_rules(false);
}
