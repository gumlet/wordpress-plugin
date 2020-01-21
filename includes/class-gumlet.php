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

    /**
     * Gumlet constructor.
     */
    public function __construct()
    {
        $this->options = get_option('gumlet_settings', []);

        $this->logger = GumletLogger::instance();


        // Change filter load order to ensure it loads after other CDN url transformations i.e. Amazon S3 which loads at position 99.


        // add_filter('wp_get_attachment_url', [ $this, 'replace_image_url' ], 100);
        // add_filter('gumlet/add-image-url', [ $this, 'replace_image_url' ]);

        // add_filter('image_downsize', [ $this, 'image_downsize' ], 10, 3);

        // add_filter('wp_calculate_image_srcset', [ $this, 'calculate_image_srcset' ], 10, 5);

        add_action('wp_head', [ $this, 'add_prefetch' ], 1);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_script'], 1);

        add_action('init', [$this, 'init_ob'], 1);
        // add_filter('pum_popup_content', [ $this, 'replace_images_in_content' ], PHP_INT_MAX);
        // add_filter('the_content', [ $this, 'replace_images_in_content' ], PHP_INT_MAX);
        // add_filter('post_thumbnail_html', [ $this, 'replace_images_in_content' ], PHP_INT_MAX );
        // add_filter('get_image_tag', [ $this, 'replace_images_in_content' ], PHP_INT_MAX );
            // add_filter('wp_get_attachment_image_attributes', [ $this, 'replace_images_in_content' ], PHP_INT_MAX );
    }

    public function enqueue_script()
    {
        if (isset($this->options['external_cdn_link'])) {
            $external_cdn_host = parse_url($this->options['external_cdn_link'], PHP_URL_HOST);
        }

        wp_register_script('gumlet-script', 'https://cdn.gumlet.com/gumlet.js/2.0/gumlet.min.js', array(), '2.0', false);
        wp_localize_script('gumlet-script', 'gumlet_wp_config', array(
        'gumlet_host' => parse_url($this->options['cdn_link'], PHP_URL_HOST),
        'current_host' => isset($external_cdn_host) ? $external_cdn_host : parse_url(home_url('/'), PHP_URL_HOST),
        'lazy_load' => (!empty($this->options['lazy_load'])) ? 1 : 0,
        'auto_format' => (!empty($this->options['auto_format'])) ? 1 : 0,
        'auto_compress' => (!empty($this->options['auto_compress'])) ? 1 : 0,
        'quality' => (!empty($this->options['quality'])) ? $this->options['quality'] : 80
      ));
        wp_enqueue_script('gumlet-script');
    }

    public function init_ob()
    {
        ob_start([$this, 'replace_images_in_content']);
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
    public function get_option($key, $default = '')
    {
        return array_key_exists($key, $this->options) ? $this->options[ $key ] : $default;
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
     * Set params when running image_downsize
     *
     * @param false|array  $return
     * @param int          $attachment_id
     * @param string|array $size
     *
     * @return false|array
     */
    public function image_downsize($return, $attachment_id, $size)
    {
        if (! empty($this->options['cdn_link'])) {
            $img_url = wp_get_attachment_url($attachment_id);

            $params = [];
            if (is_array($size)) {
                $params['w'] = $width = isset($size[0]) ? $size[0] : 0;
                $params['h'] = $height = isset($size[1]) ? $size[1] : 0;
            } else {
                $available_sizes = $this->get_all_defined_sizes();
                if (isset($available_sizes[ $size ])) {
                    $size        = $available_sizes[ $size ];
                    $params['w'] = $width = $size['width'];
                    $params['h'] = $height = $size['height'];
                }
            }

            $params = array_filter($params);

            $img_url = add_query_arg($params, $img_url);

            if (! isset($width) || ! isset($height)) {
                // any other type: use the real image
                $meta   = wp_get_attachment_metadata($attachment_id);

                // Image sizes is missing for pdf thumbnails
                if ($meta) {
                    $meta['width']  = isset($meta['width']) ? $meta['width'] : 0;
                    $meta['height'] = isset($meta['height']) ? $meta['height'] : 0;
                } else {
                    $meta = array("width" => 0, "height" => 0);
                }


                $width  = isset($width) ? $width : $meta['width'];
                $height = isset($height) ? $height : $meta['height'];
            }

            $return = [ $img_url, $width, $height, true ];
        }

        return $return;
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

    /**
     * Modify image urls in content to use gumlet host.
     *
     * @param $content
     *
     * @return string
     */
    public function replace_images_in_content($content)
    {
        // $content = file_get_contents("/Users/adityapatadia/Turing/wordpress/wp-content/plugins/gumlet/test.html");
        // Added null to apply filters wp_get_attachment_url to improve compatibility with https://en-gb.wordpress.org/plugins/amazon-s3-and-cloudfront/ - does not break wordpress if the plugin isn't present.
        $amp_endpoint = false;
        if (function_exists('is_amp_endpoint')) {
            $amp_endpoint = is_amp_endpoint();
        }

        if (! empty($this->options['cdn_link']) && !is_admin() && !$amp_endpoint &&  !isset($_GET['ct_builder'])) {
            $gumlet_host = parse_url($this->options['cdn_link'], PHP_URL_HOST);
            if (isset($this->options['external_cdn_link'])) {
                $external_cdn_host = parse_url($this->options['external_cdn_link'], PHP_URL_HOST);
            }

            $going_to_be_replaced_host = isset($external_cdn_host) ?  $external_cdn_host : parse_url(home_url('/'), PHP_URL_HOST);

            // replaces src with data-gmsrc and removes srcset from images
            if (preg_match_all('/<img\s[^>]*src=([\"\']??)([^\" >]*?)\1[^>]*>/iU', $content, $matches)) {
                foreach ($matches[0] as $img_tag) {
                    $doc = new DOMDocument();
                    // convert image tag to UTF-8 encoding.
                    $img_tag = mb_convert_encoding($img_tag, 'HTML-ENTITIES', "UTF-8");
                    @$doc->loadHTML($img_tag);
                    $imageTag = $doc->getElementsByTagName('img')[0];
                    $src = $imageTag->getAttribute('src');
                    if (!$src) {
                        $src = $imageTag->getAttribute('data-src');
                    }

                    if (strpos($src, ';base64,') !== false) {
                        // does not process data URL.
                        continue;
                    }
                    preg_match_all('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', $src, $size_matches);
                    if ($size_matches[0] && strlen($size_matches[0][0]) > 4) {
                        $src = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', '', $src);
                    }

                    if (parse_url($src, PHP_URL_HOST) == $going_to_be_replaced_host || parse_url($src, PHP_URL_HOST) == $gumlet_host) {
                        $imageTag->setAttribute("data-gmsrc", $src);
                        $imageTag->removeAttribute("src");
                        $imageTag->removeAttribute("srcset");
                        $imageTag->removeAttribute("data-src");
                        $imageTag->removeAttribute("data-srcset");
                        $imageTag->removeAttribute("data-lazy-src");
                        $new_img_tag = $doc->saveHTML($imageTag);
                        $content = str_replace($img_tag, $new_img_tag, $content);
                    }
                }
            }

            // We don't want links to be processed by Gumlet

            // if (preg_match_all('/<a\s[^>]*href=([\"\']??)([^\" >]*?)\1[^>]*>(.*)<\/a>/iU', $content, $matches)) {
            //     foreach ($matches[0] as $link) {
            //         $content = str_replace($link[2], apply_filters('wp_get_attachment_url', $link[2], null), $content);
            //     }
            // }

            // this replaces background URLs on any tags with data-bg
            preg_match_all('~\bstyle=(\'|")(((?!style).)*?)background(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $content, $matches);

            if (!empty($matches)) {
                foreach ($matches[0] as $match) {
                    preg_match('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $match, $bg);
                    if (strpos($bg['image'], ';base64,') !== false) {
                        // does not process data URL.
                        continue;
                    }
                    if (parse_url($bg[4], PHP_URL_HOST) == $going_to_be_replaced_host || parse_url($bg[4], PHP_URL_HOST) == $gumlet_host) {
                        preg_match_all('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', $bg['image'], $size_matches);
                        if ($size_matches[0] && strlen($size_matches[0][0]) > 4) {
                            $bg['image'] = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', '', $bg['image']);
                        }
                        $bg_less_match = str_replace($bg[0], '', $match);
                        $data_match = 'data-bg="'.$bg['image'].'" '.$bg_less_match;
                        $content = str_replace(array($match.';', $match), array( $data_match, $data_match), $content);
                    }
                }
            }


            // we now replace all backgrounds in <style> tags...
            preg_match_all('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $content, $matches);

            if (!empty($matches)) {
                foreach ($matches[0] as $match) {
                    preg_match('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $match, $bg);
                    $original_bg = $bg['image'];
                    if (strpos($bg['image'], ';base64,') !== false) {
                        // does not process data URL.
                        continue;
                    }
                    if (parse_url($bg[4], PHP_URL_HOST) == $going_to_be_replaced_host) {
                        preg_match_all('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', $bg['image'], $size_matches);
                        if ($size_matches[0] && strlen($size_matches[0][0]) > 4) {
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

        // For now, only "auto" is supported.
        if (! empty($this->options['auto_format'])) {
            $params["format"] = "auto";
        }

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

    /**
     * Get all defined image sizes
     *
     * @return array
     */
    protected function get_all_defined_sizes()
    {
        // Make thumbnails and other intermediate sizes.
        $theme_image_sizes = wp_get_additional_image_sizes();

        $sizes = [];
        foreach (get_intermediate_image_sizes() as $s) {
            $sizes[ $s ] = [ 'width' => '', 'height' => '', 'crop' => false ];
            if (isset($theme_image_sizes[ $s ])) {
                // For theme-added sizes
                $sizes[ $s ]['width']  = intval($theme_image_sizes[ $s ]['width']);
                $sizes[ $s ]['height'] = intval($theme_image_sizes[ $s ]['height']);
                $sizes[ $s ]['crop']   = $theme_image_sizes[ $s ]['crop'];
            } else {
                // For default sizes set in options
                $sizes[ $s ]['width']  = get_option("{$s}_size_w");
                $sizes[ $s ]['height'] = get_option("{$s}_size_h");
                $sizes[ $s ]['crop']   = get_option("{$s}_crop");
            }
        }

        return $sizes;
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
