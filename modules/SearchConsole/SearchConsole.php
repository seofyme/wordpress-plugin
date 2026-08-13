<?php
/**
 * Google Search Console OAuth + property selection.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\SearchConsole;

use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connects a Search Console property for rank automation.
 */
class SearchConsole {

	public const AUTH_OPTION = 'seofyme_gsc_auth';
	public const SCOPE       = 'https://www.googleapis.com/auth/webmasters.readonly https://www.googleapis.com/auth/userinfo.email';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );
		add_action( 'admin_post_seofyme_gsc_connect', array( $this, 'handle_connect' ) );
		add_action( 'admin_post_seofyme_gsc_disconnect', array( $this, 'handle_disconnect' ) );
		add_action( 'admin_post_seofyme_gsc_save_property', array( $this, 'handle_save_property' ) );
		add_action( 'admin_post_seofyme_gsc_sync', array( $this, 'handle_sync' ) );
		add_action( 'admin_init', array( $this, 'maybe_flash_notices' ) );
	}

	/**
	 * OAuth callback route (must match the Google redirect URI).
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_route(
			'seofyme/v1',
			'/gsc/callback',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_oauth_callback' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Redirect URI registered in Google Cloud Console.
	 *
	 * @return string
	 */
	public static function redirect_uri() {
		return rest_url( 'seofyme/v1/gsc/callback' );
	}

	/**
	 * Whether OAuth tokens exist.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		$auth = self::auth();
		return ! empty( $auth['refresh_token'] ) || ! empty( $auth['access_token'] );
	}

	/**
	 * Auth payload.
	 *
	 * @return array
	 */
	public static function auth() {
		$auth = get_option( self::AUTH_OPTION, array() );
		return is_array( $auth ) ? $auth : array();
	}

	/**
	 * Persist auth payload.
	 *
	 * @param array $auth Auth.
	 * @return void
	 */
	private static function save_auth( array $auth ) {
		update_option( self::AUTH_OPTION, $auth, false );
	}

	/**
	 * Clear auth.
	 *
	 * @return void
	 */
	public static function disconnect() {
		delete_option( self::AUTH_OPTION );
		Options::update(
			array(
				'gsc_property'   => '',
				'gsc_properties' => array(),
			)
		);
	}

	/**
	 * Selected property site URL (e.g. https://example.com/ or sc-domain:example.com).
	 *
	 * @return string
	 */
	public static function property() {
		return (string) Options::get( 'gsc_property', '' );
	}

	/**
	 * Flash admin notices after redirects.
	 *
	 * @return void
	 */
	public function maybe_flash_notices() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$status = isset( $_GET['seofyme_gsc'] ) ? sanitize_key( wp_unslash( $_GET['seofyme_gsc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $status ) {
			return;
		}
		$messages = array(
			'connected'    => array( __( 'Google Search Console connected.', 'seofyme-seo' ), 'success' ),
			'disconnected' => array( __( 'Search Console disconnected.', 'seofyme-seo' ), 'success' ),
			'saved'        => array( __( 'Search Console property saved.', 'seofyme-seo' ), 'success' ),
			'synced'       => array( __( 'Positions synced from Search Console.', 'seofyme-seo' ), 'success' ),
			'denied'       => array( __( 'Google authorization was cancelled.', 'seofyme-seo' ), 'error' ),
			'error'        => array( __( 'Search Console connection failed. Check your Client ID/Secret and try again.', 'seofyme-seo' ), 'error' ),
			'missing'      => array( __( 'Add a Google Client ID and Client Secret before connecting.', 'seofyme-seo' ), 'error' ),
			'noproperty'   => array( __( 'Choose a Search Console property first.', 'seofyme-seo' ), 'error' ),
			'syncfail'     => array( __( 'Could not sync positions from Search Console.', 'seofyme-seo' ), 'error' ),
		);
		if ( isset( $messages[ $status ] ) ) {
			add_settings_error( 'seofyme_messages', 'gsc_' . $status, $messages[ $status ][0], $messages[ $status ][1] );
		}
	}

	/**
	 * Start OAuth.
	 *
	 * @return void
	 */
	public function handle_connect() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'seofyme_gsc_connect' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}

		$client_id     = (string) Options::get( 'gsc_client_id', '' );
		$client_secret = (string) Options::get( 'gsc_client_secret', '' );
		$return        = isset( $_POST['return'] ) ? sanitize_text_field( wp_unslash( $_POST['return'] ) ) : 'seofyme-seo-settings';

		if ( '' === $client_id || '' === $client_secret ) {
			wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-settings&seofyme_gsc=missing' ) );
			exit;
		}

		$state = wp_generate_password( 32, false );
		set_transient(
			'seofyme_gsc_oauth_' . $state,
			array(
				'user_id' => get_current_user_id(),
				'return'  => $return,
			),
			15 * MINUTE_IN_SECONDS
		);

		$url = add_query_arg(
			array(
				'client_id'     => $client_id,
				'redirect_uri'  => self::redirect_uri(),
				'response_type' => 'code',
				'scope'         => self::SCOPE,
				'access_type'   => 'offline',
				'prompt'        => 'consent',
				'state'         => $state,
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);

		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Google OAuth endpoint.
		exit;
	}

	/**
	 * OAuth callback.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_oauth_callback( $request ) {
		$code  = (string) $request->get_param( 'code' );
		$state = (string) $request->get_param( 'state' );
		$error = (string) $request->get_param( 'error' );

		$stored = '' !== $state ? get_transient( 'seofyme_gsc_oauth_' . $state ) : false;
		if ( is_array( $stored ) ) {
			delete_transient( 'seofyme_gsc_oauth_' . $state );
		} else {
			$stored = null;
		}

		$return = is_array( $stored ) && ! empty( $stored['return'] ) ? sanitize_key( $stored['return'] ) : 'seofyme-seo-settings';
		$dest   = admin_url( 'admin.php?page=' . $return );

		if ( $error ) {
			wp_safe_redirect( $dest . '&seofyme_gsc=denied' );
			exit;
		}
		if ( ! $stored || '' === $code ) {
			wp_safe_redirect( $dest . '&seofyme_gsc=error' );
			exit;
		}

		$token = $this->exchange_code( $code );
		if ( is_wp_error( $token ) ) {
			wp_safe_redirect( $dest . '&seofyme_gsc=error' );
			exit;
		}

		$email = $this->fetch_email( (string) $token['access_token'] );
		$sites = $this->fetch_sites( (string) $token['access_token'] );

		self::save_auth(
			array(
				'access_token'  => (string) $token['access_token'],
				'refresh_token' => isset( $token['refresh_token'] ) ? (string) $token['refresh_token'] : (string) ( self::auth()['refresh_token'] ?? '' ),
				'expires_at'    => time() + (int) ( $token['expires_in'] ?? 3600 ),
				'email'         => $email,
				'connected_at'  => time(),
			)
		);

		Options::update( array( 'gsc_properties' => $sites ) );

		$current = self::property();
		if ( '' === $current && ! empty( $sites[0]['siteUrl'] ) ) {
			Options::update( array( 'gsc_property' => (string) $sites[0]['siteUrl'] ) );
		}

		wp_safe_redirect( $dest . '&seofyme_gsc=connected' );
		exit;
	}

	/**
	 * Disconnect.
	 *
	 * @return void
	 */
	public function handle_disconnect() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'seofyme_gsc_disconnect' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$return = isset( $_POST['return'] ) ? sanitize_key( wp_unslash( $_POST['return'] ) ) : 'seofyme-seo-settings';
		self::disconnect();
		wp_safe_redirect( admin_url( 'admin.php?page=' . $return . '&seofyme_gsc=disconnected' ) );
		exit;
	}

	/**
	 * Save property.
	 *
	 * @return void
	 */
	public function handle_save_property() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'seofyme_gsc_save_property' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$return   = isset( $_POST['return'] ) ? sanitize_key( wp_unslash( $_POST['return'] ) ) : 'seofyme-seo-settings';
		$property = isset( $_POST['gsc_property'] ) ? sanitize_text_field( wp_unslash( $_POST['gsc_property'] ) ) : '';
		Options::update( array( 'gsc_property' => $property ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $return . '&seofyme_gsc=saved' ) );
		exit;
	}

	/**
	 * Sync tracked keyword positions from Search Console.
	 *
	 * @return void
	 */
	public function handle_sync() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_gsc_sync' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		if ( ! self::is_connected() || '' === self::property() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-ranks&seofyme_gsc=noproperty' ) );
			exit;
		}

		$result = $this->sync_rank_positions();
		$status = is_wp_error( $result ) ? 'syncfail' : 'synced';
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-ranks&seofyme_gsc=' . $status ) );
		exit;
	}

	/**
	 * Exchange authorization code for tokens.
	 *
	 * @param string $code Code.
	 * @return array|\WP_Error
	 */
	private function exchange_code( $code ) {
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 30,
				'body'    => array(
					'code'          => $code,
					'client_id'     => (string) Options::get( 'gsc_client_id', '' ),
					'client_secret' => (string) Options::get( 'gsc_client_secret', '' ),
					'redirect_uri'  => self::redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
			return new \WP_Error( 'gsc_token', __( 'Invalid token response from Google.', 'seofyme-seo' ) );
		}
		return $body;
	}

	/**
	 * Refresh access token when needed.
	 *
	 * @return string|\WP_Error
	 */
	private function access_token() {
		$auth = self::auth();
		if ( ! empty( $auth['access_token'] ) && ! empty( $auth['expires_at'] ) && (int) $auth['expires_at'] > ( time() + 60 ) ) {
			return (string) $auth['access_token'];
		}
		if ( empty( $auth['refresh_token'] ) ) {
			return new \WP_Error( 'gsc_refresh', __( 'Search Console is not connected.', 'seofyme-seo' ) );
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 30,
				'body'    => array(
					'client_id'     => (string) Options::get( 'gsc_client_id', '' ),
					'client_secret' => (string) Options::get( 'gsc_client_secret', '' ),
					'refresh_token' => (string) $auth['refresh_token'],
					'grant_type'    => 'refresh_token',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
			return new \WP_Error( 'gsc_refresh', __( 'Could not refresh Google access token.', 'seofyme-seo' ) );
		}

		$auth['access_token'] = (string) $body['access_token'];
		$auth['expires_at']   = time() + (int) ( $body['expires_in'] ?? 3600 );
		if ( ! empty( $body['refresh_token'] ) ) {
			$auth['refresh_token'] = (string) $body['refresh_token'];
		}
		self::save_auth( $auth );
		return (string) $auth['access_token'];
	}

	/**
	 * Fetch Google account email.
	 *
	 * @param string $token Access token.
	 * @return string
	 */
	private function fetch_email( $token ) {
		$response = wp_remote_get(
			'https://www.googleapis.com/oauth2/v2/userinfo',
			array(
				'timeout' => 20,
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return '';
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $body ) && ! empty( $body['email'] ) ? sanitize_email( (string) $body['email'] ) : '';
	}

	/**
	 * List Search Console sites.
	 *
	 * @param string $token Access token.
	 * @return array
	 */
	private function fetch_sites( $token ) {
		$response = wp_remote_get(
			'https://www.googleapis.com/webmasters/v3/sites',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['siteEntry'] ) || ! is_array( $body['siteEntry'] ) ) {
			return array();
		}
		$out = array();
		foreach ( $body['siteEntry'] as $entry ) {
			if ( empty( $entry['siteUrl'] ) ) {
				continue;
			}
			$out[] = array(
				'siteUrl'     => sanitize_text_field( (string) $entry['siteUrl'] ),
				'permission'  => isset( $entry['permissionLevel'] ) ? sanitize_text_field( (string) $entry['permissionLevel'] ) : '',
			);
		}
		return $out;
	}

	/**
	 * Pull average positions for tracked keywords (last 28 days).
	 *
	 * @return true|\WP_Error
	 */
	public function sync_rank_positions() {
		$token = $this->access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$property = self::property();
		if ( '' === $property ) {
			return new \WP_Error( 'gsc_property', __( 'No Search Console property selected.', 'seofyme-seo' ) );
		}

		$tracker = new \SeofymeSEO\Modules\RankTracker\RankTracker();
		$rows    = $tracker->all();
		if ( ! $rows ) {
			return true;
		}

		$end   = gmdate( 'Y-m-d', strtotime( '-3 days' ) );
		$start = gmdate( 'Y-m-d', strtotime( '-31 days' ) );
		$url   = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode( $property ) . '/searchAnalytics/query';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'startDate'  => $start,
						'endDate'    => $end,
						'dimensions' => array( 'query' ),
						'rowLimit'   => 1000,
					)
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return new \WP_Error( 'gsc_sync', __( 'Invalid Search Console response.', 'seofyme-seo' ) );
		}

		$map = array();
		if ( ! empty( $body['rows'] ) && is_array( $body['rows'] ) ) {
			foreach ( $body['rows'] as $row ) {
				if ( empty( $row['keys'][0] ) || ! isset( $row['position'] ) ) {
					continue;
				}
				$map[ self::normalize_keyword( (string) $row['keys'][0] ) ] = (int) round( (float) $row['position'] );
			}
		}

		$changed = false;
		foreach ( $rows as $i => $row ) {
			$key = self::normalize_keyword( (string) ( $row['keyword'] ?? '' ) );
			if ( '' === $key || ! isset( $map[ $key ] ) ) {
				continue;
			}
			$pos = max( 1, min( 100, $map[ $key ] ) );
			$rows[ $i ]['position']  = $pos;
			$rows[ $i ]['history']   = isset( $rows[ $i ]['history'] ) && is_array( $rows[ $i ]['history'] ) ? $rows[ $i ]['history'] : array();
			$rows[ $i ]['history'][] = array( 'time' => time(), 'position' => $pos, 'source' => 'gsc' );
			$rows[ $i ]['history']   = array_slice( $rows[ $i ]['history'], -50 );
			$changed                 = true;
		}

		if ( $changed ) {
			update_option( \SeofymeSEO\Modules\RankTracker\RankTracker::OPTION, array_values( $rows ), false );
		}

		return true;
	}

	/**
	 * Normalize keyword for map lookups.
	 *
	 * @param string $keyword Keyword.
	 * @return string
	 */
	private static function normalize_keyword( $keyword ) {
		$keyword = trim( $keyword );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $keyword ) : strtolower( $keyword );
	}

	/**
	 * Credential fields for the Settings form (must sit inside options.php form).
	 *
	 * @return void
	 */
	public static function render_settings_fields() {
		$o        = Options::all();
		$opt      = Options::OPTION_KEY;
		$redirect = self::redirect_uri();
		?>
		<section class="sf-card" id="seofyme-gsc">
			<header class="sf-card__header">
				<h2><?php esc_html_e( 'Google Search Console', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'OAuth credentials used to connect Search Console for rank automation.', 'seofyme-seo' ); ?></p>
			</header>
			<div class="sf-card__body">
				<div class="sf-notice sf-notice--info" style="margin:0 0 16px;">
					<?php
					printf(
						/* translators: %s: OAuth redirect URI. */
						esc_html__( 'Create an OAuth client in Google Cloud Console, enable the Search Console API, and set this redirect URI: %s', 'seofyme-seo' ),
						esc_html( $redirect )
					);
					?>
				</div>
				<div class="sf-field sf-field--stack">
					<label class="sf-field__label" for="gsc_client_id"><?php esc_html_e( 'Google Client ID', 'seofyme-seo' ); ?></label>
					<input type="text" class="sf-input" name="<?php echo esc_attr( $opt ); ?>[gsc_client_id]" id="gsc_client_id" value="<?php echo esc_attr( (string) ( $o['gsc_client_id'] ?? '' ) ); ?>" autocomplete="off" />
				</div>
				<div class="sf-field sf-field--stack">
					<label class="sf-field__label" for="gsc_client_secret"><?php esc_html_e( 'Google Client Secret', 'seofyme-seo' ); ?></label>
					<input type="password" class="sf-input" name="<?php echo esc_attr( $opt ); ?>[gsc_client_secret]" id="gsc_client_secret" value="<?php echo esc_attr( (string) ( $o['gsc_client_secret'] ?? '' ) ); ?>" autocomplete="new-password" />
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Connection card for Settings / Rank tracker (standalone forms — not nested).
	 *
	 * @param string $return_page Admin page slug to return to.
	 * @return void
	 */
	public static function render_card( $return_page = 'seofyme-seo-settings' ) {
		$o          = Options::all();
		$connected  = self::is_connected();
		$auth       = self::auth();
		$properties = isset( $o['gsc_properties'] ) && is_array( $o['gsc_properties'] ) ? $o['gsc_properties'] : array();
		$property   = (string) ( $o['gsc_property'] ?? '' );
		$has_creds  = '' !== (string) ( $o['gsc_client_id'] ?? '' ) && '' !== (string) ( $o['gsc_client_secret'] ?? '' );
		?>
		<section class="sf-card">
			<header class="sf-card__header">
				<h2><?php esc_html_e( 'Connect Search Console', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'Authorize Google, pick a property, and sync keyword positions into the rank tracker.', 'seofyme-seo' ); ?></p>
			</header>
			<div class="sf-card__body">
				<div class="sf-field">
					<div class="sf-field__text">
						<span class="sf-field__label"><?php esc_html_e( 'Status', 'seofyme-seo' ); ?></span>
					</div>
					<div class="sf-field__control">
						<span class="sf-badge <?php echo esc_attr( $connected ? 'sf-badge--good' : 'sf-badge--muted' ); ?>">
							<?php echo $connected ? esc_html__( 'Connected', 'seofyme-seo' ) : esc_html__( 'Not connected', 'seofyme-seo' ); ?>
						</span>
						<?php if ( $connected && ! empty( $auth['email'] ) ) : ?>
							<span class="sf-field__desc" style="display:inline;margin-left:8px;"><?php echo esc_html( (string) $auth['email'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( ! $connected && ! $has_creds ) : ?>
					<div class="sf-notice sf-notice--warn" style="margin:0 0 16px;">
						<?php esc_html_e( 'Save a Google Client ID and Client Secret in Settings before connecting.', 'seofyme-seo' ); ?>
						<?php if ( 'seofyme-seo-settings' !== $return_page ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=seofyme-seo-settings#seofyme-gsc' ) ); ?>"><?php esc_html_e( 'Open Settings', 'seofyme-seo' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $connected ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="seofyme_gsc_save_property" />
						<input type="hidden" name="return" value="<?php echo esc_attr( $return_page ); ?>" />
						<?php wp_nonce_field( 'seofyme_gsc_save_property' ); ?>
						<div class="sf-field">
							<div class="sf-field__text">
								<label class="sf-field__label" for="sf-gsc-property-<?php echo esc_attr( $return_page ); ?>"><?php esc_html_e( 'Property', 'seofyme-seo' ); ?></label>
							</div>
							<div class="sf-field__control">
								<?php if ( $properties ) : ?>
									<select class="sf-select" name="gsc_property" id="sf-gsc-property-<?php echo esc_attr( $return_page ); ?>">
										<?php foreach ( $properties as $site ) : ?>
											<?php $url = isset( $site['siteUrl'] ) ? (string) $site['siteUrl'] : ''; ?>
											<option value="<?php echo esc_attr( $url ); ?>" <?php selected( $property, $url ); ?>><?php echo esc_html( $url ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php else : ?>
									<input type="text" class="sf-input" name="gsc_property" id="sf-gsc-property-<?php echo esc_attr( $return_page ); ?>" value="<?php echo esc_attr( $property ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
								<?php endif; ?>
							</div>
						</div>
						<div class="sf-savebar" style="margin-top:8px;">
							<button type="submit" class="sf-btn sf-btn--secondary"><?php esc_html_e( 'Save property', 'seofyme-seo' ); ?></button>
						</div>
					</form>
				<?php endif; ?>
			</div>
			<div class="sf-savebar">
				<?php if ( $connected ) : ?>
					<?php if ( 'seofyme-seo-ranks' === $return_page && '' !== $property ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
							<input type="hidden" name="action" value="seofyme_gsc_sync" />
							<?php wp_nonce_field( 'seofyme_gsc_sync' ); ?>
							<button type="submit" class="sf-btn sf-btn--primary"><?php esc_html_e( 'Sync positions', 'seofyme-seo' ); ?></button>
						</form>
					<?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<input type="hidden" name="action" value="seofyme_gsc_disconnect" />
						<input type="hidden" name="return" value="<?php echo esc_attr( $return_page ); ?>" />
						<?php wp_nonce_field( 'seofyme_gsc_disconnect' ); ?>
						<button type="submit" class="sf-btn sf-btn--danger"><?php esc_html_e( 'Disconnect', 'seofyme-seo' ); ?></button>
					</form>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="seofyme_gsc_connect" />
						<input type="hidden" name="return" value="<?php echo esc_attr( $return_page ); ?>" />
						<?php wp_nonce_field( 'seofyme_gsc_connect' ); ?>
						<button type="submit" class="sf-btn sf-btn--primary" <?php disabled( ! $has_creds ); ?>><?php esc_html_e( 'Connect Search Console', 'seofyme-seo' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
