<?php namespace Tests\Rokka_Integration;

class Rokka_Filter_Url_Test extends Rokka_UnitTestCase {
	public function test_get_attachment_url_without_rokka() {
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$expected_attachment_url = $this->get_default_wordpress_url( $image_name );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}

	public function test_get_attachment_url() {
		$this->enable_rokka();
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$expected_attachment_url = $this->get_rokka_url( $image_name, $this->get_stack_name_from_size( 'full' ) );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}

	public function test_get_attachment_image_srcset_without_rokka() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$image_name = '3000x2500.png';
			$attachment_id = $this->upload_attachment( $image_name );
			$attachment_meta = wp_get_attachment_metadata( $attachment_id );
			$post_thumbnail_filename = $attachment_meta['sizes']['post-thumbnail']['file'];
			$medium_filename = $attachment_meta['sizes']['medium']['file'];
			$large_filename = $attachment_meta['sizes']['large']['file'];
			$larger_filename = $attachment_meta['sizes']['larger']['file'];
			$huge_filename = $attachment_meta['sizes']['huge']['file'];
			$huger_filename = $attachment_meta['sizes']['huger']['file'];

			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'large' );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $post_thumbnail_filename ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_filename ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_filename ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $huge_filename ), $attachment_image_srcset ) );
			// the size huger (2500px) shouldn't appear in srcset since it's bigger than the defined max_srcset_image_width (2300px)
			$this->assertEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $huger_filename ), $attachment_image_srcset ) );
			if ( array_key_exists( 'medium_large', $attachment_meta['sizes'] ) ) {
				$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
				$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_large_filename ), $attachment_image_srcset ) );
				$this->assertCount( 6, explode( ',', $attachment_image_srcset ) );
			} else {
				$this->assertCount( 4, explode( ',', $attachment_image_srcset ) );
			}
		}
	}

	public function test_get_attachment_image_srcset() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->enable_rokka();
			$image_name = '3000x2500.png';
			$attachment_id = $this->upload_attachment( $image_name );
			$attachment_meta = wp_get_attachment_metadata( $attachment_id );
			$post_thumbnail_filename = $attachment_meta['sizes']['post-thumbnail']['file'];
			$medium_filename = $attachment_meta['sizes']['medium']['file'];
			$large_filename = $attachment_meta['sizes']['large']['file'];
			$larger_filename = $attachment_meta['sizes']['larger']['file'];
			$huge_filename = $attachment_meta['sizes']['huge']['file'];
			$huger_filename = $attachment_meta['sizes']['huger']['file'];

			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'huge' );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $post_thumbnail_filename, $this->get_stack_name_from_size( 'post-thumbnail' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_filename, $this->get_stack_name_from_size( 'medium' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $large_filename, $this->get_stack_name_from_size( 'large' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $larger_filename, $this->get_stack_name_from_size( 'larger' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $huge_filename, $this->get_stack_name_from_size( 'huge' ) ), $attachment_image_srcset ) );
			// the size huger (2500px) shouldn't appear in srcset since it's bigger than the defined max_srcset_image_width (2300px)
			$this->assertEquals( 0, preg_match_all( $this->get_rokka_url_regex_pattern( $huger_filename, $this->get_stack_name_from_size( 'huger' ) ), $attachment_image_srcset ) );
			if ( array_key_exists( 'medium_large', $attachment_meta['sizes'] ) ) {
				// The size medium_large was added in WordPress 4.4
				$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
				$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_large_filename, $this->get_stack_name_from_size( 'medium_large' ) ), $attachment_image_srcset ) );
				$this->assertCount( 6, explode( ',', $attachment_image_srcset ) );
			} else {
				$this->assertCount( 4, explode( ',', $attachment_image_srcset ) );
			}
		}
	}

	public function test_get_attachment_image_srcset_too_small_image() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->enable_rokka();
			$image_name = '1000x1800.png';
			$attachment_id = $this->upload_attachment( $image_name );
			$attachment_meta = wp_get_attachment_metadata( $attachment_id );
			$thumbnail_filename = $attachment_meta['sizes']['thumbnail']['file'];
			$medium_crop_filename = $attachment_meta['sizes']['medium-crop']['file'];
			$large_crop_filename = $attachment_meta['sizes']['large-crop']['file'];
			// the sizes larger-crop and huge-crop are generated even if they are bigger than the original image
			$larger_crop_filename = $attachment_meta['sizes']['larger-crop']['file'];
			$huge_crop_filename = $attachment_meta['sizes']['huge-crop']['file'];

			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'huger-crop' );
			$this->assertCount( 3, explode( ',', $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $thumbnail_filename, $this->get_stack_name_from_size( 'thumbnail' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_crop_filename, $this->get_stack_name_from_size( 'medium-crop' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $large_crop_filename, $this->get_stack_name_from_size( 'large-crop' ) ), $attachment_image_srcset ) );
			// the sizes larger-crop and huge-crop shouldn't be in the srcset since they have the same sice as the large-crop image
			$this->assertEquals( 0, preg_match_all( $this->get_rokka_url_regex_pattern( $larger_crop_filename, $this->get_stack_name_from_size( 'larger-crop' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 0, preg_match_all( $this->get_rokka_url_regex_pattern( $huge_crop_filename, $this->get_stack_name_from_size( 'huge-crop' ) ), $attachment_image_srcset ) );
		}
	}

	public function test_get_attachment_image_without_rokka() {
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$large_filename = $attachment_meta['sizes']['large']['file'];
		$medium_filename = $attachment_meta['sizes']['medium']['file'];
		$larger_filename = $attachment_meta['sizes']['larger']['file'];

		$attachment_image = wp_get_attachment_image( $attachment_id, 'medium' );

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			// the requested size appears in src attribute and in srcset attribute
			$this->assertEquals( 2, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image ) );
			$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_large_filename ), $attachment_image ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_filename ), $attachment_image ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_filename ), $attachment_image ) );
			// the size huge shouldn't appear in srcset since it's the same size as the original image
			$huge_filename = $attachment_meta['sizes']['huge']['file']; // huge size of the image only exists in newer versions of WordPress since the size is bigger than the image it wasn't added in old versions.
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $image_name ), $attachment_image ) );
			$this->assertEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $huge_filename ), $attachment_image ) );
		} else {
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image ) );
		}
	}

	public function test_get_attachment_image() {
		$this->enable_rokka();
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$large_filename = $attachment_meta['sizes']['large']['file'];
		$medium_filename = $attachment_meta['sizes']['medium']['file'];
		$larger_filename = $attachment_meta['sizes']['larger']['file'];

		$attachment_image = wp_get_attachment_image( $attachment_id, 'medium' );

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $large_filename, $this->get_stack_name_from_size( 'large' ) ), $attachment_image ) );
			// the requested size appears in src attribute and in srcset attribute
			$this->assertEquals( 2, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_filename, $this->get_stack_name_from_size( 'medium' ) ), $attachment_image ) );
			$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_large_filename, $this->get_stack_name_from_size( 'medium_large' ) ), $attachment_image ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $larger_filename, $this->get_stack_name_from_size( 'larger' ) ), $attachment_image ) );

			$huge_filename = $attachment_meta['sizes']['huge']['file']; // huge size of the image only exists in newer versions of WordPress since the size is bigger than the image it wasn't added in old versions.
			// the size huge shouldn't appear in srcset since it's bigger than max_srcset_image_width defined in WordPress
			$this->assertEquals( 0, preg_match_all( $this->get_rokka_url_regex_pattern( $huge_filename, $this->get_stack_name_from_size( 'huge' ) ), $attachment_image ) );
		} else {
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_filename, $this->get_stack_name_from_size( 'medium' ) ), $attachment_image ) );
		}
	}

	public function test_get_attachment_image_src_by_size_array_without_rokka() {
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$large_filename = $attachment_meta['sizes']['large']['file'];
		$expected_attachment_url = $this->get_default_wordpress_url( $large_filename );
		$attachment_src = wp_get_attachment_image_src( $attachment_id, array( 1000, 750 ) );
		$attachment_url = $attachment_src[0];
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}

	public function test_get_attachment_image_src_by_size_array() {
		$this->enable_rokka();
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$large_filename = $attachment_meta['sizes']['large']['file'];
		$expected_attachment_url = $this->get_rokka_url( $large_filename, $this->get_stack_name_from_size( 'large' ) );
		$attachment_src = wp_get_attachment_image_src( $attachment_id, array( 1000, 750 ) );
		$attachment_url = $attachment_src[0];
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}

	public function test_get_attachment_image_src_by_unknown_size_without_rokka() {
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$expected_attachment_url = $this->get_default_wordpress_url( $image_name );
		$attachment_src = wp_get_attachment_image_src( $attachment_id, 'unknown-size' );
		$attachment_url = $attachment_src[0];
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}

	public function test_get_attachment_image_src_by_unknown_size() {
		$this->enable_rokka();
		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$expected_attachment_url = $this->get_rokka_url( $image_name, $this->get_stack_name_from_size( 'full' ) );
		$attachment_src = wp_get_attachment_image_src( $attachment_id, 'unknown-size' );
		$attachment_url = $attachment_src[0];
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}
}
