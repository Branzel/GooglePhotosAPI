<?php
/**
 * Plugin Name: Branzel's Google API Blocks
 * Description: Gutenberg Google Media Blocks
 * Version: 1.0.0
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: googleapi
 * Domain Path: /languages
 *
 * @package Branzel
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define certain plugin variables as constants.
define( 'BRANZEL_GOOGLEAPI_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'BRANZEL_GOOGLEAPI__FILE__', __FILE__ );
define( 'BRANZEL_GOOGLEAPI_BASENAME', plugin_basename( BRANZEL_GOOGLEAPI__FILE__ ) );

/**
 * Load GoogleAPI class, which holds common functions and variables.
 */
require_once BRANZEL_GOOGLEAPI_ABSPATH . 'classes/class-googleapi.php';

// Start up GoogleAPI on WordPress's "init" action hook.
add_action( 'init', array( 'Branzel_GoogleAPI', 'run' ) );

add_action( 'plugins_loaded', 'googleapi_load_textdomain' );
/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function googleapi_load_textdomain() {
  load_plugin_textdomain( 'googleapi', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}