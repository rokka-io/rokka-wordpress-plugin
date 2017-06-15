<?php
/**
 * Plugin Name: Rokka Integration
 * Version: 1.1.3
 * Plugin URI: https://github.com/rokka-io/rokka-wordpress-plugin
 * Description: Rokka image processing and cdn plugin for WordPress.
 * Author: Liip AG
 * Author URI: http://liip.ch/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.0
 * Tested up to: 4.8
 *
 * Text Domain: rokka-integration
 * Domain Path: /languages/
 *
 * @package rokka-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ROKKA_PLUGIN_PATH', plugins_url( '', __FILE__ ) );

// Load plugin class files
require_once( dirname( __FILE__ ) . '/src/class-rokka-integration.php' );
require_once( dirname( __FILE__ ) . '/src/class-rokka-integration-settings.php' );
require_once( dirname( __FILE__ ) . '/src/class-rokka-helper.php' );
require_once( dirname( __FILE__ ) . '/src/class-rokka-media-management.php' );
require_once( dirname( __FILE__ ) . '/src/class-rokka-filter-url.php' );
require_once( dirname( __FILE__ ) . '/src/class-rokka-filter-content.php' );
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( dirname( __FILE__ ) . '/src/cli-command/class-rokka-wp-cli-command.php' );
}

//add vendor library
require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

/**
 * Returns the main instance of Rokka_Integration to prevent the need to use globals.
 *
 * @return Rokka_Integration Rokka_Integration instance
 */
function rokka_integration() {
	$instance = Rokka_Integration::instance( __FILE__, '1.0.0' );
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
			$instance->settings = Rokka_Integration_Settings::instance( $instance, $rokka_helper );
		}
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		\WP_CLI::add_command(
			'rokka',
			'Rokka_Wp_Cli_Command'
		);
	}

	return $instance;
}

rokka_integration();
