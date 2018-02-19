<?php
/**
 * Handles actions from WP REST API.
 *
 * @package rokka-integration
 */

namespace Rokka_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Rest
 */
class Rokka_Rest {

	/**
	 * Rokka helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Rokka_Rest constructor.
	 *
	 * @param Rokka_Helper $rokka_helper Rokka helper.
	 */
	public function __construct( Rokka_Helper $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		$this->init();
	}

	/**
	 * Initializes media management.
	 */
	public function init() {
		// handle changes on attachments
		add_action( 'rest_insert_attachment', array( $this, 'handle_rest_insert' ), 10, 3 );
	}

	/**
	 * Handle upload of image to rokka.
	 *
	 * @param integer $attachment_id Attachment id.
	 *
	 * @return boolean
	 */
	public function rokka_upload( $attachment_id ) {
		try {
			$upload_success = $this->rokka_helper->upload_image_to_rokka( $attachment_id );

			if ( ! $upload_success ) {
				return false;
			}
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Updates file on rokka.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return boolean
	 */
	public function rokka_update( $attachment_id ) {
		// Do noting if attachment isn't on rokka
		if ( ! $this->rokka_helper->is_on_rokka( $attachment_id ) ) {
			return true;
		}

		try {
			// delete old image on rokka before uploading new one
			$delete_success = $this->rokka_helper->delete_image_from_rokka( $attachment_id );

			if ( ! $delete_success ) {
				return false;
			}

			// upload new file to rokka
			$upload_success = $this->rokka_helper->upload_image_to_rokka( $attachment_id );

			if ( ! $upload_success ) {
				return false;
			}
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Handles an attachment after it was created or updated via the REST API.
	 *
	 * @since 4.7.0
	 *
	 * @param \WP_Post         $attachment Inserted or updated attachment
	 *                                     object.
	 * @param \WP_REST_Request $request    The request sent to the API.
	 * @param bool             $creating   True when creating an attachment, false when updating.
	 */
	public function handle_rest_insert( $attachment, $request, $creating ) {
		if ( $creating ) {
			$this->rokka_upload( $attachment->ID );
		} else {
			$this->rokka_update( $attachment->ID );
		}
	}

}
