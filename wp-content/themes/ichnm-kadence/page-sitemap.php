<?php
/**
 * Nested public sitemap (NAS-class utility page).
 */
get_header();

while (have_posts()) {
    the_post();
    ?>
    <main class="ichnm-sitemap-hub wrap">
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
        <div class="ichnm-page-intro">
          <?php
          $raw = (string) get_the_content(null, false);
          $raw = preg_replace('/<!--\s*ichnm:sitemap-hub\s*-->/', '', $raw) ?? $raw;
          echo apply_filters('the_content', $raw);
          ?>
        </div>
      </header>
      <?php
      if (function_exists('ichnm_sitemap_html')) {
          echo ichnm_sitemap_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      }
      ?>
    </main>
    <?php
}

get_footer();
