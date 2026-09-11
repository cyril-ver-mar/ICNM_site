<?php
/**
 * Laboratory / department pack template.
 */
get_header();

while (have_posts()) {
    the_post();
    $structure = get_page_by_path('structure');
    $structure_href = $structure instanceof WP_Post ? get_permalink($structure) : home_url('/structure/');
    ?>
    <main class="ichnm-single ichnm-single-lab wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($structure_href); ?>">Структура</a> · лаборатория</p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
}

get_footer();
