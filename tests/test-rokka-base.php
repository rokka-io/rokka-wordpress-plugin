<?php
class Rokka_Base_Test extends Rokka_UnitTestCase {
	/**
	 * Test check version.
	 */
	public function test_check_version() {
		$rokka_integration_instance = Rokka_Integration::instance();
		update_option( 'rokka-integration_version', ( (float) $rokka_integration_instance->_version - 1 ) );
		$rokka_integration_instance->check_version();
		// rokka-integration_updated action should have been called 2 times (1. first load of plugin when starting unit test 2. manual change of version in this unit test)
		$this->assertEquals( 2, did_action( 'rokka-integration_updated' ) );

		update_option( 'rokka-integration_version', $rokka_integration_instance->_version );
		$rokka_integration_instance->check_version();
		// rokka-integration_updated action should not have been called again since the version number already matched.
		$this->assertEquals( 2, did_action( 'rokka-integration_updated' ) );
	}
}
