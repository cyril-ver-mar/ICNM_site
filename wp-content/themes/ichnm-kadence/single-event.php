<?php
/**
 * Event / conference item.
 */
get_header();

while (have_posts()) {
    the_post();
    $hub = function_exists('ichnm_hub_kicker') ? ichnm_hub_kicker('events') : ['label' => 'Мероприятия', 'href' => home_url('/events/')];
    ?>
    <main class="ichnm-single ichnm-single-event wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($hub['href']); ?>"><?php echo esc_html($hub['label']); ?></a></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
}

get_footer();
