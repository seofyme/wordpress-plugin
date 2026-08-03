<?php
/**
 * Local SEO — locations, hours, store locator schema.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local business CPT + schema.
 */
class Seofyme_Local_SEO {

	public const CPT = 'seofyme_location';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes' ] );
		add_action( 'save_post_' . self::CPT, [ $this, 'save' ] );
		add_action( 'wp_head', [ $this, 'output_schema' ], 5 );
		add_shortcode( 'seofyme_store_locator', [ $this, 'store_locator_shortcode' ] );
	}

	/**
	 * CPT.
	 *
	 * @return void
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			[
				'labels'       => [
					'name'          => __( 'Locations', 'seofyme-seo' ),
					'singular_name' => __( 'Location', 'seofyme-seo' ),
					'add_new_item'  => __( 'Add location', 'seofyme-seo' ),
				],
				'public'       => true,
				'show_in_menu' => 'wpseo_dashboard',
				'menu_icon'    => 'dashicons-location',
				'supports'     => [ 'title', 'editor', 'thumbnail' ],
				'has_archive'  => true,
				'rewrite'      => [ 'slug' => 'locations' ],
				'show_in_rest' => true,
			]
		);
	}

	/**
	 * Meta boxes.
	 *
	 * @return void
	 */
	public function meta_boxes() {
		add_meta_box( 'seofyme_local', __( 'Local business details', 'seofyme-seo' ), [ $this, 'render' ], self::CPT, 'normal', 'high' );
	}

	/**
	 * Render fields.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_local', 'seofyme_local_nonce' );
		$fields = [
			'business_name' => 'Business name',
			'phone'         => 'Phone',
			'email'         => 'Email',
			'street'        => 'Street',
			'city'          => 'City',
			'region'        => 'Region / state',
			'postal'        => 'Postal code',
			'country'       => 'Country',
			'lat'           => 'Latitude',
			'lng'           => 'Longitude',
			'opening_hours' => 'Opening hours (e.g. Mo-Fr 09:00-17:00)',
			'maps_embed'    => 'Google Maps embed URL',
		];
		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, '_seofyme_local_' . $key, true );
			printf(
				'<p><label><strong>%1$s</strong><br><input type="text" class="widefat" name="seofyme_local[%2$s]" value="%3$s" /></label></p>',
				esc_html__( $label, 'seofyme-seo' ),
				esc_attr( $key ),
				esc_attr( (string) $value )
			);
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
		$data = isset( $_POST['seofyme_local'] ) && is_array( $_POST['seofyme_local'] ) ? wp_unslash( $_POST['seofyme_local'] ) : []; // phpcs:ignore
		foreach ( $data as $key => $value ) {
			update_post_meta( $post_id, '_seofyme_local_' . sanitize_key( $key ), sanitize_text_field( $value ) );
		}
	}

	/**
	 * Schema on location pages.
	 *
	 * @return void
	 */
	public function output_schema() {
		if ( ! is_singular( self::CPT ) ) {
			return;
		}
		$id   = get_queried_object_id();
		$get  = static function ( $key ) use ( $id ) {
			return get_post_meta( $id, '_seofyme_local_' . $key, true );
		};
		$data = [
			'@context'    => 'https://schema.org',
			'@type'       => 'LocalBusiness',
			'name'        => $get( 'business_name' ) ?: get_the_title( $id ),
			'telephone'   => $get( 'phone' ),
			'email'       => $get( 'email' ),
			'url'         => get_permalink( $id ),
			'address'     => [
				'@type'           => 'PostalAddress',
				'streetAddress'   => $get( 'street' ),
				'addressLocality' => $get( 'city' ),
				'addressRegion'   => $get( 'region' ),
				'postalCode'      => $get( 'postal' ),
				'addressCountry'  => $get( 'country' ),
			],
		];
		if ( $get( 'lat' ) && $get( 'lng' ) ) {
			$data['geo'] = [
				'@type'     => 'GeoCoordinates',
				'latitude'  => $get( 'lat' ),
				'longitude' => $get( 'lng' ),
			];
		}
		if ( $get( 'opening_hours' ) ) {
			$data['openingHours'] = $get( 'opening_hours' );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * Store locator shortcode.
	 *
	 * @return string
	 */
	public function store_locator_shortcode() {
		$locations = get_posts(
			[
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
			]
		);
		if ( empty( $locations ) ) {
			return '<p>' . esc_html__( 'No locations yet.', 'seofyme-seo' ) . '</p>';
		}
		$html = '<div class="seofyme-store-locator"><ul>';
		foreach ( $locations as $loc ) {
			$city  = get_post_meta( $loc->ID, '_seofyme_local_city', true );
			$phone = get_post_meta( $loc->ID, '_seofyme_local_phone', true );
			$html .= '<li><a href="' . esc_url( get_permalink( $loc ) ) . '"><strong>' . esc_html( get_the_title( $loc ) ) . '</strong></a>';
			if ( $city ) {
				$html .= ' — ' . esc_html( $city );
			}
			if ( $phone ) {
				$html .= ' (' . esc_html( $phone ) . ')';
			}
			$embed = get_post_meta( $loc->ID, '_seofyme_local_maps_embed', true );
			if ( $embed ) {
				$html .= '<div class="seofyme-map"><iframe src="' . esc_url( $embed ) . '" width="100%" height="240" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>';
			}
			$html .= '</li>';
		}
		$html .= '</ul></div>';
		return $html;
	}
}
