<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 26.01.18
 * Time: 10:28
 */

class Rokka_REST_UnitTestCase extends Rokka_UnitTestCase {
	/**
	 * @var \WP_REST_Server
	 */
	protected $server;

	/**
	 * Setup our test server.
	 */
	public function setUp() {
		parent::setUp();
		/** @var \WP_REST_Server $wp_rest_server */
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

	/**
	 * Uploads image via rest and returns response.
	 *
	 * @param string $image_name Name of image which should be uploaded.
	 * @return WP_REST_Response
	 */
	public function upload_image_via_rest( $image_name ) {
		$file = file_get_contents( $this->images_dir . $image_name );
		// create admin user
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$request  = new \WP_REST_Request( 'POST', '/wp/v2/media' );
		$request->set_body( $file );
		$request->set_header( 'content-type', 'image/png' );
		$request->set_header( 'Content-Disposition', 'attachment; filename="' . $image_name . '"' );
		return $this->server->dispatch( $request );
	}
}
