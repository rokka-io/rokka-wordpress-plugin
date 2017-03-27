<?php
/**
 * Class Rokka_Media_Management
 */
class Rokka_Media_Management {

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_filter( 'manage_media_columns', array( $this, 'add_rokka_hash_to_media_columns' ), 10, 2 );
		add_action( 'manage_media_custom_column', array( $this, 'add_hash_column_data' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_hash_edit_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_hash_field' ), 10, 2 );
	}

	function save_hash_field($post, $attachment) {
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

	public function add_hash_edit_field( $form_fields, $post ) {
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

	public function add_rokka_hash_to_media_columns( $posts_columns, $detached ) {
		$new_column = array(
			'hash' => __( 'Rokka Hash', 'rokka' ),
		);
		return array_merge( $posts_columns, $new_column );
	}

	/**
	 * Prints data to hash column
	 *
	 * @param string $column Name of custom column.
	 * @param int    $post_id Id of current post.
	 */
	public function add_hash_column_data( $column, $post_id ) {
		if ( 'hash' === $column ) {
			$rokka_info = get_post_meta( $post_id, 'rokka_info', true );
			echo esc_html( $rokka_info['hash'] );
		}
	}

}
