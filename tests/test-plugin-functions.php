<?php

class PluginFunctionsTest extends WP_UnitTestCase {
	private $_plugin_dir;
	private $images;
	private $rokka_company_name = 'dummy_company_name';
	private $rokka_url = '';
	private $stack_prefix = 'wp-';
	protected $sizes = [];

	public function setUp() {
		parent::setUp();
		$this->_plugin_dir = dirname( dirname( __FILE__ ) );
		$this->sizes = [
			'thumbnail' => [
				'width' => 150,
				'height' => 150,
			],
			'medium' => [
				'width' => 300,
				'height' => 300,
			],
			'medium_large' => [
				'width' => 768,
				'height' => 0,
			],
			'large' => [
				'width' => 1024,
				'height' => 1024,
			],
			'larger' => [
				'width' => 1600,
				'height' => 1600,
			],
			'huge' => [
				'width' => 2000,
				'height' => 2000,
			],
		];
		$this->prepare_image_sizes();
		$this->rokka_url = 'https://' . $this->rokka_company_name . '.rokka.io';
		$this->images = [];
		$this->images['2000x1500.png'] = $this->attachment_create_upload_object( $this->_plugin_dir . '/tests/features/images/2000x1500.png', 0 );
	}

	public function tearDown() {
		parent::tearDown();
		$upload_dir = wp_upload_dir( null, false );
		exec(sprintf("rm -rf %s", escapeshellarg($upload_dir['basedir'])));
		exec(sprintf("mkdir -p %s", escapeshellarg($upload_dir['basedir'])));
	}

	public function test_get_attachment_url_without_rokka() {
		$image_to_check = '2000x1500.png';
		$expected_attachment_url = $this->get_default_wordpress_url( $image_to_check );
		$attachment_url = wp_get_attachment_url( $this->images[$image_to_check] );
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}

	public function test_get_attachment_url() {
		$this->add_rokka_hashes();
		$image_to_check = '2000x1500.png';
		$image_id = $this->images[$image_to_check];
		$expected_attachment_url = $this->get_rokka_url( $image_id, $image_to_check, $this->get_stack_name_from_size( 'full' ) );
		$attachment_url = wp_get_attachment_url( $image_id );
		$this->assertEquals( $expected_attachment_url, $attachment_url );
		$this->remove_rokka_hashes();
	}

	public function test_get_attachment_image_srcset_without_rokka() {
		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$image_to_check        = '2000x1500.png';
			$attachment_meta       = wp_get_attachment_metadata( $this->images[ $image_to_check ] );
			$large_filename        = $attachment_meta['sizes']['large']['file'];
			$medium_filename       = $attachment_meta['sizes']['medium']['file'];
			$larger_filename       = $attachment_meta['sizes']['larger']['file'];
			$huge_filename         = $attachment_meta['sizes']['huge']['file'];

			$attachment_image_srcset = wp_get_attachment_image_srcset( $this->images[ $image_to_check ], 'large' );
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
			$this->add_rokka_hashes();
			$image_to_check        = '2000x1500.png';
			$attachment_meta       = wp_get_attachment_metadata( $this->images[ $image_to_check ] );
			$large_filename        = $attachment_meta['sizes']['large']['file'];
			$medium_filename       = $attachment_meta['sizes']['medium']['file'];
			$larger_filename       = $attachment_meta['sizes']['larger']['file'];
			$huge_filename         = $attachment_meta['sizes']['huge']['file'];

			$attachment_image_srcset = wp_get_attachment_image_srcset( $this->images[ $image_to_check ], 'large' );
			$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $large_filename, $this->get_stack_name_from_size( 'large' ) ), $attachment_image_srcset ) );
			$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $medium_filename, $this->get_stack_name_from_size( 'medium' ) ), $attachment_image_srcset ) );
			if ( array_key_exists( 'medium_large', $attachment_meta['sizes'] ) ) {
				$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
				$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $medium_large_filename, $this->get_stack_name_from_size( 'medium_large' ) ), $attachment_image_srcset ) );
			}
			$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $larger_filename, $this->get_stack_name_from_size( 'larger' ) ), $attachment_image_srcset ) );
			// the size huge shouldn't appear in srcset since it's bigger than max_srcset_image_width defined in WordPress
			$this->assertEquals( 0, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $huge_filename, $this->get_stack_name_from_size( 'huge' ) ), $attachment_image_srcset ) );
			$this->remove_rokka_hashes();
		}
	}

	public function test_get_attachment_image_without_rokka() {
		$image_to_check = '2000x1500.png';
		$attachment_meta = wp_get_attachment_metadata( $this->images[$image_to_check] );
		$large_filename = $attachment_meta['sizes']['large']['file'];
		$medium_filename = $attachment_meta['sizes']['medium']['file'];
		$larger_filename = $attachment_meta['sizes']['larger']['file'];
		$huge_filename = $attachment_meta['sizes']['huge']['file'];

		$attachment_image = wp_get_attachment_image( $this->images[$image_to_check], 'medium' );

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $large_filename ), $attachment_image ) );
			// the requested size appears in src attribute and in srcset attribute
			$this->assertEquals( 2, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image ) );
			$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_large_filename ), $attachment_image ) );
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $larger_filename ), $attachment_image ) );
			// the size huge shouldn't appear in srcset since it's bigger than max_srcset_image_width defined in WordPress
			$this->assertEquals( 0, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $huge_filename ), $attachment_image ) );
		} else {
			$this->assertEquals( 1, preg_match_all( $this->get_default_wordpress_url_regex_pattern( $medium_filename ), $attachment_image ) );
		}
	}

	public function test_get_attachment_image() {
		$this->add_rokka_hashes();
		$image_to_check = '2000x1500.png';
		$attachment_meta = wp_get_attachment_metadata( $this->images[$image_to_check] );
		$large_filename = $attachment_meta['sizes']['large']['file'];
		$medium_filename = $attachment_meta['sizes']['medium']['file'];
		$larger_filename = $attachment_meta['sizes']['larger']['file'];
		$huge_filename = $attachment_meta['sizes']['huge']['file'];

		$attachment_image = wp_get_attachment_image( $this->images[$image_to_check], 'medium' );

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $large_filename, $this->get_stack_name_from_size( 'large' ) ), $attachment_image ) );
			// the requested size appears in src attribute and in srcset attribute
			$this->assertEquals( 2, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $medium_filename, $this->get_stack_name_from_size( 'medium' ) ), $attachment_image ) );
			$medium_large_filename = $attachment_meta['sizes']['medium_large']['file'];
			$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $medium_large_filename, $this->get_stack_name_from_size( 'medium_large' ) ), $attachment_image ) );
			$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $larger_filename, $this->get_stack_name_from_size( 'larger' ) ), $attachment_image ) );
			// the size huge shouldn't appear in srcset since it's bigger than max_srcset_image_width defined in WordPress
			$this->assertEquals( 0, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $huge_filename, $this->get_stack_name_from_size( 'huge' ) ), $attachment_image ) );
		} else {
			$this->assertEquals( 1, preg_match_all( $this->ger_rokka_url_regex_pattern( $this->images[ $image_to_check ], $medium_filename, $this->get_stack_name_from_size( 'medium' ) ), $attachment_image ) );
		}
		$this->remove_rokka_hashes();
	}

	protected function prepare_image_sizes() {
		// redefine original WordPress sizes
		update_option( 'thumbnail_size_w', $this->sizes['thumbnail']['width'] );
		update_option( 'thumbnail_size_h', $this->sizes['thumbnail']['height'] );

		update_option( 'medium_size_w', $this->sizes['medium']['width'] );
		update_option( 'medium_size_h', $this->sizes['medium']['height'] );

		update_option( 'medium_large_size_w', $this->sizes['medium_large']['width'] );
		update_option( 'medium_large_size_h', $this->sizes['medium_large']['height'] );

		update_option( 'large_size_w', $this->sizes['large']['width'] );
		update_option( 'large_size_h', $this->sizes['large']['height'] );

		// add custom sizes
		add_image_size( 'larger', $this->sizes['larger']['width'], $this->sizes['larger']['height'] );
		add_image_size( 'huge', $this->sizes['huge']['width'], $this->sizes['huge']['height'] );
	}

	protected function add_rokka_hashes() {
		foreach( $this->images as $id ) {
			add_post_meta( $id, 'rokka_hash', $this->get_rokka_hash( $id ), true );
		}
	}

	protected function remove_rokka_hashes() {
		foreach( $this->images as $id ) {
			delete_post_meta( $id, 'rokka_hash' );
		}
	}

	protected function get_default_wordpress_url( $filename ) {
		// we can't use wp_get_upload_dir() here since this method was introduced in WordPress 4.5.
		$current_upload_dir = wp_upload_dir( null, false );
		return $current_upload_dir['url'] . '/' . $filename;
	}

	protected function get_default_wordpress_url_regex_pattern( $filename ) {
		return '/' . preg_quote( $this->get_default_wordpress_url( $filename ), '/' ) . '/';
	}

	protected function get_rokka_url( $id, $filename, $stack ) {
		return $this->rokka_url . '/' . $stack . '/' . $this->get_rokka_hash( $id ) . '/' . $filename;
	}

	protected function ger_rokka_url_regex_pattern( $id, $filename, $stack ) {
		return '/' . preg_quote( $this->get_rokka_url( $id, $filename, $stack ), '/' ) . '/';
	}

	protected function get_rokka_hash( $image_id ) {
		return 'rokka_dummy_hash_' . $image_id;
	}

	protected function get_stack_name_from_size( $size ) {
		return $this->stack_prefix . $size;
	}

	/**
	 * Backport from WP_UnitTest_Factory_For_Attachment of WordPress 4.8 to be able to use this in WordPress < 4.4.
	 * source: https://develop.svn.wordpress.org/tags/4.8/tests/phpunit/includes/factory/class-wp-unittest-factory-for-attachment.php
	 */
	public function attachment_create_upload_object( $file, $parent = 0 ) {
		$contents = file_get_contents($file);
		$upload = wp_upload_bits(basename($file), null, $contents);

		$type = '';
		if ( ! empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
				$type = $mime['type'];
		}

		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $id;
	}
}
