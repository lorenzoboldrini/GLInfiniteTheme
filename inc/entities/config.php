<?php
/**
 * Entity Manager configuration: storage, defaults, sanitization and validation.
 *
 * An "entity" is a custom post type; a "taxonomy" is an independent object that
 * can be attached to one entity ("specific") or to several ("shared"). Both are
 * configured from wp-admin and registered at runtime (see register.php).
 * Everything is stored in ONE autoloaded option so that reading it on every
 * `init` costs no extra query. Every read goes through a normalizer, so even a
 * tampered option can never inject anything that this file would not have
 * produced itself.
 *
 * The entity <-> taxonomy association lives ONLY on the taxonomy side
 * (`entities`): a single source of truth, so it can never be contradictory.
 *
 * Stored shape (version 2):
 *
 *     array(
 *         'version'    => 2,
 *         'items'      => array(
 *             '<slug>' => array(
 *                 'slug'        => string,   // ^[a-z][a-z0-9_]{1,12}[a-z0-9]$ (3-14 chars)
 *                 'singular'    => string,   // 1-40 chars
 *                 'plural'      => string,   // 1-40 chars
 *                 'icon'        => string,   // whitelisted dashicon
 *                 'supports'    => string[], // whitelisted post type features
 *                 'has_archive' => bool,
 *             ),
 *         ),
 *         'taxonomies' => array(
 *             '<slug>' => array(
 *                 'slug'         => string,   // ^[a-z][a-z0-9_]{1,24}[a-z0-9]$ (3-26 chars), registered as "glinf_<slug>"
 *                 'singular'     => string,   // 1-40 chars
 *                 'plural'       => string,   // 1-40 chars
 *                 'hierarchical' => bool,
 *                 'entities'     => string[], // slugs of existing entities
 *             ),
 *         ),
 *     )
 *
 * Version 1 stored the taxonomies inside each entity: it is migrated on read
 * (see glinf_migrate_v1_config()) and written back as version 2 on the next save.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** Option holding the whole configuration (autoloaded). */
const GLINF_ENTITIES_OPTION = 'glinf_entities';

/**
 * Option used as "rewrite rules need a flush" flag.
 *
 * It is autoloaded and never deleted (its value toggles 0/1): a missing option
 * would cost one database query on every request, an autoloaded one costs none.
 */
const GLINF_ENTITIES_FLUSH_OPTION = 'glinf_entities_flush';

/** Version of the stored structure. */
const GLINF_ENTITIES_VERSION = 2;

/** Maximum number of entities (keeps the autoloaded option small). */
const GLINF_ENTITIES_MAX = 20;

/** Maximum number of taxonomies in total (keeps the autoloaded option small). */
const GLINF_TAXONOMIES_MAX = 20;

/** Maximum length of a singular/plural name (entity or taxonomy). */
const GLINF_NAME_MAX = 40;

/** Prefix of every entity post type. Its length counts against the 20-character post type limit. */
const GLINF_POST_TYPE_PREFIX = 'glinf_';

/** Prefix of every taxonomy key. Its length counts against the 32-character taxonomy limit. */
const GLINF_TAXONOMY_PREFIX = 'glinf_';

/**
 * Entity slug format: 3 to 14 characters (post type is GLINF_POST_TYPE_PREFIX + slug,
 * max 20), starts with a letter, ends with a letter or digit, lowercase only.
 */
const GLINF_ENTITY_SLUG_REGEX = '/^[a-z][a-z0-9_]{1,12}[a-z0-9]\z/';

/**
 * Taxonomy slug format: 3 to 26 characters (taxonomy key is GLINF_TAXONOMY_PREFIX + slug,
 * max 32), starts with a letter, ends with a letter or digit, lowercase only.
 */
const GLINF_TAXONOMY_SLUG_REGEX = '/^[a-z][a-z0-9_]{1,24}[a-z0-9]\z/';

/** Icon used when none (or an invalid one) is chosen. */
const GLINF_ENTITY_DEFAULT_ICON = 'dashicons-admin-post';

/**
 * Returns the post type name of an entity.
 *
 * @param string $slug Entity slug.
 * @return string
 */
function glinf_entity_post_type( string $slug ): string {
	return GLINF_POST_TYPE_PREFIX . $slug;
}

/**
 * Returns the archive/single URL segment of an entity.
 *
 * @param string $slug Entity slug.
 * @return string
 */
function glinf_entity_rewrite_slug( string $slug ): string {
	return str_replace( '_', '-', $slug );
}

/**
 * Returns the registered key of a taxonomy.
 *
 * @param string $slug Taxonomy slug.
 * @return string
 */
function glinf_taxonomy_key( string $slug ): string {
	return GLINF_TAXONOMY_PREFIX . $slug;
}

/**
 * Returns the term archive URL segment of a taxonomy.
 *
 * Same rule as the entities: the segment is the slug with underscores turned into hyphens.
 *
 * @param string $slug Taxonomy slug.
 * @return string
 */
function glinf_taxonomy_rewrite_slug( string $slug ): string {
	return glinf_entity_rewrite_slug( $slug );
}

/**
 * Returns the post type features an entity may enable (whitelist).
 *
 * @return string[]
 */
function glinf_get_entity_supports_whitelist(): array {
	return array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments' );
}

/**
 * Returns the features enabled by default on a new entity.
 *
 * @return string[]
 */
function glinf_get_entity_default_supports(): array {
	return array( 'title', 'editor', 'thumbnail', 'excerpt' );
}

/**
 * Returns the curated list of dashicons an entity may use (whitelist).
 *
 * Human readable labels live in admin.php: this list only drives validation.
 *
 * @return string[]
 */
function glinf_get_entity_icons(): array {
	return array(
		'dashicons-admin-post',
		'dashicons-admin-page',
		'dashicons-admin-media',
		'dashicons-admin-comments',
		'dashicons-admin-users',
		'dashicons-admin-tools',
		'dashicons-admin-home',
		'dashicons-admin-links',
		'dashicons-book',
		'dashicons-book-alt',
		'dashicons-calendar-alt',
		'dashicons-camera',
		'dashicons-cart',
		'dashicons-clipboard',
		'dashicons-megaphone',
		'dashicons-store',
		'dashicons-tickets-alt',
		'dashicons-location',
		'dashicons-portfolio',
		'dashicons-star-filled',
		'dashicons-heart',
		'dashicons-awards',
		'dashicons-businessman',
		'dashicons-groups',
		'dashicons-building',
		'dashicons-food',
		'dashicons-video-alt2',
		'dashicons-images-alt2',
		'dashicons-products',
		'dashicons-testimonial',
		'dashicons-lightbulb',
		'dashicons-art',
		'dashicons-hammer',
		'dashicons-airplane',
		'dashicons-shield',
		'dashicons-tag',
		'dashicons-chart-bar',
		'dashicons-money-alt',
		'dashicons-pets',
		'dashicons-database',
	);
}

/**
 * Returns the slugs an entity or a taxonomy cannot use.
 *
 * The "glinf_" prefix already prevents a clash on the post type or taxonomy key
 * itself, but the archive/single/term URL segment is built from the user slug,
 * so it must not collide with core post types, taxonomies, query variables or
 * WordPress paths. Entities and taxonomies share ONE list (and one filter)
 * because they compete for the same URL namespace.
 *
 * @return string[]
 */
function glinf_get_reserved_slugs(): array {
	$reserved = array(
		// Core post types.
		'post',
		'page',
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
		// Core taxonomies.
		'category',
		'post_tag',
		'nav_menu',
		'link_category',
		'post_format',
		'wp_theme',
		'wp_template_part_area',
		'wp_pattern_category',
		// Public query variables (WP::$public_query_vars) and rewrite tags.
		'm',
		'p',
		'posts',
		'w',
		'cat',
		'withcomments',
		'withoutcomments',
		's',
		'search',
		'exact',
		'sentence',
		'calendar',
		'paged',
		'more',
		'tb',
		'pb',
		'author',
		'order',
		'orderby',
		'year',
		'month',
		'monthnum',
		'day',
		'hour',
		'minute',
		'second',
		'name',
		'category_name',
		'tag',
		'feed',
		'feeds',
		'author_name',
		'pagename',
		'page_id',
		'error',
		'attachment_id',
		'subpost',
		'subpost_id',
		'preview',
		'robots',
		'favicon',
		'taxonomy',
		'term',
		'cpage',
		'post_type',
		'embed',
		// Built-in URL segments.
		'comments',
		'comment_page',
		'trackback',
		'date',
		'type',
		'rss',
		'rss2',
		'atom',
		'rdf',
		'rest_route',
		'wp_json',
		'wp_admin',
		'wp_content',
		'wp_includes',
		'wp_login',
		'wp_sitemap',
		'sitemap',
		'xmlrpc',
		// Common reserved words and this theme's namespace.
		'admin',
		'administrator',
		'login',
		'logout',
		'register',
		'dashboard',
		'index',
		'home',
		'glinf',
	);

	/**
	 * Filters the slugs that cannot be used for an entity or a taxonomy.
	 *
	 * @param string[] $reserved Reserved slugs (lowercase, underscores instead of hyphens).
	 */
	$filtered = apply_filters( 'glinf_reserved_entity_slugs', $reserved );

	if ( ! is_array( $filtered ) ) {
		return $reserved;
	}

	$clean = array();
	foreach ( $filtered as $value ) {
		if ( is_string( $value ) ) {
			// Hyphens and underscores are equivalent for URLs: compare in one form.
			$clean[] = strtolower( str_replace( '-', '_', $value ) );
		}
	}

	return array_values( array_unique( $clean ) );
}

/**
 * Tells whether a slug is reserved.
 *
 * @param string        $slug     Entity or taxonomy slug.
 * @param string[]|null $reserved Optional precomputed list (avoids re-running the filter in loops).
 * @return bool
 */
function glinf_is_slug_reserved( string $slug, ?array $reserved = null ): bool {
	$reserved = $reserved ?? glinf_get_reserved_slugs();

	return in_array( $slug, $reserved, true );
}

/**
 * Tells whether a string has the shape of a valid entity slug.
 *
 * @param string $slug Candidate slug.
 * @return bool
 */
function glinf_is_valid_entity_slug_format( string $slug ): bool {
	return 1 === preg_match( GLINF_ENTITY_SLUG_REGEX, $slug );
}

/**
 * Tells whether a string has the shape of a valid taxonomy slug.
 *
 * @param string $slug Candidate slug.
 * @return bool
 */
function glinf_is_valid_taxonomy_slug_format( string $slug ): bool {
	return 1 === preg_match( GLINF_TAXONOMY_SLUG_REGEX, $slug );
}

/**
 * Sanitizes an untrusted value into plain text ('' for anything but strings).
 *
 * @param mixed $value Raw value (already unslashed).
 * @return string
 */
function glinf_entity_clean_text( mixed $value ): string {
	return is_string( $value ) ? sanitize_text_field( $value ) : '';
}

/**
 * Normalizes an untrusted slug: plain text, trimmed, lowercase.
 *
 * Characters are NOT stripped on purpose: silently rewriting "my-slug" into
 * "myslug" would surprise the user. The result is validated by
 * glinf_validate_new_slug() and rejected when it does not match.
 *
 * @param mixed $value Raw value (already unslashed).
 * @return string
 */
function glinf_normalize_slug_input( mixed $value ): string {
	// The slug is ASCII only (see the format check), so strtolower() is enough and needs no mbstring.
	return strtolower( trim( glinf_entity_clean_text( $value ) ) );
}

/**
 * Tells whether a display name (entity or taxonomy) is acceptable (1 to 40 characters).
 *
 * @param string $name Sanitized name.
 * @return bool
 */
function glinf_is_valid_name( string $name ): bool {
	$length = mb_strlen( $name, 'UTF-8' );

	return $length >= 1 && $length <= GLINF_NAME_MAX;
}

/**
 * Casts a checkbox-like value to bool.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function glinf_entity_to_bool( mixed $value ): bool {
	return is_scalar( $value ) && filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Sanitizes an entity into its canonical shape (lenient, never throws).
 *
 * This does not decide whether the entity is acceptable (see
 * glinf_validate_entity_fields() and glinf_validate_new_slug()); it only
 * guarantees that every key exists with a safe value and type.
 *
 * @param array<string, mixed> $raw Raw entity (already unslashed).
 * @return array<string, mixed>
 */
function glinf_sanitize_entity( array $raw ): array {
	$icon = $raw['icon'] ?? '';
	if ( ! is_string( $icon ) || ! in_array( $icon, glinf_get_entity_icons(), true ) ) {
		$icon = GLINF_ENTITY_DEFAULT_ICON;
	}

	$supports = array();
	if ( isset( $raw['supports'] ) && is_array( $raw['supports'] ) ) {
		// Iterating the whitelist gives a stable order and drops unknown values.
		foreach ( glinf_get_entity_supports_whitelist() as $feature ) {
			if ( in_array( $feature, $raw['supports'], true ) ) {
				$supports[] = $feature;
			}
		}
	}

	return array(
		'slug'        => glinf_normalize_slug_input( $raw['slug'] ?? '' ),
		'singular'    => glinf_entity_clean_text( $raw['singular'] ?? '' ),
		'plural'      => glinf_entity_clean_text( $raw['plural'] ?? '' ),
		'icon'        => $icon,
		'supports'    => $supports,
		'has_archive' => glinf_entity_to_bool( $raw['has_archive'] ?? false ),
	);
}

/**
 * Sanitizes a taxonomy into its canonical shape (lenient, never throws).
 *
 * The attached entities are a whitelist filter: only slugs present in
 * $entity_slugs survive, so a forged checkbox value can never attach a
 * taxonomy to something that does not exist. Iterating the whitelist (instead of
 * the posted list) also removes duplicates and gives a stable order.
 *
 * @param array<string, mixed> $raw          Raw taxonomy (already unslashed).
 * @param string[]             $entity_slugs Slugs of the entities that exist.
 * @return array<string, mixed>
 */
function glinf_sanitize_taxonomy( array $raw, array $entity_slugs ): array {
	$posted   = isset( $raw['entities'] ) && is_array( $raw['entities'] ) ? $raw['entities'] : array();
	$entities = array();

	foreach ( $entity_slugs as $entity_slug ) {
		if ( in_array( $entity_slug, $posted, true ) ) {
			$entities[] = $entity_slug;
		}
	}

	return array(
		'slug'         => glinf_normalize_slug_input( $raw['slug'] ?? '' ),
		'singular'     => glinf_entity_clean_text( $raw['singular'] ?? '' ),
		'plural'       => glinf_entity_clean_text( $raw['plural'] ?? '' ),
		'hierarchical' => glinf_entity_to_bool( $raw['hierarchical'] ?? false ),
		'entities'     => $entities,
	);
}

/**
 * Returns the default (empty) entity used by the "add" form.
 *
 * @return array<string, mixed>
 */
function glinf_get_default_entity(): array {
	return glinf_sanitize_entity(
		array(
			'supports'    => glinf_get_entity_default_supports(),
			'has_archive' => true,
		)
	);
}

/**
 * Returns the default (empty) taxonomy used by the "add" form.
 *
 * @return array<string, mixed>
 */
function glinf_get_default_taxonomy(): array {
	// Hierarchical by default: terms can then have a selectable parent, as with core categories.
	return glinf_sanitize_taxonomy( array( 'hierarchical' => true ), array() );
}

/**
 * Tells how a taxonomy is attached: to one entity, to several or to none.
 *
 * "specific" and "shared" use the same mechanism and differ only in the count;
 * the value drives the label in the admin list and the card used by the term archive.
 *
 * @param array<string, mixed> $taxonomy Normalized taxonomy.
 * @return string One of "unattached", "specific" or "shared".
 */
function glinf_get_taxonomy_scope( array $taxonomy ): string {
	$count = count( $taxonomy['entities'] );

	if ( 0 === $count ) {
		return 'unattached';
	}

	return 1 === $count ? 'specific' : 'shared';
}

/**
 * Returns the taxonomies attached to an entity.
 *
 * @param string                              $entity_slug Entity slug.
 * @param array<string, array<string, mixed>> $taxonomies  Normalized taxonomies (see glinf_get_taxonomies()).
 * @return array<int, array<string, mixed>> A list (not keyed), in configuration order.
 */
function glinf_get_entity_taxonomies( string $entity_slug, array $taxonomies ): array {
	$found = array();

	foreach ( $taxonomies as $taxonomy ) {
		if ( in_array( $entity_slug, $taxonomy['entities'], true ) ) {
			$found[] = $taxonomy;
		}
	}

	return $found;
}

/**
 * Removes an entity from the `entities` of every taxonomy.
 *
 * Used when an entity is deleted, in the SAME save that removes the entity: the
 * taxonomies stay (their terms are still in the database) but are detached.
 *
 * @param array<string, array<string, mixed>> $taxonomies  Normalized taxonomies.
 * @param string                              $entity_slug Slug of the deleted entity.
 * @return array<string, array<string, mixed>>
 */
function glinf_detach_entity_from_taxonomies( array $taxonomies, string $entity_slug ): array {
	foreach ( $taxonomies as $key => $taxonomy ) {
		$taxonomies[ $key ]['entities'] = array_values( array_diff( $taxonomy['entities'], array( $entity_slug ) ) );
	}

	return $taxonomies;
}

/**
 * Converts a version 1 configuration into the version 2 shape (pure).
 *
 * In version 1 every entity carried its own taxonomies, keyed
 * "glinf_<entity>_<name>". Each row becomes an independent taxonomy whose slug is
 * that key WITHOUT the "glinf_" prefix ("<entity>_<name>"): the registered key
 * (prefix + slug) stays IDENTICAL, so terms already assigned in the database are
 * not lost. The taxonomy is attached to the entity it came from.
 *
 * The result is NOT trusted: it goes through glinf_normalize_stored_config()
 * like any other input, which drops whatever is invalid, duplicated or over the
 * limits. Rows that were already invalid in version 1 (wrong prefix) are skipped here.
 *
 * @param array<string, mixed> $stored Version 1 option value.
 * @return array<string, mixed> Version 2 shaped (unnormalized) configuration.
 */
function glinf_migrate_v1_config( array $stored ): array {
	$migrated = array(
		'version'    => GLINF_ENTITIES_VERSION,
		'items'      => array(),
		'taxonomies' => array(),
	);

	if ( ! isset( $stored['items'] ) || ! is_array( $stored['items'] ) ) {
		return $migrated;
	}

	foreach ( $stored['items'] as $entity_slug => $raw ) {
		if ( ! is_string( $entity_slug ) || ! is_array( $raw ) ) {
			continue;
		}

		$rows = isset( $raw['taxonomies'] ) && is_array( $raw['taxonomies'] ) ? $raw['taxonomies'] : array();
		unset( $raw['taxonomies'] );

		$migrated['items'][ $entity_slug ] = $raw;

		// Version 1 keys always started with "glinf_<entity>_".
		$old_prefix = GLINF_POST_TYPE_PREFIX . $entity_slug . '_';

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['slug'] ) || ! is_string( $row['slug'] ) || ! str_starts_with( $row['slug'], $old_prefix ) ) {
				continue;
			}

			$slug = substr( $row['slug'], strlen( GLINF_POST_TYPE_PREFIX ) );

			// First one wins, as in version 1 where a duplicate key was dropped.
			if ( isset( $migrated['taxonomies'][ $slug ] ) ) {
				continue;
			}

			$migrated['taxonomies'][ $slug ] = array(
				'slug'         => $slug,
				'singular'     => $row['singular'] ?? '',
				'plural'       => $row['plural'] ?? '',
				'hierarchical' => $row['hierarchical'] ?? false,
				'entities'     => array( $entity_slug ),
			);
		}
	}

	return $migrated;
}

/**
 * Turns an untrusted stored option value into a clean configuration.
 *
 * Anything invalid is dropped rather than repaired: a broken entry must never
 * become a registered post type, taxonomy or URL segment. A version 1 value is
 * migrated first. Any other version is read as version 2 (best effort, still
 * fully validated): a mismatch must never silently discard the whole option.
 *
 * Entities are normalized first; taxonomies then keep only the entities that
 * survived and are dropped when their slug equals an entity slug (both would
 * claim the same URL segment).
 *
 * @param mixed $stored Value of the option.
 * @return array{items: array<string, array<string, mixed>>, taxonomies: array<string, array<string, mixed>>}
 */
function glinf_normalize_stored_config( mixed $stored ): array {
	$config = array(
		'items'      => array(),
		'taxonomies' => array(),
	);

	if ( ! is_array( $stored ) ) {
		return $config;
	}

	$version = $stored['version'] ?? 0;
	if ( 1 === $version || '1' === $version ) {
		$stored = glinf_migrate_v1_config( $stored );
	}

	$reserved = glinf_get_reserved_slugs();

	if ( isset( $stored['items'] ) && is_array( $stored['items'] ) ) {
		foreach ( $stored['items'] as $key => $raw ) {
			if ( count( $config['items'] ) >= GLINF_ENTITIES_MAX ) {
				break;
			}

			if ( ! is_string( $key ) || ! is_array( $raw ) ) {
				continue;
			}

			$entity = glinf_sanitize_entity( $raw );

			if (
				$entity['slug'] !== $key
				|| ! glinf_is_valid_entity_slug_format( $key )
				|| glinf_is_slug_reserved( $key, $reserved )
				|| ! glinf_is_valid_name( $entity['singular'] )
				|| ! glinf_is_valid_name( $entity['plural'] )
			) {
				continue;
			}

			$config['items'][ $key ] = $entity;
		}
	}

	if ( isset( $stored['taxonomies'] ) && is_array( $stored['taxonomies'] ) ) {
		$entity_slugs = array_keys( $config['items'] );

		foreach ( $stored['taxonomies'] as $key => $raw ) {
			if ( count( $config['taxonomies'] ) >= GLINF_TAXONOMIES_MAX ) {
				break;
			}

			if ( ! is_string( $key ) || ! is_array( $raw ) ) {
				continue;
			}

			$taxonomy = glinf_sanitize_taxonomy( $raw, $entity_slugs );

			if (
				$taxonomy['slug'] !== $key
				|| ! glinf_is_valid_taxonomy_slug_format( $key )
				|| glinf_is_slug_reserved( $key, $reserved )
				|| isset( $config['items'][ $key ] )
				|| ! glinf_is_valid_name( $taxonomy['singular'] )
				|| ! glinf_is_valid_name( $taxonomy['plural'] )
			) {
				continue;
			}

			$config['taxonomies'][ $key ] = $taxonomy;
		}
	}

	return $config;
}

/**
 * Returns the whole configuration (entities and taxonomies).
 *
 * Cached for the request. The value is always the output of the normalizer.
 *
 * @param bool $refresh Drop the per-request cache and read the option again.
 * @return array{items: array<string, array<string, mixed>>, taxonomies: array<string, array<string, mixed>>}
 */
function glinf_get_config( bool $refresh = false ): array {
	static $cache = null;

	if ( $refresh || null === $cache ) {
		$cache = glinf_normalize_stored_config( get_option( GLINF_ENTITIES_OPTION, array() ) );
	}

	return $cache;
}

/**
 * Returns the configured entities, keyed by slug.
 *
 * @param bool $refresh Drop the per-request cache and read the option again.
 * @return array<string, array<string, mixed>>
 */
function glinf_get_entities( bool $refresh = false ): array {
	return glinf_get_config( $refresh )['items'];
}

/**
 * Returns the configured taxonomies, keyed by slug.
 *
 * @param bool $refresh Drop the per-request cache and read the option again.
 * @return array<string, array<string, mixed>>
 */
function glinf_get_taxonomies( bool $refresh = false ): array {
	return glinf_get_config( $refresh )['taxonomies'];
}

/**
 * Returns a single entity.
 *
 * @param string $slug Entity slug.
 * @return array<string, mixed>|null Null when it does not exist.
 */
function glinf_get_entity( string $slug ): ?array {
	return glinf_get_entities()[ $slug ] ?? null;
}

/**
 * Returns a single taxonomy.
 *
 * @param string $slug Taxonomy slug.
 * @return array<string, mixed>|null Null when it does not exist.
 */
function glinf_get_taxonomy( string $slug ): ?array {
	return glinf_get_taxonomies()[ $slug ] ?? null;
}

/**
 * Creates the options this feature reads on every request.
 *
 * Both are autoloaded: for a missing option WordPress runs one query per
 * request, for an autoloaded one it runs none. add_option() does nothing when
 * the option already exists (a version 1 option is migrated on read, not here),
 * so this is safe to call more than once.
 *
 * @return void
 */
function glinf_entities_ensure_options(): void {
	add_option(
		GLINF_ENTITIES_OPTION,
		array(
			'version'    => GLINF_ENTITIES_VERSION,
			'items'      => array(),
			'taxonomies' => array(),
		),
		'',
		true
	);
	add_option( GLINF_ENTITIES_FLUSH_OPTION, 0, '', true );
}

/**
 * Marks the rewrite rules as dirty; they are flushed once on the next `init`.
 *
 * @return void
 */
function glinf_entities_flag_rewrite_flush(): void {
	update_option( GLINF_ENTITIES_FLUSH_OPTION, 1, true );
}

/**
 * Persists entities and taxonomies in one atomic write.
 *
 * A single option means a single update: deleting an entity can detach it from
 * every taxonomy in the same save, with no window in which the two halves
 * disagree. Input is normalized again before saving, so what reaches the database
 * is always the output of glinf_normalize_stored_config().
 *
 * @param array<string, array<string, mixed>> $items      Entities keyed by slug.
 * @param array<string, array<string, mixed>> $taxonomies Taxonomies keyed by slug.
 * @return bool True when the configuration is stored (also when unchanged).
 */
function glinf_save_config( array $items, array $taxonomies ): bool {
	$normalized = glinf_normalize_stored_config(
		array(
			'version'    => GLINF_ENTITIES_VERSION,
			'items'      => $items,
			'taxonomies' => $taxonomies,
		)
	);

	$data = array(
		'version'    => GLINF_ENTITIES_VERSION,
		'items'      => $normalized['items'],
		'taxonomies' => $normalized['taxonomies'],
	);

	// update_option() returns false both on failure and when nothing changed.
	$updated = update_option( GLINF_ENTITIES_OPTION, $data, true );
	glinf_get_config( true );

	if ( $updated ) {
		glinf_entities_flag_rewrite_flush();
		return true;
	}

	return get_option( GLINF_ENTITIES_OPTION ) === $data;
}

/**
 * Returns every URL segment already claimed by a registered post type or taxonomy.
 *
 * Used so that a new entity (or taxonomy) never shadows an existing route.
 *
 * @return string[]
 */
function glinf_get_registered_rewrite_slugs(): array {
	$slugs = array();

	$objects = array_merge(
		get_post_types( array(), 'objects' ),
		get_taxonomies( array(), 'objects' )
	);

	foreach ( $objects as $object ) {
		if ( isset( $object->rewrite ) && is_array( $object->rewrite ) && isset( $object->rewrite['slug'] ) && is_string( $object->rewrite['slug'] ) ) {
			$slugs[] = strtolower( trim( $object->rewrite['slug'], '/' ) );
		}
		if ( isset( $object->has_archive ) && is_string( $object->has_archive ) ) {
			$slugs[] = strtolower( trim( $object->has_archive, '/' ) );
		}
	}

	return array_values( array_unique( $slugs ) );
}

/**
 * Validates the slug of an entity or taxonomy that is about to be created.
 *
 * Only call this when creating: after creation the slug is immutable. Entities
 * and taxonomies share the URL namespace, so a slug used by one kind is refused
 * for the other too (this includes taxonomies with no entity attached, which are
 * not registered and would otherwise go unnoticed).
 *
 * @param string                              $slug       Normalized slug (see glinf_normalize_slug_input()).
 * @param string                              $kind       "entity" or "taxonomy".
 * @param array<string, array<string, mixed>> $items      Currently saved entities.
 * @param array<string, array<string, mixed>> $taxonomies Currently saved taxonomies.
 * @return string Empty string when valid, otherwise an error code.
 */
function glinf_validate_new_slug( string $slug, string $kind, array $items, array $taxonomies ): string {
	$is_taxonomy = 'taxonomy' === $kind;

	if ( ! ( $is_taxonomy ? glinf_is_valid_taxonomy_slug_format( $slug ) : glinf_is_valid_entity_slug_format( $slug ) ) ) {
		return 'slug_invalid';
	}

	if ( glinf_is_slug_reserved( $slug ) ) {
		return 'slug_reserved';
	}

	if ( isset( $items[ $slug ] ) || isset( $taxonomies[ $slug ] ) ) {
		return 'slug_exists';
	}

	// Something else (a plugin, a leftover) already registered the same key.
	if ( $is_taxonomy ? taxonomy_exists( glinf_taxonomy_key( $slug ) ) : post_type_exists( glinf_entity_post_type( $slug ) ) ) {
		return 'slug_conflict';
	}

	$rewrite_slug = glinf_entity_rewrite_slug( $slug );

	if ( in_array( $rewrite_slug, glinf_get_registered_rewrite_slugs(), true ) ) {
		return 'slug_conflict';
	}

	// A page or post with the same path would be shadowed by (or shadow) the archive.
	if ( null !== get_page_by_path( $rewrite_slug, OBJECT, array( 'page', 'post' ) ) ) {
		return 'slug_conflict';
	}

	return '';
}

/**
 * Validates the slug of an entity that is about to be created.
 *
 * @param string                              $slug       Normalized slug.
 * @param array<string, array<string, mixed>> $items      Currently saved entities.
 * @param array<string, array<string, mixed>> $taxonomies Currently saved taxonomies.
 * @return string Empty string when valid, otherwise an error code.
 */
function glinf_validate_new_entity_slug( string $slug, array $items, array $taxonomies ): string {
	return glinf_validate_new_slug( $slug, 'entity', $items, $taxonomies );
}

/**
 * Validates the slug of a taxonomy that is about to be created.
 *
 * @param string                              $slug       Normalized slug.
 * @param array<string, array<string, mixed>> $items      Currently saved entities.
 * @param array<string, array<string, mixed>> $taxonomies Currently saved taxonomies.
 * @return string Empty string when valid, otherwise an error code.
 */
function glinf_validate_new_taxonomy_slug( string $slug, array $items, array $taxonomies ): string {
	return glinf_validate_new_slug( $slug, 'taxonomy', $items, $taxonomies );
}

/**
 * Validates the editable fields of a sanitized entity.
 *
 * @param array<string, mixed> $entity Output of glinf_sanitize_entity().
 * @return string Empty string when valid, otherwise an error code.
 */
function glinf_validate_entity_fields( array $entity ): string {
	if ( ! glinf_is_valid_name( $entity['singular'] ) || ! glinf_is_valid_name( $entity['plural'] ) ) {
		return 'name_invalid';
	}

	if ( array() === $entity['supports'] ) {
		return 'supports_required';
	}

	return '';
}

/**
 * Validates the editable fields of a sanitized taxonomy.
 *
 * The attached entities need no check here: the sanitizer already reduced them
 * to slugs of existing entities. A taxonomy attached to none is valid.
 *
 * @param array<string, mixed> $taxonomy Output of glinf_sanitize_taxonomy().
 * @return string Empty string when valid, otherwise an error code.
 */
function glinf_validate_taxonomy_fields( array $taxonomy ): string {
	if ( ! glinf_is_valid_name( $taxonomy['singular'] ) || ! glinf_is_valid_name( $taxonomy['plural'] ) ) {
		return 'name_invalid';
	}

	return '';
}
