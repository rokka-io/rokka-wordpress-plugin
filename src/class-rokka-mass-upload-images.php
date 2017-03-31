<?php
/**
 * Image mass uploader
 *
 * @package WordPress
 * @subpackage rokka-wordpress-plugin
 */

/**
 * Class Rokka_Mass_Upload_Images
 */
class Rokka_Mass_Upload_Images {


	/**
	 * Rokka helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;


	/**
	 * Rokka_Mass_Upload_Images constructor.
	 *
	 * @param Rokka_Helper $rokka_helper Rokka helper.
	 */
	public function __construct( Rokka_Helper $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		add_action( 'wp_ajax_rokka_upload_image', array( $this, 'rokka_upload_image' ) );

	}

	/**
	 * Upload image to Rokka
	 */
	function rokka_upload_image() {
		try {
			$image_id = $_POST['id'];

			if ( empty( get_post_meta( $image_id, 'rokka_info', true ) ) ) {
				$image_data = wp_get_attachment_metadata( $image_id );

				$data = $this->rokka_helper->upload_image_to_rokka( $image_id, $image_data );

				if ( $data ) {
					wp_send_json_success( $image_id );
				} else {
					wp_send_json_error( $data );
				}
			} else {
				wp_send_json_error( __( 'This image is already on rokka. No need to upload it another time.', 'rokka-image-cdn' ) );
			}
			wp_die(); // this is required to terminate immediately and return a proper response

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
			wp_die();
		}
	}

	/**
	 * Findes all images which are not yet uploaded to Rokka.
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
}
