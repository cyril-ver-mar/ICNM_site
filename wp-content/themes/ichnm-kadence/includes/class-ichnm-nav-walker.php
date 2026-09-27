<?php
/**
 * Primary nav walker: nav-parent + submenu-toggle (+) for mobile accordion,
 * menu-item-has-children keeps WP classes for desktop carets.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ICNM_Nav_Walker extends Walker_Nav_Menu
{
    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0): void
    {
        $item = $data_object;
        $indent = ($depth) ? str_repeat("\t", $depth) : '';

        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        $has_children = in_array('menu-item-has-children', $classes, true);

        $class_names = implode(' ', array_map('sanitize_html_class', array_filter($classes)));
        $output .= $indent . '<li class="' . esc_attr($class_names) . '">';

        $atts = [];
        $atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
        $atts['href'] = !empty($item->url) ? $item->url : '';

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if ($value === '') {
                continue;
            }
            $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        $link = '<a' . $attributes . '>' . esc_html($title) . '</a>';

        if ($has_children) {
            $label = sprintf(
                /* translators: %s: menu item title */
                __('Подменю: %s', 'ichnm'),
                $title
            );
            $output .= '<div class="nav-parent">';
            $output .= $link;
            $output .= '<button type="button" class="submenu-toggle" aria-expanded="false" aria-label="'
                . esc_attr($label) . '"></button>';
            $output .= '</div>';
        } else {
            $output .= $link;
        }
    }
}
