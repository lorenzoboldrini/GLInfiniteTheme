<?php
/**
 * Entity Manager admin: what the "Entities" and "Taxonomies" screens share.
 *
 * - The layout of the screens: header, tabs, empty-state card, delete confirmation.
 * - The one stylesheet, enqueued ONLY on these two screens (see
 *   glinf_entities_load_screen(): it hooks the enqueue from `load-{screen}`).
 * - The plumbing of the lists: the lazy loading of the WP_List_Table classes,
 *   Screen Options (rows per page) and the group "Delete" action, which only leads
 *   to a confirmation screen (nothing is changed while it is shown).
 *
 * The data side (parsing the query string, search, filters, sorting, choosing the
 * items of a group action) is in list-data.php; the table classes are in
 * list-tables.php and are NOT loaded here (they extend WP_List_Table, which is
 * admin only).
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** User option holding the rows per page of the Entities list. */
const GLINF_ENTITIES_PER_PAGE_OPTION = 'glinf_entities_per_page';

/** User option holding the rows per page of the Taxonomies list. */
const GLINF_TAXONOMIES_PER_PAGE_OPTION = 'glinf_taxonomies_per_page';

/** Handle of the admin stylesheet of the Entity Manager. */
const GLINF_ENTITIES_ADMIN_STYLE = 'glinf-admin-entities';

/**
 * Returns the menu page slug of a screen.
 *
 * @param string $kind "entities" or "taxonomies".
 * @return string
 */
function glinf_entities_screen_page( string $kind ): string {
	return 'taxonomies' === $kind ? GLINF_TAXONOMIES_PAGE : GLINF_ENTITIES_PAGE;
}

/**
 * Builds an URL of a screen of the Entity Manager.
 *
 * @param string                $kind "entities" or "taxonomies".
 * @param array<string, string> $args Extra query arguments (must be safe, whitelisted values).
 * @return string
 */
function glinf_entities_screen_url( string $kind, array $args = array() ): string {
	return 'taxonomies' === $kind ? glinf_taxonomies_admin_url( $args ) : glinf_entities_admin_url( $args );
}

/**
 * Returns the name of the user option that stores the rows per page of a list.
 *
 * @param string $kind "entities" or "taxonomies".
 * @return string
 */
function glinf_entities_per_page_option( string $kind ): string {
	return 'taxonomies' === $kind ? GLINF_TAXONOMIES_PER_PAGE_OPTION : GLINF_ENTITIES_PER_PAGE_OPTION;
}

/**
 * Tells whether the current request is a POST.
 *
 * @return bool
 */
function glinf_entities_is_post_request(): bool {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

	return 'POST' === $method;
}

/**
 * Reads the whitelisted state of a list from the current query string.
 *
 * @param string $kind "entities" or "taxonomies".
 * @return array{view: string, filter: string, s: string, orderby: string, order: string, paged: int}
 */
function glinf_entities_current_list_query( string $kind ): array {
	// The state is whitelisted field by field in glinf_list_parse_query(); reading it changes nothing.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only list state, whitelisted by glinf_list_parse_query().
	return glinf_list_parse_query( $kind, wp_unslash( $_GET ), glinf_get_config() );
}

/**
 * Builds the URL of a list from its state, leaving out what is a default.
 *
 * The search text is percent-encoded here on purpose: add_query_arg() does not
 * encode values, and an unencoded "&" would inject a parameter.
 *
 * @param string                     $kind      "entities" or "taxonomies".
 * @param array<string, mixed>       $query     State from glinf_list_parse_query().
 * @param array<string, string|int>  $overrides State values to change.
 * @param array<string, string>      $extra     Arguments added as they are (e.g. a notice code).
 * @return string
 */
function glinf_entities_list_url( string $kind, array $query, array $overrides = array(), array $extra = array() ): string {
	$state = array_merge( $query, $overrides );
	$args  = array();

	if ( isset( $state['view'] ) && '' !== $state['view'] && 'all' !== $state['view'] ) {
		$args['view'] = (string) $state['view'];
	}
	if ( ! empty( $state['filter'] ) ) {
		$args['filter'] = (string) $state['filter'];
	}
	if ( isset( $state['s'] ) && '' !== $state['s'] ) {
		$args['s'] = rawurlencode( (string) $state['s'] );
	}

	$orderby = (string) ( $state['orderby'] ?? 'name' );
	$order   = (string) ( $state['order'] ?? 'asc' );
	if ( 'name' !== $orderby || 'asc' !== $order ) {
		$args['orderby'] = $orderby;

		if ( 'desc' === $order ) {
			$args['order'] = 'desc';
		}
	}

	if ( (int) ( $state['paged'] ?? 0 ) > 1 ) {
		$args['paged'] = (string) (int) $state['paged'];
	}

	return glinf_entities_screen_url( $kind, array_merge( $args, $extra ) );
}

/**
 * Redirects to a list keeping its state (search, filters, sorting) and stops.
 *
 * @param string                $kind  "entities" or "taxonomies".
 * @param array<string, string> $extra Extra arguments (a whitelisted notice code).
 * @return never
 */
function glinf_entities_redirect_to_list( string $kind, array $extra = array() ): never {
	wp_safe_redirect( glinf_entities_list_url( $kind, glinf_entities_current_list_query( $kind ), array(), $extra ) );
	exit;
}

/**
 * Returns the markup of an empty table cell: a dash for sighted users, a word for screen readers.
 *
 * A bare dash is read out as "em dash" by some screen readers.
 *
 * @param string|null $label Text for screen readers (defaults to "None").
 * @return string
 */
function glinf_entities_empty_value_html( ?string $label = null ): string {
	return '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html( $label ?? __( 'None', 'gl-infinite-theme' ) ) . '</span>';
}

/**
 * Reads the number of items a redirect reports (shown in "N entities deleted").
 *
 * It is cast to an integer and bounded, never printed as received.
 *
 * @return int Between 1 and the largest possible number of items.
 */
function glinf_entities_read_count_arg(): int {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, cast to a bounded integer.
	$count = isset( $_GET['glinf_count'] ) && is_string( $_GET['glinf_count'] ) ? absint( wp_unslash( $_GET['glinf_count'] ) ) : 1;

	return max( 1, min( $count, max( GLINF_ENTITIES_MAX, GLINF_TAXONOMIES_MAX ) ) );
}

/**
 * Returns the list table of a screen, creating it on first use.
 *
 * WP_List_Table exists only in wp-admin and is loaded here, on demand: never at
 * bootstrap. The classes are in list-tables.php, which can only be included after
 * the core class. Creating the table early (from the `load-` hook) matters: its
 * constructor registers the columns that Screen Options offers to hide.
 *
 * @param string $kind "entities" or "taxonomies".
 * @return WP_List_Table
 */
function glinf_entities_get_list_table( string $kind ): WP_List_Table {
	static $tables = array();

	if ( ! isset( $tables[ $kind ] ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once GLINF_DIR . 'inc/entities/list-tables.php';

		$tables[ $kind ] = 'taxonomies' === $kind ? new GLINF_Taxonomies_List_Table() : new GLINF_Entities_List_Table();
	}

	return $tables[ $kind ];
}

/**
 * Enqueues the stylesheet of the Entity Manager.
 *
 * Hooked from glinf_entities_load_screen(), i.e. only when one of the two
 * screens is being loaded: no other admin page gets this file. It uses only the
 * admin colour variables and logical properties, so it follows the colour scheme
 * and right-to-left languages. There is no JavaScript.
 *
 * @return void
 */
function glinf_entities_enqueue_admin_assets(): void {
	wp_enqueue_style(
		GLINF_ENTITIES_ADMIN_STYLE,
		get_template_directory_uri() . '/assets/css/admin-entities.css',
		array( 'common', 'forms', 'list-tables' ),
		GLINF_VERSION
	);
}

/**
 * Validates the "rows per page" value of Screen Options before it is saved.
 *
 * Hooked to `set_screen_option_{option}` (core runs it from wp-admin/admin.php,
 * before the screen loads). Returning false makes core skip the save.
 *
 * @param mixed  $status Value core would save (false by default).
 * @param string $option Option name.
 * @param mixed  $value  Value submitted by the user.
 * @return int|false The value clamped to 1-100, or false when it is not a number.
 */
function glinf_entities_filter_screen_option( mixed $status, string $option, mixed $value ): int|false {
	unset( $status, $option );

	return is_numeric( $value ) ? glinf_list_clamp_per_page( $value ) : false;
}
add_filter( 'set_screen_option_' . GLINF_ENTITIES_PER_PAGE_OPTION, 'glinf_entities_filter_screen_option', 10, 3 );
add_filter( 'set_screen_option_' . GLINF_TAXONOMIES_PER_PAGE_OPTION, 'glinf_entities_filter_screen_option', 10, 3 );

/**
 * Keeps the items chosen with the group action between the `load-` hook and the render.
 *
 * @param string          $kind "entities" or "taxonomies".
 * @param string[]|null   $set  Slugs to remember; null only reads.
 * @return string[]|null The remembered slugs, null when the request is not a group action to confirm.
 */
function glinf_entities_pending_selection( string $kind, ?array $set = null ): ?array {
	static $pending = array();

	if ( null !== $set ) {
		$pending[ $kind ] = $set;
	}

	return $pending[ $kind ] ?? null;
}

/**
 * Reads which group action was chosen in the list ("delete" or '' for none).
 *
 * The list table has two selectors (`action` above the table, `action2` below);
 * whichever holds a known action wins. Anything else means "no action".
 *
 * @return string
 */
function glinf_entities_read_bulk_action(): string {
	foreach ( array( 'action', 'action2' ) as $field ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only called after check_admin_referer() in glinf_entities_process_bulk_request().
		if ( isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] ) && 'delete' === sanitize_key( wp_unslash( $_POST[ $field ] ) ) ) {
			return 'delete';
		}
	}

	return '';
}

/**
 * Handles the POST that the list's group action sends to its own screen.
 *
 * It changes NOTHING: it only validates the request and remembers which items
 * were chosen, so the screen shows the confirmation. Order: capability (checked by
 * the caller), method, nonce, sanitize, validate. The actual deletion is the job
 * of glinf_handle_bulk_delete_entities() / glinf_handle_bulk_delete_taxonomies(),
 * which run only after the user confirms and check their own nonce.
 *
 * @param string $kind "entities" or "taxonomies".
 * @return void
 */
function glinf_entities_process_bulk_request( string $kind ): void {
	if ( ! glinf_entities_is_post_request() ) {
		return;
	}

	// The two selectors of the list are always submitted with the group form. A POST without them is not ours
	// (for instance a Screen Options save that core did not redirect): leave it alone, the list is simply shown.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only tells the group form apart from other POSTs; the nonce is verified right below.
	if ( ! isset( $_POST['action'] ) && ! isset( $_POST['action2'] ) ) {
		return;
	}

	$table = glinf_entities_get_list_table( $kind );
	check_admin_referer( $table->get_bulk_nonce_action() );

	// Choosing no action (or an unknown one) is not an error: back to the same list.
	if ( '' === glinf_entities_read_bulk_action() ) {
		glinf_entities_redirect_to_list( $kind );
	}

	$config = glinf_get_config();
	$is_tax = 'taxonomies' === $kind;

	// Sanitized element by element, against the slugs that exist, in glinf_list_sanitize_selection().
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashed here, sanitized by glinf_list_sanitize_selection().
	$received = isset( $_POST['glinf_selected'] ) ? wp_unslash( $_POST['glinf_selected'] ) : array();
	$selected = glinf_list_sanitize_selection(
		$received,
		array_map( 'strval', array_keys( $is_tax ? $config['taxonomies'] : $config['items'] ) ),
		$is_tax ? GLINF_TAXONOMIES_MAX : GLINF_ENTITIES_MAX
	);

	if ( array() === $selected ) {
		glinf_entities_redirect_to_list( $kind, array( 'glinf_notice' => 'nothing_selected' ) );
	}

	glinf_entities_pending_selection( $kind, $selected );
}

/**
 * Runs when one of the two screens is being loaded (before any output).
 *
 * Hooked to `load-{hook}` by glinf_entities_admin_menu() and
 * glinf_taxonomies_admin_menu(), which know the hook names from the values
 * add_menu_page()/add_submenu_page() return (the name of a sub-page depends on
 * the translated menu title, so it cannot be written by hand).
 *
 * @param string $kind "entities" or "taxonomies".
 * @return void
 */
function glinf_entities_load_screen( string $kind ): void {
	glinf_entities_require_cap();

	add_action( 'admin_enqueue_scripts', 'glinf_entities_enqueue_admin_assets' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing, whitelisted below.
	$action = isset( $_GET['action'] ) && is_string( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

	// The forms and the confirmation screen have no list, so no Screen Options.
	if ( in_array( $action, array( 'new', 'edit', 'confirm-delete' ), true ) ) {
		return;
	}

	glinf_entities_get_list_table( $kind );

	add_screen_option(
		'per_page',
		array(
			'label'   => 'taxonomies' === $kind ? __( 'Taxonomies per page', 'gl-infinite-theme' ) : __( 'Entities per page', 'gl-infinite-theme' ),
			'default' => GLINF_LIST_PER_PAGE_DEFAULT,
			'option'  => glinf_entities_per_page_option( $kind ),
		)
	);

	$screen = get_current_screen();
	if ( $screen instanceof WP_Screen ) {
		$screen->set_screen_reader_content(
			'taxonomies' === $kind
				? array(
					'heading_views'      => __( 'Filter taxonomies list', 'gl-infinite-theme' ),
					'heading_pagination' => __( 'Taxonomies list navigation', 'gl-infinite-theme' ),
					'heading_list'       => __( 'Taxonomies list', 'gl-infinite-theme' ),
				)
				: array(
					'heading_views'      => __( 'Filter entities list', 'gl-infinite-theme' ),
					'heading_pagination' => __( 'Entities list navigation', 'gl-infinite-theme' ),
					'heading_list'       => __( 'Entities list', 'gl-infinite-theme' ),
				)
		);
	}

	glinf_entities_process_bulk_request( $kind );
}

/**
 * `load-` callback of the Entities screen.
 *
 * @return void
 */
function glinf_entities_load_entities_screen(): void {
	glinf_entities_load_screen( 'entities' );
}

/**
 * `load-` callback of the Taxonomies screen.
 *
 * @return void
 */
function glinf_entities_load_taxonomies_screen(): void {
	glinf_entities_load_screen( 'taxonomies' );
}

/**
 * Prints the tabs that switch between the two screens of the Manager.
 *
 * Shown on every screen (list, forms, confirmation). The current tab carries
 * aria-current, so it is not conveyed by colour alone.
 *
 * @param string $current "entities" or "taxonomies".
 * @return void
 */
function glinf_entities_render_tabs( string $current ): void {
	$tabs = array(
		'entities'   => __( 'Entities', 'gl-infinite-theme' ),
		'taxonomies' => __( 'Taxonomies', 'gl-infinite-theme' ),
	);
	?>
	<nav class="glinf-em-tabs nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Entity Manager', 'gl-infinite-theme' ); ?>">
		<?php foreach ( $tabs as $kind => $label ) : ?>
			<a href="<?php echo esc_url( glinf_entities_screen_url( $kind ) ); ?>" class="<?php echo esc_attr( $kind === $current ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>"<?php echo $kind === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Reserved spot, at the top of both screens, for the summary dashboard.
 *
 * Phase C of the Entity Manager redesign will print the dashboard here (counters,
 * shortcuts). It prints nothing today. It runs right under the tabs, before the
 * list or the form, on every screen of the Manager.
 *
 * @param string $section "entities" or "taxonomies".
 * @return void
 */
function glinf_entities_render_dashboard( string $section ): void {
	unset( $section );
}

/**
 * Opens a screen of the Manager: the wrapper, the heading with its action and the tabs.
 *
 * The caller closes the wrapper with `</div>`. The heading and the action are
 * on the same line (core "wp-heading-inline" pattern) and the tabs follow.
 *
 * @param string                              $section "entities" or "taxonomies" (the tab that stays active).
 * @param string                              $title   Heading of the screen.
 * @param array{label: string, url: string}|null $action  Optional "Add New" link.
 * @return void
 */
function glinf_entities_render_page_start( string $section, string $title, ?array $action = null ): void {
	?>
	<div class="wrap glinf-em glinf-em--<?php echo esc_attr( $section ); ?>">
		<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
		<?php if ( null !== $action ) : ?>
			<a href="<?php echo esc_url( $action['url'] ); ?>" class="page-title-action"><?php echo esc_html( $action['label'] ); ?></a>
		<?php endif; ?>
		<hr class="wp-header-end">

		<?php
		glinf_entities_render_tabs( $section );
		glinf_entities_render_dashboard( $section );
}

/**
 * Prints the empty-state card of a list (nothing configured yet).
 *
 * @param string                                    $icon   Dashicon class.
 * @param string                                    $title  Heading.
 * @param string                                    $text   Explanation.
 * @param array{label: string, url: string}|null    $action Optional call to action.
 * @return void
 */
function glinf_entities_render_empty_state( string $icon, string $title, string $text, ?array $action = null ): void {
	?>
	<section class="glinf-em-card glinf-em-empty">
		<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
		<h2><?php echo esc_html( $title ); ?></h2>
		<p><?php echo esc_html( $text ); ?></p>
		<?php if ( null !== $action ) : ?>
			<p><a href="<?php echo esc_url( $action['url'] ); ?>" class="button button-primary"><?php echo esc_html( $action['label'] ); ?></a></p>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Prints the confirmation screen of a deletion (one item or a group).
 *
 * Reaching this screen changes nothing. The "Yes, delete" button posts the slugs
 * and a nonce to admin-post.php, where the handler checks capability, method and
 * nonce again and validates the slugs before saving. Everything printed is
 * plain text escaped here.
 *
 * @param array<string, mixed> $args {
 *     Screen data.
 *
 *     @type string                                                    $heading      Card heading.
 *     @type string                                                    $intro        Sentence above the list of items.
 *     @type array<int, array{name: string, code: string, detail: string}> $items    Items to delete.
 *     @type string[]                                                  $consequences What happens, one sentence each.
 *     @type string                                                    $action       admin-post action.
 *     @type string                                                    $nonce_action Nonce action.
 *     @type string                                                    $nonce_name   Name of the nonce field.
 *     @type string[]                                                  $slugs        Slugs to post (already validated).
 *     @type string                                                    $cancel_url   Where "Cancel" goes.
 * }
 * @return void
 */
function glinf_entities_render_confirm_form( array $args ): void {
	?>
	<section class="glinf-em-card glinf-em-confirm" aria-labelledby="glinf-confirm-title">
		<h2 id="glinf-confirm-title"><?php echo esc_html( $args['heading'] ); ?></h2>
		<p><?php echo esc_html( $args['intro'] ); ?></p>

		<ul class="glinf-em-confirm__items">
			<?php foreach ( $args['items'] as $item ) : ?>
				<li>
					<strong><?php echo esc_html( $item['name'] ); ?></strong>
					<code><?php echo esc_html( $item['code'] ); ?></code>
					<?php if ( '' !== $item['detail'] ) : ?>
						<span class="glinf-em-confirm__detail"><?php echo esc_html( $item['detail'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<p><strong><?php esc_html_e( 'What happens', 'gl-infinite-theme' ); ?></strong></p>
		<ul class="glinf-em-confirm__effects">
			<?php foreach ( $args['consequences'] as $sentence ) : ?>
				<li><?php echo esc_html( $sentence ); ?></li>
			<?php endforeach; ?>
		</ul>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( $args['action'] ); ?>">
			<?php wp_nonce_field( $args['nonce_action'], $args['nonce_name'] ); ?>
			<?php foreach ( $args['slugs'] as $slug ) : ?>
				<input type="hidden" name="glinf_slugs[]" value="<?php echo esc_attr( $slug ); ?>">
			<?php endforeach; ?>
			<p class="submit">
				<button type="submit" class="button button-primary glinf-em-danger"><?php esc_html_e( 'Yes, delete', 'gl-infinite-theme' ); ?></button>
				<a href="<?php echo esc_url( $args['cancel_url'] ); ?>" class="button"><?php esc_html_e( 'Cancel', 'gl-infinite-theme' ); ?></a>
			</p>
		</form>
	</section>
	<?php
}
