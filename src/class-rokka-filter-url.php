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
		add_filter( 'wp_get_attachment_image_src', array( $this, 'rewrite_attachment_image_src' ), 10, 4 );
		add_filter( 'set_url_scheme', array( $this, 'keep_url_scheme' ), 10, 3 );
		//add_filter( 'wp_get_attachment_url', array( $this, 'rewrite_attachment_url' ), 10, 2 );
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
	 * @param array|false  $image         Either array with src, width & height, icon src, or false.
	 * @param int          $attachment_id Image attachment ID.
	 * @param string|array $size          Size of image. Image size or array of width and height values
	 *                                    (in that order). Default 'thumbnail'.
	 * @param bool         $icon          Whether the image should be treated as an icon. Default false.
	 *
	 * @return false|array Returns an array (url, width, height, is_intermediate), or false, if no image is available.
	 */
	function rewrite_attachment_image_src( $image, $attachment_id, $size = 'thumbnail', $icon = false ) {
		$rokka_data = get_post_meta( $attachment_id, 'rokka_info', true );
		$rokka_hash = get_post_meta( $attachment_id, 'rokka_hash', true );

		if ( $rokka_hash ) {
			$stack = null;

			// if size is requests as width / height array -> guess rokka size
			if ( is_array( $size ) ) {
				$rokka_sizes = $this->rokka_helper->list_thumbnail_sizes();
				foreach ( $rokka_sizes as $size_name => $size_values ) {
					if ( $size[0] <= $size_values[0] ) {
						$stack = $size_name;
						break;
					}
				}
				if ( is_null( $stack ) ) {
					$stack = 'large';
				}
			} else {
				$stack = $size;
			}

			$url = $this->rokka_helper->get_rokka_url( $stack, $rokka_hash, $rokka_data['format'] );

			$image[0] = $url;
		}

		return $image;
	}

	/**
	 * Keep url scheme in Rokka urls.
	 *
	 * @param string      $url         The complete URL including scheme and path.
	 * @param string      $scheme      Scheme applied to the URL. One of 'http', 'https', or 'relative'.
	 * @param string|null $orig_scheme Scheme requested for the URL. One of 'http', 'https', 'login',
	 *                                 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
	 *
	 * @return string
	 */
	function keep_url_scheme( $url, $scheme, $orig_scheme ) {
		if( false !== strpos( $url, $this->rokka_helper->get_rokka_domain() ) ) {
			$url = str_replace( $scheme . '://', $this->rokka_helper->get_rokka_scheme() . '://', $url );
		}
		return $url;
	}

	/**
	 * Rewrite url to Rokka.
	 *
	 * @param string $url     URL for the given attachment.
	 * @param int    $post_id Attachment ID.
	 *
	 * @return string
	 */
	function rewrite_attachment_url( $url, $post_id ) {
		return $url;
	}

}
