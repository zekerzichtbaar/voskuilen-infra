<?php

/**
 * Theme setup.
 */

namespace App;

use function Roots\bundle;

/**
 * Register the theme assets.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    bundle('app')->enqueue();
}, 100);

/**
 * Register the theme assets with the block editor.
 *
 * @return void
 */
add_action('enqueue_block_editor_assets', function () {
    bundle('editor')->enqueue();
}, 100);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Enable features from the Soil plugin if activated.
     *
     * @link https://roots.io/plugins/soil/
     */
    add_theme_support('soil', [
        'clean-up',
        'nav-walker',
        'nice-search',
        'relative-urls',
    ]);

    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
        'secondary_navigation' => __('Secondary Navigation', 'sage'),
        'highlighted_navigation' => __('Highlighted Navigation', 'sage'),
        'footer1_navigation' => __('Footer 1 Navigation', 'sage'),
        'footer2_navigation' => __('Footer 2 Navigation', 'sage'),
        'footer3_navigation' => __('Footer 3 Navigation', 'sage'),
        'footer_policy_navigation' => __('Footer Policy Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});
/**
 * Edit query vars according to $_GET filters
 */
add_action( 'pre_get_posts', function( $query ) {
    if ( !is_admin() && $query->is_main_query() && is_archive() == 'project') {
        $args = $_GET;

        // Set search
        if(!empty($args['search'])) {
            $query->set( 's', $args['search'] );
        }

        // Set category
        if(!empty($args['cat'])) {
            $query->set( 'tax_query', [[
                'taxonomy' => 'project_category',
                'field' => 'slug',
                'terms' => array_values($args['cat'])
            ]] );
        }
    }
    return $query;
});

add_shortcode('cta', function($attributes) {
    $cta_post = get_post($attributes['post_id']);
    $output =   '<a href="'. get_permalink($cta_post->ID) .'" class="bg-offwhite md:float-left md:w-64 group text-base md:mx-20 my-8 md:-ml-12">
                    <div class="relative w-full aspect-video">'. 
                        wp_get_attachment_image( get_post_thumbnail_id($cta_post->ID), 'medium', false, ["class" => "w-full h-full my-0 absolute inset-0 object-cover object-center"] ) .
                    '</div>
                    <div class="p-8 md:p-6"><div class="mb-4 font-semibold">'.
                        $cta_post->post_title .
                        '</div><div class="flex justify-between items-center">
                            <span>Lees meer</span>
                            <svg class="group-hover:translate-x-3 h-6 duration-300 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#fff" class="w-6 h-6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                            </svg>
                        </div>
                    </div>
                </a>';

    return $output;
}); 