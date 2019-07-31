<?php

class Gumlet_Options_Page {

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


	public function __construct() {
		$this->options = get_option( 'gumlet_settings', [] );
		add_action( 'admin_init', [ $this, 'gumlet_register_settings' ] );
		add_action( 'admin_menu', [ $this, 'gumlet_add_options_link' ] );
	}

	/**
	 * Plugin loader instance.
	 *
	 * @return Gumlet_Options_Page
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Renders options page
	 */
	public function gumlet_options_page() {
		?>
		<div class="wrap">

			<h1>
				<img src="<?php echo plugins_url( 'assets/images/gumlet-logo.png', __DIR__ ); ?>" alt="gumlet Logo">
			</h1>

			<p><strong>Need help getting started?</strong> It's easy! Check out our
				<a href="https://github.com/gumlet/wordpress-plugin" target="_blank">instructions.</a>
			</p>

			<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( 'gumlet_settings_group' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label class="description" for="gumlet_settings[cdn_link]"><?php esc_html_e( 'Gumlet Source', 'gumlet' ); ?>
							</th>
							<td>
								<input id="gumlet_settings[cdn_link]" type="url" name="gumlet_settings[cdn_link]" placeholder="https://yourcompany.gumlet.com" value="<?php echo $this->get_option( 'cdn_link' ); ?>" class="regular-text code"/>
							</td>
						</tr>
                        <tr>
                            <th>
                                <label class="description" for="gumlet_settings[external_cdn_link]"><?php esc_html_e( 'CDN URL', 'gumlet' ); ?>
                            </th>
                            <td>
                                <input id="gumlet_settings[external_cdn_link]" type="url" name="gumlet_settings[external_cdn_link]" placeholder="http://s3-eu-west-2.amazonaws.com/your-bucket" value="<?php echo $this->get_option( 'external_cdn_link' ); ?>" class="regular-text code"/>
                            </td>
                        </tr>
						<tr>
							<th>
								<label class="description" for="gumlet_settings[auto_format]"><?php esc_html_e( 'Auto Format Images', 'gumlet' ); ?></label>
							</th>
							<td>
								<input id="gumlet_settings[auto_format]" type="checkbox" name="gumlet_settings[auto_format]" value="1" <?php checked( $this->get_option( 'auto_format' ) ) ?> />
							</td>
						</tr>
						<tr>
							<th>
								<label class="description" for="gumlet_settings[auto_compress]"><?php esc_html_e( 'Auto Compress Images', 'gumlet' ); ?></label>
							</th>
							<td>
								<input id="gumlet_settings[auto_compress]" type="checkbox" name="gumlet_settings[auto_compress]" value="1" <?php checked( $this->get_option( 'auto_compress' ) ) ?> />
							</td>
						</tr>
						<tr>
							<th>
								<label class="description" for="gumlet_settings[quality]"><?php esc_html_e( 'Image Quality', 'gumlet' ); ?></label>
							</th>
							<td>
								<input id="gumlet_settings[quality]" type="number" name="gumlet_settings[quality]" value="80" <?php checked( $this->get_option( 'quality' ) ) ?> />
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_html_e( 'Save Options', 'gumlet' ); ?>"/>
				</p>
			</form>

			<p class="description">
				This plugin is powered by
				<a href="http://www.gulet.com" target="_blank">Gumlet</a>. You can find and contribute to the code on
				<a href="https://github.com/gumlet/wordpress-plugin" target="_blank">GitHub</a>.
			</p>
		</div>
		<?php
	}

	/**
	 *  Adds link to options page in Admin > Settings menu.
	 */
	public function gumlet_add_options_link() {
		add_options_page( 'gumlet', 'gumlet', 'manage_options', 'gumlet-options', [ $this, 'gumlet_options_page' ] );
	}

	/**
	 *  Creates our settings in the options table.
	 */
	public function gumlet_register_settings() {
		register_setting( 'gumlet_settings_group', 'gumlet_settings' );
	}

	/**
	 * Get option and handle if option is not set
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected function get_option( $key ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : '';
	}
}

Gumlet_Options_Page::instance();
