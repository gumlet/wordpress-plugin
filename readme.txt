=== Gumlet - Automatically optimize all images and deliver via CDN ===

Contributors: adityapatadia
Tags: images, image management, image manipulation, image optimization, image optimisation, gumlet, wepb,photo, photos, picture, pictures, thumbnail, thumbnails, upload, batch, cdn, content delivery network
Requires at least: 3.3
Tested up to: 5.2.2
Stable tag: 1.0.4
License: BSD-2
License URI: http://opensource.org/licenses/BSD-2-Clause

Official WordPress plugin to automatically load all your WordPress images via the Gumlet service for smaller, faster, better looking images.

== Description ==

* Your images behind a CDN.
* Automatically smaller and faster images with the [Auto Format](https://docs.gumlet.com/developers/api-reference#format) option.
* Automatically smaller images with the [Auto Compress](https://docs.gumlet.com/developers/api-reference#compress) option.
* Use arbitrary [Gumlet API params](https://docs.gumlet.com/developers/api-reference) when editing `<img>` tags in "Text mode" and they will pass through.
* No lock in! Disable the plugin and your images will be served as they were before installation.

Getting Started
---------------

If you don't already have an Gumlet account then sign up at [gumlet.com](https://www.gumlet.com).

1. Create a `Web Folder` gumlet source with the `Base URL` set to your WordPress root URL (__without__ the `wp-content` part). For example, if your WordPress instance is at [http://example.com](http://example.com) and an example image is `http://example.com/wp-content/uploads/2017/01/image.jpg` then your source's `Base URL` would be just `http://example.com/`.

2. [Download](https://github.com/gumlet/wordpress-plugin/releases) the plugin `wordpress-plugin.zip` and install on your WordPress instance. In the WordPress admin, click "Plugins" on the right and then "Add New". This will take you to a page to upload the `worspress-plugin.zip` file. Alternatively, you can extract the contents of `wordpress-plugin.zip` into the `wp-content/plugins` directory of your WordPress instance.

3. Return to the "Plugins" page and ensure the "gumlet plugin" is activated. Once activated, click the "settings" link and populate the "Gumlet Host" field (e.g., `http://yourcompany.gumlet.com`). This is the full host of the gumlet source you created in step #1. Optionally, you can also turn on [Auto Format](https://docs.gumlet.com/developers/api-reference#format) or [Auto Compress](https://docs.gumlet.com/developers/api-reference#compress). Finally, click "Save Options" when you're done.

4. Go to a post on your WordPress blog and ensure your images are now serving through Gumlet.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/gumlet-wordpress-plugin` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->gumlet screen to configure the plugin

__Note__ An Gumlet account is required for this plugin to work.

== Frequently Asked Questions ==

Qustions? Email support@gumlet.com

== Screenshots ==

1. Gumlet Settings Panel
