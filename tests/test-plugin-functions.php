<?php

class PluginFunctionsTest extends WP_UnitTestCase {
	private $_plugin_dir;
	private $images;
	private $rokka_company_name = 'dummy_company_name';
	private $rokka_url = '';
	private $full_stack_name = 'full';
	private $default_stack_prefix = 'wp-';

	public function setUp() {
		parent::setUp();
		$this->_plugin_dir = dirname( dirname( __FILE__ ) );
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
		$expected_attachment_url = $this->get_rokka_url( $image_id, $image_to_check, $this->default_stack_prefix . $this->full_stack_name );
		$attachment_url = wp_get_attachment_url( $image_id );
		$this->assertEquals( $expected_attachment_url, $attachment_url );
		$this->remove_rokka_hashes();
	}

	public function add_rokka_hashes() {
		foreach( $this->images as $id ) {
			add_post_meta( $id, 'rokka_hash', 'rokka_dummy_hash_' . $id, true );
		}
	}

	public function remove_rokka_hashes() {
		foreach( $this->images as $id ) {
			delete_post_meta( $id, 'rokka_hash' );
		}
	}

	public function get_default_wordpress_url( $filename ) {
		// we can't use wp_get_upload_dir() here since this method was introduced in WordPress 4.5.
		$current_upload_dir = wp_upload_dir( null, false );
		return $current_upload_dir['url'] . '/' . $filename;
	}

	public function get_rokka_url( $id, $filename, $stack ) {
		return $this->rokka_url . '/' . $stack . '/rokka_dummy_hash_' . $id . '/' . $filename;
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
