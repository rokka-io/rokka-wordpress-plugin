<?php
/**
 * Rokka helper class.
 *
 * @package rokka-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rokka_Helper
 */
class Rokka_Helper {

	/**
	 * List of allowed mime types.
	 *
	 * @var array
	 */
	const ALLOWED_MIME_TYPES = [ 'image/gif', 'image/jpg', 'image/jpeg', 'image/png' ];

	/**
	 * Constant name of rokka domain.
	 *
	 * @var string
	 */
	const ROKKA_DOMAIN_CONSTANT_NAME = 'ROKKA_DOMAIN';

	/**
	 * Constant name of rokka scheme.
	 *
	 * @var string
	 */
	const ROKKA_SCHEME_CONSTANT_NAME = 'ROKKA_SCHEME';

	/**
	 * Constant name of rokka company name option.
	 *
	 * @var string
	 */
	const OPTION_COMPANY_NAME_CONSTANT_NAME = 'ROKKA_COMPANY_NAME';

	/**
	 * Constant name of rokka api key option.
	 *
	 * @var string
	 */
	const OPTION_API_KEY_CONSTANT_NAME = 'ROKKA_API_KEY';

	/**
	 * Constant name of rokka stack prefix option.
	 *
	 * @var string
	 */
	const OPTION_STACK_PREFIX_CONSTANT_NAME = 'ROKKA_STACK_PREFIX';

	/**
	 * Stack name of original image.
	 *
	 * @var string
	 */
	const FULL_SIZE_STACK_NAME = 'full';

	/**
	 * Default stack prefix.
	 *
	 * @var string
	 */
	const STACK_PREFIX_DEFAULT = 'wp-';

	/**
	 * Name of stack sync operation create
	 *
	 * @var string
	 */
	const STACK_SYNC_OPERATION_CREATE = 'create';

	/**
	 * Name of stack sync operation keep
	 *
	 * @var string
	 */
	const STACK_SYNC_OPERATION_KEEP = 'keep';

	/**
	 * Name of stack sync operation update
	 *
	 * @var string
	 */
	const STACK_SYNC_OPERATION_UPDATE = 'update';

	/**
	 * Name of stack sync operation delete
	 *
	 * @var string
	 */
	const STACK_SYNC_OPERATION_DELETE = 'delete';

	/**
	 * Rokka base domain
	 *
	 * @var string
	 */
	private $rokka_base_domain = 'rokka.io';

	/**
	 * Rokka domain
	 *
	 * @var string
	 */
	private $rokka_domain = '';

	/**
	 * Rokka scheme
	 *
	 * @var string
	 */
	private $rokka_scheme = 'https';

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
	 * Autoformat option.
	 *
	 * @var bool
	 */
	private $autoformat = false;

	/**
	 * Delete previous image enabled.
	 *
	 * @var bool
	 */
	private $delete_previous = false;

	/**
	 * Output parsing enabled.
	 *
	 * @var string
	 */
	private $output_parsing_enabled = false;

	/**
	 * Settings complete.
	 *
	 * @var bool
	 */
	private $settings_complete = false;

	/**
	 * Rokka enabled.
	 *
	 * @var bool
	 */
	private $rokka_enabled = false;

	/**
	 * Stack prefix.
	 *
	 * @var string
	 */
	private $stack_prefix = '';

	/**
	 * Rokka client library instance.
	 *
	 * @var \Rokka\Client\Image
	 */
	private $rokka_client = null;

	/**
	 * Rokka_Helper constructor.
	 */
	public function __construct() {
		// load base settings from constants if defined
		if ( defined( self::ROKKA_DOMAIN_CONSTANT_NAME ) ) {
			$this->rokka_domain = constant( self::ROKKA_DOMAIN_CONSTANT_NAME );
		}
		if ( defined( self::ROKKA_SCHEME_CONSTANT_NAME ) ) {
			$this->rokka_scheme = constant( self::ROKKA_SCHEME_CONSTANT_NAME );
		}

		$this->load_options();
	}

	/**
	 * Loads options from database
	 */
	protected function load_options() {
		// loading options is expensive so we just do it once
		if ( defined( self::OPTION_COMPANY_NAME_CONSTANT_NAME ) ) {
			$this->company_name = constant( self::OPTION_COMPANY_NAME_CONSTANT_NAME );
		} else {
			$this->company_name = get_option( 'rokka_company_name' );
		}

		// if rokka domain is not configured in constants create default rokka domain (<company_name>.rokka.io)
		if ( empty( $this->get_rokka_domain() ) ) {
			$this->rokka_domain = $this->get_rokka_company_name() . '.' . $this->get_rokka_base_domain();
		}

		if ( defined( self::OPTION_API_KEY_CONSTANT_NAME ) ) {
			$this->api_key = constant( self::OPTION_API_KEY_CONSTANT_NAME );
		} else {
			$this->api_key = get_option( 'rokka_api_key' );
		}
		$this->autoformat = (bool) get_option( 'rokka_autoformat' );
		$this->delete_previous = get_option( 'rokka_delete_previous' );
		// Backwards compatibility to plugin v1.1.0
		if ( 'on' === $this->delete_previous ) {
			update_option( 'rokka_delete_previous', true );
			$this->delete_previous = true;
		}
		$this->delete_previous = (bool) $this->delete_previous;
		$this->output_parsing_enabled = get_option( 'rokka_output_parsing' );
		// Backwards compatibility to plugin v1.1.0
		if ( 'on' === $this->output_parsing_enabled ) {
			update_option( 'rokka_output_parsing', true );
			$this->output_parsing_enabled = true;
		}
		$this->output_parsing_enabled = (bool) $this->output_parsing_enabled;
		$this->rokka_enabled = get_option( 'rokka_rokka_enabled' );
		// Backwards compatibility to plugin v1.1.0
		if ( 'on' === $this->rokka_enabled ) {
			update_option( 'rokka_rokka_enabled', true );
			$this->rokka_enabled = true;
		}
		$this->rokka_enabled = (bool) $this->rokka_enabled;
		if ( defined( self::OPTION_STACK_PREFIX_CONSTANT_NAME ) ) {
			$stack_prefix = constant( self::OPTION_STACK_PREFIX_CONSTANT_NAME );
		} else {
			$stack_prefix = get_option( 'rokka_stack_prefix' );
		}
		$this->stack_prefix = ( ! empty( $stack_prefix ) ? $stack_prefix : self::STACK_PREFIX_DEFAULT );

		// check if settings are complete
		if ( ! empty( $this->company_name ) && ! empty( $this->api_key ) ) {
			$this->settings_complete = true;
		}

		// If settings are not complete -> disable rokka integration
		if ( ! $this->settings_complete ) {
			update_option( 'rokka_rokka_enabled', false );
			$this->rokka_enabled = false;
		}
	}

	/**
	 * Returns Rokka image client.
	 *
	 * @return \Rokka\Client\Image
	 */
	public function rokka_get_client() {
		if ( is_null( $this->rokka_client ) ) {
			$this->rokka_client = \Rokka\Client\Factory::getImageClient( $this->get_rokka_company_name(), $this->get_rokka_api_key(), '' );
		}

		return $this->rokka_client;
	}

	/**
	 * Sets the rokka image client. (Should only be used in unit tests to mock rokka client library)
	 *
	 * @param \Rokka\Client\Image $rokka_client Rokka client library instance.
	 */
	public function rokka_set_client( $rokka_client ) {
		$this->rokka_client = $rokka_client;
	}

	/**
	 * Uploads file to Rokka.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $file_path Path to file which should be uploaded.
	 *
	 * @return bool
	 *
	 * @throws Exception Throws exception if there was something wrong with uploading image to rokka.
	 */
	public function upload_image_to_rokka( $attachment_id, $file_path = '' ) {
		if ( empty( $file_path ) ) {
			$file_path = get_attached_file( $attachment_id );
		}

		if ( ! $this->is_valid_attachment( $attachment_id, $file_path ) ) {
			return true; // return true if upload is not needed.
		}

		$file_name = wp_basename( $file_path );
		$client = $this->rokka_get_client();
		// @codingStandardsIgnoreStart
		$source_images_collection = $client->uploadSourceImage( file_get_contents( $file_path ), $file_name );
		// @codingStandardsIgnoreEnd

		if ( is_object( $source_images_collection ) ) {
			$source_images = $source_images_collection->getSourceImages();
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
	 *
	 * @throws Exception Throws exception if there was something wrong with deleting image on rokka.
	 */
	public function delete_image_from_rokka( $attachment_id ) {
		$hash = get_post_meta( $attachment_id, 'rokka_hash', true );

		if ( $hash ) {
			$success = true;

			// remove image from rokka only if there doesn't exist an image with the same hash
			if ( ! $this->image_with_same_hash_exists( $hash ) ) {
				$client = $this->rokka_get_client();
				$success = $client->deleteSourceImage( $hash );
			}

			if ( $success ) {
				delete_post_meta( $attachment_id, 'rokka_meta' );
				delete_post_meta( $attachment_id, 'rokka_hash' );
				delete_post_meta( $attachment_id, 'rokka_subject_area' );
			}
			return $success;
		}

		return false;
	}

	/**
	 * Checks if there are more than one images with the same rokka hash saved in the database.
	 *
	 * @param string $hash Rokka hash.
	 *
	 * @return bool
	 */
	public function image_with_same_hash_exists( $hash ) {
		$hash_meta_query_args = array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'post_parent' => null,
			// @codingStandardsIgnoreStart
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key'     => 'rokka_hash',
					'value'   => $hash,
					'compare' => '='
				),
			),
			// @codingStandardsIgnoreEnd
		);
		$images = get_posts( $hash_meta_query_args );

		return count( $images ) > 1;
	}

	/**
	 * Checks if attachment is valid before it gets uploaded to rokka.
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
			$error_msg = sprintf( esc_html_x( 'File %s does not exist', '%s contains file path', 'rokka-integration' ), $file_path );
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
	 * Checks if given attachement is already on rokka.
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
	 * Creates stack on rokka.
	 *
	 * @param string $name Stack name.
	 * @param int    $width Width of resize operation.
	 * @param int    $height Height of resize operation.
	 * @param bool   $crop If crop stack operation should be added. Default false.
	 * @param bool   $overwrite Overwrite stack if already exists. Default true.
	 * @param bool   $autoformat Enable autoformat on stack. Default false.
	 *
	 * @throws Exception Throws exception if there was something wrong with the request.
	 */
	public function create_stack( $name, $width, $height, $crop = false, $overwrite = true, $autoformat = false ) {
		$client = $this->rokka_get_client();
		$operations = array();
		$mode = $crop ? 'fill' : 'box';
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
		$options = array(
			'autoformat' => $autoformat,
		);

		$client->createStack( $name, $operations, '', $options, $overwrite );
	}

	/**
	 * Creates noop stack (full size stack) on rokka.
	 *
	 * @param string $name Stack name.
	 * @param bool   $overwrite Overwrite stack if already exists. Default true.
	 *
	 * @throws Exception Throws exception if there was something wrong with the request.
	 */
	public function create_noop_stack( $name, $overwrite = true ) {
		$client = $this->rokka_get_client();
		$operations = array();
		$operations[] = new \Rokka\Client\Core\StackOperation( 'noop' );
		$client->createStack( $name, $operations, '', [], $overwrite );
	}

	/**
	 * Updates stack on rokka.
	 *
	 * @param string $name Stack name.
	 * @param int    $width Width of resize operation.
	 * @param int    $height Height of resize operation.
	 * @param bool   $crop If crop stack operation should be added. Default false.
	 *
	 * @throws Exception Throws exception if there was something wrong with the request.
	 */
	public function update_stack( $name, $width, $height, $crop = false ) {
		$this->create_stack( $name, $width, $height, $crop, true, $this->get_autoformat() );
	}

	/**
	 * Deletes stack on rokka.
	 *
	 * @param string $name Stack name.
	 */
	public function delete_stack( $name ) {
		$client = $this->rokka_get_client();
		$client->deleteStack( $name );
	}

	/**
	 * Syncs stacks with rokka.
	 *
	 * @return array
	 *
	 * @throws Exception Throws exception if there was something wrong with syncing the stacks with rokka.
	 */
	public function rokka_sync_stacks() {
		$stacks_to_sync = $this->get_stacks_to_sync();

		foreach ( $stacks_to_sync as $stack ) {
			// handle full size stack specially
			if ( $stack['name'] === $this->get_prefixed_stack_name( $this->get_rokka_full_size_stack_name() ) ) {
				if ( self::STACK_SYNC_OPERATION_CREATE === $stack['operation'] ) {
					$this->create_noop_stack( $stack['name'] );
				}
				continue;
			}

			if ( self::STACK_SYNC_OPERATION_CREATE === $stack['operation'] ) {
				$this->create_stack( $stack['name'], $stack['width'], $stack['height'], $stack['crop'], $this->get_autoformat() );
			} elseif ( self::STACK_SYNC_OPERATION_UPDATE === $stack['operation'] ) {
				$this->update_stack( $stack['name'], $stack['width'], $stack['height'], $stack['crop'] );
			} elseif ( self::STACK_SYNC_OPERATION_DELETE === $stack['operation'] ) {
				$this->delete_stack( $stack['name'] );
			}
		}

		return $stacks_to_sync;
	}

	/**
	 * Prepares stacks which should be synced with rokka.
	 *
	 * Stacks will be returned in the following format:
	 *
	 * array(
	 *     array(
	 *         'name' => 'stackname',
	 *         'width' => 900,
	 *         'height' => 300,
	 *         'crop' => true,
	 *         'operation' => create|keep|update|delete,
	 *     ),
	 *     ...
	 * )
	 *
	 * @return array Array with prepared stacks.
	 */
	public function get_stacks_to_sync() {
		$sizes = $this->get_available_image_sizes();
		$client = $this->rokka_get_client();
		$stack_collection = $client->listStacks();
		/**
		 * Stacks from rokka.
		 *
		 * @var \Rokka\Client\Core\Stack[] $stacks_on_rokka
		 */
		$stacks_on_rokka = array_filter( $stack_collection->getStacks(), function ( $stack ) {
			// filter out all non prefixed stacks
			return substr( $stack->name, 0, strlen( $this->get_stack_prefix() ) ) === $this->get_stack_prefix();
		} );

		$stacks_to_sync = array();

		// Create a noop stack (full size stack) if it not exists already
		$noop_stackname = $this->get_prefixed_stack_name( $this->get_rokka_full_size_stack_name() );
		try {
			$client->getStack( $noop_stackname );
			$stacks_to_sync[ $noop_stackname ] = array(
				'name' => $noop_stackname,
				'width' => '0',
				'height' => '0',
				'crop' => false,
				'operation' => self::STACK_SYNC_OPERATION_KEEP,
			);
		} catch ( Exception $e ) {
			$stacks_to_sync[ $noop_stackname ] = array(
				'name' => $noop_stackname,
				'width' => '0',
				'height' => '0',
				'crop' => false,
				'operation' => self::STACK_SYNC_OPERATION_CREATE,
			);
		}
		if ( ! empty( $sizes ) ) {
			foreach ( $sizes as $name => $size ) {
				$width = $size[0];
				$height = $size[1];
				$crop = $size[2];
				$prefixed_name = $this->get_prefixed_stack_name( $name );
				$stack_already_on_rokka = false;

				// loop through all stacks which are already on rokka
				foreach ( $stacks_on_rokka as $stack ) {
					// if stack is already on rokka
					if ( $stack->name === $prefixed_name ) {
						$stack_already_on_rokka = true;
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
								// if stack has changed
								if ( $stack_width !== $width || $stack_height !== $height || $stack_crop !== $crop ) {
									$stacks_to_sync[ $prefixed_name ] = array(
										'name' => $prefixed_name,
										'width' => $width,
										'height' => $height,
										'crop' => $crop,
										'operation' => self::STACK_SYNC_OPERATION_UPDATE,
									);
								} else {
									$stacks_to_sync[ $prefixed_name ] = array(
										'name' => $prefixed_name,
										'width' => $width,
										'height' => $height,
										'crop' => $crop,
										'operation' => self::STACK_SYNC_OPERATION_KEEP,
									);
								}
							}
						}
						// set sync operation to update if autoformat option changed
						if ( $this->autoformat_changed( $stack ) ) {
							$stacks_to_sync[ $prefixed_name ]['operation'] = self::STACK_SYNC_OPERATION_UPDATE;
						}
						break;
					}
				}

				if ( ! $stack_already_on_rokka ) {
					$stacks_to_sync[ $prefixed_name ] = array(
						'name' => $prefixed_name,
						'width' => $width,
						'height' => $height,
						'crop' => $crop,
						'operation' => self::STACK_SYNC_OPERATION_CREATE,
					);
				}
			}
		}

		// find deleted stacks in WordPress
		if ( ! empty( $stacks_on_rokka ) && ! empty( $sizes ) ) {
			foreach ( $stacks_on_rokka as $stack ) {
				// full size stack should never be deleted
				if ( $stack->name === $this->get_prefixed_stack_name( $this->get_rokka_full_size_stack_name() ) ) {
					continue;
				}
				$stack_still_exists_in_wp = false;
				foreach ( $sizes as $name => $size ) {
					$prefixed_name = $this->get_prefixed_stack_name( $name );
					if ( $stack->name === $prefixed_name ) {
						$stack_still_exists_in_wp = true;
						break;
					}
				}
				if ( ! $stack_still_exists_in_wp ) {
					$stacks_to_sync[ $stack->name ] = array(
						'name' => $stack->name,
						'width' => 0,
						'height' => 0,
						'crop' => false,
						'operation' => self::STACK_SYNC_OPERATION_DELETE,
					);
				}
			}
		}

		return $stacks_to_sync;
	}

	/**
	 * Checks if the autoformat option has changed since the last stack synchronization.
	 *
	 * @param \Rokka\Client\Core\Stack $stack Stack to check option.
	 *
	 * @return bool
	 */
	protected function autoformat_changed( $stack ) {
		// @codingStandardsIgnoreStart
		$stack_options = $stack->stackOptions;
		// @codingStandardsIgnoreEnd
		if ( array_key_exists( 'autoformat', $stack_options ) ) {
				return $stack_options['autoformat'] !== $this->get_autoformat();
		} else {
			return $this->get_autoformat();
		}
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
			// if width or height is 0 or bigger than 10000 (no limit) set to 10000 (rokka maximum)
			$width = ( $width > 0 && $width < 10000 ) ? $width : 10000;
			$height = ( $height > 0 && $height < 10000 ) ? $height : 10000;
			$sizes[ $_size ] = array( $width, $height, $crop );
		}

		return $sizes;
	}

	/**
	 * Returns nearest matching image size name by given width and height.
	 *
	 * @param int $width  Width to get size name for.
	 * @param int $height Height to get size name for.
	 *
	 * @return string
	 */
	public function get_nearest_matching_image_size( $width, $height ) {
		$sizes = $this->get_available_image_sizes();
		foreach ( $sizes as $size_name => $size_values ) {
			// If the image dimensions are within 1px of the expected size, use it.
			if ( wp_image_matches_ratio( $width, $height, $size_values[0], $size_values[0] ) ) {
				if ( $width <= $size_values[0] ) {
					return $size_name;
				}
			}
		}

		return $this->get_rokka_full_size_stack_name();
	}

	/**
	 * Retrieves size name by given image url.
	 *
	 * @param int    $image_id ID of image.
	 * @param string $image_url URL of image.
	 * @param array  $image_meta Meta information of image.
	 *
	 * @return string
	 */
	public function get_size_by_image_url( $image_id, $image_url, $image_meta = array() ) {
		if ( empty( $image_meta ) ) {
			$image_meta = wp_get_attachment_metadata( $image_id );
		}

		$last_slash_pos = strrpos( $image_url, '/' );

		if ( false === $last_slash_pos ) {
			return $this->get_rokka_full_size_stack_name();
		}

		$image_name = substr( $image_url, $last_slash_pos + 1 );

		foreach ( $image_meta['sizes'] as $name => $size ) {
			if ( $image_name === $size['file'] ) {
				return $name;
			}
		}

		return $this->get_rokka_full_size_stack_name();
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
	 * Returns rokka url of image
	 *
	 * @param string $hash Rokka hash.
	 * @param string $filename Image filename.
	 * @param string $size Image size.
	 *
	 * @return string
	 */
	public function get_rokka_url( $hash, $filename, $size = 'thumbnail' ) {
		if ( is_array( $size ) ) {
			// if size is requested as width / height array -> find nearest size
			$stack = $this->get_nearest_matching_image_size( $size[0], $size[1] );
		} else {
			$stack = $size;
		}
		if ( empty( $filename ) ) {
			// use fallback image name if empty
			$filename = 'image.jpg';
		}
		return $this->get_rokka_scheme() . '://' . $this->get_rokka_domain() . '/' . $this->get_prefixed_stack_name( $stack ) . '/' . $hash . '/' . $this->sanitize_rokka_filename( $filename );
	}

	/**
	 * Returns prefixes stack name
	 *
	 * @param string $stack_name Stack name without prefix.
	 * @return string Prefixed stack name.
	 */
	public function get_prefixed_stack_name( $stack_name ) {
		return $this->get_stack_prefix() . $stack_name;
	}

	/**
	 * Sanitizes filename before sending it to rokka.
	 *
	 * @param string $filename Filename to sanitize.
	 * @return string
	 */
	public function sanitize_rokka_filename( $filename ) {
		$filename = preg_replace( '/[^a-z0-9\-\.]/', '-', strtolower( $filename ) );
		// remove all dots expect of last one
		$filename = preg_replace( '/\.(?=.*\.)/', '-', $filename );
		return $filename;
	}

	/**
	 * Saves subject area on rokka.
	 *
	 * @param string $hash Rokka hash.
	 * @param int    $x X value of subject area.
	 * @param int    $y Y value of subject area.
	 * @param int    $width Width of subject area.
	 * @param int    $height Height of subject area.
	 *
	 * @return false|string New hash on success. False on failure.
	 *
	 * @throws Exception Throws exception if there was something wrong with saving the subject area on rokka.
	 */
	public function save_subject_area( $hash, $x, $y, $width, $height ) {
		$client = $this->rokka_get_client();
		$subject_area = new Rokka\Client\Core\DynamicMetadata\SubjectArea( $x, $y, $width, $height );
		$new_hash = $client->setDynamicMetadata(
			$subject_area,
			$hash,
			'',
			array(
				'deletePrevious' => $this->get_delete_previous(),
			)
		);

		return $new_hash;
	}

	/**
	 * Deletes subject area on rokka.
	 *
	 * @param string $hash Rokka hash.
	 *
	 * @return false|string New hash on success. False on failure.
	 */
	public function remove_subject_area( $hash ) {
		$client = $this->rokka_get_client();
		try {
			$new_hash = $client->deleteDynamicMetadata(
				Rokka\Client\Core\DynamicMetadata\SubjectArea::getName(),
				$hash,
				'',
				array(
					'deletePrevious' => $this->get_delete_previous(),
				)
			);
		} catch ( GuzzleHttp\Exception\ClientException $e ) {
			// the deleteDynamicMetadata will throw a ClientException if the SubjectArea doesn't exist
			// ignore this exception and continue
			// hash stays the same in this case
			$new_hash = $hash;
		}

		return $new_hash;
	}

	/**
	 * Checks rokka credentials.
	 *
	 * @return false|string New hash on success. False on failure.
	 */
	public function check_rokka_credentials() {
		$client = $this->rokka_get_client();
		try {
			// the list stacks request fails if the credentials are wrong
			$client->listStacks( 1 );
			return true;
		} catch ( GuzzleHttp\Exception\ClientException $e ) {
			return false;
		}
	}

	/**
	 * Stores message in option to print it after redirect
	 *
	 * @param string $message Message which should be stored.
	 * @param string $type Message type (error, warning, success, info).
	 *
	 * @return bool True if message was stored successfully.
	 */
	public function store_message_in_notices_option( $message, $type = 'success' ) {
		if ( ! empty( $message ) ) {
			// store message in option array
			$notices = get_option( 'rokka_notices' );
			$notices[ $type ][] = $message;

			return update_option( 'rokka_notices', $notices );
		}

		return false;
	}

	/**
	 * Returns Rokka url scheme.
	 *
	 * @return string
	 */
	public function get_rokka_scheme() {
		return $this->rokka_scheme;
	}

	/**
	 * Returns Rokka base domain.
	 *
	 * @return string
	 */
	public function get_rokka_base_domain() {
		return $this->rokka_base_domain;
	}

	/**
	 * Returns Rokka domain.
	 *
	 * @return string
	 */
	public function get_rokka_domain() {
		// remove all trailing slashes from domain
		return rtrim( $this->rokka_domain, '/' );
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
	 * Returns if rokka is enabled.
	 *
	 * @return bool
	 */
	public function is_rokka_enabled() {
		return $this->rokka_enabled;
	}

	/**
	 * Returns stack prefix.
	 *
	 * @return string
	 */
	public function get_stack_prefix() {
		return $this->stack_prefix;
	}

	/**
	 * Returns if settings are complete.
	 *
	 * @return bool
	 */
	public function are_settings_complete() {
		return $this->settings_complete;
	}

	/**
	 * Returns if autoformat option is enabled.
	 *
	 * @return bool
	 */
	public function get_autoformat() {
		return $this->autoformat;
	}

	/**
	 * Returns if output parsing is enabled.
	 *
	 * @return bool
	 */
	public function is_output_parsing_enabled() {
		return $this->output_parsing_enabled;
	}

	/**
	 * Returns if previous image should be deleted on metadata change.
	 *
	 * @return bool
	 */
	public function get_delete_previous() {
		return $this->delete_previous;
	}

}
