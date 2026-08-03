<?php
/**
 * Seofyme Premium admin menus and settings.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Seofyme premium admin UI under SEO menu.
 */
class Seofyme_Premium_Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'register_menus' ], 99 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( SEOFYME_SEO_FILE ), [ $this, 'action_links' ] );
	}

	/**
	 * Add submenu pages.
	 *
	 * @return void
	 */
	public function register_menus() {
		$parent = 'wpseo_dashboard';

		add_submenu_page(
			$parent,
			__( 'Redirects', 'seofyme-seo' ),
			__( 'Redirects', 'seofyme-seo' ),
			'edit_others_posts',
			'seofyme-redirects',
			[ $this, 'render_redirects_page' ]
		);

		add_submenu_page(
			$parent,
			__( 'Workouts', 'seofyme-seo' ),
			__( 'Workouts', 'seofyme-seo' ),
			'edit_others_posts',
			'seofyme-workouts',
			[ $this, 'render_workouts_page' ]
		);

		add_submenu_page(
			$parent,
			__( 'Seofyme Tools', 'seofyme-seo' ),
			__( 'Seofyme Tools', 'seofyme-seo' ),
			'manage_options',
			'seofyme-tools',
			[ $this, 'render_tools_page' ]
		);
	}

	/**
	 * Register options.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'seofyme_premium', 'seofyme_ai_api_key', [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
		register_setting( 'seofyme_premium', 'seofyme_ai_provider', [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'default' => 'openai' ] );
		register_setting( 'seofyme_premium', 'seofyme_bot_blocker', [ 'type' => 'array', 'sanitize_callback' => [ $this, 'sanitize_bots' ], 'default' => [] ] );
		register_setting( 'seofyme_premium', 'seofyme_indexnow_key', [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
		register_setting( 'seofyme_premium', 'seofyme_schema_aggregate', [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ] );
		register_setting( 'seofyme_premium', 'seofyme_local_seo', [ 'type' => 'array', 'default' => [] ] );
	}

	/**
	 * Sanitize bot blocker options.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	public function sanitize_bots( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}
		return array_map( 'sanitize_key', $value );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'seofyme' ) === false && strpos( $hook, 'wpseo' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'seofyme-premium-admin',
			SEOFYME_PREMIUM_URL . 'assets/admin.css',
			[],
			SEOFYME_SEO_VERSION
		);

		wp_enqueue_script(
			'seofyme-premium-admin',
			SEOFYME_PREMIUM_URL . 'assets/admin.js',
			[ 'jquery' ],
			SEOFYME_SEO_VERSION,
			true
		);

		wp_localize_script(
			'seofyme-premium-admin',
			'seofymePremium',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => esc_url_raw( rest_url( 'seofyme/v1/' ) ),
				'nonce'   => wp_create_nonce( 'seofyme_premium' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Plugin action links.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=seofyme-tools' ) ) . '">' . esc_html__( 'Seofyme Tools', 'seofyme-seo' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * Redirects page.
	 *
	 * @return void
	 */
	public function render_redirects_page() {
		$admin = Seofyme_Premium::instance()->module( 'redirect_admin' );
		if ( $admin ) {
			$admin->render_page();
		}
	}

	/**
	 * Workouts page.
	 *
	 * @return void
	 */
	public function render_workouts_page() {
		$workouts = Seofyme_Premium::instance()->module( 'workouts' );
		if ( $workouts ) {
			$workouts->render_page();
		}
	}

	/**
	 * Tools / settings page.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$bots     = (array) get_option( 'seofyme_bot_blocker', [] );
		$known    = Seofyme_Bot_Blocker::known_bots();
		$provider = get_option( 'seofyme_ai_provider', 'openai' );
		?>
		<div class="wrap seofyme-tools">
			<h1><?php esc_html_e( 'Seofyme SEO Tools', 'seofyme-seo' ); ?></h1>
			<p><?php esc_html_e( 'Premium-parity features bundled with Seofyme SEO — redirects, multi-keyphrase, internal linking, AI drafting, bot controls, Local/Video/News SEO, and more.', 'seofyme-seo' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'seofyme_premium' ); ?>

				<h2><?php esc_html_e( 'AI drafting', 'seofyme-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="seofyme_ai_provider"><?php esc_html_e( 'Provider', 'seofyme-seo' ); ?></label></th>
						<td>
							<select name="seofyme_ai_provider" id="seofyme_ai_provider">
								<option value="openai" <?php selected( $provider, 'openai' ); ?>>OpenAI</option>
								<option value="anthropic" <?php selected( $provider, 'anthropic' ); ?>>Anthropic</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="seofyme_ai_api_key"><?php esc_html_e( 'API key', 'seofyme-seo' ); ?></label></th>
						<td><input type="password" class="regular-text" name="seofyme_ai_api_key" id="seofyme_ai_api_key" value="<?php echo esc_attr( get_option( 'seofyme_ai_api_key', '' ) ); ?>" autocomplete="off" /></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'AI bot blocker', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'Block selected AI crawlers from training on your content (via robots.txt).', 'seofyme-seo' ); ?></p>
				<table class="form-table" role="presentation">
					<?php foreach ( $known as $key => $label ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="seofyme_bot_blocker[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $bots, true ) ); ?> />
									<?php esc_html_e( 'Block', 'seofyme-seo' ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php esc_html_e( 'IndexNow', 'seofyme-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="seofyme_indexnow_key"><?php esc_html_e( 'IndexNow key', 'seofyme-seo' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="seofyme_indexnow_key" id="seofyme_indexnow_key" value="<?php echo esc_attr( get_option( 'seofyme_indexnow_key', '' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Leave empty to auto-generate on first publish.', 'seofyme-seo' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Schema aggregation', 'seofyme-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Aggregate schema graph', 'seofyme-seo' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="seofyme_schema_aggregate" value="1" <?php checked( get_option( 'seofyme_schema_aggregate', true ) ); ?> />
								<?php esc_html_e( 'Deduplicate and expose a single site-wide schema graph endpoint', 'seofyme-seo' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
