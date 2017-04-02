<?php
/**
 * Media Management
 *
 * @package rokka-wordpress-plugin
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
		// add hash field
		$hash = get_post_meta( $post->ID, 'rokka_hash', true );
		$hash_field_info = array(
			'label' => __( 'Rokka Hash', 'rokka' ),
			'value' => $hash,
			'required' => true,
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
			die( esc_html__( 'Image data does not exist. Please re-upload the image.' ) );
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
	<legend>Selection</legend>
	<label><span>X</span>
		<input type="text" id="subjectarea-sel-x-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][x]" value="' . $rokka_subject_area_x . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	<br />
	<label><span>Y</span>
		<input type="text" id="subjectarea-sel-y-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][y]" value="' . $rokka_subject_area_y . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	<div class="nowrap">
	<label><span class="screen-reader-text">selection width</span>
		<input type="text" id="subjectarea-sel-width-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][width]" value="' . $rokka_subject_area_width . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	<span class="subjectarea-separator">&times;</span>
	<label><span class="screen-reader-text">selection height</span>
	<input type="text" id="subjectarea-sel-height-' . $post_id . '" name="attachments[' . $post_id . '][rokka_subject_area][height]" value="' . $rokka_subject_area_height . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	</div>
	<input type="button" onclick="rokkaSubjectAreaEdit.removeSelection(' . $post_id . ')" class="button" value="' . esc_attr__( 'Remove selection' ) . '" />
</fieldset>';

		$subject_area_field_info = array(
			'label' => __( 'Rokka Subject Area', 'rokka' ),
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
			if ( '' === trim( $attachment['rokka_hash'] ) ) {
				// adding our custom error
				$post['errors']['rokka_hash']['errors'][] = __( 'Rokka Hash is required!', 'rokka' );
			} else {
				update_post_meta( $post['ID'], 'rokka_hash', $attachment['rokka_hash'] );
				$hash = $attachment['rokka_hash'];
			}
		}

		if ( isset( $attachment['rokka_subject_area'] ) ) {
			update_post_meta( $post['ID'], 'rokka_subject_area', $attachment['rokka_subject_area'] );

			if ( empty( $hash ) ) {
				$hash = get_post_meta( $post['ID'], 'rokka_hash', true );
			}

			if ( $hash ) {
				$width = intval( $attachment['rokka_subject_area']['width'] );
				$height = intval( $attachment['rokka_subject_area']['height'] );
				$x = intval( $attachment['rokka_subject_area']['x'] );
				$y = intval( $attachment['rokka_subject_area']['y'] );
				if ( $width >= 3 && $height >= 3 ) {
					$new_hash = $this->rokka_helper->save_subject_area( $hash, $x, $y, $width, $height );
				} else {
					// TODO this doesn't work if image doesn't have a subject area yet
					// TODO new hash is somehow not available after this call
					$new_hash = $this->rokka_helper->remove_subject_area( $hash );
				}
				update_post_meta( $post['ID'], 'rokka_hash', $new_hash );
			}
		}

		if ( ! $hash ) {
			return $post;
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
		// add hash column
		$new_columns = array(
			'hash' => __( 'Rokka Hash', 'rokka' ),
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
		// add data to hash column
		if ( 'hash' === $column ) {
			$rokka_hash = get_post_meta( $post_id, 'rokka_hash', true );
			echo esc_html( $rokka_hash );
		}
	}

}
