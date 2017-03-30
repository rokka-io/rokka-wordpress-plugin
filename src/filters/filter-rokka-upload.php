<?php

require_once( ABSPATH . 'wp-includes/media.php' );

/**
 * Class Filter_Rokka_Upload
 */
class Filter_Rokka_Upload {

	/**
	 * @var Class_Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Filter_Rokka_Upload constructor.
	 *
	 * @param Class_Rokka_Helper $rokka_helper
	 */
	function __construct( Class_Rokka_Helper $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		$this->init();
	}

	/**
	 * Initializes Filter_Rokka_Upload class.
	 */
	protected function init() {
		add_action( 'add_attachment', array( $this, 'rokka_upload' ), 10, 1 );
		add_filter( 'wp_save_image_editor_file', array( $this, 'rokka_save_image_editor_file' ), 10, 5 );
		add_action( 'delete_attachment', array( $this, 'rokka_delete' ), 10, 1 );
	}

	/**
	 * Handle upload of image to Rokka.
	 *
	 * @param integer $attachment_id
	 */
	function rokka_upload( $attachment_id ) {
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		$this->rokka_helper->upload_image_to_rokka( $attachment_id, $attachment_meta );
	}

	/**
	 * @param $post_id
	 */
	function rokka_delete( $post_id ) {
		$this->rokka_helper->delete_image_from_rokka( $post_id );
	}

	/**
	 * Filter whether to skip saving the image file.
	 *
	 * Returning a non-null value will short-circuit the save method,
	 * returning that value instead.
	 *
	 * @since 3.5.0
	 *
	 * @param mixed $override Value to return instead of saving. Default null.
	 * @param string $filename Name of the file to be saved.
	 * @param WP_Image_Editor $image WP_Image_Editor instance.
	 * @param string $mime_type Image mime type.
	 * @param int $post_id Post ID.
	 *
	 * @return null|bool
	 */
	function rokka_save_image_editor_file( $override, $filename, $image, $mime_type, $post_id ) {
		return $override;
	}

}
