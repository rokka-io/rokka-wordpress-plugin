<?php namespace Tests\Rokka_Integration;
/**
 * Created by PhpStorm.
 * User: work
 * Date: 26.01.18
 * Time: 09:12
 */

use Rokka_Integration\Rokka_Integration;

class Rokka_UnitTestCase extends \WP_UnitTestCase {
	protected $_plugin_dir;
	protected $features_dir;
	protected $rokka_company_name = 'dummy_company_name';
	protected $rokka_api_key = 'dummy_api_key';
	protected $rokka_url = '';
	protected $stack_prefix = 'wp-';
	protected $rokka_hash = 'my_random_rokka_hash_123';
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
		];
		$this->prepare_image_sizes();
		$this->rokka_url = 'https://' . $this->rokka_company_name . '.rokka.io';
	}

	public function tearDown() {
		// Remove all uploads.
		$this->remove_added_uploads();
		parent::tearDown();
	}

	/**
	 * Simulates backend environment (is_admin() === true)
	 */
	protected function enable_backend() {
		// enable backend
		set_current_screen( 'edit-post' );
	}

	/**
	 * Enables rokka integration
	 */
	protected function enable_rokka() {
		// Set rokka options
		update_option( 'rokka_api_key', $this->rokka_api_key );
		update_option( 'rokka_company_name', $this->rokka_company_name );
		update_option( 'rokka_rokka_enabled', true );

		// Reload plugin to enable rokka
		Rokka_Integration::instance()->init_plugin();

		// Mock rokka client library
		$rokka_client_mock = $this->createMock( \Rokka\Client\Image::class );
		$source_image_collection_mock = $this->createMock( \Rokka\Client\Core\SourceImageCollection::class );
		$source_images = array(
			(object) array(
				'format' => '',
				'organization' => $this->rokka_company_name,
				'link' => '',
				'created' => '',
				'hash' => $this->rokka_hash,
			)
		);

		$source_image_collection_mock->method( 'getSourceImages' )
		                             ->willReturn( $source_images );

		// Configure the stub.
		$rokka_client_mock->method( 'uploadSourceImage' )
		                  ->willReturn( $source_image_collection_mock );

		$rokka_integration_plugin = Rokka_Integration::instance();
		$rokka_integration_plugin->rokka_helper->rokka_set_client( $rokka_client_mock );
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

	protected function add_rokka_hash( $attachment_id ) {
		add_post_meta( $attachment_id, 'rokka_hash', $this->get_rokka_hash(), true );
	}

	protected function remove_rokka_hash( $attachment_id ) {
		delete_post_meta( $attachment_id, 'rokka_hash' );
	}

	protected function get_default_wordpress_url( $filename ) {
		// we can't use wp_get_upload_dir() here since this method was introduced in WordPress 4.5.
		$current_upload_dir = wp_upload_dir( null, false );
		return $current_upload_dir['url'] . '/' . $filename;
	}

	protected function get_default_wordpress_url_regex_pattern( $filename ) {
		return '/' . preg_quote( $this->get_default_wordpress_url( $filename ), '/' ) . '/';
	}

	protected function get_rokka_url( $filename, $stack, $rokka_url = '' ) {
		if ( empty( $rokka_url ) ) {
			$rokka_url = $this->rokka_url;
		}
		return $rokka_url . '/' . $stack . '/' . $this->get_rokka_hash() . '/' . $filename;
	}

	protected function ger_rokka_url_regex_pattern( $filename, $stack ) {
		return '/' . preg_quote( $this->get_rokka_url( $filename, $stack ), '/' ) . '/';
	}

	protected function get_rokka_hash() {
		return $this->rokka_hash;
	}

	protected function get_stack_name_from_size( $size ) {
		return $this->stack_prefix . $size;
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
