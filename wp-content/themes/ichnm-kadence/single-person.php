<?php
/**
 * Personal page template.
 */
get_header();

while (have_posts()) {
    the_post();
    $hub = function_exists('ichnm_hub_kicker') ? ichnm_hub_kicker('leadership') : ['label' => 'Руководство', 'href' => home_url('/leadership/')];
    $content = (string) get_the_content(null, false);
    ?>
    <main class="ichnm-single ichnm-single-person wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($hub['href']); ?>"><?php echo esc_html($hub['label']); ?></a></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content">
        <?php echo apply_filters('the_content', $content); ?>
      </div>
    </main>
    <?php
}

get_footer();
