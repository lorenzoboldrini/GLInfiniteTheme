<?php
/**
 * Block templates and card patterns generated for every entity and taxonomy.
 *
 * Nothing is written to the filesystem or to the database: the markup is
 * produced by the functions below from the saved configuration and handed to
 * the core registries on `init`.
 *
 * - Templates use register_block_template() (WordPress 6.7+). On older
 *   versions the function does not exist, no fallback is provided and the
 *   generic templates/single.html and templates/archive.html apply: they
 *   already work for custom post types because the Query Loop inherits the
 *   main query. A template file with the same slug in the theme, or a
 *   version customised by the user in the Site Editor, always wins over the
 *   registered one (WordPress filters them out).
 * - The card is a pattern that the archive templates include with core/pattern:
 *   glinf/entity-<slug>-card for an entity (and for a taxonomy attached to ONE
 *   entity, so its term archive looks like the entity archive) and the generic
 *   glinf/entity-card for a taxonomy shared by several entities. Their look comes
 *   from the theme tokens through styles.blocks["core/post-template"].css in
 *   theme.json, so they follow every Style Variation. No CSS is added here.
 * - Every registered taxonomy (at least one entity attached) gets its own term
 *   template ("taxonomy-glinf_<slug>"), so the same mechanism serves specific and
 *   shared taxonomies. Taxonomies with no entity are not registered, hence they
 *   get no template.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** Slug of the pattern category that groups the entity cards. */
const GLINF_ENTITIES_PATTERN_CATEGORY = 'glinf-entities';

/** Namespace used for the registered templates (name format "namespace//slug"). */
const GLINF_ENTITIES_TEMPLATE_NAMESPACE = 'gl-infinite-theme';

/** Slug of the generic card pattern, used by the term archive of a taxonomy shared by several entities. */
const GLINF_ENTITIES_GENERIC_CARD_PATTERN = 'glinf/entity-card';

/**
 * Returns the pattern slug of the card of an entity.
 *
 * @param string $slug Entity slug.
 * @return string
 */
function glinf_entity_card_pattern_slug( string $slug ): string {
	return 'glinf/entity-' . $slug . '-card';
}

/**
 * Serializes block attributes for a block comment.
 *
 * Uses the core serializer, which escapes "--", "<", ">" and "&" so that a
 * value can never close the HTML comment that carries it.
 *
 * @param array<string, mixed> $attrs Block attributes.
 * @return string Empty string when there are no attributes, otherwise " {json}".
 */
function glinf_entity_block_attrs( array $attrs ): string {
	return array() === $attrs ? '' : ' ' . serialize_block_attributes( $attrs );
}

/**
 * Returns the opening comment of a block.
 *
 * @param string               $name  Block name without the "core/" namespace.
 * @param array<string, mixed> $attrs Block attributes.
 * @return string
 */
function glinf_entity_block_open( string $name, array $attrs = array() ): string {
	return '<!-- wp:' . $name . glinf_entity_block_attrs( $attrs ) . ' -->';
}

/**
 * Returns a self-closing block comment.
 *
 * @param string               $name  Block name without the "core/" namespace.
 * @param array<string, mixed> $attrs Block attributes.
 * @return string
 */
function glinf_entity_block_void( string $name, array $attrs = array() ): string {
	return '<!-- wp:' . $name . glinf_entity_block_attrs( $attrs ) . ' /-->';
}

/**
 * Returns the closing comment of a block.
 *
 * @param string $name Block name without the "core/" namespace.
 * @return string
 */
function glinf_entity_block_close( string $name ): string {
	return '<!-- /wp:' . $name . ' -->';
}

/**
 * Indents every line of a markup block with tabs (readability of the output only).
 *
 * @param string[] $lines Lines to indent.
 * @param int      $level Number of tabs.
 * @return string[]
 */
function glinf_entity_indent( array $lines, int $level = 1 ): array {
	$tabs = str_repeat( "\t", $level );

	return array_map(
		static function ( string $line ) use ( $tabs ): string {
			return '' === $line ? '' : $tabs . $line;
		},
		$lines
	);
}

/**
 * Wraps the blocks of a card in the group that gives them the theme block gap.
 *
 * @param string[] $blocks Block markup lines of the card.
 * @return string Block markup.
 */
function glinf_entity_card_wrap( array $blocks ): string {
	// The wrapping group gives the card children the theme block gap (no CSS needed).
	$lines = array_merge(
		array(
			glinf_entity_block_open( 'group' ),
			'<div class="wp-block-group">',
		),
		glinf_entity_indent( $blocks ),
		array(
			'</div>',
			glinf_entity_block_close( 'group' ),
		)
	);

	return implode( "\n", $lines );
}

/**
 * Builds the markup of the card shown for each item in the archive.
 *
 * Only the blocks enabled by the entity features are included.
 *
 * @param array<string, mixed> $entity Normalized entity.
 * @return string Block markup.
 */
function glinf_entity_card_markup( array $entity ): string {
	$supports = $entity['supports'];
	$blocks   = array();

	if ( in_array( 'thumbnail', $supports, true ) ) {
		$blocks[] = glinf_entity_block_void( 'post-featured-image', array( 'isLink' => true ) );
	}

	if ( in_array( 'title', $supports, true ) ) {
		$blocks[] = glinf_entity_block_void(
			'post-title',
			array(
				'level'  => 3,
				'isLink' => true,
			)
		);
	}

	// The excerpt block also builds one from the content when there is no manual excerpt.
	if ( in_array( 'excerpt', $supports, true ) || in_array( 'editor', $supports, true ) ) {
		$blocks[] = glinf_entity_block_void( 'post-excerpt' );
	}

	// A card must always offer a link to the item: only the title and the featured image are links.
	// The "read more" link is used (not a linked date) because its accessible name always includes the item title.
	if ( ! in_array( 'title', $supports, true ) && ! in_array( 'thumbnail', $supports, true ) ) {
		$blocks[] = glinf_entity_block_void( 'read-more' );
	}

	return glinf_entity_card_wrap( $blocks );
}

/**
 * Builds the markup of the generic card, used for items of ANY entity.
 *
 * It is a static pattern, so it cannot know which features the entity of each
 * item enables. Every block hides itself when the item has nothing to show
 * (no title, no featured image, no excerpt), and that could leave an item
 * without any link. The "read more" link is therefore ALWAYS present: it is
 * rendered for every post and its accessible name includes the item title (or
 * "untitled post N"), so every card links to its item with a meaningful name.
 * Trade-off: for items that also have a title it is a further link to the same
 * page. Entity-specific cards do not have this compromise, they add it only when
 * the entity has neither title nor featured image.
 *
 * @return string Block markup.
 */
function glinf_entity_generic_card_markup(): string {
	return glinf_entity_card_wrap(
		array(
			glinf_entity_block_void( 'post-featured-image', array( 'isLink' => true ) ),
			glinf_entity_block_void(
				'post-title',
				array(
					'level'  => 3,
					'isLink' => true,
				)
			),
			glinf_entity_block_void( 'post-excerpt' ),
			glinf_entity_block_void( 'read-more' ),
		)
	);
}

/**
 * Builds the markup of an archive-like template: header, title, item grid, pagination, footer.
 *
 * Same structure as templates/archive.html, with the Query Loop inheriting the
 * main query (so the same markup works for a post type archive and for a term
 * archive), a three-column grid and a card pattern as item.
 *
 * @param string $post_type Post type the editor previews (the front end uses the main query).
 * @param string $card_slug Slug of the card pattern.
 * @return string Block markup.
 */
function glinf_entity_loop_template_markup( string $post_type, string $card_slug ): string {
	$query_attrs = array(
		'queryId' => 0,
		'query'   => array(
			'perPage'  => 10,
			'pages'    => 0,
			'offset'   => 0,
			'postType' => $post_type,
			'order'    => 'desc',
			'orderBy'  => 'date',
			'author'   => '',
			'search'   => '',
			'exclude'  => array(),
			'sticky'   => '',
			// Use the main query: no extra query, core pagination and caching.
			'inherit'  => true,
		),
	);

	$query = array_merge(
		array(
			glinf_entity_block_open( 'query', $query_attrs ),
			'<div class="wp-block-query">',
		),
		glinf_entity_indent(
			array_merge(
				array(
					glinf_entity_block_open(
						'post-template',
						array(
							'layout' => array(
								'type'        => 'grid',
								'columnCount' => 3,
							),
						)
					),
				),
				glinf_entity_indent(
					array(
						glinf_entity_block_void( 'pattern', array( 'slug' => $card_slug ) ),
					)
				),
				array(
					glinf_entity_block_close( 'post-template' ),
					'',
					glinf_entity_block_open( 'query-pagination' ),
					"\t" . glinf_entity_block_void( 'query-pagination-previous' ),
					'',
					"\t" . glinf_entity_block_void( 'query-pagination-numbers' ),
					'',
					"\t" . glinf_entity_block_void( 'query-pagination-next' ),
					glinf_entity_block_close( 'query-pagination' ),
					'',
					glinf_entity_block_open( 'query-no-results' ),
					"\t" . glinf_entity_block_void( 'pattern', array( 'slug' => 'glinf/query-no-results' ) ),
					glinf_entity_block_close( 'query-no-results' ),
				)
			)
		),
		array(
			'</div>',
			glinf_entity_block_close( 'query' ),
		)
	);

	$main = array_merge(
		array(
			glinf_entity_block_open(
				'group',
				array(
					'tagName' => 'main',
					'layout'  => array( 'type' => 'constrained' ),
				)
			),
			'<main class="wp-block-group">',
			"\t" . glinf_entity_block_void(
				'query-title',
				array(
					'type'  => 'archive',
					'level' => 1,
				)
			),
			'',
			"\t" . glinf_entity_block_void( 'term-description' ),
			'',
		),
		glinf_entity_indent( $query ),
		array(
			'</main>',
			glinf_entity_block_close( 'group' ),
		)
	);

	return implode(
		"\n",
		array_merge(
			array(
				glinf_entity_block_void(
					'template-part',
					array(
						'slug'    => 'header',
						'tagName' => 'header',
					)
				),
				'',
			),
			$main,
			array(
				'',
				glinf_entity_block_void(
					'template-part',
					array(
						'slug'    => 'footer',
						'tagName' => 'footer',
					)
				),
			)
		)
	);
}

/**
 * Builds the markup of the archive template of an entity.
 *
 * @param array<string, mixed> $entity Normalized entity.
 * @return string Block markup.
 */
function glinf_entity_archive_template_markup( array $entity ): string {
	return glinf_entity_loop_template_markup(
		glinf_entity_post_type( $entity['slug'] ),
		glinf_entity_card_pattern_slug( $entity['slug'] )
	);
}

/**
 * Builds the markup of the term archive template of a taxonomy.
 *
 * A taxonomy attached to ONE entity uses that entity's card (its term archive
 * looks like the entity archive); a taxonomy attached to several uses the
 * generic card, because the items come from different entities.
 *
 * @param array<string, mixed> $taxonomy Normalized taxonomy with at least one entity.
 * @return string Block markup.
 */
function glinf_taxonomy_template_markup( array $taxonomy ): string {
	$entities = $taxonomy['entities'];

	$card_slug = 1 === count( $entities )
		? glinf_entity_card_pattern_slug( $entities[0] )
		: GLINF_ENTITIES_GENERIC_CARD_PATTERN;

	return glinf_entity_loop_template_markup( glinf_entity_post_type( $entities[0] ), $card_slug );
}

/**
 * Builds the comments section markup (core Comments block).
 *
 * @return string[] Lines of markup.
 */
function glinf_entity_comments_markup(): array {
	return array(
		glinf_entity_block_open( 'comments' ),
		'<div class="wp-block-comments">',
		"\t" . glinf_entity_block_void( 'comments-title' ),
		'',
		"\t" . glinf_entity_block_open( 'comment-template' ),
		"\t\t" . glinf_entity_block_void( 'comment-author-name' ),
		"\t\t" . glinf_entity_block_void( 'comment-date' ),
		"\t\t" . glinf_entity_block_void( 'comment-content' ),
		"\t\t" . glinf_entity_block_void( 'comment-reply-link' ),
		"\t" . glinf_entity_block_close( 'comment-template' ),
		'',
		"\t" . glinf_entity_block_open( 'comments-pagination' ),
		"\t\t" . glinf_entity_block_void( 'comments-pagination-previous' ),
		"\t\t" . glinf_entity_block_void( 'comments-pagination-numbers' ),
		"\t\t" . glinf_entity_block_void( 'comments-pagination-next' ),
		"\t" . glinf_entity_block_close( 'comments-pagination' ),
		'',
		"\t" . glinf_entity_block_void( 'post-comments-form' ),
		'</div>',
		glinf_entity_block_close( 'comments' ),
	);
}

/**
 * Builds the markup of the single template of an entity.
 *
 * Same structure as templates/single.html; the title, featured image, content
 * and comments blocks depend on the entity features, and one post terms block
 * is added for each taxonomy attached to the entity.
 *
 * @param array<string, mixed>             $entity     Normalized entity.
 * @param array<int, array<string, mixed>> $taxonomies Taxonomies attached to the entity (see glinf_get_entity_taxonomies()).
 * @return string Block markup.
 */
function glinf_entity_single_template_markup( array $entity, array $taxonomies ): string {
	$supports = $entity['supports'];
	$blocks   = array();

	if ( in_array( 'title', $supports, true ) ) {
		$blocks[] = glinf_entity_block_void( 'post-title', array( 'level' => 1 ) );
	}

	if ( in_array( 'thumbnail', $supports, true ) ) {
		$blocks[] = glinf_entity_block_void( 'post-featured-image' );
	}

	foreach ( $taxonomies as $taxonomy ) {
		$blocks[] = glinf_entity_block_void(
			'post-terms',
			array(
				'term'   => glinf_taxonomy_key( $taxonomy['slug'] ),
				// The prefix is printed as HTML by the block: escape the user-provided name.
				'prefix' => esc_html( $taxonomy['plural'] ) . ': ',
			)
		);
	}

	if ( in_array( 'editor', $supports, true ) ) {
		$blocks[] = glinf_entity_block_void( 'post-content', array( 'layout' => array( 'type' => 'constrained' ) ) );
	}

	if ( in_array( 'comments', $supports, true ) ) {
		$blocks[] = glinf_entity_comments_markup();
	}

	$body = array();
	foreach ( $blocks as $block ) {
		if ( array() !== $body ) {
			$body[] = '';
		}
		foreach ( (array) $block as $line ) {
			$body[] = $line;
		}
	}

	$main = array_merge(
		array(
			glinf_entity_block_open(
				'group',
				array(
					'tagName' => 'main',
					'layout'  => array( 'type' => 'constrained' ),
				)
			),
			'<main class="wp-block-group">',
		),
		glinf_entity_indent( $body ),
		array(
			'</main>',
			glinf_entity_block_close( 'group' ),
		)
	);

	return implode(
		"\n",
		array_merge(
			array(
				glinf_entity_block_void(
					'template-part',
					array(
						'slug'    => 'header',
						'tagName' => 'header',
					)
				),
				'',
			),
			$main,
			array(
				'',
				glinf_entity_block_void(
					'template-part',
					array(
						'slug'    => 'footer',
						'tagName' => 'footer',
					)
				),
			)
		)
	);
}

/**
 * Registers the single and archive templates of every entity and the term template of every taxonomy.
 *
 * Template slugs follow the WordPress hierarchy ("single-{post_type}",
 * "archive-{post_type}" and "taxonomy-{taxonomy}") so they are picked before
 * the generic ones. Taxonomy keys only contain lowercase letters, numbers and
 * underscores (the slug format), which is what the core accepts in a template name.
 *
 * @return void
 */
function glinf_register_entity_templates(): void {
	// WordPress < 6.7: no template registration API, generic templates apply.
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}

	$config = glinf_get_config();

	foreach ( $config['items'] as $entity ) {
		$post_type = glinf_entity_post_type( $entity['slug'] );

		register_block_template(
			GLINF_ENTITIES_TEMPLATE_NAMESPACE . '//single-' . $post_type,
			array(
				/* translators: %s: singular name of the entity, e.g. "Event". */
				'title'       => sprintf( __( 'Single: %s', 'gl-infinite-theme' ), $entity['singular'] ),
				/* translators: %s: singular name of the entity. */
				'description' => sprintf( __( 'Displays a single %s.', 'gl-infinite-theme' ), $entity['singular'] ),
				'content'     => glinf_entity_single_template_markup( $entity, glinf_get_entity_taxonomies( $entity['slug'], $config['taxonomies'] ) ),
			)
		);

		if ( $entity['has_archive'] ) {
			register_block_template(
				GLINF_ENTITIES_TEMPLATE_NAMESPACE . '//archive-' . $post_type,
				array(
					/* translators: %s: plural name of the entity, e.g. "Events". */
					'title'       => sprintf( __( 'Archive: %s', 'gl-infinite-theme' ), $entity['plural'] ),
					/* translators: %s: plural name of the entity. */
					'description' => sprintf( __( 'Displays the archive of %s.', 'gl-infinite-theme' ), $entity['plural'] ),
					'content'     => glinf_entity_archive_template_markup( $entity ),
				)
			);
		}
	}

	foreach ( $config['taxonomies'] as $taxonomy ) {
		// Not registered without entities (see glinf_register_taxonomy()): a template would never be used.
		if ( array() === $taxonomy['entities'] ) {
			continue;
		}

		register_block_template(
			GLINF_ENTITIES_TEMPLATE_NAMESPACE . '//taxonomy-' . glinf_taxonomy_key( $taxonomy['slug'] ),
			array(
				/* translators: %s: plural name of the taxonomy, e.g. "Genres". */
				'title'       => sprintf( __( 'Taxonomy: %s', 'gl-infinite-theme' ), $taxonomy['plural'] ),
				/* translators: %s: plural name of the taxonomy. */
				'description' => sprintf( __( 'Displays the items of a term of %s.', 'gl-infinite-theme' ), $taxonomy['plural'] ),
				'content'     => glinf_taxonomy_template_markup( $taxonomy ),
			)
		);
	}
}
add_action( 'init', 'glinf_register_entity_templates' );

/**
 * Registers the pattern category, the generic card and the card pattern of every entity.
 *
 * Titles and descriptions are plain text (they were sanitized when saved and
 * the editor prints them as text), so they are not HTML-escaped here: doing so
 * would show "&amp;" to the user. Anything that lands in block markup is
 * escaped where the markup is built.
 *
 * @return void
 */
function glinf_register_entity_patterns(): void {
	$entities = glinf_get_entities();

	if ( array() === $entities ) {
		return;
	}

	register_block_pattern_category(
		GLINF_ENTITIES_PATTERN_CATEGORY,
		array( 'label' => __( 'Entities', 'gl-infinite-theme' ) )
	);

	register_block_pattern(
		GLINF_ENTITIES_GENERIC_CARD_PATTERN,
		array(
			'title'       => __( 'Entity card (generic)', 'gl-infinite-theme' ),
			'description' => __( 'Card for items of any entity. Used in the archives of taxonomies shared by several entities.', 'gl-infinite-theme' ),
			'categories'  => array( GLINF_ENTITIES_PATTERN_CATEGORY ),
			'inserter'    => true,
			'content'     => glinf_entity_generic_card_markup(),
		)
	);

	foreach ( $entities as $entity ) {
		register_block_pattern(
			glinf_entity_card_pattern_slug( $entity['slug'] ),
			array(
				/* translators: %s: plural name of the entity, e.g. "Events". */
				'title'       => sprintf( __( '%s card', 'gl-infinite-theme' ), $entity['plural'] ),
				/* translators: %s: plural name of the entity. */
				'description' => sprintf( __( 'Card used for each item in the %s archive.', 'gl-infinite-theme' ), $entity['plural'] ),
				'categories'  => array( GLINF_ENTITIES_PATTERN_CATEGORY ),
				'inserter'    => true,
				'content'     => glinf_entity_card_markup( $entity ),
			)
		);
	}
}
add_action( 'init', 'glinf_register_entity_patterns' );
