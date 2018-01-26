<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 26.01.18
 * Time: 09:12
 */

class Rokka_UnitTestCase extends WP_UnitTestCase {
	protected $_plugin_dir;
	protected $images_dir;
	protected $rokka_company_name = 'dummy_company_name';
	protected $rokka_url = '';
	protected $stack_prefix = 'wp-';
	protected $sizes = [];

	public function setUp() {
		parent::setUp();
		$this->_plugin_dir = dirname( dirname( __FILE__ ) );
		$this->images_dir = $this->_plugin_dir . '/tests/features/images/';
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
		parent::tearDown();
		$upload_dir = wp_upload_dir( null, false );
		exec(sprintf("rm -rf %s", escapeshellarg($upload_dir['basedir'])));
		exec(sprintf("mkdir -p %s", escapeshellarg($upload_dir['basedir'])));
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
		add_post_meta( $attachment_id, 'rokka_hash', $this->get_rokka_hash( $attachment_id ), true );
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

	public function upload_attachment( $image_name ) {
		return $this->attachment_create_upload_object( $this->images_dir . $image_name, 0 );
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
