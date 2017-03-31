<?php

require_once dirname( __FILE__ ) . '/../web/wp-unittest-config.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_switch_theme() {
	switch_theme( 'nebis' );
}

tests_add_filter( 'plugins_loaded', '_manually_switch_theme' );

// autoload timber plugin dependencies
if ( file_exists($composer_autoload = __DIR__ . '/../web/content/plugins/timber-library/vendor/autoload.php') ) {
	require_once($composer_autoload);
}

require $_tests_dir . '/includes/bootstrap.php';
