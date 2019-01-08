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
 * Generate a number of posts.
 *
 * @param  integer $count  How many posts we want.
 *
 * @return void
 */
function generate_posts( $count = 0 ) {

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 11 ? absint( $count ) : 10;

	// Make the posts.
	for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

		// Handle the action before we do the post.
		do_action( Core\HOOK_PREFIX . 'before_post_generate' );

		// Set the args.
		$setup_args = array(
			'post_title'   => Helpers\get_fake_title(),
			'post_content' => Helpers\get_fake_content(),
			'post_date'    => Helpers\get_random_date( 'Y-m-d H:i:s' ),
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'meta_input'   => array(
				Core\HOOK_PREFIX . 'sourced' => 1,
				Core\HOOK_PREFIX . 'created' => time(),
			),
		);

		// Filter my args.
		$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_post_args', $setup_args );

		// Insert the post into the database.
		$insert_id  = wp_insert_post( $setup_args );

		// If no ID came back, return that.
		if ( empty( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_id_created', Core\QUERY_BASE . 'type' => 'posts' ) );
		}

		// If we hit an actual WP error, do that.
		if ( is_wp_error( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $insert_id->get_error_code(), Core\QUERY_BASE . 'type' => 'posts' ) );
		}

		// Handle the action for a successful post.
		do_action( Core\HOOK_PREFIX . 'after_post_generate', $insert_id );
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => 'posts',
		Core\QUERY_BASE . 'count'   => $generate_count,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}

/**
 * Make my fake comments.
 *
 * @param  integer $count       How many comments per post we want.
 * @param  boolean $add_thread  Whether to create some threaded comments as well.
 *
 * @return void
 */
function generate_comments( $count = 0, $add_thread = true ) {

	// First grab some random posts to apply comments to.
	$post_array = Datasets\fetch_random_content();

	// Bail without a post array.
	if ( empty( $post_array ) ) {
		Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_post_array', Core\QUERY_BASE . 'type' => 'comments' ) );
	}

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 25 ? absint( $count ) : 25;

	// Set an overall counter.
	$total_generate = 0;

	// Now loop my posts.
	foreach ( $post_array as $post ) {

		// Check if comments are open and skip without.
		if ( 'open' !== esc_attr( $post->comment_status ) ) {
			continue;
		}

		// Make a timestamp of our post.
		$post_stamp = strtotime( $post->post_date );

		// Set an empty for thread IDs.
		$parents    = array();

		// And now loop the commenters.
		for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

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
				'comment_meta'         => array(
					Core\HOOK_PREFIX . 'sourced' => 1,
					Core\HOOK_PREFIX . 'created' => time(),
				),
			);

			// Run the new comment setup.
			$insert_id  = wp_insert_comment( $setup_args );

			// If no ID came back, return that.
			if ( empty( $insert_id ) ) {
				Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_id_created', Core\QUERY_BASE . 'type' => 'comments' ) );
			}

			// If we hit an actual WP error, do that.
			if ( is_wp_error( $insert_id ) ) {
				Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $insert_id->get_error_code(), Core\QUERY_BASE . 'type' => 'comments' ) );
			}

			// If we wanna make threads, add this ID.
			if ( false !== $add_thread ) {

				// Add the data pieces into an array.
				$parents[ $insert_id ] = $comment_stamp;
			}

			// Increment the overall counter.
			$total_generate++;
		}

		// Now thread if we have threads and allow it.
		if ( ! empty( $parents ) && get_option( 'thread_comments' ) ) {

			// Now loop the IDs we just set to thread.
			foreach ( $parents as $parent_id => $parent_stamp ) {

				// Fetch some random data.
				$random_person  = Helpers\get_fake_userdata( 'array' );

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
					'comment_meta'         => array(
						Core\HOOK_PREFIX . 'sourced' => 1,
						Core\HOOK_PREFIX . 'created' => time(),
					),
				);

				// Run the new comment setup.
				$response_id    = wp_insert_comment( $response_args );

				// If no ID came back, return that.
				if ( empty( $response_id ) ) {
					Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_id_created', Core\QUERY_BASE . 'type' => 'comments' ) );
				}

				// If we hit an actual WP error, do that.
				if ( is_wp_error( $response_id ) ) {
					Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $response_id->get_error_code(), Core\QUERY_BASE . 'type' => 'comments' ) );
				}

				// Increment the overall counter.
				$total_generate++;
			}

			// Done with the threaded loop.
		}
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => 'comments',
		Core\QUERY_BASE . 'count'   => $total_generate,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}

/**
 * Generate a number of users.
 *
 * @param  integer $count  How many users we want.
 *
 * @return void
 */
function generate_users( $count = 0 ) {

	// Check my count.
	$generate_count = ! empty( $count ) && absint( $count ) < 40 ? absint( $count ) : 20;

	// Get my default role to set users.
	$default_role   = apply_filters( Core\HOOK_PREFIX . 'default_user_role', get_option( 'default_role', 'subscriber' ) );

	// Make the posts.
	for ( $i = 0; $i < absint( $generate_count ); $i++ ) {

		// Handle the action before we do the post.
		do_action( Core\HOOK_PREFIX . 'before_user_generate' );

		// Fetch some random data.
		$random_person  = Helpers\get_fake_userdata( 'array' );

		preprint( $random_person, true );

		// Set the args.
		$setup_args = array(
			'user_pass'             => wp_generate_password( 16, true, true ),
			'user_login'            => $random_person['user-login'],
			'user_nicename'         => $random_person['user-login'],
			'user_email'            => $random_person['email-address'],
			'display_name'          => $random_person['display-name'],
			'nickname'              => $random_person['user-login'],
			'first_name'            => $random_person['first-name'],
			'last_name'             => $random_person['last-name'],
			'user_registered'       => date( 'Y-m-d H:i:s', time() ),
			'role'                  => $default_role,
		);

		// Filter my args.
		$setup_args = apply_filters( Core\HOOK_PREFIX . 'generate_user_args', $setup_args );

		// Insert the user into the database.
		$insert_id  = wp_insert_user( $setup_args );

		// If no ID came back, return that.
		if ( empty( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => 'no_id_created', Core\QUERY_BASE . 'type' => 'users' ) );
		}

		// If we hit an actual WP error, do that.
		if ( is_wp_error( $insert_id ) ) {
			Helpers\get_action_redirect( array( Core\QUERY_BASE . 'error' => $insert_id->get_error_code(), Core\QUERY_BASE . 'type' => 'users' ) );
		}

		// Handle the action for a successful post.
		do_action( Core\HOOK_PREFIX . 'after_user_generate', $insert_id );
	}

	// Set up the success return args.
	$setup_redirect = array(
		Core\QUERY_BASE . 'success' => 1,
		Core\QUERY_BASE . 'type'    => 'users',
		Core\QUERY_BASE . 'count'   => $generate_count,
	);

	// And redirect.
	Helpers\get_action_redirect( $setup_redirect );
}
