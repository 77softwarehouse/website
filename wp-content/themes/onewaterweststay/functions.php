<?php
/**
 * Theme setup for One Water West Stay.
 *
 * @package OneWaterWestStay
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('post-thumbnails');
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'onewaterweststay-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
});

add_filter('excerpt_length', function (): int {
    return 22;
});
