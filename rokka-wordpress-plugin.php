<?php
/**
 * Plugin Name: Rokka Integration
 * Version: 1.0.0
 * Plugin URI: https://github.com/rokka-io/rokka-wordpress-plugin
 * Description: Rokka image processing and cdn plugin for WordPress.
 * Author: Liip AG
 * Author URI: http://liip.ch/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.0
 * Tested up to: 4.7
 *
 * Text Domain: rokka-wordpress-plugin
 * Domain Path: /languages/
 *
 * @package rokka-wordpress-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ROKKA_PLUGIN_PATH', plugins_url( '', __FILE__ ) );

// Load plugin class files
require_once( 'src/class-rokka-wordpress-plugin.php' );
require_once( 'src/class-rokka-wordpress-plugin-settings.php' );
require_once( 'src/class-rokka-helper.php' );
require_once( 'src/class-rokka-media-management.php' );
require_once( 'src/class-rokka-filter-url.php' );
require_once( 'src/class-rokka-filter-content.php' );

//add vendor library
require_once( 'vendor/autoload.php' );

/**
 * Returns the main instance of Rokka_WordPress_Plugin to prevent the need to use globals.
 *
 * @return Rokka_WordPress_Plugin Rokka_WordPress_Plugin instance
 */
function rokka_wordpress_plugin() {
	$instance = Rokka_WordPress_Plugin::instance( __FILE__, '1.0.0' );
	$rokka_helper = new Rokka_Helper();

	if ( $rokka_helper->is_rokka_enabled() ) {
		new Rokka_Filter_Url( $rokka_helper );
		if ( ! is_admin() && $rokka_helper->is_output_parsing_enabled() ) {
			new Rokka_Filter_Content( $rokka_helper );
		}
	}

	if ( is_admin() ) {
		if ( $rokka_helper->is_rokka_enabled() ) {
			new Rokka_Media_Management( $rokka_helper );
		}

		if ( is_null( $instance->settings ) ) {
			$instance->settings = Rokka_WordPress_Plugin_Settings::instance( $instance, $rokka_helper );
		}
	}

	return $instance;
}

rokka_wordpress_plugin();
