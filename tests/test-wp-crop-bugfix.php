<?php namespace Tests\Rokka_Integration;

class WP_Crop_Bugfix_Test extends WP_Crop_Bugfix_UnitTestCase {
	public function test_get_attachment_metadata_without_bugfix() {
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$this->assertArrayHasKey( 'medium-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'large-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'larger-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'huge-crop', $attachment_meta['sizes'] );
		// Size huger-crop doesn't exist without bugfix
		$this->assertArrayNotHasKey( 'huger-crop', $attachment_meta['sizes'] );

		// The ratio of the cropped size and the generated image should be equal
		$expected_ratio = $this->sizes['huge']['width'] / $this->sizes['huge']['height']; // -> ratio: 1
		$actual_ratio = $attachment_meta['sizes']['huge-crop']['width'] / $attachment_meta['sizes']['huge-crop']['height']; // -> ratio: 1.3333
		//$this->assertEquals( $expected_ratio, $actual_ratio ); // -> This test would fail
	}

	public function test_get_attachment_metadata() {
		$this->enable_wp_crop_bugfix();
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$this->assertArrayHasKey( 'medium-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'large-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'larger-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'huge-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'huger-crop', $attachment_meta['sizes'] );

		// The ratio of the cropped size and the generated image should be equal
		$expected_ratio = $this->sizes['huge']['width'] / $this->sizes['huge']['height']; // -> ratio: 1
		$actual_ratio = $attachment_meta['sizes']['huge-crop']['width'] / $attachment_meta['sizes']['huge-crop']['height']; // -> ratio: 1
		$this->assertEquals( $expected_ratio, $actual_ratio ); // -> This test passes
	}
}
