=== Rokka Integration ===
Contributors: liip, tschortsch
Donate link: https://rokka.io/
Tags: rokka, image, service, cdn, integration
Requires at least: 4.0
Tested up to: 4.7.3
Stable tag: 1.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to integrate the rokka image service (https://rokka.io).

== Description ==
The rokka image converter supports you in storing your digital images – easy and neat. Whether for handling image formats, SEO attributes or the lightning fast delivery, rokka is just the right tool for your digital images.
This WordPress plugin integrates the rokka image service. All images from your image libary will be synchronized to your rokka account and be served directly through rokka.

== Installation ==

1. Upload the `rokka-integration` directory into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add your rokka credentials in the rokka settings (`Settings > Rokka Settings`)
1. Synchronize your image sizes to rokka (`Settings > Rokka Settings > Sync stacks`)
1. Enable rokka support (`Settings > Rokka Settings > Enable rokka integration`)
1. Start uploading your images to rokka via mass uploader (`Settings > Rokka Settings > Mass upload/delete`) or image by image in the media management (`Media (list mode)`)

== Frequently Asked Questions ==

= What will happen to my image sizes? =

You can synchronize all defined image sizes as so called stacks to rokka.

= An image from rokka can't be loaded anymore. What should I do? =

If an image which was previously uploaded to rokka can't be loaded anymore try comparing the rokka hash in the WordPress attachment edit screen with the hash of the image on rokka.io (https://rokka.io/dashboard/) itself.
If it doesn't match anymore just copy & paste the hash from rokka.io to the WordPress attachment edit screen and save the image.

= What is the stack prefix used for? =

When synchronizing image sizes from WordPress to rokka the name of the size will be prefixed with this value to create a new stack on rokka.
By prefixing the rokka stack names we ensure that already existing stacks on rokka won't be overwritten.
Additionally we are able to identify deleted image sizes on WordPress and delete them on rokka as well.

= I created a new / changed an existing size in WordPress. What do I have to do now? =

Everytime you change something on your size configuration in WordPress you need to re-synchronize the sizes with the rokka stacks.
You can do this in the rokka settings (`Settings > Rokka Settings > Sync stacks`).

== Screenshots ==

1. Media management with rokka integration
2. Rokka settings
3. Rokka stack synchronization
4. Rokka mass uploader

== Changelog ==

= 1.0.0 =

* Initial release of the plugin
