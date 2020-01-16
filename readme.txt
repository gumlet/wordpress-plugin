=== Gumlet Automatic Image Optimization, Compression, and Lazy Load ===

Contributors: adityapatadia
Tags: images, image management, image manipulation, image optimization, image compression, lazy load images, gumlet, picture, pictures, thumbnails, cdn, content delivery network, jpeg, png, webp
Requires at least: 3.3
Tested up to: 5.3.0
Stable tag: 1.1.2
License: BSD-2
License URI: http://opensource.org/licenses/BSD-2-Clause

Official WordPress plugin to automatically load all your WordPress images via the Gumlet service for smaller, faster, better looking images.

== Description ==

Make your website faster by optimizing your JPEG and PNG images. This plugin automatically resizes, optimizes and compresses all your images by integrating with the popular image compression service Gumlet.

= Why use Gumlet image optimizer? =

* **Automatically optimize** your current and future images.
* **SaaS based** optimization to avoid any load on your server.
* **Original images** are never modified.
* **Automatically resize** all images as per client device size and image container.
* **CDN Delivery** - All your images are delivered via CloudFront CDN.
* **Progressive JPEG** - Display JPEG images more quickly with progressive JPEG encoding.
* **Lazy load** - Defer offscreen images with automatic lazy load built-in.
* **Animated images** - PNG and GIF compression and resize.
* **SVG Support** - Yes, your SVG images can also be compressed and Gumlet optimizes them automatically.
* **WooCommerce** compatible.
* **Retina screens** are supported by delivering right sized image.
* **Analytics** and Usage on the Gumlet dashboard.
* **Color profiles** are automatically translated to standard RGB color.
* **Convert CMYK to RGB** to save more space and add compatibility.
* **No limit** on file size or number of requests.
* **1 GB free** bandwidth every month with unlimited requests.
* **No lock in!** Disable the plugin and your images will be served as they were before installation.

= How does it work? =

Gumlet service sits between your users and your image storage and automatically keeps delivering most optimized images to end users.

Gumlet service includes origin cache, image resize and optimization service, processed cache and CloudFront Global CDN. You are free to choose storage of your choice and you always retain control over your original master images. This also helps you stay away from vendor lock-in.

![how gumlet works](https://demo.gumlet.com/infographics.svg?w=400)

= Getting Started =

It's super easy to get started  with this plugin. Just follow the steps given on [Installation](https://wordpress.org/plugins/gumlet/#installation) page.

= Fix your Google PageSpeed image opportunities =
Is your Google PageSpeed performance test opportunities telling you to:

- Defer Offscreen Images
- Optimize Images
- Properly Size Images
- Serve images with correct dimensions
- Use WebP images
- Or, Serve Images in Next-Gen Formats

Gumlet is the answer with options for automating every aspect of image optimization.

= WooCommerce compatibility =

This plugin is *fully compatible with WooCommerce*. Just go ahead and enable the plugin and your e-commerce store images will be fully optimized.

= Defer Offscreen Images =

Gumlet has lazy load built-in. If your page has a bunch of images below the fold, lazy loading will drastically speed up your page by serving only the images being viewed and delaying others further down the page.

= Automatic Resize =

Gumlet automatically resizes images all your images according to container size and client device. If a person visits your site from an iPhone 6, images will be delivered as per iPhone screen size and DPR. This ensures that unnecessary bandwidth is not wasted by loading huge images on mobile devices.

= WebP Images =

We automatically detect if your users' browser supports WebP images and if it does, we will deliver image in this Next-Gen format. It can reduce the image size by at least 20-30% compared to JPEG.

= Security =

All images are loaded via HTTPS protocol and we use highest security measures to prevent any eavesdropping in communication between client and servers.

== Screenshots ==

1. Gumlet Settings Panel

== Frequently Asked Questions ==

= Q: How many images can I optimize for free? =
A: We have no cap on number of images that can be optimized through our plugin. You will be charged when your image delivery bandwidth exceeds 1 GB in a given month.

= Q: How can I remove the 1 GB limit? =
A: The limit is not hard enforced. When you cross the limit, we will send you invoice at the start of next month. Please pay it in 20 days and enjoy uninterrupted image delivery experience.

= Q: Does Gumlet delete or replace my original full-size images? =
A: Nope. Your original images always remain as they are and we never touch them. We only resize and compress images on-the-fly and cache it in our servers. You retain full control over your original images and they are never overwritten.

= Q: I’m a photographer, can I keep all my EXIF data? =
A: Yes! EXIF data stores camera settings, focal length, date, time and location information in image files. Since we never overwrite your original images, your all EXIF data will always be preserved. We only remove the EXIF data when we deliver images to your users.

== Installation ==

= From your WordPress dashboard =

1. Visit *Plugins > Add New*.
2. Search for 'gumlet' and press the 'Install Now' button for the plugin named 'Gumlet Automatic Image Optimization, Compression, and Lazy Load' by 'Gumlet'.
3. Activate the plugin from your *Plugins* page.
4. Go to the *Settings > Gumlet* page.
5. In the **Gumlet Source** input, enter the **subdomain** you created on https://www.gumlet.com/user/sources. (Check instructions below)

= From WordPress.org =

1. Download the plugin named 'Gumlet Automatic Image Optimization, Compression, and Lazy Load' by 'Gumlet'.
2. Upload the `gumlet` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate the plugin from your *Plugins* page.
4. Go to the *Settings > Gumlet* page.
5. In the **Gumlet Source** input, enter the subdomain you created on https://www.gumlet.com/user/sources. (Check instructions below)

= Creating Source =

1. If you don't already have an Gumlet account then sign up at [gumlet.com](https://www.gumlet.com).
2. Create a [Web Folder](https://docs.gumlet.com/getting-started/setup-image-source#web-folders) gumlet source with the `Base URL` set to your WordPress root URL (__without__ the `wp-content` part). For example, if your WordPress instance is at [http://example.com](http://example.com) and an example image is `http://example.com/wp-content/uploads/2017/01/image.jpg` then your source's `Base URL` would be just `http://example.com/`.
3. Choose a **subdomain** which fits your website name. For example, if your site is https://fashion.com, you can choose subdomain like https://fashion.gumlet.com.
4. Once the source is created, add that subdomain in **Step 5** above.

If you need any help, you can reach out at support@gumlet.com.
