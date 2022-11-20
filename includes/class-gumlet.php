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
        // useyourdrive plugin exclude
        'useyourdrive-get-filelist',
        // wpdiscuz
        "wpdLoadMoreComments",
        "wpdVoteOnComment",
        "wpdSorting",
        "wpdAddComment",
        "wpdGetSingleComment",
        "wpdCheckNotificationType",
        "wpdRedirect",
        "wpdEditComment",
        "wpdSaveEditedComment",
        "wpdUpdateAutomatically",
        "wpdReadMore",
        "wpdShowReplies",
        "wpdMostReactedComment",
        "wpdHottestThread",
        "wpdGetInfo",
        "wpdGetActivityPage",
        "wpdGetSubscriptionsPage",
        "wpdGetFollowsPage",
        "wpdDeleteComment",
        "wpdCancelSubscription",
        "wpdCancelFollow",
        "wpdEmailDeleteLinks",
        "wpdGuestAction",
        "wpdStickComment",
        "wpdCloseThread",
        "wpdFollowUser",
        "wpdBubbleUpdate",
        "wpdAddInlineComment",
        "wpdGetLastInlineComments",
        "wpdGetInlineCommentForm",
        "wpdAddSubscription",
        "wpdUnsubscribe",
        "wpdUserRate",
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

        add_filter('script_loader_tag', [$this,'add_asyncdefer_attribute'], 10, 2);

        add_action('wp_head', [ $this, 'add_prefetch' ], 1);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_script'], 1);

        //add_action('amp_post_template_data', [$this, 'replace_images_in_amp_instant_article'], 1);

        add_action('wp', [$this, 'init_ob'], 1);
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
        // this check is for elementor gallery ajax requests which has action type of get_listings
        if(isset($_GET['action']) && $_GET['action'] == 'get_listings')
        {
            return true;
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

            wp_register_script('gumlet-script-async', 'https://cdn.jsdelivr.net/npm/gumlet.js@2.2/dist/gumlet.min.js');
            wp_localize_script('gumlet-script-async', 'gumlet_wp_config', array(
              'gumlet_host' => parse_url($this->options['cdn_link'], PHP_URL_HOST),
              'current_host' => isset($external_cdn_host) ? $external_cdn_host : parse_url(home_url('/'), PHP_URL_HOST),
              'lazy_load' => (!empty($this->options['lazy_load'])) ? 1 : 0,
              'width_from_img' => get_option('gumlet_width_from_img') ? 1 : 0,
              'width_from_flex' => get_option('gumlet_width_from_flex') ? 1 : 0,
              'min_width' => get_option('gumlet_min_width') ? get_option('gumlet_min_width') : '',
              'auto_compress' => (!empty($this->options['auto_compress'])) ? 1 : 0,
              "auto_webp" => (!empty($this->options['server_webp'])) ? 1 : 0,
              'quality' => (!empty($this->options['quality'])) ? $this->options['quality'] : 80
            ));
            wp_enqueue_script('gumlet-script-async');
        }
    }

    public function init_ob()
    {
        //test this,cdn_link checking in init_ob
        global $wp_query;
        $excluded_explode = explode("\n", $this->get_option("exclude_post_types"));
        foreach ($excluded_explode as $value) {
            $excluded_post_types[] = trim($value);
        }

        if (!empty($this->options['cdn_link']) && $this->isWelcome()) {
            if( (function_exists('amp_is_request') && amp_is_request()) || (isset($_GET['ia_markup']) && $_GET[ 'ia_markup' ]))
            {
                ob_start([$this, 'replace_images_in_amp_instant_article']);
            }
            else if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            {   
                $this->logger->log("inside ajax req.",$_SERVER['HTTP_X_REQUESTED_WITH']);
                ob_start([$this, 'convert_json']);
            } 
            else if(in_array(get_post_type($wp_query->post), $excluded_post_types)){
                // excluded post types will not have gumlet-js enabled on them.
                ob_start([$this, 'replace_images_in_amp_instant_article']);
            } else{
                ob_start([$this, 'replace_images_in_content']);
            }
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
                && ($parsed_url['host'] === parse_url(home_url('/'), PHP_URL_HOST) 
                || (isset($this->options['external_cdn_link']) && ! empty($this->options['external_cdn_link']) 
                && strpos($this->options['external_cdn_link'], $parsed_url['host']) !== false))
                && preg_match('/\.(jpg|jpeg|gif|png)$/i', $parsed_url['path'])
            ) 
            {
                $cdn = parse_url($this->options['cdn_link']);

                foreach ([ 'scheme', 'host', 'port' ] as $url_part){
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

    public function replace_wc_gallery_thumbs($matches) {
      $url = $this->absoluteUrl($matches[1]);
      $str = str_replace($matches[1], $this->replace_image_url($url) , $matches[0]);
      // $str = str_replace($matches[1], plugins_url('assets/images/pixel.png', __DIR__) . "#gumleturl=" . $url , $matches[0]);
      return $str;
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
     * converting json .
     *
     * @param $content
     *
     * @return 
     */
    public function convert_json($content){
        $obj=json_decode($content);
        $this->logger->log("json object.",$obj);
        if($obj && property_exists($obj, 'html')) {   
            $obj->html=$this->replace_images_in_content($obj->html);
            $this->logger->log("converted.",$obj->html);
            return json_encode($obj);
        } else {
            return $content;
        }
    }

    /**
     * Modify image urls in content to use gumlet host.
     *
     * @param $content
     *
     * @return string
     */
    public function replace_images_in_amp_instant_article($content)
    {
        $gumlet_host = parse_url($this->options['cdn_link'], PHP_URL_HOST);

        if (isset($this->options['external_cdn_link'])) {
            $external_cdn_host = parse_url($this->options['external_cdn_link'], PHP_URL_HOST);
        }   
        $going_to_be_replaced_host = isset($external_cdn_host) ?  $external_cdn_host : parse_url(home_url('/'), PHP_URL_HOST);

        // replacing img src in amp-img tag.
        if (preg_match_all('/<amp-img\s[^>]*src=([\"\']??)([^\" >]*?)\1[^>]*>/iU', $content, $matches, PREG_PATTERN_ORDER)) {
            $this->logger->log("amp-img src",$matches);
            $content = $this->replace_in_amp_src($matches,$content,$gumlet_host,$going_to_be_replaced_host);
        }

        // replacing img srcset in amp-img tag.
        if (preg_match_all('/<amp-img\s[^>]*srcset=([\"\']??)([^\">]*?)\1[^>]*>/iU', $content, $matches, PREG_PATTERN_ORDER)) {
            $this->logger->log("amp-img srcset",$matches);
            $content = $this->replace_in_amp_srcset($matches,$content,$gumlet_host,$going_to_be_replaced_host);
        }

        // replacing img src in img tag.
        if (preg_match_all('/<img\s[^>]*src=([\"\']??)([^\" >]*?)\1[^>]*>/iU', $content, $matches, PREG_PATTERN_ORDER)) {
            $this->logger->log("img src",$matches);
            $content = $this->replace_in_amp_src($matches,$content,$gumlet_host,$going_to_be_replaced_host);
        }

        // replacing img srcset in img tag.
        if (preg_match_all('/<img\s[^>]*srcset=([\"\']??)([^\">]*?)\1[^>]*>/iU', $content, $matches, PREG_PATTERN_ORDER)) {
            $this->logger->log("img srcset",$matches);
            $content = $this->replace_in_amp_srcset($matches,$content,$gumlet_host,$going_to_be_replaced_host);
        }
        return $content;
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
        // $myfile = fopen("/Users/adityapatadia/gumlet/wordpress/json.txt", "r") or die("Unable to open file!");
        // $content = fread($myfile,filesize("/Users/adityapatadia/gumlet/wordpress/json.txt"));
        // fclose($myfile); 

        $excluded_urls = explode("\n", $this->get_option("exclude_images"));
        $excluded_urls = array_map('trim', $excluded_urls);
        // Added null to apply filters wp_get_attachment_url to improve compatibility with https://en-gb.wordpress.org/plugins/amazon-s3-and-cloudfront/ - does not break wordpress if the plugin isn't present.

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
        $this->logger->log("Processing content:". $content);
        // replaces src with data-gmsrc and removes srcset from images
        if (preg_match_all('/<img\s[^>]*src=([\"\']??)([^\" >]*?)\1[^>]*>/iU', $content, $matches, PREG_PATTERN_ORDER)) {
            $content = $this->replace_src_in_imgtag($matches,$content,$gumlet_host,$going_to_be_replaced_host,$excluded_urls);
        }

        // now we will replace srcset in SOURCE tags to data-srcset.
        if (preg_match_all('/<source\s[^>]*srcset=([\"\']??)([^\" >]*?)\1[^>]*>/iU', $content, $matches)) {
            $content = $this->replace_srcset_in_source($matches,$content);
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
        $content_length = strlen($content);
        $sublength = 25000;
        $final_matches = array();
        for ($i=0; $i < $content_length; $i += $sublength) { 
            $subcontent = substr($content, $i, $sublength);
            $num_matches = preg_match_all('~\bstyle=(\'|")(((?!style).)*?)background(-image)?\s*:(.*?)url\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $subcontent, $matches);
            if ($num_matches) {
                $final_matches = array_merge($final_matches, $matches[0]);
            }
        }
        $content = $this->replace_src_in_style($final_matches,$content,$going_to_be_replaced_host,$gumlet_host,$excluded_urls);
        
        $content_length = strlen($content);
        $final_matches = array();
        // we now replace all backgrounds in <style> tags...
        for ($i=0; $i < $content_length; $i += $sublength) { 
            $subcontent = substr($content, $i, $sublength);
            $num_matches = preg_match_all('~\bbackground(-image)?\s*:(.*?)url\(\s*(\'|")?(?<image>.*?)\3?\s*\);?~i', $subcontent, $matches);
            if ($num_matches) {
                $final_matches = array_merge($final_matches, $matches[0]);
            }
        }
        $content = $this->replace_src_in_css($final_matches,$content,$going_to_be_replaced_host,$gumlet_host,$excluded_urls);
        
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
        //  array_push( $auto, 'enhance' );
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
    
    /**
     * Replace src tag with datagm-src in IMGtag and removing srcset.
     *
     * @param  array $matches
     * @param  string $content
     * @param  string $gumlet_host
     * @param  string $going_to_be_replaced_host
     * @param  string $excluded_urls
     * @return string
     */
    public function replace_src_in_imgtag($matches,$content,$gumlet_host,$going_to_be_replaced_host,$excluded_urls)
    {
        $this->logger->log("Matched regex:", $matches);
        foreach ($matches[0] as $unconverted_img_tag) {
            $this->logger->log("Processing img:", $unconverted_img_tag);
            $doc = new DOMDocument();
            $img_tag = $this-> convert_to_utf($unconverted_img_tag);

            @$doc->loadHTML($img_tag);
            $imageTag = $doc->getElementsByTagName('img')[0];
            $src = $imageTag->getAttribute('src');
            if (!$src) {
                $src = $imageTag->getAttribute('data-src');
            }

            if (!$src) {
                $src = $imageTag->getAttribute('data-large_image');
            }

            if(substr( $src, 0, 3) === "{%=") {
                $this->logger->log("Skipping due to template {%= url");
                continue;
            }

            if(strpos($imageTag->getAttribute('class'), "hidden") !== false){
                // if image class has "hidden" class, disable lazy load for those images.
                // this is useful for sliders.
                $imageTag->setAttribute("data-gmlazy", 'false');
            }

            if (in_array($src, $excluded_urls)) {
                // don't process excluded URLs
                $imageTag->setAttribute("data-gumlet", 'false');
                $new_img_tag = $doc->saveHTML($imageTag);
                $content = str_replace($unconverted_img_tag, $new_img_tag, $content);
                $this->logger->log("Skipping due to excluded URL");
                continue;
            }

            if (strpos($src, ';base64,') !== false || strpos($src, 'data:image/svg+xml') !== false) {
                // does not process data URL.
                $this->logger->log("Skipping due to data URL");
                continue;
            }

            if (strpos(stripcslashes($src), '"') !== false) {
                // this URL is actually part of JSON data. It has quotes in it. We will ignore this URL
                continue;
            }

            preg_match_all('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|svg))/i', $src, $size_matches, PREG_PATTERN_ORDER);
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
                $imageTag->removeAttribute("data-lazy-srcset");
                $imageTag->removeAttribute("data-lazy-src");
                // check if this is magento product image and if it is, set data-src as well
                if (strpos($imageTag->getAttribute("class"), "wp-post-image") !== false  && $imageTag->getAttribute("data-large_image_width") != '') {
                    $imageTag->setAttribute("data-src", $src);
                }
                $new_img_tag = $doc->saveHTML($imageTag);
                $this->logger->log("New img tag:", $new_img_tag);
                $content = str_replace($unconverted_img_tag, $new_img_tag, $content);
            } else {
                $this->logger->log("Skipping due to mismatched host to be replaced.");
            }
        }
        return $content;
    }

    /**
     * Replace srcset in source.
     *
     * @param  array $matches
     * @param  string $content
     * @return string
     */
    public function replace_srcset_in_source($matches,$content) {
        foreach ($matches[0] as $unconverted_img_tag) {
            $doc = new DOMDocument();
            $source_tag = $this-> convert_to_utf($unconverted_img_tag);
            //write function to remove srcset for img and this function.
            @$doc->loadHTML($source_tag);
            $sourceTag = $doc->getElementsByTagName('source')[0];
            $src = $sourceTag->getAttribute('srcset');
            $sourceTag->removeAttribute("srcset");
            $sourceTag->setAttribute("data-srcset", $src);
            $new_source_tag = $doc->saveHTML($sourceTag);
            $content = str_replace($unconverted_img_tag, $new_source_tag, $content);
        }
        return $content;
    }

    /**
     * Replace background src in source.
     *
     * @param  array $matches
     * @param  string $content
     * @param  string $going_to_be_replaced_host
     * @param  string $gumlet_host
     * @param  string $excluded_urls
     * @return string
     */
    public function replace_src_in_style($matches,$content,$going_to_be_replaced_host,$gumlet_host,$excluded_urls) {
        foreach ($matches as $match) {
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
        return $content;
    }

    /**
     * Replace background src in css.
     *
     * @param  array $matches
     * @param  string $content
     * @param  string $going_to_be_replaced_host
     * @param  string $gumlet_host
     * @param  string $excluded_urls
     * @return string
     */
    public function replace_src_in_css($matches,$content,$going_to_be_replaced_host,$gumlet_host,$excluded_urls) {
        foreach ($matches as $match) {
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
                $bg['image'] = $this->replace_image_url($bg['image']);
                $final_bg_style = str_replace($original_bg, $bg['image'], $match);
                $content = str_replace(array($match.';', $match), array( $final_bg_style, $final_bg_style), $content);
            }
        }
        return $content;
    }

    /**
     * Convert img tag to UTF-8 enconding.
     *
     * @param  string $unconverted_img_tag
     * @return string
     */
    public function convert_to_utf($unconverted_img_tag){
        if(function_exists("mb_convert_encoding")) {
            $img_tag = mb_convert_encoding($unconverted_img_tag, 'HTML-ENTITIES', "UTF-8");
            return $img_tag;
        } else {
            return $unconverted_img_tag;
        }
    }

    /**
     * Replace src in AMP request.
     * For AMP and IMG tag.
     * @param  array $matches
     * @param  string $content
     * @param  string $gumlet_host
     * @param  string $going_to_be_replaced_host
     * @return string
     */
    public function replace_in_amp_src($matches,$content,$gumlet_host,$going_to_be_replaced_host){
        
        for ($i=0; $i < count($matches[0]); $i++) {
            $amp_img_tag=$matches[0][$i];
            $src=$matches[2][$i];

            if (strpos($src, ';base64,') !== false || strpos($src, 'data:image/svg+xml') !== false) {
                // does not process data URL.
                $this->logger->log("Skipping due to data URL");
                continue;
            }

            if (parse_url($src, PHP_URL_HOST) == $going_to_be_replaced_host || parse_url($src, PHP_URL_HOST) == $gumlet_host || !parse_url($src, PHP_URL_HOST)) {
                $newsrc = $this->replace_image_url($src);
                $new_img_tag = str_replace($src, $newsrc ,$amp_img_tag);
                $content = str_replace($amp_img_tag, $new_img_tag, $content);
            }
            else{
                $this->logger->log("Skipping due to mismatched host to be replaced.");
            }
        }
        return $content;
    }

    /**
     * Replace srcset in AMP request.
     * For AMP and IMG tag.
     * @param  array $matches
     * @param  string $content
     * @param  string $gumlet_host
     * @param  string $going_to_be_replaced_host
     * @return string
     */
    public function replace_in_amp_srcset($matches,$content,$gumlet_host,$going_to_be_replaced_host){
        for ($i=0; $i < count($matches[0]) ; $i++) {
            $amp_img_tag=$matches[0][$i];

            $src_and_sizes=explode(",",$matches[2][$i]);

            for ($j=0; $j < count($src_and_sizes) ; $j++) {
                $src_sizes_array=explode(" ",trim($src_and_sizes[$j]));
                $src=trim($src_sizes_array[0]);

                if (strpos($src, ';base64,') !== false || strpos($src, 'data:image/svg+xml') !== false)
                {
                    // does not process data URL.
                    $this->logger->log("Skipping due to data URL");
                    continue;
                }

                if (parse_url($src, PHP_URL_HOST) == $going_to_be_replaced_host || parse_url($src, PHP_URL_HOST) == $gumlet_host || !parse_url($src, PHP_URL_HOST)) {
                    $newsrc = $this->replace_image_url($src);
                    $src_sizes_array[0]=$newsrc;
                }
                else{
                    $this->logger->log("Skipping due to mismatched host to be replaced.");
                }
                $src_and_sizes[$j] = join(" ", $src_sizes_array);
            }
            $new_img_tag = str_replace($matches[2][$i], join(", ", $src_and_sizes) ,$amp_img_tag);
            $content = str_replace($amp_img_tag, $new_img_tag, $content);
        }
        return $content;
    }
        
}
Gumlet::instance();