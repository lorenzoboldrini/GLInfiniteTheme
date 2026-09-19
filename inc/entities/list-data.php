<?php
/**
 * Entity Manager lists: the data side (pure functions, no admin dependencies).
 *
 * Everything the "Entities" and "Taxonomies" lists do with the configuration
 * lives here, so it can be tested without rendering anything: building the rows,
 * reading the (untrusted) query string, searching, filtering, sorting,
 * paginating, counting the views and, for the group "Delete" action, choosing
 * which items are affected and computing the resulting configuration.
 *
 * Nothing here reads a superglobal, queries the database or writes anything: the
 * callers hand in the configuration and the (already unslashed) input, and get
 * plain arrays back. At most 20 entities and 20 taxonomies exist (see
 * GLINF_ENTITIES_MAX and GLINF_TAXONOMIES_MAX), so all of it happens in memory.
 *
 * A "kind" is either "entities" or "taxonomies".
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** Rows per page when the user has not chosen another value in Screen Options. */
const GLINF_LIST_PER_PAGE_DEFAULT = 20;

/** Highest value accepted for "rows per page". */
const GLINF_LIST_PER_PAGE_MAX = 100;

/** Longest search text taken into account, in characters. */
const GLINF_LIST_SEARCH_MAX = 100;

/**
 * Returns the labels of the views (the links above the list), keyed by view.
 *
 * The keys are the whitelist of accepted `view` values.
 *
 * @param string $kind "entities" or "taxonomies".
 * @return array<string, string>
 */
function glinf_list_get_view_labels( string $kind ): array {
	if ( 'taxonomies' === $kind ) {
		return array(
			'all'          => __( 'All', 'gl-infinite-theme' ),
			'specific'     => __( 'Specific', 'gl-infinite-theme' ),
			'shared'       => __( 'Shared', 'gl-infinite-theme' ),
			'unattached'   => __( 'Not attached', 'gl-infinite-theme' ),
			'hierarchical' => __( 'Hierarchical', 'gl-infinite-theme' ),
		);
	}

	return array(
		'all'        => __( 'All', 'gl-infinite-theme' ),
		'archive'    => __( 'With archive', 'gl-infinite-theme' ),
		'no_archive' => __( 'Without archive', 'gl-infinite-theme' ),
		'with_tax'   => __( 'With taxonomies', 'gl-infinite-theme' ),
		'no_tax'     => __( 'Without taxonomies', 'gl-infinite-theme' ),
	);
}

/**
 * Returns the columns a list can be sorted by (whitelist of `orderby`).
 *
 * @param string $kind "entities" or "taxonomies".
 * @return string[]
 */
function glinf_list_get_orderby_whitelist( string $kind ): array {
	if ( 'taxonomies' === $kind ) {
		return array( 'name', 'slug', 'url_base', 'hierarchical', 'scope' );
	}

	return array( 'name', 'slug', 'url_base', 'archive', 'taxonomies' );
}

/**
 * Builds the rows of the entities list from the configuration.
 *
 * Each row carries the effective URL base and the taxonomies attached to the
 * entity (their slugs, to filter, and their plural names, to show).
 *
 * @param array<string, mixed> $config Normalized configuration (see glinf_get_config()).
 * @return array<string, array<string, mixed>> Rows keyed by entity slug, in configuration order.
 */
function glinf_list_build_entity_rows( array $config ): array {
	$rows = array();

	foreach ( $config['items'] as $entity ) {
		$attached = glinf_get_entity_taxonomies( $entity['slug'], $config['taxonomies'] );

		$rows[ $entity['slug'] ] = array(
			'slug'           => $entity['slug'],
			'singular'       => $entity['singular'],
			'plural'         => $entity['plural'],
			'icon'           => $entity['icon'],
			'has_archive'    => (bool) $entity['has_archive'],
			'url_base'       => glinf_entity_url_base( $entity ),
			'taxonomy_slugs' => array_values( wp_list_pluck( $attached, 'slug' ) ),
			'taxonomy_names' => array_values( wp_list_pluck( $attached, 'plural' ) ),
		);
	}

	return $rows;
}

/**
 * Builds the rows of the taxonomies list from the configuration.
 *
 * Each row carries the registered key, the effective URL base, the scope
 * (specific, shared, unattached) and the entities the taxonomy is attached to.
 *
 * @param array<string, mixed> $config Normalized configuration (see glinf_get_config()).
 * @return array<string, array<string, mixed>> Rows keyed by taxonomy slug, in configuration order.
 */
function glinf_list_build_taxonomy_rows( array $config ): array {
	$rows = array();

	foreach ( $config['taxonomies'] as $taxonomy ) {
		$entity_names = array();
		foreach ( $taxonomy['entities'] as $entity_slug ) {
			// The normalizer guarantees that every attached slug exists; the guard only keeps a bad input from breaking the list.
			if ( isset( $config['items'][ $entity_slug ] ) ) {
				$entity_names[] = $config['items'][ $entity_slug ]['plural'];
			}
		}

		$rows[ $taxonomy['slug'] ] = array(
			'slug'         => $taxonomy['slug'],
			'key'          => glinf_taxonomy_key( $taxonomy['slug'] ),
			'singular'     => $taxonomy['singular'],
			'plural'       => $taxonomy['plural'],
			'hierarchical' => (bool) $taxonomy['hierarchical'],
			'url_base'     => glinf_taxonomy_url_base( $taxonomy ),
			'scope'        => glinf_get_taxonomy_scope( $taxonomy ),
			'entity_slugs' => array_values( $taxonomy['entities'] ),
			'entity_names' => $entity_names,
		);
	}

	return $rows;
}

/**
 * Reads one string from the untrusted input ('' when missing or not a string).
 *
 * @param array<mixed> $input Query string values (already unslashed).
 * @param string       $key   Parameter name.
 * @return string
 */
function glinf_list_input_string( array $input, string $key ): string {
	return isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? $input[ $key ] : '';
}

/**
 * Turns the untrusted query string into a whitelisted list state.
 *
 * Nothing is trusted and nothing is reflected: `view`, `orderby` and `order`
 * must be one of the known values, `filter` must be the slug of something that
 * exists (a taxonomy for the entities list, an entity for the taxonomies list),
 * `s` is plain text of bounded length and `paged` an integer. Anything else,
 * including arrays, falls back to the default, so unknown values are ignored.
 *
 * @param string               $kind   "entities" or "taxonomies".
 * @param array<mixed>         $input  Query string values (already unslashed, typically $_GET).
 * @param array<string, mixed> $config Normalized configuration.
 * @return array{view: string, filter: string, s: string, orderby: string, order: string, paged: int}
 */
function glinf_list_parse_query( string $kind, array $input, array $config ): array {
	$view = sanitize_key( glinf_list_input_string( $input, 'view' ) );
	if ( ! array_key_exists( $view, glinf_list_get_view_labels( $kind ) ) ) {
		$view = 'all';
	}

	// The dropdown of the entities list picks a taxonomy, the one of the taxonomies list an entity.
	$filter_pool = 'taxonomies' === $kind ? array_keys( $config['items'] ) : array_keys( $config['taxonomies'] );
	$filter      = sanitize_key( glinf_list_input_string( $input, 'filter' ) );
	if ( ! in_array( $filter, array_map( 'strval', $filter_pool ), true ) ) {
		$filter = '';
	}

	$search = trim( sanitize_text_field( glinf_list_input_string( $input, 's' ) ) );
	$search = mb_substr( $search, 0, GLINF_LIST_SEARCH_MAX, 'UTF-8' );

	$orderby = sanitize_key( glinf_list_input_string( $input, 'orderby' ) );
	if ( ! in_array( $orderby, glinf_list_get_orderby_whitelist( $kind ), true ) ) {
		$orderby = 'name';
	}

	$order = strtolower( sanitize_key( glinf_list_input_string( $input, 'order' ) ) );
	if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
		$order = 'asc';
	}

	return array(
		'view'    => $view,
		'filter'  => $filter,
		's'       => $search,
		'orderby' => $orderby,
		'order'   => $order,
		'paged'   => absint( glinf_list_input_string( $input, 'paged' ) ),
	);
}

/**
 * Case-insensitive "contains" that works with or without the mbstring extension.
 *
 * @param string $haystack Text to look into.
 * @param string $needle   Text to look for (not empty).
 * @return bool
 */
function glinf_list_contains( string $haystack, string $needle ): bool {
	if ( function_exists( 'mb_stripos' ) ) {
		return false !== mb_stripos( $haystack, $needle, 0, 'UTF-8' );
	}

	return false !== stripos( $haystack, $needle );
}

/**
 * Tells whether a row belongs to a view.
 *
 * @param string               $kind "entities" or "taxonomies".
 * @param array<string, mixed> $row  Row built by glinf_list_build_*_rows().
 * @param string               $view Whitelisted view.
 * @return bool
 */
function glinf_list_row_in_view( string $kind, array $row, string $view ): bool {
	if ( 'all' === $view ) {
		return true;
	}

	if ( 'taxonomies' === $kind ) {
		return match ( $view ) {
			'specific'     => 'specific' === $row['scope'],
			'shared'       => 'shared' === $row['scope'],
			'unattached'   => 'unattached' === $row['scope'],
			'hierarchical' => true === $row['hierarchical'],
			default        => true,
		};
	}

	return match ( $view ) {
		'archive'    => true === $row['has_archive'],
		'no_archive' => false === $row['has_archive'],
		'with_tax'   => array() !== $row['taxonomy_slugs'],
		'no_tax'     => array() === $row['taxonomy_slugs'],
		default      => true,
	};
}

/**
 * Tells whether a row matches the dropdown filter.
 *
 * @param string               $kind   "entities" or "taxonomies".
 * @param array<string, mixed> $row    Row built by glinf_list_build_*_rows().
 * @param string               $filter Whitelisted slug ('' means no filter).
 * @return bool
 */
function glinf_list_row_matches_filter( string $kind, array $row, string $filter ): bool {
	if ( '' === $filter ) {
		return true;
	}

	if ( 'taxonomies' === $kind ) {
		return in_array( $filter, $row['entity_slugs'], true );
	}

	return in_array( $filter, $row['taxonomy_slugs'], true );
}

/**
 * Tells whether a row matches the search text (names, slug and URL base).
 *
 * @param array<string, mixed> $row    Row built by glinf_list_build_*_rows().
 * @param string               $needle Search text ('' means no search).
 * @return bool
 */
function glinf_list_row_matches_search( array $row, string $needle ): bool {
	if ( '' === $needle ) {
		return true;
	}

	foreach ( array( 'singular', 'plural', 'slug', 'url_base' ) as $field ) {
		if ( glinf_list_contains( (string) $row[ $field ], $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Applies the view, the dropdown filter and the search to the rows.
 *
 * The three are combined with AND. Keys and order of the rows are kept.
 *
 * @param string                              $kind  "entities" or "taxonomies".
 * @param array<string, array<string, mixed>> $rows  Rows.
 * @param array<string, mixed>                $query State from glinf_list_parse_query().
 * @return array<string, array<string, mixed>>
 */
function glinf_list_filter_rows( string $kind, array $rows, array $query ): array {
	return array_filter(
		$rows,
		static function ( array $row ) use ( $kind, $query ): bool {
			return glinf_list_row_in_view( $kind, $row, $query['view'] )
				&& glinf_list_row_matches_filter( $kind, $row, $query['filter'] )
				&& glinf_list_row_matches_search( $row, $query['s'] );
		}
	);
}

/**
 * Counts the rows of every view (on the whole set, ignoring search and filter).
 *
 * @param string                              $kind "entities" or "taxonomies".
 * @param array<string, array<string, mixed>> $rows Rows.
 * @return array<string, int> Count keyed by view.
 */
function glinf_list_count_views( string $kind, array $rows ): array {
	$counts = array();

	foreach ( array_keys( glinf_list_get_view_labels( $kind ) ) as $view ) {
		$counts[ $view ] = 0;
		foreach ( $rows as $row ) {
			if ( glinf_list_row_in_view( $kind, $row, $view ) ) {
				++$counts[ $view ];
			}
		}
	}

	return $counts;
}

/**
 * Compares two texts for sorting: natural order, case-insensitive, accents ignored.
 *
 * "Épices" sorts with the E's, not after the Z's. It is not a full locale-aware
 * collation, but it needs no extension and is enough for names of this length.
 *
 * @param string $a First text.
 * @param string $b Second text.
 * @return int
 */
function glinf_list_collate( string $a, string $b ): int {
	return strnatcasecmp( remove_accents( $a ), remove_accents( $b ) );
}

/**
 * Compares two rows by the chosen column (ascending).
 *
 * @param string               $kind    "entities" or "taxonomies".
 * @param array<string, mixed> $a       First row.
 * @param array<string, mixed> $b       Second row.
 * @param string               $orderby Whitelisted column.
 * @return int
 */
function glinf_list_compare_rows( string $kind, array $a, array $b, string $orderby ): int {
	switch ( $orderby ) {
		case 'slug':
			return glinf_list_collate( $a['slug'], $b['slug'] );
		case 'url_base':
			return glinf_list_collate( $a['url_base'], $b['url_base'] );
		case 'archive':
			return (int) $a['has_archive'] <=> (int) $b['has_archive'];
		case 'hierarchical':
			return (int) $a['hierarchical'] <=> (int) $b['hierarchical'];
		case 'taxonomies':
			return count( $a['taxonomy_slugs'] ) <=> count( $b['taxonomy_slugs'] );
		case 'scope':
			// Unattached (0 entities) before specific (1) before shared (2 or more).
			return count( $a['entity_slugs'] ) <=> count( $b['entity_slugs'] );
		case 'name':
		default:
			return glinf_list_collate( $a['plural'], $b['plural'] );
	}
}

/**
 * Sorts the rows by a column.
 *
 * The direction only applies to the chosen column: the tie-breaker is always the
 * plural name and then the slug, ascending, so the order is stable and total.
 *
 * @param string                              $kind    "entities" or "taxonomies".
 * @param array<string, array<string, mixed>> $rows    Rows.
 * @param string                              $orderby Whitelisted column.
 * @param string                              $order   "asc" or "desc".
 * @return array<string, array<string, mixed>> Same rows, keys kept.
 */
function glinf_list_sort_rows( string $kind, array $rows, string $orderby, string $order ): array {
	uasort(
		$rows,
		static function ( array $a, array $b ) use ( $kind, $orderby, $order ): int {
			$result = glinf_list_compare_rows( $kind, $a, $b, $orderby );

			if ( 'desc' === $order ) {
				$result = -$result;
			}

			if ( 0 === $result ) {
				$result = glinf_list_collate( $a['plural'], $b['plural'] );
			}

			return 0 === $result ? glinf_list_collate( $a['slug'], $b['slug'] ) : $result;
		}
	);

	return $rows;
}

/**
 * Clamps a "rows per page" value to the accepted range.
 *
 * @param mixed $value Raw value (user option or Screen Options field).
 * @return int Between 1 and GLINF_LIST_PER_PAGE_MAX; the default when it is not numeric.
 */
function glinf_list_clamp_per_page( mixed $value ): int {
	if ( ! is_numeric( $value ) ) {
		return GLINF_LIST_PER_PAGE_DEFAULT;
	}

	return max( 1, min( GLINF_LIST_PER_PAGE_MAX, (int) $value ) );
}

/**
 * Cuts the rows into pages.
 *
 * The requested page is clamped, so a stale `paged` never shows an empty page.
 *
 * @param array<string, array<string, mixed>> $rows     Rows.
 * @param int                                 $per_page Rows per page (clamped to 1 or more).
 * @param int                                 $paged    Requested page (1-based, 0 = first).
 * @return array{rows: array<string, array<string, mixed>>, total: int, pages: int, paged: int}
 */
function glinf_list_paginate( array $rows, int $per_page, int $paged ): array {
	$per_page = max( 1, $per_page );
	$total    = count( $rows );
	$pages    = (int) ceil( $total / $per_page );
	$paged    = max( 1, min( $paged, max( 1, $pages ) ) );

	return array(
		'rows'  => array_slice( $rows, ( $paged - 1 ) * $per_page, $per_page, true ),
		'total' => $total,
		'pages' => $pages,
		'paged' => $paged,
	);
}

/**
 * Chooses which existing items a group action applies to.
 *
 * The received value is untrusted: it must be an array, only its first
 * `$max` elements are looked at (the form never sends more than the number of
 * items that can exist), every element must be a string that, once normalized as
 * a slug, is present in the configuration; everything else is discarded. The
 * result has no duplicates and follows the order of the configuration.
 *
 * @param mixed    $received       Raw value of the posted list (already unslashed).
 * @param string[] $existing_slugs Slugs present in the configuration (the whitelist).
 * @param int      $max            Largest number of items that can exist.
 * @return string[]
 */
function glinf_list_sanitize_selection( mixed $received, array $existing_slugs, int $max ): array {
	if ( ! is_array( $received ) ) {
		return array();
	}

	$wanted = array();
	foreach ( array_slice( array_values( $received ), 0, max( 0, $max ) ) as $value ) {
		$slug = glinf_normalize_slug_input( $value );

		if ( '' !== $slug ) {
			$wanted[ $slug ] = true;
		}
	}

	$selected = array();
	foreach ( $existing_slugs as $slug ) {
		if ( isset( $wanted[ (string) $slug ] ) ) {
			$selected[] = (string) $slug;
		}
	}

	return $selected;
}

/**
 * Computes the configuration that results from deleting a group of items.
 *
 * Only the configuration changes: posts and terms are never touched. Deleting an
 * entity also detaches it from every taxonomy, in the same result, so the
 * two halves never disagree; deleting a taxonomy removes only that taxonomy.
 * The input is not modified and nothing is saved: the caller passes the result
 * to glinf_save_config().
 *
 * @param array<string, mixed> $config   Normalized configuration.
 * @param string               $kind     "entities" or "taxonomies".
 * @param mixed                $received Raw posted list of slugs (already unslashed).
 * @return array{items: array<string, array<string, mixed>>, taxonomies: array<string, array<string, mixed>>, slugs: string[], removed: int}
 */
function glinf_list_bulk_delete_config( array $config, string $kind, mixed $received ): array {
	$is_entities = 'entities' === $kind;
	$existing    = array_keys( $is_entities ? $config['items'] : $config['taxonomies'] );
	$max         = $is_entities ? GLINF_ENTITIES_MAX : GLINF_TAXONOMIES_MAX;
	$slugs       = glinf_list_sanitize_selection( $received, array_map( 'strval', $existing ), $max );

	$items      = $config['items'];
	$taxonomies = $config['taxonomies'];

	foreach ( $slugs as $slug ) {
		if ( $is_entities ) {
			unset( $items[ $slug ] );
			$taxonomies = glinf_detach_entity_from_taxonomies( $taxonomies, $slug );
		} else {
			unset( $taxonomies[ $slug ] );
		}
	}

	return array(
		'items'      => $items,
		'taxonomies' => $taxonomies,
		'slugs'      => $slugs,
		'removed'    => count( $slugs ),
	);
}
