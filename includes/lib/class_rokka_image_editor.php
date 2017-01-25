<?php

/**
 * Created by PhpStorm.
 * User: philou
 * Date: 05/01/17
 * Time: 09:54
 */
class class_rokka_image_editor
{

    /**
     * @var WP_Post
     */
    private $post;

    function __construct($post)
    {
        $this->post = $post;
        $this->process_request();
    }

    public function process_request () {

        if ($this->post['do'] === 'save') {

            switch ($this->post['context']) {
                case 'edit-attachment':
                    $changes = json_decode( wp_unslash($_REQUEST['history']) );
                    $this->process_changes($changes);
                    break;
            }
        }
        else if ($this->post['do'] === 'save') {
            //todo restore image to its original form

        }
        file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r('SPARTAAAAA',true).PHP_EOL, FILE_APPEND);

    }

    protected function process_changes($changes) {


        // Expand change operations.
        foreach ( $changes as $key => $obj ) {
            if ( isset($obj->r) ) {
                $obj->type = 'rotate';
                $obj->angle += $obj->r;
                unset($obj->r);
            } elseif ( isset($obj->c) ) {
                $obj->type = 'crop';
                $obj->sel += $obj->c;
                file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r($obj->c,true).PHP_EOL, FILE_APPEND);

                unset($obj->c);
            }
            $changes[$key] = $obj;
        }

        file_put_contents("/tmp/wordpress.log", __METHOD__ . print_r($changes,true).PHP_EOL, FILE_APPEND);





    }

    protected function calculate_crop_area() {
        if ( $image instanceof WP_Image_Editor ) {
            $size = $image->get_size();
            $w = $size['width'];
            $h = $size['height'];

            $scale = 1 / _image_get_preview_ratio( $w, $h ); // discard preview scaling
            $image->crop( $sel->x * $scale, $sel->y * $scale, $sel->w * $scale, $sel->h * $scale );
        } else {
            $scale = 1 / _image_get_preview_ratio( imagesx( $image ), imagesy( $image ) ); // discard preview scaling
            $image = _crop_image_resource( $image, $sel->x * $scale, $sel->y * $scale, $sel->w * $scale, $sel->h * $scale );
        }
    }

}