<?php
/**
 * PHPUnit bootstrap file
 *
 * @package rokka-integration
 */

$_wp_version = getenv( 'WP_VERSION' );
if ( ! $_wp_version ) {
	$_wp_version = 'latest';
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib/' . $_wp_version;
}

echo "Running tests with WordPress " . $_wp_version . PHP_EOL;

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

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
