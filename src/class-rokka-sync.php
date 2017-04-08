<?php
/**
 * Rokka synchronization
 *
 * @package rokka-image-cdn
 */

/**
 * Class Rokka_Sync
 */
class Rokka_Sync {

	/**
	 * Rokka helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Rokka_Sync constructor.
	 *
	 * @param Rokka_Helper $rokka_helper Rokka Helper.
	 */
	public function __construct( Rokka_Helper $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		$this->init();
	}

	/**
	 * Initializes Rokka_Sync class.
	 */
	protected function init() {
		add_action( 'add_attachment', array( $this, 'rokka_upload' ), 10, 1 );
		add_filter( 'update_attached_file', array( $this, 'rokka_update' ), 10, 2 );
		add_action( 'delete_attachment', array( $this, 'rokka_delete' ), 10, 1 );
		add_filter( 'wp_save_image_editor_file', array( $this, 'rokka_save_image_editor_file' ), 10, 5 );

		add_action( 'wp_ajax_rokka_upload_image', array( $this, 'ajax_rokka_upload_image' ) );
		add_action( 'wp_ajax_rokka_create_stacks', array( $this, 'ajax_rokka_create_stacks' ) );
	}

	/**
	 * Handle upload of image to Rokka.
	 *
	 * @param integer $attachment_id Attachment id.
	 */
	public function rokka_upload( $attachment_id ) {
		$this->rokka_helper->upload_image_to_rokka( $attachment_id );
	}

	/**
	 * Updates file on rokka.
	 *
	 * @param string $file          Path to the attached file to update.
	 * @param int    $attachment_id Attachment ID.
	 *
	 * @return bool
	 */
	public function rokka_update( $file, $attachment_id ) {
		// This check is also needed that this function is not executed when the attachment is added (add_attachment action)
		if ( ! $this->rokka_helper->is_on_rokka( $attachment_id ) ) {
			return $file;
		}

		// delete old image on rokka before uploading new one
		$this->rokka_helper->delete_image_from_rokka( $attachment_id );

		// upload new file to rokka
		$this->rokka_helper->upload_image_to_rokka( $attachment_id, $file );
		return $file;
	}

	/**
	 * Deletes an image on Rokka.
	 *
	 * @param int $post_id Attachment id.
	 *
	 * @return bool
	 */
	public function rokka_delete( $post_id ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
			return false;
		}

		return $this->rokka_helper->delete_image_from_rokka( $post_id );
	}

	/**
	 * Filter whether to skip saving the image file.
	 *
	 * Returning a non-null value will short-circuit the save method,
	 * returning that value instead.
	 *
	 * @since 3.5.0
	 *
	 * @param mixed           $override Value to return instead of saving. Default null.
	 * @param string          $filename Name of the file to be saved.
	 * @param WP_Image_Editor $image WP_Image_Editor instance.
	 * @param string          $mime_type Image mime type.
	 * @param int             $post_id Post ID.
	 *
	 * @return null|bool
	 */
	public function rokka_save_image_editor_file( $override, $filename, $image, $mime_type, $post_id ) {
		return $override;
	}

	/**
	 * Upload image to Rokka (rokka_upload_image ajax endpoint)
	 */
	public function ajax_rokka_upload_image() {
		$nonce_valid = check_ajax_referer( 'rokka-settings', 'nonce', false );

		if ( ! $nonce_valid ) {
			wp_send_json_error( __( 'Permission denied! There was something wrong with the nonce.', 'rokka-image-cdn' ), 403 );
			wp_die();
		}

		try {
			if ( isset( $_POST['image_id'] ) ) {
				$image_id = intval( $_POST['image_id'] );

				if ( ! $this->rokka_helper->is_on_rokka( $image_id ) ) {
					$upload_success = $this->rokka_helper->upload_image_to_rokka( $image_id );

					if ( $upload_success ) {
						wp_send_json_success( $image_id );
					} else {
						wp_send_json_error( $image_id, 400 );
					}
				} else {
					wp_send_json_error( __( 'This image is already on Rokka. No need to upload it another time.', 'rokka-image-cdn' ), 400 );
				}
			} else {
				wp_send_json_error( __( 'image_id parameter missing.', 'rokka-image-cdn' ), 400 );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 400 );
		}

		wp_die();
	}

	/**
	 * Get all images which are not yet uploaded to Rokka.
	 *
	 * @return array Array with ids of images.
	 */
	public function get_images_for_upload() {
		$image_ids = $this->get_all_images();

		$image_ids = array_filter( $image_ids, function ( $image_id ) {
			return ! $this->rokka_helper->is_on_rokka( $image_id );
		} );

		return $image_ids;
	}

	/**
	 * Get all images from database.
	 *
	 * @return array Array with ids of images.
	 */
	private function get_all_images() {
		$query_images_args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		);

		$query_images = new WP_Query( $query_images_args );

		return $query_images->posts;
	}

	/**
	 * Creates stacks on Rokka.
	 */
	public function ajax_rokka_create_stacks() {
		$nonce_valid = check_ajax_referer( 'rokka-settings', 'nonce', false );

		if ( ! $nonce_valid ) {
			wp_send_json_error( __( 'Permission denied! There was something wrong with the nonce.', 'rokka-image-cdn' ), 403 );
			wp_die();
		}

		$sizes = $this->rokka_helper->rokka_create_stacks();

		if ( $sizes ) {
			wp_send_json_success( $sizes );
			wp_die();
		}

		wp_send_json_error( __( 'Could not process stacks.', 'rokka-image-cdn' ), 400 );
		wp_die();
	}

}
