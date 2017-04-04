<?php
/**
 * Plugin Name: Rokka.io
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
 * Text Domain: rokka-image-cdn
 * Domain Path: /languages/
 *
 * @package rokka-image-cdn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ROKKA_PLUGIN_PATH', plugins_url( '', __FILE__ ) );

// Load plugin class files
require_once( 'src/class-rokka-image-cdn.php' );
require_once( 'src/class-rokka-image-cdn-settings.php' );
require_once( 'src/class-rokka-sync.php' );
require_once( 'src/class-rokka-image-editor.php' );
require_once( 'src/class-rokka-helper.php' );
require_once( 'src/class-rokka-media-management.php' );
require_once( 'src/class-rokka-filter-url.php' );
require_once( 'src/class-rokka-filter-content.php' );

//add vendor library
require_once( 'vendor/autoload.php' );

/**
 * Returns the main instance of Rokka_Image_Cdn to prevent the need to use globals.
 *
 * @return Rokka_Image_Cdn Rokka_Image_Cdn instance
 */
function rokka_image_cdn() {
	$instance = Rokka_Image_Cdn::instance( __FILE__, '1.0.0' );
	$rokka_helper = new Rokka_Helper();

	if ( get_option( 'rokka_rokka_enabled' ) ) {
		new Rokka_Filter_Url( $rokka_helper );
		if ( ! is_admin() && get_option( 'rokka_output_parsing' ) ) {
			new Rokka_Filter_Content( $rokka_helper );
		}
	}

	if ( is_admin() ) {
		$rokka_sync = new Rokka_Sync( $rokka_helper );
		new Rokka_Media_Management( $rokka_helper );
		new Rokka_Image_Editor( $rokka_helper );

		if ( is_null( $instance->settings ) ) {
			$instance->settings = Rokka_Image_Cdn_Settings::instance( $instance, $rokka_sync );
		}
	}

	return $instance;
}

rokka_image_cdn();
