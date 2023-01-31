<?php
function rokka_integration_theme_setup() {
	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		)
	);

	// Add support for editor styles.
	add_post_type_support( 'page', 'excerpt' );

	rokka_define_image_sizes();
}
add_action( 'after_setup_theme', 'rokka_integration_theme_setup' );

add_filter( 'max_srcset_image_width', 'rokka_max_srcset_image_width', 10, 0 );

function rokka_max_srcset_image_width() {
	return 3000;
}

add_filter( 'big_image_size_threshold', function () {
	return 100;
} );

/**
 * Define image sizes to test rokka stacks
 */
function rokka_define_image_sizes() {
	// Image size medium 375w <unlimited>h
	update_option( 'medium_size_w', 370 );
	update_option( 'medium_size_h', 9999 );

	// Image size medium_large 750w <unlimited>h
	update_option( 'medium_large_size_w', 735 );
	update_option( 'medium_large_size_h', 9999 );

	// Image size medium-large-to-large 1250w <unlimited>h
	add_image_size( 'medium-large-to-large', 1250, 9999 );

	// Image size responsive can be used to stretch image to the full with of the container.
	add_image_size( 'responsive', 1250, 9999 );

	// Image size large 1500w <unlimited>h
	update_option( 'large_size_w', 1500 );
	update_option( 'large_size_h', 9999 );

	// Image size colmd4-image is used render different sizes attribute for images in a col-md-4 column.
	add_image_size( 'colmd4-image', 1500, 9999 );

	// Image size teaser 750w 375h cropped
	add_image_size( 'teaser', 750, 375, false );

	add_image_size( 'ratio-2-to-1-2000w', 2000, 1000, true );
	add_image_size( 'ratio-2-to-1-1000w', 1000, 500, true );
	add_image_size( 'ratio-2-to-1-750w', 750, 375, true );
	add_image_size( 'ratio-2-to-1-500w', 500, 250, true );

	add_filter( 'site_icon_image_sizes', 'rokka_additional_size_icon_sizes', 10 , 1 );
}

function rokka_additional_size_icon_sizes( $site_icons ) {
	return array_merge( $site_icons, array( 1024, 333 ) );
}
