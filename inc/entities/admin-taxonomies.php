<?php
/**
 * Entity Manager admin: the "Taxonomies" screen (list, add/edit form and the save/delete handlers).
 *
 * A taxonomy is an object on its own, attached to one or more entities: with one
 * entity it is "specific", with several it is "shared", with none it is "not
 * attached" (kept in the configuration but not registered). The screen lives at
 * `admin.php?page=glinf-taxonomies` and renders both the list and the form
 * (`&action=new` / `&action=edit&glinf_taxonomy=<slug>`). Writes go through
 * `admin-post.php`. The guards (capability, POST method, nonce) are the ones used
 * by the Entities screen, in the same order. No CSS or JS is enqueued.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** Menu page slug. */
const GLINF_TAXONOMIES_PAGE = 'glinf-taxonomies';

/**
 * Registers the "Taxonomies" sub-page of the Entity Manager menu.
 *
 * Priority 11: the top-level page and its "Entities" sub-page (admin.php, priority 10)
 * must exist first, so "Taxonomies" is listed second.
 *
 * @return void
 */
function glinf_taxonomies_admin_menu(): void {
	add_submenu_page(
		GLINF_ENTITIES_PAGE,
		__( 'Taxonomies', 'gl-infinite-theme' ),
		__( 'Taxonomies', 'gl-infinite-theme' ),
		GLINF_ENTITIES_CAP,
		GLINF_TAXONOMIES_PAGE,
		'glinf_render_taxonomies_page'
	);
}
add_action( 'admin_menu', 'glinf_taxonomies_admin_menu', 11 );

/**
 * Builds an URL of the Taxonomies screen.
 *
 * @param array<string, string> $args Extra query arguments (must be safe, whitelisted values).
 * @return string
 */
function glinf_taxonomies_admin_url( array $args = array() ): string {
	return add_query_arg(
		array_merge( array( 'page' => GLINF_TAXONOMIES_PAGE ), $args ),
		admin_url( 'admin.php' )
	);
}

/**
 * Redirects to the Taxonomies screen and stops.
 *
 * Only whitelisted codes and validated slugs are ever placed in the URL: user
 * text is never reflected.
 *
 * @param array<string, string> $args Query arguments.
 * @return never
 */
function glinf_taxonomies_redirect( array $args = array() ): never {
	wp_safe_redirect( glinf_taxonomies_admin_url( $args ) );
	exit;
}

/**
 * Returns the messages the screen can show, keyed by the code used in the URL.
 *
 * Being the only source of accepted codes, this is also the whitelist.
 *
 * @return array<string, array{type: string, text: string}>
 */
function glinf_taxonomies_get_messages(): array {
	$messages = array(
		'saved'         => array(
			'type' => 'success',
			'text' => __( 'Taxonomy saved.', 'gl-infinite-theme' ),
		),
		'deleted'       => array(
			'type' => 'success',
			'text' => __( 'Taxonomy configuration deleted. Its terms were not deleted.', 'gl-infinite-theme' ),
		),
		'slug_invalid'  => array(
			'type' => 'error',
			'text' => __( 'The slug must be 3 to 26 characters long, start with a letter, end with a letter or a number, and contain only lowercase letters, numbers and underscores.', 'gl-infinite-theme' ),
		),
		'slug_reserved' => array(
			'type' => 'error',
			'text' => __( 'This slug is reserved by WordPress. Please choose another one.', 'gl-infinite-theme' ),
		),
		'slug_exists'   => array(
			'type' => 'error',
			'text' => __( 'This slug is already used by an entity or a taxonomy. Please choose another one.', 'gl-infinite-theme' ),
		),
		'slug_conflict' => array(
			'type' => 'error',
			'text' => __( 'This slug conflicts with an existing content type, taxonomy, page or URL. Please choose another one.', 'gl-infinite-theme' ),
		),
		'name_invalid'  => array(
			'type' => 'error',
			'text' => __( 'The singular and plural names are required and must be 1 to 40 characters long.', 'gl-infinite-theme' ),
		),
		'limit_reached' => array(
			'type' => 'error',
			'text' => sprintf(
				/* translators: %d: maximum number of taxonomies. */
				__( 'You can create up to %d taxonomies. Delete one to add another.', 'gl-infinite-theme' ),
				GLINF_TAXONOMIES_MAX
			),
		),
		'not_found'     => array(
			'type' => 'error',
			'text' => __( 'The requested taxonomy does not exist.', 'gl-infinite-theme' ),
		),
		'save_failed'   => array(
			'type' => 'error',
			'text' => __( 'The taxonomy could not be saved. Please try again.', 'gl-infinite-theme' ),
		),
	);

	return $messages + glinf_entities_get_url_base_messages();
}

/**
 * Returns the label of each way a taxonomy can be attached, keyed by scope.
 *
 * @return array<string, string>
 */
function glinf_taxonomies_get_scope_labels(): array {
	return array(
		'specific'   => __( 'Specific', 'gl-infinite-theme' ),
		'shared'     => __( 'Shared', 'gl-infinite-theme' ),
		'unattached' => __( 'Not attached', 'gl-infinite-theme' ),
	);
}

/**
 * Returns the key of the transient that refills the form of the current user.
 *
 * @return string
 */
function glinf_taxonomies_form_state_key(): string {
	return 'glinf_taxonomy_form_' . get_current_user_id();
}

/**
 * Stores the (already sanitized) form data for one minute and redirects with an error code.
 *
 * @param array<string, mixed> $taxonomy Sanitized taxonomy as submitted.
 * @param string               $original Slug of the taxonomy being edited, '' when creating.
 * @param string               $code     Error code (a key of glinf_taxonomies_get_messages()).
 * @return never
 */
function glinf_taxonomies_fail( array $taxonomy, string $original, string $code ): never {
	set_transient(
		glinf_taxonomies_form_state_key(),
		array(
			'taxonomy' => $taxonomy,
			'original' => $original,
		),
		GLINF_ENTITIES_FORM_STATE_TTL
	);

	if ( '' === $original ) {
		glinf_taxonomies_redirect(
			array(
				'action'       => 'new',
				'glinf_notice' => $code,
			)
		);
	}

	glinf_taxonomies_redirect(
		array(
			'action'         => 'edit',
			'glinf_taxonomy' => $original,
			'glinf_notice' => $code,
		)
	);
}

/**
 * Reads and consumes the form data saved after a failed submission.
 *
 * The data is sanitized again, against the entities that exist NOW: a transient
 * is storage, not a trusted source.
 *
 * @param string $original Slug being edited, '' when creating (must match the stored one).
 * @return array<string, mixed>|null
 */
function glinf_taxonomies_pull_form_state( string $original ): ?array {
	$state = get_transient( glinf_taxonomies_form_state_key() );
	delete_transient( glinf_taxonomies_form_state_key() );

	if (
		! is_array( $state )
		|| ! isset( $state['taxonomy'], $state['original'] )
		|| ! is_array( $state['taxonomy'] )
		|| $state['original'] !== $original
	) {
		return null;
	}

	return glinf_sanitize_taxonomy( $state['taxonomy'], array_keys( glinf_get_entities() ) );
}

/**
 * Builds the sentence that says how many terms a taxonomy has (delete confirmation).
 *
 * Terms exist only for a registered taxonomy: the count is skipped otherwise.
 * This runs on an admin screen only, never on the front end.
 *
 * @param string $key Registered taxonomy key.
 * @return string Empty string when unknown or when there are no terms.
 */
function glinf_taxonomies_terms_sentence( string $key ): string {
	if ( ! taxonomy_exists( $key ) ) {
		return '';
	}

	$count = wp_count_terms(
		array(
			'taxonomy'   => $key,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $count ) || (int) $count < 1 ) {
		return '';
	}

	return sprintf(
		/* translators: %d: number of terms of the taxonomy. */
		_n( 'It currently has %d term.', 'It currently has %d terms.', (int) $count, 'gl-infinite-theme' ),
		(int) $count
	);
}

/**
 * Renders the Taxonomies screen (list or form).
 *
 * @return void
 */
function glinf_render_taxonomies_page(): void {
	glinf_entities_require_cap();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only screen routing: every value is whitelisted or validated below and nothing is changed.
	$action        = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$taxonomy_slug = isset( $_GET['glinf_taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['glinf_taxonomy'] ) ) : '';
	$notice_code   = isset( $_GET['glinf_notice'] ) ? sanitize_key( wp_unslash( $_GET['glinf_notice'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$messages = glinf_taxonomies_get_messages();
	if ( ! isset( $messages[ $notice_code ] ) ) {
		$notice_code = '';
	}

	// Only a redirect after a failed save may refill the form: a plain visit must never show stale data.
	$is_error = '' !== $notice_code && 'error' === $messages[ $notice_code ]['type'];

	if ( 'new' === $action ) {
		$taxonomy = ( $is_error ? glinf_taxonomies_pull_form_state( '' ) : null ) ?? glinf_get_default_taxonomy();
		glinf_render_taxonomy_form( $taxonomy, false, $notice_code );
		return;
	}

	if ( 'edit' === $action ) {
		$stored = glinf_get_taxonomy( $taxonomy_slug );

		if ( null !== $stored ) {
			// After a failed save, show what the user typed (the slug always comes from storage).
			$posted   = $is_error ? glinf_taxonomies_pull_form_state( $taxonomy_slug ) : null;
			$taxonomy = null === $posted ? $stored : array_merge( $posted, array( 'slug' => $stored['slug'] ) );
			glinf_render_taxonomy_form( $taxonomy, true, $notice_code );
			return;
		}

		$notice_code = 'not_found';
	}

	glinf_render_taxonomies_list( $notice_code );
}

/**
 * Renders the list of taxonomies.
 *
 * @param string $notice_code Message code to show, '' for none.
 * @return void
 */
function glinf_render_taxonomies_list( string $notice_code ): void {
	$config      = glinf_get_config();
	$entities    = $config['items'];
	$taxonomies  = $config['taxonomies'];
	$can_add     = count( $taxonomies ) < GLINF_TAXONOMIES_MAX;
	$new_url     = glinf_taxonomies_admin_url( array( 'action' => 'new' ) );
	$post_url    = admin_url( 'admin-post.php' );
	$yes_label   = __( 'Yes', 'gl-infinite-theme' );
	$no_label    = __( 'No', 'gl-infinite-theme' );
	$scope_label = glinf_taxonomies_get_scope_labels();

	$messages      = glinf_taxonomies_get_messages();
	$limit_message = $messages['limit_reached']['text'];
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Taxonomies', 'gl-infinite-theme' ); ?></h1>
		<?php if ( $can_add ) : ?>
			<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Taxonomy', 'gl-infinite-theme' ); ?></a>
		<?php endif; ?>
		<hr class="wp-header-end">

		<?php glinf_entities_render_notice( $notice_code, $messages ); ?>

		<p><?php esc_html_e( 'Taxonomies group your content, like categories or tags. Attach a taxonomy to one entity to make it specific, or to several entities to share it: each one gets its own term archive, generated automatically.', 'gl-infinite-theme' ); ?></p>

		<?php if ( ! $can_add ) : ?>
			<p><?php echo esc_html( $limit_message ); ?></p>
		<?php endif; ?>

		<?php if ( array() === $taxonomies ) : ?>
			<p><?php esc_html_e( 'No taxonomies yet. Add your first one to get started.', 'gl-infinite-theme' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Slug', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Type', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Attached to', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Hierarchical', 'gl-infinite-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'gl-infinite-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $taxonomies as $taxonomy ) : ?>
						<?php
						$slug     = $taxonomy['slug'];
						$key      = glinf_taxonomy_key( $slug );
						$edit_url = glinf_taxonomies_admin_url(
							array(
								'action'         => 'edit',
								'glinf_taxonomy' => $slug,
							)
						);

						$confirm = sprintf(
							/* translators: %s: plural name of the taxonomy. */
							__( 'Delete the "%s" taxonomy? Only its configuration is removed: its terms and their assignments stay in the database and reappear if you create a taxonomy with the same slug again.', 'gl-infinite-theme' ),
							$taxonomy['plural']
						);
						$terms   = glinf_taxonomies_terms_sentence( $key );
						if ( '' !== $terms ) {
							$confirm .= ' ' . $terms;
						}

						// Every slug in `entities` exists (the normalizer guarantees it), so the lookup cannot miss.
						$attached_names = array();
						foreach ( $taxonomy['entities'] as $entity_slug ) {
							$attached_names[] = $entities[ $entity_slug ]['plural'];
						}
						$attached = implode( ', ', $attached_names );
						?>
						<tr>
							<th scope="row">
								<strong><?php echo esc_html( $taxonomy['plural'] ); ?></strong>
								(<?php echo esc_html( $taxonomy['singular'] ); ?>)
							</th>
							<td><code><?php echo esc_html( $key ); ?></code></td>
							<td><?php echo esc_html( $scope_label[ glinf_get_taxonomy_scope( $taxonomy ) ] ); ?></td>
							<td><?php echo '' === $attached ? '&mdash;' : esc_html( $attached ); ?></td>
							<td><?php echo esc_html( $taxonomy['hierarchical'] ? $yes_label : $no_label ); ?></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>">
									<?php esc_html_e( 'Edit', 'gl-infinite-theme' ); ?>
									<span class="screen-reader-text"><?php echo esc_html( $taxonomy['plural'] ); ?></span>
								</a>
								<form method="post" action="<?php echo esc_url( $post_url ); ?>" onsubmit="<?php echo esc_attr( 'return confirm( ' . wp_json_encode( $confirm ) . ' );' ); ?>">
									<input type="hidden" name="action" value="glinf_delete_taxonomy">
									<input type="hidden" name="glinf_taxonomy" value="<?php echo esc_attr( $slug ); ?>">
									<input type="hidden" name="glinf_taxonomy_nonce" value="<?php echo esc_attr( wp_create_nonce( 'glinf_delete_taxonomy_' . $slug ) ); ?>">
									<button type="submit" class="button-link button-link-delete">
										<?php esc_html_e( 'Delete', 'gl-infinite-theme' ); ?>
										<span class="screen-reader-text"><?php echo esc_html( $taxonomy['plural'] ); ?></span>
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
 * @param array<string, mixed> $taxonomy    Taxonomy to show (sanitized).
 * @param bool                 $is_edit     True when editing an existing taxonomy (slug is read-only).
 * @param string               $notice_code Message code to show, '' for none.
 * @return void
 */
function glinf_render_taxonomy_form( array $taxonomy, bool $is_edit, string $notice_code ): void {
	$entities       = glinf_get_entities();
	$list_url       = glinf_taxonomies_admin_url();
	$new_entity_url = glinf_entities_admin_url( array( 'action' => 'new' ) );
	$title          = $is_edit ? __( 'Edit Taxonomy', 'gl-infinite-theme' ) : __( 'Add New Taxonomy', 'gl-infinite-theme' );
	$submit_label   = $is_edit ? __( 'Update Taxonomy', 'gl-infinite-theme' ) : __( 'Create Taxonomy', 'gl-infinite-theme' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( $title ); ?></h1>
		<hr class="wp-header-end">

		<?php glinf_entities_render_notice( $notice_code, glinf_taxonomies_get_messages() ); ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="glinf_save_taxonomy">
			<?php wp_nonce_field( 'glinf_save_taxonomy', 'glinf_taxonomy_nonce' ); ?>
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="glinf_original_taxonomy" value="<?php echo esc_attr( $taxonomy['slug'] ); ?>">
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="glinf-taxonomy-slug"><?php esc_html_e( 'Slug', 'gl-infinite-theme' ); ?></label></th>
					<td>
						<input type="text" id="glinf-taxonomy-slug" name="glinf_taxonomy[slug]" value="<?php echo esc_attr( $taxonomy['slug'] ); ?>" class="regular-text code" maxlength="26" pattern="[a-z][a-z0-9_]{1,24}[a-z0-9]" autocapitalize="none" autocomplete="off" spellcheck="false" aria-describedby="glinf-taxonomy-slug-desc" required <?php wp_readonly( $is_edit ); ?>>
						<p class="description" id="glinf-taxonomy-slug-desc">
							<?php
							if ( $is_edit ) {
								esc_html_e( 'The slug cannot be changed after creation: it identifies the terms in the database.', 'gl-infinite-theme' );
							} else {
								esc_html_e( '3 to 26 characters: lowercase letters, numbers and underscores, starting with a letter. It cannot be changed later. By default it is also used in the address of the term archives (underscores become hyphens): see URL base.', 'gl-infinite-theme' );
							}
							?>
						</p>
					</td>
				</tr>
				<?php glinf_entities_render_url_base_row( 'taxonomy', $taxonomy, $is_edit ); ?>
				<tr>
					<th scope="row"><label for="glinf-taxonomy-singular"><?php esc_html_e( 'Singular name', 'gl-infinite-theme' ); ?></label></th>
					<td>
						<input type="text" id="glinf-taxonomy-singular" name="glinf_taxonomy[singular]" value="<?php echo esc_attr( $taxonomy['singular'] ); ?>" class="regular-text" maxlength="40" aria-describedby="glinf-taxonomy-singular-desc" required>
						<p class="description" id="glinf-taxonomy-singular-desc"><?php esc_html_e( 'Example: Genre. Up to 40 characters.', 'gl-infinite-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="glinf-taxonomy-plural"><?php esc_html_e( 'Plural name', 'gl-infinite-theme' ); ?></label></th>
					<td>
						<input type="text" id="glinf-taxonomy-plural" name="glinf_taxonomy[plural]" value="<?php echo esc_attr( $taxonomy['plural'] ); ?>" class="regular-text" maxlength="40" aria-describedby="glinf-taxonomy-plural-desc" required>
						<p class="description" id="glinf-taxonomy-plural-desc"><?php esc_html_e( 'Example: Genres. Up to 40 characters.', 'gl-infinite-theme' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Structure', 'gl-infinite-theme' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Structure', 'gl-infinite-theme' ); ?></span></legend>
							<label for="glinf-taxonomy-hierarchical">
								<input type="checkbox" id="glinf-taxonomy-hierarchical" name="glinf_taxonomy[hierarchical]" value="1" aria-describedby="glinf-taxonomy-hierarchical-desc" <?php checked( $taxonomy['hierarchical'] ); ?>>
								<?php esc_html_e( 'Hierarchical (like categories)', 'gl-infinite-theme' ); ?>
							</label>
							<p class="description" id="glinf-taxonomy-hierarchical-desc"><?php esc_html_e( 'Each term can have a parent term, chosen from a list. Leave unticked for flat labels, like tags.', 'gl-infinite-theme' ); ?></p>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Attach to', 'gl-infinite-theme' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Attach to', 'gl-infinite-theme' ); ?></span></legend>
							<?php if ( array() === $entities ) : ?>
								<p>
									<?php esc_html_e( 'There are no entities yet.', 'gl-infinite-theme' ); ?>
									<a href="<?php echo esc_url( $new_entity_url ); ?>"><?php esc_html_e( 'Add New Entity', 'gl-infinite-theme' ); ?></a>
								</p>
							<?php else : ?>
								<?php foreach ( $entities as $entity ) : ?>
									<label for="<?php echo esc_attr( 'glinf-taxonomy-entity-' . $entity['slug'] ); ?>">
										<input type="checkbox" id="<?php echo esc_attr( 'glinf-taxonomy-entity-' . $entity['slug'] ); ?>" name="glinf_taxonomy[entities][]" value="<?php echo esc_attr( $entity['slug'] ); ?>" <?php checked( in_array( $entity['slug'], $taxonomy['entities'], true ) ); ?>>
										<?php echo esc_html( $entity['plural'] ); ?>
									</label>
									<br>
								<?php endforeach; ?>
								<p class="description"><?php esc_html_e( 'One entity makes the taxonomy specific, several make it shared. A taxonomy attached to none is not used on the site.', 'gl-infinite-theme' ); ?></p>
							<?php endif; ?>
						</fieldset>
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
 * Handles the "save taxonomy" form (create and update).
 *
 * Order matters: capability, method, nonce, then sanitization and validation.
 * Whatever the client sends for the slug of an existing taxonomy is ignored:
 * the taxonomy is identified by the hidden original slug and the slug is
 * immutable, as it is what ties the terms to the taxonomy. The attached
 * entities are a whitelist filter over the entities that exist. The URL base,
 * unlike the slug, can change on update (see glinf_validate_url_base()).
 *
 * @return void
 */
function glinf_handle_save_taxonomy(): void {
	glinf_entities_require_cap();
	glinf_entities_require_post();
	check_admin_referer( 'glinf_save_taxonomy', 'glinf_taxonomy_nonce' );

	// The whole array is sanitized field by field by glinf_sanitize_taxonomy() below.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashed here, sanitized field by field in glinf_sanitize_taxonomy().
	$raw = isset( $_POST['glinf_taxonomy'] ) && is_array( $_POST['glinf_taxonomy'] ) ? wp_unslash( $_POST['glinf_taxonomy'] ) : array();

	$original   = isset( $_POST['glinf_original_taxonomy'] ) ? glinf_normalize_slug_input( wp_unslash( $_POST['glinf_original_taxonomy'] ) ) : '';
	$config     = glinf_get_config( true );
	$taxonomies = $config['taxonomies'];
	$is_edit    = '' !== $original;

	if ( $is_edit ) {
		if ( ! isset( $taxonomies[ $original ] ) ) {
			glinf_taxonomies_redirect( array( 'glinf_notice' => 'not_found' ) );
		}
		$slug = $original;
	} else {
		$slug = glinf_normalize_slug_input( $raw['slug'] ?? '' );
	}

	// The slug key is overwritten: for an existing taxonomy the posted value is never trusted.
	$taxonomy = glinf_sanitize_taxonomy( array_merge( $raw, array( 'slug' => $slug ) ), array_keys( $config['items'] ) );

	$error = '';
	if ( ! $is_edit ) {
		$error = count( $taxonomies ) >= GLINF_TAXONOMIES_MAX ? 'limit_reached' : glinf_validate_new_taxonomy_slug( $slug, $config['items'], $taxonomies );
	}
	if ( '' === $error ) {
		$error = glinf_validate_taxonomy_fields( $taxonomy );
	}
	if ( '' === $error ) {
		$error = glinf_validate_taxonomy_url_base( $taxonomy, $config['items'], $taxonomies );
	}

	if ( '' !== $error ) {
		glinf_taxonomies_fail( $taxonomy, $original, $error );
	}

	$taxonomies[ $slug ] = $taxonomy;

	if ( ! glinf_save_config( $config['items'], $taxonomies ) ) {
		glinf_taxonomies_fail( $taxonomy, $original, 'save_failed' );
	}

	glinf_taxonomies_redirect( array( 'glinf_notice' => 'saved' ) );
}
add_action( 'admin_post_glinf_save_taxonomy', 'glinf_handle_save_taxonomy' );

/**
 * Handles the "delete taxonomy" form.
 *
 * Removes ONLY the configuration. Terms and their assignments stay in the
 * database and reappear if a taxonomy with the same slug is created again.
 *
 * @return void
 */
function glinf_handle_delete_taxonomy(): void {
	glinf_entities_require_cap();
	glinf_entities_require_post();

	// The slug is needed to build the nonce action; it is sanitized here and nothing is changed before the nonce is verified.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on the next line, the nonce action depends on this value.
	$slug = isset( $_POST['glinf_taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['glinf_taxonomy'] ) ) : '';
	check_admin_referer( 'glinf_delete_taxonomy_' . $slug, 'glinf_taxonomy_nonce' );

	$config     = glinf_get_config( true );
	$taxonomies = $config['taxonomies'];

	if ( ! isset( $taxonomies[ $slug ] ) ) {
		glinf_taxonomies_redirect( array( 'glinf_notice' => 'not_found' ) );
	}

	unset( $taxonomies[ $slug ] );

	if ( ! glinf_save_config( $config['items'], $taxonomies ) ) {
		glinf_taxonomies_redirect( array( 'glinf_notice' => 'save_failed' ) );
	}

	glinf_taxonomies_redirect( array( 'glinf_notice' => 'deleted' ) );
}
add_action( 'admin_post_glinf_delete_taxonomy', 'glinf_handle_delete_taxonomy' );
