<?php
namespace Tests\Rokka_Integration;

use Rokka_Integration\Rokka_Helper;

class Rokka_Helper_Test extends Rokka_UnitTestCase {
	protected $rokka_helper = null;

	public function setUp(): void
	{
		parent::setUp();
		$this->rokka_helper = new Rokka_Helper();
	}

	/**
	 * Test get_available_image_sizes includes custom site_icon sizes.
	 */
	public function test_get_available_image_sizes() {
		$expected_sizes = array(
			'thumbnail',
			'medium',
			'medium_large',
			'large',
			'1536x1536',
			'2048x2048',
			'post-thumbnail',
			'larger',
			'huge',
			'huger',
			'medium-crop',
			'large-crop',
			'larger-crop',
			'huge-crop',
			'huger-crop',
			'zero-height-crop',
			'zero-width-crop',
			'site_icon-333',
		);
		$this->assertEquals( $expected_sizes, array_keys( $this->rokka_helper->get_available_image_sizes() ) );
		// site_icon-1024 should not be included since it is bigger than the maximum icon_size of 512 defined in WP_Icon_Size class
		$this->assertNotContains( 'site_icon-1024', array_keys( $this->rokka_helper->get_available_image_sizes() ) );
	}
}
