<?php
/**
 * Created by PhpStorm.
 * User: philou
 * Date: 06/02/17
 * Time: 14:37
 */



class Class_Rokka_Helper
{
    /**
     *
     */
    const rokka_url = 'https://api.rokka.io';

    const allowed_mime_types = ['image/jpeg', 'image/tiff', 'image/png'];

    /**
     * Class_Rokka_Helper constructor.
     */
    public function __construct() {

    }


    /**
     * @return \Rokka\Client\Image
     */
    public function rokka_get_client()
    {
        return \Rokka\Client\Factory::getImageClient(get_option('rokka_company_name'), get_option('rokka_api_key'), get_option('rokka_api_secret'));
    }


    /**
     * @param $post_id
     * @param $data
     * @return array $data
     */
    public function upload_image_to_rokka($post_id, $data){
        var_dump($data);
        $this->validate_files_before_upload($post_id);
        $file_paths = $this->get_attachment_file_paths($post_id, true, $data);
        $client = $this->rokka_get_client();
        $fileParts = explode('/', $file_paths['full']);
        $fileName = array_pop($fileParts);
        $sourceImage = $client->uploadSourceImage(file_get_contents($file_paths['full']), $fileName);
        //file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r($sourceImage,true) . PHP_EOL, FILE_APPEND);

        if (is_object($sourceImage)) {
            $sourceImages = $sourceImage->getSourceImages();
            $sourceImage = array_pop($sourceImages);
            $url = self::rokka_url . $sourceImage->link . '.' . $sourceImage->format;
            //todo allenfalls stacks in array integrieren.
            $rokka_info = array(
                'url' => $url,
                'hash' => $sourceImage->hash,
                'format' => $sourceImage->format,
                'organization' => $sourceImage->organization,
                'link' => $sourceImage->link,
                'local_files_removed' => $file_paths,
                'created' => $sourceImage->created,
            );
            update_post_meta($post_id, 'rokka_info', $rokka_info);

            return $data;
        }

        return false;
    }


    /**
     * Deletes an image from rokka.io
     * @param $post_id
     * @return bool
     */
    public function delete_image_from_rokka($post_id){
        $meta_data = get_post_meta($post_id, 'rokka_info', true);

        if($meta_data) {
            $client = $this->rokka_get_client();
            $hash = $meta_data['hash'];
            file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r($hash,true) . PHP_EOL, FILE_APPEND);

            return $client->deleteSourceImage($hash);
        }

        return false;
    }


    /**
     * @ignore this function is not implemented properly nor used at this point
     */
    private function remove_local_files(){
        //todo allow wp to remove file from local filesystem
        //$remove_local_files_setting = get_setting( 'remove-local-file' );
        $remove_local_files_setting = false;
        //todo not implemented yet
        if ($remove_local_files_setting) {
            // Remove duplicates
            $files_to_remove = array_unique($files_to_remove);
            // Delete the files
            //todo implement this if you know how to do it
            remove_local_files($files_to_remove);
        }
    }


    private function validate_files_before_upload($post_id){
        //the meta stuff should be possible here too
        $file_path = get_attached_file($post_id, true);
        $file_name = basename($file_path);
        $type = get_post_mime_type($post_id);
        $allowed_types = self::allowed_mime_types;

        // check mime type of file is in allowed S3 mime types
        if (!in_array($type, $allowed_types)) {
            $error_msg = sprintf(__('Mime type %s is not allowed in rokka', 'rokka-image-cdn'), $type);
            //todo implement
            return return_upload_error($error_msg, $return_metadata);
        }

        // Check file exists locally before attempting upload
        if (!file_exists($file_path)) {
            $error_msg = sprintf(__('File %s does not exist', 'rokka-image-cdn'), $file_path);

            return return_upload_error($error_msg, $return_metadata);
        }

        return true;
    }

    /**
     * Get file paths for all attachment versions.
     *
     * @param int $attachment_id
     * @param bool $exists_locally
     * @param array|bool $meta
     * @param bool $include_backups
     *
     * @return array
     */
    function get_attachment_file_paths($attachment_id, $exists_locally = true, $meta = false, $include_backups = true)
    {
        $paths = array();
        $file_path = get_attached_file($attachment_id, true);
        $file_name = basename($file_path);
        $backups = get_post_meta($attachment_id, '_wp_attachment_backup_sizes', true);

        if (!$meta) {
            $meta = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
        }

        if (is_wp_error($meta)) {
            return $paths;
        }

        $original_file = $file_path; // Not all attachments will have meta

        if (isset($meta['file'])) {
            $original_file = str_replace($file_name, basename($meta['file']), $file_path);
        }

        // Original file
        $paths['full'] = $original_file;

        // Sizes
        if (isset($meta['sizes'])) {
            foreach ($meta['sizes'] as $size => $file) {
                if (isset($file['file'])) {
                    $paths[$size] = str_replace($file_name, $file['file'], $file_path);
                }
            }
        }

        // Thumb
        if (isset($meta['thumb'])) {
            $paths[] = str_replace($file_name, $meta['thumb'], $file_path);
        }

        // Backups
        if ($include_backups && is_array($backups)) {
            foreach ($backups as $backup) {
                $paths[] = str_replace($file_name, $backup['file'], $file_path);
            }
        }

        // Allow other processes to add files to be uploaded
        $paths = apply_filters('rokka_attachment_file_paths', $paths, $attachment_id, $meta);

        // Remove duplicates
        $paths = array_unique($paths);

        // Remove paths that don't exist
        if ($exists_locally) {
            foreach ($paths as $key => $path) {
                if (!file_exists($path)) {
                    unset($paths[$key]);
                }
            }
        }

        return $paths;
    }

    /**
     *
     * @return array
     */
    function rokka_create_stacks()
    {
        // require_once wp-includes/media.php
        $sizes = $this->list_thumbnail_sizes();
        $client = $this->rokka_get_client();

        $stacks = $client->listStacks();

        if (!empty($sizes)) {
            foreach ($sizes as $name => $size) {
                $continue = true;

                foreach ($stacks->getStacks() as $stack) {

                    if ($stack->name == $name) {
                        $continue = false;
                        continue;
                    }
                }
                if ($continue && $size[0] > 0) {
                    $resize = new \Rokka\Client\Core\StackOperation('resize', [
                        'width' => $size[0],
                        //'height' => $size[1]
                        'height' => 10000,
                        //aspect ratio will be kept
                        'mode' => 'box'
                    ]);

                    $return = $client->createStack($name, [$resize]);
                }
            }
        }

        return $sizes;
    }

    /**
     * @return array
     */
    public function list_thumbnail_sizes()
    {
        global $_wp_additional_image_sizes;
        $sizes = array();
        $rSizes = array();
        foreach (get_intermediate_image_sizes() as $s) {
            $sizes[$s] = array(0, 0);
            if (in_array($s, array('thumbnail', 'medium', 'large'))) {
                $sizes[$s][0] = get_option($s . '_size_w');
                $sizes[$s][1] = get_option($s . '_size_h');
            } else {
                if (isset($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes[$s])) {
                    $sizes[$s] = array(
                        $_wp_additional_image_sizes[$s]['width'],
                        $_wp_additional_image_sizes[$s]['height'],
                    );
                }
            }
        }

        foreach ($sizes as $size => $atts) {
            $rSizes[$size] = $atts;
        }

        return $rSizes;
    }

}