<?php
/**
 * Events hub: upcoming banner + conference / institute events.
 */
get_header();

$hub = function_exists('ichnm_hub_strings') ? ichnm_hub_strings() : [];

while (have_posts()) {
    the_post();
    $next = function_exists('ichnm_home_copy') ? (ichnm_home_copy()['next_event'] ?? []) : [];
    $aist = function_exists('ichnm_translated_page')
        ? ichnm_translated_page('aist')
        : get_page_by_path('aist');
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
          <h2><?php echo esc_html($hub['next_event'] ?? 'Ближайшее мероприятие'); ?></h2>
          <div class="ichnm-event-banner">
            <h3><?php echo esc_html((string) $next['title']); ?></h3>
            <?php if (!empty($next['when'])) : ?>
              <p class="ichnm-event-when"><?php echo esc_html((string) $next['when']); ?></p>
            <?php endif; ?>
            <p class="ichnm-event-actions">
              <?php if (!empty($next['href'])) : ?>
                <a class="ichnm-pill ichnm-pill-primary" href="<?php echo esc_url((string) $next['href']); ?>"><?php echo esc_html($hub['event_register'] ?? 'Регистрация / сайт серии'); ?></a>
              <?php endif; ?>
              <?php if ($aist instanceof WP_Post) : ?>
                <a class="ichnm-pill" href="<?php echo esc_url(get_permalink($aist)); ?>"><?php echo esc_html($hub['aist_section'] ?? 'Раздел AIST'); ?></a>
              <?php endif; ?>
            </p>
          </div>
        </section>
      <?php endif; ?>

      <section class="ichnm-home-band" aria-labelledby="ichnm-events-list">
        <div class="ichnm-band-head">
          <h2 id="ichnm-events-list"><?php echo esc_html($hub['events_calendar'] ?? 'Календарь и архив'); ?></h2>
        </div>
        <?php
        $copy = function_exists('ichnm_migrated_copy') ? ichnm_migrated_copy() : [];
        $conferences = is_array($copy['conferences'] ?? null) ? $copy['conferences'] : [];
        if ($conferences && function_exists('ichnm_conference_archive_card_html')) {
            $aist_rows = [];
            $other_rows = [];
            foreach ($conferences as $conf) {
                if (!is_array($conf) || empty($conf['slug'])) {
                    continue;
                }
                if (($conf['kind'] ?? '') === 'aist') {
                    $aist_rows[] = $conf;
                } else {
                    $other_rows[] = $conf;
                }
            }
            if ($aist_rows) {
                echo '<h3>' . esc_html($hub['aist_archive'] ?? 'Архив AIST') . '</h3>';
                echo '<div class="ichnm-card-grid">';
                foreach ($aist_rows as $conf) {
                    echo ichnm_conference_archive_card_html($conf);
                }
                echo '</div>';
            }
            if ($other_rows) {
                echo '<h3>' . esc_html($hub['other_conferences'] ?? 'Другие конференции Института') . '</h3>';
                echo '<div class="ichnm-card-grid">';
                foreach ($other_rows as $conf) {
                    echo ichnm_conference_archive_card_html($conf);
                }
                echo '</div>';
            }
        } else {
            $query = new WP_Query([
                'post_type' => 'event',
                'posts_per_page' => 30,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            if ($query->have_posts()) {
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
            } else {
                echo '<p>' . esc_html($hub['events_empty'] ?? 'Мероприятия появятся после публикации календаря.') . '</p>';
            }
        }
        ?>
      </section>
    </main>
    <?php
}

get_footer();
