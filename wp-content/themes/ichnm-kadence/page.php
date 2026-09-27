<?php
/**
 * Default vitrine page wrapper (Kadence child).
 */
get_header();

while (have_posts()) {
    the_post();
    $slug = (string) get_post_field('post_name', get_post());
    $base = function_exists('ichnm_page_hub_base') ? ichnm_page_hub_base(get_post()) : $slug;
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
        'leadership',
        'documents',
        'scientific-council',
        /* contacts stays prose-width; city-map is max 42rem inside the column */
    ];
    $parent_id = (int) wp_get_post_parent_id(get_the_ID());
    $parent_slug = $parent_id > 0 ? (string) get_post_field('post_name', $parent_id) : '';
    $catalogue_parents = ['science', 'developments', 'facilities'];
    $is_wide = in_array($slug, $wide_slugs, true)
        || in_array($base, ['structure', 'science', 'developments', 'facilities', 'cooperation', 'leadership', 'documents', 'scientific-council'], true)
        || in_array($parent_slug, $catalogue_parents, true);
    $body_class = $is_wide ? 'ichnm-page-body entry-content is-wide' : 'ichnm-page-body entry-content';

    $catalogue_kickers = [
        'science' => ['label' => 'Научная деятельность', 'href' => ''],
        'developments' => ['label' => 'Научная деятельность', 'href' => ''],
        // Facilities: crumbs already show About — no duplicate kicker (ticket 78).
        'facilities' => null,
        'cooperation' => ['label' => 'Научная деятельность', 'href' => ''],
    ];
    $kicker = array_key_exists($slug, $catalogue_kickers)
        ? $catalogue_kickers[$slug]
        : ($catalogue_kickers[$base] ?? null);
    if ($kicker === null && !array_key_exists($slug, $catalogue_kickers) && !array_key_exists($base, $catalogue_kickers)
        && in_array($parent_slug, $catalogue_parents, true)) {
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

    $home_href = function_exists('pll_home_url') ? (string) pll_home_url() : home_url('/');
    $about = function_exists('ichnm_translated_page') ? ichnm_translated_page('about') : get_page_by_path('about');
    $about_href = $about instanceof WP_Post ? (string) get_permalink($about) : home_url('/about/');
    $about_branch = [
        'about', 'leadership', 'structure', 'science', 'developments', 'cooperation',
        'publications', 'education', 'scientific-council', 'facilities', 'documents',
        'vacancies', 'charter', 'anti-corruption', 'e-appeals', 'hr', 'labor-protection',
        'engineering', 'accounting', 'union', 'young-scientists', 'aspirantura', 'doctorate',
        'defense-council', 'internships', 'for-staff', 'pvtr', 'ethics', 'personal-data',
        'video-surveillance', 'collective-agreement', 'thin-film-department',
    ];
    $students_branch = ['for-students', 'student-nir', 'graduate-employment'];
    $show_crumbs = in_array($base, $about_branch, true) || in_array($parent_slug, $catalogue_parents, true)
        || in_array($base, ['contacts', 'news', 'events', 'aist'], true)
        || in_array($base, $students_branch, true);
    ?>
    <main class="ichnm-page wrap">
      <?php if ($show_crumbs) : ?>
      <nav class="ichnm-crumbs" aria-label="Навигация">
        <a href="<?php echo esc_url($home_href); ?>">Главная</a>
        <?php if ($base !== 'about' && in_array($base, $about_branch, true)) : ?>
          <span class="ichnm-crumbs-sep"> / </span>
          <a href="<?php echo esc_url($about_href); ?>">Об институте</a>
        <?php endif; ?>
        <?php if ($base === 'structure' || in_array($base, ['hr', 'labor-protection', 'engineering', 'accounting', 'union', 'young-scientists'], true)) : ?>
          <?php if ($base !== 'structure') : ?>
            <span class="ichnm-crumbs-sep"> / </span>
            <?php
            $structure = function_exists('ichnm_translated_page') ? ichnm_translated_page('structure') : get_page_by_path('structure');
            $structure_href = $structure instanceof WP_Post ? (string) get_permalink($structure) : home_url('/structure/');
            ?>
            <a href="<?php echo esc_url($structure_href); ?>">Структура</a>
          <?php endif; ?>
        <?php endif; ?>
        <?php if (in_array($base, ['student-nir', 'graduate-employment'], true)) : ?>
          <span class="ichnm-crumbs-sep"> / </span>
          <?php
            $students = function_exists('ichnm_translated_page') ? ichnm_translated_page('for-students') : get_page_by_path('for-students');
            $students_href = $students instanceof WP_Post ? (string) get_permalink($students) : home_url('/for-students/');
          ?>
          <a href="<?php echo esc_url($students_href); ?>">Для студентов</a>
        <?php endif; ?>
        <span class="ichnm-crumbs-sep"> / </span>
        <span aria-current="page"><?php the_title(); ?></span>
      </nav>
      <?php endif; ?>
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
