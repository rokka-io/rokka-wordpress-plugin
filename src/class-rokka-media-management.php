<?php
/**
 * Media Management
 *
 * @package rokka-image-cdn
 */

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
		add_filter( 'manage_media_columns', array( $this, 'add_custom_media_columns' ), 10, 2 );
		add_action( 'manage_media_custom_column', array( $this, 'print_custom_media_columns_data' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_hash_edit_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_subject_area_edit_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_custom_attachment_fields' ), 10, 2 );

		// handle admin list actions
		add_action( 'admin_action_rokka_delete_image', array( $this, 'delete_image' ) );
		add_action( 'admin_action_rokka_upload_image', array( $this, 'upload_image' ) );

		// display all notices after executing list actions
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Adds hash as custom attachment field
	 * Source: https://code.tutsplus.com/articles/creating-custom-fields-for-attachments-in-wordpress--net-13076
	 *
	 * @param array   $form_fields An array of attachment form fields.
	 * @param WP_Post $post        The WP_Post attachment object.
	 * @return array
	 */
	public function add_attachment_hash_edit_field( $form_fields, $post ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post->ID ) ) {
			return $form_fields;
		}

		// add hash field
		$hash = get_post_meta( $post->ID, 'rokka_hash', true );
		$hash_field_info = array(
			'label' => __( 'Rokka Hash', 'rokka-image-cdn' ),
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
	 * @param array   $form_fields An array of attachment form fields.
	 * @param WP_Post $post        The WP_Post attachment object.
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
		<label><span>' . esc_html__( 'Offset X', 'rokka-image-cdn' ) . '</span>
			<input type="text" id="subjectarea-sel-x-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][x]" value="' . $rokka_subject_area_x . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
		<label><span>' . esc_html__( 'Offset Y', 'rokka-image-cdn' ) . '</span>
			<input type="text" id="subjectarea-sel-y-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][y]" value="' . $rokka_subject_area_y . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
	</div>
	<div class="nowrap">
		<label><span>' . esc_html__( 'Width', 'rokka-image-cdn' ) . '</span>
			<input type="text" id="subjectarea-sel-width-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][width]" value="' . $rokka_subject_area_width . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
		<label><span>' . esc_html__( 'Height', 'rokka-image-cdn' ) . '</span>
		<input type="text" id="subjectarea-sel-height-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][height]" value="' . $rokka_subject_area_height . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
		</label>
	</div>
	<div>
		<span id="subjectarea-info-' . $post_id . '"></span>
	</div>
	<input type="button" onclick="rokkaSubjectAreaEdit.removeSelection(' . $post_id . ')" class="button" value="' . esc_attr__( 'Remove selection', 'rokka-image-cdn' ) . '" />
</fieldset>';

		$subject_area_field_info = array(
			'label' => __( 'Rokka Subject Area', 'rokka-image-cdn' ),
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
	function save_custom_attachment_fields( $post, $attachment ) {
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
			if ( $width >= 3 && $height >= 3 ) {
				$new_hash = $this->rokka_helper->save_subject_area( $hash, $x, $y, $width, $height );
			} else {
				$new_hash = $this->rokka_helper->remove_subject_area( $hash );
			}
			update_post_meta( $post['ID'], 'rokka_hash', $new_hash );
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
	public function add_custom_media_columns( $posts_columns, $detached ) {
		// add rokka column
		$new_columns = array(
			'rokka' => __( 'Rokka', 'rokka-image-cdn' ),
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
				$output .= '<form method="POST" action="' . esc_url( admin_url( 'admin.php' ) ) . '" >';
				if ( $this->rokka_helper->is_on_rokka( $post_id ) ) {
					$output .= 'synced to rokka!';
					$output .= '<input type="hidden" name="action" value="rokka_delete_image" />';
					$output .= '<input type="hidden" name="rokka_delete_image_id" value="' . esc_attr( $post_id ) . '" />';
					$output .= wp_nonce_field( 'rokka_delete_image_' . $post_id, '_wpnonce', true, false );
					$output .= get_submit_button( esc_html__( 'Delete image', 'rokka-image-cdn' ), 'delete', 'submit-delete-' . $post_id );
				} else {
					$output .= 'not yet no rokka :(';
					$output .= '<input type="hidden" name="action" value="rokka_upload_image" />';
					$output .= '<input type="hidden" name="rokka_upload_image_id" value="' . esc_attr( $post_id ) . '" />';
					$output .= wp_nonce_field( 'rokka_upload_image_' . $post_id, '_wpnonce', true, false );
					$output .= get_submit_button( esc_html__( 'Upload image', 'rokka-image-cdn' ), 'primary', 'submit-upload-' . $post_id );
				}
				$output .= '</form>';
			} else {
				$output .= esc_html__( 'This mime type is not supported on rokka', 'rokka-image-cdn' );
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
		if ( ! isset( $_REQUEST['rokka_delete_image_id'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		$post_id = intval( $_REQUEST['rokka_delete_image_id'] );
		check_admin_referer( 'rokka_delete_image_' . $post_id );

		$this->rokka_helper->delete_image_from_rokka( $post_id );

		$this->store_message_in_notices_option( 'Image ' . $post_id . ' was successfully removed from rokka.' );

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Uploads image to rokka.
	 */
	public function upload_image() {
		if ( ! isset( $_REQUEST['rokka_upload_image_id'] ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		$post_id = intval( $_REQUEST['rokka_upload_image_id'] );
		check_admin_referer( 'rokka_upload_image_' . $post_id );

		$this->rokka_helper->upload_image_to_rokka( $post_id );

		$this->store_message_in_notices_option( 'Image ' . $post_id . ' was successfully uploaded to rokka.' );

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Stores message in option to print it out after redirect
	 *
	 * @param string $message Message which should be stored.
	 * @param string $type Message type (error, warning, success, info).
	 *
	 * @return bool True if message was stored successfully.
	 */
	protected function store_message_in_notices_option( $message, $type = 'success' ) {
		if ( ! empty( $message ) ) {
			// store message in option array
			$notices = get_option( 'rokka_notices' );
			$notices[ $type ][] = $message;

			return update_option( 'rokka_notices', $notices );
		}

		return false;
	}

	/**
	 * Displays all admin notices
	 *
	 * @return bool
	 */
	public function show_admin_notices() {
		$notices = get_option( 'rokka_notices' );
		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return '';
		}

		// print all messages
		foreach ( $notices as $type => $messages ) {
			foreach ( $messages as $notice ) {
				echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
			}
		}

		return delete_option( 'rokka_notices' );
	}

}
