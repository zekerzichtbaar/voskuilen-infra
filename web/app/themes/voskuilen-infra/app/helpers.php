<?php

function get_post_or_latest($id = null) {
    $args = ['post_type' => 'project', 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => 1];
    $query = new WP_Query($args);

    return $query;
}