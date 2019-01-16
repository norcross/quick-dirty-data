<?php
/**
 * Any external API calls.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\APICalls;

// Set our alias items.
use QuickDirtyData as Core;
use QuickDirtyData\Helpers as Helpers;

/**
 * Get a set of words from Datamuse.
 *
 * @param  array $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function fetch_datamuse_content( $custom_args = array() ) {

	// First set the search phrase. Note, this is not translated on purpose.
	$set_phrase = apply_filters( Core\HOOK_PREFIX . 'datamuse_search_phrase', 'everything was beautiful and nothing hurt', $custom_args );

	// Handle the request setup.
	$query_args = wp_parse_args( $custom_args, array( 'max' => 250, 'ml' => urlencode( $set_phrase ) ) );

	// Create my domain.
	$domain_url = add_query_arg( $query_args, 'https://api.datamuse.com/words' );

	// Get our HTTP call args.
	$setup_args = get_api_setup_args();

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

	// And return them.
	return array_map( 'sanitize_text_field', $json_words );
}

/**
 * Get a set of words from the Bacon Ipsum API.
 *
 * @param  array $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function fetch_bacon_ipsum_content( $custom_args = array() ) {

	// Handle the request setup.
	$build_args = wp_parse_args( $custom_args, array( 'type' => 'meat-and-filler', 'format' => 'text', 'paras' => rand( 2, 5 ) ) );

	// Filter the available args.
	$body_setup = apply_filters( Core\HOOK_PREFIX . 'bacon_ipsum_args', $build_args );

	// Get our HTTP call args.
	$setup_args = get_api_setup_args( array( 'body' => $body_setup ) );

	// Make my data request.
	$remote_get = wp_remote_get( 'https://baconipsum.com/api/', $setup_args );

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

	// And return them.
	return sanitize_text_field( $body_text );
}

/**
 * Get a set of words from the Hipster Ipsum API.
 *
 * @param  array $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function fetch_hipster_ipsum_content( $custom_args = array() ) {

	// Handle the request setup.
	$build_args = wp_parse_args( $custom_args, array( 'type' => 'hipster-latin', 'format' => 'text', 'paras' => rand( 2, 5 ) ) );

	// Filter the available args.
	$body_setup = apply_filters( Core\HOOK_PREFIX . 'hipster_ipsum_args', $build_args );

	// Get our HTTP call args.
	$setup_args = get_api_setup_args( array( 'body' => $body_setup ) );

	// Make my data request.
	$remote_get = wp_remote_get( 'https://hipsum.co/api/', $setup_args );

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

	// And return them.
	return sanitize_text_field( $body_text );
}

/**
 * Fetch a random image using the Dog CEO API.
 *
 * @param  array $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function fetch_dog_api_image( $custom_args = array() ) {

	// Get our HTTP call args.
	$setup_args = get_api_setup_args( $custom_args );

	// Make my data request.
	$remote_get = wp_remote_get( 'https://dog.ceo/api/breeds/image/random', $setup_args );

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
	if ( empty( $json_array ) || empty( $json_array['message'] ) || empty( $json_array['status'] ) || 'success' !== sanitize_text_field( $json_array['status'] ) ) {
		return false;
	}

	// Set my image URL.
	$image_url  = esc_url( $json_array['message'] );

	// Set the individual filename.
	$image_name = basename( $image_url );

	// Handle the image name.
	$image_base = str_replace( array( 'http:', 'https:', 'images.dog.ceo', 'breeds', $image_name ), '', $image_url );
	$post_title = str_replace( array( '/', '-' ), array( '', ' ' ), $image_base );

	// Return the data.
	return array( 'url' => $image_url, 'name' => $image_name, 'title' => ucwords( $post_title ) );
}

/**
 * Fetch a random image using the LoremFlickr API.
 *
 * @param  array $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function fetch_lorem_flickr_image( $custom_args = array() ) {

	// Get our HTTP call args.
	$setup_args = get_api_setup_args( $custom_args );

	// Set the tag string to use in the lookup.
	$tag_string = get_flickr_search_tag();

	// Make my data request.
	$remote_get = wp_remote_get( 'https://loremflickr.com/json/600/600/' . esc_attr( $tag_string ), $setup_args );

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
	if ( empty( $json_array ) || empty( $json_array['file'] ) ) {
		return false;
	}

	// Set my image URL.
	$image_url  = esc_url( $json_array['file'] );

	// Set the individual filename.
	$image_name = basename( $image_url );

	// Handle the image name.
	$image_base = str_replace( array( 'http:', 'https:', 'loremflickr.com', 'cache', 'resized', $image_name ), '', $image_url );
	$post_title = str_replace( array( '/', '_' ), array( '', '-' ), $image_base );

	// Return the data.
	return array( 'url' => $image_url, 'name' => $image_name, 'title' => ucwords( $post_title ) );
}

/**
 * Create the HTTP call args.
 *
 * @param  array $custom_args  Any custom args we wanna pass.
 *
 * @return array
 */
function get_api_setup_args( $custom_args = array() ) {

	// Set up the basic HTTP call args.
	$basic_args = array(
		'method'      => 'GET',
		'sslverify'   => false,
		'httpversion' => '1.1',
		'timeout'     => 25,
	);

	// Return the merged set.
	return wp_parse_args( $custom_args, $basic_args );
}

/**
 * Get a single tag from our array of available.
 *
 * @return string
 */
function get_flickr_search_tag() {

	// First set the search tags. Note, these is not translated on purpose.
	$search_set = apply_filters( Core\HOOK_PREFIX . 'lorem_flickr_search_tags', array( 'kids', 'technology', 'sports', 'family', 'concert', 'vacation' ) );

	// If someone cleared it, send back an empty string.
	if ( empty( $search_set ) ) {
		return '';
	}

	// Shuffle the tags to get a random order.
	shuffle( $search_set );

	// Now grab the first one and return it.
	return trim( $search_set[0] );
}
