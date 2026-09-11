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

$about = function_exists('ichnm_translated_page')
    ? (ichnm_translated_page('about-overview') ?: ichnm_translated_page('about'))
    : (get_page_by_path('about-overview') ?: get_page_by_path('about'));
$structure = function_exists('ichnm_translated_page') ? ichnm_translated_page('structure') : get_page_by_path('structure');
$developments = function_exists('ichnm_translated_page') ? ichnm_translated_page('developments') : get_page_by_path('developments');
$feedback = function_exists('ichnm_translated_page') ? ichnm_translated_page('feedback') : get_page_by_path('feedback');
$events_page = function_exists('ichnm_translated_page') ? ichnm_translated_page('events') : get_page_by_path('events');
$events_archive = $events_page instanceof WP_Post
    ? (string) get_permalink($events_page)
    : (get_post_type_archive_link('event') ?: home_url('/events/'));
$news_page = function_exists('ichnm_translated_page') ? ichnm_translated_page('news') : get_page_by_path('news');
$news_archive = $news_page instanceof WP_Post
    ? (string) get_permalink($news_page)
    : (get_post_type_archive_link('news') ?: home_url('/news/'));

$href = static function (?WP_Post $page, string $fallback = '#'): string {
    return $page instanceof WP_Post ? (string) get_permalink($page) : $fallback;
};

$ui = function_exists('ichnm_chrome_strings') ? ichnm_chrome_strings() : [];
$home_ui = [
    'ru' => [
        'kicker' => 'Национальная академия наук Беларуси',
        'about' => 'Сведения',
        'structure' => 'Структура',
        'developments' => 'Разработки',
        'write' => 'Написать нам',
        'stats' => 'Краткие сведения',
        'years' => 'лет институту (с %s)',
        'labs' => 'лабораторий в структуре сайта',
        'langs' => 'языковых контура структуры',
        'news' => 'Актуальные новости и СМИ о нас',
        'all_news' => 'Все новости',
        'kind_fallback' => 'Новость',
        'news_empty' => 'Новости появятся после переноса ленты в WordPress.',
        'structure_h' => 'Структура',
        'structure_teaser' => 'Подразделения института.',
        'structure_cta' => 'Открыть структуру',
        'developments_h' => 'Разработки',
        'developments_teaser' => 'Продукты и методики лабораторий.',
        'developments_cta' => 'К разработкам',
        'event_h' => 'Ближайшее мероприятие',
        'event_register' => 'Регистрация / сайт серии',
        'event_all' => 'Все мероприятия',
        'event_empty' => 'Ближайшее мероприятие будет указано после обновления календаря.',
    ],
    'en' => [
        'kicker' => 'National Academy of Sciences of Belarus',
        'about' => 'Overview',
        'structure' => 'Structure',
        'developments' => 'Developments',
        'write' => 'Contact us',
        'stats' => 'At a glance',
        'years' => 'years since %s',
        'labs' => 'laboratories on the site',
        'langs' => 'structure language shells',
        'news' => 'News and media about us',
        'all_news' => 'All news',
        'kind_fallback' => 'News',
        'news_empty' => 'News will appear after the feed is migrated into WordPress.',
        'structure_h' => 'Structure',
        'structure_teaser' => 'Institute units and laboratories.',
        'structure_cta' => 'Open structure',
        'developments_h' => 'Developments',
        'developments_teaser' => 'Products and methods from the labs.',
        'developments_cta' => 'Browse developments',
        'event_h' => 'Next event',
        'event_register' => 'Registration / series site',
        'event_all' => 'All events',
        'event_empty' => 'The next event will appear after the calendar is updated.',
    ],
    'be' => [
        'kicker' => 'Нацыянальная акадэмія навук Беларусі',
        'about' => 'Звесткі',
        'structure' => 'Структура',
        'developments' => 'Распрацоўкі',
        'write' => 'Напісаць нам',
        'stats' => 'Кароткія звесткі',
        'years' => 'гадоў інстытуту (з %s)',
        'labs' => 'лабараторый у структуры сайта',
        'langs' => 'моўных контураў структуры',
        'news' => 'Актуальныя навіны і СМІ пра нас',
        'all_news' => 'Усе навіны',
        'kind_fallback' => 'Навіна',
        'news_empty' => 'Навіны з’явяцца пасля пераносу стужкі ў WordPress.',
        'structure_h' => 'Структура',
        'structure_teaser' => 'Падраздзяленні інстытута.',
        'structure_cta' => 'Адкрыць структуру',
        'developments_h' => 'Распрацоўкі',
        'developments_teaser' => 'Прадукты і методыкі лабараторый.',
        'developments_cta' => 'Да распрацовак',
        'event_h' => 'Бліжэйшае мерапрыемства',
        'event_register' => 'Рэгістрацыя / сайт серыі',
        'event_all' => 'Усе мерапрыемствы',
        'event_empty' => 'Бліжэйшае мерапрыемства з’явіцца пасля абнаўлення календара.',
    ],
    'zh' => [
        'kicker' => '白俄罗斯国家科学院',
        'about' => '概况',
        'structure' => '机构设置',
        'developments' => '研发成果',
        'write' => '联系我们',
        'stats' => '概况数字',
        'years' => '自 %s 年建所',
        'labs' => '网站结构中的实验室',
        'langs' => '结构语言壳层',
        'news' => '新闻与媒体报道',
        'all_news' => '全部新闻',
        'kind_fallback' => '新闻',
        'news_empty' => '新闻将在迁入 WordPress 后显示。',
        'structure_h' => '机构设置',
        'structure_teaser' => '研究所下属单位与实验室。',
        'structure_cta' => '打开机构设置',
        'developments_h' => '研发成果',
        'developments_teaser' => '实验室的产品与方法。',
        'developments_cta' => '查看研发成果',
        'event_h' => '近期活动',
        'event_register' => '注册 / 系列网站',
        'event_all' => '全部活动',
        'event_empty' => '日历更新后将显示近期活动。',
    ],
];
$lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
$t = $home_ui[$lang] ?? $home_ui['ru'];
?>
<main class="ichnm-home">
  <section class="ichnm-hero" data-ichnm-block="official_intro">
    <div class="ichnm-hero-inner wrap">
      <p class="ichnm-hero-kicker"><?php echo esc_html($t['kicker']); ?></p>
      <h1 class="ichnm-hero-title"><?php echo esc_html($short); ?></h1>
      <p class="ichnm-hero-lead"><?php echo esc_html($intro); ?></p>
      <div class="ichnm-hero-pills">
        <a class="ichnm-pill ichnm-pill-primary" href="<?php echo esc_url($href($about)); ?>"><?php echo esc_html($t['about']); ?></a>
        <a class="ichnm-pill" href="<?php echo esc_url($href($structure)); ?>"><?php echo esc_html($t['structure']); ?></a>
        <a class="ichnm-pill" href="<?php echo esc_url($href($developments)); ?>"><?php echo esc_html($t['developments']); ?></a>
        <a class="ichnm-pill" href="<?php echo esc_url($href($feedback, home_url('/feedback/'))); ?>"><?php echo esc_html($t['write']); ?></a>
      </div>
    </div>
  </section>

  <section class="ichnm-stats" aria-label="<?php echo esc_attr($t['stats']); ?>">
    <div class="wrap ichnm-stats-grid">
      <div class="ichnm-stat">
        <span class="ichnm-stat-value"><?php echo esc_html((string) $years); ?></span>
        <span class="ichnm-stat-label"><?php echo esc_html(sprintf($t['years'], (string) $founded)); ?></span>
      </div>
      <div class="ichnm-stat">
        <span class="ichnm-stat-value"><?php echo esc_html((string) $labs_count); ?></span>
        <span class="ichnm-stat-label"><?php echo esc_html($t['labs']); ?></span>
      </div>
      <div class="ichnm-stat">
        <span class="ichnm-stat-value">4</span>
        <span class="ichnm-stat-label"><?php echo esc_html($t['langs']); ?></span>
      </div>
    </div>
  </section>

  <section class="ichnm-home-band" data-ichnm-block="news">
    <div class="wrap">
      <div class="ichnm-band-head">
        <h2><?php echo esc_html($t['news']); ?></h2>
        <a class="ichnm-band-more" href="<?php echo esc_url($news_archive); ?>"><?php echo esc_html($t['all_news']); ?></a>
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
              <p class="ichnm-news-kind"><?php echo esc_html($kind !== '' ? $kind : $t['kind_fallback']); ?></p>
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
        <p><?php echo esc_html($t['news_empty']); ?></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="ichnm-home-band ichnm-home-entries">
    <div class="wrap ichnm-entry-grid">
      <article class="ichnm-entry-card" data-ichnm-block="structure_entry">
        <h2><?php echo esc_html($t['structure_h']); ?></h2>
        <p><?php echo esc_html($structure_teaser !== '' ? $structure_teaser : $t['structure_teaser']); ?></p>
        <a class="ichnm-pill" href="<?php echo esc_url($href($structure)); ?>"><?php echo esc_html($t['structure_cta']); ?></a>
      </article>
      <article class="ichnm-entry-card" data-ichnm-block="developments_entry">
        <h2><?php echo esc_html($t['developments_h']); ?></h2>
        <p><?php echo esc_html($developments_teaser !== '' ? $developments_teaser : $t['developments_teaser']); ?></p>
        <a class="ichnm-pill" href="<?php echo esc_url($href($developments)); ?>"><?php echo esc_html($t['developments_cta']); ?></a>
      </article>
    </div>
  </section>

  <section class="ichnm-home-band ichnm-next-event" data-ichnm-block="next_event">
    <div class="wrap">
      <h2><?php echo esc_html($t['event_h']); ?></h2>
      <?php if ($event) : ?>
        <div class="ichnm-event-banner">
          <h3><?php echo esc_html((string) ($event['title'] ?? '')); ?></h3>
          <p class="ichnm-event-when"><?php echo esc_html((string) ($event['when'] ?? '')); ?></p>
          <p class="ichnm-event-actions">
            <?php if (!empty($event['href'])) : ?>
              <a class="ichnm-pill ichnm-pill-primary" href="<?php echo esc_url((string) $event['href']); ?>"><?php echo esc_html($t['event_register']); ?></a>
            <?php endif; ?>
            <a class="ichnm-pill" href="<?php echo esc_url($events_archive); ?>"><?php echo esc_html($t['event_all']); ?></a>
          </p>
        </div>
      <?php else : ?>
        <p><?php echo esc_html($t['event_empty']); ?></p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
get_footer();
