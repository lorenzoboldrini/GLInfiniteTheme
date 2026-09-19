<?php
/**
 * Server-side render for the glinf/cpt-grid block.
 *
 * Lists published posts of one public post type as a grid of cards. Every
 * attribute is validated here: the editor input is never trusted (the block
 * can also be saved by a filtered user or edited by hand in the code editor).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Post types that are viewable but make no sense as a grid of cards.
$glinf_excluded_post_types = array( 'attachment' );

// Post type: it must exist and be viewable on the front end, otherwise fall back to "post".
$glinf_post_type = isset( $attributes['postType'] ) && is_string( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';
if (
	'' === $glinf_post_type
	|| in_array( $glinf_post_type, $glinf_excluded_post_types, true )
	|| ! post_type_exists( $glinf_post_type )
	|| ! is_post_type_viewable( $glinf_post_type )
) {
	$glinf_post_type = 'post';
}

// Numbers: clamp to the same ranges declared in block.json.
$glinf_per_page = isset( $attributes['postsPerPage'] ) ? min( 24, max( 1, absint( $attributes['postsPerPage'] ) ) ) : 6;
$glinf_columns  = isset( $attributes['columns'] ) ? min( 6, max( 1, absint( $attributes['columns'] ) ) ) : 3;

// Sorting: whitelist only. "rand" is left out on purpose: it defeats the query cache.
$glinf_order_by = isset( $attributes['orderBy'] ) && is_string( $attributes['orderBy'] ) ? $attributes['orderBy'] : 'date';
if ( ! in_array( $glinf_order_by, array( 'date', 'title', 'modified', 'menu_order' ), true ) ) {
	$glinf_order_by = 'date';
}
$glinf_order = isset( $attributes['order'] ) && is_string( $attributes['order'] ) && 'ASC' === strtoupper( $attributes['order'] ) ? 'ASC' : 'DESC';

// Heading level: the tag name is built only from a validated integer.
$glinf_title_level = isset( $attributes['titleLevel'] ) ? absint( $attributes['titleLevel'] ) : 3;
if ( ! in_array( $glinf_title_level, array( 2, 3, 4, 5, 6 ), true ) ) {
	$glinf_title_level = 3;
}
$glinf_title_tag = 'h' . $glinf_title_level;

// Display options: anything but a real boolean falls back to the default.
$glinf_show_image   = isset( $attributes['showImage'] ) && is_bool( $attributes['showImage'] ) ? $attributes['showImage'] : true;
$glinf_show_excerpt = isset( $attributes['showExcerpt'] ) && is_bool( $attributes['showExcerpt'] ) ? $attributes['showExcerpt'] : true;
$glinf_show_date    = isset( $attributes['showDate'] ) && is_bool( $attributes['showDate'] ) ? $attributes['showDate'] : true;

// Taxonomy filter: applied only when the taxonomy belongs to the post type and the term exists in it.
$glinf_taxonomy  = isset( $attributes['taxonomy'] ) && is_string( $attributes['taxonomy'] ) ? sanitize_key( $attributes['taxonomy'] ) : '';
$glinf_term_id   = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;
$glinf_tax_query = array();
if (
	'' !== $glinf_taxonomy
	&& $glinf_term_id > 0
	&& taxonomy_exists( $glinf_taxonomy )
	&& is_object_in_taxonomy( $glinf_post_type, $glinf_taxonomy )
	&& get_term( $glinf_term_id, $glinf_taxonomy ) instanceof WP_Term
) {
	$glinf_tax_query[] = array(
		'taxonomy' => $glinf_taxonomy,
		'field'    => 'term_id',
		'terms'    => array( $glinf_term_id ),
	);
}

// menu_order alone is often 0 for every post: add the date as a stable tie-breaker.
$glinf_orderby_arg = 'menu_order' === $glinf_order_by
	? array(
		'menu_order' => $glinf_order,
		'date'       => 'DESC',
	)
	: $glinf_order_by;

/*
 * No transient on purpose. Since WP 6.1 WP_Query caches its results in the
 * object cache, keyed by the query and invalidated through "last_changed", so
 * a second cache layer would only add stale-data risks.
 *
 * The meta and term caches stay off: the card needs no terms, and when images
 * are shown update_post_thumbnail_cache() (called below) primes the meta of all
 * posts and the thumbnails in one batch.
 */
$glinf_query = new WP_Query(
	array(
		'post_type'              => $glinf_post_type,
		'post_status'            => 'publish',
		'posts_per_page'         => $glinf_per_page,
		'orderby'                => $glinf_orderby_arg,
		'order'                  => $glinf_order,
		'tax_query'              => $glinf_tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Only added for a validated filter, and bounded by posts_per_page.
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);

// Nothing to show: print nothing on the front end (the editor shows its own placeholder).
if ( ! $glinf_query->have_posts() ) {
	wp_reset_postdata();
	return;
}

// Prime thumbnails and their metadata in one go, so the loop below runs no queries.
if ( $glinf_show_image ) {
	update_post_thumbnail_cache( $glinf_query );
}

// Approximate rendered width of a card, so the browser picks a fitting image size.
$glinf_image_sizes = sprintf(
	'(max-width: 600px) 100vw, (max-width: 900px) %1$dvw, %2$dvw',
	(int) round( 100 / min( $glinf_columns, 2 ) ),
	(int) round( 100 / $glinf_columns )
);

// The excerpt is a short teaser: a fixed word count keeps the cards even.
$glinf_excerpt_words = 20;

/*
 * Both column counts are validated integers, so the inline style cannot carry
 * anything else. The medium value caps the columns at two for the tablet
 * breakpoint (CSS cannot clamp a repeat() count itself).
 */
$glinf_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'style' => sprintf(
			'--glinf-cpt-grid-columns:%1$d;--glinf-cpt-grid-columns-medium:%2$d;',
			$glinf_columns,
			min( $glinf_columns, 2 )
		),
	)
);
?>
<div <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php // The explicit role keeps list semantics in Safari/VoiceOver, which drops them when list-style is none. ?>
	<ul class="glinf-cpt-grid__list" role="list">
		<?php foreach ( $glinf_query->posts as $glinf_post ) : ?>
			<?php
			$glinf_permalink = get_permalink( $glinf_post );
			if ( ! $glinf_permalink ) {
				continue;
			}

			$glinf_title = get_the_title( $glinf_post );
			if ( '' === trim( $glinf_title ) ) {
				$glinf_title = __( '(no title)', 'gl-infinite-theme' );
			}

			// Decorative image (alt=""): the title link right after it already names the item, so it gets no tab stop of its own.
			$glinf_image = $glinf_show_image
				? get_the_post_thumbnail(
					$glinf_post,
					'large',
					array(
						'alt'   => '',
						'class' => 'glinf-cpt-grid__image',
						'sizes' => $glinf_image_sizes,
					)
				)
				: '';

			// Never leak the text of a password-protected post.
			$glinf_excerpt = '';
			if ( $glinf_show_excerpt && ! post_password_required( $glinf_post ) ) {
				$glinf_excerpt_source = '' !== trim( $glinf_post->post_excerpt )
					? $glinf_post->post_excerpt
					: strip_shortcodes( excerpt_remove_blocks( $glinf_post->post_content ) );

				/*
				 * Not get_the_excerpt(): without a manual excerpt it runs the
				 * "the_content" filters on the whole post, which is slow and can
				 * render other dynamic blocks (even this one) for every card.
				 */
				$glinf_excerpt = wp_trim_words( $glinf_excerpt_source, $glinf_excerpt_words, '&hellip;' );
			}
			?>
			<li class="glinf-cpt-grid__item">
				<article class="glinf-cpt-grid__card">
					<?php if ( '' !== $glinf_image ) : ?>
						<?php echo $glinf_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-built <img>, attributes escaped by core. wp_kses_post() would strip srcset/sizes/fetchpriority. ?>
					<?php endif; ?>

					<<?php echo tag_escape( $glinf_title_tag ); ?> class="glinf-cpt-grid__title">
						<a class="glinf-cpt-grid__link" href="<?php echo esc_url( $glinf_permalink ); ?>"><?php echo esc_html( $glinf_title ); ?></a>
					</<?php echo tag_escape( $glinf_title_tag ); ?>>

					<?php if ( $glinf_show_date ) : ?>
						<time class="glinf-cpt-grid__date" datetime="<?php echo esc_attr( get_the_date( 'c', $glinf_post ) ); ?>"><?php echo esc_html( get_the_date( '', $glinf_post ) ); ?></time>
					<?php endif; ?>

					<?php if ( '' !== $glinf_excerpt ) : ?>
						<p class="glinf-cpt-grid__excerpt"><?php echo esc_html( $glinf_excerpt ); ?></p>
					<?php endif; ?>
				</article>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php
// The loop above never touches the global $post; reset anyway in case a filter did.
wp_reset_postdata();
