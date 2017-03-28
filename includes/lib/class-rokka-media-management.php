<?php

/**
 * Class Rokka_Media_Management
 */
class Rokka_Media_Management {

	/**
	 * Rokka_Media_Management constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initializes media management.
	 */
	public function init() {
		add_filter( 'manage_media_columns', array( $this, 'add_custom_media_columns' ), 10, 2 );
		add_action( 'manage_media_custom_column', array( $this, 'print_custom_media_columns_data' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_custom_attachment_edit_fields' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_custom_attachment_fields' ), 10, 2 );
	}

	/**
	 * Adds custom attachment fields to edit screen
	 * Source: https://code.tutsplus.com/articles/creating-custom-fields-for-attachments-in-wordpress--net-13076
	 *
	 * @param array   $form_fields An array of attachment form fields.
	 * @param WP_Post $post        The WP_Post attachment object.
	 * @return array
	 */
	public function add_custom_attachment_edit_fields( $form_fields, $post ) {
		// add hash field
		$hash = get_post_meta( $post->ID, 'rokka_hash', true );
		$hash_field_info = array(
			'label' => __( 'Rokka Hash', 'rokka' ),
			'value' => $hash,
			'required' => true,
		);
		if( array_key_exists( 'rokka_hash', $form_fields ) ) {
			array_merge( $form_fields['rokka_hash'], $hash_field_info );
		} else {
			$form_fields['rokka_hash'] = $hash_field_info;
		}

		// add subject area field
		$subject_area = get_post_meta( $post->ID, 'rokka_subject_area', true );
		$attachment_src = wp_get_attachment_image_src( $post->ID, 'full' );
		print_r($attachment_src);

		$post_id = $post->ID;
		$nonce = wp_create_nonce("rokka-subjectarea-edit-$post_id");

		$attachment_width = $attachment_src[1];
		$attachment_height = $attachment_src[2];
		if ( isset( $attachment_width, $attachment_height ) )
			$big = max( $attachment_width, $attachment_height );
		else
			die( __('Image data does not exist. Please re-upload the image.') );

		$sizer = $big > 400 ? 400 / $big : 1;

		$html = '';
		$html .= '<input type="text" id="subjectarea-selection-' . $post_id . '" value="" />';
		$html .= '<input type="hidden" id="subjectarea-sizer-' . $post_id . '" value="'. $sizer . '" />';
		$html .= '<input type="hidden" id="subjectarea-original-width-' . $post_id . '" value="' . ( isset( $attachment_width ) ? $attachment_width : 0 ) . '" />';
		$html .= '<input type="hidden" id="subjectarea-original-height-' . $post_id . '" value="' . ( isset( $attachment_height ) ? $attachment_height : 0 ) . '" />';

		$html .= "
<div id='subjectarea-{$post_id}' class='subjectarea-wrap'>
	<img id='image-subjectarea-preview-{$post_id}' src='{$attachment_src[0]}' />
</div>
<input type='text' name='attachments[{$post_id}][subjectarea]' id='attachments[{$post_id}][subjectarea]' />
";

		$html .= '
<fieldset id="subjectarea-sel-' . $post_id . '" class="subjectarea-sel">
	<legend>Selection</legend>
	<label><span class="screen-reader-text">selection x</span>
		<input type="text" id="subjectarea-sel-x-' . $post_id . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	<label><span class="screen-reader-text">selection x</span>
		<input type="text" id="subjectarea-sel-y-' . $post_id . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	<div class="nowrap">
	<label><span class="screen-reader-text">selection width</span>
		<input type="text" id="subjectarea-sel-width-' . $post_id . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	<span class="subjectarea-separator">&times;</span>
	<label><span class="screen-reader-text">selection height</span>
	<input type="text" id="subjectarea-sel-height-' . $post_id . '" onkeyup="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" onblur="rokkaSubjectAreaEdit.setNumSelection(' . $post_id . ', this)" />
	</label>
	</div>
</fieldset>';

		$html .= "
<script>
jQuery( document ).ready( function () {
	rokkaSubjectAreaEdit.imgLoaded('{$post_id}');
});
</script>
";

		$subject_area_field_info = array(
			'label' => __( 'Rokka Subject Area', 'rokka' ),
			'input' => 'html',
			'html' => $html
		);
		if( array_key_exists( 'rokka_subject_area', $form_fields ) ) {
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
		// save hash field
		if( isset( $attachment['rokka_hash'] ) ){
			if ( '' === trim( $attachment['rokka_hash'] ) ){
				// adding our custom error
				$post['errors']['rokka_hash']['errors'][] = __( 'Rokka Hash is required!', 'rokka' );
			} else {
				update_post_meta( $post['ID'], 'rokka_hash', $attachment['rokka_hash']);
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
