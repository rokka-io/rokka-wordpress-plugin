<?php

/**
 * Class Rokka_Filter_Url
 */
class Rokka_Filter_Url {

	/**
	 * @var Class_Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Rokka_Filter_Url constructor.
	 *
	 * @param Class_Rokka_Helper $rokka_helper
	 */
	function __construct( Class_Rokka_Helper $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		$this->init();
	}

	/**
	 * Initializes media management.
	 */
	public function init() {
		//add_filter( 'wp_get_attachment_image_src', array( $this, 'rewrite_attachment_image_src' ), 1, 4 );
		//add_filter( 'wp_get_attachment_url', array( $this, 'rokka_get_attachment_url' ) );
	}

	/**
	 * Retrieve an image to represent an attachment.
	 *
	 * A mime icon for files, thumbnail or intermediate size for images.
	 *
	 * The returned array contains four values: the URL of the attachment image src,
	 * the width of the image file, the height of the image file, and a boolean
	 * representing whether the returned array describes an intermediate (generated)
	 * image size or the original, full-sized upload.
	 *
	 *
	 * @param int $attachment_id Image attachment ID.
	 * @param string|array $size Optional. Image size. Accepts any valid image size, or an array of width
	 *                                    and height values in pixels (in that order). Default 'thumbnail'.
	 * @param bool $icon Optional. Whether the image should be treated as an icon. Default false.
	 *
	 * @return false|array Returns an array (url, width, height, is_intermediate), or false, if no image is available.
	 */
	function rewrite_attachment_image_src( $image, $attachment_id, $size = 'thumbnail', $icon = false ) {
		$rokka_data = get_post_meta( $attachment_id, 'rokka_info', true );
		$rokka_hash = get_post_meta( $attachment_id, 'rokka_hash', true );
		$image_data = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		//todo use sizes for stackname, also figure out how to deal with rotation and stuff.
		$sizes = $image_data['sizes'];
		/* example
		[sizes] => Array
			(
				[thumbnail] => Array
					(
						[file] => Lee-e1483459760152-150x150.png
						[width] => 150
						[height] => 150
						[mime-type] => image/png
					)

				[medium] => Array
					(
						[file] => Lee-e1483459760152-310x350.png
						[width] => 310
						[height] => 350
						[mime-type] => image/png
					)

				[post-thumbnail] => Array
					(
						[file] => Lee-512x510.png
						[width] => 512
						[height] => 510
						[mime-type] => image/png
					)

			)

		*/

		if ( $rokka_data ) {
			$sizes      = $this->rokka_helper->list_thumbnail_sizes();
			$sizeString = null;
			if ( is_array( $sizes ) ) {
				$imageSizes = $sizes;
				foreach ( $sizes as $size_name => $sizes_values ) {

					if ( $sizes_values[0] >= $size[0] ) {
						$sizeString = $size_name;
						continue;
					}
				}
				if ( is_null( $sizeString ) ) {
					$sizeString = 'large';
				}

			} else {
				$imageSizes = $sizes[ $size ];
				$sizeString = $size;
			}

			$url = 'https://' . $rokka_data['organization'] . '.rokka.io/' . $sizeString . '/' . $rokka_hash . '.' . $rokka_data['format'];
			//file_put_contents( "/tmp/wordpress.log", __METHOD__ . print_r( $url, true ) . PHP_EOL, FILE_APPEND );

			//todo get thu
			if ( $rokka_data ) {
				$image   = array();
				$image[] = $url;
				$image[] = $imageSizes[0];
				$image[] = $imageSizes[1];

			} else {
				return $image;
			}
		}


		return $image;
	}

	function rokka_get_attachment_url( $url ) {
		//todo remove
		//file_put_contents("/tmp/wordpress.log", 'rokka_get_attachment_url: ' . print_r($url,true) . PHP_EOL, FILE_APPEND);

		return $url;
	}

}
