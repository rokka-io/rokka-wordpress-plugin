<?php
/**
 * Media Management
 *
 * @package rokka-integration
 */

namespace Rokka_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Media_Management
 */
class Rokka_Media_Management {

	/**
	 * Rokka helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Rokka_Media_Management constructor.
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
		// add custom columns to media admin list
		add_filter( 'manage_media_columns', array( $this, 'add_custom_media_columns' ), 10, 2 );
		add_action( 'manage_media_custom_column', array( $this, 'print_custom_media_columns_data' ), 10, 2 );

		// add additional fields to attachment edit
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_hash_edit_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_subject_area_edit_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_custom_attachment_fields' ), 10, 2 );

		// handle admin list actions
		add_action( 'admin_action_rokka_delete_image', array( $this, 'delete_image' ) );
		add_action( 'admin_action_rokka_upload_image', array( $this, 'upload_image' ) );

		// handle bulk actions
		add_filter( 'bulk_actions-upload', array( $this, 'add_bulk_actions' ), 10, 1 );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_upload_bulk_action' ), 10, 3 );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_delete_bulk_action' ), 10, 3 );
	}

	/**
	 * Adds hash as custom attachment field
	 * Source: https://code.tutsplus.com/articles/creating-custom-fields-for-attachments-in-wordpress--net-13076
	 *
	 * @param array    $form_fields An array of attachment form fields.
	 * @param \WP_Post $post        The WP_Post attachment object.
	 * @return array
	 */
	public function add_attachment_hash_edit_field( $form_fields, $post ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post->ID ) ) {
			return $form_fields;
		}

		// add hash field
		$hash = get_post_meta( $post->ID, 'rokka_hash', true );
		$hash_field_info = array(
			'label' => __( 'Rokka Hash', 'rokka-integration' ),
			'value' => $hash,
		);
		if ( array_key_exists( 'rokka_hash', $form_fields ) ) {
			array_merge( $form_fields['rokka_hash'], $hash_field_info );
		} else {
			$form_fields['rokka_hash'] = $hash_field_info;
		}

		return $form_fields;
	}

	/**
	 * Adds subject area as custom attachment field
	 * Source: https://code.tutsplus.com/articles/creating-custom-fields-for-attachments-in-wordpress--net-13076
	 *
	 * @param array    $form_fields An array of attachment form fields.
	 * @param \WP_Post $post        The WP_Post attachment object.
	 * @return array
	 */
	public function add_attachment_subject_area_edit_field( $form_fields, $post ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post->ID ) ) {
			return $form_fields;
		}

		$rokka_subject_area = get_post_meta( $post->ID, 'rokka_subject_area', true );
		$rokka_subject_area_x = '';
		$rokka_subject_area_y = '';
		$rokka_subject_area_width = '';
		$rokka_subject_area_height = '';
		if ( is_array( $rokka_subject_area ) ) {
			$rokka_subject_area_x = $rokka_subject_area['x'];
			$rokka_subject_area_y = $rokka_subject_area['y'];
			$rokka_subject_area_width = $rokka_subject_area['width'];
			$rokka_subject_area_height = $rokka_subject_area['height'];
		}
		$attachment_src = wp_get_attachment_image_src( $post->ID, 'full' );

		$post_id = $post->ID;

		$attachment_width = $attachment_src[1];
		$attachment_height = $attachment_src[2];
		if ( isset( $attachment_width, $attachment_height ) ) {
			$big = max( $attachment_width, $attachment_height );
		} else {
			// Image data does not exist
			return $form_fields;
		}

		$sizer = $big > 400 ? 400 / $big : 1;

		$html = '';
		$html .= '<input type="hidden" id="subjectarea-sizer-' . $post_id . '" value="' . $sizer . '" />';
		$html .= '<input type="hidden" id="subjectarea-original-width-' . $post_id . '" value="' . ( isset( $attachment_width ) ? $attachment_width : 0 ) . '" />';
		$html .= '<input type="hidden" id="subjectarea-original-height-' . $post_id . '" value="' . ( isset( $attachment_height ) ? $attachment_height : 0 ) . '" />';

		$html .= "
<div id='subjectarea-{$post_id}' class='subjectarea-wrap'>
	<img id='image-subjectarea-preview-{$post_id}' onload='rokkaSubjectAreaEdit.init({$post_id})' src='{$attachment_src[0]}' />
</div>
";

		$html .= '
<fieldset id="subjectarea-sel-' . $post_id . '" class="subjectarea-sel">
	<div class="nowrap">
		<label><span>' . esc_html__( 'Offset X', 'rokka-integration' ) . '</span>
			<input type="text" id="subjectarea-sel-x-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][x]" value="' . $rokka_subject_area_x . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
		<label><span>' . esc_html__( 'Offset Y', 'rokka-integration' ) . '</span>
			<input type="text" id="subjectarea-sel-y-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][y]" value="' . $rokka_subject_area_y . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
	</div>
	<div class="nowrap">
		<label><span>' . esc_html__( 'Width', 'rokka-integration' ) . '</span>
			<input type="text" id="subjectarea-sel-width-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][width]" value="' . $rokka_subject_area_width . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
		<label><span>' . esc_html__( 'Height', 'rokka-integration' ) . '</span>
		<input type="text" id="subjectarea-sel-height-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][height]" value="' . $rokka_subject_area_height . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
	</div>
	<div>
		<span id="subjectarea-info-' . $post_id . '"></span>
	</div>
	<input type="button" onclick="rokkaSubjectAreaEdit.removeSelection(' . $post_id . ')" class="button" value="' . esc_attr__( 'Remove selection', 'rokka-integration' ) . '" />
</fieldset>';

		$subject_area_field_info = array(
			'label' => __( 'Rokka Subject Area', 'rokka-integration' ),
			'input' => 'html',
			'html' => $html,
		);
		if ( array_key_exists( 'rokka_subject_area', $form_fields ) ) {
			array_merge( $form_fields['rokka_subject_area'], $subject_area_field_info );
		} else {
			$form_fields['rokka_subject_area'] = $subject_area_field_info;
		}

		return $form_fields;
	}

	/**
	 * Saves custom attachment fields to database
	 * Source: https://code.tutsplus.com/articles/creating-custom-fields-for-attachments-in-wordpress--net-13076
	 *
	 * @param array $post       An array of post data.
	 * @param array $attachment An array of attachment metadata.
	 * @return array
	 */
	public function save_custom_attachment_fields( $post, $attachment ) {
		$hash = '';

		// save hash field
		if ( isset( $attachment['rokka_hash'] ) ) {
			$rokka_hash = sanitize_text_field( $attachment['rokka_hash'] );
			if ( '' === trim( $rokka_hash ) ) {
				delete_post_meta( $post['ID'], 'rokka_hash' );
			} else {
				update_post_meta( $post['ID'], 'rokka_hash', $rokka_hash );
				$hash = $rokka_hash;
			}
		}

		// stop saving rokka specific fields if rokka hash was removed
		if ( empty( $hash ) ) {
			return $post;
		}

		if ( isset( $attachment['rokka_subject_area'] ) ) {
			update_post_meta( $post['ID'], 'rokka_subject_area', $attachment['rokka_subject_area'] );

			$width = intval( $attachment['rokka_subject_area']['width'] );
			$height = intval( $attachment['rokka_subject_area']['height'] );
			$x = intval( $attachment['rokka_subject_area']['x'] );
			$y = intval( $attachment['rokka_subject_area']['y'] );
			try {
				if ( $width >= 3 && $height >= 3 ) {
					$new_hash = $this->rokka_helper->save_subject_area( $hash, $x, $y, $width, $height );
				} else {
					$new_hash = $this->rokka_helper->remove_subject_area( $hash );
				}
				update_post_meta( $post['ID'], 'rokka_hash', $new_hash );
			} catch ( \Exception $e ) {
				/* translators: %s contains error message */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error saving the subject area. Message: %s', '%s contains error message', 'rokka-integration' ), $e->getMessage() ), 'error' );
			}
		}

		return $post;
	}

	/**
	 * Add custom columns to media list table
	 *
	 * @param array $posts_columns An array of columns displayed in the Media list table.
	 * @param bool  $detached      Whether the list table contains media not attached
	 *                             to any posts. Default true.
	 * @return array
	 */
	public function add_custom_media_columns( $posts_columns, $detached = true ) {
		// add rokka column
		$new_columns = array(
			'rokka' => __( 'Rokka', 'rokka-integration' ),
		);
		return array_merge( $posts_columns, $new_columns );
	}

	/**
	 * Prints data to custom media list columns
	 *
	 * @param string $column  Name of custom column.
	 * @param int    $post_id Id of current post.
	 */
	public function print_custom_media_columns_data( $column, $post_id ) {
		// add data to rokka column
		if ( 'rokka' === $column ) {
			$output = '';
			if ( $this->rokka_helper->is_allowed_mime_type( $post_id ) ) {
				if ( $this->rokka_helper->is_on_rokka( $post_id ) ) {
					$output .= '<p><a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?action=rokka_delete_image&image_id=' . $post_id ), 'rokka_delete_image_' . $post_id ) ) . '" class="button delete rokka-delete-image" title="' . esc_html__( 'Remove image from rokka', 'rokka-integration' ) . '"><span class="dashicons dashicons-trash"></span></a></p>';
				} else {
					$output .= '<p><a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?action=rokka_upload_image&image_id=' . $post_id ), 'rokka_upload_image_' . $post_id ) ) . '" class="button" title="' . esc_attr__( 'Upload image to rokka', 'rokka-integration' ) . '"><span class="dashicons dashicons-upload"></span></a></p>';
				}
			} else {
				$output .= esc_html__( 'MIME type not supported', 'rokka-integration' );
			}

			// @codingStandardsIgnoreStart
			echo $output;
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Deletes image from rokka.
	 */
	public function delete_image() {
		if ( ! isset( $_REQUEST['image_id'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		$post_id = intval( $_REQUEST['image_id'] );
		check_admin_referer( 'rokka_delete_image_' . $post_id );

		try {
			$delete_success = $this->rokka_helper->delete_image_from_rokka( $post_id );

			if ( $delete_success ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'Image %s was successfully deleted from rokka.', '%s contains image id', 'rokka-integration' ), $post_id ) );
			} else {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error deleting image %s from rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
			}
		} catch ( \Exception $e ) {
			/* translators: %1$s contains image id, %2$s contains error message */
			$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error deleting image %1$s from rokka. Message: %2$s', '%1$s contains image id, %2$s contains error message', 'rokka-integration' ), $post_id, $e->getMessage() ), 'error' );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Uploads image to rokka.
	 */
	public function upload_image() {
		if ( ! isset( $_REQUEST['image_id'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		$post_id = intval( $_REQUEST['image_id'] );
		check_admin_referer( 'rokka_upload_image_' . $post_id );

		try {
			$upload_success = $this->rokka_helper->upload_image_to_rokka( $post_id );

			if ( $upload_success ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'Image %s was successfully uploaded to rokka.', '%s contains image id', 'rokka-integration' ), $post_id ) );
			} else {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error uploading image %s to rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
			}
		} catch ( \Exception $e ) {
			/* translators: %1$s contains image id, %2$s contains error message */
			$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error uploading image %1$s to rokka. Message: %2$s', '%1$s contains image id, %2$s contains error message', 'rokka-integration' ), $post_id, $e->getMessage() ), 'error' );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Adds custom bulk actions.
	 *
	 * @since 4.7.0
	 *
	 * @param array $bulk_actions An array of the available bulk actions.
	 *
	 * @return array
	 */
	public function add_bulk_actions( $bulk_actions ) {
		$bulk_actions['rokka_upload'] = __( 'Upload to rokka', 'rokka-integration' );
		$bulk_actions['rokka_delete'] = __( 'Delete from rokka', 'rokka-integration' );
		return $bulk_actions;
	}

	/**
	 * Handles rokka upload bulk action.
	 *
	 * @since 4.7.0
	 *
	 * @param string $redirect_to The redirect URL.
	 * @param string $doaction    The action being taken.
	 * @param array  $post_ids    The items to take the action on.
	 *
	 * @return string
	 */
	public function handle_upload_bulk_action( $redirect_to, $doaction, $post_ids = array() ) {
		if ( 'rokka_upload' !== $doaction ) {
			return $redirect_to;
		}
		$image_count = count( $post_ids );
		foreach ( $post_ids as $post_id ) {
			if ( ! $this->rokka_helper->is_allowed_mime_type( $post_id ) ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'The mime type of attachment %s is not supported on rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
				$image_count--;
			} elseif ( $this->rokka_helper->is_on_rokka( $post_id ) ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'The image %s is already on rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
				$image_count--;
			} else {
				try {
					$upload_success = $this->rokka_helper->upload_image_to_rokka( $post_id );

					if ( ! $upload_success ) {
						/* translators: %s contains image id */
						$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error uploading image %s to rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
					}
				} catch ( \Exception $e ) {
					/* translators: %1$s contains image id, %2$s contains error message */
					$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error uploading image %1$s to rokka. Message: %2$s', '%1$s contains image id, %2$s contains error message', 'rokka-integration' ), $post_id, $e->getMessage() ), 'error' );
				}
			}
		}
		if ( $image_count > 0 ) {
			/* translators: %s contains image count */
			$this->rokka_helper->store_message_in_notices_option( sprintf( _nx( '%s image has been uploaded to rokka.', '%s images have been uploaded to rokka.', $image_count, '%s contains image count', 'rokka-integration' ), $image_count ) );
		}
		return $redirect_to;
	}

	/**
	 * Handles rokka delete bulk action.
	 *
	 * @since 4.7.0
	 *
	 * @param string $redirect_to The redirect URL.
	 * @param string $doaction    The action being taken.
	 * @param array  $post_ids    The items to take the action on.
	 *
	 * @return string
	 */
	public function handle_delete_bulk_action( $redirect_to, $doaction, $post_ids = array() ) {
		if ( 'rokka_delete' !== $doaction ) {
			return $redirect_to;
		}
		$image_count = count( $post_ids );
		foreach ( $post_ids as $post_id ) {
			if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
				/* translators: %s contains image id */
				$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'The image %s is not yet on rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
				$image_count--;
			} else {
				try {
					$delete_success = $this->rokka_helper->delete_image_from_rokka( $post_id );

					if ( ! $delete_success ) {
						/* translators: %s contains image id */
						$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error deleting image %s from rokka.', '%s contains image id', 'rokka-integration' ), $post_id ), 'error' );
					}
				} catch ( \Exception $e ) {
					/* translators: %1$s contains image id, %2$s contains error message */
					$this->rokka_helper->store_message_in_notices_option( sprintf( _x( 'There was an error deleting image %1$s from rokka. Message: %2$s', '%1$s contains image id, %2$s contains error message', 'rokka-integration' ), $post_id, $e->getMessage() ), 'error' );
				}
			}
		}
		if ( $image_count > 0 ) {
			/* translators: %s contains image count */
			$this->rokka_helper->store_message_in_notices_option( sprintf( _nx( '%s image has been deleted from rokka.', '%s images have been deleted from rokka.', $image_count, '%s contains image count', 'rokka-integration' ), $image_count ) );
		}
		return $redirect_to;
	}

}
