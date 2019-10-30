<?php
/**
 * Plugin Name: Rokka Integration
 * Plugin URI: https://github.com/rokka-io/rokka-wordpress-plugin
 * Description: Rokka image processing and cdn plugin for WordPress.
 * Version: 3.1.0
 * Author: Liip AG
 * Author URI: https://www.liip.ch
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
