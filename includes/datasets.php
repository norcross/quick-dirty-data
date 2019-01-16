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

/**
 * Read one of our files and return a random entry.
 *
 * @param  string $type  Which file type we wanna read.
 *
 * @return string
 */
function fetch_local_file_data( $type = '' ) {

	// Bail without being passed a type.
	if ( empty( $type ) ) {
		return false;
	}

	// Set my source file.
	$file_src_setup = Core\DATAFILE_ROOT . esc_attr( $type ) . '.txt';

	// Filter our available source file.
	$file_src_setup = apply_filters( Core\HOOK_PREFIX . 'random_srcfile', $file_src_setup, $type );

	// Bail without a source.
	if ( empty( $file_src_setup ) || ! is_file( $file_src_setup ) ) {
		return false;
	}

	// Handle the file read.
	$file_readarray = file( $file_src_setup, FILE_IGNORE_NEW_LINES );

	// Trim all my stuff in the array.
	$trimmed_array  = array_map( 'trim', $file_readarray );

	// Shuffle the array.
	shuffle( $trimmed_array );

	// Now switch between my data types.
	switch ( sanitize_text_field( $type ) ) {

		// Handle titles.
		case 'title' :

			// Return it cleaned up.
			return array_map( 'wp_strip_all_tags', $trimmed_array );

			// And be done.
			break;

		// Handle content.
		case 'content' :

			// Merge my text in to actual paragraphs.
			$merge_text = implode( PHP_EOL, $trimmed_array );

			// Return it trimmed and de-tagged.
			return wp_strip_all_tags( $merge_text, false );

			// And be done.
			break;

		// Handle excerpt.
		case 'excerpt' :

			// Return it cleaned up.
			return wp_strip_all_tags( $trimmed_array[0], true );

			// And be done.
			break;

		// Handle the rest, which is just the first random.
		default :

			// Return it cleaned up.
			return wp_strip_all_tags( $trimmed_array[0], true );

		// End all the case checks.
	}
}
