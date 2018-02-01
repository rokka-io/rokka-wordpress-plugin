<?php namespace Tests\Rokka_Integration;

if ( version_compare( $GLOBALS['wp_version'], '4.7', '>=' ) ) {
	class Rokka_Rest_Test extends Rokka_REST_UnitTestCase {
		public function test_add_image_via_rest_without_rokka() {
			$image_name = '2000x1500.png';
			$response   = $this->upload_image_via_rest( $image_name );
			// should have status 201 Created
			$this->assertEquals( 201, $response->get_status() );
			$data = $response->get_data();
			$this->assertArrayHasKey( 'source_url', $data );
			$this->assertEquals( $this->get_default_wordpress_url( $image_name ), $data['source_url'] );
		}

		public function test_add_image_via_rest() {
			$this->enable_rokka();
			$image_name = '2000x1500.png';
			$response   = $this->upload_image_via_rest( $image_name );
			// should have status 201 Created
			$this->assertEquals( 201, $response->get_status() );
			$data = $response->get_data();
			$this->assertArrayHasKey( 'source_url', $data );
			$this->assertEquals( $this->get_rokka_url( $image_name, $this->get_stack_name_from_size( 'full' ) ), $data['source_url'] );
		}
	}
}
