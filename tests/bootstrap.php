<?php
/**
 * PHPUnit bootstrap file
 *
 * @package rokka-integration
 */

// Require composer dependencies.
require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';

// Determine the tests directory (from a WP dev checkout).
// Try the WP_TESTS_DIR environment variable first.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Next, try the WP_PHPUNIT composer package.
if ( ! $_tests_dir ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

// See if we're installed inside an existing WP dev instance.
if ( ! $_tests_dir ) {
	$_try_tests_dir = dirname( __FILE__ ) . '/../../../../../tests/phpunit';
	if ( file_exists( $_try_tests_dir . '/includes/functions.php' ) ) {
		$_tests_dir = $_try_tests_dir;
	}
}
// Fallback.
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Do not try to load JavaScript files from an external URL - this takes a
// while.
define( 'GUTENBERG_LOAD_VENDOR_SCRIPTS', false );

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/rokka-integration.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load own UnitTestCase framework
require_once dirname( __FILE__ ) . '/framework/Rokka_UnitTestCase.php';
require_once dirname( __FILE__ ) . '/framework/WP_Crop_Bugfix_UnitTestCase.php';
// Only initialize REST unit tests in supported versions (WP >= 4.7)
if ( version_compare( $GLOBALS['wp_version'], '4.7', '>=' ) ) {
	require_once dirname( __FILE__ ) . '/framework/Rokka_REST_UnitTestCase.php';
}
