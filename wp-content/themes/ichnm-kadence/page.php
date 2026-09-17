<?php
/**
 * Default vitrine page wrapper (Kadence child).
 */
get_header();

while (have_posts()) {
    the_post();
    $slug = (string) get_post_field('post_name', get_post());
    $wide_slugs = [
        'structure',
        'structure-en',
        'structure-be',
        'structure-zh',
        'science',
        'science-en',
        'science-be',
        'science-zh',
        'developments',
        'facilities',
        'cooperation',
        'cooperation-en',
        'cooperation-be',
        'cooperation-zh',
        /* contacts stays prose-width; city-map is max 42rem inside the column */
    ];
    $parent_id = (int) wp_get_post_parent_id(get_the_ID());
    $parent_slug = $parent_id > 0 ? (string) get_post_field('post_name', $parent_id) : '';
    $catalogue_parents = ['science', 'developments', 'facilities'];
    $is_wide = in_array($slug, $wide_slugs, true)
        || in_array($parent_slug, $catalogue_parents, true);
    $body_class = $is_wide ? 'ichnm-page-body entry-content is-wide' : 'ichnm-page-body entry-content';
    ?>
    <main class="ichnm-page wrap">
      <header class="ichnm-page-head">
        <h1><?php the_title(); ?></h1>
      </header>
      <div class="<?php echo esc_attr($body_class); ?>">
        <?php the_content(); ?>
      </div>
    </main>
    <?php
}

get_footer();
