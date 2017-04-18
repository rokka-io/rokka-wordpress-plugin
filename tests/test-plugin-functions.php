<?php

class PluginFunctionsTest extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		$this->_plugin_dir = dirname( dirname( __FILE__ ) );
		$this->attachment_bigger_than_biggest_size_id = $this->factory->attachment->create_upload_object( $this->_plugin_dir . '/tests/images/2000x1500.png', 0 );
	}

	public function tearDown() {
		parent::tearDown();
		$upload_dir = wp_get_upload_dir();
		//rmdir($upload_dir['basedir']);
		exec(sprintf("rm -rf %s", escapeshellarg($upload_dir['basedir'])));
		exec(sprintf("mkdir -p %s", escapeshellarg($upload_dir['basedir'])));
	}

	function test_get_plugin_version() {
		$version = '1.0.0';
		$this->assertEquals( '1.0.0', $version );
	}

	public function test_date_query_before_array() {
		echo wp_get_attachment_url( $this->attachment_bigger_than_biggest_size_id );
	}
}
