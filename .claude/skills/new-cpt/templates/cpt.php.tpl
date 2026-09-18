<?php
/**
 * Custom post type: {{singular}}.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers the {{post_type}} post type.
 *
 * @return void
 */
function {{prefix}}_register_cpt_{{slug}}(): void {
	$labels = array(
		'name'                  => _x( '{{plural}}', 'post type general name', '{{textdomain}}' ),
		'singular_name'         => _x( '{{singular}}', 'post type singular name', '{{textdomain}}' ),
		'add_new_item'          => __( 'Add New {{singular}}', '{{textdomain}}' ),
		'edit_item'             => __( 'Edit {{singular}}', '{{textdomain}}' ),
		'new_item'              => __( 'New {{singular}}', '{{textdomain}}' ),
		'view_item'             => __( 'View {{singular}}', '{{textdomain}}' ),
		'view_items'            => __( 'View {{plural}}', '{{textdomain}}' ),
		'search_items'          => __( 'Search {{plural}}', '{{textdomain}}' ),
		'not_found'             => __( 'No {{plural}} found.', '{{textdomain}}' ),
		'not_found_in_trash'    => __( 'No {{plural}} found in Trash.', '{{textdomain}}' ),
		'all_items'             => __( 'All {{plural}}', '{{textdomain}}' ),
		'archives'              => __( '{{singular}} Archives', '{{textdomain}}' ),
		'featured_image'        => __( 'Featured image', '{{textdomain}}' ),
		'set_featured_image'    => __( 'Set featured image', '{{textdomain}}' ),
		'remove_featured_image' => __( 'Remove featured image', '{{textdomain}}' ),
	);

	register_post_type(
		'{{post_type}}',
		array(
			'labels'          => $labels,
			'public'          => true,
			'show_in_rest'    => true, // Required for the block editor.
			'has_archive'     => true,
			'menu_position'   => 20,
			'menu_icon'       => 'dashicons-admin-post',
			'capability_type' => 'post',
			'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'rewrite'         => array(
				'slug'       => '{{rewrite_slug}}',
				'with_front' => false,
			),
			// Optional: default block layout for new posts.
			// 'template'      => array( array( 'core/paragraph' ) ),
			// 'template_lock' => false,
		)
	);

	/*
	 * Optional taxonomy. Uncomment and adapt.
	 *
	 * register_taxonomy(
	 *     '{{post_type}}_cat',
	 *     '{{post_type}}',
	 *     array(
	 *         'labels'       => array(
	 *             'name'          => _x( '{{singular}} Categories', 'taxonomy general name', '{{textdomain}}' ),
	 *             'singular_name' => _x( '{{singular}} Category', 'taxonomy singular name', '{{textdomain}}' ),
	 *         ),
	 *         'hierarchical' => true,
	 *         'public'       => true,
	 *         'show_in_rest' => true,
	 *         'rewrite'      => array( 'slug' => '{{rewrite_slug}}-category' ),
	 *     )
	 * );
	 */

	/*
	 * Optional meta field. Always set sanitize_callback and auth_callback.
	 *
	 * register_post_meta(
	 *     '{{post_type}}',
	 *     '{{prefix}}_{{slug}}_example',
	 *     array(
	 *         'type'              => 'string',
	 *         'single'            => true,
	 *         'show_in_rest'      => true,
	 *         'sanitize_callback' => 'sanitize_text_field',
	 *         'auth_callback'     => static function (): bool {
	 *             return current_user_can( 'edit_posts' );
	 *         },
	 *     )
	 * );
	 */
}
add_action( 'init', '{{prefix}}_register_cpt_{{slug}}' );

/**
 * Flushes rewrite rules once, when the theme is activated.
 *
 * @return void
 */
function {{prefix}}_flush_rewrite_{{slug}}(): void {
	{{prefix}}_register_cpt_{{slug}}();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', '{{prefix}}_flush_rewrite_{{slug}}' );
