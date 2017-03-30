<?php


/**
 * Class Filter_Rokka_Content
 */
class Filter_Rokka_Content
{

	/**
	 * @var
	 */
	private $rokka_helper;

	/**
	 * @var string
	 */
	private $upload_folder = '/uploads/';


	/**
	 * filter_rokka_content constructor.
	 */
	function __construct($rokka_helper)
	{
		$this->rokka_helper = $rokka_helper;
		$this->init();
	}


	/**
	 *
	 */
	function init()
	{
		//check if the custom folder is at another location than default
		if (defined('UPLOADS')) {
			$this->upload_folder = '/' . UPLOADS . '/';
		}

		$this->filter_content();
	}


	/**
	 * Get and parse the DOM before it is rendered
	 */
	protected function filter_content()
	{
		ob_start();

		add_action('shutdown', function () {
			$final = '';
			// We'll need to get the number of ob levels we're in, so that we can iterate over each, collecting
			// that buffer's output into the final output.
			$levels = ob_get_level();

			for ($i = 0; $i < $levels; $i++) {
				$final .= ob_get_clean();
			}
			// Apply any filters to the final output
			echo $this->process_content($final);
		}, 0);
	}

	/**
	 * @param $content
	 * @return mixed
	 */
	function process_content($content)
	{
		$replace_array = $this->parse_dom_for_urls($content);
		$content = $this->replace_content($content, $replace_array);

		return $content;
	}

	/**
	 * @param $content
	 * @return mixed
	 */
	protected function parse_dom_for_urls($content)
	{

		$matches = null;
		preg_match_all('/https?:\/\/[^",\'," "]*/', $content, $matches);
		$replaceArray = $this->get_url_pairs($matches);

		return $replaceArray;
	}

	/**
	 * Returns an array with original url as key and rokka url as value
	 * @param $matches
	 * @return mixed
	 */
	protected function get_url_pairs($matches)
	{

		foreach ($matches[0] as $match) {
			$attachment_info = null;
			$attachment_info = $this->get_attachment_id($match);
			$rokka_data = null;
			$rokka_data = get_post_meta($attachment_info[0], 'rokka_info');

			if (is_array($rokka_data)) {
				$url = null;
				$size = $attachment_info[1];
				$rokka_data = $rokka_data[0];
				$url = 'https://' . $rokka_data['organization'] . '.rokka.io/' . $size . '/' . $rokka_data['hash'] . '.' . $rokka_data['format'];
				$replaceArray[$match] = $url;
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
	function get_attachment_id($url)
	{

		$attachment_info = false;
		$attachment_id = 0;
		$dir = wp_upload_dir();

		if (strpos($url, $this->upload_folder)) { // Is URL in uploads directory?
			$relative_location = trim(str_replace($dir['baseurl'] . '/', '', $url));
			$file = basename($url);
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
				)
			);
			$query = new WP_Query($query_args);

			if ($query->have_posts()) {

				foreach ($query->posts as $post_id) {
					$meta = wp_get_attachment_metadata($post_id);
					$original_file = basename($meta['file']);

					if (!empty($meta['sizes'])) {

						$cropped_image_files = wp_list_pluck($meta['sizes'], 'file');

						if (!$size = array_search($file, $cropped_image_files)) {
							$size = $this->rokka_helper->get_rokka_full_size_stack_name();
						}
					} else {
						$size = $this->rokka_helper->get_rokka_full_size_stack_name();
					}

					if ($original_file === $file || in_array($file, $cropped_image_files)) {
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
	 * Replaces the content by finding the array key and replacing it with the replace_array value
	 * @param $content
	 * @param $replace_array
	 * @return mixed
	 */
	protected function replace_content($content, $replace_array)
	{

		foreach ($replace_array as $url_to_be_replaced => $new_url) {

			if ($url_to_be_replaced !== $new_url) {

				$content = str_replace($url_to_be_replaced, $new_url, $content);
			}
		}

		return $content;
	}
}
