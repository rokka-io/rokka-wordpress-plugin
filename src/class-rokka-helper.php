<?php
/**
 * Rokka helper class.
 *
 * @package rokka-wordpress-plugin
 */

/**
 * Class Rokka_Helper
 */
class Rokka_Helper {

	/**
	 * Rokka baseurl.
	 *
	 * @var string
	 */
	const ROKKA_URL = 'https://api.rokka.io';

	/**
	 * List of allowed mime types.
	 *
	 * @var array
	 */
	const ALLOWED_MIME_TYPES = [ 'image/gif', 'image/jpg', 'image/jpeg', 'image/png' ];

	/**
	 * Stack name of original image.
	 *
	 * @var string
	 */
	const FULL_SIZE_STACK_NAME = 'full';

	/**
	 * Rokka_Helper constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_rokka_create_stacks', array( $this, 'rokka_ajax_create_stacks' ) );
	}


	/**
	 * Returns Rokka image client.
	 *
	 * @return \Rokka\Client\Image
	 */
	public function rokka_get_client() {
		return \Rokka\Client\Factory::getImageClient( get_option( 'rokka_company_name' ), get_option( 'rokka_api_key' ), get_option( 'rokka_api_secret' ) );
	}


	/**
	 * Uploads file to Rokka.
	 *
	 * @param int   $post_id Attachment id.
	 * @param array $data Attachment metadata.
	 *
	 * @return array|bool $data
	 */
	public function upload_image_to_rokka( $post_id, $data ) {
		$this->validate_files_before_upload( $post_id );
		$file_paths  = $this->get_attachment_file_paths( $post_id, true, $data );
		$client      = $this->rokka_get_client();
		$file_parts   = explode( '/', $file_paths['full'] );
		$file_name    = array_pop( $file_parts );
		$source_image = $client->uploadSourceImage( file_get_contents( $file_paths['full'] ), $file_name );

		if ( is_object( $source_image ) ) {
			$source_images = $source_image->getSourceImages();
			$source_image  = array_pop( $source_images );
			$rokka_info = array(
				'hash'                => $source_image->hash,
				'format'              => $source_image->format,
				'organization'        => $source_image->organization,
				'link'                => $source_image->link,
				'created'             => $source_image->created,
			);
			update_post_meta( $post_id, 'rokka_info', $rokka_info );
			update_post_meta( $post_id, 'rokka_hash', $source_image->hash );

			return $data;
		}

		return false;
	}


	/**
	 * Deletes an image from rokka.io
	 *
	 * @param int $post_id Attachment id.
	 *
	 * @return bool
	 */
	public function delete_image_from_rokka( $post_id ) {
		$hash = get_post_meta( $post_id, 'rokka_hash', true );

		if ( $hash ) {
			$client = $this->rokka_get_client();
			return $client->deleteSourceImage( $hash );
		}

		return false;
	}

	/**
	 * Validates file before it gets uploaded to Rokka.
	 *
	 * @param int $post_id Attachment id.
	 *
	 * @return bool
	 *
	 * @throws Exception Exception on failure.
	 */
	private function validate_files_before_upload( $post_id ) {
		//the meta stuff should be possible here too
		$file_path     = get_attached_file( $post_id, true );
		$type          = get_post_mime_type( $post_id );
		$allowed_types = self::ALLOWED_MIME_TYPES;

		// check mime type of file is in allowed rokka mime types
		if ( ! in_array( $type, $allowed_types, true ) ) {
			/* translators: %s contains mime type */
			$error_msg = sprintf( esc_html_x( 'Mime type %s is not allowed in rokka', '%s contains mime type', 'rokka-image-cdn' ), $type );
			throw new Exception( $error_msg );
		}

		// Check file exists locally before attempting upload
		if ( ! file_exists( $file_path ) ) {
			/* translators: %s contains file path */
			$error_msg = sprintf( esc_html_x( 'File %s does not exist', '%s contains file path','rokka-image-cdn' ), $file_path );
			throw new Exception( $error_msg );
		}

		return true;
	}

	/**
	 * Get file paths for all attachment versions.
	 *
	 * @param int        $attachment_id Attachment id.
	 * @param bool       $exists_locally If file exists locally.
	 * @param array|bool $meta Attachment meta data of false.
	 * @param bool       $include_backups If backup sizes should be included.
	 *
	 * @return array
	 */
	function get_attachment_file_paths( $attachment_id, $exists_locally = true, $meta = false, $include_backups = true ) {
		$paths     = array();
		$file_path = get_attached_file( $attachment_id, true );
		$file_name = basename( $file_path );
		$backups   = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

		if ( ! $meta ) {
			$meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		}

		if ( is_wp_error( $meta ) ) {
			return $paths;
		}

		$original_file = $file_path; // Not all attachments will have meta

		if ( isset( $meta['file'] ) ) {
			$original_file = str_replace( $file_name, basename( $meta['file'] ), $file_path );
		}

		// Original file
		$paths['full'] = $original_file;

		// Sizes
		if ( isset( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size => $file ) {
				if ( isset( $file['file'] ) ) {
					$paths[ $size ] = str_replace( $file_name, $file['file'], $file_path );
				}
			}
		}

		// Thumb
		if ( isset( $meta['thumb'] ) ) {
			$paths[] = str_replace( $file_name, $meta['thumb'], $file_path );
		}

		// Backups
		if ( $include_backups && is_array( $backups ) ) {
			foreach ( $backups as $backup ) {
				$paths[] = str_replace( $file_name, $backup['file'], $file_path );
			}
		}

		// Allow other processes to add files to be uploaded
		$paths = apply_filters( 'rokka_attachment_file_paths', $paths, $attachment_id, $meta );

		// Remove duplicates
		$paths = array_unique( $paths );

		// Remove paths that don't exist
		if ( $exists_locally ) {
			foreach ( $paths as $key => $path ) {
				if ( ! file_exists( $path ) ) {
					unset( $paths[ $key ] );
				}
			}
		}

		return $paths;
	}

	/**
	 * Creates stacks on Rokka.
	 */
	function rokka_ajax_create_stacks() {
		$sizes = $this->rokka_create_stacks();

		if ( $sizes ) {
			wp_send_json_success( $sizes );
			wp_die();
		}

		wp_send_json_error( array(
			'error' => 'could not process stacks',
		) );
		wp_die();
	}

	/**
	 * Creates and checks stacks on rokka if they don't already exist.
	 *
	 * @return array
	 */
	function rokka_create_stacks() {
		$sizes           = $this->list_thumbnail_sizes();
		$client          = $this->rokka_get_client();
		$stacks          = $client->listStacks();
		//create a noop stack if it not exists already
		try {
			$client->getStack( $this->get_rokka_full_size_stack_name() );
		} catch ( Exception $e ) {
			$resize_noop = new \Rokka\Client\Core\StackOperation( 'noop' );
			$client->createStack( $this->get_rokka_full_size_stack_name(), [ $resize_noop ] );
		}
		if ( ! empty( $sizes ) ) {
			foreach ( $sizes as $name => $size ) {
				$continue = true;
				$delete   = false;
				foreach ( $stacks->getStacks() as $stack ) {

					if ( $stack->name == $name ) {
						$continue = false;

						//check if the max width was changed in the meantime (in wordpress config)
						$stack_operations = $stack->stackOperations;
						foreach ( $stack_operations as $operation ) {
							$operation = $operation->toArray();

							if ( $operation['name'] == 'resize' ) {
								$stack_width  = $operation['options']['width'];
								$stack_height = $operation['options']['height'];
								if ( $stack_width != $size[0] || $stack_height != $size[1] ) {

									$continue = true;
									$delete   = true;
								}
							}
						}

						continue;
					}
				}

				if ( $continue && $size[0] > 0 ) {
					if ( $delete ) {
						$client->deleteStack( $name );
					}
					$resize = new \Rokka\Client\Core\StackOperation( 'resize', [
						'width'   => $size[0],
						'height'  => $size[1],
						//aspect ratio will be kept
						'mode'    => 'box',
						'upscale' => false
					] );

					$client->createStack( $name, [ $resize ] );
				}
			}
		}

		return $sizes;
	}

	/**
	 * Lists all thumbnail sizes.
	 *
	 * @return array
	 */
	public function list_thumbnail_sizes() {
		global $_wp_additional_image_sizes;
		$sizes  = array();
		$r_sizes = array();
		foreach ( get_intermediate_image_sizes() as $s ) {
			$sizes[ $s ] = array( 0, 0 );
			if ( in_array( $s, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $s ][0] = get_option( $s . '_size_w' ) ?: 768;
				$sizes[ $s ][1] = get_option( $s . '_size_h' ) ?: 10000;
			} else {
				if ( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $s ] ) ) {
					$sizes[ $s ] = array(
						$_wp_additional_image_sizes[ $s ]['width'],
						$_wp_additional_image_sizes[ $s ]['height'],
					);
				}
			}
		}
		foreach ( $sizes as $size => $atts ) {
			$r_sizes[ $size ] = $atts;
		}

		return $r_sizes;
	}


	/**
	 * @return string
	 */
	public function get_rokka_full_size_stack_name () {
		return self::FULL_SIZE_STACK_NAME;
	}

	/**
	 * @param $hash
	 * @param $format
	 * @param string $size
	 *
	 * @return string
	 */
	public function get_rokka_url( $hash, $format, $size = 'thumbnail' ) {
		if ( is_array( $size ) ) {
			$stack = null;

			// if size is requests as width / height array -> find matching or nearest Rokka size
			$rokka_sizes = $this->list_thumbnail_sizes();
			foreach ( $rokka_sizes as $size_name => $size_values ) {
				if ( $size[0] <= $size_values[0] ) {
					$stack = $size_name;
					break;
				}
			}
			if ( is_null( $stack ) ) {
				$stack = 'thumbnail';
			}
		} else {
			$stack = $size;
		}
		return $this->get_rokka_scheme() . '://' . $this->get_rokka_company_name() . '.' . $this->get_rokka_domain() . '/' . $stack . '/' . $hash . '.' . $format;
	}

	/**
	 * @return string
	 */
	public function get_rokka_scheme() {
		return 'https';
	}

	/**
	 * @return mixed|void
	 */
	public function get_rokka_domain() {
		return get_option( 'rokka_domain' );
	}

	/**
	 * @return mixed|void
	 */
	public function get_rokka_company_name() {
		return get_option( 'rokka_company_name' );
	}

	/**
	 * @return mixed|void
	 */
	public function get_rokka_api_key() {
		return get_option( 'rokka_api_key' );
	}

	/**
	 * @return mixed|void
	 */
	public function get_rokka_api_secret() {
		return get_option( 'rokka_api_secret' );
	}

	public function is_on_rokka( $attachment_id ) {
		if ( wp_attachment_is_image( $attachment_id ) ) {
			$rokka_hash = get_post_meta( $attachment_id, 'rokka_hash', true );
			return (bool) $rokka_hash;
		}
		return false;
	}

}
