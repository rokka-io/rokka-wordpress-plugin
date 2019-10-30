<?php
/**
 * Attachment
 *
 * @package rokka-integration
 */

namespace Rokka_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Attachment
 */
class Rokka_Attachment {

	/**
	 * Rokka helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Rokka_Attachment constructor.
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
		add_action( 'add_attachment', array( $this, 'rokka_upload' ), 10, 1 );
		add_filter( 'update_attached_file', array( $this, 'rokka_update' ), 10, 2 );
		add_action( 'delete_attachment', array( $this, 'rokka_delete' ), 10, 1 );

		// Disable big image size threshold (introduced in WordPress 5.3)
		add_filter( 'big_image_size_threshold', '__return_false', 9999 );
	}

	/**
	 * Handle upload of image to rokka.
	 *
	 * @param integer $attachment_id Attachment id.
	 */
	public function rokka_upload( $attachment_id ) {
		try {
			$upload_success = $this->rokka_helper->upload_image_to_rokka( $attachment_id );

			if ( ! $upload_success ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error uploading image %s to rokka.', '%s contains image id', 'rokka-integration' ), $attachment_id ), 'error' );
			}
		} catch ( \Exception $e ) {
			/* translators: %1$s contains image id, %2$s contains error message */
			$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error uploading image %1$s to rokka. Message: %2$s', '%1$s contains image id, %2$s contains error message', 'rokka-integration' ), $attachment_id, $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Updates file on rokka.
	 *
	 * @param string $file          Path to the attached file to update.
	 * @param int    $attachment_id Attachment ID.
	 *
	 * @return string
	 */
	public function rokka_update( $file, $attachment_id ) {
		// This check is also needed that this function is not executed when the attachment is added (add_attachment action)
		if ( ! $this->rokka_helper->is_on_rokka( $attachment_id ) ) {
			return $file;
		}

		try {
			// delete old image on rokka before uploading new one
			$delete_success = $this->rokka_helper->delete_image_from_rokka( $attachment_id );

			if ( ! $delete_success ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error updating image %s on rokka.', '%s contains image id', 'rokka-integration' ), $attachment_id ), 'error' );
			}

			// upload new file to rokka
			$upload_success = $this->rokka_helper->upload_image_to_rokka( $attachment_id, $file );

			if ( ! $upload_success ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error updating image %s on rokka.', '%s contains image id', 'rokka-integration' ), $attachment_id ), 'error' );
			}
		} catch ( \Exception $e ) {
			/* translators: %1$s contains image id, %2$s contains error message */
			$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error updating image %1$s on rokka. Message: %2$s', '%1$s contains image id, %2$s contains error message', 'rokka-integration' ), $attachment_id, $e->getMessage() ), 'error' );
		}

		return $file;
	}

	/**
	 * Deletes an image on rokka.
	 *
	 * @param int $post_id Attachment id.
	 *
	 * @return bool
	 */
	public function rokka_delete( $post_id ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
			return false;
		}

		try {
			$delete_success = $this->rokka_helper->delete_image_from_rokka( $post_id );

			if ( ! $delete_success ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error deleting image %s from rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
			}
			return $delete_success;
		} catch ( \Exception $e ) {
			/* translators: %1$s contains image id, %2$s contains error message */
			$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error deleting image %1$s from rokka. Message: %2$s', '%1$s contains image id, %2$s contains error message', 'rokka-integration' ), $post_id, $e->getMessage() ), 'error' );
		}

		return false;
	}

}
