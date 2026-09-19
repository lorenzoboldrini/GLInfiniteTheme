<?php
/**
 * Entity Manager admin: the top-level menu and the "Entities" screen (list,
 * add/edit form and the save/delete handlers).
 *
 * The top-level "Entity Manager" menu has two sub-pages: "Entities" (this file,
 * `admin.php?page=glinf-entities`, which renders both the list and the form:
 * `&action=new` / `&action=edit&entity=<slug>`) and "Taxonomies" (see
 * admin-taxonomies.php). Writes go through `admin-post.php`. Every entry point
 * requires the "manage_theme_entities" capability, and every write also
 * requires a nonce. No CSS or JS is enqueued: only core wp-admin classes are used.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** Menu page slug. */
const GLINF_ENTITIES_PAGE = 'glinf-entities';

/** Lifetime, in seconds, of the transient used to refill the form after an error. */
const GLINF_ENTITIES_FORM_STATE_TTL = 60;

/**
 * Registers the top-level "Entity Manager" menu and its "Entities" sub-page.
 *
 * Adding a sub-page with the SAME slug as the top-level one renames the entry
 * that WordPress creates automatically (which would otherwise repeat the
 * top-level title). The "Taxonomies" sub-page is added by admin-taxonomies.php,
 * after this one, so it comes second.
 *
 * @return void
 */
function glinf_entities_admin_menu(): void {
	add_menu_page(
		__( 'Entity Manager', 'gl-infinite-theme' ),
		__( 'Entity Manager', 'gl-infinite-theme' ),
		GLINF_ENTITIES_CAP,
		GLINF_ENTITIES_PAGE,
		'glinf_render_entities_page',
		'dashicons-database',
		61
	);

	add_submenu_page(
		GLINF_ENTITIES_PAGE,
		__( 'Entities', 'gl-infinite-theme' ),
		__( 'Entities', 'gl-infinite-theme' ),
		GLINF_ENTITIES_CAP,
		GLINF_ENTITIES_PAGE,
		'glinf_render_entities_page'
	);
}
add_action( 'admin_menu', 'glinf_entities_admin_menu' );

/**
 * Stops the request with a 403 unless the current user has the capability.
 *
 * Used by the screen and by every handler: a menu that is merely hidden is not
 * an access control.
 *
 * @return void
 */
function glinf_entities_require_cap(): void {
	if ( ! current_user_can( GLINF_ENTITIES_CAP ) ) {
		wp_die(
			esc_html__( 'Sorry, you are not allowed to use the Entity Manager.', 'gl-infinite-theme' ),
			'',
			array( 'response' => 403 )
		);
	}
}

/**
 * Stops the request with a 405 unless it is a POST.
 *
 * `check_admin_referer()` also accepts nonces from the query string, so the
 * method is enforced separately for the handlers that change state.
 *
 * @return void
 */
function glinf_entities_require_post(): void {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

	if ( 'POST' !== $method ) {
		wp_die(
			esc_html__( 'This action requires a POST request.', 'gl-infinite-theme' ),
			'',
			array( 'response' => 405 )
		);
	}
}

/**
 * Builds an URL of the Entity Manager screen.
 *
 * @param array<string, string> $args Extra query arguments (must be safe, whitelisted values).
 * @return string
 */
function glinf_entities_admin_url( array $args = array() ): string {
	return add_query_arg(
		array_merge( array( 'page' => GLINF_ENTITIES_PAGE ), $args ),
		admin_url( 'admin.php' )
	);
}

/**
 * Redirects to the Entity Manager screen and stops.
 *
 * Only whitelisted codes and validated slugs are ever placed in the URL: user
 * text is never reflected.
 *
 * @param array<string, string> $args Query arguments.
 * @return never
 */
function glinf_entities_redirect( array $args = array() ): never {
	wp_safe_redirect( glinf_entities_admin_url( $args ) );
	exit;
}

/**
 * Returns the messages the screen can show, keyed by the code used in the URL.
 *
 * Being the only source of accepted codes, this is also the whitelist.
 *
 * @return array<string, array{type: string, text: string}>
 */
function glinf_entities_get_messages(): array {
	$messages = array(
		'saved'             => array(
			'type' => 'success',
			'text' => __( 'Entity saved.', 'gl-infinite-theme' ),
		),
		'deleted'           => array(
			'type' => 'success',
			'text' => __( 'Entity configuration deleted. Its content was not deleted. Its taxonomies stay, detached from it.', 'gl-infinite-theme' ),
		),
		'slug_invalid'      => array(
			'type' => 'error',
			'text' => __( 'The slug must be 3 to 14 characters long, start with a letter, end with a letter or a number, and contain only lowercase letters, numbers and underscores.', 'gl-infinite-theme' ),
		),
		'slug_reserved'     => array(
			'type' => 'error',
			'text' => __( 'This slug is reserved by WordPress. Please choose another one.', 'gl-infinite-theme' ),
		),
		'slug_exists'       => array(
			'type' => 'error',
			'text' => __( 'This slug is already used by an entity or a taxonomy. Please choose another one.', 'gl-infinite-theme' ),
		),
		'slug_conflict'     => array(
			'type' => 'error',
			'text' => __( 'This slug conflicts with an existing content type, taxonomy, page or URL. Please choose another one.', 'gl-infinite-theme' ),
		),
		'name_invalid'      => array(
			'type' => 'error',
			'text' => __( 'The singular and plural names are required and must be 1 to 40 characters long.', 'gl-infinite-theme' ),
		),
		'supports_required' => array(
			'type' => 'error',
			'text' => __( 'Select at least one feature.', 'gl-infinite-theme' ),
		),
		'limit_reached'     => array(
			'type' => 'error',
			'text' => sprintf(
				/* translators: %d: maximum number of entities. */
				__( 'You can create up to %d entities. Delete one to add another.', 'gl-infinite-theme' ),
				GLINF_ENTITIES_MAX
			),
		),
		'not_found'         => array(
			'type' => 'error',
			'text' => __( 'The requested entity does not exist.', 'gl-infinite-theme' ),
		),
		'save_failed'       => array(
			'type' => 'error',
			'text' => __( 'The entity could not be saved. Please try again.', 'gl-infinite-theme' ),
		),
	);

	return $messages + glinf_entities_get_url_base_messages();
}

/**
 * Returns the messages about the URL base, shared by the Entities and Taxonomies screens.
 *
 * Both screens validate the same field with the same rules, so the codes and
 * texts live in one place and are merged into each screen's whitelist.
 *
 * @return array<string, array{type: string, text: string}>
 */
function glinf_entities_get_url_base_messages(): array {
	return array(
		'url_base_invalid'  => array(
			'type' => 'error',
			'text' => sprintf(
				/* translators: %d: maximum length of a URL base. */
				__( 'The URL base must be a single segment of up to %d characters, with lowercase letters, numbers and hyphens only. It must start and end with a letter or a number and contain at least one letter.', 'gl-infinite-theme' ),
				GLINF_URL_BASE_MAX
			),
		),
		'url_base_reserved' => array(
			'type' => 'error',
			'text' => __( 'This URL base is reserved by WordPress. Please choose another one.', 'gl-infinite-theme' ),
		),
		'url_base_exists'   => array(
			'type' => 'error',
			'text' => __( 'This URL base is already used by another entity or taxonomy. Please choose another one.', 'gl-infinite-theme' ),
		),
		'url_base_conflict' => array(
			'type' => 'error',
			'text' => __( 'This URL base conflicts with an existing content type, taxonomy, page or post. Please choose another one.', 'gl-infinite-theme' ),
		),
	);
}

/**
 * Renders the "URL base" row of the add/edit form, for entities and taxonomies.
 *
 * The slug is immutable, the URL base is not: this is the only field that
 * changes public addresses after creation. When editing, the row shows the
 * current address and a warning that old links stop working (no redirect is
 * created). Everything printed is escaped at the point of output.
 *
 * @param string               $kind    "entity" or "taxonomy".
 * @param array<string, mixed> $item    Entity or taxonomy to show (sanitized).
 * @param bool                 $is_edit True when editing an existing item.
 * @return void
 */
function glinf_entities_render_url_base_row( string $kind, array $item, bool $is_edit ): void {
	$is_taxonomy = 'taxonomy' === $kind;
	$field_id    = 'glinf-' . $kind . '-url-base';
	$field_name  = $is_taxonomy ? 'glinf_taxonomy[url_base]' : 'glinf_entity[url_base]';
	$default     = glinf_default_url_base( $item['slug'] );
	$placeholder = '' !== $default ? $default : __( 'Same as the slug', 'gl-infinite-theme' );

	// The address that is live now comes from storage, not from what was typed in a form that failed.
	$saved       = $is_edit ? ( $is_taxonomy ? glinf_get_taxonomy( $item['slug'] ) : glinf_get_entity( $item['slug'] ) ) : null;
	$current_url = null === $saved ? '' : home_url( '/' . glinf_entity_url_base( $saved ) . '/' );

	if ( $is_taxonomy ) {
		$warning = __( 'Changing the URL base breaks every existing link to the term archives of this taxonomy: the old addresses stop working and no redirect is created. Update the menus, links and bookmarks that point to them.', 'gl-infinite-theme' );
	} else {
		$warning = __( 'Changing the URL base breaks every existing link to the archive and to the items of this entity: the old addresses stop working and no redirect is created. Update the menus, links and bookmarks that point to them.', 'gl-infinite-theme' );
	}
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'URL base', 'gl-infinite-theme' ); ?></label></th>
		<td>
			<input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $item['url_base'] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="regular-text code" maxlength="<?php echo esc_attr( (string) GLINF_URL_BASE_MAX ); ?>" pattern="(?=[^a-z]*[a-z])[a-z0-9]([a-z0-9\-]*[a-z0-9])?" autocapitalize="none" autocomplete="off" spellcheck="false" aria-describedby="<?php echo esc_attr( $field_id . '-desc' . ( $is_edit ? ' ' . $field_id . '-warning' : '' ) ); ?>">
			<p class="description" id="<?php echo esc_attr( $field_id . '-desc' ); ?>">
				<?php
				printf(
					/* translators: %d: maximum length of a URL base. */
					esc_html__( 'The first part of the address, a single segment of up to %d characters: lowercase letters, numbers and hyphens, with at least one letter. Leave it empty to use the slug, with underscores turned into hyphens.', 'gl-infinite-theme' ),
					(int) GLINF_URL_BASE_MAX
				);
				if ( '' !== $current_url ) {
					echo ' ';
					echo wp_kses(
						sprintf(
							/* translators: %s: current address, wrapped in a code element. */
							__( 'Current address: %s', 'gl-infinite-theme' ),
							'<code>' . esc_html( $current_url ) . '</code>'
						),
						array( 'code' => array() )
					);
				}
				?>
			</p>
			<?php if ( $is_edit ) : ?>
				<p class="description" id="<?php echo esc_attr( $field_id . '-warning' ); ?>">
					<strong><?php esc_html_e( 'Warning:', 'gl-infinite-theme' ); ?></strong>
					<?php echo esc_html( $warning ); ?>
				</p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Returns the labels of the whitelisted dashicons, keyed by dashicon class.
 *
 * Keys must match glinf_get_entity_icons(); a missing label falls back to the class.
 *
 * @return array<string, string>
 */
function glinf_entities_get_icon_labels(): array {
	return array(
		'dashicons-admin-post'     => __( 'Pushpin', 'gl-infinite-theme' ),
		'dashicons-admin-page'     => __( 'Page', 'gl-infinite-theme' ),
		'dashicons-admin-media'    => __( 'Media', 'gl-infinite-theme' ),
		'dashicons-admin-comments' => __( 'Comments', 'gl-infinite-theme' ),
		'dashicons-admin-users'    => __( 'Users', 'gl-infinite-theme' ),
		'dashicons-admin-tools'    => __( 'Tools', 'gl-infinite-theme' ),
		'dashicons-admin-home'     => __( 'Home', 'gl-infinite-theme' ),
		'dashicons-admin-links'    => __( 'Link', 'gl-infinite-theme' ),
		'dashicons-book'           => __( 'Book', 'gl-infinite-theme' ),
		'dashicons-book-alt'       => __( 'Open book', 'gl-infinite-theme' ),
		'dashicons-calendar-alt'   => __( 'Calendar', 'gl-infinite-theme' ),
		'dashicons-camera'         => __( 'Camera', 'gl-infinite-theme' ),
		'dashicons-cart'           => __( 'Cart', 'gl-infinite-theme' ),
		'dashicons-clipboard'      => __( 'Clipboard', 'gl-infinite-theme' ),
		'dashicons-megaphone'      => __( 'Megaphone', 'gl-infinite-theme' ),
		'dashicons-store'          => __( 'Store', 'gl-infinite-theme' ),
		'dashicons-tickets-alt'    => __( 'Tickets', 'gl-infinite-theme' ),
		'dashicons-location'       => __( 'Location', 'gl-infinite-theme' ),
		'dashicons-portfolio'      => __( 'Portfolio', 'gl-infinite-theme' ),
		'dashicons-star-filled'    => __( 'Star', 'gl-infinite-theme' ),
		'dashicons-heart'          => __( 'Heart', 'gl-infinite-theme' ),
		'dashicons-awards'         => __( 'Award', 'gl-infinite-theme' ),
		'dashicons-businessman'    => __( 'Businessman', 'gl-infinite-theme' ),
		'dashicons-groups'         => __( 'Group of people', 'gl-infinite-theme' ),
		'dashicons-building'       => __( 'Building', 'gl-infinite-theme' ),
		'dashicons-food'           => __( 'Food', 'gl-infinite-theme' ),
		'dashicons-video-alt2'     => __( 'Video', 'gl-infinite-theme' ),
		'dashicons-images-alt2'    => __( 'Images', 'gl-infinite-theme' ),
		'dashicons-products'       => __( 'Products', 'gl-infinite-theme' ),
		'dashicons-testimonial'    => __( 'Testimonial', 'gl-infinite-theme' ),
		'dashicons-lightbulb'      => __( 'Light bulb', 'gl-infinite-theme' ),
		'dashicons-art'            => __( 'Art', 'gl-infinite-theme' ),
		'dashicons-hammer'         => __( 'Hammer', 'gl-infinite-theme' ),
		'dashicons-airplane'       => __( 'Airplane', 'gl-infinite-theme' ),
		'dashicons-shield'         => __( 'Shield', 'gl-infinite-theme' ),
		'dashicons-tag'            => __( 'Tag', 'gl-infinite-theme' ),
		'dashicons-chart-bar'      => __( 'Chart', 'gl-infinite-theme' ),
		'dashicons-money-alt'      => __( 'Money', 'gl-infinite-theme' ),
		'dashicons-pets'           => __( 'Pets', 'gl-infinite-theme' ),
		'dashicons-database'       => __( 'Database', 'gl-infinite-theme' ),
	);
}

/**
 * Returns the labels of the post type features, keyed by feature.
 *
 * @return array<string, string>
 */
function glinf_entities_get_supports_labels(): array {
	return array(
		'title'         => __( 'Title', 'gl-infinite-theme' ),
		'editor'        => __( 'Editor (content)', 'gl-infinite-theme' ),
		'thumbnail'     => __( 'Featured image', 'gl-infinite-theme' ),
		'excerpt'       => __( 'Excerpt', 'gl-infinite-theme' ),
		'custom-fields' => __( 'Custom fields', 'gl-infinite-theme' ),
		'comments'      => __( 'Comments', 'gl-infinite-theme' ),
	);
}

/**
 * Returns the key of the transient that refills the form of the current user.
 *
 * @return string
 */
function glinf_entities_form_state_key(): string {
	return 'glinf_entity_form_' . get_current_user_id();
}

/**
 * Stores the (already sanitized) form data for one minute and redirects with an error code.
 *
 * @param array<string, mixed> $entity   Sanitized entity as submitted.
 * @param string               $original Slug of the entity being edited, '' when creating.
 * @param string               $code     Error code (a key of glinf_entities_get_messages()).
 * @return never
 */
function glinf_entities_fail( array $entity, string $original, string $code ): never {
	set_transient(
		glinf_entities_form_state_key(),
		array(
			'entity'   => $entity,
			'original' => $original,
		),
		GLINF_ENTITIES_FORM_STATE_TTL
	);

	if ( '' === $original ) {
		glinf_entities_redirect(
			array(
				'action'       => 'new',
				'glinf_notice' => $code,
			)
		);
	}

	glinf_entities_redirect(
		array(
			'action'       => 'edit',
			'entity'       => $original,
			'glinf_notice' => $code,
		)
	);
}

/**
 * Reads and consumes the form data saved after a failed submission.
 *
 * The data is sanitized again: a transient is storage, not a trusted source.
 *
 * @param string $original Slug being edited, '' when creating (must match the stored one).
 * @return array<string, mixed>|null
 */
function glinf_entities_pull_form_state( string $original ): ?array {
	$state = get_transient( glinf_entities_form_state_key() );
	delete_transient( glinf_entities_form_state_key() );

	if (
		! is_array( $state )
		|| ! isset( $state['entity'], $state['original'] )
		|| ! is_array( $state['entity'] )
		|| $state['original'] !== $original
	) {
		return null;
	}

	return glinf_sanitize_entity( $state['entity'] );
}

/**
 * Prints a notice for a whitelisted message code.
 *
 * Shared by the Entities and the Taxonomies screens, each with its own messages.
 *
 * @param string                                           $code     Message code.
 * @param array<string, array{type: string, text: string}> $messages Messages of the screen (the whitelist).
 * @return void
 */
function glinf_entities_render_notice( string $code, array $messages ): void {
	if ( ! isset( $messages[ $code ] ) ) {
		return;
	}

	wp_admin_notice(
		esc_html( $messages[ $code ]['text'] ),
		array(
			'type'        => $messages[ $code ]['type'],
			'dismissible' => true,
			'attributes'  => array( 'role' => 'error' === $messages[ $code ]['type'] ? 'alert' : 'status' ),
		)
	);
}

/**
 * Counts the items of an entity (every status but trash and auto-draft).
 *
 * wp_count_posts() is cached by core, so this does not query more than once per post type.
 *
 * @param string $slug Entity slug.
 * @return int
 */
function glinf_entities_count_items( string $slug ): int {
	$post_type = glinf_entity_post_type( $slug );

	if ( ! post_type_exists( $post_type ) ) {
		return 0;
	}

	$counts = wp_count_posts( $post_type );
	$total  = 0;

	foreach ( array( 'publish', 'future', 'draft', 'pending', 'private' ) as $status ) {
		$total += isset( $counts->$status ) ? (int) $counts->$status : 0;
	}

	return $total;
}

/**
 * Renders the Entity Manager screen (list or form).
 *
 * @return void
 */
function glinf_render_entities_page(): void {
	glinf_entities_require_cap();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only screen routing: every value is whitelisted or validated below and nothing is changed.
	$action      = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$entity_slug = isset( $_GET['entity'] ) ? sanitize_key( wp_unslash( $_GET['entity'] ) ) : '';
	$notice_code = isset( $_GET['glinf_notice'] ) ? sanitize_key( wp_unslash( $_GET['glinf_notice'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$messages = glinf_entities_get_messages();
	if ( ! isset( $messages[ $notice_code ] ) ) {
		$notice_code = '';
	}

	// Only a redirect after a failed save may refill the form: a plain visit must never show stale data.
	$is_error = '' !== $notice_code && 'error' === $messages[ $notice_code ]['type'];

	if ( 'new' === $action ) {
		$entity = ( $is_error ? glinf_entities_pull_form_state( '' ) : null ) ?? glinf_get_default_entity();
		glinf_render_entity_form( $entity, false, $notice_code );
		return;
	}

	if ( 'edit' === $action ) {
		$stored = glinf_get_entity( $entity_slug );

		if ( null !== $stored ) {
			// After a failed save, show what the user typed (the slug always comes from storage).
			$posted = $is_error ? glinf_entities_pull_form_state( $entity_slug ) : null;
			$entity = null === $posted ? $stored : array_merge( $posted, array( 'slug' => $stored['slug'] ) );
			glinf_render_entity_form( $entity, true, $notice_code );
			return;
		}

		$notice_code = 'not_found';
	}

	glinf_render_entities_list( $notice_code );
}

/**
 * Renders the list of entities.
 *
 * @param string $notice_code Message code to show, '' for none.
 * @return void
 */
function glinf_render_entities_list( string $notice_code ): void {
	$config         = glinf_get_config();
	$entities       = $config['items'];
	$can_add        = count( $entities ) < GLINF_ENTITIES_MAX;
	$new_url        = glinf_entities_admin_url( array( 'action' => 'new' ) );
	$taxonomies_url = glinf_taxonomies_admin_url();
	$post_url       = admin_url( 'admin-post.php' );
	$yes_label      = __( 'Yes', 'gl-infinite-theme' );
	$no_label       = __( 'No', 'gl-infinite-theme' );

	$messages      = glinf_entities_get_messages();
	$limit_message = $messages['limit_reached']['text'];
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Entities', 'gl-infinite-theme' ); ?></h1>
		<?php if ( $can_add ) : ?>
			<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Entity', 'gl-infinite-theme' ); ?></a>
		<?php endif; ?>
		<hr class="wp-header-end">

		<?php glinf_entities_render_notice( $notice_code, $messages ); ?>

		<p>
			<?php esc_html_e( 'Entities are custom content types. Each one gets its own menu, archive and single templates and a card pattern, generated automatically. Taxonomies (categories, tags and the like) are created separately and attached to one or more entities.', 'gl-infinite-theme' ); ?>
			<a href="<?php echo esc_url( $taxonomies_url ); ?>"><?php esc_html_e( 'Manage taxonomies', 'gl-infinite-theme' ); ?></a>
		</p>

		<?php if ( ! $can_add ) : ?>
			<p><?php echo esc_html( $limit_message ); ?></p>
		<?php endif; ?>

		<?php if ( array() === $entities ) : ?>
			<p><?php esc_html_e( 'No entities yet. Add your first one to get started.', 'gl-infinite-theme' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Slug', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Archive', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Taxonomies', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Content', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'gl-infinite-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $entities as $entity ) : ?>
						<?php
						$slug        = $entity['slug'];
						$post_type   = glinf_entity_post_type( $slug );
						$edit_url    = glinf_entities_admin_url(
							array(
								'action' => 'edit',
								'entity' => $slug,
							)
						);
						$content_url = add_query_arg( 'post_type', $post_type, admin_url( 'edit.php' ) );
							// False when the post type is not registered or has no archive.
							$archive_url = $entity['has_archive'] ? get_post_type_archive_link( $post_type ) : false;
						$confirm     = sprintf(
							/* translators: %s: plural name of the entity. */
							__( 'Delete the "%s" entity? Only its configuration is removed: the content is NOT deleted and will reappear if you create an entity with the same slug again. Taxonomies stay, and are detached from it.', 'gl-infinite-theme' ),
							$entity['plural']
						);

						$taxonomy_names = implode( ', ', wp_list_pluck( glinf_get_entity_taxonomies( $slug, $config['taxonomies'] ), 'plural' ) );
						?>
						<tr>
							<th scope="row">
								<span class="dashicons <?php echo esc_attr( $entity['icon'] ); ?>" aria-hidden="true"></span>
								<strong><?php echo esc_html( $entity['plural'] ); ?></strong>
								(<?php echo esc_html( $entity['singular'] ); ?>)
							</th>
							<td><code><?php echo esc_html( $post_type ); ?></code></td>
							<td>
								<?php if ( $archive_url ) : ?>
									<a href="<?php echo esc_url( $archive_url ); ?>">
										<?php esc_html_e( 'View archive', 'gl-infinite-theme' ); ?>
										<span class="screen-reader-text"><?php echo esc_html( $entity['plural'] ); ?></span>
									</a>
								<?php else : ?>
									<?php echo esc_html( $entity['has_archive'] ? $yes_label : $no_label ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo '' === $taxonomy_names ? '&mdash;' : esc_html( $taxonomy_names ); ?></td>
							<td><a href="<?php echo esc_url( $content_url ); ?>"><?php echo esc_html( (string) glinf_entities_count_items( $slug ) ); ?></a></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>">
									<?php esc_html_e( 'Edit', 'gl-infinite-theme' ); ?>
									<span class="screen-reader-text"><?php echo esc_html( $entity['plural'] ); ?></span>
								</a>
								<form method="post" action="<?php echo esc_url( $post_url ); ?>" onsubmit="<?php echo esc_attr( 'return confirm( ' . wp_json_encode( $confirm ) . ' );' ); ?>">
									<input type="hidden" name="action" value="glinf_delete_entity">
									<input type="hidden" name="entity" value="<?php echo esc_attr( $slug ); ?>">
									<input type="hidden" name="glinf_entity_nonce" value="<?php echo esc_attr( wp_create_nonce( 'glinf_delete_entity_' . $slug ) ); ?>">
									<button type="submit" class="button-link button-link-delete">
										<?php esc_html_e( 'Delete', 'gl-infinite-theme' ); ?>
										<span class="screen-reader-text"><?php echo esc_html( $entity['plural'] ); ?></span>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders the add/edit form.
 *
 * The taxonomies are shown read-only: the association is stored (and edited)
 * on the taxonomy side, so the form has nothing to submit for them.
 *
 * @param array<string, mixed> $entity      Entity to show (sanitized).
 * @param bool                 $is_edit     True when editing an existing entity (slug is read-only).
 * @param string               $notice_code Message code to show, '' for none.
 * @return void
 */
function glinf_render_entity_form( array $entity, bool $is_edit, string $notice_code ): void {
	$icons          = glinf_get_entity_icons();
	$icon_labels    = glinf_entities_get_icon_labels();
	$supports_label = glinf_entities_get_supports_labels();
	$list_url       = glinf_entities_admin_url();
	$taxonomies_url = glinf_taxonomies_admin_url();
	$title          = $is_edit ? __( 'Edit Entity', 'gl-infinite-theme' ) : __( 'Add New Entity', 'gl-infinite-theme' );
	$submit_label   = $is_edit ? __( 'Update Entity', 'gl-infinite-theme' ) : __( 'Create Entity', 'gl-infinite-theme' );

	// A new entity has no taxonomies yet; the slug typed in an unsaved form must not be looked up.
	$taxonomy_names = $is_edit ? implode( ', ', wp_list_pluck( glinf_get_entity_taxonomies( $entity['slug'], glinf_get_taxonomies() ), 'plural' ) ) : '';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( $title ); ?></h1>
		<hr class="wp-header-end">

		<?php glinf_entities_render_notice( $notice_code, glinf_entities_get_messages() ); ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="glinf_save_entity">
			<?php wp_nonce_field( 'glinf_save_entity', 'glinf_entity_nonce' ); ?>
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="glinf_original_slug" value="<?php echo esc_attr( $entity['slug'] ); ?>">
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="glinf-entity-slug"><?php esc_html_e( 'Slug', 'gl-infinite-theme' ); ?></label></th>
					<td>
						<input type="text" id="glinf-entity-slug" name="glinf_entity[slug]" value="<?php echo esc_attr( $entity['slug'] ); ?>" class="regular-text code" maxlength="14" pattern="[a-z][a-z0-9_]{1,12}[a-z0-9]" autocapitalize="none" autocomplete="off" spellcheck="false" aria-describedby="glinf-entity-slug-desc" required <?php wp_readonly( $is_edit ); ?>>
						<p class="description" id="glinf-entity-slug-desc">
							<?php
							if ( $is_edit ) {
								esc_html_e( 'The slug cannot be changed after creation.', 'gl-infinite-theme' );
							} else {
								esc_html_e( '3 to 14 characters: lowercase letters, numbers and underscores, starting with a letter. It cannot be changed later. By default it is also used in the address of the archive (underscores become hyphens): see URL base.', 'gl-infinite-theme' );
							}
							?>
						</p>
					</td>
				</tr>
				<?php glinf_entities_render_url_base_row( 'entity', $entity, $is_edit ); ?>
				<tr>
					<th scope="row"><label for="glinf-entity-singular"><?php esc_html_e( 'Singular name', 'gl-infinite-theme' ); ?></label></th>
					<td>
						<input type="text" id="glinf-entity-singular" name="glinf_entity[singular]" value="<?php echo esc_attr( $entity['singular'] ); ?>" class="regular-text" maxlength="40" aria-describedby="glinf-entity-singular-desc" required>
						<p class="description" id="glinf-entity-singular-desc"><?php esc_html_e( 'Example: Event. Up to 40 characters.', 'gl-infinite-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="glinf-entity-plural"><?php esc_html_e( 'Plural name', 'gl-infinite-theme' ); ?></label></th>
					<td>
						<input type="text" id="glinf-entity-plural" name="glinf_entity[plural]" value="<?php echo esc_attr( $entity['plural'] ); ?>" class="regular-text" maxlength="40" aria-describedby="glinf-entity-plural-desc" required>
						<p class="description" id="glinf-entity-plural-desc"><?php esc_html_e( 'Example: Events. Up to 40 characters.', 'gl-infinite-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="glinf-entity-icon"><?php esc_html_e( 'Menu icon', 'gl-infinite-theme' ); ?></label></th>
					<td>
						<select id="glinf-entity-icon" name="glinf_entity[icon]">
							<?php foreach ( $icons as $icon ) : ?>
								<option value="<?php echo esc_attr( $icon ); ?>" <?php selected( $entity['icon'], $icon ); ?>><?php echo esc_html( $icon_labels[ $icon ] ?? $icon ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Features', 'gl-infinite-theme' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Features', 'gl-infinite-theme' ); ?></span></legend>
							<?php foreach ( glinf_get_entity_supports_whitelist() as $feature ) : ?>
								<label for="<?php echo esc_attr( 'glinf-entity-supports-' . $feature ); ?>">
									<input type="checkbox" id="<?php echo esc_attr( 'glinf-entity-supports-' . $feature ); ?>" name="glinf_entity[supports][]" value="<?php echo esc_attr( $feature ); ?>" <?php checked( in_array( $feature, $entity['supports'], true ) ); ?>>
									<?php echo esc_html( $supports_label[ $feature ] ?? $feature ); ?>
								</label>
								<br>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Archive', 'gl-infinite-theme' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Archive', 'gl-infinite-theme' ); ?></span></legend>
							<label for="glinf-entity-has-archive">
								<input type="checkbox" id="glinf-entity-has-archive" name="glinf_entity[has_archive]" value="1" <?php checked( $entity['has_archive'] ); ?>>
								<?php esc_html_e( 'Enable an archive page that lists all items', 'gl-infinite-theme' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Taxonomies', 'gl-infinite-theme' ); ?></th>
					<td>
						<?php if ( '' === $taxonomy_names ) : ?>
							<?php esc_html_e( 'None yet', 'gl-infinite-theme' ); ?>
						<?php else : ?>
							<?php echo esc_html( $taxonomy_names ); ?>
						<?php endif; ?>
						&mdash;
						<a href="<?php echo esc_url( $taxonomies_url ); ?>"><?php esc_html_e( 'Manage taxonomies', 'gl-infinite-theme' ); ?></a>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php echo esc_html( $submit_label ); ?></button>
				<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'gl-infinite-theme' ); ?></a>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Handles the "save entity" form (create and update).
 *
 * Order matters: capability, method, nonce, then sanitization and validation.
 * Whatever the client sends for the slug of an existing entity is ignored:
 * the entity is identified by the hidden original slug and the slug is
 * immutable, as it is what ties the saved content to the post type.
 * The taxonomies are left as they are: they are edited on their own screen.
 * The URL base, unlike the slug, can change on update: it is validated against
 * the rest of the configuration and the site (see glinf_validate_url_base()); the
 * flag that flushes the rewrite rules is set by glinf_save_config() when it changes.
 *
 * @return void
 */
function glinf_handle_save_entity(): void {
	glinf_entities_require_cap();
	glinf_entities_require_post();
	check_admin_referer( 'glinf_save_entity', 'glinf_entity_nonce' );

	// The whole array is sanitized field by field by glinf_sanitize_entity() below.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashed here, sanitized field by field in glinf_sanitize_entity().
	$raw = isset( $_POST['glinf_entity'] ) && is_array( $_POST['glinf_entity'] ) ? wp_unslash( $_POST['glinf_entity'] ) : array();

	$original = isset( $_POST['glinf_original_slug'] ) ? glinf_normalize_slug_input( wp_unslash( $_POST['glinf_original_slug'] ) ) : '';
	$config   = glinf_get_config( true );
	$items    = $config['items'];
	$is_edit  = '' !== $original;

	if ( $is_edit ) {
		if ( ! isset( $items[ $original ] ) ) {
			glinf_entities_redirect( array( 'glinf_notice' => 'not_found' ) );
		}
		$slug = $original;
	} else {
		$slug = glinf_normalize_slug_input( $raw['slug'] ?? '' );
	}

	// The slug key is overwritten: for an existing entity the posted value is never trusted.
	$entity = glinf_sanitize_entity( array_merge( $raw, array( 'slug' => $slug ) ) );

	$error = '';
	if ( ! $is_edit ) {
		$error = count( $items ) >= GLINF_ENTITIES_MAX ? 'limit_reached' : glinf_validate_new_entity_slug( $slug, $items, $config['taxonomies'] );
	}
	if ( '' === $error ) {
		$error = glinf_validate_entity_fields( $entity );
	}
	if ( '' === $error ) {
		$error = glinf_validate_entity_url_base( $entity, $items, $config['taxonomies'] );
	}

	if ( '' !== $error ) {
		glinf_entities_fail( $entity, $original, $error );
	}

	$items[ $slug ] = $entity;

	if ( ! glinf_save_config( $items, $config['taxonomies'] ) ) {
		glinf_entities_fail( $entity, $original, 'save_failed' );
	}

	glinf_entities_redirect( array( 'glinf_notice' => 'saved' ) );
}
add_action( 'admin_post_glinf_save_entity', 'glinf_handle_save_entity' );

/**
 * Handles the "delete entity" form.
 *
 * Removes ONLY the configuration. Posts and terms stay in the database and
 * reappear if an entity with the same slug is created again. The entity is also
 * detached from every taxonomy, in the SAME save: the taxonomies stay (so do
 * their terms) but stop pointing at an entity that does not exist any more.
 *
 * @return void
 */
function glinf_handle_delete_entity(): void {
	glinf_entities_require_cap();
	glinf_entities_require_post();

	// The slug is needed to build the nonce action; it is sanitized here and nothing is changed before the nonce is verified.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on the next line, the nonce action depends on this value.
	$slug = isset( $_POST['entity'] ) ? sanitize_key( wp_unslash( $_POST['entity'] ) ) : '';
	check_admin_referer( 'glinf_delete_entity_' . $slug, 'glinf_entity_nonce' );

	$config = glinf_get_config( true );
	$items  = $config['items'];

	if ( ! isset( $items[ $slug ] ) ) {
		glinf_entities_redirect( array( 'glinf_notice' => 'not_found' ) );
	}

	unset( $items[ $slug ] );

	if ( ! glinf_save_config( $items, glinf_detach_entity_from_taxonomies( $config['taxonomies'], $slug ) ) ) {
		glinf_entities_redirect( array( 'glinf_notice' => 'save_failed' ) );
	}

	glinf_entities_redirect( array( 'glinf_notice' => 'deleted' ) );
}
add_action( 'admin_post_glinf_delete_entity', 'glinf_handle_delete_entity' );
