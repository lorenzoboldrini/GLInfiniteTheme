<?php
/**
 * Entity Manager capability: who may create, edit and delete entities.
 *
 * The "manage_theme_entities" capability is deliberately NOT given to the
 * administrator ROLE: it is stored as user meta on individual users (through
 * WP_User::add_cap()), so an administrator without it neither sees the menu
 * nor can call the handlers. Multisite super admins pass every capability
 * check, which is accepted.
 *
 * The capability is only EFFECTIVE together with "manage_options": it lives in
 * user meta, so it would otherwise survive a demotion (set_role() removes roles,
 * not individual capabilities) and leave a former administrator in control.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** Capability required by the Entity Manager screen and handlers. */
const GLINF_ENTITIES_CAP = 'manage_theme_entities';

/** Option guarding the one-off migration of existing sites. */
const GLINF_ENTITIES_CAPS_VERSION_OPTION = 'glinf_entities_caps_version';

/** Current version of the capability migration. */
const GLINF_ENTITIES_CAPS_VERSION = 1;

/**
 * Gives the capability to a user (stored as user meta, not in the role).
 *
 * @param WP_User $user Target user.
 * @return void
 */
function glinf_entities_grant_cap( WP_User $user ): void {
	// Look at the user's own capabilities: has_cap() would also be true for super admins.
	if ( empty( $user->caps[ GLINF_ENTITIES_CAP ] ) ) {
		$user->add_cap( GLINF_ENTITIES_CAP );
	}
}

/**
 * Grants the capability to the user who activates the theme.
 *
 * Also creates the options read on every request. The capability is handed out
 * ONLY on the first activation (or migration): the guard option is checked and
 * set, so another administrator who switches away and back cannot obtain it
 * without an existing holder granting it from the profile screen.
 *
 * @return void
 */
function glinf_entities_on_switch_theme(): void {
	glinf_entities_ensure_options();

	if ( (int) get_option( GLINF_ENTITIES_CAPS_VERSION_OPTION, 0 ) >= GLINF_ENTITIES_CAPS_VERSION ) {
		return;
	}

	$user = wp_get_current_user();

	if ( $user->exists() && user_can( $user, 'switch_themes' ) ) {
		glinf_entities_grant_cap( $user );
		update_option( GLINF_ENTITIES_CAPS_VERSION_OPTION, GLINF_ENTITIES_CAPS_VERSION, true );
	}
}
add_action( 'after_switch_theme', 'glinf_entities_on_switch_theme' );

/**
 * One-off migration for sites where the theme was already active.
 *
 * Gives the capability to the administrator with the lowest ID (the "default
 * admin"). Guarded by an autoloaded option, so afterwards it costs nothing.
 * It only runs for a logged-in administrator because `admin_init` also fires
 * for anonymous requests to admin-ajax.php.
 *
 * @return void
 */
function glinf_entities_migrate_caps(): void {
	if ( (int) get_option( GLINF_ENTITIES_CAPS_VERSION_OPTION, 0 ) >= GLINF_ENTITIES_CAPS_VERSION ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	glinf_entities_ensure_options();

	$admins = get_users(
		array(
			'role'    => 'administrator',
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => 1,
		)
	);

	if ( ! empty( $admins ) && $admins[0] instanceof WP_User ) {
		glinf_entities_grant_cap( $admins[0] );
	}

	update_option( GLINF_ENTITIES_CAPS_VERSION_OPTION, GLINF_ENTITIES_CAPS_VERSION, true );
}
add_action( 'admin_init', 'glinf_entities_migrate_caps' );

/**
 * Makes the Entity Manager capability effective only for administrators.
 *
 * Appending "manage_options" to the required primitive capabilities means a
 * user must hold BOTH: the explicit capability and administrator-level rights.
 * A demoted user keeps the stale user meta but loses the access.
 *
 * @param string[] $caps Primitive capabilities required for the check.
 * @param string   $cap  Capability being checked.
 * @return string[]
 */
function glinf_entities_map_meta_cap( array $caps, string $cap ): array {
	if ( GLINF_ENTITIES_CAP === $cap ) {
		$caps[] = 'manage_options';
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'glinf_entities_map_meta_cap', 10, 2 );

/**
 * Tells whether the current user may manage the capability of a profile.
 *
 * @param WP_User $profile_user User whose profile is shown or saved.
 * @return bool
 */
function glinf_entities_can_manage_profile_cap( WP_User $profile_user ): bool {
	return current_user_can( GLINF_ENTITIES_CAP )
		&& current_user_can( 'edit_user', $profile_user->ID )
		// Only administrators (or above) are eligible.
		&& user_can( $profile_user, 'manage_options' );
}

/**
 * Prints the "Entity Manager" section in the user profile screen.
 *
 * Visible only to users who already have the capability, and only for
 * administrator targets. On your own profile the checkbox is read-only: nobody
 * can revoke the capability from themselves, which prevents a lock-out.
 *
 * @param WP_User $profile_user User being edited.
 * @return void
 */
function glinf_entities_render_profile_section( WP_User $profile_user ): void {
	if ( ! glinf_entities_can_manage_profile_cap( $profile_user ) ) {
		return;
	}

	$is_self    = get_current_user_id() === $profile_user->ID;
	$is_checked = $is_self || ! empty( $profile_user->caps[ GLINF_ENTITIES_CAP ] );
	?>
	<h2><?php esc_html_e( 'Entity Manager', 'gl-infinite-theme' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Permissions', 'gl-infinite-theme' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php esc_html_e( 'Entity Manager permissions', 'gl-infinite-theme' ); ?></span></legend>
					<?php if ( ! $is_self ) : ?>
						<?php wp_nonce_field( 'glinf_entities_profile_' . $profile_user->ID, 'glinf_entities_profile_nonce', false ); ?>
					<?php endif; ?>
					<label for="glinf-can-manage-entities">
						<input type="checkbox" name="glinf_can_manage_entities" id="glinf-can-manage-entities" value="1" aria-describedby="glinf-can-manage-entities-desc" <?php checked( $is_checked ); ?> <?php disabled( $is_self ); ?>>
						<?php esc_html_e( 'Can manage entities', 'gl-infinite-theme' ); ?>
					</label>
					<p class="description" id="glinf-can-manage-entities-desc">
						<?php
						if ( $is_self ) {
							esc_html_e( 'You cannot remove this permission from yourself.', 'gl-infinite-theme' );
						} else {
							esc_html_e( 'Allows this user to create, edit and delete entities (custom content types).', 'gl-infinite-theme' );
						}
						?>
					</p>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'glinf_entities_render_profile_section' );
add_action( 'edit_user_profile', 'glinf_entities_render_profile_section' );

/**
 * Saves the capability checkbox of the profile screen.
 *
 * WordPress core has already verified the profile nonce; the capability of
 * the current user, the target and our own nonce are checked here too. Any
 * failed check silently leaves the capability untouched.
 *
 * @param int $user_id ID of the user being edited.
 * @return void
 */
function glinf_entities_save_profile_section( int $user_id ): void {
	if ( ! current_user_can( GLINF_ENTITIES_CAP ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	// Never change your own permission (lock-out protection).
	if ( get_current_user_id() === $user_id ) {
		return;
	}

	$nonce = isset( $_POST['glinf_entities_profile_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['glinf_entities_profile_nonce'] ) ) : '';
	if ( false === wp_verify_nonce( $nonce, 'glinf_entities_profile_' . $user_id ) ) {
		return;
	}

	$target = get_userdata( $user_id );
	if ( ! $target instanceof WP_User || ! glinf_entities_can_manage_profile_cap( $target ) ) {
		return;
	}

	$grant = isset( $_POST['glinf_can_manage_entities'] ) && '1' === $_POST['glinf_can_manage_entities'];

	if ( $grant ) {
		glinf_entities_grant_cap( $target );
	} else {
		$target->remove_cap( GLINF_ENTITIES_CAP );
	}
}
add_action( 'edit_user_profile_update', 'glinf_entities_save_profile_section' );
