<?php
/**
 * Entity Manager lists: the WP_List_Table classes of the "Entities" and "Taxonomies" screens.
 *
 * This file extends WP_List_Table, which exists only in wp-admin and is not
 * loaded on the front end: NEVER require it from the bootstrap. It is loaded on
 * demand by glinf_entities_get_list_table() (admin-common.php), right after
 * requiring the core class.
 *
 * The data side (rows, filters, sorting, pagination) is in list-data.php and is
 * pure; these classes only draw it. Everything is read from the configuration in
 * memory: sorting and filtering never query the database. The only queries are
 * the per-row counters (content of an entity, terms of a taxonomy), made for the
 * rows on the current page only and cached by core.
 *
 * Differences from a stock list table, all on purpose:
 * - the search and the filter dropdown belong to a GET form of their own
 *   (`#glinf-list-filter`), while the table sits in a POST form for the group
 *   action: the controls inside the table reach the GET form through the `form`
 *   attribute, so the two forms never mix;
 * - pagination is drawn here, from the whitelisted state, instead of from the raw
 *   request URI, and shows the page number as text (there is no "go to page" box
 *   that could post to the wrong form).
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Behavior shared by the Entities and the Taxonomies lists.
 */
abstract class GLINF_Entity_Manager_List_Table extends WP_List_Table {

	/**
	 * Kind of list: "entities" or "taxonomies".
	 *
	 * @var string
	 */
	protected string $kind = 'entities';

	/**
	 * Whitelisted state read from the query string (see glinf_list_parse_query()).
	 *
	 * @var array<string, mixed>
	 */
	protected array $query = array();

	/**
	 * Number of rows of every view, on the whole set.
	 *
	 * @var array<string, int>
	 */
	protected array $view_counts = array();

	/**
	 * Number of rows before search, filter and view are applied.
	 *
	 * @var int
	 */
	protected int $total_rows = 0;

	/**
	 * Sets up the table for the current screen.
	 *
	 * @param string $kind     "entities" or "taxonomies".
	 * @param string $plural   Plural id used for CSS classes and the bulk nonce action.
	 * @param string $singular Singular id.
	 */
	public function __construct( string $kind, string $plural, string $singular ) {
		$this->kind = $kind;

		parent::__construct(
			array(
				'plural'   => $plural,
				'singular' => $singular,
				'ajax'     => false,
			)
		);
	}

	/**
	 * Builds the rows of this list from the configuration.
	 *
	 * @param array<string, mixed> $config Normalized configuration.
	 * @return array<string, array<string, mixed>>
	 */
	abstract protected function build_rows( array $config ): array;

	/**
	 * Returns the options of the dropdown filter: slug => plural name.
	 *
	 * @param array<string, mixed> $config Normalized configuration.
	 * @return array<string, string>
	 */
	abstract protected function get_filter_options( array $config ): array;

	/**
	 * Returns the label of the dropdown filter (screen reader text).
	 *
	 * @return string
	 */
	abstract protected function get_filter_label(): string;

	/**
	 * Returns the text of the "no filter" option of the dropdown.
	 *
	 * @return string
	 */
	abstract protected function get_filter_all_label(): string;

	/**
	 * Returns the label of the search button and box.
	 *
	 * @return string
	 */
	abstract protected function get_search_label(): string;

	/**
	 * Returns the name of the nonce action of the group form (created by core, see display_tablenav()).
	 *
	 * @return string
	 */
	public function get_bulk_nonce_action(): string {
		return 'bulk-' . $this->_args['plural'];
	}

	/**
	 * Returns the whitelisted state of the list (available after prepare_items()).
	 *
	 * @return array<string, mixed>
	 */
	public function get_query_state(): array {
		return $this->query;
	}

	/**
	 * Returns the number of rows to show per page (Screen Options, 1 to 100).
	 *
	 * @return int
	 */
	protected function get_per_page(): int {
		return glinf_list_clamp_per_page(
			$this->get_items_per_page( glinf_entities_per_page_option( $this->kind ), GLINF_LIST_PER_PAGE_DEFAULT )
		);
	}

	/**
	 * Reads the state, applies view, filter, search, sorting and pagination to the configuration.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$config = glinf_get_config();

		// Whitelisted field by field; reading the state changes nothing.
		$this->query = glinf_entities_current_list_query( $this->kind );

		$rows              = $this->build_rows( $config );
		$this->total_rows  = count( $rows );
		$this->view_counts = glinf_list_count_views( $this->kind, $rows );

		$rows = glinf_list_filter_rows( $this->kind, $rows, $this->query );
		$rows = glinf_list_sort_rows( $this->kind, $rows, $this->query['orderby'], $this->query['order'] );

		$per_page = $this->get_per_page();
		$page     = glinf_list_paginate( $rows, $per_page, $this->query['paged'] );

		$this->query['paged'] = $page['paged'];
		$this->items          = $page['rows'];

		$this->set_pagination_args(
			array(
				'total_items' => $page['total'],
				'per_page'    => $per_page,
				'total_pages' => $page['pages'],
			)
		);
	}

	/**
	 * Builds an URL of this list that keeps the current state, with some changes.
	 *
	 * @param array<string, string|int> $overrides State values to change (e.g. array( 'paged' => 2 )).
	 * @return string
	 */
	protected function list_url( array $overrides = array() ): string {
		return glinf_entities_list_url( $this->kind, $this->query, $overrides );
	}

	/**
	 * Returns the views (links above the list) with their counters.
	 *
	 * @return array<string, string>
	 */
	protected function get_views() {
		$links = array();

		foreach ( glinf_list_get_view_labels( $this->kind ) as $view => $label ) {
			$links[ $view ] = array(
				'url'     => $this->list_url(
					array(
						'view'  => $view,
						'paged' => 0,
					)
				),
				'label'   => sprintf(
					'%1$s <span class="count">(%2$s)</span>',
					esc_html( $label ),
					esc_html( number_format_i18n( $this->view_counts[ $view ] ?? 0 ) )
				),
				'current' => ( $this->query['view'] ?? 'all' ) === $view,
			);
		}

		return $this->get_views_links( $links );
	}

	/**
	 * Returns the group actions: only "Delete".
	 *
	 * @return array<string, string>
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'gl-infinite-theme' ),
		);
	}

	/**
	 * Returns the classes of the table element.
	 *
	 * "fixed" is left out on purpose: with seven columns, equal widths squeeze the name.
	 *
	 * @return string[]
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'striped', 'glinf-em-table', $this->_args['plural'] );
	}

	/**
	 * Prints the dropdown filter above the table (top only, so ids are unique).
	 *
	 * The controls are associated with the GET form through the `form` attribute:
	 * they are inside the table's POST form in the markup but never submitted with it.
	 *
	 * @param string $which "top" or "bottom".
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$options = $this->get_filter_options( glinf_get_config() );

		if ( array() === $options ) {
			return;
		}
		?>
		<div class="alignleft actions glinf-em-filter">
			<label class="screen-reader-text" for="glinf-filter-select"><?php echo esc_html( $this->get_filter_label() ); ?></label>
			<select id="glinf-filter-select" name="filter" form="glinf-list-filter">
				<option value=""><?php echo esc_html( $this->get_filter_all_label() ); ?></option>
				<?php foreach ( $options as $slug => $name ) : ?>
					<option value="<?php echo esc_attr( (string) $slug ); ?>" <?php selected( $this->query['filter'] ?? '', (string) $slug ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Filter', 'gl-infinite-theme' ), '', '', false, array( 'id' => 'glinf-filter-submit', 'form' => 'glinf-list-filter' ) ); ?>
		</div>
		<?php
	}

	/**
	 * Prints the message of the empty table (items exist, but search or filters hide them all).
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'Nothing matches your search or filters.', 'gl-infinite-theme' );
		echo ' <a href="' . esc_url( glinf_entities_screen_url( $this->kind ) ) . '">' . esc_html__( 'Reset filters', 'gl-infinite-theme' ) . '</a>';
	}

	/**
	 * Builds a first/previous/next/last link of the pagination.
	 *
	 * @param string $class_name CSS class of the link.
	 * @param int    $page       Target page.
	 * @param string $label      Text for screen readers.
	 * @param string $glyph      Visible glyph (an HTML entity, not user data).
	 * @return string
	 */
	private function page_link( string $class_name, int $page, string $label, string $glyph ): string {
		return sprintf(
			'<a class="%1$s button" href="%2$s"><span class="screen-reader-text">%3$s</span><span aria-hidden="true">%4$s</span></a>',
			esc_attr( $class_name ),
			esc_url( $this->list_url( array( 'paged' => $page ) ) ),
			esc_html( $label ),
			$glyph
		);
	}

	/**
	 * Prints the pagination.
	 *
	 * Same markup and classes as the core one, but the links come from the
	 * whitelisted state and the current page is text, not an input.
	 *
	 * @param string $which "top" or "bottom".
	 * @return void
	 */
	protected function pagination( $which ) {
		$total = (int) $this->get_pagination_arg( 'total_items' );

		if ( $total < 1 ) {
			echo '<div class="tablenav-pages no-pages"><span class="displaying-num">' . esc_html__( '0 items', 'gl-infinite-theme' ) . '</span></div>';
			return;
		}

		$pages   = max( 1, (int) $this->get_pagination_arg( 'total_pages' ) );
		$current = min( max( 1, (int) ( $this->query['paged'] ?? 1 ) ), $pages );

		if ( 'top' === $which && $pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">' . esc_html(
			sprintf(
				/* translators: %s: number of items. */
				_n( '%s item', '%s items', $total, 'gl-infinite-theme' ),
				number_format_i18n( $total )
			)
		) . '</span>';

		$links   = array();
		$links[] = $current > 1
			? $this->page_link( 'first-page', 1, __( 'First page', 'gl-infinite-theme' ), '&laquo;' )
			: '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		$links[] = $current > 1
			? $this->page_link( 'prev-page', $current - 1, __( 'Previous page', 'gl-infinite-theme' ), '&lsaquo;' )
			: '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';

		$links[] = '<span class="screen-reader-text">' . esc_html__( 'Current Page', 'gl-infinite-theme' ) . '</span>'
			. '<span class="paging-input"><span class="tablenav-paging-text">'
			. wp_kses(
				sprintf(
					/* translators: 1: current page, 2: total pages. */
					_x( '%1$s of %2$s', 'paging', 'gl-infinite-theme' ),
					'<span class="current-page-number">' . esc_html( number_format_i18n( $current ) ) . '</span>',
					'<span class="total-pages">' . esc_html( number_format_i18n( $pages ) ) . '</span>'
				),
				array( 'span' => array( 'class' => array() ) )
			)
			. '</span></span>';

		$links[] = $current < $pages
			? $this->page_link( 'next-page', $current + 1, __( 'Next page', 'gl-infinite-theme' ), '&rsaquo;' )
			: '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		$links[] = $current < $pages
			? $this->page_link( 'last-page', $pages, __( 'Last page', 'gl-infinite-theme' ), '&raquo;' )
			: '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';

		$output .= "\n<span class=\"pagination-links\">" . implode( "\n", $links ) . '</span>';

		// Every fragment above is escaped where it is built; the glyphs are fixed entities.
		echo '<div class="tablenav-pages' . ( $pages < 2 ? ' one-page' : '' ) . '">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Prints the checkbox of a row.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_cb( $item ) {
		$field_id = 'cb-select-' . $item['slug'];

		return sprintf(
			'<input type="checkbox" name="glinf_selected[]" value="%1$s" id="%2$s"><label for="%2$s"><span class="screen-reader-text">%3$s</span></label>',
			esc_attr( $item['slug'] ),
			esc_attr( $field_id ),
			esc_html(
				sprintf(
					/* translators: %s: name of the item. */
					__( 'Select %s', 'gl-infinite-theme' ),
					$item['plural']
				)
			)
		);
	}

	/**
	 * Returns the accessible name of the row header (WordPress 7.1+; harmless before).
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function get_primary_column_aria_label( $item ) {
		return (string) $item['plural'];
	}

	/**
	 * Returns the "yes"/"no" pill. The text is always there: colour never carries the meaning alone.
	 *
	 * @param bool $on True for "Yes".
	 * @return string
	 */
	protected function bool_badge( bool $on ): string {
		return sprintf(
			'<span class="glinf-em-badge %1$s">%2$s</span>',
			$on ? 'glinf-em-badge--on' : 'glinf-em-badge--off',
			esc_html( $on ? __( 'Yes', 'gl-infinite-theme' ) : __( 'No', 'gl-infinite-theme' ) )
		);
	}

	/**
	 * Prints the "search" form (GET) and the table (POST, for the group action).
	 *
	 * The views come first in the markup, then the search, then the table: the
	 * tab order follows the reading order.
	 *
	 * @return void
	 */
	public function render(): void {
		$page = glinf_entities_screen_page( $this->kind );
		?>
		<div class="glinf-em-toolbar">
			<?php $this->views(); ?>
			<form id="glinf-list-filter" class="glinf-em-search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
				<?php if ( 'all' !== ( $this->query['view'] ?? 'all' ) ) : ?>
					<input type="hidden" name="view" value="<?php echo esc_attr( $this->query['view'] ); ?>">
				<?php endif; ?>
				<?php if ( 'name' !== ( $this->query['orderby'] ?? 'name' ) || 'asc' !== ( $this->query['order'] ?? 'asc' ) ) : ?>
					<input type="hidden" name="orderby" value="<?php echo esc_attr( $this->query['orderby'] ); ?>">
					<input type="hidden" name="order" value="<?php echo esc_attr( $this->query['order'] ); ?>">
				<?php endif; ?>
				<p class="search-box">
					<label class="screen-reader-text" for="glinf-list-search-input"><?php echo esc_html( $this->get_search_label() ); ?>:</label>
					<input type="search" id="glinf-list-search-input" name="s" value="<?php echo esc_attr( $this->query['s'] ?? '' ); ?>" maxlength="<?php echo esc_attr( (string) GLINF_LIST_SEARCH_MAX ); ?>">
					<?php submit_button( $this->get_search_label(), 'compact', '', false, array( 'id' => 'search-submit' ) ); ?>
				</p>
			</form>
		</div>

		<form id="glinf-list-bulk" method="post" action="<?php echo esc_url( $this->list_url() ); ?>">
			<?php $this->display(); ?>
		</form>
		<?php
	}
}

/**
 * The list of entities.
 */
class GLINF_Entities_List_Table extends GLINF_Entity_Manager_List_Table {

	/**
	 * Sets up the table.
	 */
	public function __construct() {
		parent::__construct( 'entities', 'glinf-entities', 'glinf-entity' );
	}

	/**
	 * Returns the columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox">',
			'name'       => __( 'Name', 'gl-infinite-theme' ),
			'slug'       => __( 'Slug (post type)', 'gl-infinite-theme' ),
			'url_base'   => __( 'URL base', 'gl-infinite-theme' ),
			'archive'    => __( 'Archive', 'gl-infinite-theme' ),
			'taxonomies' => __( 'Taxonomies', 'gl-infinite-theme' ),
			'content'    => __( 'Content', 'gl-infinite-theme' ),
		);
	}

	/**
	 * Returns the sortable columns (the "content" counter is not: it would need a query per entity).
	 *
	 * The fourth item is printed as it is in a caption: it must be escaped here.
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	protected function get_sortable_columns() {
		return array(
			'name'       => array( 'name', false, '', esc_html__( 'Table ordered by Name.', 'gl-infinite-theme' ), 'asc' ),
			'slug'       => array( 'slug', false, '', esc_html__( 'Table ordered by Slug.', 'gl-infinite-theme' ) ),
			'url_base'   => array( 'url_base', false, '', esc_html__( 'Table ordered by URL base.', 'gl-infinite-theme' ) ),
			'archive'    => array( 'archive', false, '', esc_html__( 'Table ordered by Archive.', 'gl-infinite-theme' ) ),
			'taxonomies' => array( 'taxonomies', false, '', esc_html__( 'Table ordered by number of taxonomies.', 'gl-infinite-theme' ) ),
		);
	}

	/**
	 * Builds the rows.
	 *
	 * @param array<string, mixed> $config Normalized configuration.
	 * @return array<string, array<string, mixed>>
	 */
	protected function build_rows( array $config ): array {
		return glinf_list_build_entity_rows( $config );
	}

	/**
	 * Returns the taxonomies for the dropdown filter.
	 *
	 * @param array<string, mixed> $config Normalized configuration.
	 * @return array<string, string>
	 */
	protected function get_filter_options( array $config ): array {
		return array_map( static fn( array $taxonomy ): string => $taxonomy['plural'], $config['taxonomies'] );
	}

	/**
	 * Returns the label of the dropdown.
	 *
	 * @return string
	 */
	protected function get_filter_label(): string {
		return __( 'Filter by taxonomy', 'gl-infinite-theme' );
	}

	/**
	 * Returns the "no filter" option.
	 *
	 * @return string
	 */
	protected function get_filter_all_label(): string {
		return __( 'All taxonomies', 'gl-infinite-theme' );
	}

	/**
	 * Returns the label of the search box.
	 *
	 * @return string
	 */
	protected function get_search_label(): string {
		return __( 'Search Entities', 'gl-infinite-theme' );
	}

	/**
	 * Draws the name: icon, plural (link to the form), singular.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_name( array $item ): string {
		return sprintf(
			'<span class="glinf-em-name"><span class="dashicons %1$s" aria-hidden="true"></span><span class="glinf-em-name__text"><strong><a class="row-title" href="%2$s">%3$s</a></strong> <span class="glinf-em-name__singular">(%4$s)</span></span></span>',
			esc_attr( $item['icon'] ),
			esc_url(
				glinf_entities_admin_url(
					array(
						'action' => 'edit',
						'entity' => $item['slug'],
					)
				)
			),
			esc_html( $item['plural'] ),
			esc_html( $item['singular'] )
		);
	}

	/**
	 * Draws the row actions under the name: Edit, View archive (when it exists), Delete.
	 *
	 * "Delete" only leads to the confirmation screen (a plain link: viewing it changes nothing).
	 *
	 * @param array<string, mixed> $item        Row.
	 * @param string               $column_name Current column.
	 * @param string               $primary     Primary column.
	 * @return string
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$actions = array(
			'edit' => sprintf(
				'<a href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url(
					glinf_entities_admin_url(
						array(
							'action' => 'edit',
							'entity' => $item['slug'],
						)
					)
				),
				/* translators: %s: plural name of the entity. */
				esc_attr( sprintf( __( 'Edit %s', 'gl-infinite-theme' ), $item['plural'] ) ),
				esc_html__( 'Edit', 'gl-infinite-theme' )
			),
		);

		// False when the post type is not registered or has no archive.
		$archive_url = $item['has_archive'] ? get_post_type_archive_link( glinf_entity_post_type( $item['slug'] ) ) : false;
		if ( $archive_url ) {
			$actions['view'] = sprintf(
				'<a href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url( $archive_url ),
				/* translators: %s: plural name of the entity. */
				esc_attr( sprintf( __( 'View the archive of %s', 'gl-infinite-theme' ), $item['plural'] ) ),
				esc_html__( 'View archive', 'gl-infinite-theme' )
			);
		}

		$actions['delete'] = sprintf(
			'<a href="%1$s" class="submitdelete" aria-label="%2$s">%3$s</a>',
			esc_url(
				glinf_entities_admin_url(
					array(
						'action' => 'confirm-delete',
						'entity' => $item['slug'],
					)
				)
			),
			/* translators: %s: plural name of the entity. */
			esc_attr( sprintf( __( 'Delete %s', 'gl-infinite-theme' ), $item['plural'] ) ),
			esc_html__( 'Delete', 'gl-infinite-theme' )
		);

		return $this->row_actions( $actions, true );
	}

	/**
	 * Draws the slug (post type).
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_slug( array $item ): string {
		return '<code>' . esc_html( glinf_entity_post_type( $item['slug'] ) ) . '</code>';
	}

	/**
	 * Draws the effective URL base.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_url_base( array $item ): string {
		return '<code>' . esc_html( '/' . $item['url_base'] . '/' ) . '</code>';
	}

	/**
	 * Draws the archive pill.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_archive( array $item ): string {
		return $this->bool_badge( (bool) $item['has_archive'] );
	}

	/**
	 * Draws the names of the attached taxonomies ("None" for screen readers when there are none).
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_taxonomies( array $item ): string {
		if ( array() === $item['taxonomy_names'] ) {
			return glinf_entities_empty_value_html();
		}

		return esc_html( implode( ', ', $item['taxonomy_names'] ) );
	}

	/**
	 * Draws the content counter, linked to the list of the items.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_content( array $item ): string {
		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg( 'post_type', glinf_entity_post_type( $item['slug'] ), admin_url( 'edit.php' ) ) ),
			esc_html( number_format_i18n( glinf_entities_count_items( $item['slug'] ) ) )
		);
	}
}

/**
 * The list of taxonomies.
 */
class GLINF_Taxonomies_List_Table extends GLINF_Entity_Manager_List_Table {

	/**
	 * Sets up the table.
	 */
	public function __construct() {
		parent::__construct( 'taxonomies', 'glinf-taxonomies', 'glinf-taxonomy' );
	}

	/**
	 * Returns the columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox">',
			'name'         => __( 'Name', 'gl-infinite-theme' ),
			'slug'         => __( 'Key', 'gl-infinite-theme' ),
			'url_base'     => __( 'URL base', 'gl-infinite-theme' ),
			'hierarchical' => __( 'Hierarchical', 'gl-infinite-theme' ),
			'scope'        => __( 'Attached to', 'gl-infinite-theme' ),
			'terms'        => __( 'Terms', 'gl-infinite-theme' ),
		);
	}

	/**
	 * Returns the sortable columns (the terms counter is not: it would need a query per taxonomy).
	 *
	 * The fourth item is printed as it is in a caption: it must be escaped here.
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	protected function get_sortable_columns() {
		return array(
			'name'         => array( 'name', false, '', esc_html__( 'Table ordered by Name.', 'gl-infinite-theme' ), 'asc' ),
			'slug'         => array( 'slug', false, '', esc_html__( 'Table ordered by Key.', 'gl-infinite-theme' ) ),
			'url_base'     => array( 'url_base', false, '', esc_html__( 'Table ordered by URL base.', 'gl-infinite-theme' ) ),
			'hierarchical' => array( 'hierarchical', false, '', esc_html__( 'Table ordered by Hierarchical.', 'gl-infinite-theme' ) ),
			'scope'        => array( 'scope', false, '', esc_html__( 'Table ordered by Attached to.', 'gl-infinite-theme' ) ),
		);
	}

	/**
	 * Builds the rows.
	 *
	 * @param array<string, mixed> $config Normalized configuration.
	 * @return array<string, array<string, mixed>>
	 */
	protected function build_rows( array $config ): array {
		return glinf_list_build_taxonomy_rows( $config );
	}

	/**
	 * Returns the entities for the dropdown filter.
	 *
	 * @param array<string, mixed> $config Normalized configuration.
	 * @return array<string, string>
	 */
	protected function get_filter_options( array $config ): array {
		return array_map( static fn( array $entity ): string => $entity['plural'], $config['items'] );
	}

	/**
	 * Returns the label of the dropdown.
	 *
	 * @return string
	 */
	protected function get_filter_label(): string {
		return __( 'Filter by entity', 'gl-infinite-theme' );
	}

	/**
	 * Returns the "no filter" option.
	 *
	 * @return string
	 */
	protected function get_filter_all_label(): string {
		return __( 'All entities', 'gl-infinite-theme' );
	}

	/**
	 * Returns the label of the search box.
	 *
	 * @return string
	 */
	protected function get_search_label(): string {
		return __( 'Search Taxonomies', 'gl-infinite-theme' );
	}

	/**
	 * Draws the name: plural (link to the form) and singular.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_name( array $item ): string {
		return sprintf(
			'<span class="glinf-em-name"><span class="dashicons dashicons-tag" aria-hidden="true"></span><span class="glinf-em-name__text"><strong><a class="row-title" href="%1$s">%2$s</a></strong> <span class="glinf-em-name__singular">(%3$s)</span></span></span>',
			esc_url(
				glinf_taxonomies_admin_url(
					array(
						'action'         => 'edit',
						'glinf_taxonomy' => $item['slug'],
					)
				)
			),
			esc_html( $item['plural'] ),
			esc_html( $item['singular'] )
		);
	}

	/**
	 * Draws the row actions under the name: Edit and Delete (the latter only leads to the confirmation screen).
	 *
	 * @param array<string, mixed> $item        Row.
	 * @param string               $column_name Current column.
	 * @param string               $primary     Primary column.
	 * @return string
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$actions = array(
			'edit'   => sprintf(
				'<a href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url(
					glinf_taxonomies_admin_url(
						array(
							'action'         => 'edit',
							'glinf_taxonomy' => $item['slug'],
						)
					)
				),
				/* translators: %s: plural name of the taxonomy. */
				esc_attr( sprintf( __( 'Edit %s', 'gl-infinite-theme' ), $item['plural'] ) ),
				esc_html__( 'Edit', 'gl-infinite-theme' )
			),
			'delete' => sprintf(
				'<a href="%1$s" class="submitdelete" aria-label="%2$s">%3$s</a>',
				esc_url(
					glinf_taxonomies_admin_url(
						array(
							'action'         => 'confirm-delete',
							'glinf_taxonomy' => $item['slug'],
						)
					)
				),
				/* translators: %s: plural name of the taxonomy. */
				esc_attr( sprintf( __( 'Delete %s', 'gl-infinite-theme' ), $item['plural'] ) ),
				esc_html__( 'Delete', 'gl-infinite-theme' )
			),
		);

		return $this->row_actions( $actions, true );
	}

	/**
	 * Draws the registered key.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_slug( array $item ): string {
		return '<code>' . esc_html( $item['key'] ) . '</code>';
	}

	/**
	 * Draws the effective URL base.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_url_base( array $item ): string {
		return '<code>' . esc_html( '/' . $item['url_base'] . '/' ) . '</code>';
	}

	/**
	 * Draws the hierarchical pill.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_hierarchical( array $item ): string {
		return $this->bool_badge( (bool) $item['hierarchical'] );
	}

	/**
	 * Draws how the taxonomy is attached: the scope pill plus the entity name (specific) or the number of entities (shared).
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_scope( array $item ): string {
		$labels = glinf_taxonomies_get_scope_labels();
		$scope  = (string) $item['scope'];
		$detail = '';

		if ( 'specific' === $scope && isset( $item['entity_names'][0] ) ) {
			$detail = $item['entity_names'][0];
		} elseif ( 'shared' === $scope ) {
			$detail = sprintf(
				/* translators: %d: number of entities the taxonomy is attached to. */
				_n( '%d entity', '%d entities', count( $item['entity_slugs'] ), 'gl-infinite-theme' ),
				count( $item['entity_slugs'] )
			);
		}

		return sprintf(
			'<span class="glinf-em-badge glinf-em-badge--%1$s">%2$s</span>%3$s',
			esc_attr( $scope ),
			esc_html( $labels[ $scope ] ?? $scope ),
			'' === $detail ? '' : ' <span class="glinf-em-scope-detail">' . esc_html( $detail ) . '</span>'
		);
	}

	/**
	 * Draws the number of terms (only for a registered taxonomy), linked to the screen that manages them.
	 *
	 * @param array<string, mixed> $item Row.
	 * @return string
	 */
	protected function column_terms( array $item ): string {
		$count = glinf_taxonomies_count_terms( $item['key'] );

		if ( null === $count ) {
			return glinf_entities_empty_value_html( __( 'Not available: the taxonomy is not attached to any entity', 'gl-infinite-theme' ) );
		}

		$link = add_query_arg(
			array(
				'taxonomy'  => $item['key'],
				'post_type' => glinf_entity_post_type( (string) ( $item['entity_slugs'][0] ?? '' ) ),
			),
			admin_url( 'edit-tags.php' )
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $link ),
			esc_html( number_format_i18n( $count ) )
		);
	}
}
