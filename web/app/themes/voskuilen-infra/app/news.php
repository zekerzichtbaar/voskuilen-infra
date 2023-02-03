<?php
// Change dashboard Posts to News
function cp_change_post_object() {
	$get_post_type = get_post_type_object('post');
    $labels = $get_post_type->labels;
	// $labels->name = 'Nieuws';
	// $labels->singular_name = 'Nieuws';
	// $labels->add_new = 'Nieuw Artikel';
	// $labels->add_new_item = 'Nieuw Artikel';
	// $labels->edit_item = 'Wijzig Artikel';
	// $labels->new_item = 'Artikel';
	// $labels->view_item = 'View Nieuws';
	// $labels->search_items = 'Search Nieuws';
	// $labels->not_found = 'No Nieuws found';
	// $labels->not_found_in_trash = 'No Nieuws found in Trash';
	// $labels->all_items = 'All Nieuws';
	$labels->menu_name = 'Nieuws';
	// $labels->name_admin_bar = 'Nieuws';
}

add_action( 'init', 'cp_change_post_object' );