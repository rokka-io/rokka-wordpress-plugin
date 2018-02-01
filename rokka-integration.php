<?php
/**
 * Plugin Name: Rokka Integration
 * Version: 2.0.0
 * Plugin URI: https://github.com/rokka-io/rokka-wordpress-plugin
 * Description: Rokka image processing and cdn plugin for WordPress.
 * Author: Liip AG
 * Author URI: http://liip.ch/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.0
 * Tested up to: 4.9.2
 *
 * Text Domain: rokka-integration
 * Domain Path: /languages/
 *
 * @package rokka-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define ROKKA_INTEGRATION_PLUGIN_FILE.
if ( ! defined( 'ROKKA_PLUGIN_FILE' ) ) {
	define( 'ROKKA_PLUGIN_FILE', __FILE__ );
}

// Include the main Rokka_Integration class.
if ( ! class_exists( \Rokka_Integration\Rokka_Integration::class ) ) {
	include_once dirname( __FILE__ ) . '/src/class-rokka-integration.php';
}

// Initialize plugin
\Rokka_Integration\Rokka_Integration::instance();
