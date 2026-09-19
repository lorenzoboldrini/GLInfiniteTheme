<?php
/**
 * Runtime registration of the configured entities and taxonomies.
 *
 * Nothing is registered from PHP constants: the saved configuration (see
 * config.php) is read on `init` and turned into register_post_type() and
 * register_taxonomy() calls.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Builds the full label set of an entity post type.
 *
 * @param array<string, mixed> $entity Entity (see config.php).
 * @return array<string, string>
 */
function glinf_get_entity_post_type_labels( array $entity ): array {
	$singular = $entity['singular'];
	$plural   = $entity['plural'];

	return array(
		'name'                     => $plural,
		'singular_name'            => $singular,
		'menu_name'                => $plural,
		'name_admin_bar'           => $singular,
		'add_new'                  => __( 'Add New', 'gl-infinite-theme' ),
		/* translators: %s: singular name of the entity, e.g. "Event". */
		'add_new_item'             => sprintf( __( 'Add New %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'edit_item'                => sprintf( __( 'Edit %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'new_item'                 => sprintf( __( 'New %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'view_item'                => sprintf( __( 'View %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: plural name of the entity, e.g. "Events". */
		'view_items'               => sprintf( __( 'View %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the entity. */
		'search_items'             => sprintf( __( 'Search %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the entity. */
		'not_found'                => sprintf( __( 'No %s found.', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the entity. */
		'not_found_in_trash'       => sprintf( __( 'No %s found in Trash.', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the entity. */
		'all_items'                => sprintf( __( 'All %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: singular name of the entity. */
		'archives'                 => sprintf( __( '%s Archives', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'attributes'               => sprintf( __( '%s Attributes', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'insert_into_item'         => sprintf( __( 'Insert into %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'uploaded_to_this_item'    => sprintf( __( 'Uploaded to this %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: plural name of the entity. */
		'filter_items_list'        => sprintf( __( 'Filter %s list', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the entity. */
		'items_list_navigation'    => sprintf( __( '%s list navigation', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the entity. */
		'items_list'               => sprintf( __( '%s list', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: singular name of the entity. */
		'item_published'           => sprintf( __( '%s published.', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'item_published_privately' => sprintf( __( '%s published privately.', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'item_reverted_to_draft'   => sprintf( __( '%s reverted to draft.', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'item_trashed'             => sprintf( __( '%s trashed.', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'item_scheduled'           => sprintf( __( '%s scheduled.', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the entity. */
		'item_updated'             => sprintf( __( '%s updated.', 'gl-infinite-theme' ), $singular ),
	);
}

/**
 * Builds the full label set of a taxonomy.
 *
 * @param array<string, mixed> $taxonomy Taxonomy (see config.php).
 * @return array<string, string>
 */
function glinf_get_taxonomy_labels( array $taxonomy ): array {
	$singular = $taxonomy['singular'];
	$plural   = $taxonomy['plural'];

	return array(
		'name'                       => $plural,
		'singular_name'              => $singular,
		'menu_name'                  => $plural,
		/* translators: %s: plural name of the taxonomy, e.g. "Genres". */
		'search_items'               => sprintf( __( 'Search %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'popular_items'              => sprintf( __( 'Popular %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'all_items'                  => sprintf( __( 'All %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: singular name of the taxonomy, e.g. "Genre". */
		'parent_item'                => sprintf( __( 'Parent %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the taxonomy. */
		'parent_item_colon'          => sprintf( __( 'Parent %s:', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the taxonomy. */
		'edit_item'                  => sprintf( __( 'Edit %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the taxonomy. */
		'view_item'                  => sprintf( __( 'View %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the taxonomy. */
		'update_item'                => sprintf( __( 'Update %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the taxonomy. */
		'add_new_item'               => sprintf( __( 'Add New %s', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: singular name of the taxonomy. */
		'new_item_name'              => sprintf( __( 'New %s Name', 'gl-infinite-theme' ), $singular ),
		/* translators: %s: plural name of the taxonomy. */
		'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'not_found'                  => sprintf( __( 'No %s found.', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'no_terms'                   => sprintf( __( 'No %s', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'items_list_navigation'      => sprintf( __( '%s list navigation', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'items_list'                 => sprintf( __( '%s list', 'gl-infinite-theme' ), $plural ),
		/* translators: %s: plural name of the taxonomy. */
		'back_to_items'              => sprintf( __( '&larr; Go to %s', 'gl-infinite-theme' ), $plural ),
	);
}

/**
 * Registers the post type of one entity.
 *
 * The post type does not list its taxonomies: the association is declared on
 * the taxonomy side (see glinf_register_taxonomy()), which is where it is stored.
 *
 * @param array<string, mixed> $entity Normalized entity (see glinf_get_entities()).
 * @return void
 */
function glinf_register_entity( array $entity ): void {
	register_post_type(
		glinf_entity_post_type( $entity['slug'] ),
		array(
			'labels'          => glinf_get_entity_post_type_labels( $entity ),
			'public'          => true,
			// Required by the block editor.
			'show_in_rest'    => true,
			'has_archive'     => $entity['has_archive'],
			'menu_icon'       => $entity['icon'],
			// An empty list would make core fall back to title + editor: "false" means no features.
			'supports'        => array() === $entity['supports'] ? false : $entity['supports'],
			'capability_type' => 'post',
			'rewrite'         => array(
				'slug'       => glinf_entity_rewrite_slug( $entity['slug'] ),
				'with_front' => false,
			),
		)
	);
}

/**
 * Registers one taxonomy for the entities it is attached to.
 *
 * The taxonomy is registered with the full list of post types, so one code path
 * serves "specific" (one entity) and "shared" (several) taxonomies alike. A
 * taxonomy attached to no entity is not registered at all: its terms stay in the
 * database and come back when it is attached again.
 *
 * @param array<string, mixed> $taxonomy Normalized taxonomy (see glinf_get_taxonomies()).
 * @return void
 */
function glinf_register_taxonomy( array $taxonomy ): void {
	if ( array() === $taxonomy['entities'] ) {
		return;
	}

	$post_types = array_map( 'glinf_entity_post_type', $taxonomy['entities'] );

	register_taxonomy(
		glinf_taxonomy_key( $taxonomy['slug'] ),
		$post_types,
		array(
			'labels'            => glinf_get_taxonomy_labels( $taxonomy ),
			'public'            => true,
			'hierarchical'      => $taxonomy['hierarchical'],
			// Required by the block editor, which reads terms through the REST API.
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				// A flat segment ("/genre/rock/") cannot be mistaken for a single post URL.
				'slug'         => glinf_taxonomy_rewrite_slug( $taxonomy['slug'] ),
				'with_front'   => false,
				'hierarchical' => $taxonomy['hierarchical'],
			),
		)
	);
}

/**
 * Registers every configured entity, then every taxonomy.
 *
 * Post types go first so that, whichever code reacts to the taxonomy being
 * registered (`registered_taxonomy`, REST controllers, plugins), the object
 * types it is attached to already exist. register_taxonomy() receives the post
 * types directly: the alternative, listing the taxonomies in register_post_type(),
 * requires the taxonomy to be registered BEFORE the post type and silently
 * ignores it otherwise, which is fragile once a taxonomy can serve several
 * post types.
 *
 * @return void
 */
function glinf_register_entities(): void {
	$config = glinf_get_config();

	foreach ( $config['items'] as $entity ) {
		glinf_register_entity( $entity );
	}

	foreach ( $config['taxonomies'] as $taxonomy ) {
		glinf_register_taxonomy( $taxonomy );
	}
}
add_action( 'init', 'glinf_register_entities' );

/**
 * Flushes the rewrite rules once after the configuration changed.
 *
 * Exception to the "flush only on theme switch" rule, on purpose: a new or
 * renamed archive URL does not work until the rules are rebuilt. The flag is
 * set on save/delete only, and cleared BEFORE flushing so a failure cannot make
 * the flush repeat on every request. Hooked to `wp_loaded`, after every `init` callback,
 * so post types registered by plugins are part of the rules. On theme activation the
 * core already flushes (check_theme_switched()), so no extra flush is needed there.
 *
 * @return void
 */
function glinf_entities_maybe_flush_rewrite_rules(): void {
	if ( 1 !== (int) get_option( GLINF_ENTITIES_FLUSH_OPTION, 0 ) ) {
		return;
	}

	update_option( GLINF_ENTITIES_FLUSH_OPTION, 0, true );
	flush_rewrite_rules( false );
}
add_action( 'wp_loaded', 'glinf_entities_maybe_flush_rewrite_rules' );
