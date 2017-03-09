<?php
/**
 * Created by PhpStorm.
 * User: philou
 * Date: 07.03.17
 * Time: 10:47
 */

class Filter_Rokka_Content
{

	private $rokka_client;

	/**
	 * filter_rokka_content constructor.
	 */
	function __construct($rokka_client)
	{
		$this->rokka_client = $rokka_client;
		$this->init();
	}

	 function init () {
		add_filter( 'the_content', array( $this, 'filter_post' ), 1, 1 );

	}

	function filter_post($content) {
		$replace_array = $this->get_url_pairs($content);
		$content = $this->replace_content($content, $replace_array);

		return $content;
	}

	protected function replace_content($content, $replace_array){

		foreach($replace_array as $url_to_be_replaced => $new_url){

			if ($url_to_be_replaced !== $new_url){

				$content = str_replace($url_to_be_replaced,$new_url,$content);
			}
		}

		return $content;
	}

	protected function get_url_pairs($content){
		$replaceArray = array();
		preg_match_all( '/<img [^>]+>/', $content, $matches );
		foreach($matches[0] as $match){

			preg_match_all('/http[^>]+\.[a-z]{3,5}/',$match,$matches2);

			$attachment_info = $this->get_attachment_id( $matches2[0][0] );
			$rokka_data = get_post_meta($attachment_info[0], 'rokka_info');
			if(is_array($rokka_data)){
				$size = $attachment_info[1];
				$rokka_data = $rokka_data[0];
				$url = 'https://' . $rokka_data['organization'] . '.rokka.io/' . $size . '/' . $rokka_data['hash'] . '.' . $rokka_data['format'];
				$replaceArray[$matches2[0][0]] = $url;
			}
			else {
				$replaceArray[$matches2[0][0]] = $url;
			}
		}

		return $replaceArray;
	}


	/**
	 * Get an attachment ID given a URL.
	 *
	 * @param string $url
	 *
	 * @return int Attachment ID on success, 0 on failure
	 */
	function get_attachment_id( $url ) {
		$attachment_id = 0;
		$dir = wp_upload_dir();
		if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
			$file = basename( $url );
			$query_args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'value'   => $file,
						'compare' => 'LIKE',
						'key'     => '_wp_attachment_metadata',
					),
				)
			);
			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {

				foreach ( $query->posts as $post_id ) {
					$meta = wp_get_attachment_metadata( $post_id );
					$original_file       = basename( $meta['file'] );
					$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );

					if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
						$attachment_info[0] = $post_id;
						$attachment_info[1] = array_search ($file, $cropped_image_files);
						break;
					}

				}
			}
		}
		return $attachment_info;
	}



}