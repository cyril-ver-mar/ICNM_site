<?php
/**
 * Idempotent import of honest migrated_copy into pages and feed CPTs.
 */

if (!defined('ABSPATH')) {
    exit;
}

const ICHNM_CONTENT_SEED_VERSION = 17;

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

/**
 * @param array{paragraphs?:list<string>,list?:list<string>} $block
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
    return $html;
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
        $parts = [];
        if (!empty($unit['phone'])) {
            $parts[] = '<p>Телефон: ' . esc_html((string) $unit['phone']) . '</p>';
        }
        foreach ($unit['people'] ?? [] as $person) {
            if (!is_array($person)) {
                continue;
            }
            $line = trim((string) ($person['name'] ?? ''));
            if ($line === '') {
                continue;
            }
            if (!empty($person['role'])) {
                $line .= ' — ' . (string) $person['role'];
            }
            $parts[] = '<p>' . esc_html($line) . '</p>';
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
        }
        if (!$parts) {
            continue;
        }
        wp_update_post([
            'ID' => (int) $page->ID,
            'post_content' => implode('', $parts),
        ]);
    }

    ichnm_fill_leadership_page();
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
        $href = ichnm_person_permalink($id);
        $name = (string) ($person['name'] ?? $id);
        $role = (string) ($person['role'] ?? '');
        $degree = (string) ($person['degree'] ?? '');
        $initials = (string) ($person['initials'] ?? mb_substr($name, 0, 1));
        $cards[] = '<a class="ichnm-person-card" href="' . esc_url($href) . '">';
        $cards[] = '<span class="ichnm-person-card-photo" aria-hidden="true">' . esc_html($initials) . '</span>';
        $cards[] = '<span class="ichnm-person-card-body">';
        $cards[] = '<span class="ichnm-person-card-role">' . esc_html($role) . '</span>';
        $cards[] = '<span class="ichnm-person-card-name">' . esc_html($name) . '</span>';
        if ($degree !== '') {
            $cards[] = '<span class="ichnm-person-card-degree">' . esc_html($degree) . '</span>';
        }
        $cards[] = '</span></a>';
    }
    $cards[] = '</div>';
    wp_update_post([
        'ID' => (int) $page->ID,
        'post_content' => $intro . implode('', $cards),
    ]);
}

function ichnm_catalogue_cards_html(string $title, array $items, string $lab_key = 'lab_id'): string
{
    if (!$items) {
        return '';
    }
    $labs = [];
    foreach (ichnm_migrated_copy()['labs'] ?? [] as $lab) {
        if (is_array($lab) && !empty($lab['id'])) {
            $labs[(string) $lab['id']] = $lab;
        }
        if (is_array($lab) && !empty($lab['slug'])) {
            $labs['lab-' . $lab['slug']] = $lab;
        }
    }
    $parts = ['<h2>' . esc_html($title) . '</h2>', '<div class="ichnm-card-grid ichnm-catalogue-grid">'];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $parts[] = '<article class="ichnm-catalogue-card">';
        $parts[] = '<h3>' . esc_html((string) ($item['title'] ?? '')) . '</h3>';
        if (!empty($item['lead'])) {
            $parts[] = '<p>' . esc_html((string) $item['lead']) . '</p>';
        }
        if (!empty($item['product'])) {
            $parts[] = '<p>' . esc_html((string) $item['product']) . '</p>';
        }
        if (!empty($item['spec'])) {
            $parts[] = '<p>' . esc_html((string) $item['spec']) . '</p>';
        }
        if (!empty($item['contacts'])) {
            $parts[] = '<p class="ichnm-catalogue-meta">' . esc_html((string) $item['contacts']) . '</p>';
        }
        $lab_id = (string) ($item[$lab_key] ?? '');
        if ($lab_id !== '' && isset($labs[$lab_id])) {
            $lab = $labs[$lab_id];
            $slug = (string) ($lab['slug'] ?? '');
            $lab_title = (string) ($lab['title'] ?? $slug);
            $href = $slug !== '' ? home_url('/labs/' . rawurlencode($slug) . '/') : '#';
            $parts[] = '<p class="ichnm-catalogue-meta"><a href="' . esc_url($href) . '">' . esc_html($lab_title) . '</a></p>';
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
        $html .= ichnm_catalogue_cards_html('Направления работы', is_array($copy['science_topics'] ?? null) ? $copy['science_topics'] : []);
        wp_update_post(['ID' => (int) $science->ID, 'post_content' => $html]);
    }

    $developments = get_page_by_path('developments');
    if ($developments instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['developments'] ?? null) ? $copy['pages']['developments'] : []);
        $html .= ichnm_catalogue_cards_html('Разработки', is_array($copy['developments_items'] ?? null) ? $copy['developments_items'] : []);
        wp_update_post(['ID' => (int) $developments->ID, 'post_content' => $html]);
    }

    $facilities = get_page_by_path('facilities');
    if ($facilities instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array($copy['pages']['facilities'] ?? null) ? $copy['pages']['facilities'] : []);
        $html .= ichnm_catalogue_cards_html('Материальная база', is_array($copy['facilities_items'] ?? null) ? $copy['facilities_items'] : []);
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
    $href = ichnm_person_permalink($id);
    $name = (string) ($person['name'] ?? $id);
    $role = (string) ($person['role'] ?? '');
    $degree = (string) ($person['degree'] ?? '');
    $initials = (string) ($person['initials'] ?? mb_substr($name, 0, 1));
    $html = '<a class="ichnm-person-card" href="' . esc_url($href) . '">';
    $html .= '<span class="ichnm-person-card-photo" aria-hidden="true">' . esc_html($initials) . '</span>';
    $html .= '<span class="ichnm-person-card-body">';
    $html .= '<span class="ichnm-person-card-role">' . esc_html($role) . '</span>';
    $html .= '<span class="ichnm-person-card-name">' . esc_html($name) . '</span>';
    if ($degree !== '') {
        $html .= '<span class="ichnm-person-card-degree">' . esc_html($degree) . '</span>';
    }
    $html .= '</span></a>';
    return $html;
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
    $cards = ['<div class="ichnm-card-grid ichnm-leadership-grid">'];
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
    $src = ichnm_source_url('maps/world-countries.svg');
    $html = '<figure class="ichnm-world-map" aria-label="Карта научного сотрудничества">';
    $html .= '<img class="ichnm-world-map-bg" src="' . esc_url($src) . '" alt="" width="1000" height="500" loading="lazy">';
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
        $html .= '<div class="ichnm-map-hotspot" style="left:' . esc_attr((string) $x) . '%;top:' . esc_attr((string) $y) . '%">';
        $html .= '<button type="button" class="ichnm-map-pin" aria-describedby="ichnm-pop-' . esc_attr($slug) . '">';
        $html .= '<span class="screen-reader-text">' . esc_html($title) . '</span></button>';
        $html .= '<div class="ichnm-map-pop" id="ichnm-pop-' . esc_attr($slug) . '">';
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
    $path = WP_CONTENT_DIR . '/uploads/ichnm-source/maps/minsk.svg';
    $left = '62';
    $top = '48';
    if (is_readable($path)) {
        $raw = (string) file_get_contents($path);
        if (preg_match('/data-pin-left="([\d.]+)"/', $raw, $m)) {
            $left = $m[1];
        }
        if (preg_match('/data-pin-top="([\d.]+)"/', $raw, $m)) {
            $top = $m[1];
        }
    }
    $src = ichnm_source_url('maps/minsk.svg');
    $html = '<figure class="ichnm-world-map ichnm-city-map" aria-label="Минск, ул. Ф. Скорины, 36">';
    $html .= '<img class="ichnm-world-map-bg" src="' . esc_url($src) . '" alt="" width="800" height="500" loading="lazy">';
    $html .= '<div class="ichnm-map-hotspot" style="left:' . esc_attr($left) . '%;top:' . esc_attr($top) . '%">';
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
    $html .= '<h2>Карта сотрудничества</h2>' . ichnm_world_map_html();
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
        $html .= '<h2>Как нас найти</h2>' . ichnm_minsk_map_html();
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

function ichnm_import_publications(): void
{
    foreach (ichnm_migrated_copy()['labs'] ?? [] as $lab) {
        if (!is_array($lab)) {
            continue;
        }
        $lab_slug = (string) ($lab['slug'] ?? '');
        $lab_title = (string) ($lab['title'] ?? $lab_slug);
        foreach ($lab['publications'] ?? [] as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $cite = trim((string) ($row['cite'] ?? ''));
            if ($cite === '') {
                continue;
            }
            $doi = trim((string) ($row['doi'] ?? ''));
            $year = (int) ($row['year'] ?? 0);
            $key = 'pub-' . md5($lab_slug . '|' . $cite . '|' . $doi);
            $html = '<p class="ichnm-pub-cite">' . esc_html($cite) . '</p>';
            if ($doi !== '') {
                $href = str_starts_with(strtolower($doi), 'http') ? $doi : ('https://doi.org/' . $doi);
                $html .= '<p class="ichnm-pub-doi"><a href="' . esc_url($href) . '" rel="noopener noreferrer">DOI: ' . esc_html($doi) . '</a></p>';
            }
            if ($lab_slug !== '') {
                $html .= '<p class="ichnm-pub-lab"><a href="' . esc_url(home_url('/labs/' . rawurlencode($lab_slug) . '/')) . '">' . esc_html($lab_title) . '</a></p>';
            }
            $post_date = ($year > 1990 ? (string) $year : '2024') . '-06-15 12:00:00';
            $title = mb_substr($cite, 0, 120);
            ichnm_upsert_post([
                'post_type' => 'publication',
                'post_status' => 'publish',
                'post_name' => sanitize_title($lab_slug . '-pub-' . ($index + 1) . '-' . substr(md5($cite), 0, 6)),
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
    $html .= '<figure class="ichnm-pub-chart"><figcaption>Статьи по годам</figcaption>';
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
    $education = get_page_by_path('education');
    if ($education instanceof WP_Post) {
        $copy = ichnm_migrated_copy();
        $html = ichnm_blocks_html(is_array($copy['pages']['education'] ?? null) ? $copy['pages']['education'] : []);
        $children = [
            'aspirantura' => 'Аспирантура',
            'doctorate' => 'Докторантура',
            'defense-council' => 'Совет по защитам',
            'internships' => 'Стажировки',
            'courses' => 'Курсы',
        ];
        $html .= '<h2>Разделы</h2><ul class="ichnm-hub-links">';
        foreach ($children as $slug => $title) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        wp_update_post(['ID' => (int) $education->ID, 'post_content' => $html]);
    }

    $documents = get_page_by_path('documents');
    if ($documents instanceof WP_Post) {
        $copy = ichnm_migrated_copy();
        $html = ichnm_blocks_html(is_array($copy['pages']['documents'] ?? null) ? $copy['pages']['documents'] : []);
        $children = [
            'charter' => 'Устав',
            'anti-corruption' => 'Антикоррупция',
            'e-appeals' => 'Электронные обращения',
        ];
        $html .= '<h2>Документы</h2><ul class="ichnm-hub-links">';
        foreach ($children as $slug => $title) {
            $page = get_page_by_path($slug);
            $href = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . $slug . '/');
            $html .= '<li><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        $html .= '<p class="ichnm-chart-note">Реквизиты института — в разделе <a href="' . esc_url(home_url('/requisites/')) . '">Контакты → Реквизиты</a>, не здесь.</p>';
        wp_update_post(['ID' => (int) $documents->ID, 'post_content' => $html]);
    }

    $union = get_page_by_path('union');
    if ($union instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array(ichnm_migrated_copy()['pages']['union'] ?? null) ? ichnm_migrated_copy()['pages']['union'] : []);
        $html .= '<p class="ichnm-chart-note">Это страница первичной организации института, а не ссылка на общеакадемический сайт <a href="https://profnan.by/">profnan.by</a>.</p>';
        wp_update_post(['ID' => (int) $union->ID, 'post_content' => $html]);
    }

    $smu = get_page_by_path('young-scientists');
    if ($smu instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array(ichnm_migrated_copy()['pages']['young-scientists'] ?? null) ? ichnm_migrated_copy()['pages']['young-scientists'] : []);
        wp_update_post(['ID' => (int) $smu->ID, 'post_content' => $html]);
    }

    $vacancies = get_page_by_path('vacancies');
    if ($vacancies instanceof WP_Post) {
        $html = ichnm_blocks_html(is_array(ichnm_migrated_copy()['pages']['vacancies'] ?? null) ? ichnm_migrated_copy()['pages']['vacancies'] : []);
        $feedback = get_page_by_path('feedback');
        if ($feedback instanceof WP_Post) {
            $html .= '<p><a class="ichnm-pill ichnm-pill-primary" href="' . esc_url(get_permalink($feedback)) . '">Написать нам</a></p>';
        }
        wp_update_post(['ID' => (int) $vacancies->ID, 'post_content' => $html]);
    }
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

function ichnm_import_next_event(): void
{
    $event = ichnm_migrated_copy()['next_event'] ?? null;
    if (!is_array($event) || empty($event['title'])) {
        return;
    }
    $slug = 'aist-2025';
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

function ichnm_lab_pack_html(array $lab): string
{
    $parts = [];
    $about = trim((string) ($lab['about'] ?? ''));
    if ($about !== '') {
        $parts[] = '<p>' . esc_html($about) . '</p>';
    }
    if (!empty($lab['kicker'])) {
        $parts[] = '<p><em>' . esc_html((string) $lab['kicker']) . '</em></p>';
    }
    $contacts = [];
    if (!empty($lab['phone'])) {
        $contacts[] = esc_html((string) $lab['phone']);
    }
    if (!empty($lab['email'])) {
        $email = (string) $lab['email'];
        $contacts[] = '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
    }
    if ($contacts) {
        $parts[] = '<p>' . implode(' · ', $contacts) . '</p>';
    }

    $people_index = function_exists('ichnm_people_index') ? ichnm_people_index() : [];
    $staff_ids = [];
    if (!empty($lab['head_id'])) {
        $staff_ids[] = (string) $lab['head_id'];
    }
    foreach ($lab['staff_ids'] ?? [] as $sid) {
        $sid = (string) $sid;
        if ($sid !== '' && !in_array($sid, $staff_ids, true)) {
            $staff_ids[] = $sid;
        }
    }
    if ($staff_ids) {
        $parts[] = '<h2>Сотрудники</h2><div class="ichnm-card-grid ichnm-leadership-grid">';
        foreach ($staff_ids as $sid) {
            $person = $people_index[$sid] ?? null;
            if (!$person) {
                $post = ichnm_find_by_slug('person', $sid);
                if ($post instanceof WP_Post) {
                    $person = [
                        'id' => $sid,
                        'name' => get_the_title($post),
                        'role' => ($sid === (string) ($lab['head_id'] ?? '')) ? 'Заведующий лабораторией' : 'Сотрудник',
                        'initials' => mb_substr(get_the_title($post), 0, 1),
                    ];
                }
            }
            if (!$person) {
                continue;
            }
            if ($sid === (string) ($lab['head_id'] ?? '') && empty($person['role'])) {
                $person['role'] = 'Заведующий лабораторией';
            }
            $parts[] = ichnm_person_card_html($person, $sid);
        }
        $parts[] = '</div>';
    }

    $directions = $lab['directions'] ?? [];
    if (is_array($directions) && $directions) {
        $parts[] = '<h2>Направления</h2><ul>';
        foreach ($directions as $row) {
            $parts[] = '<li>' . esc_html(is_string($row) ? $row : (string) ($row['title'] ?? $row['name'] ?? '')) . '</li>';
        }
        $parts[] = '</ul>';
    }

    $projects = $lab['projects'] ?? [];
    if (is_array($projects) && $projects) {
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
            if (!empty($project['years'])) {
                $line .= ' (' . (string) $project['years'] . ')';
            }
            if (($project['status'] ?? '') === 'completed') {
                $done[] = $line;
            } else {
                $active[] = $line;
            }
        }
        $parts[] = '<h2 id="projects">Действующие и завершённые научные проекты</h2>';
        if ($active) {
            $parts[] = '<h3>Действующие</h3><ul>';
            foreach ($active as $line) {
                $parts[] = '<li>' . esc_html($line) . '</li>';
            }
            $parts[] = '</ul>';
        }
        if ($done) {
            $parts[] = '<h3>Завершённые</h3><ul>';
            foreach ($done as $line) {
                $parts[] = '<li>' . esc_html($line) . '</li>';
            }
            $parts[] = '</ul>';
        }
    }

    $equipment = $lab['equipment'] ?? [];
    if (is_array($equipment) && $equipment) {
        $parts[] = '<h2>Оборудование</h2><ul>';
        foreach ($equipment as $row) {
            $label = is_string($row) ? $row : (string) ($row['title'] ?? $row['name'] ?? '');
            if ($label !== '') {
                $parts[] = '<li>' . esc_html($label) . '</li>';
            }
        }
        $parts[] = '</ul>';
    }

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
            $parts[] = '<h2>Аффилиации</h2><ul>';
            foreach ($affiliations as $aff) {
                if (!is_array($aff)) {
                    continue;
                }
                $line = trim((string) ($aff['role'] ?? '') . ' · ' . (string) ($aff['unit_id'] ?? ''));
                $parts[] = '<li>' . esc_html(trim($line, ' ·')) . '</li>';
            }
            $parts[] = '</ul>';
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
        $parts[] = '<h2>Материалы конференций</h2>';
        foreach ($conferences as $conf) {
            if (!is_array($conf)) {
                continue;
            }
            $parts[] = '<h3>' . esc_html((string) ($conf['title'] ?? $conf['slug'] ?? '')) . '</h3>';
            if (!empty($conf['when'])) {
                $parts[] = '<p>' . esc_html((string) $conf['when']) . '</p>';
            }
            foreach ($conf['paragraphs'] ?? [] as $para) {
                $parts[] = '<p>' . esc_html((string) $para) . '</p>';
            }
            $files = $conf['files'] ?? [];
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
        $rows[] = [
            'type' => 'facility',
            'title' => (string) ($item['title'] ?? ''),
            'href' => home_url('/facilities/#' . sanitize_title((string) ($item['slug'] ?? ''))),
            'meta' => (string) ($item['lead'] ?? $item['spec'] ?? 'Прибор'),
        ];
    }
    foreach ($copy['developments_items'] ?? [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $rows[] = [
            'type' => 'development',
            'title' => (string) ($item['title'] ?? ''),
            'href' => home_url('/developments/#' . sanitize_title((string) ($item['slug'] ?? ''))),
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

    $render = static function (array $items) use (&$render): string {
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
                $page = get_page_by_path($id);
                $href = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . rawurlencode($id) . '/');
                if ($id === 'news') {
                    $news = get_page_by_path('news');
                    $href = $news instanceof WP_Post ? get_permalink($news) : $href;
                }
                if ($id === 'events') {
                    $events = get_page_by_path('events');
                    $href = $events instanceof WP_Post ? get_permalink($events) : $href;
                }
                if ($id === 'publications') {
                    $pubs = get_page_by_path('publications');
                    $href = $pubs instanceof WP_Post ? get_permalink($pubs) : $href;
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

    $html = '<nav class="ichnm-sitemap" aria-label="Карта сайта">' . $render($tree) . '</nav>';
    $html .= '<h2>Руководство</h2><ul class="ichnm-hub-links">';
    foreach (ichnm_migrated_copy()['leadership_order'] ?? [] as $id) {
        $person = ichnm_find_by_slug('person', (string) $id);
        if (!$person instanceof WP_Post) {
            continue;
        }
        $html .= '<li><a href="' . esc_url(get_permalink($person)) . '">' . esc_html(get_the_title($person)) . '</a></li>';
    }
    $html .= '</ul>';
    $search = get_page_by_path('search');
    if ($search instanceof WP_Post) {
        $html .= '<p><a href="' . esc_url(get_permalink($search)) . '">Открыть поиск</a></p>';
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
