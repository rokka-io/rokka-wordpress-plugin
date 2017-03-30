<?php

/**
 * Created by PhpStorm.
 * User: philou
 * Date: 06/02/17
 * Time: 14:37
 */
class Class_Rokka_Helper {

	const rokka_url = 'https://api.rokka.io';

	const allowed_mime_types = [ 'image/gif', 'image/jpg', 'image/jpeg', 'image/png' ];

	const full_size_stack_name = 'full';

	/**
	 * Class_Rokka_Helper constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_rokka_create_stacks', array( $this, 'rokka_ajax_create_stacks' ) );
	}


	/**
	 * @return \Rokka\Client\Image
	 */
	public function rokka_get_client() {
		return \Rokka\Client\Factory::getImageClient( get_option( 'rokka_company_name' ), get_option( 'rokka_api_key' ), get_option( 'rokka_api_secret' ) );
	}


	/**
	 * @param $post_id
	 * @param $data
	 *
	 * @return array|bool $data
	 */
	public function upload_image_to_rokka( $post_id, $data ) {
		$this->validate_files_before_upload( $post_id );
		$file_paths  = $this->get_attachment_file_paths( $post_id, true, $data );
		$client      = $this->rokka_get_client();
		$fileParts   = explode( '/', $file_paths['full'] );
		$fileName    = array_pop( $fileParts );
		$sourceImage = $client->uploadSourceImage( file_get_contents( $file_paths['full'] ), $fileName );
		//file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r($sourceImage,true) . PHP_EOL, FILE_APPEND);

		if ( is_object( $sourceImage ) ) {
			$sourceImages = $sourceImage->getSourceImages();
			$sourceImage  = array_pop( $sourceImages );
			$url          = self::rokka_url . $sourceImage->link . '.' . $sourceImage->format;
			//todo allenfalls stacks in array integrieren.
			$rokka_info = array(
				'hash'                => $sourceImage->hash,
				'format'              => $sourceImage->format,
				'organization'        => $sourceImage->organization,
				'link'                => $sourceImage->link,
				'created'             => $sourceImage->created,
			);
			update_post_meta( $post_id, 'rokka_info', $rokka_info );
			update_post_meta( $post_id, 'rokka_hash', $sourceImage->hash );

			return $data;
		}

		return false;
	}


	/**
	 * Deletes an image from rokka.io
	 *
	 * @param $post_id
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
	 * @param $post_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function validate_files_before_upload( $post_id ) {
		//the meta stuff should be possible here too
		$file_path     = get_attached_file( $post_id, true );
		$type          = get_post_mime_type( $post_id );
		$allowed_types = self::allowed_mime_types;

		// check mime type of file is in allowed rokka mime types
		if ( ! in_array( $type, $allowed_types ) ) {
			$error_msg = sprintf( __( 'Mime type %s is not allowed in rokka', 'rokka-image-cdn' ), $type );
			throw new Exception( $error_msg );
		}

		// Check file exists locally before attempting upload
		if ( ! file_exists( $file_path ) ) {
			$error_msg = sprintf( __( 'File %s does not exist', 'rokka-image-cdn' ), $file_path );
			throw new Exception( $error_msg );
		}

		return true;
	}

	/**
	 * Get file paths for all attachment versions.
	 *
	 * @param int $attachment_id
	 * @param bool $exists_locally
	 * @param array|bool $meta
	 * @param bool $include_backups
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
	 *
	 */
	function rokka_ajax_create_stacks() {
		$sizes = $this->rokka_create_stacks();

		if ( $sizes ) {
			wp_send_json_success( $sizes );
			wp_die();
		}

		wp_send_json_error( [ 'error' => 'could not process stacks' ] );
		wp_die();
	}

	/**
	 * creates and checks stacks on rokka if they don't already exist
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
	 * @return array
	 */
	public function list_thumbnail_sizes() {
		global $_wp_additional_image_sizes;
		$sizes  = array();
		$rSizes = array();
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
			$rSizes[ $size ] = $atts;
		}

		return $rSizes;
	}


	/**
	 * @return string
	 */
	public function get_rokka_full_size_stack_name () {
		return self::full_size_stack_name;
	}

	/**
	 * @param $hash
	 * @param $format
	 * @param string $stack
	 *
	 * @return string
	 */
	public function get_rokka_url( $hash, $format, $stack = 'full' ) {
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

}
