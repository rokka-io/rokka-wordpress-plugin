<?php

class PluginFunctionsTest extends WP_UnitTestCase {
	function test_get_plugin_version() {
		$version = '1.0.0';
		$this->assertEquals( '1.0.0', $version );
	}
}
