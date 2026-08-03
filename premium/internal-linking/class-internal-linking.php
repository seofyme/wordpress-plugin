<?php
/**
 * Internal linking suggestions while writing.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggests relevant internal links from site content.
 */
class Seofyme_Internal_Linking {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'wp_ajax_seofyme_link_suggestions', [ $this, 'ajax_suggestions' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
	}

	/**
	 * Meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		foreach ( get_post_types( [ 'public' => true ], 'names' ) as $type ) {
			add_meta_box(
				'seofyme_internal_linking',
				__( 'Seofyme — Internal linking suggestions', 'seofyme-seo' ),
				[ $this, 'render_meta_box' ],
				$type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		echo '<div id="seofyme-link-suggestions" data-post-id="' . esc_attr( (string) $post->ID ) . '">';
		echo '<p><button type="button" class="button" id="seofyme-refresh-links">' . esc_html__( 'Refresh suggestions', 'seofyme-seo' ) . '</button></p>';
		echo '<ul class="seofyme-suggestion-list"><li>' . esc_html__( 'Click refresh to find related pages to link to.', 'seofyme-seo' ) . '</li></ul>';
		echo '</div>';
	}

	/**
	 * Build suggestions for a post.
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit Limit.
	 * @return array
	 */
	public function suggest( $post_id, $limit = 8 ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}

		$focus = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
		$extra = get_post_meta( $post_id, Seofyme_Multi_Keyphrase::META_KEY, true );
		$terms = [];
		if ( $focus ) {
			$terms[] = $focus;
		}
		if ( is_array( $extra ) ) {
			foreach ( $extra as $row ) {
				if ( ! empty( $row['keyphrase'] ) ) {
					$terms[] = $row['keyphrase'];
				}
			}
		}
		if ( empty( $terms ) ) {
			$terms = array_slice( array_filter( explode( ' ', strtolower( $post->post_title ) ) ), 0, 4 );
		}

		$query_args = [
			'post_type'      => get_post_types( [ 'public' => true ], 'names' ),
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'post__not_in'   => [ $post_id ],
			's'              => implode( ' ', array_slice( $terms, 0, 3 ) ),
			'orderby'        => 'relevance',
			'no_found_rows'  => true,
		];

		$q     = new WP_Query( $query_args );
		$out   = [];
		$body  = strtolower( wp_strip_all_tags( $post->post_content ) );

		foreach ( $q->posts as $candidate ) {
			$url = get_permalink( $candidate );
			// Skip if already linked.
			if ( $url && strpos( $body, strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) !== false ) {
				continue;
			}
			$out[] = [
				'id'    => $candidate->ID,
				'title' => get_the_title( $candidate ),
				'url'   => $url,
				'type'  => $candidate->post_type,
			];
			if ( count( $out ) >= $limit ) {
				break;
			}
		}

		return $out;
	}

	/**
	 * AJAX handler.
	 *
	 * @return void
	 */
	public function ajax_suggestions() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		wp_send_json_success( [ 'suggestions' => $this->suggest( $post_id ) ] );
	}

	/**
	 * REST.
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_route(
			'seofyme/v1',
			'/internal-links/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'permission_callback' => static function ( $req ) {
					return current_user_can( 'edit_post', (int) $req['id'] );
				},
				'callback'            => function ( $req ) {
					return rest_ensure_response( $this->suggest( (int) $req['id'] ) );
				},
			]
		);
	}
}
