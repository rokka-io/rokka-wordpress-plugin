=== Rokka Integration ===
Contributors: liip, tschortsch
Donate link: https://rokka.io/
Tags: rokka, image, service, cdn, integration
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 1.1.3
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to integrate the [rokka image service](https://rokka.io).

== Description ==
The [rokka image converter](https://rokka.io) supports you in storing your digital images – easy and neat. Whether for handling image formats, SEO attributes or the lightning fast delivery, rokka is just the right tool for your digital images.
This WordPress plugin integrates the rokka image service. All images from your image libary will be synchronized to your rokka account and be served directly through rokka.

= Further Information =

* Documentation: [https://github.com/rokka-io/rokka-wordpress-plugin/wiki](https://github.com/rokka-io/rokka-wordpress-plugin/wiki)
* WordPress Plugin: [https://wordpress.org/plugins/rokka-integration/](https://wordpress.org/plugins/rokka-integration/)
* Website: [https://rokka.io](https://rokka.io)
* GitHub Repository: [https://github.com/rokka-io/rokka-wordpress-plugin](https://github.com/rokka-io/rokka-wordpress-plugin)
* Issue tracker: [https://github.com/rokka-io/rokka-wordpress-plugin/issues](https://github.com/rokka-io/rokka-wordpress-plugin/issues)

== Installation ==

1. Upload the `rokka-integration` directory into the `/wp-content/plugins/` directory
1. Activate the plugin through the `Plugins` menu in WordPress
1. Add your rokka credentials in the rokka settings (`Settings > Rokka Settings`)
1. Synchronize your image sizes to rokka (`Settings > Rokka Settings > Sync stacks`)
1. Enable rokka support (`Settings > Rokka Settings > Enable rokka integration`)
1. Start uploading your images to rokka via mass uploader (`Settings > Rokka Settings > Mass upload/delete`) or image by image in the media management (`Media (list mode)`)

== Frequently Asked Questions ==

= What will happen to my image sizes? =

You can synchronize all defined image sizes as so called stacks to rokka.

= An image from rokka can't be loaded anymore. What should I do? =

If an image which was previously uploaded to rokka can't be loaded anymore try comparing the rokka hash in the WordPress attachment edit screen with the hash of the image on rokka.io ([https://rokka.io/dashboard/](https://rokka.io/dashboard/)) itself.
If it doesn't match anymore just copy & paste the hash from rokka.io to the WordPress attachment edit screen and save the image.

= What is the stack prefix used for? =

When synchronizing image sizes from WordPress to rokka the name of the size will be prefixed with this value to create a new stack on rokka.
By prefixing the rokka stack names we ensure that already existing stacks on rokka won't be overwritten.
Additionally we are able to identify deleted image sizes on WordPress and delete them on rokka as well.

= I created a new / changed an existing size in WordPress. What do I have to do now? =

Everytime you change something on your size configuration in WordPress you need to re-synchronize the sizes with the rokka stacks.
You can do this in the rokka settings (`Settings > Rokka Settings > Sync stacks`).

= Have you found a bug or do you have a feature request? =

Please create a new GitHub issue and let us know: [https://github.com/rokka-io/rokka-wordpress-plugin/issues](https://github.com/rokka-io/rokka-wordpress-plugin/issues)

== Screenshots ==

1. Media management with rokka integration
2. Rokka settings
3. Rokka stack synchronization
4. Rokka mass uploader

== Changelog ==

= 1.1.3 [WIP] =

* [FEATURE] Add Cli-Commands to create stacks and noop-stacks on rokka
* [CHANGE] Use overwrite option to update existsing stack

= 1.1.2 =

* [FIX] Do not delete rokka image if there are other images with the same hash

= 1.1.1 =

* [CHANGE] Save plugin options as booleans
* [FIX] Fix a problem with allowed filenames (slugs) on rokka

= 1.1.0 =

* [FEATURE] Added option to define if previous image should be deleted on rokka if metadata changes

= 1.0.0 =

* Initial release of this plugin
