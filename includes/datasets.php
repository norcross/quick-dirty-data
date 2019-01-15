<?php
/**
 * Datasets as needed.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\Datasets;

// Set our alias items.
use QuickDirtyData as Core;
use QuickDirtyData\Helpers as Helpers;

/**
 * Get some random posts.
 *
 * @param  string $post_type    Which post type it is.
 * @param  array  $custom_args  Any additional args.
 *
 * @return array
 */
function fetch_site_content( $post_type = 'post', $custom_args = array() ) {

	// Set up my args.
	$basic_args = array(
		'post_type'      => esc_attr( $post_type ),
		'post_status'    => 'publish',
		'orderby'        => 'rand',
		'posts_per_page' => 10,
	);

	// Parse my args.
	$build_args = wp_parse_args( $custom_args, $basic_args );

	// Filter my args.
	$setup_args = apply_filters( Core\HOOK_PREFIX . 'site_content_fetch_args', $build_args, $post_type );

	// Bail with empty args.
	if ( empty( $setup_args ) ) {
		return false;
	}

	// Now get my items.
	$get_items  = get_posts( $setup_args );

	// Handle managing the WP_Error data.
	if ( is_wp_error( $get_items ) ) {

		// Set each item as a variable.
		$error_code = $get_items->get_error_code();
		$error_text = $get_items->get_error_message();

		// Store the data.
		Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

		// And bail.
		return false;
	}

	// Return them or false.
	return ! empty( $get_items ) ? $get_items : false;
}

/**
 * Get all the site users.
 *
 * @param  string $user_role    Which user role we wanna get.
 * @param  array  $custom_args  Any additional args.
 *
 * @return array
 */
function fetch_site_users( $user_role = 'customer', $custom_args = array() ) {

	// Set up my args.
	$basic_args = array(
		'role' => esc_attr( $user_role ),
	);

	// Parse my args.
	$build_args = wp_parse_args( $custom_args, $basic_args );

	// Filter my args.
	$setup_args = apply_filters( Core\HOOK_PREFIX . 'site_user_fetch_args', $build_args, $user_role );

	// Bail with empty args.
	if ( empty( $setup_args ) ) {
		return false;
	}

	// Now get my items.
	$get_users  = get_users( $setup_args );

	// Handle managing the WP_Error data.
	if ( is_wp_error( $get_users ) ) {

		// Set each item as a variable.
		$error_code = $get_users->get_error_code();
		$error_text = $get_users->get_error_message();

		// Store the data.
		Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

		// And bail.
		return false;
	}

	// Return them or false.
	return ! empty( $get_users ) ? $get_users : false;
}

/**
 * Get all the terms for a taxonomy.
 *
 * @param  string $taxonomy     Which taxonomy we wanna get.
 * @param  array  $custom_args  Any additional args.
 *
 * @return array
 */
function fetch_site_terms( $taxonomy = 'category', $custom_args = array() ) {

	// Set up my args.
	$basic_args = array(
		'taxonomy'   => esc_attr( $taxonomy ),
		'hide_empty' => false,
	);

	// Parse my args.
	$build_args = wp_parse_args( $custom_args, $basic_args );

	// Filter my args.
	$setup_args = apply_filters( Core\HOOK_PREFIX . 'site_term_fetch_args', $build_args, $taxonomy );

	// Bail with empty args.
	if ( empty( $setup_args ) ) {
		return false;
	}

	// Now get my items.
	$get_terms  = get_terms( $setup_args );

	// Handle managing the WP_Error data.
	if ( is_wp_error( $get_terms ) ) {

		// Set each item as a variable.
		$error_code = $get_terms->get_error_code();
		$error_text = $get_terms->get_error_message();

		// Store the data.
		Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

		// And bail.
		return false;
	}

	// Return them or false.
	return ! empty( $get_terms ) ? $get_terms : false;
}
