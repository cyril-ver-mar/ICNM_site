<?php
/**
 * Catalogue CPT admin: direction / facility / development (tickets 82–86).
 *
 * Meta is editor source of truth after first save (_ichnm_editor_owned).
 * Content sync must not wipe owned title/body/meta (person-admin pattern).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @return list<string> */
function ichnm_catalogue_post_types(): array
{
    return ['direction', 'facility', 'development'];
}

/**
 * Hub parent slug for a catalogue CPT.
 */
function ichnm_catalogue_type_parent(string $post_type): string
{
    return match ($post_type) {
        'direction' => 'science',
        'facility' => 'facilities',
        'development' => 'developments',
        default => '',
    };
}

/**
 * CPT for a hub parent slug.
 */
function ichnm_catalogue_parent_type(string $parent): string
{
    return match ($parent) {
        'science' => 'direction',
        'facilities' => 'facility',
        'developments' => 'development',
        default => '',
    };
}

/**
 * @return array<string, string> slug => title
 */
function ichnm_catalogue_lab_choices(): array
{
    $out = ['' => '— без лаборатории (общеинститутское) —'];
    $copy = function_exists('ichnm_migrated_copy') ? ichnm_migrated_copy() : [];
    foreach ($copy['labs'] ?? [] as $lab) {
        if (!is_array($lab) || empty($lab['slug'])) {
            continue;
        }
        $slug = (string) $lab['slug'];
        $out[$slug] = (string) ($lab['title'] ?? $slug);
    }
    $posts = get_posts([
        'post_type' => 'department',
        'posts_per_page' => 100,
        'post_status' => 'publish',
        'suppress_filters' => true,
    ]);
    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $slug = (string) get_post_meta((int) $post->ID, '_ichnm_lab_slug', true);
        if ($slug === '') {
            $slug = (string) $post->post_name;
        }
        $slug = preg_replace('/-(en|be|zh)$/', '', $slug) ?: $slug;
        if ($slug !== '' && !isset($out[$slug])) {
            $out[$slug] = get_the_title($post);
        }
    }
    return $out;
}

/**
 * @return array<string, string> person_id => name
 */
function ichnm_catalogue_person_choices(): array
{
    $out = ['' => '— не закреплено —'];
    $copy = function_exists('ichnm_migrated_copy') ? ichnm_migrated_copy() : [];
    foreach ($copy['people'] ?? [] as $person) {
        if (!is_array($person) || empty($person['id'])) {
            continue;
        }
        $out[(string) $person['id']] = (string) ($person['name'] ?? $person['id']);
    }
    $posts = get_posts([
        'post_type' => 'person',
        'posts_per_page' => 300,
        'post_status' => 'publish',
        'suppress_filters' => true,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $id = (string) get_post_meta((int) $post->ID, '_ichnm_person_id', true);
        if ($id === '') {
            $id = (string) $post->post_name;
        }
        if ($id !== '') {
            $out[$id] = get_the_title($post);
        }
    }
    return $out;
}

/**
 * @return array<string, string> direction slug => title
 */
function ichnm_catalogue_direction_choices(): array
{
    $out = ['' => '— без направления —'];
    $posts = get_posts([
        'post_type' => 'direction',
        'posts_per_page' => 200,
        'post_status' => 'publish',
        'suppress_filters' => true,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $slug = (string) $post->post_name;
        if ($slug !== '') {
            $out[$slug] = get_the_title($post);
        }
    }
    if (count($out) === 1 && function_exists('ichnm_catalogue_rows_for_parent')) {
        foreach (ichnm_catalogue_rows_for_parent('science') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = sanitize_title((string) ($row['slug'] ?? ''));
            if ($slug !== '') {
                $out[$slug] = (string) ($row['title'] ?? $slug);
            }
        }
    }
    return $out;
}

function ichnm_catalogue_is_editor_owned(int $post_id): bool
{
    if ($post_id <= 0) {
        return false;
    }
    return (string) get_post_meta($post_id, '_ichnm_editor_owned', true) === '1';
}

/**
 * Seed empty catalogue meta from a migrated row (never overwrite non-empty).
 *
 * @param array<string, mixed> $row
 */
function ichnm_seed_catalogue_meta_if_empty(int $post_id, array $row): void
{
    if ($post_id <= 0) {
        return;
    }
    $lab_slug = trim((string) ($row['lab_slug'] ?? ''));
    if ($lab_slug === '' && !empty($row['lab_id'])) {
        $lab_id = (string) $row['lab_id'];
        $lab_slug = str_starts_with($lab_id, 'lab-') ? substr($lab_id, 4) : $lab_id;
    }
    if ($lab_slug !== '' && (string) get_post_meta($post_id, '_ichnm_lab_slug', true) === '') {
        update_post_meta($post_id, '_ichnm_lab_slug', $lab_slug);
    }
    $lab_id = trim((string) ($row['lab_id'] ?? ''));
    if ($lab_id !== '' && (string) get_post_meta($post_id, '_ichnm_lab_id', true) === '') {
        update_post_meta($post_id, '_ichnm_lab_id', $lab_id);
    }
    $staff = trim((string) ($row['staff_id'] ?? $row['head_id'] ?? ''));
    if ($staff !== '' && (string) get_post_meta($post_id, '_ichnm_staff_id', true) === '') {
        update_post_meta($post_id, '_ichnm_staff_id', $staff);
    }
    $dir = trim((string) ($row['direction_slug'] ?? $row['direction_id'] ?? ''));
    if ($dir !== '' && (string) get_post_meta($post_id, '_ichnm_direction_slug', true) === '') {
        update_post_meta($post_id, '_ichnm_direction_slug', sanitize_title($dir));
    }
    $lead = trim((string) ($row['lead'] ?? ''));
    if ($lead !== '' && (string) get_post_meta($post_id, '_ichnm_lead', true) === '') {
        update_post_meta($post_id, '_ichnm_lead', $lead);
    }
}

/**
 * Build the row-shaped array used by catalogue HTML helpers from a CPT post.
 *
 * @return array<string, mixed>
 */
function ichnm_catalogue_post_as_row(WP_Post $post): array
{
    $id = (int) $post->ID;
    $lab_slug = (string) get_post_meta($id, '_ichnm_lab_slug', true);
    $lab_id = (string) get_post_meta($id, '_ichnm_lab_id', true);
    if ($lab_id === '' && $lab_slug !== '') {
        $lab_id = 'lab-' . $lab_slug;
    }
    $lab_title = '';
    if ($lab_slug !== '' && function_exists('ichnm_labs_by_id')) {
        $labs = ichnm_labs_by_id();
        if ($lab_id !== '' && isset($labs[$lab_id])) {
            $lab_title = (string) ($labs[$lab_id]['title'] ?? '');
        } elseif (isset($labs['lab-' . $lab_slug])) {
            $lab_title = (string) ($labs['lab-' . $lab_slug]['title'] ?? '');
        }
    }
    $lead = (string) get_post_meta($id, '_ichnm_lead', true);
    if ($lead === '') {
        $lead = (string) $post->post_excerpt;
    }
    $row = [
        'slug' => (string) $post->post_name,
        'title' => get_the_title($post),
        'lead' => $lead,
        'lab_slug' => $lab_slug,
        'lab_id' => $lab_id,
        'lab_title' => $lab_title,
        'staff_id' => (string) get_post_meta($id, '_ichnm_staff_id', true),
        'direction_slug' => (string) get_post_meta($id, '_ichnm_direction_slug', true),
        'spec' => '',
        'product' => '',
        'contacts' => '',
    ];
    // Prefer plain text from content for lead/spec when meta empty.
    $plain = trim(wp_strip_all_tags((string) $post->post_content));
    if ($row['lead'] === '' && $plain !== '') {
        $row['lead'] = mb_substr($plain, 0, 280);
    }
    return $row;
}

/**
 * @return list<array<string, mixed>>
 */
function ichnm_catalogue_cpt_rows(string $parent, string $lab_slug = ''): array
{
    $type = ichnm_catalogue_parent_type($parent);
    if ($type === '') {
        return [];
    }
    $args = [
        'post_type' => $type,
        'posts_per_page' => 200,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'suppress_filters' => true,
    ];
    if ($lab_slug !== '') {
        $args['meta_key'] = '_ichnm_lab_slug';
        $args['meta_value'] = $lab_slug;
    }
    $posts = get_posts($args);
    $rows = [];
    foreach ($posts as $post) {
        if ($post instanceof WP_Post) {
            $rows[] = ichnm_catalogue_post_as_row($post);
        }
    }
    return $rows;
}

add_action('add_meta_boxes', static function (): void {
    foreach (ichnm_catalogue_post_types() as $type) {
        add_meta_box(
            'ichnm_catalogue_meta',
            'Каталог ИХНМ',
            'ichnm_render_catalogue_meta_box',
            $type,
            'side',
            'high'
        );
    }
});

function ichnm_render_catalogue_meta_box(WP_Post $post): void
{
    wp_nonce_field('ichnm_catalogue_meta', 'ichnm_catalogue_meta_nonce');
    $lab = (string) get_post_meta((int) $post->ID, '_ichnm_lab_slug', true);
    $staff = (string) get_post_meta((int) $post->ID, '_ichnm_staff_id', true);
    $dir = (string) get_post_meta((int) $post->ID, '_ichnm_direction_slug', true);

    echo '<p><label for="ichnm_lab_slug"><strong>Лаборатория</strong></label><br>';
    echo '<select name="ichnm_lab_slug" id="ichnm_lab_slug" style="width:100%">';
    foreach (ichnm_catalogue_lab_choices() as $slug => $title) {
        echo '<option value="' . esc_attr($slug) . '"' . selected($lab, $slug, false) . '>'
            . esc_html($title) . '</option>';
    }
    echo '</select></p>';

    echo '<p><label for="ichnm_staff_id"><strong>Сотрудник (опционально)</strong></label><br>';
    echo '<select name="ichnm_staff_id" id="ichnm_staff_id" style="width:100%">';
    foreach (ichnm_catalogue_person_choices() as $id => $name) {
        echo '<option value="' . esc_attr($id) . '"' . selected($staff, $id, false) . '>'
            . esc_html($name) . '</option>';
    }
    echo '</select></p>';

    if ($post->post_type === 'development') {
        echo '<p><label for="ichnm_direction_slug"><strong>Направление (опционально)</strong></label><br>';
        echo '<select name="ichnm_direction_slug" id="ichnm_direction_slug" style="width:100%">';
        foreach (ichnm_catalogue_direction_choices() as $slug => $title) {
            echo '<option value="' . esc_attr($slug) . '"' . selected($dir, $slug, false) . '>'
                . esc_html($title) . '</option>';
        }
        echo '</select></p>';
    }

    echo '<p class="description">Фото — изображение записи. Файлы — медиатека / полка в тексте.</p>';
}

add_action('save_post', static function (int $post_id, WP_Post $post): void {
    if (!empty($GLOBALS['ichnm_catalogue_sync_lock'])) {
        return;
    }
    if (!in_array($post->post_type, ichnm_catalogue_post_types(), true)) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['ichnm_catalogue_meta_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['ichnm_catalogue_meta_nonce'])), 'ichnm_catalogue_meta')
    ) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $lab = isset($_POST['ichnm_lab_slug'])
        ? sanitize_title(wp_unslash((string) $_POST['ichnm_lab_slug']))
        : '';
    update_post_meta($post_id, '_ichnm_lab_slug', $lab);
    if ($lab !== '') {
        update_post_meta($post_id, '_ichnm_lab_id', 'lab-' . $lab);
    } else {
        delete_post_meta($post_id, '_ichnm_lab_id');
    }

    // Person ids may contain hyphens (kulikouskaya).
    $staff = isset($_POST['ichnm_staff_id'])
        ? sanitize_text_field(wp_unslash((string) $_POST['ichnm_staff_id']))
        : '';
    update_post_meta($post_id, '_ichnm_staff_id', $staff);

    if ($post->post_type === 'development') {
        $dir = isset($_POST['ichnm_direction_slug'])
            ? sanitize_title(wp_unslash((string) $_POST['ichnm_direction_slug']))
            : '';
        update_post_meta($post_id, '_ichnm_direction_slug', $dir);
    }

    update_post_meta($post_id, '_ichnm_editor_owned', '1');
}, 10, 2);
