<?php
/**
 * Rokka url filter
 *
 * @package rokka-integration
 */

namespace Rokka_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_filter( 'image_resize_dimensions', array( $this, 'image_resize_dimensions_enforce_ratio' ), 10, 6 );
	}

	/**
	 * Filters whether to preempt calculating the image resize dimensions.
	 *
	 * Passing a non-null value to the filter will effectively short-circuit
	 * image_resize_dimensions(), returning that value instead.
	 *
	 * @since 3.4.0
	 *
	 * @param null|mixed $resize_dimensions The resize dimensions which is returned.
	 * @param int        $orig_w Original width in pixels.
	 * @param int        $orig_h Original height in pixels.
	 * @param int        $dest_w New width in pixels.
	 * @param int        $dest_h New height in pixels.
	 * @param bool|array $crop   Whether to crop image to specified width and height or resize.
	 *                           An array can specify positioning of the crop area. Default false.
	 *
	 * @return array|bool|null If null is returned the core implementation continues to calculate the resize dimensions
	 */
	public function image_resize_dimensions_enforce_ratio( $resize_dimensions, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
		// Overwrite resize dimensions if image needs to be cropped.
		// This fixes the problem, that an image with a wrong ratio is generated.
		if ( $crop ) {
			if ( $dest_w > $orig_w || $dest_h > $orig_h ) {
				// If the destination width or height is bigger than the original image skip this size
				return false;
			}

			$aspect_ratio = $orig_w / $orig_h;

			$new_w = $dest_w;
			$new_h = $dest_h;

			// If requested width was 0 (not defined) calculate new width with aspect ratio of the original image
			if ( ! $new_w ) {
				$new_w = (int) round( $new_h * $aspect_ratio );
			}

			// If requested height was 0 (not defined) calculate new height with aspect ratio of the original image
			if ( ! $new_h ) {
				$new_h = (int) round( $new_w / $aspect_ratio );
			}

			$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );

			$crop_w = round( $new_w / $size_ratio );
			$crop_h = round( $new_h / $size_ratio );

			if ( ! is_array( $crop ) || count( $crop ) !== 2 ) {
				$crop = array( 'center', 'center' );
			}

			list( $x, $y ) = $crop;

			if ( 'left' === $x ) {
				$s_x = 0;
			} elseif ( 'right' === $x ) {
				$s_x = $orig_w - $crop_w;
			} else {
				$s_x = floor( ( $orig_w - $crop_w ) / 2 );
			}

			if ( 'top' === $y ) {
				$s_y = 0;
			} elseif ( 'bottom' === $y ) {
				$s_y = $orig_h - $crop_h;
			} else {
				$s_y = floor( ( $orig_h - $crop_h ) / 2 );
			}

			// the return array matches the parameters to imagecopyresampled()
			// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
			return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
		}

		return $resize_dimensions;
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
			if ( array_key_exists( '_rokka_url_rewritten', $intermediate ) && $intermediate['_rokka_url_rewritten'] ) {
				// If image_get_intermediate_size filter exists rokka url is already set in this filter.
				$img_url = $intermediate['url'];
			} else {
				// This is needed to get the rokka url before the image_get_intermediate_size filter was introduced in WP 4.4
				if ( is_array( $size ) ) {
					$size_name = $this->rokka_helper->get_nearest_matching_image_size( $id, $size[0], $size[1] );
				} else {
					$size_name = $size;
				}
				$img_url = $this->rokka_helper->get_rokka_url( $rokka_hash, $intermediate['file'], $size_name );
			}
			$width = $intermediate['width'];
			$height = $intermediate['height'];
			$is_intermediate = true;
		}
		elseif ( $size_with_same_ratio = $this->rokka_helper->get_smaller_image_size_with_same_ratio( $meta['sizes'], $size ) ) {
			$img_url = $this->rokka_helper->get_rokka_url( $rokka_hash, $meta['sizes'][ $size_with_same_ratio ]['file'], $size_with_same_ratio );
			$width = $meta['sizes'][ $size_with_same_ratio ]['width'];
			$height = $meta['sizes'][ $size_with_same_ratio ]['height'];
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

		if ( is_array( $size ) ) {
			$attachment_meta = wp_get_attachment_metadata( $post_id );
			$size_name = $this->rokka_helper->get_size_by_image_name( $post_id, $filename, $attachment_meta );
		} else {
			$size_name = $size;
		}

		$data['url'] = $this->rokka_helper->get_rokka_url( $rokka_hash, $filename, $size_name );
		// Set flag that we can check in image_downsize filter if url has already been rewritten
		$data['_rokka_url_rewritten'] = true;
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
