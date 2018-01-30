<?php

class RokkaRestTest extends Rokka_REST_UnitTestCase {
	public function test_add_image_via_rest_without_rokka() {
		$image_name = '2000x1500.png';
		$file = file_get_contents( $this->images_dir . $image_name );
		// create admin user
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$request  = new \WP_REST_Request( 'POST', '/wp/v2/media' );
		$request->set_body( $file );
		$request->set_header( 'content-type', 'image/png' );
		$request->set_header( 'Content-Disposition', 'attachment; filename="' . $image_name . '"' );
		$response = $this->server->dispatch( $request );
		// should have status 201 Created
		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'source_url', $data );
		$this->assertEquals( $this->get_default_wordpress_url( $image_name ), $data[ 'source_url' ] );
	}

	public function test_add_image_via_rest() {
		$this->enable_rokka();
		$image_name = '2000x1500.png';
		$file = file_get_contents( $this->images_dir . $image_name );
		// create admin user
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$request  = new \WP_REST_Request( 'POST', '/wp/v2/media' );
		$request->set_body( $file );
		$request->set_header( 'content-type', 'image/png' );
		$request->set_header( 'Content-Disposition', 'attachment; filename="' . $image_name . '"' );
		$response = $this->server->dispatch( $request );
		// should have status 201 Created
		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'source_url', $data );
		$this->assertEquals( $this->get_rokka_url( $image_name, $this->get_stack_name_from_size( 'full' ) ), $data[ 'source_url' ] );
	}
}
