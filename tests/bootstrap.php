<?php
/**
 * PHPUnit bootstrap file
 *
 * @package rokka-integration
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

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
require_once dirname( __FILE__ ) . '/Rokka_UnitTestCase.php';
require_once dirname( __FILE__ ) . '/Rokka_REST_UnitTestCase.php';
