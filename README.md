Gumlet Wordpress Plugin
=======================

Official [WordPress plugin](https://wordpress.org/plugins/gumlet/) to automatically load all your existing (and future) WordPress images via the [Gumlet](https://www.gumlet.com/) service for smaller, faster, and better looking images.

* [Features](#features)
* [Getting Started](#getting-started)
* [Testing](#testing)

<a name="features"></a>
Features
--------

* Your images behind a CDN.
* Automatically smaller and faster images with the [Auto Format](https://docs.gumlet.com/developers/api-reference#format) option.
* Automatically compress images with [Auto Compress](https://docs.gumlet.com/developers/api-reference#compress) option.
* Use arbitrary [Gumlet API params](https://docs.gumlet.com/developers/api-reference) when editing `<img>` tags in "Text mode" and they will pass through.
* No lock in! Disable the plugin and your images will be served as they were before installation.

Getting Started
---------------

1. If you don't already have an Gumlet account then sign up at [gumlet.com](https://www.gumlet.com).

2. Create a [Web Folder](https://docs.gumlet.com/getting-started/setup-image-source#web-folders) gumlet source with the `Base URL` set to your WordPress root URL (__without__ the `wp-content` part). For example, if your WordPress instance is at [http://example.com](http://example.com) and an example image is `http://example.com/wp-content/uploads/2017/01/image.jpg` then your source's `Base URL` would be just `http://example.com/`.

3. [Download](https://github.com/gumlet/wordpress-plugin/releases) the gumlet WordPress plugin `gumlet_plugin.zip` and install on your WordPress instance. In the WordPress admin, click "Plugins" on the right and then "Add New". This will take you to a page to upload the `gumlet_plugin.zip` file. Alternatively, you can extract the contents of `gumlet_plugin.zip` into the `wp-content/plugins` directory of your WordPress instance.

4. Return to the "Plugins" page and ensure the "gumlet plugin" is activated. Once activated, click the "settings" link and populate the "Gumlet Host" field (e.g., `https://yourcompany.gumlet.com`). This is the full host of the gumlet source you created in step #1. Optionally, you can also turn on `Auto Format` or `Auto Compress`. Finally, click "Save Options" when you're done.

5. Go to a post on your WordPress blog and ensure your images are now serving through gumlet.

Testing
-------

This plugin uses phpunit to run its tests. You will need to set up a local test database to run these tests. You can do that using the bootstrap script:

```
$ bin/install-wp-tests.sh gumlet_wordpress_tests <YOUR MYSQL USERNAME> <YOUR MYSQL PASSWORD>
```

Then running the tests is as simple as:

```
$ phpunit
```

Testing with Docker
-------

This plugin uses phpunit to run its tests. You can use Docker if you don't want to set up the test database locally.

Start the database:
```
$ docker-compose up -d mysql
```

Then running the tests is as simple as:
```
$ docker-compose up phpunit
```
