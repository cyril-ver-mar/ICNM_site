<?php
/**
 * Default vitrine page wrapper (Kadence child).
 */
get_header();

while (have_posts()) {
    the_post();
    $slug = (string) get_post_field('post_name', get_post());
    $wide_slugs = [
        'structure',
        'structure-en',
        'structure-be',
        'structure-zh',
        'science',
        'science-en',
        'science-be',
        'science-zh',
        'developments',
        'facilities',
        'cooperation',
        'cooperation-en',
        'cooperation-be',
        'cooperation-zh',
        /* contacts stays prose-width; city-map is max 42rem inside the column */
    ];
    $parent_id = (int) wp_get_post_parent_id(get_the_ID());
    $parent_slug = $parent_id > 0 ? (string) get_post_field('post_name', $parent_id) : '';
    $catalogue_parents = ['science', 'developments', 'facilities'];
    $is_wide = in_array($slug, $wide_slugs, true)
        || in_array($parent_slug, $catalogue_parents, true);
    $body_class = $is_wide ? 'ichnm-page-body entry-content is-wide' : 'ichnm-page-body entry-content';

    $catalogue_kickers = [
        'science' => ['label' => 'Научная деятельность', 'href' => ''],
        'developments' => ['label' => 'Научная деятельность', 'href' => ''],
        'facilities' => ['label' => 'Об институте', 'href' => home_url('/about/')],
        'cooperation' => ['label' => 'Научная деятельность', 'href' => ''],
    ];
    $kicker = $catalogue_kickers[$slug] ?? null;
    if ($kicker === null && in_array($parent_slug, $catalogue_parents, true)) {
        $parent_titles = [
            'science' => 'Направления работы',
            'developments' => 'Разработки',
            'facilities' => 'Материальная база',
        ];
        $parent_page = $parent_id > 0 ? get_post($parent_id) : null;
        $kicker = [
            'label' => $parent_titles[$parent_slug] ?? get_the_title($parent_id),
            'href' => $parent_page instanceof WP_Post ? (string) get_permalink($parent_page) : home_url('/' . $parent_slug . '/'),
        ];
    }
    ?>
    <main class="ichnm-page wrap">
      <header class="ichnm-page-head">
        <?php if (is_array($kicker) && ($kicker['label'] ?? '') !== '') : ?>
          <p class="ichnm-kicker"><?php
            if (($kicker['href'] ?? '') !== '') {
                echo '<a href="' . esc_url($kicker['href']) . '">' . esc_html($kicker['label']) . '</a>';
            } else {
                echo esc_html($kicker['label']);
            }
          ?></p>
        <?php endif; ?>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="<?php echo esc_attr($body_class); ?>">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
}

get_footer();
