<?php
/**
 * Block Editor
 *
 * @package rokka-integration
 */

namespace Rokka_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Block_Editor
 */
class Rokka_Block_Editor {

	/**
	 * Rokka_Block_Editor constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initializes block editor.
	 */
	public function init() {
		// Disable image editing
		if ( class_exists( 'WP_Block_Editor_Context' ) ) {
			// Class WP_Block_Editor_Context does only exist in WP >= 5.8
			add_filter( 'block_editor_settings_all', array( $this, 'disable_image_editing' ), 10, 1 );
		} else {
			add_filter( 'block_editor_settings', array( $this, 'disable_image_editing' ), 10, 1 );
		}
	}

	/**
	 * Disable image editing in block editor.
	 *
	 * @param array $editor_settings Editor settings.
	 * @return array
	 */
	public function disable_image_editing( $editor_settings ) {
		$editor_settings['imageEditing'] = false;
		return $editor_settings;
	}

}
