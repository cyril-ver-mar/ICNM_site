<?php
/**
 * Person CPT admin fields: metrics + person-owned optional sections (tickets 59–60).
 *
 * Meta is the editor source of truth after first fill. Front HTML is rebuilt from
 * meta on save and preferred over seed on content sync when meta is non-empty.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return list<string>
 */
function ichnm_person_admin_metric_fields(): array
{
    return ['orcid', 'google_scholar', 'scopus_author', 'elibrary', 'researchgate'];
}

/**
 * @return array<string, string>
 */
function ichnm_person_admin_section_fields(): array
{
    return [
        'awards' => 'Награды (по одной на строку)',
        'publications_scientific' => 'Научные публикации (по одной на строку)',
        'publications_methodical' => 'Методические публикации (по одной на строку)',
        'interests' => 'Исследовательские интересы (по одной на строку)',
        'projects' => 'Научные проекты (по одной на строку; person-owned, без sync с лабораторией)',
    ];
}

/**
 * Read admin meta into the person-array shape used by metrics/sections HTML.
 *
 * @return array{profiles: array<string, string>, bibliometrics: array<string, array<string, mixed>>, person_sections: array<string, list<string>>}
 */
function ichnm_person_meta_as_person_array(int $post_id): array
{
    $profiles = [];
    $bibliometrics = [];
    foreach (ichnm_person_admin_metric_fields() as $field) {
        $url = trim((string) get_post_meta($post_id, '_ichnm_profile_' . $field, true));
        if ($url !== '') {
            $profiles[$field] = $url;
        }
        $h = trim((string) get_post_meta($post_id, '_ichnm_h_index_' . $field, true));
        $c = trim((string) get_post_meta($post_id, '_ichnm_citations_' . $field, true));
        if ($h !== '' || $c !== '') {
            $stats = [];
            if ($h !== '') {
                $stats['h_index'] = $h;
            }
            if ($c !== '') {
                $stats['citations'] = $c;
            }
            $bibliometrics[$field] = $stats;
        }
    }

    $sections = [];
    foreach (array_keys(ichnm_person_admin_section_fields()) as $key) {
        $raw = (string) get_post_meta($post_id, '_ichnm_section_' . $key, true);
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $items[] = $line;
            }
        }
        if ($items) {
            $sections[$key] = $items;
        }
    }

    return [
        'profiles' => $profiles,
        'bibliometrics' => $bibliometrics,
        'person_sections' => $sections,
    ];
}

/**
 * Whether the editor has stored any person-owned meta yet.
 */
function ichnm_person_meta_has_editor_data(int $post_id): bool
{
    $overlay = ichnm_person_meta_as_person_array($post_id);
    return $overlay['profiles'] !== []
        || $overlay['bibliometrics'] !== []
        || $overlay['person_sections'] !== [];
}

/**
 * Prefer non-empty admin meta over seed for metrics / optional sections.
 *
 * @param array<string, mixed> $person
 * @return array<string, mixed>
 */
function ichnm_person_with_meta_overlay(array $person, int $post_id): array
{
    if ($post_id <= 0 || !ichnm_person_meta_has_editor_data($post_id)) {
        return $person;
    }
    $overlay = ichnm_person_meta_as_person_array($post_id);
    if ($overlay['profiles'] !== []) {
        $seed_profiles = is_array($person['profiles'] ?? null) ? $person['profiles'] : [];
        $person['profiles'] = array_merge($seed_profiles, $overlay['profiles']);
        if (!empty($overlay['profiles']['orcid'])) {
            $person['orcid'] = $overlay['profiles']['orcid'];
        }
    }
    if ($overlay['bibliometrics'] !== []) {
        $seed_biblio = is_array($person['bibliometrics'] ?? null) ? $person['bibliometrics'] : [];
        $person['bibliometrics'] = array_merge($seed_biblio, $overlay['bibliometrics']);
    }
    if ($overlay['person_sections'] !== []) {
        $person['person_sections'] = $overlay['person_sections'];
        foreach ($overlay['person_sections'] as $key => $items) {
            $person[$key] = $items;
        }
    }
    return $person;
}

/**
 * Seed empty meta keys from migrated person so the admin form matches the page.
 *
 * @param array<string, mixed> $person
 */
function ichnm_seed_person_meta_if_empty(int $post_id, array $person): void
{
    if ($post_id <= 0) {
        return;
    }
    $profiles = is_array($person['profiles'] ?? null) ? $person['profiles'] : [];
    if (!empty($person['orcid']) && empty($profiles['orcid'])) {
        $profiles['orcid'] = (string) $person['orcid'];
    }
    $bibliometrics = is_array($person['bibliometrics'] ?? null) ? $person['bibliometrics'] : [];
    foreach (ichnm_person_admin_metric_fields() as $field) {
        $url_key = '_ichnm_profile_' . $field;
        if ((string) get_post_meta($post_id, $url_key, true) === '') {
            $raw = (string) ($profiles[$field] ?? $person[$field] ?? '');
            if ($raw !== '') {
                update_post_meta($post_id, $url_key, $raw);
            }
        }
        $stats = is_array($bibliometrics[$field] ?? null) ? $bibliometrics[$field] : [];
        $h_key = '_ichnm_h_index_' . $field;
        $c_key = '_ichnm_citations_' . $field;
        if ((string) get_post_meta($post_id, $h_key, true) === '' && isset($stats['h_index']) && (string) $stats['h_index'] !== '') {
            update_post_meta($post_id, $h_key, (string) $stats['h_index']);
        }
        if ((string) get_post_meta($post_id, $c_key, true) === '' && isset($stats['citations']) && (string) $stats['citations'] !== '') {
            update_post_meta($post_id, $c_key, (string) $stats['citations']);
        }
    }

    $sections = is_array($person['person_sections'] ?? null) ? $person['person_sections'] : [];
    foreach (array_keys(ichnm_person_admin_section_fields()) as $key) {
        $meta_key = '_ichnm_section_' . $key;
        if ((string) get_post_meta($post_id, $meta_key, true) !== '') {
            continue;
        }
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
        if ($items) {
            update_post_meta($post_id, $meta_key, implode("\n", $items));
        }
    }
}

/**
 * Rebuild marked metrics / sections blocks in person post_content from meta.
 */
function ichnm_apply_person_meta_to_content(int $post_id): void
{
    if ($post_id <= 0 || !function_exists('ichnm_person_metrics_html')) {
        return;
    }
    $post = get_post($post_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'person') {
        return;
    }

    $overlay = ichnm_person_meta_as_person_array($post_id);
    $metrics = ichnm_person_metrics_html($overlay);
    $sections = ichnm_person_optional_sections_html($overlay);
    $metrics_block = '<!-- ichnm:person-metrics -->' . $metrics . '<!-- /ichnm:person-metrics -->';
    $sections_block = '<!-- ichnm:person-sections -->' . $sections . '<!-- /ichnm:person-sections -->';

    $content = (string) $post->post_content;
    if (preg_match('/<!-- ichnm:person-metrics -->.*?<!-- \/ichnm:person-metrics -->/s', $content)) {
        $content = (string) preg_replace(
            '/<!-- ichnm:person-metrics -->.*?<!-- \/ichnm:person-metrics -->/s',
            $metrics_block,
            $content,
            1
        );
    } else {
        $content .= $metrics_block;
    }
    if (preg_match('/<!-- ichnm:person-sections -->.*?<!-- \/ichnm:person-sections -->/s', $content)) {
        $content = (string) preg_replace(
            '/<!-- ichnm:person-sections -->.*?<!-- \/ichnm:person-sections -->/s',
            $sections_block,
            $content,
            1
        );
    } else {
        $content .= $sections_block;
    }

    // Avoid re-entering save_post_person while refreshing content.
    $GLOBALS['ichnm_person_meta_content_lock'] = true;
    wp_update_post([
        'ID' => $post_id,
        'post_content' => $content,
    ]);
    unset($GLOBALS['ichnm_person_meta_content_lock']);
}

add_action('add_meta_boxes', static function (): void {
    add_meta_box(
        'ichnm_person_metrics',
        'Наукометрия и профили',
        'ichnm_render_person_metrics_metabox',
        'person',
        'normal',
        'default'
    );
    add_meta_box(
        'ichnm_person_sections',
        'Разделы персональной страницы',
        'ichnm_render_person_sections_metabox',
        'person',
        'normal',
        'default'
    );
});

function ichnm_render_person_metrics_metabox(WP_Post $post): void
{
    wp_nonce_field('ichnm_person_meta', 'ichnm_person_meta_nonce');
    $labels = [
        'orcid' => 'ORCID (URL или id)',
        'google_scholar' => 'Google Scholar (URL)',
        'scopus_author' => 'Scopus Author (URL)',
        'elibrary' => 'eLIBRARY / РИНЦ (URL)',
        'researchgate' => 'ResearchGate (URL)',
    ];
    echo '<table class="form-table"><tbody>';
    foreach (ichnm_person_admin_metric_fields() as $field) {
        $url = (string) get_post_meta($post->ID, '_ichnm_profile_' . $field, true);
        $h = (string) get_post_meta($post->ID, '_ichnm_h_index_' . $field, true);
        $c = (string) get_post_meta($post->ID, '_ichnm_citations_' . $field, true);
        echo '<tr><th>' . esc_html($labels[$field] ?? $field) . '</th><td>';
        echo '<input type="url" class="large-text" name="ichnm_profile_' . esc_attr($field)
            . '" value="' . esc_attr($url) . '" placeholder="https://…">';
        echo '<p><label>h-индекс <input type="text" name="ichnm_h_index_' . esc_attr($field)
            . '" value="' . esc_attr($h) . '" size="6"></label> ';
        echo '<label>цитирований <input type="text" name="ichnm_citations_' . esc_attr($field)
            . '" value="' . esc_attr($c) . '" size="8"></label></p>';
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="description">Пустые сети не показываются на сайте. Числа без URL профиля тоже скрыты.</p>';
}

function ichnm_render_person_sections_metabox(WP_Post $post): void
{
    echo '<table class="form-table"><tbody>';
    foreach (ichnm_person_admin_section_fields() as $key => $label) {
        $val = (string) get_post_meta($post->ID, '_ichnm_section_' . $key, true);
        echo '<tr><th><label for="ichnm_section_' . esc_attr($key) . '">'
            . esc_html($label) . '</label></th><td>';
        echo '<textarea class="large-text" rows="4" id="ichnm_section_' . esc_attr($key)
            . '" name="ichnm_section_' . esc_attr($key) . '">' . esc_textarea($val) . '</textarea>';
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="description">Секции выводятся только если заполнены. Не синхронизируются с каталогами лабораторий.</p>';
}

add_action('save_post_person', static function (int $post_id): void {
    if (!empty($GLOBALS['ichnm_person_meta_content_lock'])) {
        return;
    }
    if (!isset($_POST['ichnm_person_meta_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['ichnm_person_meta_nonce'])), 'ichnm_person_meta')
    ) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    foreach (ichnm_person_admin_metric_fields() as $field) {
        $url_key = 'ichnm_profile_' . $field;
        $h_key = 'ichnm_h_index_' . $field;
        $c_key = 'ichnm_citations_' . $field;
        $url = isset($_POST[$url_key]) ? esc_url_raw(wp_unslash((string) $_POST[$url_key])) : '';
        $h = isset($_POST[$h_key]) ? sanitize_text_field(wp_unslash((string) $_POST[$h_key])) : '';
        $c = isset($_POST[$c_key]) ? sanitize_text_field(wp_unslash((string) $_POST[$c_key])) : '';
        update_post_meta($post_id, '_ichnm_profile_' . $field, $url);
        update_post_meta($post_id, '_ichnm_h_index_' . $field, $h);
        update_post_meta($post_id, '_ichnm_citations_' . $field, $c);
    }
    foreach (array_keys(ichnm_person_admin_section_fields()) as $key) {
        $field = 'ichnm_section_' . $key;
        $raw = isset($_POST[$field]) ? sanitize_textarea_field(wp_unslash((string) $_POST[$field])) : '';
        update_post_meta($post_id, '_ichnm_section_' . $key, $raw);
    }
    ichnm_apply_person_meta_to_content($post_id);
});
