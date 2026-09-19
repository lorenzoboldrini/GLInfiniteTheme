<?php
/**
 * Entity Manager dashboard: reading the site and printing the summary.
 *
 * The dashboard sits under the tabs of the two LIST screens (Entities and
 * Taxonomies); the forms and the delete confirmations do not show it. It has two
 * parts:
 *
 * - counter cards, specific to the screen (glinf_dashboard_stats());
 * - the "Health checks" panel, the same on both screens (glinf_dashboard_get_health_checks()).
 *
 * It is READ-ONLY: it changes nothing and has no button that does. Every problem
 * comes with links to the screen where the user fixes it. What is computed is in
 * dashboard-data.php (pure functions); this file only gathers the facts that need
 * WordPress (glinf_dashboard_build_context()) and prints the result.
 *
 * Nothing is persisted: no transient, no option. The numbers are computed on each
 * visit of a list screen (admin only, never on the front end), so they are always
 * fresh. The totals of content items and terms reuse the per-request memo of
 * glinf_entities_count_items() and glinf_taxonomies_count_terms(), which the list
 * columns read too: the database is asked once for each of them.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** Most rows read for the customized templates and for the pages with the same path (a hard bound, far above what can exist: at most 60 templates are generated). */
const GLINF_DASHBOARD_QUERY_LIMIT = 200;

/**
 * Finds the templates saved in the database (customized in the Site Editor) for the theme's generated slugs.
 *
 * ONE query, restricted to what WordPress itself uses to decide which template
 * wins: published `wp_template` rows tagged with the active theme (the `wp_theme`
 * term, matched by name against get_stylesheet(), exactly like get_block_templates()
 * does), whose slug starts with one of the prefixes templates.php gives to the
 * templates it generates. It is a prepared query rather than get_block_templates()
 * or WP_Query because:
 *
 * - get_block_templates() also reads the template files of the theme and of every
 *   plugin, and can only look up slugs known in advance, while the orphaned
 *   templates (whose entity is gone) can only be found by their prefix;
 * - WP_Query cannot match a prefix, and would load the whole content of every
 *   template (with three more queries for the theme term) to read one column.
 *
 * Only the slugs are read. Nothing is cached, so the answer is always current.
 *
 * @return string[] Slugs (post names) of the saved templates.
 */
function glinf_dashboard_find_custom_templates(): array {
	global $wpdb;

	// The result is only used to print this screen; a cache would show templates that were just reset.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only, prepared, one bounded admin-only query; see the docblock.
	$slugs = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.post_name FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->term_relationships} AS tr ON tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id
			WHERE p.post_type = 'wp_template'
			AND p.post_status = 'publish'
			AND tt.taxonomy = 'wp_theme'
			AND t.name = %s
			AND ( p.post_name LIKE %s OR p.post_name LIKE %s OR p.post_name LIKE %s )
			ORDER BY p.post_name ASC
			LIMIT %d",
			get_stylesheet(),
			$wpdb->esc_like( 'single-' . GLINF_POST_TYPE_PREFIX ) . '%',
			$wpdb->esc_like( 'archive-' . GLINF_POST_TYPE_PREFIX ) . '%',
			$wpdb->esc_like( 'taxonomy-' . GLINF_TAXONOMY_PREFIX ) . '%',
			GLINF_DASHBOARD_QUERY_LIMIT
		)
	);

	return is_array( $slugs ) ? array_map( 'strval', $slugs ) : array();
}

/**
 * Finds the published pages and posts that have the same path as a URL base.
 *
 * ONE query for all the bases (top-level items only: a child page has a longer path).
 *
 * @param string[] $bases URL bases to look for.
 * @return array<int, array{path: string, type: string, title: string, edit_url: string}>
 */
function glinf_dashboard_find_content_paths( array $bases ): array {
	if ( array() === $bases ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'              => array( 'page', 'post' ),
			'post_status'            => 'publish',
			'post_name__in'          => array_values( array_unique( $bases ) ),
			'post_parent'            => 0,
			'posts_per_page'         => GLINF_DASHBOARD_QUERY_LIMIT,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'orderby'                => 'none',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'lazy_load_term_meta'    => false,
		)
	);

	$paths = array();
	foreach ( $query->posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$edit = get_edit_post_link( $post->ID, 'raw' );

		$paths[] = array(
			'path'     => $post->post_name,
			'type'     => 'post' === $post->post_type ? 'post' : 'page',
			'title'    => '' !== $post->post_title ? $post->post_title : $post->post_name,
			'edit_url' => is_string( $edit ) ? $edit : '',
		);
	}

	return $paths;
}

/**
 * Gathers the facts about the site that the health checks need.
 *
 * Reads: two autoloaded options (no query), the registered post types and
 * taxonomies (memory), and at most two queries: one for the customized templates
 * and one for the pages and posts with the same path as a URL base. Links the user
 * cannot follow are left out ('').
 *
 * @param array<string, mixed> $config Normalized configuration.
 * @return array<string, mixed> Context for glinf_dashboard_get_health_checks().
 */
function glinf_dashboard_build_context( array $config ): array {
	$rules = get_option( 'rewrite_rules' );

	$bases = array();
	foreach ( glinf_dashboard_build_elements( $config ) as $element ) {
		if ( $element['active'] ) {
			$bases[] = $element['base'];
		}
	}

	return array(
		'permalink_structure' => (string) get_option( 'permalink_structure', '' ),
		// Not generated yet: the option is empty (or not an array) and the checks then say nothing.
		'rewrite_rules'       => is_array( $rules ) ? $rules : array(),
		'registered_routes'   => glinf_get_registered_rewrite_routes(),
		'content_paths'       => glinf_dashboard_find_content_paths( $bases ),
		'custom_templates'    => glinf_dashboard_find_custom_templates(),
		'urls'                => array(
			'permalinks'  => current_user_can( 'manage_options' ) ? admin_url( 'options-permalink.php' ) : '',
			// The list of the templates: the deep link to one template has changed across versions, this one is handled by core itself.
			'site_editor' => current_user_can( 'edit_theme_options' ) ? add_query_arg( 'p', '/template', admin_url( 'site-editor.php' ) ) : '',
		),
	);
}

/**
 * Computes the totals that need the database, for the counters of a screen.
 *
 * Entities: the items of every entity. Taxonomies: the terms of every registered
 * taxonomy (a taxonomy attached to no entity is not registered and has no count).
 * Both go through the per-request memo shared with the list columns.
 *
 * @param string               $section "entities" or "taxonomies".
 * @param array<string, mixed> $config  Normalized configuration.
 * @return array{contents: int, terms: int}
 */
function glinf_dashboard_collect_counts( string $section, array $config ): array {
	$counts = array(
		'contents' => 0,
		'terms'    => 0,
	);

	if ( 'taxonomies' === $section ) {
		foreach ( $config['taxonomies'] as $taxonomy ) {
			$counts['terms'] += glinf_taxonomies_count_terms( glinf_taxonomy_key( $taxonomy['slug'] ) ) ?? 0;
		}

		return $counts;
	}

	foreach ( $config['items'] as $entity ) {
		$counts['contents'] += glinf_entities_count_items( $entity['slug'] );
	}

	return $counts;
}

/**
 * Prints the counter cards.
 *
 * A list (one card per item). A card is a link when its counter has a filtered
 * view to lead to; the label of a link is underlined, so it is not the colour
 * alone that tells it apart.
 *
 * @param array<int, array{id: string, label: string, value: int, max: int, url: string}> $stats Result of glinf_dashboard_stats().
 * @return void
 */
function glinf_dashboard_render_stats( array $stats ): void {
	?>
	<ul class="glinf-em-stats">
		<?php foreach ( $stats as $stat ) : ?>
			<?php
			$max_text = $stat['max'] > 0
				? sprintf(
					/* translators: %s: maximum number of items, e.g. "20" in "3 of 20". */
					__( 'of %s', 'gl-infinite-theme' ),
					number_format_i18n( $stat['max'] )
				)
				: '';
			?>
			<li class="glinf-em-stat glinf-em-stat--<?php echo esc_attr( $stat['id'] ); ?>">
				<?php if ( '' !== $stat['url'] ) : ?>
					<a class="glinf-em-stat__body glinf-em-stat__body--link" href="<?php echo esc_url( $stat['url'] ); ?>">
						<span class="glinf-em-stat__value"><?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?></span>
						<span class="glinf-em-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
					</a>
				<?php else : ?>
					<span class="glinf-em-stat__body">
						<span class="glinf-em-stat__value"><?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?><?php if ( '' !== $max_text ) : ?> <span class="glinf-em-stat__max"><?php echo esc_html( $max_text ); ?></span><?php endif; ?></span>
						<span class="glinf-em-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
					</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Prints an action link of a check, with the name of what it acts on for screen readers.
 *
 * @param array{label: string, url: string, sr?: string} $link Link.
 * @return void
 */
function glinf_dashboard_render_link( array $link ): void {
	if ( '' === $link['url'] ) {
		return;
	}
	?>
	<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?><?php if ( ! empty( $link['sr'] ) ) : ?> <span class="screen-reader-text"><?php echo esc_html( $link['sr'] ); ?></span><?php endif; ?></a>
	<?php
}

/**
 * Prints the label of a severity: an icon and a word (never colour alone).
 *
 * @param string $severity "warning" or "notice".
 * @return void
 */
function glinf_dashboard_render_severity( string $severity ): void {
	$is_warning = 'warning' === $severity;
	?>
	<span class="glinf-em-severity glinf-em-severity--<?php echo esc_attr( $is_warning ? 'warning' : 'notice' ); ?>">
		<span class="dashicons <?php echo esc_attr( $is_warning ? 'dashicons-warning' : 'dashicons-info' ); ?>" aria-hidden="true"></span>
		<span class="glinf-em-severity__text"><?php echo esc_html( $is_warning ? __( 'Warning', 'gl-infinite-theme' ) : __( 'Notice', 'gl-infinite-theme' ) ); ?></span>
	</span>
	<?php
}

/**
 * Prints one health check.
 *
 * @param array<string, mixed> $check A check from glinf_dashboard_get_health_checks().
 * @return void
 */
function glinf_dashboard_render_check( array $check ): void {
	?>
	<li class="glinf-em-check glinf-em-check--<?php echo esc_attr( 'warning' === $check['severity'] ? 'warning' : 'notice' ); ?>">
		<p class="glinf-em-check__message">
			<?php glinf_dashboard_render_severity( (string) $check['severity'] ); ?>
			<?php echo esc_html( $check['message'] ); ?>
		</p>

		<?php if ( array() !== $check['items'] ) : ?>
			<ul class="glinf-em-check__items">
				<?php foreach ( $check['items'] as $item ) : ?>
					<li>
						<strong><?php echo esc_html( $item['label'] ); ?></strong>
						<?php if ( '' !== $item['code'] ) : ?>
							<code><?php echo esc_html( $item['code'] ); ?></code>
						<?php endif; ?>
						<?php if ( '' !== $item['detail'] ) : ?>
							<span class="glinf-em-check__detail"><?php echo esc_html( $item['detail'] ); ?></span>
						<?php endif; ?>
						<?php if ( array() !== $item['links'] ) : ?>
							<span class="glinf-em-check__links">
								<?php foreach ( $item['links'] as $link ) : ?>
									<?php glinf_dashboard_render_link( $link ); ?>
								<?php endforeach; ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( array() !== $check['actions'] ) : ?>
			<p class="glinf-em-check__actions">
				<?php foreach ( $check['actions'] as $action ) : ?>
					<a class="button" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a>
				<?php endforeach; ?>
			</p>
		<?php endif; ?>
	</li>
	<?php
}

/**
 * Prints the "Health checks" panel.
 *
 * A disclosure (details/summary: keyboard operable and announced by screen
 * readers, no JavaScript). The summary carries the verdict, so it is readable
 * with the panel closed. It is open when there is something to read, and closed
 * when everything is fine: the positive state is stated in the summary and needs
 * no space of its own.
 *
 * @param array<int, array<string, mixed>> $checks Result of glinf_dashboard_get_health_checks().
 * @return void
 */
function glinf_dashboard_render_health( array $checks ): void {
	$counts = glinf_dashboard_count_checks( $checks );
	?>
	<details class="glinf-em-health"<?php echo array() !== $checks ? ' open' : ''; ?>>
		<summary class="glinf-em-health__summary">
			<span class="glinf-em-health__title"><?php esc_html_e( 'Health checks', 'gl-infinite-theme' ); ?></span>
			<?php if ( array() === $checks ) : ?>
				<span class="glinf-em-status glinf-em-status--ok">
					<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'No issues found', 'gl-infinite-theme' ); ?>
				</span>
			<?php endif; ?>
			<?php if ( $counts['warning'] > 0 ) : ?>
				<span class="glinf-em-status glinf-em-status--warning">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of warnings. */
							_n( '%s warning', '%s warnings', $counts['warning'], 'gl-infinite-theme' ),
							number_format_i18n( $counts['warning'] )
						)
					);
					?>
				</span>
			<?php endif; ?>
			<?php if ( $counts['notice'] > 0 ) : ?>
				<span class="glinf-em-status glinf-em-status--notice">
					<span class="dashicons dashicons-info" aria-hidden="true"></span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of notices. */
							_n( '%s notice', '%s notices', $counts['notice'], 'gl-infinite-theme' ),
							number_format_i18n( $counts['notice'] )
						)
					);
					?>
				</span>
			<?php endif; ?>
		</summary>

		<?php if ( array() === $checks ) : ?>
			<p class="glinf-em-health__ok"><?php esc_html_e( 'Everything looks fine: the limits, the addresses, the rewrite rules and the templates of your entities and taxonomies are in order.', 'gl-infinite-theme' ); ?></p>
		<?php else : ?>
			<ul class="glinf-em-checks">
				<?php foreach ( $checks as $check ) : ?>
					<?php glinf_dashboard_render_check( $check ); ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</details>
	<?php
}

/**
 * Prints the dashboard at the top of a list screen.
 *
 * Called by the two list renderers, right after the notice and before the intro
 * and the list; never by the forms or the delete confirmations. With an empty
 * configuration the counters would all be zero, so only the health panel is
 * printed, and only when there is something to report (for instance saved
 * templates left behind by entities that were all deleted).
 *
 * @param string $section "entities" or "taxonomies".
 * @return void
 */
function glinf_entities_render_dashboard( string $section ): void {
	$section  = 'taxonomies' === $section ? 'taxonomies' : 'entities';
	$config   = glinf_get_config();
	$checks   = glinf_dashboard_get_health_checks( $config, glinf_dashboard_build_context( $config ) );
	$is_empty = array() === $config['items'] && array() === $config['taxonomies'];

	if ( $is_empty && array() === $checks ) {
		return;
	}

	$title_id = 'glinf-em-dashboard-title-' . $section;
	?>
	<section class="glinf-em-dashboard" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
		<h2 id="<?php echo esc_attr( $title_id ); ?>" class="screen-reader-text"><?php esc_html_e( 'Overview', 'gl-infinite-theme' ); ?></h2>

		<?php
		if ( ! $is_empty ) {
			glinf_dashboard_render_stats( glinf_dashboard_stats( $section, $config, glinf_dashboard_collect_counts( $section, $config ) ) );
		}

		glinf_dashboard_render_health( $checks );
		?>
	</section>
	<?php
}
