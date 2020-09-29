<?php

class Gumlet
{

    /**
     * The instance of the class.
     *
     * @var Gumlet
     */
    protected static $instance;

    /**
     * Plugin options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Buffer is started by plugin and should be ended on shutdown.
     *
     * @var bool
     */
    protected $buffer_started = false;

    private $doingAjax = false;

    public static $excludedAjaxActions = array(
        //Add Media popup     Image to editor              Woo product variations
        'query-attachments', 'send-attachment-to-editor', 'woocommerce_load_variations',
        //avia layout builder AJAX calls
        'avia_ajax_text_to_interface', 'avia_ajax_text_to_preview',
        //My Listing theme
        'mylisting_upload_file',
        //Oxygen stuff
        'ct_get_components_tree', 'ct_exec_code'
    );

    /**
     * Gumlet constructor.
     */
    public function __construct()
    {
        $this->options = get_option('gumlet_settings', []);
        $this->logger = GumletLogger::instance();

        $this->doingAjax = (function_exists("wp_doing_ajax") && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX);

        // Change filter load order to ensure it loads after other CDN url transformations i.e. Amazon S3 which loads at position 99.


        // add_filter('wp_get_attachment_url', [ $this, 'replace_image_url' ], 100);
        // add_filter('gumlet/add-image-url', [ $this, 'replace_image_url' ]);

        // add_filter('image_downsize', [ $this, 'image_downsize' ], 10, 3);

        // add_filter('wp_calculate_image_srcset', [ $this, 'calculate_image_srcset' ], 10, 5);

        add_filter('script_loader_tag', [$this,'add_asyncdefer_attribute'], 10, 2);

        add_action('wp_head', [ $this, 'add_prefetch' ], 1);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_script'], 1);

        add_action('init', [$this, 'init_ob'], 1);
        // add_filter('pum_popup_content', [ $this, 'replace_images_in_content' ], PHP_INT_MAX);
        // add_filter('the_content', [ $this, 'replace_images_in_content' ], PHP_INT_MAX);
        // add_filter('post_thumbnail_html', [ $this, 'replace_images_in_content' ], PHP_INT_MAX );
        // add_filter('get_image_tag', [ $this, 'replace_images_in_content' ], PHP_INT_MAX );
            // add_filter('wp_get_attachment_image_attributes', [ $this, 'replace_images_in_content' ], PHP_INT_MAX );
    }

    public function add_asyncdefer_attribute($tag, $handle)
    {
        // if the unique handle/name of the registered script has 'async' in it
        if (strpos($handle, 'async') !== false) {
            // return the tag with the async attribute
            return str_replace('<script ', '<script async ', $tag);
        }
        // if the unique handle/name of the registered script has 'defer' in it
        elseif (strpos($handle, 'defer') !== false) {
            // return the tag with the defer attribute
            return str_replace('<script ', '<script defer ', $tag);
        }
        // otherwise skip
        else {
            return $tag;
        }
    }

    protected function isWelcome()
    {
        // disable for AMP pages
        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return false;
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $admin = parse_url(admin_url());
            $referrer = parse_url($_SERVER['HTTP_REFERER']);
            //don't act on pages being customized (wp-admin/customize.php)
            if (isset($referrer['path']) && ($referrer['path'] === $admin['path'] . 'customize.php' || $referrer['path'] === $admin['path'] . 'post.php')) {
                return false;
            } elseif ($this->doingAjax && $admin['host'] == $referrer['host'] && strpos($referrer['path'], $admin['path']) === 0) {
                return false;
            }
        }
        $referrerPath = (isset($referrer['path']) ? $referrer['path'] : '');
        return !(
            is_feed()
             || strpos($_SERVER['REQUEST_URI'], "/feed/") !== false
             || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
             || (defined('DOING_CRON') && DOING_CRON)
             || (defined('WP_CLI') && WP_CLI)
             || (isset($_GET['PageSpeed']) && $_GET['PageSpeed'] == 'off') || strpos($referrerPath, 'PageSpeed=off')
             ||  isset($_GET['fl_builder']) || strpos($referrerPath, '/?fl_builder') //sssh.... Beaver Builder is editing :)
             ||  isset($_GET['brizy-edit']) || strpos($referrerPath, '/?brizy-edit') //sssh.... Brizy Builder is editing :)
             ||  isset($_GET['brizy-edit-iframe']) || strpos($referrerPath, '/?brizy-edit-iframe') //sssh.... Brizy Builder is editing :)
             || (isset($_GET['tve']) && $_GET['tve'] == 'true') //Thrive Architect editor (thrive-visual-editor/thrive-visual-editor.php)
             || (isset($_GET['ct_builder']) && $_GET['ct_builder'] == 'true') //Oxygen Builder
             || (isset($_GET['action']) && $_GET['action'] == 'ct_render_shortcode') // oxygen templates
             || isset($_GET['gumlet_disable']) // able to disable for debug
             || (isset($_REQUEST['action']) && in_array($_REQUEST['action'], self::$excludedAjaxActions))
             || (is_admin() && function_exists("is_user_logged_in") && is_user_logged_in() && !$this->doingAjax)
        );
    }

    public function enqueue_script()
    {
        if (!empty($this->options['cdn_link']) && $this->isWelcome()) {
            if (isset($this->options['external_cdn_link'])) {
                $external_cdn_host = parse_url($this->options['external_cdn_link'], PHP_URL_HOST);
            }

            wp_register_script('gumlet-script-async', 'https://cdn.gumlet.com/gumlet.js/2.0/gumlet.min.js', array(), '2.0.2', false);
            wp_localize_script('gumlet-script-async', 'gumlet_wp_config', array(
              'gumlet_host' => parse_url($this->options['cdn_link'], PHP_URL_HOST),
              'current_host' => isset($external_cdn_host) ? $external_cdn_host : parse_url(home_url('/'), PHP_URL_HOST),
              'lazy_load' => (!empty($this->options['lazy_load'])) ? 1 : 0,
              'width_from_img' => get_option('gumlet_width_from_img') ? 1 : 0,
              'auto_compress' => (!empty($this->options['auto_compress'])) ? 1 : 0,
              'quality' => (!empty($this->options['quality'])) ? $this->options['quality'] : 80
            ));
            wp_enqueue_script('gumlet-script-async');
        }
    }

    public function init_ob()
    {
        if ($this->isWelcome()) {
            ob_start([$this, 'replace_images_in_content']);
        }
    }

    /**
     * Plugin loader instance.
     *
     * @return Gumlet
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Set a single option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set_option($key, $value)
    {
        $this->options[ $key ] = $value;
    }

    /**
     * Get a single option.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get_option($key, $default = null)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    /**
     * Override options from settings.
     * Used in unit tests.
     *
     * @param array $options
     */
    public function set_options($options)
    {
        $this->options = $options;
    }

    /**
     * Modify image urls for attachments to use gumlet host.
     *
     * @param string $url
     *
     * @return string
     */
    public function replace_image_url($url)
    {
        if (! empty($this->options['cdn_link'])) {
            $parsed_url = parse_url($url);

            //Check if image is hosted on current site url -OR- the CDN url specified. Using strpos because we're comparing the host to a full CDN url.
            if (
                isset($parsed_url['host'], $parsed_url['path'])
                && ($parsed_url['host'] === parse_url(home_url('/'), PHP_URL_HOST) || (isset($this->options['external_cdn_link']) && ! empty($this->options['external_cdn_link']) && strpos($this->options['external_cdn_link'], $parsed_url['host']) !== false))
                && preg_match('/\.(jpg|jpeg|gif|png)$/i', $parsed_url['path'])
            ) {
                $cdn = parse_url($this->options['cdn_link']);

                foreach ([ 'scheme', 'host', 'port' ] as $url_part) {
                    if (isset($cdn[ $url_part ])) {
                        $parsed_url[ $url_part ] = $cdn[ $url_part ];
                    } else {
                        unset($parsed_url[ $url_part ]);
                    }
                }

                if (! empty($this->options['external_cdn_link'])) {
                    $cdn_path = parse_url($this->options['external_cdn_link'], PHP_URL_PATH);

                    if (isset($cdn_path, $parsed_url['path']) && $cdn_path !== '/' && ! empty($parsed_url['path'])) {
                        $parsed_url['path'] = str_replace($cdn_path, '', $parsed_url['path']);
                    }
                }

                $url = http_build_url($parsed_url);

                $url = add_query_arg($this->get_global_params(), $url);
            }
        }

        return $url;
    }

    /**
     * Change url for images in srcset
     *
     * @param array  $sources
     * @param array  $size_array
     * @param string $image_src
     * @param array  $image_meta
     * @param int    $attachment_id
     *
     * @return array
     */
    public function calculate_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        if (! empty($this->options['cdn_link'])) {
            $widths = array(30,50,100,200,300,320,400,500,576,600,640,700,720,750,768,800,900,940,1000,1024,1080,1100,1140,1152,1200,1242,1300,1400,1440,1442,1500,1536,1600,1700,1800,1880,1900,1920,2000,2048,2100,2200,2208,2280,2300,2400,2415,2500,2560,2600,2700,2732,2800,2880,2900,3000,3100,3200,3300,3400,3500,3600,3700,3800,3900,4000,4100,4200,4300,4400,4500,4600,4700,4800,4900,5000,5100,5120);

            foreach ($widths as $width) {
                if ($attachment_id) {
                    $image_src = wp_get_attachment_url($attachment_id);
                }
                $image_src            = remove_query_arg('h', $image_src);
                $sources[ $width ]['url'] = add_query_arg('w', $width, $image_src);
                $sources[ $width ]['descriptor'] = 'w';
                $sources[ $width ]['value'] = $width;
            }
        }
        return $sources;
    }

    public function replace_wc_gallery_thumbs($matches) {
      $doc = new DOMDocument();
      $img_tag = mb_convert_encoding($matches[0], 'HTML-ENTITIES', "UTF-8");
      @$doc->loadHTML($img_tag);
      $imageTag = $doc->getElementsByTagName('div')[0];

      if(strpos($imageTag->getAttribute("class"), "elementor-gallery-item__image") !== false) {
        // this is elementor gallery.. need to put image in background
        $url = $imageTag->getAttribute('data-thumbnail');
        $imageTag-> setAttribute("data-bg", $this->replace_image_url($url));
        $imageTag->removeAttribute("data-thumbnail");

        return $doc->saveHTML($imageTag);
      } else {
        // this seems like other thumbnail
        $url = $this->absoluteUrl($matches[1]);
        $str = str_replace($matches[1], plugins_url('assets/images/pixel.png', __DIR__) . "#gumleturl=" . $url , $matches[0]);
        return $str;
      }

    }

    static function absoluteUrl($url, $cssPath = false) {
        $url = trim($url);
        $URI = parse_url($url);
        if(isset($URI['host']) && strlen($URI['host'])) {
            if(!isset($URI['scheme']) || !strlen($URI['scheme'])) {
                $url = (is_ssl() ? 'https' : 'http') . '://' . ltrim($url, '/');
            }
            return $url;
        } elseif(substr($url, 0, 1) === '/') {
            return home_url() . $url;
        } else {
            if($cssPath) {
                $homePath = self::get_home_path();
                if(strpos($cssPath, $homePath) !== false) {
                    $url = self::normalizePath($cssPath . $url);
                    return str_replace( $homePath, trailingslashit(get_home_url()), $url);
                }
                return $url;
            } else {
                global $wp;
                return trailingslashit(home_url($wp->request)) . $url;
            }
        }
    }

    /**
     * Modify image urls in content to use gumlet host.
     *
     * @param $content
     *
     * @return string
     */
    public function replace_images_in_content($content)
    {
        $excluded_urls = explode("\n", $this->get_option("exclude_images"));
        $excluded_urls = array_map('trim', $excluded_urls);
        // Added null to apply filters wp_get_attachment_url to improve compatibility with https://en-gb.wordpress.org/plugins/amazon-s3-and-cloudfront/ - does not break wordpress if the plugin isn't present.

        if (! empty($this->options['cdn_link'])) {
            $gumlet_host = parse_url($this->options['cdn_link'], PHP_URL_HOST);
            if (isset($this->options['external_cdn_link'])) {
                $external_cdn_host = parse_url($this->options['external_cdn_link'], PHP_URL_HOST);
            }

            $going_to_be_replaced_host = isset($external_cdn_host) ?  $external_cdn_host : parse_url(home_url('/'), PHP_URL_HOST);


            // this is bad hack for working with S3 hosts without region name in-built. unhack it later
            $is_s3_host = false;

            if (strpos($going_to_be_replaced_host, 'amazonaws.com') !== false) {
              $s3_host_array = explode(".", $going_to_be_replaced_host);
              if(count($s3_host_array) == 5) {
                // this is an s3 host with region name in-built
                $is_s3_host = true;
              }
            }

            // replaces src with data-gmsrc and removes srcset from images
            if (preg_match_all('/<img\s[^>]*src=([\"\']??)([^\" >]*?)\1[^>]*>/iU', $content, $matches)) {
                foreach ($matches[0] as $unconverted_img_tag) {
                    $doc = new DOMDocument();
                    // convert image tag to UTF-8 encoding.
                    $img_tag = mb_convert_encoding($unconverted_img_tag, 'HTML-ENTITIES', "UTF-8");
                    @$doc->loadHTML($img_tag);
                    $imageTag = $doc->getElementsByTagName('img')[0];
                    $src = $imageTag->getAttribute('src');
                    if (!$src) {
                        $src = $imageTag->getAttribute('data-src');
                    }

                    if (!$src) {
                        $src = $imageTag->getAttribute('data-large_image');
                    }

                    if (in_array($src, $excluded_urls)) {
                        // don't process excluded URLs
                        $imageTag->setAttribute("data-gumlet", 'false');
                        $new_img_tag = $doc->saveHTML($imageTag);
                        $content = str_replace($unconverted_img_tag, $new_img_tag, $content);
                        continue;
                    }

                    if (strpos($src, ';base64,') !== false || strpos($src, 'data:image/svg+xml') !== false) {
                        // does not process data URL.
                        continue;
                    }

                    if (strpos(stripcslashes($src), '"') !== false) {
                        // this URL is actually part of JSON data. It has quotes in it. We will ignore this URL
                        continue;
                    }

                    preg_match_all('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', $src, $size_matches);
                    if ($size_matches[0] && strlen($size_matches[0][0]) > 4 && $this->get_option("original_images")) {
                        $src = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', '', $src);
                    }

                    $current_host = parse_url($src, PHP_URL_HOST);

                    // S3 host without region is detected here and replaced with host with region.
                    // this is a bad hack. unhack it.
                    if(strpos($current_host, "amazonaws.com") !== false){
                      $current_host_array = explode(".", $current_host);
                      if(count($current_host_array) == 4 && $is_s3_host) {
                        // this current host is S3 URL without region in it. put actual host with region into it.
                        $parsed_url = parse_url($src);
                        $parsed_url['host'] = $going_to_be_replaced_host;
                        $src = $this->unparse_url($parsed_url);
                      }
                    }

                    if (parse_url($src, PHP_URL_HOST) == $going_to_be_replaced_host || parse_url($src, PHP_URL_HOST) == $gumlet_host || !parse_url($src, PHP_URL_HOST)) {
                        $imageTag->setAttribute("data-gmsrc", $src);
                        $imageTag->setAttribute("src", plugins_url('assets/images/pixel.png', __DIR__));
                        $imageTag->removeAttribute("srcset");
                        $imageTag->removeAttribute("data-src");
                        $imageTag->removeAttribute("data-srcset");
                        $imageTag->removeAttribute("data-large_image");
                        $imageTag->removeAttribute("data-lazy-srcset");
                        $imageTag->removeAttribute("data-lazy-src");
                        // check if this is magento product image and if it is, set data-src as well
                        if (strpos($imageTag->getAttribute("class"), "wp-post-image") !== false  && $imageTag->getAttribute("data-large_image_width") != '') {
                            $imageTag->setAttribute("data-src", $src);
                        }
                        $new_img_tag = $doc->saveHTML($imageTag);
                        $content = str_replace($unconverted_img_tag, $new_img_tag, $content);
                    }
                }
            }

            // now we will replace srcset in SOURCE tags to data-srcset.
            if (preg_match_all('/<source\s[^>]*srcset=([\"\']??)([^\" >]*?)\1[^>]*>/iU', $content, $matches)) {
                foreach ($matches[0] as $unconverted_source_tag) {
                    $doc = new DOMDocument();
                    // convert image tag to UTF-8 encoding.
                    $source_tag = mb_convert_encoding($unconverted_source_tag, 'HTML-ENTITIES', "UTF-8");
                    @$doc->loadHTML($source_tag);
                    $sourceTag = $doc->getElementsByTagName('source')[0];
                    $src = $sourceTag->getAttribute('srcset');
                    $sourceTag->removeAttribute("srcset");
                    $sourceTag->setAttribute("data-srcset", $src);
                    $new_source_tag = $doc->saveHTML($sourceTag);
                    $content = str_replace($unconverted_source_tag, $new_source_tag, $content);
                }
            }

            // replace wordpress thumbnails
            $content = preg_replace_callback(
                '/\<div[^\<\>]*?\sdata-thumb(?:nail|)\=(?:\"|\')(.+?)(?:\"|\')(?:.+?)\>/s',
                array($this, 'replace_wc_gallery_thumbs'),
                $content
            );

            // We don't want links to be processed by Gumlet

            // if (preg_match_all('/<a\s[^>]*href=([\"\']??)([^\" >]*?)\1[^>]*>(.*)<\/a>/iU', $content, $matches)) {
            //     foreach ($matches[0] as $link) {
            //         $content = str_replace($link[2], apply_filters('wp_get_attachment_url', $link[2], null), $content);
            //     }
            // }

            // this replaces background URLs on any tags with data-bg
            preg_match_all('~\bstyle=(\'|")(((?!style).)*?)background(-image)?\s*:(.*?)url\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $content, $matches);

            if (!empty($matches)) {
                foreach ($matches[0] as $match) {
                    preg_match('~\bbackground(-image)?\s*:(.*?)url\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $match, $bg);
                    if (strpos($bg['image'], ';base64,') !== false || strpos($bg['image'], 'data:image/svg+xml') !== false) {
                        // does not process data URL.
                        continue;
                    }
                    if (parse_url($bg[4], PHP_URL_HOST) == $going_to_be_replaced_host || parse_url($bg[4], PHP_URL_HOST) == $gumlet_host) {
                        if (in_array($bg['image'], $excluded_urls)) {
                            // don't process excluded URLs
                            continue;
                        }
                        preg_match_all('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', $bg['image'], $size_matches);
                        if ($size_matches[0] && strlen($size_matches[0][0]) > 4  && $this->get_option("original_images")) {
                            $bg['image'] = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', '', $bg['image']);
                        }
                        $bg_less_match = str_replace($bg[0], '', $match);
                        $data_match = 'data-bg="'.$bg['image'].'" '.$bg_less_match;
                        $content = str_replace(array($match.';', $match), array( $data_match, $data_match), $content);
                    }
                }
            }


            // we now replace all backgrounds in <style> tags...
            preg_match_all('~\bbackground(-image)?\s*:(.*?)url\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $content, $matches);

            if (!empty($matches)) {
                foreach ($matches[0] as $match) {
                    preg_match('~\bbackground(-image)?\s*:(.*?)url\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $match, $bg);
                    $original_bg = $bg['image'];
                    if (strpos($bg['image'], ';base64,') !== false || strpos($bg['image'], 'data:image/svg+xml') !== false) {
                        // does not process data URL.
                        continue;
                    }

                    if (in_array($original_bg, $excluded_urls)) {
                        // don't process excluded URLs
                        continue;
                    }

                    if (parse_url($bg[4], PHP_URL_HOST) == $going_to_be_replaced_host) {
                        preg_match_all('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', $bg['image'], $size_matches);
                        if ($size_matches[0] && strlen($size_matches[0][0]) > 4  && $this->get_option("original_images")) {
                            $bg['image'] = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', '', $bg['image']);
                        }
                        $parsed_url = parse_url($bg['image']);
                        $parsed_url['host'] = $gumlet_host;
                        $bg['image'] = $this->unparse_url($parsed_url);
                        $bg['image'] = add_query_arg($this->get_global_params(), $bg['image']);
                        $final_bg_style = str_replace($original_bg, $bg['image'], $match);
                        $content = str_replace(array($match.';', $match), array( $final_bg_style, $final_bg_style), $content);
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Add tag to dns prefetch cdn host
     */
    public function add_prefetch()
    {
        if (! empty($this->options['cdn_link'])) {
            $gumlet_host = parse_url($this->options['cdn_link'], PHP_URL_HOST);
            printf(
                '<link rel="dns-prefetch" href="%s"/>',
                esc_attr('https://' . $gumlet_host)
            );
        }
    }

    /**
     * Returns a array of global parameters to be applied in all images,
     * according to plugin's settings.
     *
     * @return array Global parameters to be appened at the end of each img URL.
     */
    protected function get_global_params()
    {
        $params = [];

        if (! empty($this->options['quality'])) {
            $params["quality"] = $this->options['quality'];
        }

        // if ( ! empty ( $this->options['auto_enhance'] ) ) {
        // 	array_push( $auto, 'enhance' );
        // }

        if (! empty($this->options['auto_compress'])) {
            $params["compress"] = "true";
        }

        return $params;
    }


    protected function unparse_url($parsed_url)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}

Gumlet::instance();
