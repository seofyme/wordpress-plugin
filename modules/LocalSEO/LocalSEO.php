<?php
/**
 * Local SEO locations + schema.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\LocalSEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location CPT, fields, schema, store locator.
 */
class LocalSEO {

	public const CPT = 'seofyme_location';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'save_post_' . self::CPT, array( $this, 'save' ) );
		add_action( 'wp_head', array( $this, 'schema' ), 6 );
		add_shortcode( 'seofyme_store_locator', array( $this, 'shortcode' ) );
	}

	/**
	 * CPT.
	 *
	 * @return void
	 */
	public function cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Locations', 'seofyme-seo' ),
					'singular_name' => __( 'Location', 'seofyme-seo' ),
				),
				'public'       => true,
				'show_in_menu' => 'seofyme-seo',
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'locations' ),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Box.
	 *
	 * @return void
	 */
	public function box() {
		add_meta_box( 'seofyme_local', __( 'Local business details', 'seofyme-seo' ), array( $this, 'render' ), self::CPT, 'normal', 'high' );
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_local', 'seofyme_local_nonce' );
		$keys = array( 'business_name', 'phone', 'email', 'street', 'city', 'region', 'postal', 'country', 'lat', 'lng', 'opening_hours', 'maps_embed' );
		foreach ( $keys as $key ) {
			$val = get_post_meta( $post->ID, '_seofyme_local_' . $key, true );
			printf( '<p><label><strong>%1$s</strong><br><input type="text" class="widefat" name="seofyme_local[%2$s]" value="%3$s" /></label></p>', esc_html( ucwords( str_replace( '_', ' ', $key ) ) ), esc_attr( $key ), esc_attr( (string) $val ) );
		}
	}

	/**
	 * Save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['seofyme_local_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_local_nonce'] ) ), 'seofyme_local' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$data = isset( $_POST['seofyme_local'] ) && is_array( $_POST['seofyme_local'] ) ? wp_unslash( $_POST['seofyme_local'] ) : array(); // phpcs:ignore
		foreach ( $data as $key => $value ) {
			update_post_meta( $post_id, '_seofyme_local_' . sanitize_key( $key ), sanitize_text_field( $value ) );
		}
	}

	/**
	 * Schema.
	 *
	 * @return void
	 */
	public function schema() {
		if ( ! is_singular( self::CPT ) ) {
			return;
		}
		$id  = get_queried_object_id();
		$get = static function ( $k ) use ( $id ) {
			return get_post_meta( $id, '_seofyme_local_' . $k, true );
		};
		$data = array(
			'@context'  => 'https://schema.org',
			'@type'     => 'LocalBusiness',
			'name'      => $get( 'business_name' ) ?: get_the_title( $id ),
			'telephone' => $get( 'phone' ),
			'email'     => $get( 'email' ),
			'url'       => get_permalink( $id ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $get( 'street' ),
				'addressLocality' => $get( 'city' ),
				'addressRegion'   => $get( 'region' ),
				'postalCode'      => $get( 'postal' ),
				'addressCountry'  => $get( 'country' ),
			),
		);
		if ( $get( 'lat' ) && $get( 'lng' ) ) {
			$data['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => $get( 'lat' ), 'longitude' => $get( 'lng' ) );
		}
		if ( $get( 'opening_hours' ) ) {
			$data['openingHours'] = $get( 'opening_hours' );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * Shortcode.
	 *
	 * @return string
	 */
	public function shortcode() {
		$locations = get_posts( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'posts_per_page' => 100, 'no_found_rows' => true ) );
		if ( ! $locations ) {
			return '<p>' . esc_html__( 'No locations yet.', 'seofyme-seo' ) . '</p>';
		}
		$html = '<div class="seofyme-store-locator"><ul>';
		foreach ( $locations as $loc ) {
			$html .= '<li><a href="' . esc_url( get_permalink( $loc ) ) . '"><strong>' . esc_html( get_the_title( $loc ) ) . '</strong></a>';
			$city  = get_post_meta( $loc->ID, '_seofyme_local_city', true );
			if ( $city ) {
				$html .= ' — ' . esc_html( $city );
			}
			$html .= '</li>';
		}
		$html .= '</ul></div>';
		return $html;
	}
}
