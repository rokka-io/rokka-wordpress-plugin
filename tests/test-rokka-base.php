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

	/**
	 * Settings provided via constant should be prioritized over database options
	 */
	public function test_check_constant_settings() {
		$constant_api_key = 'constant_api_key';
		$constant_company_name = 'constant_company_name';
		$constant_domain = 'constant_domain.test';
		$constant_scheme = 'http';
		define( Rokka_Helper::OPTION_API_KEY_CONSTANT_NAME, $constant_api_key );
		define( Rokka_Helper::OPTION_COMPANY_NAME_CONSTANT_NAME, $constant_company_name );
		define( Rokka_Helper::ROKKA_DOMAIN_CONSTANT_NAME, $constant_domain );
		define( Rokka_Helper::ROKKA_SCHEME_CONSTANT_NAME, $constant_scheme );
		$this->enable_rokka();

		$this->assertEquals( $constant_company_name, Rokka_Integration::instance()->rokka_helper->get_rokka_company_name() );
		$this->assertEquals( $constant_api_key, Rokka_Integration::instance()->rokka_helper->get_rokka_api_key() );
		$this->assertEquals( $constant_domain, Rokka_Integration::instance()->rokka_helper->get_rokka_domain() );
		$this->assertEquals( $constant_scheme, Rokka_Integration::instance()->rokka_helper->get_rokka_scheme() );

		$image_name = '2000x1500.png';
		$attachment_id = $this->upload_attachment( $image_name );
		$expected_attachment_url = $this->get_rokka_url( $image_name, $this->get_stack_name_from_size( 'full' ), $constant_scheme . '://' . $constant_domain );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$this->assertEquals( $expected_attachment_url, $attachment_url );
	}
}
