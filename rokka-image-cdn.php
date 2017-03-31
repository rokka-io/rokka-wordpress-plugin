<?php
/*
 * Plugin Name: Rokka Wordpress Plugin
 * Version: 1.0
 * Plugin URI: http://rokka.io/
 * Description: Rokka image processing and cdn plugin for WordPress.
 * Author: Philippe Savary
 * Author URI: http://liip.ch/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: Rokka_Image_Cdn
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Philippe Savary
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ROKKA_PLUGIN_PATH', plugins_url( '', __FILE__ ) );

// Load plugin class files
require_once( 'src/class-rokka-image-cdn.php' );
require_once( 'src/class-rokka-image-cdn-settings.php' );
require_once( 'src/class-rokka-image-cdn-admin-api.php' );
require_once( 'src/class-rokka-filter-url.php' );
require_once( 'src/class-rokka-sync.php' );
require_once( 'src/filters/filter-rokka-content.php' );
require_once( 'src/filters/class-filter-rokka-image-editor.php' );
require_once( 'src/class-rokka-mass-upload-images.php' );
require_once( 'src/class-rokka-helper.php' );
require_once( 'src/class-rokka-media-management.php' );

//add vendor library
require_once( 'vendor/autoload.php' );

/**
 * Returns the main instance of Rokka_Image_Cdn to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Rokka_Image_Cdn
 */
function rokka_image_cdn() {

	$instance     = Rokka_Image_Cdn::instance( __FILE__, '1.0.0' );
	$rokka_helper = new Rokka_Helper();
	$mass_upload  = new Rokka_Mass_Upload_Images( $rokka_helper );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Rokka_Image_Cdn_Settings::instance( $instance, $mass_upload );
	}

	if ( get_option( 'rokka_rokka_enabled' ) ) {
		new Rokka_Sync( $rokka_helper );
		new Rokka_Media_Management();
		new Rokka_Filter_Url( $rokka_helper );
		new Filter_Rokka_Content( $rokka_helper );
		new Filter_Rokka_Image_Editor( $rokka_helper );
	}

	return $instance;
}

rokka_image_cdn();
