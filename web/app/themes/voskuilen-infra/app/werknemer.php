<?php

/**
 * Registers the `werknemer` post type.
 */
function werknemer_init() {
	register_post_type( 'werknemer', [
		'labels' => array(
			'name' => __( 'Werknemers' ),
			'singular_name' => __( 'Werknemer' ),
			'add_new' => __('Nieuwe werknemer', ' werknemers'),
			'add_new_item' => __('Nieuwe werknemer'),
			'edit_item' => __('Bewerk werknemer'),
			'new_item' => __('Nieuwe werknemer'),
			'view_item' => __('Bekijk werknemer'),
			'search_items' => __('Zoek werknemer'),
			'not_found' =>  __('Geen werknemer(en) gevonden'),
			'not_found_in_trash' => __('Geen werknemer(en) gevonden'),
			'parent_item_colon' => ''
		),
		'public' => true,
		'has_archive' => true,
		'public' => true,
		'show_ui' => true,
		'query_var' => true,
		'capability_type' => 'post',
		'hierarchical' => true,
		'show_in_rest' => true,
		'supports' => array('title','thumbnail','revisions', 'editor', 'page-attributes'),
		'rewrite' => array( 'slug' => 'medewerker', 'with_front' => false ),
		'menu_icon' => 'dashicons-groups',
		
	]);

	register_taxonomy( 'werknemer_category', ['werknemer'], [
		'hierarchical' => true,
		'show_in_rest' => true,
		'show_admin_column' => true,
		'labels' => [
			'name' => 'Categorieën',
			'add_new_item' => 'Nieuwe categorie'
		],
	]);
	register_taxonomy_for_object_type('werknemer_category', 'werknemer');
}

add_action( 'init', 'werknemer_init' );