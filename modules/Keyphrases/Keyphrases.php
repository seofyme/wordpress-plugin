<?php
/**
 * Additional keyphrases (up to 5).
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Keyphrases;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Related keyphrase metabox.
 */
class Keyphrases {

	public const MAX = 5;

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	/**
	 * Metabox.
	 *
	 * @return void
	 */
	public function box() {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			add_meta_box( 'seofyme_keyphrases', __( 'Related keyphrases', 'seofyme-seo' ), array( $this, 'render' ), $type, 'side', 'default' );
		}
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_keyphrases', 'seofyme_keyphrases_nonce' );
		$items = Post_Meta::get( $post->ID, Post_Meta::KEYPHRASES, array() );
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		while ( count( $items ) < self::MAX ) {
			$items[] = array( 'keyphrase' => '', 'synonyms' => '' );
		}
		echo '<p class="description">' . esc_html__( 'Optimize for up to 5 related keyphrases. Synonyms and word forms count toward a full SEO check for each.', 'seofyme-seo' ) . '</p>';
		foreach ( $items as $i => $item ) {
			printf(
				'<p><input type="text" class="widefat" name="seofyme_kp[%d][keyphrase]" placeholder="%s" value="%s" /></p>',
				(int) $i,
				esc_attr__( 'Keyphrase', 'seofyme-seo' ),
				esc_attr( $item['keyphrase'] ?? '' )
			);
			printf(
				'<p><input type="text" class="widefat" name="seofyme_kp[%d][synonyms]" placeholder="%s" value="%s" /></p>',
				(int) $i,
				esc_attr__( 'Synonyms, comma-separated', 'seofyme-seo' ),
				esc_attr( $item['synonyms'] ?? '' )
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
		if ( ! isset( $_POST['seofyme_keyphrases_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_keyphrases_nonce'] ) ), 'seofyme_keyphrases' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$raw   = isset( $_POST['seofyme_kp'] ) && is_array( $_POST['seofyme_kp'] ) ? wp_unslash( $_POST['seofyme_kp'] ) : array(); // phpcs:ignore
		$clean = array();
		foreach ( array_slice( $raw, 0, self::MAX ) as $row ) {
			$kp = sanitize_text_field( $row['keyphrase'] ?? '' );
			if ( '' === $kp ) {
				continue;
			}
			$clean[] = array(
				'keyphrase' => $kp,
				'synonyms'  => sanitize_text_field( $row['synonyms'] ?? '' ),
			);
		}
		Post_Meta::set( $post_id, Post_Meta::KEYPHRASES, $clean );
	}
}
