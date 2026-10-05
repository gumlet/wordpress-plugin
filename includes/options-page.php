<?php

class Gumlet_Options_Page
{

    /**
     * The instance of the class.
     *
     * @var Gumlet_Options_Page
     */
    protected static $instance;

    /**
     * Plugin options
     *
     * @var array
     */
    protected $options = [];


    public function __construct()
    {
        $this->options = get_option('gumlet_settings', []);
        add_action('admin_init', [ $this, 'gumlet_register_settings' ]);
        add_action('admin_menu', [ $this, 'gumlet_add_options_link' ]);
    }

    /**
     * Plugin loader instance.
     *
     * @return Gumlet_Options_Page
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Renders options page
     */
    public function gumlet_options_page()
    {
        ?>
        <!-- <head>
          <link rel="stylesheet" type="text/css" href="gumlet.css">
        </head> -->
        <div class="wrap">
    <h1>
        <img src="<?php echo plugins_url('assets/images/gumlet-logo.png', __DIR__); ?>" alt="gumlet Logo"
            style="width:200px; margin-left: -12px;">
    </h1>
    <?php
            if( isset($_GET['settings-updated']) ){
          ?>
    <div class="notice notice-warning gumlet-warning">
        <p><strong>Heads up! Clear cache:</strong> We recommend you clear cache after enabling Gumlet.</p>
    </div>
    <?php
            }
            if (!empty($this->options['signed_urls'])) {
          ?>
    <div class="notice notice-warning gumlet-warning">
        <p><strong>Signed URLs are on.</strong> Auto Resize stays off. Gumlet.js would change the image URL after it is signed, and Gumlet would reject that request. Images load from the signed URL. Enable Secure URLs on the Gumlet source after the token below is saved, then clear any page cache.</p>
        <?php if ((int) $this->get_option('signed_url_expiry') > 0) { ?>
        <p>These URLs expire <?php echo (int) $this->get_option('signed_url_expiry'); ?> seconds after the page is rendered. Keep any full-page cache shorter than that.</p>
        <?php } ?>
    </div>
    <?php
            }
          ?>
    <div class="notice notice-info">
        <p><strong>Important!</strong> Gumlet <strong>does not</strong> work well with other lazy-load plugins. We
            recommend you <strong>disable</strong> all other lazy-load plugins and lazy-load settings in themes.</p>
        <p><strong>Need help getting started?</strong> It's easy! Check out our
            <a href="https://docs.gumlet.com/docs/image-integration-wordpress" target="_blank">instructions.</a>
        </p>
    </div>

    <form method="post" action="<?php echo admin_url('options.php'); ?>">

        <?php settings_fields('gumlet_settings_group'); ?>
        <div class="mytabs">
            <input type="radio" id="tabsettings" name="mytabs" checked="checked">
            <label for="tabsettings" class="mytablabel">Settings</label>
            <div class="tab">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[cdn_link]">
                                    <?php esc_html_e('Gumlet Source', 'gumlet'); ?> *
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[cdn_link]" type="url" name="gumlet_settings[cdn_link]"
                                    placeholder="https://yourcompany.gumlet.io"
                                    value="<?php echo $this->get_option('cdn_link'); ?>" required="required"
                                    class="regular-text code" />
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[external_cdn_link]">
                                    <?php esc_html_e('Current Image Domain', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[external_cdn_link]" type="url"
                                    name="gumlet_settings[external_cdn_link]" placeholder="https://www.otherdomain.com"
                                    value="<?php echo $this->get_option('external_cdn_link'); ?>"
                                    class="regular-text code" />
                                <p style="color: #666">&nbsp;If you are using any other domain apart from your website
                                    main domain to serve images, please enter the domain name here.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[lazy_load]">
                                    <?php esc_html_e('Lazy Load Images', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[lazy_load]" type="checkbox" name="gumlet_settings[lazy_load]"
                                    value="1" <?php checked($this->get_option('lazy_load')) ?> />
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[auto_compress]">
                                    <?php esc_html_e('Auto Compress Images', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[auto_compress]" type="checkbox"
                                    name="gumlet_settings[auto_compress]" value="1" <?php
                                    checked($this->get_option('auto_compress')) ?> />
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[quality]">
                                    <?php esc_html_e('Image Quality', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[quality]" type="number" placeholder="Default: 80"
                                    name="gumlet_settings[quality]" value="<?php echo $this->get_option('quality'); ?>"
                                    min="40" max='95' style="width: 200px;" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <input type="radio" id="tabadvanced" name="mytabs">
            <label for="tabadvanced" class="mytablabel">Advanced</label>
            <div class="tab">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings_auto_resize">
                                    <?php esc_html_e('Auto Resize', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="hidden" name="gumlet_settings[auto_resize]" value="0" />
                                <input id="gumlet_settings_auto_resize" type="checkbox"
                                    name="gumlet_settings[auto_resize]" value="1" <?php
                                    checked($this->get_option('auto_resize', true)) ?> />
                                <p style="color: #666"><?php esc_html_e('When enabled, images use a placeholder and Gumlet.js resizes per viewport (including lazy loading, if enabled). When disabled, Gumlet.js is not loaded: the image src is set directly to the Gumlet URL (no placeholder and no lazy loading).', 'gumlet'); ?></p>
                                <p class="gumlet-warning"><?php esc_html_e('Ignored while Signed URLs is enabled under Experimental. Auto Resize stays off so Gumlet.js cannot change a URL after it is signed.', 'gumlet'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[original_images]">
                                    <?php esc_html_e('Use Original Images', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[original_images]" type="checkbox"
                                    name="gumlet_settings[original_images]" value="1" <?php
                                    checked($this->get_option('original_images')) ?> />
                                <p style="color: #666">If this is enabled (recommended), plugin will use original images
                                    before processing. <br>If this is not enabled, Gumlet will use images resized by
                                    wordpress for further processing.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[server_webp]">
                                    <?php esc_html_e('Browser Webp Detect', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[server_webp]" type="checkbox"
                                    name="gumlet_settings[server_webp]" value="1" <?php
                                    checked($this->get_option('server_webp')) ?> />
                                <p style="color: #666">If this is enabled, plugin will detect Webp support from browser
                                    rather than from server.(recommended OFF)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_width_from_img">
                                    <?php esc_html_e('Use <img> Width', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_width_from_img" type="checkbox" name="gumlet_width_from_img" value="1"
                                    <?php checked(get_option('gumlet_width_from_img')) ?> />
                                <p style="color: #666">If this is enabled, plugin will use width from &lt;img&gt;
                                    element width attribute rather than calculating actual width.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_width_from_flex">
                                    <?php esc_html_e('Use Flex Width', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_width_from_flex" type="checkbox" name="gumlet_width_from_flex" value="1"
                                    <?php checked(get_option('gumlet_width_from_flex')) ?> />
                                <p style="color: #666">If this is enabled, plugin will use width from Flex CSS propery rather than calculating actual width.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_min_width">
                                    <?php esc_html_e('Minimum Width', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_min_width" type="number" name="gumlet_min_width" min="0" max="5000" value="<?php echo get_option('gumlet_min_width'); ?>" />
                                <p style="color: #666">If set, this will be minimum pixel width for image serving.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[exclude_images]">
                                    <?php esc_html_e('Exclude Image URLs', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea id="gumlet_settings[exclude_images]" style="width: 500px; height: 100px"
                                    placeholder="Enter every URL in new line."
                                    name="gumlet_settings[exclude_images]"><?php print($this->get_option('exclude_images')) ?></textarea>
                                <p style="color: #666">The URLs you enter here will not be processed by Gumlet. Please
                                    enter one URL per line.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[exclude_post_types]">
                                    <?php esc_html_e('Exclude Post Types', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <textarea id="gumlet_settings[exclude_post_types]" style="width: 500px; height: 100px"
                                    placeholder="Enter each post type in new line."
                                    name="gumlet_settings[exclude_post_types]"><?php print($this->get_option('exclude_post_types')) ?></textarea>
                                <p style="color: #666">Enter post types here to exclude those posts from being processed with Gumlet.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <input type="radio" id="tabexperimental" name="mytabs">
            <label for="tabexperimental" class="mytablabel">Experimental</label>
            <div class="tab">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[optimize_priority_images]">
                                    <?php esc_html_e('Optimize Priority Images', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[optimize_priority_images]" type="checkbox"
                                    name="gumlet_settings[optimize_priority_images]" value="1" <?php
                                    checked($this->get_option('optimize_priority_images')) ?> />
                                <p style="color: #666">Serves your most important image (usually the hero or first
                                    image) through Gumlet at the right size for each screen, without waiting for
                                    Gumlet.js. WordPress and many themes mark this image with fetchpriority="high", and by
                                    default Gumlet leaves it untouched, so it loads at its full original size.
                                    <br>Turn this on to improve Largest Contentful Paint (LCP) and reduce mobile page
                                    weight. Recommended if your theme uses large banner or slider images.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[aspect_ratio_placeholder]">
                                    <?php esc_html_e('Aspect-Ratio Placeholders', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[aspect_ratio_placeholder]" type="checkbox"
                                    name="gumlet_settings[aspect_ratio_placeholder]" value="1" <?php
                                    checked($this->get_option('aspect_ratio_placeholder')) ?> />
                                <p style="color: #666">While an image is waiting to load, reserves space with the image's
                                    exact shape (from its width and height attributes) instead of a 1x1 square. This stops
                                    content from jumping as images load (better CLS) and fixes gaps in masonry grids,
                                    galleries and sliders that measure images while loading. Images without width and
                                    height use the default placeholder.
                                    <br>Turn this on if you see large gaps or overlapping content that corrects itself
                                    when the browser window is resized.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[signed_urls]">
                                    <?php esc_html_e('Signed URLs', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[signed_urls]" type="checkbox"
                                    name="gumlet_settings[signed_urls]" value="1" <?php
                                    checked($this->get_option('signed_urls')) ?> />
                                <p style="color: #666">Signs each image URL with your Gumlet source secure token. Unsigned or modified URLs are rejected, which stops other sites from using your images or CDN and consuming bandwidth.</p>
                                <p class="gumlet-warning"><strong>Auto Resize will not run while this is on.</strong> Gumlet.js adds the width in the browser after the URL is signed, and Gumlet rejects the changed URL. Images are served from the signed URL directly. Gumlet.js lazy loading stays off as well. In the Gumlet dashboard, open the image source, Security tab, and turn on Secure URLs. Paste the token below and save here before enabling it on the source, then clear any page cache.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[secure_token]">
                                    <?php esc_html_e('Secure token', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[secure_token]" type="text" name="gumlet_settings[secure_token]"
                                    value="<?php echo esc_attr($this->get_option('secure_token')); ?>"
                                    class="regular-text code" autocomplete="off" spellcheck="false" />
                                <p class="gumlet-warning">From the source Security tab. Used only on the server to sign URLs. Leave Signed URLs off until this is saved.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[signed_url_expiry]">
                                    <?php esc_html_e('Expiry (seconds)', 'gumlet'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="gumlet_settings[signed_url_expiry]" type="number" min="0" step="1"
                                    name="gumlet_settings[signed_url_expiry]" placeholder="Optional"
                                    value="<?php echo esc_attr($this->get_option('signed_url_expiry')); ?>"
                                    style="width: 200px;" />
                                <p class="gumlet-warning">Optional. Leave blank and signed URLs do not expire. A number is how many seconds the URL stays valid after the page is rendered (3600 is one hour). Gumlet rejects it after that and caches the image only until then. Keep any full-page cache shorter than this value.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <input type="submit" class="button-primary" value="<?php esc_html_e('Save Options', 'gumlet'); ?>" />
        <style>
        
        .mytabs {
            display: flex;
            flex-wrap: wrap;
            margin: 5px auto;
        }

        .mytabs input[type="radio"] {
            display: none;
        }

        .mytabs .tab {
            width: 100%;
            padding: 20px;
            background: #fff;
            order: 1;
            display: none;
        }
        .mytabs .mytablabel {
            padding: 10px;
            background: #e2e2e2;
        }

        .mytabs input[type='radio']:checked + label + .tab {
            display: block;
        }

        .mytabs input[type="radio"]:checked + label {
            background: #fff;
        }

        .gumlet-warning,
        .notice.gumlet-warning,
        .notice.gumlet-warning p {
            color: #d63638;
        }

        .notice.gumlet-warning {
            border-left-color: #d63638;
            background: #fcf0f1;
        }
        </style>

    </form>
    <br>
    <p class="description">
        This plugin is powered by
        <a href="https://www.gumlet.com" target="_blank">Gumlet</a>. You can find and contribute to the code on
        <a href="https://github.com/gumlet/wordpress-plugin" target="_blank">GitHub</a>.
    </p>
</div>
		<?php
    }

    /**
     *  Adds link to options page in Admin > Settings menu.
     */
    public function gumlet_add_options_link()
    {
        add_options_page('Gumlet', 'Gumlet', 'manage_options', 'gumlet-options', [ $this, 'gumlet_options_page' ]);
    }

    /**
     *  Creates our settings in the options table.
     */
    public function gumlet_register_settings()
    {
        register_setting('gumlet_settings_group', 'gumlet_settings');
        register_setting('gumlet_settings_group', 'gumlet_width_from_img', ["type"=> 'boolean', "default"=>true]);
        register_setting('gumlet_settings_group', 'gumlet_width_from_flex', ["type"=> 'boolean', "default"=>false]);
        register_setting('gumlet_settings_group', 'gumlet_min_width', ["type"=> 'integer']);
    }

    /**
     * Get option and handle if option is not set
     *
     * @param string $key
     * @param mixed  $default Returned when the key is not present in the array.
     * @return mixed
     */
    protected function get_option($key, $default = '')
    {
        // array_key_exists: key exists but value is null → still use null (isset() would treat as missing).
        return array_key_exists($key, $this->options) ? $this->options[ $key ] : $default;
    }
}

Gumlet_Options_Page::instance();
