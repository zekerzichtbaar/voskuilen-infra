<?php

/**
 * Registers the `project` post type.
 */
function project_init() {
	register_post_type(
		'project',
		[
			'labels'                => [
				'name'                  => __( 'Projects', 'voskuilen-infra' ),
				'singular_name'         => __( 'Project', 'voskuilen-infra' ),
				'all_items'             => __( 'All Projects', 'voskuilen-infra' ),
				'archives'              => __( 'Project Archives', 'voskuilen-infra' ),
				'attributes'            => __( 'Project Attributes', 'voskuilen-infra' ),
				'insert_into_item'      => __( 'Insert into Project', 'voskuilen-infra' ),
				'uploaded_to_this_item' => __( 'Uploaded to this Project', 'voskuilen-infra' ),
				'featured_image'        => _x( 'Featured Image', 'project', 'voskuilen-infra' ),
				'set_featured_image'    => _x( 'Set featured image', 'project', 'voskuilen-infra' ),
				'remove_featured_image' => _x( 'Remove featured image', 'project', 'voskuilen-infra' ),
				'use_featured_image'    => _x( 'Use as featured image', 'project', 'voskuilen-infra' ),
				'filter_items_list'     => __( 'Filter Projects list', 'voskuilen-infra' ),
				'items_list_navigation' => __( 'Projects list navigation', 'voskuilen-infra' ),
				'items_list'            => __( 'Projects list', 'voskuilen-infra' ),
				'new_item'              => __( 'New Project', 'voskuilen-infra' ),
				'add_new'               => __( 'Add New', 'voskuilen-infra' ),
				'add_new_item'          => __( 'Add New Project', 'voskuilen-infra' ),
				'edit_item'             => __( 'Edit Project', 'voskuilen-infra' ),
				'view_item'             => __( 'View Project', 'voskuilen-infra' ),
				'view_items'            => __( 'View Projects', 'voskuilen-infra' ),
				'search_items'          => __( 'Search Projects', 'voskuilen-infra' ),
				'not_found'             => __( 'No Projects found', 'voskuilen-infra' ),
				'not_found_in_trash'    => __( 'No Projects found in trash', 'voskuilen-infra' ),
				'parent_item_colon'     => __( 'Parent Project:', 'voskuilen-infra' ),
				'menu_name'             => __( 'Projects', 'voskuilen-infra' ),
			],
			'public'                => true,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_nav_menus'     => true,
			'supports'              => [ 'title', 'editor', 'thumbnail' ],
			'has_archive'           => true,
			'rewrite'               => true,
			'query_var'             => true,
			'menu_position'         => null,
			'menu_icon'             => 'dashicons-admin-post',
			'show_in_rest'          => true,
			'rest_base'             => 'project',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		]
	);

}

add_action( 'init', 'project_init' );

/**
 * Sets the post updated messages for the `project` post type.
 *
 * @param  array $messages Post updated messages.
 * @return array Messages for the `project` post type.
 */
function project_updated_messages( $messages ) {
	global $post;

	$permalink = get_permalink( $post );

	$messages['project'] = [
		0  => '', // Unused. Messages start at index 1.
		/* translators: %s: post permalink */
		1  => sprintf( __( 'Project updated. <a target="_blank" href="%s">View Project</a>', 'voskuilen-infra' ), esc_url( $permalink ) ),
		2  => __( 'Custom field updated.', 'voskuilen-infra' ),
		3  => __( 'Custom field deleted.', 'voskuilen-infra' ),
		4  => __( 'Project updated.', 'voskuilen-infra' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Project restored to revision from %s', 'voskuilen-infra' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		/* translators: %s: post permalink */
		6  => sprintf( __( 'Project published. <a href="%s">View Project</a>', 'voskuilen-infra' ), esc_url( $permalink ) ),
		7  => __( 'Project saved.', 'voskuilen-infra' ),
		/* translators: %s: post permalink */
		8  => sprintf( __( 'Project submitted. <a target="_blank" href="%s">Preview Project</a>', 'voskuilen-infra' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		/* translators: 1: Publish box date format, see https://secure.php.net/date 2: Post permalink */
		9  => sprintf( __( 'Project scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Project</a>', 'voskuilen-infra' ), date_i18n( __( 'M j, Y @ G:i', 'voskuilen-infra' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
		/* translators: %s: post permalink */
		10 => sprintf( __( 'Project draft updated. <a target="_blank" href="%s">Preview Project</a>', 'voskuilen-infra' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	];

	return $messages;
}

add_filter( 'post_updated_messages', 'project_updated_messages' );

/**
 * Sets the bulk post updated messages for the `project` post type.
 *
 * @param  array $bulk_messages Arrays of messages, each keyed by the corresponding post type. Messages are
 *                              keyed with 'updated', 'locked', 'deleted', 'trashed', and 'untrashed'.
 * @param  int[] $bulk_counts   Array of item counts for each message, used to build internationalized strings.
 * @return array Bulk messages for the `project` post type.
 */
function project_bulk_updated_messages( $bulk_messages, $bulk_counts ) {
	global $post;

	$bulk_messages['project'] = [
		/* translators: %s: Number of Projects. */
		'updated'   => _n( '%s Project updated.', '%s Projects updated.', $bulk_counts['updated'], 'voskuilen-infra' ),
		'locked'    => ( 1 === $bulk_counts['locked'] ) ? __( '1 Project not updated, somebody is editing it.', 'voskuilen-infra' ) :
						/* translators: %s: Number of Projects. */
						_n( '%s Project not updated, somebody is editing it.', '%s Projects not updated, somebody is editing them.', $bulk_counts['locked'], 'voskuilen-infra' ),
		/* translators: %s: Number of Projects. */
		'deleted'   => _n( '%s Project permanently deleted.', '%s Projects permanently deleted.', $bulk_counts['deleted'], 'voskuilen-infra' ),
		/* translators: %s: Number of Projects. */
		'trashed'   => _n( '%s Project moved to the Trash.', '%s Projects moved to the Trash.', $bulk_counts['trashed'], 'voskuilen-infra' ),
		/* translators: %s: Number of Projects. */
		'untrashed' => _n( '%s Project restored from the Trash.', '%s Projects restored from the Trash.', $bulk_counts['untrashed'], 'voskuilen-infra' ),
	];

	return $bulk_messages;
}

add_filter( 'bulk_post_updated_messages', 'project_bulk_updated_messages', 10, 2 );
