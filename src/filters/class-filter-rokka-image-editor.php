<?php
/**
 * Image editor
 *
 * @package WordPress\rokka-wordpress-plugin
 */

/**
 * Class Filter_Rokka_Image_Editor
 */
class Filter_Rokka_Image_Editor {
	/**
	 * Rokka client
	 *
	 * @var \Rokka\Client\Image
	 */
	private $rokka_client;

	/**
	 * Filter_Rokka_Image_Editor constructor.
	 *
	 * @param Rokka_Helper $rokka_helper Rokka helper.
	 */
	function __construct( $rokka_helper ) {
		$this->rokka_client = $rokka_helper->rokka_get_client();
		$this->init();
	}

	/**
	 * Initializes image editor.
	 */
	function init() {
		add_filter( 'wp_image_editor_before_change', array( $this, 'save_image_editor_changes' ), 10, 2 );
		add_filter( 'update_attached_file', array( $this, 'handle_image_restore' ), 10, 2 );
	}

	/**
	 * Apply each change in image editor to Rokka image
	 *
	 * @param WP_Image_Editor $image   WP_Image_Editor instance.
	 * @param array           $changes Array of change operations.
	 * @return WP_Image_Editor
	 */
	public function save_image_editor_changes( $image, $changes ) {
		// check if it's a save request
		if ( ! empty( $_REQUEST['do'] ) && 'save' === $_REQUEST['do'] && ! empty( $_REQUEST['postid'] ) ) {
			$post_id = $_REQUEST['postid'];
			$hash = get_post_meta( $post_id, 'rokka_hash', true );

			if ( ! $hash ) {
				return $image;
			}

			// apply each change to Rokka image
			foreach ( $changes as $operation ) {
				switch ( $operation->type ) {
					case 'rotate':
						$angle = $operation->angle;
						if ( 0 !== $angle ) {
							if ( $angle > 0 ) {
								// clockwise rotation in wp is done in negative angles
								$angle -= 360;
							}
							$angle = abs( $angle );
							// TODO implement Rokka API call to do rotation
						}
						break;
					case 'crop':
						$sel = $operation->sel;
						$subject_area = new Rokka\Client\Core\DynamicMetadata\SubjectArea( $sel->x, $sel->y, $sel->w, $sel->h );
						$hash = $this->rokka_client->setDynamicMetadata( $subject_area, $hash );
						update_post_meta( $post_id, 'rokka_hash', $hash );
						break;
				}
			}
		}
		return $image;
	}

	/**
	 * Handles restore of original image.
	 *
	 * @param string $file          Path to the attached file to update.
	 * @param int    $attachment_id Attachment ID.
	 * @return string
	 */
	public function handle_image_restore( $file, $attachment_id ) {
		$hash = get_post_meta( $attachment_id, 'rokka_hash', true );

		// if file is not stored in Rokka do nothing
		if ( ! $hash ) {
			return $file;
		}

		$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
		$is_restore = false;
		if ( ! empty( $backup_sizes ) && isset( $backup_sizes['full-orig'], $file ) ) {
			// if filename is the same as the original filename it's a restore
			$is_restore = basename( $file ) === $backup_sizes['full-orig']['file'] ;
		}
		// remove custom metadata from Rokka image on restore
		if ( $is_restore ) {
			// remove subject area
			$hash = $this->rokka_client->deleteDynamicMetadata( 'SubjectArea', $hash );
			update_post_meta( $attachment_id, 'rokka_hash', $hash );
		}

		return $file;
	}

}
