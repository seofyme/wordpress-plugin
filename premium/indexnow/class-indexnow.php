<?php
/**
 * IndexNow integration for fast indexing pings.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pings IndexNow on publish/update.
 */
class Seofyme_IndexNow {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'transition_post_status', [ $this, 'on_transition' ], 10, 3 );
		add_action( 'init', [ $this, 'serve_key_file' ] );
	}

	/**
	 * Ensure key exists.
	 *
	 * @return string
	 */
	public function get_key() {
		$key = get_option( 'seofyme_indexnow_key', '' );
		if ( ! $key ) {
			$key = wp_generate_password( 32, false, false );
			update_option( 'seofyme_indexnow_key', $key, false );
		}
		return $key;
	}

	/**
	 * Serve key verification file at /{key}.txt
	 *
	 * @return void
	 */
	public function serve_key_file() {
		$key  = $this->get_key();
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		if ( $path === '/' . $key . '.txt' ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html( $key );
			exit;
		}
	}

	/**
	 * On publish.
	 *
	 * @param string  $new New status.
	 * @param string  $old Old status.
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function on_transition( $new, $old, $post ) {
		if ( $new !== 'publish' || ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}
		$url = get_permalink( $post );
		if ( $url ) {
			$this->submit( [ $url ] );
		}
	}

	/**
	 * Submit URLs to IndexNow.
	 *
	 * @param array $urls URLs.
	 * @return bool
	 */
	public function submit( array $urls ) {
		$key    = $this->get_key();
		$host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$payload = [
			'host'        => $host,
			'key'         => $key,
			'keyLocation' => home_url( '/' . $key . '.txt' ),
			'urlList'     => array_values( $urls ),
		];

		$response = wp_remote_post(
			'https://api.indexnow.org/indexnow',
			[
				'timeout' => 8,
				'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
				'body'    => wp_json_encode( $payload ),
			]
		);

		return ! is_wp_error( $response );
	}
}
