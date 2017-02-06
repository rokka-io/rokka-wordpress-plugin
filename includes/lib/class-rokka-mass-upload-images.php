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
    }


    /**
     * Main function to check if there are images that need to be uploaded to rokka
     */
    public function process_all_images()
    {

        $image_posts = $this->get_all_images();
        //die(var_dump($image_posts));

        foreach ($image_posts as $image) {
            if (empty(get_post_meta($image->ID, 'rokka_info', true))) {
                $image_data = get_post_meta($image->ID, '_wp_attachment_metadata', true);

                $this->rokka_helper->upload_image_to_rokka($image->ID, $image_data);
                //var_dump($image);

            }
        }
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
        );

        $query_images = new WP_Query($query_images_args);

        return $query_images->posts;
    }
}