<?php
/**
 * The specific admin bar setup.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\AdminBar;

// Set our alias items.
use QuickDirtyData as Core;
use QuickDirtyData\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'admin_bar_menu', __NAMESPACE__ . '\load_admin_bar', 9999 );

/**
 * Set up some quick links for the admin bar.
 *
 * @param  WP_Admin_Bar $wp_admin_bar  The global WP_Admin_Bar object.
 *
 * @return void.
 */
function load_admin_bar( $wp_admin_bar ) {

	// Bail if current user doesnt have cap.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// First check for datatypes.
	$datatypes  = Helpers\get_datatypes( 'admin-bar' );

	// Bail if no datatypes exist.
	if ( empty( $datatypes ) ) {
		return;
	}

	// Get my parent menu items.
	$parent_id  = set_parent_node( $wp_admin_bar );

	// Bail if no parent ID was set.
	if ( ! $parent_id ) {
		return;
	}

	// Set my base URL.
	$query_base = set_query_base();

	// Now loop my types.
	foreach ( $datatypes as $datatype ) {

		// Make my link.
		$item_link  = add_query_arg( array( Core\QUERY_BASE . 'type' => sanitize_key( $datatype ) ), $query_base );

		// Set my label.
		$item_label = sprintf( __( 'Generate %s', 'quick-dirty-data' ), ucfirst( $datatype ) );

		// Set my args for the item.
		$item_args  = array(
			'id'       => $parent_id . '-' . esc_attr( $datatype ),
			'title'    => esc_attr( $item_label ),
			'href'     => esc_url( $item_link ),
			'position' => 0,
			'parent'   => $parent_id,
			'meta'     => array(
				'title' => esc_attr( $item_label ),
			),
		);

		// Set my arguments via filter.
		$node_args  = apply_filters( Core\HOOK_PREFIX . 'admin_bar_item_args', $item_args, $datatype );

		// Add the individual link.
		$wp_admin_bar->add_node( $node_args );

		// Nothing left for the individual link.
	}

	// No remaining changes to the admin bar.
}

/**
 * Set the parent node and return the ID used.
 *
 * @param  WP_Admin_Bar $wp_admin_bar  The global WP_Admin_Bar object.
 *
 * @return string
 */
function set_parent_node( $wp_admin_bar ) {

	// Bail without the bar.
	if ( empty( $wp_admin_bar ) ) {
		return false;
	}

	// Set my base args for the item.
	$base_args  = array(
		'id'     => Core\ADMIN_BAR_ID,
		'title'  => __( 'QD Data', 'quick-dirty-data' ),
		'parent' => 0,
	);

	// Get my parent menu items.
	$node_args  = apply_filters( Core\HOOK_PREFIX . 'admin_bar_parent_args', $base_args );

	// Add a parent item.
	$wp_admin_bar->add_node( $node_args );

	// Return the ID used to set the secondary.
	return $node_args['id'];
}

/**
 * Set the query URL base.
 *
 * @return string
 */
function set_query_base() {

	// Create a nonce.
	$set_nonce  = wp_create_nonce( Core\NONCE_PREFIX . 'generate' );

	// Set my base URL.
	return add_query_arg( array( Core\QUERY_BASE . 'generate' => 1, Core\QUERY_BASE . 'nonce' => $set_nonce ), admin_url( '/' ) );
}
