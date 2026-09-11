<?php
/**
 * «СМИ о нас» item.
 */
get_header();

while (have_posts()) {
    the_post();
    $hub = function_exists('ichnm_hub_kicker') ? ichnm_hub_kicker('media_about') : ['label' => 'СМИ о нас', 'href' => home_url('/news/')];
    ?>
    <main class="ichnm-single ichnm-single-media wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($hub['href']); ?>#ichnm-news-media"><?php echo esc_html($hub['label']); ?></a></p>
        <h1><?php the_title(); ?></h1>
        <p class="ichnm-single-meta"><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time></p>
      </header>
      <div class="ichnm-single-body entry-content">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
}

get_footer();
