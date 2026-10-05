Gumlet Wordpress Plugin
=======================

Official [WordPress plugin](https://wordpress.org/plugins/gumlet/) to automatically load all your existing (and future) WordPress images via the [Gumlet](https://www.gumlet.com/) service for smaller, faster, and better looking images.

* [Features](#features)
* [Getting Started](#getting-started)
* [Signed URLs](#signed-urls)
* [Testing](#testing)

<a name="features"></a>
Features
--------

* Your images behind a CDN.
* Automatically smaller and faster images with the [Auto Format](https://docs.gumlet.com/reference/image-formats#format-fm) option.
* Automatically compress images with [Auto Compress](https://docs.gumlet.com/reference/image-formats#compress) option.
* Use arbitrary [Gumlet API params](https://docs.gumlet.com/reference/image-formats) when editing `<img>` tags in "Text mode" and they will pass through.
* No lock in! Disable the plugin and your images will be served as they were before installation.
* Added support for Gumlet Video Embed.
* Experimental [signed URLs](#signed-urls), with an optional expiry, so unsigned or modified image requests are rejected. Auto Resize stays off while this is enabled.

Getting Started
---------------

1. If you don't already have an Gumlet account then sign up at [gumlet.com](https://www.gumlet.com).

2. Create a [Web Folder](https://docs.gumlet.com/docs/configure-image-source) gumlet source with the `Base URL` set to your WordPress root URL (__without__ the `wp-content` part). For example, if your WordPress instance is at [http://example.com](http://example.com) and an example image is `http://example.com/wp-content/uploads/2017/01/image.jpg` then your source's `Base URL` would be just `http://example.com/`.

3. [Download](https://github.com/gumlet/wordpress-plugin/releases) the gumlet WordPress plugin `gumlet_plugin.zip` and install on your WordPress instance. In the WordPress admin, click "Plugins" on the right and then "Add New". This will take you to a page to upload the `gumlet_plugin.zip` file. Alternatively, you can extract the contents of `gumlet_plugin.zip` into the `wp-content/plugins` directory of your WordPress instance.

4. Return to the "Plugins" page and ensure the "gumlet plugin" is activated. Once activated, click the "settings" link and populate the "Gumlet Host" field (e.g., `https://yourcompany.gumlet.io`). This is the full host of the gumlet source you created in step #1. Optionally, you can also turn on `Auto Format` or `Auto Compress`. Finally, click "Save Options" when you're done.

5. Go to a post on your WordPress blog and ensure your images are now serving through gumlet.

<a name="signed-urls"></a>
Signed URLs (experimental)
--------------------------

Use this when a client should not be able to reuse their Gumlet host, or change width and other parameters, and consume CDN bandwidth.

1. In the Gumlet dashboard, open the image source and the Security tab. Turn on Secure URLs and copy the secure token. Leave the source setting off until the plugin is saving signatures, or existing images will return 403.
2. In WordPress, open Settings → Gumlet → Experimental. Turn on Signed URLs, paste the token, and save.
3. Expiry is optional. Leave it blank and signed URLs do not expire. Set a duration in seconds (3600 is one hour) to add Gumlet's `expires` timestamp. Gumlet rejects the URL after that and caches the image only until then. Keep any full-page cache shorter than this duration.
4. Turn Secure URLs on for the source, then clear any page cache.

Auto Resize does not run while Signed URLs is enabled. Gumlet.js adds the width in the browser after the URL is signed, and Gumlet rejects the changed URL. Images are served from the signed URL directly, and Gumlet.js lazy loading stays off. The token stays on the server.
