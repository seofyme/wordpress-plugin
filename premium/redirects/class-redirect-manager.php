<?php
/**
 * Front-end redirect execution.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies stored redirects early in the request.
 */
class Seofyme_Redirect_Manager {

	/**
	 * Repository.
	 *
	 * @var Seofyme_Redirect_Repository
	 */
	private $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new Seofyme_Redirect_Repository();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', [ $this, 'maybe_redirect' ], 1 );
	}

	/**
	 * Execute matching redirect.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		if ( ! is_string( $path ) || $path === '' ) {
			$path = '/';
		}

		$redirect = $this->repo->find_by_origin( $path );
		if ( ! $redirect ) {
			// Also try with trailing slash variant.
			$alt = trailingslashit( $path );
			if ( $alt !== $path ) {
				$redirect = $this->repo->find_by_origin( $alt );
			}
		}

		if ( ! $redirect ) {
			return;
		}

		$type = (int) $redirect['type'];
		if ( in_array( $type, [ 410, 451 ], true ) ) {
			status_header( $type );
			nocache_headers();
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die( esc_html__( 'This content is no longer available.', 'seofyme-seo' ), '', [ 'response' => $type ] );
		}

		$target = $redirect['target'];
		if ( strpos( $target, 'http' ) !== 0 ) {
			$target = home_url( $target );
		}

		wp_redirect( $target, $type ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}
}
