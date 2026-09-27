<?php
/**
 * Development single — crumbs to Разработки.
 */
get_header();

while (have_posts()) {
    the_post();
    $hub = get_page_by_path('developments');
    $hub_href = $hub instanceof WP_Post ? (string) get_permalink($hub) : home_url('/developments/');
    $hub_label = 'Разработки';
    $home_href = function_exists('pll_home_url') ? (string) pll_home_url() : home_url('/');
    $about = function_exists('ichnm_translated_page') ? ichnm_translated_page('about') : get_page_by_path('about');
    $about_href = $about instanceof WP_Post ? (string) get_permalink($about) : home_url('/about/');
    $content = (string) get_the_content(null, false);
    ?>
    <main class="ichnm-single ichnm-single-catalogue wrap">
      <nav class="ichnm-crumbs" aria-label="Навигация">
        <a href="<?php echo esc_url($home_href); ?>">Главная</a>
        <span class="ichnm-crumbs-sep"> / </span>
        <a href="<?php echo esc_url($about_href); ?>">Об институте</a>
        <span class="ichnm-crumbs-sep"> / </span>
        <a href="<?php echo esc_url($hub_href); ?>"><?php echo esc_html($hub_label); ?></a>
        <span class="ichnm-crumbs-sep"> / </span>
        <span aria-current="page"><?php the_title(); ?></span>
      </nav>
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($hub_href); ?>"><?php echo esc_html($hub_label); ?></a></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content page-body is-wide">
        <?php echo apply_filters('the_content', $content); ?>
      </div>
    </main>
    <?php
}

get_footer();
