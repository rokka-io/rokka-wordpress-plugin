<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 26.01.18
 * Time: 10:28
 */

class Rokka_REST_UnitTestCase extends Rokka_UnitTestCase {
	protected $server;
	/**
	 * Setup our test server.
	 */
	public function setUp() {
		parent::setUp();
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server;
		do_action( 'rest_api_init' );
	}
	/**
	 * Unset the server.
	 */
	public function tearDown() {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
	}
}
