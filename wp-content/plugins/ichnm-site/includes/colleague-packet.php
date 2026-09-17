<?php
/**
 * Colleague packet ingest — attach incoming/ slots into vitrines (ticket 33).
 *
 * Mirrored by src/core/colleague_packet.py (pytest). Empty files are never content.
 */

if (!defined('ABSPATH')) {
    exit;
}

const ICHNM_INCOMING_MIN_BINARY_BYTES = 64;

/**
 * @return array<string, list<string>>
 */
function ichnm_document_page_aliases(): array
{
    return [
        'charter' => ['ustav', 'charter'],
        'anti-corruption' => ['anticorruption', 'anti-corruption', 'anti_corruption'],
        'e-appeals' => ['e-appeals', 'e_appeals', 'electronic-appeals'],
    ];
}

function ichnm_incoming_dir(string $slot = ''): string
{
    $base = ichnm_source_path('incoming');
    $slot = trim($slot, '/');
    return $slot === '' ? $base : $base . '/' . $slot;
}

function ichnm_is_nonempty_content_file(string $path): bool
{
    if ($path === '' || !is_file($path)) {
        return false;
    }
    $name = basename($path);
    if ($name === '' || $name[0] === '.' || strtolower($name) === '.gitkeep') {
        return false;
    }
    $size = (int) filesize($path);
    if ($size <= 0) {
        return false;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $textish = ['csv', 'tsv', 'txt', 'json', 'svg', 'html', 'htm', 'md'];
    if (in_array($ext, $textish, true)) {
        $raw = (string) file_get_contents($path);
        return trim($raw) !== '';
    }
    return $size >= ICHNM_INCOMING_MIN_BINARY_BYTES;
}

function ichnm_incoming_stem_key(string $path): string
{
    $stem = pathinfo($path, PATHINFO_FILENAME);
    $stem = strtolower(trim(str_replace([' ', '_'], '-', $stem)));
    return $stem;
}

/**
 * @return array<string, string> page_id => absolute path
 */
function ichnm_resolve_document_attachments(): array
{
    $dir = ichnm_incoming_dir('documents');
    if (!is_dir($dir)) {
        return [];
    }
    $files = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $path = $dir . '/' . $name;
        if (ichnm_is_nonempty_content_file($path)) {
            $files[] = $path;
        }
    }
    sort($files);
    $out = [];
    foreach (ichnm_document_page_aliases() as $page_id => $aliases) {
        $alias_keys = [];
        foreach ($aliases as $alias) {
            $alias_keys[] = strtolower(str_replace([' ', '_'], '-', $alias));
        }
        foreach ($files as $path) {
            $stem = ichnm_incoming_stem_key($path);
            foreach ($alias_keys as $alias) {
                if ($stem === $alias || str_starts_with($stem, $alias . '-')) {
                    $out[$page_id] = $path;
                    break 2;
                }
            }
        }
    }
    return $out;
}

/**
 * Honest or filled PDF shelf for one document page.
 *
 * @param list<string> $labels
 */
function ichnm_document_shelf_html(string $page_id, array $labels, array $attachments): string
{
    $path = $attachments[$page_id] ?? '';
    $filled = is_string($path) && ichnm_is_nonempty_content_file($path);
    $items = '';

    if ($filled) {
        $rel = 'incoming/documents/' . basename($path);
        $href = ichnm_source_url($rel);
        $label = trim((string) ($labels[0] ?? basename($path)));
        if ($label === '') {
            $label = basename($path);
        }
        $items .= '<li class="file-slot is-filled">';
        $items .= '<a href="' . esc_url($href) . '">' . esc_html($label) . '</a>';
        $items .= '<small>Скачать</small></li>';
        foreach (array_slice($labels, 1) as $extra) {
            $extra = trim((string) $extra);
            if ($extra === '') {
                continue;
            }
            $items .= '<li class="file-slot"><span>' . esc_html($extra) . '</span>';
            $items .= '<small>Файл не загружен</small></li>';
        }
    } else {
        foreach ($labels as $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $items .= '<li class="file-slot"><span>' . esc_html($label) . '</span>';
            $items .= '<small>Файл не загружен</small></li>';
        }
    }

    if ($items === '') {
        return '';
    }
    return '<ul class="file-shelf" aria-label="Слоты для документов">' . $items . '</ul>';
}

/**
 * Re-render document child pages with attach-aware shelves (idempotent).
 */
function ichnm_attach_incoming_documents(): void
{
    $copy = ichnm_migrated_copy();
    $pages = is_array($copy['pages'] ?? null) ? $copy['pages'] : [];
    $attachments = ichnm_resolve_document_attachments();

    foreach (array_keys(ichnm_document_page_aliases()) as $slug) {
        $page = get_page_by_path($slug);
        if (!$page instanceof WP_Post) {
            continue;
        }
        $block = is_array($pages[$slug] ?? null) ? $pages[$slug] : [];
        $html = '';
        foreach ($block['paragraphs'] ?? [] as $para) {
            $para = trim((string) $para);
            if ($para === '') {
                continue;
            }
            $html .= '<p>' . esc_html($para) . '</p>';
        }
        $items = $block['list'] ?? [];
        if (is_array($items) && $items) {
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
        if ($empty !== '' && empty($attachments[$slug])) {
            $html .= '<p class="ichnm-empty-slot">' . esc_html($empty) . '</p>';
        }
        $labels = $block['file_slots'] ?? [];
        if (!is_array($labels)) {
            $labels = [];
        }
        $html .= ichnm_document_shelf_html($slug, $labels, $attachments);
        if ($html === '') {
            continue;
        }
        wp_update_post([
            'ID' => (int) $page->ID,
            'post_content' => $html,
        ]);
    }
}

/**
 * @return list<array<string, mixed>>
 */
function ichnm_load_incoming_publication_rows(): array
{
    $dir = ichnm_incoming_dir('publications');
    if (!is_dir($dir)) {
        return [];
    }
    $skip = [
        'ichnm-publications-normalized.json',
        'readme.md',
    ];
    $out = [];
    $seen = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $lower = strtolower($name);
        if (in_array($lower, $skip, true) || preg_match('/^ichnm-lab-\d+-t4\.html$/', $lower)) {
            continue;
        }
        $path = $dir . '/' . $name;
        if (!ichnm_is_nonempty_content_file($path)) {
            continue;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'tsv') {
            $rows = ichnm_parse_publication_csv($path, $ext === 'tsv' ? "\t" : ',');
        } elseif ($ext === 'json') {
            $rows = ichnm_parse_publication_json($path);
        } else {
            // xlsx/ods: convert to csv/json first (documented).
            continue;
        }
        foreach ($rows as $row) {
            $cite = trim((string) ($row['cite'] ?? ''));
            if ($cite === '') {
                continue;
            }
            $doi = trim((string) ($row['doi'] ?? ''));
            $lab = trim((string) ($row['laboratory'] ?? $row['lab_id'] ?? ''));
            $key = strtolower($lab . '|' . $cite . '|' . $doi);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }
    }
    return $out;
}

/**
 * @return list<array<string, mixed>>
 */
function ichnm_parse_publication_csv(string $path, string $delimiter = ','): array
{
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        return [];
    }
    $header = fgetcsv($fh, 0, $delimiter);
    if (!is_array($header) || !$header) {
        fclose($fh);
        return [];
    }
    $header = array_map(static fn($h) => strtolower(trim((string) $h)), $header);
    $rows = [];
    while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
        if (!is_array($data) || count(array_filter($data, static fn($v) => trim((string) $v) !== '')) === 0) {
            continue;
        }
        $raw = [];
        foreach ($header as $i => $key) {
            if ($key === '') {
                continue;
            }
            $raw[$key] = trim((string) ($data[$i] ?? ''));
        }
        $row = ichnm_normalize_incoming_publication_row($raw);
        if ($row !== null) {
            $rows[] = $row;
        }
    }
    fclose($fh);
    return $rows;
}

/**
 * @return list<array<string, mixed>>
 */
function ichnm_parse_publication_json(string $path): array
{
    $decoded = json_decode((string) file_get_contents($path), true);
    $items = [];
    if (is_array($decoded) && array_is_list($decoded)) {
        $items = $decoded;
    } elseif (is_array($decoded)) {
        foreach (['items', 'publications', 'publications_items'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                $items = $decoded[$key];
                break;
            }
        }
    }
    $rows = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $row = ichnm_normalize_incoming_publication_row($item);
        if ($row !== null) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * @param array<string, mixed> $raw
 * @return array<string, mixed>|null
 */
function ichnm_normalize_incoming_publication_row(array $raw): ?array
{
    $cite = trim((string) ($raw['cite'] ?? ''));
    if ($cite === '') {
        $bits = array_filter([
            trim((string) ($raw['authors'] ?? '')),
            trim((string) ($raw['title'] ?? '')),
            trim((string) ($raw['journal'] ?? '')),
            trim((string) ($raw['year'] ?? '')),
        ], static fn($v) => $v !== '');
        $cite = implode('. ', $bits);
    }
    if ($cite === '') {
        return null;
    }
    $row = ['cite' => $cite];
    $doi = trim((string) ($raw['doi'] ?? ''));
    if ($doi !== '') {
        $lower = strtolower($doi);
        foreach (['https://doi.org/', 'http://doi.org/', 'doi:'] as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                $doi = substr($doi, strlen($prefix));
                break;
            }
        }
        $doi = trim($doi);
        if ($doi !== '') {
            $row['doi'] = str_starts_with(strtolower($doi), 'http') ? $doi : ('https://doi.org/' . $doi);
        }
    }
    $lab = trim((string) ($raw['laboratory'] ?? $raw['lab_title'] ?? ''));
    if ($lab !== '') {
        $row['laboratory'] = $lab;
    }
    $lab_id = trim((string) ($raw['lab_id'] ?? $raw['lab_slug'] ?? ''));
    if ($lab_id !== '') {
        $row['lab_id'] = $lab_id;
        if (empty($row['lab_slug'])) {
            $row['lab_slug'] = str_starts_with($lab_id, 'lab-') ? substr($lab_id, 4) : $lab_id;
        }
    }
    if (isset($raw['year']) && (int) $raw['year'] > 0) {
        $row['year'] = (int) $raw['year'];
    }
    return $row;
}

/**
 * Idempotent CPT upsert for colleague spreadsheet rows (meta key prefix incoming-).
 */
function ichnm_import_incoming_publications(): void
{
    foreach (ichnm_load_incoming_publication_rows() as $index => $row) {
        $cite = trim((string) ($row['cite'] ?? ''));
        if ($cite === '') {
            continue;
        }
        $doi = trim((string) ($row['doi'] ?? ''));
        $lab_slug = trim((string) ($row['lab_slug'] ?? ''));
        if ($lab_slug === '' && !empty($row['lab_id'])) {
            $lab_id = (string) $row['lab_id'];
            $lab_slug = str_starts_with($lab_id, 'lab-') ? substr($lab_id, 4) : $lab_id;
        }
        $lab_title = trim((string) ($row['laboratory'] ?? $lab_slug));
        $year = (int) ($row['year'] ?? 0);
        $key = 'incoming-pub-' . md5($lab_slug . '|' . $cite . '|' . $doi);
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
        ichnm_upsert_post([
            'post_type' => 'publication',
            'post_status' => 'publish',
            'post_name' => sanitize_title(($lab_slug !== '' ? $lab_slug : 'institute') . '-incoming-pub-' . ($index + 1) . '-' . substr(md5($cite), 0, 6)),
            'post_title' => mb_substr($cite, 0, 120),
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

/**
 * @return list<array{network:string,label:string,href:string}>
 */
function ichnm_parse_incoming_icnm_social(): array
{
    $path = ichnm_incoming_dir('social') . '/urls.txt';
    if (!ichnm_is_nonempty_content_file($path)) {
        return [];
    }
    $labels = [
        'facebook' => 'Facebook',
        'vk' => 'ВКонтакте',
        'telegram' => 'Telegram',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
    ];
    $rows = [];
    $seen = [];
    foreach (preg_split('/\R/', (string) file_get_contents($path)) ?: [] as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = preg_split('/\s+/', $line, 2);
        if (!is_array($parts) || count($parts) < 2) {
            continue;
        }
        $network = strtolower(trim($parts[0]));
        $href = trim($parts[1]);
        if ($href === '' || (!str_starts_with(strtolower($href), 'http://') && !str_starts_with(strtolower($href), 'https://'))) {
            continue;
        }
        if (isset($seen[$network])) {
            continue;
        }
        $seen[$network] = true;
        $rows[] = [
            'network' => $network,
            'label' => $labels[$network] ?? ucfirst($network),
            'href' => $href,
        ];
    }
    return $rows;
}

function ichnm_apply_incoming_icnm_social(): void
{
    $rows = ichnm_parse_incoming_icnm_social();
    update_option('ichnm_incoming_icnm_social', $rows, false);
}

/**
 * @return list<array{network:string,label:string,href:string}>
 */
function ichnm_effective_icnm_social(): array
{
    $from_model = [];
    $model = function_exists('ichnm_site_model') ? ichnm_site_model() : [];
    foreach ($model['footer']['icnm_social'] ?? [] as $row) {
        if (is_array($row) && !empty($row['href'])) {
            $from_model[] = $row;
        }
    }
    $incoming = get_option('ichnm_incoming_icnm_social', []);
    if (!is_array($incoming) || !$incoming) {
        $incoming = ichnm_parse_incoming_icnm_social();
    }
    $merged = [];
    $seen = [];
    foreach (array_merge($from_model, is_array($incoming) ? $incoming : []) as $row) {
        if (!is_array($row) || empty($row['href'])) {
            continue;
        }
        $network = strtolower((string) ($row['network'] ?? $row['label'] ?? $row['href']));
        if (isset($seen[$network])) {
            continue;
        }
        $seen[$network] = true;
        $merged[] = $row;
    }
    return $merged;
}

function ichnm_resolve_nas_brand_vector(): string
{
    $dir = ichnm_incoming_dir('brand');
    if (!is_dir($dir)) {
        return '';
    }
    $candidates = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $path = $dir . '/' . $name;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['svg', 'pdf', 'ai', 'eps'], true)) {
            continue;
        }
        if (!ichnm_is_nonempty_content_file($path)) {
            continue;
        }
        if (str_contains(strtolower(pathinfo($path, PATHINFO_FILENAME)), 'ichnm')) {
            continue;
        }
        $candidates[] = $path;
    }
    if (!$candidates) {
        return '';
    }
    usort($candidates, static function (string $a, string $b): int {
        $a_nas = (str_contains(strtolower(basename($a)), 'nas') || str_contains(strtolower(basename($a)), 'nan')) ? 0 : 1;
        $b_nas = (str_contains(strtolower(basename($b)), 'nas') || str_contains(strtolower(basename($b)), 'nan')) ? 0 : 1;
        if ($a_nas !== $b_nas) {
            return $a_nas <=> $b_nas;
        }
        $a_svg = str_ends_with(strtolower($a), '.svg') ? 0 : 1;
        $b_svg = str_ends_with(strtolower($b), '.svg') ? 0 : 1;
        return $a_svg <=> $b_svg;
    });
    return $candidates[0];
}

/**
 * When a NAS vector exists in incoming/brand, copy SVG into the theme brand hook.
 */
function ichnm_apply_incoming_nas_brand(): void
{
    $src = ichnm_resolve_nas_brand_vector();
    if ($src === '' || !str_ends_with(strtolower($src), '.svg')) {
        update_option('ichnm_incoming_nas_brand', '', false);
        return;
    }
    $theme_dir = get_stylesheet_directory() . '/assets';
    if (!is_dir($theme_dir)) {
        return;
    }
    $dest = $theme_dir . '/nas-emblem.svg';
    // Theme mount is writable; assets/incoming is read-only — copy out.
    if (@copy($src, $dest)) {
        update_option('ichnm_incoming_nas_brand', $dest, false);
    }
}

/**
 * @return list<string> basenames of non-empty roster files
 */
function ichnm_list_incoming_rosters(): array
{
    $dir = ichnm_incoming_dir('rosters');
    if (!is_dir($dir)) {
        return [];
    }
    $exts = ['csv', 'tsv', 'xlsx', 'ods', 'docx', 'json'];
    $found = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $path = $dir . '/' . $name;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts, true) || !ichnm_is_nonempty_content_file($path)) {
            continue;
        }
        $found[] = $name;
    }
    sort($found);
    update_option('ichnm_incoming_rosters', $found, false);
    return $found;
}

/**
 * Apply all colleague-packet slots that have real files. Safe to run repeatedly.
 */
function ichnm_apply_colleague_packet(): void
{
    ichnm_attach_incoming_documents();
    ichnm_import_incoming_publications();
    ichnm_apply_incoming_icnm_social();
    ichnm_apply_incoming_nas_brand();
    ichnm_list_incoming_rosters();
}
