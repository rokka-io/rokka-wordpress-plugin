=== Rokka Integration ===
Contributors: liip, tschortsch
Donate link: https://rokka.io/
Tags: rokka, image, service, cdn, integration
Requires at least: 4.7
Tested up to: 6.1
Requires PHP: 7.1
Stable tag: 4.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to integrate the [rokka.io](https://rokka.io) image service.

== Description ==
[rokka](https://rokka.io) is digital image processing done right. Store, render and deliver images. Easy and blazingly fast. This Wordpress plugin automatically uploads your pictures to rokka and delivers them in the right format, as light and as fast as possible. And you only pay what you use, no upfront and fixed costs.
This WordPress plugin integrates the rokka image service. All images from your image libary will be synchronized to your rokka account and be served directly through rokka.

= Requirements =
* WordPress >= 4.7
* PHP >= 7.1

= Further Information =

* Documentation: [https://github.com/rokka-io/rokka-wordpress-plugin/wiki](https://github.com/rokka-io/rokka-wordpress-plugin/wiki)
* WordPress Plugin: [https://wordpress.org/plugins/rokka-integration/](https://wordpress.org/plugins/rokka-integration/)
* Website: [https://rokka.io](https://rokka.io)
* GitHub Repository: [https://github.com/rokka-io/rokka-wordpress-plugin](https://github.com/rokka-io/rokka-wordpress-plugin)
* Changelog: [https://github.com/rokka-io/rokka-wordpress-plugin/releases](https://github.com/rokka-io/rokka-wordpress-plugin/releases)
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

= 5.0.0 =

* **Breaking Change [CHANGE] Removed autoformat option**. The `autoformat` option has been removed. This option is now always enabled on every stack. There is no reason anymore to disable this option. Please check if your stacks are up-to-date after updating to this version.
* [COMPATIBILITY] Tested with WordPress 6.1.

= 4.0.0 =

Starting with this version the plugin only supports WordPress version >= 4.7 and PHP versions >= 7.1.
This step was needed to be compatible with the latest rokka PHP client library.

* **[COMPATIBILITY] Dropped support for WordPress < 4.7 and PHP < 7.1**. Please update your website if you still would like to receive updates for this plugin.
* [COMPATIBILITY] Tested with WordPress 6.0.

= 3.3.1 =

* [FIX] The autoformat option wasn't respected in the stack synchronization for the no-operation (full) stack.

= 3.3.0 =

* [FEATURE] Add possibility to remove all rokka hashes from files. This can be useful after copying a database from one environment to another.
* [FIX] Use new `block_editor_settings_all` filter instead of deprecated `block_editor_settings` to disable image editing (WP >= 5.8).
* [NOTICE] Tested with WordPress 5.8.1.

= 3.2.0 =

* [NOTICE] Tested with WordPress 5.5.
* [FIX] Disabled image editing in block editor.

= 3.1.1 =

* [NOTICE] Tested with WordPress 5.4.
* [UPDATE] Update dependency versions

= 3.1.0 =

This release fixes some compatibility issues with WordPress 5.3. The new version of WordPress introduces a `big_image_size_threshold` filter (see: https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/) which limits the image size of new uploaded images. Since this could lead to unexpected behaviour when loading bigger image sizes we disable this filter when the rokka plugin is enabled.

* [FIX] Fix styling issues with WordPress 5.3.
* [NOTICE] Disable new `big_image_size_threshold` filter which was introduced in WordPress 5.3.

= 3.0.1 =

* [IMPROVEMENT] Improve admin settings
* [UPDATE] Update rokka-client-php to v1.10.0

= 3.0.0 =

This release fixes an issue in WordPress where srcsets are generated with images of a ratio that doesn't match the ratio of the requested image size. This happens when the requested size is larger than the original image. When requesting an image size with a fixed ratio and the original image is smaller than this size you would expect to get all smaller sizes of the same ratio.

To achieve this behavior this release changes the way images are downsized by WordPress. **This means that you need to regenerate the thumbnails when you activate and deactivate this plugin.** This can be done for example with the following plugin: [https://wordpress.org/plugins/regenerate-thumbnails/](https://wordpress.org/plugins/regenerate-thumbnails/)

Everything works fine if you don't do it but the fix will then only apply to newly uploaded images.

* [BUGFIX] Fix WordPress bug where srcsets are generated with images of a wrong ratio.
* [BUGFIX] Fix warning in media library if attachment metadata is not yet generated after file upload.
* [UPDATE] Update rokka-client-php to v1.8.0

= 2.0.3 =

* [BUGFIX] Fix WordPress bug where `$detached` parameter in `manage_media_columns`-filter is not set.

= 2.0.2 =

* [BUGFIX] Fetch image meta data if not passed to `get_size_by_image_url()`

= 2.0.1 =

* [BUGFIX] Autoformat option was not set when syncing stacks for the first time.
* [BUGFIX] Backport `wp_image_matches_ratio` function to be compatible with older WordPress versions than 4.6.0
* [BUGFIX] Fix finding of nearest matching size if image is requested with a width/height array instead of a size string

= 2.0.0 =

This is a huge rewrite of the plugin but there shouldn't be any breaking changes.

* [FEATURE] Support attachments which are uploaded through the WordPress REST API (This ensures compatibility to the new Gutenberg editor)
* [FEATURE] Auto disable rokka integration if settings are incomplete
* [UPDATE] Update rokka-client-php to v1.2.0
* [CHANGE] Improve unit test coverage

= 1.2.3 =

* [FEATURE] Add unit tests to test url filtering
* [UPDATE] Update rokka-client-php to v1.0.0
* [FIX] Fix URL filtering when creating srcset
* [FIX] Use correct filenames for different sizes
* [FIX] There shouldn't be an upload error when it's not needed to upload an attachment to rokka

= 1.2.2 =

* [UPDATE] Update rokka-client-php to v0.10.0
* [CHANGE] Improve generation of prefixed stack names

= 1.2.1 =

* [FEATURE] Add autoformat option. If autoformat is enabled, rokka will deliver an image in the usually smaller WebP format instead of PNG or JPG, if the client supports it.
* [FIX] Fix usage of `ROKKA_DOMAIN` constant

= 1.2.0 =

* [FEATURE] Add possibility to define options with constants. Available constants are: `ROKKA_COMPANY_NAME`, `ROKKA_API_KEY` and `ROKKA_STACK_PREFIX`
* [FEATURE] Add possibility to overwrite base settings with constants. Available constants are: `ROKKA_DOMAIN` (default: `rokka.io`) and `ROKKA_SCHEME` (default: `https`)
* [REMOVE] Remove rokka api secret option since it's not used anymore

= 1.1.3 =

* [FEATURE] Add Cli-Commands to create stacks and noop-stacks on rokka
* [CHANGE] Use overwrite option to update existsing stack

= 1.1.2 =

* [FIX] Do not delete rokka image if there are other images with the same hash

= 1.1.1 =

* [CHANGE] Save plugin options as booleans
* [FIX] Fix a problem with allowed filenames (slugs) on rokka

= 1.1.0 =

* [FEATURE] Add option to define if previous image should be deleted on rokka if metadata changes

= 1.0.0 =

* Initial release of this plugin
