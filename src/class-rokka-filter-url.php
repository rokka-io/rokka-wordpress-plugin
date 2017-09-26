<?php
/**
 * Rokka url filter
 *
 * @package rokka-integration
 */

/**
 * Class Rokka_Filter_Url
 */
class Rokka_Filter_Url {

	/**
	 * Rokka helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Rokka_Filter_Url constructor.
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
		add_filter( 'set_url_scheme', array( $this, 'keep_url_scheme' ), 10, 3 );
		add_filter( 'wp_get_attachment_url', array( $this, 'rewrite_attachment_url' ), 10, 2 );
		add_filter( 'wp_get_attachment_thumb_url', array( $this, 'rewrite_attachment_thumb_url' ), 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'rewrite_attachment_url_for_js' ), 10, 3 );
		add_filter( 'image_downsize', array( $this, 'downsize_image' ), 10, 3 );
		add_filter( 'image_get_intermediate_size', array( $this, 'rewrite_intermediate_size_url' ), 10, 3 );
		add_filter( 'wp_calculate_image_srcset_meta', array( $this, 'rewrite_srcset_meta' ), 10, 4 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'rewrite_image_srcset_sources' ), 10, 5 );
	}

	/**
	 * Keep url scheme in rokka urls.
	 *
	 * @param string      $url         The complete URL including scheme and path.
	 * @param string      $scheme      Scheme applied to the URL. One of 'http', 'https', or 'relative'.
	 * @param string|null $orig_scheme Scheme requested for the URL. One of 'http', 'https', 'login',
	 *                                 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
	 *
	 * @return string
	 */
	public function keep_url_scheme( $url, $scheme, $orig_scheme ) {
		if ( false !== strpos( $url, $this->rokka_helper->get_rokka_domain() ) ) {
			$url = str_replace( $scheme . '://', $this->rokka_helper->get_rokka_scheme() . '://', $url );
		}
		return $url;
	}

	/**
	 * Rewrite url to rokka.
	 *
	 * @param string $url     URL for the given attachment.
	 * @param int    $post_id Attachment ID.
	 *
	 * @return string
	 */
	public function rewrite_attachment_url( $url, $post_id ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
			return $url;
		}

		$rokka_hash = get_post_meta( $post_id, 'rokka_hash', true );
		$attachment_meta = wp_get_attachment_metadata( $post_id );
		$filename = wp_basename( $attachment_meta['file'] );

		$url = $this->rokka_helper->get_rokka_url( $rokka_hash, $filename, $this->rokka_helper->get_rokka_full_size_stack_name() );

		return $url;
	}

	/**
	 * Rewrite thumb url to rokka.
	 *
	 * @param string $url     URL for the given attachment.
	 * @param int    $post_id Attachment ID.
	 *
	 * @return string
	 */
	public function rewrite_attachment_thumb_url( $url, $post_id ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
			return $url;
		}

		$rokka_hash = get_post_meta( $post_id, 'rokka_hash', true );
		$attachment_meta = wp_get_attachment_metadata( $post_id );
		if ( array_key_exists( 'thumbnail', $attachment_meta['sizes'] ) ) {
			$filename = $attachment_meta['sizes']['thumbnail']['file'];
		} else {
			// if size is not available in meta data use original filename
			$filename = wp_basename( $attachment_meta['file'] );
		}

		$url = $this->rokka_helper->get_rokka_url( $rokka_hash, $filename, 'thumbnail' );

		return $url;
	}

	/**
	 * Rewrites urls of differnent attachment sizes before they are sent to JavaScript.
	 *
	 * @param array      $response   Array of prepared attachment data.
	 * @param int|object $attachment Attachment ID or object.
	 * @param array      $meta       Array of attachment meta data.
	 *
	 * @return array
	 */
	public function rewrite_attachment_url_for_js( $response, $attachment, $meta ) {
		if ( ! $this->rokka_helper->is_on_rokka( $attachment->ID ) ) {
			return $response;
		}

		$rokka_hash = get_post_meta( $attachment->ID, 'rokka_hash', true );
		// The $response array which is sent to JS holds all urls for the available sizes in the following format:
		// https://liip-development.rokka.io/<size>/<filename-from-attachment-metadata>
		// Regenerate the rokka urls and replace them.
		foreach ( $response['sizes'] as $size => $size_details ) {
			if ( array_key_exists( $size, $meta['sizes'] ) ) {
				$filename = $meta['sizes'][ $size ]['file'];
			} else {
				// if size is not available in meta data use original filename
				$filename = wp_basename( $meta['file'] );
			}
			$response['sizes'][ $size ]['url'] = $this->rokka_helper->get_rokka_url( $rokka_hash, $filename, $size );
		}

		return $response;
	}

	/**
	 * Own implementation to downsize images with rokka urls.
	 *
	 * @param bool         $downsize Whether to short-circuit the image downsize. Default false.
	 * @param int          $id       Attachment ID for image.
	 * @param array|string $size     Size of image. Image size or array of width and height values (in that order).
	 *                               Default 'medium'.
	 *
	 * @return array|bool
	 */
	public function downsize_image( $downsize, $id, $size ) {
		if ( ! $this->rokka_helper->is_on_rokka( $id ) ) {
			return $downsize;
		}

		$img_url = false;
		$meta = wp_get_attachment_metadata( $id );
		$height = 0;
		$width = 0;
		$is_intermediate = false;

		$rokka_hash = get_post_meta( $id, 'rokka_hash', true );

		// try for a new style intermediate size
		// @codingStandardsIgnoreStart
		if ( $intermediate = image_get_intermediate_size( $id, $size ) ) {
			// this is needed to overwrite the url before the image_get_intermediate_size filter was introduced in WP 4.4
			// TODO do not call get_rokka_url twice
			$img_url = $this->rokka_helper->get_rokka_url( $rokka_hash, $intermediate['file'], $size );
			$width = $intermediate['width'];
			$height = $intermediate['height'];
			$is_intermediate = true;
		}
		elseif ( 'thumbnail' === $size  ) {
			// fall back to the old thumbnail
			if ( ( $thumb_file = wp_get_attachment_thumb_file( $id ) ) && $info = getimagesize( $thumb_file ) ) {
				$file_name = $meta['sizes']['thumbnail']['file'];
				$img_url = $this->rokka_helper->get_rokka_url( $rokka_hash, $file_name, 'thumbnail' );
				$width = $info[0];
				$height = $info[1];
				$is_intermediate = true;
			}
		}
		// @codingStandardsIgnoreEnd
		if ( ! $width && ! $height && isset( $meta['width'], $meta['height'] ) ) {
			// any other type: use the real image
			$width = $meta['width'];
			$height = $meta['height'];
		}

		if ( $img_url ) {
			// we have the actual image size, but might need to further constrain it if content_width is narrower
			list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

			return array( $img_url, $width, $height, $is_intermediate );
		}
		return false;
	}

	/**
	 * Rewrites urls of intermediate size output.
	 *
	 * @since 4.4.0
	 *
	 * @param array        $data    Array of file relative path, width, and height on success. May also include
	 *                              file absolute path and URL.
	 * @param int          $post_id The post_id of the image attachment.
	 * @param string|array $size    Registered image size or flat array of initially-requested height and width
	 *                              dimensions (in that order).
	 *
	 * @return array
	 */
	public function rewrite_intermediate_size_url( $data, $post_id, $size ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
			return $data;
		}

		$rokka_hash = get_post_meta( $post_id, 'rokka_hash', true );

		$filename = $data['file'];
		$data['url'] = $this->rokka_helper->get_rokka_url( $rokka_hash, $filename, $size );
		return $data;
	}

	/**
	 * Rewrite image srcset meta
	 *
	 * @since 4.4.0
	 *
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param array  $size_array    Array of width and height values in pixels (in that order).
	 * @param string $image_src     The 'src' of the image.
	 * @param int    $attachment_id The image attachment ID or 0 if not supplied.
	 *
	 * @return array
	 */
	public function rewrite_srcset_meta( $image_meta, $size_array, $image_src, $attachment_id ) {
		if ( ! $this->rokka_helper->is_on_rokka( $attachment_id ) ) {
			return $image_meta;
		}

		// copy original image meta data
		$rewritten_image_meta = $image_meta;
		// rewrite attachment meta data to rokka filenames
		// this is a pretty nasty hack to get the $src_matched boolean in media.php to be true (and srcset gets printed)
		$rewritten_image_meta['file'] = $this->rokka_helper->sanitize_rokka_filename( wp_basename( $image_meta['file'] ) );
		foreach ( $image_meta['sizes'] as $size_name => $size ) {
			$rewritten_image_meta['sizes'][ $size_name ]['file'] = $this->rokka_helper->sanitize_rokka_filename( $size['file'] );
		}

		return $rewritten_image_meta;
	}

	/**
	 * Filters an image's 'srcset' sources.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $sources {
	 *     One or more arrays of source data to include in the 'srcset'.
	 *
	 *     @type array $width {
	 *         @type string $url        The URL of an image source.
	 *         @type string $descriptor The descriptor type used in the image candidate string,
	 *                                  either 'w' or 'x'.
	 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
	 *                                  pixel density value if paired with an 'x' descriptor.
	 *     }
	 * }
	 * @param array  $size_array    Array of width and height values in pixels (in that order).
	 * @param string $image_src     The 'src' of the image.
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id Image attachment ID or 0.
	 *
	 * @return array
	 */
	public function rewrite_image_srcset_sources( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! $this->rokka_helper->is_on_rokka( $attachment_id ) ) {
			return $sources;
		}

		$rokka_hash = get_post_meta( $attachment_id, 'rokka_hash', true );

		$rewritten_sources = array();
		foreach ( $sources as $source_width => $source ) {
			// copy original source data
			$rewritten_sources[ $source_width ] = $source;

			$size_name = $this->rokka_helper->get_size_by_image_url( $attachment_id, $source['url'], $image_meta );

			if ( array_key_exists( $size_name, $image_meta['sizes'] ) ) {
				$filename = $image_meta['sizes'][ $size_name ]['file'];
			} else {
				// if size is not available in meta data use original filename
				$filename = wp_basename( $image_meta['file'] );
			}

			$rewritten_sources[ $source_width ]['url'] = $this->rokka_helper->get_rokka_url( $rokka_hash, $filename, $size_name );
		}

		return $rewritten_sources;
	}

}
