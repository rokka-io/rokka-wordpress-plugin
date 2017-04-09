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
	 * Company name.
	 *
	 * @var string
	 */
	private $company_name = '';

	/**
	 * Rokka API Key.
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Rokka API Secret.
	 *
	 * @var string
	 */
	private $api_secret = '';

	/**
	 * Rokka enabled.
	 *
	 * @var bool
	 */
	private $rokka_enabled = false;

	/**
	 * Rokka_Helper constructor.
	 */
	public function __construct() {
		$this->load_options();
	}

	/**
	 * Loads options from database
	 */
	protected function load_options() {
		// loading options is expensive so we just do it once
		$this->company_name = get_option( 'rokka_company_name' );
		$this->api_key = get_option( 'rokka_api_key' );
		$this->api_secret = get_option( 'rokka_api_secret' );
		$this->rokka_enabled = get_option( 'rokka_rokka_enabled' );
		if ( ! $this->company_name || ! $this->api_key || ! $this->api_secret ) {
			$this->rokka_enabled = false;
		}
	}

	/**
	 * Returns Rokka image client.
	 *
	 * @return \Rokka\Client\Image
	 */
	public function rokka_get_client() {
		return \Rokka\Client\Factory::getImageClient( $this->get_rokka_company_name(), $this->get_rokka_api_key(), $this->get_rokka_api_secret() );
	}


	/**
	 * Uploads file to Rokka.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $file_path Path to file which should be uploaded.
	 *
	 * @return bool
	 */
	public function upload_image_to_rokka( $attachment_id, $file_path = '' ) {
		if ( empty( $file_path ) ) {
			$file_path = get_attached_file( $attachment_id );
		}

		if ( ! $this->is_valid_attachment( $attachment_id, $file_path ) ) {
			return false;
		}

		$file_name = wp_basename( $file_path );
		$client = $this->rokka_get_client();
		// @codingStandardsIgnoreStart
		$source_image = $client->uploadSourceImage( file_get_contents( $file_path ), $file_name );
		// @codingStandardsIgnoreEnd

		if ( is_object( $source_image ) ) {
			$source_images = $source_image->getSourceImages();
			$source_image = array_pop( $source_images );
			$rokka_meta = array(
				'format' => $source_image->format,
				'organization' => $source_image->organization,
				'link' => $source_image->link,
				'created' => $source_image->created,
			);
			update_post_meta( $attachment_id, 'rokka_meta', $rokka_meta );
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
			delete_post_meta( $attachment_id, 'rokka_meta' );
			delete_post_meta( $attachment_id, 'rokka_hash' );
			delete_post_meta( $attachment_id, 'rokka_subject_area' );
			$client = $this->rokka_get_client();
			return $client->deleteSourceImage( $hash );
		}

		return false;
	}

	/**
	 * Checks if attachment is valid before it gets uploaded to Rokka.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $file_path Path to file which should be uploaded.
	 *
	 * @return bool
	 *
	 * @throws Exception Exception on failure.
	 */
	private function is_valid_attachment( $attachment_id, $file_path ) {
		if ( empty( $file_path ) ) {
			$file_path = get_attached_file( $attachment_id );
		}

		// check mime type of file is in allowed rokka mime types
		if ( ! $this->is_allowed_mime_type( $attachment_id ) ) {
			return false;
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
	 * Checks if attachment has an allowed mime type
	 *
	 * @param int $attachment_id Attachment id.
	 *
	 * @return bool
	 */
	public function is_allowed_mime_type( $attachment_id ) {
		$type = get_post_mime_type( $attachment_id );
		$allowed_types = self::ALLOWED_MIME_TYPES;

		return in_array( $type, $allowed_types, true );
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
				$delete = false;
				$width = $size[0];
				$height = $size[1];
				$crop = $size[2];

				// loop through all stacks which are already on Rokka
				foreach ( $stacks->getStacks() as $stack ) {
					if ( $stack->name === $name ) {
						$continue = false;

						// check if width, height or mode was changed in the meantime (in WordPress config)
						// @codingStandardsIgnoreStart
						$stack_operations = $stack->stackOperations;
						// @codingStandardsIgnoreEnd
						foreach ( $stack_operations as $operation ) {
							$operation = $operation->toArray();

							if ( 'resize' === $operation['name'] ) {
								$stack_width = intval( $operation['options']['width'] );
								$stack_height = intval( $operation['options']['height'] );
								$stack_crop = ( 'fill' === $operation['options']['mode'] ) ? true : false;
								if ( $stack_width !== $width || $stack_height !== $height || $stack_crop !== $crop ) {
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

					$operations = array();
					$mode = $crop  ? 'fill' : 'box';
					$operations[] = new \Rokka\Client\Core\StackOperation( 'resize', array(
						'width' => $width,
						'height' => $height,
						'mode' => $mode,
						'upscale' => false,
					) );
					if ( $crop ) {
						$operations[] = new \Rokka\Client\Core\StackOperation( 'crop', array(
							'width' => $width,
							'height' => $height,
						) );
					}

					$client->createStack( $name, $operations );
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
			$width = 0;
			$height = 0;
			$crop = false;
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
				$width = intval( get_option( "{$_size}_size_w" ) );
				$height = intval( get_option( "{$_size}_size_h" ) );
				$crop = (bool) get_option( "{$_size}_crop" );
			} else {
				if ( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$width = $_wp_additional_image_sizes[ $_size ]['width'];
					$height = $_wp_additional_image_sizes[ $_size ]['height'];
					$crop = $_wp_additional_image_sizes[ $_size ]['crop'];
				}
			}
			// if width or height is 0 or bigger than 10000 (no limit) set to 10000 (Rokka maximum)
			$width = ( $width > 0 && $width < 10000 ) ? $width : 10000;
			$height = ( $height > 0 && $height < 10000 ) ? $height : 10000;
			$sizes[ $_size ] = array( $width, $height, $crop );
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
	 * @param string $filename Image filename.
	 * @param string $size Image size.
	 *
	 * @return string
	 */
	public function get_rokka_url( $hash, $filename, $size = 'thumbnail' ) {
		if ( is_array( $size ) ) {
			$stack = null;

			// if size is requested as width / height array -> find matching or nearest Rokka size
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
		if ( empty( $filename ) ) {
			// use fallback image name if empty
			$filename = 'image.jpg';
		}
		return $this->get_rokka_scheme() . '://' . $this->get_rokka_company_name() . '.' . $this->get_rokka_domain() . '/' . $stack . '/' . $hash . '/' . $this->sanitize_rokka_filename( $filename );
	}

	/**
	 * Sanitizes filename before sending it to Rokka.
	 *
	 * @param string $filename Filename to sanitize.
	 * @return string
	 */
	public function sanitize_rokka_filename( $filename ) {
		return preg_replace( '/[^a-z0-9\-\.]/', '-', strtolower( $filename ) );
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
	 * @return string
	 */
	public function get_rokka_domain() {
		return self::ROKKA_DOMAIN;
	}

	/**
	 * Returns Rokka company name from options.
	 *
	 * @return string
	 */
	public function get_rokka_company_name() {
		return $this->company_name;
	}

	/**
	 * Returns Rokka api key from options.
	 *
	 * @return string
	 */
	public function get_rokka_api_key() {
		return $this->api_key;
	}

	/**
	 * Returns Rokka api secret from options.
	 *
	 * @return string
	 */
	public function get_rokka_api_secret() {
		return $this->api_secret;
	}

	/**
	 * Returns if rokka is enabled.
	 *
	 * @return bool
	 */
	public function is_rokka_enabled() {
		return $this->rokka_enabled;
	}

}
