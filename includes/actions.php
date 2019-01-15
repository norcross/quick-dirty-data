<?php
/**
 * The actual data generation actions.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\Actions;

// Set our alias items.
use QuickDirtyData as Core;
use QuickDirtyData\Helpers as Helpers;
use QuickDirtyData\Generators as Generators;

/**
 * Start our engines.
 */
add_action( 'admin_init', __NAMESPACE__ . '\maybe_generate_data' );

/**
 * Our data generator check.
 *
 * @return void
 */
function maybe_generate_data() {

	// We only run this on admin.
	if ( ! is_admin() ) {
		return;
	}

	// Confirm we passed the appropriate flag to begin.
	if ( empty( $_GET[ Core\QUERY_BASE . 'generate' ] ) ) {
		return;
	}

	// Check to see if our nonce was provided.
	if ( empty( $_GET[ Core\QUERY_BASE . 'nonce' ] ) || ! wp_verify_nonce( $_GET[ Core\QUERY_BASE . 'nonce' ], Core\NONCE_PREFIX . 'generate' ) ) {
		wp_die( __( 'The nonce check failed. Why?', 'quick-dirty-data' ), __( 'Data Generation Error', 'quick-dirty-data' ) );
	}

	// Check for some type to compare.
	if ( empty( $_GET[ Core\QUERY_BASE . 'type' ] ) ) {
		return;
	}

	// Set our data type and count.
	$data_type  = sanitize_text_field( $_GET[ Core\QUERY_BASE . 'type' ] );

	// Now switch between my data types.
	switch ( $data_type ) {

		// Create regular posts.
		case 'posts':

			// Get the amount we wanna generate.
			$count  = apply_filters( Core\HOOK_PREFIX . 'generate_post_count', 10 );

			// And run the generator.
			Generators\generate_posts( $count );
			break;

		// Create comments.
		case 'comments':

			// Get the amount we wanna generate.
			$count  = apply_filters( Core\HOOK_PREFIX . 'generate_comment_count', 5 );

			// And run the generator.
			Generators\generate_comments( $count );
			break;

		// Create users.
		case 'users':

			// Get the amount we wanna generate.
			$count  = apply_filters( Core\HOOK_PREFIX . 'generate_user_count', 20 );

			// And run the generator.
			Generators\generate_users( $count );
			break;

		// Create customers.
		case 'customers':

			// Get the amount we wanna generate.
			$count  = apply_filters( Core\HOOK_PREFIX . 'generate_customer_count', 20 );

			// And run the generator.
			Generators\generate_customers( $count );
			break;

		// Create products.
		case 'products':

			// Get the amount we wanna generate.
			$count  = apply_filters( Core\HOOK_PREFIX . 'generate_product_count', 10 );

			// And run the generator.
			Generators\generate_products( $count );
			break;

		// End all the case checks.
	}

	// Handle the action that allows for other generator types.
	do_action( Core\HOOK_PREFIX . 'generate_data', $data_type );
}

