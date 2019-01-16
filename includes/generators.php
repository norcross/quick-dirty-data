<?php
/**
 * The data generation engines.
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData\Generators;

// Set our alias items.
use QuickDirtyData as Core;
use QuickDirtyData\Helpers as Helpers;
use QuickDirtyData\Datasets as Datasets;

/**
 * Generate a given number of posts.
 *
 * @param  integer $count      How many posts we want.
 * @param  boolean $add_image  Whether to add a featured image.
 *
 * @return void
 */
function generate_posts( $count = 0, $add_image = true ) {

	// Set the type as a variable.
	$generate_type  = 'posts';

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 11 ? absint( $count ) : 10;

	// Set an overall counter.
	$total_generate = 0;

	// Make the posts.
	for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

		// Handle the action before we do the post.
		do_action( Core\HOOK_PREFIX . 'before_post_generate' );

		// Set the args.
		$setup_args = array(
			'post_type'    => 'post',
			'post_title'   => Helpers\get_fake_title(),
			'post_content' => Helpers\get_fake_content(),
			'post_date'    => Helpers\get_random_date( 'Y-m-d H:i:s' ),
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		);

		// Filter my args.
		$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_post_args', $setup_args );

		// Bail if we wiped out the args.
		if ( empty( $setup_args ) ) {
			continue;
		}

		// Insert the post into the database.
		$insert_id  = wp_insert_post( $setup_args );

		// If no ID came back, return that.
		if ( empty( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_post_id_created', Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// If we hit an actual WP error, do that.
		if ( is_wp_error( $insert_id ) ) {

			// Set each item as a variable.
			$error_code = $insert_id->get_error_code();
			$error_text = $insert_id->get_error_message();

			// Store the data.
			Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

			// Then redirect.
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $error_code, Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// Attempt to add the image if need be.
		if ( false !== $add_image ) {
			$featured   = generate_featured_image( $insert_id );
		}

		// Attempt to get a category ID.
		$maybe_category = Helpers\get_random_term( 'category' );

		// Set the object term if we have one.
		if ( ! empty( $maybe_category ) ) {
			wp_set_object_terms( $insert_id, absint( $maybe_category ), 'category' );
		}

		// Set the appropriate meta.
		Helpers\set_generated_meta( $insert_id, $generate_type );

		// Handle the action for a successful post.
		do_action( Core\HOOK_PREFIX . 'after_post_generate', $insert_id );

		// Increment the overall counter.
		$total_generate++;
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => $generate_type,
		Core\QUERY_BASE . 'count'   => $total_generate,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}

/**
 * Generate a given number of comments.
 *
 * @param  integer $count       How many comments per post we want.
 * @param  boolean $add_thread  Whether to create some threaded comments as well.
 *
 * @return void
 */
function generate_comments( $count = 0, $add_thread = true ) {

	// Set the type as a variable.
	$generate_type  = 'comments';

	// First grab some random posts to apply comments to.
	$content_array  = Datasets\fetch_site_content();

	// Bail without a post array.
	if ( empty( $content_array ) ) {
		Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_available_posts', Core\QUERY_BASE . 'type' => $generate_type ) );
	}

	// Check the option table for allowing threaded comments.
	$allow_threads  = get_option( 'thread_comments', 0 );

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 25 ? absint( $count ) : 25;

	// Set an overall counter.
	$total_generate = 0;

	// Now loop my posts.
	foreach ( $content_array as $post ) {

		// Handle the action before comments.
		do_action( Core\HOOK_PREFIX . 'before_post_comments', $post->ID, $post );

		// Check if comments are open and skip without.
		if ( 'open' !== esc_attr( $post->comment_status ) ) {
			continue;
		}

		// Make a timestamp of our post.
		$post_stamp = strtotime( $post->post_date );

		// Set an empty for thread IDs.
		$parent_ids = array();

		// And now loop the commenters.
		for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

			// Handle the action before we do the comment.
			do_action( Core\HOOK_PREFIX . 'before_comment_generate', $post->ID, $post );

			// Fetch some random data.
			$random_person  = Helpers\get_fake_userdata( 'array' );

			// Make a timestamp in the future.
			$comment_stamp  = $post_stamp + rand( HOUR_IN_SECONDS, WEEK_IN_SECONDS );

			// Get the random text.
			$comment_text   = Helpers\get_fake_content( array( 'sentences' => rand( 2, 5 ), 'paras' => '' ) );

			// Set up the args.
			$setup_args = array(
				'comment_post_ID'      => absint( $post->ID ),
				'comment_author'       => $random_person['display-name'],
				'comment_author_email' => $random_person['email-address'],
				'comment_author_url'   => '',
				'comment_type'         => '',
				'comment_content'      => $comment_text,
				'comment_parent'       => 0,
				'user_id'              => 0,
				'comment_date'         => date( 'Y-m-d H:i:s', $comment_stamp ),
				'comment_approved'     => 1,
			);

			// Filter my args.
			$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_comment_args', $setup_args );

			// Bail if we wiped out the args.
			if ( empty( $setup_args ) ) {
				continue;
			}

			// Run the new comment setup.
			$insert_id  = wp_insert_comment( $setup_args );

			// If no ID came back, return that.
			if ( empty( $insert_id ) ) {
				Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_comment_id_created', Core\QUERY_BASE . 'type' => $generate_type ) );
			}

			// If we hit an actual WP error, do that.
			if ( is_wp_error( $insert_id ) ) {

				// Set each item as a variable.
				$error_code = $insert_id->get_error_code();
				$error_text = $insert_id->get_error_message();

				// Store the data.
				Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

				// Then redirect.
				Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $error_code, Core\QUERY_BASE . 'type' => $generate_type ) );
			}

			// Set the appropriate meta.
			Helpers\set_generated_meta( $insert_id, $generate_type );

			// If we wanna make threads, add this ID.
			if ( false !== $add_thread ) {
				$parent_ids[ $insert_id ] = $comment_stamp;
			}

			// Handle the action for a successful comment.
			do_action( Core\HOOK_PREFIX . 'after_comment_generate', $insert_id, $post->ID, $post );

			// Increment the overall counter.
			$total_generate++;
		}

		// Now thread if we have threads and allow it.
		if ( ! empty( $allow_threads ) && ! empty( $parent_ids ) ) {

			// Now loop the IDs we just set to thread.
			foreach ( $parent_ids as $parent_id => $parent_stamp ) {

				// Handle the action before we do the thread comment.
				do_action( Core\HOOK_PREFIX . 'before_comment_thread_generate', $parent_id, $post->ID, $post );

				// Fetch some random data.
				$random_person  = Helpers\get_fake_userdata( 'array' );

				// Bail the random person empty.
				if ( empty( $random_person ) ) {
					Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_random_user_data', Core\QUERY_BASE . 'type' => $generate_type ) );
				}

				// Make a timestamp in the future.
				$response_stamp = $parent_stamp + rand( HOUR_IN_SECONDS, DAY_IN_SECONDS );

				// Get the random text.
				$response_text  = Helpers\get_fake_content( array( 'sentences' => rand( 2, 5 ), 'paras' => '' ) );

				// Set up the args.
				$response_args  = array(
					'comment_post_ID'      => absint( $post->ID ),
					'comment_author'       => $random_person['display-name'],
					'comment_author_email' => $random_person['email-address'],
					'comment_author_url'   => '',
					'comment_type'         => '',
					'comment_content'      => $response_text,
					'comment_parent'       => absint( $parent_id ),
					'user_id'              => 0,
					'comment_date'         => date( 'Y-m-d H:i:s', $response_stamp ),
					'comment_approved'     => 1,
				);

				// Filter my args.
				$response_args  = apply_filters( Core\HOOK_PREFIX . 'generate_comment_thread_args', $response_args, $parent_id, $post->ID );

				// Bail if we wiped out the args.
				if ( empty( $response_args ) ) {
					continue;
				}

				// Run the new comment setup.
				$response_id    = wp_insert_comment( $response_args );

				// If no ID came back, return that.
				if ( empty( $response_id ) ) {
					Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_comment_thread_id_created', Core\QUERY_BASE . 'type' => $generate_type ) );
				}

				// If we hit an actual WP error, do that.
				if ( is_wp_error( $response_id ) ) {

					// Set each item as a variable.
					$error_code = $response_id->get_error_code();
					$error_text = $response_id->get_error_message();

					// Store the data.
					Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

					// Now do the redirect.
					Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $error_code, Core\QUERY_BASE . 'type' => $generate_type ) );
				}

				// Set the appropriate meta.
				Helpers\set_generated_meta( $response_id, $generate_type );

				// Handle the action for a successful comment.
				do_action( Core\HOOK_PREFIX . 'after_comment_thread_generate', $response_id, $parent_id, $post->ID, $post );

				// Increment the overall counter.
				$total_generate++;
			}

			// Done with the threaded loop.
		}

		// Handle the action after comments.
		do_action( Core\HOOK_PREFIX . 'after_post_comments', $post->ID, $post );
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => $generate_type,
		Core\QUERY_BASE . 'count'   => $total_generate,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}

/**
 * Generate a given number of users.
 *
 * @param  integer $count  How many users we want.
 *
 * @return void
 */
function generate_users( $count = 0 ) {

	// Set the type as a variable.
	$generate_type  = 'users';

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 40 ? absint( $count ) : 20;

	// Get my default role to set users.
	$default_role   = apply_filters( Core\HOOK_PREFIX . 'default_user_role', get_option( 'default_role', 'subscriber' ) );

	// Set an overall counter.
	$total_generate = 0;

	// Make the posts.
	for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

		// Handle the action before we do the post.
		do_action( Core\HOOK_PREFIX . 'before_user_generate' );

		// Fetch some random data.
		$random_person  = Helpers\get_fake_userdata( 'array' );

		// Bail the random person empty.
		if ( empty( $random_person ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_random_user_data', Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// Set the args.
		$setup_args = array(
			'user_pass'       => wp_generate_password( 32, true, true ),
			'user_login'      => $random_person['user-login'],
			'user_nicename'   => $random_person['user-login'],
			'user_email'      => $random_person['email-address'],
			'display_name'    => $random_person['display-name'],
			'nickname'        => $random_person['user-login'],
			'first_name'      => $random_person['first-name'],
			'last_name'       => $random_person['last-name'],
			'user_registered' => date( 'Y-m-d H:i:s', $random_person['registered'] ),
			'role'            => $default_role,
		);

		// Filter my args.
		$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_user_args', $setup_args );

		// Bail if we wiped out the args.
		if ( empty( $setup_args ) ) {
			continue;
		}

		// Insert the user into the database.
		$insert_id  = wp_insert_user( $setup_args );

		// If no ID came back, return that.
		if ( empty( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_user_id_created', Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// If we hit an actual WP error, do that.
		if ( is_wp_error( $insert_id ) ) {

			// Set each item as a variable.
			$error_code = $insert_id->get_error_code();
			$error_text = $insert_id->get_error_message();

			// Store the data.
			Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

			// Then redirect.
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $error_code, Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// Set the appropriate meta.
		Helpers\set_generated_meta( $insert_id, $generate_type );

		// Handle the action for a successful post.
		do_action( Core\HOOK_PREFIX . 'after_user_generate', $insert_id );

		// Increment the overall counter.
		$total_generate++;
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => $generate_type,
		Core\QUERY_BASE . 'count'   => $total_generate,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}

/**
 * Include a featured image to a newly generated post.
 *
 * @param  integer $post_id  The post ID the post thumbnail is to be associated with.
 *
 * @return mixed
 */
function generate_featured_image( $post_id = 0 ) {

	// Bail without a post ID.
	if ( empty( $post_id ) ) {
		return false;
	}

	// Set the type as a variable.
	$generate_type  = 'attachments';

	// Make sure our files get included.
	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}

	// Get my image data.
	$image_data = Helpers\get_fake_image();

	// Bail if the image data couldn't be retrieved.
	if ( empty( $image_data ) ) {
		return false;
	}

	// Download file to temp location.
	$temp_store = download_url( $image_data['url'] );

	// If error storing temporarily, return the error.
	if ( is_wp_error( $temp_store ) ) {
		return false; // $temp_store->get_error_code()
	}

	// Set my file array argument variable.
	$file_args  = array( 'name' => $image_data['name'], 'tmp_name' => $temp_store );

	// If error storing temporarily, unlink.
	if ( is_wp_error( $temp_store ) ) {

		// Unlink the file.
		@unlink( $file_args['tmp_name'] );

		// And set the name as empty.
		$file_args['tmp_name'] = '';
	}

	// Do the validation and storage stuff.
	$attach_id  = media_handle_sideload( $file_args, $post_id, '', array( 'post_title' => $image_data['title'] ) );

	// If error storing permanently, unlink.
	if ( is_wp_error( $attach_id ) ) {

		// Unlink the file.
		@unlink( $file_args['tmp_name'] );

		// And return false.
		return false;
	}

	// Set the appropriate meta.
	Helpers\set_generated_meta( $attach_id, $generate_type );

	// Set and return.
	return set_post_thumbnail( $post_id, $attach_id );
}

/**
 * Generate a given number of products.
 *
 * @param  integer $count      How many products we want.
 * @param  boolean $add_image  Whether to add a featured image.
 *
 * @return void
 */
function generate_products( $count = 0, $add_image = true ) {

	// Set the type as a variable.
	$generate_type  = 'products';

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 11 ? absint( $count ) : 10;

	// Set an overall counter.
	$total_generate = 0;

	// Make the posts.
	for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

		// Handle the action before we do the post.
		do_action( Core\HOOK_PREFIX . 'before_product_generate' );

		// Set the args.
		$setup_args = array(
			'post_type'    => 'product',
			'post_title'   => Helpers\get_fake_title(),
			'post_content' => Helpers\get_fake_content(),
			'post_date'    => Helpers\get_random_date( 'Y-m-d H:i:s' ),
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		);

		// Filter my args.
		$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_product_args', $setup_args );

		// Bail if we wiped out the args.
		if ( empty( $setup_args ) ) {
			continue;
		}

		// Insert the post into the database.
		$insert_id  = wp_insert_post( $setup_args );

		// If no ID came back, return that.
		if ( empty( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_product_id_created', Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// If we hit an actual WP error, do that.
		if ( is_wp_error( $insert_id ) ) {

			// Set each item as a variable.
			$error_code = $insert_id->get_error_code();
			$error_text = $insert_id->get_error_message();

			// Store the data.
			Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

			// Then redirect.
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $error_code, Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// Set all my WooCommerce product terms.
		Helpers\set_woo_product_terms( $insert_id );

		// Set all my WooCommerce product meta.
		Helpers\set_woo_product_meta( $insert_id );

		// Attempt to add the image if need be.
		if ( false !== $add_image ) {
			$featured   = generate_featured_image( $insert_id );
		}

		// Set the appropriate meta.
		Helpers\set_generated_meta( $insert_id, $generate_type );

		// Handle the action for a successful post.
		do_action( Core\HOOK_PREFIX . 'after_product_generate', $insert_id );

		// Increment the overall counter.
		$total_generate++;
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => $generate_type,
		Core\QUERY_BASE . 'count'   => $total_generate,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}

/**
 * Generate a given number of customers.
 *
 * @param  integer $count  How many customers we want.
 *
 * @return void
 */
function generate_customers( $count = 0 ) {

	// Set the type as a variable.
	$generate_type  = 'customers';

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 40 ? absint( $count ) : 20;

	// Set an overall counter.
	$total_generate = 0;

	// Make the posts.
	for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

		// Handle the action before we do the post.
		do_action( Core\HOOK_PREFIX . 'before_customer_generate' );

		// Fetch some random data.
		$random_person  = Helpers\get_fake_userdata( 'array' );

		// Bail the random person empty.
		if ( empty( $random_person ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_random_user_data', Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// Set the args.
		$setup_args = array(
			'user_pass'       => wp_generate_password( 32, true, true ),
			'user_login'      => $random_person['user-login'],
			'user_nicename'   => $random_person['user-login'],
			'user_email'      => $random_person['email-address'],
			'display_name'    => $random_person['display-name'],
			'nickname'        => $random_person['user-login'],
			'first_name'      => $random_person['first-name'],
			'last_name'       => $random_person['last-name'],
			'user_registered' => date( 'Y-m-d H:i:s', $random_person['registered'] ),
			'role'            => 'customer',
		);

		// Filter my args.
		$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_customer_args', $setup_args );

		// Bail if we wiped out the args.
		if ( empty( $setup_args ) ) {
			continue;
		}

		// Insert the user into the database.
		$insert_id  = wp_insert_user( $setup_args );

		// If no ID came back, return that.
		if ( empty( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_customer_id_created', Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// If we hit an actual WP error, do that.
		if ( is_wp_error( $insert_id ) ) {

			// Set each item as a variable.
			$error_code = $insert_id->get_error_code();
			$error_text = $insert_id->get_error_message();

			// Store the data.
			Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

			// Then redirect.
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $error_code, Core\QUERY_BASE . 'type' => $generate_type ) );
		}

		// Set all the extra Woo related things.
		Helpers\set_woo_customer_meta( $insert_id, $random_person );

		// Set the appropriate meta.
		Helpers\set_generated_meta( $insert_id, $generate_type );

		// Handle the action for a successful post.
		do_action( Core\HOOK_PREFIX . 'after_customer_generate', $insert_id );

		// Increment the overall counter.
		$total_generate++;
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => $generate_type,
		Core\QUERY_BASE . 'count'   => $total_generate,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}

/**
 * Generate a given number of comments.
 *
 * @param  integer $count       How many comments per post we want.
 * @param  boolean $add_thread  Whether to create some threaded comments as well.
 *
 * @return void
 */
function generate_reviews( $count = 0 ) {

	// Set the type as a variable.
	$generate_type  = 'reviews';

	// First grab some random posts to apply comments to.
	$product_array  = Datasets\fetch_site_content( 'product' );

	// Bail without a post array.
	if ( empty( $product_array ) ) {
		Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_available_products', Core\QUERY_BASE . 'type' => $generate_type ) );
	}

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 25 ? absint( $count ) : 25;

	// Set an overall counter.
	$total_generate = 0;

	// Now loop my products.
	foreach ( $product_array as $product ) {

		// Handle the action before comments.
		do_action( Core\HOOK_PREFIX . 'before_product_reviews', $product->ID, $product );

		// Check if comments are open and skip without.
		if ( 'open' !== esc_attr( $product->comment_status ) ) {
			continue;
		}

		// Make a timestamp of our product.
		$prod_stamp = strtotime( $product->post_date );

		// And now loop the commenters.
		for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

			// Handle the action before we do the comment.
			do_action( Core\HOOK_PREFIX . 'before_review_generate', $product->ID, $post );

			// Fetch some random data.
			$random_person  = Helpers\get_fake_userdata( 'array' );

			// Make a timestamp in the future.
			$comment_stamp  = $prod_stamp + rand( HOUR_IN_SECONDS, WEEK_IN_SECONDS );

			// Get the random text.
			$comment_text   = Helpers\get_fake_content( array( 'sentences' => rand( 2, 5 ), 'paras' => '' ) );

			// Set up the args.
			$setup_args = array(
				'comment_post_ID'      => absint( $product->ID ),
				'comment_author'       => $random_person['display-name'],
				'comment_author_email' => $random_person['email-address'],
				'comment_author_url'   => '',
				'comment_type'         => '',
				'comment_content'      => $comment_text,
				'comment_parent'       => 0,
				'user_id'              => 0,
				'comment_date'         => date( 'Y-m-d H:i:s', $comment_stamp ),
				'comment_approved'     => 1,
			);

			// Filter my args.
			$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_review_args', $setup_args );

			// Bail if we wiped out the args.
			if ( empty( $setup_args ) ) {
				continue;
			}

			// Run the new comment setup.
			$insert_id  = wp_insert_comment( $setup_args );

			// If no ID came back, return that.
			if ( empty( $insert_id ) ) {
				Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_review_id_created', Core\QUERY_BASE . 'type' => $generate_type ) );
			}

			// If we hit an actual WP error, do that.
			if ( is_wp_error( $insert_id ) ) {

				// Set each item as a variable.
				$error_code = $insert_id->get_error_code();
				$error_text = $insert_id->get_error_message();

				// Store the data.
				Helpers\manage_wp_error_data( array( $error_code => $error_text ), 'add' );

				// Then redirect.
				Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $error_code, Core\QUERY_BASE . 'type' => $generate_type ) );
			}

			// Set the appropriate meta.
			Helpers\set_generated_meta( $insert_id, $generate_type );

			// Handle the action for a successful comment.
			do_action( Core\HOOK_PREFIX . 'after_review_generate', $insert_id, $product->ID, $product );

			// Increment the overall counter.
			$total_generate++;
		}

		// Handle the action after comments.
		do_action( Core\HOOK_PREFIX . 'after_product_reviews', $product->ID, $product );
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => $generate_type,
		Core\QUERY_BASE . 'count'   => $total_generate,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}
