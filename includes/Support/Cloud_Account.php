<?php
/**
 * Seofyme Cloud account, plan, and usage status.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches and caches the connected Seofyme Cloud subscription.
 */
class Cloud_Account {

	public const TRANSIENT_KEY = 'seofyme_cloud_account_status';
	public const LAST_GOOD_KEY = 'seofyme_cloud_account_last_good';
	public const LAST_ERROR_KEY = 'seofyme_cloud_account_error';
	public const TRANSIENT_TTL = HOUR_IN_SECONDS;

	/**
	 * Default status for a disconnected account.
	 *
	 * @return array
	 */
	public static function default_status() {
		return array(
			'planName'     => 'Free',
			'planSlug'     => 'free',
			'entitlements' => array(
				'allowSeofymeAi'          => false,
				'maxSeofymeAiMonth'       => 0,
				'maxSeofymeAiTokensMonth' => 0,
			),
			'usage'        => array(
				'period'  => '',
				'requests' => array(
					'used'      => 0,
					'limit'     => 0,
					'remaining' => 0,
				),
				'tokens' => array(
					'used'      => 0,
					'limit'     => 0,
					'remaining' => 0,
				),
			),
		);
	}

	/**
	 * Whether both Cloud credentials are configured.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		return '' !== (string) Options::get( 'seofyme_public_key', '' )
			&& '' !== (string) Options::get( 'seofyme_secret_key', '' );
	}

	/**
	 * Get cached status, fetching it when needed.
	 *
	 * @param bool $force Force an API refresh.
	 * @return array
	 */
	public static function get_status( $force = false ) {
		if ( ! self::is_connected() ) {
			return self::default_status();
		}

		if ( ! $force ) {
			$cached = get_transient( self::TRANSIENT_KEY );
			if ( is_array( $cached ) && isset( $cached['planName'] ) ) {
				return $cached;
			}
		}

		return self::sync();
	}

	/**
	 * Refresh plan and usage from Seofyme Cloud.
	 *
	 * @return array
	 */
	public static function sync() {
		if ( ! self::is_connected() ) {
			delete_option( self::LAST_ERROR_KEY );
			return self::default_status();
		}

		$response = wp_remote_post(
			Options::cloud_api_base() . '/usage',
			array(
				'timeout' => 45,
				'headers' => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
					'User-Agent'   => 'Seofyme-SEO-WordPress/' . SEOFYME_SEO_VERSION,
					'X-Product'    => 'seofyme',
					'X-Public-Key' => (string) Options::get( 'seofyme_public_key', '' ),
					'X-Secret-Key' => (string) Options::get( 'seofyme_secret_key', '' ),
				),
				'body'    => '{}',
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::handle_error( $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return self::handle_error( __( 'Invalid response from Seofyme Cloud.', 'seofyme-seo' ) );
		}

		if ( $code < 200 || $code >= 300 || ( isset( $body['success'] ) && false === $body['success'] ) ) {
			$message = ! empty( $body['message'] )
				? sanitize_text_field( (string) $body['message'] )
				: sprintf(
					/* translators: %d: HTTP status code. */
					__( 'Seofyme Cloud returned HTTP %d.', 'seofyme-seo' ),
					$code
				);
			return self::handle_error( $message );
		}

		$data = isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : $body;
		if ( empty( $data['planName'] ) ) {
			return self::handle_error( __( 'Seofyme Cloud returned an invalid plan.', 'seofyme-seo' ) );
		}

		$status = array(
			'planName'     => sanitize_text_field( (string) $data['planName'] ),
			'planSlug'     => isset( $data['planSlug'] ) ? sanitize_key( (string) $data['planSlug'] ) : '',
			'entitlements' => isset( $data['entitlements'] ) && is_array( $data['entitlements'] ) ? $data['entitlements'] : array(),
			'usage'        => isset( $data['usage'] ) && is_array( $data['usage'] ) ? $data['usage'] : array(),
		);

		delete_option( self::LAST_ERROR_KEY );
		set_transient( self::TRANSIENT_KEY, $status, self::TRANSIENT_TTL );
		update_option( self::LAST_GOOD_KEY, $status, false );

		return $status;
	}

	/**
	 * Return a last known good status when a refresh fails.
	 *
	 * @param string $message Error message.
	 * @return array
	 */
	private static function handle_error( $message ) {
		update_option( self::LAST_ERROR_KEY, sanitize_text_field( (string) $message ), false );
		$last = get_option( self::LAST_GOOD_KEY, null );
		if ( is_array( $last ) && isset( $last['planName'] ) ) {
			set_transient( self::TRANSIENT_KEY, $last, self::TRANSIENT_TTL );
			return $last;
		}
		return self::default_status();
	}

	/**
	 * Last refresh error.
	 *
	 * @return string
	 */
	public static function get_last_error() {
		$error = get_option( self::LAST_ERROR_KEY, '' );
		return is_string( $error ) ? $error : '';
	}

	/**
	 * Clear cached status.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( self::TRANSIENT_KEY );
	}
}
