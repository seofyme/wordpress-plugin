<?php
/**
 * WooCommerce SEO enhancements.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\WooCommerce;

use SeofymeSEO\Schema\Json_Ld;
use SeofymeSEO\Support\Options;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product schema + cart/checkout noindex helpers.
 */
class WooCommerceSEO {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'wp_head', array( $this, 'product_schema' ), 8 );
		add_action( 'wp_head', array( $this, 'noindex_cart' ), 1 );
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'save_post_product', array( $this, 'save' ) );
	}

	/**
	 * Product meta box extras.
	 *
	 * @return void
	 */
	public function box() {
		add_meta_box( 'seofyme_woo', __( 'Seofyme WooCommerce SEO', 'seofyme-seo' ), array( $this, 'render' ), 'product', 'side' );
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_woo', 'seofyme_woo_nonce' );
		$gtin = get_post_meta( $post->ID, '_seofyme_gtin', true );
		$brand = get_post_meta( $post->ID, '_seofyme_brand', true );
		printf( '<p><label>%s<br><input type="text" class="widefat" name="seofyme_brand" value="%s" /></label></p>', esc_html__( 'Brand', 'seofyme-seo' ), esc_attr( $brand ) );
		printf( '<p><label>%s<br><input type="text" class="widefat" name="seofyme_gtin" value="%s" /></label></p>', esc_html__( 'GTIN / barcode', 'seofyme-seo' ), esc_attr( $gtin ) );
	}

	/**
	 * Save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['seofyme_woo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_woo_nonce'] ) ), 'seofyme_woo' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_seofyme_brand', isset( $_POST['seofyme_brand'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_brand'] ) ) : '' );
		update_post_meta( $post_id, '_seofyme_gtin', isset( $_POST['seofyme_gtin'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_gtin'] ) ) : '' );
	}

	/**
	 * Noindex cart/checkout/account.
	 *
	 * @return void
	 */
	public function noindex_cart() {
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			echo '<meta name="robots" content="noindex,follow" />' . "\n";
		}
	}

	/**
	 * Product JSON-LD.
	 *
	 * @return void
	 */
	public function product_schema() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product ) {
			return;
		}
		$id   = $product->get_id();
		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => Post_Meta::resolved_title( $id ),
			'description' => Post_Meta::resolved_description( $id ),
			'sku'         => $product->get_sku(),
			'image'       => wp_get_attachment_url( $product->get_image_id() ),
			'brand'       => array( '@type' => 'Brand', 'name' => get_post_meta( $id, '_seofyme_brand', true ) ?: get_bloginfo( 'name' ) ),
			'offers'      => array(
				'@type'         => 'Offer',
				'url'           => get_permalink( $id ),
				'priceCurrency' => get_woocommerce_currency(),
				'price'         => $product->get_price(),
				'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			),
		);
		$gtin = get_post_meta( $id, '_seofyme_gtin', true );
		if ( $gtin ) {
			$data['gtin'] = $gtin;
		}
		if ( $product->get_average_rating() ) {
			$data['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => max( 1, (int) $product->get_review_count() ),
			);
		}
		Json_Ld::print_script( $data );
	}

}
