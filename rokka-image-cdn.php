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

if ( ! defined( 'ABSPATH' ) ) exit;

define('ROKKA_PLUGIN_PATH', plugins_url( '', __FILE__ ));

// Load plugin class files
require_once( 'includes/class-rokka-image-cdn.php' );
require_once( 'includes/class-rokka-image-cdn-settings.php' );
// Load plugin libraries
require_once( 'includes/lib/class-rokka-image-cdn-admin-api.php' );
require_once('includes/lib/filters/filter-rokka-upload.php');
require_once('includes/lib/filters/filter-rokka-content.php');
require_once('includes/lib/filters/filter-rokka-image-editor.php');
require_once ('includes/lib/class-rokka-mass-upload-images.php');
require_once ('includes/lib/class-rokka-helper.php');
require_once ('includes/lib/class-rokka-media-management.php');



//add vendor library
require_once( 'vendor/autoload.php' );

use \Rokka\Client\Factory;

/**
 * Returns the main instance of Rokka_Image_Cdn to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Rokka_Image_Cdn
 */
function rokka_image_cdn() {

	$instance = Rokka_Image_Cdn::instance( __FILE__, '1.0.0' );
    $rokka_helper = new Class_Rokka_Helper();
    $mass_upload = new Class_Rokka_Mass_Upload_Images($rokka_helper);

    if ( is_null( $instance->settings ) ) {
		$instance->settings = Rokka_Image_Cdn_Settings::instance( $instance, $mass_upload );
	}

    if (get_option('rokka_rokka_enabled')) {
		new Rokka_Media_Management();
        new Filter_Rokka_Upload($rokka_helper);
        new Filter_Rokka_Content($rokka_helper);
        new Filter_Rokka_Image_Editor( $rokka_helper );
    }

    return $instance;
}

rokka_image_cdn();

