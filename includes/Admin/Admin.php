<?php
/**
 * Admin menus and settings screens.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Admin;

use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wp-admin integration.
 */
class Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . SEOFYME_SEO_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Menus.
	 *
	 * @return void
	 */
	public function menus() {
		add_menu_page(
			__( 'Seofyme SEO', 'seofyme-seo' ),
			__( 'Seofyme SEO', 'seofyme-seo' ),
			'manage_options',
			'seofyme-seo',
			array( $this, 'render_dashboard' ),
			'dashicons-chart-area',
			58
		);

		add_submenu_page( 'seofyme-seo', __( 'Dashboard', 'seofyme-seo' ), __( 'Dashboard', 'seofyme-seo' ), 'manage_options', 'seofyme-seo', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'seofyme-seo', __( 'Settings', 'seofyme-seo' ), __( 'Settings', 'seofyme-seo' ), 'manage_options', 'seofyme-seo-settings', array( $this, 'render_settings' ) );
		add_submenu_page( 'seofyme-seo', __( 'Redirects', 'seofyme-seo' ), __( 'Redirects', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-redirects', array( $this, 'render_redirects' ) );
		add_submenu_page( 'seofyme-seo', __( 'Bulk editor', 'seofyme-seo' ), __( 'Bulk editor', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-bulk', array( $this, 'render_bulk' ) );
		add_submenu_page( 'seofyme-seo', __( 'Workouts', 'seofyme-seo' ), __( 'Workouts', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-workouts', array( $this, 'render_workouts' ) );
	}

	/**
	 * Assets.
	 *
	 * @param string $hook Hook.
	 * @return void
	 */
	public function assets( $hook ) {
		$is_seofyme = ( strpos( $hook, 'seofyme-seo' ) !== false );
		$screen     = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_editor  = $screen && in_array( $screen->base, array( 'post', 'page' ), true );

		if ( ! $is_seofyme && ! $is_editor ) {
			return;
		}

		wp_enqueue_style( 'seofyme-seo-admin', SEOFYME_SEO_URL . 'assets/css/admin.css', array(), SEOFYME_SEO_VERSION );
		wp_enqueue_script( 'seofyme-seo-admin', SEOFYME_SEO_URL . 'assets/js/admin.js', array( 'jquery' ), SEOFYME_SEO_VERSION, true );
		wp_localize_script(
			'seofyme-seo-admin',
			'seofymeSEO',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => esc_url_raw( rest_url( 'seofyme/v1/' ) ),
				'nonce'   => wp_create_nonce( 'seofyme_seo' ),
			)
		);
	}

	/**
	 * Plugin links.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=seofyme-seo-settings' ) ) . '">' . esc_html__( 'Settings', 'seofyme-seo' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$sitemap = home_url( '/sitemap.xml' );
		?>
		<div class="wrap seofyme-wrap">
			<h1><?php esc_html_e( 'Seofyme SEO', 'seofyme-seo' ); ?></h1>
			<p><?php esc_html_e( 'Your own SEO stack — content guidance, technical SEO, redirects, linking, and AI drafting. Built by Seofyme.', 'seofyme-seo' ); ?></p>
			<div class="seofyme-cards">
				<div class="seofyme-card">
					<h2><?php esc_html_e( 'XML sitemap', 'seofyme-seo' ); ?></h2>
					<p><a href="<?php echo esc_url( $sitemap ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $sitemap ); ?></a></p>
				</div>
				<div class="seofyme-card">
					<h2><?php esc_html_e( 'Quick links', 'seofyme-seo' ); ?></h2>
					<ul>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'seofyme-seo' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-redirects' ) ); ?>"><?php esc_html_e( 'Redirects', 'seofyme-seo' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-bulk' ) ); ?>"><?php esc_html_e( 'Bulk editor', 'seofyme-seo' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-workouts' ) ); ?>"><?php esc_html_e( 'Workouts', 'seofyme-seo' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o    = Options::all();
		$bots = \SeofymeSEO\Modules\BotBlocker\BotBlocker::known_bots();
		?>
		<div class="wrap seofyme-wrap">
			<h1><?php esc_html_e( 'Seofyme SEO settings', 'seofyme-seo' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'seofyme_seo' ); ?>
				<h2><?php esc_html_e( 'General', 'seofyme-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="title_separator"><?php esc_html_e( 'Title separator', 'seofyme-seo' ); ?></label></th>
						<td><input name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[title_separator]" id="title_separator" value="<?php echo esc_attr( $o['title_separator'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th><label for="homepage_title"><?php esc_html_e( 'Homepage title', 'seofyme-seo' ); ?></label></th>
						<td><input name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[homepage_title]" id="homepage_title" value="<?php echo esc_attr( $o['homepage_title'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="homepage_description"><?php esc_html_e( 'Homepage description', 'seofyme-seo' ); ?></label></th>
						<td><textarea name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[homepage_description]" id="homepage_description" class="large-text" rows="3"><?php echo esc_textarea( $o['homepage_description'] ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="organization_name"><?php esc_html_e( 'Organization name', 'seofyme-seo' ); ?></label></th>
						<td><input name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[organization_name]" id="organization_name" value="<?php echo esc_attr( $o['organization_name'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="organization_logo"><?php esc_html_e( 'Organization logo URL', 'seofyme-seo' ); ?></label></th>
						<td><input name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[organization_logo]" id="organization_logo" value="<?php echo esc_attr( $o['organization_logo'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Features', 'seofyme-seo' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[xml_sitemap]" value="1" <?php checked( $o['xml_sitemap'] ); ?> /> <?php esc_html_e( 'XML sitemaps', 'seofyme-seo' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[schema_enabled]" value="1" <?php checked( $o['schema_enabled'] ); ?> /> <?php esc_html_e( 'Structured data', 'seofyme-seo' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[schema_aggregate]" value="1" <?php checked( $o['schema_aggregate'] ); ?> /> <?php esc_html_e( 'Schema aggregation endpoint', 'seofyme-seo' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[noindex_search]" value="1" <?php checked( $o['noindex_search'] ); ?> /> <?php esc_html_e( 'Noindex search results', 'seofyme-seo' ); ?></label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'AI drafting', 'seofyme-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="ai_provider"><?php esc_html_e( 'Provider', 'seofyme-seo' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[ai_provider]" id="ai_provider">
								<option value="openai" <?php selected( $o['ai_provider'], 'openai' ); ?>>OpenAI</option>
								<option value="anthropic" <?php selected( $o['ai_provider'], 'anthropic' ); ?>>Anthropic</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ai_api_key"><?php esc_html_e( 'API key', 'seofyme-seo' ); ?></label></th>
						<td><input type="password" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[ai_api_key]" id="ai_api_key" value="<?php echo esc_attr( $o['ai_api_key'] ); ?>" class="regular-text" autocomplete="off" /></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'AI bot blocker', 'seofyme-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $bots as $key => $label ) : ?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<td><label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[bot_blocker][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, (array) $o['bot_blocker'], true ) ); ?> /> <?php esc_html_e( 'Block', 'seofyme-seo' ); ?></label></td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php esc_html_e( 'IndexNow', 'seofyme-seo' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="indexnow_key"><?php esc_html_e( 'Key', 'seofyme-seo' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[indexnow_key]" id="indexnow_key" value="<?php echo esc_attr( $o['indexnow_key'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Leave empty to auto-generate.', 'seofyme-seo' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Redirects screen proxy.
	 *
	 * @return void
	 */
	public function render_redirects() {
		( new \SeofymeSEO\Modules\Redirects\Admin_Page() )->render();
	}

	/**
	 * Bulk screen proxy.
	 *
	 * @return void
	 */
	public function render_bulk() {
		( new \SeofymeSEO\Modules\AI\BulkMeta() )->render_page();
	}

	/**
	 * Workouts screen proxy.
	 *
	 * @return void
	 */
	public function render_workouts() {
		( new \SeofymeSEO\Modules\Workouts\Workouts() )->render_page();
	}
}
