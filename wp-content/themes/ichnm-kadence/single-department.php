<?php
/**
 * Laboratory / department pack template.
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
    ?>
    <main class="ichnm-single ichnm-single-lab wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($hub['href']); ?>"><?php echo esc_html($hub['label']); ?></a> · <?php echo esc_html($suffix); ?></p>
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="ichnm-single-body entry-content">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
    break; // singular lab URL must never render translation siblings
}

get_footer();
