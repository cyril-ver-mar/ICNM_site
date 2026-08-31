<?php
/**
 * Homepage shell from the site model. Kadence can wrap this; blocks stay in locked order.
 */
get_header();

$model = function_exists('ichnm_site_model') ? ichnm_site_model() : [];
$blocks = $model['homepage']['blocks'] ?? [];
$labels = [
    'official_intro' => 'Об институте',
    'news' => 'Новости',
    'structure_entry' => 'Структура',
    'developments_entry' => 'Разработки',
    'next_event' => 'Ближайшее мероприятие',
];

echo '<main class="ichnm-home">';
foreach ($blocks as $block) {
    echo '<section class="ichnm-home-block" data-ichnm-block="' . esc_attr($block) . '">';
    echo '<h2>' . esc_html($labels[$block] ?? $block) . '</h2>';
    if ($block === 'official_intro') {
        echo '<p>' . esc_html($model['identity']['legal_name'] ?? '') . '</p>';
        echo '<p>Ведущие исследования в области тонкоплёночных и наноструктурированных органических материалов, композитов и синтеза новых органических соединений.</p>';
    } else {
        echo '<p>Плейсхолдер блока главной. Контент подставится из лент и витринных страниц.</p>';
    }
    echo '</section>';
}
echo '</main>';

get_footer();
