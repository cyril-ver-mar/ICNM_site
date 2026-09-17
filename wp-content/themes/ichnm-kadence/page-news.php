<?php
/**
 * Combined news hub: institute news + «СМИ о нас».
 */
get_header();

$hub = function_exists('ichnm_hub_strings') ? ichnm_hub_strings() : [];

while (have_posts()) {
    the_post();
    ?>
    <main class="ichnm-news-hub wrap">
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
        <div class="ichnm-page-intro">
          <?php
          $raw = (string) get_the_content(null, false);
          $raw = preg_replace('/<!--\s*ichnm:news-hub\s*-->/', '', $raw) ?? $raw;
          echo apply_filters('the_content', $raw);
          ?>
        </div>
      </header>

      <nav class="section-jump" aria-label="На этой странице">
        <a href="#ichnm-news-institute"><?php echo esc_html($hub['institute_news'] ?? 'Новости Института'); ?></a>
        <a href="#ichnm-news-media"><?php echo esc_html($hub['media_about'] ?? 'СМИ о нас'); ?></a>
      </nav>

      <section class="ichnm-home-band" aria-labelledby="ichnm-news-institute">
        <div class="ichnm-band-head">
          <h2 id="ichnm-news-institute"><?php echo esc_html($hub['institute_news'] ?? 'Новости Института'); ?></h2>
          <a class="ichnm-band-more" href="#ichnm-news-institute"><?php echo esc_html($hub['to_feed'] ?? 'К ленте'); ?></a>
        </div>
        <?php
        $institute = new WP_Query([
            'post_type' => 'news',
            'posts_per_page' => 12,
            'post_status' => 'publish',
        ]);
        if ($institute->have_posts()) :
            echo '<div class="ichnm-news-grid">';
            while ($institute->have_posts()) {
                $institute->the_post();
                echo '<article class="ichnm-news-card">';
                echo '<time datetime="' . esc_attr(get_the_date('c')) . '">' . esc_html(get_the_date()) . '</time>';
                echo '<h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
                echo '</article>';
            }
            echo '</div>';
            wp_reset_postdata();
        else :
            echo '<p>' . esc_html($hub['news_empty'] ?? 'Новости Института появятся после публикации ленты.') . '</p>';
        endif;
        ?>
      </section>

      <section class="ichnm-home-band" aria-labelledby="ichnm-news-media">
        <div class="ichnm-band-head">
          <h2 id="ichnm-news-media"><?php echo esc_html($hub['media_about'] ?? 'СМИ о нас'); ?></h2>
          <a class="ichnm-band-more" href="<?php echo esc_url(get_post_type_archive_link('media_about') ?: home_url('/media-about/')); ?>"><?php echo esc_html($hub['all_media'] ?? 'Все материалы'); ?></a>
        </div>
        <?php
        $media = new WP_Query([
            'post_type' => 'media_about',
            'posts_per_page' => 12,
            'post_status' => 'publish',
        ]);
        if ($media->have_posts()) :
            echo '<div class="ichnm-news-grid">';
            while ($media->have_posts()) {
                $media->the_post();
                echo '<article class="ichnm-news-card">';
                echo '<time datetime="' . esc_attr(get_the_date('c')) . '">' . esc_html(get_the_date()) . '</time>';
                echo '<h3><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
                echo '</article>';
            }
            echo '</div>';
            wp_reset_postdata();
        else :
            echo '<p>' . esc_html($hub['media_empty'] ?? 'Публикации СМИ появятся после переноса карточек.') . '</p>';
        endif;
        ?>
      </section>
    </main>
    <?php
}

get_footer();
