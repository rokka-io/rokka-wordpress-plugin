<?php
/**
 * Created by PhpStorm.
 * User: philou
 * Date: 06/02/17
 * Time: 09:00
 */


class Class_Rokka_Mass_Upload_Images
{


    /**
     * @var Class_Rokka_Helper
     */
    private $rokka_helper;


    /**
     * class_rokka_mass_upload_images constructor.
     * @param $rokka_helper
     */
    public function __construct(Class_Rokka_Helper $rokka_helper)
    {
        $this->rokka_helper = $rokka_helper;
        add_action( 'wp_ajax_rokka_upload_image', array($this, 'rokka_upload_image') );

    }


    function rokka_upload_image() {

        try {
            $image_id = $_POST['id'];

            if (empty(get_post_meta($image_id, 'rokka_info', true))) {
                $image_data = wp_get_attachment_metadata($image_id);

                    $data = $this->rokka_helper->upload_image_to_rokka($image_id, $image_data);

                if($data){
                    wp_send_json_success($image_id);
                }
                else {
                    wp_send_json_error($data);
                }
            }
            else {
                wp_send_json_error("This image is already on rokka. No need to upload it another time");
            }
            wp_die(); // this is required to terminate immediately and return a proper response

        }
        catch (Exception $e){
            wp_send_json_error($e->getMessage());
            wp_die();
        }
    }



    /**
     * Main function to check if there are images that need to be uploaded to rokka
     */
    public function process_all_images()
    {

        $image_posts = $this->get_all_images();

        foreach ($image_posts as $image) {
            if (empty(get_post_meta($image->ID, 'rokka_info', true))) {
                $image_data = wp_get_attachment_metadata($image->ID);

                $this->rokka_helper->upload_image_to_rokka($image->ID, $image_data);

            }
        }
    }

    public function get_images_for_upload() {

        $image_ids = $this->get_all_images();

        $image_ids = array_filter($image_ids, function($image_id) {
            return ! $this->is_on_rokka($image_id);
        });

        return $image_ids;
    }

    public function is_on_rokka($image_id) {
        return ! empty(get_post_meta($image_id, 'rokka_info', true));
    }


    /**
     * @return array
     */
    private function get_all_images()
    {
        global $wp_query;
        $query_images_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );

        $query_images = new WP_Query($query_images_args);

        return $query_images->posts;
    }
}