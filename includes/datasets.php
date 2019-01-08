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
function fetch_random_content( $post_type = 'post', $custom_args = array() ) {

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
	$setup_args = apply_filters( Core\HOOK_PREFIX . 'random_content_args', $build_args, $post_type );

	// Bail with empty args.
	if ( empty( $setup_args ) ) {
		return false;
	}

	// Now get my items.
	$get_items  = get_posts( $setup_args );

	// Return them or false.
	return ! empty( $get_items ) && ! is_wp_error( $get_items ) ? $get_items : false;
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
	$setup_args = apply_filters( Core\HOOK_PREFIX . 'random_user_args', $user_role, $build_args );

	// Bail with empty args.
	if ( empty( $setup_args ) ) {
		return false;
	}

	// Now get my items.
	$get_users  = get_users( $setup_args );

	// Return them or false.
	return ! empty( $get_users ) && ! is_wp_error( $get_users ) ? $get_users : false;
}
