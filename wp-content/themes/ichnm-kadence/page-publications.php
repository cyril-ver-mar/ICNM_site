<?php
/**
 * Publications hub: year chart (mock until official series) + lab catalogue.
 */
get_header();

$hub = function_exists('ichnm_hub_strings') ? ichnm_hub_strings() : [];

while (have_posts()) {
    the_post();
    $chart = function_exists('ichnm_publication_chart_html') ? ichnm_publication_chart_html() : '';
    ?>
    <main class="ichnm-pubs-hub wrap">
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
        <div class="ichnm-page-intro">
          <?php
          $raw = (string) get_the_content(null, false);
          $raw = preg_replace('/<!--\s*ichnm:publications-hub\s*-->/', '', $raw) ?? $raw;
          echo apply_filters('the_content', $raw);
          ?>
        </div>
      </header>

      <?php echo $chart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

      <section class="ichnm-home-band" aria-labelledby="ichnm-pubs-list">
        <div class="ichnm-band-head">
          <h2 id="ichnm-pubs-list"><?php echo esc_html($hub['pubs_labs'] ?? 'Публикации лабораторий'); ?></h2>
        </div>
        <?php
        $query = new WP_Query([
            'post_type' => 'publication',
            'posts_per_page' => 300,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        if ($query->have_posts()) :
            echo '<div class="ichnm-pub-list">';
            while ($query->have_posts()) {
                $query->the_post();
                echo '<article class="ichnm-pub-item">';
                echo '<div class="ichnm-pub-body">' . apply_filters('the_content', get_the_content()) . '</div>';
                echo '</article>';
            }
            echo '</div>';
            wp_reset_postdata();
        else :
            echo '<p>' . esc_html($hub['pubs_empty'] ?? 'Стартовый список публикаций появится после передачи каталога Институтом или снятия записей со старого ichnm.by.') . '</p>';
        endif;
        ?>
      </section>
    </main>
    <?php
}

get_footer();
