<?php
/**
 * Personal page template.
 */
get_header();

while (have_posts()) {
    the_post();
    $role = '';
    $content = (string) get_the_content(null, false);
    ?>
    <main class="ichnm-single ichnm-single-person wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url(home_url('/leadership/')); ?>">Руководство и персоналии</a></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content">
        <?php echo apply_filters('the_content', $content); ?>
      </div>
    </main>
    <?php
}

get_footer();
