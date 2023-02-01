<?php

function get_post_or_latest($id = null, $offset = null, $post_type = 'post') {
    if($id == null) {
        $args = ['post_type' => $post_type, 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => 1, 'offset' => $offset];
    } else {
        $args = ['post_type' => $post_type, 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => 1, 'p' => $id];
    }
    $query = new WP_Query($args);

    return $query;
}