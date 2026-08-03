<?php
/**
 * Internal linking suggestions.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\InternalLinking;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggests related pages to link to.
 */
class InternalLinking {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'wp_ajax_seofyme_link_suggestions', array( $this, 'ajax' ) );
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
			add_meta_box( 'seofyme_links', __( 'Internal linking', 'seofyme-seo' ), array( $this, 'render' ), $type, 'side' );
		}
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		echo '<div id="seofyme-link-suggestions" data-post-id="' . esc_attr( (string) $post->ID ) . '">';
		echo '<p><button type="button" class="button" id="seofyme-refresh-links">' . esc_html__( 'Refresh suggestions', 'seofyme-seo' ) . '</button></p>';
		echo '<ul class="seofyme-suggestion-list"></ul></div>';
	}

	/**
	 * Suggest.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function suggest( $post_id ) {
		$post  = get_post( $post_id );
		$terms = array_filter( array( Post_Meta::get( $post_id, Post_Meta::FOCUS_KW ) ) );
		$extra = Post_Meta::get( $post_id, Post_Meta::KEYPHRASES, array() );
		if ( is_array( $extra ) ) {
			foreach ( $extra as $row ) {
				if ( ! empty( $row['keyphrase'] ) ) {
					$terms[] = $row['keyphrase'];
				}
			}
		}
		if ( empty( $terms ) && $post ) {
			$terms = array_slice( preg_split( '/\s+/', strtolower( $post->post_title ) ), 0, 3 );
		}

		$q = new \WP_Query(
			array(
				'post_type'      => get_post_types( array( 'public' => true ), 'names' ),
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'post__not_in'   => array( $post_id ),
				's'              => implode( ' ', array_slice( $terms, 0, 3 ) ),
				'no_found_rows'  => true,
			)
		);

		$out = array();
		foreach ( $q->posts as $candidate ) {
			$out[] = array(
				'id'    => $candidate->ID,
				'title' => get_the_title( $candidate ),
				'url'   => get_permalink( $candidate ),
			);
		}
		return $out;
	}

	/**
	 * AJAX.
	 *
	 * @return void
	 */
	public function ajax() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}
		wp_send_json_success( array( 'suggestions' => $this->suggest( $post_id ) ) );
	}
}
