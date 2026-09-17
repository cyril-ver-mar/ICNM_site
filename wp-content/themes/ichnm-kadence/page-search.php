<?php
/**
 * Institute search: people, units, facilities, developments.
 */
get_header();

$q = isset($_GET['q']) ? sanitize_text_field(wp_unslash((string) $_GET['q'])) : '';
$hits = ($q !== '' && function_exists('ichnm_search_query')) ? ichnm_search_query($q) : [];
$hub = function_exists('ichnm_hub_strings') ? ichnm_hub_strings() : [];
$ui = function_exists('ichnm_chrome_strings') ? ichnm_chrome_strings() : [];
$labels = [
    'person' => $hub['search_kind_person'] ?? 'Персоналии',
    'unit' => $hub['search_kind_unit'] ?? 'Подразделения',
    'facility' => $hub['search_kind_facility'] ?? 'Приборы',
    'development' => $hub['search_kind_development'] ?? 'Разработки',
];

while (have_posts()) {
    the_post();
    ?>
    <main class="ichnm-search-hub wrap">
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
        <div class="ichnm-page-intro">
          <?php
          $raw = (string) get_the_content(null, false);
          $raw = preg_replace('/<!--\s*ichnm:search-hub\s*-->/', '', $raw) ?? $raw;
          echo apply_filters('the_content', $raw);
          ?>
        </div>
      </header>

      <form class="ichnm-search-form is-page" action="<?php echo esc_url(get_permalink()); ?>" method="get" role="search">
        <label class="screen-reader-text" for="ichnm-q-page"><?php echo esc_html($hub['search_query'] ?? 'Запрос'); ?></label>
        <input id="ichnm-q-page" name="q" type="search" value="<?php echo esc_attr($q); ?>" placeholder="<?php echo esc_attr($hub['search_placeholder'] ?? 'Фамилия, лаборатория, прибор, разработка'); ?>" required minlength="2">
        <button type="submit" class="ichnm-pill ichnm-pill-primary"><?php echo esc_html($ui['find'] ?? 'Найти'); ?></button>
      </form>

      <div class="ichnm-search-results">
        <?php if ($q !== '' && mb_strlen($q) < 2) : ?>
          <p><?php echo esc_html($hub['search_min'] ?? 'Введите не меньше двух букв.'); ?></p>
        <?php elseif ($q !== '' && !$hits) : ?>
          <p><?php echo esc_html($hub['search_empty'] ?? 'Ничего не найдено. Попробуйте фамилию, лабораторию, прибор или разработку.'); ?></p>
        <?php elseif ($hits) : ?>
          <?php
          $grouped = [];
          foreach ($hits as $hit) {
              $grouped[$hit['type']][] = $hit;
          }
          foreach ($grouped as $type => $group) :
              ?>
            <section class="ichnm-search-group">
              <h2><?php echo esc_html($labels[$type] ?? $type); ?></h2>
              <ul>
                <?php foreach ($group as $hit) : ?>
                  <li>
                    <a href="<?php echo esc_url($hit['href']); ?>"><?php echo esc_html($hit['title']); ?></a>
                    <?php if ($hit['meta'] !== '') : ?>
                      <small><?php echo esc_html(mb_substr(wp_strip_all_tags($hit['meta']), 0, 120)); ?></small>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
    <?php
}

get_footer();
