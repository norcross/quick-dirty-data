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
use QuickDirtyData\APICalls as APICalls;
use QuickDirtyData\Datasets as Datasets;

/**
 * Get all the available datatypes.
 *
 * @param  string $location  Optional flag for where we are.
 *
 * @return array
 */
function get_datatypes( $location = '' ) {

	// Create our initial array of types.
	$type_array = array(
		'posts',
		'comments',
		'users',
	);

	// Return the array.
	return apply_filters( Core\HOOK_PREFIX . 'datatypes', $type_array, $location );
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
 * Handle storing our error codes and messags.
 *
 * @param  mixed  $args    The args related to each action.
 * @param  string $action  Whether we are adding or checking.
 *
 * @return mixed
 */
function manage_wp_error_data( $args, $action = '' ) {

	// We need an action and args.
	if ( empty( $args ) || empty( $action ) || ! in_array( sanitize_text_field( $action ), array( 'add', 'check' ) ) ) {
		return false;
	}

	// Get our current array of data.
	$error_data = get_option( Core\HOOK_PREFIX . 'error_data', array() );

	// Handle adding one to the array.
	if ( 'add' === sanitize_text_field( $action ) ) {

		// Make sure the args is an array.
		if ( ! is_array( $args ) ) {
			return false;
		}

		// Merge our args.
		$merge_data = wp_parse_args( $args, $error_data );

		// Make sure we aren't storing duplicates.
		$store_data = array_unique( $merge_data );

		// Update the data.
		update_option( Core\HOOK_PREFIX . 'error_data', $store_data );

		// And just be done.
		return;
	}

	// Handle checking one from the array.
	if ( 'check' === sanitize_text_field( $action ) ) {

		// Make sure the args is not an array.
		if ( is_array( $args ) ) {
			return false;
		}

		// Set my default return text.
		$default_return = __( 'The required random user data could not be found.', 'quick-dirty-data' );

		// Check for the key one way or the other.
		return ! empty( $error_data ) && array_key_exists( sanitize_text_field( $args ), $error_data ) ? $error_data[ $args ] : $default_return;
	}
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
 * @param  array $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function get_fake_title( $source = 'datamuse', $custom_args = array() ) {

	// Allow a hardwired bypass for other content generators.
	$maybe_pass = apply_filters( Core\HOOK_PREFIX . 'title_generate_bypass', '', $custom_args );

	// Return the potentially bypassed content.
	if ( ! empty( $maybe_pass ) ) {
		return $maybe_pass;
	}

	// Now switch between my source types.
	switch ( $source ) {

		case 'datamuse':

			$word_array = APICalls\fetch_datamuse_content( $custom_args );
			break;

		case 'local':

			$word_array = Datasets\fetch_local_file_data( 'title' );
			break;

		// End all the case checks.
	}

	// Bail without an array of words.
	if ( empty( $word_array ) ) {
		return false;
	}

	// Set our word count.
	$word_count = apply_filters( Core\HOOK_PREFIX . 'title_word_count', 3, $custom_args );

	// If we asked for less than 2 (which should only be 1 but will check zero) return first.
	if ( absint( $word_count ) < 2 ) {

		// Caps my text.
		$text_caps  = ucfirst( $word_array[0] );

		// Return the words.
		return wp_strip_all_tags( $text_caps );
	}

	// Now cut up the array.
	$text_slice = array_slice( $word_array, 0, absint( $word_count ) );

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
 * @param  string $source       Where to source the image from.
 * @param  array  $custom_args  Any custom args to pass.
 *
 * @return string
 */
function get_fake_content( $source = 'hipster', $custom_args = array() ) {

	// Allow a hardwired bypass for other content generators.
	$maybe_pass = apply_filters( Core\HOOK_PREFIX . 'content_generate_bypass', '', $source, $custom_args );

	// Return the potentially bypassed content.
	if ( ! empty( $maybe_pass ) ) {
		return $maybe_pass;
	}

	// Set my word group.
	$word_group = '';

	// Now switch between my image sources.
	switch ( $source ) {

		case 'hipster':

			$word_group = APICalls\fetch_hipster_ipsum_content( $custom_args );
			break;

		case 'bacon':

			$word_group = APICalls\fetch_bacon_ipsum_content( $custom_args );
			break;

		case 'local':

			$word_group = Datasets\fetch_local_file_data( 'content' );
			break;

		// End all the case checks.
	}

	// Bail without an array of words.
	if ( empty( $word_group ) ) {
		return false;
	}

	// Return the words.
	return wp_strip_all_tags( $word_group );
}

/**
 * Get a random image.
 *
 * @param  string $source       Where to source the image from.
 * @param  array  $custom_args  Any custom args we wanna pass.
 *
 * @return string
 */
function get_fake_image( $source = 'dogapi', $custom_args = array() ) {

	// Allow a hardwired bypass for other content generators.
	$maybe_pass = apply_filters( Core\HOOK_PREFIX . 'image_generate_bypass', '', $source, $custom_args );

	// Return the potentially bypassed content.
	if ( ! empty( $maybe_pass ) ) {
		return $maybe_pass;
	}

	// Now switch between my image sources.
	switch ( $source ) {

		case 'dogapi':

			return APICalls\fetch_dog_api_image( $custom_args );
			break;

		case 'flickr':

			return APICalls\fetch_lorem_flickr_image( $custom_args );
			break;

		default :
			return false;

		// End all the case checks.
	}
}

/**
 * Get a single term randomly.
 *
 * @param  string $taxonomy  The taxonomy we want a term from.
 * @param  string $field     Optional single field, or object return.
 *
 * @return mixed
 */
function get_random_term( $taxonomy = '', $field = 'term_id' ) {

	// Bail without being passed a taxonomy.
	if ( empty( $taxonomy ) ) {
		return false;
	}

	// Attempt to get all the terms.
	$terms  = Datasets\fetch_site_terms( $taxonomy );

	// If no terms, return the appropriate default.
	if ( empty( $terms ) ) {

		// Now switch between my taxonomy types.
		switch ( $taxonomy ) {

			case 'category':

				return get_option( 'default_category', 0 );
				break;

			case 'product_cat':

				return get_option( 'default_product_cat', 0 );
				break;

			default :
				return 0;

			// End all the case checks.
		}

		// Nothing left for the empty terms.
	}

	// Shuffle the term array.
	shuffle( $terms );

	// If we requested the entire object (or blanked it out), return that.
	if ( empty( $field ) || 'object' === sanitize_text_field( $field ) ) {
		return $terms[0];
	}

	// Return the field from the first item.
	return $terms[0][ $field ];
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
	$first_name = Datasets\fetch_local_file_data( 'first-name' );
	$last_name  = Datasets\fetch_local_file_data( 'last-name' );
	$street_key = Datasets\fetch_local_file_data( 'street-name' );

	// Get the city / state / zip.
	$ctstzp_key = Datasets\fetch_local_file_data( 'city-state-zip' );
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

 		// Handle phone number.
		case 'phone' :

			// Return the number.
			return '(555) 555-' . rand( 1111, 9999 );

 			// And break.
 			break;

 		// Handle registered date.
		case 'registered' :

			// Return the number.
			return current_time( 'timestamp', 0 ) - rand( DAY_IN_SECONDS, WEEK_IN_SECONDS );

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
 				'phone'         => '(555) 555-' . rand( 1111, 9999 ),
				'registered'    => current_time( 'timestamp', 0 ) - rand( DAY_IN_SECONDS, WEEK_IN_SECONDS ),
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

/**
 * Set the meta keys indicating this was made by the data generator.
 *
 * @param integer $insert_id  The ID of what we just made.
 * @param string  $meta_type  Which meta type it is.
 */
function set_generated_meta( $insert_id = 0, $meta_type = '' ) {

	// Handle the meta type switch.
	switch ( $meta_type ) {

		// Handle post meta.
		case 'post' :
		case 'posts' :
		case 'attachment' :
		case 'attachments' :
		case 'product' :
		case 'products' :

			// Update the meta with our keys.
			update_post_meta( $insert_id, Core\META_PREFIX . 'sourced', 1 );
			update_post_meta( $insert_id, Core\META_PREFIX . 'created', time() );

 			// And break.
 			break;

 		// Handle comment and review meta.
		case 'comment' :
		case 'comments' :
		case 'review' :
		case 'reviews' :

			// Update the meta with our keys.
			update_comment_meta( $insert_id, Core\META_PREFIX . 'sourced', 1 );
			update_comment_meta( $insert_id, Core\META_PREFIX . 'created', time() );

 			// And break.
 			break;

 		// Handle user and customer meta.
		case 'user' :
		case 'users' :
		case 'customer' :
		case 'customers' :

			// Update the meta with our keys.
			update_user_meta( $insert_id, Core\META_PREFIX . 'sourced', 1 );
			update_user_meta( $insert_id, Core\META_PREFIX . 'created', time() );

 			// And break.
 			break;

		// End all case breaks.
	}

	// No other updates needed, so do the action.
	do_action( Core\HOOK_PREFIX . 'after_sourced_meta_generated', $insert_id, $meta_type );
}

/**
 * Set the appropriate terms for a WooCommerce product.
 *
 * @param integer $product_id  The product ID we just created.
 */
function set_woo_product_terms( $product_id = 0 ) {

	// Get my product category ID.
	$product_cat_id = get_random_term( 'product_cat' );

	// Now set the object terms.
	wp_set_object_terms( $product_id, absint( $product_cat_id ), 'product_cat' );
	wp_set_object_terms( $product_id, 'simple', 'product_type' );
}

/**
 * Set the appropriate meta for a WooCommerce product.
 *
 * @param integer $product_id  The product ID we just created.
 */
function set_woo_product_meta( $product_id = 0 ) {

	// Bail without our pieces.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Get my SKU and price.
	$product_sku    = get_post_field( 'post_name', $product_id, 'raw' );
	$product_price  = rand( 4, 199 ) . '.' . rand( 11, 99 );

	// Set the default args.
	$default_args   = array(
		'total_sales'            => '0',
		'_visibility'            => 'visible',
		'_stock_status'          => 'instock',
		'_tax_status'            => 'taxable',
		'_tax_class'             => '',
		'_downloadable'          => 'no',
		'_download_limit'        => '-1',
		'_download_expiry'       => '-1',
		'_virtual'               => 'no',
		'_purchase_note'         => '',
		'_featured'              => 'no',
		'_weight'                => '',
		'_length'                => '',
		'_width'                 => '',
		'_height'                => '',
		'_sku'                   => sanitize_text_field( $product_sku ),
		'_product_attributes'    => array(),
		'_price'                 => floatval( $product_price ),
		'_regular_price'         => floatval( $product_price ),
		'_sale_price'            => '',
		'_sale_price_dates_from' => '',
		'_sale_price_dates_to'   => '',
		'_sold_individually'     => '',
		'_manage_stock'          => 'no',
		'_backorders'            => 'no',
		'_stock'                 => '',
	);

	// Filter the available meta args we pass.
	$filtered_args  = apply_filters( Core\HOOK_PREFIX . 'generate_product_meta_args', array(), $product_id );

	// Set my product args.
	$product_args   = wp_parse_args( $filtered_args, $default_args );

	// Update our keys.
	foreach ( $product_args as $meta_key => $meta_value ) {
		update_post_meta( $product_id, $meta_key, $meta_value );
	}

	// And that's it.
	do_action( Core\HOOK_PREFIX . 'after_product_meta_generated', $product_id, $product_args );
}

/**
 * Set the appropriate usermeta for a WooCommerce customer.
 *
 * @param integer $user_id    The user ID we just created.
 * @param array   $user_args  The various args used to set the meta.
 */
function set_woo_customer_meta( $user_id = 0, $user_args = array() ) {

	// Bail without our pieces.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Set the default args.
	$default_args   = array(
		'billing_address_1'   => $user_args['street-name'],
		'billing_city'        => $user_args['city'],
		'billing_country'     => 'US',
		'billing_email'       => $user_args['email-address'],
		'billing_first_name'  => $user_args['first-name'],
		'billing_last_name'   => $user_args['last-name'],
		'billing_phone'       => $user_args['phone'],
		'billing_postcode'    => $user_args['zipcode'],
		'billing_state'       => $user_args['state'],
		'shipping_address_1'  => $user_args['street-name'],
		'shipping_city'       => $user_args['city'],
		'shipping_country'    => 'US',
		'shipping_first_name' => $user_args['first-name'],
		'shipping_last_name'  => $user_args['last-name'],
		'shipping_postcode'   => $user_args['zipcode'],
		'shipping_state'      => $user_args['state'],
	);

	// Filter the available meta args we pass.
	$filtered_args  = apply_filters( Core\HOOK_PREFIX . 'generate_customer_meta_args', array(), $user_id );

	// Set my product args.
	$customer_args  = wp_parse_args( $filtered_args, $default_args );

	// Update our keys.
	foreach ( $customer_args as $meta_key => $meta_value ) {
		update_user_meta( $user_id, $meta_key, $meta_value );
	}

	// And that's it.
	do_action( Core\HOOK_PREFIX . 'after_customer_meta_generated', $user_id, $user_args );
}
