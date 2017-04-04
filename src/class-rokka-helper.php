<?php
/**
 * Rokka helper class.
 *
 * @package rokka-image-cdn
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
	const ROKKA_SCHEME = 'https';

	/**
	 * Rokka baseurl.
	 *
	 * @var string
	 */
	const ROKKA_DOMAIN = 'rokka.io';

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
	 * @param int $attachment_id Attachment id.
	 *
	 * @return bool
	 */
	public function upload_image_to_rokka( $attachment_id ) {
		$this->validate_attachment_before_upload( $attachment_id );

		$file_path = get_attached_file( $attachment_id );
		$file_name = wp_basename( $file_path );
		$client = $this->rokka_get_client();
		// @codingStandardsIgnoreStart
		$source_image = $client->uploadSourceImage( file_get_contents( $file_path ), $file_name );
		// @codingStandardsIgnoreEnd

		if ( is_object( $source_image ) ) {
			$source_images = $source_image->getSourceImages();
			$source_image  = array_pop( $source_images );
			$rokka_info = array(
				'hash' => $source_image->hash,
				'format' => $source_image->format,
				'organization' => $source_image->organization,
				'link' => $source_image->link,
				'created' => $source_image->created,
			);
			update_post_meta( $attachment_id, 'rokka_info', $rokka_info );
			update_post_meta( $attachment_id, 'rokka_hash', $source_image->hash );

			return true;
		}

		return false;
	}


	/**
	 * Deletes an image from rokka.io
	 *
	 * @param int $attachment_id Attachment id.
	 *
	 * @return bool
	 */
	public function delete_image_from_rokka( $attachment_id ) {
		$hash = get_post_meta( $attachment_id, 'rokka_hash', true );

		if ( $hash ) {
			$client = $this->rokka_get_client();
			return $client->deleteSourceImage( $hash );
		}

		return false;
	}

	/**
	 * Validates file before it gets uploaded to Rokka.
	 *
	 * @param int $attachment_id Attachment id.
	 *
	 * @return bool
	 *
	 * @throws Exception Exception on failure.
	 */
	private function validate_attachment_before_upload( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id, true );
		$type = get_post_mime_type( $attachment_id );
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
	 * Checks if given attachement is already on Rokka.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return bool
	 */
	public function is_on_rokka( $attachment_id ) {
		if ( wp_attachment_is_image( $attachment_id ) ) {
			$rokka_hash = get_post_meta( $attachment_id, 'rokka_hash', true );
			return (bool) $rokka_hash;
		}
		return false;
	}

	/**
	 * Creates and checks stacks on rokka if they don't already exist.
	 *
	 * @return array
	 */
	public function rokka_create_stacks() {
		$sizes = $this->get_available_image_sizes();
		$client = $this->rokka_get_client();
		$stacks = $client->listStacks();

		// Create a noop stack (full size stack) if it not exists already
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
				$width = $size[0];
				$height = $size[1];

				// loop through all stacks which are already on Rokka
				foreach ( $stacks->getStacks() as $stack ) {
					if ( $stack->name === $name ) {
						$continue = false;

						//check if width or height was changed in the meantime (in WordPress config)
						// @codingStandardsIgnoreStart
						$stack_operations = $stack->stackOperations;
						// @codingStandardsIgnoreEnd
						foreach ( $stack_operations as $operation ) {
							$operation = $operation->toArray();

							if ( 'resize' === $operation['name'] ) {
								$stack_width  = $operation['options']['width'];
								$stack_height = $operation['options']['height'];
								if ( $stack_width !== $width || $stack_height !== $height ) {
									$continue = true;
									$delete = true;
								}
							}
						}
						break;
					}
				}

				if ( $continue && $width > 0 ) {
					if ( $delete ) {
						$client->deleteStack( $name );
					}
					$resize_operation = new \Rokka\Client\Core\StackOperation( 'resize', array(
						'width'   => $width,
						'height'  => $height,
						//aspect ratio will be kept
						'mode'    => 'box',
						'upscale' => false,
					) );

					$client->createStack( $name, array( $resize_operation ) );
				}
			}
		}

		return $sizes;
	}

	/**
	 * Gets all image sizes
	 *
	 * @return array
	 */
	public function get_available_image_sizes() {
		global $_wp_additional_image_sizes;
		$sizes  = array();

		foreach ( get_intermediate_image_sizes() as $_size ) {
			$sizes[ $_size ] = array( 0, 0 );
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
				$sizes[ $_size ][0]  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ][1] = get_option( "{$_size}_size_h" );
			} else {
				if ( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						$_wp_additional_image_sizes[ $_size ]['width'],
						$_wp_additional_image_sizes[ $_size ]['height'],
					);
				}
			}
		}

		return $sizes;
	}


	/**
	 * Gets stack name of full size image
	 *
	 * @return string
	 */
	public function get_rokka_full_size_stack_name() {
		return self::FULL_SIZE_STACK_NAME;
	}

	/**
	 * Returns Rokka url of image
	 *
	 * @param string $hash Rokka hash.
	 * @param string $format File format.
	 * @param string $size Image size.
	 *
	 * @return string
	 */
	public function get_rokka_url( $hash, $format, $size = 'thumbnail' ) {
		if ( is_array( $size ) ) {
			$stack = null;

			// if size is requests as width / height array -> find matching or nearest Rokka size
			$rokka_sizes = $this->get_available_image_sizes();
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
	 * Saves subject area on Rokka.
	 *
	 * @param string $hash Rokka hash.
	 * @param int    $x X value of subject area.
	 * @param int    $y Y value of subject area.
	 * @param int    $width Width of subject area.
	 * @param int    $height Height of subject area.
	 *
	 * @return false|string New hash on success. False on failure.
	 */
	public function save_subject_area( $hash, $x, $y, $width, $height ) {
		$client = $this->rokka_get_client();
		$subject_area = new Rokka\Client\Core\DynamicMetadata\SubjectArea( $x, $y, $width, $height );
		$new_hash = $client->setDynamicMetadata( $subject_area, $hash );

		return $new_hash;
	}

	/**
	 * Deletes subject area on Rokka.
	 *
	 * @param string $hash Rokka hash.
	 *
	 * @return false|string New hash on success. False on failure.
	 */
	public function remove_subject_area( $hash ) {
		$client = $this->rokka_get_client();
		try {
			$new_hash = $client->deleteDynamicMetadata( 'SubjectArea', $hash );
		} catch ( GuzzleHttp\Exception\ClientException $e ) {
			// the deleteDynamicMetadata will throw a ClientException if the SubjectArea doesn't exist
			// ignore this exception and continue
			// hash stays the same in this case
			$new_hash = $hash;
		}

		return $new_hash;
	}

	/**
	 * Returns Rokka url scheme.
	 *
	 * @return string
	 */
	public function get_rokka_scheme() {
		return self::ROKKA_SCHEME;
	}

	/**
	 * Returns Rokka domain from options.
	 *
	 * @return string|bool
	 */
	public function get_rokka_domain() {
		return self::ROKKA_DOMAIN;
	}

	/**
	 * Returns Rokka company name from options.
	 *
	 * @return string|bool
	 */
	public function get_rokka_company_name() {
		return get_option( 'rokka_company_name' );
	}

	/**
	 * Returns Rokka api key from options.
	 *
	 * @return string|bool
	 */
	public function get_rokka_api_key() {
		return get_option( 'rokka_api_key' );
	}

	/**
	 * Returns Rokka api secret from options.
	 *
	 * @return string|bool
	 */
	public function get_rokka_api_secret() {
		return get_option( 'rokka_api_secret' );
	}

}
