<?php namespace Tests\Rokka_Integration;

use Rokka_Integration\WP_Crop_Bugfix;

class WP_Crop_Bugfix_UnitTestCase extends \WP_UnitTestCase {
	protected $_plugin_dir;
	protected $features_dir;
	protected $sizes = [];

	public function setUp() {
		parent::setUp();

		$this->_plugin_dir = dirname( dirname( dirname( __FILE__ ) ) ); // two levels up
		$this->features_dir = $this->_plugin_dir . '/tests/features/';
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
			'huger' => [
				'width' => 2500,
				'height' => 2500,
			],
		];
		$this->prepare_image_sizes();
		// enhance max image width in srcset
		add_filter( 'max_srcset_image_width', array( $this, 'enhance_max_srcset_image_width'), 10, 0 );
	}

	public function enhance_max_srcset_image_width() {
		return 2300;
	}

	public function tearDown() {
		// Remove all uploads.
		$this->remove_added_uploads();
		parent::tearDown();
	}

	/**
	 * Enables WordPress crop bugfix
	 */
	protected function enable_wp_crop_bugfix() {
		new WP_Crop_Bugfix();
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
		add_image_size( 'huger', $this->sizes['huger']['width'], $this->sizes['huger']['height'] );

		// add cropped sizes
		add_image_size( 'medium-crop', $this->sizes['medium']['width'], $this->sizes['medium']['height'], true );
		add_image_size( 'large-crop', $this->sizes['large']['width'], $this->sizes['large']['height'], true );
		add_image_size( 'larger-crop', $this->sizes['larger']['width'], $this->sizes['larger']['height'], true );
		add_image_size( 'huge-crop', $this->sizes['huge']['width'], $this->sizes['huge']['height'], true );
		add_image_size( 'huger-crop', $this->sizes['huger']['width'], $this->sizes['huger']['height'], true );
	}

	protected function get_default_wordpress_url( $filename ) {
		// we can't use wp_get_upload_dir() here since this method was introduced in WordPress 4.5.
		$current_upload_dir = wp_upload_dir( null, false );
		return $current_upload_dir['url'] . '/' . $filename;
	}

	protected function get_default_wordpress_url_regex_pattern( $filename ) {
		return '/' . preg_quote( $this->get_default_wordpress_url( $filename ), '/' ) . '/';
	}

	public function upload_attachment( $file_name ) {
		return $this->attachment_create_upload_object( $this->features_dir . $file_name, 0 );
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
