<?php
/**
 * Publications hub: year groups + stats sidebar (ticket 48).
 */
get_header();

$hub = function_exists('ichnm_hub_strings') ? ichnm_hub_strings() : [];
$home_href = function_exists('pll_home_url') ? (string) pll_home_url() : home_url('/');
$about = function_exists('ichnm_translated_page') ? ichnm_translated_page('about') : get_page_by_path('about');
$about_href = $about instanceof WP_Post ? (string) get_permalink($about) : home_url('/about/');

while (have_posts()) {
    the_post();
    $chart = function_exists('ichnm_publication_chart_html') ? ichnm_publication_chart_html() : '';

    $query = new WP_Query([
        'post_type' => 'publication',
        'posts_per_page' => 300,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    $rows = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $year = (int) get_post_meta(get_the_ID(), '_ichnm_year', true);
            if ($year < 1900) {
                $year = (int) get_the_date('Y');
            }
            $rows[] = [
                'year' => $year,
                'id' => get_the_ID(),
                'html' => '<article class="ichnm-pub-item">'
                    . '<div class="ichnm-pub-body">' . apply_filters('the_content', get_the_content()) . '</div>'
                    . '</article>',
            ];
        }
        wp_reset_postdata();
    }
    $groups = function_exists('ichnm_publications_group_by_year')
        ? ichnm_publications_group_by_year($rows)
        : [];
    ?>
    <main class="ichnm-pubs-hub wrap">
      <nav class="ichnm-crumbs" aria-label="Навигация">
        <a href="<?php echo esc_url($home_href); ?>">Главная</a>
        <span class="ichnm-crumbs-sep"> / </span>
        <a href="<?php echo esc_url($about_href); ?>">Об институте</a>
        <span class="ichnm-crumbs-sep"> / </span>
        <span aria-current="page"><?php the_title(); ?></span>
      </nav>
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
      </header>

      <div class="ichnm-pubs-layout">
        <div class="ichnm-pubs-main">
          <?php if ($groups) : ?>
            <?php foreach ($groups as $year => $items) : ?>
              <?php
              $label = ((int) $year > 0) ? (string) $year : (string) ($hub['pubs_other'] ?? 'Без года');
              $anchor = ((int) $year > 0) ? 'year-' . (int) $year : 'year-other';
              ?>
              <section class="pub-year" id="<?php echo esc_attr($anchor); ?>">
                <h2><?php echo esc_html($label); ?></h2>
                <div class="ichnm-pub-list">
                  <?php
                  foreach ($items as $item) {
                      echo $item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                  }
                  ?>
                </div>
              </section>
            <?php endforeach; ?>
          <?php else : ?>
            <p><?php echo esc_html($hub['pubs_empty'] ?? 'Стартовый список публикаций появится после передачи каталога Институтом или снятия записей со старого ichnm.by.'); ?></p>
          <?php endif; ?>
        </div>
        <aside class="ichnm-pubs-sidebar" aria-label="<?php echo esc_attr($hub['articles_by_year'] ?? 'Статьи по годам'); ?>">
          <span class="ichnm-lab-plaque" aria-hidden="true"></span>
          <?php echo $chart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </aside>
      </div>
    </main>
    <?php
}

get_footer();
