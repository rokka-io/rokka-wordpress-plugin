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
	public function __construct( Class_Rokka_Helper $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		$this->init();
	}

	/**
	 * Initializes media management.
	 */
	public function init() {
		add_filter( 'wp_get_attachment_image_src', array( $this, 'rewrite_attachment_image_src' ), 10, 4 );
		add_filter( 'set_url_scheme', array( $this, 'keep_url_scheme' ), 10, 3 );
		add_filter( 'wp_get_attachment_url', array( $this, 'rewrite_attachment_url' ), 10, 2 );
		add_filter( 'wp_get_attachment_thumb_url', array( $this, 'rewrite_attachment_thumb_url' ), 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'rewrite_attachment_url_for_js' ), 10, 3 );
		add_filter( 'image_downsize', array( $this, 'downsize_image' ), 10, 3 );
		add_filter( 'image_get_intermediate_size', array( $this, 'rewrite_intermediate_size_url' ), 10, 3 );
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
	public function rewrite_attachment_image_src( $image, $attachment_id, $size = 'thumbnail', $icon = false ) {
		if ( ! $this->rokka_helper->is_on_rokka( $attachment_id ) ) {
			return $image;
		}

		$rokka_data = get_post_meta( $attachment_id, 'rokka_info', true );
		$rokka_hash = get_post_meta( $attachment_id, 'rokka_hash', true );
		$url = $this->rokka_helper->get_rokka_url( $rokka_hash, $rokka_data['format'], $size );

		$image[0] = $url;

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
	public function keep_url_scheme( $url, $scheme, $orig_scheme ) {
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
	public function rewrite_attachment_url( $url, $post_id ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
			return $url;
		}

		$rokka_data = get_post_meta( $post_id, 'rokka_info', true );
		$rokka_hash = get_post_meta( $post_id, 'rokka_hash', true );
		$url = $this->rokka_helper->get_rokka_url( $rokka_hash, $rokka_data['format'] );

		return $url;
	}

	/**
	 * Rewrite thumb url to Rokka.
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

		$rokka_data = get_post_meta( $post_id, 'rokka_info', true );
		$rokka_hash = get_post_meta( $post_id, 'rokka_hash', true );
		$url = $this->rokka_helper->get_rokka_url( $rokka_hash, $rokka_data['format'], 'thumbnail' );

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

		$rokka_data = get_post_meta( $attachment->ID, 'rokka_info', true );
		$rokka_hash = get_post_meta( $attachment->ID, 'rokka_hash', true );
		// The $response array which is sent to JS holds all urls for the available sizes in the following format:
		// https://liip-development.rokka.io/<size>/<filename-from-attachment-metadata>
		// Regenerate the Rokka urls and replace them.
		foreach( $response['sizes'] as $size => $size_details ) {
			$response['sizes'][$size]['url'] = $this->rokka_helper->get_rokka_url( $rokka_hash, $rokka_data['format'], $size );
		}

		return $response;
	}

	/**
	 * Own implementation to downsize images with Rokka urls.
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
		$meta = wp_get_attachment_metadata($id);
		$width = $height = 0;
		$is_intermediate = false;

		$rokka_data = get_post_meta( $id, 'rokka_info', true );
		$rokka_hash = get_post_meta( $id, 'rokka_hash', true );

		// try for a new style intermediate size
		if ( $intermediate = image_get_intermediate_size($id, $size) ) {
			$img_url = $intermediate['url'];
			$width = $intermediate['width'];
			$height = $intermediate['height'];
			$is_intermediate = true;
		}
		elseif ( $size == 'thumbnail' ) {
			// fall back to the old thumbnail
			if ( ($thumb_file = wp_get_attachment_thumb_file($id)) && $info = getimagesize($thumb_file) ) {
				$img_url = $this->rokka_helper->get_rokka_url( $rokka_hash, $rokka_data['format'], 'thumbnail' );
				$width = $info[0];
				$height = $info[1];
				$is_intermediate = true;
			}
		}
		if ( !$width && !$height && isset( $meta['width'], $meta['height'] ) ) {
			// any other type: use the real image
			$width = $meta['width'];
			$height = $meta['height'];
		}

		if ( $img_url) {
			// we have the actual image size, but might need to further constrain it if content_width is narrower
			list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

			return array( $img_url, $width, $height, $is_intermediate );
		}
		return false;
	}

	/**
	 * @param array        $data    Array of file relative path, width, and height on success. May also include
	 *                              file absolute path and URL.
	 * @param int          $post_id The post_id of the image attachment
	 * @param string|array $size    Registered image size or flat array of initially-requested height and width
	 *                              dimensions (in that order).
	 *
	 * @return array
	 */
	public function rewrite_intermediate_size_url( $data, $post_id, $size ) {
		if ( ! $this->rokka_helper->is_on_rokka( $post_id ) ) {
			return $data;
		}

		$rokka_data = get_post_meta( $post_id, 'rokka_info', true );
		$rokka_hash = get_post_meta( $post_id, 'rokka_hash', true );
		$data['url'] = $this->rokka_helper->get_rokka_url( $rokka_hash, $rokka_data['format'], $size );

		return $data;
	}

}
