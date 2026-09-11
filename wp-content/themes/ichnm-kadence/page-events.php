<?php
/**
 * Events hub: upcoming banner + conference / institute events.
 */
get_header();

while (have_posts()) {
    the_post();
    $next = function_exists('ichnm_home_copy') ? (ichnm_home_copy()['next_event'] ?? []) : [];
    $aist = get_page_by_path('aist');
    ?>
    <main class="ichnm-events-hub wrap">
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
        <div class="ichnm-page-intro">
          <?php
          $raw = (string) get_the_content(null, false);
          $raw = preg_replace('/<!--\s*ichnm:events-hub\s*-->/', '', $raw) ?? $raw;
          echo apply_filters('the_content', $raw);
          ?>
        </div>
      </header>

      <?php if (!empty($next['title'])) : ?>
        <section class="ichnm-home-band ichnm-next-event" data-ichnm-block="next_event">
          <h2>Ближайшее мероприятие</h2>
          <div class="ichnm-event-banner">
            <h3><?php echo esc_html((string) $next['title']); ?></h3>
            <?php if (!empty($next['when'])) : ?>
              <p class="ichnm-event-when"><?php echo esc_html((string) $next['when']); ?></p>
            <?php endif; ?>
            <p class="ichnm-event-actions">
              <?php if (!empty($next['href'])) : ?>
                <a class="ichnm-pill ichnm-pill-primary" href="<?php echo esc_url((string) $next['href']); ?>">Регистрация / сайт серии</a>
              <?php endif; ?>
              <?php if ($aist instanceof WP_Post) : ?>
                <a class="ichnm-pill" href="<?php echo esc_url(get_permalink($aist)); ?>">Раздел AIST</a>
              <?php endif; ?>
            </p>
          </div>
        </section>
      <?php endif; ?>

      <section class="ichnm-home-band" aria-labelledby="ichnm-events-list">
        <div class="ichnm-band-head">
          <h2 id="ichnm-events-list">Календарь и архив</h2>
        </div>
        <?php
        $query = new WP_Query([
            'post_type' => 'event',
            'posts_per_page' => 30,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        if ($query->have_posts()) :
            echo '<div class="ichnm-news-grid">';
            while ($query->have_posts()) {
                $query->the_post();
                echo '<article class="ichnm-news-card">';
                echo '<time datetime="' . esc_attr(get_the_date('c')) . '">' . esc_html(get_the_date('Y')) . '</time>';
                echo '<h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
                echo '</article>';
            }
            echo '</div>';
            wp_reset_postdata();
        else :
            echo '<p>Мероприятия появятся после публикации календаря.</p>';
        endif;
        ?>
      </section>
    </main>
    <?php
}

get_footer();
