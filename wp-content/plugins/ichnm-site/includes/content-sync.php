<?php
/**
 * Idempotent import of honest migrated_copy into pages and feed CPTs.
 */

if (!defined('ABSPATH')) {
    exit;
}

const ICHNM_CONTENT_SEED_VERSION = 37;

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
        'direction', 'facility', 'development' => '_ichnm_catalogue_key',
        default => '',
    };
    $meta_value = $slug;
    if (in_array($post_type, ['direction', 'facility', 'development'], true)) {
        $meta_value = $post_type . ':' . $slug;
    }

    $candidates = [];
    if ($meta_key !== '') {
        $candidates = get_posts([
            'post_type' => $post_type,
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => 50,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
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
    ichnm_import_catalogue_cpts();
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
    $parts = [];
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
    foreach (ichnm_migrated_copy()['union_people'] ?? [] as $person) {
        if (!is_array($person) || empty($person['id'])) {
            continue;
        }
        $id = (string) $person['id'];
        $rows[$id] = array_merge($rows[$id] ?? [], $person, ['_unit_title' => 'Профсоюз']);
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
    // Cards only — no lead prose before the grid (ticket 43).
    $people = ichnm_people_index();
    $order = $copy['leadership_order'] ?? array_keys($people);
    $cards = ['<div class="people-list ichnm-card-grid ichnm-leadership-grid">'];
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
        'post_content' => implode('', $cards),
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
 * All catalogue rows for one hub parent (CPT first, then migrated-copy fallback).
 *
 * @return list<array>
 */
function ichnm_catalogue_rows_for_parent(string $parent): array
{
    if (function_exists('ichnm_catalogue_cpt_rows')) {
        $cpt = ichnm_catalogue_cpt_rows($parent);
        if ($cpt) {
            return $cpt;
        }
    }
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
 * Seed-only rows (always from migrated_copy) — used by CPT import.
 *
 * @return list<array>
 */
function ichnm_catalogue_seed_rows_for_parent(string $parent): array
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
 * Prefers CPT singles; falls back to child pages / constructed URL.
 */
function ichnm_catalogue_detail_permalink(string $parent, string $slug): string
{
    $slug = sanitize_title($slug);
    if ($slug === '' || !isset(ichnm_catalogue_parent_titles()[$parent])) {
        return '';
    }
    $type = function_exists('ichnm_catalogue_parent_type') ? ichnm_catalogue_parent_type($parent) : '';
    if ($type !== '') {
        $post = ichnm_find_by_slug($type, $slug);
        if ($post instanceof WP_Post) {
            return (string) get_permalink($post);
        }
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
    $is_developments = ($parent === 'developments');
    $detail_text = trim((string) ($item['spec'] ?? $item['product'] ?? ''));
    $contacts = trim((string) ($item['contacts'] ?? ''));
    if ($contacts === '') {
        $contacts = ichnm_catalogue_meta($item);
    }
    if ($contacts === '') {
        $contacts = 'ichnm@ichnm.by';
    }

    $parts = [];
    // Narrow photo field (~lab hero width) — ticket 77.
    $parts[] = '<div class="ichnm-detail-hero ichnm-detail-hero-narrow">'
        . ichnm_photo_slot_html($title) . '</div>';

    if ($is_developments) {
        $parts[] = '<div class="ichnm-detail-layout">';
        $parts[] = '<div class="ichnm-detail-main">';
        $parts[] = '<section class="ichnm-detail-section"><h2>Описание</h2>';
        if ($lead !== '') {
            $parts[] = '<p>' . esc_html($lead) . '</p>';
        } else {
            $parts[] = '<p>' . esc_html($title) . '</p>';
        }
        $parts[] = '</section>';
        $parts[] = '<section class="ichnm-detail-section"><h2>Технические характеристики</h2>';
        if ($detail_text !== '') {
            $parts[] = '<p>' . esc_html($detail_text) . '</p>';
        }
        $parts[] = '</section>';
        $parts[] = '<section class="ichnm-detail-section"><h2>Контакты</h2>';
        $parts[] = '<p>' . esc_html($contacts) . '</p>';
        $staff_id = (string) ($item['staff_id'] ?? $item['head_id'] ?? '');
        if ($staff_id !== '') {
            $label = ichnm_person_contact_line($staff_id);
            if ($label !== '') {
                $parts[] = '<p>Закреплено: <a href="' . esc_url(ichnm_person_permalink($staff_id)) . '">'
                    . esc_html($label) . '</a></p>';
            }
        }
        $parts[] = '</section></div>';
        $parts[] = '<aside class="ichnm-detail-sidebar"><h2>Направление работы</h2>';
        $direction_slug = (string) ($item['direction_slug'] ?? $item['direction_id'] ?? '');
        $direction_title = (string) ($item['direction_title'] ?? $item['direction'] ?? '');
        if ($direction_slug !== '') {
            $dir_href = ichnm_catalogue_detail_permalink('science', $direction_slug);
            $label = $direction_title !== '' ? $direction_title : $direction_slug;
            $parts[] = '<p><a href="' . esc_url($dir_href) . '">' . esc_html($label) . '</a></p>';
        } elseif ($direction_title !== '') {
            $science = get_page_by_path('science');
            $href = $science instanceof WP_Post ? (string) get_permalink($science) : home_url('/science/');
            $parts[] = '<p><a href="' . esc_url($href) . '">' . esc_html($direction_title) . '</a></p>';
        } else {
            $lab_id = (string) ($item['lab_id'] ?? '');
            $labs = ichnm_labs_by_id();
            if ($lab_id !== '' && isset($labs[$lab_id])) {
                $lab = $labs[$lab_id];
                $lab_slug = (string) ($lab['slug'] ?? '');
                $lab_title = trim((string) ($item['lab_title'] ?? $lab['title'] ?? $lab_slug));
                if ($lab_slug !== '') {
                    $parts[] = '<p>Лаборатория: <a href="'
                        . esc_url(home_url('/labs/' . rawurlencode($lab_slug) . '/')) . '">'
                        . esc_html($lab_title) . '</a></p>';
                }
            }
            $science = get_page_by_path('science');
            if ($science instanceof WP_Post) {
                $parts[] = '<p><a href="' . esc_url((string) get_permalink($science))
                    . '">Направления работы</a></p>';
            }
        }
        $parts[] = '</aside></div>';
        return implode('', $parts);
    }

    if ($is_facilities) {
        if ($lead !== '') {
            $parts[] = '<p>' . esc_html($lead) . '</p>';
        }
        $parts[] = '<h2>Сведения об инструменте</h2>';
        if ($detail_text !== '') {
            $parts[] = '<p>' . esc_html($detail_text) . '</p>';
        }
        $parts[] = '<h2>Контакты</h2><p>' . esc_html($contacts) . '</p>';
        $parts[] = '<h2>Файлы для сотрудников</h2>';
        $parts[] = '<ul class="file-shelf" aria-label="Слоты для документов">'
            . '<li class="file-slot"><span>Инструкция / паспорт прибора.pdf</span>'
            . '<small>Файл не загружен</small></li></ul>';
    } else {
        if ($lead !== '') {
            $parts[] = '<p>' . esc_html($lead) . '</p>';
        }
        $parts[] = '<h2>Описание и контакты</h2>';
        $parts[] = '<p>' . esc_html($contacts) . '</p>';
    }

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

    if (!$is_facilities) {
        $parts[] = '<h2>' . esc_html('Продукт / результат') . '</h2>';
        if ($detail_text !== '') {
            $parts[] = '<p>' . esc_html($detail_text) . '</p>';
        }
    }

    // Related developments on direction pages (ticket 77).
    if ($parent === 'science') {
        $related = [];
        $dir_slug = (string) ($item['slug'] ?? '');
        $dev_source = !empty($GLOBALS['ichnm_catalogue_sync_lock'])
            && function_exists('ichnm_catalogue_seed_rows_for_parent')
            ? ichnm_catalogue_seed_rows_for_parent('developments')
            : ichnm_catalogue_rows_for_parent('developments');
        foreach ($dev_source as $dev) {
            if (!is_array($dev)) {
                continue;
            }
            $d_dir = (string) ($dev['direction_slug'] ?? $dev['direction_id'] ?? '');
            $same_lab = (string) ($dev['lab_id'] ?? '') !== ''
                && (string) ($dev['lab_id'] ?? '') === (string) ($item['lab_id'] ?? '');
            if ($d_dir !== '' && $d_dir === $dir_slug) {
                $related[] = $dev;
            } elseif ($d_dir === '' && $same_lab) {
                $related[] = $dev;
            }
        }
        if ($related) {
            $parts[] = '<h2>Связанные разработки</h2><ul class="ichnm-hub-links">';
            foreach ($related as $dev) {
                $d_slug = (string) ($dev['slug'] ?? '');
                $d_title = (string) ($dev['title'] ?? $d_slug);
                if ($d_slug === '') {
                    continue;
                }
                $parts[] = '<li><a href="'
                    . esc_url(ichnm_catalogue_detail_permalink('developments', $d_slug)) . '">'
                    . esc_html($d_title) . '</a></li>';
            }
            $parts[] = '</ul>';
        }
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
 * Kept as a fallback until CPT singles exist; CPT import trashes these afterward.
 */
function ichnm_import_catalogue_detail_pages(): void
{
    // Prefer CPT singles (ticket 83). Skip creating new child pages when CPT types exist.
    if (post_type_exists('direction') && post_type_exists('facility') && post_type_exists('development')) {
        return;
    }
    foreach (array_keys(ichnm_catalogue_parent_titles()) as $parent) {
        $parent_page = get_page_by_path($parent);
        if (!$parent_page instanceof WP_Post) {
            continue;
        }
        $parent_id = (int) $parent_page->ID;
        foreach (ichnm_catalogue_seed_rows_for_parent($parent) as $item) {
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
 * Import lab catalogue CPTs from migrated_copy (stable slugs). Ticket 83.
 * Does not wipe editor-owned title/body/meta.
 */
function ichnm_import_catalogue_cpts(): void
{
    if (!function_exists('ichnm_catalogue_parent_type')) {
        return;
    }
    $GLOBALS['ichnm_catalogue_sync_lock'] = true;

    $parents = [
        'science' => 'direction',
        'facilities' => 'facility',
        'developments' => 'development',
    ];
    foreach ($parents as $parent => $type) {
        if (!post_type_exists($type)) {
            continue;
        }
        foreach (ichnm_catalogue_seed_rows_for_parent($parent) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $slug = sanitize_title((string) ($item['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $title = (string) ($item['title'] ?? $slug);
            $key = $type . ':' . $slug;
            $lab_slug = trim((string) ($item['lab_slug'] ?? ''));
            if ($lab_slug === '' && !empty($item['lab_id'])) {
                $lab_id = (string) $item['lab_id'];
                $lab_slug = str_starts_with($lab_id, 'lab-') ? substr($lab_id, 4) : $lab_id;
            }
            $row = $item;
            $row['lab_slug'] = $lab_slug;

            $existing = get_posts([
                'post_type' => $type,
                'post_status' => 'any',
                'posts_per_page' => 1,
                'meta_key' => '_ichnm_catalogue_key',
                'meta_value' => $key,
                'suppress_filters' => true,
            ]);
            $existing_id = $existing && $existing[0] instanceof WP_Post ? (int) $existing[0]->ID : 0;
            $owned = $existing_id > 0 && function_exists('ichnm_catalogue_is_editor_owned')
                && ichnm_catalogue_is_editor_owned($existing_id);

            if ($owned) {
                ichnm_seed_catalogue_meta_if_empty($existing_id, $row);
                continue;
            }

            $content = ichnm_catalogue_detail_html($parent, $row);
            $lead = trim((string) ($row['lead'] ?? ''));
            $post_id = ichnm_upsert_post([
                'post_type' => $type,
                'post_status' => 'publish',
                'post_name' => $slug,
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $lead,
            ], '_ichnm_catalogue_key', $key);
            if ($post_id > 0 && function_exists('ichnm_seed_catalogue_meta_if_empty')) {
                ichnm_seed_catalogue_meta_if_empty($post_id, $row);
            }
        }
    }

    ichnm_trash_catalogue_detail_child_pages();
    unset($GLOBALS['ichnm_catalogue_sync_lock']);
}

/**
 * Remove legacy child-page detail duplicates once CPT singles own the URLs.
 */
function ichnm_trash_catalogue_detail_child_pages(): void
{
    foreach (array_keys(ichnm_catalogue_parent_titles()) as $parent) {
        $parent_page = get_page_by_path($parent);
        if (!$parent_page instanceof WP_Post) {
            continue;
        }
        $children = get_posts([
            'post_type' => 'page',
            'post_parent' => (int) $parent_page->ID,
            'posts_per_page' => 200,
            'post_status' => 'any',
            'suppress_filters' => true,
        ]);
        foreach ($children as $child) {
            if (!$child instanceof WP_Post) {
                continue;
            }
            $key = (string) get_post_meta((int) $child->ID, '_ichnm_catalogue_key', true);
            if ($key === '') {
                // Also trash by matching known seed slugs under this parent.
                $slug = (string) $child->post_name;
                $type = function_exists('ichnm_catalogue_parent_type')
                    ? ichnm_catalogue_parent_type($parent)
                    : '';
                if ($type === '' || !ichnm_find_by_slug($type, $slug)) {
                    continue;
                }
            }
            wp_trash_post((int) $child->ID);
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
    $accents = ichnm_lab_accent_map();
    $parts = ['<h2>' . esc_html($title) . '</h2>', '<div class="ichnm-card-grid ichnm-catalogue-grid cover-grid is-3">'];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = sanitize_title((string) ($item['slug'] ?? ''));
        $card_title = (string) ($item['title'] ?? '');
        $detail_href = ($parent !== '' && $slug !== '')
            ? ichnm_catalogue_detail_permalink($parent, $slug)
            : '';
        $lab_id = (string) ($item[$lab_key] ?? '');
        $lab = ($lab_id !== '' && isset($labs[$lab_id])) ? $labs[$lab_id] : null;
        $lab_slug = is_array($lab) ? (string) ($lab['slug'] ?? '') : (string) ($item['lab_slug'] ?? '');
        $accent = $lab_slug !== '' ? ($accents[$lab_slug] ?? '') : '';
        $style = $accent !== '' ? ' style="--lab-accent:' . esc_attr($accent) . '"' : '';
        $data_lab = $lab_slug !== '' ? ' data-lab-slug="' . esc_attr($lab_slug) . '"' : '';
        // Preview cover-card rhythm: photo-slot + title/lead/meta (ticket 26).
        $parts[] = '<article class="ichnm-catalogue-card cover-card"' . $data_lab . $style . '>';
        $parts[] = ichnm_photo_slot_html($card_title !== '' ? $card_title : $slug);
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
        $lab_title = (string) ($item['lab_title'] ?? '');
        if ($lab !== null) {
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
            $parts[] = '<p class="ichnm-catalogue-meta cover-meta">' . implode(' · ', $meta_bits) . '</p>';
        }
        $parts[] = '</article>';
    }
    $parts[] = '</div>';
    return implode('', $parts);
}

function ichnm_fill_catalogue_pages(): void
{
    $science = get_page_by_path('science');
    if ($science instanceof WP_Post) {
        $rows = ichnm_catalogue_rows_for_parent('science');
        $institute = [];
        $labs = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $lab = trim((string) ($row['lab_slug'] ?? $row['lab_id'] ?? ''));
            if ($lab === '') {
                $institute[] = $row;
            } else {
                $labs[] = $row;
            }
        }
        $html = ichnm_catalogue_cards_html('Общеинститутские направления', $institute, 'science');
        $html .= ichnm_catalogue_cards_html('Направления лабораторий', $labs, 'science');
        wp_update_post(['ID' => (int) $science->ID, 'post_content' => $html]);
    }

    $developments = get_page_by_path('developments');
    if ($developments instanceof WP_Post) {
        $rows = ichnm_catalogue_rows_for_parent('developments');
        $institute = [];
        $labs = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $lab = trim((string) ($row['lab_slug'] ?? $row['lab_id'] ?? ''));
            if ($lab === '') {
                $institute[] = $row;
            } else {
                $labs[] = $row;
            }
        }
        $html = '';
        if ($institute) {
            $html .= ichnm_catalogue_cards_html('Общеинститутские разработки', $institute, 'developments');
        }
        $html .= ichnm_catalogue_cards_html('Разработки лабораторий', $labs, 'developments');
        wp_update_post(['ID' => (int) $developments->ID, 'post_content' => $html]);
    }

    $facilities = get_page_by_path('facilities');
    if ($facilities instanceof WP_Post) {
        // Tiles only — no lead prose before the grid (ticket 49).
        $rows = ichnm_catalogue_rows_for_parent('facilities');
        $institute = [];
        $labs = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $lab = trim((string) ($row['lab_slug'] ?? $row['lab_id'] ?? ''));
            if ($lab === '') {
                $institute[] = $row;
            } else {
                $labs[] = $row;
            }
        }
        $html = '';
        if ($institute) {
            $html .= ichnm_catalogue_cards_html('Общеинститутское оснащение', $institute, 'facilities');
        }
        $html .= ichnm_catalogue_cards_html('Оборудование лабораторий', $labs, 'facilities');
        wp_update_post(['ID' => (int) $facilities->ID, 'post_content' => $html]);
    }

    $structure = get_page_by_path('structure');
    if ($structure instanceof WP_Post) {
        wp_update_post(['ID' => (int) $structure->ID, 'post_content' => ichnm_structure_hub_html(ichnm_migrated_copy())]);
    }
}

/**
 * Structure hub: tiles only (labs → admin → union/SMU). Tickets 42 / 55 / 56 / 57.
 *
 * @param array<string,mixed> $copy
 */
function ichnm_structure_hub_html(array $copy): string
{
    $people = ichnm_people_index();
    $thin_slugs = ['nano', 'films', 'lcd'];
    $thin_title = 'Отдел физико-химии тонкоплёночных материалов';
    $accents = ichnm_lab_accent_map();

    $labs = [];
    foreach ($copy['labs'] ?? [] as $lab) {
        if (is_array($lab) && !empty($lab['slug'])) {
            $labs[(string) $lab['slug']] = $lab;
        }
    }

    $lab_tile = static function (array $lab) use ($people, $accents): string {
        $slug = (string) ($lab['slug'] ?? '');
        $href = home_url('/labs/' . rawurlencode($slug) . '/');
        $title = (string) ($lab['title'] ?? $slug);
        $head_name = '';
        $head_id = (string) ($lab['head_id'] ?? '');
        if ($head_id !== '' && isset($people[$head_id])) {
            $head_name = trim((string) ($people[$head_id]['name'] ?? ''));
        }
        $accent = $accents[$slug] ?? '';
        $style = $accent !== '' ? ' style="--lab-accent:' . esc_attr($accent) . '"' : '';
        $html = '<li class="ichnm-lab-tile" data-lab-slug="' . esc_attr($slug) . '"' . $style . '>';
        $html .= '<a href="' . esc_url($href) . '">' . esc_html($title);
        if ($head_name !== '') {
            $html .= '<p class="ichnm-structure-head"><span class="ichnm-structure-role">заведующий</span> '
                . esc_html($head_name) . '</p>';
        }
        $html .= '</a></li>';
        return $html;
    };

    $html = '<h2>Лаборатории</h2>';
    $dept = get_page_by_path('thin-film-department');
    $dept_href = $dept instanceof WP_Post
        ? (string) get_permalink($dept)
        : home_url('/thin-film-department/');
    $html .= '<div class="ichnm-thin-film-group">';
    $html .= '<p class="ichnm-thin-film-label"><a href="' . esc_url($dept_href) . '">'
        . esc_html($thin_title) . '</a></p>';
    $html .= '<ul class="lab-grid ichnm-structure-grid">';
    foreach ($thin_slugs as $slug) {
        if (isset($labs[$slug])) {
            $html .= $lab_tile($labs[$slug]);
        }
    }
    $html .= '</ul></div>';

    // Buffer page body for thin-film department (ticket 71).
    if (!$dept instanceof WP_Post) {
        $dept_id = (int) wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'thin-film-department',
            'post_title' => $thin_title,
            'post_content' => '',
        ]);
        $dept = $dept_id > 0 ? get_post($dept_id) : null;
        $dept_href = $dept instanceof WP_Post
            ? (string) get_permalink($dept)
            : home_url('/thin-film-department/');
    }
    if ($dept instanceof WP_Post) {
        $dept_html = '<p>' . esc_html($thin_title) . '</p>';
        $dept_html .= '<h2>Лаборатории отдела</h2><ul class="lab-grid ichnm-structure-grid">';
        foreach ($thin_slugs as $slug) {
            if (isset($labs[$slug])) {
                $dept_html .= $lab_tile($labs[$slug]);
            }
        }
        $dept_html .= '</ul>';
        wp_update_post(['ID' => (int) $dept->ID, 'post_content' => $dept_html]);
    }

    $other = [];
    foreach ($labs as $slug => $lab) {
        if (!in_array($slug, $thin_slugs, true)) {
            $other[] = $lab;
        }
    }
    if ($other) {
        $html .= '<ul class="lab-grid ichnm-structure-grid">';
        foreach ($other as $lab) {
            $html .= $lab_tile($lab);
        }
        $html .= '</ul>';
    }

    $html .= '<h2>Административные подразделения</h2><ul class="dir-grid ichnm-structure-grid">';
    foreach ($copy['admin_units'] ?? [] as $unit) {
        if (!is_array($unit) || empty($unit['id'])) {
            continue;
        }
        $id = (string) $unit['id'];
        $href = home_url('/' . rawurlencode($id) . '/');
        $title = (string) ($unit['title'] ?? $id);
        $head_name = '';
        $unit_people = is_array($unit['people'] ?? null) ? $unit['people'] : [];
        if ($unit_people && is_array($unit_people[0] ?? null)) {
            $head_name = trim((string) ($unit_people[0]['name'] ?? ''));
            $role = trim((string) ($unit_people[0]['role'] ?? ''));
            if ($head_name === '' || str_starts_with($head_name, 'Фамилия')) {
                $head_name = $role;
            }
        }
        $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($title);
        if ($head_name !== '' && !str_starts_with($head_name, 'Фамилия')) {
            $html .= '<p class="ichnm-structure-head">' . esc_html($head_name) . '</p>';
        }
        $html .= '</a></li>';
    }
    $html .= '</ul>';

    $html .= '<h2>Общественные объединения</h2><ul class="dir-grid ichnm-structure-grid">';
    $community = [
        'union' => ['title' => 'Профсоюз', 'chair' => 'Южик Любовь Ивановна'],
        'young-scientists' => ['title' => 'Совет молодых учёных', 'chair' => 'Фамилия Имя Отчество'],
    ];
    foreach ($community as $slug => $row) {
        $page = get_page_by_path($slug);
        $href = $page instanceof WP_Post ? (string) get_permalink($page) : home_url('/' . $slug . '/');
        $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($row['title']);
        $html .= '<p class="ichnm-structure-head"><span class="ichnm-structure-role">председатель</span> '
            . esc_html($row['chair']) . '</p>';
        $html .= '</a></li>';
    }
    $html .= '</ul>';
    return $html;
}

/**
 * Lab slug → accent colour (structure / developments / facilities). Ticket 57.
 *
 * @return array<string, string>
 */
function ichnm_lab_accent_map(): array
{
    return [
        'nano' => '#0d9488',
        'films' => '#2563eb',
        'lcd' => '#ca8a04',
        'composites' => '#16a34a',
        'woodchem' => '#c2410c',
    ];
}

function ichnm_ensure_news_hub_page(): void
{
    $existing = get_page_by_path('news');
    // No lead prose / TOC — theme template renders the card feed (ticket 49).
    $body = '<!-- ichnm:news-hub -->';
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
    // Whole card is the hyperlink (ticket 81 reverts contact-edge split from 78).
    $href = ichnm_person_permalink($id);
    $name = (string) ($person['name'] ?? $id);
    $role = (string) ($person['role'] ?? '');
    $degree = (string) ($person['degree'] ?? '');
    $phone = (string) ($person['phone'] ?? '');
    $email = (string) ($person['email'] ?? '');
    $initials = (string) ($person['initials'] ?? mb_substr($name, 0, 1));
    $html = '<a class="ichnm-person-card" href="' . esc_url($href) . '">';
    $html .= '<span class="ichnm-person-card-photo photo-slot" aria-hidden="true"><span>'
        . esc_html($initials) . '</span></span>';
    $html .= '<span class="ichnm-person-card-body">';
    $html .= '<span class="ichnm-person-card-role">' . esc_html($role) . '</span>';
    $html .= '<span class="ichnm-person-card-name">' . esc_html($name) . '</span>';
    if ($degree !== '') {
        $html .= '<span class="ichnm-person-card-degree">' . esc_html($degree) . '</span>';
    }
    if ($phone !== '') {
        $html .= '<span class="ichnm-person-card-phone">Тел. ' . esc_html($phone) . '</span>';
    }
    if ($email !== '') {
        $html .= '<span class="ichnm-person-card-email">' . esc_html($email) . '</span>';
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
 * Metrics block in locked network order; show+link or hide (ticket 59).
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
        if ($href === '') {
            continue;
        }
        $stats = is_array($bibliometrics[$field] ?? null) ? $bibliometrics[$field] : [];
        $h_index = array_key_exists('h_index', $stats) ? $stats['h_index'] : null;
        $citations = array_key_exists('citations', $stats) ? $stats['citations'] : null;
        if ($h_index === '') {
            $h_index = null;
        }
        if ($citations === '') {
            $citations = null;
        }
        $bits = [];
        if ($h_index !== null) {
            $bits[] = 'h-индекс: ' . esc_html((string) $h_index);
        }
        if ($citations !== null) {
            $bits[] = 'цитирований: ' . esc_html((string) $citations);
        }
        $label = $labels[$field] ?? $field;
        // Resource name + link only — no «Профиль» category (ticket 75).
        $label_html = '<a href="' . esc_url($href) . '" rel="noopener noreferrer">' . esc_html($label) . '</a>';
        $rows[] = '<dt>' . $label_html . '</dt><dd>' . ($bits ? implode(' · ', $bits) : '') . '</dd>';
    }

    if (!$rows) {
        return '';
    }

    return '<div class="ichnm-person-metrics"><h2>Наукометрия</h2>'
        . '<dl class="metrics-list">' . implode('', $rows) . '</dl></div>';
}

/**
 * Person-owned optional sections only-if-filled (ticket 60). No lab/catalogue sync.
 */
function ichnm_person_optional_sections_html(array $person): string
{
    $labels = [
        'awards' => 'Награды',
        'publications_scientific' => 'Научные публикации',
        'publications_methodical' => 'Методические публикации',
        'interests' => 'Исследовательские интересы',
        'projects' => 'Научные проекты',
    ];
    $sections = is_array($person['person_sections'] ?? null) ? $person['person_sections'] : [];
    $parts = [];
    foreach ($labels as $key => $heading) {
        $raw = $person[$key] ?? ($sections[$key] ?? null);
        $items = [];
        if (is_string($raw) && trim($raw) !== '') {
            $items[] = trim($raw);
        } elseif (is_array($raw)) {
            foreach ($raw as $row) {
                if (is_string($row) && trim($row) !== '') {
                    $items[] = trim($row);
                } elseif (is_array($row)) {
                    $line = trim((string) ($row['title'] ?? $row['cite'] ?? $row['text'] ?? ''));
                    if ($line !== '') {
                        $items[] = $line;
                    }
                }
            }
        }
        if (!$items) {
            continue;
        }
        $parts[] = '<h2>' . esc_html($heading) . '</h2><ul>';
        foreach ($items as $item) {
            $parts[] = '<li>' . esc_html($item) . '</li>';
        }
        $parts[] = '</ul>';
    }
    return implode('', $parts);
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
    $people = ichnm_people_index();
    $parts = [];

    // Ticket 49: состав / положение / контакты.
    $parts[] = '<section id="council-members" class="ichnm-council-section">';
    $parts[] = '<h2>Состав</h2>';
    $parts[] = '<div class="people-list ichnm-card-grid ichnm-leadership-grid">';
    foreach ($copy['council_people'] ?? [] as $row) {
        if (!is_array($row) || empty($row['id'])) {
            continue;
        }
        $id = (string) $row['id'];
        $person = array_merge($people[$id] ?? [], $row);
        $parts[] = ichnm_person_card_html($person, $id);
    }
    $parts[] = '</div></section>';

    $parts[] = '<section id="council-statute" class="ichnm-council-section">';
    $parts[] = '<h2>Положение</h2>';
    $block = is_array($copy['pages']['scientific-council'] ?? null) ? $copy['pages']['scientific-council'] : [];
    $statute = ichnm_blocks_html($block);
    if ($statute !== '') {
        $parts[] = $statute;
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Положение об учёном совете появится после передачи файла.</p>';
    }
    $slots = is_array($block['file_slots'] ?? null) ? $block['file_slots'] : ['Положение об учёном совете.pdf'];
    if (!str_contains($statute, 'file-slot')) {
        $parts[] = ichnm_file_slots_html($slots);
    }
    $parts[] = '</section>';

    $parts[] = '<section id="council-contacts" class="ichnm-council-section">';
    $parts[] = '<h2>Контакты</h2>';
    $secretary = null;
    foreach ($copy['council_people'] ?? [] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $role = mb_strtolower((string) ($row['role'] ?? ''));
        if (str_contains($role, 'секретар')) {
            $secretary = array_merge($people[(string) ($row['id'] ?? '')] ?? [], $row);
            break;
        }
    }
    if (is_array($secretary)) {
        $bits = [];
        if (!empty($secretary['name'])) {
            $bits[] = esc_html((string) $secretary['name']);
        }
        if (!empty($secretary['phone'])) {
            $bits[] = esc_html((string) $secretary['phone']);
        }
        if (!empty($secretary['email'])) {
            $email = (string) $secretary['email'];
            $bits[] = '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        $parts[] = '<p>' . implode(' · ', $bits) . '</p>';
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Контакты учёного секретаря совета появятся после передачи материалов.</p>';
    }
    $parts[] = '</section>';

    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => implode('', $parts),
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
    $left = '85.61';
    $top = '26.94';
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
    $html .= '<div class="ichnm-map-pop">';
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
                $html .= '<p class="ichnm-catalogue-meta ichnm-coop-place">' . esc_html((string) $partner['place']) . '</p>';
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
        $block = is_array($copy['pages']['contacts'] ?? null) ? $copy['pages']['contacts'] : [];
        $html = '';
        foreach ($block['paragraphs'] ?? [] as $para) {
            $para = trim((string) $para);
            if ($para === '') {
                continue;
            }
            $html .= '<p>' . esc_html($para) . '</p>';
        }
        $list = is_array($block['list'] ?? null) ? $block['list'] : [];
        if ($list) {
            $html .= '<div class="info-grid">';
            foreach ($list as $item) {
                $item = trim((string) $item);
                if ($item === '') {
                    continue;
                }
                $role = '';
                $body = $item;
                if (preg_match('/^([^:]+):\s*(.+)$/u', $item, $m)) {
                    $role = trim($m[1]);
                    $body = trim($m[2]);
                }
                $html .= '<article class="info-card">';
                if ($role !== '') {
                    $html .= '<p class="leader-role">' . esc_html($role) . '</p>';
                }
                $html .= '<p>' . esc_html($body) . '</p></article>';
            }
            $html .= '</div>';
        }
        $html .= '<h2 id="find-us">Как нас найти</h2>';
        $html .= '<div class="ichnm-contacts-maps">';
        $html .= '<div class="ichnm-yandex-map" aria-label="Яндекс.Карты">'
            . '<iframe src="https://yandex.ru/map-widget/v1/?ll=27.6286%2C53.9315&z=16&pt=27.6286,53.9315,pm2rdm"'
            . ' width="100%" height="320" frameborder="0" allowfullscreen="true"'
            . ' style="border:0;display:block" title="ИХНМ на Яндекс.Картах"></iframe></div>';
        $html .= '<div class="map-slot is-filled">[ichnm_minsk_map]</div>';
        $html .= '</div>';

        // One contacts page: feedback + requisites as anchors (ticket 50).
        $html .= '<section id="feedback" class="ichnm-contacts-section">';
        $html .= '<h2>Обратная связь</h2>';
        $appeals = get_page_by_path('e-appeals');
        $html .= '<p>Письмо в институт по общим вопросам. Официальные обращения по Закону об обращениях граждан — на отдельной странице.</p>';
        if ($appeals instanceof WP_Post) {
            $html .= '<p><a class="ichnm-cta-appeals" href="' . esc_url(get_permalink($appeals))
                . '">Электронные обращения</a></p>';
        }
        $html .= '[ichnm_feedback_form]';
        $html .= '</section>';

        $html .= '<section id="requisites" class="ichnm-contacts-section">';
        $html .= '<h2>Реквизиты</h2>';
        $html .= ichnm_blocks_html(is_array($copy['pages']['requisites'] ?? null) ? $copy['pages']['requisites'] : []);
        $html .= '</section>';

        wp_update_post(['ID' => (int) $contacts->ID, 'post_content' => $html]);
    }

    // Standalone pages kept for redirects only; body points to contacts anchors.
    $requisites_page = get_page_by_path('requisites');
    if ($requisites_page instanceof WP_Post) {
        $html = '<p>Реквизиты перенесены на страницу <a href="'
            . esc_url(home_url('/contacts/#requisites'))
            . '">Контакты</a>.</p>';
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
    // Body kept for seed; template_redirect sends visitors to contacts#feedback (ticket 50).
    $html = '<p>Форма обратной связи перенесена на страницу <a href="'
        . esc_url(home_url('/contacts/#feedback'))
        . '">Контакты</a>.</p>';
    $html .= '[ichnm_feedback_form]';
    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => $html,
    ]);
}

function ichnm_ensure_publications_hub_page(): void
{
    // No lead prose — list + sidebar chart are rendered in page-publications.php (ticket 48).
    $body = '<!-- ichnm:publications-hub -->';
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

/**
 * Group publication rows by year (desc). Pure seam for ticket 48.
 *
 * @param list<array{year?:int|string,html?:string,id?:int}> $rows
 * @return array<int, list<array{year?:int|string,html?:string,id?:int}>>
 */
function ichnm_publications_group_by_year(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $year = (int) ($row['year'] ?? 0);
        if ($year < 1900) {
            $year = 0;
        }
        if (!isset($groups[$year])) {
            $groups[$year] = [];
        }
        $groups[$year][] = $row;
    }
    krsort($groups, SORT_NUMERIC);
    return $groups;
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
    ];
    foreach ($education_children as $slug => $_title) {
        ichnm_fill_vitrine_page_from_copy($slug, is_array($pages[$slug] ?? null) ? $pages[$slug] : []);
    }
    // Courses dropped from menu (ticket 68); keep page as redirect note if present.
    if (isset($pages['courses'])) {
        ichnm_fill_vitrine_page_from_copy('courses', is_array($pages['courses']) ? $pages['courses'] : []);
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
        'for-staff' => 'Для сотрудника',
        'pvtr' => 'ПВТР',
        'ethics' => 'Этика',
        'personal-data' => 'Персональные данные',
        'video-surveillance' => 'Видеонаблюдение',
        'collective-agreement' => 'Коллективный договор',
    ];
    foreach ($document_children as $slug => $_title) {
        ichnm_fill_vitrine_page_from_copy($slug, is_array($pages[$slug] ?? null) ? $pages[$slug] : []);
    }
    ichnm_fill_vitrine_page_from_copy('e-appeals', is_array($pages['e-appeals'] ?? null) ? $pages['e-appeals'] : []);
    foreach (['student-nir', 'graduate-employment', 'thin-film-department', 'for-students'] as $extra) {
        ichnm_fill_vitrine_page_from_copy($extra, is_array($pages[$extra] ?? null) ? $pages[$extra] : []);
    }

    $for_students = get_page_by_path('for-students');
    if ($for_students instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($pages['for-students'] ?? null) ? $pages['for-students'] : []);
        $html .= '<h2>Разделы</h2><ul class="ichnm-hub-links">';
        foreach (
            [
                'student-nir' => 'Научно-исследовательская работа студентов',
                'graduate-employment' => 'Трудоустройство выпускников',
            ] as $slug => $title
        ) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? (string) get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        wp_update_post(['ID' => (int) $for_students->ID, 'post_content' => $html]);
    }

    $nir = get_page_by_path('student-nir');
    if ($nir instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($pages['student-nir'] ?? null) ? $pages['student-nir'] : []);
        $science = get_page_by_path('science');
        $devs = get_page_by_path('developments');
        $html .= '<ul class="ichnm-hub-links">';
        if ($science instanceof WP_Post) {
            $html .= '<li><a href="' . esc_url((string) get_permalink($science)) . '">Направления работы</a></li>';
        }
        if ($devs instanceof WP_Post) {
            $html .= '<li><a href="' . esc_url((string) get_permalink($devs)) . '">Разработки</a></li>';
        }
        $html .= '</ul>';
        wp_update_post(['ID' => (int) $nir->ID, 'post_content' => $html]);
    }

    $documents = get_page_by_path('documents');
    if ($documents instanceof WP_Post) {
        // Hub tiles: core three for quick access + staff shelf entry (tickets 61 / 78 / 79).
        $html = '<div class="ichnm-doc-grid">';
        foreach (['charter' => 'Устав', 'anti-corruption' => 'Антикоррупция', 'for-staff' => 'Для сотрудника'] as $slug => $title) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? (string) get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<a class="ichnm-doc-tile" href="' . esc_url($href) . '">';
            $html .= '<strong>' . esc_html($title) . '</strong></a>';
        }
        $contacts = get_page_by_path('contacts');
        $req_href = ($contacts instanceof WP_Post ? (string) get_permalink($contacts) : home_url('/contacts/')) . '#requisites';
        $html .= '<a class="ichnm-doc-tile" href="' . esc_url($req_href) . '">';
        $html .= '<strong>Реквизиты</strong></a>';
        $html .= '</div>';
        $html .= '<h2>Нормативные документы для сотрудников</h2><ul class="ichnm-hub-links">';
        foreach (
            [
                'pvtr' => 'Правила внутреннего трудового распорядка',
                'ethics' => 'Этика',
                'personal-data' => 'Персональные данные',
                'video-surveillance' => 'Видеонаблюдение',
                'collective-agreement' => 'Коллективный договор',
            ] as $slug => $title
        ) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? (string) get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        wp_update_post(['ID' => (int) $documents->ID, 'post_content' => $html]);
    }

    $union = get_page_by_path('union');
    if ($union instanceof WP_Post) {
        $union_people = is_array($copy['union_people'] ?? null) ? $copy['union_people'] : [];
        $html = ichnm_community_pack_html(
            'union',
            is_array($pages['union'] ?? null) ? $pages['union'] : [],
            $union_people
        );
        $coll = get_page_by_path('collective-agreement');
        if ($coll instanceof WP_Post) {
            $html .= '<p><a href="' . esc_url((string) get_permalink($coll)) . '">Коллективный договор</a></p>';
        }
        wp_update_post(['ID' => (int) $union->ID, 'post_content' => $html]);
    }

    $smu = get_page_by_path('young-scientists');
    if ($smu instanceof WP_Post) {
        $smu_people = [
            ['id' => 'smu-chair', 'name' => 'Фамилия Имя Отчество', 'role' => 'Председатель совета молодых учёных', 'initials' => 'П'],
            ['id' => 'smu-deputy', 'name' => 'Фамилия Имя Отчество', 'role' => 'Заместитель председателя', 'initials' => 'З'],
            ['id' => 'smu-secretary', 'name' => 'Фамилия Имя Отчество', 'role' => 'Секретарь', 'initials' => 'С'],
        ];
        $html = ichnm_community_pack_html(
            'young-scientists',
            is_array($pages['young-scientists'] ?? null) ? $pages['young-scientists'] : [],
            $smu_people
        );
        wp_update_post(['ID' => (int) $smu->ID, 'post_content' => $html]);
    }

    $vacancies = get_page_by_path('vacancies');
    if ($vacancies instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($pages['vacancies'] ?? null) ? $pages['vacancies'] : []);
        $contacts = get_page_by_path('contacts');
        if ($contacts instanceof WP_Post) {
            $html .= '<p><a class="ichnm-pill ichnm-pill-primary" href="'
                . esc_url((string) get_permalink($contacts) . '#feedback')
                . '">Написать нам</a></p>';
        }
        wp_update_post(['ID' => (int) $vacancies->ID, 'post_content' => $html]);
    }
}

/**
 * Union / SMU pack: leadership · documents · contacts (ticket 45). No «Ко всем подразделениям».
 *
 * @param array{paragraphs?:list<string>,list?:list<string>,file_slots?:list<string>} $block
 * @param list<array<string,mixed>> $leaders
 */
function ichnm_community_pack_html(string $slug, array $block, array $leaders): string
{
    $parts = [];
    $parts[] = '<section id="leadership" class="ichnm-community-section">';
    $parts[] = '<h2>Руководство</h2>';
    if ($leaders) {
        $parts[] = '<div class="people-list ichnm-card-grid ichnm-leadership-grid">';
        foreach ($leaders as $person) {
            if (!is_array($person) || empty($person['id'])) {
                continue;
            }
            $parts[] = ichnm_person_card_html($person, (string) $person['id']);
        }
        $parts[] = '</div>';
    } else {
        $intro = ichnm_blocks_html(['paragraphs' => array_slice($block['paragraphs'] ?? [], 0, 1)]);
        $parts[] = $intro !== '' ? $intro : '<p class="ichnm-empty-slot">Состав руководства появится после передачи материалов.</p>';
    }
    $parts[] = '</section>';

    $parts[] = '<section id="documents" class="ichnm-community-section">';
    $parts[] = '<h2>Документы</h2>';
    $slots = is_array($block['file_slots'] ?? null) ? $block['file_slots'] : [];
    if ($slots) {
        $parts[] = ichnm_file_slots_html($slots);
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Документы подразделения появятся после передачи файлов.</p>';
    }
    if ($slug === 'union') {
        $parts[] = '<p class="ichnm-chart-note">Это страница первичной организации института, а не ссылка на общеакадемический сайт <a href="https://profnan.by/">profnan.by</a>.</p>';
    }
    $parts[] = '</section>';

    $parts[] = '<section id="contacts" class="ichnm-community-section">';
    $parts[] = '<h2>Контакты</h2>';
    $rest = array_slice($block['paragraphs'] ?? [], $leaders ? 0 : 1);
    $contact_html = ichnm_blocks_html(['paragraphs' => $rest, 'list' => $block['list'] ?? []]);
    if ($contact_html !== '') {
        $parts[] = $contact_html;
    } else {
        $parts[] = '<p class="ichnm-empty-slot">Контакты появятся после передачи материалов.</p>';
    }
    $parts[] = '</section>';
    return implode('', $parts);
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
    $accents = ichnm_lab_accent_map();
    $accent = $accents[$slug] ?? '';
    $accent_style = $accent !== '' ? ' style="--lab-accent:' . esc_attr($accent) . '"' : '';
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
    if (!empty($lab['kicker'])) {
        $parts[] = '<p class="lab-kicker">' . esc_html((string) $lab['kicker']) . '</p>';
    }
    $parts[] = '<nav class="lab-local" aria-label="Разделы лаборатории">';
    foreach ($nav as $id => $label) {
        $parts[] = '<a href="#' . esc_attr($id) . '">' . esc_html($label) . '</a>';
    }
    $parts[] = '</nav>';

    // About — large collective hero photo (ticket 44); hide empty prose (ticket 74).
    $about = trim((string) ($lab['about'] ?? ''));
    $parts[] = '<section id="about" class="lab-hero">';
    $parts[] = '<div class="lab-hero-photo">' . ichnm_photo_slot_html($title) . '</div>';
    $parts[] = '<div class="lab-hero-copy"><h2>О лаборатории</h2>';
    if ($about !== '') {
        $parts[] = '<p>' . esc_html($about) . '</p>';
    }
    $parts[] = '</div></section>';

    // Directions — omit empty section (ticket 74). Prefer CPT by lab (ticket 84).
    $directions = [];
    if (function_exists('ichnm_catalogue_cpt_rows') && $slug !== '') {
        $directions = ichnm_catalogue_cpt_rows('science', $slug);
    }
    if (!$directions) {
        $directions = is_array($lab['directions'] ?? null) ? $lab['directions'] : [];
    }
    if ($directions) {
        $parts[] = '<section id="directions"><h2>Направления</h2>';
        $parts[] = '<ul class="dir-grid ichnm-lab-tile-grid">';
        foreach ($directions as $row) {
            if (is_string($row)) {
                $parts[] = '<li class="ichnm-lab-tile" data-lab-slug="' . esc_attr($slug) . '"'
                    . ($accent_style !== '' ? $accent_style : '') . '><a href="#directions"><strong>'
                    . esc_html($row) . '</strong></a></li>';
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
            $d_slug = (string) ($row['slug'] ?? '');
            $href = $d_slug !== ''
                ? ichnm_catalogue_detail_permalink('science', $d_slug)
                : '#directions';
            $parts[] = '<li class="ichnm-lab-tile" data-lab-slug="' . esc_attr($slug) . '"'
                . ($accent_style !== '' ? $accent_style : '') . '><a href="' . esc_url($href) . '"><strong>'
                . esc_html($d_title) . '</strong>';
            if ($lead !== '') {
                $parts[] = '<p class="ichnm-structure-head">' . esc_html($lead) . '</p>';
            }
            $parts[] = '</a></li>';
        }
        $parts[] = '</ul></section>';
    }

    // Projects — omit when empty (ticket 74).
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
        $item = '<li class="ichnm-lab-tile" data-lab-slug="' . esc_attr($slug) . '"'
            . ($accent_style !== '' ? $accent_style : '') . '><a href="#projects"><strong>'
            . esc_html($line) . '</strong>';
        if ($meta) {
            $item .= '<p class="ichnm-structure-head">' . esc_html(implode(' · ', $meta)) . '</p>';
        }
        $item .= '</a></li>';
        if (($project['status'] ?? '') === 'completed') {
            $done[] = $item;
        } else {
            $active[] = $item;
        }
    }
    if ($active || $done) {
        $parts[] = '<section id="projects"><h2>Действующие и завершённые научные проекты</h2>';
        if ($active) {
            $parts[] = '<h3>Действующие</h3><ul class="dir-grid ichnm-lab-tile-grid">' . implode('', $active) . '</ul>';
        }
        if ($done) {
            $parts[] = '<h3>Завершённые</h3><ul class="dir-grid ichnm-lab-tile-grid">' . implode('', $done) . '</ul>';
        }
        $parts[] = '</section>';
    }

    // Equipment → dedicated facility pages (ticket 76); CPT by lab (ticket 84).
    $equipment = [];
    if (function_exists('ichnm_catalogue_cpt_rows') && $slug !== '') {
        $equipment = ichnm_catalogue_cpt_rows('facilities', $slug);
    }
    if (!$equipment) {
        $equipment = is_array($lab['equipment'] ?? null) ? $lab['equipment'] : [];
    }
    if ($equipment) {
        $parts[] = '<section id="equipment"><h2>Оборудование</h2>';
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
            $href = $e_slug !== ''
                ? ichnm_catalogue_detail_permalink('facilities', $e_slug)
                : '';
            $parts[] = '<li class="lab-equip-item ichnm-lab-tile" data-lab-slug="'
                . esc_attr($slug) . '"' . ($accent_style !== '' ? $accent_style : '') . '>';
            if ($href !== '') {
                $parts[] = '<a class="lab-equip-card" href="' . esc_url($href) . '" data-lab-slug="'
                    . esc_attr($slug) . '"' . ($accent_style !== '' ? $accent_style : '') . '>';
            } else {
                $parts[] = '<div class="lab-equip-card" data-lab-slug="' . esc_attr($slug) . '"'
                    . ($accent_style !== '' ? $accent_style : '') . '>';
            }
            $parts[] = ichnm_photo_slot_html($e_title);
            $parts[] = '<h3>' . esc_html($e_title) . '</h3>';
            if ($lead !== '') {
                $parts[] = '<p>' . esc_html($lead) . '</p>';
            }
            $parts[] = $href !== '' ? '</a>' : '</div>';
            $parts[] = '</li>';
        }
        $parts[] = '</ul></section>';
    }

    // Services / developments — CPT by lab (ticket 84); omit empty (ticket 74).
    $developments = [];
    if (function_exists('ichnm_catalogue_cpt_rows') && $slug !== '') {
        $developments = ichnm_catalogue_cpt_rows('developments', $slug);
    }
    if (!$developments) {
        $developments = is_array($lab['developments'] ?? null) ? $lab['developments'] : [];
    }
    if ($developments) {
        $parts[] = '<section id="services"><h2>Услуги и разработки</h2>';
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
            $href = $d_slug !== ''
                ? ichnm_catalogue_detail_permalink('developments', $d_slug)
                : '';
            $data_lab = $slug !== '' ? ' data-lab-slug="' . esc_attr($slug) . '"' : '';
            $parts[] = '<li class="lab-equip-item">';
            if ($href !== '') {
                $parts[] = '<a class="lab-equip-card" href="' . esc_url($href) . '"' . $data_lab . $accent_style . '>';
            } else {
                $parts[] = '<div class="lab-equip-card"' . $data_lab . $accent_style . '>';
            }
            $parts[] = ichnm_photo_slot_html($d_title);
            $parts[] = '<h3>' . esc_html($d_title) . '</h3>';
            if ($lead !== '') {
                $parts[] = '<p>' . esc_html($lead) . '</p>';
            }
            $parts[] = $href !== '' ? '</a>' : '</div>';
            $parts[] = '</li>';
        }
        $parts[] = '</ul></section>';
    }

    // Staff — omit empty; accent wraps cards (ticket 76).
    if ($staff_ids) {
        $parts[] = '<section id="staff"><h2>Наша команда</h2><div class="staff-tab">';
        $parts[] = '<div class="people-list ichnm-lab-team" data-lab-slug="' . esc_attr($slug) . '"'
            . $accent_style . '>';
        foreach ($staff_ids as $sid) {
            $person = $resolve_person($sid);
            if (!$person) {
                continue;
            }
            $parts[] = ichnm_person_card_html($person, $sid);
        }
        $parts[] = '</div></div></section>';
    }

    // Publications — omit empty.
    $publications = is_array($lab['publications'] ?? null) ? $lab['publications'] : [];
    if ($publications) {
        $parts[] = '<section id="pubs"><h2>Избранные публикации</h2>';
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
        $parts[] = '</section>';
    }

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
        $seed_person = $person;
        $existing = ichnm_find_by_slug('person', $id);
        $existing_id = $existing instanceof WP_Post ? (int) $existing->ID : 0;
        if ($existing_id > 0 && function_exists('ichnm_seed_person_meta_if_empty')) {
            ichnm_seed_person_meta_if_empty($existing_id, $seed_person);
            $person = ichnm_person_with_meta_overlay($seed_person, $existing_id);
        }

        $name = (string) ($person['name'] ?? $id);
        $initials = trim((string) ($person['initials'] ?? ''));
        if ($initials === '' && $name !== '') {
            $parts_name = preg_split('/\s+/u', $name) ?: [];
            $initials = '';
            foreach (array_slice($parts_name, 0, 2) as $chunk) {
                $initials .= mb_strtoupper(mb_substr($chunk, 0, 1));
            }
        }

        $copy = [];
        if (!empty($person['role'])) {
            $copy[] = '<p class="leader-role">' . esc_html((string) $person['role']) . '</p>';
        }
        if (!empty($person['demo']) || $id === 'ivanov-demo') {
            $copy[] = '<p class="ichnm-demo-mark" role="note">Демонстрационный профиль (образец), не сотрудник Института.</p>';
        }
        if (!empty($person['degree'])) {
            $copy[] = '<p>' . esc_html((string) $person['degree']) . '</p>';
        }
        $contacts = [];
        if (!empty($person['phone'])) {
            $contacts[] = 'Тел. ' . esc_html((string) $person['phone']);
        }
        if (!empty($person['email'])) {
            $email = (string) $person['email'];
            $contacts[] = '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
        if ($contacts) {
            $copy[] = '<p>' . implode('</p><p>', $contacts) . '</p>';
        }
        $affiliations = $person['affiliations'] ?? [];
        if (is_array($affiliations) && $affiliations) {
            $aff_html = ichnm_person_affiliations_html($affiliations);
            if ($aff_html !== '') {
                $copy[] = $aff_html;
            }
        }
        $copy[] = '<!-- ichnm:person-sections -->' . ichnm_person_optional_sections_html($person)
            . '<!-- /ichnm:person-sections -->';
        $bio = ichnm_blocks_html(['paragraphs' => $person['bio'] ?? []]);
        if ($bio !== '') {
            $copy[] = '<h2>Биография</h2>' . $bio;
        }

        $metrics = '<!-- ichnm:person-metrics -->' . ichnm_person_metrics_html($person)
            . '<!-- /ichnm:person-metrics -->';

        $parts = ['<div class="leader-profile">'];
        $parts[] = '<div class="leader-profile-media">';
        $parts[] = '<div class="leader-photo leader-photo-lg" role="img" aria-label="'
            . esc_attr('Место для официального фото: ' . $name) . '">';
        if ($initials !== '') {
            $parts[] = '<span>' . esc_html($initials) . '</span>';
        }
        $parts[] = '</div>';
        // Metrics under photo (ticket 75).
        $parts[] = $metrics;
        $parts[] = '</div>';
        $parts[] = '<div class="leader-profile-copy">' . implode('', $copy) . '</div></div>';

        $post_id = ichnm_upsert_post([
            'post_type' => 'person',
            'post_status' => 'publish',
            'post_name' => $id,
            'post_title' => $name,
            'post_content' => implode('', $parts),
        ], '_ichnm_person_id', $id);
        if ($post_id > 0 && function_exists('ichnm_seed_person_meta_if_empty')) {
            ichnm_seed_person_meta_if_empty($post_id, $seed_person);
        }
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
 * Also apply IA parity: drop about-overview; contacts without page-children (ticket 41 / 50).
 *
 * @param list<array<string,mixed>> $menu
 * @return list<array<string,mixed>>
 */
function ichnm_menu_with_labs(array $menu): array
{
    $menu = ichnm_menu_parity_filter($menu);
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

/**
 * WP menu parity filters without editing site_model.json (tickets 41, 50, 63).
 *
 * @param list<array<string,mixed>> $menu
 * @return list<array<string,mixed>>
 */
function ichnm_menu_parity_filter(array $menu): array
{
    $out = [];
    $e_appeals_item = null;
    foreach ($menu as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = (string) ($item['id'] ?? '');
        if ($id === 'about-overview' || $id === 'feedback' || $id === 'requisites') {
            continue;
        }
        if (!empty($item['children']) && is_array($item['children'])) {
            // Lift e-appeals out of Documents children (ticket 63).
            $kept = [];
            foreach ($item['children'] as $child) {
                if (!is_array($child)) {
                    continue;
                }
                if ((string) ($child['id'] ?? '') === 'e-appeals') {
                    $e_appeals_item = $child;
                    continue;
                }
                $kept[] = $child;
            }
            $item['children'] = ichnm_menu_parity_filter($kept);
        }
        if ($id === 'contacts') {
            $item['children'] = [];
        }
        $out[] = $item;
        if ($id === 'documents' && is_array($e_appeals_item)) {
            $out[] = $e_appeals_item;
            $e_appeals_item = null;
        }
    }
    return $out;
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

    // Single «Об институте» vitrine: merge former about + about-overview (ticket 41).
    $about = get_page_by_path('about');
    if ($about instanceof WP_Post) {
        $overview_block = is_array($copy['pages']['about-overview'] ?? null) ? $copy['pages']['about-overview'] : [];
        $about_block = is_array($copy['pages']['about'] ?? null) ? $copy['pages']['about'] : [];
        $overview_paras = $overview_block;
        unset($overview_paras['list']);
        $html = ichnm_blocks_html($overview_paras);
        $science = get_page_by_path('science');
        $science_href = $science instanceof WP_Post
            ? (string) get_permalink($science)
            : home_url('/science/');
        $html .= '<p class="ichnm-about-science-cta"><a class="ichnm-pill ichnm-pill-primary" href="'
            . esc_url($science_href) . '">Направления работы</a></p>';
        $awards = is_array($about_block['list'] ?? null) ? $about_block['list'] : [];
        if ($awards) {
            $html .= '<h2>Достижения</h2><ul>';
            foreach ($awards as $item) {
                $item = trim((string) $item);
                if ($item === '') {
                    continue;
                }
                $html .= '<li>' . esc_html($item) . '</li>';
            }
            $html .= '</ul>';
        }
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
        wp_update_post(['ID' => (int) $about->ID, 'post_content' => $html]);
    }

    // Legacy slug kept for 301 → about (ticket 41).
    $overview = get_page_by_path('about-overview');
    if ($overview instanceof WP_Post) {
        $html = '<p>Раздел «Сведения» объединён со страницей <a href="'
            . esc_url(home_url('/about/'))
            . '">Об институте</a>.</p>';
        wp_update_post(['ID' => (int) $overview->ID, 'post_content' => $html]);
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
    // Prefer CPT catalogue for search (ticket 85); fall back to seed JSON.
    $facility_rows = function_exists('ichnm_catalogue_cpt_rows')
        ? ichnm_catalogue_cpt_rows('facilities')
        : [];
    if (!$facility_rows) {
        $facility_rows = is_array($copy['facilities_items'] ?? null) ? $copy['facilities_items'] : [];
        foreach (ichnm_lab_catalogue_rows('equipment') as $row) {
            $facility_rows[] = $row;
        }
    }
    foreach ($facility_rows as $item) {
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
    $dev_rows = function_exists('ichnm_catalogue_cpt_rows')
        ? ichnm_catalogue_cpt_rows('developments')
        : [];
    if (!$dev_rows) {
        $dev_rows = is_array($copy['developments_items'] ?? null) ? $copy['developments_items'] : [];
        foreach (ichnm_lab_catalogue_rows('developments') as $row) {
            $dev_rows[] = $row;
        }
    }
    foreach ($dev_rows as $item) {
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
    $dir_rows = function_exists('ichnm_catalogue_cpt_rows')
        ? ichnm_catalogue_cpt_rows('science')
        : [];
    foreach ($dir_rows as $item) {
        if (!is_array($item)) {
            continue;
        }
        $s_slug = sanitize_title((string) ($item['slug'] ?? ''));
        if ($s_slug === '') {
            continue;
        }
        $rows[] = [
            'type' => 'unit',
            'title' => (string) ($item['title'] ?? ''),
            'href' => ichnm_catalogue_detail_permalink('science', $s_slug),
            'meta' => (string) ($item['lead'] ?? 'Направление'),
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
        // Files may arrive after the seed is current — still attach the packet.
        if (function_exists('ichnm_apply_colleague_packet')) {
            ichnm_apply_colleague_packet();
        }
        return;
    }
    ichnm_configure_site_contour();
    ichnm_enable_bvi_plugin();
    ichnm_ensure_pages();
    ichnm_import_people();
    ichnm_import_council_people();
    ichnm_import_admin_unit_people();
    ichnm_import_catalogue_cpts();
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
    // After hubs/pages are filled: attach PDFs, import colleague pubs, social/brand hooks.
    if (function_exists('ichnm_apply_colleague_packet')) {
        ichnm_apply_colleague_packet();
    }
    update_option('ichnm_content_seed_version', ICHNM_CONTENT_SEED_VERSION);
    flush_rewrite_rules(false);
}
