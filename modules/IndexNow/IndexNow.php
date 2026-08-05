<?php
/**
 * IndexNow pings on publish.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\IndexNow;

use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submits URLs to IndexNow.
 */
class IndexNow {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 10, 3 );
		add_action( 'init', array( $this, 'serve_key' ) );
	}

	/**
	 * Key.
	 *
	 * @return string
	 */
	public function key() {
		$key = Options::get( 'indexnow_key', '' );
		if ( ! $key ) {
			$key = wp_generate_password( 32, false, false );
			Options::update( array( 'indexnow_key' => $key ) );
		}
		return $key;
	}

	/**
	 * Key file.
	 *
	 * @return void
	 */
	public function serve_key() {
		$key  = $this->key();
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
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
	 * @param string   $new New.
	 * @param string   $old Old.
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function on_transition( $new, $old, $post ) {
		if ( 'publish' !== $new || ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}
		$url = get_permalink( $post );
		if ( $url ) {
			$this->submit( array( $url ) );
		}
	}

	/**
	 * Submit.
	 *
	 * @param array $urls URLs.
	 * @return bool
	 */
	public function submit( array $urls ) {
		$key = $this->key();
		$res = wp_remote_post(
			'https://api.indexnow.org/indexnow',
			array(
				'timeout' => 8,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode(
					array(
						'host'        => wp_parse_url( home_url(), PHP_URL_HOST ),
						'key'         => $key,
						'keyLocation' => home_url( '/' . $key . '.txt' ),
						'urlList'     => array_values( $urls ),
					)
				),
			)
		);
		return ! is_wp_error( $res );
	}
}
