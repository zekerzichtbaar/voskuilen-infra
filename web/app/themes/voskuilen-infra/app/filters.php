<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Yoast Breadcrumbd.
 */
add_filter('wpseo_breadcrumb_separator', function() {
    return '<svg class="px-2 mb-0.5" height="18" width="auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
  ';
});
add_filter( 'wpseo_breadcrumb_links', function($links) {
  $links[0]['text'] = '<svg class="text-primary -mt-1" height="22" width="auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>';
  return $links;
});

/**
 * Add attributes to pagination links.
 */
add_filter('next_posts_link_attributes', function() {
  return 'class="p-3 border-2 border-primary text-primary hover:text-white hover:bg-primary duration-150"';
});
add_filter('previous_posts_link_attributes', function() {
  return 'class="p-3 border-2 border-primary text-primary hover:text-white hover:bg-primary duration-150"';
});
