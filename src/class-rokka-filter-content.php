<?php
/**
 * Rokka filter content
 *
 * @package rokka-image-cdn
 */

/**
 * Class Rokka_Filter_Content
 */
class Rokka_Filter_Content {

	/**
	 * Rokka helper.
	 *
	 * @var Rokka_Helper
	 */
	private $rokka_helper;

	/**
	 * Uploads folder path.
	 *
	 * @var string
	 */
	private $upload_folder = '/uploads/';

	/**
	 * Rokka_Filter_Content constructor.
	 *
	 * @param Rokka_Helper $rokka_helper Rokka helper.
	 */
	public function __construct( $rokka_helper ) {
		$this->rokka_helper = $rokka_helper;
		$this->init();
	}

	/**
	 * Initialize Rokka_Filter_Content.
	 */
	protected function init() {
		// check if the custom folder is at another location than default
		if ( defined( 'UPLOADS' ) ) {
			$this->upload_folder = '/' . UPLOADS . '/';
		}

		add_action( 'shutdown', array( $this, 'filter_content' ), 9999, 0 );
	}

	/**
	 * Get and parse the DOM before it is rendered.
	 */
	public function filter_content() {
		ob_start();

		$final = '';
		// We'll need to get the number of ob levels we're in, so that we can iterate over each, collecting
		// that buffer's output into the final output.
		$levels = ob_get_level();

		for ( $i = 0; $i < $levels; $i++ ) {
			$final .= ob_get_clean();
		}
		// Apply any filters to the final output

		// @codingStandardsIgnoreStart
		echo $this->process_content( $final );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Processes content.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	protected function process_content( $content ) {
		$replace_array = $this->parse_dom_for_urls( $content );
		$content = $this->replace_content( $content, $replace_array );

		return $content;
	}

	/**
	 * Parses DOM for URLs.
	 *
	 * @param string $content Content.
	 * @return array
	 */
	protected function parse_dom_for_urls( $content ) {
		$matches = null;
		preg_match_all( '/https?:\/\/[^",\'," "]*/', $content, $matches );
		$replace_array = $this->get_url_pairs( $matches );

		return $replace_array;
	}

	/**
	 * Returns an array with original url as key and rokka url as value.
	 *
	 * @param array $matches URLs from DOM.
	 * @return array
	 */
	protected function get_url_pairs( $matches ) {
		$rewritten_urls = array();

		foreach ( $matches[0] as $match ) {
			$attachment_info = $this->get_attachment_info( $match );
			$attachment_id = $attachment_info[0];
			$attachment_size = $attachment_info[1];

			if ( $this->rokka_helper->is_on_rokka( $attachment_id ) ) {
				$rokka_hash = get_post_meta( $attachment_id, 'rokka_hash', true );
				$rokka_info = get_post_meta( $attachment_id, 'rokka_info', true );

				$url = $this->rokka_helper->get_rokka_url( $rokka_hash, $rokka_info['format'], $attachment_size );
				$rewritten_urls[ $match ] = $url;
			}
		}
		return $rewritten_urls;
	}

	/**
	 * Get an attachment info by a given URL.
	 *
	 * @param string $url URL to get attachment info from.
	 *
	 * @return int Attachment info on success, false on failure
	 */
	protected function get_attachment_info( $url ) {
		$attachment_info = false;
		$dir = wp_upload_dir();

		if ( strpos( $url, $this->upload_folder ) ) { // Is URL in uploads directory?
			$relative_location = trim( str_replace( $dir['baseurl'] . '/', '', $url ) );
			$file = basename( $url );
			$query_args = array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'fields' => 'ids',
				'meta_query' => array(
					array(
						'value' => $relative_location,
						'compare' => 'LIKE',
						'key' => '_wp_attachment_metadata',
					),
				),
			);
			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post_id ) {
					$meta = wp_get_attachment_metadata( $post_id );
					$original_file = basename( $meta['file'] );

					if ( ! empty( $meta['sizes'] ) ) {
						$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
						$size = array_search( $file, $cropped_image_files, true );
						if ( ! $size ) {
							$size = $this->rokka_helper->get_rokka_full_size_stack_name();
						}
					} else {
						$size = $this->rokka_helper->get_rokka_full_size_stack_name();
					}

					if ( $original_file === $file || in_array( $file, $cropped_image_files, true ) ) {
						$attachment_info[0] = $post_id;
						$attachment_info[1] = $size;
						break;
					}
				}
			}
		}
		return $attachment_info;
	}

	/**
	 * Replaces the content by finding the url in the array key and replacing it with the url in the array value.
	 *
	 * @param string $content Content.
	 * @param array  $rewritten_urls URLs which needs to be replaced.
	 * @return string
	 */
	protected function replace_content( $content, $rewritten_urls ) {
		foreach ( $rewritten_urls as $url_to_be_replaced => $new_url ) {
			if ( $url_to_be_replaced !== $new_url ) {
				$content = str_replace( $url_to_be_replaced, $new_url, $content );
			}
		}

		return $content;
	}
}
