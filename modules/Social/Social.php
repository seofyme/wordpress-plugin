<?php
/**
 * Social sharing meta + preview fields.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Social;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Open Graph / Twitter tags.
 */
class Social {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'wp_head', array( $this, 'output' ), 2 );
	}

	/**
	 * Box.
	 *
	 * @return void
	 */
	public function box() {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			add_meta_box( 'seofyme_social', __( 'Social preview', 'seofyme-seo' ), array( $this, 'render' ), $type, 'normal' );
		}
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_social', 'seofyme_social_nonce' );
		$fields = array(
			Post_Meta::OG_TITLE => __( 'Facebook title', 'seofyme-seo' ),
			Post_Meta::OG_DESC  => __( 'Facebook description', 'seofyme-seo' ),
			Post_Meta::OG_IMAGE => __( 'Facebook image URL', 'seofyme-seo' ),
			Post_Meta::TW_TITLE => __( 'X title', 'seofyme-seo' ),
			Post_Meta::TW_DESC  => __( 'X description', 'seofyme-seo' ),
			Post_Meta::TW_IMAGE => __( 'X image URL', 'seofyme-seo' ),
		);
		echo '<div class="seofyme-social-grid">';
		foreach ( $fields as $key => $label ) {
			$val  = Post_Meta::get( $post->ID, $key );
			$name = 'seofyme_social[' . $key . ']';
			if ( false !== strpos( $key, 'description' ) ) {
				printf(
					'<p><label>%1$s<br><textarea class="widefat" rows="2" name="%2$s">%3$s</textarea></label></p>',
					esc_html( $label ),
					esc_attr( $name ),
					esc_textarea( $val )
				);
			} else {
				printf(
					'<p><label>%1$s<br><input type="text" class="widefat" name="%2$s" value="%3$s" /></label></p>',
					esc_html( $label ),
					esc_attr( $name ),
					esc_attr( $val )
				);
			}
		}
		echo '</div>';
	}

	/**
	 * Save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['seofyme_social_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_social_nonce'] ) ), 'seofyme_social' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$data = isset( $_POST['seofyme_social'] ) && is_array( $_POST['seofyme_social'] ) ? wp_unslash( $_POST['seofyme_social'] ) : array(); // phpcs:ignore
		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );
			if ( 0 !== strpos( $key, '_seofyme_' ) ) {
				continue;
			}
			if ( false !== strpos( $key, 'image' ) ) {
				Post_Meta::set( $post_id, $key, esc_url_raw( $value ) );
			} elseif ( false !== strpos( $key, 'description' ) ) {
				Post_Meta::set( $post_id, $key, sanitize_textarea_field( $value ) );
			} else {
				Post_Meta::set( $post_id, $key, sanitize_text_field( $value ) );
			}
		}
	}

	/**
	 * Output tags.
	 *
	 * @return void
	 */
	public function output() {
		if ( ! is_singular() ) {
			return;
		}
		$id    = get_queried_object_id();
		$title = Post_Meta::get( $id, Post_Meta::OG_TITLE ) ?: Post_Meta::resolved_title( $id );
		$desc  = Post_Meta::get( $id, Post_Meta::OG_DESC ) ?: Post_Meta::resolved_description( $id );
		$image = Post_Meta::get( $id, Post_Meta::OG_IMAGE ) ?: get_the_post_thumbnail_url( $id, 'large' );

		echo '<meta property="og:type" content="article" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_permalink( $id ) ) . '" />' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		}

		$xt = Post_Meta::get( $id, Post_Meta::TW_TITLE ) ?: $title;
		$xd = Post_Meta::get( $id, Post_Meta::TW_DESC ) ?: $desc;
		$xi = Post_Meta::get( $id, Post_Meta::TW_IMAGE ) ?: $image;
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $xt ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $xd ) . '" />' . "\n";
		if ( $xi ) {
			echo '<meta name="twitter:image" content="' . esc_url( $xi ) . '" />' . "\n";
		}
	}
}
