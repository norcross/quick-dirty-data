<?php
/**
 * The items related to creating WooCommerce data.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\WooCommerce;

// Set our alias items.
use QuickDirtyData as Core;
use QuickDirtyData\Helpers as Helpers;

/**
 * Start our engines.
 */
add_filter( 'qckdrty_datatypes', __NAMESPACE__ . '\include_woo_datatypes', 10, 2 );

/**
 * Add our WooCommerce datatypes if we have Woo.
 *
 * @param  array  $datatypes  The existing datatypes.
 * @param  string $location   Optional flag for where we are.
 *
 * @return array
 */
function include_woo_datatypes( $datatypes, $location ) {

	// First do the check for WooCommerce items.
	$maybe_woo  = Helpers\get_plugin_status( 'woocommerce/woocommerce.php' );

	// If we do not have Woo, just return the original datatypes.
	if ( ! $maybe_woo ) {
		return $datatypes;
	}

	// Set our Woo args.
	$woo_types  = apply_filters( Core\HOOK_PREFIX . 'woo_datatypes', array( 'products', 'orders', 'reviews', 'customers' ), $location );

	// Merge our args and return.
	return wp_parse_args( (array) $woo_types, $datatypes );
}

