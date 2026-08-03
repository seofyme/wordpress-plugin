<?php
/**
 * Orphaned content detection.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds published posts with no inbound internal links.
 */
class Seofyme_Orphaned_Content {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
	}

	/**
	 * Find orphaned posts.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function find_orphans( $limit = 50 ) {
		$posts = get_posts(
			[
				'post_type'      => [ 'post', 'page' ],
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			]
		);

		// Build inbound link map from recent content.
		$sources = get_posts(
			[
				'post_type'      => get_post_types( [ 'public' => true ], 'names' ),
				'post_status'    => 'publish',
				'posts_per_page' => 300,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			]
		);

		$linked_ids = [];
		foreach ( $sources as $source_id ) {
			$content = get_post_field( 'post_content', $source_id );
			if ( ! is_string( $content ) || $content === '' ) {
				continue;
			}
			if ( preg_match_all( '#href=["\']([^"\']+)["\']#i', $content, $matches ) ) {
				foreach ( $matches[1] as $href ) {
					$id = url_to_postid( $href );
					if ( $id && (int) $id !== (int) $source_id ) {
						$linked_ids[ $id ] = true;
					}
				}
			}
		}

		$orphans = [];
		foreach ( $posts as $post ) {
			if ( isset( $linked_ids[ $post->ID ] ) ) {
				continue;
			}
			// Home page is never orphaned.
			if ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
				continue;
			}
			$orphans[] = [
				'id'    => $post->ID,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'type'  => $post->post_type,
				'date'  => get_the_date( 'c', $post ),
			];
			if ( count( $orphans ) >= $limit ) {
				break;
			}
		}

		return $orphans;
	}

	/**
	 * REST.
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_route(
			'seofyme/v1',
			'/orphaned',
			[
				'methods'             => 'GET',
				'permission_callback' => static function () {
					return current_user_can( 'edit_others_posts' );
				},
				'callback'            => function () {
					return rest_ensure_response( $this->find_orphans() );
				},
			]
		);
	}
}
