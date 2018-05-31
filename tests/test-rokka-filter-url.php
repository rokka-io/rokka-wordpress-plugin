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
			$image_name = '2000x1500.png';
			$attachment_id = $this->upload_attachment( $image_name );
			$attachment_meta       = wp_get_attachment_metadata( $attachment_id );
			$large_filename        = $attachment_meta['sizes']['large']['file'];
			$medium_filename       = $attachment_meta['sizes']['medium']['file'];
			$larger_filename       = $attachment_meta['sizes']['larger']['file'];
			$huge_filename         = $attachment_meta['sizes']['huge']['file'];

			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'large' );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_filename ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image_srcset ) );
			if ( array_key_exists( 'medium_large', $attachment_meta['sizes'] ) ) {
				$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
				$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_large_filename ), $attachment_image_srcset ) );
			}
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_filename ), $attachment_image_srcset ) );
			// the size huge shouldn't appear in srcset since it's bigger than max_srcset_image_width defined in WordPress
			$this->assertEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $huge_filename ), $attachment_image_srcset ) );
		}
	}

	public function test_get_attachment_image_srcset() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->enable_rokka();
			$image_name = '2000x1500.png';
			$attachment_id = $this->upload_attachment( $image_name );
			$attachment_meta       = wp_get_attachment_metadata( $attachment_id );
			$large_filename        = $attachment_meta['sizes']['large']['file'];
			$medium_filename       = $attachment_meta['sizes']['medium']['file'];
			$larger_filename       = $attachment_meta['sizes']['larger']['file'];
			$huge_filename         = $attachment_meta['sizes']['huge']['file'];

			$attachment_image_srcset = wp_get_attachment_image_srcset( $attachment_id, 'large' );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $large_filename, $this->get_stack_name_from_size( 'large' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_filename, $this->get_stack_name_from_size( 'medium' ) ), $attachment_image_srcset ) );
			if ( array_key_exists( 'medium_large', $attachment_meta['sizes'] ) ) {
				$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
				$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $medium_large_filename, $this->get_stack_name_from_size( 'medium_large' ) ), $attachment_image_srcset ) );
			}
			$this->assertEquals( 1, preg_match_all( $this->get_rokka_url_regex_pattern( $larger_filename, $this->get_stack_name_from_size( 'larger' ) ), $attachment_image_srcset ) );
			// the size huge shouldn't appear in srcset since it's bigger than max_srcset_image_width defined in WordPress
			$this->assertEquals( 0, preg_match_all( $this->get_rokka_url_regex_pattern( $huge_filename, $this->get_stack_name_from_size( 'huge' ) ), $attachment_image_srcset ) );
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
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_filename ), $attachment_image ) );
			// the requested size appears in src attribute and in srcset attribute
			$this->assertEquals( 2, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image ) );
			$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_large_filename ), $attachment_image ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_filename ), $attachment_image ) );

			$huge_filename = $attachment_meta['sizes']['huge']['file']; // huge size of the image only exists in newer versions of WordPress since the size is bigger than the image it wasn't added in old versions.
			// the size huge shouldn't appear in srcset since it's bigger than max_srcset_image_width defined in WordPress
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
}
