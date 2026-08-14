<?php
/**
 * Public REST ping so CacheRocket can verify the plugin is still installed.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register CacheRocket-compatible install probe routes.
 */
class Rest {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'cacherocket/v1',
			'/ping',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'ping' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Lightweight install probe response.
	 *
	 * Probe clients expect ok + service === "cacherocket" at this path.
	 *
	 * @return \WP_REST_Response
	 */
	public function ping() {
		$host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$domain = is_string( $host ) ? strtolower( $host ) : '';

		return new \WP_REST_Response(
			array(
				'ok'        => true,
				'service'   => 'cacherocket',
				'plugin'    => 'seofyme-seo',
				'product'   => 'seofyme',
				'version'   => defined( 'SEOFYME_SEO_VERSION' ) ? SEOFYME_SEO_VERSION : '0',
				'domain'    => $domain,
				'siteUrl'   => home_url( '/' ),
				'connected' => Cloud_Account::is_connected(),
			),
			200
		);
	}
}
