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
 * Submits URLs to IndexNow when the administrator has opted in.
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
	 * Whether IndexNow submissions are enabled.
	 *
	 * Off by default until an administrator opts in under Settings.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) Options::get( 'indexnow_enabled', false );
	}

	/**
	 * Key.
	 *
	 * A key is generated only after IndexNow has been enabled.
	 *
	 * @return string
	 */
	public function key() {
		$key = Options::get( 'indexnow_key', '' );
		if ( $key ) {
			return (string) $key;
		}
		if ( ! $this->is_enabled() ) {
			return '';
		}
		$key = wp_generate_password( 32, false, false );
		Options::update( array( 'indexnow_key' => $key ) );
		return $key;
	}

	/**
	 * Key file.
	 *
	 * @return void
	 */
	public function serve_key() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		$key = $this->key();
		if ( '' === $key ) {
			return;
		}
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
		if ( ! $this->is_enabled() ) {
			return;
		}
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
		if ( ! $this->is_enabled() ) {
			return false;
		}
		$key = $this->key();
		if ( '' === $key ) {
			return false;
		}
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
