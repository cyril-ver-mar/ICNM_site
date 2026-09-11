<?php
/**
 * Event / conference item.
 */
get_header();

while (have_posts()) {
    the_post();
    $events = get_page_by_path('events');
    $events_href = $events instanceof WP_Post ? get_permalink($events) : home_url('/events/');
    ?>
    <main class="ichnm-single ichnm-single-event wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($events_href); ?>">Мероприятия</a></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
}

get_footer();
