<?php
/**
 * WordPress Crop Bugfix
 *
 * @package rokka-integration
 */

namespace Rokka_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Crop_Bugfix
 */
class WP_Crop_Bugfix {

	/**
	 * WP_Crop_Bugfix constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initializes media management.
	 */
	public function init() {
		add_filter( 'image_resize_dimensions', array( $this, 'image_resize_dimensions_enforce_ratio' ), 10, 6 );
	}

	/**
	 * Overwrite image resize dimensions if image needs to be cropped.
	 * This fixes the problem, that images with a wrong ratio are generated.
	 * It generates the largest possible cropped images for all defined sizes even if the destination width and height are bigger than the original image.
	 *
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
		if ( $crop ) {
			// Bugfix start
			$aspect_ratio = $orig_w / $orig_h;
			if ( 0 === $dest_w || 0 === $dest_h ) {
				$dest_ratio = $aspect_ratio;
			} else {
				$dest_ratio = $dest_w / $dest_h;
			}

			if ( $dest_w > $orig_w && $dest_h <= $orig_h ) {
				$new_w = $orig_w;
				$new_h = (int) round( $orig_w / $dest_ratio );
			} elseif ( $dest_w <= $orig_w && $dest_h > $orig_h ) {
				$new_w = (int) round( $orig_h * $dest_ratio );
				$new_h = $orig_h;
			} elseif ( $dest_w > $orig_w && $dest_h > $orig_h ) {
				if ( $orig_w / $dest_ratio > $orig_h ) {
					$new_w = (int) round( $orig_h * $dest_ratio );
					$new_h = $orig_h;
				} else {
					$new_w = $orig_w;
					$new_h = (int) round( $orig_w / $dest_ratio );
				}
			} else {
				$new_w = $dest_w;
				$new_h = $dest_h;
			}
			// Bugfix end

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
}
