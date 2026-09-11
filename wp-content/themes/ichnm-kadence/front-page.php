<?php
/**
 * Homepage shell aligned with the HTML preview contour (not a Kadence pixel clone).
 */
get_header();

$model = function_exists('ichnm_site_model') ? ichnm_site_model() : [];
$copy = function_exists('ichnm_home_copy') ? ichnm_home_copy() : [];
$identity = $model['identity'] ?? [];
$short = (string) ($identity['short_name'] ?? 'ИХНМ НАН Беларуси');
$intro = (string) ($copy['home_intro'] ?? '');
if ($intro === '') {
    $intro = (string) ($identity['legal_name'] ?? $short);
}
$founded = (int) ($copy['founded_year'] ?? 1998);
$years = max(1, (int) gmdate('Y') - $founded);
$labs_count = (int) ($copy['labs_count'] ?? 0);
$news = is_array($copy['news'] ?? null) ? $copy['news'] : [];
$feed = is_array($copy['feed'] ?? null) ? $copy['feed'] : $news;
$event = is_array($copy['next_event'] ?? null) ? $copy['next_event'] : [];
$structure_teaser = (string) ($copy['structure_teaser'] ?? '');
$developments_teaser = (string) ($copy['developments_teaser'] ?? '');

$about = get_page_by_path('about-overview') ?: get_page_by_path('about');
$structure = get_page_by_path('structure');
$developments = get_page_by_path('developments');
$feedback = get_page_by_path('feedback');
$events_page = get_page_by_path('events');
$events_archive = $events_page instanceof WP_Post
    ? (string) get_permalink($events_page)
    : (get_post_type_archive_link('event') ?: home_url('/events/'));
$news_page = get_page_by_path('news');
$news_archive = $news_page instanceof WP_Post
    ? (string) get_permalink($news_page)
    : (get_post_type_archive_link('news') ?: home_url('/news/'));

$href = static function (?WP_Post $page, string $fallback = '#'): string {
    return $page instanceof WP_Post ? (string) get_permalink($page) : $fallback;
};
?>
<main class="ichnm-home">
  <section class="ichnm-hero" data-ichnm-block="official_intro">
    <div class="ichnm-hero-inner wrap">
      <p class="ichnm-hero-kicker">Национальная академия наук Беларуси</p>
      <h1 class="ichnm-hero-title"><?php echo esc_html($short); ?></h1>
      <p class="ichnm-hero-lead"><?php echo esc_html($intro); ?></p>
      <div class="ichnm-hero-pills">
        <a class="ichnm-pill ichnm-pill-primary" href="<?php echo esc_url($href($about)); ?>">Сведения</a>
        <a class="ichnm-pill" href="<?php echo esc_url($href($structure)); ?>">Структура</a>
        <a class="ichnm-pill" href="<?php echo esc_url($href($developments)); ?>">Разработки</a>
        <a class="ichnm-pill" href="<?php echo esc_url($href($feedback, home_url('/feedback/'))); ?>">Написать нам</a>
      </div>
    </div>
  </section>

  <section class="ichnm-stats" aria-label="Краткие сведения">
    <div class="wrap ichnm-stats-grid">
      <div class="ichnm-stat">
        <span class="ichnm-stat-value"><?php echo esc_html((string) $years); ?></span>
        <span class="ichnm-stat-label">лет институту (с <?php echo esc_html((string) $founded); ?>)</span>
      </div>
      <div class="ichnm-stat">
        <span class="ichnm-stat-value"><?php echo esc_html((string) $labs_count); ?></span>
        <span class="ichnm-stat-label">лабораторий в структуре сайта</span>
      </div>
      <div class="ichnm-stat">
        <span class="ichnm-stat-value">4</span>
        <span class="ichnm-stat-label">языковых контура структуры</span>
      </div>
    </div>
  </section>

  <section class="ichnm-home-band" data-ichnm-block="news">
    <div class="wrap">
      <div class="ichnm-band-head">
        <h2>Актуальные новости и СМИ о нас</h2>
        <a class="ichnm-band-more" href="<?php echo esc_url($news_archive); ?>">Все новости</a>
      </div>
      <?php if ($feed) : ?>
        <div class="ichnm-news-grid">
          <?php foreach ($feed as $item) : ?>
            <?php
            $permalink = (string) ($item['permalink'] ?? '');
            $title = (string) ($item['title'] ?? '');
            $kind = (string) ($item['kind_label'] ?? '');
            $external = (($item['kind'] ?? '') === 'media' && $permalink !== '' && !str_contains($permalink, home_url()));
            ?>
            <article class="ichnm-news-card">
              <p class="ichnm-news-kind"><?php echo esc_html($kind !== '' ? $kind : 'Новость'); ?></p>
              <time datetime="<?php echo esc_attr((string) ($item['date'] ?? '')); ?>">
                <?php echo esc_html((string) ($item['date_label'] ?? $item['date'] ?? '')); ?>
              </time>
              <h3>
                <?php if ($permalink !== '') : ?>
                  <a href="<?php echo esc_url($permalink); ?>"<?php echo $external ? ' rel="noopener noreferrer"' : ''; ?>>
                    <?php echo esc_html($title); ?>
                  </a>
                <?php else : ?>
                  <?php echo esc_html($title); ?>
                <?php endif; ?>
              </h3>
              <?php if (!empty($item['outlet'])) : ?>
                <p class="ichnm-news-outlet"><?php echo esc_html((string) $item['outlet']); ?></p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p>Новости появятся после переноса ленты в WordPress.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="ichnm-home-band ichnm-home-entries">
    <div class="wrap ichnm-entry-grid">
      <article class="ichnm-entry-card" data-ichnm-block="structure_entry">
        <h2>Структура</h2>
        <p><?php echo esc_html($structure_teaser !== '' ? $structure_teaser : 'Подразделения института.'); ?></p>
        <a class="ichnm-pill" href="<?php echo esc_url($href($structure)); ?>">Открыть структуру</a>
      </article>
      <article class="ichnm-entry-card" data-ichnm-block="developments_entry">
        <h2>Разработки</h2>
        <p><?php echo esc_html($developments_teaser !== '' ? $developments_teaser : 'Продукты и методики лабораторий.'); ?></p>
        <a class="ichnm-pill" href="<?php echo esc_url($href($developments)); ?>">К разработкам</a>
      </article>
    </div>
  </section>

  <section class="ichnm-home-band ichnm-next-event" data-ichnm-block="next_event">
    <div class="wrap">
      <h2>Ближайшее мероприятие</h2>
      <?php if ($event) : ?>
        <div class="ichnm-event-banner">
          <h3><?php echo esc_html((string) ($event['title'] ?? '')); ?></h3>
          <p class="ichnm-event-when"><?php echo esc_html((string) ($event['when'] ?? '')); ?></p>
          <p class="ichnm-event-actions">
            <?php if (!empty($event['href'])) : ?>
              <a class="ichnm-pill ichnm-pill-primary" href="<?php echo esc_url((string) $event['href']); ?>">Регистрация / сайт серии</a>
            <?php endif; ?>
            <a class="ichnm-pill" href="<?php echo esc_url($events_archive); ?>">Все мероприятия</a>
          </p>
        </div>
      <?php else : ?>
        <p>Ближайшее мероприятие будет указано после обновления календаря.</p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
get_footer();
