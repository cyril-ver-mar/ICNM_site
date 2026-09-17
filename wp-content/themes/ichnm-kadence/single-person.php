<?php
/**
 * Personal page template (preview leader-profile contour).
 */
get_header();

while (have_posts()) {
    the_post();
    $hub = function_exists('ichnm_hub_kicker') ? ichnm_hub_kicker('leadership') : ['label' => 'Руководство', 'href' => home_url('/leadership/')];
    $content = (string) get_the_content(null, false);
    $home_href = function_exists('pll_home_url') ? (string) pll_home_url() : home_url('/');
    ?>
    <main class="ichnm-single ichnm-single-person wrap">
      <nav class="ichnm-crumbs" aria-label="Навигация">
        <a href="<?php echo esc_url($home_href); ?>">Главная</a>
        <span class="ichnm-crumbs-sep"> / </span>
        <a href="<?php echo esc_url($hub['href']); ?>"><?php echo esc_html($hub['label']); ?></a>
        <span class="ichnm-crumbs-sep"> / </span>
        <span aria-current="page"><?php the_title(); ?></span>
      </nav>
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($hub['href']); ?>"><?php echo esc_html($hub['label']); ?></a></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content page-body is-wide">
        <?php echo apply_filters('the_content', $content); ?>
      </div>
    </main>
    <?php
}

get_footer();
