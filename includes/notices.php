<?php
/**
 * The admin notices setup.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\AdminNotices;

// Set our alias items.
use QuickDirtyData as Core;
use QuickDirtyData\Helpers as Helpers;

/**
 * Start our engines.
 */
add_filter( 'removable_query_args', __NAMESPACE__ . '\set_removable_args' );
add_action( 'admin_notices', __NAMESPACE__ . '\admin_notices' );

/**
 * Add our custom strings to the vars.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array $args  The modified array of args.
 */
function set_removable_args( $args ) {

	// This should only happen on the admin side.
	if ( ! is_admin() ) {
		return $args;
	}

	// Set our array of args we use.
	$remove = array(
		Core\QUERY_BASE . 'result',
		Core\QUERY_BASE . 'success',
		Core\QUERY_BASE . 'error',
		Core\QUERY_BASE . 'type',
		Core\QUERY_BASE . 'count',
	);

	// Include my new args and return.
	return wp_parse_args( $remove, $args );
}

/**
 * Check for our data creation and display the result.
 *
 * @return void
 */
function admin_notices() {

	// We don't have either flag. Bail.
	if ( empty( $_GET[ Core\QUERY_BASE . 'result' ] ) ) {
		return;
	}

	// Figure out my data type.
	$data_type  = ! empty( $_GET[ Core\QUERY_BASE . 'type' ] ) ? sanitize_text_field( $_GET[ Core\QUERY_BASE . 'type' ] ) : 'unknown';

	// Handle the success.
	if ( ! empty( $_GET[ Core\QUERY_BASE . 'success' ] ) ) {

		// Figure out my count.
		$generated  = ! empty( $_GET[ Core\QUERY_BASE . 'count' ] ) ? absint( $_GET[ Core\QUERY_BASE . 'count' ] ) : 0;

		// Now switch between my data types.
		switch ( $data_type ) {

			// The text for orders.
			case 'posts':

				// Set my notice text.
				$notice_txt = sprintf( _n( 'Success! %d new post has been created.', 'Success! %d new posts have been created.', $generated, 'quick-dirty-data' ), $generated );

				// And break.
				break;

			// Create comments.
			case 'comments':

				// Set my notice text.
				$notice_txt = sprintf( _n( 'Success! %d new comment has been created.', 'Success! %d new comments have been created.', $generated, 'quick-dirty-data' ), $generated );

				// And break.
				break;
		}

		// Filter it.
		$notice_txt = apply_filters( Core\HOOK_PREFIX . 'success_notice_text', $notice_txt, $data_type );

		// And handle the notice.
		output_notice( $notice_txt, 'success' );

		// And be done.
		return;
	}

	// Handle the errors.
	if ( ! empty( $_GET[ Core\QUERY_BASE . 'error' ] ) ) {

		// Set our error type.
		$error_type = sanitize_text_field( $_GET[ Core\QUERY_BASE . 'error' ] );

		// Now switch between my data types.
		switch ( $error_type ) {

			// No ID.
			case 'no_id_created':
				$notice_txt = __( 'There was an error creating the new ID', 'quick-dirty-data' );
				break;

			// No array of posts.
			case 'no_post_array':
				$notice_txt = __( 'No posts exist that allow comments.', 'quick-dirty-data' );
				break;
		}

		// Filter it.
		$notice_txt = apply_filters( Core\HOOK_PREFIX . 'success_error_text', $notice_txt, $error_type, $data_type );

		// And handle the notice.
		output_notice( $notice_txt, 'error' );

		// And be done.
		return;
	}
}

/**
 * Output and display the actual admin notice.
 *
 * @param  string  $notice_text  The message text itself.
 * @param  string  $notice_type  What message type it is, so we know what class to include.
 * @param  boolean $can_dismiss  Whether to include the dismissable class.
 *
 * @return HTML
 */
function output_notice( $notice_text = '', $notice_type = 'info', $can_dismiss = true ) {

	// Bail with no text.
	if ( empty( $notice_text ) ) {
		return;
	}

	// Set our base class.
	$base_class = 'notice notice-' . sanitize_text_field( $notice_type );

	// Check for the dismissable.
	$set_class  = ! empty( $can_dismiss ) ? $base_class . ' is-dismissible' : $base_class;

	// And the actual message.
	echo '<div class="' . esc_attr( $set_class ) . '">';
		echo '<p><strong>' . wp_kses_post( $notice_text ) . '</strong></p>';
	echo '</div>';
}
