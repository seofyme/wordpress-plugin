<?php
/**
 * Admin menus and settings screens.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Admin;

use SeofymeSEO\Support\Cloud_Account;
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
		add_action( 'admin_init', array( $this, 'handle_account_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_head', array( $this, 'menu_icon_styles' ) );
		add_filter( 'plugin_action_links_' . SEOFYME_SEO_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Size the custom PNG correctly in the wp-admin menu.
	 *
	 * @return void
	 */
	public function menu_icon_styles() {
		?>
		<style>
			#adminmenu #toplevel_page_seofyme-seo .wp-menu-image img {
				width: 20px;
				height: 20px;
				padding: 7px 0 0;
				opacity: 0.85;
			}
			#adminmenu #toplevel_page_seofyme-seo:hover .wp-menu-image img,
			#adminmenu #toplevel_page_seofyme-seo.wp-has-current-submenu .wp-menu-image img,
			#adminmenu #toplevel_page_seofyme-seo.current .wp-menu-image img {
				opacity: 1;
			}
		</style>
		<?php
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
			SEOFYME_SEO_URL . 'assets/seofyme-logo.png',
			58
		);

		add_submenu_page( 'seofyme-seo', __( 'Dashboard', 'seofyme-seo' ), __( 'Dashboard', 'seofyme-seo' ), 'manage_options', 'seofyme-seo', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'seofyme-seo', __( 'Settings', 'seofyme-seo' ), __( 'Settings', 'seofyme-seo' ), 'manage_options', 'seofyme-seo-settings', array( $this, 'render_settings' ) );
		add_submenu_page( 'seofyme-seo', __( 'Site audit', 'seofyme-seo' ), __( 'Site audit', 'seofyme-seo' ), 'manage_options', 'seofyme-seo-audit', array( $this, 'render_audit' ) );
		add_submenu_page( 'seofyme-seo', __( 'Redirects', 'seofyme-seo' ), __( 'Redirects', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-redirects', array( $this, 'render_redirects' ) );
		add_submenu_page( 'seofyme-seo', __( '404 monitor', 'seofyme-seo' ), __( '404 monitor', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-404', array( $this, 'render_404' ) );
		add_submenu_page( 'seofyme-seo', __( 'Link assistant', 'seofyme-seo' ), __( 'Link assistant', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-links', array( $this, 'render_links' ) );
		add_submenu_page( 'seofyme-seo', __( 'Rank tracker', 'seofyme-seo' ), __( 'Rank tracker', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-ranks', array( $this, 'render_ranks' ) );
		add_submenu_page( 'seofyme-seo', __( 'Bulk editor', 'seofyme-seo' ), __( 'Bulk editor', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-bulk', array( $this, 'render_bulk' ) );
		add_submenu_page( 'seofyme-seo', __( 'Image SEO', 'seofyme-seo' ), __( 'Image SEO', 'seofyme-seo' ), 'upload_files', 'seofyme-seo-images', array( $this, 'render_images' ) );
		add_submenu_page( 'seofyme-seo', __( 'Workouts', 'seofyme-seo' ), __( 'Workouts', 'seofyme-seo' ), 'edit_others_posts', 'seofyme-seo-workouts', array( $this, 'render_workouts' ) );
		add_submenu_page( 'seofyme-seo', __( 'Account', 'seofyme-seo' ), __( 'Account', 'seofyme-seo' ), 'manage_options', 'seofyme-seo-account', array( $this, 'render_account' ) );
	}

	/**
	 * Save Cloud credentials and refresh subscription status.
	 *
	 * @return void
	 */
	public function handle_account_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['seofyme_save_account'] ) ) {
			check_admin_referer( 'seofyme_save_account' );
			$public = isset( $_POST['seofyme_public_key'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_public_key'] ) ) : '';
			$secret = isset( $_POST['seofyme_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_secret_key'] ) ) : '';

			Options::update(
				array(
					'seofyme_public_key' => $public,
					'seofyme_secret_key' => $secret,
				)
			);
			Cloud_Account::clear_cache();

			if ( '' !== $public && '' !== $secret ) {
				$status = Cloud_Account::sync();
				$error  = Cloud_Account::get_last_error();
				if ( $error ) {
					add_settings_error(
						'seofyme_messages',
						'account_save_error',
						sprintf(
							/* translators: %s: API error message. */
							__( 'Credentials saved, but the account could not be refreshed: %s', 'seofyme-seo' ),
							$error
						),
						'error'
					);
				} else {
					add_settings_error(
						'seofyme_messages',
						'account_saved',
						sprintf(
							/* translators: %s: subscription plan name. */
							__( 'Account connected. Current plan: %s.', 'seofyme-seo' ),
							isset( $status['planName'] ) ? (string) $status['planName'] : 'Free'
						),
						'success'
					);
				}
			} else {
				Cloud_Account::sync();
				add_settings_error( 'seofyme_messages', 'account_disconnected', __( 'Cloud credentials updated.', 'seofyme-seo' ), 'success' );
			}
		}

		if ( isset( $_POST['seofyme_refresh_plan'] ) ) {
			check_admin_referer( 'seofyme_refresh_plan' );
			Cloud_Account::clear_cache();
			$status = Cloud_Account::sync();
			$error  = Cloud_Account::get_last_error();
			if ( $error ) {
				add_settings_error(
					'seofyme_messages',
					'plan_refresh_error',
					sprintf(
						/* translators: %s: API error message. */
						__( 'Plan refresh failed: %s', 'seofyme-seo' ),
						$error
					),
					'error'
				);
			} else {
				add_settings_error(
					'seofyme_messages',
					'plan_refreshed',
					sprintf(
						/* translators: %s: subscription plan name. */
						__( 'Plan status refreshed: %s.', 'seofyme-seo' ),
						isset( $status['planName'] ) ? (string) $status['planName'] : 'Free'
					),
					'success'
				);
			}
		}
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

		$css_ver = SEOFYME_SEO_VERSION . '.' . (string) filemtime( SEOFYME_SEO_PATH . 'assets/css/admin.css' );
		$js_ver  = SEOFYME_SEO_VERSION . '.' . (string) filemtime( SEOFYME_SEO_PATH . 'assets/js/admin.js' );
		wp_enqueue_style( 'seofyme-seo-admin', SEOFYME_SEO_URL . 'assets/css/admin.css', array(), $css_ver );
		wp_enqueue_script( 'seofyme-seo-admin', SEOFYME_SEO_URL . 'assets/js/admin.js', array( 'jquery' ), $js_ver, true );
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
		$schema  = home_url( '/seofyme-schema.json' );
		Page_Shell::open(
			__( 'Dashboard', 'seofyme-seo' ),
			__( 'Content guidance, technical SEO, redirects, linking, and AI drafting — in one clean workspace.', 'seofyme-seo' )
		);
		?>
			<div class="seofyme-cards">
				<article class="seofyme-card">
					<span class="seofyme-kicker"><?php esc_html_e( 'Technical', 'seofyme-seo' ); ?></span>
					<h2><?php esc_html_e( 'XML sitemap', 'seofyme-seo' ); ?></h2>
					<p><?php esc_html_e( 'Keep search engines in sync with your published content.', 'seofyme-seo' ); ?></p>
					<div class="seofyme-actions">
						<a class="button button-primary" href="<?php echo esc_url( $sitemap ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open sitemap', 'seofyme-seo' ); ?></a>
					</div>
				</article>
				<article class="seofyme-card">
					<span class="seofyme-kicker"><?php esc_html_e( 'Structured data', 'seofyme-seo' ); ?></span>
					<h2><?php esc_html_e( 'Schema graph', 'seofyme-seo' ); ?></h2>
					<p><?php esc_html_e( 'A single aggregated graph for machines and AI systems.', 'seofyme-seo' ); ?></p>
					<div class="seofyme-actions">
						<a class="button" href="<?php echo esc_url( $schema ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View graph', 'seofyme-seo' ); ?></a>
					</div>
				</article>
				<article class="seofyme-card">
					<span class="seofyme-kicker"><?php esc_html_e( 'Workflow', 'seofyme-seo' ); ?></span>
					<h2><?php esc_html_e( 'Jump in', 'seofyme-seo' ); ?></h2>
					<ul>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'seofyme-seo' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-redirects' ) ); ?>"><?php esc_html_e( 'Redirects', 'seofyme-seo' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-bulk' ) ); ?>"><?php esc_html_e( 'Bulk editor', 'seofyme-seo' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-workouts' ) ); ?>"><?php esc_html_e( 'Workouts', 'seofyme-seo' ); ?></a></li>
					</ul>
				</article>
			</div>
		<?php
		Page_Shell::close();
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
		Page_Shell::open(
			__( 'Settings', 'seofyme-seo' ),
			__( 'Tune titles, schema, AI drafting, bot controls, and IndexNow.', 'seofyme-seo' )
		);
		?>
			<form method="post" action="options.php" class="seofyme-panel-stack">
				<?php settings_fields( 'seofyme_seo' ); ?>
				<section class="seofyme-panel">
					<h2><?php esc_html_e( 'General', 'seofyme-seo' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th><label for="title_separator"><?php esc_html_e( 'Title separator', 'seofyme-seo' ); ?></label></th>
							<td><input type="text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[title_separator]" id="title_separator" value="<?php echo esc_attr( $o['title_separator'] ); ?>" class="small-text" /></td>
						</tr>
						<tr>
							<th><label for="homepage_title"><?php esc_html_e( 'Homepage title', 'seofyme-seo' ); ?></label></th>
							<td><input type="text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[homepage_title]" id="homepage_title" value="<?php echo esc_attr( $o['homepage_title'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th><label for="homepage_description"><?php esc_html_e( 'Homepage description', 'seofyme-seo' ); ?></label></th>
							<td><textarea name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[homepage_description]" id="homepage_description" class="large-text" rows="3"><?php echo esc_textarea( $o['homepage_description'] ); ?></textarea></td>
						</tr>
						<tr>
							<th><label for="organization_name"><?php esc_html_e( 'Organization name', 'seofyme-seo' ); ?></label></th>
							<td><input type="text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[organization_name]" id="organization_name" value="<?php echo esc_attr( $o['organization_name'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th><label for="organization_logo"><?php esc_html_e( 'Organization logo URL', 'seofyme-seo' ); ?></label></th>
							<td><input type="url" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[organization_logo]" id="organization_logo" value="<?php echo esc_attr( $o['organization_logo'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Features', 'seofyme-seo' ); ?></th>
							<td class="seofyme-feature-toggles">
								<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[xml_sitemap]" value="1" <?php checked( $o['xml_sitemap'] ); ?> /> <?php esc_html_e( 'XML sitemaps', 'seofyme-seo' ); ?></label>
								<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[schema_enabled]" value="1" <?php checked( $o['schema_enabled'] ); ?> /> <?php esc_html_e( 'Structured data', 'seofyme-seo' ); ?></label>
								<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[schema_aggregate]" value="1" <?php checked( $o['schema_aggregate'] ); ?> /> <?php esc_html_e( 'Schema aggregation endpoint', 'seofyme-seo' ); ?></label>
								<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[noindex_search]" value="1" <?php checked( $o['noindex_search'] ); ?> /> <?php esc_html_e( 'Noindex search results', 'seofyme-seo' ); ?></label>
							</td>
						</tr>
					</table>
				</section>

				<section class="seofyme-panel">
					<h2><?php esc_html_e( 'BYO AI (optional fallback)', 'seofyme-seo' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Used only when Seofyme Cloud keys are empty.', 'seofyme-seo' ); ?></p>
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
				</section>

				<section class="seofyme-panel">
					<h2><?php esc_html_e( 'AI visibility', 'seofyme-seo' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th><?php esc_html_e( 'llms.txt', 'seofyme-seo' ); ?></th>
							<td class="seofyme-feature-toggles">
								<label>
									<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[llms_txt]" value="1" <?php checked( ! empty( $o['llms_txt'] ) ); ?> />
									<?php esc_html_e( 'Publish llms.txt so AI tools can discover important pages', 'seofyme-seo' ); ?>
								</label>
								<p class="description">
									<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( home_url( '/llms.txt' ) ); ?></a>
									— <?php esc_html_e( 'Re-save Permalinks once after enabling if the URL 404s.', 'seofyme-seo' ); ?>
								</p>
							</td>
						</tr>
					</table>
					<h3><?php esc_html_e( 'AI bot blocker', 'seofyme-seo' ); ?></h3>
					<div class="seofyme-bot-grid">
						<?php foreach ( $bots as $key => $label ) : ?>
							<label>
								<span><?php echo esc_html( $label ); ?></span>
								<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[bot_blocker][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, (array) $o['bot_blocker'], true ) ); ?> />
							</label>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="seofyme-panel">
					<h2><?php esc_html_e( 'IndexNow', 'seofyme-seo' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th><label for="indexnow_key"><?php esc_html_e( 'Key', 'seofyme-seo' ); ?></label></th>
							<td>
								<input type="text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[indexnow_key]" id="indexnow_key" value="<?php echo esc_attr( $o['indexnow_key'] ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Leave empty to auto-generate.', 'seofyme-seo' ); ?></p>
							</td>
						</tr>
					</table>
				</section>

				<section class="seofyme-panel">
					<h2><?php esc_html_e( 'Agency / reports', 'seofyme-seo' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th><?php esc_html_e( 'White-label', 'seofyme-seo' ); ?></th>
							<td class="seofyme-feature-toggles">
								<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[whitelabel_enabled]" value="1" <?php checked( ! empty( $o['whitelabel_enabled'] ) ); ?> /> <?php esc_html_e( 'Enable white-label menu name', 'seofyme-seo' ); ?></label>
							</td>
						</tr>
						<tr>
							<th><label for="whitelabel_name"><?php esc_html_e( 'Brand name', 'seofyme-seo' ); ?></label></th>
							<td><input type="text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[whitelabel_name]" id="whitelabel_name" value="<?php echo esc_attr( $o['whitelabel_name'] ); ?>" class="regular-text" placeholder="Acme SEO" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Email reports', 'seofyme-seo' ); ?></th>
							<td class="seofyme-feature-toggles">
								<label><input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[email_reports]" value="1" <?php checked( ! empty( $o['email_reports'] ) ); ?> /> <?php esc_html_e( 'Send weekly SEO summary', 'seofyme-seo' ); ?></label>
							</td>
						</tr>
						<tr>
							<th><label for="report_email"><?php esc_html_e( 'Report email', 'seofyme-seo' ); ?></label></th>
							<td><input type="email" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[report_email]" id="report_email" value="<?php echo esc_attr( $o['report_email'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" /></td>
						</tr>
					</table>
				</section>

				<?php submit_button( __( 'Save settings', 'seofyme-seo' ) ); ?>
			</form>
		<?php
		Page_Shell::close();
	}

	/**
	 * Cloud account and subscription page.
	 *
	 * @return void
	 */
	public function render_account() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options   = Options::all();
		$connected = Cloud_Account::is_connected();
		$status    = Cloud_Account::get_status();
		$error     = Cloud_Account::get_last_error();
		$usage     = isset( $status['usage'] ) && is_array( $status['usage'] ) ? $status['usage'] : array();
		$requests  = isset( $usage['requests'] ) && is_array( $usage['requests'] ) ? $usage['requests'] : array();
		$tokens    = isset( $usage['tokens'] ) && is_array( $usage['tokens'] ) ? $usage['tokens'] : array();

		$format_quota = static function ( $quota ) {
			$used  = isset( $quota['used'] ) ? number_format_i18n( (int) $quota['used'] ) : '0';
			$limit = array_key_exists( 'limit', $quota ) && null !== $quota['limit']
				? number_format_i18n( (int) $quota['limit'] )
				: __( 'Unlimited', 'seofyme-seo' );
			return sprintf(
				/* translators: 1: amount used, 2: plan limit. */
				__( '%1$s of %2$s used', 'seofyme-seo' ),
				$used,
				$limit
			);
		};

		ob_start();
		if ( $connected ) :
			?>
			<form method="post">
				<?php wp_nonce_field( 'seofyme_refresh_plan' ); ?>
				<button type="submit" name="seofyme_refresh_plan" value="1" class="sf-btn sf-btn--secondary"><?php esc_html_e( 'Refresh plan', 'seofyme-seo' ); ?></button>
			</form>
			<?php
		endif;
		?>
		<a class="sf-btn sf-btn--primary" href="https://seofyme.com/account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage account', 'seofyme-seo' ); ?></a>
		<?php
		$actions = ob_get_clean();

		Page_Shell::open(
			__( 'Account', 'seofyme-seo' ),
			__( 'Connect Seofyme Cloud, review your subscription, and refresh current AI usage.', 'seofyme-seo' ),
			$actions
		);
		?>
			<form method="post">
				<?php wp_nonce_field( 'seofyme_save_account' ); ?>
				<section class="sf-card">
					<header class="sf-card__header">
						<h2><?php esc_html_e( 'API credentials', 'seofyme-seo' ); ?></h2>
						<p><?php esc_html_e( 'Create Seofyme product API keys in your account, then connect this WordPress site.', 'seofyme-seo' ); ?></p>
					</header>
					<div class="sf-card__body">
						<div class="sf-field sf-field--stack">
							<label class="sf-field__label" for="seofyme-account-public-key"><?php esc_html_e( 'Public API key', 'seofyme-seo' ); ?></label>
							<input type="text" id="seofyme-account-public-key" name="seofyme_public_key" value="<?php echo esc_attr( $options['seofyme_public_key'] ?? '' ); ?>" class="sf-input" autocomplete="off" />
						</div>
						<div class="sf-field sf-field--stack">
							<label class="sf-field__label" for="seofyme-account-secret-key"><?php esc_html_e( 'Secret API key', 'seofyme-seo' ); ?></label>
							<input type="password" id="seofyme-account-secret-key" name="seofyme_secret_key" value="<?php echo esc_attr( $options['seofyme_secret_key'] ?? '' ); ?>" class="sf-input" autocomplete="new-password" />
						</div>
					</div>
				</section>
				<div class="sf-savebar">
					<button type="submit" name="seofyme_save_account" value="1" class="sf-btn sf-btn--primary"><?php esc_html_e( 'Save credentials', 'seofyme-seo' ); ?></button>
				</div>
			</form>

			<section class="sf-card">
				<header class="sf-card__header">
					<h2><?php esc_html_e( 'Plan status', 'seofyme-seo' ); ?></h2>
					<p><?php esc_html_e( 'Subscription and monthly usage are synced from Seofyme Cloud.', 'seofyme-seo' ); ?></p>
				</header>
				<div class="sf-card__body">
					<?php if ( $error ) : ?>
						<div class="sf-notice sf-notice--warn">
							<?php
							printf(
								/* translators: %s: API error message. */
								esc_html__( 'Last plan refresh failed: %s', 'seofyme-seo' ),
								esc_html( $error )
							);
							?>
						</div>
					<?php endif; ?>
					<table class="sf-table">
						<tbody>
							<tr>
								<th><?php esc_html_e( 'Connection', 'seofyme-seo' ); ?></th>
								<td>
									<span class="sf-badge <?php echo esc_attr( $connected ? '' : 'sf-badge--muted' ); ?>">
										<?php echo $connected ? esc_html__( 'Connected', 'seofyme-seo' ) : esc_html__( 'Not connected', 'seofyme-seo' ); ?>
									</span>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Plan', 'seofyme-seo' ); ?></th>
								<td><?php echo esc_html( isset( $status['planName'] ) ? (string) $status['planName'] : 'Free' ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'AI requests this month', 'seofyme-seo' ); ?></th>
								<td><?php echo esc_html( $format_quota( $requests ) ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'AI tokens this month', 'seofyme-seo' ); ?></th>
								<td><?php echo esc_html( $format_quota( $tokens ) ); ?></td>
							</tr>
							<?php if ( ! empty( $usage['period'] ) ) : ?>
								<tr>
									<th><?php esc_html_e( 'Usage period', 'seofyme-seo' ); ?></th>
									<td><?php echo esc_html( (string) $usage['period'] ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</section>

			<section class="sf-card">
				<header class="sf-card__header">
					<h2><?php esc_html_e( 'Manage subscription', 'seofyme-seo' ); ?></h2>
					<p><?php esc_html_e( 'Compare plans or manage billing from your Seofyme account.', 'seofyme-seo' ); ?></p>
				</header>
				<div class="sf-card__body sf-card__body--flush">
					<a class="sf-btn sf-btn--primary" href="https://seofyme.com/pricing" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Compare plans', 'seofyme-seo' ); ?></a>
					<a class="sf-btn sf-btn--secondary" href="https://seofyme.com/account" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Seofyme account', 'seofyme-seo' ); ?></a>
				</div>
			</section>
		<?php
		Page_Shell::close();
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

	/**
	 * 404 monitor.
	 *
	 * @return void
	 */
	public function render_404() {
		( new \SeofymeSEO\Modules\NotFound\NotFoundMonitor() )->render_page();
	}

	/**
	 * Link assistant.
	 *
	 * @return void
	 */
	public function render_links() {
		( new \SeofymeSEO\Modules\LinkAssistant\LinkAssistant() )->render_page();
	}

	/**
	 * Rank tracker.
	 *
	 * @return void
	 */
	public function render_ranks() {
		( new \SeofymeSEO\Modules\RankTracker\RankTracker() )->render_page();
	}

	/**
	 * Image SEO.
	 *
	 * @return void
	 */
	public function render_images() {
		( new \SeofymeSEO\Modules\ImageSEO\ImageSEO() )->render_page();
	}

	/**
	 * Site audit.
	 *
	 * @return void
	 */
	public function render_audit() {
		( new \SeofymeSEO\Modules\SiteAudit\SiteAudit() )->render_page();
	}
}
