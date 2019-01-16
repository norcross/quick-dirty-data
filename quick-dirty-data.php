<?php
/**
 * Plugin Name: Quick and Dirty Data
 * Plugin URI:  https://github.com/norcross/quick-dirty-data
 * Description: A basic data generator for various WordPress things.
 * Version:     0.0.1
 * Author:      Andrew Norcross
 * Author URI:  http://andrewnorcross.com
 * Text Domain: quick-dirty-data
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package QuickDirtyData
 */

// Call our namepsace.
namespace QuickDirtyData;

// Call our CLI namespace.
use WP_CLI;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our version.
define( __NAMESPACE__ . '\VERS', '0.0.1' );

// Plugin Folder URL.
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

// Plugin root file.
define( __NAMESPACE__ . '\FILE', __FILE__ );

// Set our assets directory constant.
define( __NAMESPACE__ . '\ASSETS_URL', URL . 'assets' );

// Set our datafile directory constants.
define( __NAMESPACE__ . '\DATAFILE_URL', URL . 'assets/data' );
define( __NAMESPACE__ . '\DATAFILE_ROOT', __DIR__ . '/assets/data/' );

// Set our various prefixes and IDs.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'qckdrty_' );
define( __NAMESPACE__ . '\META_PREFIX', '_qckdrty_meta_' );
define( __NAMESPACE__ . '\NONCE_PREFIX', 'qckdrty_nonce_' );
define( __NAMESPACE__ . '\QUERY_BASE', 'qckdrty_run_' );
define( __NAMESPACE__ . '\ADMIN_BAR_ID', 'quick-dirty-data' );

// Go and load our files.
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/api-calls.php';
require_once __DIR__ . '/includes/datasets.php';
require_once __DIR__ . '/includes/generators.php';
require_once __DIR__ . '/includes/actions.php';
require_once __DIR__ . '/includes/notices.php';
require_once __DIR__ . '/includes/admin-bar.php';
require_once __DIR__ . '/includes/woocommerce.php';

// Check that we have the constant available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	// Load our commands file.
	require_once dirname( __FILE__ ) . '/includes/commands.php';

	// And add our command.
	WP_CLI::add_command( 'quickdirty', Commands::class );
}

