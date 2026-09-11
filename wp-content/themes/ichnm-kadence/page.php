<?php
/**
 * Default vitrine page wrapper (Kadence child).
 */
get_header();

while (have_posts()) {
    the_post();
    ?>
    <main class="ichnm-page wrap">
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-page-body entry-content">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
}

get_footer();
