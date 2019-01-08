<?php
/**
 * Our helper functions to use across the plugin.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\Helpers;

// Set our alias items.
use QuickDirtyData as Core;

/**
 * Get all the available datatypes.
 *
 * @param  string $location  Optional flag for where we are.
 *
 * @return array
 */
function get_datatypes( $location = '' ) {

	// Create our initial array of types.
	$base_array = array(
		'posts',
		'comments',
		'users',
	);

	// Return the array.
	return apply_filters( Core\HOOK_PREFIX . 'datatypes', $base_array, $location );
}

/**
 * Check the array of installed plugins to see if it exists.
 *
 * @param  string $install_string  The plugin we wanna check.
 *
 * @return boolean
 */
function get_plugin_status( $install_string = '' ) {

	// Get the array of active plugins.
	$currently_active   = get_option( 'active_plugins' );

	// check the array for being active, with fallback
	return in_array( $install_string, $currently_active ) ? true : false;
}

/**
 * Our URL redirect helper.
 *
 * @param  array $setup_args  The args we need to redirect.
 *
 * @return void
 */
function get_action_redirect( $setup_args = array() ) {

	// We need setup args.
	if ( empty( $setup_args ) ) {
		return;
	}

	// Set the args with our result flag.
	$redirect_args  = wp_parse_args( $setup_args, array( Core\QUERY_BASE . 'result' => 1 ) );

	// Make my redirect link.
	$redirect_link  = add_query_arg( $redirect_args, admin_url( '/' ) );

	// Redirect and exit.
	wp_safe_redirect( $redirect_link );
	exit();
}

/**
 * Create a random date.
 *
 * @param  string $date_format  What format to return the date in.
 *
 * @return mixed
 */
function get_random_date( $date_format = 'timestamp' ) {

	// Set our start and end time.
	$range_oldest   = apply_filters( Core\HOOK_PREFIX . 'date_range_oldest', strtotime( '-72 weeks' ) );
	$range_newest   = apply_filters( Core\HOOK_PREFIX . 'date_range_newest', strtotime( '-1 week' ) );

	// Now create the random stamp.
	$random_stamp   = rand( $range_oldest, $range_newest );

	// Return based on what we asked for.
	return 'timestamp' === $date_format ? $random_stamp : date( $date_format, $random_stamp );
}

/**
 * Create a random title using the Datamuse API.
 *
 * @param  array   $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function get_fake_title( $custom_args = array() ) {

	// Allow a hardwired bypass for other content generators.
	$maybe_pass = apply_filters( Core\HOOK_PREFIX . 'title_generate_bypass', '', $custom_args );

	// Return the potentially bypassed content.
	if ( ! empty( $maybe_pass ) ) {
		return $maybe_pass;
	}

	// First set the search phrase. Note, this is not translated on purpose.
	$set_phrase = apply_filters( Core\HOOK_PREFIX . 'datamuse_search_phrase', 'everything was beautiful and nothing hurt', $custom_args );

	// Handle the request setup.
	$query_args = wp_parse_args( $custom_args, array( 'max' => 250, 'ml' => urlencode( $set_phrase ) ) );

	// Create my domain.
	$domain_url = add_query_arg( $query_args, 'https://api.datamuse.com/words' );

	// Now set up the call.
	$setup_args = array(
		'method'      => 'GET',
		'sslverify'   => false,
		'httpversion' => '1.1',
		'timeout'     => 25,
	);

	// Make my data request.
	$remote_get = wp_remote_get( $domain_url, $setup_args );

	// Bail on a bad request.
	if ( empty( $remote_get ) || is_wp_error( $remote_get ) ) {
		return false;
	}

	// Pull out the text.
	$body_text  = wp_remote_retrieve_body( $remote_get );

	// Bail on bad data.
	if ( empty( $body_text ) || is_wp_error( $body_text ) ) {
		return false;
	}

	// Pull out our JSON data as an array.
	$json_array = json_decode( $body_text, true );

	// Bail without JSON.
	if ( empty( $json_array ) ) {
		return false;
	}

	// Pull out the words.
	$json_group = wp_list_pluck( $json_array, 'word' );

	// Bail without group JSON.
	if ( empty( $json_group ) ) {
		return false;
	}

	// Trim and upper case everything.
	$json_words = array_map( 'trim', $json_group );

	// Bail without words JSON.
	if ( empty( $json_words ) ) {
		return false;
	}

	// Randomize them.
	shuffle( $json_words );

	// Set our word count.
	$word_count = apply_filters( Core\HOOK_PREFIX . 'title_word_count', 3, $custom_args );

	// If we asked for less than 2 (which should only be 1 but will check zero) return first.
	if ( absint( $word_count ) < 2 ) {

		// Caps my text.
		$text_caps  = ucfirst( $json_words[0] );

		// Return the words.
		return wp_strip_all_tags( $text_caps );
	}

	// Now cut up the array.
	$text_slice = array_slice( $json_words, 0, absint( $word_count ) );

	// Now set up the string.
	$text_setup = implode( ' ', $text_slice );

	// Caps my text.
	$text_caps  = ucwords( $text_setup );

	// Return the words.
	return wp_strip_all_tags( $text_caps );
}

/**
 * Generate fake content via the Bacon Ipsum API.
 *
 * @param  array  $custom_args  Any custom args to pass.
 *
 * @return string
 */
function get_fake_content( $custom_args = array() ) {

	// Allow a hardwired bypass for other content generators.
	$maybe_pass = apply_filters( Core\HOOK_PREFIX . 'content_generate_bypass', '', $custom_args );

	// Return the potentially bypassed content.
	if ( ! empty( $maybe_pass ) ) {
		return $maybe_pass;
	}

	// Handle the request setup.
	$bacon_args = wp_parse_args( $custom_args, array( 'type' => 'meat-and-filler', 'format' => 'text', 'paras' => rand( 2, 5 ) ) );

	// Filter the available args.
	$bacon_args = apply_filters( Core\HOOK_PREFIX . 'content_bacon_args', $bacon_args, $custom_args );

	// Now set my args.
	$setup_args = array(
		'method'      => 'GET',
		'sslverify'   => false,
		'httpversion' => '1.1',
		'timeout'     => 25,
		'body'        => $bacon_args
	);

	// Make my data request.
	$remote_get = wp_remote_get( 'https://baconipsum.com/api/', $setup_args );

	// Bail on a bad request.
	if ( empty( $remote_get ) || is_wp_error( $remote_get ) ) {
		return false;
	}

	// Pull out the text.
	$body_text  = wp_remote_retrieve_body( $remote_get );

	// Return one or the other.
	return ! empty( $body_text ) && ! is_wp_error( $body_text ) ? $body_text : false;
}

/**
 * Read one of our files and return a random entry.
 *
 * @param  string $type  Which file type we wanna read.
 *
 * @return string
 */
function get_random_from_file( $type = '' ) {

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
	$file_readarray = file( $file_src_setup );

	// Fetch a random one.
	$fetch_single   = array_rand( array_flip( $file_readarray ), 1 );

	// Return it trimmed and cleaned.
	return trim( wp_strip_all_tags( $fetch_single, true ) );
}

/**
 * Get a piece of user data at random.
 *
 * @param  string $field  Which field to return.
 *
 * @return string
 */
function get_fake_userdata( $field = '' ) {

	// Bail without a field.
	if ( empty( $field ) ) {
		return false;
	}

	// First create the entire dataset.
	$first_name = get_random_from_file( 'first-name' );
	$last_name  = get_random_from_file( 'last-name' );
	$street_key = get_random_from_file( 'street-name' );

	// Get the city / state / zip.
	$ctstzp_key = get_random_from_file( 'city-state-zip' );
	$ctstzp_arr = explode( ';', $ctstzp_key );
	$ctstzp_bit = array_map( 'trim', $ctstzp_arr );

	// Make my email address key.
	$email_key  = strtolower( $first_name . $last_name );

	// Handle the field switch.
	switch ( $field ) {

		// Handle first name.
		case 'first-name' :

			// Return the name.
			return $first_name;

 			// And break.
 			break;

 		// Handle last name.
		case 'last-name' :

			// Return the name.
			return $first_name;

 			// And break.
 			break;

 		// Handle display name.
		case 'display-name' :

			// Return the name.
			return $first_name . ' ' . $last_name;

 			// And break.
 			break;

 		// Handle user login.
		case 'user-login' :

			// Return the name.
			return sanitize_key( $email_key );

 			// And break.
 			break;

		// Handle email.
		case 'email' :
		case 'email-address' :

			// Use a combination of the email key and the example.com
			return sanitize_key( $email_key ) . rand( 1000, 9999 ) . '@example.com';

 			// And break.
 			break;

 		// Handle street name.
		case 'street-name' :

			// Return the name.
			return rand( 12, 9999 ) . ' ' . $street_key;

 			// And break.
 			break;

 		// Handle city.
		case 'city' :

			// Return the name.
			return ucwords( $ctstzp_bit[0] );

 			// And break.
 			break;

 		// Handle state.
		case 'state' :

			// Return the name.
			return strtoupper( $ctstzp_bit[1] );

 			// And break.
 			break;

 		// Handle zip code.
		case 'zip' :
		case 'zipcode' :
		case 'zip-code' :

			// Return the name.
			return absint( $ctstzp_bit[2] );

 			// And break.
 			break;

 		// Handle a full address.
		case 'address' :

			// Return the address array.
 			return array(
				'street-name' => rand( 12, 9999 ) . ' ' . $street_key,
 				'city'        => ucwords( $ctstzp_bit[0] ),
 				'state'       => strtoupper( $ctstzp_bit[1] ),
 				'zipcode'     => absint( $ctstzp_bit[2] ),
 			);

 			// And break.
 			break;

 		// Return the entire dataset.
 		case 'array' :

 			// Return the thing.
 			return array(
				'first-name'    => $first_name,
 				'last-name'     => $last_name,
 				'display-name'  => $first_name . ' ' . $last_name,
 				'user-login'    => strtolower( $first_name . $last_name ),
 				'email-address' => sanitize_key( $email_key ) . rand( 1000, 9999 ) . '@example.com',
				'street-name'   => rand( 12, 9999 ) . ' ' . $street_key,
 				'city'          => ucwords( $ctstzp_bit[0] ),
 				'state'         => strtoupper( $ctstzp_bit[1] ),
 				'zipcode'       => absint( $ctstzp_bit[2] ),
 			);

  			// And break.
 			break;

		// End all case breaks.
	}

	// Nothing left, so return false with a filter.
	return apply_filters( Core\HOOK_PREFIX . 'fake_userdata', false, $field );
}
