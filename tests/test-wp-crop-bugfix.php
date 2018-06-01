<?php namespace Tests\Rokka_Integration;

class WP_Crop_Bugfix_Test extends WP_Crop_Bugfix_UnitTestCase {
	public function test_bug_image_wrong_ratio() {
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$this->assertArrayHasKey( 'medium-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'large-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'larger-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'huge-crop', $attachment_meta['sizes'] );
		// Size huger-crop doesn't exist without bugfix
		$this->assertArrayNotHasKey( 'huger-crop', $attachment_meta['sizes'] );

		// The huge-crop gets generated with a size of 2000x1500px because the maximum width and height are 2000px and the height of the image is only 1500px (=> Wrong ratio)
		// This results in different ratios of the defined size and the generated image
		$expected_ratio = $this->get_ratio( $this->sizes['huge']['width'], $this->sizes['huge']['height'] ); // -> ratio: 1
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes']['huge-crop']['width'], $attachment_meta['sizes']['huge-crop']['height'] ); // -> ratio: 1.3333
		$this->assertNotEquals( $expected_ratio, $actual_ratio ); // -> Ratios are not equal
	}

	public function test_bug_image_wrong_ratio_fixed() {
		$this->enable_wp_crop_bugfix();
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$this->assertArrayHasKey( 'medium-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'large-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'larger-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'huge-crop', $attachment_meta['sizes'] );
		$this->assertArrayHasKey( 'huger-crop', $attachment_meta['sizes'] );

		// With the bugfix the huge-crop size gets generated with a size of 1500x1500px (=> Corret ratio)
		// The ratio of the defined size and the generated image are now equal
		$expected_ratio = $this->get_ratio( $this->sizes['huge']['width'], $this->sizes['huge']['height'] ); // -> ratio: 1
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes']['huge-crop']['width'], $attachment_meta['sizes']['huge-crop']['height'] ); // -> ratio: 1
		$this->assertEquals( $expected_ratio, $actual_ratio ); // -> This test passes
	}

	public function test_bug_srcset_empty() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$image_name = '3200x2400.png';
			$attachment_id = $this->upload_attachment( $image_name );

			// The huger-crop gets generated with a size of 2500x2400px because the maximum width and height are 2500px and the height of the image is only 2400px (=> Wrong ratio)
			// When getting the srcset with this size WordPress can't find any other image with the same ratio (2500x2400px).
			// And since the max_srcset_image_width is set to 1800px the huger-crop size isn't returned which means the srcset is empty.
			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'huger-crop' );
			$this->assertEmpty( $attachment_image_srcset );
		}
	}

	public function test_bug_srcset_empty_fixed() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->enable_wp_crop_bugfix();
			$image_name = '3200x2400.png';
			$attachment_id = $this->upload_attachment( $image_name );

			// With the bugfix the huger-crop size gets generated with a size of 2400x2400px (=> Correct ratio)
			// When getting the srcset with this size we get all other sizes with a 1:1 ratio.
			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'huger-crop' );
			$this->assertNotEmpty( $attachment_image_srcset );
		}
	}

	public function test_bug_srcset_wrong_ratio() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$image_name = '2000x1500.png';
			$attachment_id = $this->upload_attachment( $image_name );

			// The huger-crop doesn't get generated during upload because the width (2500px) and height (2500px) are both bigger than the original image.
			$attachment_meta = wp_get_attachment_metadata( $attachment_id );
			$this->assertArrayNotHasKey( 'huger-crop', $attachment_meta['sizes'] );

			// since getting the srcset with the size huger-crop image_downsize() falls back to the original image with a ratio of 4:3 (2000x1500px) and generates a srcset with images in that ratio
			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'huger-crop' );
			$medium_crop_filename = $attachment_meta['sizes']['medium-crop']['file'];
			$large_crop_filename = $attachment_meta['sizes']['large-crop']['file'];
			$larger_crop_filename = $attachment_meta['sizes']['larger-crop']['file'];
			$huge_crop_filename = $attachment_meta['sizes']['huge-crop']['file'];
			$this->assertNotEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_crop_filename ), $attachment_image_srcset ) );
			$this->assertNotEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_crop_filename ), $attachment_image_srcset ) );
			$this->assertNotEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_crop_filename ), $attachment_image_srcset ) );
			$this->assertNotEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $huge_crop_filename ), $attachment_image_srcset ) );
			// Instead all images with the ratio 4:3 are in the srcset
			$medium_filename = $attachment_meta['sizes']['medium']['file'];
			$large_filename = $attachment_meta['sizes']['large']['file'];
			$larger_filename = $attachment_meta['sizes']['larger']['file'];
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $image_name ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_filename ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_filename ), $attachment_image_srcset ) );
		}
	}

	public function test_bug_srcset_wrong_ratio_fixed() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->enable_wp_crop_bugfix();
			$image_name = '2000x1500.png';
			$attachment_id = $this->upload_attachment( $image_name );

			// With the bugfix the huger-crop size gets generated with a size of 1500x1500px (=> Correct ratio)
			// When getting the srcset with this size we get all other sizes with a 1:1 ratio.
			$attachment_meta = wp_get_attachment_metadata( $attachment_id );

			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'huger-crop' );
			$medium_crop_filename = $attachment_meta['sizes']['medium-crop']['file'];
			$large_crop_filename = $attachment_meta['sizes']['large-crop']['file'];
			$larger_crop_filename = $attachment_meta['sizes']['larger-crop']['file'];
			$huge_crop_filename = $attachment_meta['sizes']['huge-crop']['file'];
			$this->assertNotEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_crop_filename ), $attachment_image_srcset ) );
			$this->assertNotEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_crop_filename ), $attachment_image_srcset ) );
			$this->assertNotEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_crop_filename ), $attachment_image_srcset ) );
			$this->assertNotEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $huge_crop_filename ), $attachment_image_srcset ) );
		}
	}

	public function test_bugfix_dest_width_bigger_than_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-width-bigger-than-image';
		$dest_size_width = 2100;
		$dest_size_height = 1400;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 3:2
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_width = 2000;
		$expected_height = (int) round( $expected_width / $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_dest_height_bigger_than_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-height-bigger-than-image';
		$dest_size_width = 2000;
		$dest_size_height = 1750;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 4:3
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_height = 1500;
		$expected_width = (int) round( $expected_height * $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_landscape_dest_size_bigger_than_landscape_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-size-bigger-than-image';
		$dest_size_width = 3000;
		$dest_size_height = 2000;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 3:2 (landscape)
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );

		// first image
		$image_name = '2000x1500.png'; // ratio 4:3 (landscape)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_width = 2000;
		$expected_height = (int) round( $expected_width / $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );

		// 2nd image
		$image_name = '2000x1000.png'; // ratio 2:1 (landscape)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_height = 1000;
		$expected_width = (int) round( $expected_height * $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_portrait_dest_size_bigger_than_landscape_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-size-bigger-than-image';
		$dest_size_width = 2400;
		$dest_size_height = 3200;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 3:4 (portrait)
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );

		$image_name = '2000x1500.png'; // ratio 4:3 (landscape)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_height = 1500;
		$expected_width = (int) round( $expected_height * $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_landscape_dest_size_bigger_than_portrait_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-size-bigger-than-image';
		$dest_size_width = 3200;
		$dest_size_height = 2400;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 4:3 (landscape)
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );

		$image_name = '1000x2000.png'; // ratio 1:2 (portrait)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_width = 1000;
		$expected_height = (int) round( $expected_width / $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_portrait_dest_size_bigger_than_portrait_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-size-bigger-than-image';
		$dest_size_width = 2000;
		$dest_size_height = 3000;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 2:3 (portrait)
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );

		// first image
		$image_name = '1500x2000.png'; // ratio 3:4 (portrait)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_height = 2000;
		$expected_width = (int) round( $expected_height * $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );

		// 2nd image
		$image_name = '1000x2000.png'; // ratio 1:2 (portrait)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_width = 1000;
		$expected_height = (int) round( $expected_width / $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_cubic_dest_size_bigger_than_portrait_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-size-bigger-than-image';
		$dest_size_width = 3000;
		$dest_size_height = 3000;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 1:1 (cubic)
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );

		$image_name = '1000x2000.png'; // ratio 1:2 (portrait)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_width = 1000;
		$expected_height = (int) round( $expected_width / $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_cubic_dest_size_bigger_than_landscape_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-size-bigger-than-image';
		$dest_size_width = 3000;
		$dest_size_height = 3000;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 1:1 (cubic)
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );

		$image_name = '2000x1000.png'; // ratio 2:1 (landscape)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_height = 1000;
		$expected_width = (int) round( $expected_height * $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}

	public function test_bugfix_cubic_dest_size_bigger_than_cubic_image() {
		$this->enable_wp_crop_bugfix();
		$dest_size_name = 'crop-size-bigger-than-image';
		$dest_size_width = 3000;
		$dest_size_height = 3000;
		$dest_ratio = $dest_size_width / $dest_size_height; // ratio 1:1 (cubic)
		add_image_size( $dest_size_name, $dest_size_width, $dest_size_height, 1 );

		$image_name = '1000x1000.png'; // ratio 1:1 (cubic)
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		// cropped image should use largest possible portion of original image with correct ratio
		$expected_height = 1000;
		$expected_width = (int) round( $expected_height * $dest_ratio );
		$this->assertEquals($expected_width, $attachment_meta['sizes'][$dest_size_name]['width']);
		$this->assertEquals($expected_height, $attachment_meta['sizes'][$dest_size_name]['height']);
		// generated image should have same ratio as size
		$expected_ratio = $this->round_ratio( $dest_ratio );
		$actual_ratio = $this->get_ratio( $attachment_meta['sizes'][$dest_size_name]['width'], $attachment_meta['sizes'][$dest_size_name]['height'] );
		$this->assertEquals( $expected_ratio, $actual_ratio );
	}
}
