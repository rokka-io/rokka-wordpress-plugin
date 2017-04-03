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
	 * @param int   $attachment_id Attachment id.
	 *
	 * @return bool
	 */
	public function upload_image_to_rokka( $attachment_id ) {
		$this->validate_attachment_before_upload( $attachment_id );

		$file_path = get_attached_file( $attachment_id );
		$file_name = wp_basename( $file_path );
		$client = $this->rokka_get_client();
		$source_image = $client->uploadSourceImage( file_get_contents( $file_path ), $file_name );

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
		//the meta stuff should be possible here too
		$file_path     = get_attached_file( $attachment_id, true );
		$type          = get_post_mime_type( $attachment_id );
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

	public function save_subject_area( $hash, $x, $y, $width, $height ) {
		$client = $this->rokka_get_client();
		$subject_area = new Rokka\Client\Core\DynamicMetadata\SubjectArea( $x, $y, $width, $height );
		$new_hash = $client->setDynamicMetadata( $subject_area, $hash );

		return $new_hash;
	}

	public function remove_subject_area( $hash ) {
		$client = $this->rokka_get_client();
		try {
			$new_hash = $client->deleteDynamicMetadata( 'SubjectArea', $hash );
		} catch( GuzzleHttp\Exception\ClientException $e ) {
			// the deleteDynamicMetadata will throw a ClientException if the SubjectArea doesn't exist
			// ignore this exception and continue
			// hash stays the same in this case
			$new_hash = $hash;
		}

		return $new_hash;
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
