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
