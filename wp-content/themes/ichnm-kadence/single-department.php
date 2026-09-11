<?php
/**
 * Laboratory / department pack template.
 */
get_header();

while (have_posts()) {
    the_post();
    $structure = function_exists('ichnm_translated_page')
        ? ichnm_translated_page('structure')
        : get_page_by_path('structure');
    $structure_href = $structure instanceof WP_Post ? get_permalink($structure) : home_url('/structure/');
    $kicker = [
        'ru' => 'Структура · лаборатория',
        'en' => 'Structure · laboratory',
        'be' => 'Структура · лабараторыя',
        'zh' => '机构设置 · 实验室',
    ];
    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'ru';
    $kicker_text = $kicker[$lang] ?? $kicker['ru'];
    ?>
    <main class="ichnm-single ichnm-single-lab wrap">
      <header class="ichnm-page-head">
        <p class="ichnm-kicker"><a href="<?php echo esc_url($structure_href); ?>"><?php echo esc_html(explode(' · ', $kicker_text)[0]); ?></a> · <?php echo esc_html(explode(' · ', $kicker_text)[1] ?? 'лаборатория'); ?></p>
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
