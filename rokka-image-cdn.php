<?php
/*
 * Plugin Name: Rokka_Image_Cdn
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

// Load plugin class files
require_once( 'includes/class-rokka-image-cdn.php' );
require_once( 'includes/class-rokka-image-cdn-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-rokka-image-cdn-admin-api.php' );
require_once( 'includes/lib/class-rokka-image-cdn-post-type.php' );
require_once( 'includes/lib/class-rokka-image-cdn-taxonomy.php' );
require_once('includes/lib/filters/filter-rokka-upload.php');
require_once( 'includes/lib/class_rokka_image_editor.php' );
require_once ('includes/lib/class-rokka-mass-upload-images.php');
require_once ('includes/lib/class-rokka-helper.php');


//add vendor library
require_once( 'vendor/autoload.php' );
//require_once( 'vendor/' );

use \Rokka\Client\Factory;

/**
 * Returns the main instance of Rokka_Image_Cdn to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Rokka_Image_Cdn
 */
function rokka_image_cdn () {
	$instance = Rokka_Image_Cdn::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Rokka_Image_Cdn_Settings::instance( $instance );
	}
    $date = new DateTime();
    //file_put_contents("/tmp/wordpress.log", $date->format('Y-m-d H:i:s') . ': hallo'.PHP_EOL, FILE_APPEND);
    if (get_option('rokka_rokka_enabled')) {
        $rokka_helper = new Class_Rokka_Helper();
        new Filter_Rokka_Upload($rokka_helper);
        rokka_intercept_ajax_image_edit();
        //file_put_contents("/tmp/wordpress.log", $date->format('Y-m-d H:i:s') . 'rokka enabled'.PHP_EOL, FILE_APPEND);

    }
    return $instance;
}


/**
 * intecept ajax calls to wordpress in order to make changes to the image editor
 */
function rokka_intercept_ajax_image_edit()
{
    $date = new DateTime();
   // file_put_contents("/tmp/wordpress.log", $date->format('Y-m-d H:i:s') . ': POST:'. var_dump($_POST, false).PHP_EOL, FILE_APPEND);
    $attachment_id = intval($_POST['postid']);
    //todo verify nonce
    if ($_POST['action'] == 'image-editor' && is_ajax())//&& wp_verify_nonce($_POST['_ajax_nonce'] ,"image_editor-$attachment_id"))
    {
        new class_rokka_image_editor($_POST);
        file_put_contents("/tmp/wordpress.log", $date->format('Y-m-d H:i:s') . ': WE DO IMAGE EDIT PROCESSING:'. print_r($_POST,true).PHP_EOL, FILE_APPEND);
    }
}

/**
 * Is this an AJAX process?
 *
 * @return bool
 */
function is_ajax() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return true;
    }

    return false;
}

    rokka_image_cdn();

