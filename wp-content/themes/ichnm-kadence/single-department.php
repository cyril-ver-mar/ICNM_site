<?php
/**
 * Laboratory / department pack template (preview lab-pack contour).
 */
get_header();

while (have_posts()) {
    the_post();
    $hub = function_exists('ichnm_hub_kicker') ? ichnm_hub_kicker('structure') : ['label' => 'Структура', 'href' => home_url('/structure/')];
    $lab_word = [
        'ru' => 'лаборатория',
        'en' => 'laboratory',
        'be' => 'лабараторыя',
        'zh' => '实验室',
    ];
    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
    $suffix = $lab_word[$lang] ?? $lab_word['ru'];
    $home_href = function_exists('pll_home_url') ? (string) pll_home_url() : home_url('/');
    ?>
    <main class="ichnm-single ichnm-single-lab wrap is-lab-site">
      <nav class="ichnm-crumbs" aria-label="Навигация">
        <a href="<?php echo esc_url($home_href); ?>">Главная</a>
        <span class="ichnm-crumbs-sep"> / </span>
        <a href="<?php echo esc_url($hub['href']); ?>"><?php echo esc_html($hub['label']); ?></a>
        <span class="ichnm-crumbs-sep"> / </span>
        <span aria-current="page"><?php the_title(); ?></span>
      </nav>
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($hub['href']); ?>"><?php echo esc_html($hub['label']); ?></a> · <?php echo esc_html($suffix); ?></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content page-body is-wide is-lab">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
    break;
}

get_footer();
