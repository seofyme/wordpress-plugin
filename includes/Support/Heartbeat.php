<?php
/**
 * Connected-install heartbeat to CacheRocket.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports this site as a connected Seofyme install so it shows up in the
 * CacheRocket admin (WordPress installs) and can be probed via the public
 * REST ping. Only runs when Cloud API keys are present (connecting keys is
 * the opt-in).
 */
class Heartbeat {

	public const TRANSIENT_KEY = 'seofyme_heartbeat_sent';
	public const LAST_OPTION = 'seofyme_last_heartbeat';

	/** Shared WordPress install lifecycle API (heartbeat / disconnect). */
	public const API_BASE = 'https://api.cacherocket.com/web/v1/wordpress';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'maybe_send' ), 40 );
	}

	/**
	 * WordPress install lifecycle API base (filterable for staging only).
	 *
	 * @return string
	 */
	public static function api_base() {
		/**
		 * Filter the WordPress install lifecycle API base (heartbeat / disconnect).
		 *
		 * @param string $base Default production wordpress gateway path.
		 */
		$base = apply_filters( 'seofyme_wordpress_api_base', self::API_BASE );
		$base = is_string( $base ) ? untrailingslashit( $base ) : self::API_BASE;
		return $base ? $base : self::API_BASE;
	}

	/**
	 * Throttled heartbeat on admin page loads.
	 *
	 * @return void
	 */
	public function maybe_send() {
		self::send( false );
	}

	/**
	 * Send a heartbeat.
	 *
	 * @param bool $force Bypass the local throttle.
	 * @return bool True when sent or skipped by throttle.
	 */
	public static function send( $force = false ) {
		if ( ! Cloud_Account::is_connected() ) {
			return false;
		}

		if ( ! $force && get_transient( self::TRANSIENT_KEY ) ) {
			return true;
		}

		$site_url = home_url( '/' );
		$host     = wp_parse_url( $site_url, PHP_URL_HOST );
		$domain   = is_string( $host ) ? strtolower( $host ) : '';
		if ( '' === $domain ) {
			return false;
		}

		global $wp_version;

		$response = wp_remote_post(
			self::api_base() . '/pluginHeartbeat',
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
					'User-Agent'   => 'Seofyme-SEO-WordPress/' . SEOFYME_SEO_VERSION,
					'X-Product'    => 'seofyme',
				),
				'body'    => wp_json_encode(
					array(
						'publicKey'     => (string) Options::get( 'seofyme_public_key', '' ),
						'secretKey'     => (string) Options::get( 'seofyme_secret_key', '' ),
						'siteUrl'       => $site_url,
						'domain'        => $domain,
						'pluginVersion' => defined( 'SEOFYME_SEO_VERSION' ) ? SEOFYME_SEO_VERSION : '0',
						'wpVersion'     => isset( $wp_version ) ? (string) $wp_version : '',
						'phpVersion'    => PHP_VERSION,
						'product'       => 'seofyme',
					)
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		set_transient( self::TRANSIENT_KEY, 1, 12 * HOUR_IN_SECONDS );
		update_option(
			self::LAST_OPTION,
			array(
				'sentAt'  => gmdate( 'c' ),
				'siteUrl' => $site_url,
				'domain'  => $domain,
			),
			false
		);

		return true;
	}
}
