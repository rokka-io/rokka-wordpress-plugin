<?php
/**
 * Created by PhpStorm.
 * User: tschortsch
 * Date: 07.03.17
 * Time: 10:47
 */

class Filter_Rokka_Image_Editor {
	private $rokka_client;

	/**
	 * Filter_Rokka_Image_Editor constructor.
	 *
	 * @param Class_Rokka_Helper $rokka_helper
	 */
	function __construct( $rokka_helper ) {
		$this->rokka_client = $rokka_helper->rokka_get_client();
		$this->init();
	}

	 function init () {
		add_filter( 'wp_image_editor_before_change', array( $this, 'save_image_changes' ), 10, 2 );
	}

	public function save_image_changes( $image, $changes ) {
		if ( ! $image instanceof WP_Image_Editor ) {
			return $image;
		}

		if( ! empty( $_REQUEST['do'] ) && 'save' == $_REQUEST['do'] && ! empty( $_REQUEST['postid'] ) ) {
			$post_id = $_REQUEST['postid'];
			$meta_data = get_post_meta( $post_id, 'rokka_info', true );

			if( ! $meta_data ) {
				return $image;
			}

			$hash = $meta_data[ 'hash' ];

			foreach ( $changes as $operation ) {
				switch ( $operation->type ) {
					case 'rotate':
						$angle = $operation->angle;
						if ( $angle != 0 ) {
							if( $angle > 0 ) {
								// clockwise rotation in wp is done in negative angles
								$angle -= 360;
							}
							$angle = abs( $angle );
						}
						break;
					case 'crop':
						$sel = $operation->sel;
						$subject_area = new Rokka\Client\Core\DynamicMetadata\SubjectArea( $sel->x, $sel->y, $sel->w, $sel->h);
						$hash = $this->rokka_client->setDynamicMetadata( $subject_area, $hash );
						$meta_data['hash'] = $hash;
						update_post_meta( $post_id, 'rokka_info', $meta_data );
						break;
				}
			}
		}
		return $image;
	}

}